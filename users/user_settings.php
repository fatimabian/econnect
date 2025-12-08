<?php
session_start();
include 'db_connect.php';
include 'header.php';

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$errors = [];
$success = "";

if (isset($_POST['update_settings'])) {
    $username = trim($_POST['username']);
    $street = trim($_POST['street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($street) || empty($barangay) || empty($city) || empty($province)) $errors[] = "Full address is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (!empty($password) && $password !== $confirm_password) $errors[] = "Passwords do not match.";

    // Check for unique username, email, phone
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE (username=? OR email=? OR contact=?) AND id != ?");
    $checkStmt->bind_param("sssi", $username, $email, $phone, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        $errors[] = "Username, email, or phone already exists.";
    }

    if (empty($errors)) {
        // Update query
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET username=?, street=?, barangay=?, city=?, province=?, contact=?, email=?, password=? WHERE id=?");
            $updateStmt->bind_param("ssssssssi", $username, $street, $barangay, $city, $province, $phone, $email, $hashedPassword, $user_id);
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET username=?, street=?, barangay=?, city=?, province=?, contact=?, email=? WHERE id=?");
            $updateStmt->bind_param("sssssssi", $username, $street, $barangay, $city, $province, $phone, $email, $user_id);
        }

        if ($updateStmt->execute()) {
            $success = "Settings updated successfully.";
            // Refresh user data
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update settings. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - ECOnnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3f4a36;
            --secondary-color: #5f7353;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-text: #2c3529;
            --border-color: #e0e0e0;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fa !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-text);
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            margin-top: 80px;
            margin-left: 100px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                padding: 20px 15px;
                margin-top: 70px;
            }
        }

        /* Page Header */
        .page-header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: var(--secondary-color);
            font-size: 1.6rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1rem;
            margin: 0;
        }

        /* Cards */
        .settings-card {
            background: white;
            border-radius: 16px;
            border: none;
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            overflow: hidden;
            transition: var(--transition);
        }

        .settings-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 25px;
            border-bottom: none;
            border-radius: 16px 16px 0 0 !important;
        }

        .card-header-custom h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-custom i {
            font-size: 1.2rem;
        }

        .card-body {
            padding: 25px;
        }

        /* User Info Card */
        .user-info-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 5px solid var(--primary-color);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .user-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark-text);
            margin: 0;
        }

        .user-role {
            color: var(--secondary-color);
            font-weight: 600;
            margin: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }

        .info-item i {
            color: var(--secondary-color);
            width: 20px;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #f8f9fa;
            width: 100%;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(63, 74, 54, 0.25);
            background-color: white;
        }

        /* Button Styles */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            cursor: pointer;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(63, 74, 54, 0.3);
        }

        /* Alerts */
        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .alert-success-custom {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger-custom {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Password toggle */
        .password-toggle {
            background: transparent;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
            padding: 0 10px;
            display: flex;
            align-items: center;
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.4s ease-out;
        }

        /* Grid Layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .main-content {
                padding: 25px;
            }
            
            .page-title {
                font-size: 1.6rem;
            }
            
            .card-body {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
                margin-top: 70px;
            }
            
            .page-title {
                font-size: 1.4rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .user-info-card {
                padding: 15px;
            }
            
            .user-name {
                font-size: 1.2rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-primary-custom {
                padding: 10px 20px;
            }
            
            .user-info-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .user-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px 12px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .card-header-custom {
                padding: 15px 20px;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 0.95rem;
            }
            
            .btn-primary-custom {
                font-size: 0.95rem;
            }
        }

        /* Required field indicator */
        .required:after {
            content: " *";
            color: var(--danger-color);
        }

        /* Password strength indicator */
        .password-strength {
            height: 5px;
            border-radius: 10px;
            margin-top: 8px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .strength-meter {
            height: 100%;
            width: 0%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        /* Input groups for password visibility toggle */
        .input-group {
            display: flex;
            align-items: center;
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            flex: 1;
        }

        .input-group .password-toggle {
            border: 2px solid #e9ecef;
            border-left: none;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            background-color: #f8f9fa;
            height: 100%;
            padding: 0 15px;
        }
    </style>
</head>
<body>

<div class="main-content fade-in-up">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user-cog"></i> User Settings
        </h1>
        <p class="page-subtitle">Manage your profile information and account settings</p>
    </div>

    <!-- Success Messages -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success-custom fade-in-up">
            <i class="fas fa-check-circle fa-lg"></i>
            <div>
                <strong>Success!</strong> <?= htmlspecialchars($success) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger-custom fade-in-up">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <div>
                <strong>Error!</strong>
                <?php foreach($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- User Information Card -->
    <div class="user-info-card fade-in-up">
        <div class="user-info-header">
            <div class="user-avatar">
                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <h3 class="user-name"><?= htmlspecialchars($user['username'] ?? 'User') ?></h3>
                <p class="user-role">Registered User</p>
            </div>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span><strong>Phone:</strong> <?= htmlspecialchars($user['contact'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><strong>Address:</strong> 
                    <?= htmlspecialchars($user['street'] ?? '') ?>, 
                    <?= htmlspecialchars($user['barangay'] ?? '') ?>, 
                    <?= htmlspecialchars($user['city'] ?? '') ?>, 
                    <?= htmlspecialchars($user['province'] ?? '') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="settings-card fade-in-up">
        <div class="card-header-custom">
            <h3><i class="fas fa-user-edit"></i> Edit Profile Information</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="settingsForm" novalidate>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input type="text" 
                               name="username" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['username']) ?>"
                               required
                               placeholder="Enter your username">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-road"></i> Street
                        </label>
                        <input type="text" 
                               name="street" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['street']) ?>"
                               required
                               placeholder="Enter street address">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-map"></i> Barangay
                        </label>
                        <input type="text" 
                               name="barangay" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['barangay']) ?>"
                               required
                               placeholder="Enter barangay">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-city"></i> City
                        </label>
                        <input type="text" 
                               name="city" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['city']) ?>"
                               required
                               placeholder="Enter city">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-landmark"></i> Province
                        </label>
                        <input type="text" 
                               name="province" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['province']) ?>"
                               required
                               placeholder="Enter province">
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" 
                               name="phone" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['contact']) ?>"
                               required
                               placeholder="09123456789"
                               pattern="[0-9]{10,15}">
                        <small class="text-muted">10-15 digits only</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label required">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               value="<?= htmlspecialchars($user['email']) ?>"
                               required
                               placeholder="example@email.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> New Password (leave blank if not changing)
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   name="password" 
                                   id="newPassword"
                                   class="form-control"
                                   placeholder="Enter new password"
                                   oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="password-toggle" data-target="newPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter" id="passwordStrength"></div>
                        </div>
                        <small class="text-muted">At least 8 characters with mixed case, numbers, and special characters</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirmPassword"
                                   class="form-control"
                                   placeholder="Confirm new password">
                            <button type="button" class="password-toggle" data-target="confirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-button mt-4">
                    <button type="submit" 
                            name="update_settings" 
                            class="btn btn-primary-custom"
                            onclick="return validateForm()">
                        <i class="fas fa-save me-2"></i> Update Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Security Notice -->
    <div class="alert alert-warning-custom fade-in-up">
        <i class="fas fa-shield-alt fa-lg"></i>
        <div>
            <strong>Security Notice:</strong> Keep your password secure and never share it with anyone. 
            Make sure to log out after each session, especially on shared computers.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert-custom').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
});

// Password strength checker
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('passwordStrength');
    let strength = 0;
    
    if (!password) {
        strengthBar.style.width = '0%';
        strengthBar.style.backgroundColor = '';
        return;
    }
    
    // Length check
    if (password.length >= 8) strength += 20;
    if (password.length >= 12) strength += 10;
    
    // Contains lowercase
    if (/[a-z]/.test(password)) strength += 20;
    
    // Contains uppercase
    if (/[A-Z]/.test(password)) strength += 20;
    
    // Contains numbers
    if (/[0-9]/.test(password)) strength += 20;
    
    // Contains special characters
    if (/[!@#$%^&*]/.test(password)) strength += 20;
    
    // Cap at 100%
    strength = Math.min(strength, 100);
    strengthBar.style.width = strength + '%';
    
    // Color coding
    if (strength < 40) {
        strengthBar.style.backgroundColor = '#dc3545'; // Red
    } else if (strength < 70) {
        strengthBar.style.backgroundColor = '#ffc107'; // Yellow
    } else if (strength < 90) {
        strengthBar.style.backgroundColor = '#17a2b8'; // Blue
    } else {
        strengthBar.style.backgroundColor = '#28a745'; // Green
    }
}

// Form validation
function validateForm() {
    const form = document.getElementById('settingsForm');
    const password = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    let isValid = true;

    // Clear previous errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    // Check required fields
    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        }
    });

    // Validate email format
    const email = form.querySelector('input[type="email"]');
    if (email && email.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value)) {
            email.classList.add('is-invalid');
            isValid = false;
        }
    }

    // Validate phone format (10-15 digits)
    const phone = form.querySelector('input[name="phone"]');
    if (phone && phone.value) {
        const phoneRegex = /^[0-9]{10,15}$/;
        if (!phoneRegex.test(phone.value)) {
            phone.classList.add('is-invalid');
            isValid = false;
        }
    }

    // Check password match if password field is filled
    if (password.value || confirmPassword.value) {
        if (password.value !== confirmPassword.value) {
            password.classList.add('is-invalid');
            confirmPassword.classList.add('is-invalid');
            isValid = false;
        }
        
        // Check password strength if password is provided
        if (password.value && password.value.length < 8) {
            password.classList.add('is-invalid');
            isValid = false;
        }
    }

    if (!isValid) {
        // Scroll to first error
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
        return false;
    }

    return true;
}
</script>

</body>
</html>
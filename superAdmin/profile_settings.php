<?php
session_start();
include "../db_connect.php";

// ---------------------------
// Authentication Check
// ---------------------------
if (!isset($_SESSION['super_admin_id'])) {
    die("Unauthorized access.");
}

$admin_id = $_SESSION['super_admin_id'];

// ---------------------------
// Fetch Current User Data
// ---------------------------
$stmt = $conn->prepare("SELECT * FROM super_admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ---------------------------
// Initialize Variables
// ---------------------------
$errors = [];
$success = '';
$old = [
    'name' => $user['name'],
    'username' => $user['username'],
    'email' => $user['email'],
    'phone' => $user['phone']
];

// ---------------------------
// Handle Form Submission
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_update'])) {
    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    $old = compact('name', 'username', 'email', 'phone');

    // Validation: Required fields
    if (empty($name) || empty($username) || empty($email) || empty($phone)) {
        $errors['general'] = "All fields except password are required.";
    }

    // Validation: Check uniqueness
    if (empty($errors)) {
        $stmtCheck = $conn->prepare("SELECT id FROM super_admin WHERE (email=? OR username=?) AND id != ?");
        $stmtCheck->bind_param("ssi", $email, $username, $admin_id);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if ($resCheck->num_rows > 0) {
            $errors['general'] = "Email or username is already taken.";
        }
    }

    // Validation: Password match
    if (!empty($password) && $password !== $confirm) {
        $errors['general'] = "Passwords do not match.";
    }

    // Update Database if No Errors
    if (empty($errors)) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $conn->prepare("UPDATE super_admin SET name=?, username=?, email=?, phone=?, password=? WHERE id=?");
            $stmtUpdate->bind_param("sssssi", $name, $username, $email, $phone, $hashedPassword, $admin_id);
        } else {
            $stmtUpdate = $conn->prepare("UPDATE super_admin SET name=?, username=?, email=?, phone=? WHERE id=?");
            $stmtUpdate->bind_param("ssssi", $name, $username, $email, $phone, $admin_id);
        }

        if ($stmtUpdate->execute()) {
            $success = "Profile updated successfully!";
            $user = array_merge($user, $old);
            $old = $user;
        } else {
            $errors['general'] = "Database error: " . $stmtUpdate->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings • Super Admin</title>
    <link rel="stylesheet" href="superAdmin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3f4a36;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition-speed: 0.3s;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%) !important;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .content-area {
            margin-left: 100px;
            padding: 100px 30px 30px;
            transition: margin-left var(--transition-speed) ease;
            min-height: calc(100vh - 70px);
        }
        
        @media (max-width: 768px) {
            .content-area {
                margin-left: 0 !important;
                padding: 100px 20px 20px;
            }
        }
        
        .profile-card {
            border-radius: 14px;
            background: white;
            box-shadow: var(--card-shadow);
            border: none;
            overflow: hidden;
            transition: all var(--transition-speed) ease;
            border-left: 4px solid var(--primary-color);
            position: relative;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), #6d8c54);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
            color: white;
            padding: 25px;
            margin: -1rem -1rem 2rem -1rem;
            border-radius: 14px 14px 0 0;
        }
        
        .form-control {
            border: 2px solid #eef2f7;
            border-radius: 10px;
            padding: 12px 15px;
            transition: var(--transition-speed);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(63, 74, 54, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition-speed);
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(63, 74, 54, 0.3);
            color: white;
            background: linear-gradient(135deg, #5a6c4a 0%, var(--primary-color) 100%);
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f4f8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        
        .input-group-icon {
            position: relative;
        }
        
        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 4;
        }
        
        .input-group-icon .form-control {
            padding-left: 45px;
        }
        
        .password-toggle-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #a0aec0;
            cursor: pointer;
            z-index: 5;
            padding: 5px;
        }
        
        .password-toggle-btn:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 576px) {
            .content-area {
                padding: 100px 15px 15px;
            }
            
            .profile-header {
                padding: 20px;
            }
            
            .form-control {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>

<?php include "header.php"; ?>

<div class="content-area">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <!-- Success/Error Messages -->
                <?php if($success): ?>
                    <div class="alert alert-success alert-custom d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                        <div><?= htmlspecialchars($success) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errors['general'])): ?>
                    <div class="alert alert-danger alert-custom d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                        <div><?= htmlspecialchars($errors['general']) ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Card -->
                <div class="card profile-card p-4">
                    <div class="profile-header">
                        <h3 class="mb-0 fw-bold"><i class="fas fa-user-cog me-2"></i>Profile Settings</h3>
                        <p class="mb-0 opacity-75">Manage your account details and password</p>
                    </div>
                    
                    <form id="profileForm" method="POST">
                        <div class="row">
                            <!-- Personal Information Section -->
                            <div class="col-12 mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-id-card"></i>
                                    Personal Information
                                </h5>
                            </div>
                            
                            <!-- Full Name -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="name" class="form-control"
                                           value="<?= htmlspecialchars($old['name']) ?>" required>
                                </div>
                            </div>
                            
                            <!-- Username -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-at"></i>
                                    <input type="text" name="username" class="form-control"
                                           value="<?= htmlspecialchars($old['username']) ?>" required>
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" class="form-control"
                                           value="<?= htmlspecialchars($old['email']) ?>" required>
                                </div>
                            </div>
                            
                            <!-- Phone -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="text" name="phone" class="form-control"
                                           value="<?= htmlspecialchars($old['phone']) ?>" required>
                                </div>
                            </div>
                            
                            <!-- Password Section -->
                            <div class="col-12 mb-4">
                                <h5 class="section-title">
                                    <i class="fas fa-lock"></i>
                                    Change Password
                                </h5>
                                <p class="text-muted mb-4">Leave blank if you don't want to change your password</p>
                            </div>
                            
                            <!-- New Password -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="password" name="password" class="form-control"
                                           placeholder="Enter new password">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Confirm Password -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group-icon">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                           placeholder="Confirm new password">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordError" class="text-danger small mt-2" style="display:none;">
                                    <i class="fas fa-exclamation-circle me-1"></i>Passwords do not match
                                </div>
                            </div>
                            
                            <!-- Hidden input for update trigger -->
                            <input type="hidden" name="confirm_update" value="1">
                            
                            <!-- Action Button -->
                            <div class="col-12 mt-4">
                                <button type="button" class="btn btn-primary py-3" id="saveBtn">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--primary-color);" id="confirmModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Confirm Update
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-triangle fa-3x" style="color: var(--warning-color); margin-bottom: 15px;"></i>
                    <h5 class="fw-semibold mb-2">Are you sure?</h5>
                    <p class="text-muted mb-0">You're about to update your profile information.</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary px-4" id="confirmYes">
                    <i class="fas fa-check me-2"></i>Yes, Update
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fixed toggle password function
    function togglePassword(fieldId, button) {
        const field = document.getElementById(fieldId);
        const icon = button.querySelector('i');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Password match validation
    document.getElementById('saveBtn').addEventListener('click', function() {
        const pw = document.getElementById('password').value;
        const cpw = document.getElementById('confirm_password').value;
        const errorMsg = document.getElementById('passwordError');
        
        // Validate required fields
        const requiredFields = ['name', 'username', 'email', 'phone'];
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Validate password match
        if (pw !== cpw) {
            errorMsg.style.display = 'block';
            document.getElementById('confirm_password').classList.add('is-invalid');
            alert('Passwords do not match');
            return;
        } else {
            errorMsg.style.display = 'none';
            document.getElementById('confirm_password').classList.remove('is-invalid');
        }
        
        // Show confirmation modal
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    });
    
    // Submit form when confirmed
    document.getElementById('confirmYes').addEventListener('click', function() {
        document.getElementById('profileForm').submit();
    });
    
    // Real-time password match validation
    document.getElementById('confirm_password').addEventListener('input', function() {
        const pw = document.getElementById('password').value;
        const cpw = this.value;
        const errorMsg = document.getElementById('passwordError');
        
        if (pw && cpw && pw !== cpw) {
            errorMsg.style.display = 'block';
            this.classList.add('is-invalid');
        } else {
            errorMsg.style.display = 'none';
            this.classList.remove('is-invalid');
        }
    });
    
    // Real-time validation for required fields
    document.querySelectorAll('input[required]').forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
</script>

</body>
</html>
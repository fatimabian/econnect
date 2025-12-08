<?php
session_start();
include "db_connect.php";

// ==========================
// CHECK IF ADMIN IS LOGGED IN
// ==========================
if (!isset($_SESSION['barangay_admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['barangay_admin_id'];

// ==========================
// FETCH ADMIN DATA
// ==========================
$stmt = $conn->prepare("SELECT full_name, barangay, username, email, phone_number, status FROM barangay_admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin not found.");
}

$admin_barangay = $admin['barangay'];

// ==========================
// HANDLE PROFILE UPDATE
// ==========================
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirmUpdate'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);

    // Debug: Show what we received
    error_log("Profile Update Attempt - Full Name: $full_name, Email: $email, Phone: $phone, Admin ID: $admin_id");

    // Validate inputs
    if (empty($full_name)) {
        $errors['full_name'] = "Name cannot be empty.";
    } elseif (strlen($full_name) < 2) {
        $errors['full_name'] = "Name must be at least 2 characters.";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email cannot be empty.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }
    
    if (!empty($phone) && !preg_match("/^[0-9]{10,15}$/", $phone)) {
        $errors['phone_number'] = "Phone number must be 10-15 digits.";
    }

    // Check if email already exists (excluding current admin)
    if (empty($errors['email'])) {
        $check_email = $conn->prepare("SELECT id FROM barangay_admins WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $admin_id);
        $check_email->execute();
        $check_email->store_result();
        
        if ($check_email->num_rows > 0) {
            $errors['email'] = "Email already exists.";
        }
        $check_email->close();
    }

    // Update database if no errors
    if (empty($errors)) {
        // Remove updated_at since the column doesn't exist in your database
        $sql = "UPDATE barangay_admins SET full_name=?, email=?, phone_number=? WHERE id=?";
        error_log("SQL Query: $sql");
        error_log("Parameters: $full_name, $email, $phone, $admin_id");
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $full_name, $email, $phone, $admin_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = "Profile updated successfully.";
                $admin['full_name'] = $full_name;
                $admin['email'] = $email;
                $admin['phone_number'] = $phone;
                
                // Update session
                $_SESSION['full_name'] = $full_name;
                
                // Refresh the page to show updated data
                echo '<script>window.location.href = window.location.href;</script>';
                exit();
            } else {
                $errors['general'] = "No changes were made. Please check if you entered new information.";
            }
        } else {
            $errors['general'] = "Database error: " . $stmt->error;
            error_log("Database Error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        error_log("Validation Errors: " . print_r($errors, true));
    }
}

// ==========================
// HANDLE PASSWORD CHANGE (WITHOUT CURRENT PASSWORD)
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['changePassword'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $password_errors = [];
    
    // Validate new password
    if (strlen($new_password) < 8) {
        $password_errors['new_password'] = "Password must be at least 8 characters.";
    } elseif (!preg_match("/[A-Z]/", $new_password)) {
        $password_errors['new_password'] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match("/[a-z]/", $new_password)) {
        $password_errors['new_password'] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match("/[0-9]/", $new_password)) {
        $password_errors['new_password'] = "Password must contain at least one number.";
    } elseif (!preg_match("/[!@#$%^&*]/", $new_password)) {
        $password_errors['new_password'] = "Password must contain at least one special character (!@#$%^&*).";
    }
    
    if ($new_password !== $confirm_password) {
        $password_errors['confirm_password'] = "Passwords do not match.";
    }
    
    if (empty($password_errors)) {
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        // Remove updated_at from password update since column doesn't exist
        $stmt = $conn->prepare("UPDATE barangay_admins SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password_hash, $admin_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $password_success = "Password changed successfully. You will be logged out in 5 seconds.";
                // Set a session variable to show logout message
                $_SESSION['password_changed'] = true;
                
                // Redirect after showing message
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "logout.php";
                    }, 5000);
                </script>';
            } else {
                $password_errors['general'] = "No changes were made to the password.";
            }
        } else {
            $password_errors['general'] = "Failed to update password: " . $stmt->error;
            error_log("Password Update Error: " . $stmt->error);
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Settings - ECOnnect</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background-color: #f5f7fa !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--dark-text);
    min-height: 100vh;
}

/* Content Area */
.content-area {
    margin-left: 270px;
    padding: 30px;
    margin-top: 90px;
    transition: margin-left 0.3s ease;
    min-height: calc(100vh - 90px);
}

.sidebar.collapsed ~ .content-area {
    margin-left: 100px;
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

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 1rem;
    transition: var(--transition);
    background-color: #f8f9fa;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(63, 74, 54, 0.25);
    background-color: white;
}

.input-group-text {
    background-color: var(--light-bg);
    border: 2px solid #e9ecef;
    border-right: none;
    color: var(--secondary-color);
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
}

.btn-primary-custom:hover {
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(63, 74, 54, 0.3);
}

.btn-outline-custom {
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    background: transparent;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    transition: var(--transition);
}

.btn-outline-custom:hover {
    background: var(--primary-color);
    color: white;
}

/* Admin Info Card */
.admin-info-card {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-left: 5px solid var(--primary-color);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.admin-info-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.admin-avatar {
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

.admin-details {
    flex: 1;
}

.admin-name {
    font-size: 1.4rem;
    font-weight: 700;
    color: var(--dark-text);
    margin: 0;
}

.admin-role {
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

/* Status Badge */
.status-badge {
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-active {
    background-color: rgba(40, 167, 69, 0.15);
    color: var(--success-color);
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-inactive {
    background-color: rgba(108, 117, 125, 0.15);
    color: #6c757d;
    border: 1px solid rgba(108, 117, 125, 0.3);
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

.alert-warning-custom {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border-left: 4px solid var(--warning-color);
}

.alert-info-custom {
    background-color: rgba(23, 162, 184, 0.1);
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

/* Progress Bar for Password */
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

/* Modal */
.modal-content {
    border-radius: 16px;
    border: none;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header-custom {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 20px 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-title {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-body {
    padding: 25px;
}

.modal-footer-custom {
    padding: 20px 25px;
    border-top: 1px solid var(--border-color);
    background-color: var(--light-bg);
}

/* Responsive Design */
@media (max-width: 992px) {
    .content-area {
        margin-left: 240px;
        padding: 25px;
    }
    
    .sidebar.collapsed ~ .content-area {
        margin-left: 90px;
    }
    
    .page-title {
        font-size: 1.6rem;
    }
}

@media (max-width: 768px) {
    .content-area {
        margin-left: 20px !important;
        margin-top: 80px;
        padding: 20px 15px;
    }
    
    .page-title {
        font-size: 1.4rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .admin-info-card {
        padding: 15px;
    }
    
    .admin-name {
        font-size: 1.2rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-primary-custom {
        padding: 10px 20px;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-header-custom,
    .modal-footer-custom {
        padding: 15px 20px;
    }
}

@media (max-width: 576px) {
    .content-area {
        padding: 15px 12px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .card-header-custom {
        padding: 15px 20px;
    }
    
    .form-control, .form-select {
        padding: 10px 12px;
        font-size: 0.95rem;
    }
    
    .btn-primary-custom {
        font-size: 0.95rem;
    }
    
    .admin-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .modal-body {
        padding: 15px;
    }
}

/* Animation for success messages */
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

/* Password toggle button */
.password-toggle {
    background: transparent;
    border: none;
    color: var(--secondary-color);
    cursor: pointer;
    padding: 0 10px;
    display: flex;
    align-items: center;
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Required field indicator */
.required:after {
    content: " *";
    color: var(--danger-color);
}

/* Logout countdown */
.logout-countdown {
    font-weight: bold;
    color: var(--primary-color);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* Fix for hidden inputs in forms */
.hidden-submit {
    display: none;
}
</style>
</head>
<body>

<?php 
// Use the new admin header you created
include "header.php"; 
?>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<div class="content-area fade-in-up">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user-cog"></i> Admin Settings
            <span class="badge bg-light text-dark ms-2">
                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($admin_barangay) ?>
            </span>
        </h1>
        <p class="page-subtitle">Manage your profile information and security settings</p>
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

    <?php if (!empty($password_success)): ?>
        <div class="alert alert-info-custom fade-in-up">
            <i class="fas fa-key fa-lg"></i>
            <div>
                <strong>Password Changed!</strong> <?= htmlspecialchars($password_success) ?>
                <div class="mt-2">
                    <small>You will be automatically logged out in <span class="logout-countdown">5</span> seconds for security.</small>
                </div>
            </div>
        </div>
        <script>
            // Countdown timer for logout
            let countdown = 5;
            const countdownElement = document.querySelector('.logout-countdown');
            const countdownInterval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    window.location.href = 'logout.php';
                }
            }, 1000);
        </script>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-danger-custom fade-in-up">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <div>
                <strong>Error!</strong> <?= htmlspecialchars($errors['general']) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($password_errors['general'])): ?>
        <div class="alert alert-danger-custom fade-in-up">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <div>
                <strong>Error!</strong> <?= htmlspecialchars($password_errors['general']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Admin Information Card -->
    <div class="admin-info-card fade-in-up">
        <div class="admin-info-header">
            <div class="admin-avatar">
                <?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="admin-details">
                <h3 class="admin-name"><?= htmlspecialchars($admin['full_name'] ?? 'Admin Name') ?></h3>
                <p class="admin-role">Barangay Administrator</p>
            </div>
            <span class="status-badge <?= ($admin['status'] === 'Active') ? 'status-active' : 'status-inactive' ?>">
                <i class="fas <?= ($admin['status'] === 'Active') ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                <?= htmlspecialchars($admin['status'] ?? 'Inactive') ?>
            </span>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <i class="fas fa-user"></i>
                <span><strong>Username:</strong> <?= htmlspecialchars($admin['username'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span><strong>Email:</strong> <?= htmlspecialchars($admin['email'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-phone"></i>
                <span><strong>Phone:</strong> <?= htmlspecialchars($admin['phone_number'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><strong>Barangay:</strong> <?= htmlspecialchars($admin_barangay) ?></span>
            </div>
        </div>
    </div>

    <!-- Profile Update Form -->
    <div class="settings-card fade-in-up">
        <div class="card-header-custom">
            <h3><i class="fas fa-user-edit"></i> Edit Profile Information</h3>
        </div>
        <div class="card-body">
            <form id="updateForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                <input type="hidden" name="confirmUpdate" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input type="text" 
                               class="form-control <?= !empty($errors['full_name']) ? 'is-invalid' : '' ?>" 
                               name="full_name" 
                               value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" 
                               required
                               placeholder="Enter your full name">
                        <?php if (!empty($errors['full_name'])): ?>
                            <div class="invalid-feedback d-block">
                                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($errors['full_name']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input type="email" 
                               class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" 
                               name="email" 
                               value="<?= htmlspecialchars($admin['email'] ?? '') ?>" 
                               required
                               placeholder="example@email.com">
                        <?php if (!empty($errors['email'])): ?>
                            <div class="invalid-feedback d-block">
                                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($errors['email']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" 
                               class="form-control <?= !empty($errors['phone_number']) ? 'is-invalid' : '' ?>" 
                               name="phone_number" 
                               value="<?= htmlspecialchars($admin['phone_number'] ?? '') ?>" 
                               placeholder="09123456789"
                               pattern="[0-9]{10,15}">
                        <?php if (!empty($errors['phone_number'])): ?>
                            <div class="invalid-feedback d-block">
                                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($errors['phone_number']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Barangay
                        </label>
                        <input type="text" 
                               class="form-control" 
                               value="<?= htmlspecialchars($admin_barangay) ?>" 
                               disabled
                               style="background-color: #e9ecef;">
                    </div>

                    <div class="col-12 mt-2">
                        <button type="button" 
                                class="btn btn-primary-custom" 
                                onclick="validateAndShowUpdateModal()">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </div>
                
                <!-- Hidden submit button for the modal -->
                <button type="submit" id="hiddenUpdateSubmit" class="hidden-submit"></button>
            </form>
        </div>
    </div>

    <!-- Change Password Form -->
    <div class="settings-card fade-in-up">
        <div class="card-header-custom">
            <h3><i class="fas fa-lock"></i> Change Password</h3>
        </div>
        <div class="card-body">
            <form id="passwordForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
                <input type="hidden" name="changePassword" value="1">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required">
                            <i class="fas fa-lock"></i> New Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control <?= !empty($password_errors['new_password']) ? 'is-invalid' : '' ?>" 
                                   name="new_password" 
                                   id="newPassword"
                                   required
                                   placeholder="Enter new password"
                                   oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-target="newPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter" id="passwordStrength"></div>
                        </div>
                        <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                        <?php if (!empty($password_errors['new_password'])): ?>
                            <div class="invalid-feedback d-block">
                                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($password_errors['new_password']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">
                            <i class="fas fa-lock"></i> Confirm New Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control <?= !empty($password_errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                   name="confirm_password" 
                                   id="confirmPassword"
                                   required
                                   placeholder="Confirm new password">
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-target="confirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <?php if (!empty($password_errors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block">
                                <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($password_errors['confirm_password']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12 mt-2">
                        <button type="button" 
                                class="btn btn-primary-custom" 
                                onclick="validateAndShowPasswordModal()">
                            <i class="fas fa-key me-2"></i> Change Password
                        </button>
                    </div>
                </div>
                
                <!-- Hidden submit button for the modal -->
                <button type="submit" id="hiddenPasswordSubmit" class="hidden-submit"></button>
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

<!-- Confirmation Modal for Profile Update -->
<div class="modal fade" id="confirmUpdateModal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-circle me-2"></i> Confirm Profile Update
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-user-edit text-primary fa-4x mb-3"></i>
                <h5 class="mb-3">Update Profile Information?</h5>
                <p class="text-muted">Are you sure you want to update your profile information?</p>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary-custom" onclick="submitUpdateForm()">
                    <i class="fas fa-check me-2"></i> Yes, Update Profile
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Password Change -->
<div class="modal fade" id="confirmPasswordModal" tabindex="-1" aria-labelledby="confirmPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header-custom">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-circle me-2"></i> Change Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-lock text-primary fa-4x mb-3"></i>
                <h5 class="mb-3">Change Your Password?</h5>
                <p class="text-muted">You will be logged out after changing your password for security reasons.</p>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary-custom" onclick="submitPasswordForm()">
                    <i class="fas fa-key me-2"></i> Yes, Change Password
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

    // Password strength checker
    window.checkPasswordStrength = function(password) {
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
    };

    // Validate update form
    window.validateAndShowUpdateModal = function() {
        const form = document.getElementById('updateForm');
        const fullName = form.querySelector('input[name="full_name"]');
        const email = form.querySelector('input[name="email"]');
        
        let isValid = true;
        
        // Reset errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
        
        // Validate full name
        if (!fullName.value.trim()) {
            showFieldError(fullName, 'Full name is required');
            isValid = false;
        } else if (fullName.value.trim().length < 2) {
            showFieldError(fullName, 'Full name must be at least 2 characters');
            isValid = false;
        }
        
        // Validate email
        if (!email.value.trim()) {
            showFieldError(email, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            showFieldError(email, 'Please enter a valid email address');
            isValid = false;
        }
        
        if (isValid) {
            const modal = new bootstrap.Modal(document.getElementById('confirmUpdateModal'));
            modal.show();
        }
    };
    
    // Validate password form
    window.validateAndShowPasswordModal = function() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        
        let isValid = true;
        
        // Reset errors
        newPassword.classList.remove('is-invalid');
        confirmPassword.classList.remove('is-invalid');
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            if (el.parentNode && el.parentNode.contains(newPassword) || el.parentNode.contains(confirmPassword)) {
                el.remove();
            }
        });
        
        // Validate new password
        if (!newPassword.value) {
            showFieldError(newPassword, 'New password is required');
            isValid = false;
        } else if (newPassword.value.length < 8) {
            showFieldError(newPassword, 'Password must be at least 8 characters');
            isValid = false;
        } else if (!/[A-Z]/.test(newPassword.value)) {
            showFieldError(newPassword, 'Password must contain at least one uppercase letter');
            isValid = false;
        } else if (!/[a-z]/.test(newPassword.value)) {
            showFieldError(newPassword, 'Password must contain at least one lowercase letter');
            isValid = false;
        } else if (!/[0-9]/.test(newPassword.value)) {
            showFieldError(newPassword, 'Password must contain at least one number');
            isValid = false;
        } else if (!/[!@#$%^&*]/.test(newPassword.value)) {
            showFieldError(newPassword, 'Password must contain at least one special character (!@#$%^&*)');
            isValid = false;
        }
        
        // Validate confirmation
        if (!confirmPassword.value) {
            showFieldError(confirmPassword, 'Please confirm your password');
            isValid = false;
        } else if (newPassword.value !== confirmPassword.value) {
            showFieldError(confirmPassword, 'Passwords do not match');
            isValid = false;
        }
        
        if (isValid) {
            const modal = new bootstrap.Modal(document.getElementById('confirmPasswordModal'));
            modal.show();
        }
    };
    
    function showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i> ${message}`;
        field.parentNode.appendChild(errorDiv);
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Submit forms
    window.submitUpdateForm = function() {
        showLoading();
        document.getElementById('hiddenUpdateSubmit').click();
    };
    
    window.submitPasswordForm = function() {
        showLoading();
        document.getElementById('hiddenPasswordSubmit').click();
    };

    // Loading overlay
    function showLoading() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
    
    function hideLoading() {
        document.getElementById('loadingOverlay').style.display = 'none';
    }
    
    // Hide loading when page is fully loaded
    window.addEventListener('load', hideLoading);
    
    // Auto-hide alerts after 5 seconds (except password success)
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            if (!alert.querySelector('.logout-countdown')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);

    // Real-time validation
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
        
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
            // Remove error message when user starts typing
            const errorDiv = this.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.remove();
            }
        });
    });
});
</script>
</body>
</html>
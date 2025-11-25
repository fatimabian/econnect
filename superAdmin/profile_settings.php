<?php
session_start();
include "../db_connect.php";

// ---------------------------
// Check login
// ---------------------------
if (!isset($_SESSION['super_admin_id'])) {
    die("Unauthorized access.");
}

$admin_id = $_SESSION['super_admin_id'];

// ---------------------------
// Fetch user
// ---------------------------
$stmt = $conn->prepare("SELECT * FROM super_admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// ---------------------------
// Initialize variables
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
// Handle form submission
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_update'])) {

    $name     = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    $old = compact('name','username','email','phone');

    // Required fields
    if ($name === '' || $username === '' || $email === '' || $phone === '') {
        $errors['general'] = "All fields except password are required.";
    }

    // Check uniqueness
    $stmtCheck = $conn->prepare("SELECT id FROM super_admin WHERE (email=? OR username=?) AND id != ?");
    $stmtCheck->bind_param("ssi", $email, $username, $admin_id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    if ($resCheck->num_rows > 0) {
        $errors['general'] = "Email or Username is already taken.";
    }

    // Update if no errors
    if (empty($errors)) {
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $conn->prepare("UPDATE super_admin SET name=?, username=?, email=?, phone=?, password=? WHERE id=?");
            $stmtUpdate->bind_param("sssssi", $name, $username, $email, $phone, $hashedPassword, $admin_id);
        } else {
            $stmtUpdate = $conn->prepare("UPDATE super_admin SET name=?, username=?, email=?, phone=? WHERE id=?");
            $stmtUpdate->bind_param("ssssi", $name, $username, $email, $phone, $admin_id);
        }

        if ($stmtUpdate->execute()) {
            $success = "Profile updated successfully!";
            $user = array_merge($user, $old); // update user info for form
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
<title>Super Admin • Profile Settings</title>
<link rel="stylesheet" href="superAdmin.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: rgba(68,64,51,0.4) !important; }
.content-area { margin-left: 260px; padding: 40px; }
@media (max-width: 992px) { .content-area { margin-left: 0; padding: 20px; } }
.profile-card { border-radius: 15px; background: #ffffff; }
</style>
</head>
<body>

<?php include "header.php"; ?>

<div class="content-area">
    <h3 class="mb-4 fw-bold">Profile Settings</h3>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12">

                <?php if($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if(isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                <?php endif; ?>

                <div class="card p-4 shadow-sm profile-card">
                    <form id="profileForm" method="POST">
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($old['name']) ?>" required>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control"
                                   value="<?= htmlspecialchars($old['username']) ?>" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($old['email']) ?>" required>
                        </div>

                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= htmlspecialchars($old['phone']) ?>" required>
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">Change Password</h6>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="Enter new password">
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                   placeholder="Re-enter new password">
                            <small id="passwordError" class="text-danger" style="display:none;">Passwords do not match.</small>
                        </div>

                        <!-- Hidden input to trigger PHP update -->
                        <input type="hidden" name="confirm_update" value="1">

                        <button type="button" class="btn btn-success w-100 fw-semibold py-2" id="saveBtn">
                            Save Changes
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalLabel">Confirm Update</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to update your profile?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
        <button type="button" class="btn btn-primary" id="confirmYes">Yes</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Show modal only if passwords match
document.getElementById('saveBtn').addEventListener('click', function() {
    const pw = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const errorMsg = document.getElementById('passwordError');

    if(pw !== cpw) {
        errorMsg.style.display = 'block';
        return; // do not show modal
    } else {
        errorMsg.style.display = 'none';
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    }
});

// Submit form when 'Yes' is clicked
document.getElementById('confirmYes').addEventListener('click', function() {
    document.getElementById('profileForm').submit();
});
</script>

<?php include "../footer.php"; ?>
</body>
</html>

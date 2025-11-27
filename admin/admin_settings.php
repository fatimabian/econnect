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
$stmt = $conn->prepare("SELECT full_name, barangay, username, email, phone_number FROM barangay_admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin not found.");
}

$admin_barangay = $admin['barangay']; // <-- dynamically get admin barangay

// ==========================
// HANDLE PROFILE UPDATE
// ==========================
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['confirmUpdate'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);

    // Validate inputs
    if (empty($full_name)) $errors['full_name'] = "Name cannot be empty.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format.";
    if (!preg_match("/^[0-9]{10,15}$/", $phone)) $errors['phone_number'] = "Invalid phone number.";

    // Update database if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE barangay_admins SET full_name=?, email=?, phone_number=? WHERE id=?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $admin_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $admin['full_name'] = $full_name;
            $admin['email'] = $email;
            $admin['phone_number'] = $phone;
        } else {
            $errors['general'] = "Database error: " . $stmt->error;
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body { 
    background: rgba(68,64,51,0.4) !important;
    font-family: Arial, sans-serif;
    padding-top: 70px;
}
.main-content { 
    margin-left: 100px; 
    padding: 20px; 
}
.settings-container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    max-width: 700px;
    margin-top: 30px;
}
h3 { 
    color: #3f4a36; 
    font-weight: 600; 
    margin-bottom: 20px; 
}
.form-label { 
    font-weight: 600; 
}
.error-msg { 
    color: red; 
    font-size: 0.9rem; 
}
.success-msg { 
    color: green; 
    font-size: 1rem; 
    font-weight: 600; 
    margin-bottom: 10px; 
}
button.btn-update {
    background-color: #3f4a36 !important;
    color: white !important; 
    width: 100%;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    transition: 0.3s;
}
button.btn-update:hover {
    background-color: #2f3927 !important;
    color: #fff !important;
}
.modal-header {
    background-color: #3f4a36;
    color: #fff;
}
</style>
</head>
<body>

<?php include "header.php"; ?>

<div class="main-content">
     <h2 class="fw mb-4">Admin Settings - <?= htmlspecialchars($admin_barangay) ?></h2>
    <div class="settings-container">
        
        <?php if (!empty($success)): ?>
            <p class="success-msg"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <p class="error-msg"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form id="updateForm" method="POST">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required>
                <?php if (!empty($errors['full_name'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['full_name']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control" name="phone_number" value="<?= htmlspecialchars($admin['phone_number'] ?? '') ?>" required>
                <?php if (!empty($errors['phone_number'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['phone_number']) ?></p>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-update" data-bs-toggle="modal" data-bs-target="#confirmModal">
                <i class="bi bi-pencil-square"></i> Update Profile
            </button>

            <!-- Confirmation Modal -->
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Update</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    Are you sure you want to update your profile information?
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="confirmUpdate" class="btn btn-success"><i class="bi bi-check-circle"></i> Yes, Update</button>
                  </div>
                </div>
              </div>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

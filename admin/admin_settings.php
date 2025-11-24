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

$admin_id = $_SESSION['barangay_admin_id']; // use correct session key


// ==========================
// FETCH ADMIN DATA
// ==========================
$stmt = $conn->prepare("SELECT full_name, barangay, username, email FROM barangay_admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin not found.");
}

// ==========================
// HANDLE PROFILE UPDATE
// ==========================
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['updateProfile'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Validate passwords
    if (!empty($password) && $password !== $confirm_password) {
        $errors['password'] = "Passwords do not match.";
    }

    // Update database if no errors
    if (empty($errors)) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE barangay_admins SET email=?, password=? WHERE id=?");
            $stmt->bind_param("ssi", $email, $hashedPassword, $admin_id);
        } else {
            $stmt = $conn->prepare("UPDATE barangay_admins SET email=? WHERE id=?");
            $stmt->bind_param("si", $email, $admin_id);
        }

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $admin['email'] = $email; // update current data
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
<style>
<style>
body { 
    background-color: #f7f7f7; 
    font-family: Arial, sans-serif; 
}

.main-content { 
    margin-left: 260px; 
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
    background-color: #3f4a36 !important; /* solid green */
    color: white !important; 
    width: 100%;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    transition: 0.3s;
}

button.btn-update:hover {
    background-color: #2f3927 !important; /* slightly darker on hover */
    color: #fff !important;
}

</style>
</head>
<body>

<?php include "header.php"; ?>
<?php include "nav.php"; ?>

<div class="main-content">
    <div class="settings-container">
        <h3>Admin Settings</h3>

        <?php if (!empty($success)): ?>
            <p class="success-msg"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <p class="error-msg"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Barangay</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($admin['barangay'] ?? '') ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="password" placeholder="Leave blank if not changing">
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm new password">
                <?php if (!empty($errors['password'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" name="updateProfile" class="btn btn-update">Update Profile</button>
        </form>
    </div>
</div>

</body>
</html>

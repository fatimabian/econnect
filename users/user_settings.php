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

<div class="settings-wrapper">
    <h2>User Settings</h2>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error) echo "<p>$error</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <p><?= $success ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>">
        </div>

        <div class="form-group">
            <label>Street</label>
            <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($user['street']) ?>">
        </div>

        <div class="form-group">
            <label>Barangay</label>
            <input type="text" name="barangay" class="form-control" value="<?= htmlspecialchars($user['barangay']) ?>">
        </div>

        <div class="form-group">
            <label>City</label>
            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city']) ?>">
        </div>

        <div class="form-group">
            <label>Province</label>
            <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($user['province']) ?>">
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['contact']) ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <div class="form-group">
            <label>New Password (leave blank if not changing)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control">
        </div>

        <div class="form-button">
            <button type="submit" name="update_settings" class="btn btn-primary">Update Settings</button>
        </div>
    </form>
</div>

<style>
.settings-wrapper {
    margin-left: 250px; /* if you have a sidebar */
    padding: 90px 20px 100px 20px; /* top padding >= header height (70px) */
    background-color: #f5f5f5;
    min-height: 100vh;
}
.form-group {
    margin-bottom: 15px;
}
.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
textarea.form-control {
    resize: vertical;
}
.alert {
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.alert-danger { background-color: #f8d7da; color: #721c24; }
.alert-success { background-color: #d4edda; color: #155724; }
.form-button {
    text-align: right;
    margin-top: 20px;
}
</style>

<?php include 'footer.php'; ?>

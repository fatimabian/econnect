<?php
session_start();
include "../db_connect.php"; // adjust path if needed

// Check if user is logged in
if (!isset($_SESSION['super_admin_id'])) {
    die("Unauthorized access.");
}

$admin_id = $_SESSION['super_admin_id'];

// Get form inputs
$name     = trim($_POST['name']);
$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$phone    = trim($_POST['phone']);
$password = trim($_POST['password']);
$confirm  = trim($_POST['confirm_password']);

// Validate required fields
if (empty($name) || empty($username) || empty($email) || empty($phone)) {
    die("All fields except password are required.");
}

// Check if email or username already exists (exclude current user)
$checkQuery = $conn->prepare("SELECT id FROM super_admin WHERE (email=? OR username=?) AND id != ?");
$checkQuery->bind_param("ssi", $email, $username, $admin_id);
$checkQuery->execute();
$checkResult = $checkQuery->get_result();

if ($checkResult->num_rows > 0) {
    die("Email or Username is already taken.");
}

// Update query
if ($password != "") {

    // Check passwords match
    if ($password !== $confirm) {
        die("Passwords do not match.");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE super_admin SET name=?, username=?, email=?, phone=?, password=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $username, $email, $phone, $hashedPassword, $admin_id);

} else {

    // Update without password
    $stmt = $conn->prepare("UPDATE super_admin SET name=?, username=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $username, $email, $phone, $admin_id);
}

// Execute the statement
if ($stmt->execute()) {
    echo "
    <script>
        alert('Profile Updated Successfully!');
        window.location.href = 'profile_settings.php';
    </script>
    ";
} else {
    die("Error updating profile: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>

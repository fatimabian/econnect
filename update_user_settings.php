<?php
session_start();
include '../db_connect.php'; // Correct path
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Sanitize inputs
$fname = $_POST['fname'];
$mname = $_POST['mname'];
$lname = $_POST['lname'];
$suffix = $_POST['suffix'];
$email = $_POST['email'];
$contact = $_POST['contact'];
$street = $_POST['street'];
$barangay = $_POST['barangay'];
$city = $_POST['city'];
$province = $_POST['province'];
$region = $_POST['region'];
$zip = $_POST['zip'];

// Password fields
$last_password = $_POST['last_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Fetch current password
$sql = "SELECT password FROM users WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Flag to update password
$update_password = false;

if (!empty($last_password) || !empty($new_password) || !empty($confirm_password)) {
    if (!password_verify($last_password, $user['password'])) {
        die("Current password is incorrect.");
    }
    if ($new_password !== $confirm_password) {
        die("New password and confirm password do not match.");
    }
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update_password = true;
}

// Build update query
if ($update_password) {
    $sql = "UPDATE users SET fname=?, mname=?, lname=?, suffix=?, email=?, contact=?, street=?, barangay=?, city=?, province=?, region=?, zip=?, password=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssi", $fname, $mname, $lname, $suffix, $email, $contact, $street, $barangay, $city, $province, $region, $zip, $hashed_password, $user_id);
} else {
    $sql = "UPDATE users SET fname=?, mname=?, lname=?, suffix=?, email=?, contact=?, street=?, barangay=?, city=?, province=?, region=?, zip=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssi", $fname, $mname, $lname, $suffix, $email, $contact, $street, $barangay, $city, $province, $region, $zip, $user_id);
}

if ($stmt->execute()) {
    header("Location: user_settings.php?success=1");
    exit;
} else {
    echo "Error updating profile: " . $conn->error;
}
?>

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "../db_connect.php"; // adjust path if needed

// Check if any admin is logged in
$username = "";

if (isset($_SESSION['super_admin_id'])) {
    $id = $_SESSION['super_admin_id'];
    $stmt = $conn->prepare("SELECT username FROM super_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $username = $row['username'];
    }
} elseif (isset($_SESSION['barangay_admin_id'])) {
    $id = $_SESSION['barangay_admin_id'];
    $stmt = $conn->prepare("SELECT username FROM barangay_admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()){
        $username = $row['username'];
    }
} else {
    // Not logged in
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="header">
    <div class="logo">
        <img src="../img/logoo.png" alt="Logo">
        <span>Welcome, <?php echo htmlspecialchars($username); ?>!</span>
    </div>

    <div class="header-right">
        <div class="current-time"><?php echo date("Y-m-d H:i:s"); ?></div>
        <button class="logout-btn" onclick="location.href='logout.php'">Log Out</button>
    </div>
</div>

</body>
</html>

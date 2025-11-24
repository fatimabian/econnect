<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include "../db_connect.php"; // adjust path as needed

$username = "";

// Check which admin is logged in
if (isset($_SESSION['super_admin_id'])) {
    $id = $_SESSION['super_admin_id'];
    $stmt = $conn->prepare("SELECT username FROM super_admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        $username = $row['username'];
    }
    $stmt->close();
} elseif (isset($_SESSION['barangay_admin_id'])) {
    $id = $_SESSION['barangay_admin_id'];
    $stmt = $conn->prepare("SELECT username FROM barangay_admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        $username = $row['username'];
    }
    $stmt->close();
} else {
    // Not logged in, redirect to login
    header("Location: ../login.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header</title>
    
<style>
    /* HEADER */
.header {
    width: 100%;
    background-color: #3f4a36;
    color: white;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 9999;
    height: 60px;
}

.header .logo {
    display: flex;
    align-items: center;
    gap: 8px;
}

.header img {
    height: 35px;
    border-radius: 50%;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.logout-btn {
    color: white;
    background: transparent;
    border: 1px solid white;
    border-radius: 8px;
    padding: 5px 12px;
    text-decoration: none;
    font-weight: 500;
}

.logout-btn:hover {
    background: white;
    color: #3f4a36;
    transition: 0.3s ease;
}
</style>
</head>
<body>

<div class="header">
    <div class="logo">
        <img src="../img/logoo.png" alt="Logo">
        <span>Welcome, Super Admin!</span>
    </div>

    <div class="header-right">
        <div class="current-time"><?php echo date("Y-m-d H:i:s"); ?></div>
        <button class="logout-btn" onclick="location.href='logout.php'">Log Out</button>
    </div>
</div>


</body>
</html>

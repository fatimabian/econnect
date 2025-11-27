<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "../db_connect.php";

// ---------------------------
// CHECK IF BARANGAY ADMIN IS LOGGED IN
// ---------------------------
if (!isset($_SESSION['barangay_admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['barangay_admin_id'];
$stmt = $conn->prepare("SELECT username FROM barangay_admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$username = ($row = $result->fetch_assoc()) ? $row['username'] : 'Admin';

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EcoTrack Admin</title>

<!-- Bootstrap & FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
}

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
    z-index: 2000;
    height: 70px;
}
.header .logo {
    display: flex;
    align-items: center;
    gap: 10px;
}
.header img { height: 40px; border-radius: 50%; }
.header-right { display: flex; align-items: center; gap: 15px; }
.logout-btn {
    color: white;
    background: transparent;
    border: 1px solid white;
    border-radius: 8px;
    padding: 5px 12px;
    text-decoration: none;
}
.logout-btn:hover {
    background: white;
    color: #3f4a36;
    transition: 0.3s;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    width: 70px;
    height: calc(100vh - 70px);
    background: #5f7353;
    overflow: hidden;
    transition: width 0.3s ease;
    z-index: 1500;
    padding-top: 20px;
}
.sidebar:hover { width: 220px; }
.nav-item {
    display: flex;
    align-items: center;
    padding: 14px 18px;
    color: white;
    cursor: pointer;
    transition: 0.2s;
}
.nav-item:hover { background: #4d5f44; }
.nav-item i { font-size: 20px; width: 30px; }
.nav-text {
    opacity: 0;
    white-space: nowrap;
    margin-left: 10px;
    transition: opacity .3s;
}
.sidebar:hover .nav-text { opacity: 1; }
.nav-item.active { background: #3c4d35; border-left: 4px solid #a8e6a3; }

/* MOBILE TOGGLE */
#toggleSidebar {
    display: none;
}
@media (max-width: 768px) {
    #toggleSidebar {
        display: block;
        position: fixed;
        top: 80px;
        left: 15px;
        background: #5f7353;
        color: white;
        border: none;
        font-size: 20px;
        padding: 10px 14px;
        border-radius: 8px;
        z-index: 3000;
    }
    .sidebar { transform: translateX(-100%); }
    .sidebar.show { transform: translateX(0); }
}

/* CONTENT AREA */
.content-area {
    margin-left: 90px;
    padding: 20px;
    margin-top: 90px;
}
@media (max-width: 768px) {
    .content-area { margin-left: 20px; }
}
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="logo">
        <img src="../img/logoo.png" alt="Logo">
        <span>Welcome, <?= htmlspecialchars($username); ?>!</span>
    </div>
    <div class="header-right">
        <div class="current-time"><?= date("Y-m-d H:i:s") ?></div>
        <button class="logout-btn" onclick="location.href='logout.php'">Log Out</button>
    </div>
</div>

<!-- MOBILE MENU BUTTON -->
<button id="toggleSidebar">☰</button>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebarMenu">

    <div class="nav-item <?= ($currentPage == 'adminDash.php') ? 'active' : '' ?>" onclick="location.href='adminDash.php'">
        <i class="fa fa-chart-line"></i>
        <span class="nav-text">Dashboard</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'collection_management.php') ? 'active' : '' ?>" onclick="location.href='collection_management.php'">
        <i class="fa fa-truck"></i>
        <span class="nav-text">Schedule</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'notification.php') ? 'active' : '' ?>" onclick="location.href='notification.php'">
        <i class="fa fa-bell"></i>
        <span class="nav-text">Notifications</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'send_message.php') ? 'active' : '' ?>" onclick="location.href='send_message.php'">
        <i class="fa fa-paper-plane"></i>
        <span class="nav-text">Announcements</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'user_management.php') ? 'active' : '' ?>" onclick="location.href='user_management.php'">
        <i class="fa fa-users"></i>
        <span class="nav-text">User Management</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'real_time_tracking.php') ? 'active' : '' ?>" onclick="location.href='real_time_tracking.php'">
        <i class="fa fa-location-dot"></i>
        <span class="nav-text">Real-Time Tracking</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'complain_support.php') ? 'active' : '' ?>" onclick="location.href='complain_support.php'">
        <i class="fa fa-headset"></i>
        <span class="nav-text">Complaints & Support</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'admin_settings.php') ? 'active' : '' ?>" onclick="location.href='admin_settings.php'">
        <i class="fa fa-gear"></i>
        <span class="nav-text">Settings</span>
    </div>

</div>

<script>
document.getElementById("toggleSidebar").onclick = () => {
    document.getElementById("sidebarMenu").classList.toggle("show");
};
</script>

</body>
</html>

<?php
include 'db_connect.php';


// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

// Fetch username from database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'] ?? 'User';

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EcoTrack Users</title>

<!-- FontAwesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
}

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
    box-sizing: border-box; /* Prevents overflow */
}

.header .logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.header img {
    height: 40px;
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
}

.logout-btn:hover {
    background: white;
    color: #3f4a36;
    transition: 0.3s;
}

.sidebar {
    position: fixed;
    top: 70px;              /* sits below the header */
    left: 0;
    width: 70px;
    height: calc(100vh - 70px);
    background: #5f7353;
    overflow: hidden;
    transition: width 0.3s ease;
    z-index: 1500;
    padding-top: 20px;
}

/* Expand on hover */
.sidebar:hover {
    width: 220px;
}

/* NAV ITEMS */
.nav-item {
    display: flex;
    align-items: center;
    padding: 14px 18px;
    color: white;
    cursor: pointer;
    transition: 0.2s;
}

.nav-item:hover {
    background: #4d5f44;
}

.nav-item i {
    font-size: 20px;
    width: 30px;
}

.nav-text {
    opacity: 0;
    white-space: nowrap;
    margin-left: 10px;
    transition: opacity .3s;
}

.sidebar:hover .nav-text {
    opacity: 1;
}

.nav-item.active {
    background: #3c4d35;
    border-left: 4px solid #a8e6a3;
}

#toggleSidebar {
    display: none;
}

@media (max-width: 768px) {
    #toggleSidebar {
        display: block;
        position: fixed;
        top: 80px; /* below header */
        left: 15px;
        background: #5f7353;
        color: white;
        border: none;
        font-size: 20px;
        padding: 10px 14px;
        border-radius: 8px;
        z-index: 3000;
    }

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }
}

.content-area {
    margin-left: 90px;
    padding: 20px;
    margin-top: 90px;
}

@media (max-width: 768px) {
    .content-area {
        margin-left: 20px;
    }
}
</style>
</head>

<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<body>

<!-- HEADER -->
<div class="header">
    <div class="logo">
        <img src="../img/logoo.png" alt="Logo">
        <span>Welcome, User!</span>
    </div>

    <div class="header-right">
        <div class="current-time"><?php echo date("Y-m-d H:i:s"); ?></div>
        <button class="logout-btn" onclick="location.href='logout.php'">Log Out</button>
    </div>
</div>

<!-- MOBILE MENU BUTTON -->
<button id="toggleSidebar">☰</button>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebarMenu">

    <div class="nav-item <?= ($currentPage == 'usersDash.php') ? 'active' : '' ?>" onclick="location.href='usersDash.php'">
        <i class="fa-solid fa-calendar"></i>
        <span class="nav-text">Schedule</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'live_truck_tracking.php') ? 'active' : '' ?>" onclick="location.href='live_truck_tracking.php'">
        <i class="fa fa-location-dot"></i>
        <span class="nav-text">Live Truck Tracking</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'inbox.php') ? 'active' : '' ?>" onclick="location.href='inbox.php'">
        <i class="fa-solid fa-inbox"></i>
        <span class="nav-text">Inbox</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'announcements.php') ? 'active' : '' ?>" onclick="location.href='announcements.php'">
        <i class="fa-solid fa-bullhorn"></i>
        <span class="nav-text">Announcements</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'submit_report.php') ? 'active' : '' ?>" onclick="location.href='submit_report.php'">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span class="nav-text">Submit Report</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'user_settings.php') ? 'active' : '' ?>" onclick="location.href='user_settings.php'">
        <i class="fa fa-gear"></i>
        <span class="nav-text">User Settings</span>
    </div>

</div>

<script>
document.getElementById("toggleSidebar").onclick = () => {
    document.getElementById("sidebarMenu").classList.toggle("show");
};
</script>

</body>
</html>

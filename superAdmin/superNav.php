<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

<style>
/* Sidebar */
.sidebar {
    position: fixed;
    top: 100px; /* adjust based on header height */
    left: 20px;
    width: 200px;
    background-color: #5f7353;
    padding: 15px 0;
    box-sizing: border-box;
    z-index: 999;
    border-radius: 45px;
}

/* Sidebar buttons */
.sidebar button {
    width: 160px;
    margin: 5px auto;
    padding: 10px;
    background-color: white;
    border: none;
    font-size: 14px;
    cursor: pointer;
    border-radius: 15px;
    transition: 0.3s;
    display: flex;
    align-items: center;
}

.sidebar button i {
    margin-right: 8px;
}

.sidebar button:hover {
    background-color: #e0ffe0;
    transform: translateX(4px);
}

.sidebar button.active {
    background-color: #e7ffe5;
    color: green;
    font-weight: bold;
    border: 2px solid green;
}

/* Mobile toggle button */
#toggleSidebar {
    z-index: 1001;
}

/* Push content for desktop */
.content-area {
    margin-left: 260px;
    padding: 20px;
    margin-top: 120px; /* header height + spacing */
}

/* Responsive: hide sidebar on small screens */
@media (max-width: 768px) {
    .content-area {
        margin-left: 20px;
        margin-top: 160px;
    }
}
</style>

</head>
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- MOBILE TOGGLE BUTTON -->
<button class="btn btn-success d-md-none mt-2 ms-2" id="toggleSidebar">
    ☰ Menu
</button>

<!-- SIDEBAR -->
<div class="sidebar d-none d-md-block" id="sidebarMenu">
    <button class="<?= ($currentPage == 'superAdmin.php') ? 'active' : '' ?>" onclick="location.href='superAdmin.php'">
        <i class="fa fa-chart-line me-2"></i> Dashboard
    </button>
    <button class="<?= ($currentPage == 'barangay_admin.php') ? 'active' : '' ?>" onclick="location.href='barangay_admin.php'">
        <i class="fa fa-user-shield me-2"></i> Barangay Admin
    </button>
    <button class="<?= ($currentPage == 'crew_accounts.php') ? 'active' : '' ?>" onclick="location.href='crew_accounts.php'">
        <i class="fa fa-users-gear me-2"></i> Collection Crew
    </button>
    <button class="<?= ($currentPage == 'live_tracking.php') ? 'active' : '' ?>" onclick="location.href='live_tracking.php'">
        <i class="fa fa-location-dot me-2"></i> Live Tracking
    </button>
    <button class="<?= ($currentPage == 'profile_settings.php') ? 'active' : '' ?>" onclick="location.href='profile_settings.php'">
        <i class="fa fa-gear me-2"></i> Profile Settings
    </button>
</div>

<script>
document.getElementById("toggleSidebar").onclick = function () {
    document.getElementById("sidebarMenu").classList.toggle("d-none");
};
</script>

</html>

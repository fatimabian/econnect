<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Side Bar</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- MOBILE TOGGLE BUTTON -->
<button class="btn btn-success d-md-none mt-2 ms-2" id="toggleSidebar">
    ☰ Menu
</button>

<!-- SIDEBAR -->
<div class="sidebar d-none d-md-block" id="sidebarMenu">
    <button class="<?= ($currentPage == 'adminDash.php') ? 'active' : '' ?>" onclick="location.href='adminDash.php'">Dashboard</button>
    <button class="<?= ($currentPage == 'collection_management.php') ? 'active' : '' ?>" onclick="location.href='collection_management.php'">Collection Management</button>
    <button class="<?= ($currentPage == 'notification.php') ? 'active' : '' ?>" onclick="location.href='notification.php'">Notification</button>
    <button class="<?= ($currentPage == 'user_management.php') ? 'active' : '' ?>" onclick="location.href='user_management.php'">User Management</button>
    <button class="<?= ($currentPage == 'real_time_tracking.php') ? 'active' : '' ?>" onclick="location.href='real_time_tracking.php'">Real-Time Tracking</button>
    <button class="<?= ($currentPage == 'complain_support.php') ? 'active' : '' ?>" onclick="location.href='complain_support.php'">Complaints and Support</button>
    <button class="<?= ($currentPage == 'admin_settings.php') ? 'active' : '' ?>" onclick="location.href='admin_settings.php'">Admin Settings</button>
</div>

<script>
document.getElementById("toggleSidebar").onclick = function () {
    document.getElementById("sidebarMenu").classList.toggle("d-none");
};
</script>

</body>
</html>

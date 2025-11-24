<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Side Bar</title>
    <link rel="stylesheet" href="users.css">
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
    <button class="<?= ($currentPage == 'usersDash.php') ? 'active' : '' ?>" onclick="location.href='usersDash.php'">Schedule</button>
    <button class="<?= ($currentPage == 'live_truck_tracking.php') ? 'active' : '' ?>" onclick="location.href='live_truck_tracking.php'">Live Truck Tracking</button>
    <button class="<?= ($currentPage == 'inbox.php') ? 'active' : '' ?>" onclick="location.href='inbox.php'">Inbox</button>
    <!-- <button class="<?= ($currentPage == 'announcements.php') ? 'active' : '' ?>" onclick="location.href='announcements.php'">Announcements</button> -->
    <button class="<?= ($currentPage == 'submit_report.php') ? 'active' : '' ?>" onclick="location.href='submit_report.php'">Submit Report</button>
    <button class="<?= ($currentPage == 'user_settings.php') ? 'active' : '' ?>" onclick="location.href='user_settings.php'">User Settings</button>
</div>

<script>
document.getElementById("toggleSidebar").onclick = function () {
    document.getElementById("sidebarMenu").classList.toggle("d-none");
};
</script>

</script>

</body>
</html>

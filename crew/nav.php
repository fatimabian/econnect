<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- MOBILE TOGGLE BUTTON -->
<button class="btn btn-success d-md-none mt-2 ms-2" id="toggleSidebar">
    ☰ Menu
</button>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebarMenu">
    <button class="<?= ($currentPage == 'crewDash.php') ? 'active' : '' ?>" onclick="location.href='crewDash.php'">Dashboard</button>
    <button class="<?= ($currentPage == 'crewMap.php') ? 'active' : '' ?>" onclick="location.href='crewMap.php'">Collection Map</button>
    <button class="<?= ($currentPage == 'crewInbox.php') ? 'active' : '' ?>" onclick="location.href='crewInbox.php'">Inbox</button>
    <button class="<?= ($currentPage == 'crewReport.php') ? 'active' : '' ?>" onclick="location.href='crewReport.php'">Report</button>
    <button class="<?= ($currentPage == 'crewProfile.php') ? 'active' : '' ?>" onclick="location.href='crewProfile.php'">Profile</button>
</div>

<style>
/* SIDEBAR */
.sidebar {
    width: 220px;
    background-color: #5f7353;
    padding: 20px 15px;
    border-radius: 20px;
    height: 100vh;
    position: fixed;
    top: 70px; /* space for fixed header */
    left: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    z-index: 1000;
}

.sidebar button {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    background-color: white;
    text-align: left;
    font-weight: 500;
    transition: 0.3s;
}

.sidebar button:hover,
.sidebar button.active {
    background-color: #e0ffe0;
}

/* MOBILE */
@media (max-width: 768px) {
    .sidebar {
        position: absolute;
        top: 0;
        left: -250px;
        transition: 0.3s;
        height: 100%;
        z-index: 9999;
    }
    .sidebar.show {
        left: 0;
    }
    #toggleSidebar {
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 10000;
    }
}
</style>

<script>
// Toggle sidebar on mobile
document.getElementById("toggleSidebar").onclick = function () {
    document.getElementById("sidebarMenu").classList.toggle("show");
};
</script>

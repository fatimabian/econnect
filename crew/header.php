<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include "../db_connect.php";

// CHECK IF CREW IS LOGGED IN
if (!isset($_SESSION['crew_id'])) {
    header("Location: ../login.php");
    exit;
}

$crew_id = $_SESSION['crew_id'];
$stmt = $conn->prepare("SELECT username FROM collection_crew WHERE id = ?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$result = $stmt->get_result();
$username = ($row = $result->fetch_assoc()) ? $row['username'] : 'Crew';

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EcoTrack Crew Portal</title>

<!-- Bootstrap & FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fa;
    color: #333;
    min-height: 100vh;
}

/* HEADER STYLES */
.header {
    width: 100%;
    background: linear-gradient(135deg, #3f4a36 0%, #2c3529 100%);
    color: white;
    padding: 0 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 2000;
    height: 70px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.header .logo {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header img {
    height: 42px;
    width: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #a8e6a3;
}

.logo-text {
    display: flex;
    flex-direction: column;
}

.logo-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #fff;
    line-height: 1.2;
}

.logo-subtext {
    font-size: 0.85rem;
    opacity: 0.9;
    color: #a8e6a3;
    font-weight: 500;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
}

.user-badge i {
    color: #a8e6a3;
}

.current-time {
    font-size: 0.9rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
    white-space: nowrap;
}

.logout-btn {
    color: white;
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    padding: 8px 18px;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.logout-btn:hover {
    background: white;
    color: #3f4a36;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* MOBILE MENU TOGGLE */
.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: background 0.3s;
    margin-right: 10px;
}

.menu-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* DESKTOP COLLAPSE TOGGLE */
.collapse-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
    border-radius: 6px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.collapse-toggle:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: rotate(180deg);
}

/* SIDEBAR STYLES */
.sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    width: 250px;
    height: calc(100vh - 70px);
    background: linear-gradient(to bottom, #5f7353 0%, #4a5a40 100%);
    overflow-y: auto;
    overflow-x: hidden;
    transition: all 0.3s ease;
    z-index: 1500;
    padding-top: 20px;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

/* Collapsed sidebar - shows only icons */
.sidebar.collapsed {
    width: 80px;
}

.sidebar.collapsed .nav-text {
    opacity: 0;
    visibility: hidden;
    width: 0;
}

.sidebar.collapsed .nav-item {
    padding: 16px;
    justify-content: center;
    margin: 5px 10px;
}

.sidebar.collapsed .nav-item i {
    margin: 0;
}

/* Expand on hover when collapsed */
.sidebar.collapsed:hover {
    width: 250px;
}

.sidebar.collapsed:hover .nav-text {
    opacity: 1;
    visibility: visible;
    width: auto;
}

.sidebar.collapsed:hover .nav-item {
    padding: 16px 24px;
    justify-content: flex-start;
    margin: 5px 15px;
}

.sidebar.collapsed:hover .nav-item i {
    margin-right: 0;
    width: 30px;
}

/* NAV ITEMS */
.nav-item {
    display: flex;
    align-items: center;
    padding: 16px 24px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    margin: 5px 15px;
    border-radius: 8px;
    white-space: nowrap;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateX(5px);
    border-left-color: #a8e6a3;
}

.nav-item i {
    font-size: 20px;
    width: 30px;
    text-align: center;
    transition: margin 0.3s ease;
}

.nav-text {
    white-space: nowrap;
    margin-left: 15px;
    font-weight: 500;
    transition: all 0.3s ease;
    overflow: hidden;
}

.nav-item.active {
    background: rgba(168, 230, 163, 0.15);
    border-left: 4px solid #a8e6a3;
    font-weight: 600;
}

.nav-item.active i {
    color: #a8e6a3;
}

/* CONTENT AREA */
.content-area {
    margin-left: 270px;
    padding: 30px;
    margin-top: 90px;
    transition: margin-left 0.3s ease;
    min-height: calc(100vh - 90px);
}

.sidebar.collapsed ~ .content-area {
    margin-left: 100px;
}

/* OVERLAY FOR MOBILE MENU */
.overlay {
    display: none;
    position: fixed;
    top: 70px;
    left: 0;
    width: 100%;
    height: calc(100vh - 70px);
    background: rgba(0, 0, 0, 0.5);
    z-index: 1499;
    backdrop-filter: blur(3px);
}

.sidebar.show ~ .overlay {
    display: block;
}

/* RESPONSIVE DESIGN */
@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    
    .sidebar.collapsed {
        width: 70px;
    }
    
    .content-area {
        margin-left: 240px;
        padding: 20px;
    }
    
    .sidebar.collapsed ~ .content-area {
        margin-left: 90px;
    }
}

@media (max-width: 768px) {
    .menu-toggle {
        display: block;
    }
    
    .collapse-toggle {
        display: none;
    }
    
    .logo-title {
        font-size: 1.2rem;
    }
    
    .logo-subtext {
        font-size: 0.8rem;
    }
    
    .header-right {
        gap: 12px;
    }
    
    .user-badge span:not(.user-icon) {
        display: none;
    }
    
    .current-time {
        font-size: 0.8rem;
        padding: 5px 10px;
    }
    
    .logout-btn span {
        display: none;
    }
    
    .logout-btn {
        padding: 8px;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .logout-btn i {
        margin: 0;
        font-size: 18px;
    }
    
    .sidebar {
        transform: translateX(-100%);
        width: 280px;
        box-shadow: 5px 0 20px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar.collapsed {
        width: 280px;
    }
    
    .sidebar.collapsed .nav-text {
        opacity: 1;
        visibility: visible;
        width: auto;
    }
    
    .sidebar.collapsed .nav-item {
        padding: 18px 20px;
        justify-content: flex-start;
        margin: 5px 10px;
    }
    
    .content-area {
        margin-left: 20px !important;
        margin-top: 80px;
        padding: 20px 15px;
    }
    
    .nav-item {
        padding: 18px 20px;
        margin: 5px 10px;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 0 15px;
        height: 65px;
    }
    
    .header img {
        height: 36px;
        width: 36px;
    }
    
    .logo-title {
        font-size: 1.1rem;
    }
    
    .logo-subtext {
        font-size: 0.75rem;
    }
    
    .current-time {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    
    .sidebar {
        width: 100%;
        max-width: 300px;
    }
    
    .content-area {
        padding: 15px 12px;
    }
}

/* Scrollbar styling for sidebar */
.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div style="display: flex; align-items: center; gap: 10px;">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="logo">
            <img src="../img/logoo.png" alt="Logo">
        </div>
    </div>

    <div class="header-right">
        
        <button class="collapse-toggle" id="collapseToggle" title="Toggle Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="current-time"><?= date("Y-m-d H:i:s") ?></div>
        <button class="logout-btn" onclick="location.href='logout.php'" title="Log Out">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log Out</span>
        </button>
    </div>
</div>

<!-- OVERLAY FOR MOBILE MENU -->
<div class="overlay" id="menuOverlay"></div>

<!-- SIDEBAR -->
<div class="sidebar collapsed" id="sidebarMenu">

    <div class="nav-item <?= ($currentPage == 'crewDash.php') ? 'active' : '' ?>" onclick="location.href='crewDash.php'">
        <i class="fas fa-chart-line"></i>
        <span class="nav-text">Dashboard</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'status.php') ? 'active' : '' ?>" onclick="location.href='status.php'">
        <i class="fas fa-map-marker-alt"></i>
        <span class="nav-text">Collection Status</span>
    </div>

    <div class="nav-item <?= ($currentPage == 'crewProfile.php') ? 'active' : '' ?>" onclick="location.href='crewProfile.php'">
        <i class="fas fa-user-cog"></i>
        <span class="nav-text">Profile & Settings</span>
    </div>

</div>

<script>
// Mobile menu toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebarMenu');
const overlay = document.getElementById('menuOverlay');
const collapseToggle = document.getElementById('collapseToggle');

// Mobile menu functionality
menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('show');
});

overlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
});

// Desktop collapse toggle
collapseToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    
    // Rotate the arrow icon
    const icon = collapseToggle.querySelector('i');
    if (sidebar.classList.contains('collapsed')) {
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
    } else {
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-left');
    }
});

// Close sidebar on mobile when clicking outside
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !menuToggle.contains(e.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});

// Close sidebar on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('show')) {
        sidebar.classList.remove('show');
    }
});

// Update time every second
function updateTime() {
    const now = new Date();
    const timeString = now.toISOString().slice(0, 19).replace('T', ' ');
    const timeElements = document.querySelectorAll('.current-time');
    timeElements.forEach(el => {
        el.textContent = timeString;
    });
}

// Update time immediately and every second
updateTime();
setInterval(updateTime, 1000);

// Save sidebar state in localStorage
function saveSidebarState() {
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('crewSidebarCollapsed', isCollapsed);
}

function loadSidebarState() {
    const savedState = localStorage.getItem('crewSidebarCollapsed');
    if (savedState === 'true') {
        sidebar.classList.add('collapsed');
        const icon = collapseToggle.querySelector('i');
        icon.classList.remove('fa-chevron-left');
        icon.classList.add('fa-chevron-right');
    }
}

// Save state when toggled
sidebar.addEventListener('transitionend', saveSidebarState);
collapseToggle.addEventListener('click', saveSidebarState);

// Load saved state on page load
window.addEventListener('load', loadSidebarState);

// Prevent sidebar hover effects on mobile
if (window.innerWidth <= 768) {
    sidebar.classList.remove('collapsed');
}
</script>

</body>
</html>
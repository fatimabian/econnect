<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../db_connect.php";

if (!isset($_SESSION['crew_id'])) {
    header("Location: crewLogin.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];

// Fetch crew info
$stmt = $conn->prepare("SELECT full_name FROM collection_crew WHERE id = ?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$result = $stmt->get_result();
$crew = $result->fetch_assoc();
$stmt->close();

if (!$crew) {
    echo "Crew member not found.";
    exit();
}

$crew_name = $crew['full_name'];
?>

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
    gap: 10px;
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
    cursor: pointer;
}

.logout-btn:hover {
    background: white;
    color: #3f4a36;
    transition: 0.3s ease;
}
</style>

<div class="header">
    <div class="logo">
        <img src="../img/logoo.png" alt="Logo">
        <span>Welcome, <?= htmlspecialchars($crew_name) ?>!</span>
    </div>
    <div class="header-right">
        <div class="current-time"><?= date("Y-m-d H:i:s") ?></div>
        <button class="logout-btn" onclick="location.href='logout.php'">Log Out</button>
    </div>
</div>

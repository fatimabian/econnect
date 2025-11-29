<?php
session_start();
include "db_connect.php";

// ==========================
// CHECK IF USER IS LOGGED IN
// ==========================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT barangay FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$barangay = $user['barangay'];
$stmt->close();

// Fetch crew assigned to this barangay
$stmt = $conn->prepare("SELECT * FROM collection_crew WHERE barangay = ?");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$crew = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch today's schedule for the barangay
$stmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date = CURDATE()");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inknut+Antiqua:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'header.php'; ?>

<div class="map-container">
  <h2 class="map-title">Live Truck Tracking</h2>
</div>

<div class="dashboard-grid">
  <!-- Map Placeholder -->
  <div class="map-placeholder">
    <p>Map will appear here</p>
  </div>

  <!-- Status Panel -->
  <div class="status-panel">
    <h3>Status:</h3>

    <div class="truck-status">
      <p><strong>Barangay:</strong> <?= htmlspecialchars($barangay); ?></p>

      <?php if ($crew): ?>
          <p><strong>Assigned Crew:</strong> <?= htmlspecialchars($crew['full_name']); ?></p>
          <p>Status: On Route</p>

          <?php if ($schedule): ?>
              <p><strong>Scheduled Time:</strong> <?= htmlspecialchars($schedule['time']); ?></p>
              <p><strong>ETA:</strong> Calculating...</p>
          <?php else: ?>
              <p><strong>No collection schedule for today.</strong></p>
          <?php endif; ?>

      <?php else: ?>
          <p><strong>No assigned crew for this barangay.</strong></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
    
.map-container {
    margin-top: 90px;
    text-align: center;
}
.map-title {
    font-family: 'Inknut Antiqua', serif;
    font-weight: 700;
    font-size: 28px;
    margin-bottom: 100px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: auto 300px;
    column-gap: 30px;
    margin-top: 80px;
    padding: 0 20px;
    align-items: start;
}

.map-placeholder {
    width: 800px;
    height: 500px;
    border: 2px dashed #888;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f5f5;
    color: #555;
    font-size: 18px;
    text-align: center;
    margin: 0 auto;
    margin-left: 300px;
}

.status-panel {
    width: 300px;
    border: 2px solid #aaa;
    padding: 20px;
    background-color: #C4D5C5;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.status-panel h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 20px;
    border-bottom: 1px solid #ccc;
    padding-bottom: 5px;
}

.truck-status {
    margin-bottom: 15px;
    padding: 10px;
    border: 1px dashed #ccc;
    border-radius: 6px;
    background-color: #C4D5C5;
}

.truck-status p {
    margin: 3px 0;
}
</style>

</body>
</html>

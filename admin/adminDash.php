<?php
session_start();
include "../db_connect.php"; // adjust path if needed

// ---------------------------
// SESSION & AUTH CHECK
// ---------------------------
if (!isset($_SESSION['barangay_admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['barangay_admin_id'];
$admin_name = $_SESSION['barangay_admin_name'] ?? 'Admin';

// ---------------------------
// FETCH ADMIN BARANGAY
// ---------------------------
$barangay = '';
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id=? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $barangay = $row['barangay'];
}
$stmt->close();

// ---------------------------
// NEXT PICKUP SCHEDULE
// ---------------------------
$next_pickup_display = "No Schedule";
$stmt = $conn->prepare("SELECT date, time FROM collection_schedule WHERE barangay=? AND date >= CURDATE() ORDER BY date ASC LIMIT 1");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $next_pickup_display = date("M d, Y H:i", strtotime($row['date'] . ' ' . $row['time']));
}
$stmt->close();

// ---------------------------
// COMPLETED PICKUPS
// ---------------------------
$completed_pickups = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS completed FROM collection_schedule WHERE barangay=? AND date < CURDATE()");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $completed_pickups = $row['completed'];
}
$stmt->close();

// ---------------------------
// TOTAL USERS
// ---------------------------
$total_users = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM users WHERE barangay=?");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_users = $row['total_users'];
}
$stmt->close();

// ---------------------------
// COMPLAINTS REPORTS
// ---------------------------
$total_complaints = 0;
$pending_complaints = 0;
$resolved_complaints = 0;

$stmt = $conn->prepare("SELECT 
                            COUNT(*) AS total,
                            SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending,
                            SUM(CASE WHEN status='Resolved' THEN 1 ELSE 0 END) AS resolved
                        FROM complaints");
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_complaints = $row['total'] ?? 0;
    $pending_complaints = $row['pending'] ?? 0;
    $resolved_complaints = $row['resolved'] ?? 0;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { font-family: Arial, sans-serif; background: #eef1ee; }
    .content { margin-left: 260px; padding: 20px; margin-top: 20px; }
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .card-title { font-weight: 600; color: #3f4a36; margin-bottom: 10px; }
    .card-number { font-size: 1.8rem; font-weight: 700; color: #3f4a36; }
</style>
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="content">
    <h2 class="mb-4">Welcome, <?= htmlspecialchars($admin_name) ?>!</h2>

    <div class="dashboard-grid">
        <div class="card">
            <div class="card-title">Total Users</div>
            <div class="card-number"><?= $total_users ?></div>
        </div>

        <div class="card">
            <div class="card-title">Next Pickup Schedule</div>
            <div class="card-number"><?= htmlspecialchars($next_pickup_display) ?></div>
        </div>

        <div class="card">
            <div class="card-title">Completed Pickups</div>
            <div class="card-number"><?= $completed_pickups ?></div>
        </div>

        <div class="card">
            <div class="card-title">All Complaints</div>
            <div class="card-number"><?= $total_complaints ?></div>
        </div>

        <div class="card">
            <div class="card-title">Pending Complaints</div>
            <div class="card-number"><?= $pending_complaints ?></div>
        </div>

        <div class="card">
            <div class="card-title">Resolved Complaints</div>
            <div class="card-number"><?= $resolved_complaints ?></div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>

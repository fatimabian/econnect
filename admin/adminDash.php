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
// COMPLETED PICKUPS
// ---------------------------
$completed_pickups = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS completed FROM completed_pickups cp
                        INNER JOIN collection_crew c ON cp.crew_id = c.id
                        WHERE c.barangay=?");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $completed_pickups = $row['completed'];
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    body { background-color: rgba(68,64,51,0.4) !important; padding-top: 70px; font-family: Arial, sans-serif; }
    .content { margin-left: 100px; padding: 20px; margin-top: 20px; }
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 30px; }
    .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
    .card-title { font-weight: 600; color: #3f4a36; margin-bottom: 10px; }
    .card-number { font-size: 1.8rem; font-weight: 700; color: #3f4a36; }
    canvas { background: white; border-radius: 12px; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .next-schedule { margin-bottom: 20px; text-align: center; }
    .next-schedule span { font-weight: 700; color: #198754; font-size: 1.2rem; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="content">
    <h2 class="mb-4">Welcome, <?= htmlspecialchars($admin_name) ?>!</h2>

    <!-- Next Pickup Schedule -->
<div class="next-schedule card p-3 mb-4 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #a8e6cf, #dcedc1); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-size: 1.2rem;">
    <div class="d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="#198754" class="bi bi-calendar-check" viewBox="0 0 16 16">
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1zm3.854 6.854a.5.5 0 1 0-.708-.708L3.5 9.793 2.854 9.146a.5.5 0 1 0-.708.708l1 1a.5.5 0 0 0 .708 0l2-2z"/>
        </svg>
        <div>
            <strong>Next Pickup:</strong> <span><?= htmlspecialchars($next_pickup_display) ?></span>
        </div>
    </div>
</div>


    <!-- Charts -->
    <div class="row g-4">
        <div class="col-md-4">
            <canvas id="complaintsChart"></canvas>
        </div>
        <div class="col-md-4">
            <canvas id="pickupsChart"></canvas>
        </div>
        <div class="col-md-4">
            <canvas id="usersChart"></canvas>
        </div>
    </div>

    <!-- Info Boxes -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-title">Total Users</div>
            <div class="card-number"><?= $total_users ?></div>
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

<script>
const complaintsData = {
    labels: ['Pending', 'Resolved'],
    datasets: [{
        label: 'Complaints',
        data: [<?= $pending_complaints ?>, <?= $resolved_complaints ?>],
        backgroundColor: ['#ffc107','#198754']
    }]
};

const pickupsData = {
    labels: ['Completed Pickups'],
    datasets: [{
        label: 'Pickups',
        data: [<?= $completed_pickups ?>],
        backgroundColor: ['#0d6efd']
    }]
};

const usersData = {
    labels: ['Total Users'],
    datasets: [{
        label: 'Users',
        data: [<?= $total_users ?>],
        backgroundColor: ['#6f42c1']
    }]
};

new Chart(document.getElementById('complaintsChart'), {
    type: 'doughnut',
    data: complaintsData,
    options: {
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Complaints Overview' }
        }
    }
});

new Chart(document.getElementById('pickupsChart'), {
    type: 'bar',
    data: pickupsData,
    options: {
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Completed Pickups' }
        },
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('usersChart'), {
    type: 'bar',
    data: usersData,
    options: {
        plugins: {
            legend: { display: false },
            title: { display: true, text: 'Total Users' }
        },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>

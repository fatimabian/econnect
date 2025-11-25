<?php
session_start();
include "../db_connect.php";

// ---------------------------
// SESSION CHECK
// ---------------------------
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$super_admin_id = $_SESSION['super_admin_id'];
$super_admin_name = $_SESSION['super_admin_name'] ?? 'Super Admin';

// ---------------------------
// FETCH STATS
// ---------------------------
$total_admins = $conn->query("SELECT COUNT(*) AS total_admins FROM barangay_admins")->fetch_assoc()['total_admins'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc()['total_users'] ?? 0;
$total_crew = $conn->query("SELECT COUNT(*) AS total_crew FROM collection_crew")->fetch_assoc()['total_crew'] ?? 0;

$result = $conn->query("
    SELECT 
        COUNT(*) AS total_complaints,
        SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) AS pending_complaints,
        SUM(CASE WHEN status='Resolved' THEN 1 ELSE 0 END) AS resolved_complaints
    FROM complaints
");
$row = $result->fetch_assoc();
$total_complaints = $row['total_complaints'] ?? 0;
$pending_complaints = $row['pending_complaints'] ?? 0;
$resolved_complaints = $row['resolved_complaints'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { font-family: Arial, sans-serif; background: rgb(68,64,51,0.4); margin:0; padding:0; }
.content { margin-left: 100px; padding: 100px 20px; transition: margin-left 0.3s ease; }
.dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-top: 20px; }
.card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align:center; transition: transform 0.2s; }
.card:hover { transform: translateY(-5px); }
.card-title { font-weight: 600; color: #3f4a36; margin-bottom: 10px; }
.card-number { font-size: 1.8rem; font-weight: 700; color: #3f4a36; }
h2 { color: #3f4a36; }
canvas { background: white; border-radius: 12px; padding: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
@media (max-width:768px){ .content { margin-left:0!important; padding:100px 15px; } }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="content">
    <h2 class="mb-4">Welcome, <?= htmlspecialchars($super_admin_name) ?>!</h2>

    <!-- GRAPHS -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <h5 class="text-center">User Accounts</h5>
            <canvas id="usersChart"></canvas>
        </div>
        <div class="col-md-6 mb-3">
            <h5 class="text-center">Complaints Overview</h5>
            <canvas id="complaintsChart"></canvas>
        </div>
    </div>

    <!-- DASHBOARD CARDS -->
    <div class="dashboard-grid">
        <div class="card">
            <div class="card-title">Admin Users</div>
            <div class="card-number"><?= $total_admins ?></div>
        </div>
        <div class="card">
            <div class="card-title">Citizen Users</div>
            <div class="card-number"><?= $total_users ?></div>
        </div>
        <div class="card">
            <div class="card-title">Crew Users</div>
            <div class="card-number"><?= $total_crew ?></div>
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
const usersChartCtx = document.getElementById('usersChart').getContext('2d');
new Chart(usersChartCtx, {
    type: 'bar',
    data: {
        labels: ['Admin Users', 'Citizen Users', 'Crew Users'],
        datasets: [{
            label: 'Number of Users',
            data: [<?= $total_admins ?>, <?= $total_users ?>, <?= $total_crew ?>],
            backgroundColor: ['#ffc107','#0d6efd','#198754']
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } } }
});

const complaintsChartCtx = document.getElementById('complaintsChart').getContext('2d');
new Chart(complaintsChartCtx, {
    type: 'line',
    data: {
        labels: ['Total', 'Pending', 'Resolved'],
        datasets: [{
            label: 'Complaints',
            data: [<?= $total_complaints ?>, <?= $pending_complaints ?>, <?= $resolved_complaints ?>],
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.2)',
            tension: 0.4,
            fill:true,
            pointBackgroundColor:'#0d6efd',
            pointRadius:6
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, precision:0 } } }
});
</script>

</body>
</html>

<?php
session_start();
include "../db_connect.php";


// SESSION & AUTH CHECK
if (!isset($_SESSION['barangay_admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['barangay_admin_id'];
$admin_name = $_SESSION['barangay_admin_name'] ?? 'Admin';


// FETCH ADMIN BARANGAY
$barangay = '';
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id=? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $barangay = $row['barangay'];
}
$stmt->close();


// NEXT PICKUP SCHEDULE
$next_pickup_display = "No Schedule";
$stmt = $conn->prepare("SELECT date, time FROM collection_schedule WHERE barangay=? AND date >= CURDATE() ORDER BY date ASC LIMIT 1");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $next_pickup_display = date("M d, Y H:i", strtotime($row['date'] . ' ' . $row['time']));
}
$stmt->close();


// TOTAL USERS
$total_users = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_users FROM users WHERE barangay=?");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_users = $row['total_users'];
}
$stmt->close();


// COMPLETED PICKUPS
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


// Fetch crew count for this barangay
$total_crew = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS total_crew FROM collection_crew WHERE barangay=?");
$stmt->bind_param("s", $barangay);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $total_crew = $row['total_crew'];
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary-color: #3f4a36;
    --secondary-color: #6c757d;
    --success-color: #198754;
    --info-color: #0dcaf0;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --transition-speed: 0.3s;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    color: #333;
}

.content {
    margin-left: 100px;
    padding: 100px 30px 30px;
    transition: margin-left var(--transition-speed) ease;
    min-height: calc(100vh - 70px);
}

.dashboard-header {
    margin-bottom: 30px;
}

.dashboard-header h2 {
    color: var(--primary-color);
    font-weight: 700;
    font-size: 2rem;
    margin-bottom: 8px;
}

.dashboard-header p {
    color: var(--secondary-color);
    font-size: 1rem;
    margin-bottom: 0;
}

/* Updated dashboard grid for 4 cards per row */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

/* Stat cards */
.stat-card {
    background: white;
    padding: 20px 15px;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    text-align: center;
    transition: all var(--transition-speed) ease;
    border-left: 4px solid var(--primary-color);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 180px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), #6d8c54);
}

.card-icon {
    font-size: 1.8rem;
    margin-bottom: 10px;
    color: var(--primary-color);
    opacity: 0.9;
}

.card-title {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 8px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.card-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-color);
    margin-bottom: 4px;
    line-height: 1.2;
}

.card-subtitle {
    font-size: 0.8rem;
    color: var(--secondary-color);
    opacity: 0.8;
    line-height: 1.3;
}

.charts-container {
    background: white;
    border-radius: 14px;
    padding: 20px;
    box-shadow: var(--card-shadow);
    margin-bottom: 25px;
}

.chart-title {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1.2rem;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.chart-wrapper {
    position: relative;
    height: 320px;
    padding: 10px;
}

.welcome-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    color: white;
    padding: 25px;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
}

.welcome-card h3 {
    font-weight: 700;
    margin-bottom: 8px;
    font-size: 1.5rem;
}

.welcome-card p {
    opacity: 0.9;
    margin-bottom: 0;
    font-size: 0.95rem;
}

.welcome-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

/* Next Schedule Card */
.schedule-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    color: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
    text-align: center;
}

.schedule-icon {
    font-size: 2rem;
    margin-bottom: 15px;
    opacity: 0.9;
}

.schedule-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 5px;
}

.schedule-date {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0;
}

/* Barangay Info */
.barangay-info {
    background: white;
    padding: 15px 20px;
    border-radius: 14px;
    box-shadow: var(--card-shadow);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.barangay-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.barangay-name {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }
}

@media (max-width: 768px) {
    .content {
        margin-left: 0 !important;
        padding: 100px 20px 20px;
    }
    
    .dashboard-header h2 {
        font-size: 1.6rem;
    }
    
    .chart-wrapper {
        height: 280px;
    }
    
    .stat-card {
        padding: 18px 12px;
        min-height: 160px;
    }
    
    .card-number {
        font-size: 1.8rem;
    }
    
    .welcome-card, .schedule-card {
        padding: 20px;
    }
    
    .welcome-card h3 {
        font-size: 1.3rem;
    }
}

@media (max-width: 576px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .charts-container {
        padding: 15px;
    }
    
    .chart-wrapper {
        height: 250px;
    }
    
    .welcome-card, .schedule-card {
        text-align: center;
        padding: 18px;
    }
    
    .welcome-icon {
        font-size: 2rem;
    }
}

/* Additional small devices */
@media (max-width: 480px) {
    .content {
        padding: 100px 15px 15px;
    }
    
    .stat-card {
        min-height: 150px;
        padding: 15px 10px;
    }
    
    .card-number {
        font-size: 1.6rem;
    }
    
    .card-icon {
        font-size: 1.6rem;
    }
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="content">
    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="row align-items-center">
            <div class="col-md-10">
                <h3>Welcome back, <?= htmlspecialchars($admin_name) ?>!</h3>
                <p>Manage your barangay's waste collection system efficiently.</p>
            </div>
            <div class="col-md-2 text-end d-none d-md-block">
                <div class="welcome-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Barangay Info -->
    <div class="barangay-info">
        <div class="barangay-icon">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <h4 class="barangay-name mb-0">Barangay <?= htmlspecialchars($barangay) ?></h4>
    </div>

    <!-- Next Pickup Schedule -->
    <div class="schedule-card">
        <div class="schedule-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="schedule-label">Next Collection Schedule</div>
        <div class="schedule-date"><?= htmlspecialchars($next_pickup_display) ?></div>
    </div>

    <!-- Charts Section -->
    <div class="charts-container">
        <h5 class="chart-title">User Distribution</h5>
        <div class="chart-wrapper">
            <canvas id="usersChart"></canvas>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-title">Total Users</div>
            <div class="card-number"><?= $total_users ?></div>
            <div class="card-subtitle">Registered Residents</div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-truck-loading"></i>
            </div>
            <div class="card-title">Collection Crew</div>
            <div class="card-number"><?= $total_crew ?></div>
            <div class="card-subtitle">Active Crew Members</div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="card-title">Completed Pickups</div>
            <div class="card-number"><?= $completed_pickups ?></div>
            <div class="card-subtitle">Successful Collections</div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="card-title">Total Activities</div>
            <div class="card-number"><?= $total_users + $total_crew + $completed_pickups ?></div>
            <div class="card-subtitle">All System Activities</div>
        </div>
    </div>
    
    <!-- Additional stats -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="card-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="card-title">System Status</div>
                <div class="card-number">Active</div>
                <div class="card-subtitle">All services operational</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-title">Last Updated</div>
                <div class="card-number">Today</div>
                <div class="card-subtitle"><?= date('F j, Y') ?></div>
            </div>
        </div>
    </div>
</div>

<script>
// User Distribution Chart
const usersChartCtx = document.getElementById('usersChart').getContext('2d');
new Chart(usersChartCtx, {
    type: 'doughnut',
    data: {
        labels: ['Resident Users', 'Crew Members'],
        datasets: [{
            data: [<?= $total_users ?>, <?= $total_crew ?>],
            backgroundColor: [
                'rgba(13, 110, 253, 0.8)',
                'rgba(25, 135, 84, 0.8)'
            ],
            borderColor: [
                'rgba(13, 110, 253, 1)',
                'rgba(25, 135, 84, 1)'
            ],
            borderWidth: 2,
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.raw;
                        return label;
                    }
                }
            }
        },
        cutout: '60%',
        animation: {
            animateScale: true,
            animateRotate: true
        }
    }
});

// Make sure charts resize properly on window resize
window.addEventListener('resize', function() {
    // Chart.js automatically handles resizing with responsive: true
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>
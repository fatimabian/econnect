<?php
session_start();
include "../db_connect.php";


// SESSION CHECK
if (!isset($_SESSION['super_admin_id'])) {
    header("Location: ../login.php");
    exit;
}

$super_admin_id = $_SESSION['super_admin_id'];
$super_admin_name = $_SESSION['super_admin_name'] ?? 'Super Admin';


// FETCH STATS
$total_admins = $conn->query("SELECT COUNT(*) AS total_admins FROM barangay_admins")->fetch_assoc()['total_admins'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc()['total_users'] ?? 0;
$total_crew = $conn->query("SELECT COUNT(*) AS total_crew FROM collection_crew")->fetch_assoc()['total_crew'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin Dashboard</title>

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

/* Smaller stat cards */
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
    
    .welcome-card {
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
    
    .welcome-card {
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
                <h3>Welcome back, <?= htmlspecialchars($super_admin_name) ?>!</h3>
                <p>Here's an overview of your system statistics and user management dashboard.</p>
            </div>
            <div class="col-md-2 text-end d-none d-md-block">
                <div class="welcome-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- User Statistics Cards - Now 4 per row -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="card-title">Admin Users</div>
            <div class="card-number"><?= $total_admins ?></div>
            <div class="card-subtitle">Barangay Administrators</div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-title">Citizen Users</div>
            <div class="card-number"><?= $total_users ?></div>
            <div class="card-subtitle">Registered Citizens</div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-truck-loading"></i>
            </div>
            <div class="card-title">Crew Users</div>
            <div class="card-number"><?= $total_crew ?></div>
            <div class="card-subtitle">Collection Crew Members</div>
        </div>
        
        <div class="stat-card">
            <div class="card-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="card-title">Total Users</div>
            <div class="card-number"><?= $total_admins + $total_users + $total_crew ?></div>
            <div class="card-subtitle">All System Users</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-container">
        <h5 class="chart-title">User Distribution</h5>
        <div class="chart-wrapper">
            <canvas id="usersChart"></canvas>
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
        labels: ['Admin Users', 'Citizen Users', 'Crew Users'],
        datasets: [{
            data: [<?= $total_admins ?>, <?= $total_users ?>, <?= $total_crew ?>],
            backgroundColor: [
                'rgba(255, 193, 7, 0.8)',
                'rgba(13, 110, 253, 0.8)',
                'rgba(25, 135, 84, 0.8)'
            ],
            borderColor: [
                'rgba(255, 193, 7, 1)',
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

</body>
</html>
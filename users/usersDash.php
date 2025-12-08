<?php
session_start();
include '../db_connect.php';
include 'header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch logged-in user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "<p>User not found.</p>";
    exit;
}

$barangay = $user['barangay'];
$username = htmlspecialchars($user['username']);

// Fetch schedules
$nextStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date >= CURDATE() ORDER BY date ASC, time ASC LIMIT 1");
$nextStmt->bind_param("s", $barangay);
$nextStmt->execute();
$nextSchedule = $nextStmt->get_result()->fetch_assoc();

$lastStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date < CURDATE() ORDER BY date DESC, time DESC LIMIT 2");
$lastStmt->bind_param("s", $barangay);
$lastStmt->execute();
$lastSchedules = $lastStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$allStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? ORDER BY date ASC, time ASC");
$allStmt->bind_param("s", $barangay);
$allStmt->execute();
$allSchedules = $allStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare calendar events
$calendarEvents = [];
foreach ($allSchedules as $schedule) {
    $calendarEvents[] = [
        'title' => 'Collection',
        'start' => $schedule['date'] . 'T' . $schedule['time'],
        'allDay' => false,
        'color' => '#3f4a36'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>User Dashboard - Waste Collection System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

* {
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    color: #333;
    overflow-x: hidden;
}

/* Content area with sidebar offset */
.content {
    margin-left: 100px;
    padding: 80px 20px 20px;
    width: calc(100% - 100px);
    min-height: calc(100vh - 70px);
    box-sizing: border-box;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 768px) {
    .content {
        margin-left: 0;
        padding: 70px 15px 15px;
        width: 100%;
        min-height: calc(100vh - 60px);
    }
}

@media (max-width: 576px) {
    .content {
        padding: 65px 10px 10px;
        min-height: calc(100vh - 55px);
    }
}

.dashboard-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 20px;
    width: 100%;
}

.welcome-card h3 {
    font-weight: 700;
    margin-bottom: 8px;
    font-size: 1.3rem;
    line-height: 1.3;
}

.welcome-card p {
    opacity: 0.9;
    margin-bottom: 0;
    font-size: 0.9rem;
    line-height: 1.4;
}

.welcome-icon {
    font-size: 2rem;
    opacity: 0.8;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
    width: 100%;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    text-align: center;
    transition: all var(--transition-speed) ease;
    border-left: 4px solid var(--primary-color);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 160px;
    width: 100%;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.12);
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
    margin-bottom: 12px;
    color: var(--primary-color);
    opacity: 0.9;
}

.card-title {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 10px;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    line-height: 1.2;
}

.card-number {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--primary-color);
    margin-bottom: 5px;
    line-height: 1.1;
}

.card-subtitle {
    font-size: 0.8rem;
    color: var(--secondary-color);
    opacity: 0.8;
    line-height: 1.2;
    padding: 0 5px;
}

/* Schedule Cards */
.schedule-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    width: 100%;
    margin-bottom: 20px;
}

.schedule-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    width: 100%;
}

.schedule-card h4 {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
}

.schedule-card h4 i {
    font-size: 1.1rem;
}

.schedule-item {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 3px solid var(--primary-color);
}

.schedule-item:last-child {
    margin-bottom: 0;
}

.schedule-date {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
    margin-bottom: 5px;
    line-height: 1.3;
}

.schedule-time {
    color: var(--secondary-color);
    font-size: 0.9rem;
    margin-bottom: 10px;
    line-height: 1.2;
}

.schedule-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    line-height: 1.2;
}

.status-completed {
    background: #d1e7dd;
    color: #0a3622;
}

.status-upcoming {
    background: #cfe2ff;
    color: #052c65;
}

/* Calendar Section */
.calendar-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    width: 100%;
    overflow: hidden;
}

.calendar-section h4 {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
}

.calendar-section h4 i {
    font-size: 1.1rem;
}

#calendar {
    background-color: #fff;
    border-radius: 8px;
    padding: 15px;
    min-height: 450px;
    width: 100%;
}

.fc {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    width: 100%;
}

.fc .fc-toolbar-title {
    font-size: 1.3rem;
    color: var(--primary-color);
    font-weight: 600;
    line-height: 1.2;
}

.fc .fc-button-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    font-size: 0.85rem;
    padding: 6px 12px;
}

.fc .fc-button-primary:hover {
    background-color: #5a6c4a;
    border-color: #5a6c4a;
}

.fc .fc-daygrid-day-number {
    color: var(--primary-color);
    font-weight: 500;
}

.fc .fc-day-today {
    background-color: rgba(63, 74, 54, 0.1) !important;
}

.fc-view {
    width: 100% !important;
}

/* Barangay Info */
.barangay-info {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
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

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--secondary-color);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1.1rem;
    margin-bottom: 0;
    line-height: 1.4;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding-top: 60px;
    }
    
    .content {
        padding: 70px 15px 15px;
        width: 100%;
    }
    
    .dashboard-container {
        padding: 0 5px;
    }
    
    .welcome-card {
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .welcome-card h3 {
        font-size: 1.2rem;
    }
    
    .welcome-card p {
        font-size: 0.85rem;
    }
    
    .welcome-icon {
        font-size: 1.8rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .stat-card {
        min-height: 140px;
        padding: 15px;
    }
    
    .card-icon {
        font-size: 1.5rem;
        margin-bottom: 10px;
    }
    
    .card-title {
        font-size: 0.85rem;
        margin-bottom: 8px;
    }
    
    .card-number {
        font-size: 1.8rem;
        margin-bottom: 3px;
    }
    
    .card-subtitle {
        font-size: 0.75rem;
    }
    
    .schedule-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .schedule-card {
        padding: 15px;
    }
    
    .schedule-card h4 {
        font-size: 1.1rem;
        margin-bottom: 15px;
        gap: 8px;
    }
    
    .schedule-item {
        padding: 12px;
        margin-bottom: 12px;
    }
    
    .schedule-date {
        font-size: 1rem;
    }
    
    .schedule-time {
        font-size: 0.85rem;
    }
    
    .schedule-status {
        font-size: 0.7rem;
        padding: 3px 10px;
    }
    
    .calendar-section {
        padding: 15px;
    }
    
    #calendar {
        padding: 10px;
        min-height: 400px;
    }
    
    .fc .fc-toolbar {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .fc .fc-toolbar-title {
        font-size: 1.1rem;
        text-align: center;
        margin: 5px 0;
    }
    
    .fc .fc-toolbar-chunk {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .fc .fc-button {
        font-size: 0.8rem;
        padding: 5px 10px;
    }
    
    .fc .fc-view {
        min-height: 350px;
    }
    
    .empty-state {
        padding: 30px 15px;
    }
    
    .empty-state i {
        font-size: 2.5rem;
    }
    
    .empty-state p {
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    body {
        padding-top: 55px;
    }
    
    .content {
        padding: 65px 10px 10px;
    }
    
    .welcome-card {
        padding: 12px;
    }
    
    .welcome-card h3 {
        font-size: 1.1rem;
    }
    
    .welcome-card p {
        font-size: 0.8rem;
    }
    
    .stat-card {
        min-height: 130px;
        padding: 12px;
    }
    
    .card-icon {
        font-size: 1.3rem;
    }
    
    .card-number {
        font-size: 1.6rem;
    }
    
    .card-title {
        font-size: 0.8rem;
    }
    
    .card-subtitle {
        font-size: 0.7rem;
    }
    
    .schedule-card h4 {
        font-size: 1rem;
    }
    
    .schedule-date {
        font-size: 0.95rem;
    }
    
    .schedule-time {
        font-size: 0.8rem;
    }
    
    .schedule-status {
        font-size: 0.65rem;
        padding: 2px 8px;
    }
    
    #calendar {
        min-height: 350px;
        padding: 8px;
    }
    
    .fc .fc-toolbar-title {
        font-size: 1rem;
    }
    
    .fc .fc-button {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    
    .empty-state {
        padding: 25px 10px;
    }
    
    .empty-state i {
        font-size: 2rem;
    }
    
    .empty-state p {
        font-size: 0.95rem;
    }
}

/* For medium devices */
@media (min-width: 577px) and (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .stat-card {
        min-height: 150px;
    }
    
    .schedule-row {
        grid-template-columns: 1fr;
    }
}

/* For tablets */
@media (min-width: 769px) and (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .stat-card {
        min-height: 160px;
    }
    
    .schedule-row {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* For larger screens */
@media (min-width: 993px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
    
    .stat-card {
        min-height: 170px;
    }
}

/* Scrollbar styling */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #5a6c4a;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="content">
    <div class="dashboard-container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-10">
                    <h3>Welcome back, <?= $username ?>!</h3>
                    <p>Track your waste collection schedule and activities.</p>
                </div>
                <div class="col-md-2 text-end d-none d-md-block">
                    <div class="welcome-icon">
                        <i class="fas fa-user-circle"></i>
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

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="card-title">Upcoming Collections</div>
                <div class="card-number">
                    <?= $nextSchedule ? 1 : 0 ?>
                </div>
                <div class="card-subtitle">Scheduled for next pickup</div>
            </div>
            
            <div class="stat-card">
                <div class="card-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="card-title">Past Collections</div>
                <div class="card-number"><?= count($lastSchedules) ?></div>
                <div class="card-subtitle">Recently completed</div>
            </div>
        </div>

        <!-- Schedule Information -->
        <div class="schedule-row">
            <div class="schedule-card">
                <h4><i class="fas fa-calendar-alt"></i> Next Schedule</h4>
                <?php if($nextSchedule): ?>
                    <div class="schedule-item">
                        <div class="schedule-date"><?= date("F d, Y", strtotime($nextSchedule['date'])) ?></div>
                        <div class="schedule-time"><?= date("g:i a", strtotime($nextSchedule['time'])) ?></div>
                        <span class="schedule-status status-upcoming">Upcoming</span>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No upcoming schedule</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="schedule-card">
                <h4><i class="fas fa-history"></i> Recent Collections</h4>
                <?php if($lastSchedules): ?>
                    <?php foreach($lastSchedules as $schedule): ?>
                        <div class="schedule-item">
                            <div class="schedule-date"><?= date("F d, Y", strtotime($schedule['date'])) ?></div>
                            <div class="schedule-time"><?= date("g:i a", strtotime($schedule['time'])) ?></div>
                            <span class="schedule-status status-completed">Completed</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No previous collections</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="calendar-section">
            <h4><i class="fas fa-calendar-week"></i> Collection Calendar</h4>
            <div id="calendar"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        events: <?= json_encode($calendarEvents) ?>,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Today',
            month: 'Month',
            week: 'Week',
            day: 'Day'
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: true
        },
        // Mobile responsive settings
        windowResize: function(view) {
            if (window.innerWidth < 768) {
                calendar.changeView('dayGridMonth');
                calendar.setOption('headerToolbar', {
                    left: 'prev,next',
                    center: 'title',
                    right: 'today'
                });
            } else {
                calendar.setOption('headerToolbar', {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                });
            }
        }
    });
    calendar.render();
    
    // Adjust calendar for mobile on load
    if (window.innerWidth < 768) {
        calendar.changeView('dayGridMonth');
        calendar.setOption('headerToolbar', {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        });
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            calendar.updateSize();
        }, 250);
    });
});
</script>
</body>
</html>
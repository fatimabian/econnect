<?php
session_start();
include "../db_connect.php";
include "header.php";

// Check if crew is logged in
if (!isset($_SESSION['crew_id'])) {
    header("Location: ../login.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];

// Fetch crew info
$stmt = $conn->prepare("SELECT * FROM collection_crew WHERE id = ?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$crew = $stmt->get_result()->fetch_assoc();
$stmt->close();
$barangay = $crew['barangay'];

// Fetch next schedule
$nextStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay=? AND date >= CURDATE() ORDER BY date ASC, time ASC LIMIT 1");
$nextStmt->bind_param("s", $barangay);
$nextStmt->execute();
$nextSchedule = $nextStmt->get_result()->fetch_assoc();
$nextStmt->close();

// Fetch last 2 schedules
$lastStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay=? AND date < CURDATE() ORDER BY date DESC, time DESC LIMIT 2");
$lastStmt->bind_param("s", $barangay);
$lastStmt->execute();
$lastSchedules = $lastStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lastStmt->close();

// Fetch all schedules for calendar
$allStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay=? ORDER BY date ASC, time ASC");
$allStmt->bind_param("s", $barangay);
$allStmt->execute();
$allSchedules = $allStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$allStmt->close();

$calendarEvents = [];
foreach ($allSchedules as $schedule) {
    $calendarEvents[] = [
        'title' => 'Collection',
        'start' => $schedule['date'] . 'T' . $schedule['time'],
        'allDay' => false
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crew Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<style>
body {
    margin: 0;
    font-family: Georgia, serif;
    background-color: #f8f8f8;
    padding-top: 70px; /* for fixed header */
}

.container {
    display: flex;
    gap: 20px;
    padding: 20px;
}

/* MAIN CONTENT */
.main-content {
    margin-left: 220px; /* space for sidebar */
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

/* CALENDAR */
.calendar-wrapper {
    max-width: 500px;
    flex-shrink: 0;
}

#calendar {
    max-width: 500px;
}

/* SCHEDULE BOXES */
.schedule-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    flex: 1;
}

.schedule-box {
    background: #C4D5C5;
    padding: 15px;
    border-radius: 10px;
}

.schedule-box h3 {
    font-size: 18px;
    color: #3f4a36;
    margin-bottom: 10px;
}

.schedule-entry p {
    margin: 3px 0;
}

footer {
    background-color: #3f4a36;
    color: white;
    text-align: center;
    padding: 10px 0;
    margin-top: 30px;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        flex-direction: column;
    }
}
</style>
</head>
<body>

<div class="container">
    <?php include 'nav.php'; ?>

    <div class="main-content">
        <!-- Calendar -->
        <div class="calendar-wrapper">
            <div id="calendar"></div>
        </div>

        <!-- Schedules beside calendar -->
        <div class="schedule-container">
            <div class="schedule-box">
                <h3>Next Schedule</h3>
                <?php if($nextSchedule): ?>
                    <p><strong>Date:</strong> <?= date("F d, Y", strtotime($nextSchedule['date'])) ?></p>
                    <p><strong>Time:</strong> <?= date("g:i a", strtotime($nextSchedule['time'])) ?></p>
                <?php else: ?>
                    <p>No upcoming schedule.</p>
                <?php endif; ?>
            </div>

            <div class="schedule-box">
                <h3>Last Schedules</h3>
                <?php if($lastSchedules): ?>
                    <?php foreach($lastSchedules as $schedule): ?>
                        <div class="schedule-entry">
                            <p><strong>Date:</strong> <?= date("F d, Y", strtotime($schedule['date'])) ?></p>
                            <p><strong>Time:</strong> <?= date("g:i a", strtotime($schedule['time'])) ?></p>
                            <p><strong>Remarks:</strong> Completed</p>
                        </div>
                        <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No previous schedules.</p>
                <?php endif; ?>
            </div>
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
    });
    calendar.render();
});
</script>

</body>
</html>

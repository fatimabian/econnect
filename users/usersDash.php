<?php
session_start();
include '../db_connect.php';
include 'header.php';
include 'nav.php';

// ==========================
// Check if user is logged in
// ==========================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ==========================
// Fetch logged-in user info
// ==========================
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo "<p>User not found.</p>";
    exit;
}

$barangay = $user['barangay'];

// ==========================
// Fetch schedules
// ==========================
$nextStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date >= CURDATE() ORDER BY date ASC, time ASC LIMIT 1");
$nextStmt->bind_param("s", $barangay);
$nextStmt->execute();
$nextSchedule = $nextStmt->get_result()->fetch_assoc();

$lastStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date < CURDATE() ORDER BY date DESC, time DESC LIMIT 2");
$lastStmt->bind_param("s", $barangay);
$lastStmt->execute();
$lastSchedules = $lastStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$allStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ?");
$allStmt->bind_param("s", $barangay);
$allStmt->execute();
$allSchedules = $allStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$events = [];
foreach ($allSchedules as $s) {
    $events[] = [
        'title' => 'Collection',
        'start' => $s['date'],
        'allDay' => true,
        'color' => '#28a745'
    ];
}
$events_json = json_encode($events);
?>

<div class="page-container">
    <div class="calendar-wrapper">
        <div class="calendar-box">
            <div class="d-flex justify-content-center mb-3 gap-2">
                <!-- Month Selector -->
                <select id="monthSelect" class="form-select w-auto">
                    <?php
                    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                    foreach($months as $index => $month){
                        echo "<option value='$index'>$month</option>";
                    }
                    ?>
                </select>

                <!-- Year Selector -->
                <select id="yearSelect" class="form-select w-auto"></select>
            </div>

            <!-- Calendar -->
            <div id="calendar"></div>
        </div>
    </div>

    <div class="right-section">
        <!-- Next Schedule -->
        <div class="schedule-box">
            <h3>Next Schedule</h3>
            <?php if($nextSchedule): ?>
                <p><strong>Date:</strong> <?= date("F d, Y", strtotime($nextSchedule['date'])) ?></p>
                <p><strong>Time:</strong> <?= date("g:i a", strtotime($nextSchedule['time'])) ?></p>
            <?php else: ?>
                <p>No upcoming schedule.</p>
            <?php endif; ?>
        </div>

        <!-- Last Schedules -->
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

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const yearSelect = document.getElementById("yearSelect");
    const currentYear = new Date().getFullYear();

    for (let y = currentYear - 10; y <= currentYear + 10; y++) {
        let option = document.createElement("option");
        option.value = y;
        option.textContent = y;
        yearSelect.appendChild(option);
    }
    yearSelect.value = currentYear;
    document.getElementById("monthSelect").value = new Date().getMonth();

    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: "auto",
        events: <?= $events_json ?>,
        eventDisplay: 'background',
    });
    calendar.render();

    document.getElementById("monthSelect").addEventListener("change", function () {
        let month = parseInt(this.value);
        let year = parseInt(yearSelect.value);
        calendar.gotoDate(new Date(year, month, 1));
    });
    yearSelect.addEventListener("change", function () {
        let year = parseInt(this.value);
        let month = parseInt(document.getElementById("monthSelect").value);
        calendar.gotoDate(new Date(year, month, 1));
    });
});
</script>

<style>
body {
    margin: 0;
    font-family: Georgia, serif;
    background-color: #f8f8f8;
    padding-top: 60px;
}

.page-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    gap: 50px;
    padding-bottom: 120px; /* ensures calendar won't overlap footer */
}

.calendar-wrapper {
    width: 400px;
}

.calendar-box {
    background: #e0ecd8;
    padding: 20px 25px;
    border-radius: 15px;
}

#calendar {
    width: 100%;
}

.right-section {
    width: 320px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.schedule-box {
    background: #C4D5C5;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0px 2px 4px rgba(0,0,0,0.1);
    font-family: Poppins, sans-serif;
}

.schedule-box h3 {
    margin-bottom: 15px;
    font-size: 20px;
    color: #5f7353;
}

.schedule-entry p {
    margin: 3px 0;
}

footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #3f4a36;
    color: white;
    text-align: center;
    padding: 10px 0;
}
</style>

<!-- <?php include 'footer.php'; ?> -->

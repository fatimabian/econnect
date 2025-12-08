<?php
session_start();
date_default_timezone_set('Asia/Manila'); // FIX: Set the timezone to ensure date() returns the correct time for December 7th.
include "../db_connect.php";
include "header.php";

// Check if crew is logged in
if (!isset($_SESSION['crew_id'])) {
    header("Location: ../login.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];
$today = date('Y-m-d');

// Fetch crew info and barangay
$stmt = $conn->prepare("SELECT * FROM collection_crew WHERE id = ?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$crew = $stmt->get_result()->fetch_assoc();
$stmt->close();
$barangay = $crew['barangay'];

// Check if today is collection day for this barangay
$isCollectionDay = false;
$collectionSchedule = null;

// Check collection_schedule table for today's date
$scheduleStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date = ?");
$scheduleStmt->bind_param("ss", $barangay, $today);
$scheduleStmt->execute();
$result = $scheduleStmt->get_result();

if ($result->num_rows > 0) {
    $isCollectionDay = true;
    $collectionSchedule = $result->fetch_assoc();
}
$scheduleStmt->close();

// IPROG SMS API token
$api_token = "dda33f23a9d96e5f433c56d8907c072b40830ef7";

// Handle status update (only if it's collection day)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && $isCollectionDay) {
    $status = $_POST['status'];

    // Insert status into database
    $insertStmt = $conn->prepare("INSERT INTO collection_status (crew_id, barangay, status) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iss", $crew_id, $barangay, $status);
    $insertStmt->execute();
    $insertStmt->close();

    // Fetch all users in this barangay
    $userStmt = $conn->prepare("SELECT contact, fname, lname FROM users WHERE barangay=? AND status='Active'");
    $userStmt->bind_param("s", $barangay);
    $userStmt->execute();
    $users = $userStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $userStmt->close();

    // Send SMS to all residents
    foreach ($users as $user) {
        $contact = preg_replace('/^0/', '+63', trim($user['contact']));
        $message_text = "Hello {$user['fname']}, garbage collection for {$barangay} is now: $status";

        $sms_data = [
            "api_token" => $api_token,
            "phone_number" => $contact,
            "message" => $message_text
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($sms_data)
            ]
        ];

        $context = stream_context_create($options);
        @file_get_contents("https://sms.iprogtech.com/api/v1/sms_messages", false, $context);
    }

    $success = "Status updated to '$status' and SMS notifications sent.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && !$isCollectionDay) {
    $error = "Status update is only allowed on collection days. Today is not a scheduled collection day for $barangay.";
}

// Fetch last 5 statuses
$lastStmt = $conn->prepare("SELECT * FROM collection_status WHERE crew_id=? ORDER BY timestamp DESC LIMIT 5");
$lastStmt->bind_param("i", $crew_id);
$lastStmt->execute();
$lastStatuses = $lastStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$lastStmt->close();

// Fetch upcoming collection schedules (next 7 days)
$upcomingStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay = ? AND date >= ? ORDER BY date ASC LIMIT 7");
$upcomingStmt->bind_param("ss", $barangay, $today);
$upcomingStmt->execute();
$upcomingSchedules = $upcomingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$upcomingStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crew Status Update - ECOnnect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary-green: #3f4a36;
    --secondary-green: #5f7353;
    --accent-green: #a8e6a3;
    --light-green: #e8f5e9;
    --dark-green: #2c3529;
    --text-dark: #333333;
    --text-light: #666666;
    --background-light: #f8f9fa;
    --white: #ffffff;
    --border-color: #e0e0e0;
    --shadow-light: rgba(0, 0, 0, 0.08);
    --shadow-medium: rgba(0, 0, 0, 0.15);
    --danger-red: #dc3545;
    --warning-yellow: #ffc107;
    --success-teal: #20c997;
    --info-blue: #0dcaf0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
    min-height: 100vh;
    color: var(--text-dark);
    margin: 0;
    padding: 0;
}

.content {
    margin-left: 270px;
    padding: 100px 30px 30px;
    transition: margin-left 0.3s ease;
    min-height: calc(100vh - 70px);
}

/* Dashboard Header */
.dashboard-header {
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

.dashboard-header h2 {
    color: var(--primary-green);
    font-weight: 700;
    font-size: 2.2rem;
    margin-bottom: 5px;
}

.dashboard-header p {
    color: var(--text-light);
    font-size: 1.05rem;
    margin-bottom: 0;
}

/* Collection Day Banner */
.collection-day-banner {
    background: linear-gradient(135deg, var(--accent-green) 0%, #8cd585 100%);
    color: var(--dark-green);
    padding: 15px 25px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(168, 230, 163, 0.3);
}

.collection-day-banner i {
    font-size: 1.3rem;
}

.no-collection-banner {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: var(--white);
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 10px 30px var(--shadow-medium);
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(50px, -50px);
}

.welcome-card h3 {
    font-weight: 700;
    margin-bottom: 10px;
    font-size: 1.8rem;
    position: relative;
    z-index: 1;
}

.welcome-card p {
    opacity: 0.9;
    margin-bottom: 0;
    font-size: 1rem;
    position: relative;
    z-index: 1;
}

.welcome-icon {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 4rem;
    opacity: 0.2;
}

/* Status Update Card */
.status-card {
    background: var(--white);
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 10px 30px var(--shadow-medium);
    margin-bottom: 30px;
    border: 1px solid var(--border-color);
}

.status-card h5 {
    color: var(--primary-green);
    font-weight: 600;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.3rem;
}

.status-card h5 i {
    font-size: 1.4rem;
    color: var(--secondary-green);
}

/* Status Buttons Grid */
.status-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.status-btn {
    padding: 20px;
    border-radius: 14px;
    font-weight: 600;
    font-size: 1.1rem;
    border: none;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 130px;
    text-align: center;
    box-shadow: 0 6px 12px var(--shadow-light);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.status-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: currentColor;
    opacity: 0.3;
}

.status-btn:hover:not(.disabled) {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px var(--shadow-medium);
}

.status-btn:active:not(.disabled) {
    transform: translateY(-2px);
}

.status-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    filter: grayscale(50%);
}

.status-btn.disabled::after {
    content: 'Only available on collection days';
    position: absolute;
    bottom: -25px;
    left: 0;
    right: 0;
    font-size: 0.8rem;
    color: var(--danger-red);
    font-weight: 500;
    white-space: nowrap;
}

.btn-onway {
    background: linear-gradient(135deg, var(--info-blue) 0%, #0aa8d9 100%);
    color: white;
}

.btn-delayed {
    background: linear-gradient(135deg, var(--danger-red) 0%, #c82333 100%);
    color: white;
}

.btn-started {
    background: linear-gradient(135deg, var(--warning-yellow) 0%, #e0a800 100%);
    color: #212529;
}

.btn-completed {
    background: linear-gradient(135deg, var(--success-teal) 0%, #198754 100%);
    color: white;
}

.status-btn i {
    font-size: 2rem;
    margin-bottom: 12px;
}

.status-btn span {
    font-size: 1.1rem;
    font-weight: 600;
}

/* Alert Messages */
.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: none;
    border-left: 5px solid #28a745;
    color: #155724;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.15);
    font-weight: 600;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border: none;
    border-left: 5px solid var(--danger-red);
    color: #721c24;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(220, 53, 69, 0.15);
    font-weight: 600;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: none;
    border-left: 5px solid var(--warning-yellow);
    color: #856404;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(255, 193, 7, 0.15);
    font-weight: 600;
}

/* Status Log Card */
.status-log-card {
    background: var(--white);
    padding: 30px;
    border-radius: 18px;
    box-shadow: 0 10px 30px var(--shadow-medium);
    border: 1px solid var(--border-color);
}

.status-log-card h5 {
    color: var(--primary-green);
    font-weight: 600;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-green);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.3rem;
}

.status-log-card h5 i {
    font-size: 1.4rem;
    color: var(--secondary-green);
}

.status-log-item {
    background: var(--light-green);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    border-left: 5px solid var(--secondary-green);
    transition: all 0.3s ease;
    border: 1px solid rgba(95, 115, 83, 0.1);
}

.status-log-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.status-badge {
    display: inline-block;
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 10px;
    letter-spacing: 0.5px;
}

.status-on-the-way { background: var(--info-blue); color: white; }
.status-delayed { background: var(--danger-red); color: white; }
.status-started { background: var(--warning-yellow); color: #212529; }
.status-completed { background: var(--success-teal); color: white; }

.status-time {
    color: var(--text-light);
    font-size: 0.95rem;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-time i {
    font-size: 0.9rem;
    color: var(--secondary-green);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-light);
}

.empty-state i {
    font-size: 3.5rem;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-state p {
    font-size: 1.1rem;
    margin-bottom: 10px;
    font-weight: 500;
}

/* Schedule Cards */
.schedule-card {
    background: var(--white);
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 10px 30px var(--shadow-medium);
    margin-bottom: 25px;
    border: 1px solid var(--border-color);
}

.schedule-card h5 {
    color: var(--primary-green);
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.3rem;
}

.schedule-card h5 i {
    font-size: 1.4rem;
    color: var(--secondary-green);
}

.schedule-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.schedule-item {
    background: var(--light-green);
    padding: 18px;
    border-radius: 12px;
    border-left: 5px solid var(--secondary-green);
    transition: all 0.3s ease;
}

.schedule-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.schedule-item.today {
    border-left: 5px solid var(--accent-green);
    background: linear-gradient(135deg, var(--accent-green) 0%, #c5e6c2 100%);
}

.schedule-date {
    font-weight: 700;
    color: var(--primary-green);
    font-size: 1.1rem;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.schedule-date i {
    color: var(--secondary-green);
}

.schedule-time {
    color: var(--text-light);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.schedule-time i {
    font-size: 0.9rem;
    color: var(--secondary-green);
}

/* No Schedule State */
.no-schedule-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light);
}

.no-schedule-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.3;
}

.no-schedule-state p {
    font-size: 1.1rem;
    margin-bottom: 10px;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .content {
        margin-left: 100px;
    }
    
    .status-buttons {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .collection-day-banner,
    .no-collection-banner {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    body {
        padding-top: 65px;
    }
    
    .content {
        margin-left: 20px !important;
        padding: 100px 20px 20px;
    }
    
    .dashboard-header h2 {
        font-size: 1.8rem;
    }
    
    .welcome-card h3 {
        font-size: 1.5rem;
    }
    
    .status-buttons {
        grid-template-columns: 1fr;
    }
    
    .status-btn {
        min-height: 100px;
        flex-direction: row;
        justify-content: flex-start;
        padding: 20px;
        gap: 20px;
        text-align: left;
    }
    
    .status-btn i {
        margin-bottom: 0;
        font-size: 1.8rem;
    }
    
    .welcome-icon {
        display: none;
    }
    
    .schedule-card,
    .status-log-card {
        padding: 25px;
    }
}

@media (max-width: 576px) {
    .content {
        padding: 100px 15px 15px;
    }
    
    .welcome-card,
    .status-card,
    .schedule-card,
    .status-log-card {
        padding: 25px 20px;
    }
    
    .status-btn {
        min-height: 90px;
    }
    
    .status-log-item,
    .schedule-item {
        padding: 15px;
    }
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="content-area">
    <div class="dashboard-header">
        <div>
            <h2>Status Update Panel</h2>
            <p>Manage collection status for <?= htmlspecialchars($barangay) ?></p>
        </div>
        
        <?php if($isCollectionDay): ?>
            <div class="collection-day-banner">
                <i class="fas fa-calendar-check"></i>
                <span>Today is Collection Day</span>
            </div>
        <?php else: ?>
            <div class="no-collection-banner">
                <i class="fas fa-calendar-times"></i>
                <span>No Collection Scheduled Today</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="welcome-card">
        <h3>Hello, <?= htmlspecialchars($crew['username']) ?>!</h3>
        <p>Update real-time collection status and notify residents in <?= htmlspecialchars($barangay) ?></p>
        <div class="welcome-icon">
            <i class="fas fa-broadcast-tower"></i>
        </div>
    </div>

    <div class="schedule-card">
        <h5><i class="fas fa-calendar-alt"></i> Upcoming Collection Schedule</h5>
        <?php if(!empty($upcomingSchedules)): ?>
            <div class="schedule-list">
                <?php foreach($upcomingSchedules as $schedule): 
                    $scheduleDate = date('Y-m-d', strtotime($schedule['date']));
                    $isTodaySchedule = ($scheduleDate == $today);
                ?>
                    <div class="schedule-item <?= $isTodaySchedule ? 'today' : '' ?>">
                        <div class="schedule-date">
                            <i class="fas fa-trash-alt"></i>
                            <?= date('F d, Y (l)', strtotime($schedule['date'])) ?>
                            <?php if($isTodaySchedule): ?>
                                <span style="color: var(--accent-green); font-weight: 700; margin-left: auto;">TODAY</span>
                            <?php endif; ?>
                        </div>
                        <div class="schedule-time">
                            <i class="far fa-clock"></i>
                            Scheduled Time: <?= date('h:i A', strtotime($schedule['time'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-schedule-state">
                <i class="fas fa-calendar-plus"></i>
                <p>No upcoming collection schedule found</p>
                <p class="text-muted">Contact your barangay admin to set up collection schedules</p>
            </div>
        <?php endif; ?>
    </div>

    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="status-card">
        <h5><i class="fas fa-sync-alt"></i> Update Collection Status</h5>
        
        <?php if(!$isCollectionDay): ?>
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    Status updates are only available on scheduled collection days. 
                    <strong>Today is <?= date('l, F j, Y') ?></strong> which is not a collection day for <?= htmlspecialchars($barangay) ?>.
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="status-buttons">
            <button type="submit" name="status" value="On the Way" 
                    class="status-btn btn-onway <?= !$isCollectionDay ? 'disabled' : '' ?>"
                    <?= !$isCollectionDay ? 'disabled' : '' ?>>
                <i class="fas fa-truck-moving"></i>
                <span>On the Way</span>
            </button>
            <button type="submit" name="status" value="Delayed" 
                    class="status-btn btn-delayed <?= !$isCollectionDay ? 'disabled' : '' ?>"
                    <?= !$isCollectionDay ? 'disabled' : '' ?>>
                <i class="fas fa-clock"></i>
                <span>Delayed</span>
            </button>
            <button type="submit" name="status" value="Collection Started" 
                    class="status-btn btn-started <?= !$isCollectionDay ? 'disabled' : '' ?>"
                    <?= !$isCollectionDay ? 'disabled' : '' ?>>
                <i class="fas fa-play-circle"></i>
                <span>Collection Started</span>
            </button>
            <button type="submit" name="status" value="Collection Completed" 
                    class="status-btn btn-completed <?= !$isCollectionDay ? 'disabled' : '' ?>"
                    <?= !$isCollectionDay ? 'disabled' : '' ?>>
                <i class="fas fa-check-double"></i>
                <span>Collection Completed</span>
            </button>
        </form>
        <p class="text-muted mt-3 mb-0">
            <small><i class="fas fa-info-circle"></i> 
                <?php if($isCollectionDay): ?>
                    Selecting a status will send SMS notifications to all residents in <?= htmlspecialchars($barangay) ?>.
                <?php else: ?>
                    Status buttons are disabled on non-collection days.
                <?php endif; ?>
            </small>
        </p>
    </div>

    <div class="status-log-card">
        <h5><i class="fas fa-history"></i> Recent Status Updates</h5>
        <?php if($lastStatuses): ?>
            <?php foreach($lastStatuses as $s): 
                // Determine badge class based on status
                $badgeClass = '';
                switch($s['status']) {
                    case 'On the Way': $badgeClass = 'status-on-the-way'; break;
                    case 'Delayed': $badgeClass = 'status-delayed'; break;
                    case 'Collection Started': $badgeClass = 'status-started'; break;
                    case 'Collection Completed': $badgeClass = 'status-completed'; break;
                }
            ?>
                <div class="status-log-item">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($s['status']) ?></span>
                            <div class="status-time">
                                <i class="far fa-clock"></i>
                                <?= date("F d, Y g:i a", strtotime($s['timestamp'])) ?>
                            </div>
                        </div>
                        <div class="text-muted mt-2 mt-sm-0">
                            <small>Barangay: <?= htmlspecialchars($barangay) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <p>No status updates yet</p>
                <p class="text-muted">Update your first status to see it here</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation for status updates (only if enabled)
    const statusButtons = document.querySelectorAll('.status-btn:not(.disabled)');
    statusButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const status = this.querySelector('span').textContent;
            if (!confirm(`Are you sure you want to update status to "${status}"?\n\nThis will send SMS notifications to all residents in <?= htmlspecialchars($barangay) ?>.`)) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
    
    // Update layout based on sidebar state
    function updateLayout() {
        const content = document.querySelector('.content');
        const sidebar = document.querySelector('.sidebar');
        
        if (window.innerWidth <= 768) {
            content.style.marginLeft = '20px';
        } else if (sidebar && sidebar.classList.contains('collapsed')) {
            content.style.marginLeft = '100px';
        } else {
            content.style.marginLeft = '270px';
        }
    }
    
    updateLayout();
    window.addEventListener('resize', updateLayout);
    
    // Show tooltip for disabled buttons
    const disabledButtons = document.querySelectorAll('.status-btn.disabled');
    disabledButtons.forEach(button => {
        button.addEventListener('mouseover', function() {
            this.style.cursor = 'not-allowed';
        });
    });
});
</script>
</body>
</html>
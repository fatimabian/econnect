<?php
session_start();
date_default_timezone_set('Asia/Manila'); // ensure timezone

include "db_connect.php";

// Ensure admin is logged in
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// -- try to add reminder_sent column if it does not exist (safe)
$checkCol = $conn->query("SHOW COLUMNS FROM `collection_schedule` LIKE 'reminder_sent'");
if ($checkCol === false) {
    // ignore - DB probably fine but continue
} elseif ($checkCol->num_rows === 0) {
    // Add column; ignore errors
    @$conn->query("ALTER TABLE `collection_schedule` ADD COLUMN `reminder_sent` TINYINT(1) NOT NULL DEFAULT 0");
}

// fetch barangay of admin
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_barangay);
$stmt->fetch();
$stmt->close();

if (!$admin_barangay) {
    $_SESSION['error'] = "Your admin barangay could not be determined.";
    header("Location: collection_management.php");
    exit;
}

// helper: normalize phone to +63XXXXXXXXXX if possible
function normalize_phone($raw) {
    $raw = preg_replace('/\D+/', '', trim($raw)); // digits only
    if (preg_match('/^09\d{9}$/', $raw)) {
        return '+63' . substr($raw, 1);
    }
    if (preg_match('/^\+63\d{10}$/', $raw)) {
        return $raw;
    }
    // allow already international e.g. 63XXXXXXXXXX
    if (preg_match('/^63\d{10}$/', $raw)) {
        return '+' . $raw;
    }
    return null;
}

// helper: send single SMS via iProg (curl)
function send_sms_iprog($phone, $message, $api_token) {
    $url = "https://sms.iprogtech.com/api/v1/sms_messages";
    $payload = json_encode([
        "api_token" => $api_token,
        "phone_number" => $phone,
        "message" => $message
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Return true for 2xx responses
    return ($err === '' && $code >= 200 && $code < 300);
}

// get all active resident phones in barangay
function get_active_resident_phones($conn, $barangay) {
    $phones = [];
    $users_stmt = $conn->prepare("SELECT id, contact FROM users WHERE barangay = ? AND status = 'Active'");
    $users_stmt->bind_param("s", $barangay);
    $users_stmt->execute();
    $res = $users_stmt->get_result();
    while ($u = $res->fetch_assoc()) {
        $norm = normalize_phone($u['contact']);
        if ($norm) {
            $phones[] = ['phone' => $norm, 'user_id' => $u['id']];
        }
    }
    $users_stmt->close();
    return $phones;
}

// === SMS API TOKEN - replace with secure storage in production ===
$sms_api_token = "dda33f23a9d96e5f433c56d8907c072b40830ef7";

// === HANDLE ADD SCHEDULE POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
    $barangay = $admin_barangay;
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    // basic validation
    if (empty($date) || empty($time)) {
        $_SESSION['error'] = "Date and time are required.";
        header("Location: collection_management.php");
        exit;
    }
    if ($date < date('Y-m-d')) {
        $_SESSION['error'] = "Collection date must be today or later.";
        header("Location: collection_management.php");
        exit;
    }

    // insert schedule and send initial SMS inside transaction
    $conn->begin_transaction();
    try {
        $ins = $conn->prepare("INSERT INTO collection_schedule (barangay, date, time, reminder_sent) VALUES (?, ?, ?, 0)");
        $ins->bind_param("sss", $barangay, $date, $time);
        if (!$ins->execute()) {
            throw new Exception("Failed to insert schedule: " . $ins->error);
        }
        $new_schedule_id = $ins->insert_id;
        $ins->close();

        // send immediate notification to all active users
        $phones = get_active_resident_phones($conn, $barangay);
        $date_fmt = date('F j, Y', strtotime($date));
        $time_fmt = date('g:i A', strtotime($time));
        $message = "NOTICE: Waste collection scheduled for $date_fmt at $time_fmt. Please prepare your waste. - Barangay $barangay";

        $all_ok = true;
        foreach ($phones as $p) {
            $ok = send_sms_iprog($p['phone'], $message, $sms_api_token);
            if (!$ok) $all_ok = false; // we continue sending even if some fail
        }

        $conn->commit();
        $_SESSION['success'] = "Schedule created. Notification sent to residents." . ($all_ok ? '' : ' (Some SMS may have failed.)');
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Could not create schedule: " . $e->getMessage();
    }

    header("Location: collection_management.php");
    exit;
}

// === HANDLE RESCHEDULE POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reschedule') {
    $schedule_id = intval($_POST['schedule_id'] ?? 0);
    $new_date = $_POST['new_date'] ?? '';
    $new_time = $_POST['new_time'] ?? '';

    if ($schedule_id <= 0 || empty($new_date) || empty($new_time)) {
        $_SESSION['error'] = "Invalid reschedule data.";
        header("Location: collection_management.php");
        exit;
    }

    if ($new_date < date('Y-m-d')) {
        $_SESSION['error'] = "New date must be today or later.";
        header("Location: collection_management.php");
        exit;
    }

    // fetch old schedule for message
    $old_stmt = $conn->prepare("SELECT date, time FROM collection_schedule WHERE id = ? AND barangay = ?");
    $old_stmt->bind_param("is", $schedule_id, $admin_barangay);
    $old_stmt->execute();
    $old_res = $old_stmt->get_result();
    $old = $old_res->fetch_assoc();
    $old_stmt->close();

    if (!$old) {
        $_SESSION['error'] = "Schedule not found or not authorized.";
        header("Location: collection_management.php");
        exit;
    }

    $old_date_fmt = date('F j, Y', strtotime($old['date']));
    $old_time_fmt = $old['time'] ? date('g:i A', strtotime($old['time'])) : '-';
    $new_date_fmt = date('F j, Y', strtotime($new_date));
    $new_time_fmt = date('g:i A', strtotime($new_time));

    $conn->begin_transaction();
    try {
        // update schedule and reset reminder_sent so day-before reminder will run again
        $upd = $conn->prepare("UPDATE collection_schedule SET date = ?, time = ?, reminder_sent = 0 WHERE id = ? AND barangay = ?");
        $upd->bind_param("ssis", $new_date, $new_time, $schedule_id, $admin_barangay);
        if (!$upd->execute()) {
            throw new Exception("Failed to update schedule: " . $upd->error);
        }
        $upd->close();

        // send reschedule SMS immediately
        $phones = get_active_resident_phones($conn, $admin_barangay);
        $reschedule_msg = "IMPORTANT: Waste collection rescheduled from $old_date_fmt $old_time_fmt to $new_date_fmt $new_time_fmt. Please prepare your waste. - Barangay $admin_barangay";

        $all_ok = true;
        foreach ($phones as $p) {
            $ok = send_sms_iprog($p['phone'], $reschedule_msg, $sms_api_token);
            if (!$ok) $all_ok = false;
        }

        $conn->commit();
        $_SESSION['success'] = "Schedule updated. Reschedule notifications sent." . ($all_ok ? '' : ' (Some SMS may have failed.)');
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Could not reschedule: " . $e->getMessage();
    }

    header("Location: collection_management.php");
    exit;
}

// === DAILY REMINDER SENDER (runs on each page load) ===
// Find schedules for this barangay where date = tomorrow and reminder_sent = 0
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$reminder_select = $conn->prepare("SELECT id, date, time FROM collection_schedule WHERE barangay = ? AND date = ? AND reminder_sent = 0");
$reminder_select->bind_param("ss", $admin_barangay, $tomorrow);
$reminder_select->execute();
$rem_rows = $reminder_select->get_result();
$reminder_ids = [];
$reminders_sent_count = 0;
if ($rem_rows->num_rows > 0) {
    // gather phones once
    $phones = get_active_resident_phones($conn, $admin_barangay);

    while ($sch = $rem_rows->fetch_assoc()) {
        $reminder_ids[] = $sch['id'];
        $date_fmt = date('F j, Y', strtotime($sch['date']));
        $time_fmt = $sch['time'] ? date('g:i A', strtotime($sch['time'])) : '-';
        $rem_msg = "REMINDER: Waste collection scheduled for tomorrow ($date_fmt) at $time_fmt. Please prepare. - Barangay $admin_barangay";

        $all_ok_for_schedule = true;
        foreach ($phones as $p) {
            $ok = send_sms_iprog($p['phone'], $rem_msg, $sms_api_token);
            if (!$ok) $all_ok_for_schedule = false;
        }
        if ($all_ok_for_schedule) $reminders_sent_count++;
        // (we'll mark reminder_sent even if some SMS failed, to avoid repetition; if you prefer otherwise, adjust)
    }

    // mark reminder_sent = 1 for these schedules
    if (!empty($reminder_ids)) {
        // build parameter list safely
        $placeholders = implode(',', array_fill(0, count($reminder_ids), '?'));
        $types = str_repeat('i', count($reminder_ids));
        $sql = "UPDATE collection_schedule SET reminder_sent = 1 WHERE id IN ($placeholders)";
        $stmtUpd = $conn->prepare($sql);
        $bind_names = [];
        $bind_names[] = $types;
        foreach ($reminder_ids as $k => $id) {
            $bind_names[] = &$reminder_ids[$k];
        }
        // call_user_func_array for bind_param
        call_user_func_array([$stmtUpd, 'bind_param'], $bind_names);
        $stmtUpd->execute();
        $stmtUpd->close();
    }
}
$reminder_select->close();

// === Fetch schedules to show (future including today) ===
// show upcoming schedules for this barangay (today and beyond), latest data (dynamic)
$sql = "
SELECT cs.id, cs.barangay, cs.date, cs.time,
       cc.username AS collector_username,
       (SELECT cs2.status FROM collection_status cs2 WHERE cs2.barangay = cs.barangay ORDER BY cs2.timestamp DESC LIMIT 1) AS collection_status
FROM collection_schedule cs
LEFT JOIN collection_crew cc ON cc.barangay = cs.barangay
WHERE cs.barangay = ?
AND cs.date >= CURDATE()
ORDER BY cs.date ASC, cs.time ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_barangay);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $row['collection_status'] = $row['collection_status'] ?: 'Pending';
    $schedules[] = $row;
}
$stmt->close();

// completed count (historical)
$completed_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM collection_status WHERE barangay = ? AND status = 'Collection Completed'");
$completed_stmt->bind_param("s", $admin_barangay);
$completed_stmt->execute();
$completed_count = $completed_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$completed_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Management • Barangay Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary-color: #3f4a36;
    --secondary-color: #6c757d;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
    --light-bg: #f8f9fa;
    --dark-text: #212529;
    --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%) !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    color: var(--dark-text);
    min-height: 100vh;
    padding-top: 10px;
}

.content-area {
    padding: 15px;
    min-height: calc(100vh - 60px);
    margin: 0 auto;
    max-width: 100%;
}

/* Small devices (phones) */
@media (max-width: 576px) {
    body {
        padding-top: 5px;
    }
    
    .content-area {
        padding: 10px 8px;
    }
}

/* Medium devices (tablets) */
@media (min-width: 577px) and (max-width: 768px) {
    .content-area {
        padding: 15px;
    }
}

/* Large devices (desktops) */
@media (min-width: 769px) {
    .content-area {
        padding: 20px;
        max-width: 1400px;
    }
}

/* Header Card */
.header-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    color: white;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: var(--card-shadow);
}

.header-card h1 {
    font-weight: 600;
    font-size: 1.5rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.header-card .badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 500;
    padding: 0.3rem 0.6rem;
    font-size: 0.85rem;
}

.header-card p {
    margin-top: 0.25rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Buttons */
.btn-custom {
    background: linear-gradient(135deg, var(--primary-color) 0%, #5a6c4a 100%);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
    white-space: nowrap;
    height: 40px;
    font-size: 0.9rem;
}

.btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(63, 74, 54, 0.25);
    color: white;
    background: linear-gradient(135deg, #5a6c4a 0%, var(--primary-color) 100%);
}

.btn-reschedule {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    border: none;
    color: #212529;
    font-weight: 600;
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: var(--transition);
    font-size: 0.8rem;
    white-space: nowrap;
    height: 34px;
}

.btn-reschedule:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.25);
    background: linear-gradient(135deg, #e0a800 0%, #ffc107 100%);
    color: #212529;
}

/* Table Styling */
.table-responsive {
    border-radius: 8px;
    overflow: hidden;
    margin: 0;
}

.table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 750px;
    font-size: 0.9rem;
}

.table thead {
    background-color: var(--primary-color);
    color: white;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table th {
    border: none;
    padding: 0.6rem 0.5rem;
    font-weight: 600;
    vertical-align: middle;
    text-align: center;
    white-space: nowrap;
    font-size: 0.85rem;
}

.table td {
    padding: 0.6rem 0.5rem;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
    text-align: center;
    font-size: 0.85rem;
}

/* Status Badges */
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 15px;
    font-weight: 600;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.badge-pending {
    background-color: #ffc107 !important;
    color: #212529;
}

.badge-onway {
    background-color: #17a2b8 !important;
    color: white;
}

.badge-started {
    background-color: #fd7e14 !important;
    color: white;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.stats-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: var(--card-shadow);
    text-align: center;
    transition: var(--transition);
    border-top: 3px solid var(--primary-color);
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

.stats-card i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
}

.stats-card h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stats-card p {
    color: #6c757d;
    margin-bottom: 0;
    font-size: 0.85rem;
}

/* SMS Preview */
.sms-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.75rem;
    margin: 0.75rem 0;
    font-family: monospace;
    font-size: 0.85rem;
    line-height: 1.3;
    max-height: 120px;
    overflow-y: auto;
}

.sms-preview-header {
    color: #6c757d;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
}

/* Auto badge */
.auto-badge {
    background: rgba(25, 135, 84, 0.1);
    color: var(--success-color);
    border: 1px solid var(--success-color);
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .stats-card {
        padding: 0.75rem;
    }
    
    .stats-card h3 {
        font-size: 1.3rem;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .btn-custom, .btn-reschedule {
        width: 100%;
        justify-content: center;
        margin-bottom: 0.25rem;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-out;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
}

.spinner {
    width: 35px;
    height: 35px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="loading-overlay">
    <div class="spinner"></div>
</div>

<div class="content-area">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="header-card fade-in">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div class="d-flex flex-column">
                <h1>
                    <i class="fas fa-calendar-alt me-2"></i> Collection Management
                    <span class="badge ms-2"><?= htmlspecialchars($admin_barangay) ?></span>
                </h1>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-bell me-1"></i> Automatic reminders sent day before collection
                </p>
            </div>
            <div class="d-flex gap-2 mt-2 mt-md-0">
                <span class="auto-badge">
                    <i class="fas fa-robot me-1"></i> Auto-Notifications
                </span>
                <button class="btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle"></i> Add Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid fade-in">
        <div class="stats-card">
            <i class="fas fa-calendar-alt text-primary"></i>
            <h3><?= count($schedules) ?></h3>
            <p>Active Schedules</p>
        </div>
        <div class="stats-card">
            <i class="fas fa-clock text-warning"></i>
            <h3><?= count(array_filter($schedules, fn($s) => $s['collection_status'] == 'Pending')) ?></h3>
            <p>Pending</p>
        </div>
        <div class="stats-card">
            <i class="fas fa-truck-moving text-info"></i>
            <h3><?= count(array_filter($schedules, fn($s) => $s['collection_status'] == 'On the Way')) ?></h3>
            <p>On the Way</p>
        </div>
        <div class="stats-card">
            <i class="fas fa-check-circle text-success"></i>
            <h3><?= $completed_count ?></h3>
            <p>Completed</p>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="card main-card fade-in">
        <div class="card-header">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchBar" class="form-control" placeholder="Search schedules...">
            </div>
            <div class="d-flex align-items-center gap-2 mt-1 mt-md-0">
                <span class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    <?= count($schedules) ?> active
                </span>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Collector</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTable">
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                        <h5 class="mb-2">No Active Schedules</h5>
                                        <p class="text-muted mb-3">Add your first collection schedule</p>
                                        <button class="btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                                            <i class="fas fa-plus-circle me-2"></i> Create Schedule
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($schedules as $row): ?>
                            <?php
                            try {
                                $date = new DateTime($row['date']);
                                $formattedDate = $date->format('M j, Y');
                                $dayBefore = date('M j, Y', strtotime($row['date'] . ' -1 day'));
                            } catch (Exception $e) {
                                $formattedDate = $row['date'];
                                $dayBefore = 'N/A';
                            }
                            
                            if (!empty($row['time'])) {
                                $time = date('g:i A', strtotime($row['time']));
                            } else {
                                $time = '-';
                            }
                            ?>
                            <tr>
                                <td class="fw-bold"><?= $i++ ?></td>
                                <td>
                                    <div class="d-flex flex-column align-items-center">
                                        <span><?= htmlspecialchars($formattedDate) ?></span>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <i class="fas fa-bell me-1"></i> Reminder: <?= $dayBefore ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span><?= htmlspecialchars($time) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['collector_username'])): ?>
                                        <span class="collector-name"><?= htmlspecialchars($row['collector_username']) ?></span>
                                    <?php else: ?>
                                        <span class="no-collector">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $row['collection_status'] ?? 'Pending';
                                    if($status == 'Pending'): ?>
                                        <span class="status-badge badge-pending">
                                            <i class="fas fa-clock me-1"></i> Pending
                                        </span>
                                    <?php elseif($status == 'On the Way'): ?>
                                        <span class="status-badge badge-onway">
                                            <i class="fas fa-truck-moving me-1"></i> On Way
                                        </span>
                                    <?php elseif($status == 'Collection Started'): ?>
                                        <span class="status-badge badge-started">
                                            <i class="fas fa-play-circle me-1"></i> Started
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge badge-pending">
                                            <i class="fas fa-question-circle me-1"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-reschedule btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#rescheduleModal"
                                            data-schedule-id="<?= $row['id'] ?>"
                                            data-current-date="<?= htmlspecialchars($row['date']) ?>"
                                            data-current-time="<?= htmlspecialchars($row['time']) ?>">
                                        <i class="fas fa-calendar-edit me-1"></i> Reschedule
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD SCHEDULE MODAL -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="add_schedule_process.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i> Add Schedule
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Barangay</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($admin_barangay) ?>" readonly>
                    <input type="hidden" name="barangay" value="<?= htmlspecialchars($admin_barangay) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Collection Date</label>
                    <input type="date" class="form-control" name="date" required min="<?= date('Y-m-d') ?>">
                    <small class="text-muted">Automatic reminder will be sent day before</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Collection Time</label>
                    <input type="time" class="form-control" name="time" required>
                    <small class="text-muted">Time of collection</small>
                </div>
                
                <div class="alert alert-info p-2">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Automatic SMS reminder will be sent to all citizens the day before collection.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" class="btn-custom">
                    <i class="fas fa-check-circle me-2"></i> Add Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- RESCHEDULE MODAL -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="rescheduleForm" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-edit me-2"></i> Reschedule Collection
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="schedule_id" name="schedule_id">
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Current Schedule</label>
                    <div class="alert alert-info p-2">
                        <div id="currentSchedule"></div>
                    </div>
                </div>
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">New Date</label>
                            <input type="date" id="new_date" name="new_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                            <small class="text-muted">Future date</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">New Time</label>
                            <input type="time" id="new_time" name="new_time" class="form-control" required>
                            <small class="text-muted">Collection time</small>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">SMS Notification</label>
                    <div class="sms-preview">
                        <div class="sms-preview-header">
                            <span>To: All residents</span>
                            <span><i class="fas fa-sms"></i> SMS</span>
                        </div>
                        <div id="smsPreview">
                            <em>Set new date and time to see SMS...</em>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-bell me-1"></i> Auto-reminder will also be sent day before new date
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="submit" name="reschedule" class="btn-reschedule">
                    <i class="fas fa-paper-plane me-2"></i> Send SMS & Reschedule
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search Functionality
    const searchBar = document.getElementById('searchBar');
    if (searchBar) {
        searchBar.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#scheduleTable tr');
            
            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Set minimum date to today
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        const today = new Date().toISOString().split('T')[0];
        input.min = today;
        if (!input.value) {
            input.value = today;
        }
    });

    // Set default time to next hour for add modal
    const addTimeInput = document.querySelector('#addModal input[name="time"]');
    if (addTimeInput) {
        const now = new Date();
        const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
        const hours = String(nextHour.getHours()).padStart(2, '0');
        const minutes = String(nextHour.getMinutes()).padStart(2, '0');
        addTimeInput.value = `${hours}:${minutes}`;
    }

    // Form submission loading
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            document.querySelector('.loading-overlay').style.display = 'flex';
        });
    });

    // Reschedule Modal Setup
    const rescheduleModal = document.getElementById('rescheduleModal');
    if (rescheduleModal) {
        rescheduleModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const scheduleId = button.getAttribute('data-schedule-id');
            const currentDate = button.getAttribute('data-current-date');
            const currentTime = button.getAttribute('data-current-time');
            
            // Format current date and time
            const currentDateObj = new Date(currentDate);
            const formattedDate = currentDateObj.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            
            // Format time properly
            let formattedTime;
            if (currentTime.includes('AM') || currentTime.includes('PM')) {
                formattedTime = currentTime; // Already formatted
            } else {
                const timeParts = currentTime.split(':');
                const hours = parseInt(timeParts[0]);
                const minutes = timeParts[1];
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const displayHours = hours % 12 || 12;
                formattedTime = `${displayHours}:${minutes} ${ampm}`;
            }
            
            // Set form values
            document.getElementById('schedule_id').value = scheduleId;
            document.getElementById('currentSchedule').innerHTML = 
                `<strong>${formattedDate} at ${formattedTime}</strong>`;
            
            // Set default new date/time (next day at same time)
            const nextDay = new Date(currentDate);
            nextDay.setDate(nextDay.getDate() + 1);
            const newDateInput = document.getElementById('new_date');
            const newTimeInput = document.getElementById('new_time');
            
            newDateInput.value = nextDay.toISOString().split('T')[0];
            newTimeInput.value = currentTime;
            
            // Update SMS preview
            updateSMSPreview(currentDateObj, formattedTime, nextDay, currentTime);
        });
    }
    
    // Update SMS preview when date/time changes
    document.getElementById('new_date').addEventListener('change', updateSMSFromForm);
    document.getElementById('new_time').addEventListener('change', updateSMSFromForm);
    
    function updateSMSFromForm() {
        const currentText = document.getElementById('currentSchedule').textContent;
        const newDate = document.getElementById('new_date').value;
        const newTime = document.getElementById('new_time').value;
        
        if (currentText && newDate && newTime) {
            // Extract current date and time from text
            const parts = currentText.split(' at ');
            if (parts.length === 2) {
                const currentDateText = parts[0].replace('strong>', '').replace('</strong', '').trim();
                const currentTimeText = parts[1].replace('strong>', '').replace('</strong', '').trim();
                
                // Parse dates
                const currentDate = new Date(currentDateText);
                const newDateObj = new Date(newDate);
                
                updateSMSPreview(currentDate, currentTimeText, newDateObj, newTime);
            }
        }
    }
    
    function updateSMSPreview(oldDate, oldTime, newDate, newTime) {
        const options = { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        };
        
        const formattedOldDate = oldDate.toLocaleDateString('en-US', options);
        const formattedOldTime = oldTime;
        
        const formattedNewDate = newDate.toLocaleDateString('en-US', options);
        
        // Format new time
        let formattedNewTime;
        if (newTime.includes('AM') || newTime.includes('PM')) {
            formattedNewTime = newTime;
        } else {
            const timeParts = newTime.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = timeParts[1] || '00';
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            formattedNewTime = `${displayHours}:${minutes} ${ampm}`;
        }
        
        const smsMessage = `IMPORTANT: Waste collection rescheduled from ${formattedOldDate} ${formattedOldTime} to ${formattedNewDate} ${formattedNewTime}. Please prepare your waste. - Barangay <?= htmlspecialchars($admin_barangay) ?>`;
        
        document.getElementById('smsPreview').innerHTML = smsMessage;
    }
    
    // Handle reschedule form submission
    const rescheduleForm = document.getElementById('rescheduleForm');
    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate date is in future
            const newDate = new Date(document.getElementById('new_date').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (newDate < today) {
                alert('Error: New date must be today or in the future.');
                return;
            }
            
            // Show confirmation
            if (confirm('Are you sure? This will:\n1. Send reschedule SMS immediately\n2. Schedule reminder for day before new date')) {
                document.querySelector('.loading-overlay').style.display = 'flex';
                this.submit();
            }
        });
    }
    
    // Auto-hide loading overlay after 5 seconds (safety)
    setTimeout(() => {
        document.querySelector('.loading-overlay').style.display = 'none';
    }, 5000);
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
</body>
</html>
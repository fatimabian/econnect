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
$sql = "SELECT cs.id, cs.barangay, cs.date, cs.time, cc.username AS collector_username, (SELECT cs2.status FROM collection_status cs2 WHERE cs2.schedule_id = cs.id ORDER BY cs2.timestamp DESC LIMIT 1) AS collection_status FROM collection_schedule cs LEFT JOIN collection_crew cc ON cc.barangay = cs.barangay WHERE cs.barangay = ? AND cs.date >= CURDATE() ORDER BY cs.date ASC, cs.time ASC";
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

<div class="loading-overlay"><div class="spinner"></div></div>

<div class="content-area">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="header-card d-flex justify-content-between align-items-start">
            <div>
                <h3>
                    <i class="fas fa-calendar-days me-2"></i>
                    Collection Management 
                    <small class="badge ms-2">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= htmlspecialchars($admin_barangay) ?>
                    </small>
                </h3>
                <p class="mb-0">
                    <i class="fas fa-bell me-1"></i>
                    Automatic reminders sent one day before collection
                </p>
            </div>

            <div class="d-flex gap-2">
                <button class="btn-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus-circle me-1"></i>
                    Add Schedule
                </button>
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

    <?php if (!empty($reminder_ids) && $reminders_sent_count >= 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-bell me-1"></i>
            <?= count($reminder_ids) ?> reminder(s) sent for collections on <?= date('F j, Y', strtotime($tomorrow)) ?>.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-2">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i></th>
                            <th><i class="fas fa-calendar-day me-1"></i> Date</th>
                            <th><i class="fas fa-clock me-1"></i> Time</th>
                            <th><i class="fas fa-user-alt me-1"></i> Collector</th>
                            <th><i class="fas fa-info-circle me-1"></i> Status</th>
                            <th><i class="fas fa-gear me-1"></i> Action</th>
                        </tr>
                    </thead>

                    <tbody id="scheduleTable">
                        <?php if (empty($schedules)): ?>
                            <tr><td colspan="6" class="text-center py-4">No Active Schedules</td></tr>
                        <?php else: $i=1; foreach($schedules as $r): 
                            $dateObj = DateTime::createFromFormat('Y-m-d', $r['date']);
                            $formattedDate = $dateObj ? $dateObj->format('M j, Y') : $r['date'];
                            $dayBefore = $dateObj ? $dateObj->modify('-1 day')->format('M j, Y') : '-';
                            $time = $r['time'] ? date('g:i A', strtotime($r['time'])) : '-';
                            $status = $r['collection_status'];
                            ?>
                            <tr>
                                <td class="fw-bold"><?= $i++ ?></td>
                                <td>
                                    <div><?= htmlspecialchars($formattedDate) ?></div>
                                    <small class="text-muted">Reminder: <?= htmlspecialchars($dayBefore) ?></small>
                                </td>
                                <td><?= htmlspecialchars($time) ?></td>
                                <td><?= htmlspecialchars($r['collector_username'] ?: 'Not Assigned') ?></td>
                                <td>
                                    <?php if ($status == 'Pending'): ?>
                                        <span class="status-badge badge-pending"><i class="fas fa-clock me-1"></i> Pending</span>
                                    <?php elseif ($status == 'On the Way'): ?>
                                        <span class="status-badge badge-onway"><i class="fas fa-truck-moving me-1"></i> On the Way</span>
                                    <?php elseif ($status == 'Collection Started'): ?>
                                        <span class="status-badge badge-started"><i class="fas fa-play me-1"></i> Started</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-pending"><i class="fas fa-question me-1"></i> <?= htmlspecialchars($status) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-reschedule btn-sm" 
                                        data-bs-toggle="modal" data-bs-target="#rescheduleModal"
                                        data-schedule-id="<?= $r['id'] ?>"
                                        data-current-date="<?= htmlspecialchars($r['date']) ?>"
                                        data-current-time="<?= htmlspecialchars($r['time']) ?>">
                                        <i class="fas fa-calendar-pen me-1"></i> Reschedule
                                    </button>

                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ADD SCHEDULE MODAL -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" onsubmit="document.querySelector('.loading-overlay').style.display='flex'">
      <input type="hidden" name="action" value="add_schedule">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus-circle me-1"></i> Add Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Barangay</label>
          <input class="form-control" value="<?= htmlspecialchars($admin_barangay) ?>" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Collection Date</label>
          <input type="date" name="date" class="form-control" required min="<?= date('Y-m-d') ?>">
          <small class="text-muted">Automatic reminder will be sent day before</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Collection Time</label>
          <input type="time" name="time" class="form-control" required>
        </div>
        <div class="alert alert-info">Automatic SMS reminder will be sent to all active residents the day before.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn-custom"><i class="fas fa-check me-1"></i> Add Schedule</button>
      </div>
    </form>
  </div>
</div>

<!-- RESCHEDULE MODAL -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" id="rescheduleForm" class="modal-content" onsubmit="document.querySelector('.loading-overlay').style.display='flex'">
      <input type="hidden" name="action" value="reschedule">
      <input type="hidden" name="schedule_id" id="rs_schedule_id">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-calendar-edit me-1"></i> Reschedule Collection</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Current Schedule</label>
          <div class="alert alert-info p-2" id="currentSchedule">--</div>
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">New Date</label>
              <input type="date" id="new_date" name="new_date" class="form-control" required min="<?= date('Y-m-d') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="form-label">New Time</label>
              <input type="time" id="new_time" name="new_time" class="form-control" required>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">SMS Preview</label>
          <div class="sms-preview" id="smsPreview">Set new date/time to preview the SMS.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn-reschedule"><i class="fas fa-paper-plane me-1"></i> Send SMS & Reschedule</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // set default date/time inputs
    document.querySelectorAll('input[type="date"]').forEach(i => {
        const today = new Date().toISOString().split('T')[0];
        i.min = today;
    });

    // reschedule modal fill
    var resModal = document.getElementById('rescheduleModal');
    if (resModal) {
        resModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            var sid = btn.getAttribute('data-schedule-id');
            var curDate = btn.getAttribute('data-current-date');
            var curTime = btn.getAttribute('data-current-time');

            // populate fields
            document.getElementById('rs_schedule_id').value = sid;
            var formattedDate = (new Date(curDate)).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
            var formattedTime = curTime ? (function(){
                var t = curTime.split(':');
                if (t.length>=2) {
                    var hr = parseInt(t[0]), min = t[1];
                    var ampm = hr >= 12 ? 'PM' : 'AM';
                    hr = hr % 12 || 12;
                    return hr + ':' + min + ' ' + ampm;
                }
                return curTime;
            })() : '-';
            document.getElementById('currentSchedule').innerHTML = '<strong>' + formattedDate + ' at ' + formattedTime + '</strong>';

            // IMPORTANT: do NOT auto-fill new date/time — admin must input them manually
            // Clear the inputs so admin must enter the desired date/time
            document.getElementById('new_date').value = '';
            document.getElementById('new_time').value = '';

            // Optionally, focus the date field so admin can start typing right away
            setTimeout(() => {
                const ndElem = document.getElementById('new_date');
                if (ndElem) ndElem.focus();
            }, 150);


            updateSMSPreview();
        });
    }

    // update SMS preview on change
    ['new_date','new_time'].forEach(id => {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', updateSMSPreview);
    });

    function updateSMSPreview() {
        var current = document.getElementById('currentSchedule').textContent || '';
        var newDate = document.getElementById('new_date').value;
        var newTime = document.getElementById('new_time').value;
        if (!current || !newDate || !newTime) {
            document.getElementById('smsPreview').textContent = 'Set new date/time to preview the SMS.';
            return;
        }
        var oldText = current.replace(/\\s+/g,' ').trim();
        var formattedOld = oldText;
        var nd = new Date(newDate);
        var newDateStr = nd.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
        // format time to h:mm AM/PM
        var tParts = newTime.split(':');
        var hr = parseInt(tParts[0]||0), min = tParts[1]||'00';
        var ampm = hr>=12 ? 'PM' : 'AM';
        var displayHr = hr%12 || 12;
        var newTimeStr = displayHr + ':' + min + ' ' + ampm;

        var sms = 'IMPORTANT: Waste collection rescheduled from ' + oldText + ' to ' + newDateStr + ' ' + newTimeStr + '. Please prepare your waste. - Barangay <?= htmlspecialchars($admin_barangay) ?>';
        document.getElementById('smsPreview').textContent = sms;
    }

    // prevent submit if new date in past
    var rsForm = document.getElementById('rescheduleForm');
    if (rsForm) {
        rsForm.addEventListener('submit', function(e) {
            var nd = document.getElementById('new_date').value;
            if (!nd) { e.preventDefault(); alert('Please set a new date.'); return; }
            var ndObj = new Date(nd); ndObj.setHours(0,0,0,0);
            var today = new Date(); today.setHours(0,0,0,0);
            if (ndObj < today) { e.preventDefault(); alert('New date must be today or later.'); return; }
            if (!confirm('Are you sure you want to reschedule and send SMS now?')) { e.preventDefault(); return; }
            // allow submit - loading overlay already set on form attr
        });
    }

    // simple search filter
    var search = document.getElementById('searchBar');
    if (search) {
        search.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#scheduleTable tr').forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // auto hide alerts after 5s
    setTimeout(()=>{document.querySelectorAll('.alert').forEach(a=>a.classList.remove('show'))},5000);
});
</script>
</body>
</html>
<?php
session_start();
include 'db_connect.php';
include 'header.php';
// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$barangay = $user['barangay'];

// Fetch user's reports
$reportStmt = $conn->prepare("SELECT * FROM complaints WHERE email = ? ORDER BY created_at DESC");
$reportStmt->bind_param("s", $user['email']);
$reportStmt->execute();
$reports = $reportStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Separate pending and resolved reports
$pending_reports = array_filter($reports, fn($r) => $r['status'] !== 'Resolved');
$resolved_reports = array_filter($reports, fn($r) => $r['status'] === 'Resolved');

// Fetch next collection schedule
$nextStmt = $conn->prepare("
    SELECT * 
    FROM collection_schedule 
    WHERE barangay = ? AND date >= CURDATE() 
    ORDER BY date ASC, time ASC 
    LIMIT 1
");
$nextStmt->bind_param("s", $barangay);
$nextStmt->execute();
$nextSchedule = $nextStmt->get_result()->fetch_assoc();

// Fetch admin messages to user
$msgStmt = $conn->prepare("
    SELECT ui.*, ba.full_name AS admin_name
    FROM user_inbox ui
    JOIN barangay_admins ba ON ui.admin_id = ba.id
    WHERE ui.user_id = ?
    ORDER BY ui.created_at DESC
");
$msgStmt->bind_param("i", $user_id);
$msgStmt->execute();
$admin_messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inbox</title>
<style>
.inbox-wrapper {
    margin-left: 250px;
    padding: 20px;
    min-height: 100vh;
    background-color: #f5f5f5;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.message-box {
    background-color: #ffffff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.message-box h4 {
    margin-bottom: 15px;
    color: #333;
    font-weight: 600;
}
.message-entry {
    padding: 10px 15px;
    border-left: 4px solid #007bff;
    border-radius: 4px;
    background-color: #f9f9f9;
    margin-bottom: 10px;
}
.message-entry.resolved {
    border-left-color: #28a745;
    background-color: #e6f7ee;
}
.message-entry.pending {
    border-left-color: #ffc107;
    background-color: #fff8e1;
}
.message-entry p {
    margin: 0 0 5px 0;
    word-wrap: break-word;
}
.message-entry small {
    color: #666;
}
.inbox-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.inbox-col {
    flex: 1;
    min-width: 300px;
}
</style>
</head>
<body>

<div class="inbox-wrapper">
    <h2>Inbox</h2>

    <!-- Next Collection Schedule -->
    <div class="message-box">
        <h4>Next Collection Schedule</h4>
        <?php if($nextSchedule): ?>
            <p>Your next collection is scheduled on 
            <strong><?= date("F d, Y", strtotime($nextSchedule['date'])) ?></strong> 
            at <strong><?= date("g:i a", strtotime($nextSchedule['time'])) ?></strong>.</p>
        <?php else: ?>
            <p>No upcoming collection schedules.</p>
        <?php endif; ?>
    </div>

    <div class="inbox-row">
        <!-- Admin Messages -->
        <div class="inbox-col message-box">
            <h4>Admin Messages</h4>
            <?php if($admin_messages): ?>
                <?php foreach($admin_messages as $msg): ?>
                    <div class="message-entry <?= $msg['status'] == 'Read' ? 'resolved' : 'pending' ?>">
                        <p><?= htmlspecialchars($msg['message']) ?></p>
                        <small>From: <strong><?= htmlspecialchars($msg['admin_name']) ?></strong> | Sent: <?= date("F d, Y h:i a", strtotime($msg['created_at'])) ?></small>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No messages from admin.</p>
            <?php endif; ?>
        </div>

        <!-- Pending Reports -->
        <div class="inbox-col message-box">
            <h4>Pending Reports</h4>
            <?php if($pending_reports): ?>
                <?php foreach($pending_reports as $report): ?>
                    <div class="message-entry pending">
                        <p><?= htmlspecialchars($report['message']) ?></p>
                        <small>Status: <strong><?= $report['status'] ?></strong> | Submitted: <?= date("F d, Y h:i a", strtotime($report['created_at'])) ?></small>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No pending reports.</p>
            <?php endif; ?>
        </div>

        <!-- Resolved Reports -->
        <div class="inbox-col message-box">
            <h4>Resolved Reports</h4>
            <?php if($resolved_reports): ?>
                <?php foreach($resolved_reports as $report): ?>
                    <div class="message-entry resolved">
                        <p><?= htmlspecialchars($report['message']) ?></p>
                        <small>Status: <strong><?= $report['status'] ?></strong> | Submitted: <?= date("F d, Y h:i a", strtotime($report['created_at'])) ?></small>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No resolved reports.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- <?php include 'footer.php'; ?> -->
</body>
</html>

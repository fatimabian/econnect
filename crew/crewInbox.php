<?php
session_start();
include "../db_connect.php";
include "header.php";

// Check if crew is logged in
if (!isset($_SESSION['crew_id'])) {
    header("Location: crewLogin.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];

// Fetch crew info
$stmt = $conn->prepare("SELECT * FROM collection_crew WHERE id = ?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$crew = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch messages from barangay admins
$msgStmt = $conn->prepare("
    SELECT ci.id, ba.full_name AS sender_name, ci.message, ci.status, ci.created_at
    FROM crew_inbox ci
    JOIN barangay_admins ba ON ci.admin_id = ba.id
    WHERE ci.crew_id = ?
    ORDER BY ci.created_at DESC
");
$msgStmt->bind_param("i", $crew_id);
$msgStmt->execute();
$messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$msgStmt->close();

// Fetch upcoming collection schedule
$schedStmt = $conn->prepare("
    SELECT * FROM collection_schedule
    WHERE barangay = ?
    ORDER BY date, time
");
$schedStmt->bind_param("s", $crew['barangay']);
$schedStmt->execute();
$schedules = $schedStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$schedStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crew Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body {
    margin: 0;
    font-family: Georgia, serif;
    background-color: #f8f8f8;
    padding-top: 70px; /* space for fixed header */
}

/* Sidebar */
.sidebar {
    width: 220px;
    position: fixed;
    top: 70px;
    left: 0;
    height: 100%;
    background: #3f4a36;
    color: white;
    padding-top: 20px;
}

.sidebar a {
    color: white;
    display: block;
    padding: 10px 20px;
    text-decoration: none;
}
.sidebar a:hover {
    background: #2f3927;
}

/* Main content */
.main-content {
    margin-left: 220px;
    padding: 20px;
}

/* Tables */
.table thead {
    background-color: #3f4a36;
    color: white;
}
.unread {
    background-color: #e0ffe0;
}

/* Footer */
footer {
    background-color: #3f4a36;
    color: white;
    text-align: center;
    padding: 10px 0;
    margin-top: 30px;
    border-radius: 10px;
}

/* Responsive */
@media (max-width: 767px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }
    .main-content {
        margin-left: 0;
    }
}
</style>
</head>
<body>

<?php include 'nav.php'; ?>

<div class="main-content">
    <h2>Welcome, <?= htmlspecialchars($crew['full_name']) ?></h2>

    <!-- Inbox Messages -->
    <h3 class="mt-4">Inbox</h3>
    <?php if($messages): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Sender</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Received At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($messages as $msg): ?>
                    <tr id="msg-<?= $msg['id'] ?>" class="<?= $msg['status'] == 'Unread' ? 'unread' : '' ?>">
                        <td><?= htmlspecialchars($msg['sender_name']) ?></td>
                        <td><?= htmlspecialchars($msg['message']) ?></td>
                        <td class="status"><?= $msg['status'] ?></td>
                        <td><?= date("F d, Y g:i a", strtotime($msg['created_at'])) ?></td>
                        <td>
                            <?php if($msg['status'] == 'Unread'): ?>
                                <button class="btn btn-success btn-sm mark-read" data-id="<?= $msg['id'] ?>">Mark Read</button>
                            <?php endif; ?>
                            <button class="btn btn-danger btn-sm delete-msg" data-id="<?= $msg['id'] ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No messages yet.</p>
    <?php endif; ?>

    <!-- Collection Schedule -->
    <h3 class="mt-5">Upcoming Collection Schedule (<?= htmlspecialchars($crew['barangay']) ?>)</h3>
    <?php if($schedules): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($schedules as $sched): ?>
                    <tr>
                        <td><?= date("F d, Y", strtotime($sched['date'])) ?></td>
                        <td><?= date("g:i a", strtotime($sched['time'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No scheduled collections.</p>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Mark as read
    $('.mark-read').click(function() {
        let msgId = $(this).data('id');
        $.get('crewDashboard_action.php', { mark_read: msgId }, function() {
            // Update row dynamically
            let row = $('#msg-' + msgId);
            row.removeClass('unread');
            row.find('.status').text('Read');
            row.find('.mark-read').remove();
        });
    });

    // Delete message
    $('.delete-msg').click(function() {
        if(!confirm('Are you sure you want to delete this message?')) return;
        let msgId = $(this).data('id');
        $.get('crewDashboard_action.php', { delete_msg: msgId }, function() {
            $('#msg-' + msgId).fadeOut(300, function() { $(this).remove(); });
        });
    });
});
</script>

</body>
</html>

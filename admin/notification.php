<?php
session_start();
include "db_connect.php";

// Check if barangay admin is logged in
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// ----------------------
// Fetch Crew Reports
// ----------------------
$crew_messages = [];
$sql_crew = "
SELECT cr.id, cr.report_title, cr.report_message, cr.created_at, c.username AS crew_username,
       ci.id AS inbox_id, ci.status
FROM crew_reports cr
JOIN collection_crew c ON cr.crew_id = c.id
LEFT JOIN crew_inbox ci ON ci.crew_id = c.id AND ci.admin_id = ?
WHERE c.barangay = (SELECT barangay FROM barangay_admins WHERE id = ?)
ORDER BY cr.created_at DESC
";
$stmt = $conn->prepare($sql_crew);
$stmt->bind_param("ii", $admin_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()){
    $crew_messages[] = $row;
}
$stmt->close();

// ----------------------
// Fetch Admin Sent Messages
// ----------------------
$admin_sent_messages = [];

// To Crew
$sql_sent_crew = "
SELECT ci.id, ci.message, ci.created_at, c.username AS recipient, 'Crew' AS recipient_type
FROM crew_inbox ci
JOIN collection_crew c ON ci.crew_id = c.id
WHERE ci.admin_id = ?
ORDER BY ci.created_at DESC
";
$stmt2 = $conn->prepare($sql_sent_crew);
$stmt2->bind_param("i", $admin_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while($row = $result2->fetch_assoc()) $admin_sent_messages[] = $row;
$stmt2->close();

// To Users
$sql_sent_users = "
SELECT ui.id, ui.message, ui.created_at, u.username AS recipient, 'User' AS recipient_type
FROM user_inbox ui
JOIN users u ON ui.user_id = u.id
WHERE ui.admin_id = ?
ORDER BY ui.created_at DESC
";
$stmt3 = $conn->prepare($sql_sent_users);
$stmt3->bind_param("i", $admin_id);
$stmt3->execute();
$result3 = $stmt3->get_result();
while($row = $result3->fetch_assoc()) $admin_sent_messages[] = $row;
$stmt3->close();

// Sort all messages by created_at descending
usort($admin_sent_messages, fn($a,$b)=>strtotime($b['created_at']) - strtotime($a['created_at']));

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Notifications</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #eef1ee; font-family: Arial, sans-serif; }
.content { margin-left: 260px; padding: 20px; margin-top: 20px; }
.card { border-radius: 14px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
</style>
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="content">
    <h2 class="fw-bold text-dark mb-4">Notifications</h2>

    <!-- Crew Reports -->
    <div class="card p-3 mb-4">
        <h5 class="mb-3">Messages from Crew Reports</h5>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Crew</th>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Sent At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!empty($crew_messages)): ?>
                <?php $i=1; foreach($crew_messages as $msg): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($msg['crew_username']) ?></td>
                    <td><?= htmlspecialchars($msg['report_title']) ?></td>
                    <td><?= htmlspecialchars($msg['report_message']) ?></td>
                    <td><?= htmlspecialchars($msg['created_at']) ?></td>
                    <td><?= $msg['status'] ?? 'Unread' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted">No messages from crew reports yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Admin Sent Messages -->
    <div class="card p-3">
        <h5 class="mb-3">Messages Sent by Admin</h5>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Recipient</th>
                    <th>Recipient Type</th>
                    <th>Message</th>
                    <th>Sent At</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!empty($admin_sent_messages)): ?>
                <?php $i=1; foreach($admin_sent_messages as $msg): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($msg['recipient']) ?></td>
                    <td><?= htmlspecialchars($msg['recipient_type']) ?></td>
                    <td><?= htmlspecialchars($msg['message']) ?></td>
                    <td><?= htmlspecialchars($msg['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="text-center text-muted">No messages sent by admin yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

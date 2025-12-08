<?php
session_start();
include 'db_connect.php';

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

// -----------------------
// HANDLE TOGGLE READ/UNREAD
// -----------------------
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = $conn->prepare("SELECT status, user_id FROM user_inbox WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $msg = $stmt->get_result()->fetch_assoc();

    if ($msg && $msg['user_id'] == $user_id) {
        $new_status = ($msg['status'] === "Read") ? "Unread" : "Read";
        $stmt2 = $conn->prepare("UPDATE user_inbox SET status=? WHERE id=?");
        $stmt2->bind_param("si", $new_status, $id);
        $stmt2->execute();
    }
    $_SESSION['success'] = "Message status updated.";
    header("Location: inbox.php");
    exit;
}

// -----------------------
// HANDLE DELETE MESSAGE
// -----------------------
if (isset($_POST['delete_message'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM user_inbox WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $_SESSION['success'] = "Message deleted.";
    header("Location: inbox.php");
    exit;
}

// -----------------------
// FETCH MESSAGES
// -----------------------
$messages = [];

// 1. Next Collection Schedule (static, non-toggleable)
$nextStmt = $conn->prepare("SELECT * FROM collection_schedule WHERE barangay=? AND date>=CURDATE() ORDER BY date ASC, time ASC LIMIT 1");
$nextStmt->bind_param("s", $barangay);
$nextStmt->execute();
$nextSchedule = $nextStmt->get_result()->fetch_assoc();
if ($nextSchedule) {
    $messages[] = [
        'type' => 'Collection Schedule',
        'subject' => 'Next Collection',
        'message' => "Scheduled on " . date("F d, Y", strtotime($nextSchedule['date'])) . " at " . date("g:i a", strtotime($nextSchedule['time'])),
        'status' => 'Unread',
        'created_at' => $nextSchedule['date'] . ' ' . $nextSchedule['time'],
        'id' => null // No toggle/delete
    ];
}

// 2. Admin Messages (from user_inbox)
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

foreach ($admin_messages as $msg) {
    $messages[] = [
        'type' => 'Announcement',
        'subject' => 'From Admin: ' . $msg['admin_name'],
        'message' => $msg['message'],
        'status' => $msg['status'],
        'id' => $msg['id'],
        'created_at' => $msg['created_at']
    ];
}

// 3. User Reports (complaints)
$reportStmt = $conn->prepare("SELECT * FROM complaints WHERE email=? ORDER BY created_at DESC");
$reportStmt->bind_param("s", $user['email']);
$reportStmt->execute();
$reports = $reportStmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($reports as $r) {
    $messages[] = [
        'type' => 'Report',
        'subject' => ucfirst($r['status']) . ' Report',
        'message' => $r['message'],
        'status' => ($r['status'] === 'Resolved') ? 'Read' : 'Unread',
        'id' => $r['id'],
        'created_at' => $r['created_at']
    ];
}

// Sort messages by created_at descending
usort($messages, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Inbox</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: rgba(68,64,51,0.4) !important; font-family: Arial, sans-serif; padding-top: 80px; padding-left: 70px; }
.card { border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.action-btn { display: flex; align-items: center; gap: 5px; background: white; border: 1px solid #ddd; padding: 4px 10px; border-radius: 6px; font-size: 0.9rem; cursor: pointer; text-decoration: none; color: black; }
.action-btn:hover { background: #f2f2f2; }
.modal-header { background: #3f4a36; color: white; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-4">
    <h2 class="mb-4">Inbox</h2>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="card p-3">
        <input type="text" id="searchBar" class="form-control mb-3" placeholder="Search messages...">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Sent At</th>
                        <th>Status</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="messageTable">
                    <?php if(!empty($messages)): $i=1; foreach($messages as $msg): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($msg['type']) ?></td>
                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                        <td><?= htmlspecialchars($msg['message']) ?></td>
                        <td><?= date("F d, Y h:i a", strtotime($msg['created_at'])) ?></td>
                        <td>
                            <span class="badge <?= ($msg['status']==='Read') ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $msg['status'] ?>
                            </span>
                        </td>
                        <td class="d-flex gap-2">
                            <?php if(isset($msg['id']) && $msg['id'] !== null): ?>
                                <a href="?toggle_id=<?= $msg['id'] ?>" class="action-btn">
                                    <i class="bi <?= ($msg['status']==='Read') ? 'bi-envelope-open' : 'bi-envelope' ?>"></i>
                                    <?= ($msg['status']==='Read') ? 'Unread' : 'Read' ?>
                                </a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                                    <button type="submit" name="delete_message" class="action-btn"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No messages yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("searchBar").addEventListener("keyup", function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll("#messageTable tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? "" : "none";
    });
});
</script>

</body>
</html>

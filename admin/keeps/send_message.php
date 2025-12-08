<?php
session_start();
include "db_connect.php";

// ---------------------------
// CHECK IF BARANGAY ADMIN LOGGED IN
// ---------------------------
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// ---------------------------
// GET ADMIN BARANGAY
// ---------------------------
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_barangay = $stmt->get_result()->fetch_assoc()['barangay'] ?? '';
$stmt->close();

// ---------------------------
// HANDLE POST REQUESTS
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // SEND MESSAGE
    if (isset($_POST['send_message'])) {
        $recipient_type = $_POST['recipient_type'];
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $phones = [];

        // Save to Crew Inbox
        if ($recipient_type === 'crew' || $recipient_type === 'both') {
            $crew_list = $conn->prepare("SELECT id, phone FROM collection_crew WHERE barangay = ?");
            $crew_list->bind_param("s", $admin_barangay);
            $crew_list->execute();
            $crew_result = $crew_list->get_result();
            while ($row = $crew_result->fetch_assoc()) {
                $stmt = $conn->prepare("INSERT INTO crew_inbox (admin_id, crew_id, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $admin_id, $row['id'], $subject, $message);
                $stmt->execute();
                $stmt->close();

                if (!empty($row['phone'])) {
                    $phone = preg_replace('/\s+/', '', $row['phone']);
                    if (preg_match('/^09\d{9}$/', $phone)) $phone = '+63' . substr($phone, 1);
                    elseif (!str_starts_with($phone, '+63')) $phone = '+63' . $phone;
                    $phones[] = $phone;
                }
            }
            $crew_list->close();
        }

        // Save to User Inbox
        if ($recipient_type === 'user' || $recipient_type === 'both') {
            $user_list = $conn->prepare("SELECT id, contact FROM users WHERE barangay = ?");
            $user_list->bind_param("s", $admin_barangay);
            $user_list->execute();
            $user_result = $user_list->get_result();
            while ($row = $user_result->fetch_assoc()) {
                $stmt = $conn->prepare("INSERT INTO user_inbox (admin_id, user_id, subject, message) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $admin_id, $row['id'], $subject, $message);
                $stmt->execute();
                $stmt->close();

                if (!empty($row['contact'])) {
                    $phone = preg_replace('/\s+/', '', $row['contact']);
                    if (preg_match('/^09\d{9}$/', $phone)) $phone = '+63' . substr($phone, 1);
                    elseif (!str_starts_with($phone, '+63')) $phone = '+63' . $phone;
                    $phones[] = $phone;
                }
            }
            $user_list->close();
        }

        // Send SMS via iProg
        if (!empty($phones)) {
            $api_token = "dda33f23a9d96e5f433c56d8907c072b40830ef7";
            $sms_message = "$subject: $message";
            $url = "https://sms.iprogtech.com/api/v1/sms_messages";

            foreach ($phones as $phone) {
                $data = [
                    "api_token"    => $api_token,
                    "phone_number" => $phone,
                    "message"      => $sms_message
                ];

                $options = [
                    'http' => [
                        'header'  => "Content-Type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($data),
                    ]
                ];

                $context = stream_context_create($options);
                $response = @file_get_contents($url, false, $context);

                if ($response === FALSE) {
                    file_put_contents('sms_error_log.txt', date('Y-m-d H:i:s') . " - Failed to send to $phone".PHP_EOL, FILE_APPEND);
                } else {
                    file_put_contents('sms_log.txt', date('Y-m-d H:i:s') . " - Sent to $phone: $response".PHP_EOL, FILE_APPEND);
                }
            }
        }

        $_SESSION['success'] = "Message sent successfully!";
        header("Location: send_message.php");
        exit;
    }

    // DELETE MESSAGE
    if (isset($_POST['delete_message'])) {
        $msg_id = $_POST['msg_id'];

        $stmt = $conn->prepare("DELETE ci FROM crew_inbox ci
                                INNER JOIN collection_crew c ON ci.crew_id = c.id
                                WHERE ci.id=? AND ci.admin_id=? AND c.barangay=?");
        $stmt->bind_param("iis", $msg_id, $admin_id, $admin_barangay);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $stmt2 = $conn->prepare("DELETE ui FROM user_inbox ui
                                     INNER JOIN users u ON ui.user_id = u.id
                                     WHERE ui.id=? AND ui.admin_id=? AND u.barangay=?");
            $stmt2->bind_param("iis", $msg_id, $admin_id, $admin_barangay);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();

        $_SESSION['success'] = "Message deleted successfully!";
        header("Location: send_message.php");
        exit;
    }
}

// ---------------------------
// FETCH SENT MESSAGES
// ---------------------------
$sql = "
SELECT 'Collection Crew' AS recipient_type, ci.subject, ci.message, ci.created_at, ci.id AS msg_id
FROM crew_inbox ci
INNER JOIN collection_crew c ON ci.crew_id = c.id
WHERE ci.admin_id = ? AND c.barangay = ?

UNION ALL

SELECT 'Citizens' AS recipient_type, ui.subject, ui.message, ui.created_at, ui.id AS msg_id
FROM user_inbox ui
INNER JOIN users u ON ui.user_id = u.id
WHERE ui.admin_id = ? AND u.barangay = ?

ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isis", $admin_id, $admin_barangay, $admin_id, $admin_barangay);
$stmt->execute();
$result = $stmt->get_result();
$sent_messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Message</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: rgba(68,64,51,0.4) !important; 
    font-family: Arial, sans-serif; 
    padding-top:70px; 
    padding-left:70px; }

.card { 
    border-radius:14px; 
    box-shadow:0 4px 12px rgba(0,0,0,0.08); 
}

h2 { color:#3f4a36; }

.icon-btn { 
    background:none; border:none; 
    color:black; padding:6px 10px; 
    border-radius:6px; font-size:1rem; 
    outline:none; }

.send-btn { 
    background:none; 
    border:none; 
    color:black; 
    padding:6px 10px; 
    border-radius:6px; 
    font-size:1rem; 
    outline:2px solid black; }

.modal-header { 
    background-color:#3f4a36; 
    color:white; }
    
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw mb-4">Announcements - <?= htmlspecialchars($admin_barangay) ?></h2>

        <!-- Send Message Button -->
        <button class="send-btn" data-bs-toggle="modal" data-bs-target="#sendModal">
            <i class="bi bi-envelope"></i> Send Message
        </button>
    </div>

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
                        <th>Recipient Type</th>
                        <th>Subject</th>
                        <th>Sent At</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody id="messageTable">
                <?php if(!empty($sent_messages)): $i=1; foreach($sent_messages as $msg): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= $msg['recipient_type'] ?></td>
                        <td><?= htmlspecialchars($msg['subject']) ?></td>
                        <td><?= $msg['created_at'] ?></td>
                        <td class="d-flex gap-1">
                            <!-- View Button -->
                            <button class="icon-btn"
                                data-bs-toggle="modal" data-bs-target="#viewModal"
                                data-recipient="<?= $msg['recipient_type'] ?>"
                                data-subject="<?= htmlspecialchars($msg['subject']) ?>"
                                data-message="<?= htmlspecialchars($msg['message']) ?>">
                                <i class="bi bi-eye"></i> View
                            </button>

                            <!-- Delete Button -->
                            <button class="icon-btn text-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-id="<?= $msg['msg_id'] ?>"
                                data-recipient="<?= $msg['recipient_type'] ?>">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center text-muted">No messages yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SEND MESSAGE MODAL -->
<div class="modal fade" id="sendModal">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content card p-3">
        <div class="modal-header">
            <h5 class="modal-title">Send New Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Recipient Type</label>
                <select name="recipient_type" class="form-select" required>
                    <option value="crew">Collection Crew</option>
                    <option value="user">Citizens</option>
                    <option value="both">Both Crew & Citizens</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" rows="4" class="form-control" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" name="send_message" class="send-btn">
                <i class="bi bi-send"></i> Send
            </button>
        </div>
    </form>
  </div>
</div>

<!-- VIEW MESSAGE MODAL -->
<div class="modal fade" id="viewModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card p-3">
        <div class="modal-header">
            <h5 class="modal-title">View Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p><strong>Recipient:</strong> <span id="view_recipient"></span></p>
            <p><strong>Subject:</strong> <span id="view_subject"></span></p>
            <p><strong>Message:</strong></p>
            <p id="view_message" style="white-space: pre-wrap;"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="icon-btn" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content card p-3">
        <div class="modal-header">
            <h5 class="modal-title">Delete Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            Delete message sent to <strong id="deleteRecipient"></strong>?
            <input type="hidden" name="msg_id" id="delete_id">
        </div>
        <div class="modal-footer">
            <button type="button" class="icon-btn" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_message" class="icon-btn text-danger">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search filter
document.getElementById("searchBar").addEventListener("keyup", function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll("#messageTable tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? "" : "none";
    });
});

// Fill Delete Modal
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    this.querySelector('#delete_id').value = btn.dataset.id;
    this.querySelector('#deleteRecipient').textContent = btn.dataset.recipient;
});

// Fill View Modal
document.getElementById('viewModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    this.querySelector('#view_recipient').textContent = btn.dataset.recipient;
    this.querySelector('#view_subject').textContent = btn.dataset.subject;
    this.querySelector('#view_message').textContent = btn.dataset.message;
});
</script>

</body>
</html>

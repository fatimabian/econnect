<?php
session_start();
include "../db_connect.php";

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

// Handle toggle read/unread
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $stmt = $conn->prepare("SELECT status FROM crew_inbox WHERE id=? AND crew_id=?");
    $stmt->bind_param("ii", $id, $crew_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $new_status = ($res['status'] === 'Unread') ? 'Read' : 'Unread';
        $stmt2 = $conn->prepare("UPDATE crew_inbox SET status=? WHERE id=?");
        $stmt2->bind_param("si", $new_status, $id);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();
    header("Location: crewInbox.php");
    exit;
}

// Fetch messages with subject and sender username
$msgStmt = $conn->prepare("
    SELECT ci.id, ba.username AS sender_name, ci.subject, ci.message, ci.status, ci.created_at
    FROM crew_inbox ci
    JOIN barangay_admins ba ON ci.admin_id = ba.id
    WHERE ci.crew_id = ?
    ORDER BY ci.created_at DESC
");
$msgStmt->bind_param("i", $crew_id);
$msgStmt->execute();
$messages = $msgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$msgStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crew Inbox</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background: rgba(68,64,51,0.4) !important; 
    padding-top: 80px; 
    padding-left: 70px; 
    font-family: Arial, sans-serif; }

.card { 
    border-radius: 14px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

.action-btn { 
    display:flex; 
    align-items:center; 
    gap:5px; background:white; 
    border:1px solid #ddd; 
    padding:4px 10px; 
    border-radius:6px; 
    font-size:0.9rem; 
    cursor:pointer; 
    text-decoration:none; 
    color:black; }

.action-btn:hover { background:#f2f2f2; }

.modal-header { 
    background:#3f4a36; 
    color:white; }

.badge-unread { background-color: #6c757d; }

.badge-read { background-color: #198754; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-4">
    <h2 class="fw mb-4">Inbox - <?= htmlspecialchars($crew['username']) ?></h2>

    <div class="card p-3">
        <input type="text" id="searchBar" class="form-control mb-3" placeholder="Search messages...">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Sent At</th>
                        <th>Status</th>
                        <th style="width: 250px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="messageTable">
                <?php if (!empty($messages)): $i=1; foreach ($messages as $msg): ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><?= htmlspecialchars($msg['sender_name']); ?></td>
                        <td><?= htmlspecialchars($msg['subject']); ?></td>
                        <td><?= date("F d, Y g:i a", strtotime($msg['created_at'])); ?></td>
                        <td>
                            <?php if ($msg['status'] === "Read"): ?>
                                <span class="badge badge-read">Read</span>
                            <?php else: ?>
                                <span class="badge badge-unread">Unread</span>
                            <?php endif; ?>
                        </td>
                        <td class="d-flex gap-2">
                            <a href="?toggle_id=<?= $msg['id'] ?>" class="action-btn">
                                <?php if($msg['status']==='Read'): ?>
                                    <i class="bi bi-envelope-open"></i> Unread
                                <?php else: ?>
                                    <i class="bi bi-envelope"></i> Read
                                <?php endif; ?>
                            </a>
                            <button class="action-btn" data-bs-toggle="modal" data-bs-target="#viewModal"
                                data-sender="<?= htmlspecialchars($msg['sender_name']) ?>"
                                data-subject="<?= htmlspecialchars($msg['subject']) ?>"
                                data-message="<?= htmlspecialchars($msg['message']) ?>"
                                data-date="<?= htmlspecialchars($msg['created_at']) ?>">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button class="action-btn" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-id="<?= $msg['id'] ?>"
                                data-message="<?= htmlspecialchars($msg['message']) ?>">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No messages yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="viewModalSubject"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p><strong>Sender:</strong> <span id="viewModalSender"></span></p>
            <p><strong>Sent At:</strong> <span id="viewModalDate"></span></p>
            <hr>
            <p id="viewModalMessage"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" action="crewInbox_action.php">
        <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-trash"></i> Delete Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            Are you sure you want to delete:  
            <strong id="deleteMessageText"></strong>?
            <input type="hidden" name="delete_id" id="delete_id">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_message" class="btn btn-danger">Delete</button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fill Delete Modal
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    this.querySelector('#delete_id').value = btn.dataset.id;
    this.querySelector('#deleteMessageText').textContent = btn.dataset.message;
});

// Fill View Modal
document.getElementById('viewModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    this.querySelector('#viewModalSubject').textContent = btn.dataset.subject;
    this.querySelector('#viewModalSender').textContent = btn.dataset.sender;
    this.querySelector('#viewModalMessage').textContent = btn.dataset.message;
    this.querySelector('#viewModalDate').textContent = btn.dataset.date;
});

// Search Filter
document.getElementById("searchBar").addEventListener("keyup", function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll("#messageTable tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? "" : "none";
    });
});
</script>
</body>
</html>

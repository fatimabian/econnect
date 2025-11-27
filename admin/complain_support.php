<?php
session_start();
include "db_connect.php";

// ---------------------------
// CHECK IF ADMIN IS LOGGED IN
// ---------------------------
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
$is_superadmin = $_SESSION['super_admin_id'] ?? false;

if (!$admin_id && !$is_superadmin) {
    header("Location: ../login.php");
    exit;
}

// Get admin barangay if logged in as barangay admin
$admin_barangay = '';
if ($admin_id) {
    $stmt = $conn->prepare("SELECT barangay, full_name FROM barangay_admins WHERE id=?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->bind_result($admin_barangay, $admin_name);
    $stmt->fetch();
    $stmt->close();
}

// ---------------------------
// HANDLE AJAX ACTIONS
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false];
    $id = intval($_POST['id']);

    if ($_POST['action'] === "resolve") {
        $stmt = $conn->prepare("UPDATE complaints SET status='Resolved' WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $response['success'] = true;
        $stmt->close();
    }

    if ($_POST['action'] === "delete") {
        $stmt = $conn->prepare("DELETE FROM complaints WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $response['success'] = true;
        $stmt->close();
    }

    echo json_encode($response);
    exit;
}

// ---------------------------
// FETCH PENDING COMPLAINTS
// ---------------------------
$pending = [];
if ($is_superadmin) {
    $sql = "SELECT * FROM complaints ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM complaints WHERE status='Pending' AND message LIKE ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $barangay_like = "%$admin_barangay%";
    $stmt->bind_param("s", $barangay_like);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ECOnnect - Complaints & Support</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

<style>
body {
    background: rgba(68,64,51,0.4) !important;
    font-family: Arial, sans-serif;
    padding-top: 0px;
}
.content-area { margin-left: 260px; padding: 25px; }
.table-card { background: white; padding: 20px; border-radius: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-bottom: 35px; }
.status-pending { color: #000; font-weight: 700; }
.icon-btn { background: none; border: none; color: black; font-size: 1rem; display: flex; align-items: center; gap: 5px; }
.icon-btn:hover { color: #555; }
.modal-header { background: #3f4a36 !important; color: white !important; }
.modal-close-btn { background: none; border: none; color: white; font-size: 1.3rem; }
.modal-close-btn:hover { color: #ddd; }
</style>
</head>
<body>

<?php include "header.php"; ?>

<div class="content-area">
    <h2 class="fw mb-4">Pending Complaints <?= $admin_id ? " - ".htmlspecialchars($admin_barangay) : "" ?></h2>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th class="text-center" style="width: 190px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingTable">
                    <?php if (!empty($pending)): $i=1; foreach ($pending as $row): ?>
                        <tr id="row-<?= $row['id'] ?>">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td>
                                <button class="icon-btn btn-view-message" 
                                        data-message="<?= htmlspecialchars($row['message'], ENT_QUOTES) ?>">
                                    <i class="bi bi-chat-dots"></i> View
                                </button>
                            </td>
                            <td class="status-pending"><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= $row['created_at'] ?></td>
                            <td class="text-center d-flex gap-2 justify-content-center">
                                <?php if($row['status'] === 'Pending'): ?>
                                <button class="icon-btn btn-resolve" data-id="<?= $row['id'] ?>">
                                    <i class="bi bi-check-circle"></i> Resolve
                                </button>
                                <?php endif; ?>
                                <button class="icon-btn btn-delete text-danger" data-id="<?= $row['id'] ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No pending complaints.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VIEW MESSAGE MODAL -->
<div class="modal fade" id="viewMessageModal">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-chat-dots"></i> Full Message</h5>
        <button class="modal-close-btn" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body" id="modalMessageContent" style="white-space: pre-wrap;"></div>
      <div class="modal-footer">
        <button class="btn btn-dark btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function handleAction(action, id) {
    if (action === "delete" && !confirm("Delete this complaint?")) return;
    fetch("complain_support.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=${action}&id=${id}`
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return alert("Action failed.");
        document.getElementById("row-" + id)?.remove();
    });
}

// Buttons
document.querySelectorAll(".btn-resolve").forEach(btn =>
    btn.addEventListener("click", () => handleAction("resolve", btn.dataset.id))
);

document.querySelectorAll(".btn-delete").forEach(btn =>
    btn.addEventListener("click", () => handleAction("delete", btn.dataset.id))
);

// View message
document.querySelectorAll(".btn-view-message").forEach(btn => {
    btn.addEventListener("click", () => {
        document.getElementById("modalMessageContent").textContent = btn.dataset.message;
        new bootstrap.Modal(document.getElementById('viewMessageModal')).show();
    });
});
</script>
</body>
</html>

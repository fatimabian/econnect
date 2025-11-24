<?php
session_start();
include "db_connect.php";

// ==========================
// HANDLE ACTIONS (AJAX)
// ==========================
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

// ==========================
// FETCH COMPLAINTS
// ==========================
$pending = [];
$resolved = [];

$stmt = $conn->prepare("SELECT * FROM complaints ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Pending') {
        $pending[] = $row;
    } else {
        $resolved[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ECOnnect - Complaints & Support</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { 
    background: #f0f2f0; 
    font-family: Arial, sans-serif; 
}

.content-area { 
    margin-left: 260px; 
    padding: 20px; 
    margin-top: 20px; 
}

.table-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    margin-bottom: 30px;
}

.status-pending { color: #ff9800; font-weight: 600; }
.status-resolved { color: #2e7d32; font-weight: 600; }

td {
    white-space: pre-wrap;   /* preserves line breaks */
    word-wrap: break-word;   /* wraps long words */
    max-width: 250px;        /* optional max width */
}

.btn-sm { font-size: 0.8rem; }
</style>
</head>

<body>

<?php include "header.php"; ?>
<?php include "nav.php"; ?>

<div class="content-area">

    <!-- ========================== -->
    <!-- PENDING TABLE -->
    <!-- ========================== -->
    <div class="table-card">
        <h3 class="mb-3">Pending Complaints</h3>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingTable">
                    <?php if (count($pending) > 0): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($pending as $row): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-view-message" 
                                            data-message="<?= htmlspecialchars($row['message'], ENT_QUOTES) ?>">View Message</button>
                                </td>
                                <td class="status-pending">Pending</td>
                                <td><?= $row['created_at'] ?></td>
                                <td>
                                    <button class="btn btn-success btn-sm btn-resolve" data-id="<?= $row['id'] ?>">Resolve</button>
                                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $row['id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No pending complaints.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================== -->
    <!-- RESOLVED TABLE -->
    <!-- ========================== -->
    <div class="table-card">
        <h3 class="mb-3">Resolved Complaints</h3>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Resolved At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="resolvedTable">
                    <?php if (count($resolved) > 0): ?>
                        <?php $i = 1; ?>
                        <?php foreach ($resolved as $row): ?>
                            <tr id="resolved-<?= $row['id'] ?>">
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm btn-view-message" 
                                            data-message="<?= htmlspecialchars($row['message'], ENT_QUOTES) ?>">View Message</button>
                                </td>
                                <td class="status-resolved">Resolved</td>
                                <td><?= $row['created_at'] ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $row['id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted">No resolved complaints.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ========================== -->
<!-- VIEW MESSAGE MODAL -->
<!-- ========================== -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewMessageLabel">Full Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalMessageContent" style="white-space: pre-wrap;"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ==========================
// HANDLE BUTTON ACTIONS
// ==========================
function handleAction(action, id) {
    if (action === "delete" && !confirm("Delete this record?")) return;

    fetch("complain_support.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=${action}&id=${id}`
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) return alert("Action failed.");

        const row = document.getElementById("row-" + id);

        // MOVE TO RESOLVED TABLE
        if (action === "resolve") {
            const resolvedTable = document.getElementById("resolvedTable");
            let newRow = row.cloneNode(true);

            // Update status
            newRow.querySelector("td:nth-child(6)").textContent = "Resolved";
            newRow.querySelector("td:nth-child(6)").classList.remove("status-pending");
            newRow.querySelector("td:nth-child(6)").classList.add("status-resolved");

            // Remove resolve button
            let resolveBtn = newRow.querySelector(".btn-resolve");
            if (resolveBtn) resolveBtn.remove();

            newRow.id = "resolved-" + id;
            resolvedTable.prepend(newRow);
            row.remove();

            // Reattach event for view message button
            attachViewMessageEvent(newRow.querySelector(".btn-view-message"));
            return;
        }

        // DELETE ITEM
        if (action === "delete") {
            const pendingRow = document.getElementById("row-" + id);
            const resolvedRow = document.getElementById("resolved-" + id);
            if (pendingRow) pendingRow.remove();
            if (resolvedRow) resolvedRow.remove();
        }
    });
}

// Attach resolve/delete events
document.querySelectorAll(".btn-resolve").forEach(btn =>
    btn.addEventListener("click", () => handleAction("resolve", btn.dataset.id))
);

document.querySelectorAll(".btn-delete").forEach(btn =>
    btn.addEventListener("click", () => handleAction("delete", btn.dataset.id))
);

// ==========================
// VIEW MESSAGE MODAL
// ==========================
function attachViewMessageEvent(btn) {
    btn.addEventListener("click", () => {
        const message = btn.dataset.message;
        document.getElementById("modalMessageContent").textContent = message;

        const modal = new bootstrap.Modal(document.getElementById('viewMessageModal'));
        modal.show();
    });
}

// Initial attach
document.querySelectorAll(".btn-view-message").forEach(btn => attachViewMessageEvent(btn));
</script>

</body>
</html>

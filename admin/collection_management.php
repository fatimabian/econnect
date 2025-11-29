<?php
session_start();
include "db_connect.php";

// ---------------------------
// CHECK IF BARANGAY ADMIN IS LOGGED IN
// ---------------------------
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// ---------------------------
// GET LOGGED-IN ADMIN INFO
// ---------------------------
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_barangay);
$stmt->fetch();
$stmt->close();

// ---------------------------
// FETCH SCHEDULES ONLY FOR ADMIN'S BARANGAY
// ---------------------------
$schedules = [];
$sql = "
    SELECT cs.id, cs.barangay, cs.date, cs.time, 
           (SELECT username FROM collection_crew WHERE barangay = cs.barangay LIMIT 1) AS collector_username
    FROM collection_schedule cs
    WHERE cs.barangay = ?
    ORDER BY cs.id ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_barangay);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { 
    background-color: rgba(68,64,51,0.4) !important; 
    padding-top: 70px; 
    font-family: Arial, sans-serif; 
    padding-left: 70px;
}
.card { 
    border-radius: 14px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
}
.table th, .table td { 
    vertical-align: middle; 
}

/* BUTTON STYLING: NO COLOR */
.btn {
    font-weight: 600; 
    display: flex; 
    align-items: center; 
    gap: 5px; 
    background: none !important; 
    border: none !important; 
    color: #000 !important; 
    padding: 4px 8px;
}
.btn:hover {
    background: rgba(0,0,0,0.05) !important;
}

/* ADD SCHEDULE BUTTON OUTLINE */
.btn-outline-action {
    background: none;
    border: 2px solid black;
    color: black;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    transition: 0.3s;
}
.btn-outline-action:hover {
    background-color: rgba(77, 95, 68, 0.1);
}

/* MODAL HEADER GREEN */
.modal-header { 
    background-color: #4d5f44 !important; 
    color: #fff !important; 
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Collection Management - <?= htmlspecialchars($admin_barangay) ?></h3>
        <button class="btn-outline-action fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Add Schedule
        </button>
    </div>

    <div class="card p-3">
        <!-- SEARCH BAR -->
        <input type="text" id="searchBar" class="form-control mb-3" placeholder="Search in the table...">
        
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Barangay</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Collector</th>
                        <th style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="scheduleTable">
                    <?php $i=1; foreach($schedules as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['barangay']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['time']) ?></td>
                        <td>
                            <?= $row['collector_username'] ? htmlspecialchars($row['collector_username']) : '<span class="text-danger fw-bold">No Collector</span>' ?>
                        </td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $row['id'] ?>"
                                data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                                data-date="<?= $row['date'] ?>"
                                data-time="<?= $row['time'] ?>"
                            ><i class="bi bi-pencil-square"></i> Manage</button>

                            <button class="btn btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteModal"
                                data-id="<?= $row['id'] ?>"
                                data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                                data-date="<?= $row['date'] ?>"
                            ><i class="bi bi-trash-fill"></i> Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($schedules)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No schedules found for <?= htmlspecialchars($admin_barangay) ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form action="add_schedule_process.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" class="form-control mb-2" name="barangay" value="<?= htmlspecialchars($admin_barangay) ?>" readonly>
        <input type="date" class="form-control mb-2" name="date" required>
        <input type="time" class="form-control mb-2" name="time" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn fw-bold"><i class="bi bi-check-circle"></i> Add</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form action="update_schedule_process.php" method="POST" class="modal-content" id="editForm">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="schedule_id" id="edit_id">
        <input type="text" class="form-control mb-2" name="barangay" id="edit_barangay" readonly>
        <input type="date" class="form-control mb-2" name="date" id="edit_date" required>
        <input type="time" class="form-control mb-2" name="time" id="edit_time" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn fw-bold" id="confirmEditBtn">
            <i class="bi bi-save"></i> Update
        </button>
      </div>
    </form>
  </div>
</div>

<!-- CONFIRM EDIT MODAL -->
<div class="modal fade" id="confirmEditModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Update</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to update this schedule?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> No</button>
        <button type="button" class="btn fw-bold" id="submitEditForm"><i class="bi bi-check-circle"></i> Yes, Update</button>
      </div>
    </div>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="GET" class="modal-content" id="deleteForm">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-trash-fill"></i> Delete Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete the schedule for <strong id="deleteInfo"></strong>?
        <input type="hidden" name="id" id="delete_id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> No</button>
        <button type="submit" formaction="delete_schedule.php" class="btn fw-bold"><i class="bi bi-check-circle"></i> Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// SEARCH FILTER
document.getElementById("searchBar").addEventListener("keyup", function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll("#scheduleTable tr").forEach(row => {
        let match = false;
        row.querySelectorAll("td").forEach(td => {
            if (td.innerText.toLowerCase().includes(q)) match = true;
        });
        row.style.display = match ? "" : "none";
    });
});

// FILL EDIT MODAL
document.getElementById('editModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    this.querySelector('#edit_id').value = btn.dataset.id;
    this.querySelector('#edit_barangay').value = btn.dataset.barangay;
    this.querySelector('#edit_date').value = btn.dataset.date;
    this.querySelector('#edit_time').value = btn.dataset.time;
});

// CONFIRM EDIT
document.getElementById('confirmEditBtn').addEventListener('click', function() {
    const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
    editModal.hide();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmEditModal'));
    confirmModal.show();
});

// SUBMIT FORM AFTER CONFIRMATION
document.getElementById('submitEditForm').addEventListener('click', function() {
    document.getElementById('editForm').submit();
});

// FILL DELETE MODAL
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    this.querySelector('#delete_id').value = btn.dataset.id;
    this.querySelector('#deleteInfo').textContent = btn.dataset.barangay + " on " + btn.dataset.date;
});
</script>
</body>
</html>

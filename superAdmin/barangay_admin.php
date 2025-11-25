<?php
session_start();
include "db_connect.php";

/* =======================================================
   ADD BARANGAY ADMIN
======================================================= */
if (isset($_POST['add_admin'])) {
    $full_name = trim($_POST['full_name']);
    $barangay = trim($_POST['barangay']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = "Inactive";

    $check = $conn->prepare("SELECT id FROM barangay_admins WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Username already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO barangay_admins (full_name, barangay, username, password, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full_name, $barangay, $username, $password, $status);
        $stmt->execute();
        $_SESSION['success'] = "Admin added successfully.";
    }

    header("Location: barangay_admin.php");
    exit();
}

/* =======================================================
   EDIT BARANGAY ADMIN
======================================================= */
if (isset($_POST['edit_admin'])) {
    $id = intval($_POST['edit_id']);
    $full_name = trim($_POST['edit_full_name']);
    $barangay = trim($_POST['edit_barangay']);
    $username = trim($_POST['edit_username']);
    $status = trim($_POST['edit_status']);

    if (!empty($_POST['edit_password'])) {
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE barangay_admins SET full_name=?, barangay=?, username=?, status=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $full_name, $barangay, $username, $status, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE barangay_admins SET full_name=?, barangay=?, username=?, status=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $barangay, $username, $status, $id);
    }

    $stmt->execute();
    $_SESSION['success'] = "Admin updated successfully.";
    header("Location: barangay_admin.php");
    exit();
}

/* =======================================================
   DELETE BARANGAY ADMIN
======================================================= */
if (isset($_POST['delete_admin'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM barangay_admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['success'] = "Admin removed successfully.";
    header("Location: barangay_admin.php");
    exit();
}

/* =======================================================
   TOGGLE STATUS
======================================================= */
if (isset($_GET['toggle_id'])) {
    $id = intval($_GET['toggle_id']);
    $row = $conn->query("SELECT status FROM barangay_admins WHERE id=$id")->fetch_assoc();
    $new_status = ($row['status'] === "Active") ? "Inactive" : "Active";

    $stmt = $conn->prepare("UPDATE barangay_admins SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $_SESSION['success'] = "Status updated.";
    header("Location: barangay_admin.php");
    exit();
}

/* =======================================================
   FETCH BARANGAY ADMINS
======================================================= */
$result = $conn->query("SELECT * FROM barangay_admins ORDER BY id ASC");
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Admin Accounts</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: rgba(68,64,51,0.4); padding-top: 100px; }
.card { border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.btn-primary, .btn-danger, .btn-success, .btn-secondary, .btn-warning { font-weight: 600; }
.table th, .table td { vertical-align: middle; }
</style>
</head>
<body>

<?php include "header.php"; ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Barangay Admin Accounts</h3>
        <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#addAdminModal">+ Add Admin</button>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card p-3">
        <input type="text" id="searchBar" class="form-control mb-3" placeholder="Search admin...">
        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-warning">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Barangay</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th style="width: 260px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="adminTable">
                    <?php $i=1; foreach($admins as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['barangay']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <?= $row['status'] === "Active" ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                        </td>
                        <td class="d-flex gap-1">
                            <!-- EDIT -->
                            <button class="btn btn-sm btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editAdminModal"
                                data-id="<?= $row['id'] ?>"
                                data-full="<?= htmlspecialchars($row['full_name']) ?>"
                                data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                                data-username="<?= htmlspecialchars($row['username']) ?>"
                                data-status="<?= $row['status'] ?>"
                            >Edit</button>

                            <!-- REMOVE -->
                            <button class="btn btn-sm btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#deleteAdminModal"
                                data-id="<?= $row['id'] ?>"
                                data-full="<?= htmlspecialchars($row['full_name']) ?>"
                            >Remove</button>

                            <!-- ACTIVATE / DEACTIVATE -->
                            <a href="?toggle_id=<?= $row['id'] ?>" class="btn btn-sm <?= $row['status'] === 'Active' ? 'btn-secondary' : 'btn-success' ?>">
                                <?= $row['status'] === "Active" ? "Deactivate" : "Activate" ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD ADMIN MODAL -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
        <div class="modal-header bg-warning">
            <h5 class="modal-title">Add Barangay Admin</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input name="full_name" class="form-control mb-2" placeholder="Full Name" required>
            <input name="barangay" class="form-control mb-2" placeholder="Barangay" required>
            <input name="username" class="form-control mb-2" placeholder="Username" required>
            <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
        </div>
        <div class="modal-footer">
            <button type="submit" name="add_admin" class="btn btn-warning fw-bold">Add Admin</button>
        </div>
    </form>
  </div>
</div>

<!-- EDIT ADMIN MODAL -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content" id="editAdminForm">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Edit Barangay Admin</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_id" name="edit_id">
            <input id="edit_full_name" name="edit_full_name" class="form-control mb-2" required>
            <input id="edit_barangay" name="edit_barangay" class="form-control mb-2" required>
            <input id="edit_username" name="edit_username" class="form-control mb-2" required>
            <select id="edit_status" name="edit_status" class="form-select mb-2">
                <option>Active</option>
                <option>Inactive</option>
            </select>
            <input type="password" id="edit_password" name="edit_password" class="form-control mb-2" placeholder="New Password (optional)">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary fw-bold" id="confirmEditBtn">Update</button>
        </div>
    </form>
  </div>
</div>

<!-- EDIT CONFIRM MODAL -->
<div class="modal fade" id="confirmEditModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Confirm Update</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to update this admin's information?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <button type="button" class="btn btn-primary fw-bold" id="submitEditForm">Yes, Update</button>
        </div>
    </div>
  </div>
</div>

<!-- DELETE ADMIN MODAL -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
        <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Remove Admin</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to remove <strong id="deleteAdminName"></strong>?</p>
            <input type="hidden" id="delete_id" name="delete_id">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <button type="submit" name="delete_admin" class="btn btn-danger fw-bold">Yes, Remove</button>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fill Edit Modal
document.getElementById('editAdminModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    const modal = this;
    modal.querySelector('#edit_id').value = btn.dataset.id;
    modal.querySelector('#edit_full_name').value = btn.dataset.full;
    modal.querySelector('#edit_barangay').value = btn.dataset.barangay;
    modal.querySelector('#edit_username').value = btn.dataset.username;
    modal.querySelector('#edit_status').value = btn.dataset.status;
});

// Close edit modal then show confirmation
document.getElementById('confirmEditBtn').addEventListener('click', function() {
    const editModalEl = document.getElementById('editAdminModal');
    const editModal = bootstrap.Modal.getInstance(editModalEl);
    editModal.hide();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmEditModal'));
    confirmModal.show();
});

// Submit edit form after confirmation
document.getElementById('submitEditForm').addEventListener('click', function() {
    document.getElementById('editAdminForm').submit();
});

// Fill Delete Modal
document.getElementById('deleteAdminModal').addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    const modal = this;
    modal.querySelector('#delete_id').value = btn.dataset.id;
    modal.querySelector('#deleteAdminName').textContent = btn.dataset.full;
});

// Search Filter
document.getElementById("searchBar").addEventListener("keyup", function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll("#adminTable tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? "" : "none";
    });
});
</script>
</body>
</html>

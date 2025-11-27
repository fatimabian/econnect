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
$stmt = $conn->prepare("SELECT barangay FROM barangay_admins WHERE id=?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_barangay = $stmt->get_result()->fetch_assoc()['barangay'] ?? '';
$stmt->close();

// ===========================
// HANDLE ENABLE/DISABLE USER
// ===========================
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['user_id']);
    $newStatus = $_POST['current_status'] === "Active" ? "Disabled" : "Active";

    $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=? AND barangay=?");
    $stmt->bind_param("sis", $newStatus, $id, $admin_barangay);
    $stmt->execute();
    $stmt->close();

    header("Location: user_management.php?updated=1");
    exit();
}

// ===========================
// HANDLE RESET PASSWORD
// ===========================
if (isset($_POST['reset_password'])) {
    $id = intval($_POST['user_id']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword === $confirmPassword && !empty($newPassword)) {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=? AND barangay=?");
        $stmt->bind_param("sis", $hashed, $id, $admin_barangay);
        $stmt->execute();
        $stmt->close();

        header("Location: user_management.php?password_reset=1");
        exit();
    } else {
        $errorMsg = "Passwords do not match or are empty!";
    }
}

// ===========================
// HANDLE DELETE USER
// ===========================
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND barangay=?");
    $stmt->bind_param("is", $id, $admin_barangay);
    $stmt->execute();
    $stmt->close();

    header("Location: user_management.php?deleted=1");
    exit();
}

// ===========================
// FETCH USERS BY BARANGAY
// ===========================
$users = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE barangay=? ORDER BY id DESC");
$stmt->bind_param("s", $admin_barangay);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body { background-color: rgba(68,64,51,0.4) !important; font-family: Arial, sans-serif; padding-top:100px; padding-left:70px; }
.card { border-radius:14px; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.table th, .table td { vertical-align: middle; }

/* ICON BUTTONS */
.icon-btn { background:none; border:1px solid #000; color:black; font-size:0.85rem; padding:4px 8px; border-radius:6px; cursor:pointer; display:flex; align-items:center; gap:4px; }
.icon-btn i { font-size:1rem; }
.icon-btn:hover { background: rgba(0,0,0,0.05); }
.modal-header { background-color:#4d5f44; color:#fff; font-size:0.95rem; }
.modal-dialog-centered { display:flex; align-items:flex-start; min-height: calc(100% - 120px); margin:60px auto 0 auto !important; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>User Management - <?= htmlspecialchars($admin_barangay) ?></h3>
        <input type="text" id="searchBar" class="form-control w-25" placeholder="Search users...">
    </div>

    <?php if(isset($errorMsg)): ?><div class="alert alert-warning"><?= $errorMsg ?></div><?php endif; ?>
    <?php if(isset($_GET['updated'])): ?><div class="alert alert-success">User status updated successfully!</div><?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?><div class="alert alert-danger">User deleted successfully!</div><?php endif; ?>
    <?php if(isset($_GET['password_reset'])): ?><div class="alert alert-info">Password reset successfully!</div><?php endif; ?>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="userTable">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; foreach($users as $user): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['contact']) ?></td>
                        <td><span class="badge <?= $user['status']=='Active'?'bg-success':'bg-secondary' ?>"><?= $user['status'] ?></span></td>
                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                        <td class="d-flex gap-1">
                            <button class="icon-btn" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?= $user['id'] ?>" data-status="<?= $user['status'] ?>" title="Manage User">
                                <i class="bi bi-gear-fill"></i> Manage
                            </button>
                            <button class="icon-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $user['id'] ?>" data-name="<?= htmlspecialchars($user['full_name']) ?>" title="Delete User">
                                <i class="bi bi-trash-fill text-danger"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($users)): ?>
                    <tr><td colspan="8" class="text-center text-muted">No users found in <?= htmlspecialchars($admin_barangay) ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Manage User</h5>
        <button type="button" class="icon-btn" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="user_id" id="edit_id">
        <input type="hidden" name="current_status" id="edit_status">
        <button type="submit" name="toggle_status" id="toggleStatusBtn" class="icon-btn w-100 mb-3">
            <i class="bi bi-person-check"></i> Toggle Status
        </button>
        <hr>
        <h6>Reset Password</h6>
        <input type="password" name="new_password" placeholder="New Password" class="form-control mb-2">
        <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-control mb-2">
        <button type="submit" name="reset_password" class="icon-btn w-100"><i class="bi bi-arrow-counterclockwise"></i> Update Password</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="GET" class="modal-content p-3" id="deleteForm">
      <div class="modal-header">
        <h5 class="modal-title">Delete User</h5>
        <button type="button" class="icon-btn" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete <strong id="deleteName"></strong>?
        <input type="hidden" name="delete" id="delete_id">
      </div>
      <div class="modal-footer">
        <button type="button" class="icon-btn" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Close</button>
        <button type="submit" class="icon-btn text-danger"><i class="bi bi-trash-fill"></i> Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// SEARCH FILTER
document.getElementById("searchBar").addEventListener("keyup", function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll("#userTable tbody tr").forEach(row=>{
        row.style.display = Array.from(row.cells).some(td => td.innerText.toLowerCase().includes(q)) ? "" : "none";
    });
});

// FILL EDIT MODAL
const editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_status').value = btn.dataset.status;
});

// FILL DELETE MODAL
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function(event){
    const btn = event.relatedTarget;
    document.getElementById('delete_id').value = btn.dataset.id;
    document.getElementById('deleteName').textContent = btn.dataset.name;
});
</script>
</body>
</html>

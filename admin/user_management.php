<?php
session_start();
include "db_connect.php";

// ===========================
// HANDLE ENABLE/DISABLE USER
// ===========================
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['user_id']);
    $currentStatus = $_POST['status'] === "Active" ? "Disabled" : "Active";

    $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
    $stmt->bind_param("si", $currentStatus, $id);
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
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $id);
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
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: user_management.php?deleted=1");
    exit();
}

// ===========================
// FETCH USERS
// ===========================
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
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
body { background: #eef1ee; }
.content { margin-left: 260px; padding: 20px; margin-top: 20px; }
.card { border-radius: 14px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }

/* MODAL STYLING */
.modal-backdrop.show { opacity: .5 !important; }
.modal { z-index: 99999 !important; }
.modal-content { border-radius: 18px; padding: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.25); border: none; }
.modal-header { border-bottom: none; background: #3f4a36; color: white; border-radius: 14px 14px 0 0; padding: 15px 20px; }
.modal-title { font-size: 1.25rem; font-weight: 600; }
.modal-body { padding: 20px; }
.modal-footer { border-top: none; padding: 15px 20px; }

/* ACTION BUTTON STYLING */
.action-btn {
    border-radius: 8px;
    padding: 5px 10px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s ease-in-out;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    text-decoration: none;
}
.action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); color: #fff !important; }
.btn-edit { background-color: #6c757d; color: #fff; }
.btn-edit:hover { background-color: #5a6268; color: #fff; }
.btn-delete { background-color: #dc3545; color: #fff; }
.btn-delete:hover { background-color: #c82333; color: #fff; }
</style>
</head>
<body>

<?php include "header.php"; ?>
<?php include "nav.php"; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">User Management</h2>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">User status updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger">User deleted successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['password_reset'])): ?>
        <div class="alert alert-info">Password reset successfully!</div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-warning"><?= $errorMsg; ?></div>
    <?php endif; ?>

    <div class="card p-3">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Contact</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Date Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php $i = 1; foreach ($users as $row): ?>
                        <tr>
                            <td><?= $i++; ?></td>
                            <td><?= htmlspecialchars($row['full_name']); ?></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td><?= htmlspecialchars($row['contact']); ?></td>
                            <td><?= htmlspecialchars($row['barangay']); ?></td>
                            <td>
                                <span class="badge <?= $row['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?= htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                            <td>
                                <!-- EDIT MODAL TRIGGER -->
                                <button class="action-btn btn-edit" data-bs-toggle="modal"
                                        data-bs-target="#editModal<?= $row['id']; ?>">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <!-- DELETE -->
                                <a href="user_management.php?delete=<?= $row['id']; ?>"
                                   class="action-btn btn-delete"
                                   onclick="return confirm('Delete this user?');">
                                   <i class="bi bi-trash-fill"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==============================
     MODALS (outside table)
================================= -->
<?php foreach ($users as $row): ?>
<div class="modal fade" id="editModal<?= $row['id']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manage User: <?= htmlspecialchars($row['username']); ?></h5>
        <button type="button" class="btn-close bg-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- ENABLE/DISABLE CONFIRMATION -->
        <div class="mb-4 p-3 border rounded" style="background:#f8f9fa;">
            <p class="mb-2 fw-semibold">
                <?= $row['status'] === "Active" ? "Do you want to disable this user?" : "Do you want to enable this user?"; ?>
            </p>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $row['id']; ?>">
                <input type="hidden" name="status" value="<?= $row['status']; ?>">
                <button type="submit" name="toggle_status" class="btn btn-warning w-100">
                    <?= $row['status'] === "Active" ? "Disable User" : "Enable User"; ?>
                </button>
            </form>
        </div>

        <!-- RESET PASSWORD FORM -->
        <div class="mb-3 p-3 border rounded" style="background:#f1f3f5;">
            <h6 class="fw-bold mb-3">Reset Password</h6>
            <form method="POST">
                <input type="hidden" name="user_id" value="<?= $row['id']; ?>">
                <div class="mb-3">
                    <label class="form-label">New Password:</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password:</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="reset_password" class="btn btn-info w-100 text-white">
                    Reset Password
                </button>
            </form>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

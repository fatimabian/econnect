<?php
session_start();
include "db_connect.php";

// ==========================
// HANDLE ADD BARANGAY ADMIN
// ==========================
if(isset($_POST['add_admin'])){
    $full_name = trim($_POST['full_name']);
    $barangay = trim($_POST['barangay']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO barangay_admins (full_name, barangay, username, password, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $barangay, $username, $password, $email);
    $stmt->execute();
    $stmt->close();
    header("Location: barangay_admin_accounts.php");
    exit();
}

// ==========================
// HANDLE EDIT BARANGAY ADMIN
// ==========================
if(isset($_POST['edit_admin'])){
    $id = intval($_POST['edit_id']);
    $full_name = trim($_POST['edit_full_name']);
    $barangay = trim($_POST['edit_barangay']);
    $username = trim($_POST['edit_username']);
    $email = trim($_POST['edit_email']);

    if(!empty($_POST['edit_password'])){
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE barangay_admins SET full_name=?, barangay=?, username=?, email=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $full_name, $barangay, $username, $email, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE barangay_admins SET full_name=?, barangay=?, username=?, email=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $barangay, $username, $email, $id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: barangay_admin_accounts.php");
    exit();
}

// ==========================
// HANDLE DELETE BARANGAY ADMIN
// ==========================
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM barangay_admins WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: barangay_admin_accounts.php");
    exit();
}

// ==========================
// FETCH ALL BARANGAY ADMINS
// ==========================
$result = $conn->query("SELECT * FROM barangay_admins ORDER BY id ASC");
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Admin Accounts</title>
<link rel="stylesheet" href="superAdmin.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include "header.php"; ?>
<?php include "superNav.php"; ?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Barangay Admin Accounts</h3>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addAdminModal">+ Add Admin</button>
    </div>

    <div class="card p-3 shadow-sm">
        <table class="table table-bordered">
            <thead class="table-warning">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Barangay</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $counter = 1;
            if(count($admins) > 0):
                foreach($admins as $row): 
            ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['barangay']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editAdminModal"
                            data-id="<?= $row['id'] ?>"
                            data-full="<?= htmlspecialchars($row['full_name']) ?>"
                            data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                            data-username="<?= htmlspecialchars($row['username']) ?>"
                            data-email="<?= htmlspecialchars($row['email']) ?>"
                        >Edit</button>
                        <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this admin?')">Remove</a>
                    </td>
                </tr>
            <?php 
            $counter++;
                endforeach; 
            else: ?>
                <tr>
                    <td colspan="6" class="text-center">No admin accounts found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Barangay Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" name="full_name" placeholder="Full Name" class="form-control mb-3" required>
                <input type="text" name="barangay" placeholder="Barangay" class="form-control mb-3" required>
                <input type="text" name="username" placeholder="Username" class="form-control mb-3" required>
                <input type="email" name="email" placeholder="Email" class="form-control mb-3" required>
                <input type="password" name="password" placeholder="Password" class="form-control mb-3" required>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_admin" class="btn btn-warning">Add Admin</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Barangay Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="text" name="edit_full_name" id="edit_full_name" class="form-control mb-3" required>
                <input type="text" name="edit_barangay" id="edit_barangay" class="form-control mb-3" required>
                <input type="text" name="edit_username" id="edit_username" class="form-control mb-3" required>
                <input type="email" name="edit_email" id="edit_email" class="form-control mb-3" required>
                <input type="password" name="edit_password" id="edit_password" placeholder="New Password (leave blank to keep old)" class="form-control mb-3">
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_admin" class="btn btn-primary">Update Admin</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate Edit Modal
var editModal = document.getElementById('editAdminModal');
editModal.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  document.getElementById('edit_id').value = button.getAttribute('data-id');
  document.getElementById('edit_full_name').value = button.getAttribute('data-full');
  document.getElementById('edit_barangay').value = button.getAttribute('data-barangay');
  document.getElementById('edit_username').value = button.getAttribute('data-username');
  document.getElementById('edit_email').value = button.getAttribute('data-email');
});
</script>

</body>
</html>

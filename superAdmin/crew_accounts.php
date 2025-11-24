<?php
session_start();
include "db_connect.php";

// ==========================
// HANDLE ADD CREW
// ==========================
if(isset($_POST['add_crew'])){
    $full_name = trim($_POST['full_name']);
    $barangay = trim($_POST['barangay']);
    $username = trim($_POST['username']);
    $status = trim($_POST['status']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO collection_crew (full_name, barangay, username, status, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $full_name, $barangay, $username, $status, $password);
    $stmt->execute();
    $stmt->close();
    header("Location: crew_accounts.php");
    exit();
}

// ==========================
// HANDLE EDIT CREW
// ==========================
if(isset($_POST['edit_crew'])){
    $id = intval($_POST['edit_id']);
    $full_name = trim($_POST['edit_full_name']);
    $barangay = trim($_POST['edit_barangay']);
    $username = trim($_POST['edit_username']);
    $status = trim($_POST['edit_status']);

    if(!empty($_POST['edit_password'])){
        $password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE collection_crew SET full_name=?, barangay=?, username=?, status=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $full_name, $barangay, $username, $status, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE collection_crew SET full_name=?, barangay=?, username=?, status=? WHERE id=?");
        $stmt->bind_param("ssssi", $full_name, $barangay, $username, $status, $id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: crew_accounts.php");
    exit();
}

// ==========================
// HANDLE DELETE CREW
// ==========================
if(isset($_GET['delete_id'])){
    $id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM collection_crew WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: crew_accounts.php");
    exit();
}

// ==========================
// FETCH ALL CREWS
// ==========================
$result = $conn->query("SELECT * FROM collection_crew ORDER BY id ASC");
$crews = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collection Crew Accounts</title>
<link rel="stylesheet" href="superAdmin.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include "header.php"; ?>
<?php include "superNav.php"; ?>

<div class="content-area">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Collection Crew Accounts</h3>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addCrewModal">+ Add Crew Member</button>
    </div>

    <div class="card p-3 shadow-sm">
        <table class="table table-bordered">
            <thead class="table-warning">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Barangay</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $counter = 1;
            if(count($crews) > 0):
                foreach($crews as $row): 
            ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['barangay']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <?php if($row['status'] == 'Active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCrewModal"
                            data-id="<?= $row['id'] ?>"
                            data-full="<?= htmlspecialchars($row['full_name']) ?>"
                            data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                            data-username="<?= htmlspecialchars($row['username']) ?>"
                            data-status="<?= htmlspecialchars($row['status']) ?>"
                        >Edit</button>
                        <a href="?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this crew member?')">Remove</a>
                    </td>
                </tr>
            <?php 
            $counter++;
                endforeach; 
            else: ?>
                <tr>
                    <td colspan="6" class="text-center">No crew accounts found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Crew Modal -->
<div class="modal fade" id="addCrewModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Crew Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" name="full_name" placeholder="Name" class="form-control mb-3" required>
                <input type="text" name="barangay" placeholder="Barangay" class="form-control mb-3" required>
                <input type="text" name="username" placeholder="Username" class="form-control mb-3" required>
                <select name="status" class="form-select mb-3">
                    <option value="Active" selected>Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                <input type="password" name="password" placeholder="Password" class="form-control mb-3" required>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_crew" class="btn btn-warning">Add Crew</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Crew Modal -->
<div class="modal fade" id="editCrewModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Crew Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="text" name="edit_full_name" id="edit_full_name" class="form-control mb-3" required>
                <input type="text" name="edit_barangay" id="edit_barangay" class="form-control mb-3" required>
                <input type="text" name="edit_username" id="edit_username" class="form-control mb-3" required>
                <select name="edit_status" id="edit_status" class="form-select mb-3">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                <input type="password" name="edit_password" id="edit_password" placeholder="New Password (leave blank to keep old)" class="form-control mb-3">
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_crew" class="btn btn-primary">Update Crew</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate Edit Modal
var editModal = document.getElementById('editCrewModal');
editModal.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  document.getElementById('edit_id').value = button.getAttribute('data-id');
  document.getElementById('edit_full_name').value = button.getAttribute('data-full');
  document.getElementById('edit_barangay').value = button.getAttribute('data-barangay');
  document.getElementById('edit_username').value = button.getAttribute('data-username');
  document.getElementById('edit_status').value = button.getAttribute('data-status');
});
</script>

</body>
</html>

<?php
session_start();
include "../db_connect.php"; 

if (!isset($_SESSION['super_admin_id'])) {
    die("Unauthorized access.");
}

$admin_id = $_SESSION['super_admin_id'];

$result = $conn->prepare("SELECT * FROM super_admin WHERE id = ?");
$result->bind_param("i", $admin_id);
$result->execute();
$user = $result->get_result()->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Profile Settings</title>
    <link rel="stylesheet" href="superAdmin.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include "header.php"; ?>
<?php include "superNav.php"; ?>

<div class="content-area">

<h3 class="mb-4">Profile Settings</h3>

<div class="card p-4 shadow-sm" style="max-width: 600px;">
<form action="update_profile.php" method="POST">

    <div class="mb-3">
    <label class="form-label">Full Name</label>
    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Username</label>
    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
</div>

<div class="mb-3">
    <label class="form-label">Phone Number</label>
    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
</div>


    <hr>

    <div class="mb-3">
        <label class="form-label">Change Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter new password">
    </div>

    <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password">
    </div>

    <button class="btn btn-success w-100">Save Changes</button>

</form>
</div>

</div>

<?php include "../footer.php"; ?>

</body>
</html>

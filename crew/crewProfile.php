<?php
session_start();
include "../db_connect.php";

// ==========================
// CHECK IF CREW IS LOGGED IN
// ==========================
if (!isset($_SESSION['crew_id'])) {
    header("Location: crewLogin.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];

// ==========================
// FETCH CREW DATA
// ==========================
$stmt = $conn->prepare("SELECT full_name, barangay, username, email, phone FROM collection_crew WHERE id=?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$result = $stmt->get_result();
$crew = $result->fetch_assoc();
$stmt->close();

if (!$crew) {
    die("Crew member not found.");
}

// ==========================
// HANDLE PROFILE UPDATE
// ==========================
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['updateProfile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validate full name
    if (empty($full_name)) {
        $errors['full_name'] = "Full name cannot be empty.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Validate phone
    if (empty($phone)) {
        $errors['phone'] = "Phone number cannot be empty.";
    }

    // Update database if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE collection_crew SET full_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $crew_id);

        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $crew['full_name'] = $full_name;
            $crew['email'] = $email;
            $crew['phone'] = $phone;
        } else {
            $errors['general'] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crew Profile - ECOnnect</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
body { background-color: #f7f7f7; font-family: Arial, sans-serif; }
.main-content { margin-left: 260px; padding: 20px; }
.profile-container { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 700px; margin-top: 30px; }
h3 { color: #3f4a36; font-weight: 600; margin-bottom: 20px; }
.form-label { font-weight: 600; }
.error-msg { color: red; font-size: 0.9rem; }
.success-msg { color: green; font-size: 1rem; font-weight: 600; margin-bottom: 10px; }
button.btn-update { background-color: #3f4a36 !important; color: white !important; width: 100%; font-weight: 600; border: none; border-radius: 8px; transition: 0.3s; }
button.btn-update:hover { background-color: #2f3927 !important; color: #fff !important; }
</style>
</head>
<body>

<?php include "header.php"; ?>
<?php include "nav.php"; ?>

<div class="main-content">
    <div class="profile-container">
        <h3>Crew Profile</h3>

        <?php if (!empty($success)): ?>
            <p class="success-msg"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <?php if (!empty($errors['general'])): ?>
            <p class="error-msg"><?= htmlspecialchars($errors['general']) ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($crew['full_name'] ?? '') ?>" required>
                <?php if (!empty($errors['full_name'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['full_name']) ?></p>
                <?php endif; ?>
            </div>

            <!-- <div class="mb-3">
                <label class="form-label">Assigned Barangay</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($crew['barangay'] ?? '') ?>" readonly>
            </div> -->

            <!-- <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($crew['username'] ?? '') ?>" readonly>
            </div> -->

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($crew['email'] ?? '') ?>" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($crew['phone'] ?? '') ?>" required>
                <?php if (!empty($errors['phone'])): ?>
                    <p class="error-msg"><?= htmlspecialchars($errors['phone']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" name="updateProfile" class="btn btn-update">Update Profile</button>
        </form>
    </div>
</div>

</body>
</html>

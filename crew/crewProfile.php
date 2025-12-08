<?php
session_start();
include "../db_connect.php";

// ---------------------------
// CHECK IF CREW IS LOGGED IN
// ---------------------------
if (!isset($_SESSION['crew_id'])) {
    header("Location: crewLogin.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];

// ---------------------------
// FETCH CREW DATA
// ---------------------------
$stmt = $conn->prepare("SELECT full_name, barangay, username, email, phone FROM collection_crew WHERE id=?");
$stmt->bind_param("i", $crew_id);
$stmt->execute();
$result = $stmt->get_result();
$crew = $result->fetch_assoc();
$stmt->close();

if (!$crew) {
    die("Crew member not found.");
}

// ---------------------------
// HANDLE PROFILE UPDATE
// ---------------------------
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary-green: #3f4a36;
    --dark-green: #2c3529;
    --accent-green: #a8e6a3;
    --light-green: #e8f5e9;
    --white: #ffffff;
    --border-color: #e0e0e0;
    --text-dark: #333333;
}

/* Body */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #f8f9fa 0%, var(--light-green) 100%);
    min-height: 100vh;
    padding-top: 70px;
    color: var(--text-dark);
}

/* Main Container */
.main-content {
    margin-left: 70px;
    padding: 25px;
    transition: margin-left 0.3s ease;
    min-height: calc(100vh - 70px);
}

/* Profile Card */
.profile-container {
    max-width: 800px;
    margin: 0 auto;
    border-radius: 20px;
    background: var(--white);
    box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    overflow: hidden;
    animation: fadeIn 0.5s ease-out;
}

/* Profile Header */
.profile-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
    color: var(--white);
    text-align: center;
    padding: 30px;
    position: relative;
}
.profile-icon {
    font-size: 4rem;
    color: var(--accent-green);
    margin-bottom: 15px;
}
.profile-header h3 {
    font-size: 2.2rem;
    font-weight: 700;
}
.profile-subtitle {
    font-size: 1rem;
    color: var(--accent-green);
    font-weight: 500;
}

/* Profile Body */
.profile-body {
    padding: 40px;
}

/* Success & Error Messages */
.success-msg {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.error-msg {
    color: #dc3545;
    font-size: 0.85rem;
    margin-top: 5px;
}

/* Input Fields */
.form-control {
    border-radius: 10px;
    padding: 12px;
    border: 2px solid var(--border-color);
}
.form-control:focus {
    border-color: var(--accent-green);
    box-shadow: 0 0 0 0.2rem rgba(168,230,163,0.25);
}

/* Update Button */
.btn-update {
    background: var(--primary-green) !important;
    color: var(--white) !important;
    border: none;
    padding: 14px 30px;
    font-weight: 600;
    border-radius: 12px;
    width: 100%;
    transition: all 0.3s ease;
}
.btn-update:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    color: var(--white);
}

/* Profile Info Cards */
.profile-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}
.info-card {
    background: var(--light-green);
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid var(--accent-green);
}
.info-card label {
    font-size: 0.9rem;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: block;
}
.info-card .info-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--primary-green);
}

/* Divider */
.section-divider {
    height: 1px;
    background: var(--border-color);
    border: none;
    margin: 30px 0;
}

/* Responsive */
@media (max-width: 992px) {
    .profile-info-grid {
        grid-template-columns: 1fr;
    }
    .main-content {
        margin-left: 20px;
    }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<?php include "header.php"; ?>

<div class="main-content">
    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="profile-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Crew Profile</h3>
            <p class="profile-subtitle">Manage your personal information and settings</p>
        </div>

        <!-- Body -->
        <div class="profile-body">
            <?php if ($success): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <!-- Read-only Info -->
            <div class="profile-info-grid">
                <div class="info-card">
                    <label><i class="fas fa-map-marker-alt"></i> Assigned Barangay</label>
                    <div class="info-value"><?= htmlspecialchars($crew['barangay'] ?? 'Not assigned') ?></div>
                </div>
                <div class="info-card">
                    <label><i class="fas fa-user-tag"></i> Username</label>
                    <div class="info-value"><?= htmlspecialchars($crew['username']) ?></div>
                </div>
            </div>

            <hr class="section-divider">

            <!-- Editable Form -->
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($crew['full_name']) ?>" required>
                        <?php if (!empty($errors['full_name'])): ?>
                            <p class="error-msg"><?= htmlspecialchars($errors['full_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($crew['email']) ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <p class="error-msg"><?= htmlspecialchars($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($crew['phone']) ?>" required>
                        <?php if (!empty($errors['phone'])): ?>
                            <p class="error-msg"><?= htmlspecialchars($errors['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" name="updateProfile" class="btn btn-update mt-4">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>

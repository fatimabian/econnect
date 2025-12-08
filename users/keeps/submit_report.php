<?php
session_start();
include 'db_connect.php';
include 'header.php';

// Assume logged-in user
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Initialize variables
$errors = [];
$success = "";

if (isset($_POST['submit_report'])) {
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validation
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($message)) $errors[] = "Message is required.";

    if (empty($errors)) {
        // Insert into complaints table
        $insertStmt = $conn->prepare("INSERT INTO complaints (full_name, email, phone, message, status) VALUES (?, ?, ?, ?, 'Pending')");
        $combinedMessage = "Subject: $subject\nAddress: $address\nMessage: $message";
        $insertStmt->bind_param("ssss", $full_name, $email, $phone, $combinedMessage);

        if ($insertStmt->execute()) {
            $success = "Your report has been submitted successfully.";
        } else {
            $errors[] = "Failed to submit report. Please try again.";
        }
    }
}
?>

<div class="report-wrapper">
    <h2>Submit a Report / Complaint</h2>

    <?php if($errors): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $error) echo "<p>$error</p>"; ?>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success">
            <p><?= $success ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>">
        </div>

        <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['street'] . ', ' . $user['barangay'] . ', ' . $user['city']) ?>">
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['contact']) ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
        </div>

        <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject" class="form-control">
        </div>

        <div class="form-group">
            <label>Message</label>
            <textarea name="message" class="form-control" rows="5"></textarea>
        </div>

        <div class="form-button">
            <button type="submit" name="submit_report" class="btn btn-primary">Submit Report</button>
        </div>
    </form>
</div>

<style>
    body{
        padding-top: 150px;
        background-color:  rgba(68,64,51,0.4) !important;
    }
.report-wrapper {
    margin-left: 150px;
    padding-left: 20px;
    background-color: #f5f5f5;
    min-height: 100vh;
}

.form-group {
    margin-bottom: 15px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

textarea.form-control {
    resize: vertical;
}

.alert {
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-danger { background-color: #f8d7da; color: #721c24; }
.alert-success { background-color: #d4edda; color: #155724; }

.form-button {
    text-align: right;
    margin-top: 20px;
}
</style>

<?php include 'footer.php'; ?>

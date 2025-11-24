<?php
session_start();
include "../db_connect.php";
include "header.php";

if (!isset($_SESSION['crew_id'])) {
    header("Location: crewLogin.php");
    exit();
}

$crew_id = $_SESSION['crew_id'];
$success = $error = "";

if (isset($_POST['submit_report'])) {
    $title = trim($_POST['report_title']);
    $message = trim($_POST['report_message']);

    if ($title == "" || $message == "") {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO crew_reports (crew_id, report_title, report_message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $crew_id, $title, $message);
        if ($stmt->execute()) {
            $success = "Report submitted successfully.";
        } else {
            $error = "Error submitting report. Try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crew Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Georgia, serif; background: #f8f8f8; padding-top: 70px; }
.container { max-width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
.alert { margin-top: 15px; }
</style>
</head>
<body>
    <<?php include 'nav.php'; ?>    
<div class="container">
    <h2>Submit a Report</h2>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php elseif($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="mb-3">
            <label for="report_title" class="form-label">Title</label>
            <input type="text" class="form-control" name="report_title" id="report_title" required>
        </div>
        <div class="mb-3">
            <label for="report_message" class="form-label">Message</label>
            <textarea class="form-control" name="report_message" id="report_message" rows="5" required></textarea>
        </div>
        <button type="submit" name="submit_report" class="btn btn-primary">Send Report</button>
    </form>
</div>
</body>
</html>

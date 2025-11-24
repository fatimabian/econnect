<?php
session_start();
include "db_connect.php";

$id = $_POST['id'] ?? null;
$type = $_POST['type'] ?? null;
$message = $_POST['message'] ?? null;

if(!$id || !$type || !$message || !isset($_SESSION['barangay_admin_id'])){
    http_response_code(400);
    exit('Invalid request');
}

$admin_id = $_SESSION['barangay_admin_id'];

if($type === 'Crew'){
    $stmt = $conn->prepare("UPDATE crew_inbox SET message=? WHERE id=? AND admin_id=?");
} else { // User
    $stmt = $conn->prepare("UPDATE user_inbox SET message=? WHERE id=? AND admin_id=?");
}

$stmt->bind_param("sii", $message, $id, $admin_id);
$stmt->execute();
$stmt->close();
$conn->close();
echo 'success';

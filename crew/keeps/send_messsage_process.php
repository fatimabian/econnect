<?php
session_start();
include "db_connect.php";

$recipient_type = $_POST['recipient_type'] ?? null;
$message = trim($_POST['message'] ?? '');
$admin_id = $_SESSION['barangay_admin_id'] ?? null;

if(!$recipient_type || !$message || !$admin_id){
    http_response_code(400);
    exit('Invalid request');
}

if($recipient_type === 'crew'){
    // Send to all crew in this barangay
    $sql = "INSERT INTO crew_inbox (crew_id, admin_id, message, status, created_at)
            SELECT id, ?, ?, 'Unread', NOW() FROM collection_crew WHERE barangay=(SELECT barangay FROM barangay_admins WHERE id=?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $admin_id, $message, $admin_id);
} else { // Users
    $sql = "INSERT INTO user_inbox (user_id, admin_id, message, created_at)
            SELECT id, ?, ?, NOW() FROM users";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $admin_id, $message);
}

$stmt->execute();
$stmt->close();
$conn->close();
echo 'success';

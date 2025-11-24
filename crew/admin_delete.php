<?php
session_start();
include "db_connect.php";

$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id || !isset($_POST['id'], $_POST['type'])) {
    exit('Invalid request');
}

$id = intval($_POST['id']);
$type = $_POST['type'];

if($type === "Crew") {
    $stmt = $conn->prepare("DELETE FROM crew_inbox WHERE id=? AND admin_id=?");
    $stmt->bind_param("ii", $id, $admin_id);
} else if($type === "User") {
    $stmt = $conn->prepare("DELETE FROM user_inbox WHERE id=? AND admin_id=?");
    $stmt->bind_param("ii", $id, $admin_id);
} else {
    exit('Invalid type');
}

$stmt->execute();
echo "deleted";
?>

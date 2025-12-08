<?php
session_start();
include "db_connect.php";

$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id || !isset($_POST['id'])) {
    exit('Invalid request');
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("UPDATE crew_inbox SET status='Read' WHERE id=? AND admin_id=?");
$stmt->bind_param("ii", $id, $admin_id);
$stmt->execute();
echo "success";
?>

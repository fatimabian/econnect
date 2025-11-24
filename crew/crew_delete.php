<?php
session_start();
include "db_connect.php";

$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id || !isset($_POST['id'])) {
    exit('Invalid request');
}

$id = intval($_POST['id']);
$stmt = $conn->prepare("DELETE FROM crew_reports WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
echo "deleted";
?>

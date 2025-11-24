<?php
include "../db_connect.php";
$crew_id = $_GET['crew_id'];

$stmt = $conn->prepare("INSERT INTO completed_pickups (crew_id, completed_at) VALUES (?, NOW())");
$stmt->bind_param("i", $crew_id);
$stmt->execute();

echo "Pickup Completed!";

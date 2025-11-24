<?php
include "../db_connect.php";

$crew_id = $_POST['crew_id'];
$lat = $_POST['lat'];
$lng = $_POST['lng'];

$stmt = $conn->prepare("UPDATE collection_crew SET lat=?, lng=? WHERE id=?");
$stmt->bind_param("ddi", $lat, $lng, $crew_id);
$stmt->execute();

echo "OK";

<?php
include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = $_POST['schedule_id'];
    $barangay = $_POST['barangay'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $stmt = $conn->prepare("
        UPDATE collection_schedule
        SET barangay = ?, date = ?, time = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $barangay, $date, $time, $id);

    if ($stmt->execute()) {
        header("Location: collection_management.php?updated=1");
        exit;
    } else {
        echo "Error updating schedule.";
    }
}

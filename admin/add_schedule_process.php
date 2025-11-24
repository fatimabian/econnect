<?php
include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $barangay = $_POST['barangay'];
    $date = $_POST['date'];
    $time = $_POST['time'];

    $stmt = $conn->prepare("
        INSERT INTO collection_schedule (barangay, date, time)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("sss", $barangay, $date, $time);

    if ($stmt->execute()) {
        header("Location: collection_management.php?added=1");
        exit;
    } else {
        echo "Error adding schedule.";
    }
}

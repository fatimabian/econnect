<?php
include "db_connect.php";

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM collection_schedule WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: collection_management.php?deleted=1");
        exit;
    } else {
        echo "Error deleting schedule.";
    }
}

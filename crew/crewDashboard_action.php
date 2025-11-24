<?php
include '../db_connect.php';

if(isset($_GET['mark_read'])) {
    $id = intval($_GET['mark_read']);
    $conn->query("UPDATE crew_inbox SET status='Read' WHERE id=$id");
}

if(isset($_GET['delete_msg'])) {
    $id = intval($_GET['delete_msg']);
    $conn->query("DELETE FROM crew_inbox WHERE id=$id");
}
?>

<?php
include "../db_connect.php";

$result = $conn->query("SELECT lat, lng FROM collection_crew LIMIT 1");
echo json_encode($result->fetch_assoc());

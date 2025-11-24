<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "econnect";

// $servername = "sql209.infinityfree.com";
// $username = "if0_40431922";
// $password = "Pf0qBbF3nO5i"; 
// $dbname = "if0_40431922_XXX";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
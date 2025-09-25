<?php
$servername = "localhost";
$username   = "root";
$password   = "toor";           // your MySQL password
$dbname     = "cet_internship_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
?>

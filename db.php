<?php
$host = "sql103.infinityfree.com";  // Change if your database is hosted remotely
$user = "if0_38568116";       // Your MySQL username
$pass = "KpXSolJEtejI";           // Your MySQL password
$dbname = "if0_38568116_new_attendance_db";  // Database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
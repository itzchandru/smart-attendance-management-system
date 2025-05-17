<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=sql103.infinityfree.com;dbname=if0_38568116_new_attendance_db", "if0_38568116", "KpXSolJEtejI");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connected successfully!";
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

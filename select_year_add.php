<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Year</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4 p-4 bg-white shadow rounded text-center">
    <h3 class="mb-4">🎓 Select Year to Add Student</h3>
    
    <div class="row">
        <div class="col-md-3 mb-3">
            <a href="1st_add_student.php?year=1" class="btn btn-primary btn-lg w-100 py-3">1st Year</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="2nd_add_student.php?year=2" class="btn btn-success btn-lg w-100 py-3">2nd Year</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="add_student.php?year=3" class="btn btn-warning btn-lg w-100 py-3">3rd Year</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="4th_add_student.php?year=4" class="btn btn-danger btn-lg w-100 py-3">4th Year</a>
        </div>
    </div>

    <a href="manage_students.php" class="btn btn-secondary mt-3">🔙 Back</a>
</div>
</body>
</html>
<?php
session_start();
include "db.php"; // Include database connection

// Get filter parameters from the URL
$student = $_GET['student'] ?? '';
$subject = $_GET['subject'] ?? '';
$dateRange = $_GET['date_range'] ?? '';

// Parse date range
$startDate = '';
$endDate = '';
if (!empty($dateRange)) {
    $dates = explode(' to ', $dateRange);
    $startDate = $dates[0];
    $endDate = $dates[1] ?? $dates[0]; // If only one date is selected
}

// Build the SQL query for first-year tables
$query = "SELECT fourth_students.name, fourth_students.roll_number, fourth_attendance.date, fourth_attendance.status, fourth_subjects.subject_name 
          FROM fourth_attendance 
          JOIN fourth_students ON fourth_attendance.roll_number = fourth_students.roll_number 
          JOIN fourth_subjects ON fourth_attendance.subject_code = fourth_subjects.subject_code 
          WHERE 1=1";

if (!empty($student)) {
    $query .= " AND fourth_students.roll_number = '$student'";
}
if (!empty($subject)) {
    $query .= " AND fourth_attendance.subject_code = '$subject'";
}
if (!empty($startDate) && !empty($endDate)) {
    $query .= " AND fourth_attendance.date BETWEEN '$startDate' AND '$endDate'";
}

$query .= " ORDER BY fourth_attendance.date DESC";

// Execute the query
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview First Year Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #6EE7B7, #3B82F6);
        }
        .container-custom {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
        }
        .table-custom th, .table-custom td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table-custom th {
            background-color: #f8f9fa;
        }
        .status-present {
            color: green;
        }
        .status-absent {
            color: red;
        }
    </style>
</head>
<body class="gradient-bg">
    <div class="container-custom mt-5">
        <div class="text-center mb-4">
            <img src="./images/immigration.png" alt="Logo" class="mb-3" style="width: 80px;">
            <h3 class="text-center">📋 First Year Attendance Report</h3>
        </div>

        <!-- Display Filters -->
        <div class="mb-4">
            <p><strong>Filters Applied:</strong></p>
            <ul>
                <?php if (!empty($student)): ?>
                    <li><strong>Student:</strong> <?= $student; ?></li>
                <?php endif; ?>
                <?php if (!empty($subject)): ?>
                    <li><strong>Subject:</strong> <?= $subject; ?></li>
                <?php endif; ?>
                <?php if (!empty($startDate) && !empty($endDate)): ?>
                    <li><strong>Date Range:</strong> <?= $startDate; ?> to <?= $endDate; ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Attendance Table -->
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['name']; ?></td>
                            <td><?= $row['roll_number']; ?></td>
                            <td><?= $row['date']; ?></td>
                            <td><?= $row['subject_name']; ?></td>
                            <td class="<?= $row['status'] === 'Present' ? 'status-present' : 'status-absent'; ?>">
                                <?= $row['status']; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No attendance records found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="4th_principal_export_attendance.php" class="btn btn-primary">⬅️ Back to Filters</a>
        </div>
    </div>
</body>
</html>
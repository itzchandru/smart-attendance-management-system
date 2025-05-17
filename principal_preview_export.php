<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session only if it's not already active
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    if (count($dates) === 2) {
        $startDate = $dates[0];
        $endDate = $dates[1];
    } else {
        die("Invalid date range format. Expected format: 'YYYY-MM-DD to YYYY-MM-DD'.");
    }
}

// Enable SQL_BIG_SELECTS to allow large queries
$conn->query("SET SQL_BIG_SELECTS=1");

// Build the SQL query
$query = "SELECT students.name, students.roll_number, attendance.date, attendance.status, subjects.subject_name 
          FROM attendance 
          JOIN students ON attendance.roll_number = students.roll_number 
          JOIN subjects ON attendance.subject_code = subjects.subject_code 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($student)) {
    $query .= " AND students.roll_number = ?";
    $params[] = $student;
    $types .= "s";
}
if (!empty($subject)) {
    $query .= " AND attendance.subject_code = ?";
    $params[] = $subject;
    $types .= "s";
}
if (!empty($startDate) && !empty($endDate)) {
    $query .= " AND attendance.date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$query .= " ORDER BY attendance.date DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Attendance</title>
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
            <h3 class="text-center">📋 Preview Attendance Report</h3>
        </div>

        <!-- Display Filters -->
        <div class="mb-4">
            <p><strong>Filters Applied:</strong></p>
            <ul>
                <?php if (!empty($student)): ?>
                    <li><strong>Student:</strong> <?= htmlspecialchars($student); ?></li>
                <?php endif; ?>
                <?php if (!empty($subject)): ?>
                    <li><strong>Subject:</strong> <?= htmlspecialchars($subject); ?></li>
                <?php endif; ?>
                <?php if (!empty($startDate) && !empty($endDate)): ?>
                    <li><strong>Date Range:</strong> <?= htmlspecialchars($startDate); ?> to <?= htmlspecialchars($endDate); ?></li>
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
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['roll_number']); ?></td>
                            <td><?= htmlspecialchars($row['date']); ?></td>
                            <td><?= htmlspecialchars($row['subject_name']); ?></td>
                            <td class="<?= $row['status'] === 'Present' ? 'status-present' : 'status-absent'; ?>">
                                <?= htmlspecialchars($row['status']); ?>
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
            <a href="3rd_principal_export_attendance_form.php" class="btn btn-primary">⬅️ Back to Filters</a>
        </div>
    </div>
</body>
</html>
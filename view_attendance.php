<?php
session_start();
include 'db.php';

// Ensure principal is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch distinct subjects, years, and months for filters
$subject_query = "SELECT DISTINCT subject_name FROM attendance ORDER BY subject_name";
$subject_result = $conn->query($subject_query);

$year_query = "SELECT DISTINCT YEAR(date) as year FROM attendance ORDER BY year DESC";
$year_result = $conn->query($year_query);

$month_query = "SELECT DISTINCT MONTH(date) as month, MONTHNAME(date) as month_name 
                FROM attendance 
                ORDER BY month";
$month_result = $conn->query($month_query);

// Get filters from GET
$search_name = $_GET['search_name'] ?? '';
$search_roll = $_GET['search_roll'] ?? '';
$search_subject = $_GET['search_subject'] ?? '';
$search_date = $_GET['search_date'] ?? '';
$search_status = $_GET['search_status'] ?? '';
$search_week = $_GET['search_week'] ?? '';
$search_month = $_GET['search_month'] ?? '';
$search_year = $_GET['search_year'] ?? '';

$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build filtered query
$base_query = "FROM attendance 
               JOIN students ON attendance.roll_number = students.roll_number 
               WHERE 1=1";

if ($search_name) {
    $base_query .= " AND students.name LIKE '%$search_name%'";
}
if ($search_roll) {
    $base_query .= " AND students.roll_number LIKE '%$search_roll%'";
}
if ($search_subject) {
    $base_query .= " AND attendance.subject_name = '$search_subject'";
}
if ($search_date) {
    $base_query .= " AND attendance.date = '$search_date'";
}
if ($search_status) {
    $base_query .= " AND attendance.status = '$search_status'";
}
if ($search_week) {
    $base_query .= " AND WEEK(attendance.date, 1) = WEEK('$search_week', 1) AND YEAR(attendance.date) = YEAR('$search_week')";
}
if ($search_month && $search_year) {
    $base_query .= " AND MONTH(attendance.date) = '$search_month' AND YEAR(attendance.date) = '$search_year'";
}

$attendance_query = "SELECT students.name AS student_name, students.roll_number, 
                            attendance.subject_name, attendance.status, attendance.date 
                     $base_query 
                     ORDER BY attendance.date DESC, students.name ASC
                     LIMIT $limit OFFSET $offset";

$attendance_result = $conn->query($attendance_query);
if (!$attendance_result) {
    die("Query failed: " . $conn->error);
}

// For pagination
$total_query = "SELECT COUNT(*) as total $base_query";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);

// Calculate attendance statistics
$stats_query = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count
                $base_query";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
$present_percentage = $stats['total_records'] > 0 ? round(($stats['present_count'] / $stats['total_records']) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.15);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            border: none;
        }
        
        .table td, .table th {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover td {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .badge-present {
            background-color: #28a745;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .badge-absent {
            background-color: #dc3545;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .filter-section {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 12px;
            color: white;
        }
        
        .stats-card.present {
            background: linear-gradient(135deg, var(--success-color), var(--accent-color));
        }
        
        .stats-card.absent {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }
        
        .stats-card.total {
            background: linear-gradient(135deg, #7209b7, #560bad);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .pagination .page-link {
            color: black;
            border-radius: 8px;
            margin: 0 3px;
            border: none;
            padding: 0.5rem 1rem;
        }
        
        .pagination .page-link:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-control, .form-select {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .input-group-text {
            background-color: white;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .progress-bar {
            background-color: var(--success-color);
        }
        
        .export-btn {
            background-color: #4cc9f0;
            border-color: #4cc9f0;
            color: white;
        }
        
        .export-btn:hover {
            background-color: #3aa8d8;
            border-color: #3aa8d8;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-1"><i class="bi bi-calendar-check me-2"></i> Attendance Records</h1>
                <p class="mb-0 opacity-75">View and analyze student attendance data</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-light rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card total">
                <i class="bi bi-journal-text" style="font-size: 1.5rem;"></i>
                <div class="stats-value"><?= $stats['total_records'] ?></div>
                <div class="stats-label">Total Records</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card present">
                <i class="bi bi-check-circle" style="font-size: 1.5rem;"></i>
                <div class="stats-value"><?= $stats['present_count'] ?></div>
                <div class="stats-label">Present (<?= $present_percentage ?>%)</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: <?= $present_percentage ?>%" 
                         aria-valuenow="<?= $present_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card absent">
                <i class="bi bi-x-circle" style="font-size: 1.5rem;"></i>
                <div class="stats-value"><?= $stats['absent_count'] ?></div>
                <div class="stats-label">Absent (<?= 100 - $present_percentage ?>%)</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= 100 - $present_percentage ?>%" 
                         aria-valuenow="<?= 100 - $present_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-funnel me-2"></i>Filter Options
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Student Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="search_name" class="form-control" placeholder="Search by name" 
                               value="<?= htmlspecialchars($search_name); ?>">
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Roll Number</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-123"></i></span>
                        <input type="text" name="search_roll" class="form-control" placeholder="Search by roll number" 
                               value="<?= htmlspecialchars($search_roll); ?>">
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Subject</label>
                    <select name="search_subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php while ($row = $subject_result->fetch_assoc()) { ?>
                            <option value="<?= htmlspecialchars($row['subject_name']); ?>" <?= $search_subject == $row['subject_name'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($row['subject_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Status</label>
                    <select name="search_status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Present" <?= $search_status == 'Present' ? 'selected' : ''; ?>>Present</option>
                        <option value="Absent" <?= $search_status == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Specific Date</label>
                    <input type="date" name="search_date" class="form-control" value="<?= htmlspecialchars($search_date); ?>">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Week</label>
                    <input type="week" name="search_week" class="form-control" value="<?= htmlspecialchars($search_week); ?>">
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label">Month</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <select name="search_month" class="form-select">
                                <option value="">All Months</option>
                                <?php while ($row = $month_result->fetch_assoc()) { ?>
                                    <option value="<?= $row['month'] ?>" <?= $search_month == $row['month'] ? 'selected' : ''; ?>>
                                        <?= $row['month_name'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <select name="search_year" class="form-select">
                                <option value="">Year</option>
                                <?php while ($row = $year_result->fetch_assoc()) { ?>
                                    <option value="<?= $row['year'] ?>" <?= $search_year == $row['year'] ? 'selected' : ''; ?>>
                                        <?= $row['year'] ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 me-2">
                        <i class="bi bi-search me-1"></i> Apply Filters
                    </button>
                    <a href="view_attendance.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table me-2"></i>Attendance Records</span>
            <div>
                <button class="btn btn-sm export-btn me-2">
                    <i class="bi bi-download me-1"></i> Export
                </button>
                <span class="badge bg-light text-dark">
                    Page <?= $page ?> of <?= $total_pages ?> (<?= $total_row['total'] ?> records)
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Roll No.</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Day</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_result->num_rows > 0): ?>
                            <?php while ($row = $attendance_result->fetch_assoc()): 
                                $date = new DateTime($row['date']);
                                $dayName = $date->format('l');
                                $formattedDate = $date->format('M j, Y');
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                                    <td><?= htmlspecialchars($row['roll_number']); ?></td>
                                    <td><?= htmlspecialchars($row['subject_name']); ?></td>
                                    <td>
                                        <span class="badge <?= $row['status'] === 'Present' ? 'badge-present' : 'badge-absent' ?>">
                                            <?= $row['status'] === 'Present' ? '<i class="bi bi-check-circle me-1"></i> Present' : '<i class="bi bi-x-circle me-1"></i> Absent' ?>
                                        </span>
                                    </td>
                                    <td><?= $formattedDate ?></td>
                                    <td><?= $dayName ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem; color: #6c757d;"></i>
                                    <h5 class="mt-2">No attendance records found</h5>
                                    <p class="text-muted">Try adjusting your filters or add new records</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php 
// Check if $page is set in URL and ensure it’s a valid number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = $page < 1 ? 1 : $page;  // Ensure page is at least 1

// Assuming $total_pages is calculated from the database (total records / records per page)
$total_pages = 10; // For example, update with your logic
?>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center mt-4">
            <!-- First Page Button -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=1<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" aria-label="First">
                    <span aria-hidden="true">&laquo;&laquo;</span>
                </a>
            </li>
            <!-- Previous Page Button -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            
            <?php 
            // Show page numbers
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            
            if ($start > 1) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            
            if ($end < $total_pages) {
                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            ?>
            
            <!-- Next Page Button -->
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
            <!-- Last Page Button -->
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $total_pages ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" aria-label="Last">
                    <span aria-hidden="true">&raquo;&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>


        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Initialize date pickers
    flatpickr("input[type=date]", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });
    
    flatpickr("input[type=week]", {
        weekNumbers: true,
        dateFormat: "Y-\\WW",
    });
    
    // Export button functionality (placeholder)
    document.querySelector('.export-btn').addEventListener('click', function() {
        alert('Export functionality would be implemented here. This could export to Excel, PDF, or CSV.');
    });
</script>
</body>
</html>
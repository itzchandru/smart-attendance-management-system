<?php
session_start();
include 'db.php';

if (!isset($_SESSION['principal_id'])) {
    header("Location: principal_login.php");
    exit();
}

$principal_name = $_SESSION['principal_name'];

// Fetch total counts
$total_students_query = "
    SELECT COUNT(*) AS total_students FROM (
        SELECT id FROM first_students
        UNION ALL
        SELECT id FROM second_students
        UNION ALL
        SELECT id FROM students
        UNION ALL
        SELECT id FROM fourth_students
    ) AS all_students
";

$total_students = $conn->query($total_students_query)->fetch_assoc()['total_students'];

$total_faculty = $conn->query("SELECT COUNT(*) AS total_faculty FROM faculty")->fetch_assoc()['total_faculty'];

$total_subjects_query = "
    SELECT COUNT(*) AS total_subjects FROM (
        SELECT id FROM first_subjects
        UNION ALL
        SELECT id FROM second_subjects
        UNION ALL
        SELECT id FROM subjects
        UNION ALL
        SELECT id FROM fourth_subjects
    ) AS all_subjects
";

$total_subjects = $conn->query($total_subjects_query)->fetch_assoc()['total_subjects'];

$total_attendance_query = "
    SELECT COUNT(*) AS total_attendance FROM (
        SELECT id FROM first_attendance
        UNION ALL
        SELECT id FROM second_attendance
        UNION ALL
        SELECT id FROM attendance
        UNION ALL
        SELECT id FROM fourth_attendance
    ) AS all_attendance
";

$total_attendance = $conn->query($total_attendance_query)->fetch_assoc()['total_attendance'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard | ECE Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --dark-color: #1a1a2e;
            --light-color: #ffffff;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--secondary-color);
            color: var(--dark-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--light-color) !important;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--accent-color) 100%);
            min-height: calc(100vh - 56px);
            width: 14rem;
            transition: all 0.3s;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            padding: 1rem;
            margin: 0.2rem 0;
            border-radius: 0.35rem;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            color: var(--light-color);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: var(--light-color);
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem;
            width: calc(100% - 14rem);
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .welcome-card {
            background-color: var(--light-color);
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: all 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .stat-card .stat-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .action-card {
            background-color: var(--light-color);
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            height: 100%;
        }
        
        .action-btn {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 0.35rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .footer {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 1.5rem;
            text-align: center;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .footer-brand-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 0.35rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .footer-social {
            margin: 1rem 0;
        }
        
        .footer-social a {
            color: var(--light-color);
            margin: 0 0.5rem;
            font-size: 1.25rem;
            transition: all 0.3s;
        }
        
        .footer-social a:hover {
            color: var(--primary-color);
        }
        
        .footer-copyright {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
    .sidebar {
        display: none !important;
    }

    .main-content {
        width: 100% !important;
    }
}

    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-mortarboard-fill me-2"></i>ECE Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?php echo $principal_name; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="index.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column">
            <div class="p-3">
                <h5 class="text-white mb-0">Navigation</h5>
            </div>
            <ul class="nav flex-column px-3">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="principal_view_attendance_redirect.php">
                        <i class="bi bi-calendar-check"></i> Attendance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="principal_analytics_redirect.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="principal_export_redirect.php">
                        <i class="bi bi-file-earmark-arrow-down"></i> Reports
                    </a>
                </li>
                
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <h2 class="fw-bold text-primary animate__animated animate__fadeIn">Principal Dashboard</h2>
                <p class="text-muted">Welcome back, <?php echo $principal_name; ?></p>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card animate__animated animate__fadeInUp">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="bi bi-people-fill text-primary"></i>
                            </div>
                            <div class="stat-title">Total Students</div>
                            <div class="stat-value"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card animate__animated animate__fadeInUp animate__delay-1s">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="bi bi-person-video3 text-success"></i>
                            </div>
                            <div class="stat-title">Total Faculty</div>
                            <div class="stat-value"><?php echo $total_faculty; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card animate__animated animate__fadeInUp animate__delay-2s">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="bi bi-book text-info"></i>
                            </div>
                            <div class="stat-title">Total Subjects</div>
                            <div class="stat-value"><?php echo $total_subjects; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card animate__animated animate__fadeInUp animate__delay-3s">
                        <div class="card-body">
                            <div class="stat-icon">
                                <i class="bi bi-clipboard-check text-warning"></i>
                            </div>
                            <div class="stat-title">Attendance Records</div>
                            <div class="stat-value"><?php echo $total_attendance; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="action-card animate__animated animate__fadeIn">
                        <h5 class="fw-bold mb-4">Quick Actions</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="principal_view_attendance_redirect.php" class="btn btn-primary action-btn">
                                    <i class="bi bi-calendar-check me-2"></i> View Attendance
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="principal_analytics_redirect.php" class="btn btn-success action-btn">
                                    <i class="bi bi-graph-up me-2"></i> View Analytics
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="principal_export_redirect.php" class="btn btn-info action-btn">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i> Export Report
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="index.php" class="btn btn-danger action-btn">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-brand">
                <div class="footer-brand-icon">
                    <i class="bi bi-cpu-fill"></i>
                </div>
                <div>
                    <h5 class="mb-0">ECE Department</h5>
                    <small>Electronics & Communication Engineering</small>
                </div>
            </div>
            <div class="footer-social">
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-twitter"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
                <a href="#"><i class="bi bi-github"></i></a>
            </div>
            <div class="footer-copyright">
                <p class="mb-1">© <?php echo date('Y'); ?> Electronics & Communication Engineering Department. All rights reserved.</p>
                <p class="mb-1">Designed and developed by Chandru B, Sundareswaran M, Abdulgani B, and Aravinthkumar K</p>
                <p class="mb-0">Final Year ECE Students | GCE Thanjavur | Batch of 2025</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>
<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'];

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

$recent_activity = [];

// 1. New student registrations
$student_query = "
    SELECT name, created_at FROM (
        SELECT name, created_at FROM first_students WHERE DATE(created_at) = CURDATE()
        UNION ALL
        SELECT name, created_at FROM second_students WHERE DATE(created_at) = CURDATE()
        UNION ALL
        SELECT name, created_at FROM students WHERE DATE(created_at) = CURDATE()
        UNION ALL
        SELECT name, created_at FROM fourth_students WHERE DATE(created_at) = CURDATE()
    ) AS all_students
    ORDER BY created_at DESC
    LIMIT 1;
";

$result = $conn->query($student_query);
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = [
        'icon' => 'fas fa-user-plus',
        'color' => 'text-success',
        'text' => "New student registered: " . $row['name'],
        'time' => timeAgo($row['created_at'])
    ];
}

// 2. Attendance marked
$attendance_query = "
    SELECT subject_name, date FROM (
        SELECT subject_name, date FROM first_attendance
        UNION ALL
        SELECT subject_name, date FROM second_attendance
        UNION ALL
        SELECT subject_name, date FROM attendance
        UNION ALL
        SELECT subject_name, date FROM fourth_attendance
    ) AS all_attendance
    ORDER BY date DESC
    LIMIT 1;
";
$result = $conn->query($attendance_query);
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = [
        'icon' => 'fas fa-clipboard-check',
        'color' => 'text-primary',
        'text' => "Attendance marked for " . $row['subject_name'],
        'time' => timeAgo($row['date'])
    ];
}

// Function to convert datetime to "x mins ago"
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return $diff . " secs ago";
    if ($diff < 3600) return floor($diff / 60) . " mins ago";
    if ($diff < 86400) return floor($diff / 3600) . " hrs ago";
    return date("d M Y", $time);
}

$today = date("Y-m-d");

$attendance_overview = [
    '1st Year' => $conn->query("SELECT COUNT(*) AS count FROM first_attendance WHERE date = '$today'")->fetch_assoc()['count'],
    '2nd Year' => $conn->query("SELECT COUNT(*) AS count FROM second_attendance WHERE date = '$today'")->fetch_assoc()['count'],
    '3rd Year' => $conn->query("SELECT COUNT(*) AS count FROM attendance WHERE date = '$today'")->fetch_assoc()['count'],
    '4th Year' => $conn->query("SELECT COUNT(*) AS count FROM fourth_attendance WHERE date = '$today'")->fetch_assoc()['count'],
];

// Check for pending registration requests
$pending_requests = $conn->query("SELECT COUNT(*) AS count FROM registration_requests WHERE is_verified = 1 AND is_approved = 0")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | ECE Department</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --info-color: #560bad;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--dark-color), #16213e);
            color: white;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin: 5px 0;
            border-radius: 5px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
            flex: 1;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        
        .stat-card {
            color: white;
            padding: 20px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.5;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        
        .stat-card .count {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        .stat-card .title {
            opacity: 0.8;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .students-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }
        
        .faculty-card {
            background: linear-gradient(135deg, #7209b7, #560bad);
        }
        
        .subjects-card {
            background: linear-gradient(135deg, var(--accent-color), #4cc9f0);
        }
        
        .attendance-card {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }
        
        .recent-activity {
            list-style: none;
            padding: 0;
        }
        
        .recent-activity li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }
        
        .recent-activity li:last-child {
            border-bottom: none;
        }
        
        .recent-activity .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .recent-activity .activity-text {
            flex: 1;
        }
        
        .recent-activity .activity-time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .quick-actions .btn {
            padding: 10px;
            margin: 3px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quick-actions .btn i {
            margin-right: 8px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .user-profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }
        
        .user-profile .user-info h5 {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .user-profile .user-info p {
            margin-bottom: 0;
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .welcome-banner h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-banner p {
            opacity: 0.9;
            max-width: 600px;
        }
        
        .welcome-banner .btn {
            margin-top: 15px;
            background: white;
            color: var(--primary-color);
            font-weight: 600;
            border: none;
        }
        
        .pending-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.6rem;
            padding: 3px 6px;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 10px 15px;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .stat-card {
                margin-bottom: 20px;
            }

            .quick-actions .btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .recent-activity li {
                flex-direction: column;
                align-items: flex-start;
            }

            .recent-activity .activity-icon {
                margin-bottom: 5px;
            }
        }
        
        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-up {
            animation: slideUp 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }
        
        /* Footer styles */
        footer {
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        @media (max-width: 992px) {
            footer {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white shadow-sm d-md-none px-3">
        <button class="btn btn-outline-primary" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <span class="navbar-brand ms-2">Admin Dashboard</span>
    </nav>

    <div class="d-flex flex-grow-1">
        <!-- Sidebar -->
        <div class="sidebar col-md-3 col-lg-2 d-none d-lg-block" id="sidebar">
            <div class="user-profile">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=random" alt="Admin">
                <div class="user-info">
                    <h5><?php echo $admin_name; ?></h5>
                    <p>Administrator</p>
                </div>
            </div>
            
            <ul class="nav flex-column mt-2">
                <li class="nav-item">
                    <a class="nav-link active" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_approval.php">
                        <i class="fas fa-user-check"></i>
                        <span>Registration Approval</span>
                        <?php if ($pending_requests > 0): ?>
                        <span class="badge bg-danger pending-badge"><?php echo $pending_requests; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_students.php">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_faculty.php">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Faculty</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_subjects.php">
                        <i class="fas fa-book"></i>
                        <span>Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_attendance_redirect.php">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_analytics_redirect.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="export_redirect.php">
                        <i class="fas fa-file-export"></i>
                        <span>Export</span>
                    </a>
                </li>
                <li class="nav-item mt-1">
                    <a class="nav-link text-danger" href="index.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Welcome Banner -->
            <div class="welcome-banner slide-up">
                <h2>Welcome back, <?php echo $admin_name; ?>!</h2>
                <p>Here's what's happening with your institution today. You have full control over students, faculty, subjects, and attendance records.</p>
                <?php if ($pending_requests > 0): ?>
                <a href="registration_approval.php" class="btn btn-warning">
                    <i class="fas fa-user-check"></i> <?php echo $pending_requests; ?> Pending Approvals
                </a>
                <?php endif; ?>
            </div>

            <!-- Stats Cards -->
            <div class="row fade-in">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card students-card">
                        <i class="fas fa-users"></i>
                        <div class="count"><?php echo $total_students; ?></div>
                        <div class="title">Total Students</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card faculty-card">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <div class="count"><?php echo $total_faculty; ?></div>
                        <div class="title">Faculty Members</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card subjects-card">
                        <i class="fas fa-book"></i>
                        <div class="count"><?php echo $total_subjects; ?></div>
                        <div class="title">Subjects Offered</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card attendance-card">
                        <i class="fas fa-clipboard-check"></i>
                        <div class="count"><?php echo $total_attendance; ?></div>
                        <div class="title">Attendance Records</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions and Recent Activity -->
            <div class="row mt-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Quick Actions</h5>
                            <span class="badge bg-primary">Shortcuts</span>
                        </div>
                        <div class="card-body">
                            <div class="row quick-actions">
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="manage_students.php" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus"></i>Add Student
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="manage_faculty.php" class="btn btn-outline-success">
                                        <i class="fas fa-user-tie"></i>Add Faculty
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="manage_subjects.php" class="btn btn-outline-info">
                                        <i class="fas fa-book-medical"></i>Add Subject
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="view_attendance_redirect.php" class="btn btn-outline-warning">
                                        <i class="fas fa-calendar-check"></i>View Attendance
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="admin_analytics_redirect.php" class="btn btn-outline-danger">
                                        <i class="fas fa-chart-pie"></i>View Analytics
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="export_redirect.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-file-excel"></i>Export Data
                                    </a>
                                </div>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <a href="admin_approval.php" class="btn btn-outline-success">
                                        <i class="fas fa-user-check"></i> Registration Approval
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <ul class="recent-activity">
                                <?php foreach($recent_activity as $activity): ?>
                                <li>
                                    <div class="activity-icon <?php echo $activity['color']; ?>">
                                        <i class="<?php echo $activity['icon']; ?>"></i>
                                    </div>
                                    <div class="activity-text">
                                        <?php echo $activity['text']; ?>
                                        <div class="activity-time"><?php echo $activity['time']; ?></div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Attendance Overview</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Student Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="studentsChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container-fluid">
            <div class="row text-start text-md-center">
                <div class="col-md-4 mb-3">
                    <h5>About ECE Department</h5>
                    <p>The Electronics and Communication Engineering department provides cutting-edge education and research opportunities in various fields of electronics.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white text-decoration-none">Home</a></li>
                        <li><a href="manage_students.php" class="text-white text-decoration-none">Students</a></li>
                        <li><a href="manage_faculty.php" class="text-white text-decoration-none">Faculty</a></li>
                        <li><a href="#" class="text-white text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Connect With Us</h5>
                    <div class="mb-2">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                    <p class="mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i> Government College of Engineering, Thanjavur
                    </p>
                </div>
            </div>

            <hr class="bg-light">

            <div class="row">
                <div class="col-md-6 text-start text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> ECE Department. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end text-md-end">
                    <p class="mb-0">Developed by: Chandru B, Sundareswaran M, Abdulgani B, and Aravinthkumar K</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attendance Chart
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        const attendanceChart = new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Attendance Percentage',
                    data: [85, 79, 88, 91, 82, 89],
                    backgroundColor: 'rgba(67, 97, 238, 0.2)',
                    borderColor: 'rgba(67, 97, 238, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 70,
                        max: 100
                    }
                }
            }
        });

        // Students Chart
        const studentsCtx = document.getElementById('studentsChart').getContext('2d');
        const studentsChart = new Chart(studentsCtx, {
            type: 'doughnut',
            data: {
                labels: ['First Year', 'Second Year', 'Third Year', 'Fourth Year'],
                datasets: [{
                    data: [120, 115, 110, 105],
                    backgroundColor: [
                        'rgba(67, 97, 238, 0.8)',
                        'rgba(63, 55, 201, 0.8)',
                        'rgba(72, 149, 239, 0.8)',
                        'rgba(76, 201, 240, 0.8)'
                    ],
                    borderColor: [
                        'rgba(67, 97, 238, 1)',
                        'rgba(63, 55, 201, 1)',
                        'rgba(72, 149, 239, 1)',
                        'rgba(76, 201, 240, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Animate cards on scroll
        const animateOnScroll = () => {
            const cards = document.querySelectorAll('.card, .stat-card, .welcome-banner');
            
            cards.forEach(card => {
                const cardPosition = card.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if (cardPosition < screenPosition) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        };

        // Set initial state for animation
        document.querySelectorAll('.card, .stat-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease-out';
        });

        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
        
        // Toggle sidebar on mobile
        document.getElementById('toggleSidebar').addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('d-none');
            sidebar.classList.toggle('position-absolute');
            sidebar.classList.toggle('bg-dark');
            sidebar.classList.toggle('p-3');
            sidebar.classList.toggle('w-100');
            sidebar.classList.toggle('h-100');
        });
    </script>
    <script>
    const attendanceOverviewData = <?php echo json_encode($attendance_overview); ?>;
    const studentDistributionData = <?php
        echo json_encode([
            '1st Year' => $conn->query("SELECT COUNT(*) FROM first_students")->fetch_row()[0],
            '2nd Year' => $conn->query("SELECT COUNT(*) FROM second_students")->fetch_row()[0],
            '3rd Year' => $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0],
            '4th Year' => $conn->query("SELECT COUNT(*) FROM fourth_students")->fetch_row()[0],
        ]);
    ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data from PHP
const attendanceLabels = Object.keys(attendanceOverviewData);
const attendanceCounts = Object.values(attendanceOverviewData);

const studentLabels = Object.keys(studentDistributionData);
const studentCounts = Object.values(studentDistributionData);

// Attendance Overview Chart (Bar Chart)
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
new Chart(attendanceCtx, {
    type: 'bar',
    data: {
        labels: attendanceLabels,
        datasets: [{
            label: 'Today\'s Attendance Count',
            data: attendanceCounts,
            backgroundColor: '#17a2b8'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Student Distribution Chart (Doughnut Chart)
const studentCtx = document.getElementById('studentsChart').getContext('2d');
new Chart(studentCtx, {
    type: 'doughnut',
    data: {
        labels: studentLabels,
        datasets: [{
            label: 'Student Count',
            data: studentCounts,
            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

</body>
</html>
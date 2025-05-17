<?php
session_start();
include 'db.php';

// Ensure admin is logged in
if (!isset($_SESSION['principal_id'])) {
    header("Location: principal_login.php");
    exit();
}

// Filter Variables
$filter = $_GET['filter'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$student_filter = $_GET['student'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Initialize filter conditions
$filter_conditions = [];

// Apply predefined date filters
if ($filter === "today") {
    $filter_conditions[] = "first_attendance.date = CURDATE()";
} elseif ($filter === "yesterday") {
    $filter_conditions[] = "first_attendance.date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filter === "week") {
    $filter_conditions[] = "first_attendance.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === "month") {
    $filter_conditions[] = "first_attendance.date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
}

// Apply custom date range filter
if (!empty($start_date) && !empty($end_date)) {
    $filter_conditions[] = "first_attendance.date BETWEEN '" . $conn->real_escape_string($start_date) . "' AND '" . $conn->real_escape_string($end_date) . "'";
}

// Apply subject filter
if (!empty($subject_filter) && $subject_filter !== "all") {
    $filter_conditions[] = "first_attendance.subject_name = '" . $conn->real_escape_string($subject_filter) . "'";
}

// Apply student filter
if (!empty($student_filter) && $student_filter !== "all") {
    $filter_conditions[] = "first_attendance.roll_number = '" . $conn->real_escape_string($student_filter) . "'";
}

// Construct WHERE clause
$where_clause = !empty($filter_conditions) ? " WHERE " . implode(" AND ", $filter_conditions) : "";

// Total present count
$total_present_query = "SELECT COUNT(*) AS count FROM first_attendance WHERE status='Present' " . (!empty($where_clause) ? " AND " . implode(" AND ", $filter_conditions) : "");
$total_present = $conn->query($total_present_query)->fetch_assoc()['count'];

// Total attendance records
$total_records_query = "SELECT COUNT(*) AS count FROM first_attendance " . (!empty($where_clause) ? $where_clause : "");
$total_records = $conn->query($total_records_query)->fetch_assoc()['count'];

// Calculate overall attendance percentage
$overall_attendance = ($total_records > 0) ? ($total_present / $total_records) * 100 : 0;

// Fetch all subjects for dropdown
$subject_result = $conn->query("SELECT DISTINCT subject_name FROM first_attendance ORDER BY subject_name");

// Fetch all students for dropdown
$student_result = $conn->query("SELECT DISTINCT roll_number, student_name FROM first_attendance ORDER BY student_name");

// Fetch student-wise attendance
$student_attendance_query = "
    SELECT roll_number, student_name, 
           SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count, 
           COUNT(*) AS total_count 
    FROM first_attendance
    " . (!empty($where_clause) ? $where_clause . " AND roll_number IS NOT NULL " : " WHERE roll_number IS NOT NULL ") . "
    GROUP BY roll_number, student_name 
    ORDER BY student_name ASC
";

$student_attendance_result = $conn->query($student_attendance_query);

// Prepare data for student-wise chart
$student_names = [];
$student_present_counts = [];
$student_absent_counts = [];
$student_percentages = [];

while ($row = $student_attendance_result->fetch_assoc()) {
    $student_names[] = $row['student_name'];
    $student_present_counts[] = $row['present_count'];
    $student_absent_counts[] = $row['total_count'] - $row['present_count'];
    $student_percentages[] = ($row['total_count'] > 0) ? round(($row['present_count'] / $row['total_count']) * 100, 2) : 0;
}

// Fetch daily attendance trend
$daily_trend_query = "
    SELECT date, 
           SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,
           COUNT(*) AS total_count
    FROM first_attendance
    " . (!empty($where_clause) ? $where_clause : "") . "
    GROUP BY date
    ORDER BY date ASC
";
$daily_trend_result = $conn->query($daily_trend_query);

$daily_dates = [];
$daily_percentages = [];

while ($row = $daily_trend_result->fetch_assoc()) {
    $daily_dates[] = date('M j', strtotime($row['date']));
    $daily_percentages[] = ($row['total_count'] > 0) ? round(($row['present_count'] / $row['total_count']) * 100, 2) : 0;
}

// Fetch subject-wise attendance
$subject_attendance_query = "
    SELECT subject_name,
           SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,
           COUNT(*) AS total_count
    FROM first_attendance
    " . (!empty($where_clause) ? $where_clause : "") . "
    GROUP BY subject_name
    ORDER BY subject_name ASC
";
$subject_attendance_result = $conn->query($subject_attendance_query);

$subject_names = [];
$subject_percentages = [];

while ($row = $subject_attendance_result->fetch_assoc()) {
    $subject_names[] = $row['subject_name'];
    $subject_percentages[] = ($row['total_count'] > 0) ? round(($row['present_count'] / $row['total_count']) * 100, 2) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #43aa8b;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
            min-height: 100vh;
            padding-top: 60px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 1rem;
            border-radius: 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        
        .main-content {
            margin-top: 80px;
            padding-bottom: 80px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            background-color: white;
        }
        
        /* Enhanced Summary Cards */
        .stat-card {
            text-align: center;
            padding: 1.2rem 0.5rem;
            border-radius: 12px;
            color: white;
            margin-bottom: 1rem;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        
        .stat-card.total {
            background: linear-gradient(135deg, #7209b7, #560bad);
        }
        
        .stat-card.present {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
        }
        
        .stat-card.absent {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }
        
        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0.25rem 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .progress {
            height: 25px;
            border-radius: 12px;
            background-color: #e9ecef;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1030;
            display: flex;
            justify-content: space-around;
            padding: 0.5rem 0;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            color: #666;
            text-decoration: none;
            font-size: 0.7rem;
        }
        
        .nav-item.active {
            color: var(--primary-color);
        }
        
        .nav-icon {
            font-size: 1.2rem;
            margin-bottom: 0.2rem;
        }
        
        /* Dark Mode */
        .dark-mode {
            background: #121212;
            color: #ffffff;
        }
        
        .dark-mode .card {
            background: #1e1e1e;
            color: #ffffff;
        }
        
        .dark-mode .card-header {
            background: #1e1e1e;
            border-bottom: 1px solid #333;
        }
        
        .dark-mode .form-control, 
        .dark-mode .form-select {
            background: #333;
            color: #fff;
            border: 1px solid #444;
        }
        
        .dark-mode .input-group-text {
            background: #333;
            color: #fff;
            border: 1px solid #444;
        }
        
        .dark-mode .progress {
            background-color: #333;
        }
        
        .dark-mode .mobile-nav {
            background: #1e1e1e;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
        }
        
        .dark-mode .nav-item {
            color: #aaa;
        }
        
        .dark-mode .nav-item.active {
            color: var(--accent-color);
        }
        
        .dark-mode .stat-card {
            border-color: rgba(255,255,255,0.1);
        }
        
        /* Responsive adjustments */
        @media (min-width: 768px) {
            body {
                padding-top: 0;
            }
            
            .dashboard-header {
                position: static;
                border-radius: 12px;
                margin: 1rem 0;
            }
            
            .main-content {
                margin-top: 0;
                padding-bottom: 0;
            }
            
            .mobile-nav {
                display: none;
            }
            
            .stat-card {
                padding: 1.5rem;
                min-height: 150px;
            }
            
            .stat-icon {
                font-size: 2.2rem;
            }
            
            .stat-value {
                font-size: 2.5rem;
            }
            
            .stat-label {
                font-size: 1rem;
            }
        }
        
        /* Filter form adjustments */
        .filter-form .form-group {
            margin-bottom: 0.5rem;
        }
        
        /* Button adjustments */
        .btn {
            white-space: nowrap;
        }
        
        /* Dark mode toggle */
        .dark-mode-toggle {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 1050;
        }
        
        .dark-mode-toggle .form-check-input {
            width: 2.5em;
            height: 1.25em;
        }
        
        /* Animation for cards */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animated-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
</head>
<body>

    <!-- Dashboard Header (Mobile) -->
    <div class="dashboard-header d-block d-md-none">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h5 mb-0"><i class="bi bi-bar-chart-line me-2"></i> Attendance Analytics</h1>
            </div>
            <a href="principal_alt_dashboard.php" class="btn btn-light btn-sm rounded-pill">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>

    <div class="container main-content">
        <!-- Dashboard Header (Desktop) -->
        <div class="dashboard-header d-none d-md-block">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1"><i class="bi bi-bar-chart-line me-2"></i> Attendance Analytics Dashboard</h1>
                    <p class="mb-0 opacity-75 d-none d-md-block">Comprehensive insights into student attendance patterns</p>
                </div>
                <a href="principal_alt_dashboard.php" class="btn btn-light rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card animated-card">
            <div class="card-header">
                <i class="bi bi-funnel me-2"></i>Filter Options
            </div>
            <div class="card-body">
                <form method="GET" class="row g-2 filter-form">
                    <div class="col-6 col-md-3">
                        <select name="filter" class="form-select form-select-sm">
                            <option value="">All Time</option>
                            <option value="today" <?= ($filter === "today") ? "selected" : "" ?>>Today</option>
                            <option value="yesterday" <?= ($filter === "yesterday") ? "selected" : "" ?>>Yesterday</option>
                            <option value="week" <?= ($filter === "week") ? "selected" : "" ?>>Last 7 Days</option>
                            <option value="month" <?= ($filter === "month") ? "selected" : "" ?>>Last 30 Days</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date; ?>" placeholder="From">
                    </div>

                    <div class="col-6 col-md-3">
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date; ?>" placeholder="To">
                    </div>

                    <div class="col-6 col-md-3">
                        <select name="subject" class="form-select form-select-sm">
                            <option value="all">All Subjects</option>
                            <?php while ($row = $subject_result->fetch_assoc()) { ?>
                                <option value="<?= $row['subject_name']; ?>" <?= ($subject_filter === $row['subject_name']) ? "selected" : "" ?>>
                                    <?= $row['subject_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 mt-2">
                        <select name="student" class="form-select form-select-sm">
                            <option value="all">All Students</option>
                            <?php while ($row = $student_result->fetch_assoc()) { ?>
                                <option value="<?= $row['roll_number']; ?>" <?= ($student_filter === $row['roll_number']) ? "selected" : "" ?>>
                                    <?= $row['student_name']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-6 col-md-3 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                    </div>

                    <div class="col-6 col-md-3 mt-2">
                        <a href="1st_principal_analytics.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Enhanced Summary Cards -->
        <div class="row">
            <div class="col-4">
                <div class="stat-card total animated-card">
                    <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
                    <div class="stat-value"><?= $total_records ?></div>
                    <div class="stat-label">TOTAL PERIODS</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card present animated-card">
                    <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                    <div class="stat-value"><?= $total_present ?></div>
                    <div class="stat-label">PRESENT</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card absent animated-card">
                    <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                    <div class="stat-value"><?= ($total_records - $total_present) ?></div>
                    <div class="stat-label">ABSENT</div>
                </div>
            </div>
        </div>

        <!-- Overall Attendance Progress -->
        <div class="card animated-card">
            <div class="card-header">
                <i class="bi bi-speedometer2 me-2"></i>Overall Attendance Rate
            </div>
            <div class="card-body">
                <div class="progress mb-2">
                    <div class="progress-bar" role="progressbar" style="width: <?= $overall_attendance; ?>%;" 
                         aria-valuenow="<?= $overall_attendance; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?= round($overall_attendance, 2); ?>%
                    </div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>0%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <!-- Overall Attendance Chart -->
            <div class="col-md-6">
                <div class="card animated-card">
                    <div class="card-header">
                        <i class="bi bi-pie-chart me-2"></i>Attendance Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daily Trend Chart -->
            <div class="col-md-6">
                <div class="card animated-card">
                    <div class="card-header">
                        <i class="bi bi-graph-up me-2"></i>Daily Trend
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Student-wise Attendance -->
            <div class="col-md-6">
                <div class="card animated-card">
                    <div class="card-header">
                        <i class="bi bi-people me-2"></i>By Student
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="studentAttendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Subject-wise Attendance -->
            <div class="col-md-6">
                <div class="card animated-card">
                    <div class="card-header">
                        <i class="bi bi-book me-2"></i>By Subject
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="subjectAttendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
            
            // Update charts for dark mode
            updateChartsForDarkMode(document.body.classList.contains("dark-mode"));
            
            // Save preference
            localStorage.setItem('darkMode', document.body.classList.contains("dark-mode") ? 'enabled' : 'disabled');
        }
        
        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            document.getElementById('darkModeToggle').checked = true;
        }
        
        // Chart colors for light mode
        const lightModeColors = {
            present: '#4cc9f0',
            absent: '#f72585',
            background: 'white',
            text: '#666',
            grid: 'rgba(0, 0, 0, 0.1)',
            border: 'rgba(0, 0, 0, 0.1)'
        };
        
        // Chart colors for dark mode
        const darkModeColors = {
            present: '#4cc9f0',
            absent: '#f72585',
            background: '#1e1e1e',
            text: 'white',
            grid: 'rgba(255, 255, 255, 0.1)',
            border: 'rgba(255, 255, 255, 0.1)'
        };
        
        // Get current color scheme
        function getChartColors() {
            return document.body.classList.contains('dark-mode') ? darkModeColors : lightModeColors;
        }
        
        // Update charts when dark mode changes
        function updateChartsForDarkMode(isDarkMode) {
            const colors = isDarkMode ? darkModeColors : lightModeColors;
            
            // Update overall attendance chart
            attendanceChart.data.datasets[0].backgroundColor = [colors.present, colors.absent];
            attendanceChart.options.plugins.legend.labels.color = colors.text;
            attendanceChart.update();
            
            // Update student attendance chart
            studentAttendanceChart.data.datasets[0].backgroundColor = colors.present;
            studentAttendanceChart.data.datasets[1].backgroundColor = colors.absent;
            studentAttendanceChart.options.scales.x.grid.color = colors.grid;
            studentAttendanceChart.options.scales.x.ticks.color = colors.text;
            studentAttendanceChart.options.scales.y.grid.color = colors.grid;
            studentAttendanceChart.options.scales.y.ticks.color = colors.text;
            studentAttendanceChart.options.plugins.legend.labels.color = colors.text;
            studentAttendanceChart.update();
            
            // Update daily trend chart
            dailyTrendChart.data.datasets[0].borderColor = colors.present;
            dailyTrendChart.data.datasets[0].backgroundColor = hexToRgba(colors.present, 0.1);
            dailyTrendChart.options.scales.x.grid.color = colors.grid;
            dailyTrendChart.options.scales.x.ticks.color = colors.text;
            dailyTrendChart.options.scales.y.grid.color = colors.grid;
            dailyTrendChart.options.scales.y.ticks.color = colors.text;
            dailyTrendChart.update();
            
            // Update subject attendance chart
            subjectAttendanceChart.data.datasets[0].backgroundColor = [
                hexToRgba(colors.present, 0.7),
                hexToRgba(colors.absent, 0.7),
                '#f8961e',
                '#43aa8b',
                '#577590',
                '#9c6644'
            ];
            subjectAttendanceChart.options.scales.x.grid.color = colors.grid;
            subjectAttendanceChart.options.scales.x.ticks.color = colors.text;
            subjectAttendanceChart.options.scales.y.grid.color = colors.grid;
            subjectAttendanceChart.options.scales.y.ticks.color = colors.text;
            subjectAttendanceChart.update();
        }
        
        // Helper function to convert hex to rgba
        function hexToRgba(hex, alpha) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        
        // Overall Attendance Chart (Doughnut)
        const attendanceChart = new Chart(document.getElementById('attendanceChart'), {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?= $total_present; ?>, <?= ($total_records - $total_present); ?>],
                    backgroundColor: [getChartColors().present, getChartColors().absent],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            },
                            padding: 15
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Student-wise Attendance Chart (Bar)
        const studentAttendanceChart = new Chart(document.getElementById('studentAttendanceChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($student_names); ?>,
                datasets: [
                    {
                        label: 'Present',
                        data: <?= json_encode($student_present_counts); ?>,
                        backgroundColor: getChartColors().present,
                        borderWidth: 0,
                        borderRadius: 4
                    },
                    {
                        label: 'Absent',
                        data: <?= json_encode($student_absent_counts); ?>,
                        backgroundColor: getChartColors().absent,
                        borderWidth: 0,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false,
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            }
                        }
                    }
                }
            }
        });
        
        // Daily Trend Chart (Line)
        const dailyTrendChart = new Chart(document.getElementById('dailyTrendChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($daily_dates); ?>,
                datasets: [{
                    label: 'Attendance Percentage',
                    data: <?= json_encode($daily_percentages); ?>,
                    borderColor: getChartColors().present,
                    backgroundColor: hexToRgba(getChartColors().present, 0.1),
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: getChartColors().present,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            }
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            },
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Subject-wise Attendance Chart (Bar)
        const subjectAttendanceChart = new Chart(document.getElementById('subjectAttendanceChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($subject_names); ?>,
                datasets: [{
                    label: 'Attendance Percentage',
                    data: <?= json_encode($subject_percentages); ?>,
                    backgroundColor: [
                        hexToRgba(getChartColors().present, 0.7),
                        hexToRgba(getChartColors().absent, 0.7),
                        '#f8961e',
                        '#43aa8b',
                        '#577590',
                        '#9c6644'
                    ],
                    borderWidth: 0,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false,
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            }
                        }
                    },
                    y: {
                        min: 0,
                        max: 100,
                        grid: {
                            color: getChartColors().grid
                        },
                        ticks: {
                            color: getChartColors().text,
                            font: {
                                family: 'Poppins',
                                size: 10
                            },
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Initialize Select2 with mobile-friendly settings
        $(document).ready(function() {
            $('select').select2({
                width: '100%',
                placeholder: "Select...",
                allowClear: true,
                dropdownAutoWidth: true,
                dropdownParent: $('body') // Important for mobile
            });
        });
        
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                const width = bar.getAttribute('aria-valuenow');
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width + '%';
                }, 500);
            });
        });
    </script>
</body>
</html>
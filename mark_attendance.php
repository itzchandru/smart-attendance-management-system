<?php
session_start();
include 'db.php';

// Check if faculty is logged in
if (!isset($_SESSION['faculty_name']) || !isset($_SESSION['faculty_id'])) {
    // Redirect to login if faculty is not logged in
    header('Location: login.php');
    exit();
}

// Fetch subjects
$subjectQuery = "SELECT subject_code, subject_name FROM subjects"; 
$subjectResult = $conn->query($subjectQuery);

// Fetch students
$studentQuery = "SELECT roll_number, name FROM students";
$studentResult = $conn->query($studentQuery);

// Store students for later use
$students = [];
while ($row = $studentResult->fetch_assoc()) {
    $students[] = $row;
}

// Define time slots with continuous periods
$timeSlots = [
    '9:10 AM - 10:00 AM' => 'Period 1',
    '10:00 AM - 10:50 AM' => 'Period 2',
    '11:00 AM - 11:50 AM' => 'Period 3',
    '11:50 AM - 12:40 PM' => 'Period 4',
    '9:10 AM - 10:50 PM' => 'Morning Continuous Session (Periods 1-2)',
    '11:00 AM - 12:40 PM' => 'Morning Continuous Session (Periods 3-4)',
    '1:30 PM - 2:20 PM' => 'Period 5',
    '2:20 PM - 3:10 PM' => 'Period 6',
    '3:10 PM - 4:00 PM' => 'Period 7',
    '4:00 PM - 4:50 PM' => 'Period 8',
    '1:30 PM - 3:10 PM' => 'Afternoon Continuous Session (Periods 5-6)',
    '3:10 PM - 4:50 PM' => 'Afternoon Continuous Session (Periods 7-8)',
    '1:30 PM - 4:50 PM' => 'Afternoon Continuous Session (Periods 5-8)'
];

// Fetch faculty name from session
$faculty_name = $_SESSION['name']; // Faculty name set when the faculty logs in
$faculty_id = $_SESSION['faculty_id']; // Faculty ID set when the faculty logs in

// Handle form submission for marking attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $roll_number = $_POST['roll_number'];
    $status = $_POST['status']; // 'Present', 'Absent', or 'Leave'
    $time_slot = $_POST['time_slot'];
    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $year = $_POST['year'];

    // Insert attendance into the appropriate table based on the year
    $insertQuery = "
        INSERT INTO {$year}attendance (date, roll_number, student_name, time_slot, subject_code, subject_name, status, marked_by, faculty_id, year)
        VALUES ('$date', '$roll_number', '{$students[$roll_number]}', '$time_slot', '$subject_code', '$subject_name', '$status', '$faculty_name', '$faculty_id', '$year')
    ";

    if ($conn->query($insertQuery) === TRUE) {
        echo "Attendance marked successfully!";
    } else {
        echo "Error: " . $insertQuery . "<br>" . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Dynamic Background */
        body {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            padding: 20px;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Container */
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-width: 1000px;
            width: 100%;
            padding: 20px;
            margin: 20px auto;
        }

        /* Dark Mode */
        .dark-mode body {
            background: #121212;
            color: #ffffff;
        }

        .dark-mode .container {
            background: rgba(30, 30, 30, 0.95);
            color: #ffffff;
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }

        /* Table Styling */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table thead th {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        /* Status Toggle Buttons */
        .status-toggle {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .status-toggle .btn {
            flex: 1;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-status-present {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .btn-status-present:hover {
            background-color: rgba(40, 167, 69, 0.25);
        }

        .btn-status-present.active {
            background-color: #28a745;
            color: white;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        }

        .btn-status-absent {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .btn-status-absent:hover {
            background-color: rgba(220, 53, 69, 0.25);
        }

        .btn-status-absent.active {
            background-color: #dc3545;
            color: white;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
        }

        

        /* Mobile Specific Styles */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px auto;
            }

            .table-responsive {
                border: 0;
                width: 100%;
            }

            .table {
                width: 100%;
                display: block;
            }

            .table thead {
                display: none;
            }

            .table tbody {
                display: block;
                width: 100%;
            }

            .table tr {
                display: flex;
                flex-direction: column;
                margin-bottom: 15px;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 12px;
                background: rgba(255,255,255,0.95);
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }

            .dark-mode .table tr {
                background: rgba(30,30,30,0.95);
                border-color: #444;
            }

            .table td {
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
                padding: 10px 0;
                border: none;
                border-bottom: 1px solid #f0f0f0;
            }

            .dark-mode .table td {
                border-bottom-color: #444;
            }

            .table td:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 15px;
                color: #555;
            }

            .dark-mode .table td::before {
                color: #aaa;
            }

            .status-toggle {
                width: 100%;
                margin-top: 10px;
            }

            .status-toggle .btn {
                padding: 10px;
                font-size: 15px;
            }
        }

        /* Dark Mode Adjustments */
        .dark-mode .card-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
        }

        .dark-mode .table {
            background: #2d2d2d;
            color: #ffffff;
        }

        .dark-mode .table thead th {
            background: linear-gradient(to right, #343a40, #495057);
            color: #ffffff;
        }

        .dark-mode .form-control, 
        .dark-mode .form-select {
            background: #333;
            color: #fff;
            border: 1px solid #444;
        }

        .dark-mode .form-control:focus, 
        .dark-mode .form-select:focus {
            background: #444;
            color: #fff;
            border-color: #555;
        }

        /* Dark Mode Toggle */
        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            cursor: pointer;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Preview Modal Styles */
        .preview-header {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .dark-mode .preview-header {
            background-color: #343a40;
        }

        .preview-summary {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .dark-mode .preview-summary {
            background-color: #343a40;
        }

        .present {
            color: #28a745;
            font-weight: bold;
        }

        .absent {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container p-3 p-md-4">
        <!-- Dark Mode Toggle -->
        <div class="dark-mode-toggle" onclick="toggleDarkMode()">
            <i class="bi bi-moon-fill"></i>
        </div>

        <!-- Back Button -->
        <a href="faculty_dashboard.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back
        </a>

        <h2 class="text-center mb-4"><i class="bi bi-clipboard2-pulse-fill"></i> Attendance Management</h2>

        <?php if(isset($_SESSION['success'])) { ?>
            <div class="alert alert-success text-center"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php } ?>

        <form action="submit_attendance.php" method="POST" id="attendanceForm">
            <!-- Basic Info Row -->
            <div class="row">
                <!-- Select Date -->
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-calendar"></i> Date Selection
                        </div>
                        <div class="card-body">
                            <label for="date" class="form-label">Select Date:</label>
                            <input type="date" name="date" id="dateInput" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Time Slot Selection -->
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-clock"></i> Time Slot
                        </div>
                        <div class="card-body">
                            <label for="time_slot" class="form-label">Select Time Slot:</label>
                            <select name="time_slot" id="timeSlotSelect" class="form-select" required>
                                <option value="">-- Select Time Slot --</option>
                                <?php foreach ($timeSlots as $time => $label): ?>
                                    <option value="<?= $time ?>" 
                                            <?= strpos($label, 'Continuous') ? 'class="continuous-session"' : '' ?>>
                                        <?= $time ?> (<?= $label ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Select Subject -->
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <i class="bi bi-book"></i> Subject
                        </div>
                        <div class="card-body">
                            <label for="subject_code" class="form-label">Select Subject:</label>
                            <select name="subject_code" id="subjectSelect" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                <?php while ($row = $subjectResult->fetch_assoc()) { ?>
                                    <option value="<?= $row['subject_code']; ?>" data-subject-name="<?= $row['subject_name']; ?>">
                                        <?= $row['subject_code'] . " - " . $row['subject_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <input type="hidden" name="subject_name" id="subject_name">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Attendance Section -->
            <div class="card mb-3">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people-fill"></i> Student Attendance</span>
                        <div class="d-flex">
                            <button type="button" class="btn btn-sm btn-success me-2" onclick="markAllMobile('Present')">
                                <i class="bi bi-check-circle-fill"></i> Mark All Present
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="markAllMobile('Absent')">
                                <i class="bi bi-x-circle-fill"></i> Mark All Absent
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="searchStudent" class="form-control" placeholder="Search by name or roll number...">
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-center justify-content-end">
                            <span class="badge bg-primary">
                                <i class="bi bi-people-fill me-1"></i> Total Students: <?= count($students) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="py-3">
                                        <i class="bi bi-person-badge me-2"></i>
                                        Roll Number
                                    </th>
                                    <th class="py-3">
                                        <i class="bi bi-person-lines-fill me-2"></i>
                                        Student Name
                                    </th>
                                    <th class="py-3 text-end">
                                        <i class="bi bi-toggle2-on me-2"></i>
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody">
                                <?php foreach ($students as $student) { ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark p-2">
                                            <i class="bi bi-tag-fill text-primary me-2"></i>
                                            <?= htmlspecialchars($student['roll_number']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            
                                            <span class="fw-semibold"><?= htmlspecialchars($student['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <!-- Hidden select for form submission -->
                                        <select name="status[<?= $student['roll_number']; ?>]" class="status-select d-none">
                                            <option value="Present">Present</option>
                                            <option value="Absent">Absent</option>
                                        </select>
                                        
                                        <!-- Toggle buttons -->
                                        <div class="btn-group btn-group-sm status-toggle" role="group">
                                            <button type="button" 
                                                class="btn btn-status-present"
                                                onclick="setStatus(this, 'Present', '<?= $student['roll_number']; ?>')">
                                                <i class="bi bi-check-circle-fill"></i> Present
                                            </button>
                                            <button type="button" 
                                                class="btn btn-status-absent"
                                                onclick="setStatus(this, 'Absent', '<?= $student['roll_number']; ?>')">
                                                <i class="bi bi-x-circle-fill"></i> Absent
                                            </button>
                                        </div>
                                        
                                        <input type="hidden" name="student_roll[]" value="<?= $student['roll_number']; ?>">
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-info" onclick="showPreview()">
                    <i class="bi bi-eye-fill"></i> Preview Attendance
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                    <i class="bi bi-send-fill"></i> Submit Attendance
                </button>
            </div>

            <!-- Confirmation Modal -->
            <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">Confirm Submission</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to submit the attendance for <span id="confirmDate" class="fw-bold"></span>?</p>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle-fill"></i> Please review all entries before submitting.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Preview Modal -->
        <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="previewModalLabel">Attendance Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="preview-header">
                            <h4><i class="bi bi-clipboard2-data-fill me-2"></i> Attendance Details</h4>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <strong><i class="bi bi-calendar-date me-2"></i>Date:</strong>
                                    <span id="previewDate"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="bi bi-clock me-2"></i>Time Slot:</strong>
                                    <span id="previewTimeSlot"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="bi bi-book me-2"></i>Subject:</strong>
                                    <span id="previewSubject"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                    <!-- Preview content will be inserted here by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="preview-summary mt-4">
                            <h5><i class="bi bi-graph-up me-2"></i>Summary</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Total Students:</strong> <span id="totalStudents" class="badge bg-primary">0</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Present:</strong> <span id="presentCount" class="badge bg-success">0</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Absent:</strong> <span id="absentCount" class="badge bg-danger">0</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Percentage:</strong> <span id="attendancePercentage" class="badge bg-info">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                            <i class="bi bi-send-fill"></i> Proceed to Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-Time Clock -->
        <div class="text-center mt-4">
            <span id="liveClock" class="badge bg-dark">
                <i class="bi bi-clock-history me-2"></i>
                <span id="clockText"></span>
            </span>
        </div>
    </div>

    <script>
        // Initialize status buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('dateInput').value = today;
            
            // Initialize all status buttons
            document.querySelectorAll('.status-select').forEach(select => {
                const rollNumber = select.name.match(/\[(.*?)\]/)[1];
                setStatusButtons(rollNumber, select.value);
            });
            
            // Setup mobile responsive tables
            setupMobileTable();
            
            // Initialize clock
            updateClock();
            setInterval(updateClock, 1000);
            
            // Check for saved dark mode preference
            if (localStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
                const icon = document.querySelector(".dark-mode-toggle i");
                icon.classList.remove("bi-moon-fill");
                icon.classList.add("bi-sun-fill");
            }
        });

        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
            const icon = document.querySelector(".dark-mode-toggle i");
            icon.classList.toggle("bi-moon-fill");
            icon.classList.toggle("bi-sun-fill");
            
            // Save preference to localStorage
            localStorage.setItem("darkMode", document.body.classList.contains("dark-mode"));
        }

        // Real-Time Clock
        function updateClock() {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            };
            const dateTimeStr = now.toLocaleDateString('en-US', options);
            document.getElementById("clockText").textContent = dateTimeStr;
        }

        // Search Students
        document.getElementById("searchStudent").addEventListener("input", function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll("#studentTableBody tr").forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(searchTerm) ? "" : "none";
            });
        });

        // Subject Name Update
        document.getElementById("subjectSelect").addEventListener("change", function() {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById("subject_name").value = selectedOption.getAttribute("data-subject-name");
        });

        // Set status for a student
        function setStatus(button, status, rollNumber) {
            // Update the hidden select element
            document.querySelector(`select[name="status[${rollNumber}]"]`).value = status;
            
            // Update button states
            setStatusButtons(rollNumber, status);
            
            // Add visual feedback
            button.classList.add('active-click');
            setTimeout(() => {
                button.classList.remove('active-click');
            }, 200);
        }
        
        // Update the visual state of the toggle buttons
        function setStatusButtons(rollNumber, status) {
            const container = document.querySelector(`select[name="status[${rollNumber}]"]`).closest('td');
            
            // Remove active class from both buttons first
            container.querySelectorAll('.btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to the correct button
            if (status === 'Present') {
                container.querySelector('.btn-status-present').classList.add('active');
            } else {
                container.querySelector('.btn-status-absent').classList.add('active');
            }
        }
        
        // Mark all students
        function markAllMobile(status) {
            document.querySelectorAll('.status-select').forEach(select => {
                select.value = status;
                const rollNumber = select.name.match(/\[(.*?)\]/)[1];
                setStatusButtons(rollNumber, status);
            });
            
            // Visual feedback
            const btn = status === 'Present' ? 
                document.querySelector('.btn-success') : 
                document.querySelector('.btn-danger');
            btn.classList.add('active-click');
            setTimeout(() => {
                btn.classList.remove('active-click');
            }, 300);
        }

        // Setup mobile responsive tables
        function setupMobileTable() {
            if (window.innerWidth <= 768) {
                document.querySelectorAll('#studentTableBody td').forEach(td => {
                    const header = td.closest('tr').querySelector('th')?.textContent || '';
                    td.setAttribute('data-label', header);
                });
            }
        }

        // Handle window resize for responsive tables
        window.addEventListener('resize', setupMobileTable);

        // Show Attendance Preview
        function showPreview() {
            // Get form values
            const date = document.getElementById('dateInput').value;
            const timeSlot = document.getElementById('timeSlotSelect').value;
            const subjectSelect = document.getElementById('subjectSelect');
            const subject = subjectSelect.options[subjectSelect.selectedIndex].text;
            
            // Validate required fields
            if (!date || !timeSlot || subjectSelect.selectedIndex === 0) {
                alert('Please fill in all required fields before previewing.');
                return;
            }
            
            // Update confirmation modal date
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('confirmDate').textContent = formattedDate;
            
            // Update preview header
            document.getElementById('previewDate').textContent = formattedDate;
            document.getElementById('previewTimeSlot').textContent = timeSlot;
            document.getElementById('previewSubject').textContent = subject;
            
            // Generate preview table
            const previewBody = document.getElementById('previewTableBody');
            previewBody.innerHTML = '';
            
            let presentCount = 0;
            let absentCount = 0;
            const statusSelects = document.querySelectorAll('.status-select');
            
            statusSelects.forEach((select, index) => {
                const row = document.createElement('tr');
                const rollCell = document.createElement('td');
                const nameCell = document.createElement('td');
                const statusCell = document.createElement('td');
                
                // Get student details from the table
                const studentRow = select.closest('tr');
                const rollNumber = studentRow.querySelector('td:first-child').textContent.trim();
                const studentName = studentRow.querySelector('td:nth-child(2)').textContent.trim();
                const status = select.value;
                
                rollCell.textContent = rollNumber;
                nameCell.textContent = studentName;
                
                if (status === 'Present') {
                    statusCell.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Present</span>';
                    presentCount++;
                } else {
                    statusCell.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>Absent</span>';
                    absentCount++;
                }
                
                row.appendChild(rollCell);
                row.appendChild(nameCell);
                row.appendChild(statusCell);
                previewBody.appendChild(row);
            });
            
            // Update summary
            const totalStudents = presentCount + absentCount;
            const percentage = totalStudents > 0 ? Math.round((presentCount / totalStudents) * 100) : 0;
            
            document.getElementById('totalStudents').textContent = totalStudents;
            document.getElementById('presentCount').textContent = presentCount;
            document.getElementById('absentCount').textContent = absentCount;
            document.getElementById('attendancePercentage').textContent = percentage + '%';
            
            // Show the modal
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }
    </script>
</body>
</html>
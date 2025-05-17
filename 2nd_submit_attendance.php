<?php
session_start();
include 'db.php';

// 1. SECURITY CHECKS
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: 2nd_mark_attendance.php");
    exit();
}

// 2. VERIFY FACULTY IS LOGGED IN
if (!isset($_SESSION['faculty_id'])) {
    die("Error: You must be logged in as faculty to mark attendance.");
}

// 3. GET POST DATA
$date = $_POST['date'];
$time_slot = $_POST['time_slot'];
$subject_code = $_POST['subject_code'];
$subject_name = $_POST['subject_name'];
$status = $_POST['status']; // Array of roll numbers with attendance status
$year = 2; // For 2nd year students
$faculty_id = $_SESSION['faculty_id'];
$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty';

// 4. PROCESS EACH STUDENT
foreach ($status as $roll_number => $second_attendance_status) {
    try {
        // 4.1 Fetch student name
        $studentQuery = "SELECT name FROM second_students WHERE roll_number = ?";
        $stmt = $conn->prepare($studentQuery);
        $stmt->bind_param("s", $roll_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $student_name = $student['name'] ?? 'Unknown';

        // 4.2 Insert attendance record
        $insertQuery = "INSERT INTO second_attendance 
            (roll_number, student_name, subject_code, subject_name, 
             date, time_slot, status, year, faculty_id, marked_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param(
            "sssssssiis",
            $roll_number,
            $student_name,
            $subject_code,
            $subject_name,
            $date,
            $time_slot,
            $second_attendance_status,
            $year,
            $faculty_id,
            $faculty_name
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to record attendance for $roll_number: " . $conn->error);
        }
    } catch (Exception $e) {
        // Log error but continue with other students
        error_log($e->getMessage());
    }
}

// 5. REDIRECT ON SUCCESS
header("Location: 2nd_attendance_success.php");
exit();
?>
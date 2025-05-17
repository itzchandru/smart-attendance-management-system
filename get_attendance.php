<?php
$conn = new mysqli("sql103.infinityfree.com", "if0_38568116", "KpXSolJEtejI", "if0_38568116_new_attendance_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default year (you can change this dynamically too)
$year = isset($_GET['year']) ? intval($_GET['year']) : 3;
$date = date("Y-m-d");

// Get latest subject marked for today
$sql_subject = "SELECT subject_name, time_slot, marked_by 
                FROM attendance 
                WHERE date = '$date' AND year = $year 
                ORDER BY id DESC LIMIT 1";
$result_subject = $conn->query($sql_subject);
$subject = $result_subject->fetch_assoc();

$sql_present = "SELECT COUNT(*) as total FROM attendance 
                WHERE date = '$date' AND year = $year AND status = 'Present'";
$sql_absent = "SELECT COUNT(*) as total FROM attendance 
                WHERE date = '$date' AND year = $year AND status = 'Absent'";
$sql_leave = "SELECT COUNT(*) as total FROM attendance 
                WHERE date = '$date' AND year = $year AND status = 'Leave'";

$present = $conn->query($sql_present)->fetch_assoc()['total'];
$absent = $conn->query($sql_absent)->fetch_assoc()['total'];
$leave = $conn->query($sql_leave)->fetch_assoc()['total'];

echo json_encode([
    "subject_name" => $subject['subject_name'] ?? "--",
    "time_slot" => $subject['time_slot'] ?? "--",
    "marked_by" => $subject['marked_by'] ?? "--",
    "present" => $present,
    "absent" => $absent,
    "leave" => $leave,
    "date" => $date
]);
?>
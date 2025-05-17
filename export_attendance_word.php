<?php
session_start();
include "db.php";
require 'vendor/autoload.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\Language;

// Increase SQL join size limit
$conn->query("SET SQL_BIG_SELECTS = 1");

// Get Filters
$student = $_GET['student'] ?? '';  
$subject = $_GET['subject'] ?? '';  
$startDate = $_GET['start_date'] ?? ''; 
$endDate = $_GET['end_date'] ?? ''; 

// Get Student Name
$studentName = "Attendance_Report";
if (!empty($student)) {
    $stmtStudent = $conn->prepare("SELECT name FROM students WHERE roll_number = ?");
    $stmtStudent->bind_param("s", $student);
    $stmtStudent->execute();
    $resultStudent = $stmtStudent->get_result();
    if ($row = $resultStudent->fetch_assoc()) {
        $studentName = "Attendance_" . str_replace(" ", "_", $row['name']);
    }
}

// Set Headers
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header("Content-Disposition: attachment; filename=$studentName.docx");

// Create Word Document
$phpWord = new PhpWord();
$phpWord->getSettings()->setThemeFontLang(new Language(Language::EN_US));

// 🧾 Define Styles
$phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16, 'color' => '1E3A8A'], ['alignment' => 'center']);
$phpWord->addTitleStyle(2, ['bold' => true, 'size' => 12, 'color' => '0F766E'], ['alignment' => 'left']);
$phpWord->addParagraphStyle('centered', ['alignment' => 'center']);
$phpWord->addParagraphStyle('left', ['alignment' => 'left']);
$phpWord->addParagraphStyle('spaced', ['spaceAfter' => 200]);
$phpWord->addTableStyle('SubjectTable', [
    'borderSize' => 10, 'borderColor' => '000000', 'cellMargin' => 80
]);

$section = $phpWord->addSection([
    'marginTop' => 600,
    'marginBottom' => 600,
    'marginLeft' => 800,
    'marginRight' => 800,
]);

// ✍️ Header
$section->addTitle("Government College of Engineering, Thanjavur", 1);
$section->addText("📅 Report Date: " . date("d-M-Y"), ['italic' => true, 'size' => 10], 'left');
$section->addText(" ", [], 'spaced');

// 🔍 Report Title
$section->addTitle("📌 Student Attendance Report", 2);
$section->addTextBreak(1);

// SQL Query
$query = "SELECT students.name AS student_name, students.roll_number, students.department, students.year, 
          attendance.date, attendance.subject_code, attendance.status, subjects.subject_name
          FROM attendance 
          JOIN students ON attendance.roll_number = students.roll_number
          JOIN subjects ON attendance.subject_code = subjects.subject_code
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($student)) {
    $query .= " AND attendance.roll_number = ?";
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

// Add a LIMIT to restrict the data size and optimize query execution
$query .= " ORDER BY attendance.date DESC LIMIT 100"; // Fetch a maximum of 100 rows

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// 🔍 If No Data
if ($result->num_rows == 0) {
    $section->addText("⚠️ No attendance records found for the given filters.", ['bold' => true, 'color' => 'FF0000']);
} else {
    $attendanceData = [];

    while ($row = $result->fetch_assoc()) {
        $roll = $row['roll_number'];
        $studentName = $row['student_name'];
        $department = $row['department'];
        $year = $row['year'];
        $subjectCode = $row['subject_code'];
        $subjectName = $row['subject_name'];
        $status = $row['status'];

        if (!isset($attendanceData[$roll])) {
            $attendanceData[$roll] = [
                'name' => $studentName,
                'department' => $department,
                'year' => $year,
                'subjects' => []
            ];
        }

        if (!isset($attendanceData[$roll]['subjects'][$subjectCode])) {
            $attendanceData[$roll]['subjects'][$subjectCode] = [
                'subject_name' => $subjectName,
                'present' => 0,
                'absent' => 0,
                'total' => 0
            ];
        }

        $attendanceData[$roll]['subjects'][$subjectCode]['total']++;
        if ($status === 'Present') {
            $attendanceData[$roll]['subjects'][$subjectCode]['present']++;
        } else {
            $attendanceData[$roll]['subjects'][$subjectCode]['absent']++;
        }
    }

    foreach ($attendanceData as $rollNumber => $info) {
        // 🧾 Student Info
        $section->addText("👤 Student Details", ['bold' => true, 'size' => 11, 'color' => '1F2937']);
        $section->addText("Roll Number: {$rollNumber}", ['size' => 10]);
        $section->addText("Name: {$info['name']}", ['size' => 10]);
        $section->addText("Department: {$info['department']}", ['size' => 10]);
        $section->addText("Year: {$info['year']}", ['size' => 10]);
        $section->addTextBreak(1);

        // 🗂 Subject Table
        $table = $section->addTable('SubjectTable');
        $headers = ["Subject Code", "Subject Name", "Total", "Present", "Absent", "Attendance %"];
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(2000, ['bgColor' => 'BFDBFE'])->addText($header, ['bold' => true, 'size' => 9]);
        }

        foreach ($info['subjects'] as $subjectCode => $data) {
            $percentage = ($data['total'] > 0) ? round(($data['present'] / $data['total']) * 100, 2) : 0;
            $bgColor = ($percentage >= 75) ? 'D1FAE5' : 'FECACA';

            $table->addRow();
            $table->addCell(2000)->addText($subjectCode, ['size' => 9]);
            $table->addCell(3000)->addText($data['subject_name'], ['size' => 9]);
            $table->addCell(1000)->addText($data['total'], ['size' => 9]);
            $table->addCell(1000)->addText($data['present'], ['size' => 9]);
            $table->addCell(1000)->addText($data['absent'], ['size' => 9]);
            $table->addCell(1500, ['bgColor' => $bgColor])->addText("$percentage%", ['bold' => true, 'size' => 9]);
        }

        $section->addTextBreak(2);
    }
}

// Output
$writer = IOFactory::createWriter($phpWord, 'Word2007');
$tempFile = tempnam(sys_get_temp_dir(), 'attendance_') . '.docx';
$writer->save($tempFile);
readfile($tempFile);
unlink($tempFile);
exit();

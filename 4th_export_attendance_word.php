<?php
session_start();
include "db.php";
require 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\Language;

// Get Filters
$student = $_GET['student'] ?? '';  
$subject = $_GET['subject'] ?? '';  
$startDate = $_GET['start_date'] ?? ''; 
$endDate = $_GET['end_date'] ?? ''; 

// Get Student Name
$studentName = "Attendance_Report";
if (!empty($student)) {
    $stmtStudent = $conn->prepare("SELECT student_name FROM fourth_attendance WHERE roll_number = ? LIMIT 1");
    $stmtStudent->bind_param("s", $student);
    $stmtStudent->execute();
    $resultStudent = $stmtStudent->get_result();
    if ($row = $resultStudent->fetch_assoc()) {
        $studentName = "Attendance_" . str_replace(" ", "_", $row['student_name']);
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
$query = "SELECT 
            roll_number,
            student_name,
            date,
            subject_code,
            subject_name,
            status,
            year
          FROM fourth_attendance
          WHERE 4=4";

$params = [];
$types = "";

if (!empty($student)) {
    $query .= " AND roll_number = ?";
    $params[] = $student;
    $types .= "s";
}
if (!empty($subject)) {
    $query .= " AND subject_code = ?";
    $params[] = $subject;
    $types .= "s";
}
if (!empty($startDate) && !empty($endDate)) {
    $query .= " AND date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$query .= " ORDER BY date DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();

// 🔍 If No Data
if ($result->num_rows == 0) {
    $section->addText("⚠️ No attendance records found for the given filters.", ['bold' => true, 'color' => 'FF0000']);
} else {
    $attendanceData = [];

    while ($row = $result->fetch_assoc()) {
        $roll = $row['roll_number'];
        $studentName = $row['student_name'];
        $year = $row['year'];
        $subjectCode = $row['subject_code'];
        $subjectName = $row['subject_name'];
        $status = $row['status'];

        if (!isset($attendanceData[$roll])) {
            $attendanceData[$roll] = [
                'name' => $studentName,
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
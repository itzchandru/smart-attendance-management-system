<?php
include 'db.php';

// Get the year from URL parameter
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
if ($selected_year < 1 || $selected_year > 4) {
    header("Location: select_year_add.php");
    exit();
}

$year_names = [
    1 => "1st Year",
    2 => "2nd Year",
    3 => "3rd Year",
    4 => "4th Year"
];

// Handle form submission
$success_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $names = $_POST['name'];
    $roll_numbers = $_POST['roll_number'];
    $departments = $_POST['department'];
    $years = $_POST['year'];

    $table_map = [
        1 => "first_students",
        2 => "second_students",
        3 => "third_students",
        4 => "fourth_students"
    ];
    $target_table = $table_map[$selected_year];

    $stmt = $conn->prepare("INSERT INTO $target_table (name, roll_number, department, year) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $roll_number, $department, $year);

    $success_count = 0;
    for ($i = 0; $i < count($names); $i++) {
        $name = $names[$i];
        $roll_number = $roll_numbers[$i];
        $department = $departments[$i];
        $year = intval($years[$i]);

        if (!empty($name) && !empty($roll_number) && !empty($department)) {
            if ($stmt->execute()) {
                $success_count++;
            }
        }
    }

    $stmt->close();
    $conn->close();

    $success_message = "$success_count student(s) added successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Add Students</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .year-header {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .year-1 { background-color: #0d6efd; color: white; }
        .year-2 { background-color: #198754; color: white; }
        .year-3 { background-color: #ffc107; color: black; }
        .year-4 { background-color: #dc3545; color: white; }
        .remove-btn {
            float: right;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4 p-4 bg-white shadow rounded">

    <div class="year-header year-<?php echo $selected_year; ?>">
        <h3 class="text-center">📝 Add Students (<?php echo $year_names[$selected_year]; ?>)</h3>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ <strong><?php echo $success_message; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div id="students-container">
            <div class="student-entry border p-3 mb-3 bg-light rounded">
                <h5>Student 1 <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeStudent(this)">Remove</button></h5>
                <div class="mb-2">
                    <label class="form-label">👤 Name:</label>
                    <input type="text" name="name[]" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">📛 Roll Number:</label>
                    <input type="text" name="roll_number[]" class="form-control roll-input" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">🏫 Department:</label>
                    <input type="text" name="department[]" value="ECE" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">📅 Year:</label>
                    <select name="year[]" class="form-control" required>
                        <option value="1" <?php echo $selected_year == 1 ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo $selected_year == 2 ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo $selected_year == 3 ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo $selected_year == 4 ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-primary mb-3" onclick="addStudent()">➕ Add Another Student</button>
        <button type="submit" class="btn btn-success w-100">✅ Submit All Students</button>
    </form>

    <a href="select_year_add.php" class="btn btn-secondary mt-3 w-100">🔙 Back to Year Selection</a>
</div>

<script>
let studentCount = 1;

function removeStudent(button) {
    const entry = button.closest('.student-entry');
    entry.remove();
}

function addStudent() {
    studentCount++;
    const container = document.getElementById('students-container');
    const firstEntry = container.querySelector('.student-entry');
    const lastRollInput = container.querySelectorAll('.roll-input');
    let newRoll = "";

    if (lastRollInput.length > 0) {
        const lastValue = lastRollInput[lastRollInput.length - 1].value.trim();
        const match = lastValue.match(/^(\d+)([A-Za-z]+)(\d+)$/);
        if (match) {
            const prefix = match[1];
            const dept = match[2];
            const num = String(parseInt(match[3], 10) + 1).padStart(match[3].length, '0');
            newRoll = `${prefix}${dept}${num}`;
        }
    }

    const newStudent = document.createElement('div');
    newStudent.className = 'student-entry border p-3 mb-3 bg-light rounded';
    newStudent.innerHTML = `
        <h5>Student ${studentCount} <button type="button" class="btn btn-danger btn-sm remove-btn" onclick="removeStudent(this)">Remove</button></h5>
        <div class="mb-2">
            <label class="form-label">👤 Name:</label>
            <input type="text" name="name[]" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">📛 Roll Number:</label>
            <input type="text" name="roll_number[]" value="${newRoll}" class="form-control roll-input" required>
        </div>
        <div class="mb-2">
            <label class="form-label">🏫 Department:</label>
            <input type="text" name="department[]" value="ECE" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">📅 Year:</label>
            <select name="year[]" class="form-control" required>
                <option value="1" ${<?php echo $selected_year; ?> === 1 ? "selected" : ""}>1st Year</option>
                <option value="2" ${<?php echo $selected_year; ?> === 2 ? "selected" : ""}>2nd Year</option>
                <option value="3" ${<?php echo $selected_year; ?> === 3 ? "selected" : ""}>3rd Year</option>
                <option value="4" ${<?php echo $selected_year; ?> === 4 ? "selected" : ""}>4th Year</option>
            </select>
        </div>
    `;
    container.appendChild(newStudent);
}
</script>
</body>
</html>

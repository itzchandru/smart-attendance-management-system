<?php
include 'db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
if ($selected_year < 1 || $selected_year > 4) {
    header("Location: select_subject_year.php");
    exit();
}

$year_names = [
    1 => "First Year",
    2 => "Second Year",
    3 => "Third Year",
    4 => "Fourth Year"
];

$subject_tables = [
    1 => "first_subjects",
    2 => "second_subjects",
    3 => "third_subjects",
    4 => "fourth_subjects"
];

$faculty = $conn->query("SELECT id, name FROM faculty ORDER BY name");

$success = $error = "";
$added_subjects = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_names = $_POST['subject_name'] ?? [];
    $subject_codes = $_POST['subject_code'] ?? [];
    $faculty_ids = $_POST['faculty_id'] ?? [];
    $year = intval($_POST['year']);

    if (count($subject_names) === 0 || count($subject_codes) === 0) {
        $error = "⚠️ Please fill at least one subject.";
    } else {
        $table = $subject_tables[$year];
        $stmt = $conn->prepare("INSERT INTO $table (subject_name, subject_code, year, faculty_id) VALUES (?, ?, ?, ?)");

        for ($i = 0; $i < count($subject_names); $i++) {
            $name = trim($subject_names[$i]);
            $code = strtoupper(trim($subject_codes[$i]));
            $faculty_id = $faculty_ids[$i] !== "" ? intval($faculty_ids[$i]) : null;

            if ($name !== "" && $code !== "") {
                $stmt->bind_param("ssii", $name, $code, $year, $faculty_id);
                if ($stmt->execute()) {
                    $added_subjects[] = $name;
                }
            }
        }

        $stmt->close();

        if (count($added_subjects) > 0) {
            $success = "✅ Successfully added subjects: " . implode(", ", $added_subjects);
        } else {
            $error = "❌ No valid subjects were added.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Subjects - <?php echo $year_names[$selected_year]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
 <style>
    :root {
        --year-1-color: #0d6efd;
        --year-2-color: #198754;
        --year-3-color: #ffc107;
        --year-4-color: #dc3545;
        --year-hover-1: #0b5ed7;
        --year-hover-2: #157347;
        --year-hover-3: #ffca2c;
        --year-hover-4: #bb2d3b;
    }
    
    body {
        background-color: #f8f9fa;
    }
    
    .card-container {
        max-width: 900px;
        margin: 2rem auto;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        border-radius: 0.75rem;
        overflow: hidden;
    }
    
    .year-header {
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        border-radius: 0.5rem;
        background-color: var(--year-<?php echo $selected_year; ?>-color);
        color: white;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .year-header:hover {
        background-color: var(--year-hover-<?php echo $selected_year; ?>);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .year-1 { background-color: var(--year-1-color); }
    .year-2 { background-color: var(--year-2-color); }
    .year-3 { background-color: var(--year-3-color); color: #212529; }
    .year-4 { background-color: var(--year-4-color); }
    
    .subject-group {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        border-left: 4px solid var(--year-<?php echo $selected_year; ?>-color);
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .subject-group:hover {
        background-color: #e9ecef;
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-left-width: 6px;
    }
    
    .form-control, .form-select {
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        transition: all 0.2s ease;
        border: 1px solid #ced4da;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--year-<?php echo $selected_year; ?>-color);
        box-shadow: 0 0 0 0.25rem rgba(var(--year-<?php echo $selected_year; ?>-rgb), 0.25);
    }
    
    .btn-add {
        background-color: var(--year-<?php echo $selected_year; ?>-color);
        border-color: var(--year-<?php echo $selected_year; ?>-color);
        margin-bottom: 1.5rem;
        transition: all 0.2s ease;
    }
    
    .btn-add:hover {
        background-color: var(--year-hover-<?php echo $selected_year; ?>);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-remove {
        border-radius: 0.375rem;
        width: 100%;
        transition: all 0.2s ease;
    }
    
    .btn-remove:hover {
        background-color: #dc3545;
        color: white;
        transform: scale(1.05);
    }
    
    .btn-submit {
        padding: 0.5rem 1.5rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .btn-submit:hover {
        background-color: var(--year-hover-<?php echo $selected_year; ?>);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-back {
        background-color: #6c757d;
        border-color: #6c757d;
        transition: all 0.2s ease;
    }
    
    .btn-back:hover {
        background-color: #5c636a;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .alert {
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .alert:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .input-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
        transition: all 0.2s ease;
    }
    
    .subject-group:hover .input-label {
        color: var(--year-<?php echo $selected_year; ?>-color);
    }
    
    @media (max-width: 768px) {
        .subject-group .col-md-1 {
            margin-top: 0.5rem;
        }
        
        .subject-group:hover {
            transform: none;
        }
    }
</style>
</head>
<body>
<div class="container py-4">
    <div class="card-container bg-white">
        <div class="p-4">
            <div class="year-header year-<?php echo $selected_year; ?>">
                <h2 class="mb-0"><i class="bi bi-journal-plus"></i> Add Subjects</h2>
                <p class="mb-0 fw-light"><?php echo $year_names[$selected_year]; ?> Courses</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" id="subjectForm">
                <input type="hidden" name="year" value="<?php echo $selected_year; ?>">

                <div id="subjectFields">
                    <div class="subject-group">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="input-label">Subject Name</label>
                                <input type="text" name="subject_name[]" class="form-control" placeholder="e.g. Advanced Mathematics" required>
                            </div>
                            <div class="col-md-3">
                                <label class="input-label">Subject Code</label>
                                <input type="text" name="subject_code[]" class="form-control text-uppercase" placeholder="e.g. MATH101" required>
                            </div>
                            <div class="col-md-4">
                                <label class="input-label">Faculty (Optional)</label>
                                <select name="faculty_id[]" class="form-select">
                                    <option value="">-- Select Faculty --</option>
                                    <?php $faculty->data_seek(0); while ($row = $faculty->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger btn-remove remove-btn">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-4">
                    <button type="button" class="btn btn-add text-white" id="addMoreBtn">
                        <i class="bi bi-plus-circle"></i> Add Another Subject
                    </button>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="select_subject_year.php" class="btn btn-back text-white me-md-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-submit text-white" style="background-color: var(--year-<?php echo $selected_year; ?>-color);">
                        <i class="bi bi-save"></i> Save Subjects
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById("addMoreBtn").addEventListener("click", function () {
    const container = document.getElementById("subjectFields");
    const group = document.querySelector(".subject-group");
    const clone = group.cloneNode(true);

    // Clear all input values in the cloned group
    clone.querySelectorAll("input").forEach(input => input.value = "");
    clone.querySelector("select").selectedIndex = 0;
    
    // Add animation class
    clone.style.opacity = "0";
    container.appendChild(clone);
    
    // Animate the new element
    setTimeout(() => {
        clone.style.opacity = "1";
        clone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 10);
});

document.addEventListener("click", function (e) {
    if (e.target.classList.contains("remove-btn") || e.target.closest(".remove-btn")) {
        const btn = e.target.classList.contains("remove-btn") ? e.target : e.target.closest(".remove-btn");
        const group = btn.closest(".subject-group");
        
        if (document.querySelectorAll(".subject-group").length > 1) {
            // Add fade out animation before removing
            group.style.opacity = "0";
            group.style.transform = "translateX(-20px)";
            setTimeout(() => group.remove(), 200);
        } else {
            // Show toast notification instead of alert
            const toast = document.createElement("div");
            toast.className = "position-fixed bottom-0 end-0 p-3";
            toast.style.zIndex = "11";
            toast.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-danger text-white">
                        <strong class="me-auto">Notice</strong>
                        <button type="button" class="btn-close btn-close-black" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        At least one subject entry is required.
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    }
});

// Auto-focus the first input field when adding new subject
document.addEventListener('DOMContentLoaded', function() {
    const firstInput = document.querySelector('input[name="subject_name[]"]');
    if (firstInput) firstInput.focus();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
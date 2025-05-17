<?php
session_start();
include "db.php"; // Database connection

// Fetch unique roll numbers, names, and subjects
$studentsQuery = "SELECT DISTINCT roll_number, name FROM fourth_students ORDER BY roll_number ASC";
$studentsResult = $conn->query($studentsQuery);

$subjectsQuery = "SELECT DISTINCT subject_code, subject_name FROM fourth_attendance ORDER BY subject_name ASC";
$subjectsResult = $conn->query($subjectsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Export Attendance Report</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
  <style>
    :root {
      --primary-color: #4361ee;
      --dark-color: #212529;
    }

    body {
      background-color: #f5f7ff;
      font-family: 'Inter', sans-serif;
      padding: 20px;
    }

    .container-custom {
      max-width: 650px;
      margin: 0 auto;
      padding: 1.5rem;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .choices__inner,
    .form-control,
    .form-select {
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      font-size: 0.9rem;
    }

    .form-label {
      font-weight: 600;
      font-size: 0.8rem;
    }

    .btn-primary {
      padding: 0.6rem;
      font-size: 0.85rem;
      border-radius: 8px;
    }

    .filter-card {
      padding: 1.25rem;
      margin-bottom: 1.25rem;
      border-radius: 10px;
      background: white;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .section-title {
      font-size: 0.95rem;
      font-weight: 600;
    }

    .filter-icon {
      width: 32px;
      height: 32px;
      font-size: 0.9rem;
      background-color: rgba(67, 97, 238, 0.1);
      color: var(--primary-color);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      margin-right: 0.75rem;
    }

    .flatpickr-calendar {
      z-index: 10001 !important;
    }
  </style>
</head>
<body>
<div class="container-custom">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-chevron-left me-1"></i> Back
    </a>
    <span class="badge bg-primary">Admin</span>
  </div>

  <div class="text-center mb-4">
    <i class="fas fa-file-export text-primary mb-2" style="font-size: 1.8rem;"></i>
    <h4 class="fw-bold mb-2">Export Attendance</h4>
    <p class="text-muted small">Select filters to generate report</p>
  </div>

  <form action="4th_export_attendance_word.php" method="GET">
    <!-- Student Filter -->
    <div class="filter-card">
      <div class="d-flex align-items-center mb-2">
        <div class="filter-icon"><i class="fas fa-user-graduate"></i></div>
        <h5 class="section-title mb-0">Student</h5>
      </div>
      <div class="mb-2">
        <label class="form-label">Select Student</label>
        <select name="student" class="form-select" data-trigger>
          <option value="">All Students</option>
          <?php while ($row = $studentsResult->fetch_assoc()) : ?>
            <option value="<?= htmlspecialchars($row['roll_number']); ?>"><?= htmlspecialchars($row['roll_number'] . ' - ' . $row['name']); ?></option>
          <?php endwhile; ?>
        </select>
        <div class="form-text small">Leave blank for all students</div>
      </div>
    </div>

    <!-- Subject Filter -->
    <div class="filter-card">
      <div class="d-flex align-items-center mb-2">
        <div class="filter-icon"><i class="fas fa-book-open"></i></div>
        <h5 class="section-title mb-0">Subject</h5>
      </div>
      <div class="mb-2">
        <label class="form-label">Select Subject</label>
        <select name="subject" class="form-select" data-trigger>
          <option value="">All Subjects</option>
          <?php
$seenSubjects = [];
while ($row = $subjectsResult->fetch_assoc()) :
  $subjectName = $row['subject_name'];
  if (!in_array($subjectName, $seenSubjects)) :
    $seenSubjects[] = $subjectName;
    $code = !empty($row['subject_code']) ? $row['subject_code'] : $subjectName;
?>
    <option value="<?= htmlspecialchars($code); ?>"><?= htmlspecialchars($subjectName); ?></option>
<?php
  endif;
endwhile;
?>

        </select>
        <div class="form-text small">Leave blank for all subjects</div>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filter-card">
      <div class="d-flex align-items-center mb-2">
        <div class="filter-icon"><i class="fas fa-calendar-alt"></i></div>
        <h5 class="section-title mb-0">Date Range</h5>
      </div>
      <div class="mb-2">
        <label class="form-label">Select Date Range</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
          <input type="text" name="date_range" id="dateRange" class="form-control" placeholder="Select dates">
        </div>
        <div class="form-text small">Leave blank for all dates</div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-grid gap-2 mt-3">
      <button type="submit" class="btn btn-primary" id="exportButton">
        <i class="fas fa-file-export me-1"></i> Export Report
      </button>
      <div class="d-flex gap-2">
        <button type="reset" class="btn btn-outline-secondary flex-grow-1 btn-sm">
          <i class="fas fa-undo me-1"></i> Reset
        </button>
        <button type="button" class="btn btn-outline-primary flex-grow-1 btn-sm" onclick="previewData()">
          <i class="fas fa-eye me-1"></i> Preview
        </button>
      </div>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
 // Date picker
  flatpickr("#dateRange", {
    mode: "range",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "M j, Y",
    static: true
  });

  // Dropdown enhancement
  document.querySelectorAll('[data-trigger]').forEach(select => {
    new Choices(select, {
      searchEnabled: true,
      itemSelectText: '',
      shouldSort: false
    });
  });

  function previewData() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData).toString();

    const previewBtn = document.querySelector('[onclick="previewData()"]');
    const originalText = previewBtn.innerHTML;

    previewBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Loading...';
    previewBtn.disabled = true;

    // Open in same window
    window.location.href = `4th_preview_attendance.php?${params}`;
  }
</script>
</body>
</html>

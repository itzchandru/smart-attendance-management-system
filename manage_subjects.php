<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch subjects for each year with their table name
function fetchSubjectsByYear($conn, $table, $year) {
    $stmt = $conn->prepare("SELECT id, subject_name, subject_code FROM `$table`");
    $stmt->execute();
    return [$stmt->get_result(), $year, $table];
}

$all_subjects = [
    fetchSubjectsByYear($conn, 'first_subjects', 'First Year'),
    fetchSubjectsByYear($conn, 'second_subjects', 'Second Year'),
    fetchSubjectsByYear($conn, 'subjects', 'Third Year'),
    fetchSubjectsByYear($conn, 'fourth_subjects', 'Fourth Year')
];

// Handle delete
if (isset($_GET['delete_id'], $_GET['year_table'])) {
    $delete_id = intval($_GET['delete_id']);
    $table = trim($_GET['year_table']);

    // Allow only specific table names
    $allowed_tables = ['first_subjects', 'second_subjects', 'subjects', 'fourth_subjects'];
    if (!in_array($table, $allowed_tables)) {
        die("Invalid table name.");
    }

    $query = "DELETE FROM `$table` WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $delete_id);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    header("Location: manage_subjects.php?msg=Subject+deleted+successfully");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-blue: #1a56db;
            --dark-blue: #1e429f;
            --light-blue: #ebf5ff;
            --danger-red: #e02424;
            --light-red: #fdf2f2;
            --text-dark: #111827;
            --text-medium: #374151;
            --text-light: #6b7280;
            --border-color: black;
            --bg-gray: #f9fafb;
        }
        
        body {
            background-color: var(--bg-gray);
            color: var(--text-dark);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.5;
            text-decoration:none;
        }
        
        .app-header {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .page-title {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .year-section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .year-header {
            padding: 1rem 1.25rem;
            background: white;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .year-title {
            font-weight: 600;
            color: var(--primary-blue);
            margin: 0;
            font-size: 1.1rem;
        }
        
        .subject-count {
            background: var(--light-blue);
            color: var(--primary-blue);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            padding: 1.25rem;
        }
        
        @media (max-width: 640px) {
            .subjects-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .subject-card {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.25rem;
            transition: all 0.15s ease;
            background: white;
        }
        
        .subject-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 1px 3px rgba(26, 86, 219, 0.1);
        }
        
        .subject-name {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
            font-size: 1.15rem;
        }
        
        .subject-code {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-add {
            background: var(--primary-blue);
            color: white;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }
        
        .btn-add:hover {
            background: var(--dark-blue);
            color: white;
        }
        
        .btn-delete {
            background: var(--light-red);
            color: var(--danger-red);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-delete:hover {
            background: var(--danger-red);
            color: white;
        }
        
        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 2.5rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }
        
        .empty-text {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }
    </style>
</head>
<body>
    <header class="app-header py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Manage Subjects</h1>
                    <p class="page-subtitle">View and manage all subjects by academic year</p>
                </div>
                <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="action-bar">
            <div>
                <h2 class="h5 mb-1">All Subjects</h2>
                <p class="text-muted mb-0">Organized by academic year</p>
            </div>
            <a href="select_subject_year.php" class="btn-add">
                <i class="bi bi-plus-lg"></i> Add Subject
            </a>
        </div>
        
        <?php foreach ($all_subjects as [$subjects_result, $year_label, $table_name]): ?>
            <section class="year-section">
                <div class="year-header">
                    <h3 class="year-title">
                        <i class="bi bi-calendar3 me-2"></i><?= $year_label ?>
                    </h3>
                    <span class="subject-count">
                        <?= $subjects_result->num_rows ?> <?= $subjects_result->num_rows === 1 ? 'Subject' : 'Subjects' ?>
                    </span>
                </div>
                
                <?php if ($subjects_result->num_rows > 0): ?>
                    <div class="subjects-grid">
                        <?php while ($row = $subjects_result->fetch_assoc()): ?>
                            <article class="subject-card">
                                <h3 class="subject-name"><?= htmlspecialchars($row['subject_name']) ?></h3>
                                <p class="subject-code">
                                    <i class="bi bi-upc-scan"></i>
                                    <?= htmlspecialchars($row['subject_code']) ?>
                                </p>
                                <div class="d-flex justify-content-end">
                                    <button onclick="confirmDelete('<?= $row['id'] ?>', '<?= $table_name ?>')" 
                                            class="btn-delete">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-journal-text"></i>
                        </div>
                        <p class="empty-text">No subjects found for <?= $year_label ?></p>
                        <a href="select_subject_year.php" class="btn-add">
                            <i class="bi bi-plus-lg"></i> Add Subject
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, table) {
            if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                window.location.href = `manage_subjects.php?delete_id=${id}&year_table=${table}`;
            }
        }
        
        // Enhance mobile touch targets
        document.querySelectorAll('.subject-card').forEach(card => {
            card.style.cursor = 'pointer';
            card.addEventListener('click', (e) => {
                // Don't trigger if clicking on delete button
                if (!e.target.closest('.btn-delete')) {
                    // Add your subject view/edit functionality here
                    console.log('Subject clicked');
                }
            });
        });
    </script>
</body>
</html>
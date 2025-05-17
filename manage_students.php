<?php
session_start();
include 'db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle delete
if (isset($_GET['delete_id']) && isset($_GET['year'])) {
    $delete_id = intval($_GET['delete_id']);
    $year = intval($_GET['year']);

    // Set the table based on year
    $table = '';
    switch ($year) {
        case 1: $table = 'first_students'; break;
        case 2: $table = 'second_students'; break;
        case 3: $table = 'students'; break;
        case 4: $table = 'fourth_students'; break;
        default:
            header("Location: manage_students.php?error=Invalid+Year");
            exit();
    }

    // Delete student query
    $delete_query = "DELETE FROM $table WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_students.php?success=Student+Deleted+Successfully");
    exit();
}

// Fetch students from each year-specific table
function fetchStudents($conn, $table) {
    $query = "SELECT id, name, roll_number FROM $table ORDER BY name";
    $result = $conn->query($query);
    return $result;
}

$year_results = [
    1 => fetchStudents($conn, 'first_students'),
    2 => fetchStudents($conn, 'second_students'),
    3 => fetchStudents($conn, 'students'),
    4 => fetchStudents($conn, 'fourth_students')
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6366f1;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-medium: #475569;
            --text-light: #64748b;
            --danger-color: #dc2626;
            --success-color: #16a34a;
            --border-color: #e2e8f0;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-dark);
        }
        
        .app-header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .year-section {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .year-header {
            padding: 1rem 1.5rem;
            background: white;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .year-title {
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            font-size: 1.1rem;
        }
        
        .student-count {
            background: var(--light-bg);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            padding: 1.25rem;
        }
        
        @media (max-width: 640px) {
            .students-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .student-card {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 1.25rem;
            transition: all 0.15s ease;
            background: var(--card-bg);
        }
        
        .student-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
        }
        
        .student-avatar {
            width: 48px;
            height: 48px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 1rem;
        }
        
        .student-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
            text-align: center;
        }
        
        .student-detail {
            color: var(--text-medium);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-delete {
            background: white;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-add {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }
        
        .btn-add:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .empty-state {
            padding: 2rem 1rem;
            text-align: center;
            background: white;
            border-radius: 0.5rem;
        }
        
        .empty-icon {
            font-size: 2.5rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
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
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
    </style>
</head>
<body>
    <header class="app-header py-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-0 fw-bold">Student Management</h1>
                    <p class="text-muted small mb-0">Manage all student records</p>
                </div>
                <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= htmlspecialchars($_GET['success']) ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($_GET['error']) ?></div>
            </div>
        <?php endif; ?>
        
        <div class="action-bar">
            <div>
                <h2 class="h5 mb-1">All Students</h2>
                <p class="text-muted mb-0">Organized by academic year</p>
            </div>
            <a href="select_year_add.php" class="btn btn-add">
                <i class="bi bi-plus-lg me-1"></i> Add Student
            </a>
        </div>
        
        <?php foreach ($year_results as $year => $result): ?>
            <section class="year-section">
                <div class="year-header">
                    <h3 class="year-title">
                        <i class="bi bi-mortarboard me-2"></i>
                        <?= match($year) {
                            1 => 'First Year',
                            2 => 'Second Year', 
                            3 => 'Third Year',
                            4 => 'Fourth Year'
                        } ?> Students
                    </h3>
                    <span class="student-count">
                        <?= $result->num_rows ?> student<?= $result->num_rows != 1 ? 's' : '' ?>
                    </span>
                </div>
                
                <div class="students-grid">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="student-card">
                                <div class="student-avatar">
                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                </div>
                                <h4 class="student-name"><?= htmlspecialchars($row['name']) ?></h4>
                                <p class="student-detail">
                                    <i class="bi bi-person-badge"></i>
                                    <?= htmlspecialchars($row['roll_number']) ?>
                                </p>
                                <?php if (!empty($row['email'])): ?>
                                <p class="student-detail">
                                    <i class="bi bi-envelope"></i>
                                    <?= htmlspecialchars($row['email']) ?>
                                </p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-center">
                                    <button onclick="confirmDelete(<?= $row['id'] ?>, <?= $year ?>)" 
                                            class="btn btn-sm btn-delete">
                                        <i class="bi bi-trash me-1"></i> Remove
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state w-100">
                            <div class="empty-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <h5>No Students Found</h5>
                            <p class="text-muted">There are no students registered for this year</p>
                            <a href="select_year_add.php?year=<?= $year ?>" class="btn btn-sm btn-add">
                                <i class="bi bi-plus-lg me-1"></i> Add Student
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, year) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                window.location.href = `manage_students.php?delete_id=${id}&year=${year}`;
            }
        }
        
        // Enhance mobile touch targets
        document.querySelectorAll('.student-card').forEach(card => {
            card.style.cursor = 'pointer';
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-delete')) {
                    // Add your student view/edit functionality here
                    console.log('View student details');
                }
            });
        });
    </script>
</body>
</html>
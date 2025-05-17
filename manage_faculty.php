<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM faculty WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_faculty.php?msg=Faculty+deleted+successfully");
    exit();
}

// Get faculty data with profile picture from registration_requests
$faculty_query = "
    SELECT faculty.*, registration_requests.profile_picture 
    FROM faculty 
    LEFT JOIN registration_requests 
    ON faculty.email = registration_requests.email
";
$faculty_result = $conn->query($faculty_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Faculty | Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-color: #5a5c69;
        }

        body {
            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.2);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 0.35rem 0.35rem 0 0 !important;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-top: -60px;
        }

        .faculty-info {
            margin-top: 1rem;
        }

        .faculty-name {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--text-color);
        }

        .faculty-title {
            color: var(--primary-color);
            font-weight: 600;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            background-color: white;
            border-radius: 0.35rem;
        }

        .empty-state-icon {
            font-size: 5rem;
            color: #dddfeb;
            margin-bottom: 1rem;
        }

        .search-box {
            position: relative;
            max-width: 400px;
        }

        .search-box input {
            padding-left: 2.5rem;
            border-radius: 2rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #b7b9cc;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">
            <i class="bi bi-mortarboard-fill me-2"></i>EceAdmin
        </a>
        <div class="d-flex align-items-center">
            <a href="add_faculty.php" class="btn btn-primary me-2">
                <i class="bi bi-plus-lg me-1"></i> Add Faculty
            </a>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-grid me-1"></i> Dashboard
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 font-weight-bold text-gray-800">
            <i class="bi bi-people-fill me-2"></i> Faculty Management
        </h2>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" class="form-control" placeholder="Search faculty...">
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars(urldecode($_GET['msg'])) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <?php if ($faculty_result->num_rows > 0): ?>
            <?php while ($row = $faculty_result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <span>Faculty ID: <?= $row['id'] ?></span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        <div class="card-body text-center pt-5 pb-3">
                            <img src="<?= !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : 'default_profile.png'; ?>"
                                 class="profile-img" alt="Profile Picture">
                            <div class="faculty-info">
                                <h5 class="faculty-name mb-1"><?= htmlspecialchars($row['name']) ?></h5>
                                <p class="faculty-title mb-3"><?= htmlspecialchars($row['department'] ?? 'Faculty Member') ?></p>
                                <p class="text-muted mb-3">
                                    <i class="bi bi-envelope-fill me-2"></i><?= htmlspecialchars($row['email']) ?>
                                </p>
                                <div class="d-flex justify-content-center">
                                    <a href="edit_faculty.php?id=<?= $row['id'] ?>" 
                                       class="action-btn btn btn-sm btn-primary" title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </a>
                                    <a href="view_faculty.php?id=<?= $row['id'] ?>" 
                                       class="action-btn btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    <a href="manage_faculty.php?delete_id=<?= $row['id'] ?>" 
                                       class="action-btn btn btn-sm btn-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($row['name'])) ?>?');">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                <i class="bi bi-calendar-event me-1"></i> 
                                Last updated: <?= date('M d, Y', strtotime($row['updated_at'] ?? 'now')) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="h4">No Faculty Members Found</h3>
                    <p class="mb-4">You haven't added any faculty members yet. Get started by adding your first faculty member.</p>
                    <a href="add_faculty.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Faculty
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="bg-white py-4 mt-auto">
    <div class="container text-center">
        <p class="text-muted mb-0">&copy; <?= date('Y') ?> EduAdmin. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('.search-box input').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.card').forEach(card => {
            const name = card.querySelector('.faculty-name').textContent.toLowerCase();
            const email = card.querySelector('.text-muted').textContent.toLowerCase();
            card.parentElement.style.display = (name.includes(searchTerm) || email.includes(searchTerm)) ? 'block' : 'none';
        });
    });
</script>
</body>
</html>

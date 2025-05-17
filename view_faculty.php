<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if admin not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Redirect if no faculty ID provided
if (!isset($_GET['id'])) {
    header("Location: manage_faculty.php");
    exit();
}

$faculty_id = intval($_GET['id']);

// Fetch faculty data from faculty table
$query = "SELECT * FROM faculty WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$faculty = $result->fetch_assoc();

if (!$faculty) {
    header("Location: manage_faculty.php?error=Faculty+not+found");
    exit();
}

// Set profile picture path - check in this order:
// 1. First check faculty table's profile_picture field
// 2. Then check registration_requests table
// 3. Fall back to default image
$profile_picture = 'uploads/default_profile.png'; // fallback image path

// Check faculty table first
if (!empty($faculty['profile_picture']) && file_exists($faculty['profile_picture'])) {
    $profile_picture = $faculty['profile_picture'];
} else {
    // If not found in faculty table, check registration_requests
    $pic_query = "SELECT profile_picture FROM registration_requests WHERE email = ?";
    $pic_stmt = $conn->prepare($pic_query);
    $pic_stmt->bind_param("s", $faculty['email']);
    $pic_stmt->execute();
    $pic_result = $pic_stmt->get_result();
    
    if ($pic_result->num_rows > 0) {
        $pic_row = $pic_result->fetch_assoc();
        if (!empty($pic_row['profile_picture']) && file_exists($pic_row['profile_picture'])) {
            $profile_picture = $pic_row['profile_picture'];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $specialization = trim($_POST['specialization']);
    $bio = trim($_POST['bio']);

    // Use existing profile picture initially (from faculty table)
    $new_profile_picture = $faculty['profile_picture'];

    // Handle new profile picture upload (optional)
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));

        // Validate file is image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false) {
            // Generate unique file name
            $new_filename = uniqid('profile_', true) . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                // Delete old profile picture file if exists and not default
                if ($faculty['profile_picture'] !== 'uploads/default_profile.png' && file_exists($faculty['profile_picture'])) {
                    @unlink($faculty['profile_picture']);
                }
                $new_profile_picture = $target_file;
            } else {
                $error = "Failed to upload profile picture.";
            }
        } else {
            $error = "Uploaded file is not a valid image.";
        }
    }

    // Update faculty table if no error so far
    if (!isset($error)) {
        $update_query = "UPDATE faculty SET name = ?, email = ?, department = ?, position = ?, specialization = ?, bio = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssssssi", $name, $email, $department, $position, $specialization, $bio, $new_profile_picture, $faculty_id);
        if ($stmt->execute()) {
            header("Location: view_faculty.php?id=$faculty_id&msg=Faculty+updated+successfully");
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>View Faculty | Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
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
        
        .profile-header {
            height: 80px;
            border-radius: 0.35rem 0.35rem 0 0;
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-top: -75px;
        }
        
        .info-card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
        }
        
        .profile-img-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-img-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="bi bi-mortarboard-fill me-2"></i>EceAdmin
            </a>
            <div class="d-flex align-items-center">
                <a href="manage_faculty.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Faculty
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?= htmlspecialchars(urldecode($_GET['msg'])) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="profile-header bg-primary position-relative">
                        <a href="manage_faculty.php" class="back-btn btn btn-light btn-sm rounded-circle" aria-label="Back to Faculty">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    </div>
                    <div class="card-body text-center pt-4">
                        <div class="profile-img-container">
                            <?php if (file_exists($profile_picture)): ?>
                                <img src="<?= htmlspecialchars($profile_picture) ?>" 
                                     class="profile-img" 
                                     alt="Profile Picture of <?= htmlspecialchars($faculty['name']) ?>">
                            <?php else: ?>
                                <div class="profile-img profile-img-placeholder">
                                    <?= strtoupper(substr($faculty['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="mb-1 mt-3"><?= htmlspecialchars($faculty['name']) ?></h3>
                        <p class="text-muted mb-4"><?= htmlspecialchars($faculty['department'] ?? 'Faculty Member') ?></p>
                        
                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <a href="mailto:<?= htmlspecialchars($faculty['email']) ?>" class="btn btn-primary">
                                <i class="bi bi-envelope-fill me-1"></i> Email
                            </a>
                            <a href="edit_faculty.php?id=<?= $faculty_id ?>" class="btn btn-outline-primary">
                                <i class="bi bi-pencil-fill me-1"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="info-card h-100 p-4">
                            <h5 class="mb-4 border-bottom pb-3">
                                <i class="bi bi-person-lines-fill me-2"></i>Personal Information
                            </h5>
                            <div class="mb-3">
                                <div class="info-label">Full Name</div>
                                <div><?= htmlspecialchars($faculty['name']) ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="info-label">Email</div>
                                <div><?= htmlspecialchars($faculty['email']) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="info-card h-100 p-4">
                            <h5 class="mb-4 border-bottom pb-3">
                                <i class="bi bi-briefcase-fill me-2"></i>Professional Information
                            </h5>
                            <div class="mb-3">
                                <div class="info-label">Department</div>
                                <div><?= !empty($faculty['department']) ? htmlspecialchars($faculty['department']) : 'Not specified' ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="info-label">Position</div>
                                <div><?= !empty($faculty['position']) ? htmlspecialchars($faculty['position']) : 'Not specified' ?></div>
                            </div>
                            <div class="mb-3">
                                <div class="info-label">Specialization</div>
                                <div><?= !empty($faculty['specialization']) ? htmlspecialchars($faculty['specialization']) : 'Not specified' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($faculty['bio'])): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="bi bi-file-text-fill me-2"></i>Bio
                        </h5>
                        <p><?= nl2br(htmlspecialchars($faculty['bio'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-white py-4 mt-auto">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?= date('Y') ?> EduAdmin. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>
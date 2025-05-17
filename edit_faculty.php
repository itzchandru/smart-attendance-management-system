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

// Fetch profile picture from registration_requests by matching email
$profile_picture = 'uploads/default_profile.png'; // fallback image path
$pic_query = "SELECT profile_picture FROM registration_requests WHERE email = ?";
$pic_stmt = $conn->prepare($pic_query);
$pic_stmt->bind_param("s", $faculty['email']);
$pic_stmt->execute();
$pic_result = $pic_stmt->get_result();
$pic_row = $pic_result->fetch_assoc();

if ($pic_row && !empty($pic_row['profile_picture']) && file_exists($pic_row['profile_picture'])) {
    $profile_picture = htmlspecialchars($pic_row['profile_picture']);
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
    <title>Edit Faculty | Admin Panel</title>
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

        .form-card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            border-radius: 0.35rem 0.35rem 0 0;
            padding: 1.5rem;
        }

        .profile-img-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .profile-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .profile-upload-btn:hover {
            background-color: var(--accent-color);
        }

        .form-label {
            font-weight: 600;
        }

        .btn-save {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-save:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
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
                <a href="view_faculty.php?id=<?= $faculty_id ?>" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-x-lg me-1"></i> Cancel
                </a>
                <button type="submit" form="editFacultyForm" class="btn btn-save">
                    <i class="bi bi-save-fill me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="form-card">
                    <div class="form-header">
                        <h3 class="mb-0">
                            <i class="bi bi-pencil-square me-2"></i>Edit Faculty Profile
                        </h3>
                    </div>

                    <div class="card-body p-5">
                        <?php if (isset($error)) : ?>
                            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form id="editFacultyForm" method="POST" enctype="multipart/form-data" novalidate>
                            <div class="text-center mb-4">
                                <div class="profile-img-container">
                                    <img id="profilePreview" src="<?= $profile_picture ?>" class="profile-img" alt="Profile Picture" />
                                    <label for="profile_picture" class="profile-upload-btn" title="Change photo">
                                        <i class="bi bi-camera-fill"></i>
                                    </label>
                                    <input
                                        type="file"
                                        id="profile_picture"
                                        name="profile_picture"
                                        accept="image/*"
                                        class="d-none"
                                        onchange="previewImage(this)"
                                    />
                                </div>
                                <small class="text-muted">Click camera icon to change photo</small>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        value="<?= htmlspecialchars($faculty['name']) ?>"
                                        required
                                    />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input
                                        type="email"
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        value="<?= htmlspecialchars($faculty['email']) ?>"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="department"
                                        name="department"
                                        value="<?= htmlspecialchars($faculty['department']) ?>"
                                    />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="position"
                                        name="position"
                                        value="<?= htmlspecialchars($faculty['position'] ?? '') ?>"
                                    />
                                </div>

                            </div>

                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label for="specialization" class="form-label">Specialization</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="specialization"
                                        name="specialization"
                                        value="<?= htmlspecialchars($faculty['specialization'] ?? '') ?>"
                                    />
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById("profilePreview");
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>

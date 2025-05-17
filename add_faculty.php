<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$success_msg = "";
$error_msg = "";

// Handle Add Faculty
if (isset($_POST['add_faculty'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO faculty (name, email, department, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $name, $email, $department, $password);

    if ($stmt->execute()) {
        $success_msg = "Faculty added successfully";
    } else {
        $error_msg = "Error: " . $conn->error;
    }
}

// Handle Password Change
if (isset($_POST['reset_password'])) {
    $email = $_POST['reset_email'];
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $query = "UPDATE faculty SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $success_msg = "Password updated successfully";
    } else {
        $error_msg = "Error: Email not found or no changes made";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Management | Admin Panel</title>
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
        
        .management-card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .management-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 0.35rem 0.35rem 0 0 !important;
            padding: 1.25rem 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-warning {
            background-color: #f6c23e;
            border-color: #f6c23e;
            color: #1f2d3d;
        }
        
        .btn-warning:hover {
            background-color: #dda20a;
            border-color: #dda20a;
        }
        
        .password-input-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 70%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
            background: transparent;
            border: none;
            padding: 0 8px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
        }
        
        /* Adjust input padding to prevent text under the eye icon */
        .password-field {
            padding-right: 40px !important;
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
                    <i class="bi bi-arrow-left me-1"></i> Back to Faculty List
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 font-weight-bold text-gray-800">
                <i class="bi bi-person-plus-fill me-2"></i> Faculty Management
            </h2>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Add Faculty Card -->
            <div class="col-lg-6 col-md-12">
                <div class="management-card">
                    <div class="card-header">
                        <i class="bi bi-person-plus me-2"></i> Add New Faculty
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-4">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       placeholder="Enter faculty member's full name" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       placeholder="Enter faculty email address" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="department" class="form-label">Department</label>
                                <select id="department" name="department" class="form-select" required>
                                    <option value="" selected disabled>Select department</option>
                                    <option value="CSE">Computer Science</option>
                                    <option value="ECE">Electronics and Communication Engineering</option>
                                    <option value="EEE">Electrical Engineering</option>
                                    <option value="Mech">Mechanical Engineering</option>
                                    <option value="Civil">Civil Engineering</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Physics">Physics</option>
                                    <option value="Chemistry">Chemistry</option>
                                    <option value="Biology">Biology</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-4 password-input-container">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control password-field" 
                                       placeholder="Set initial password" required>
                                <button type="button" class="password-toggle" 
                                   onclick="togglePassword('password', this)">
                                   <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_faculty" class="btn btn-primary py-2">
                                    <i class="bi bi-person-plus me-1"></i> Add Faculty Member
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reset Password Card -->
            <div class="col-lg-6 col-md-12">
                <div class="management-card">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-key-fill me-2"></i> Reset Faculty Password
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-4">
                                <label for="reset_email" class="form-label">Faculty Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="reset_email" name="reset_email" class="form-control" 
                                           placeholder="Enter faculty member's email" required>
                                </div>
                            </div>
                            
                            <div class="mb-4 password-input-container">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control password-field" 
                                       placeholder="Enter new password" required>
                                <button type="button" class="password-toggle" 
                                   onclick="togglePassword('new_password', this)">
                                   <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="reset_password" class="btn btn-warning py-2">
                                    <i class="bi bi-arrow-repeat me-1"></i> Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-white py-4 mt-auto">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?= date('Y') ?> EceAdmin. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
            } else {
                input.type = "password";
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const emailInputs = form.querySelectorAll('input[type="email"]');
                    emailInputs.forEach(input => {
                        if (!input.value.includes('@')) {
                            e.preventDefault();
                            alert('Please enter a valid email address');
                            input.focus();
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
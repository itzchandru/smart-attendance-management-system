<?php
session_start();
error_reporting(E_ALL);  // Report all PHP errors
ini_set('display_errors', 1);  // Display errors on the screen

include "db.php"; // Database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email.";
    } else {
        $checkQuery = "SELECT email FROM faculty WHERE email=? UNION SELECT email FROM admin WHERE email=?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ss", $email, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $message = "❌ Email already exists!";
} else {
            // File Upload Handling
            $profilePicture = ""; // Default empty path
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Validate file type and size
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                if (in_array($_FILES['profile_picture']['type'], $allowedTypes) && 
                    $_FILES['profile_picture']['size'] <= $maxSize) {
                    
                    $fileName = time() . "_" . basename($_FILES['profile_picture']['name']);
                    $uploadFilePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFilePath)) {
                        $profilePicture = $uploadFilePath;
                    } else {
                        $message = "❌ Failed to upload profile picture!";
                    }
                } else {
                    $message = "❌ Invalid file type or size too large (max 2MB)!";
                }
            }

            if (empty($message)) {
                $token = bin2hex(random_bytes(16));

                // Insert into registration_requests table with profile picture path
                $stmt = $conn->prepare("INSERT INTO registration_requests (name, email, role, token, profile_picture) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $role, $token, $profilePicture);

                if ($stmt->execute()) {
                    $mail = new PHPMailer(true);

                    try {
                        // SMTP settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'itzchandru1@gmail.com';       // your Gmail address
                        $mail->Password = 'gxnj taip zxij pjuv';         // your generated App Password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('itzchandru1@gmail.com', 'Attendance System');
                        $mail->addAddress($email, $name);

                        // Content
                        $mail->isHTML(false);
                        $verification_link = "https://atteandance-system.great-site.net/verify_email.php?token=$token";
                        $mail->Subject = 'Verify your email';
                        $mail->Body = "Hi $name,\n\nPlease verify your email by clicking this link:\n$verification_link\n\nThis link will expire in 24 hours.";

                        $mail->send();
                        $message = "✅ Registration request sent. Please check your email to verify.";
                    } catch (Exception $e) {
                        $message = "❌ Mailer Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $message = "❌ Registration failed!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a1a; /* Dark background */
            font-family: 'Arial', sans-serif;
        }

        /* Particle background */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            background: #1a1a1a; /* Dark background */
            z-index: 1;
        }

        /* Registration container */
        .register-container {
            background: rgba(255, 255, 255, 0.9); /* Semi-transparent white */
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: start;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 2;
            animation: fadeIn 1.5s ease-in-out;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .form-label {
            margin-bottom: 4px;
            font-size: 14px;
        }

        .form-control {
            padding: 6px 10px;
            font-size: 14px;
        }

        .mb-3 {
            margin-bottom: 12px !important; /* Reduce gap between fields */
        }

        h3 {
            font-size: 20px;
            margin-bottom: 16px;
        }

        h3 {
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            animation: slideIn 1s ease-in-out;
        }

        @keyframes slideIn {
            0% { opacity: 0; transform: translateX(-50px); }
            100% { opacity: 1; transform: translateX(0); }
        }

        .form-control {
            border-radius: 8px;
        }

        .btn-success {
            border-radius: 8px;
            background: #ff5733; /* Bright button color */
            border: none;
            font-weight: bold;
        }

        .btn-success:hover {
            background: #e64a19;
        }

        .link-text {
            color: #ff5733;
            font-weight: bold;
        }

        .link-text:hover {
            color: #e64a19;
            text-decoration: none;
        }

        .icon-label {
            font-size: 18px;
            color: #ff5733;
            margin-right: 10px;
        }
        
        .form-select {
            padding: 6px 10px;
            font-size: 14px;
            border-radius: 8px;
        }

        .preview-container {
            text-align: center;
            margin: 10px 0;
        }

        .preview-image {
            max-width: 100px;
            max-height: 80px;
            border-radius: 50%;
            border: 3px solid #ff5733;
            display: none;
        }

        .file-upload-label {
            display: block;
            cursor: pointer;
            text-align: center;
        }

        .file-upload-icon {
            font-size: 40px;
            color: #ff5733;
            margin-bottom: 5px;
        }

        .file-upload-text {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>

    <!-- Particle background -->
    <div id="particles-js"></div>

    <!-- Registration container -->
    <div class="register-container">
        <h3 class="p-2"><i class="fas fa-user-plus"></i> Registration Request</h3>

        <?php if ($message): ?>
            <div class="alert alert-info text-center"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-user-tie icon-label"></i> Full Name
                </label>
                <input type="text" name="name" class="form-control" placeholder="Enter your Name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-envelope icon-label"></i> Email Address
                </label>
                <input type="email" name="email" class="form-control" placeholder="Enter your Email" required>
            </div>
            <div class="mb-3">
    <label class="form-label">
        <i class="fas fa-user-shield icon-label"></i> Role
    </label>
    <select name="role" class="form-select" required>
        <option value="">Select Role</option>
        <option value="faculty" selected>Faculty</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">
        <i class="fas fa-camera icon-label"></i> Profile Picture
    </label>
    <div class="preview-container">
        <img id="preview" class="preview-image" src="#" alt="Profile Preview" style="display:none;">
    </div>
    <label for="profile_picture" class="file-upload-label" style="cursor:pointer;">
        <div>
            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
            <div class="file-upload-text">Click to upload profile picture (Max 2MB)</div>
        </div>
    </label>
    <input type="file" id="profile_picture" name="profile_picture" class="form-control d-none" accept="image/*" required>
</div>
            <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-paper-plane"></i> Send Request
            </button>
        </form>

        <p class="text-center mt-3">Already have an account? <a href="faculty_login.php" class="link-text">Login here</a></p>
    </div>

    <!-- Include Particles.js -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        // Particles.js configuration
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#ffffff' // White particles
                },
                shape: {
                    type: 'circle',
                    stroke: {
                        width: 0,
                        color: '#000000'
                    },
                    polygon: {
                        nb_sides: 5
                    }
                },
                opacity: {
                    value: 0.5,
                    random: false,
                    anim: {
                        enable: false,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: false,
                        speed: 40,
                        size_min: 0.1,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#ffffff',
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 6,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out',
                    bounce: false,
                    attract: {
                        enable: false,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: true,
                        mode: 'repulse'
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 400,
                        line_linked: {
                            opacity: 1
                        }
                    },
                    bubble: {
                        distance: 400,
                        size: 40,
                        duration: 2,
                        opacity: 8,
                        speed: 3
                    },
                    repulse: {
                        distance: 200,
                        duration: 0.4
                    },
                    push: {
                        particles_nb: 4
                    },
                    remove: {
                        particles_nb: 2
                    }
                }
            },
            retina_detect: true
        });

        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const preview = document.getElementById('preview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        });

      
    </script>
</body>
</html>
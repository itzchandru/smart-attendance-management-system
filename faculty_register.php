<?php
session_start();
include "db.php"; // Database connection

$message = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); // Raw password
    $confirmPassword = trim($_POST['confirm_password']);

    // Check if passwords match
    if ($password !== $confirmPassword) {
        $message = "❌ Passwords do not match!";
    } else {
        // Password strength validation
        if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $password)) {
            $message = "❌ Password must be at least 8 characters long, with at least one uppercase letter, one lowercase letter, one digit, and one special character.";
        } else {
            // Secure password hashing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // File Upload Handling
            $profilePicture = ""; // Default empty path
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . "_" . basename($_FILES['profile_picture']['name']);
                $uploadFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFilePath)) {
                    $profilePicture = $uploadFilePath;
                } else {
                    $message = "❌ Failed to upload profile picture!";
                }
            }

            // Check if email already exists
            $checkQuery = "SELECT * FROM faculty WHERE email=?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "❌ Email already exists!";
            } else {
                // Insert faculty into database with hashed password
                $query = "INSERT INTO faculty (name, email, password, profile_picture) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssss", $name, $email, $hashedPassword, $profilePicture);

                if ($stmt->execute()) {
                    // Redirect to faculty login page after successful registration
                    header("Location: faculty_login.php?msg=Registration+Successful!+You+can+now+login.");
                    exit(); // Ensure no further code is executed
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
    <title>Faculty Registration</title>
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

        .password-wrapper {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #6c757d;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 14px;
        }

        .password-strength-bar {
            height: 5px;
            background: #ddd;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }

        .password-strength-bar div {
            height: 100%;
            width: 0;
            background: #ff5733;
            border-radius: 5px;
            transition: width 0.3s ease;
        }

        .icon-label {
            font-size: 18px;
            color: #ff5733;
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <!-- Particle background -->
    <div id="particles-js"></div>

    <!-- Registration container -->
    <div class="register-container">
        <h3 class="p-2"><i class="fas fa-user-plus"></i> Faculty Registration</h3>

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
                    <i class="fas fa-key icon-label"></i> Password
                </label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your Password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
                <div class="password-strength-bar">
                    <div id="password-strength-indicator"></div>
                </div>
                <div id="password-strength-text" class="password-strength"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-key icon-label"></i> Confirm Password
                </label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your Password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-camera icon-label"></i> Profile Picture
                </label>
                <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
            </div>
            <button type="submit" name="register" class="btn btn-success w-100">
                <i class="fas fa-user-check"></i> Register
            </button>
        </form>

        <p class="text-center mt-3">Already registered? <a href="faculty_login.php" class="link-text">Login here</a></p>
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
    </script>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId, iconElement) {
            var passwordField = document.getElementById(fieldId);
            if (passwordField.type === "password") {
                passwordField.type = "text";
                iconElement.classList.remove("fa-eye");
                iconElement.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                iconElement.classList.remove("fa-eye-slash");
                iconElement.classList.add("fa-eye");
            }
        }

        // Password strength indicator
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('password-strength-indicator');
        const strengthText = document.getElementById('password-strength-text');

        passwordField.addEventListener('input', () => {
            const password = passwordField.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            let strengthMessage = '';
            let barWidth = 0;
            let barColor = '';

            if (strength === 4) {
                strengthMessage = 'Strong password!';
                barWidth = '100%';
                barColor = '#28a745'; // Green
            } else if (strength === 3) {
                strengthMessage = 'Moderate password!';
                barWidth = '75%';
                barColor = '#ffc107'; // Yellow
            } else if (strength === 2) {
                strengthMessage = 'Weak password!';
                barWidth = '50%';
                barColor = '#ff5733'; // Red
            } else {
                strengthMessage = 'Very weak password!';
                barWidth = '25%';
                barColor = '#dc3545'; // Dark red
            }

            strengthBar.style.width = barWidth;
            strengthBar.style.backgroundColor = barColor;
            strengthText.textContent = strengthMessage;
        });
    </script>
</body>
</html>

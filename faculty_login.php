<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php'; // Ensure this file exists and has the correct connection

// Check if the 'Remember Me' cookie exists and auto-login
if (isset($_COOKIE['faculty_email']) && isset($_COOKIE['faculty_password'])) {
    $email = $_COOKIE['faculty_email'];
    $password = $_COOKIE['faculty_password'];

    // Query the faculty table to verify login
    $query = "SELECT * FROM faculty WHERE email = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['faculty_logged_in'] = true;
            $_SESSION['faculty_id'] = $row['id'];
            $_SESSION['faculty_name'] = $row['name'];

            header("Location: faculty_dashboard.php"); // Redirect to the attendance page
            exit();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']) ? true : false;

    // Check if email exists in the faculty table
    $query = "SELECT * FROM faculty WHERE email = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['faculty_logged_in'] = true;
            $_SESSION['faculty_id'] = $row['id'];
            $_SESSION['faculty_name'] = $row['name'];

            // Set cookies if 'Remember Me' is selected
            if ($remember_me) {
                setcookie('faculty_email', $email, time() + (86400 * 30), "/"); // Cookie expires in 30 days
                setcookie('faculty_password', $password, time() + (86400 * 30), "/"); // Cookie expires in 30 days
            } else {
                // If 'Remember Me' is not selected, delete the cookies
                setcookie('faculty_email', "", time() - 3600, "/");
                setcookie('faculty_password', "", time() - 3600, "/");
            }

            header("Location: faculty_dashboard.php"); // Redirect to the attendance page
            exit();
        } else {
            $error = "❌ Invalid Password!";
        }
    } else {
        $error = "❌ No Faculty Found with this Email!";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
            overflow: hidden;
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

        /* Login container */
        .login-container {
            background: rgba(255, 255, 255, 0.9); /* Semi-transparent white */
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: start;
            width: 100%;
            max-width: 350px;
            position: relative;
            z-index: 2;
            animation: fadeIn 1.5s ease-in-out;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        h2 {
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

        .btn-primary {
            border-radius: 8px;
            background: #ff5733; /* Bright button color */
            border: none;
            font-weight: bold;
        }

        .btn-primary:hover {
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
    </style>
</head>
<body>

    <!-- Particle background -->
    <div id="particles-js"></div>

    <!-- Login container -->
    <div class="login-container">
        <h2 class="p-2">🎓 Faculty Login</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-envelope icon-label"></i> Email Address
                </label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-key icon-label"></i> Password
                </label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="remember_me" id="remember_me">
                <label class="form-check-label" for="remember_me">Remember Me</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
        </form>


        <!-- Registration Link -->
        <div class="text-center mt-2">
            <p>Don't have an account? <a href="registration_request.php" class="link-text">Register here!</a></p>
        </div>
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
    </script>

</body>
</html>

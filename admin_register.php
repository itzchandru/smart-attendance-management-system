<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$message_type = '';
$redirect = false;

if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
    $_SESSION['last_attempt_date'] = date('Y-m-d');
}

if ($_SESSION['last_attempt_date'] != date('Y-m-d')) {
    $_SESSION['attempts'] = 0;
    $_SESSION['last_attempt_date'] = date('Y-m-d');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_SESSION['attempts'] >= 3) {
        $message = "❌ You've exceeded the maximum 3 attempts for today. Please try again tomorrow.";
        $message_type = 'danger';
    } else {
        $secret_key_entered = $_POST['admin_secret'];
        $correct_secret_key = "1234";

        $_SESSION['attempts']++;

        if ($secret_key_entered !== $correct_secret_key) {
            $remaining_attempts = 3 - $_SESSION['attempts'];
            $message = "❌ Unauthorized access. Invalid Admin Key. Remaining attempts today: $remaining_attempts";
            $message_type = 'danger';
        } else {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $password_plain = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate password match
            if ($password_plain !== $confirm_password) {
                $message = "❌ Passwords do not match!";
                $message_type = 'danger';
            } else {
                // Check if email exists
                $check_sql = "SELECT id FROM admin WHERE email = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows > 0) {
                    $message = "❌ Email already registered. Please use a different email.";
                    $message_type = 'danger';
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    // Insert new admin
                    $password = password_hash($password_plain, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO admin (name, email, password) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $name, $email, $password);

                    if ($stmt->execute()) {
                        $_SESSION['attempts'] = 0;
                        $message = "✅ Admin registered successfully! Redirecting to login page...";
                        $message_type = 'success';
                        $redirect = true;
                        
                        // Store success message in session for login page
                        $_SESSION['registration_success'] = "Registration successful! Please login with your credentials.";
                    } else {
                        $message = "❌ Error: " . $stmt->error;
                        $message_type = 'danger';
                    }

                    $stmt->close();
                }
            }
        }
    }
    $conn->close();
    
    // Redirect if registration was successful
    if ($redirect) {
        header("Refresh: 2; url=admin_login.php");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1a1a1a;
            overflow: auto;
            font-family: 'Arial', sans-serif;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            background: #1a1a1a;
            z-index: 1;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: start;
            width: 100%;
            max-width: 450px;
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
            color: #ffffff;
            margin-bottom: 20px;
            animation: slideIn 1s ease-in-out;
        }

        .form-control {
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-custom {
            border-radius: 8px;
            background: #4A55A2;
            color: #fff;
            font-weight: bold;
            padding: 10px 20px;
            margin: 10px 0;
            width: 100%;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            background: #3B4E9A;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
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
            color: rgba(17, 16, 16, 0.5);
        }

        .password-strength {
            margin-top: 5px;
            font-size: 14px;
            color: #fff;
        }

        .link-text {
            color: #4A55A2;
            font-weight: bold;
        }

        .link-text:hover {
            color: #3B4E9A;
            text-decoration: none;
        }
        .form-label{
            color:#ffffff;
        }
        #link{
            color:#ffffff;
        }
        .attempts-info {
            color: #ffffff;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .alert-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            width: 90%;
            max-width: 500px;
        }
    </style>
</head>
<body>

    <!-- Particle background -->
    <div id="particles-js"></div>

    <!-- Alert container for messages -->
    <?php if (!empty($message)): ?>
    <div class="alert-container">
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Registration container -->
    <div class="register-container">
        <h2 class="text-center mb-3">🔐 Admin Registration</h2>
        
        <div class="attempts-info">
            <?php
            $remaining_attempts = 3 - $_SESSION['attempts'];
            echo "Attempts remaining today: $remaining_attempts/3";
            ?>
        </div>

        <form method="POST">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="mb-3">
                <label class="form-label">👤 Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">📧 Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">🔑 Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a strong password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
                <div id="password-strength" class="password-strength"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">🔑 Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">🔐 Admin Secret Key</label>
                <input type="password" name="admin_secret" class="form-control" placeholder="Enter Admin Key" required>
            </div>

            <button type="submit" class="btn btn-custom w-100 py-2" <?php echo ($_SESSION['attempts'] >= 3) ? 'disabled' : ''; ?>>
                Register
            </button>
        </form>

        <p id="link" class="mt-3 text-center">Already have an account? <a href="admin_login.php" class="link-text">Login here</a></p>
    </div>

    <!-- Include Bootstrap JS for alert dismissal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
                    value: '#ffffff'
                },
                shape: {
                    type: 'circle',
                    stroke: {
                        width: 0,
                        color: '#000000'
                    }
                },
                opacity: {
                    value: 0.5,
                    random: false
                },
                size: {
                    value: 3,
                    random: true
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
                    bounce: false
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
                    repulse: {
                        distance: 200,
                        duration: 0.4
                    },
                    push: {
                        particles_nb: 4
                    }
                }
            },
            retina_detect: true
        });

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

        // Real-time password strength feedback
        const passwordField = document.getElementById('password');
        const strengthText = document.getElementById('password-strength');

        passwordField.addEventListener('input', () => {
            const password = passwordField.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            let strengthMessage = '';
            if (strength === 4) {
                strengthMessage = 'Strong password!';
                strengthText.style.color = 'green';
            } else if (strength >= 2) {
                strengthMessage = 'Medium strength';
                strengthText.style.color = 'orange';
            } else {
                strengthMessage = 'Weak password';
                strengthText.style.color = 'red';
            }

            strengthText.textContent = strengthMessage;
        });

        // Auto-dismiss alerts after 5 seconds
        window.onload = function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // If redirect is needed, don't close the success message
            <?php if ($redirect): ?>
                const successAlert = document.querySelector('.alert-success');
                if (successAlert) {
                    const bsAlert = new bootstrap.Alert(successAlert);
                    // Don't close this alert automatically
                    clearTimeout();
                }
            <?php endif; ?>
        };
    </script>
</body>
</html>
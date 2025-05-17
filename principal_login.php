<?php
session_start();
include "db.php";

$message = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $query = "SELECT * FROM principal WHERE email=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['principal_id'] = $user['id'];
            $_SESSION['principal_name'] = $user['name'];
            header("Location: principal_alt_dashboard.php");
            exit();
        } else {
            $message = "❌ Invalid password!";
        }
    } else {
        $message = "❌ No account found with this email!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #111;
            margin: 0;
            height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 2;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            animation: fadeInUp 1s ease-in-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h3 {
            color: #222;
            font-weight: 700;
        }

        .form-label {
            font-weight: 600;
        }

        .btn-login {
            background: #ff5e3a;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: 0.3s ease;
        }

        .btn-login:hover {
            background: #e84822;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
        }

        .password-wrapper {
            position: relative;
        }

        .form-control {
            border-radius: 8px;
        }

        .small-text {
            font-size: 14px;
            text-align: center;
            margin-top: 10px;
        }

        .small-text a {
            color: #ff5e3a;
            text-decoration: none;
        }

        .small-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="login-container">
    <h3 class="text-center mb-4"><i class="fas fa-user-shield me-2"></i>Principal Login</h3>

    <?php if ($message): ?>
        <div class="alert alert-danger text-center"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter your Email" required>
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
            </div>
        </div>
        <button type="submit" name="login" class="btn btn-login w-100 mt-3">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
    </form>

    <div class="small-text mt-3">
        <a href="principal_register.php"><i class="fas fa-user-plus me-1"></i>Create a new Principal Account</a>
    </div>
    <div class="small-text">
        <a href="index.php"><i class="fas fa-home me-1"></i>Back to Home</a>
    </div>
</div>

<!-- Particles.js -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        particles: {
            number: { value: 60 },
            size: { value: 3 },
            color: { value: "#ffffff" },
            line_linked: { enable: true, color: "#ffffff" },
            move: { speed: 4 }
        },
        interactivity: {
            events: {
                onhover: { enable: true, mode: "repulse" }
            }
        }
    });

    function togglePassword(fieldId, icon) {
        const input = document.getElementById(fieldId);
        const type = input.getAttribute("type") === "password" ? "text" : "password";
        input.setAttribute("type", type);
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    }
</script>

</body>
</html>

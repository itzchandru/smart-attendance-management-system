<?php
include "db.php";
$message = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $message = "❌ Passwords do not match!";
    } else {
        $check = $conn->prepare("SELECT * FROM principal WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "⚠️ Email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO principal (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            if ($stmt->execute()) {
                header("Location: principal_login.php?registered=success");
                exit();
            } else {
                $message = "❌ Something went wrong!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Principal Registration</title>
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

        .register-container {
            position: relative;
            z-index: 2;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
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

        .btn-register {
            background: #ff5e3a;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: 0.3s ease;
        }

        .btn-register:hover {
            background: #ff5e3a;
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

<div class="register-container">
    <h3 class="text-center mb-4"><i class="fas fa-user-plus me-2"></i>Principal Registration</h3>

    <?php if ($message): ?>
        <div class="alert alert-info text-center"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-user me-2"></i>Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter Full Name" required>
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter Email Address" required>
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" placeholder="Enter Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-lock me-2"></i>Confirm Password</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
            </div>
        </div>
        <button type="submit" name="register" class="btn btn-register w-100 mt-3">
            <i class="fas fa-user-plus me-2"></i>Register
        </button>
    </form>

    <div class="small-text mt-3">
        <a href="principal_login.php"><i class="fas fa-sign-in-alt me-1"></i>Already registered? Login here</a>
    </div>
    <div class="small-text">
        <a href="index.php"><i class="fas fa-home me-1"></i>Back to Home</a>
    </div>
</div>

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

    function togglePassword(id, icon) {
        const input = document.getElementById(id);
        const type = input.getAttribute("type") === "password" ? "text" : "password";
        input.setAttribute("type", type);
        icon.classList.toggle("fa-eye");
        icon.classList.toggle("fa-eye-slash");
    }
</script>

</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php'; // Ensure this file exists and has the correct connection

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists in the faculty table
    $query = "SELECT * FROM faculty WHERE email = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token valid for 1 hour

        // Store the token in the database
        $updateQuery = "UPDATE faculty SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if ($updateStmt === false) {
            die("Database error: " . $conn->error);
        }
        $updateStmt->bind_param("sss", $token, $expiry, $email);
        $updateStmt->execute();

        // Send password reset email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@gmail.com'; // Your Gmail address
            $mail->Password = 'your_app_password'; // Your Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port = 587; // TCP port to connect to

            // Enable debugging (optional)
            $mail->SMTPDebug = 0; // 0 = off, 1 = client messages, 2 = client and server messages

            // Recipients
            $mail->setFrom('no-reply@yourdomain.com', 'Your App Name');
            $mail->addAddress($email); // Add a recipient

            // Content
            $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click the link below to reset your password:<br><br><a href='$resetLink'>$resetLink</a>";

            $mail->send();
            $message = "✅ Password reset link sent to your email!";
        } catch (Exception $e) {
            $message = "❌ Failed to send password reset email. Error: " . $mail->ErrorInfo;
        }
    } else {
        $message = "❌ No Faculty Found with this Email!";
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #ffcc00; /* Bright & energetic solid color */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .forgot-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 350px;
            position: relative;
            z-index: 2;
        }

        h2 {
            font-weight: bold;
            color: #333;
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
    </style>
</head>
<body>

    <div class="forgot-container">
        <h2 class="p-3">🔑 Forgot Password</h2>

        <?php if ($message): ?>
            <div class="alert alert-info text-center"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-envelope icon-label"></i> Email Address
                </label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Send Reset Link</button>
        </form>

        <div class="text-center mt-3">
            <a href="faculty_login.php" class="link-text">Back to Login</a>
        </div>
    </div>

</body>
</html>
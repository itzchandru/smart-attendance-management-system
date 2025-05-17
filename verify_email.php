<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";
$message_type = ""; // 'success' or 'danger'

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    try {
        $pdo = new PDO("mysql:host=sql103.infinityfree.com;dbname=if0_38568116_new_attendance_db", "if0_38568116", "KpXSolJEtejI");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Update verification status
        $stmt = $pdo->prepare("UPDATE registration_requests SET is_verified = 1 WHERE token = ? AND is_verified = 0");
        $stmt->execute([$token]);

        if ($stmt->rowCount() > 0) {
            $message = "Email verified successfully! Redirecting...";
            $message_type = "success";
        } else {
            $message = "Invalid or expired verification link, or email already verified.";
            $message_type = "danger";
        }
    } catch (PDOException $e) {
        $message = "Database error: " . htmlspecialchars($e->getMessage());
        $message_type = "danger";
    }
} else {
    $message = "No verification token provided.";
    $message_type = "danger";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
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
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
        }

        .verification-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .verification-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            color: #4ecca3;
        }

        h2 {
            font-weight: 700;
            margin-bottom: 15px;
            color: #fff;
            font-size: 1.8rem;
        }

        .message-box {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .btn-home {
            background: #4ecca3;
            border: none;
            padding: 8px 20px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-home:hover {
            background: #3aa789;
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        .status-animation {
            display: inline-block;
            margin: 15px 0;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(78, 204, 163, 0.3);
            border-radius: 50%;
            border-top-color: #4ecca3;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            border: none;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: rgba(78, 204, 163, 0.2);
            border-left-color: #4ecca3;
            color: #fff;
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            border-left-color: #e74c3c;
            color: #fff;
        }

        .countdown {
            font-size: 0.8rem;
            margin-top: 5px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon">
            <?php if ($message_type == 'success'): ?>
                <i class="fas fa-check-circle"></i>
            <?php else: ?>
                <i class="fas fa-exclamation-circle"></i>
            <?php endif; ?>
        </div>
        
        <h2>Email Verification</h2>
        
        <div class="alert alert-<?php echo $message_type; ?> message-box">
            <?php echo $message; ?>
            <?php if ($message_type == 'success'): ?>
                <div class="countdown">Redirecting in <span id="countdown">3</span> seconds...</div>
            <?php endif; ?>
        </div>
        
        <?php if ($message_type == 'success'): ?>
            <div class="status-animation">
                <div class="spinner"></div>
            </div>
        <?php endif; ?>
        
        <a href="index.php" class="btn btn-home">
            <i class="fas fa-home"></i> Return Home
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($message_type == 'success'): ?>
            // Countdown and redirect
            let seconds = 5;
            const countdownElement = document.getElementById('countdown');
            
            const countdown = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdown);
                    window.location.href = 'index.php';
                }
            }, 1000);
            
            // Fallback redirect in case countdown fails
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
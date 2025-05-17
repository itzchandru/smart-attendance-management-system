<?php
// For now, no login check. Later add session admin check
error_reporting(E_ALL);
ini_set('display_errors', 1);
$pdo = new PDO("mysql:host=sql103.infinityfree.com;dbname=if0_38568116_new_attendance_db", "if0_38568116", "KpXSolJEtejI");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$message = '';
$message_type = ''; // 'success' or 'danger'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['request_id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT * FROM registration_requests WHERE id = ?");
    $stmt->execute([$id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($req) {
        if ($action == 'approve') {
            $temp_pass = bin2hex(random_bytes(4));
            $hashed_pass = password_hash($temp_pass, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("INSERT INTO faculty (name, email, password, role) VALUES (?, ?, ?, ?)");
            $insert->execute([$req['name'], $req['email'], $hashed_pass, $req['role']]);

            $update = $pdo->prepare("UPDATE registration_requests SET is_approved = 1 WHERE id = ?");
            $update->execute([$id]);

            // Send email with temporary password
            $mail = new PHPMailer(true);
            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'itzchandru1@gmail.com'; // Your Gmail address
                $mail->Password = 'gxnj taip zxij pjuv';    // Your Gmail app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('itzchandru1@gmail.com', 'Attendance System');
                $mail->addAddress($req['email'], $req['name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Account Has Been Approved';
                $mail->Body = "
                    <h2>Account Approval Notification</h2>
                    <p>Dear {$req['name']},</p>
                    <p>Your registration request has been approved by the administrator.</p>
                    <p>Here are your login details:</p>
                    <p><strong>Email:</strong> {$req['email']}</p>
                    <p><strong>Temporary Password:</strong> $temp_pass</p>
                    <p>Please login and change your password immediately.</p>
                    <p>Login URL: <a href='https://atteandance-system.great-site.net/faculty_login.php'>https://atteandance-system.great-site.net/faculty_login.php</a></p>
                    <p>Best regards,<br>Administration Team</p>
                ";
                $mail->AltBody = "Your account has been approved. Temporary password: $temp_pass";

                $mail->send();
                $message = "User approved and temporary password sent to {$req['email']}";
                $message_type = "success";
            } catch (Exception $e) {
                $message = "User approved but email could not be sent. Temporary password: <b>$temp_pass</b> (Please send this manually). Error: " . $mail->ErrorInfo;
                $message_type = "warning";
            }

        } elseif ($action == 'reject') {
            $delete = $pdo->prepare("DELETE FROM registration_requests WHERE id = ?");
            $delete->execute([$id]);
            $message = "Request rejected and deleted.";
            $message_type = "info";
        }
    } else {
        $message = "Request not found.";
        $message_type = "danger";
    }
}

$stmt = $pdo->prepare("SELECT * FROM registration_requests WHERE is_verified = 1 AND is_approved = 0");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Approval Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            font-weight: 700;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #858796;
        }
        
        .table th {
            font-weight: 600;
            color: #4e73df;
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
        }
        
        .btn-approve {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-reject {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        
        .status-badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        
        .badge-pending {
            color: #fff;
            background-color: var(--warning-color);
        }
        
        .alert-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        
        .show-alert {
            opacity: 1;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #b7b9cc;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dddfeb;
        }
    </style>
</head>
<body>
    <div class="admin-container">
    <a href="admin_dashboard.php" class="btn btn-primary mb-3">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pending Registration Requests</h5>
                <span class="badge bg-primary"><?php echo count($requests); ?> Pending</span>
            </div>
            <div class="card-body">
                <?php if (count($requests) == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h4>No pending requests</h4>
                        <p>All registration requests have been processed</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r): ?>
                                <tr>
                                    <td><?=htmlspecialchars($r['name'])?></td>
                                    <td><?=htmlspecialchars($r['email'])?></td>
                                    <td><?=ucfirst(htmlspecialchars($r['role']))?></td>
                                    <td><span class="status-badge badge-pending">Pending Approval</span></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?=$r['id']?>">
                                            <button type="submit" name="action" value="approve" 
                                                class="btn btn-success btn-sm btn-approve"
                                                onclick="return confirm('Approve this registration request?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?=$r['id']?>">
                                            <button type="submit" name="action" value="reject" 
                                                class="btn btn-danger btn-sm btn-reject"
                                                onclick="return confirm('Reject this registration request?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-message show-alert" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert-message');
            if (alert) {
                setTimeout(() => {
                    alert.classList.remove('show-alert');
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>
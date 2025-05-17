<?php
session_start();
include "db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE faculty SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $faculty_id);
        if ($stmt->execute()) {
            $message = "Password updated successfully!";
        } else {
            $message = "Error updating password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .password-container {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6b7280;
    }
    .toggle-password:hover {
      color: #4b5563;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
  <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md border border-gray-100">
    <div class="flex justify-center mb-6">
      <div class="bg-blue-100 p-3 rounded-full">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>
    </div>
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Reset Your Password</h2>
    
    <?php if ($message): ?>
      <div class="mb-4 p-3 rounded-md text-center <?php echo strpos($message, 'successfully') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-medium mb-1">New Password</label>
        <div class="password-container">
          <input type="password" name="new_password" id="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
          <i class="toggle-password fas fa-eye" data-target="new_password"></i>
        </div>
        <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
      </div>
      
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-medium mb-1">Confirm Password</label>
        <div class="password-container">
          <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition" required>
          <i class="toggle-password fas fa-eye" data-target="confirm_password"></i>
        </div>
      </div>
      
      <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
        Update Password
      </button>
    </form>
    
    <div class="mt-6 text-center">
      <a href="faculty_dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Dashboard
      </a>
    </div>
  </div>

  <script>
    document.querySelectorAll('.toggle-password').forEach(item => {
      item.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        if (type === 'password') {
          this.classList.remove('fa-eye-slash');
          this.classList.add('fa-eye');
        } else {
          this.classList.remove('fa-eye');
          this.classList.add('fa-eye-slash');
        }
      });
    });
  </script>
</body>
</html>
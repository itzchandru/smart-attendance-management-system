<?php
session_start();
include "db.php";
error_reporting(E_ALL);  // Report all PHP errors
ini_set('display_errors', 1);  // Display errors on the screen
if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'];

// Step 1: Get the faculty email from the faculty table (since faculty_id is in session)
$query = "SELECT email FROM faculty WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
$facultyInfo = $result->fetch_assoc();
$stmt->close();

if (!$facultyInfo) {
    // No faculty found with this ID
    die("Faculty not found.");
}

$faculty_email = $facultyInfo['email'];

// Step 2: Fetch profile picture and name from registration_request table by matching email
$query2 = "SELECT name, profile_picture FROM registration_requests WHERE email = ?";
$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("s", $faculty_email);
$stmt2->execute();
$result2 = $stmt2->get_result();
$faculty = $result2->fetch_assoc();
$stmt2->close();

if (!$faculty) {
    // Fallback if no registration request found
    $faculty = [
        'name' => $facultyInfo['email'], // or 'User'
        'profile_picture' => 'default-profile.png' // your default image
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ECE Faculty Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary: #3B82F6; /* Blue for ECE */
      --secondary: #10B981; /* Green */
      --accent: #8B5CF6; /* Purple for electronics */
      --dark: #1E293B;
      --light: #F8FAFC;
    }
    
    .ece-gradient-bg {
      background: linear-gradient(135deg, var(--primary), var(--accent));
    }
    
    .card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
      position: relative;
      border: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
    }
    
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    }
    
    .dark-mode {
      background-color: var(--dark);
      color: var(--light);
    }
    
    .dark-mode .card {
      background: rgba(30, 41, 59, 0.95);
      border-color: rgba(255, 255, 255, 0.05);
    }
    
    .welcome-card {
      background: linear-gradient(135deg, rgb(255, 255, 255), rgb(255, 254, 254));
      border: 1px solid rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
    }
    
    .tool-card {
      transition: all 0.3s ease;
    }
    
    .tool-card:hover {
      border-color: var(--primary);
    }
    
    .nav-shadow {
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
    
    .profile-img {
      border: 3px solid white;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;
    }
    
    .profile-img:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }
    
    .department-badge {
      background: linear-gradient(135deg, var(--primary), var(--accent));
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
    }
    
    .circuit-pattern {
      background-image: radial-gradient(circle, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
      background-size: 20px 20px;
    }
  </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300 circuit-pattern">

<!-- Navbar -->
<nav class="ece-gradient-bg text-white py-4 px-6 nav-shadow sticky top-0 z-50">
  <div class="container mx-auto flex justify-between items-center">
    <div class="flex items-center space-x-3">
      <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-md">
        <i class="fas fa-microchip text-xl text-blue-600"></i>
      </div>
      <div>
        <h1 class="text-2xl font-bold">ECE Faculty Portal</h1>
        <div class="department-badge text-xs mt-1">
          <i class="fas fa-bolt mr-1"></i> Electronics & Communication Engineering
        </div>
      </div>
    </div>

    <button id="menuToggle" class="md:hidden p-2 rounded-lg bg-white bg-opacity-20 hover:bg-opacity-30 transition">
      <i class="fas fa-bars"></i>
    </button>

    <div id="navMenu" class="hidden md:flex items-center space-x-6">
      <button onclick="toggleDarkMode()" class="p-2 bg-white bg-opacity-10 rounded-lg hover:bg-opacity-20 transition">
        <i class="fas fa-moon"></i>
      </button>

     <div class="flex items-center space-x-3 group cursor-pointer">
    <img src="<?php echo htmlspecialchars($faculty['profile_picture'] ?? 'default-profile.png'); ?>" alt="Profile" 
         class="w-10 h-10 rounded-full profile-img group-hover:ring-2 group-hover:ring-white">
    <div>
      <p class="font-medium text-sm">Welcome,</p>
      <p class="font-semibold">
        Prof. 
        <?php 
          $firstName = !empty($faculty['name']) ? explode(' ', $faculty['name'])[0] : 'User';
          echo htmlspecialchars($firstName);
        ?>
      </p>
    </div>
</div>


      <a href="index.php" class="flex items-center space-x-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-6 py-4 mt-4 rounded-lg shadow-xl">
    <div class="flex flex-col items-center mb-4">
      <img src="<?php echo $faculty['profile_picture']; ?>" alt="Profile" class="w-16 h-16 rounded-full profile-img mb-2">
      <h3 class="font-semibold text-lg">Prof. <?php echo $faculty['name']; ?></h3>
      <div class="department-badge mt-2">
        <i class="fas fa-microchip mr-1"></i> ECE Department
      </div>
    </div>
    <ul class="space-y-3">
      <li>
        <button onclick="toggleDarkMode()" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
          <i class="fas fa-moon w-5 text-center"></i>
          <span>Dark Mode</span>
        </button>
      </li>
      <li>
        <a href="index.php" class="block flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition text-red-500">
          <i class="fas fa-sign-out-alt w-5 text-center"></i>
          <span>Logout</span>
        </a>
      </li>
    </ul>
  </div>
</nav>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">

  <!-- Welcome Card -->
  <div class="max-w-4xl mx-auto mb-12">
    <div class="card welcome-card p-8 text-center">
      <div class="flex justify-center mb-6">
        <img src="<?php echo $faculty['profile_picture']; ?>" alt="Profile Picture" 
             class="w-28 h-28 rounded-full profile-img border-4 border-white shadow-xl">
      </div>
      <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-2">Welcome, Prof. <?php echo $faculty['name']; ?>!</h2>
      <p class="text-lg text-gray-600 dark:text-gray-300 mb-4">Department of Electronics & Communication Engineering</p>
      <div class="flex justify-center space-x-4">
        <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-100 px-4 py-2 rounded-full text-sm font-medium">
          <i class="fas fa-microchip mr-2"></i> ECE Faculty Portal
        </div>
        <div class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-100 px-4 py-2 rounded-full text-sm font-medium">
          <i class="fas fa-calendar-alt mr-2"></i> <?php echo date('F j, Y'); ?>
        </div>
        <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 px-4 py-2 rounded-full text-sm font-medium">
          <i class="fas fa-clock mr-2"></i> <span id="current-time"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <h2 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white flex items-center border-b border-gray-200 dark:border-gray-700 pb-2">
    <i class="fas fa-bolt text-yellow-500 mr-3"></i> ECE Faculty Quick Actions
  </h2>
  
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
    <!-- Mark Attendance -->
    <div class="card p-6">
      <div class="flex justify-center mb-4">
        <div class="w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
          <i class="fas fa-qrcode text-3xl text-blue-500 dark:text-blue-300"></i>
        </div>
      </div>
      <h3 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2">Mark Attendance</h3>
      <p class="text-center text-gray-500 dark:text-gray-400 mb-6">Easily mark attendance for your students</p>
      <div class="flex justify-center">
        <a href="all_mark_attendance.php" class="flex items-center justify-center space-x-2 bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg transition w-full max-w-xs">
          <i class="fas fa-arrow-right"></i>
          <span>Go to Attendance</span>
        </a>
      </div>
    </div>

    <!-- View Time Table -->
    <div class="card p-6">
      <div class="flex justify-center mb-4">
        <div class="w-16 h-16 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
          <i class="fas fa-calendar-alt text-3xl text-purple-500 dark:text-purple-300"></i>
        </div>
      </div>
      <h3 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2">ECE Time Table</h3>
      <p class="text-center text-gray-500 dark:text-gray-400 mb-6">View your teaching schedule</p>
      <div class="flex justify-center">
        <a href="time_table.php" class="flex items-center justify-center space-x-2 bg-purple-500 hover:bg-purple-600 text-white px-6 py-3 rounded-lg transition w-full max-w-xs">
          <i class="fas fa-arrow-right"></i>
          <span>View Schedule</span>
        </a>
      </div>
    </div>
<!-- Reset Password -->
<div class="card p-6">
  <div class="flex justify-center mb-4">
    <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
      <i class="fas fa-key text-3xl text-red-500 dark:text-red-300"></i>
    </div>
  </div>
  <h3 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2">Reset Password</h3>
  <p class="text-center text-gray-500 dark:text-gray-400 mb-6">Reset your account password securely</p>
  <div class="flex justify-center">
    <a href="reset_password.php" class="flex items-center justify-center space-x-2 bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg transition w-full max-w-xs">
      <i class="fas fa-undo"></i>
      <span>Reset Password</span>
    </a>
  </div>
</div>

    <!-- Lab Management -->
    <div class="card p-6">
      <div class="flex justify-center mb-4">
        <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
          <i class="fas fa-sign-out-alt text-3xl text-red-500 dark:text-red-300"></i>
        </div>
      </div>
      <h3 class="text-xl font-semibold text-center text-gray-800 dark:text-white mb-2">Logout</h3>
      <p class="text-center text-gray-500 dark:text-gray-400 mb-6">Securely log out of your account.</p>
      <div class="flex justify-center">
        <a href="index.php" class="flex items-center justify-center space-x-2 bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg transition w-full max-w-xs">
          <i class="fas fa-sign-out-alt"></i>
          <span>Logout</span>
        </a>
      </div>
    </div>
  </div>

<!-- Footer -->
<footer class="bg-gray-100 dark:bg-gray-800 py-8 border-t border-gray-200 dark:border-gray-700">
  <div class="container mx-auto px-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
      <div class="mb-6 md:mb-0">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
            <i class="fas fa-microchip text-white"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">ECE Department</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">Electronics & Communication Engineering</p>
          </div>
        </div>
      </div>
      <div class="flex space-x-6">
        <a href="#" class="text-gray-500 hover:text-blue-500 dark:hover:text-blue-300 transition">
          <i class="fab fa-facebook-f"></i>
        </a>
        <a href="#" class="text-gray-500 hover:text-blue-400 dark:hover:text-blue-300 transition">
          <i class="fab fa-twitter"></i>
        </a>
        <a href="#" class="text-gray-500 hover:text-purple-500 dark:hover:text-purple-300 transition">
          <i class="fab fa-linkedin-in"></i>
        </a>
        <a href="#" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
          <i class="fab fa-github"></i>
        </a>
      </div>
    </div>
    <div class="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
  <p>© <?php echo date('Y'); ?> Electronics & Communication Engineering Department. All rights reserved.</p>
  <p class="mt-2">Designed and developed by Chandru B, Sundareswaran M, Abdulgani B, and Aravinthkumar K</p>
  <p>Final Year ECE Students | GCE Thanjavur | Batch of 2025</p>
</div>

  </div>
</footer>

<script>
  // Dark mode toggle
  function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    
    // Update icon
    const icon = document.querySelector('[onclick="toggleDarkMode()"] i');
    if (document.body.classList.contains('dark-mode')) {
      icon.classList.replace('fa-moon', 'fa-sun');
    } else {
      icon.classList.replace('fa-sun', 'fa-moon');
    }
  }

  // Check for saved dark mode preference
  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    const icon = document.querySelector('[onclick="toggleDarkMode()"] i');
    if (icon) icon.classList.replace('fa-moon', 'fa-sun');
  }

  // Mobile menu toggle
  document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.toggle('hidden');
  });

  // Update current time
  function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: true 
    });
    document.getElementById('current-time').textContent = timeString;
  }
  
  // Update time immediately and then every minute
  updateCurrentTime();
  setInterval(updateCurrentTime, 60000);
</script>

</body>
</html>
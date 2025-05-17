<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ECE Department - Government College of Engineering, Thanjavur</title>
  
  <!-- Bootstrap & Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Custom Styles -->
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

    .home-container {
background: #70e6e4;
background: linear-gradient(0deg, rgba(112, 230, 228, 0.8) 0%, rgba(45, 139, 253, 0.8) 0%);      
      padding: 30px;
      border-radius: 16px;
      backdrop-filter: blur(10px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
      text-align: center;
      width: 100%;
      max-width: 500px;
      position: relative;
      z-index: 2;
      animation: fadeIn 1.5s ease-in-out;
    }

    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    h1 {
      font-weight: bold;
      color: #fff;
      margin-bottom: 5px;
    }

    h5 {
      color: rgba(255, 255, 255, 0.8);
      margin-bottom: 20px;
    }

    .department-badge {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 14px;
      margin-bottom: 20px;
      display: inline-block;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-custom {
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      font-weight: bold;
      padding: 15px 20px;
      margin: 10px 0;
      width: 100%;
      text-align: left;
      transition: all 0.3s ease;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .btn-custom:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .btn-custom i {
      margin-right: 10px;
      transition: transform 0.3s ease;
    }

    .btn-custom:hover i {
      transform: rotate(360deg);
    }

    .section-title {
      color: #fff;
      font-size: 18px;
      margin-top: 30px;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .section-content {
      font-size: 15px;
      color: rgba(255, 255, 255, 0.8);
    }

    .footer {
      margin-top: 25px;
      color: rgba(255, 255, 255, 0.7);
      font-size: 13px;
    }
  </style>
</head>

<body>
  <!-- Particle Background -->
  <div id="particles-js"></div>

  <!-- Main Content -->
  <div class="home-container">
    <h1>🎓Smart Attendance System</h1>
    <h5>Government College of Engineering, Thanjavur</h5>

    <div class="department-badge">
      <i class="fas fa-microchip"></i> Electronics and Communication Engineering
    </div>

    <p class="lead" style="color: rgba(255, 255, 255, 0.85);">
      Welcome to the ECE Department Attendance Management System.
    </p>

    <!-- Login Buttons -->
    <a href="faculty_login.php" class="btn btn-custom">
      <i class="fas fa-chalkboard-teacher"></i> Faculty Login
    </a>

    <a href="admin_login.php" class="btn btn-custom">
      <i class="fas fa-user-shield"></i> Admin Login
    </a>

    <a href="principal_login.php" class="btn btn-custom">
      <i class="fas fa-user-tie"></i> Principal Login (View Only)
    </a>


    <!-- Footer -->
    <div class="footer">
      &copy; 2025 ECE Department | GCE Thanjavur. All rights reserved.
    </div>
  </div>

  <!-- Particle.js Script -->
  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
  <script>
    particlesJS('particles-js', {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 }},
        color: { value: '#ffffff' },
        shape: {
          type: 'circle',
          polygon: { nb_sides: 5 }
        },
        opacity: { value: 0.5 },
        size: { value: 3, random: true },
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
          out_mode: 'out'
        }
      },
      interactivity: {
        events: {
          onhover: { enable: true, mode: 'repulse' },
          onclick: { enable: true, mode: 'push' }
        },
        modes: {
          repulse: { distance: 200 },
          push: { particles_nb: 4 }
        }
      },
      retina_detect: true
    });
  </script>
</body>
</html>

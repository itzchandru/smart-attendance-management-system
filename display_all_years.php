<?php
$host = "sql103.infinityfree.com";
$user = "if0_38568116";
$pass = "KpXSolJEtejI";
$dbname = "if0_38568116_new_attendance_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the year to display from URL parameter or default to 3rd Year
$yearToDisplay = isset($_GET['year']) ? $_GET['year'] : '3rd Year';
$yearTables = [
    '1st Year' => 'first_attendance',
    '2nd Year' => 'second_attendance',
    '3rd Year' => 'attendance',
    '4th Year' => 'fourth_attendance',
];

$table = $yearTables[$yearToDisplay] ?? 'attendance';
$data = null;

// Get the most recent attendance record
$stmt = $conn->prepare("SELECT * FROM `$table` ORDER BY date DESC, id DESC LIMIT 1");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $latest = $result->fetch_assoc();

        // Format date as DD-MM-YYYY
        $dateObj = new DateTime($latest['date']);
        $formattedDate = $dateObj->format('d-m-Y');
        $latest['formatted_date'] = $formattedDate;
        
        // Get counts for this specific record
        $countQuery = $conn->prepare("
            SELECT 
                SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present,
                SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent,
                COUNT(*) AS total
            FROM `$table` 
            WHERE subject_code=? 
            AND time_slot=? 
            AND date=?
        ");
        
        $countQuery->bind_param(
            "sss", 
            $latest['subject_code'], 
            $latest['time_slot'], 
            $latest['date']
        );
        
        if ($countQuery->execute()) {
            $countResult = $countQuery->get_result();
            $counts = $countResult->fetch_assoc();
            
            $latest['year_label'] = $yearToDisplay;
            $latest['present'] = $counts['present'] ?? 0;
            $latest['absent'] = $counts['absent'] ?? 0;
            $latest['total'] = $counts['total'] ?? 0;
            $latest['percentage'] = $counts['total'] > 0 ? round(($counts['present']/$counts['total'])*100) : 0;
            $data = $latest;
        }
    }
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($data) {
        echo json_encode([
            'present' => $data['present'],
            'absent' => $data['absent'],
            'percentage' => $data['percentage'],
            'timestamp' => time()
        ]);
    } else {
        echo json_encode(['error' => 'No data available']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>ECE Attendance - <?php echo $yearToDisplay; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3a0ca3;
            --primary-light: #4cc9f0;
            --secondary: #7209b7;
            --accent: #4361ee;
            --success: #38b000;
            --danger: #ff4d6d;
            --highlight: #f72585;
            --light: #ffffff;
            --dark: #212529;
            --ece-blue: #1a237e;
            --ece-purple: #4a148c;
        }
        
        * {
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            font-family: 'Poppins', sans-serif;
            color: var(--dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, var(--ece-blue), var(--ece-purple));
            color: white;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(26, 35, 126, 0.5);
            position: relative;
            z-index: 10;
        }
        
        h1 {
            font-size: 6rem;
            margin: 0;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
            letter-spacing: 2px;
            font-weight: 900;
            line-height: 1;
        }
        
        .department {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-light);
            margin: 10px 0;
            text-transform: uppercase;
        }
        
        .year-label {
            font-size: 4rem;
            font-weight: 800;
            color: var(--highlight);
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            position: relative;
            margin-bottom: 80px;
        }
        
        .attendance-card {
            background: white;
            border-radius: 30px;
            width: 95%;
            max-width: 2000px;
            padding: 50px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            border: 10px solid var(--accent);
        }
        
        .subject-info {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .subject-name {
            font-size: 5rem;
            font-weight: 800;
            color: var(--ece-blue);
            margin: 0 0 15px 0;
            line-height: 1.2;
        }
        
        .subject-code {
            font-size: 3.5rem;
            color: white;
            background: linear-gradient(135deg, var(--ece-blue), var(--ece-purple));
            display: inline-block;
            padding: 10px 40px;
            border-radius: 50px;
            margin-bottom: 20px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(26, 35, 126, 0.4);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .detail-item {
            background: rgba(58, 12, 163, 0.05);
            padding: 30px;
            border-radius: 20px;
            border-left: 8px solid var(--accent);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        
        .detail-label {
            font-size: 2.5rem;
            color: var(--ece-purple);
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .detail-value {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--ece-blue);
        }
        
        .attendance-stats {
            display: flex;
            justify-content: space-around;
            margin: 40px 0;
            gap: 30px;
        }
        
        .stat {
            text-align: center;
            padding: 40px;
            border-radius: 25px;
            width: 45%;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .present-stat {
            background: linear-gradient(135deg, rgba(56, 176, 0, 0.15), rgba(56, 176, 0, 0.25));
            border: 4px solid var(--success);
        }
        
        .absent-stat {
            background: linear-gradient(135deg, rgba(255, 77, 109, 0.15), rgba(255, 77, 109, 0.25));
            border: 4px solid var(--danger);
        }
        
        .percentage-stat {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.15), rgba(67, 97, 238, 0.25));
            border: 4px solid var(--accent);
            width: 30%;
        }
        
        .stat-value {
            font-size: 7rem;
            font-weight: 900;
            line-height: 1;
            margin: 15px 0;
        }
        
        .present-stat .stat-value {
            color: var(--success);
        }
        
        .absent-stat .stat-value {
            color: var(--danger);
        }
        
        .percentage-stat .stat-value {
            color: var(--accent);
        }
        
        .stat-label {
            font-size: 2.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .marked-by {
            font-size: 2.2rem;
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: rgba(58, 12, 163, 0.1);
            border-radius: 15px;
            font-weight: 600;
        }
        
        .marked-by strong {
            color: var(--ece-purple);
        }
        
        .last-updated {
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 1.8rem;
            color: #666;
        }
        
        .real-time-badge {
            position: absolute;
            top: 20px;
            right: 30px;
            background: var(--highlight);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 1.8rem;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(247, 37, 133, 0.4);
            animation: pulse 1.5s infinite;
        }
        
        .ece-logo {
            position: absolute;
            top: 30px;
            left: 30px;
            height: 80px;
            opacity: 0.9;
        }
       
        
        .year-selector-bottom {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            z-index: 100;
        }
        
        .year-buttons-bottom {
            display: flex;
            gap: 8px;
            background: rgba(255,255,255,0.9);
            padding: 10px 15px;
            border-radius: 50px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .year-button-bottom {
            background: var(--ece-blue);
            color: white;
            border: none;
            padding: 8px 15px;
            font-size: 1.4rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(26, 35, 126, 0.3);
        }
        
        .year-button-bottom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.4);
        }
        
        .year-button-bottom.active {
            background: var(--highlight);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.4);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @media (max-width: 1200px) {
            h1 { font-size: 5rem; }
            .department { font-size: 2.5rem; }
            .year-label { font-size: 3.5rem; }
            .subject-name { font-size: 4rem; }
            .subject-code { font-size: 3rem; }
            .stat-value { font-size: 6rem; }
        }
        
        @media (max-width: 768px) {
            h1 { font-size: 3.5rem; }
            .department { font-size: 2rem; }
            .year-label { font-size: 2.5rem; }
            .attendance-card { padding: 30px; }
            .subject-name { font-size: 3rem; }
            .subject-code { font-size: 2.2rem; }
            .details-grid { grid-template-columns: 1fr; }
            .attendance-stats { flex-direction: column; }
            .stat, .percentage-stat { width: 100%; }
            .ece-logo { height: 60px; top: 20px; left: 20px; }
            
            .year-buttons-bottom {
                flex-wrap: wrap;
                border-radius: 25px;
                padding: 8px;
            }
            
            .year-button-bottom {
                padding: 6px 12px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRX5Xf-jkCbr5Snhbgb81Z5_3Te60vi2oQ0_A&s" alt="ECE Logo" class="ece-logo">

    <div class="header">
        <h1>ATTENDANCE MONITOR</h1>
        <div class="department">Electronics & Communication Engineering</div>
        <div class="year-label"><?php echo $yearToDisplay; ?></div>
    </div>

    <div class="main-content" id="main-content">
        <?php if ($data): ?>
            <div class="attendance-card">
                <div class="real-time-badge">LIVE UPDATES</div>
                
                <div class="subject-info">
                    <div class="subject-name"><?php echo $data['subject_name']; ?></div>
                    <div class="subject-code"><?php echo $data['subject_code']; ?></div>
                </div>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?php echo $data['formatted_date']; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Time Slot</div>
                        <div class="detail-value"><?php echo $data['time_slot']; ?></div>
                    </div>
                </div>
                
                <div class="attendance-stats">
                    <div class="stat present-stat">
                        <div class="stat-label">Present</div>
                        <div class="stat-value" id="present-count"><?php echo $data['present']; ?></div>
                    </div>
                    
                    <div class="stat percentage-stat">
                        <div class="stat-label">Attendance</div>
                        <div class="stat-value" id="percentage"><?php echo $data['percentage']; ?>%</div>
                    </div>
                    
                    <div class="stat absent-stat">
                        <div class="stat-label">Absent</div>
                        <div class="stat-value" id="absent-count"><?php echo $data['absent']; ?></div>
                    </div>
                </div>
                
                <div class="marked-by">
                    <strong>Marked By:</strong> <?php echo $data['marked_by']; ?>
                </div>
                
               
            </div>
        <?php else: ?>
            <div class="attendance-card">
                <div class="subject-info">
                    <div class="subject-name">No Attendance Data</div>
                    <div class="subject-code">Waiting for updates...</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
  <div class="last-updated" id="last-updated">
                    Last updated: <?php echo date('H:i:s'); ?>
                </div>
    <div class="year-selector-bottom">
        <div class="year-buttons-bottom">
            <?php foreach ($yearTables as $label => $table): ?>
                <a href="?year=<?php echo urlencode($label); ?>" 
                   class="year-button-bottom <?php echo $yearToDisplay === $label ? 'active' : ''; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Auto-refresh system
        document.addEventListener('DOMContentLoaded', function() {
            let refreshInterval = 5000; // 5 seconds
            let lastUpdateTime = Date.now();
            let errorCount = 0;
            let refreshTimer;
            
            // Function to update the display
            function updateDisplay(data) {
                if (data.present !== undefined) {
                    // Animate number changes
                    animateValue('present-count', parseInt(document.getElementById('present-count').textContent), data.present, 1000);
                    animateValue('absent-count', parseInt(document.getElementById('absent-count').textContent), data.absent, 1000);
                    animateValue('percentage', parseInt(document.getElementById('percentage').textContent), data.percentage, 1000);
                    
                    // Update last updated time
                    const now = new Date();
                    document.getElementById('last-updated').textContent = 'Last updated: ' + now.toLocaleTimeString();
                    
                    // Visual feedback
                    if (data.present > parseInt(document.getElementById('present-count').textContent)) {
                        document.getElementById('present-count').parentElement.style.backgroundColor = 'rgba(56, 176, 0, 0.4)';
                        setTimeout(() => {
                            document.getElementById('present-count').parentElement.style.backgroundColor = '';
                        }, 1000);
                    }
                    
                    errorCount = 0; // Reset error count on success
                }
            }
            
            // Function to fetch latest data
            function fetchLatestData() {
                fetch(window.location.href + '&ajax=1')
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                        updateDisplay(data);
                        lastUpdateTime = Date.now();
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        errorCount++;
                        
                        // If multiple errors occur, do full page refresh
                        if (errorCount >= 3) {
                            location.reload();
                        }
                    });
            }
            
            // Number animation function
            function animateValue(id, start, end, duration) {
                const obj = document.getElementById(id);
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    const value = Math.floor(progress * (end - start) + start);
                    obj.innerHTML = id === 'percentage' ? value + '%' : value;
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    }
                };
                window.requestAnimationFrame(step);
            }
            
            // Start the auto-refresh system
            function startAutoRefresh() {
                // Initial fetch
                fetchLatestData();
                
                // Set up regular refresh
                refreshTimer = setInterval(() => {
                    const timeSinceLastUpdate = Date.now() - lastUpdateTime;
                    if (timeSinceLastUpdate >= refreshInterval) {
                        fetchLatestData();
                    }
                }, refreshInterval);
                
                // Also refresh when window gains focus
                window.addEventListener('focus', fetchLatestData);
            }
            
            // Initialize everything
            startAutoRefresh();
            
            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                clearInterval(refreshTimer);
            });
        });
    </script>
</body>
</html>
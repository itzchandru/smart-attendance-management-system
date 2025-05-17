<?php 
// Database Connection
session_start();
include "db.php"; // Include database connection

// Fetch unique time slots for table headers
$timeSlotsQuery = "SELECT DISTINCT time_slot FROM timetable ORDER BY time_slot";
$timeSlotsResult = $conn->query($timeSlotsQuery);
$timeSlots = [];
while ($row = $timeSlotsResult->fetch_assoc()) {
    $timeSlots[] = $row['time_slot'];
}

// Separate morning and evening slots
$morningSlots = array_filter($timeSlots, function($slot) {
    return strpos($slot, 'AM') !== false; // Assuming morning slots contain 'AM'
});
$eveningSlots = array_filter($timeSlots, function($slot) {
    return strpos($slot, 'PM') !== false; // Assuming evening slots contain 'PM'
});

// Remove the last column (evening slots)
array_pop($eveningSlots);

// Fetch unique days
$daysQuery = "SELECT DISTINCT day FROM timetable ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$daysResult = $conn->query($daysQuery);
$days = [];
while ($row = $daysResult->fetch_assoc()) {
    $days[] = $row['day'];
}

// Fetch timetable data
$timetableData = [];
$dataQuery = "SELECT * FROM timetable";
$dataResult = $conn->query($dataQuery);
while ($row = $dataResult->fetch_assoc()) {
    $timetableData[$row['day']][$row['time_slot']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            flex-direction: column;
        }
        .container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            width: 95%;
            max-width: 1200px;
            animation: fadeIn 1s ease-in-out;
            overflow-x: auto; /* Enable horizontal scrolling on small screens */
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
            font-weight: bold;
            font-size: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            overflow: hidden;
            animation: fadeInUp 1s ease-in-out;
            border: 2px solid powderblue;
            min-width: 600px; /* Minimum width for the table */
        }
        th, td {
            text-align: center;
            padding: 12px;
            border: 1px solid powderblue;
            color: #fff;
        }
        th {
            background: rgba(52, 58, 64, 0.9);
            border-bottom: 2px solid powderblue;
        }
        td {
            background: rgba(255, 255, 255, 0.1);
        }
        tr:hover {
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 20px 0;
            background: #ff5f6d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #ff3c4a;
        }

        /* Mobile-friendly adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            h2 {
                font-size: 1.5rem;
            }
            table {
                font-size: 14px;
            }
            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="container">
        <h2>College Timetable</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Day / Time</th>
                    <?php 
                    // Morning slots
                    foreach ($morningSlots as $slot) { 
                        echo "<th>$slot</th>"; 
                    }
                    // Evening slots (excluding the last column)
                    foreach ($eveningSlots as $slot) { 
                        echo "<th>$slot</th>"; 
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($days as $day) {
                    echo "<tr><th>$day</th>";
                    // Morning slots
                    foreach ($morningSlots as $slot) {
                        if (isset($timetableData[$day][$slot])) {
                            $data = $timetableData[$day][$slot];
                            echo "<td>{$data['subject_name']}<br><small>{$data['faculty_name']}</small></td>";
                        } else {
                            echo "<td>-</td>"; // Empty slot
                        }
                    }
                    // Evening slots (excluding the last column)
                    foreach ($eveningSlots as $slot) {
                        if (isset($timetableData[$day][$slot])) {
                            $data = $timetableData[$day][$slot];
                            echo "<td>{$data['subject_name']}<br><small>{$data['faculty_name']}</small></td>";
                        } else {
                            echo "<td>-</td>"; // Empty slot
                        }
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
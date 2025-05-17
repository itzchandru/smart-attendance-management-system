<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['principal_id'])) {
    header("Location: principal_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Year to View Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same styling as your existing design */
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --light: #f8f9fc;
            --border: #e3e6f0;
        }

        body {
            background-color: var(--light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .attendance-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-align: center;
            padding: 1.25rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 2rem;
            background: white;
        }

        .year-icon {
            font-size: 2.25rem;
            color: var(--primary);
            margin-bottom: 1.25rem;
        }

        .form-select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 500;
            width: 100%;
        }

        .btn-outline {
            color: var(--primary);
            border: 1px solid var(--primary);
            margin-top: 1rem;
            background: transparent;
        }
    </style>
</head>
<body>

<div class="attendance-card">
    <div class="card-header">
        <i class="fas fa-eye me-2"></i> View Analytics by Year
    </div>
    <div class="card-body text-center">
        <div class="year-icon">
            <i class="fas fa-users"></i>
        </div>

        <form id="yearForm" method="get">
            <div class="mb-4">
                <select name="year" id="year" class="form-select" required>
                    <option value="" selected disabled>Select Academic Year</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">Final Year</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-arrow-right me-2"></i> Continue
            </button>

            <a href="principal_alt_dashboard.php" class="btn btn-outline">
                <i class="fas fa-chevron-left me-2"></i> Back to Dashboard
            </a>
        </form>
    </div>
</div>

<script>
    document.getElementById("yearForm").addEventListener("submit", function(event) {
        event.preventDefault();

        const selectedYear = document.getElementById("year").value;
        let targetPage = "";

        switch (selectedYear) {
            case "1":
                targetPage = "1st_principal_analytics.php";
                break;
            case "2":
                targetPage = "2nd_principal_analytics.php";
                break;
            case "3":
                targetPage = "principal_analytics.php";
                break;
            case "4":
                targetPage = "4th_principal_analytics.php";
                break;
            default:
                alert("Please select a valid year.");
                return;
        }

        window.location.href = targetPage + "?year=" + selectedYear;
    });
</script>

</body>
</html>

<?php
session_start();

// --- AUTH CHECK ---
//Ensures the judge is the one who who actually logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'judge') {
    header("Location: http://localhost/court_system/assets/stuff/login.php");
    exit;
}

$judge_id = $_SESSION['username'];
$username = $_SESSION['username'];

//DATABASE CONNECTION
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


// --- GET JUDGE NAME ---
$stmt = $conn->prepare("SELECT username FROM judges WHERE judge_id = ?");
$stmt->bind_param("i", $judge_id);
$stmt->execute();
$judge = $stmt->get_result()->fetch_assoc();
$judge_name = $judge['username'];

// --- CASE FILTERING ---
$filter = "";
$filter_value = "";

if (isset($_GET['status']) && $_GET['status'] !== "") {
    $filter = " AND status = ?";
    $filter_value = $_GET['status'];
}

// Handle filter value for "concluded" which is likely stored as "completed" in the database
$db_filter_value = $filter_value === "concluded" ? "completed" : $filter_value;


// --- FETCH CASES ASSIGNED TO THIS JUDGE ---
if ($filter) {
    $query = $conn->prepare("SELECT * FROM cases WHERE judge_id = ? $filter ORDER BY case_date DESC");
    $query->bind_param("is", $judge_id, $db_filter_value);
} else {
    $query = $conn->prepare("SELECT * FROM cases WHERE judge_id = ? ORDER BY case_date DESC");
    $query->bind_param("i", $judge_id);
}

$query->execute();
$cases = $query->get_result();

// --- TODAY’S CAUSE LIST ---
$today = date("Y-m-d");
$cl = $conn->prepare("
    SELECT * FROM cases 
    WHERE judge_id = ? AND DATE(case_date) = ?
    ORDER BY id DESC
");
$cl->bind_param("is", $judge_id, $today);
$cl->execute();
$causelist = $cl->get_result();

// --- COUNTERS ---
function count_cases($conn, $judge_id, $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM cases WHERE judge_id = ? AND status = ?");
    $stmt->bind_param("is", $judge_id, $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['c'];
}

$pending_count = count_cases($conn, $judge_id, "pending");
$completed_count = count_cases($conn, $judge_id, "completed");
$adjourned_count = count_cases($conn, $judge_id, "adjourned");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Judge Dashboard</title>
    <style>
        /* --- INSTITUTIONAL THEME COLORS --- */
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-light-bg: #f4f7f6;
            --color-white: #ffffff;
            --color-red: #c0392b;
            --color-green: #27ae60;
            --color-orange: #f39c12;
        }

        /* --- GENERAL STYLES & BACKGROUND IMAGE --- */
        body {
            font-family: 'Times New Roman', Times, serif; 
            margin: 0;
            padding: 0;
            color: var(--color-text);
            
            /* Background Image Application */
            background-image: 
                /* White Overlay (Adjusted to 60% opacity) */
                linear-gradient(rgba(255, 255, 255, 0.6), rgba(255, 255, 255, 0.6)), 
                /* Judiciary Building Image */
                url("/court_system/assets/images/building.jpg"); 

            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .container {
            max-width: 1400px;
            margin: 20px auto 40px auto;
            padding: 0 20px;
        }

        /* --- BANNER STYLES (Adjusted) --- */
        .banner {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 15px 50px;
            display: flex;
            align-items: center;
            justify-content: space-between; /* Re-enabled to space Logo and Logout */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
        }

        .logo-container {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-shrink: 0; 
        }

        .judiciary-logo {
            height: 60px;
            width: auto;
        }

        .banner-title {
            /* Now centered using auto margins */
            flex-grow: 1; 
            text-align: center; 
            /* Remove fixed margin that was used for optical centering */
            margin: 0 auto;
        }
        
        .banner-title h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }
        
        /* Logout Button Style */
        .logout-link {
            text-decoration: none;
            color: green;
            background-color: #cbaa07ff;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .logout-link:hover {
            background-color: #882417; /* Darker red on hover */
        }

        /* --- HEADINGS --- */
        .welcome-heading {
            color: var(--color-primary);
            font-size: 2.2rem;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .section-heading {
            color: var(--color-primary);
            font-size: 1.6rem;
            border-bottom: 2px solid var(--color-secondary);
            padding-bottom: 8px;
            margin-top: 40px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        /* --- COUNTERS --- */
        .counters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .counter-item {
            background: var(--color-white);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            font-size: 1.1rem;
            color: var(--color-text);
            border-left: 5px solid;
        }

        .counter-item strong {
            font-size: 1.5rem;
            display: block;
            margin-top: 5px;
        }

        .counter-item.pending { border-left-color: var(--color-orange); }
        .counter-item.adjourned { border-left-color: var(--color-red); }
        .counter-item.completed { border-left-color: var(--color-green); }

        /* --- FILTER BOX --- */
        .filter-box {
            margin-bottom: 30px;
            padding: 15px;
            background: #e9ecef;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .filter-box form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-box select, .filter-box button {
            padding: 8px 12px;
            border-radius: 4px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 1rem;
        }

        .filter-box button {
            background-color: var(--color-primary);
            color: var(--color-white);
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .filter-box button:hover {
            background-color: #053315;
        }

        /* --- TABLE STYLES --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: var(--color-white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        table thead tr {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        table th {
            background-color: var(--color-primary); 
            color: var(--color-white);
            text-align: left;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            max-width: 200px;
        }

        table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Status Colors in Table */
        td.status-pending { color: var(--color-orange); font-weight: bold; }
        td.status-adjourned { color: var(--color-red); font-weight: bold; }
        td.status-completed, td.status-concluded { color: var(--color-green); font-weight: bold; }

        /* Truncate long cell content for readability */
        table td:nth-child(3) { /* Description */
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<header class="banner">
    <div class="logo-container">
        <img src="/court_system/assets/images/logo.png" alt="Logo" class="judiciary-logo">
    </div>
    
    <div class="banner-title">
        <h1>JUDGE CASE MANAGEMENT DASHBOARD</h1>
    </div>
    
    <a href="/court_system/assets/stuff/logout.php" class="logout-link">Logout</a>
</header>

<div class="container">
    <h1 class="welcome-heading">Welcome, Judge <?= htmlspecialchars($judge_name) ?></h1>

    <div class="counters">
        <div class="counter-item pending">Pending: <strong><?= $pending_count ?></strong></div>
        <div class="counter-item adjourned">Adjourned: <strong><?= $adjourned_count ?></strong></div>
        <div class="counter-item completed">Completed: <strong><?= $completed_count ?></strong></div>
    </div>

    <div class="filter-box">
        <form method="GET">
            <label>Filter by Status:</label>
            <select name="status">
                <option value="">All</option>
                <option value="pending"   <?= ($filter_value === "pending") ? "selected" : "" ?>>Pending</option>
                <option value="adjourned" <?= ($filter_value === "adjourned") ? "selected" : "" ?>>Adjourned</option>
                <option value="concluded" <?= ($filter_value === "concluded") ? "selected" : "" ?>>Concluded</option>
            </select>
            <button type="submit">Apply Filter</button>
        </form>
    </div>

    <h2 class="section-heading">Today's Cause List (<?= $today ?>)</h2>
    <table class="cause-list">
    <thead>
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Description</th>
        <th>Attorney</th>
        <th>Initiator</th>
        <th>Defendant</th>
        <th>Date</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php while ($row = $causelist->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['case_type']) ?></td>
        <td><?= $row['description'] ?></td>
        <td><?= $row['attorney'] ?></td>
        <td><?= $row['initiator'] ?></td>
        <td><?= $row['defendant'] ?></td>
        <td><?= $row['case_date'] ?></td>
        <td class="status-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    </table>

    <h2 class="section-heading">All Assigned Cases</h2>
    <table class="case-list">
    <thead>
    <tr>
        <th>ID</th>
        <th>Type</th>
        <th>Description</th>
        <th>Attorney</th>
        <th>Initiator</th>
        <th>Defendant</th>
        <th>Date</th>
        <th>Status</th>
    </tr>
    </thead>
    <tbody>
    <?php while ($c = $cases->fetch_assoc()): ?>
    <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['case_type']) ?></td>
        <td><?= $c['description'] ?></td>
        <td><?= $c['attorney'] ?></td>
        <td><?= $c['initiator'] ?></td>
        <td><?= $c['defendant'] ?></td>
        <td><?= $c['case_date'] ?></td>
        <td class="status-<?= strtolower($c['status']) ?>"><?= $c['status'] ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody>
    </table>

</div>
</body>
</html>
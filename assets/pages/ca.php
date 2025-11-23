<?php
//SESSION START
session_start();
//Ensures the court assistant is the one who actually logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'courtAssistant') {
    header("Location: http://localhost/court_system/assets/stuff/login.php");
    exit;
}

//FILTERS

$assistant_id = $_SESSION['username'];
$username = $_SESSION['username'];

//DATABASE CONNECTION
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

//Case Counter
// NOTE: Queries remain as direct SQL strings, as requested.
$count_pending = $conn->query("SELECT COUNT(*) AS c FROM cases WHERE assistant_id='$assistant_id' AND status='pending'")->fetch_assoc()['c'];
$count_completed = $conn->query("SELECT COUNT(*) AS c FROM cases WHERE assistant_id='$assistant_id' AND status='completed'")->fetch_assoc()['c'];
$count_adjourned = $conn->query("SELECT COUNT(*) AS c FROM cases WHERE assistant_id='$assistant_id' AND status='adjourned'")->fetch_assoc()['c'];

$cases = $conn->query("SELECT * FROM cases WHERE assistant_id='$assistant_id'");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Court Assistant Dashboard</title>
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
                /* White Overlay (60% opacity) */
                linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.4)), 
                /* Judiciary Building Image path assumed based on prior context */
                url("/court_system/assets/images/mallet.png"); 

            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .container {
            max-width: 1400px;
            margin: 20px auto 40px auto;
            padding: 0 20px;
        }

        /* --- BANNER STYLES --- */
        .banner {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 15px 50px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            flex-grow: 1; 
            text-align: center; 
            margin: 0 auto;
        }
        
        .banner-title h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }
        
        .action-buttons-header {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        /* --- GENERAL BUTTONS/LINKS --- */
        .btn, td a {
            text-decoration: none;
            color: var(--color-white);
            background-color: var(--color-primary);
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9rem;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block; /* Allows padding and margin on links */
        }

        .btn:hover, td a:hover {
            background-color: #053315;
        }
        
        .btn.btn-logout {
            text-decoration: none;
            color: green;
            background-color: #cbaa07ff;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .btn.btn-logout:hover {
            background-color: #a02012;
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
            min-width: 150px;
            text-align: center;
        }

        .counter-item strong {
            font-size: 1.5rem;
            display: block;
            margin-top: 5px;
        }

        .counter-item.pending { border-left-color: var(--color-orange); }
        .counter-item.adjourned { border-left-color: var(--color-red); }
        .counter-item.completed { border-left-color: var(--color-green); }

        /* --- TABLE STYLES --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: var(--color-white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
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
        }

        table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        td.status-pending { color: var(--color-orange); font-weight: bold; }
        td.status-adjourned { color: var(--color-red); font-weight: bold; }
        td.status-completed { color: var(--color-green); font-weight: bold; }
        
        /* Styling for the original external links block */
        .external-links {
            padding: 15px;
            background: var(--color-light-bg);
            border-radius: 6px;
            margin-top: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
    </style>
</head>
<body>

<header class="banner">
    <div class="logo-container">
        <img src="/court_system/assets/images/logo.png" alt="Logo" class="judiciary-logo">
    </div>
    
    <div class="banner-title">
        <h1>COURT ASSISTANT DASHBOARD</h1>
    </div>
    
    <div class="action-buttons-header">
        <a href="http://localhost/court_system/assets/stuff/logout.php" class="btn btn-logout">Logout</a>
    </div>
</header>

<div class="container">

    <h1 class="welcome-heading">Welcome, Assistant <?php echo htmlspecialchars($username); ?></h1>

    <h2 class="section-heading">Case Summary</h2>
    <div class="counters">
        <div class="counter-item pending">Pending: <strong><?php echo $count_pending; ?></strong></div>
        <div class="counter-item adjourned">Adjourned: <strong><?php echo $count_adjourned; ?></strong></div>
        <div class="counter-item completed">Completed: <strong><?php echo $count_completed; ?></strong></div>
    </div>
    
    <h2 class="section-heading">Quick Actions</h2>
    <div class="external-links">
        <a href=" http://localhost/court_system/assets/stuff/add_case.php" class="btn">Add Case</a>
        <a href="https://localhost/court_system/assets/stuff/causelist.php" class="btn">View Cause List</a>
        <a href=" http://localhost/court_system/assets/stuff/edit_case.php" class="btn"> Edit Case Status</a>
    </div>


    <h2 class="section-heading">Your Assigned Cases</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Court</th>
                <th>Type</th>
                <th>Initiator</th>
                <th>Defendant</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $cases->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['court']; ?></td>
            <td><?php echo $row['case_type']; ?></td>
            <td><?php echo $row['initiator']; ?></td>
            <td><?php echo $row['defendant']; ?></td>
            <td><?php echo $row['case_date']; ?></td>
            <td class="status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></td>
            <td>
                <a href=" http://localhost/court_system/assets/stuff/edit_case.php?case_id=<?php echo $row['id']; ?>" style="background-color: var(--color-secondary); padding: 5px 10px;">Edit</a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>
</body>
</html>
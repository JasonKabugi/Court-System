<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: https://localhost/court_system/assets/stuff/login.php");
    exit;
}
//Database Connection
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "court_db";

$conn = new mysqli("localhost", "root", "", "court_db");
if ($conn->connect_error) die("DB connection error");

//Delete Users
if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    $conn->query("DELETE FROM users WHERE id = $uid");
    header("Location: admin.php");
    exit;
}

//Get Users
$users = $conn->query("SELECT id, username, role FROM users ORDER BY id ASC");

//Judge Case Total
$judge_sql = "
    SELECT 
        j.username AS judge_username,
        SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN c.status = 'adjourned' THEN 1 ELSE 0 END) AS adjourned,
        SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) AS completed
    FROM judges j
    LEFT JOIN cases c ON c.judge_id = j.username
    GROUP BY j.username
    ORDER BY j.username ASC
";
$judge_cases = $conn->query($judge_sql);

//Court Assistant Case Total
$assistant_sql = "
    SELECT 
        a.username AS assistant_username,
        SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN c.status = 'adjourned' THEN 1 ELSE 0 END) AS adjourned,
        SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) AS completed
    FROM courtAssistant a
    LEFT JOIN cases c ON c.assistant_id = a.username
    GROUP BY a.username
    ORDER BY a.username ASC
";
$assistant_cases = $conn->query($assistant_sql);

//Today's CauseList
$today = date("d-m-y");
$causelist = $conn->query("
    SELECT c.id, c.case_type, c.attorney, c.initiator, c.defendant, c.status, c.case_date, j.username AS judge_name
    FROM cases c
    LEFT JOIN judges j ON c.judge_id = j.username
    WHERE c.case_date = '$today'
    ORDER BY c.case_time ASC, c.id ASC
");


?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <style>
        /* --- INSTITUTIONAL THEME COLORS --- */
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-light-bg: #f4f7f6;
            --color-white: #ffffff;
            --color-red: #c0392b; /* For Delete/Adjourned */
            --color-green: #27ae60; /* For Register/Completed */
            --color-orange: #f39c12; /* For Pending */
        }

        /* --- GENERAL STYLES & BACKGROUND IMAGE --- */
        body {
            font-family: 'Times New Roman', Times, serif; 
            margin: 0;
            padding: 0;
            color: var(--color-text);
            
            /* Background Image Application */
            background-image: 
                /* White Overlay (40% opacity) */
                linear-gradient(rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.4)), 
                /* Background Image Path */
                url("/court_system/assets/images/tech.jpg"); 

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
            font-size: 1.5rem; /* Consistent size */
            margin: 0;
            font-weight: 700;
        }
        
        /* --- HEADINGS --- */
        .welcome-heading { /* Admin Panel Title */
            color: var(--color-primary);
            font-size: 2.2rem;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        h2 { /* Section Headings */
            color: var(--color-primary);
            font-size: 1.6rem;
            border-bottom: 2px solid var(--color-secondary);
            padding-bottom: 8px;
            margin-top: 40px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        /* --- GENERAL BUTTONS/LINKS --- */
        .btn {
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
            display: inline-block;
        }

        .btn:hover {
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

        .btn.register {
            background-color: var(--color-green);
            margin-bottom: 20px;
        }
        .btn.register:hover {
            background-color: #1e8449;
        }

        /* --- TABLE STYLES --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: var(--color-white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            table-layout: auto;
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
            vertical-align: middle;
        }

        table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Specific styles for action links within table cells */
        table td a {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 3px;
            white-space: nowrap;
            display: inline-block;
            text-decoration: none;
            color: var(--color-white);
        }
        
        /* Reset Password Link */
        table td a:first-child {
            background-color: var(--color-primary); 
        }
        table td a:first-child:hover {
            background-color: #053315; 
        }

        /* Delete Link */
        table td a:last-child {
            background-color: var(--color-red);
        }
        table td a:last-child:hover {
             background-color: #a02012;
        }

        /* Status Colors in Summary/Cause List */
        .status-pending { color: var(--color-orange); font-weight: bold; }
        .status-adjourned { color: var(--color-red); font-weight: bold; }
        .status-completed { color: var(--color-green); font-weight: bold; }
        
        /* User Table specific widths */
        .users-table th:nth-child(1), .users-table td:nth-child(1) { width: 5%; }
        .users-table th:nth-child(3), .users-table td:nth-child(3) { width: 15%; } 
        .users-table th:nth-child(4), .users-table td:nth-child(4) { width: 30%; }
        
        .p-text {
            margin: 10px 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<header class="banner">
    <div class="logo-container">
        <img src="/court_system/assets/images/logo.png" alt="Logo" class="judiciary-logo">
    </div>
    
    <div class="banner-title">
        <h1>SYSTEM ADMINISTRATION PANEL</h1>
    </div>
    
    <div class="action-buttons-header">
        <a href="https://localhost/court_system/assets/stuff/logout.php" class="btn btn-logout">Logout</a>
    </div>
</header>
<div class="container">

<h1 class="welcome-heading">Welcome Admin</h1>

<h2>Register New User</h2>
<a href="https://localhost/court_system/assets/stuff/register.php" class="btn register"> Register a New User</a>

<h2>User Management</h2>
<table class="users-table">
<tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr>
<?php while ($u = $users->fetch_assoc()): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= htmlspecialchars($u['username']) ?></td>
    <td><?= $u['role'] ?></td>
    <td>
        <a href="reset_password.php?id=<?= $u['id'] ?>">Reset Password</a>
        &nbsp;|&nbsp;
        <a href="admin.php?delete_user=<?= $u['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

<h2>Judge Case Summary</h2>
<table>
<tr><th>Judge</th><th>Pending</th><th>Adjourned</th><th>Completed</th></tr>
<?php while ($j = $judge_cases->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($j['judge_username']) ?></td>
    <td class="status-pending"><?= intval($j['pending']) ?></td>
    <td class="status-adjourned"><?= intval($j['adjourned']) ?></td>
    <td class="status-completed"><?= intval($j['completed']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Assistant Case Summary</h2>
<table>
<tr><th>Assistant</th><th>Pending</th><th>Adjourned</th><th>Completed</th></tr>
<?php while ($a = $assistant_cases->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($a['assistant_username']) ?></td>
    <td class="status-pending"><?= intval($a['pending']) ?></td>
    <td class="status-adjourned"><?= intval($a['adjourned']) ?></td>
    <td class="status-completed"><?= intval($a['completed']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Today's Cause List (<?= $today ?>)</h2>
<?php if ($causelist->num_rows === 0): ?>
<p class="p-text">No cases listed for today.</p>
<?php else: ?>
<table>
<tr><th>ID</th><th>Type</th><th>Attorney</th><th>Initiator</th><th>Defendant</th><th>Status</th><th>Date</th><th>Judge</th></tr>
<?php while ($c = $causelist->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['case_type']) ?></td>
    <td><?= htmlspecialchars($c['attorney']) ?></td>
    <td><?= htmlspecialchars($c['initiator']) ?></td>
    <td><?= htmlspecialchars($c['defendant']) ?></td>
    <td class="status-<?= strtolower($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></td>
    <td><?= $c['case_date'] ?></td>
    <td><?= htmlspecialchars($c['judge_name']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php endif; ?>

</div>
</body>
</html>
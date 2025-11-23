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
<body>

<h1>Admin Panel</h1>

<h2>Register New User</h2>
<a href="https://localhost/court_system/assets/stuff/register.php">Register a New User</a>

<h2>Users</h2>
<table border="1" cellpadding="6">
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
<table border="1" cellpadding="6">
<tr><th>Judge</th><th>Pending</th><th>Adjourned</th><th>Completed</th></tr>
<?php while ($j = $judge_cases->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($j['judge_username']) ?></td>
    <td><?= intval($j['pending']) ?></td>
    <td><?= intval($j['adjourned']) ?></td>
    <td><?= intval($j['completed']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Assistant Case Summary</h2>
<table border="1" cellpadding="6">
<tr><th>Assistant</th><th>Pending</th><th>Adjourned</th><th>Completed</th></tr>
<?php while ($a = $assistant_cases->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($a['assistant_username']) ?></td>
    <td><?= intval($a['pending']) ?></td>
    <td><?= intval($a['adjourned']) ?></td>
    <td><?= intval($a['completed']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>Today's Cause List (<?= $today ?>)</h2>
<?php if ($causelist->num_rows === 0): ?>
<p>No cases listed for today.</p>
<?php else: ?>
<table border="1" cellpadding="6">
<tr><th>ID</th><th>Type</th><th>Attorney</th><th>Initiator</th><th>Defendant</th><th>Status</th><th>Date</th><th>Judge</th></tr>
<?php while ($c = $causelist->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['case_type']) ?></td>
    <td><?= htmlspecialchars($c['attorney']) ?></td>
    <td><?= htmlspecialchars($c['initiator']) ?></td>
    <td><?= htmlspecialchars($c['defendant']) ?></td>
    <td><?= htmlspecialchars($c['status']) ?></td>
    <td><?= $c['case_date'] ?></td>
    <td><?= htmlspecialchars($c['judge_name']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php endif; ?>


</body>
</html>

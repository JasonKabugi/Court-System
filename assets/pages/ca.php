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
$count_pending = $conn->query("SELECT COUNT(*) AS c FROM cases WHERE assistant_id='$assistant_id' AND status='pending'")->fetch_assoc()['c'];
$count_completed = $conn->query("SELECT COUNT(*) AS c FROM cases WHERE assistant_id='$assistant_id' AND status='completed'")->fetch_assoc()['c'];
$count_adjourned = $conn->query("SELECT COUNT(*) AS c FROM cases WHERE assistant_id='$assistant_id' AND status='adjourned'")->fetch_assoc()['c'];

$cases = $conn->query("SELECT * FROM cases WHERE assistant_id='$assistant_id'");
?>

<!--DISPLAY-->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Court Assistant Dashboard</title>
</head>
<body>

<h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>

<h3>Your Case Summary</h3>
<ul>
    <li>Pending cases: <?php echo $count_pending; ?></li>
    <li>Adjourned cases: <?php echo $count_adjourned; ?></li>
    <li>Completed cases: <?php echo $count_completed; ?></li>
</ul>

<h3>Your Cases</h3>
<table border="1" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Court</th>
        <th>Type</th>
        <th>Initiator</th>
        <th>Defendant</th>
        <th>Date</th>
        <th>Status</th>
    </tr>

    <?php while ($row = $cases->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['court']; ?></td>
        <td><?php echo $row['case_type']; ?></td>
        <td><?php echo $row['initiator']; ?></td>
        <td><?php echo $row['defendant']; ?></td>
        <td><?php echo $row['case_date']; ?></td>
        <td><?php echo $row['status']; ?></td>
    </tr>
    <?php endwhile; ?>

</table>
<td>
  <!--EXTERNAL LINKS-->
    <a href=" http://localhost/court_system/assets/stuff/edit_case.php">Edit Case</a>

    <a href="https://localhost/court_system/assets/stuff/causelist.php" >Cause List</a>

    <a href=" http://localhost/court_system/assets/stuff/add_case.php">Add Case</a>



</td>
</body>
</html>

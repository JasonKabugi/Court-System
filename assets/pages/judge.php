<?php
session_start();

// --- AUTH CHECK ---
//Ensures the judge is the one who actually logged in
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

// --- FETCH CASES ASSIGNED TO THIS JUDGE ---
if ($filter) {
    $query = $conn->prepare("SELECT * FROM cases WHERE judge_id = ? $filter ORDER BY case_date DESC");
    $query->bind_param("is", $judge_id, $filter_value);
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
    <link rel="stylesheet" href="style.css" />
<body>

<h1>Welcome, Judge <?= htmlspecialchars($judge_name) ?></h1>

<div class="counters">
    <div>Pending: <strong><?= $pending_count ?></strong></div>
    <div>Adjourned: <strong><?= $adjourned_count ?></strong></div>
    <div>Completed: <strong><?= $completed_count ?></strong></div>
</div>

<!-- FILTER -->
<div class="filter-box">
    <form method="GET">
        <label>Filter by Status:</label>
        <select name="status">
            <option value="">All</option>
            <option value="pending"   <?= ($filter_value === "pending") ? "selected" : "" ?>>Pending</option>
            <option value="adjourned"   <?= ($filter_value === "adjourned") ? "selected" : "" ?>>Adjourned</option>
            <option value="concluded" <?= ($filter_value === "concluded") ? "selected" : "" ?>>Concluded</option>
        </select>
        <button type="submit">Apply</button>
    </form>
</div>

<!-- CAUSE LIST -->
<h2>Today's Cause List (<?= $today ?>)</h2>
<table>
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

<?php while ($row = $causelist->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['case_type']) ?></td>
    <td><?= $row['description'] ?></td>
    <td><?= $row['attorney'] ?></td>
    <td><?= $row['initiator'] ?></td>
    <td><?= $row['defendant'] ?></td>
    <td><?= $row['case_date'] ?></td>
    <td><?= $row['status'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<!-- CASE LIST -->
<h2>All Assigned Cases</h2>
<table>
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

<?php while ($c = $cases->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= htmlspecialchars($c['case_type']) ?></td>
    <td><?= $c['description'] ?></td>
    <td><?= $c['attorney'] ?></td>
    <td><?= $c['initiator'] ?></td>
    <td><?= $c['defendant'] ?></td>
    <td><?= $c['case_date'] ?></td>
    <td><?= $c['status'] ?></td>
</tr>
<?php endwhile; ?>
</table>


</body>
</html>

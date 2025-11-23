<?php
session_start();
$conn = new mysqli("localhost", "root", "", "court_db");
if ($conn->connect_error) die("Connection failed");

//GET FILTER VALUES
$date   = $_GET['date']   ?? date("Y-m-d");
$court  = $_GET['court']  ?? "";
$judge  = $_GET['judge']  ?? "";
$status = $_GET['status'] ?? "";

// BUILD SQL FOR CASES
$sql = "
SELECT c.*, u.username AS judge_name
FROM cases c
LEFT JOIN users u ON c.judge_id = u.id
WHERE c.case_date = ?
";

$params = [$date];
$types  = "s";

if ($court !== "") {
    $sql .= " AND c.court = ? ";
    $params[] = $court;
    $types .= "s";
}

if ($judge !== "") {
    $sql .= " AND u.username = ? ";
    $params[] = $judge;
    $types .= "s";
}

if ($status !== "") {
    $sql .= " AND c.status = ? ";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY c.case_time ASC";

//EXECUTE QUERY
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$cases = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cause List</title>
</head>
<body>

<h2>Cause List for <?= htmlspecialchars($date) ?></h2>

<!-- FILTER FORM -->
<form method="get">

Date:
<input type="date" name="date" value="<?= $date ?>">

Court:
<input type="text" name="court" value="<?= htmlspecialchars($court) ?>">



Status:
<select name="status">
    <option value="">All</option>
    <option value="pending"   <?= $status=="pending"?"selected":"" ?>>Pending</option>
    <option value="adjourned" <?= $status=="adjourned"?"selected":"" ?>>Adjourned</option>
    <option value="completed" <?= $status=="completed"?"selected":"" ?>>Completed</option>
</select>

<button type="submit">Apply Filters</button>
</form>

<br>


<br><br>


<!-- CASES TABLE -->
<table border="1" cellpadding="8">
    <tr>
        <th>Time</th>
        <th>Court</th>
        <th>Judge</th>
        <th>Assistant</th>
        <th>Initiator</th>
        <th>Defendant</th>
        <th>Case Type</th>
        <th>Status</th>
        <th>Description</th>
    </tr>

    <?php if ($cases->num_rows === 0): ?>
        <tr><td colspan="8">No cases found.</td></tr>
    <?php endif; ?>

    <?php while ($c = $cases->fetch_assoc()): ?>
    <tr>
        <td><?= $c['case_time'] ?></td>
        <td><?= $c['court'] ?></td>
        <td><?= $c['judge_id'] ?></td>
        <td><?= $c['assistant_id'] ?></td>
        <td><?= $c['initiator'] ?></td>
        <td><?= $c['defendant'] ?></td>
        <td><?= $c['case_type'] ?></td>
        <td><?= $c['status'] ?></td>
        <td><?= $c['description'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>

<br><br>

<!-- =============================== -->
<!-- PAST GENERATED CAUSE LISTS -->
<!-- =============================== -->

<h3>Past Cause Lists</h3>

<?php
$past = $conn->query("SELECT * FROM cases ORDER BY case_date DESC");

if ($past->num_rows === 0) {
    echo "No past cause lists found.";
} else {
    while ($row = $past->fetch_assoc()) {
        echo "<a href='causelist.php?date=".$row['case_date']."'>".$row['case_date']."</a><br>";
    }
}
?>

</body>
</html>

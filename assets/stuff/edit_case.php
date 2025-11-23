<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$assistant_username = $_SESSION['username'];

$conn = new mysqli("localhost", "root", "", "court_db");
if ($conn->connect_error) die("Connection failed");

// Fetch all cases belonging to this assistant
$sql = "
    SELECT id, judge_id, initiator, defendant, description, outcome, case_date, status
    FROM cases
    WHERE assistant_id = ?
    ORDER BY case_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $assistant_username);
$stmt->execute();
$cases = $stmt->get_result();

// If a case is selected for editing
if (isset($_GET['edit'])) {
    $case_id = intval($_GET['edit']);

    $stmt2 = $conn->prepare("SELECT * FROM cases WHERE id = ? AND assistant_id = ?");
    $stmt2->bind_param("is", $case_id, $assistant_username);
    $stmt2->execute();
    $case = $stmt2->get_result()->fetch_assoc();

    if (!$case) die("Case not found or not assigned to you.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = $_POST['description'];
        $status = $_POST['status'];
        $outcome = $_POST['outcome'];

        $update = $conn->prepare("
            UPDATE cases 
            SET description = ?, outcome = ?, status = ?
            WHERE id = ? AND assistant_id = ?
        ");
        $update->bind_param("sssis", $description, $outcome, $status, $case_id, $assistant_username);
        $update->execute();

        header("Location: edit_case.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<body>

<h2>Your Assigned Cases</h2>

<table border="1" cellpadding="6">
    <tr>
        <th>ID</th>
        <th>Judge</th>
        <th>Initiator</th>
        <th>Defendant</th>
        <th>Description</th>
        <th>Outcome</th>
        <th>Date</th>
        <th>Status</th>
        <th>Edit</th>
    </tr>

    <?php while ($row = $cases->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['judge_id'] ?></td>
            <td><?= $row['initiator'] ?></td>
            <td><?= $row['defendant'] ?></td>
            <td><?= $row['description'] ?></td>
            <td><?= $row['outcome'] ?></td>
            <td><?= $row['case_date'] ?></td>
            <td><?= $row['status'] ?></td>
            <td><a href="edit_case.php?edit=<?= $row['id'] ?>">Edit</a></td>
        </tr>
    <?php endwhile; ?>

</table>

<?php if (isset($case)): ?>
<hr>
<h2>Edit Case #<?= $case['id'] ?></h2>

<form method="post">

<label>Description:</label><br>
<textarea name="description" rows="5" cols="50"><?= $case['description'] ?></textarea><br><br>

<label>Outcome:</label><br>
<textarea name="outcome" rows="6" cols="50"><?= $case['outcome'] ?></textarea><br><br>

<label>Status:</label><br>
<select name="status">
    <option value="pending"   <?= $case['status']=="pending" ? "selected" : "" ?>>Pending</option>
    <option value="adjourned" <?= $case['status']=="adjourned" ? "selected" : "" ?>>Adjourned</option>
    <option value="completed" <?= $case['status']=="completed" ? "selected" : "" ?>>Completed</option>
</select><br><br>

<button type="submit">Save Changes</button>

</form>
<?php endif; ?>

</body>
</html>

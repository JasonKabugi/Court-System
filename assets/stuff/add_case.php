<?php
// Show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "court_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read inputs
    $court = trim($_POST['court'] ?? '');
    $case_type = trim($_POST['case_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $attorney = trim($_POST['attorney'] ?? '');
    $initiator = trim($_POST['initiator'] ?? '');
    $defendant = trim($_POST['defendant'] ?? '');
    $judge_id = trim($_POST['judge_id'] ?? '');
    $assistant_id = trim($_POST['assistant_id'] ?? '');
    $outcome = trim($_POST['outcome'] ?? '');
    $case_date = trim($_POST['case_date'] ?? '');
    $case_time = trim($_POST['case_time'] ?? '');

    // Required fields check
    if (
        empty($court) || empty($case_type) || empty($description) ||
        empty($attorney) || empty($initiator) || empty($defendant) ||
        empty($judge_id) || empty($assistant_id) ||
        empty($case_date) || empty($case_time)
    ) {
        echo "<p style='color:red;'>Please fill all required fields.</p>";
    } else {

        // Insert case
        $stmt = $conn->prepare("
            INSERT INTO cases (
                court, case_type, description, attorney, initiator, defendant,
                judge_id, assistant_id, outcome, case_date, case_time
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssssssss",
            $court, $case_type, $description, $attorney,
            $initiator, $defendant, $judge_id, $assistant_id,
            $outcome, $case_date, $case_time
        );

        $success = $stmt->execute();

        if ($success) {

            // Update counters for judge & CA
            $conn->query("UPDATE judges SET assigned_cases = assigned_cases + 1 WHERE username = '$judge_id'");
            $conn->query("UPDATE courtAssistant SET total_cases_handled = total_cases_handled + 1 WHERE username = '$assistant_id'");

            echo "<p style='color:green;'>Case added successfully!</p>";
        } else {
            echo "<p style='color:red;'>Error adding case: " . $stmt->error . "</p>";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Case</title>
</head>
<body>
    <h2>Add New Case</h2>

    <form method="POST" action="">
        <h1><b>SMALL CLAIMS COURT</b></h1>

        <label>Court:</label><br>
        <input type="text" name="court" required><br><br>

        <label>Case Type:</label><br>
        <select name="case_type" required>
            <option value="">--Select Case Type--</option>
            <option value="Contracts of sale or supply of goods and services">Contracts of sale or supply of goods and services</option>
            <option value="Debt recovery">Debt recovery</option>
            <option value="Dispute over money held or received">Dispute over money held or received</option>
            <option value="Liability in tort">Liability in tort</option>
            <option value="Minor personal injuries compensation">Minor personal injuries compensation</option>
            <option value="Set-off/Counterclaim arising from contracts">Set-off/Counterclaim arising from contracts</option>
        </select><br><br>

        <label>Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <label>Attorneys:</label><br>
        <input type="text" name="attorney" required><br><br>

        <label>Initiator:</label><br>
        <input type="text" name="initiator" required><br><br>

        <label>Defendant:</label><br>
        <input type="text" name="defendant" required><br><br>

        <label>Judge Username:</label><br>
        <input type="text" name="judge_id" required><br><br>

        <label>Court Assistant Username:</label><br>
        <input type="text" name="assistant_id" required><br><br>

        <label>Outcome:</label><br>
        <input type="text" name="outcome"><br><br>

        <label>Case Date:</label><br>
        <input type="date" name="case_date" required><br><br>

        <label>Case Time:</label><br>
        <input type="time" name="case_time" required><br><br>

        <button type="submit">Add Case</button>
    </form>
</body>
</html>

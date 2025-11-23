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
<head>
    <meta charset="UTF-8">
    <title>Court Assistant - Case Management</title>
    <style>
        /* --- INSTITUTIONAL THEME COLORS --- */
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-light-bg: #f4f7f6;
            --color-white: #ffffff;
        }

        /* --- GENERAL STYLES --- */
        body {
            font-family: 'Times New Roman', Times, serif; 
            background-color: var(--color-light-bg);
            margin: 0;
            padding: 30px;
            color: var(--color-text);
        }

        /* --- HEADING STYLES --- */
        h2 {
            color: var(--color-primary);
            font-size: 1.8rem;
            border-bottom: 2px solid var(--color-secondary);
            padding-bottom: 10px;
            margin-top: 25px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        hr {
            border: 0;
            height: 1px;
            background-color: #ccc;
            margin: 40px 0;
        }

        /* --- TABLE STYLES --- */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: var(--color-white);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        table th {
            background-color: var(--color-primary); /* Dark Green Header */
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
            max-width: 200px; /* Constrain wide content */
        }

        table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }
        
        /* Truncate long cell content */
        table td:nth-child(5), /* Description */
        table td:nth-child(6) { /* Outcome */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Status Colors */
        .status-pending { color: orange; font-weight: bold; }
        .status-adjourned { color: red; font-weight: bold; }
        .status-completed { color: green; font-weight: bold; }
        
        /* Edit Link */
        table td a {
            color: #3498db; /* Blue for links */
            text-decoration: none;
            font-weight: bold;
            transition: color 0.2s;
        }

        table td a:hover {
            color: var(--color-secondary);
        }
        
        /* --- EDIT FORM STYLES --- */
        form {
            background: var(--color-white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 600px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            margin-top: 15px;
            color: var(--color-primary);
        }

        textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
            font-size: 1rem;
            font-family: 'Times New Roman', Times, serif;
            transition: border-color 0.3s;
        }

        textarea:focus, select:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 3px rgba(255, 193, 7, 0.5);
            outline: none;
        }
        
        textarea {
            resize: vertical;
        }

        /* Button */
        button[type="submit"] {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 25px;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #053315; 
        }
    </style>
</head>
<body>

<h2>Your Assigned Cases</h2>

<table border="0">
    <thead>
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
    </thead>
    <tbody>
        <?php while ($row = $cases->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['judge_id'] ?></td>
                <td><?= $row['initiator'] ?></td>
                <td><?= $row['defendant'] ?></td>
                <td><?= $row['description'] ?></td>
                <td><?= $row['outcome'] ?></td>
                <td><?= $row['case_date'] ?></td>
                <td>
                    <span class="status-<?= strtolower($row['status']) ?>">
                        <?= $row['status'] ?>
                    </span>
                </td>
                <td><a href="edit_case.php?edit=<?= $row['id'] ?>">Edit</a></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php if (isset($case)): ?>
<hr>
<h2>Update Case File #<?= $case['id'] ?></h2>

<form method="post">

<label for="description">Case Description:</label>
<textarea id="description" name="description" rows="5" cols="50"><?= htmlspecialchars($case['description']) ?></textarea>

<label for="outcome">Court Outcome/Ruling:</label>
<textarea id="outcome" name="outcome" rows="6" cols="50"><?= htmlspecialchars($case['outcome']) ?></textarea>

<label for="status">Case Status:</label>
<select id="status" name="status">
    <option value="pending"   <?= $case['status']=="pending" ? "selected" : "" ?>>Pending</option>
    <option value="adjourned" <?= $case['status']=="adjourned" ? "selected" : "" ?>>Adjourned</option>
    <option value="completed" <?= $case['status']=="completed" ? "selected" : "" ?>>Completed</option>
</select>

<button type="submit"> Save Changes</button>

</form>
<?php endif; ?>

</body>
</html>
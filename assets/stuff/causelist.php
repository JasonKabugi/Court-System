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
SELECT c.*, c.judge_id AS judge_username
FROM cases c
LEFT JOIN users u ON c.judge_id = u.username
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
    $sql .= " AND c.judge_id = ? "; // Assuming judge_id in cases table stores the username directly
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
if (!empty($params) && !empty($types)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params); 
    $stmt->execute();
    $cases = $stmt->get_result();
} else {
    $cases = $conn->query("SELECT * FROM cases WHERE 1=0"); 
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cause List</title>
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
            padding: 0;
            color: var(--color-text);
        }

        /* --- BANNER SECTION --- */
        .banner {
            background-color: var(--color-primary); 
            color: var(--color-white);
            padding: 40px 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
        }

        .banner h2 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 2px solid var(--color-secondary);
            display: inline-block;
            padding-bottom: 10px;
            margin-bottom: 5px;
        }

        .banner p {
            margin: 5px 0 0;
            font-size: 1.1rem;
            color: #ccc;
        }

        /* --- MAIN CONTAINER --- */
        .container {
            max-width: 1300px;
            margin: 30px auto 40px; 
            background: var(--color-white);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        /* --- FILTER FORM (Existing styles) --- */
        .filter-box {
            background-color: #f7f9fb;
            padding: 20px;
            border-radius: 6px;
            border-left: 5px solid var(--color-secondary);
            margin-bottom: 30px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
        }

        .filter-box form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--color-primary);
        }

        input[type="date"],
        input[type="text"],
        select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-width: 150px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 3px rgba(255, 193, 7, 0.5);
            outline: none;
        }

        button {
            background-color: var(--color-secondary);
            color: var(--color-primary);
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
        }

        button:hover {
            background-color: #e3be09ff;
            color: var(--color-primary);
        }

        /* --- TABLE STYLES --- */
        h2.section-title {
            color: var(--color-primary);
            font-size: 1.8rem;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: var(--color-white);
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
        
        table td strong {
            color: var(--color-primary); 
        }

        .empty-state {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 30px;
            font-size: 1.1rem;
        }

        /* Status Colors (Existing styles) */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: capitalize;
            display: inline-block;
            font-size: 0.9rem;
        }
        .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-completed { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-adjourned { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* --- NEW: TRUNCATE/EXPAND STYLES --- */
        .expandable-cell {
            max-width: 250px; /* Limit the initial width of the description/outcome column */
            overflow: hidden;
            position: relative;
            cursor: pointer; /* Indicate it's clickable */
            line-height: 1.4;
        }
        
        /* Default: show only the first three lines */
        .content-truncated {
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Limit to 3 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Expanded state: remove the line limit */
        .expandable-cell.expanded .content-truncated {
            display: block;
            -webkit-line-clamp: unset;
        }
        
        /* The "Read More" button style */
        .toggle-button {
            font-size: 0.8rem;
            color: #3498db; /* A distinct link color */
            font-weight: bold;
            margin-top: 5px;
            display: inline-block;
            transition: color 0.2s;
        }

        .expandable-cell.expanded .toggle-button {
            color: #e74c3c; /* Change color when expanded */
        }

        /* --- PAST LISTS (ARCHIVE - Existing styles) --- */
        .archive-box {
            background: #eef2f5;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }
        .archive-box h3 {
            color: var(--color-primary);
            margin-top: 0;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .archive-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-height: 150px;
            overflow-y: auto;
            padding-right: 15px;
        }

        .archive-list a {
            text-decoration: none;
            background-color: var(--color-white);
            color: var(--color-primary);
            padding: 5px 10px;
            border: 1px solid var(--color-primary);
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .archive-list a:hover {
            background-color: var(--color-primary);
            color: var(--color-secondary);
        }
    </style>
</head>
<body>

<div class="banner">
    <h2>Milimani Small Claims Court Cause List</h2>
    <p>Schedule for: <?= htmlspecialchars($date) ?></p>
</div>

<div class="container">

    <div class="filter-box">
        <form method="get">
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" value="<?= $date ?>">
            </div>

            <div class="form-group">
                <label>Court:</label>
                <input type="text" name="court" placeholder="Milimani Small Claims Court" value="<?= htmlspecialchars($court) ?>">
            </div>

            <div class="form-group">
                <label>Judge Username:</label>
                <input type="text" name="judge" placeholder="Judge Username" value="<?= htmlspecialchars($judge) ?>">
            </div>

            <div class="form-group">
                <label>Status:</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="pending"   <?= $status=="pending"?"selected":"" ?>>Pending</option>
                    <option value="adjourned" <?= $status=="adjourned"?"selected":"" ?>>Adjourned</option>
                    <option value="completed" <?= $status=="completed"?"selected":"" ?>>Completed</option>
                </select>
            </div>

            <button type="submit">Apply Filters</button>
        </form>
    </div>

    <h2 class="section-title">Today's Cause List</h2>
    
    <div style="overflow-x:auto;">
        <table border="0">
            <thead>
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
                    <th>Outcome</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cases->num_rows === 0): ?>
                    <tr><td colspan="10" class="empty-state">No cases found for the selected criteria.</td></tr>
                <?php endif; ?>

                <?php while ($c = $cases->fetch_assoc()): 
                    $status_class = match ($c['status']) {
                        'pending' => 'status-pending',
                        'completed' => 'status-completed',
                        'adjourned' => 'status-adjourned',
                        default => '',
                    };
                ?>
                <tr>
                    <td><strong><?= $c['case_time'] ?></strong></td>
                    <td><?= $c['court'] ?></td>
                    <td><?= $c['judge_id'] ?></td> 
                    <td><?= $c['assistant_id'] ?></td>
                    <td><?= $c['initiator'] ?></td>
                    <td><?= $c['defendant'] ?></td>
                    <td><?= $c['case_type'] ?></td>
                    <td>
                        <span class="status-badge <?= $status_class ?>">
                            <?= $c['status'] ?>
                        </span>
                    </td>
                    
                    <td class="expandable-cell" onclick="toggleExpand(this)">
                        <div class="content-truncated">
                            <?= htmlspecialchars($c['description']) ?>
                        </div>
                        <span class="toggle-button">Read More</span>
                    </td>

                    <td class="expandable-cell" onclick="toggleExpand(this)">
                        <div class="content-truncated">
                            <?= htmlspecialchars($c['outcome']) ?>
                        </div>
                        <span class="toggle-button">Read More</span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-title" style="margin-top: 40px;">Past Cause Lists (Archive)</h2>

    <div class="archive-box">
        <div class="archive-list">
            <?php
            // Re-open connection for the archive query
            $conn = new mysqli("localhost", "root", "", "court_db");
            $past = $conn->query("SELECT DISTINCT case_date FROM cases WHERE case_date < CURDATE() ORDER BY case_date DESC LIMIT 20"); 

            if ($past->num_rows === 0) {
                echo "No past cause lists found.";
            } else {
                while ($row = $past->fetch_assoc()) {
                    echo "<a href='causelist.php?date=".$row['case_date']."'>".date('d M Y', strtotime($row['case_date']))."</a>";
                }
            }
            $conn->close();
            ?>
        </div>
    </div>

</div> 

<script>
    /**
     Toggles the 'expanded' class on the clicked table cell.
     This triggers the CSS rules to show/hide the full content and update the button text.
     @param {HTMLElement} cell The table cell (<td>) that was clicked.
    
     */
    function toggleExpand(cell) {
        // Toggle the 'expanded' class on the cell
        cell.classList.toggle('expanded');
        
        // Find the toggle button inside the cell
        const button = cell.querySelector('.toggle-button');
        
        // Update the button text based on the new state
        if (cell.classList.contains('expanded')) {
            button.textContent = 'Minimize';
        } else {
            button.textContent = 'Read More';
        }
    }
</script>

</body>
</html>
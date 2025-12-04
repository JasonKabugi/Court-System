<?php
// Show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "court_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Variable to store the current date for comparison
$current_date = date("Y-m-d");

// Variable to hold the case date input if validation fails, to keep form populated
$input_case_date = ''; 

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
    
    $input_case_date = $case_date; // Store input for form repopulation

    // --- 1. REQUIRED FIELDS CHECK ---
    if (
        empty($court) || empty($case_type) || empty($description) ||
        empty($attorney) || empty($initiator) || empty($defendant) ||
        empty($judge_id) || empty($assistant_id) ||
        empty($case_date) || empty($case_time)
    ) {
        echo "<p class='error-message'>Please fill all required fields.</p>";
    
    // --- 2. INTEGRITY CHECK: ONLY ALLOW TODAY'S DATE ---
    } elseif ($case_date !== $current_date) {
        echo "<p class='error-message'>Integrity Error: Cases can only be filed for today's date ({$current_date}). Backdating is not permitted.</p>";
        
    } else {
        // --- PROCEED WITH SECURE INSERTION ---
        
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

            echo "<p class='success-message'>Case added successfully! Case filed for {$current_date}.</p>";
            
            // Clear the input field after success
            $input_case_date = '';

        } else {
            echo "<p class='error-message'>Error adding case: " . $stmt->error . "</p>";
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
    <title>Add Case - Judiciary System</title>
    <style>
        /* Color Variables based on the image */
        :root {
            --color-primary: #084c1f; /* Dark Green/Navy from Header */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-light-bg: #f4f7f6;
            --color-white: #ffffff;
        }

        /* Global Reset & Body */
        body {
            font-family: 'Times New Roman', Times, serif; /* Institutional Font */
            background-color: var(--color-light-bg);
            color: var(--color-text);
            margin: 0;
            padding: 0;
            
            /* Background styles */
            background-image: url("/court_system/assets/images/flag.png"); 
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: transparent; 
        }

        /* 1. Top Navigation Bar (Mimicking the Header) */
        .header {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .navbar {
            display: flex;
            justify-content: center;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar a {
            color: var(--color-white);
            text-decoration: none;
            padding: 0 20px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: var(--color-secondary);
        }

        .navbar .star {
            color: var(--color-secondary);
            margin: 0 5px;
        }

        /* Main Content Wrapper */
        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            min-height: calc(100vh - 80px); /* Adjust height */
            justify-content: center;
        }

        /* 2. Form Container (The Centered White Card) */
        form {
            background: var(--color-white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 650px;
        }

        /* Headings & Separators */
        h2 {
            text-align: center;
            color: var(--color-primary);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 30px;
        }
        
        h1 {
            text-align: center;
            font-size: 1.4rem;
            color: var(--color-text);
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            /* Added a simple line separator */
            border-bottom: 2px solid var(--color-secondary);
        }
        
        /* Form Elements */
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--color-primary);
            margin-top: 15px;
        }

        input[type="text"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
            outline: none;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Input Grouping (Judge/Assistant, Date/Time) */
        .two-column {
            display: flex;
            gap: 30px;
            width: 100%;
        }
        .two-column > div {
            flex: 1;
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
            width: 100%;
            margin-top: 30px;
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #053315; 
        }

        /* Status Messages (PHP echoes) */
        .success-message {
            background-color: #e6ffe6;
            color: var(--color-primary) !important;
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .error-message {
            background-color: #ffe6e6;
            color: #cc0000 !important;
            border: 1px solid #f4c4c4;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('case_date');
            
            // Set the minimum selectable date to today (YYYY-MM-DD)
            dateInput.min = "<?php echo $current_date; ?>";
            
            // Set the maximum selectable date to today (to restrict future dates)
            dateInput.max = "<?php echo $current_date; ?>";
            
            // Set the default value to today's date
            dateInput.value = "<?php echo $current_date; ?>";
        });
    </script>
</head>
<body>
    
    <div class="header">
        <ul class="navbar">
            <li><a href="https://localhost/court_system/assets/pages/ca.php"><span class="star">★</span> HOME</a></li>
            <li><a href="https://localhost/court_system/assets/stuff/causelist.php"><span class="star">★</span> CAUSELIST</a></li>
            <li><a href="https://localhost/court_system/assets/stuff/edit_case.php"><span class="star">★</span> CASE MANAGEMENT</a></li>
        </ul>
    </div>

    <div class="main-container">

        <form method="POST" action="">
            <h1><b>SMALL CLAIMS COURT FILING</b></h1>

            <label for="court">Court:</label>
            <input type="text" id="court" name="court" required>

            <label for="case_type">Case Type:</label>
            <select id="case_type" name="case_type" required>
                <option value="" disabled selected>--Select Case Type--</option>
                <option value="Contracts of sale or supply of goods and services">Contracts of sale or supply of goods and services</option>
                <option value="Debt recovery">Debt recovery</option>
                <option value="Dispute over money held or received">Dispute over money held or received</option>
                <option value="Liability in tort">Liability in tort</option>
                <option value="Minor personal injuries compensation">Minor personal injuries compensation</option>
                <option value="Set-off/Counterclaim arising from contracts">Set-off/Counterclaim arising from contracts</option>
            </select>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="attorney">Attorneys:</label>
            <input type="text" id="attorney" name="attorney" required>

            <label for="initiator">Initiator (Plaintiff/Complainant):</label>
            <input type="text" id="initiator" name="initiator" required>

            <label for="defendant">Defendant (Respondent):</label>
            <input type="text" id="defendant" name="defendant" required>

            <div class="two-column">
                <div>
                    <label for="judge_id">Judge Username:</label>
                    <input type="text" id="judge_id" name="judge_id" required>
                </div>
                <div>
                    <label for="assistant_id">Court Assistant Username:</label>
                    <input type="text" id="assistant_id" name="assistant_id" required>
                </div>
            </div>

            <label for="outcome">Outcome (For Post-Trial Update):</label>
            <textarea id="outcome" name="outcome"></textarea> 

            <div class="two-column">
                <div>
                    <label for="case_date">Case Date (Today: <?php echo $current_date; ?>):</label>
                    <input type="date" id="case_date" name="case_date" required>
                </div>
                <div>
                    <label for="case_time">Case Time:</label>
                    <input type="time" id="case_time" name="case_time" required>
                </div>
            </div>

            <button type="submit">SUBMIT NEW CASE FILE</button>
        </form>
    </div>
</body>
</html>
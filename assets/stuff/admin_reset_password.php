<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// --- 1. AUTH CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: https://localhost/court_system/assets/stuff/login.php");
    exit;
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = intval($_GET['id'] ?? $_POST['user_id'] ?? 0);
$message = "";
$username_to_reset = "Unknown User";

// Fetch username for display
if ($user_id > 0) {
    $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 1) {
        $username_to_reset = $result_user->fetch_assoc()['username'];
    }
    $stmt_user->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id > 0) {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please enter and confirm the new password.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // --- Password Reset Logic (Direct Update) ---
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Use a prepared statement to update the password securely
        $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $update->bind_param("si", $hashed, $user_id);
        
        if ($update->execute()) {
            $message = "<span style='color:var(--color-green); font-weight:bold;'>Success! The password for **{$username_to_reset}** has been immediately reset.</span>";
        } else {
            $message = "<span style='color:var(--color-red); font-weight:bold;'>Error resetting password: " . $conn->error . "</span>";
        }
        $update->close();
    }
} elseif ($user_id === 0) {
    $message = "Error: No User ID specified for reset.";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reset Password</title>
    <style>
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-white: #ffffff;
            --color-red: #c0392b;
            --color-green: #27ae60;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--color-light-bg);
            /* background image */
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.6), rgba(255, 255, 255, 0.6)), 
                url("/court_system/assets/images/building.jpg"); 
            background-size: cover;
        }
        .reset-box {
            background: var(--color-white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        h2 {
            color: var(--color-primary);
            font-size: 2rem;
            margin-bottom: 5px;
        }
        .username-target {
            font-size: 1.2rem;
            color: var(--color-secondary);
            margin-bottom: 25px;
            font-weight: bold;
            display: block;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            text-align: left;
            color: var(--color-text);
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
        }
        button[type="submit"] {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background-color 0.2s;
            width: 100%;
        }
        button[type="submit"]:hover {
            background-color: #053315;
        }
        .message-box {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            text-align: left;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--color-primary);
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="reset-box">
    <h2>Reset User Password</h2>
    <span class="username-target">User: <?= htmlspecialchars($username_to_reset); ?> (ID: <?= $user_id; ?>)</span>

    <?php if (!empty($message)): ?>
        <div class="message-box">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($user_id > 0 && !strstr($message, 'Success')): ?>
        <form method="POST" action="admin_reset_password.php?id=<?= $user_id; ?>">
            <input type="hidden" name="user_id" value="<?= $user_id; ?>">
            <label for="new_password">Enter New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
            
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <button type="submit">Set New Password</button>
        </form>
    <?php endif; ?>

    <a href="https://localhost/court_system/assets/pages/admin.php" class="back-link">Go back to Admin Panel</a>
</div>

</body>
</html>
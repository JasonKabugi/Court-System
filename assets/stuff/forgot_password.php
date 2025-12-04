<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$stage = 'verification'; // Stages: verification, password_form, complete

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Determine which form was submitted ---

    if (isset($_POST['action']) && $_POST['action'] === 'verify_user') {
        // --- STAGE 1 POST: Verification (Username & Email) ---
        $username_input = trim($_POST['username'] ?? '');
        $email_input = trim($_POST['email'] ?? '');

        if (empty($username_input) || empty($email_input)) {
            $message = "Please fill all required fields.";
        } else {
            // Check if user exists with both username and email
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
            $stmt->bind_param("ss", $username_input, $email_input);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Store the verified user ID in the session
                $_SESSION['reset_user_id'] = $user['id'];
                $stage = 'password_form'; // Move to the password input stage
            } else {
                $message = "Verification failed. No account found matching that username and email.";
                $stage = 'verification';
            }
            $stmt->close();
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        // --- STAGE 2 POST: Password Reset (New Password) ---
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        $user_id_to_reset = $_SESSION['reset_user_id'] ?? 0;

        if ($user_id_to_reset === 0) {
            $message = "Session expired or verification failed. Please start over.";
            $stage = 'verification';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $message = "Please enter and confirm the new password.";
            $stage = 'password_form'; // Stay on password form
        } elseif ($new_password !== $confirm_password) {
            $message = "Passwords do not match. Try again.";
            $stage = 'password_form'; // Stay on password form
        } else {
            // Update the password directly
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Note: Removed reset_token/expires columns since we are not using them
            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param("si", $hashed, $user_id_to_reset); 
            
            if ($update->execute()) {
                $message = "Password reset **successful**. You can now login with your new password.";
                $stage = 'complete'; 
            } else {
                 $message = "Error resetting password: " . $conn->error;
                 $stage = 'password_form';
            }
            $update->close();
            unset($_SESSION['reset_user_id']); // Clear session variable after successful attempt
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <style>
        /* (CSS STYLES RETAINED FROM PREVIOUS VERSIONS) */
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-white: #ffffff;
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
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1)), 
                url("/court_system/assets/images/forgot.jpg"); 
            background-size: cover;
        }
        .reset-box {
            background: var(--color-white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            color: var(--color-primary);
            font-size: 2rem;
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            text-align: left;
            color: var(--color-text);
        }
        input[type="text"], input[type="email"], input[type="password"] {
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
            text-align: center;
            background-color: #f7f7f7;
        }
        .styled-link {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: bold;
        }
        .styled-link:hover {
            text-decoration: underline;
        }
        .success {
            color: var(--color-green);
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="reset-box">
    <h2>Reset Password</h2>

    <?php if (!empty($message)): ?>
        <div class="message-box">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($stage === 'verification'): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="verify_user">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <button type="submit">Verify Account</button>
        </form>

    <?php elseif ($stage === 'password_form'): ?>
        <p>Verification successful. Please enter your new password.</p>
        <form method="POST" action="">
            <input type="hidden" name="action" value="reset_password">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit">Set New Password</button>
        </form>
    
    <?php elseif ($stage === 'complete'): ?>
        <div class="message-box">
            <p class="success">Password changed successfully!</p>
            <a href="https://localhost/court_system/assets/stuff/login.php" class="styled-link">Back to log in</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
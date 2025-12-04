<?php
// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

session_start();

// Variable to store login message
$login_message = "";
$message_type = ""; // 'error' or 'success'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $login_message = "Please fill all required fields.";
        $message_type = "error";
    } else {

        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $db_username, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $role;

                // Redirect immediately
                switch ($role) {
            case 'admin':
                header("Location: http://localhost/court_system/assets/pages/admin.php");
                exit;
            case 'judge':
                header("Location: http://localhost/court_system/assets/pages/judge.php");
                exit;
            case 'courtAssistant':
                header("Location: http://localhost/court_system/assets/pages/ca.php");
                exit;
            }

            } else {
                $login_message = "Invalid password.";
                $message_type = "error";
            }
        } else {
            $login_message = "No account found with that username.";
            $message_type = "error";
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
    <title>User Login</title>
    <style>
        /* --- INSTITUTIONAL THEME COLORS --- */
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-white: #ffffff;
            --color-red: #c0392b; /* For errors */
        }

        /* --- GENERAL STYLES & BACKGROUND IMAGE (Matching Forgot Password) --- */
        body {
            font-family: 'Times New Roman', Times, serif;
            color: var(--color-text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            
            /* background settings */
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1)), 
                url("/court_system/assets/images/login.jpg"); 
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* --- LOGIN CARD (Matching Forgot Password) --- */
        .login-box {
            background: var(--color-white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Slightly smaller shadow, same as forgot_password */
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        /* Heading */
        h2 {
            color: var(--color-primary);
            font-weight: 700;
            font-size: 2rem; /* Matching forgot password size */
            margin-bottom: 25px; /* Matching forgot password spacing */
        }

        /* Form Grouping and Labels */
        label {
            display: block;
            text-align: left;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--color-text); /* Changed to standard text color for less clutter */
            margin-top: 15px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; 
            font-size: 1rem;
            margin-bottom: 20px; /* Added margin for consistency */
        }

        /* Button */
        button[type="submit"] {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: 10px 20px; /* Smaller padding to match forgot_password button */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            width: 100%;
            margin-top: 20px; /* Reduced margin */
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #053315; 
        }

        /* Links */
        .forgot-link {
            color: var(--color-primary); /* Use primary color for link visibility */
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 15px;
            display: inline-block;
            transition: color 0.3s;
            font-weight: bold; /* Make link stand out */
        }

        .forgot-link:hover {
            color: var(--color-secondary);
            text-decoration: underline;
        }

        /* Message Styling (Error box) */
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
            text-align: left;
        }
        .message.error {
            background-color: #ffe6e6; /* Light Red */
            color: var(--color-red); /* Dark Red */
            border: 1px solid var(--color-red);
        }
    </style>
</head>
<body>
    
    <div class="login-box">
        <form method="POST" action="">
            <h2>System Login</h2>

            <?php if (!empty($login_message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($login_message); ?>
                </div>
            <?php endif; ?>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log In</button>
            <p><a href="http://localhost/court_system/assets/stuff/forgot_password.php" class="forgot-link">Forgot Password?</a></p>

        </form>
    </div>
</body>
</html>
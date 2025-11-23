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
        // die("Please fill all required fields."); // Changed to set message for display
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

                // Redirect immediately, no echo before header
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
                // echo "Invalid password."; // Removed original echo
            }
        } else {
            $login_message = "No account found with that username.";
            $message_type = "error";
            // echo "No account found with that username."; // Removed original echo
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
        /* Color Variables based on the image */
        :root {
            --color-primary: #084c1f; /* Dark Green/Navy */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-white: #ffffff;
        }

        /* Global & Body Styles */
        body {
            font-family: 'Times New Roman', Times, serif;
            background-color: #f4f7f6;
            color: var(--color-text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            
        }

        /* Login Card */
        form {
            background: var(--color-white);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 380px;
            text-align: center;
        }

        /* Heading */
        h2 {
            color: var(--color-primary);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 30px;
        }

        /* Form Grouping and Labels */
        label {
            display: block;
            text-align: left;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--color-primary);
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
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
            outline: none;
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

        /* Links */
        p a {
            color: #555;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 15px;
            display: inline-block;
            transition: color 0.3s;
        }

        p a:hover {
            color: var(--color-secondary);
        }

        /* Message Styling (for PHP output) */
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
            text-align: left;
        }
        .message.error {
            background-color: #ffe6e6; /* Light Red */
            color: #cc0000; /* Dark Red */
            border: 1px solid #f4c4c4;
        }
    </style>
</head>
<body>
    
    <form method="POST" action="">
        <h2>User Login</h2>

        <?php if (!empty($login_message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($login_message); ?>
            </div>
        <?php endif; ?>

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
        <p><a href="http://localhost/court_system/assets/stuff/forgot_password.php">Forgot Password?</a></p>

    </form>
</body>
</html>
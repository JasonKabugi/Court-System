<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

//Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $email_input = trim($_POST['email'] ?? '');

    if (empty($username_input) || empty($email_input)) {
        $message = "Please fill all required fields.";
    } else {
        // Check if user exists with both username and email
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
        $stmt->bind_param("ss", $username_input, $email_input);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();

            // 1. Generate reset token and expiry
            $token = bin2hex(random_bytes(16));
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // 2. CORRECTED SQL: SET reset_token = ?
            $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
            // Bind three parameters: string (token), string (expires), integer (id)
            $update->bind_param("ssi", $token, $expires, $id); 
            
            if ($update->execute()) {
                // 3. Reset link 
                $reset_link = "http://localhost/court_system/assets/stuff/reset_password.php?token=$token";

                $message = "
                    A password reset link has been generated:<br>
                    <a href='$reset_link' class='styled-link'>$reset_link</a><br><br>
                    This link will expire in 1 hour.
                ";
            } else {
                 $message = "Error generating token: " . $conn->error;
            }
            $update->close();
        } else {
            $message = "No account found matching that username and email.";
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
        :root {
            --color-primary: #084c1f; /* Dark Green */
            --color-secondary: #ffc107; /* Gold/Yellow Accent */
            --color-text: #333;
            --color-white: #ffffff;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: var(--color-light-bg);
            /* Consistent background image */
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
        input[type="text"], input[type="email"] {
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
            border: 1px dashed var(--color-secondary);
            background-color: var(--color-light-bg);
            border-radius: 4px;
            word-break: break-all;
        }
        .styled-link {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: bold;
        }
        .styled-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="reset-box">
    <h2>Forgot Password</h2>

    <form method="POST" action="">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br>
        <button type="submit">Send Reset Link</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message-box">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
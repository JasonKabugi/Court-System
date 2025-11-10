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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        die("Please fill all required fields.");
    }

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
        header("Location: http://localhost/court_system/assets/pages/admin.html");
        exit;
    case 'judge':
        header("Location: http://localhost/court_system/assets/pages/judge.html");
        exit;
    case 'courtAssistant':
        header("Location: http://localhost/court_system/assets/pages/ca.html");
        exit;
    default:
        header("Location: http://localhost/court_system/assets/pages/user_dashboard.php");
        exit;
    }


        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No account found with that username.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Login</title>
</head>
<body>
    <h2>User Login</h2>
    <form method="POST" action="">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>
        <p><a href="http://localhost/court_system/assets/stuff/forgot_password.php">Forgot Password?</a></p>

    </form>
</body>
</html>

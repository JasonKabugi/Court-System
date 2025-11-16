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

// Start session & assume admin logged in for testing
session_start();
$_SESSION['role'] = 'admin'; // remove in production, depends on your login system


// Utility: Generate unique usernames
function generateUniqueUsername($conn, $role) {
    $prefixMap = [
        'admin' => 'AD',
        'judge' => 'JDK',
        'courtAssistant' => 'CA',
        'user' => 'USR'
    ];
    $prefix = $prefixMap[$role] ?? 'USR';

    do {
        $uniqueNumber = rand(100000, 999999);
        $username = $prefix . $uniqueNumber;

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $username;
}

// Admin-only check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied: Only admins can register users.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    $email = trim($email);
    $password = trim($password);
    $role = trim($role);

    if (empty($email) || empty($password) || empty($role)) {
        die("Please fill all required fields.");
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $username = generateUniqueUsername($conn, $role);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;

        switch ($role) {
            case 'judge':
                $conn->query("INSERT INTO judges (username, court) VALUES ('$username', 'Not assigned')");
                break;
            case 'courtAssistant':
                $conn->query("INSERT INTO courtAssistant (username, court) VALUES ('$username', 'Not assigned')");
                break;
            case 'admin':
                $conn->query("INSERT INTO admin (username, full_name, court) VALUES ('$username', 'New Admin', 'Main Court')");
                break;
        }

        echo "User registered successfully.<br>Generated Username: <strong>$username</strong>";
    } else {
        echo "Error registering user: " . $conn->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!--Registration Form-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register User</title>
</head>
<body>
    <h2>Register a New User</h2>
    <form method="POST" action="">
        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Role:</label><br>
        <select name="role" required>
            <option value="judge">Judge</option>
            <option value="courtAssistant">Court Assistant</option>
            <option value="admin">Admin</option>
            <option value="user">User</option>
        </select><br><br>

        <button type="submit">Register User</button>
    </form>
</body>
</html>

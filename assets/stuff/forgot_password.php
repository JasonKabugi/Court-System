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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($email)) {
        die("Please fill all required fields.");
    }

    // Check if user exists with both username and email
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id);
        $stmt->fetch();

        // Generate reset token and expiry
        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
        $update->bind_param("ssi", $token, $expires, $id);
        $update->execute();
        $update->close();

        // Reset link 
        $reset_link = "http://localhost/court_system/assets/stuff/reset_password.php?token=$token";

        echo "A password reset link has been generated:<br>";
        echo "<a href='$reset_link'>$reset_link</a><br><br>";
        echo "This link will expire in 1 hour.";
    } else {
        echo "No account found matching that username and email.";
    }

    $stmt->close();
}

$conn->close();
?>

<!--Forgot Password Form-->
<h2>Forgot Password</h2>
<form method="POST" action="">
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>
    <button type="submit">Send Reset Link</button>
</form>

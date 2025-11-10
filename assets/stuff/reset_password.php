<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = trim($_POST['new_password'] ?? '');

    if (empty($new_password)) die("Enter a new password.");

    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token=? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id);
        $stmt->fetch();

        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $update->bind_param("si", $hashed, $id);
        $update->execute();
        $update->close();

        echo "Password reset successful. <a href='login.php'>Login here</a>";
    } else {
        echo "Invalid or expired token.";
    }

    $stmt->close();
} else {
    if (empty($token)) die("No reset token provided.");
?>
<h2>Reset Password</h2>
<form method="POST" action="">
    <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
    <label>New Password:</label><br>
    <input type="password" name="new_password" required><br><br>
    <button type="submit">Reset Password</button>
</form>
<?php
}
$conn->close();
?>

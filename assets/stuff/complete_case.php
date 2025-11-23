<?php
// Show errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure ID exists
if (!isset($_GET['id'])) {
    die("No case ID provided.");
}

$case_id = (int) $_GET['id'];

// Update case status
$sql = "UPDATE cases SET status = 'completed' WHERE id = $case_id";
if ($conn->query($sql)) {
    echo "Case $case_id marked as completed.";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>

<?php
// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function create_tables($conn) {
    $sql = "
    -- USERS TABLE
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'judge', 'courtAssistant', 'user') NOT NULL DEFAULT 'user'
    );

    -- JUDGES TABLE
    CREATE TABLE IF NOT EXISTS judges (
        user_id INT PRIMARY KEY,
        court VARCHAR(255) NOT NULL,
        assigned_cases INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    -- COURT ASSISTANT TABLE
    CREATE TABLE IF NOT EXISTS courtAssistant (
        user_id INT PRIMARY KEY,
        court VARCHAR(255) NOT NULL,
        total_cases_handled INT DEFAULT 0,
        judge_id INT UNIQUE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (judge_id) REFERENCES judges(user_id)
    );

    -- ADMIN TABLE
    CREATE TABLE IF NOT EXISTS admin (
        user_id INT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        court VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    -- CASES TABLE
    CREATE TABLE IF NOT EXISTS cases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_type VARCHAR(255) NOT NULL,
        initiator VARCHAR(255) NOT NULL,
        defendant VARCHAR(255) NOT NULL,
        judge_id INT,
        assistant_id INT,
        outcome VARCHAR(255),
        case_date DATE NOT NULL,
        case_time VARCHAR(255) NOT NULL,
        FOREIGN KEY (judge_id) REFERENCES judges(user_id),
        FOREIGN KEY (assistant_id) REFERENCES courtAssistant(user_id)
    );

    -- CAUSE LIST TABLE
    CREATE TABLE IF NOT EXISTS causeList (
        id INT AUTO_INCREMENT PRIMARY KEY,
        county VARCHAR(255) NOT NULL,
        court VARCHAR(255) NOT NULL,
        division VARCHAR(255) NOT NULL,
        hearing_day VARCHAR(255) NOT NULL,
        judge_id INT NOT NULL,
        assistant_id INT NOT NULL,
        time VARCHAR(255) NOT NULL,
        mention VARCHAR(255) NOT NULL,
        FOREIGN KEY (judge_id) REFERENCES judges(user_id),
        FOREIGN KEY (assistant_id) REFERENCES courtAssistant(user_id)
    );

    -- SYSTEM LOGS TABLE
    CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255) NOT NULL,
        table_name VARCHAR(255),
        record_id INT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    ";

    try {
        if ($conn->multi_query($sql) === TRUE) {
            while ($conn->more_results() && $conn->next_result());
            echo '✅ All tables created successfully.';
        } else {
            throw new Exception('Error creating tables: ' . $conn->error);
        }
    } catch (Exception $e) {
        echo '❌ ' . $e->getMessage();
    } finally {
        $conn->close();
    }
}

create_tables($conn);
?>

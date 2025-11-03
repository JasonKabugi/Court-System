<?php
// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
//localhost means the database is on my computer
//root is the default username
//"" means that i haven't set a password, perhaps i should put one later
//court_db is the name of the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "court_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
function create_tables($conn) {
    // SQL to create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    );

    CREATE TABLE IF NOT EXISTS causeList (
        id INT AUTO_INCREMENT PRIMARY KEY,
        county VARCHAR(255) NOT NULL,
        court VARCHAR(255) NOT NULL,
        division VARCHAR(255) NOT NULL,
        day VARCHAR(255) NOT NULL UNIQUE,
        judge VARCHAR(255) NOT NULL,
        time VARCHAR(255) NOT NULL,
        mention VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (courtAssistant_id) REFERENCES courtAssistant(id)
         
    );

    CREATE TABLE IF NOT EXISTS courtAssistant (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username INT NOT NULL,
        court INT NOT NULL,
        case_filed VARCHAR(225) NOT NULL,
        judge VARCHAR(255) NOT NULL,
        case_outcome VARCHAR(255) NOT NULL,
        case_date DATE NOT NULL,
        case_time VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (courtAssistant_id) REFERENCES courtAssistant(id)
    );

    CREATE TABLE IF NOT EXISTS case (
        id INT AUTO_INCREMENT PRIMARY KEY,
        case_type INT NOT NULL,
        court INT NOT NULL,
        initiator VARCHAR(225) NOT NULL,
        defendant VARCHAR(255) NOT NULL,
        judge VARCHAR(255) NOT NULL,
        case_outcome VARCHAR(255) NOT NULL,
        case_date DATE NOT NULL,
        case_time VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (courtAssistant_id) REFERENCES courtAssistant(id),
        FOREIGN KEY (causeList_id) REFERENCES causeList(id)
    );
    ";

    try {
        // Execute SQL
        if ($conn->multi_query($sql) === TRUE) {
            // Cycle through result
            while ($conn->more_results() && $conn->next_result());
            echo "Tables created successfully";
        } else {
            throw new Exception("Error creating tables: " . $conn->error);
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    } finally {
        $conn->close();
    }
}
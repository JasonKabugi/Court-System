<?php
//Start session
session_start();

//$conn = new mySQL create a connection to the database
//localhost means the database is on my computer
//root is the default username
//"" means that i haven't set a password, perhaps i should put one later
//court_db is the name of the database
$conn = new mysqli("localhost", "root", "", "court_db");

//check the connection
// mysqli_connect_error() returns the error message if connection failed, or false/null if successful
// If there's an error, immediately stop script execution and display the error message
if (mysqli_connect_error()) {die("Database connection failed: " . mysqli_connect_error());}

//POST receives the data from index.html. thus it will receive the username, email and password
//im using post since in the index.html form, i used post in the method int=stead of get
$user_code = $_POST['user_id'];
$email = $_POST['email'];
$password = $_POST['password'];

// Prepare the SQL statement to fetch the user_id and password. not to sure if it will work but ill revisit if it doesn't
//i forgot to mention that this line of code looks for a specific user based on their user id and matches with their password. again, i haven't yet tested this but we'll revisit this. 
//another thing ,($) is a variable starter, i probably didn't explain that properly but if it works, it works. STMT is statement, so thats what i originally meant by im preparing an sql statement
$stmt = $conn->prepare("SELECT * FROM users WHERE password = ? AND user_id = ?");

//binds the actual values from the form into the sql
// SS means the parameters are strings, password and user_id
//don't know if spacing the parameters affects anything, will come back if anything
// if things work properly, it'll prevent sql injection
$stmt->bind_param("ss", $password, $user_id);

//This will execute and get the result.........
$stmt->execute();
$result = $stmt->get_result();












?>


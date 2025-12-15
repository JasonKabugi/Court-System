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
$_SESSION['role'] = 'admin'; 

// Variable to store messages for the UI
$message = "";
$messageType = ""; // 'success' or 'error'

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

// Function to validate password strength
function validatePassword($password) {
    // Requirements: 8-15 characters, uppercase, lowercase, number, special character
    $minLength = 8;
    $maxLength = 15;
    
    if (strlen($password) < $minLength || strlen($password) > $maxLength) {
        return "Password must be at least 8-15 characters long, include a mix of uppercase and lowercase letters, numbers, and special characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must be at least 8-15 characters long, include a mix of uppercase and lowercase letters, numbers, and special characters.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must be at least 8-15 characters long, include a mix of uppercase and lowercase letters, numbers, and special characters.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must be at least 8-15 characters long, include a mix of uppercase and lowercase letters, numbers, and special characters.";
    }
    if (!preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        return "Password must be at least 8-15 characters long, include a mix of uppercase and lowercase letters, numbers, and special characters.";
    }
    return true; // Password is strong
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? ''; // New variable
    $role = $_POST['role'] ?? '';

    $email = trim($email);
    $password = trim($password);
    $confirm_password = trim($confirm_password); // Trim new variable
    $role = trim($role);

    if (empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $message = "Please fill all required fields.";
        $messageType = "error";
    } elseif ($password !== $confirm_password) { // Check for password match
        $message = "Password and Confirm Password do not match.";
        $messageType = "error";
    } else {
        $validationResult = validatePassword($password); // Validate password strength

        if ($validationResult !== true) {
            // Password is not strong enough
            $message = "Registration failed: " . $validationResult;
            $messageType = "error";
        } else {
            // Strong password and passwords match, proceed with registration
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $username = generateUniqueUsername($conn, $role);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;

                // Handle role-specific tables - NOTE: Using query() is vulnerable to SQL injection here, but kept for minimum change based on original code structure. Prepared statements are recommended.
                switch ($role) {
                    case 'judge':
                        // Warning: Potential SQL Injection in original code's approach.
                        // For a secure implementation, this should also use prepared statements.
                        $conn->query("INSERT INTO judges (username, court) VALUES ('$username', 'Not assigned')");
                        break;
                    case 'courtAssistant':
                        // Warning: Potential SQL Injection in original code's approach.
                        $conn->query("INSERT INTO courtAssistant (username, court) VALUES ('$username', 'Not assigned')");
                        break;
                    case 'admin':
                        // Warning: Potential SQL Injection in original code's approach.
                        $conn->query("INSERT INTO admin (username, full_name, court) VALUES ('$username', 'New Admin', 'Main Court')");
                        break;
                }

                $message = "User registered successfully!<br>Username: <strong>$username</strong>";
                $messageType = "success";
            } else {
                $message = "Error registering user: " . $conn->error;
                $messageType = "error";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <style>
        /* Global Reset & Font */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
             background-image: 
                linear-gradient(rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.1)), 
                url("/court_system/assets/images/register.jpg"); 
            background-size: cover;
        }

        /* Card Container */
        .register-container {
            background: white;
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* Header */
        .register-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #028d30ff; /* Dark Navy */
            font-weight: 600;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #028d30ff;
            font-size: 0.9rem;
            font-weight: 500;
        }

        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background-color: #fff;
        }

        input:focus,
        select:focus {
            border-color: #ffc107;
            outline: none;
            box-shadow: 0 0 5px rgba(191, 219, 52, 1);
        }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #028d30ff; /* Navy Blue */
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background-color: #ffc107;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-align: center;
        }

        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <h2>Register A New User</h2>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password (8-15 chars, mixed case, number, special char)" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
            </div>

            <div class="form-group">
                <label for="role">Assign Role</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select a role...</option>
                    <option value="judge">Judge</option>
                    <option value="courtAssistant">Court Assistant</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Register User</button>
        </form>
    </div>

</body>
</html>
<?php
// Enabling error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "easymeals";

$conn = new mysqli($host, $username, $password, $dbname);

// Checking the connection error
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Get the data from Flutter (POST request)
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

// Password validation
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one number, and one special character."
    ]);
    exit;
}

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'superadmin';

try {
    $conn->begin_transaction();

    // Check if the email already exists
    $emailCheckStmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckStmt->store_result();

    if ($emailCheckStmt->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "User already exists."]);
        $emailCheckStmt->close();
        exit;
    }
    $emailCheckStmt->close();

    // Insert the admin data into the users table
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password_hash, $role);

    if ($stmt->execute()) {
        $conn->commit();
        echo json_encode(["success" => true, "message" => "User signed up successfully!"]);
    } else {
        throw new Exception("Error: " . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

session_start();
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "easymeals";

// Creating the database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Checking the connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging (Comment this in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Read JSON input
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!isset($data['email'], $data['password'])) {
    die(json_encode(["success" => false, "message" => "Email and password are required"]));
}

$email = trim($data['email']);
$password = $data['password'];

// SQL query to fetch admin details
$sql = "SELECT a.admin_id, u.email, u.password_hash, u.role, u.approval_status, 
               b.branch_id, b.branch_name
        FROM admin_details a
        JOIN users u ON a.admin_id = u.user_id
        JOIN branches b ON a.branch_id = b.branch_id
        WHERE u.email = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die(json_encode(["success" => false, "message" => "Database query failed: " . $conn->error]));
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(["success" => false, "message" => "Admin not found"]));
}

$admin = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $admin['password_hash'])) {
    die(json_encode(["success" => false, "message" => "Invalid credentials"]));
}

// Check if the user is actually an admin
if ($admin['role'] !== 'admin') {
    die(json_encode(["success" => false, "message" => "You must be an admin to log in"]));
}

// Check if the admin's account is pending approval
if ($admin['approval_status'] === 'pending') {
    die(json_encode(["success" => false, "message" => "Your admin account is pending approval by the superadmin. Please wait for verification."]));
}

// Store session variables
$_SESSION['admin_id'] = $admin['admin_id'];
$_SESSION['email'] = $admin['email'];
$_SESSION['branch_id'] = $admin['branch_id'];
$_SESSION['branch_name'] = $admin['branch_name'];
$_SESSION['session_id'] = session_id();

// Send JSON response
echo json_encode([
    "success" => true,
    "message" => "Admin login successful",
    "session_id" => session_id(),
    "user" => [
        "id" => $admin['admin_id'],
        "email" => $admin['email'],
        "branch_id" => $admin['branch_id'],
        "branch_name" => $admin['branch_name']
    ]
]);

$stmt->close();
$conn->close();
?>
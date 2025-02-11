<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
$host = "localhost";
$db_name = "easymeals";
$db_user = "root";
$db_password = "";

$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Retrieve email and password from the request
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Email and password are required"]);
    exit;
}

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// Fetch user data based on email
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database query error: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(["success" => false, "message" => "Invalid password"]);
    exit;
}

// Check if the user has an admin role
if ($user['role'] !== 'superadmin') {
    echo json_encode(["success" => false, "message" => "Access denied: Not an admin"]);
    exit;
}

// Login successful for admin
echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "user" => [
        "id" => $user['user_id'],
        "email" => $user['email'],
        "role" => $user['role']
    ]
]);

$conn->close();
?>
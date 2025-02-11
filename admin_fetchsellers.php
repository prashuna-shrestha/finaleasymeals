<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

error_reporting(0); // Suppress error messages in output
ini_set('display_errors', 0);

// ✅ Restore session before starting it
if (isset($_GET['session_id'])) {
    session_id($_GET['session_id']); // Set the session ID first!
}
session_start(); // Now start session

// Debugging
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// ✅ Use `$_SESSION['branch_id']` or GET parameter
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : ($_SESSION['branch_id'] ?? null);
if (!$branch_id) {
    echo json_encode(["success" => false, "message" => "Branch ID missing"]);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "easymeals";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// ✅ Query sellers for the branch
$sql = "SELECT users.user_id, users.email, users.branch_id, branches.branch_name 
        FROM users 
        JOIN branches ON users.branch_id = branches.branch_id 
        WHERE users.branch_id = ? AND users.role = 'seller'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$sellers = [];
while ($row = $result->fetch_assoc()) {
    $sellers[] = $row;
}

$stmt->close();
$conn->close();

// ✅ Return JSON response
echo json_encode(['success' => true, 'sellers' => $sellers]);
?>
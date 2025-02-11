<?php
// Fetch sellers by branch (if provided)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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

$branch_id = $_GET['branch_id'] ?? null;

// Debug output to check branch_id
if ($branch_id) {
    error_log("Filtering sellers by branch_id: $branch_id");  // This will log to the error log on the server
} else {
    error_log("Fetching all sellers (no branch_id provided)");
}

$sql = "SELECT users.user_id, users.email, users.branch_id, branches.branch_name 
        FROM users 
        LEFT JOIN branches ON users.branch_id = branches.branch_id 
        WHERE users.role = 'seller'";

if ($branch_id) {
    $sql .= " AND users.branch_id = ?"; // Filter by branch_id
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $branch_id);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $sellers = [];
    while ($row = $result->fetch_assoc()) {
        $sellers[] = $row;
    }
    echo json_encode($sellers);
} else {
    echo json_encode([]);
}

$stmt->close();
$conn->close();
?>
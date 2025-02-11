<?php
// Fetch customers by branch (if provided)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

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
    error_log("Filtering customers by branch_id: $branch_id");  // This will log to the error log on the server
} else {
    error_log("Fetching all customers (no branch_id provided)");
}

$sql = "SELECT users.user_id, users.email, users.branch_id, branches.branch_name 
        FROM users 
        LEFT JOIN branches ON users.branch_id = branches.branch_id 
        WHERE users.role = 'user'";


if ($branch_id) {
    $sql .= " AND users.branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $branch_id);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    echo json_encode($customers);
} else {
    echo json_encode([]);  // Return an empty array if no customers are found
}

$stmt->close();
$conn->close();
?>
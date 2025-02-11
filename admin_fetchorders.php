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

error_log("Branch ID: " . $branch_id);
error_log("Executing SQL Query: " . $sql);


// Debugging
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

error_log("Orders fetched: " . print_r($orders, true));


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

$sql = "SELECT o.id AS order_id, o.user_id, u.email, b.branch_name, oi.product_id, p.product_name, c.category_name, 
               oi.quantity, oi.price_per_unit AS price, (oi.quantity * oi.price_per_unit) AS total_amount, o.order_time AS created_at 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        JOIN users u ON o.user_id = u.user_id
        JOIN branches b ON o.branch_id = b.branch_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE o.branch_id = ?";



$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();
$conn->close();

// ✅ Return JSON response
if (empty($orders)) {
    echo json_encode(['success' => true, 'message' => 'No orders found', 'orders' => []]);
} else {
    echo json_encode(['success' => true, 'orders' => $orders]);
}

?>
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");

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

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : null;

$sql = "SELECT o.id AS order_id, o.user_id, u.email, b.branch_name, oi.product_id, p.product_name, c.category_name, 
               oi.quantity, oi.price_per_unit AS price, (oi.quantity * oi.price_per_unit) AS total_amount, o.order_time AS created_at 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        JOIN users u ON o.user_id = u.user_id
        JOIN branches b ON o.branch_id = b.branch_id
        JOIN categories c ON p.category_id = c.category_id";


if ($branch_id) {
    $sql .= " WHERE o.branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $branch_id);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    echo json_encode($orders);
} else {
    echo json_encode([]);
}

$stmt->close();
$conn->close();
?>
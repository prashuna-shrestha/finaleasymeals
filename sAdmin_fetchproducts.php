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

$sql = "SELECT 
            products.product_id, 
            products.product_name, 
            products.product_price, 
            products.branch_id,  -- Ensure branch_id is selected
            branches.branch_name, 
            categories.category_name 
        FROM products 
        LEFT JOIN branches ON products.branch_id = branches.branch_id 
        LEFT JOIN categories ON products.category_id = categories.category_id";

if ($branch_id) {
    $sql .= " WHERE products.branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $branch_id);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode($products);
} else {
    echo json_encode([]);
}

$stmt->close();
$conn->close();
?>
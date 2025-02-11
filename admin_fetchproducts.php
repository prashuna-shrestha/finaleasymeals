<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['session_id'])) {
    session_id($_GET['session_id']);
}
session_start();

error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : ($_SESSION['branch_id'] ?? null);
if (!$branch_id) {
    echo json_encode(["success" => false, "message" => "Branch ID missing"]);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "easymeals";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$sql = "SELECT products.product_id, products.product_name, products.product_price, 
               categories.category_name, products.branch_id, branches.branch_name
        FROM products 
        JOIN branches ON products.branch_id = branches.branch_id
        JOIN categories ON products.category_id = categories.category_id
        WHERE products.branch_id = ?";



$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

$stmt->close();
$conn->close();

if (empty($products)) {
    echo json_encode(['success' => false, 'message' => 'No products found for this branch']);
} else {
    echo json_encode(['success' => true, 'products' => $products]);
}
?>
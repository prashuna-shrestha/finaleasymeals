<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "easymeals"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get branch_id from the request
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if ($branch_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit;
}

// Query to fetch the top 8 most ordered products
$sql = "
    SELECT 
        p.product_id, 
        p.product_name, 
        p.image_url, 
        SUM(oi.quantity) AS total_quantity
    FROM 
        order_items oi
    JOIN 
        products p ON oi.product_id = p.product_id
    JOIN 
        orders o ON oi.order_id = o.id
    WHERE 
        o.branch_id = ?
    GROUP BY 
        p.product_id
    ORDER BY 
        total_quantity DESC
    LIMIT 8
";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $popular_items = [];
    while ($row = $result->fetch_assoc()) {
        $popular_items[] = $row;
    }
    echo json_encode(['success' => true, 'popular_items' => $popular_items]);
} else {
    echo json_encode(['success' => false, 'message' => 'No popular items found']);
}

// Close the connection
$stmt->close();
$conn->close();
?>
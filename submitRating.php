<?php
header('Content-Type: application/json');
$host = "localhost";
$username = "root";
$password = "";
$dbname = "easymeals";
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// Debug: Log incoming POST data
error_log("Received POST data: " . print_r($_POST, true));

// Check if required fields are provided
if (!isset($_POST['user_id']) || !isset($_POST['product_id']) || !isset($_POST['rating'])) {
    error_log("Missing required fields");
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_POST['user_id'];
$product_id = $_POST['product_id'];
$rating = $_POST['rating'];

// Debug: Log the values being processed
error_log("Processing rating for user_id: $user_id, product_id: $product_id, rating: $rating");

// Insert the rating into the ratings table
$sql = "INSERT INTO ratings (user_id, product_id, rating) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Failed to prepare SQL statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL statement']);
    exit;
}

$stmt->bind_param("iii", $user_id, $product_id, $rating);
if (!$stmt->execute()) {
    error_log("Failed to execute SQL statement: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to execute SQL statement']);
    exit;
}

if ($stmt->affected_rows > 0) {
    // Update the orders table to mark the order as rated
    $update_sql = "UPDATE orders SET rating = TRUE WHERE user_id = ? AND id = (SELECT MAX(id) FROM orders WHERE user_id = ?)";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        error_log("Failed to prepare update SQL statement: " . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update SQL statement']);
        exit;
    }

    $update_stmt->bind_param("ii", $user_id, $user_id);
    if (!$update_stmt->execute()) {
        error_log("Failed to execute update SQL statement: " . $update_stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to update order rating status']);
        exit;
    }

    if ($update_stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No rows affected in orders table']);
    }
    $update_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit rating']);
}

$stmt->close();
$conn->close();
?>
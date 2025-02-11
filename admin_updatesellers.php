<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "easymeals";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Get input data
$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? null;
$action = $data['action'] ?? null;

// Validate input
if (!$user_id || !$action || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Update seller status based on action
$approval_status = ($action === 'approve') ? 'approved' : 'rejected';

// Corrected query to update `approval_status`
$sql = "UPDATE users SET approval_status = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $approval_status, $user_id);

if ($stmt->execute()) {
    // Get the updated status to return
    $stmt->close();
    
    // Fetch updated status from database
    $sql_get_status = "SELECT approval_status FROM users WHERE user_id = ?";
    $stmt_get_status = $conn->prepare($sql_get_status);
    $stmt_get_status->bind_param("i", $user_id);
    $stmt_get_status->execute();
    $result = $stmt_get_status->get_result();
    $status_row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Seller status updated successfully',
        'status' => $status_row['approval_status']  // Return the updated status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update seller status']);
}

$stmt_get_status->close();
$conn->close();
?>
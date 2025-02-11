<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
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

$response = ["success" => false, "admins" => []];

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT a.admin_id, a.full_name, u.email, a.contact, b.branch_id, b.branch_name 
            FROM admin_details a
            JOIN users u ON a.admin_id = u.user_id
            JOIN branches b ON a.branch_id = b.branch_id";
    
    if ($branch_id) {
        $sql .= " WHERE a.branch_id = ?";
    }

    $stmt = $conn->prepare($sql);
    
    if ($branch_id) {
        $stmt->bind_param("i", $branch_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response["admins"][] = $row;
    }
    
    $response["success"] = true;
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $response["error"] = "Error fetching admins: " . $e->getMessage();
}

echo json_encode($response);
?>
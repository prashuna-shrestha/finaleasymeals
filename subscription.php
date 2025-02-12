<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$host = "localhost";
$db_name = "easymeals";
$db_user = "root";
$db_password = "root";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch subscription details for a user (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_GET['user_id'];

    // Query to get subscription details along with remaining meals from user_subscriptions
    $query = "
        SELECT us.*, s.plan_name, s.price, s.meals_per_month, s.sweets, s.drinks, 
               us.remaining_meals, s.subscription_credit
        FROM user_subscriptions us
        JOIN subscriptions s ON us.subscription_id = s.subscription_id
        WHERE us.user_id = ? AND us.status = 'active' AND us.end_date >= CURDATE()";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $subscription = $result->fetch_assoc();
            echo json_encode([
                "success" => true,
                "subscription_id" => $subscription['subscription_id'],
                "plan_name" => $subscription['plan_name'],
                "price" => $subscription['price'],
                "meals_per_month" => $subscription['meals_per_month'],
                "remaining_meals" => $subscription['remaining_meals'], // Fetched from user_subscriptions
                // "subscription_credit" => $subscription['subscription_credit'],
                "message" => "User already has an active subscription."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No active subscription found."
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database query failed"
        ]);
    }
}
// Create a new subscription (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if the user already has an active subscription
    $user_id = $data['user_id'];

    $checkQuery = "SELECT * FROM user_subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // User already has an active subscription
        echo json_encode([
            "success" => false,
            "message" => "You already have an active subscription."
        ]);
    } else {
        // Add new subscription
        $subscription_id = $data['subscription_id'];
        $order_id = $data['order_id'];
        $price = $data['amount'];

        $insertQuery = "INSERT INTO user_subscriptions (user_id, subscription_id, order_id, price, start_date, end_date, status)
                        VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'active')";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iiis", $user_id, $subscription_id, $order_id, $price);
        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Subscription successfully created."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to create subscription."
            ]);
        }
        $stmt->close();
    }

    $checkStmt->close();
}

$conn->close();
?>

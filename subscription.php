<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database credentials
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "easymeals"; 

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Handling POST request for fetching subscription credit and remaining meals
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get request data
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate input
    if (!isset($data['user_id'])) {
        echo json_encode(["success" => false, "message" => "Missing required field: user_id"]);
        exit;
    }

    $user_id = $conn->real_escape_string($data['user_id']);
    $items_ordered = isset($data['items_ordered']) ? (int)$data['items_ordered'] : 0;  // Number of items ordered

    // Query to fetch remaining meals for the user
    $sql = "SELECT us.remaining_meals 
    FROM user_subscriptions us
    WHERE us.user_id = '$user_id' AND us.end_date >= CURDATE() AND us.status = 'active'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // If subscription exists and is active
        $row = $result->fetch_assoc();
        $remaining_meals = $row['remaining_meals'];

        // Check if user has enough remaining meals
        if ($remaining_meals >= $items_ordered) {
            // Calculate remaining meals after the order
            $new_remaining_meals = $remaining_meals - $items_ordered;

            // Update the remaining meals in the database
            $update_sql = "UPDATE user_subscriptions 
               SET remaining_meals = '$new_remaining_meals' 
               WHERE user_id = '$user_id' AND status = 'active'";

            if ($conn->query($update_sql) === TRUE) {
                // Return success with subscription details
                echo json_encode([
                    "success" => true,
                    "subscription_credit" => "$items_ordered from $remaining_meals",
                    "remaining_meals" => $new_remaining_meals
                ]);
            } else {
                // Handle update failure
                echo json_encode(["success" => false, "message" => "Failed to update remaining meals"]);
            }
        } else {
            // Not enough remaining meals
            echo json_encode(["success" => false, "message" => "Not enough remaining meals for this order"]);
        }
    } else {
        // If no active subscription found
        echo json_encode(["success" => false, "message" => "No active subscription found"]);
    }
}

// Close connection
$conn->close();
?>
<?php
header("Content-Type: application/json");

// Database connection
$host = "localhost";
$db_name = "easymeals";
$db_user = "";
$db_password = "root";
$secret_key = "5a66fd7ee8d84fb2b262799ed23c2f78"; // Your Khalti Secret Key

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $db_user, $db_password, $db_name);

// Check if this request is for initiating payment
if (isset($_POST['amount'], $_POST['user_id'], $_POST['email'], $_POST['subscription_id'])) {
    $amount = $_POST['amount'] * 100; // Convert to paisa
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $subscription_id = $_POST['subscription_id'];
    $order_id = "SUB-" . uniqid(); // Unique subscription order ID

    // Step 1: Save payment details to the database
    $query = "INSERT INTO payments (
                user_id, subscription_id, amount, payment_method, transaction_id, payment_status
              ) VALUES (?, ?, ?, 'Khalti', ?, 'pending')";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("iids", $user_id, $subscription_id, $amount, $order_id);
        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id; // Get the auto-generated payment ID
            $stmt->close();
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to save payment details"
            ]);
            exit;
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Database query failed"
        ]);
        exit;
    }

    // Step 2: Initiate Khalti payment
    $payload = [
        "return_url" => "http://10.0.2.2/minoriiproject/payment_success.php",
        "website_url" => "http://10.0.2.2/minoriiproject",
        "amount" => $amount,
        "purchase_order_id" => $order_id,
        "purchase_order_name" => "Subscription from EasyMeals",
        "customer_info" => [
            "name" => "User_$user_id",
            "email" => $email,
            "phone" => "9800000000" // Replace with actual phone number if available
        ]
    ];

    $headers = [
        "Authorization: Key $secret_key",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://a.khalti.com/api/v2/epayment/initiate/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $api_response = json_decode($response, true);
        if (isset($api_response['payment_url'])) {
            echo json_encode([
                "success" => true,
                "payment_url" => $api_response['payment_url'],
                "payment_id" => $payment_id // Return payment ID for reference
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Payment initiation failed: payment_url not found",
                "response" => $api_response
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Payment initiation failed: HTTP $http_code",
            "response" => $response
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request: Missing required parameters"
    ]);
}

$conn->close();
?>

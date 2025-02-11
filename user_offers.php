<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$host = 'localhost';
$username = 'root';
$password = ''; // Replace with your actual database password
$dbname = 'easymeals';

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection error
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit();
}

// Check if branch_id is provided
if (isset($_GET['branch_id'])) {
    $branchId = intval($_GET['branch_id']); // Ensure branch_id is an integer

    // Prepare the SQL query to fetch offers
    $sql = "SELECT offer_id, offer_name, image_path FROM offers WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $offers = [];
        while ($row = $result->fetch_assoc()) {
            // Create full URL for the image path
            $imagePath = 'http://10.0.2.2/minoriiproject/' . $row['image_path']; // Update the base URL as necessary

            // Add offer_id, offer_name, and image path to the result
            $offers[] = [
                'offer_id' => intval($row['offer_id']), // Include offer_id
                'offer_name' => $row['offer_name'],
                'image_url' => $imagePath
            ];
        }

        // Return success response with offers
        echo json_encode([
            'success' => true,
            'offers' => $offers
        ]);
    } else {
        // No offers found
        echo json_encode([
            'success' => false,
            'message' => 'No offers found for the given branch ID'
        ]);
    }
} else {
    // Branch ID not provided
    echo json_encode([
        'success' => false,
        'message' => 'Branch ID is required'
    ]);
}

// Close the database connection
$conn->close();
?>
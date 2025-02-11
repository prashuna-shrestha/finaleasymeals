<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
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

try {
    $sql = "SELECT branch_id AS id, branch_name AS name FROM branches";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $branches = [];

        while ($row = $result->fetch_assoc()) {
            $branches[] = $row;
        }

        echo json_encode([
            "success" => true,
            "branches" => $branches,
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No branches found.",
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
    ]);
} finally {
    $conn->close();
}
?>
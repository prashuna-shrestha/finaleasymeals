<?php
// Enabling error reporting for debugging
// Enabling error reporting for debugging
ini_set('display_errors', 1); // Show errors directly in the page
error_reporting(E_ALL); // Report all types of errors

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
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

// Get the data from Flutter (POST request)
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$contact = $_POST['contact'] ?? '';
$branch_name = $_POST['branch_name'] ?? '';
$role = $_POST['role'] ?? ''; // Added role to differentiate between admin and others

// Validate input
if (empty($email) || empty($password) || empty($full_name) || empty($contact) || empty($branch_name) || empty($role)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

// Password validation
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters long, contain at least one uppercase letter, one lowercase letter, one number, and one special character."
    ]);
    exit;
}

// Hash the password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Start transaction for atomic operation
$conn->begin_transaction();
try {
    // Check if the branch already exists
    $branchCheckStmt = $conn->prepare("SELECT branch_id FROM branches WHERE branch_name = ?");
    $branchCheckStmt->bind_param("s", $branch_name);
    $branchCheckStmt->execute();
    $branchCheckStmt->bind_result($branch_id);
    $branchExists = $branchCheckStmt->fetch();
    $branchCheckStmt->close();

    if (!$branchExists) {
        // Insert the branch if it doesn't exist
        $insertBranchStmt = $conn->prepare("INSERT INTO branches (branch_name) VALUES (?)");
        $insertBranchStmt->bind_param("s", $branch_name);
        $insertBranchStmt->execute();
        $branch_id = $insertBranchStmt->insert_id;
        $insertBranchStmt->close();
    }

    // Set approval_status to 'pending' for admin (awaiting superadmin approval)
    $approval_status = ($role === 'admin') ? 'pending' : null;

    // Insert the user data into the users table
    $userStmt = $conn->prepare("INSERT INTO users (email, password_hash, branch_id, role, approval_status) VALUES (?, ?, ?, ?, ?)");
    $userStmt->bind_param("ssiss", $email, $password_hash, $branch_id, $role, $approval_status);

    if ($userStmt->execute()) {
        // Get the inserted user ID
        $user_id = $userStmt->insert_id; // This is the ID of the newly inserted user
        $userStmt->close();

        // Insert the admin details into the admin_detail table
        $adminDetailStmt = $conn->prepare("INSERT INTO admin_details (admin_id, full_name, contact, branch_id) VALUES (?, ?, ?, ?)");
        $adminDetailStmt->bind_param("issi", $user_id, $full_name, $contact, $branch_id);

        if ($adminDetailStmt->execute()) {
            $conn->commit();
            // Send the appropriate message for admin signup
            echo json_encode([
                "success" => true,
                "message" => "You need to verify your account. Your admin account is pending approval by the superadmin."
            ]);
        } else {
            throw new Exception("Error: " . $adminDetailStmt->error);
        }

        $adminDetailStmt->close();
    } else {
        throw new Exception("Error: " . $userStmt->error);
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();

    ?>
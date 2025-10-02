<?php
// Suppress all errors to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set header for JSON response FIRST (before any output)
header('Content-Type: application/json');

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "base_designer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed', 'message' => $conn->connect_error]);
    exit;
}

// Check if database exists
$result = $conn->select_db($dbname);
if (!$result) {
    echo json_encode(['error' => 'Database does not exist', 'message' => 'Please run setup_database.php first']);
    exit;
}

// Get the table type from request
$type = isset($_GET['type']) ? $_GET['type'] : 'tiles';

$response = [];

switch ($type) {
    case 'tiles':
        $sql = "SELECT id, name, image, width, height, color FROM tiles";
        break;
    case 'storage':
        $sql = "SELECT id, name, image, slots, items_per_slot, tiles_needed, color FROM storage";
        break;
    case 'decorations':
        $sql = "SELECT id, name, image, width, height, color FROM decorations";
        break;
    case 'items':
        // Keep backward compatibility for original items
        $sql = "SELECT * FROM items";
        break;
    default:
        echo json_encode(['error' => 'Invalid type specified']);
        $conn->close();
        exit;
}

// Check if table exists before querying
$tableCheck = $conn->query("SHOW TABLES LIKE '$type'");
if ($tableCheck && $tableCheck->num_rows === 0) {
    echo json_encode(['error' => 'Table does not exist', 'message' => 'Please run setup_database.php to create tables', 'table' => $type]);
    $conn->close();
    exit;
}

$result = $conn->query($sql);

if ($result) {
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $response = $items;
} else {
    // Return empty array instead of error to prevent breaking the UI
    $response = [];
}

// Close connection
$conn->close();

// Return the response
echo json_encode($response);
?>
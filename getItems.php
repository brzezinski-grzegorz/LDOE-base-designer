<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "base_designer";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set header for JSON response
header('Content-Type: application/json');

// Get the table type from request
$type = isset($_GET['type']) ? $_GET['type'] : 'tiles';

$response = [];

switch ($type) {
    case 'tiles':
        $sql = "SELECT id, name, image, width, height FROM tiles";
        break;
    case 'storage':
        $sql = "SELECT id, name, slots, items_per_slot, tiles_needed FROM storage";
        break;
    case 'decorations':
        $sql = "SELECT id, name, image, width, height FROM decorations";
        break;
    case 'items':
        // Keep backward compatibility for original items
        $sql = "SELECT * FROM items";
        break;
    default:
        $response = ['error' => 'Invalid type specified'];
        echo json_encode($response);
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
    $response = ['error' => 'Failed to fetch data: ' . $conn->error];
}

// Close connection
$conn->close();

// Return the response
echo json_encode($response);
?>
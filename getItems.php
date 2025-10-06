<?php
// Suppress all errors to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set header for JSON response FIRST (before any output)
header('Content-Type: application/json');

// Use centralized DB connection
require_once __DIR__ . '/dbcon.php';

// Ensure database is selected
if (empty($db_selected) || !$db_selected) {
    echo json_encode(['error' => 'Database not ready', 'message' => 'Please run setup or check DB config.']);
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
    case 'workbench':
        $sql = "SELECT id, name, image, tiles_needed FROM workbench";
        break;
    case 'furniture':
        $sql = "SELECT id, name, image, tiles_needed FROM furniture";
        break;
    case 'special':
        $sql = "SELECT id, name, image, tiles_needed FROM special";
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
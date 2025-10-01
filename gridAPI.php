<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "base_designer";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Set header for JSON response
header('Content-Type: application/json');

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Load grid data
    $grid_id = isset($_GET['grid_id']) ? intval($_GET['grid_id']) : 1;
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'grid_items'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode(['success' => true, 'items' => [], 'message' => 'Table not yet created']);
        exit;
    }
    
    $sql = "SELECT * FROM grid_items WHERE grid_id = ? ORDER BY id";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $grid_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
    $stmt->close();
    
} elseif ($method === 'POST') {
    // Save grid data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['items'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid data - items array required']);
        exit;
    }
    
    $grid_id = isset($data['grid_id']) ? intval($data['grid_id']) : 1;
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'grid_items'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Table grid_items does not exist. Please run setup_database.php first.']);
        exit;
    }
    
    // Clear existing data for this grid
    $sql = "DELETE FROM grid_items WHERE grid_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Delete prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $grid_id);
    $stmt->execute();
    $stmt->close();
    
    // Insert new data
    $sql = "INSERT INTO grid_items (grid_id, item_id, item_type, x, y) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Insert prepare failed: ' . $conn->error]);
        exit;
    }
    
    $inserted = 0;
    foreach ($data['items'] as $item) {
        $item_id = isset($item['itemId']) ? intval($item['itemId']) : 0;
        $item_type = isset($item['itemType']) ? $item['itemType'] : 'unknown';
        $x = isset($item['x']) ? intval($item['x']) : 0;
        $y = isset($item['y']) ? intval($item['y']) : 0;
        
        $stmt->bind_param("iisii", $grid_id, $item_id, $item_type, $x, $y);
        if ($stmt->execute()) {
            $inserted++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Grid saved successfully', 'inserted' => $inserted]);
    $stmt->close();
    
} elseif ($method === 'DELETE') {
    // Clear grid data
    $grid_id = isset($_GET['grid_id']) ? intval($_GET['grid_id']) : 1;
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'grid_items'");
    if ($tableCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Table grid_items does not exist. Please run setup_database.php first.']);
        exit;
    }
    
    $sql = "DELETE FROM grid_items WHERE grid_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Delete prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $grid_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo json_encode(['success' => true, 'message' => 'Grid cleared successfully', 'deleted' => $affected]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete execution failed: ' . $stmt->error]);
    }
    
    $stmt->close();
}

// Close connection
$conn->close();
?>

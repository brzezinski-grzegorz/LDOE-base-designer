<?php
require_once __DIR__ . '/dbcon.php';

// Ensure DB selected
if (empty($db_selected) || !$db_selected) {
    die('Database not initialized. Please run setup or check DB configuration.');
}

// Ensure uploads directory exists for storing uploaded images
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle CRUD operations
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $message = addRecord($conn, $table, $_POST, $_FILES, $uploadDir);
                $messageType = 'success';
                break;
            case 'edit':
                $message = updateRecord($conn, $table, $_POST, $_FILES, $uploadDir);
                $messageType = 'success';
                break;
            case 'delete':
                $message = deleteRecord($conn, $table, $_POST['id']);
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle GET delete requests
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['table']) && isset($_GET['id'])) {
    try {
        $message = deleteRecord($conn, $_GET['table'], $_GET['id']);
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// CRUD Functions
function addRecord($conn, $table, $data, $files, $uploadDir) {
    switch ($table) {
        case 'tiles':
            // Support file upload for tile image (image_file) or emoji/text
            $imgVal = $data['image'] ?? '';
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $imgVal = $uploadedImg;
            }
            
            $color = $data['color'];

            $stmt = $conn->prepare("INSERT INTO tiles (name, image, width, height, color) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $data['name'], $imgVal, $data['width'], $data['height'], $color);
            break;
            
        case 'storage':
            // Support file upload for storage image (image_file) or emoji/text
            $imgVal = $data['image'] ?? '📦';
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $imgVal = $uploadedImg;
            }
            
            $color = $data['color'] ?? '#CCCCCC';
            $stmt = $conn->prepare("INSERT INTO storage (name, image, slots, items_per_slot, tiles_needed, color) VALUES (?, ?, ?, ?, ?, ?)");
            // types: name(string), image(string), slots(int), items_per_slot(int), tiles_needed(int), color(string)
            $stmt->bind_param("ssiiis", $data['name'], $imgVal, $data['slots'], $data['items_per_slot'], $data['tiles_needed'], $color);
            break;
            
        case 'decorations':
            $decVal = $data['image'] ?? '';
            $uploadedDec = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedDec !== null) {
                $decVal = $uploadedDec;
            }
            
            $color = $data['color'] ?? '#CCCCCC';

            $stmt = $conn->prepare("INSERT INTO decorations (name, image, width, height, color) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $data['name'], $decVal, $data['width'], $data['height'], $color);
            break;
            
        case 'workbench':
            $imgVal = $data['image'] ?? '🔨';
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $imgVal = $uploadedImg;
            }
            
            $stmt = $conn->prepare("INSERT INTO workbench (name, image, tiles_needed) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $data['name'], $imgVal, $data['tiles_needed']);
            break;
            
        case 'furniture':
            $imgVal = $data['image'] ?? '🪑';
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $imgVal = $uploadedImg;
            }
            
            $stmt = $conn->prepare("INSERT INTO furniture (name, image, tiles_needed) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $data['name'], $imgVal, $data['tiles_needed']);
            break;
            
        case 'special':
            $imgVal = $data['image'] ?? '⭐';
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $imgVal = $uploadedImg;
            }
            
            $stmt = $conn->prepare("INSERT INTO special (name, image, tiles_needed) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $data['name'], $imgVal, $data['tiles_needed']);
            break;
            
        default:
            throw new Exception("Invalid table");
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return ucfirst($table) . " added successfully!";
    } else {
        throw new Exception($conn->error);
    }
}

function updateRecord($conn, $table, $data, $files, $uploadDir) {
    $id = (int)$data['id'];
    
    // Debug logging
    error_log("=== UPDATE RECORD ===");
    error_log("Table: " . $table);
    error_log("ID: " . $id);
    error_log("POST Data: " . print_r($data, true));
    
    // Variables to track for verification
    $submittedColor = null;
    
    switch ($table) {
        case 'tiles':
            // Table structure: id, name, image, width, height, color
            // All fields are required for tiles
            
            $name = $data['name'];
            $width = (int)$data['width'];
            $height = (int)$data['height'];
            $color = $data['color'] ?? '#CCCCCC';
            
            // Ensure color has # prefix
            if (substr($color, 0, 1) !== '#') {
                $color = '#' . $color;
            }
            
            // Save for verification later
            $submittedColor = $color;
            
            error_log("TILES UPDATE - name: $name, width: $width, height: $height, color: $color");
            
            // Handle image field
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                // File was uploaded - use it
                $image = $uploadedImg;
                error_log("Using uploaded file: $image");
            } elseif (!empty($data['image'])) {
                // Emoji or text value provided
                $image = $data['image'];
                error_log("Using text/emoji: $image");
            } else {
                // No new image - keep existing one
                // Fetch current image value
                $currentStmt = $conn->prepare("SELECT image FROM tiles WHERE id = ?");
                $currentStmt->bind_param("i", $id);
                $currentStmt->execute();
                $currentResult = $currentStmt->get_result();
                $currentRow = $currentResult->fetch_assoc();
                $image = $currentRow['image'] ?? '';
                $currentStmt->close();
                error_log("Keeping existing image: $image");
            }
            
            // Prepare UPDATE statement - update ALL fields
            $sql = "UPDATE tiles SET name = ?, image = ?, width = ?, height = ?, color = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }
            
            // Bind parameters: string, string, int, int, string, int
            $stmt->bind_param("ssiisi", $name, $image, $width, $height, $color, $id);
            
            error_log("SQL: $sql");
            error_log("Params: name='$name', image='$image', width=$width, height=$height, color='$color', id=$id");
            
            break;
            
        case 'storage':
            $color = $data['color'] ?? '#CCCCCC';
            if (substr($color, 0, 1) !== '#') {
                $color = '#' . $color;
            }
            
            // Handle image field for storage
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                // File was uploaded - use it
                $image = $uploadedImg;
            } elseif (!empty($data['image'])) {
                // Emoji or text value provided
                $image = $data['image'];
            } else {
                // No new image - keep existing one
                $currentStmt = $conn->prepare("SELECT image FROM storage WHERE id = ?");
                $currentStmt->bind_param("i", $id);
                $currentStmt->execute();
                $currentResult = $currentStmt->get_result();
                $currentRow = $currentResult->fetch_assoc();
                $image = $currentRow['image'] ?? '📦';
                $currentStmt->close();
            }
            
            $stmt = $conn->prepare("UPDATE storage SET name=?, image=?, slots=?, items_per_slot=?, tiles_needed=?, color=? WHERE id=?");
            $stmt->bind_param("ssiiisi", $data['name'], $image, $data['slots'], $data['items_per_slot'], $data['tiles_needed'], $color, $id);
            break;
            
        case 'decorations':
            // Handle image field for decorations
            $color = $data['color'] ?? '#CCCCCC';
            if (substr($color, 0, 1) !== '#') {
                $color = '#' . $color;
            }
            
            $uploadedDec = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedDec !== null) {
                $stmt = $conn->prepare("UPDATE decorations SET name=?, image=?, width=?, height=?, color=? WHERE id=?");
                $stmt->bind_param("ssiisi", $data['name'], $uploadedDec, $data['width'], $data['height'], $color, $id);
            } elseif (!empty($data['image'])) {
                $stmt = $conn->prepare("UPDATE decorations SET name=?, image=?, width=?, height=?, color=? WHERE id=?");
                $stmt->bind_param("ssiisi", $data['name'], $data['image'], $data['width'], $data['height'], $color, $id);
            } else {
                $stmt = $conn->prepare("UPDATE decorations SET name=?, width=?, height=?, color=? WHERE id=?");
                $stmt->bind_param("siisi", $data['name'], $data['width'], $data['height'], $color, $id);
            }
            break;
            
        case 'workbench':
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $stmt = $conn->prepare("UPDATE workbench SET name=?, image=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("ssii", $data['name'], $uploadedImg, $data['tiles_needed'], $id);
            } elseif (!empty($data['image'])) {
                $stmt = $conn->prepare("UPDATE workbench SET name=?, image=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("ssii", $data['name'], $data['image'], $data['tiles_needed'], $id);
            } else {
                $stmt = $conn->prepare("UPDATE workbench SET name=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("sii", $data['name'], $data['tiles_needed'], $id);
            }
            break;
            
        case 'furniture':
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $stmt = $conn->prepare("UPDATE furniture SET name=?, image=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("ssii", $data['name'], $uploadedImg, $data['tiles_needed'], $id);
            } elseif (!empty($data['image'])) {
                $stmt = $conn->prepare("UPDATE furniture SET name=?, image=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("ssii", $data['name'], $data['image'], $data['tiles_needed'], $id);
            } else {
                $stmt = $conn->prepare("UPDATE furniture SET name=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("sii", $data['name'], $data['tiles_needed'], $id);
            }
            break;
            
        case 'special':
            $uploadedImg = handleFileUpload($files, 'image_file', $uploadDir);
            if ($uploadedImg !== null) {
                $stmt = $conn->prepare("UPDATE special SET name=?, image=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("ssii", $data['name'], $uploadedImg, $data['tiles_needed'], $id);
            } elseif (!empty($data['image'])) {
                $stmt = $conn->prepare("UPDATE special SET name=?, image=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("ssii", $data['name'], $data['image'], $data['tiles_needed'], $id);
            } else {
                $stmt = $conn->prepare("UPDATE special SET name=?, tiles_needed=? WHERE id=?");
                $stmt->bind_param("sii", $data['name'], $data['tiles_needed'], $id);
            }
            break;
            
        default:
            throw new Exception("Invalid table: " . $table);
    }
    
    // Execute the statement
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        error_log("Execute failed: " . $error);
        throw new Exception("Failed to update: " . $error);
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    
    error_log("Update successful! Affected rows: " . $affectedRows);
    
    // Verification for tiles
    $debugInfo = "";
    if ($table === 'tiles' && $submittedColor !== null) {
        $verifyStmt = $conn->prepare("SELECT name, image, width, height, color FROM tiles WHERE id = ?");
        if (!$verifyStmt) {
            error_log("Verification prepare failed: " . $conn->error);
            $debugInfo = " | WARNING: Could not prepare verification";
        } else {
            $verifyStmt->bind_param("i", $id);
            if (!$verifyStmt->execute()) {
                error_log("Verification execute failed: " . $verifyStmt->error);
                $debugInfo = " | WARNING: Could not execute verification";
            } else {
                $result = $verifyStmt->get_result();
                $row = $result->fetch_assoc();
                
                if ($row) {
                    error_log("Verification - Record after update: " . print_r($row, true));
                    $dbColor = $row['color'] ?? 'NULL';
                    $matches = (strtolower($submittedColor) === strtolower($dbColor)) ? "✓" : "✗";
                    $debugInfo = " | Submitted: $submittedColor | In DB: $dbColor $matches";
                } else {
                    error_log("Verification - No row returned for ID: $id");
                    $debugInfo = " | WARNING: Record not found after update (ID: $id)";
                }
            }
            $verifyStmt->close();
        }
    }
    
    return ucfirst($table) . " updated successfully!" . $debugInfo;
}

function deleteRecord($conn, $table, $id) {
    $stmt = $conn->prepare("DELETE FROM $table WHERE id=?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ucfirst($table) . " deleted successfully!";
    } else {
        throw new Exception($conn->error);
    }
}

// Fetch all records
function fetchRecords($conn, $table) {
    $result = $conn->query("SELECT * FROM $table ORDER BY id DESC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Helper for handling file uploads securely. Returns stored filename (relative to project)
// or null if no file uploaded. Throws exception on error.
function handleFileUpload($files, $fieldName, $uploadDir) {
    if (!isset($files[$fieldName]) || $files[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $files[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Basic validation: allow images and emojis/text (we'll accept any file but sanitize extension)
    $allowedMime = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime)) {
        throw new Exception('Invalid file type. Allowed: PNG, JPG, GIF, WEBP');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Failed to move uploaded file.');
    }

    // Return path relative to project root so it can be used in src attributes
    return 'uploads/' . $safeName;
}

// Get active tab
$activeTab = $_GET['tab'] ?? 'tiles';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Base Designer</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
        }

        .header a {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .header a:hover {
            transform: translateY(-2px);
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .tabs {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 10px;
        }

        .tab {
            padding: 12px 24px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .tab:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .section-header h2 {
            color: #333;
            font-size: 24px;
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .icon-preview {
            font-size: 24px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
        }

        .modal-header h3 {
            color: #333;
            font-size: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .emoji-picker {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
            margin-top: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
        }

        .emoji-option {
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .emoji-option:hover {
            background: white;
            transform: scale(1.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ Admin Panel</h1>
            <a href="index.html">← Back to Designer</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=tiles" class="tab <?= $activeTab === 'tiles' ? 'active' : '' ?>">🟫 Tiles</a>
            <a href="?tab=storage" class="tab <?= $activeTab === 'storage' ? 'active' : '' ?>">📦 Storage</a>
            <a href="?tab=decorations" class="tab <?= $activeTab === 'decorations' ? 'active' : '' ?>">🌸 Decorations</a>
            <a href="?tab=workbench" class="tab <?= $activeTab === 'workbench' ? 'active' : '' ?>">🔨 Workbench</a>
            <a href="?tab=furniture" class="tab <?= $activeTab === 'furniture' ? 'active' : '' ?>">🪑 Furniture</a>
            <a href="?tab=special" class="tab <?= $activeTab === 'special' ? 'active' : '' ?>">⭐ Special</a>
        </div>

        <div class="content">
            <?php
            $records = fetchRecords($conn, $activeTab);
            ?>
            
            <div class="section-header">
                <h2><?= ucfirst($activeTab) ?> Management</h2>
                <button class="btn" onclick="openModal('add')">+ Add New</button>
            </div>

            <?php if (empty($records)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No <?= $activeTab ?> found</h3>
                    <p>Click "Add New" to create your first entry</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php if ($activeTab === 'tiles'): ?>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Actions</th>
                            <?php elseif ($activeTab === 'storage'): ?>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Slots</th>
                                <th>Items/Slot</th>
                                <th>Tiles Needed</th>
                                <th>Color</th>
                                <th>Actions</th>
                            <?php elseif ($activeTab === 'decorations'): ?>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Size</th>
                                <th>Color</th>
                                <th>Actions</th>
                            <?php elseif ($activeTab === 'workbench' || $activeTab === 'furniture' || $activeTab === 'special'): ?>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Tiles Needed</th>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <?php if ($activeTab === 'tiles'): ?>
                                    <td><?= $record['id'] ?></td>
                                    <td>
                                        <?php if (strpos($record['image'], 'uploads/') === 0): ?>
                                            <img src="<?= htmlspecialchars($record['image']) ?>" alt="image" style="width:36px;height:36px;object-fit:cover;border-radius:6px">
                                        <?php else: ?>
                                            <span class="icon-preview"><?= $record['image'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($record['name']) ?></td>
                                    <td><?= $record['width'] ?>×<?= $record['height'] ?></td>
                                    <td>
                                        <div style="width:30px;height:30px;border-radius:6px;background-color:<?= htmlspecialchars($record['color'] ?? '#CCCCCC') ?>;border:2px solid #ddd;"></div>
                                    </td>
                                <?php elseif ($activeTab === 'storage'): ?>
                                    <td><?= $record['id'] ?></td>
                                    <td>
                                        <?php if (strpos($record['image'], 'uploads/') === 0): ?>
                                            <img src="<?= htmlspecialchars($record['image']) ?>" alt="image" style="width:36px;height:36px;object-fit:cover;border-radius:6px">
                                        <?php else: ?>
                                            <span class="icon-preview"><?= $record['image'] ?? '📦' ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($record['name']) ?></td>
                                    <td><?= $record['slots'] ?></td>
                                    <td><?= $record['items_per_slot'] ?></td>
                                    <td><?= $record['tiles_needed'] ?></td>
                                    <td>
                                        <div style="width:30px;height:30px;border-radius:6px;background-color:<?= htmlspecialchars($record['color'] ?? '#CCCCCC') ?>;border:2px solid #ddd;"></div>
                                    </td>
                                <?php elseif ($activeTab === 'decorations'): ?>
                                    <td><?= $record['id'] ?></td>
                                    <td>
                                        <?php if (strpos($record['image'], 'uploads/') === 0): ?>
                                            <img src="<?= htmlspecialchars($record['image']) ?>" alt="image" style="width:36px;height:36px;object-fit:cover;border-radius:6px">
                                        <?php else: ?>
                                            <span class="icon-preview"><?= $record['image'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($record['name']) ?></td>
                                    <td><?= $record['width'] ?>×<?= $record['height'] ?></td>
                                    <td>
                                        <div style="width:30px;height:30px;border-radius:6px;background-color:<?= htmlspecialchars($record['color'] ?? '#CCCCCC') ?>;border:2px solid #ddd;"></div>
                                    </td>
                                <?php elseif ($activeTab === 'workbench' || $activeTab === 'furniture' || $activeTab === 'special'): ?>
                                    <td><?= $record['id'] ?></td>
                                    <td>
                                        <?php if (strpos($record['image'], 'uploads/') === 0): ?>
                                            <img src="<?= htmlspecialchars($record['image']) ?>" alt="image" style="width:36px;height:36px;object-fit:cover;border-radius:6px">
                                        <?php else: ?>
                                            <span class="icon-preview"><?= $record['image'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($record['name']) ?></td>
                                    <td><?= $record['tiles_needed'] ?></td>
                                <?php endif; ?>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-small btn-success" onclick='openModal("edit", <?= json_encode($record) ?>)'>Edit</button>
                                        <button class="btn btn-small btn-danger" onclick="confirmDelete(<?= $record['id'] ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New</h3>
                <button class="close-modal" onclick="closeModal()">×</button>
            </div>
                <form id="crudForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="table" value="<?= $activeTab ?>">
                <input type="hidden" name="id" id="recordId">

                <?php if ($activeTab === 'tiles'): ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="form-group">
                        <label>Image/Emoji or Upload Image</label>
                        <input type="text" name="image" id="image" placeholder="Click an emoji below or paste your own">
                        <input type="file" name="image_file" id="image_file" accept="image/*" style="margin-top:8px">
                        <div class="emoji-picker">
                            <?php
                            $tileEmojis = ['🌿','🟫','🪨','🪵','💧','🔥','❄️','⚡','🌊','🏜️','🌋','🏔️','🗻','🏖️','🏝️','🌾','🌱','🌲','🌳','🌴','🌵','🎋','🎍','🌸','🌺','🌻','🌹','🥀','🌷','🏵️','💐','🍄','🌰','🦀','🪴'];
                            foreach ($tileEmojis as $emoji) {
                                echo "<span class='emoji-option' onclick='selectEmoji(\"$emoji\", \"image\")'>$emoji</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Width *</label>
                            <input type="number" name="width" id="width" min="1" max="10" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Height *</label>
                            <input type="number" name="height" id="height" min="1" max="10" value="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tile Color *</label>
                        <input type="color" name="color" id="color" value="#CCCCCC" required>
                    </div>

                <?php elseif ($activeTab === 'storage'): ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="form-group">
                        <label>Image/Emoji or Upload Image</label>
                        <input type="text" name="image" id="image" placeholder="Click an emoji below or paste your own" value="📦">
                        <input type="file" name="image_file" id="image_file" accept="image/*" style="margin-top:8px">
                        <div class="emoji-picker">
                            <?php
                            $storageEmojis = ['📦','🎒','🧰','📥','🗄️','🗃️','📋','🗂️','📁','📂','🗳️','🧺','🛒','🛍️','👜','💼','🎁','📦','🔒','🔓','🗝️','🔑','💰','💵','💴','💶','💷','💳','📦'];
                            foreach ($storageEmojis as $emoji) {
                                echo "<span class='emoji-option' onclick='selectEmoji(\"$emoji\", \"image\")'>$emoji</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Slots *</label>
                            <input type="number" name="slots" id="slots" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Items per Slot *</label>
                            <input type="number" name="items_per_slot" id="items_per_slot" min="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tiles Needed *</label>
                        <input type="number" name="tiles_needed" id="tiles_needed" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Color *</label>
                        <input type="color" name="color" id="color" value="#CCCCCC" required>
                    </div>

                <?php elseif ($activeTab === 'decorations'): ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="form-group">
                        <label>Image/Emoji or Upload Image</label>
                        <input type="text" name="image" id="image" placeholder="Click an emoji below or paste your own">
                        <input type="file" name="image_file" id="image_file" accept="image/*" style="margin-top:8px">
                        <div class="emoji-picker">
                            <?php
                            $decorEmojis = ['🌸','🌺','🌻','🌹','🥀','🌷','🏵️','💐','🌳','🌲','🌴','🌵','🎋','🎍','🪴','🌾','🌱','🍄','🌰','🪨','🗿','⛲','🏛️','🗼','🗽','⛩️','🎪','🎡','🎢','🎠','🎨','🖼️','🪞','🕯️','💡'];
                            foreach ($decorEmojis as $emoji) {
                                echo "<span class='emoji-option' onclick='selectEmoji(\"$emoji\", \"image\")'>$emoji</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Width *</label>
                            <input type="number" name="width" id="width" min="1" max="10" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Height *</label>
                            <input type="number" name="height" id="height" min="1" max="10" value="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Color *</label>
                        <input type="color" name="color" id="color" value="#CCCCCC" required>
                    </div>
                    
                <?php elseif ($activeTab === 'workbench'): ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="form-group">
                        <label>Image/Emoji or Upload Image</label>
                        <input type="text" name="image" id="image" placeholder="Click an emoji below or paste your own" value="🔨">
                        <input type="file" name="image_file" id="image_file" accept="image/*" style="margin-top:8px">
                        <div class="emoji-picker">
                            <?php
                            $workbenchEmojis = ['🔨','🔧','🛠️','⚒️','🪛','🔩','⚙️','🪚','🗜️','⛏️','🪓','🔪','🗡️','⚔️','🏹','🛡️','🪃','🧲','⚗️','🧪','🔬','🔭','📡','🔦','💡','🕯️','🧯','🧰'];
                            foreach ($workbenchEmojis as $emoji) {
                                echo "<span class='emoji-option' onclick='selectEmoji(\"$emoji\", \"image\")'>$emoji</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tiles Needed *</label>
                        <input type="number" name="tiles_needed" id="tiles_needed" min="1" value="1" required>
                    </div>
                    
                <?php elseif ($activeTab === 'furniture'): ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="form-group">
                        <label>Image/Emoji or Upload Image</label>
                        <input type="text" name="image" id="image" placeholder="Click an emoji below or paste your own" value="🪑">
                        <input type="file" name="image_file" id="image_file" accept="image/*" style="margin-top:8px">
                        <div class="emoji-picker">
                            <?php
                            $furnitureEmojis = ['🪑','🛋️','🛏️','🚪','🪟','🖼️','🪞','🕰️','⏰','💡','🕯️','🔦','🧺','🧹','🧽','🪣','🧴','🧻','🚿','🛁','🚽','🚰','🧯','🪤','🧲','🔔','📯'];
                            foreach ($furnitureEmojis as $emoji) {
                                echo "<span class='emoji-option' onclick='selectEmoji(\"$emoji\", \"image\")'>$emoji</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tiles Needed *</label>
                        <input type="number" name="tiles_needed" id="tiles_needed" min="1" value="1" required>
                    </div>
                    
                <?php elseif ($activeTab === 'special'): ?>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>

                    <div class="form-group">
                        <label>Image/Emoji or Upload Image</label>
                        <input type="text" name="image" id="image" placeholder="Click an emoji below or paste your own" value="⭐">
                        <input type="file" name="image_file" id="image_file" accept="image/*" style="margin-top:8px">
                        <div class="emoji-picker">
                            <?php
                            $specialEmojis = ['⭐','🌟','✨','💫','⚡','🔥','💥','💢','💯','🏆','🥇','🥈','🥉','🎖️','🏅','🎗️','🎯','🎰','🎲','🎮','🕹️','👑','💎','💍','📿','🔮','🧿','🪬','🎁','🎀'];
                            foreach ($specialEmojis as $emoji) {
                                echo "<span class='emoji-option' onclick='selectEmoji(\"$emoji\", \"image\")'>$emoji</span>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tiles Needed *</label>
                        <input type="number" name="tiles_needed" id="tiles_needed" min="1" value="1" required>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, record = null) {
            const modal = document.getElementById('modal');
            const form = document.getElementById('crudForm');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');

            form.reset();
            formAction.value = action;

            if (action === 'add') {
                modalTitle.textContent = 'Add New <?= ucfirst($activeTab) ?>';
                document.getElementById('recordId').value = '';
            } else {
                modalTitle.textContent = 'Edit <?= ucfirst($activeTab) ?>';
                
                console.log('Editing record:', record);
                
                // Special handling for color field
                if (record.color) {
                    const colorField = document.getElementById('color');
                    if (colorField) {
                        // Ensure color has # prefix for HTML5 color picker
                        let colorValue = record.color;
                        if (colorValue && colorValue.charAt(0) !== '#') {
                            colorValue = '#' + colorValue;
                        }
                        console.log('Raw color from DB:', record.color);
                        console.log('Setting color to:', colorValue);
                        colorField.value = colorValue;
                    }
                }

                // Ensure hidden id field is populated (record.id) so POST contains the correct id
                const recordIdField = document.getElementById('recordId');
                if (recordIdField && typeof record.id !== 'undefined') {
                    recordIdField.value = record.id;
                    console.log('Set recordId =', record.id);
                }
                
                // Populate form fields
                for (let key in record) {
                    const field = document.getElementById(key);
                    if (field) {
                        // Skip color field as it's handled specially above
                        if (key !== 'color') {
                            field.value = record[key] || '';
                            console.log(`Set ${key} = ${record[key]}`);
                        }
                    }
                }
                // If the record has uploaded images (uploads/...), show a small preview
                const iconPreviewContainer = document.getElementById('icon_file');
                const imagePreviewContainer = document.getElementById('image_file');
                // Remove any previous previews
                document.querySelectorAll('.existing-preview').forEach(n => n.remove());

                if (record.icon && record.icon.indexOf('uploads/') === 0) {
                    const img = document.createElement('img');
                    img.src = record.icon;
                    img.className = 'existing-preview';
                    img.style = 'width:48px;height:48px;object-fit:cover;border-radius:6px;margin-top:8px;display:block';
                    if (iconPreviewContainer) iconPreviewContainer.insertAdjacentElement('afterend', img);
                }

                if (record.image && record.image.indexOf('uploads/') === 0) {
                    const img2 = document.createElement('img');
                    img2.src = record.image;
                    img2.className = 'existing-preview';
                    img2.style = 'width:48px;height:48px;object-fit:cover;border-radius:6px;margin-top:8px;display:block';
                    if (imagePreviewContainer) imagePreviewContainer.insertAdjacentElement('afterend', img2);
                }
            }

            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                window.location.href = '?tab=<?= $activeTab ?>&action=delete&table=<?= $activeTab ?>&id=' + id;
            }
        }

        function selectEmoji(emoji, fieldId) {
            document.getElementById(fieldId).value = emoji;
        }

        // Close modal when clicking outside
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>

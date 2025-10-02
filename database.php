<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "base_designer";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create items table (for left panel - Available Items)
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(255) NOT NULL,
    width INT(11) NOT NULL DEFAULT 1,
    height INT(11) NOT NULL DEFAULT 1,
    quantity INT(11) NOT NULL DEFAULT 0,
    type VARCHAR(50) NOT NULL,
    damage INT(11) DEFAULT NULL,
    defense INT(11) DEFAULT NULL,
    healing INT(11) DEFAULT NULL,
    bonus VARCHAR(100) DEFAULT NULL
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating items table: " . $conn->error);
}

// Create tiles table
$sql = "CREATE TABLE IF NOT EXISTS tiles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    width INT(11) NOT NULL DEFAULT 1,
    height INT(11) NOT NULL DEFAULT 1,
    color VARCHAR(7) DEFAULT '#CCCCCC'
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating tiles table: " . $conn->error);
}

// Check if color column exists, if not add it (for existing databases)
$checkColumn = $conn->query("SHOW COLUMNS FROM tiles LIKE 'color'");
if ($checkColumn->num_rows === 0) {
    $conn->query("ALTER TABLE tiles ADD COLUMN color VARCHAR(7) DEFAULT '#CCCCCC'");
}

// Create storage table
$sql = "CREATE TABLE IF NOT EXISTS storage (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slots INT(11) NOT NULL,
    items_per_slot INT(11) NOT NULL,
    tiles_needed INT(11) NOT NULL
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating storage table: " . $conn->error);
}

// Create decorations table
$sql = "CREATE TABLE IF NOT EXISTS decorations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    width INT(11) NOT NULL DEFAULT 1,
    height INT(11) NOT NULL DEFAULT 1
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating decorations table: " . $conn->error);
}

// Create grid_items table for saving grid layouts
$sql = "CREATE TABLE IF NOT EXISTS grid_items (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    grid_id INT(11) NOT NULL DEFAULT 1,
    item_id INT(11) NOT NULL,
    item_type VARCHAR(50) NOT NULL,
    x INT(11) NOT NULL,
    y INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating grid_items table: " . $conn->error);
}

// Sample data for tiles
$tiles = [
    ['name' => 'Grass', 'image' => '🌿', 'width' => 1, 'height' => 1],
    ['name' => 'Stone', 'image' => '🪨', 'width' => 1, 'height' => 1],
    ['name' => 'Wood', 'image' => '🪵', 'width' => 1, 'height' => 1],
    ['name' => 'Dirt', 'image' => '🟫', 'width' => 1, 'height' => 1],
    ['name' => 'Water', 'image' => '💧', 'width' => 1, 'height' => 1]
];

// Check if tiles table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM tiles");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Insert sample tiles
    $stmt = $conn->prepare("INSERT INTO tiles (name, image, width, height) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $name, $image, $width, $height);
    
    foreach ($tiles as $tile) {
        $name = $tile['name'];
        $image = $tile['image'];
        $width = $tile['width'];
        $height = $tile['height'];
        $stmt->execute();
    }
    
    $stmt->close();
}

// Sample data for items (left panel - Available Items)
$items = [
    ['name' => 'Sword', 'icon' => '⚔️', 'width' => 1, 'height' => 2, 'quantity' => 5, 'type' => 'Weapon', 'damage' => 25, 'defense' => null, 'healing' => null, 'bonus' => null],
    ['name' => 'Shield', 'icon' => '🛡️', 'width' => 2, 'height' => 2, 'quantity' => 3, 'type' => 'Armor', 'damage' => null, 'defense' => 20, 'healing' => null, 'bonus' => null],
    ['name' => 'Potion', 'icon' => '🧪', 'width' => 1, 'height' => 1, 'quantity' => 10, 'type' => 'Consumable', 'damage' => null, 'defense' => null, 'healing' => 50, 'bonus' => null],
    ['name' => 'Helmet', 'icon' => '🪖', 'width' => 2, 'height' => 2, 'quantity' => 2, 'type' => 'Armor', 'damage' => null, 'defense' => 15, 'healing' => null, 'bonus' => null],
    ['name' => 'Bow', 'icon' => '🏹', 'width' => 1, 'height' => 3, 'quantity' => 4, 'type' => 'Weapon', 'damage' => 18, 'defense' => null, 'healing' => null, 'bonus' => null],
    ['name' => 'Magic Staff', 'icon' => '🔮', 'width' => 1, 'height' => 3, 'quantity' => 1, 'type' => 'Weapon', 'damage' => 30, 'defense' => null, 'healing' => null, 'bonus' => null],
    ['name' => 'Boots', 'icon' => '🥾', 'width' => 2, 'height' => 1, 'quantity' => 6, 'type' => 'Armor', 'damage' => null, 'defense' => 8, 'healing' => null, 'bonus' => null],
    ['name' => 'Ring', 'icon' => '💍', 'width' => 1, 'height' => 1, 'quantity' => 8, 'type' => 'Accessory', 'damage' => null, 'defense' => null, 'healing' => null, 'bonus' => '+5 Magic']
];

// Check if items table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM items");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Insert sample items
    $stmt = $conn->prepare("INSERT INTO items (name, icon, width, height, quantity, type, damage, defense, healing, bonus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiissiiss", $name, $icon, $width, $height, $quantity, $type, $damage, $defense, $healing, $bonus);
    
    foreach ($items as $item) {
        $name = $item['name'];
        $icon = $item['icon'];
        $width = $item['width'];
        $height = $item['height'];
        $quantity = $item['quantity'];
        $type = $item['type'];
        $damage = $item['damage'];
        $defense = $item['defense'];
        $healing = $item['healing'];
        $bonus = $item['bonus'];
        $stmt->execute();
    }
    
    $stmt->close();
}

// Sample data for storage
$storages = [
    ['name' => 'Small Chest', 'slots' => 3, 'items_per_slot' => 20, 'tiles_needed' => 1],
    ['name' => 'Medium Chest', 'slots' => 6, 'items_per_slot' => 20, 'tiles_needed' => 2],
    ['name' => 'Large Chest', 'slots' => 9, 'items_per_slot' => 30, 'tiles_needed' => 4],
    ['name' => 'Storage Rack', 'slots' => 12, 'items_per_slot' => 40, 'tiles_needed' => 6]
];

// Check if storage table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM storage");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Insert sample storage items
    $stmt = $conn->prepare("INSERT INTO storage (name, slots, items_per_slot, tiles_needed) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siii", $name, $slots, $items_per_slot, $tiles_needed);
    
    foreach ($storages as $storage) {
        $name = $storage['name'];
        $slots = $storage['slots'];
        $items_per_slot = $storage['items_per_slot'];
        $tiles_needed = $storage['tiles_needed'];
        $stmt->execute();
    }
    
    $stmt->close();
}

// Sample data for decorations
$decorations = [
    ['name' => 'Flower', 'image' => '🌸', 'width' => 1, 'height' => 1],
    ['name' => 'Tree', 'image' => '🌳', 'width' => 2, 'height' => 2],
    ['name' => 'Rock', 'image' => '🪨', 'width' => 1, 'height' => 1],
    ['name' => 'Fence', 'image' => '🧱', 'width' => 3, 'height' => 1]
];

// Check if decorations table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM decorations");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Insert sample decorations
    $stmt = $conn->prepare("INSERT INTO decorations (name, image, width, height) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $name, $image, $width, $height);
    
    foreach ($decorations as $decoration) {
        $name = $decoration['name'];
        $image = $decoration['image'];
        $width = $decoration['width'];
        $height = $decoration['height'];
        $stmt->execute();
    }
    
    $stmt->close();
}

?>
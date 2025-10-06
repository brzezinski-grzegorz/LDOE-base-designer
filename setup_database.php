<?php
/**
 * LDOE Base Designer - Database Setup Script
 * 
 * This script creates the database (if needed), creates all required tables,
 * and optionally seeds them with production data from the LDOE game.
 * 
 * USAGE:
 * ------
 * Web (browser):
 *   - Create tables only:      https://yourhost/setup_database.php
 *   - Create + seed data:      https://yourhost/setup_database.php?seed=1
 * 
 * CLI (command line):
 *   - Create tables only:      php setup_database.php
 *   - Create + seed data:      php setup_database.php --seed
 * 
 * PREREQUISITES:
 * --------------
 * 1. Edit dbcon.php with your MySQL credentials
 * 2. Ensure MySQL user has CREATE DATABASE permission (or create DB manually first)
 * 3. Ensure the uploads/ directory exists for image files
 * 
 * NOTES:
 * ------
 * - Script is idempotent: safe to run multiple times
 * - Seeding only inserts data if tables are empty
 * - Uses utf8mb4 charset for emoji/unicode support
 * - All columns defined in CREATE TABLE (no post-creation ALTERs needed)
 */

require_once __DIR__ . '/dbcon.php';

$cli = (php_sapi_name() === 'cli');
$seedRequested = false;

if ($cli) {
    // parse CLI args
    foreach ($argv as $arg) {
        if ($arg === '--seed' || $arg === '-s') {
            $seedRequested = true;
        }
    }
} else {
    if (!empty($_GET['seed']) && ($_GET['seed'] === '1' || strtolower($_GET['seed']) === 'true')) {
        $seedRequested = true;
    }
}

$DB = isset($DB_NAME) ? $DB_NAME : null;
if (!$DB) {
    $msg = "dbcon.php does not specify a database name (\$DB_NAME). Please set it before running this script.";
    if ($cli) { echo $msg . PHP_EOL; exit(1); } else { die($msg); }
}

// If the connection is not allowed to select DB, try to create it (dev convenience)
if (empty($db_selected) || !$db_selected) {
    if ($conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string($DB) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") !== TRUE) {
        $msg = "Error creating database: " . $conn->error;
        if ($cli) { echo $msg . PHP_EOL; exit(1); } else { die($msg); }
    }
    if (!$conn->select_db($DB)) {
        $msg = "Could not select database {$DB}: " . $conn->error;
        if ($cli) { echo $msg . PHP_EOL; exit(1); } else { die($msg); }
    }
}

// Ensure utf8mb4
if (!@$conn->set_charset('utf8mb4')) {
    error_log("Warning: could not set charset utf8mb4: " . $conn->error);
}

$errors = [];
function run($sql, $conn, &$errors) {
    if ($conn->query($sql) !== TRUE) {
        $errors[] = $conn->error . " -- SQL: " . $sql;
        return false;
    }
    return true;
}

// Table creation statements with all columns included
$stmts = [
"CREATE TABLE IF NOT EXISTS tiles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    width INT(11) NOT NULL DEFAULT 1,
    height INT(11) NOT NULL DEFAULT 1,
    color VARCHAR(7) DEFAULT '#CCCCCC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS storage (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL DEFAULT '📦',
    slots INT(11) NOT NULL,
    items_per_slot INT(11) NOT NULL,
    tiles_needed INT(11) NOT NULL,
    color VARCHAR(7) DEFAULT '#CCCCCC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS decorations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    width INT(11) NOT NULL DEFAULT 1,
    height INT(11) NOT NULL DEFAULT 1,
    color VARCHAR(7) DEFAULT '#CCCCCC'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS workbench (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    tiles_needed INT(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS furniture (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    tiles_needed INT(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS special (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    tiles_needed INT(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($stmts as $sql) {
    run($sql, $conn, $errors);
}

// Report status
if ($cli) {
    echo "Tables created/verified for database: {$DB}\n";
    if (!empty($errors)) {
        echo "Warnings/Errors:\n";
        foreach ($errors as $e) { echo " - " . $e . "\n"; }
    }
} else {
    echo "<h2>Setup Database</h2>";
    echo "<p>Tables created/verified for database: <strong>" . htmlspecialchars($DB) . "</strong></p>";
    if (!empty($errors)) {
        echo "<h3>Warnings/Errors</h3><ul>";
        foreach ($errors as $e) { echo "<li>" . htmlspecialchars($e) . "</li>"; }
        echo "</ul>";
    }
}

// Seeding (optional) - Production data from LDOE game
if ($seedRequested) {
    // Helper to escape values appropriately
    $esc = function($v) use ($conn) {
        if (is_null($v)) return 'NULL';
        if (is_int($v) || is_float($v)) return $v;
        return "'" . $conn->real_escape_string($v) . "'";
    };

    // Production tiles data
    $tiles = [
        ['id' => '1', 'name' => 'Grass', 'image' => 'uploads/9f367b38c44cacb8.png', 'width' => '1', 'height' => '1', 'color' => '#2bff00'],
        ['id' => '6', 'name' => 'Wooden Floor', 'image' => 'uploads/8a474bb4d0f4f63f.png', 'width' => '1', 'height' => '1', 'color' => '#bcbf08'],
        ['id' => '7', 'name' => 'Boarded Floor', 'image' => 'uploads/8f11cf6ca4986874.png', 'width' => '1', 'height' => '1', 'color' => '#ffea00'],
        ['id' => '8', 'name' => 'Stone Floor', 'image' => 'uploads/9bac15915930b65b.png', 'width' => '1', 'height' => '1', 'color' => '#CCCCCC'],
        ['id' => '9', 'name' => 'Metal Floor', 'image' => 'uploads/5e66035fc9f41058.png', 'width' => '1', 'height' => '1', 'color' => '#652424'],
        ['id' => '10', 'name' => 'Brick Floor', 'image' => 'uploads/6c6b9c4c2a0cdd2c.png', 'width' => '1', 'height' => '1', 'color' => '#473838']
    ];

    $result = $conn->query("SELECT COUNT(*) as c FROM tiles");
    $count = 0; if ($result) { $count = intval($result->fetch_assoc()['c']); }
    if ($count === 0) {
        foreach ($tiles as $t) {
            $sql = "INSERT INTO tiles (id, name, image, width, height, color) VALUES (" . 
                intval($t['id']) . ", " . $esc($t['name']) . ", " . $esc($t['image']) . ", " . 
                intval($t['width']) . ", " . intval($t['height']) . ", " . $esc($t['color']) . ")";
            run($sql, $conn, $errors);
        }
    }

    

    // Production storage data
    $storages = [
        ['id' => '9', 'name' => 'Small Box', 'image' => 'uploads/5952a69c9882a131.webp', 'slots' => '15', 'items_per_slot' => '20', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '10', 'name' => 'Chest', 'image' => 'uploads/7c2034eb540f707d.webp', 'slots' => '15', 'items_per_slot' => '20', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '11', 'name' => 'Trunk', 'image' => 'uploads/2c1071ada56a7569.webp', 'slots' => '45', 'items_per_slot' => '20', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '12', 'name' => 'Rack', 'image' => 'uploads/a7280e688fcdbcc2.webp', 'slots' => '75', 'items_per_slot' => '20', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '13', 'name' => 'Bookshelf', 'image' => 'uploads/01e8a7555f4ef2d5.png', 'slots' => '10', 'items_per_slot' => '300', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '14', 'name' => 'Warehouse', 'image' => 'uploads/428cb0ae01137cbf.webp', 'slots' => '20', 'items_per_slot' => '300', 'tiles_needed' => '2', 'color' => '#cccccc'],
        ['id' => '15', 'name' => 'Fridge', 'image' => 'uploads/5c8492c306aa8a68.webp', 'slots' => '10', 'items_per_slot' => '300', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '16', 'name' => 'Another Round', 'image' => 'uploads/f54296382b4b7b09.webp', 'slots' => '10', 'items_per_slot' => '300', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '17', 'name' => 'Medicine Cabinet', 'image' => 'uploads/afbac905373e7ed7.webp', 'slots' => '10', 'items_per_slot' => '300', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '18', 'name' => 'Triumph', 'image' => 'uploads/a1ce74d358ff48e3.webp', 'slots' => '20', 'items_per_slot' => '1', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '19', 'name' => 'Division Box', 'image' => 'uploads/93c3ca6d15f2ea1e.png', 'slots' => '10', 'items_per_slot' => '300', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '20', 'name' => 'Electronics Crate', 'image' => 'uploads/117198fb5b1cdd6f.png', 'slots' => '15', 'items_per_slot' => '300', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '21', 'name' => 'Fish Fridge', 'image' => 'uploads/195a5eab29337924.png', 'slots' => '20', 'items_per_slot' => '100', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '22', 'name' => 'Fuel Tank', 'image' => 'uploads/bd38ee49b8612401.png', 'slots' => '1', 'items_per_slot' => '3000', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '23', 'name' => 'Sewing Rack', 'image' => 'uploads/64ac440619e6f590.png', 'slots' => '15', 'items_per_slot' => '200', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '24', 'name' => 'Storage For Explosive', 'image' => 'uploads/e0dc1c6f80a1b1e6.png', 'slots' => '8', 'items_per_slot' => '50', 'tiles_needed' => '1', 'color' => '#cccccc'],
        ['id' => '25', 'name' => 'Ore Storage', 'image' => 'uploads/e4863559b5b8e385.png', 'slots' => '8', 'items_per_slot' => '50', 'tiles_needed' => '2', 'color' => '#cccccc'],
        ['id' => '26', 'name' => 'Storage For Equipments', 'image' => 'uploads/f0ea5fd068caa19d.png', 'slots' => '4', 'items_per_slot' => '10', 'tiles_needed' => '1', 'color' => '#cccccc']
    ];

    $result = $conn->query("SELECT COUNT(*) as c FROM storage");
    $count = 0; if ($result) { $count = intval($result->fetch_assoc()['c']); }
    if ($count === 0) {
        foreach ($storages as $s) {
            $sql = "INSERT INTO storage (id, name, image, slots, items_per_slot, tiles_needed, color) VALUES (" . 
                intval($s['id']) . ", " . $esc($s['name']) . ", " . $esc($s['image']) . ", " . 
                intval($s['slots']) . ", " . intval($s['items_per_slot']) . ", " . intval($s['tiles_needed']) . ", " . 
                $esc($s['color']) . ")";
            run($sql, $conn, $errors);
        }
    }

    // Production decorations data
    $decorations = [
        ['id' => '17', 'name' => 'Pine tree seedling', 'image' => 'uploads/1a06c087332b1d94.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '18', 'name' => 'Oak seedling', 'image' => 'uploads/065ac6e9b168faba.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '19', 'name' => 'Sakura seedling', 'image' => 'uploads/b4f1e96161fd7789.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '20', 'name' => 'Hedge', 'image' => 'uploads/bc4c464039262014.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '21', 'name' => 'Spirea', 'image' => 'uploads/2f4c38b02b73a85f.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '22', 'name' => 'Flower in a pot', 'image' => 'uploads/b07ad5acc6ff5b4e.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '23', 'name' => 'Infected Specimen', 'image' => 'uploads/d199772b73553903.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '24', 'name' => 'Scarecrow', 'image' => 'uploads/1a57065c09e6c6bc.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '25', 'name' => 'Spooky Pumpkins', 'image' => 'uploads/60a9e92734e48ed4.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '26', 'name' => 'Jack-o\'-lantern', 'image' => 'uploads/cae8ef7665af10fe.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '27', 'name' => 'Fireplace', 'image' => 'uploads/c6703eefe613bd6a.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '28', 'name' => 'Wooden deer', 'image' => 'uploads/9799f64c3f1eacd5.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '29', 'name' => 'Snow globe', 'image' => 'uploads/fc9e01e509664ebd.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '30', 'name' => 'Snowman', 'image' => 'uploads/ec20380bd68d8f9d.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '31', 'name' => 'Spring Flower Garden', 'image' => 'uploads/7e5fbe0bbced0fce.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '32', 'name' => 'Spring Armchair', 'image' => 'uploads/7581f9629a8c0242.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '33', 'name' => 'Spring Flowerbed', 'image' => 'uploads/26f0a119e8a25c2d.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '34', 'name' => 'Crater Model', 'image' => 'uploads/475a11e3354bc4a4.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '35', 'name' => 'Hologram', 'image' => 'uploads/9ad4a7379f810419.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '36', 'name' => 'Barbell', 'image' => 'uploads/5b931c22e1f4db7a.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '37', 'name' => 'Gravestone', 'image' => 'uploads/d34e8238072b0b84.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '38', 'name' => 'Basketball Hoop', 'image' => 'uploads/acf98587ca863118.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '39', 'name' => 'Big catch', 'image' => 'uploads/f1c6f15bb1edada5.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '40', 'name' => 'Last Stop', 'image' => 'uploads/5e432d861a2d11a2.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '41', 'name' => 'Gilbert', 'image' => 'uploads/035bd16ff24ec883.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '42', 'name' => 'Comfort', 'image' => 'uploads/c760a5f0a2cf9088.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '43', 'name' => 'Acquired taste', 'image' => 'uploads/d027de8ea394ad02.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '44', 'name' => 'Turbo', 'image' => 'uploads/5fc828c3aaab54a0.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '45', 'name' => 'Surf\'s up', 'image' => 'uploads/314490b3d1e27047.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '46', 'name' => 'Ritual Fire', 'image' => 'uploads/bae49ba6ad263d4b.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '47', 'name' => 'Break', 'image' => 'uploads/a38393c6c50c99ee.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '48', 'name' => 'Krumpy', 'image' => 'uploads/be65325ace213314.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '49', 'name' => 'Festive Arch', 'image' => 'uploads/4964dd1590fca5f2.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '50', 'name' => 'Brain Freeze', 'image' => 'uploads/ccbced316a0602fe.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '51', 'name' => 'Treadmill', 'image' => 'uploads/8ec37a7cfd1985bc.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '52', 'name' => 'Windvane', 'image' => 'uploads/e9a82ade547794f8.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '53', 'name' => 'Pagoda', 'image' => 'uploads/7575355d14d2d437.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '54', 'name' => 'Traffic Lights', 'image' => 'uploads/356e98ef53d72dcf.webp', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '55', 'name' => 'Broken Slot Machine', 'image' => 'uploads/5415bbdf1621a2a3.png', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '56', 'name' => 'Carrots Dispenser', 'image' => 'uploads/aadd21f006552e21.png', 'width' => '1', 'height' => '1', 'color' => '#cccccc'],
        ['id' => '57', 'name' => 'Lucky Wheel', 'image' => 'uploads/f409a20c55a30954.png', 'width' => '1', 'height' => '1', 'color' => '#cccccc']
    ];

    $result = $conn->query("SELECT COUNT(*) as c FROM decorations");
    $count = 0; if ($result) { $count = intval($result->fetch_assoc()['c']); }
    if ($count === 0) {
        foreach ($decorations as $d) {
            $sql = "INSERT INTO decorations (id, name, image, width, height, color) VALUES (" . 
                intval($d['id']) . ", " . $esc($d['name']) . ", " . $esc($d['image']) . ", " . 
                intval($d['width']) . ", " . intval($d['height']) . ", " . $esc($d['color']) . ")";
            run($sql, $conn, $errors);
        }
    }

    // Production workbench data
    $workbenches = [
        ['id' => '1', 'name' => 'Garden Bed', 'image' => 'uploads/10b56fd7dd68406c.webp', 'tiles_needed' => '4'],
        ['id' => '2', 'name' => 'Woodworking Bench', 'image' => 'uploads/556ac118a5a89087.webp', 'tiles_needed' => '1'],
        ['id' => '3', 'name' => 'Rain Catcher', 'image' => 'uploads/dcfa9f6225f8d10b.webp', 'tiles_needed' => '1'],
        ['id' => '4', 'name' => 'Campfire', 'image' => 'uploads/04b340d7d5b0a0d9.webp', 'tiles_needed' => '1'],
        ['id' => '5', 'name' => 'Meat Dryer', 'image' => 'uploads/cfad3071ed3149b8.webp', 'tiles_needed' => '1'],
        ['id' => '6', 'name' => 'Melting Furnace', 'image' => 'uploads/4a4aa0d984c613e6.webp', 'tiles_needed' => '1'],
        ['id' => '7', 'name' => 'Tanning Rack', 'image' => 'uploads/95d3415faf8b4742.webp', 'tiles_needed' => '1'],
        ['id' => '8', 'name' => 'Stonecutter\'s Table', 'image' => 'uploads/9b37b372e9345531.webp', 'tiles_needed' => '1'],
        ['id' => '9', 'name' => 'Kitchen Stove', 'image' => 'uploads/ce55ac212f854b81.webp', 'tiles_needed' => '1'],
        ['id' => '10', 'name' => 'Workbench', 'image' => 'uploads/f154d2e4e86dcc7d.webp', 'tiles_needed' => '1'],
        ['id' => '11', 'name' => 'Sewing Table', 'image' => 'uploads/2389d66dc2be4160.webp', 'tiles_needed' => '1'],
        ['id' => '12', 'name' => 'Medical Table', 'image' => 'uploads/757f2152acd14608.webp', 'tiles_needed' => '1'],
        ['id' => '13', 'name' => 'Refined Melting Furnace', 'image' => 'uploads/d43edfafadd9c59a.webp', 'tiles_needed' => '1'],
        ['id' => '14', 'name' => 'Recycler', 'image' => 'uploads/71794d4413b1a345.webp', 'tiles_needed' => '1'],
        ['id' => '15', 'name' => 'Repair Workbench', 'image' => 'uploads/20a67e2df0740695.png', 'tiles_needed' => '1'],
        ['id' => '16', 'name' => 'Pressing Machine', 'image' => 'uploads/80352c0ddfe63978.webp', 'tiles_needed' => '1'],
        ['id' => '17', 'name' => 'Electronics Lab', 'image' => 'uploads/84b1aac79939b478.webp', 'tiles_needed' => '1'],
        ['id' => '18', 'name' => 'Chemistry Station', 'image' => 'uploads/a250ef7922f00461.webp', 'tiles_needed' => '1'],
        ['id' => '19', 'name' => 'Hydroponic System', 'image' => 'uploads/b7c737d5cc3a43b4.webp', 'tiles_needed' => '1']
    ];

    $result = $conn->query("SELECT COUNT(*) as c FROM workbench");
    $count = 0; if ($result) { $count = intval($result->fetch_assoc()['c']); }
    if ($count === 0) {
        foreach ($workbenches as $w) {
            $sql = "INSERT INTO workbench (id, name, image, tiles_needed) VALUES (" . 
                intval($w['id']) . ", " . $esc($w['name']) . ", " . $esc($w['image']) . ", " . 
                intval($w['tiles_needed']) . ")";
            run($sql, $conn, $errors);
        }
    }

    // Production furniture data
    $furnitures = [
        ['id' => '1', 'name' => 'Wardrobe', 'image' => 'uploads/28e0e71b535cd869.webp', 'tiles_needed' => '1'],
        ['id' => '2', 'name' => 'Shower', 'image' => 'uploads/fa1373e23603d092.webp', 'tiles_needed' => '1'],
        ['id' => '3', 'name' => 'Doormat', 'image' => 'uploads/d1dc30259df43aea.webp', 'tiles_needed' => '1'],
        ['id' => '4', 'name' => 'Mirror', 'image' => 'uploads/a87f2db20623a926.webp', 'tiles_needed' => '1'],
        ['id' => '5', 'name' => 'Hand Pump', 'image' => 'uploads/50f5e22638f781d9.webp', 'tiles_needed' => '1'],
        ['id' => '6', 'name' => 'Weapon Stand', 'image' => 'uploads/081e3abbfebdc47f.webp', 'tiles_needed' => '1'],
        ['id' => '7', 'name' => 'Outdoor Toilet', 'image' => 'uploads/0c8c8a0fc0dba3ac.webp', 'tiles_needed' => '1'],
        ['id' => '8', 'name' => 'Mannequin', 'image' => 'uploads/f7f34b706b1fc5d1.webp', 'tiles_needed' => '1'],
        ['id' => '9', 'name' => 'Houseplant', 'image' => 'uploads/2de11de0db962e77.webp', 'tiles_needed' => '1'],
        ['id' => '10', 'name' => 'Dining Table', 'image' => 'uploads/d4ee30276b3e6a98.webp', 'tiles_needed' => '1'],
        ['id' => '11', 'name' => 'Cozy Couch', 'image' => 'uploads/b3ba258ce8165da3.webp', 'tiles_needed' => '1'],
        ['id' => '12', 'name' => 'Floor Lamp', 'image' => 'uploads/0c42729a0c0be12f.webp', 'tiles_needed' => '1'],
        ['id' => '13', 'name' => 'Comfortable Bed', 'image' => 'uploads/4d4a8faaddd54e00.webp', 'tiles_needed' => '1'],
        ['id' => '14', 'name' => 'Spruce', 'image' => 'uploads/65f262513c3a563a.webp', 'tiles_needed' => '1'],
        ['id' => '15', 'name' => 'Tire Flowerbed', 'image' => 'uploads/69408b6752be3b76.webp', 'tiles_needed' => '1'],
        ['id' => '16', 'name' => 'Bath with flowers', 'image' => 'uploads/296a1a6fd12a18c6.webp', 'tiles_needed' => '1'],
        ['id' => '17', 'name' => 'Decorative Pond', 'image' => 'uploads/85a4016b038d815f.webp', 'tiles_needed' => '1'],
        ['id' => '18', 'name' => 'Sculpture', 'image' => 'uploads/84f99312f75d189c.webp', 'tiles_needed' => '1'],
        ['id' => '19', 'name' => 'Horse Feeder', 'image' => 'uploads/ca15808c14e72c72.webp', 'tiles_needed' => '1'],
        ['id' => '20', 'name' => 'Spike Trap', 'image' => 'uploads/7af9f35d85c34b35.webp', 'tiles_needed' => '1'],
        ['id' => '21', 'name' => 'Trip Wire Trap', 'image' => 'uploads/848769b80c264cae.webp', 'tiles_needed' => '1'],
        ['id' => '22', 'name' => 'Barbed Wire', 'image' => 'uploads/d85a42bac6715434.webp', 'tiles_needed' => '1'],
        ['id' => '23', 'name' => 'Turret', 'image' => 'uploads/7128809d35d3465a.webp', 'tiles_needed' => '1'],
        ['id' => '24', 'name' => 'The Blind One\'s Head', 'image' => 'uploads/3cee7da28d238bdd.webp', 'tiles_needed' => '1'],
        ['id' => '25', 'name' => 'Witch\'s Head', 'image' => 'uploads/896c102a2a0e36e7.webp', 'tiles_needed' => '1']
    ];

    $result = $conn->query("SELECT COUNT(*) as c FROM furniture");
    $count = 0; if ($result) { $count = intval($result->fetch_assoc()['c']); }
    if ($count === 0) {
        foreach ($furnitures as $f) {
            $sql = "INSERT INTO furniture (id, name, image, tiles_needed) VALUES (" . 
                intval($f['id']) . ", " . $esc($f['name']) . ", " . $esc($f['image']) . ", " . 
                intval($f['tiles_needed']) . ")";
            run($sql, $conn, $errors);
        }
    }

    // Production special data
    $specials = [
        ['id' => '1', 'name' => 'CB Radio', 'image' => 'uploads/6f63ebb7a744d999.webp', 'tiles_needed' => '1'],
        ['id' => '2', 'name' => 'Dog Crate', 'image' => 'uploads/7aa9d3ffdd8abc74.webp', 'tiles_needed' => '12'],
        ['id' => '3', 'name' => 'Gunsmith Bench', 'image' => 'uploads/1ce37cd7806ec960.webp', 'tiles_needed' => '2'],
        ['id' => '4', 'name' => 'Electric Generator', 'image' => 'uploads/020da0e760fc5d8e.webp', 'tiles_needed' => '1'],
        ['id' => '5', 'name' => 'Acid Bath', 'image' => 'uploads/858df0634b11fa9f.webp', 'tiles_needed' => '4'],
        ['id' => '6', 'name' => 'Drone and Docking Station', 'image' => 'uploads/5ad9dd8fdc2d8d1d.png', 'tiles_needed' => '1'],
        ['id' => '7', 'name' => 'Drone Upgrade Workbench', 'image' => 'uploads/810a3b3aa338f9f6.png', 'tiles_needed' => '1']
    ];

    $result = $conn->query("SELECT COUNT(*) as c FROM special");
    $count = 0; if ($result) { $count = intval($result->fetch_assoc()['c']); }
    if ($count === 0) {
        foreach ($specials as $s) {
            $sql = "INSERT INTO special (id, name, image, tiles_needed) VALUES (" . 
                intval($s['id']) . ", " . $esc($s['name']) . ", " . $esc($s['image']) . ", " . 
                intval($s['tiles_needed']) . ")";
            run($sql, $conn, $errors);
        }
    }

    if ($cli) {
        echo "Seeding completed.\n";
        if (!empty($errors)) {
            echo "Warnings/Errors during seeding:\n";
            foreach ($errors as $e) { echo " - " . $e . "\n"; }
        }
    } else {
        echo "<p>Seeding completed.</p>";
        if (!empty($errors)) {
            echo "<h3>Warnings/Errors during seeding</h3><ul>";
            foreach ($errors as $e) { echo "<li>" . htmlspecialchars($e) . "</li>"; }
            echo "</ul>";
        }
    }
}

if ($cli) exit(0);

?>
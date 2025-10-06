<?php
// Single database connection/config file
// Update these values for your environment
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'base_designer';

// Attempt to connect directly to the database
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli && !$mysqli->connect_errno) {
    // Connected and database exists
    $conn = $mysqli;
    $db_selected = true;
} else {
    // Try connecting without selecting a DB (useful for first-run setup where DB may not exist)
    $mysqliNoDb = @new mysqli($DB_HOST, $DB_USER, $DB_PASS);
    if ($mysqliNoDb && !$mysqliNoDb->connect_errno) {
        $conn = $mysqliNoDb;
        $db_selected = false; // caller may create/select the DB
    } else {
        // Fatal: cannot connect to MySQL server
        header('Content-Type: application/json');
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed', 'message' => $mysqliNoDb->connect_error ?? $mysqli->connect_error ?? 'Unknown error']));
    }
}

// Expose variables: $conn (mysqli) and $DB_NAME, $db_selected
?>
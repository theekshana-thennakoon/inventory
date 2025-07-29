<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'fot_media_inventory'; // Change to your database name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all table names
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

$db_data = [];

foreach ($tables as $table) {
    $table_data = [];
    $res = $conn->query("SELECT * FROM `$table`");
    while ($row = $res->fetch_assoc()) {
        $table_data[] = $row;
    }
    $db_data[$table] = $table_data;
}

// Output as JSON file
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="database_export.json"');
echo json_encode($db_data, JSON_PRETTY_PRINT);

$conn->close();
exit;

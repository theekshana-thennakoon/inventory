<?php
if (isset($_POST['export_sql'])) {
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';
    $dbname = 'fot_media_inventory';

    $backupFile = 'inventory_export_' . date('Ymd_His') . '.json';

    // Connect to database
    $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if ($mysqli->connect_error) {
        echo "Database connection failed.";
        exit;
    }

    $tables = [];
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $exportData = [];
    foreach ($tables as $table) {
        $tableData = [];
        $res = $mysqli->query("SELECT * FROM `$table`");
        while ($row = $res->fetch_assoc()) {
            $tableData[] = $row;
        }
        $exportData[$table] = $tableData;
    }

    $mysqli->close();

    $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $backupFile . '"');
    header('Content-Length: ' . strlen($jsonContent));
    echo $jsonContent;
    exit;
}

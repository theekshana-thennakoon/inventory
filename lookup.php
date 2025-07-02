<?php
header('Content-Type: application/json');

require_once 'config/database.php';

session_start();

if (isset($_SESSION['technical_officer_id']) && isset($_SESSION['technical_officer_name'])) {
    header("Location: dashboard.php");
    exit();
}

// Get the barcode from POST data
$barcode = $_POST['barcode'] ?? '';

if (empty($barcode)) {
    echo json_encode(['error' => 'No barcode provided']);
    exit;
}

// Look up the product in the database
try {
    $stmt = $pdo->prepare("SELECT * FROM technical_officers WHERE email = ?");
    $stmt->bindParam(':barcode', $barcode);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Return user information
        echo json_encode([
            'name' => $user['name'],
            'email' => $user['email'],
            'status' => $user['status']
        ]);
    } else {
        // If not found in database, you could integrate with an external API here
        // For example, the Open Food Facts API for food products
        echo json_encode(['error' => 'User not found in database']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}

<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

$itemId = $_POST['id'];

try {
    // First delete from issuance_items
    $stmt = $pdo->prepare("DELETE FROM issuance_items WHERE item_id = ?");
    $stmt->execute([$itemId]);

    // Then delete the item
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$itemId]);

    header("Location: index.php?success=Item deleted successfully");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=Error deleting item: " . urlencode($e->getMessage()));
    exit();
}

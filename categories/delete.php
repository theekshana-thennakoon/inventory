<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

$categoryId = $_POST['id'];

try {
    // First update items to remove category reference
    $stmt = $pdo->prepare("UPDATE items SET category_id = NULL WHERE category_id = ?");
    $stmt->execute([$categoryId]);

    // Then delete the category
    $stmt = $pdo->prepare("DELETE FROM item_categories WHERE id = ?");
    $stmt->execute([$categoryId]);

    header("Location: index.php?success=Category deleted successfully");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=Error deleting category: " . urlencode($e->getMessage()));
    exit();
}

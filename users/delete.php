<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_POST['id'];

// Prevent deleting the currently logged in user
if ($userId == $_SESSION['technical_officer_id']) {
    header("Location: index.php?error=Cannot delete your own account");
    exit();
}

try {
    // First update issuances to NULL (or you could reassign to another user)
    $stmt = $pdo->prepare("UPDATE issuances SET technical_officer_id = NULL WHERE technical_officer_id = ?");
    $stmt->execute([$userId]);

    // Then delete the user
    $stmt = $pdo->prepare("DELETE FROM technical_officers WHERE id = ?");
    $stmt->execute([$userId]);

    header("Location: index.php?success=User deleted successfully");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=Error deleting user: " . urlencode($e->getMessage()));
    exit();
}

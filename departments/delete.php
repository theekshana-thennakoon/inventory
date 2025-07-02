<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: index.php");
    exit();
}

$departmentId = $_POST['id'];

try {
    $pdo->beginTransaction();

    // First delete members
    $stmt = $pdo->prepare("DELETE FROM department_members WHERE department_id = ?");
    $stmt->execute([$departmentId]);

    // Then delete the department
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$departmentId]);

    $pdo->commit();

    header("Location: index.php?success=Department deleted successfully");
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=Error deleting department: " . urlencode($e->getMessage()));
    exit();
}
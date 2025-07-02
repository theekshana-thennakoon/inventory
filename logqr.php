<?php
require_once 'config/database.php';

session_start();
if (isset($_POST['logbarcode'])) {
    $result = $_POST['result'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM technical_officers WHERE email = ? AND (status = 'to' OR status = 'admin')");
    $stmt->execute([$result]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['technical_officer_id'] = $user['id'];
        $_SESSION['technical_officer_name'] = $user['name'];
        $_SESSION['technical_officer_status'] = $user['status'];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid barcode";
        header("Location: login.php");
        exit();
    }
}

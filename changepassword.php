<?php
require_once './config/database.php';
require_once './includes/auth.php';

redirectIfNotLoggedIn();

if (!isset($_SESSION['technical_officer_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['technical_officer_id'];
$userName = $_SESSION['technical_officer_name'];

// Get user info
$errors = [];
$password = $confirm_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password is optional for edits
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        $stmt = $pdo->prepare("SELECT * FROM technical_officers WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_password, $user['password'])) {
            if (empty($errors)) {
                try {
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE technical_officers SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $userId]);
                    }

                    header("Location: profile.php");
                    exit();
                } catch (PDOException $e) {
                    $errors['database'] = "Error updating password: " . $e->getMessage();
                }
            } else {
                $errors['current_password'] = 'Current password is incorrect';
            }
        } else {
            $errors['current_password'] = 'Current password is incorrect';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($user['name']); ?> - FOT Media Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include './includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-lg-3 col-md-4 d-md-block sidebar collapse" id="sidebarMenu">
                <?php include './includes/sidebar.php'; ?>
            </div>

            <main class="col-lg-9 col-md-8 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="bi bi-person"></i> Edit <?php echo htmlspecialchars($userName); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="view.php?id=<?php echo $userId; ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to View
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current password *</label>
                                        <input type="password" class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>"
                                            id="current_password" name="current_password" required>
                                        <?php if (isset($errors['current_password'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['current_password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">New Password</label>
                                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                                id="password" name="password">
                                            <?php if (isset($errors['password'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                            <?php endif; ?>
                                            <small class="text-muted">Leave blank to keep current password</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                                id="confirm_password" name="confirm_password">
                                            <?php if (isset($errors['confirm_password'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Change password
                                    </button>
                                    <a href="profile.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/password-strength.js"></script>
</body>

</html>
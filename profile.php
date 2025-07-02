<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
$id = $_SESSION['technical_officer_id'];
$name = $_SESSION['technical_officer_name'];
$status = $_SESSION['technical_officer_status'];

// Fetch user data from database
$stmt = $pdo->prepare('SELECT * FROM technical_officers WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die('User not found.');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile - Inventory System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
            min-height: 100vh;
        }

        .profile-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
        }

        .card.profile-card {
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            border-radius: 20px;
            border: none;
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem 2.5rem;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid #0d6efd;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #0d6efd;
        }

        .profile-info {
            margin-bottom: 1.5rem;
        }

        .logout-btn {
            width: 100%;
            font-weight: 500;
        }

        @media (max-width: 576px) {}
    </style>
</head>


<?php include './includes/header.php'; ?>
<div class="container profile-container">


    <div class="card profile-card">
        <div class="d-flex flex-column align-items-center">
            <div class="profile-avatar">
                <?php
                // Show first letter of name as avatar
                echo strtoupper(substr($user['name'], 0, 1));
                ?>
            </div>
            <h4 class="card-title mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
            <span class="badge bg-primary mb-3"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span>
        </div>
        <div class="profile-info w-100">
            <div class="row mb-2">
                <div class="col-12 col-md-6">
                    <p class="mb-2">
                        <i class="bi bi-envelope"></i>
                        <strong>Reg no / Email Address:</strong>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                </div>
                <div class="col-12 col-md-6">
                    <p class="mb-0">
                        <i class="bi bi-person-badge"></i>
                        <strong>Role:</strong>
                        <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                    </p>
                </div>
            </div>
        </div>
        <a href="changepassword.php" class="btn btn-outline-primary w-100 mb-3">Change Password</a>
        <a href="logout.php" class="btn btn-danger logout-btn">Logout</a>
    </div>
</div>
<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>

</html>
</div>
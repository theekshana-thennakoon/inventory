<?php
require_once 'config/database.php';

session_start();

if (isset($_SESSION['technical_officer_id']) && isset($_SESSION['technical_officer_name'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM technical_officers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['technical_officer_id'] = $user['id'];
        $_SESSION['technical_officer_name'] = $user['name'];
        $_SESSION['technical_officer_status'] = $user['status'];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<?php
if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            Registration successful! Please login with your credentials.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FOT Media Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <center>
                            <img src="./assets/logoblack.png" class="img-fluid" style="width:25%; text-align:center; margin-bottom:25px;">
                            <h4 style="font-size:30px;">Inventory Management System</h4>
                            <h4 style="font-size:40px;">Login</h4>
                        </center>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">University Registration number XXX/1234/123</label>
                                <input type="text" class="form-control" id="email" name="email" required>
                                <small>if you are from Student union or Faculty enter allocated email address</small>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                            <a href="loginasbarcode.php" class="btn btn-secondary ms-2">Login with Barcode</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
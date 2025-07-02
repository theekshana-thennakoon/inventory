<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        $error = 'Please enter your username';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, email FROM technical_officer WHERE email = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate unique token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $stmt->execute([$token, $expiry, $user['id']]);

                require 'vendor/autoload.php';
                $resetLink = "https://fotmedia.thedevsl.com/reset_password.php?token=$token";

                $to = $user['email'];
                $subject = "Password change link";
                $message = "Click this link to reset your password: $resetLink\n\n";
                $message .= "This link will expire in 1 hour.";

                $headers = "From: fotmediarusl@gmail.com\r\n";
                $headers .= "Reply-To: fotmediarusl@gmail.com\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                if (mail($to, $subject, $message, $headers)) {
                    $success = 'Password reset link has been sent to your email';
                } else {
                    $error = 'Failed to send email. Please try again later.';
                }





                // Send email
                // $resetLink = "https://yourdomain.com/reset_password.php?token=$token";
                // $subject = "Password Reset Request";
                // $message = "Click this link to reset your password: $resetLink\n\n";
                // $message .= "This link will expire in 1 hour.";

                // $headers = "From: no-reply@yourdomain.com";

                // if (mail($user['email'], $subject, $message, $headers)) {
                //     $success = 'Password reset link has been sent to your email';
                // } else {
                //     $error = 'Failed to send email. Please try again later.';
                // }
            } else {
                $error = 'Username not found';
            }
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .forgot-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .username-format {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="forgot-container">
            <h2 class="text-center mb-4">Forgot Password</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Enter your University Registration Number</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="username-format mt-1">
                            <span id="formatHint">Format: ITT/XXXX/XXX, BST/XXXX/XXX or ENT/XXXX/XXX</span>
                            <span class="username-admin d-none">Admin login</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="./">Back to Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const usernameInput = document.getElementById('username');
        const formatHint = document.getElementById('formatHint');
        const adminHint = document.querySelector('.username-admin');

        usernameInput.addEventListener('input', function(e) {
            const input = e.target;
            let value = input.value;

            // Check if user is typing "admin"
            if (value.toLowerCase().startsWith('admin')) {
                // Allow simple lowercase admin login
                input.value = value.toLowerCase();
                formatHint.classList.add('d-none');
                adminHint.classList.remove('d-none');
                return;
            } else {
                formatHint.classList.remove('d-none');
                adminHint.classList.add('d-none');

                // Handle department format (ITT/2021/106)
                value = value.toUpperCase();

                // Auto-insert first / after 3 characters if matching prefix
                if (/^(ITT|BST|ENT)$/.test(value) && value.length === 3) {
                    input.value = value + '/';
                    return;
                }

                // Auto-insert second / after 8 characters (3 letters + / + 4 numbers)
                if (/^(ITT|BST|ENT)\/\d{4}$/.test(value) && value.length === 8) {
                    input.value = value + '/';
                    return;
                }

                // Force uppercase for the prefix
                if (value.length <= 3) {
                    input.value = value;
                }

                // Restrict input to only allow proper formatting
                if (/^(ITT|BST|ENT)\//.test(value)) {
                    const parts = value.split('/');

                    // First segment (department code) - already handled
                    if (parts.length > 1) {
                        // Second segment (year) - only 4 digits
                        parts[1] = parts[1].replace(/\D/g, '').slice(0, 4);

                        if (parts.length > 2) {
                            // Third segment (ID) - only 3 digits
                            parts[2] = parts[2].replace(/\D/g, '').slice(0, 3);
                        }

                        // Rebuild the value with proper segments
                        input.value = parts.join('/');
                    }
                }
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = usernameInput.value;
            const isAdmin = username === 'admin';
            const isDeptFormat = /^(ITT|BST|ENT)\/\d{4}\/\d{3}$/.test(username);

            if (!isAdmin && !isDeptFormat) {
                e.preventDefault();
                alert('Username must be either "admin" or in format XXX/YYYY/ZZZ (e.g., ITT/2021/106)');
                usernameInput.focus();
            }
        });
    </script>
</body>

</html>
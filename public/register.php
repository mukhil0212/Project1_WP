<?php
session_start();
require_once '../src/Database.php';
require_once '../src/Auth.php';

use Game\Auth;

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($username, $password);
        
        if ($result['success']) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Choose Your Path</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <div class="auth-form">
            <h1>Join the Adventure</h1>
            <p class="subtitle">Create your account to begin your journey</p>
            
            <?php if ($error): ?>
                <div class="message error" role="alert" aria-live="polite">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="message success" role="alert" aria-live="polite">
                    <?= htmlspecialchars($success) ?>
                    <br><a href="login.php" class="link">Login now</a>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required minlength="3" maxlength="50"
                           aria-describedby="username-help">
                    <small id="username-help">3-50 characters, letters and numbers only</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           required minlength="6"
                           aria-describedby="password-help">
                    <small id="password-help">At least 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
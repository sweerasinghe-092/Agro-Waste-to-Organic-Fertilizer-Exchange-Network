<?php
// Login page
require_once 'config/db.php';
require_once 'includes/functions.php';

$err = "";

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Find user by email
    $st = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $st->bind_param("s", $email);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();

    // Check password
    if ($u && password_verify($password, $u['password_hash'])) {

        // Account still waiting for approval
        if (($u['status'] ?? 'active') === 'pending') {
            $err = 'Your account is waiting for administrator approval.';
        }
        // Account suspended
        elseif (($u['status'] ?? 'active') === 'suspended') {
            $err = 'Your account is suspended. Please contact the administrator.';
        }
        // Login success
        else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$u['user_id'];
            $_SESSION['full_name'] = $u['full_name'];
            $_SESSION['role'] = $u['role'];

            // Admin goes to admin page, others go to dashboard
            header('Location: ' . ($u['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
            exit;
        }
    } else {
        $err = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Agro Exchange</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="login-bg">
    <div class="login-card">
        <h1 class="auth-brand"><img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange</h1>
        <p class="sub">Welcome back</p>

        <!-- Show error -->
        <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

        <!-- Show message after registration -->
        <?php if (isset($_GET['registered'])): ?>
            <p class="message">Registration submitted. Your account will be available after administrator approval.</p>
        <?php endif; ?>

        <!-- Login form -->
        <form method="post">
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button>Login</button>
        </form>

        <p class="switch">No account? <a href="register.php">Register</a></p>
        <p class="demo">Demo: admin@agro.com / Admin@123</p>
    </div>
</div>
</body>
</html>
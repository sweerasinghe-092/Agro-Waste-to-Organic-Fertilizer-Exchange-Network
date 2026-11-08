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
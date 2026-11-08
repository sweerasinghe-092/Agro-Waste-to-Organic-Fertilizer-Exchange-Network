<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Agro Exchange</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
<div class="login-bg">
    <div class="login-card">
        <h1 class="auth-brand"><img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange</h1>
        <p class="sub">Create your account</p>
        <p class="demo">New accounts are activated after administrator approval.</p>

        <form method="post">
            <label>Full Name</label>
            <input name="full_name" required>

            <div class="row">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div>
                    <label>Phone</label>
                    <input name="phone" required>
                </div>
            </div>

            <label>Password</label>
            <input type="password" name="password" required>

            <div class="row">
                <div>
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="provider">Provider</option>
                        <option value="producer">Producer</option>
                        <option value="buyer">Buyer</option>
                    </select>
                </div>
                <div>
                    <label>District</label>
                    <select name="district_id" required>
                        <option value="">Select District</option>
                    </select>
                </div>
            </div>

            <label>Address</label>
            <input name="address">

            <button>Register</button>
        </form>

        <p class="switch">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
</body>
</html>
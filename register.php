<?php
// Register page
require_once 'config/db.php';
require_once 'includes/functions.php';

$err = "";

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $district_id = $_POST['district_id'];
    $address = trim($_POST['address']);

    // Check required fields
    if (!$full_name || !$email || !$phone || !$password || !$role || !$district_id) {
        $err = "Please fill all required fields.";
    } elseif (!preg_match('/^0[0-9]{9}$/', $phone)) {
        $err = "Enter a valid phone number.";
    } else {
        // Check if email already exists
        $st = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $st->bind_param("s", $email);
        $st->execute();

        if ($st->get_result()->num_rows > 0) {
            $err = "Email already registered.";
        } else {
            // Create password hash and save new user as pending
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, district_id, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $st->bind_param("sssssis", $full_name, $email, $phone, $hash, $role, $district_id, $address);

            if ($st->execute()) {
                $newUserId = (int)$conn->insert_id;

                // Notify all admins about new registration
                $admins = $conn->query("SELECT user_id FROM users WHERE role = 'admin' AND status = 'active'");
                while ($admin = $admins->fetch_assoc()) {
                    notify_user($conn, (int)$admin['user_id'], 'new_registration', "New registration: {$full_name} ({$role}) is waiting for approval.", 'admin.php?view=users');
                }

                header("Location: login.php?registered=1");
                exit;
            } else {
                $err = "Registration failed. Try again.";
            }
        }
    }
}
?>
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

        <!-- Show error -->
        <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>

        <!-- Register form -->
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
                    <input type="tel" name="phone" pattern="[0-9]{10}" maxlength="10" placeholder="0712345678" required>
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
                        <?php $ds = districts($conn); while ($d = $ds->fetch_assoc()): ?>
                            <option value="<?= $d['district_id'] ?>"><?= e($d['district_name']) ?></option>
                        <?php endwhile; ?>
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
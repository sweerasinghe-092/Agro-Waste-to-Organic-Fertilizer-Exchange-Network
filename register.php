<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register | Agro Exchange</title>
</head>
<body>
    <h1>Agro Exchange</h1>
    <p>Create your account</p>

    <form method="post">
        <label>Full Name</label>
        <input name="full_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Phone</label>
        <input name="phone" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Role</label>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="provider">Provider</option>
            <option value="producer">Producer</option>
            <option value="buyer">Buyer</option>
        </select>

        <label>District</label>
        <select name="district_id" required>
            <option value="">Select District</option>
        </select>

        <label>Address</label>
        <input name="address">

        <button>Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>
<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$msg = "";
$err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $pid = (int)$_POST['product_id'];
    $qty = (int)$_POST['qty'];
    if ($qty < 1) $qty = 1;
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid] += $qty;
    } else {
        $_SESSION['cart'][$pid] = $qty;
    }
    $msg = "Added to cart.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    $pid = (int)$_POST['product_id'];
    unset($_SESSION['cart'][$pid]);
    $msg = "Removed from cart.";
}

$items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $res = $conn->query("SELECT * FROM products WHERE product_id IN ($ids)");
    while ($row = $res->fetch_assoc()) {
        $row['qty'] = $_SESSION['cart'][$row['product_id']];
        $row['subtotal'] = $row['price'] * $row['qty'];
        $total += $row['subtotal'];
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cart | Agro Exchange</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="dashboard">
    <h1>My Cart</h1>
    <?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="error"><?= e($err) ?></p><?php endif; ?>
    <table>
        <tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th>Action</th></tr>
        <?php if (empty($items)): ?>
            <tr><td colspan="5">Your cart is empty.</td></tr>
        <?php else: ?>
            <?php foreach ($items as $it): ?>
            <tr>
                <td><?= e($it['product_name']) ?></td>
                <td>Rs. <?= $it['price'] ?></td>
                <td><?= $it['qty'] ?></td>
                <td>Rs. <?= $it['subtotal'] ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="product_id" value="<?= $it['product_id'] ?>">
                        <button name="remove">Remove</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
    <p>Total: Rs. <?= $total ?></p>
    <form method="post"><button name="checkout">Checkout</button></form>
    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>
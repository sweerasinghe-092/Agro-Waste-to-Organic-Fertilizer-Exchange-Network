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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $err = "Cart is empty.";
    } else {
        $conn->begin_transaction();
        try {
            $total = 0;
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $st = $conn->prepare("SELECT * FROM products WHERE product_id = ? FOR UPDATE");
                $st->bind_param("i", $pid);
                $st->execute();
                $p = $st->get_result()->fetch_assoc();
                if (!$p || $p['stock'] < $qty) {
                    throw new Exception("Not enough stock for " . $p['product_name']);
                }
                $total += $p['price'] * $qty;
            }

            $uid = (int)$_SESSION['user_id'];
            $st = $conn->prepare("INSERT INTO orders (buyer_id, total_amount, status) VALUES (?, ?, 'pending')");
            $st->bind_param("id", $uid, $total);
            $st->execute();
            $order_id = $conn->insert_id;

            foreach ($_SESSION['cart'] as $pid => $qty) {
                $st = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
                $st->bind_param("i", $pid);
                $st->execute();
                $price = $st->get_result()->fetch_assoc()['price'];

                $st = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
                $st->bind_param("iiid", $order_id, $pid, $qty, $price);
                $st->execute();

                $st = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                $st->bind_param("ii", $qty, $pid);
                $st->execute();
            }

            $conn->commit();
            $_SESSION['cart'] = [];
            $msg = "Order placed successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $err = $e->getMessage();
        }
    }
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
<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_role('buyer');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
// Add to cart - from fertilizer_market.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $pid = (int)$_POST['add_product'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $st = $conn->prepare("SELECT stock_quantity FROM fertilizer_products WHERE product_id = ? AND status = 'active'");
    $st->bind_param('i', $pid);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    if ($p) {
        $_SESSION['cart'][$pid] = min((int)$p['stock_quantity'], ($_SESSION['cart'][$pid] ?? 0) + $qty);
    }
    header('Location: cart.php');
    exit;
}

// Remove one item - confirm popup is in the HTML below
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    header('Location: cart.php');
    exit;
}

// Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $address = trim($_POST['delivery_address'] ?? '');
    $payment = $_POST['payment_method'] ?? '';
    if ($address !== '' && in_array($payment, ['cash_on_delivery', 'bank_transfer'], true) && count($_SESSION['cart'])) {
        $conn->begin_transaction();
        try {
            $total = 0;
            $valid = [];
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $st = $conn->prepare("SELECT price, stock_quantity FROM fertilizer_products WHERE product_id = ? AND status = 'active' FOR UPDATE");
                $st->bind_param('i', $pid);
                $st->execute();
                $p = $st->get_result()->fetch_assoc();
                if (!$p || $qty > $p['stock_quantity']) throw new Exception('Insufficient stock for one or more products.');
                $subtotal = $p['price'] * $qty;
                $total += $subtotal;
                $valid[] = [$pid, $qty, $p['price'], $subtotal];
            }
            $buyer = (int)$_SESSION['user_id'];
            $st = $conn->prepare('INSERT INTO orders(buyer_id, total_amount, delivery_address, payment_method) VALUES(?, ?, ?, ?)');
            $st->bind_param('idss', $buyer, $total, $address, $payment);
            $st->execute();
            $orderId = $conn->insert_id;
            foreach ($valid as [$pid, $qty, $price, $subtotal]) {
                $st = $conn->prepare('INSERT INTO order_items(order_id, product_id, quantity, unit_price, subtotal) VALUES(?, ?, ?, ?, ?)');
                $st->bind_param('iiidd', $orderId, $pid, $qty, $price, $subtotal);
                $st->execute();
                $st = $conn->prepare('UPDATE fertilizer_products SET stock_quantity = stock_quantity - ? WHERE product_id = ?');
                $st->bind_param('ii', $qty, $pid);
                $st->execute();
            }
            $conn->commit();
            $_SESSION['cart'] = [];
            $success = 'Order placed successfully. Order number: ' . $orderId;
        } catch (Exception $ex) {
            $conn->rollback();
            $error = $ex->getMessage();
        }
    } else {
        $error = 'Enter your delivery address, choose a payment method and add products.';
    }
}

$pageTitle = 'Cart and Checkout';
include 'includes/header.php';
?>
<h2 class="section-title">Your Shopping Cart</h2>
<?php if (!empty($success)): ?><p class="message"><?= e($success) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
<div class="panel table-wrap">
<table>
<tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr>
<?php
$total = 0;
foreach ($_SESSION['cart'] as $pid => $qty):
    $st = $conn->prepare('SELECT product_name, price, stock_quantity FROM fertilizer_products WHERE product_id = ?');
    $st->bind_param('i', $pid);
    $st->execute();
    $p = $st->get_result()->fetch_assoc();
    if (!$p) continue;
    $sub = $p['price'] * $qty;
    $total += $sub;
?>
<tr>
<td><?= e($p['product_name']) ?></td>
<td>Rs. <?= number_format($p['price'], 2) ?></td>
<td><?= e($qty) ?></td>
<td>Rs. <?= number_format($sub, 2) ?></td>
<td><a href="cart.php?remove=<?= $pid ?>" onclick="return confirm('Are you sure you want to remove this item from cart?');">Remove</a></td>
</tr>
<?php endforeach; ?>
</table>
<h3>Total: Rs. <?= number_format($total, 2) ?></h3>
</div>

<?php if (count($_SESSION['cart'])): ?>
<div class="form-box">
<h3>Checkout</h3>
<form method="post" onsubmit="return confirm('Are you sure you want to place this order?');">
<label>Delivery address</label>
<textarea name="delivery_address" required></textarea>
<label>Payment method</label>
<select name="payment_method" required>
<option value="cash_on_delivery">Cash on delivery</option>
<option value="bank_transfer">Bank transfer</option>
</select>
<button name="checkout" value="1">Place Order</button>
</form>
</div>
<?php else: ?>
<div class="form-box">
<p>Your cart is empty.</p>
<a class="btn" href="fertilizer_market.php">Browse fertilizer market</a>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
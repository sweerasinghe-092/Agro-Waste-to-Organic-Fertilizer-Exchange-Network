<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_role('buyer');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$pageTitle = "Cart and Checkout";
include 'includes/header.php';
?>

<h2 class="section-title">Your Shopping Cart</h2>

<div class="panel table-wrap">
    <table>
        <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr>
        <tr><td colspan="5">Your cart is empty.</td></tr>
    </table>
    <h3>Total: Rs. 0.00</h3>
</div>

<div class="form-box">
    <h3>Checkout</h3>
    <form method="post">
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

<?php include 'includes/footer.php'; ?>
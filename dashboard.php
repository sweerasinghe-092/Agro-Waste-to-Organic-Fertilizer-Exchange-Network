<?php

require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'buyer';
if ($role === 'admin') { header('Location: admin.php'); exit; }
if (!in_array($role, ['provider', 'producer', 'buyer'], true)) { http_response_code(403); exit('Access denied.'); }
$meStmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
$meStmt->bind_param('i', $uid);
$meStmt->execute();
$me = $meStmt->get_result()->fetch_assoc();

if (!$me || $me['status'] !== 'active') { session_destroy(); header('Location: login.php'); exit; }

$view = $_GET['view'] ?? 'overview';
$orderFilter = $_GET['filter'] ?? '';

// Display the cart on the dashboard without redirecting to cart.php.

// cart.php is still used only for the Add to Cart functionality.

$menus = [

    'provider' => ['overview' => 'Overview', 'listings' => 'My waste listings', 'requests' => 'Incoming requests', 'profile' => 'My profile', 'notifications' => 'Notifications'],

    'producer' => ['overview' => 'Overview', 'products' => 'My fertilizer products', 'requests' => 'Waste requests', 'orders' => 'Orders received', 'profile' => 'My profile', 'notifications' => 'Notifications'],

    'buyer' => ['overview' => 'Overview', 'orders' => 'My orders', 'cart' => 'My cart', 'profile' => 'My profile', 'notifications' => 'Notifications']

];

if (!isset($menus[$role][$view])) $view = 'overview';

$notice = '';

if (isset($_GET['remove']) && $role === 'buyer' && $view === 'cart') {

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    unset($_SESSION['cart'][(int)$_GET['remove']]);

    header('Location: dashboard.php?view=cart'); exit;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['checkout']) && $role === 'buyer') {

        $address = trim($_POST['delivery_address'] ?? '');

        $payment = $_POST['payment_method'] ?? '';

        if ($address !== '' && in_array($payment, ['cash_on_delivery', 'bank_transfer'], true) && !empty($_SESSION['cart'])) {

            $conn->begin_transaction();

            try {

                $total = 0; $valid = []; $producerIds = [];

                foreach ($_SESSION['cart'] as $pid => $qty) {

                    // Get the producer_id so a notification can be sent to the producer when an order is placed.

                    $st = $conn->prepare("SELECT price, stock_quantity, producer_id FROM fertilizer_products WHERE product_id = ? AND status = 'active' FOR UPDATE");

                    $st->bind_param("i", $pid); $st->execute(); $p = $st->get_result()->fetch_assoc();

                    if (!$p || $qty > $p['stock_quantity']) throw new Exception("Insufficient stock for one or more products.");

                    $subtotal = $p['price'] * $qty; $total += $subtotal; $valid[] = [$pid, $qty, $p['price'], $subtotal];

                    if (!in_array((int)$p['producer_id'], $producerIds, true)) $producerIds[] = (int)$p['producer_id'];

                }

                $st = $conn->prepare("INSERT INTO orders(buyer_id, total_amount, delivery_address, payment_method, order_status) VALUES(?,?,?,?,'pending')");

                $st->bind_param("idss", $uid, $total, $address, $payment); $st->execute(); $orderId = $conn->insert_id;

                foreach ($valid as [$pid, $qty, $price, $subtotal]) {

                    $st = $conn->prepare("INSERT INTO order_items(order_id, product_id, quantity, unit_price, subtotal) VALUES(?,?,?,?,?)");

                    $st->bind_param("iiidd", $orderId, $pid, $qty, $price, $subtotal); $st->execute();

                    $st = $conn->prepare("UPDATE fertilizer_products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");

                    $st->bind_param("ii", $qty, $pid); $st->execute();

                }

                $conn->commit();

                // NEW - Send a notification to the producer when a new order is placed.

                foreach ($producerIds as $producerId) {

                    notify_user($conn, $producerId, 'new_order', "New order #{$orderId} from {$me['full_name']} - Rs. " . number_format($total, 2), 'dashboard.php?view=orders');

                }

                $_SESSION['cart'] = []; $notice = "Order placed successfully. Order number: " . $orderId;

            } catch (Exception $ex) { $conn->rollback(); $notice = $ex->getMessage(); }

        } else $notice = "Enter your delivery address, choose a payment method and add products.";

    }

    if (isset($_POST['profile_update'])) {

        $name = trim($_POST['full_name'] ?? '');

        $phone = trim($_POST['phone'] ?? '');

        $address = trim($_POST['address'] ?? '');

        if ($name && $phone) {

            $st = $conn->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?');

            $st->bind_param('sssi', $name, $phone, $address, $uid);

            $st->execute();

            $_SESSION['full_name'] = $name;

            $me['full_name'] = $name; $me['phone'] = $phone; $me['address'] = $address;

            $notice = 'Your profile has been updated.';

        }

    }

    if (isset($_POST['request_action']) && $role === 'provider') {

        $rid = (int)($_POST['request_id'] ?? 0);

        $action = $_POST['request_action'];

        if (in_array($action, ['accepted', 'rejected'], true)) {

            $st = $conn->prepare("UPDATE exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id SET r.status = ? WHERE r.request_id = ? AND w.provider_id = ? AND r.status = 'pending'");

            $st->bind_param('sii', $action, $rid, $uid);

            $st->execute();

            $notice = 'Request status updated.';

        }

    }

    if (isset($_POST['add_product']) && $role === 'producer') {

        $name = trim($_POST['product_name'] ?? '');

        $type = trim($_POST['fertilizer_type'] ?? '');

        $desc = trim($_POST['description'] ?? '');

        $price = (float)($_POST['price'] ?? 0);

        $unit = trim($_POST['unit'] ?? 'kg');

        $stock = (int)($_POST['stock_quantity'] ?? 0);

        $district = (int)($_POST['district_id'] ?? 0);

        if ($name && $type && $price >= 0 && $stock >= 0) {

            $st = $conn->prepare("INSERT INTO fertilizer_products(producer_id, product_name, fertilizer_type, description, price, unit, stock_quantity, district_id) VALUES(?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0))");

            $st->bind_param('isssdsii', $uid, $name, $type, $desc, $price, $unit, $stock, $district);

            $st->execute();

            $notice = 'Product added to your marketplace inventory.';

        }

    }

    // Delete a provider's listing.

    if (isset($_POST['delete_listing']) && $role === 'provider') {

        $lid = (int)($_POST['listing_id'] ?? 0);

        $st = $conn->prepare("DELETE FROM waste_listings WHERE listing_id = ? AND provider_id = ?");

        $st->bind_param('ii', $lid, $uid);

        $st->execute();

        $notice = 'Listing deleted.';

    }

    // Provider restore request - keep the listing hidden until the admin approves it.

    if (isset($_POST['request_restore']) && $role === 'provider') {

        $lid = (int)($_POST['listing_id'] ?? 0);

        $st = $conn->prepare("UPDATE waste_listings SET status = 'pending_review' WHERE listing_id = ? AND provider_id = ? AND status = 'removed'");

        $st->bind_param('ii', $lid, $uid);

        $st->execute();

        if ($st->affected_rows > 0) {

            $admins = $conn->query("SELECT user_id FROM users WHERE role = 'admin' AND status = 'active'");

            while ($a = $admins->fetch_assoc()) {

                notify_user($conn, (int)$a['user_id'], 'restore_request', "Restore request: {$me['full_name']} asked to show a removed waste listing again.", 'admin.php?view=waste');

            }

            $notice = 'Restore request sent to admin.';

        }

    }

    // Use only the values defined in the database ENUM: pending, confirmed, delivered, cancelled.

    if (isset($_POST['order_status_update']) && $role === 'producer') {

        $oid = (int)($_POST['order_id'] ?? 0);

        $newStatus = $_POST['order_status_update'];

        if (in_array($newStatus, ['confirmed', 'delivered', 'cancelled'], true)) {

            // Get the buyer's ID so a notification can be sent to them.

            $bst = $conn->prepare("SELECT buyer_id FROM orders WHERE order_id = ?");

            $bst->bind_param('i', $oid); $bst->execute(); $brow = $bst->get_result()->fetch_assoc();

            $st = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");

            $st->bind_param('si', $newStatus, $oid);

            $st->execute();

            // NEW - Notify the buyer when the producer changes the order status.

            if ($brow) {

                $msg = "Your order #{$oid} is {$newStatus}.";

                if ($newStatus === 'confirmed') $msg = "Your order #{$oid} is confirmed by the producer.";

                if ($newStatus === 'delivered') $msg = "Your order #{$oid} is delivered.";

                if ($newStatus === 'cancelled') $msg = "Your order #{$oid} is cancelled by the producer.";

                notify_user($conn, (int)$brow['buyer_id'], 'order_update', $msg, 'dashboard.php?view=orders');

            }

            $notice = 'Order status updated.';

        }

    }

}

function countFor($conn, $sql, $uid) {

    $st = $conn->prepare($sql);

    $st->bind_param('i', $uid);

    $st->execute();

    $r = $st->get_result()->fetch_assoc();

    return (int)($r['n'] ?? 0);

}



$stats = [];

if ($role === 'provider') {

    $stats = [

        ['My listings', countFor($conn, 'SELECT COUNT(*) n FROM waste_listings WHERE provider_id = ?', $uid), 'dashboard.php?view=listings'],

        ['Available', countFor($conn, "SELECT COUNT(*) n FROM waste_listings WHERE provider_id = ? AND status = 'available'", $uid), 'dashboard.php?view=listings'],

        ['Requests', countFor($conn, 'SELECT COUNT(*) n FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id WHERE w.provider_id = ?', $uid), 'dashboard.php?view=requests'],

        ['Pending', countFor($conn, "SELECT COUNT(*) n FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id WHERE w.provider_id = ? AND r.status = 'pending'", $uid), 'dashboard.php?view=requests']

    ];

    $intro = 'Manage your agricultural waste, respond to producers and keep useful materials in circulation.';

} elseif ($role === 'producer') {

    $stats = [

        ['My products', countFor($conn, 'SELECT COUNT(*) n FROM fertilizer_products WHERE producer_id = ?', $uid), 'dashboard.php?view=products'],

        ['Stock units', countFor($conn, 'SELECT COALESCE(SUM(stock_quantity), 0) n FROM fertilizer_products WHERE producer_id = ?', $uid), 'dashboard.php?view=products'],

        ['Waste requests', countFor($conn, 'SELECT COUNT(*) n FROM exchange_requests WHERE producer_id = ?', $uid), 'dashboard.php?view=requests'],

        ['Orders received', countFor($conn, 'SELECT COUNT(*) n FROM order_items oi JOIN fertilizer_products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id WHERE p.producer_id = ?', $uid), 'dashboard.php?view=orders']

    ];

    $intro = 'Find raw materials, publish your organic fertilizer and manage customer orders.';

} else {

    $stats = [

        ['My orders', countFor($conn, 'SELECT COUNT(*) n FROM orders WHERE buyer_id = ?', $uid), 'dashboard.php?view=orders'],

        ['Pending orders', countFor($conn, "SELECT COUNT(*) n FROM orders WHERE buyer_id = ? AND (order_status = 'pending' OR order_status IS NULL)", $uid), 'dashboard.php?view=orders&filter=pending'],

        ['Available products', (int)$conn->query("SELECT COUNT(*) n FROM fertilizer_products WHERE status = 'active'")->fetch_assoc()['n'], 'fertilizer_market.php'],

        ['Available waste', (int)$conn->query("SELECT COUNT(*) n FROM waste_listings WHERE status = 'available'")->fetch_assoc()['n'], 'waste_listings.php']

    ];

    $intro = 'Discover locally made organic fertilizer and follow your purchases in one place.';

}

$pageTitle = 'My Dashboard';

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= e($pageTitle) ?> | Agro Exchange</title>

<link rel="stylesheet" href="css/style.css">

<link rel="stylesheet" href="css/dashboard.css">

<style>

.listing-actions { display:flex; gap:18px; flex-wrap:wrap; align-items:center; }

.listing-actions form { margin:0; }

.listing-actions a { color:#1a7a3a; font-weight:600; text-decoration:none; }

.delete-link { color:#c0392b; font-weight:600; background:none; border:none; cursor:pointer; font-size:14px; padding:0; }

.order-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }

.order-actions form { margin:0; }

.filter-links { display:flex; gap:10px; margin-bottom:12px; }

.filter-links a { padding:6px 14px; border:1px solid #ddd; border-radius:20px; text-decoration:none; color:#333; font-size:13px; }

.filter-links a.active { background:#1a7a3a; color:#fff; border-color:#1a7a3a; }

.cart-empty-box { text-align:center; padding:30px 0; }

.cart-empty-box .empty-cell { text-align:center; }

.cart-empty-box .primary-action { display:inline-block; margin-top:10px; }

.data-panel { overflow:hidden; }

.table-scroll { overflow-x:auto; width:100%; }

.modern-table { width:100%; min-width:720px; border-collapse:collapse; }

.modern-table th, .modern-table td { padding:10px 12px; text-align:left; vertical-align:top; white-space:normal; }

.modern-table th:first-child, .modern-table td:first-child { white-space:nowrap; }

.mini-button { white-space:nowrap; }

</style>

</head>

<body class="dash-page">

<div class="dash-layout">

<aside class="dash-sidebar">

<a class="dash-brand" href="index.php"><img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange</a>

<div class="dash-profile"><div class="avatar"><?= e(strtoupper(substr($me['full_name'], 0, 1))) ?></div><div><strong><?= e($me['full_name']) ?></strong><small><?= e(ucfirst($role)) ?> account</small></div></div>

<div class="nav-caption">WORKSPACE</div>

<nav class="dash-nav">

<a class="<?= $view === 'overview' ? 'active' : '' ?>" href="dashboard.php"><span>Overview</span></a>

<?php foreach ($menus[$role] as $key => $label): if ($key === 'overview') continue; ?>

<a class="<?= $view === $key ? 'active' : '' ?>" href="dashboard.php?view=<?= e($key) ?>"><span><?= e($label) ?></span></a>

<?php endforeach; ?>

</nav>

<div class="sidebar-bottom"><a href="index.php"><span>Visit public home</span></a><a class="logout-link" href="logout.php"><span>Log out</span></a></div>

</aside>

<main class="dash-content">

<header class="dash-topbar"><div><p class="breadcrumb">Agro Exchange / <?= e(ucfirst($view)) ?></p><h1><?= e($menus[$role][$view]) ?></h1></div><a class="top-profile" href="dashboard.php?view=profile"><span class="avatar small-avatar"><?= e(strtoupper(substr($me['full_name'], 0, 1))) ?></span><span><?= e($me['full_name']) ?></span></a></header>

<?php if ($notice): ?><div class="notice-success"><?= e($notice) ?></div><?php endif; ?>



<?php if ($view === 'overview'): ?>

<section class="welcome-banner"><div><span class="eyebrow">YOUR AGRO EXCHANGE WORKSPACE</span><h2>Good to see you, <?= e(explode(' ', $me['full_name'])[0]) ?>.</h2><p><?= e($intro) ?></p></div></section>

<section class="stats-grid"><?php foreach ($stats as $s): ?><a class="metric-card" href="<?= e($s[2]) ?>"><span><?= e($s[0]) ?></span><strong><?= number_format($s[1]) ?></strong><small>View details</small></a><?php endforeach; ?></section>

<section class="section-heading"><div><h2>Quick actions</h2><p>Jump straight to the tasks you use most.</p></div></section>

<div class="action-grid">

<?php if ($role === 'provider'): ?><a class="action-card" href="post_waste.php"><b>Post new waste</b><small>Share available organic materials</small></a><a class="action-card" href="dashboard.php?view=listings"><b>Manage listings</b><small>Edit or review your posts</small></a><a class="action-card" href="dashboard.php?view=requests"><b>Review requests</b><small>Respond to producer requests</small></a>

<?php elseif ($role === 'producer'): ?><a class="action-card" href="waste_listings.php"><b>Find raw materials</b><small>Browse available agro-waste</small></a><a class="action-card" href="dashboard.php?view=products"><b>Manage products</b><small>Publish and update fertilizer</small></a><a class="action-card" href="dashboard.php?view=orders"><b>Manage orders</b><small>Review customer purchases</small></a>

<?php else: ?><a class="action-card" href="fertilizer_market.php"><b>Explore fertilizer</b><small>Find organic products</small></a><a class="action-card" href="dashboard.php?view=cart"><b>Open my cart</b><small>Review items before checkout</small></a><a class="action-card" href="dashboard.php?view=orders"><b>Track my orders</b><small>See order status and history</small></a><?php endif; ?></div>

<section class="section-heading"><div><h2>Recent activity</h2><p>Your latest updates at a glance.</p></div></section>

<div class="data-panel"><div class="table-scroll"><table class="modern-table"><thead><tr><th>Reference</th><th>Details</th><th>Date</th><th>Status</th></tr></thead><tbody>

<?php if ($role === 'provider') { $st = $conn->prepare('SELECT listing_id, title, created_at, status FROM waste_listings WHERE provider_id = ? ORDER BY created_at DESC LIMIT 6'); $st->bind_param('i', $uid); $st->execute(); $rows = $st->get_result(); while ($r = $rows->fetch_assoc()): ?><tr><td>#<?= e($r['listing_id']) ?></td><td><?= e($r['title']) ?></td><td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td></tr><?php endwhile; ?>

<?php } elseif ($role === 'producer') { $st = $conn->prepare('SELECT product_id, product_name, created_at, status FROM fertilizer_products WHERE producer_id = ? ORDER BY created_at DESC LIMIT 6'); $st->bind_param('i', $uid); $st->execute(); $rows = $st->get_result(); while ($r = $rows->fetch_assoc()): ?><tr><td>#<?= e($r['product_id']) ?></td><td><?= e($r['product_name']) ?></td><td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td></tr><?php endwhile; ?>

<?php } else { $st = $conn->prepare('SELECT order_id, total_amount, order_date, order_status FROM orders WHERE buyer_id = ? ORDER BY order_date DESC LIMIT 6'); $st->bind_param('i', $uid); $st->execute(); $rows = $st->get_result(); while ($r = $rows->fetch_assoc()): ?><tr><td>#<?= e($r['order_id']) ?></td><td>Rs. <?= number_format((float)$r['total_amount'], 2) ?></td><td><?= e(date('M j, Y', strtotime($r['order_date']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['order_status'] ?: 'pending')) ?></span></td></tr><?php endwhile; } if (isset($rows) && $rows->num_rows === 0): ?><tr><td colspan="4" class="empty-cell">Nothing here yet. Your activity will appear here.</td></tr><?php endif; ?></tbody></table></div></div>



<?php elseif ($view === 'listings' && $role === 'provider'): ?>

<div class="data-panel">

<div class="panel-heading"><div><h2>Your waste listings</h2><p>Keep your available materials up to date.</p></div><a class="primary-action" href="post_waste.php">Post waste</a></div>

<div class="table-scroll"><table class="modern-table"><thead><tr><th>Listing</th><th>Quantity</th><th>Available from</th><th>Status</th><th>Action</th></tr></thead><tbody>

<?php $st = $conn->prepare('SELECT * FROM waste_listings WHERE provider_id = ? ORDER BY created_at DESC'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">You have not posted any waste yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?>

<tr>

<td><?= e($r['title']) ?></td>

<td><?= e($r['quantity_available']) ?> <?= e($r['unit']) ?></td>

<td><?= e($r['available_from'] ?: '—') ?></td>

<td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td>

<td><div class="listing-actions">

<?php if ($r['status'] === 'removed'): ?>

<form method="post" onsubmit="return confirm('Request admin to show this listing again?');"><input type="hidden" name="listing_id" value="<?= $r['listing_id'] ?>"><button class="mini-button" name="request_restore" value="1">Request to restore</button></form>

<?php elseif ($r['status'] === 'pending_review'): ?>

<small>Waiting for admin</small>

<?php else: ?>

<a href="post_waste.php?edit=<?= $r['listing_id'] ?>">Edit</a>

<?php endif; ?>

<form method="post" onsubmit="return confirm('Are you sure you want to delete this listing?');"><input type="hidden" name="listing_id" value="<?= $r['listing_id'] ?>"><button class="delete-link" type="submit" name="delete_listing" value="1">Delete</button></form>

</div></td>

</tr>

<?php endwhile; ?>

</tbody></table></div></div>



<?php elseif ($view === 'requests'): ?>

<div class="data-panel"><div class="panel-heading"><div><h2><?= $role === 'provider' ? 'Requests for your waste' : 'Your waste requests' ?></h2><p>Track the progress of material exchanges.</p></div></div><div class="table-scroll"><table class="modern-table"><thead><tr><th>Waste listing</th><th>Requested by</th><th>Quantity</th><th>Requested</th><th>Status / Action</th></tr></thead><tbody><?php if ($role === 'provider') { $st = $conn->prepare('SELECT r.*, w.title, u.full_name FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id JOIN users u ON r.producer_id = u.user_id WHERE w.provider_id = ? ORDER BY r.requested_at DESC'); } else { $st = $conn->prepare('SELECT r.*, w.title, u.full_name FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id JOIN users u ON w.provider_id = u.user_id WHERE r.producer_id = ? ORDER BY r.requested_at DESC'); } $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No exchange requests yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['quantity_requested']) ?> kg</td><td><?= e(date('M j, Y', strtotime($r['requested_at']))) ?></td><td><?php if ($role === 'provider' && $r['status'] === 'pending'): ?><form class="inline-actions" method="post"><input type="hidden" name="request_id" value="<?= $r['request_id'] ?>"><button class="mini-button" name="request_action" value="accepted">Accept</button><button class="mini-button secondary" name="request_action" value="rejected">Reject</button></form><?php else: ?><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></div>

<?php elseif ($view === 'products' && $role === 'producer'): ?>

<div class="data-panel"><div class="panel-heading"><div><h2>Your fertilizer products</h2><p>Add the organic products you make available to buyers.</p></div></div><form class="dashboard-form" method="post"><div class="form-grid"><label>Product name<input name="product_name" required></label><label>Fertilizer type<select name="fertilizer_type"><option>Compost</option><option>Vermicompost</option><option>Bokashi</option><option>Liquid fertilizer</option><option>Other</option></select></label><label>Price (Rs.)<input type="number" name="price" min="0" step="0.01" required></label><label>Unit<input name="unit" value="kg" required></label><label>Stock quantity<input type="number" name="stock_quantity" min="0" value="0" required></label><label>District<select name="district_id"><option value="0">Select district</option><?php $ds = $conn->query('SELECT district_id, district_name FROM districts ORDER BY district_name'); while ($d = $ds->fetch_assoc()): ?><option value="<?= $d['district_id'] ?>" <?= $me['district_id'] == $d['district_id'] ? 'selected' : '' ?>><?= e($d['district_name']) ?></option><?php endwhile; ?></select></label><label class="full-field">Description<textarea name="description" rows="3"></textarea></label></div><button class="primary-action" name="add_product">Add product</button></form><div class="table-scroll"><table class="modern-table"><thead><tr><th>Product</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead><tbody><?php $st = $conn->prepare('SELECT * FROM fertilizer_products WHERE producer_id = ? ORDER BY created_at DESC'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No products added yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?><tr><td><?= e($r['product_name']) ?></td><td><?= e($r['fertilizer_type']) ?></td><td>Rs. <?= number_format((float)$r['price'], 2) ?></td><td><?= e($r['stock_quantity']) ?> <?= e($r['unit']) ?></td><td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td></tr><?php endwhile; ?></tbody></table></div></div>

<?php elseif ($view === 'orders'): ?>

<div class="data-panel"><div class="panel-heading"><div><h2><?= $role === 'producer' ? 'Customer orders' : 'Your order history' ?></h2><p><?= $role === 'producer' ? 'Orders containing your fertilizer products.' : 'Follow the status of your purchases.' ?></p></div></div>

<?php if ($role === 'buyer'): ?><div class="filter-links"><a href="dashboard.php?view=orders" class="<?= $orderFilter !== 'pending' ? 'active' : '' ?>">All orders</a><a href="dashboard.php?view=orders&filter=pending" class="<?= $orderFilter === 'pending' ? 'active' : '' ?>">Pending only</a></div><?php endif; ?>

<div class="table-scroll"><table class="modern-table"><thead><tr><th>Order</th><th><?= $role === 'producer' ? 'Buyer' : 'Items total' ?></th><th><?= $role === 'producer' ? 'Items' : 'Date' ?></th><th><?= $role === 'producer' ? 'Total' : 'Status' ?></th><?php if ($role === 'producer'): ?><th>Date</th><th>Status</th><th>Action</th><?php endif; ?></tr></thead><tbody>

<?php

if ($role === 'buyer') {

    if ($orderFilter === 'pending') {

        $st = $conn->prepare("SELECT order_id, total_amount, order_date, COALESCE(order_status,'pending') AS order_status FROM orders WHERE buyer_id = ? AND (order_status = 'pending' OR order_status IS NULL) ORDER BY order_date DESC");

    } else {

        $st = $conn->prepare("SELECT order_id, total_amount, order_date, COALESCE(order_status,'pending') AS order_status FROM orders WHERE buyer_id = ? ORDER BY order_date DESC");

    }

    $st->bind_param('i', $uid);

    $st->execute(); $rs = $st->get_result();

    if (!$rs->num_rows) echo '<tr><td colspan="4" class="empty-cell">No orders to show yet.</td></tr>';

    while ($r = $rs->fetch_assoc()): ?><tr><td>#<?= e($r['order_id']) ?></td><td>Rs. <?= number_format((float)$r['total_amount'], 2) ?></td><td><?= e(date('M j, Y', strtotime($r['order_date']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['order_status'] ?: 'pending')) ?></span></td></tr><?php endwhile;

} else {

    $st = $conn->prepare("SELECT o.order_id, o.total_amount, o.order_date, COALESCE(o.order_status,'pending') AS order_status, u.full_name AS buyer_name, GROUP_CONCAT(CONCAT(p.product_name, ' x ', oi.quantity) SEPARATOR ', ') AS items FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN fertilizer_products p ON oi.product_id = p.product_id JOIN users u ON o.buyer_id = u.user_id WHERE p.producer_id = ? GROUP BY o.order_id, o.total_amount, o.order_date, o.order_status, u.full_name ORDER BY o.order_date DESC");

    $st->bind_param('i', $uid);

    $st->execute(); $rs = $st->get_result();

    if (!$rs->num_rows) echo '<tr><td colspan="7" class="empty-cell">No orders to show yet.</td></tr>';

    while ($r = $rs->fetch_assoc()):

        $oStatus = strtolower(trim($r['order_status'] ?: 'pending'));

    ?><tr><td>#<?= e($r['order_id']) ?></td><td><?= e($r['buyer_name']) ?></td><td><?= e($r['items']) ?></td><td>Rs. <?= number_format((float)$r['total_amount'], 2) ?></td><td><?= e(date('M j, Y', strtotime($r['order_date']))) ?></td><td><span class="status-tag"><?= e(ucfirst($oStatus)) ?></span></td><td><div class="order-actions"><?php if ($oStatus === 'pending'): ?><form method="post" onsubmit="return confirm('Accept this order?');"><input type="hidden" name="order_id" value="<?= $r['order_id'] ?>"><button class="mini-button" name="order_status_update" value="confirmed">Accept</button></form><form method="post" onsubmit="return confirm('Cancel this order?');"><input type="hidden" name="order_id" value="<?= $r['order_id'] ?>"><button class="mini-button secondary" name="order_status_update" value="cancelled">Cancel</button></form><?php elseif ($oStatus === 'confirmed'): ?><form method="post" onsubmit="return confirm('Mark this order as delivered?');"><input type="hidden" name="order_id" value="<?= $r['order_id'] ?>"><button class="mini-button" name="order_status_update" value="delivered">Mark Delivered</button></form><?php else: ?><small>—</small><?php endif; ?></div></td></tr><?php endwhile;

}

?>
</tbody></table></div></div>
<?php elseif ($view === 'cart' && $role === 'buyer'): ?>

<div class="data-panel">

<div class="panel-heading"><div><h2>Your Shopping Cart</h2><p>Review items before checkout.</p></div></div>

<?php if (empty($_SESSION['cart'])): ?>

<div class="cart-empty-box">

    <p class="empty-cell">Your cart is empty.</p>

    <a class="primary-action" href="fertilizer_market.php">Browse fertilizer market</a>

</div>

<?php else: ?>

<div class="table-scroll"><table class="modern-table"><thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr></thead><tbody>

<?php $cartTotal = 0; foreach ($_SESSION['cart'] as $pid => $qty): $st = $conn->prepare("SELECT product_name, price FROM fertilizer_products WHERE product_id = ?"); $st->bind_param("i", $pid); $st->execute(); $p = $st->get_result()->fetch_assoc(); if (!$p) continue; $sub = $p['price'] * $qty; $cartTotal += $sub; ?>

<tr><td><?= e($p['product_name']) ?></td><td>Rs. <?= number_format($p['price'], 2) ?></td><td><?= e($qty) ?></td><td>Rs. <?= number_format($sub, 2) ?></td><td><a href="dashboard.php?view=cart&remove=<?= $pid ?>" onclick="return confirm('Remove this item from cart?');">Remove</a></td></tr>

<?php endforeach; ?>

</tbody></table></div>

<h3 style="margin-top:16px;">Total: Rs. <?= number_format($cartTotal, 2) ?></h3>

<form class="dashboard-form" method="post" onsubmit="return confirm('Place this order?');">

<div class="form-grid"><label class="full-field">Delivery address<textarea name="delivery_address" required></textarea></label><label>Payment method<select name="payment_method" required><option value="cash_on_delivery">Cash on delivery</option><option value="bank_transfer">Bank transfer</option></select></label></div>

<button class="primary-action" name="checkout" value="1">Place Order</button>

</form>

<?php endif; ?>

</div>
<?php elseif ($view === 'profile'): ?>

<div class="data-panel"><div class="panel-heading"><div><h2>Personal information</h2><p>Keep your contact details current.</p></div></div><form class="dashboard-form" method="post"><div class="form-grid"><label>Full name<input name="full_name" value="<?= e($me['full_name']) ?>" required></label><label>Email address<input value="<?= e($me['email']) ?>" disabled></label><label>Phone<input name="phone" value="<?= e($me['phone']) ?>" required></label><label>Account type<input value="<?= e(ucfirst($role)) ?>" disabled></label><label class="full-field">Address<textarea name="address" rows="3"><?= e($me['address']) ?></textarea></label></div><button class="primary-action" name="profile_update">Save changes</button></form></div>

<?php elseif ($view === 'notifications'): ?>

<div class="data-panel"><div class="panel-heading"><div><h2>Notifications</h2><p>Important updates related to your account.</p></div></div><?php $st = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<p class="empty-cell">You are all caught up. No notifications yet.</p>'; while ($n = $rs->fetch_assoc()): ?><div class="notification-row"><span class="notification-dot"></span><div><b><?= e($n['content']) ?></b><small><?= e(date('M j, Y · g:i a', strtotime($n['created_at']))) ?></small></div></div><?php endwhile; ?></div>

<?php endif; ?>

</main>
</div>
</body>
</html> 
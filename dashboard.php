<?php
// Dashboard - for provider, producer and buyer
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'buyer';

// Admin goes to admin page
if ($role === 'admin') {
    header('Location: admin.php');
    exit;
}

// Block unknown roles
if (!in_array($role, ['provider', 'producer', 'buyer'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

// Get my details
$meStmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
$meStmt->bind_param('i', $uid);
$meStmt->execute();
$me = $meStmt->get_result()->fetch_assoc();

// If account is not active, logout
if (!$me || $me['status'] !== 'active') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Which page to show
$view = $_GET['view'] ?? 'overview';

// Menu for each role
$menus = [
    'provider' => ['overview' => 'Overview', 'listings' => 'My waste listings', 'requests' => 'Incoming requests', 'profile' => 'My profile', 'notifications' => 'Notifications'],
    'producer' => ['overview' => 'Overview', 'products' => 'My fertilizer products', 'requests' => 'Waste requests', 'orders' => 'Orders received', 'profile' => 'My profile', 'notifications' => 'Notifications'],
    'buyer'    => ['overview' => 'Overview', 'orders' => 'My orders', 'cart' => 'My cart', 'profile' => 'My profile', 'notifications' => 'Notifications']
];
if (!isset($menus[$role][$view])) $view = 'overview';

$notice = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update profile
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

    // Provider accepts / rejects a waste request
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

    // Producer adds a new product
    if (isset($_POST['add_product']) && $role === 'producer') {
        $name = trim($_POST['product_name'] ?? '');
        $type = trim($_POST['fertilizer_type'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $unit = trim($_POST['unit'] ?? 'kg');
        $stock = (int)($_POST['stock_quantity'] ?? 0);
        $district = (int)($_POST['district_id'] ?? 0);
        if ($name && $type && $price >= 0 && $stock >= 0) {
            $st = $conn->prepare("INSERT INTO fertilizer_products (producer_id, product_name, fertilizer_type, description, price, unit, stock_quantity, district_id) VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0))");
            $st->bind_param('isssdsii', $uid, $name, $type, $desc, $price, $unit, $stock, $district);
            $st->execute();
            $notice = 'Product added to your marketplace inventory.';
        }
    }
}

// Helper to count rows for this user
function countFor($conn, $sql, $uid) {
    $st = $conn->prepare($sql);
    $st->bind_param('i', $uid);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    return (int)($r['n'] ?? 0);
}

// Stats + intro text for each role
$stats = [];
if ($role === 'provider') {
    $stats = [
        ['My listings', countFor($conn, 'SELECT COUNT(*) n FROM waste_listings WHERE provider_id = ?', $uid), 'listings'],
        ['Available', countFor($conn, "SELECT COUNT(*) n FROM waste_listings WHERE provider_id = ? AND status = 'available'", $uid), 'listings'],
        ['Requests', countFor($conn, 'SELECT COUNT(*) n FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id WHERE w.provider_id = ?', $uid), 'requests'],
        ['Pending', countFor($conn, "SELECT COUNT(*) n FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id WHERE w.provider_id = ? AND r.status = 'pending'", $uid), 'requests']
    ];
    $intro = 'Manage your agricultural waste, respond to producers and keep useful materials in circulation.';
} elseif ($role === 'producer') {
    $stats = [
        ['My products', countFor($conn, 'SELECT COUNT(*) n FROM fertilizer_products WHERE producer_id = ?', $uid), 'products'],
        ['Stock units', countFor($conn, 'SELECT COALESCE(SUM(stock_quantity), 0) n FROM fertilizer_products WHERE producer_id = ?', $uid), 'products'],
        ['Waste requests', countFor($conn, 'SELECT COUNT(*) n FROM exchange_requests WHERE producer_id = ?', $uid), 'requests'],
        ['Orders received', countFor($conn, 'SELECT COUNT(*) n FROM order_items oi JOIN fertilizer_products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id WHERE p.producer_id = ?', $uid), 'orders']
    ];
    $intro = 'Find raw materials, publish your organic fertilizer and manage customer orders.';
} else {
    $stats = [
        ['My orders', countFor($conn, 'SELECT COUNT(*) n FROM orders WHERE buyer_id = ?', $uid), 'orders'],
        ['Pending orders', countFor($conn, "SELECT COUNT(*) n FROM orders WHERE buyer_id = ? AND order_status = 'pending'", $uid), 'orders'],
        ['Available products', (int)$conn->query("SELECT COUNT(*) n FROM fertilizer_products WHERE status = 'active'")->fetch_assoc()['n'], 'market'],
        ['Available waste', (int)$conn->query("SELECT COUNT(*) n FROM waste_listings WHERE status = 'available'")->fetch_assoc()['n'], 'waste']
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
</head>
<body class="dash-page">
<div class="dash-layout">

    <!-- Sidebar -->
    <aside class="dash-sidebar">
        <a class="dash-brand" href="index.php"><img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange</a>

        <div class="dash-profile">
            <div class="avatar"><?= e(strtoupper(substr($me['full_name'], 0, 1))) ?></div>
            <div><strong><?= e($me['full_name']) ?></strong><small><?= e(ucfirst($role)) ?> account</small></div>
        </div>

        <div class="nav-caption">WORKSPACE</div>

        <nav class="dash-nav">
            <a class="<?= $view === 'overview' ? 'active' : '' ?>" href="dashboard.php"><span>Overview</span></a>
            <?php foreach ($menus[$role] as $key => $label): if ($key === 'overview') continue; ?>
                <a class="<?= $view === $key ? 'active' : '' ?>" href="dashboard.php?view=<?= e($key) ?>">
                    <span class="nav-symbol"><?= ['listings' => '', 'requests' => '', 'products' => '', 'orders' => '', 'cart' => '', 'profile' => '', 'notifications' => ''][$key] ?? '' ?></span>
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom">
            <a href="index.php"><span>Visit public home</span></a>
            <a class="logout-link" href="logout.php"><span>Log out</span></a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="dash-content">
        <header class="dash-topbar">
            <div>
                <p class="breadcrumb">Agro Exchange / <?= e(ucfirst($view)) ?></p>
                <h1><?= e($menus[$role][$view]) ?></h1>
            </div>
            <a class="top-profile" href="dashboard.php?view=profile">
                <span class="avatar small-avatar"><?= e(strtoupper(substr($me['full_name'], 0, 1))) ?></span>
                <span><?= e($me['full_name']) ?></span>
            </a>
        </header>

        <?php if ($notice): ?><div class="notice-success"><?= e($notice) ?></div><?php endif; ?>

        <!-- Overview -->
        <?php if ($view === 'overview'): ?>
            <section class="welcome-banner">
                <div>
                    <span class="eyebrow">YOUR AGRO EXCHANGE WORKSPACE</span>
                    <h2>Good to see you, <?= e(explode(' ', $me['full_name'])[0]) ?>.</h2>
                    <p><?= e($intro) ?></p>
                </div>
            </section>

            <section class="stats-grid">
                <?php foreach ($stats as $s): ?>
                    <a class="metric-card" href="dashboard.php?view=<?= e($s[2]) ?>"><span><?= e($s[0]) ?></span><strong><?= number_format($s[1]) ?></strong><small>View details</small></a>
                <?php endforeach; ?>
            </section>

            <section class="section-heading"><div><h2>Quick actions</h2><p>Jump straight to the tasks you use most.</p></div></section>

            <div class="action-grid">
                <?php if ($role === 'provider'): ?>
                    <a class="action-card" href="post_waste.php"><b>Post new waste</b><small>Share available organic materials</small></a>
                    <a class="action-card" href="dashboard.php?view=listings"><b>Manage listings</b><small>Edit or review your posts</small></a>
                    <a class="action-card" href="dashboard.php?view=requests"><b>Review requests</b><small>Respond to producer requests</small></a>
                <?php elseif ($role === 'producer'): ?>
                    <a class="action-card" href="waste_listings.php"><b>Find raw materials</b><small>Browse available agro-waste</small></a>
                    <a class="action-card" href="dashboard.php?view=products"><b>Manage products</b><small>Publish and update fertilizer</small></a>
                    <a class="action-card" href="dashboard.php?view=orders"><b>Manage orders</b><small>Review customer purchases</small></a>
                <?php else: ?>
                    <a class="action-card" href="fertilizer_market.php"><b>Explore fertilizer</b><small>Find organic products</small></a>
                    <a class="action-card" href="cart.php"><b>Open my cart</b><small>Review items before checkout</small></a>
                    <a class="action-card" href="dashboard.php?view=orders"><b>Track my orders</b><small>See order status and history</small></a>
                <?php endif; ?>
            </div>

            <!-- Recent activity -->
            <section class="section-heading"><div><h2>Recent activity</h2><p>Your latest updates at a glance.</p></div></section>
            <div class="data-panel">
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Reference</th><th>Details</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if ($role === 'provider') {
                                $st = $conn->prepare('SELECT listing_id, title, created_at, status FROM waste_listings WHERE provider_id = ? ORDER BY created_at DESC LIMIT 6');
                                $st->bind_param('i', $uid); $st->execute(); $rows = $st->get_result();
                                while ($r = $rows->fetch_assoc()): ?>
                                    <tr><td>#<?= e($r['listing_id']) ?></td><td><?= e($r['title']) ?></td><td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td></tr>
                                <?php endwhile;
                            } elseif ($role === 'producer') {
                                $st = $conn->prepare('SELECT product_id, product_name, created_at, status FROM fertilizer_products WHERE producer_id = ? ORDER BY created_at DESC LIMIT 6');
                                $st->bind_param('i', $uid); $st->execute(); $rows = $st->get_result();
                                while ($r = $rows->fetch_assoc()): ?>
                                    <tr><td>#<?= e($r['product_id']) ?></td><td><?= e($r['product_name']) ?></td><td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td></tr>
                                <?php endwhile;
                            } else {
                                $st = $conn->prepare('SELECT order_id, total_amount, order_date, order_status FROM orders WHERE buyer_id = ? ORDER BY order_date DESC LIMIT 6');
                                $st->bind_param('i', $uid); $st->execute(); $rows = $st->get_result();
                                while ($r = $rows->fetch_assoc()): ?>
                                    <tr><td>#<?= e($r['order_id']) ?></td><td>Rs. <?= number_format((float)$r['total_amount'], 2) ?></td><td><?= e(date('M j, Y', strtotime($r['order_date']))) ?></td><td><span class="status-tag"><?= e(ucfirst($r['order_status'])) ?></span></td></tr>
                                <?php endwhile;
                            }
                            if (isset($rows) && $rows->num_rows === 0): ?>
                                <tr><td colspan="4" class="empty-cell">Nothing here yet. Your activity will appear here.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- My waste listings (provider) -->
        <?php elseif ($view === 'listings' && $role === 'provider'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Your waste listings</h2><p>Keep your available materials up to date.</p></div><a class="primary-action" href="post_waste.php">Post waste</a></div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Listing</th><th>Quantity</th><th>Available from</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php $st = $conn->prepare('SELECT * FROM waste_listings WHERE provider_id = ? ORDER BY created_at DESC'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">You have not posted any waste yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= e($r['title']) ?></td>
                                    <td><?= e($r['quantity_available']) ?> <?= e($r['unit']) ?></td>
                                    <td><?= e($r['available_from'] ?: '—') ?></td>
                                    <td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td>
                                    <td><a href="post_waste.php?edit=<?= $r['listing_id'] ?>">Edit</a></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Requests -->
        <?php elseif ($view === 'requests'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2><?= $role === 'provider' ? 'Requests for your waste' : 'Your waste requests' ?></h2><p>Track the progress of material exchanges.</p></div></div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Waste listing</th><th>Requested by</th><th>Quantity</th><th>Requested</th><th>Status / Action</th></tr></thead>
                        <tbody>
                            <?php
                            if ($role === 'provider') {
                                $st = $conn->prepare('SELECT r.*, w.title, u.full_name FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id JOIN users u ON r.producer_id = u.user_id WHERE w.provider_id = ? ORDER BY r.requested_at DESC');
                            } else {
                                $st = $conn->prepare('SELECT r.*, w.title, u.full_name FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id JOIN users u ON w.provider_id = u.user_id WHERE r.producer_id = ? ORDER BY r.requested_at DESC');
                            }
                            $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result();
                            if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No exchange requests yet.</td></tr>';
                            while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= e($r['title']) ?></td>
                                    <td><?= e($r['full_name']) ?></td>
                                    <td><?= e($r['quantity_requested']) ?> kg</td>
                                    <td><?= e(date('M j, Y', strtotime($r['requested_at']))) ?></td>
                                    <td>
                                        <?php if ($role === 'provider' && $r['status'] === 'pending'): ?>
                                            <form class="inline-actions" method="post">
                                                <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                                                <button class="mini-button" name="request_action" value="accepted">Accept</button>
                                                <button class="mini-button secondary" name="request_action" value="rejected">Reject</button>
                                            </form>
                                        <?php else: ?><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- My products (producer) -->
        <?php elseif ($view === 'products' && $role === 'producer'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Your fertilizer products</h2><p>Add the organic products you make available to buyers.</p></div></div>

                <!-- Add product form -->
                <form class="dashboard-form" method="post">
                    <div class="form-grid">
                        <label>Product name<input name="product_name" required></label>
                        <label>Fertilizer type
                            <select name="fertilizer_type"><option>Compost</option><option>Vermicompost</option><option>Bokashi</option><option>Liquid fertilizer</option><option>Other</option></select>
                        </label>
                        <label>Price (Rs.)<input type="number" name="price" min="0" step="0.01" required></label>
                        <label>Unit<input name="unit" value="kg" required></label>
                        <label>Stock quantity<input type="number" name="stock_quantity" min="0" value="0" required></label>
                        <label>District
                            <select name="district_id"><option value="0">Select district</option>
                                <?php $ds = $conn->query('SELECT district_id, district_name FROM districts ORDER BY district_name'); while ($d = $ds->fetch_assoc()): ?>
                                    <option value="<?= $d['district_id'] ?>" <?= $me['district_id'] == $d['district_id'] ? 'selected' : '' ?>><?= e($d['district_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </label>
                        <label class="full-field">Description<textarea name="description" rows="3"></textarea></label>
                    </div>
                    <button class="primary-action" name="add_product">Add product</button>
                </form>

                <!-- Product list -->
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Product</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php $st = $conn->prepare('SELECT * FROM fertilizer_products WHERE producer_id = ? ORDER BY created_at DESC'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No products added yet.</td></tr>'; while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= e($r['product_name']) ?></td>
                                    <td><?= e($r['fertilizer_type']) ?></td>
                                    <td>Rs. <?= number_format((float)$r['price'], 2) ?></td>
                                    <td><?= e($r['stock_quantity']) ?> <?= e($r['unit']) ?></td>
                                    <td><span class="status-tag"><?= e(ucfirst($r['status'])) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Orders -->
        <?php elseif ($view === 'orders'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2><?= $role === 'producer' ? 'Customer orders' : 'Your order history' ?></h2><p><?= $role === 'producer' ? 'Orders containing your fertilizer products.' : 'Follow the status of your purchases.' ?></p></div></div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Order</th><th><?= $role === 'producer' ? 'Buyer' : 'Items total' ?></th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            if ($role === 'buyer') {
                                $st = $conn->prepare('SELECT order_id, total_amount, order_date, order_status FROM orders WHERE buyer_id = ? ORDER BY order_date DESC');
                            } else {
                                $st = $conn->prepare('SELECT DISTINCT o.order_id, o.total_amount, o.order_date, o.order_status FROM orders o JOIN order_items oi ON o.order_id = oi.order_id JOIN fertilizer_products p ON oi.product_id = p.product_id WHERE p.producer_id = ? ORDER BY o.order_date DESC');
                            }
                            $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result();
                            if (!$rs->num_rows) echo '<tr><td colspan="4" class="empty-cell">No orders to show yet.</td></tr>';
                            while ($r = $rs->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= e($r['order_id']) ?></td>
                                    <td>Rs. <?= number_format((float)$r['total_amount'], 2) ?></td>
                                    <td><?= e(date('M j, Y', strtotime($r['order_date']))) ?></td>
                                    <td><span class="status-tag"><?= e(ucfirst($r['order_status'])) ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- Cart -->
        <?php elseif ($view === 'cart'): ?>
            <div class="data-panel empty-state">
                <h2>Your cart is waiting</h2>
                <p>Explore organic fertilizer and add products to your cart.</p>
                <a class="primary-action" href="fertilizer_market.php">Browse fertilizer market</a>
            </div>

        <!-- Profile -->
        <?php elseif ($view === 'profile'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Personal information</h2><p>Keep your contact details current.</p></div></div>
                <form class="dashboard-form" method="post">
                    <div class="form-grid">
                        <label>Full name<input name="full_name" value="<?= e($me['full_name']) ?>" required></label>
                        <label>Email address<input value="<?= e($me['email']) ?>" disabled></label>
                        <label>Phone<input name="phone" value="<?= e($me['phone']) ?>" required></label>
                        <label>Account type<input value="<?= e(ucfirst($role)) ?>" disabled></label>
                        <label class="full-field">Address<textarea name="address" rows="3"><?= e($me['address']) ?></textarea></label>
                    </div>
                    <button class="primary-action" name="profile_update">Save changes</button>
                </form>
            </div>

        <!-- Notifications -->
        <?php elseif ($view === 'notifications'): ?>
            <div class="data-panel">
                <div class="panel-heading"><div><h2>Notifications</h2><p>Important updates related to your account.</p></div></div>
                <?php $st = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30'); $st->bind_param('i', $uid); $st->execute(); $rs = $st->get_result(); if (!$rs->num_rows) echo '<p class="empty-cell">You are all caught up. No notifications yet.</p>'; while ($n = $rs->fetch_assoc()): ?>
                    <div class="notification-row">
                        <span class="notification-dot"></span>
                        <div><b><?= e($n['content']) ?></b><small><?= e(date('M j, Y · g:i a', strtotime($n['created_at']))) ?></small></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </main>
</div>
</body>
</html>
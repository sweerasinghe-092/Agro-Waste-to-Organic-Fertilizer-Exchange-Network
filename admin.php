<?php
require_once 'config/db.php'; require_once 'includes/functions.php'; require_role('admin');
$uid = (int)$_SESSION['user_id'];
$meSt = $conn->prepare('SELECT * FROM users WHERE user_id=?');
$meSt->bind_param('i', $uid);
$meSt->execute();
$me = $meSt->get_result()->fetch_assoc();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User approve / suspend
    if (isset($_POST['user_action'])) {
        $id = (int)$_POST['user_id'];
        $action = $_POST['user_action'];
        if ($id !== $uid && in_array($action, ['active', 'suspended'], true)) {
            $st = $conn->prepare("UPDATE users SET status=? WHERE user_id=? AND role<>'admin'");
            $st->bind_param('si', $action, $id);
            $st->execute();
            if ($st->affected_rows > 0) {
                $msg = $action === 'active' ? 'Your Agro Exchange account has been approved. You can now log in.' : 'Your Agro Exchange account has been suspended. Please contact the administrator.';
                notify_user($conn, $id, 'account_status', $msg, 'login.php');
            }
            $notice = 'User account status updated.';
        }
    }
    // Admin removes a listing
    if (isset($_POST['remove_listing'])) {
        $id = (int)$_POST['listing_id'];
        $st = $conn->prepare("UPDATE waste_listings SET status='removed' WHERE listing_id=?");
        $st->bind_param('i', $id);
        $st->execute();
        $notice = 'Listing removed from public results.';
    }
    // New category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['category_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name) {
            $st = $conn->prepare('INSERT IGNORE INTO waste_categories(category_name,description) VALUES(?,?)');
            $st->bind_param('ss', $name, $desc);
            $st->execute();
            $notice = 'Waste category saved.';
        }
    }
    // Mark all notifications as read - Delete is not used here
    if (isset($_POST['mark_all_read'])) {
        $st = $conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?');
        $st->bind_param('i', $uid);
        $st->execute();
        header('Location: admin.php?view=notifications');
        exit;
    }
    // Mark one notification as read
    if (isset($_POST['mark_read'])) {
        $nid = (int)$_POST['notification_id'];
        $st = $conn->prepare('UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?');
        $st->bind_param('ii', $nid, $uid);
        $st->execute();
        header('Location: admin.php?view=notifications');
        exit;
    }
    // NOTE: delete_notification is removed on purpose.
    // Admin notifications are not deleted, only marked as read.
    // This is how normal admin panels work.
}

$view = $_GET['view'] ?? 'overview';
$allowed = ['overview', 'users', 'waste', 'products', 'orders', 'categories', 'notifications'];
if (!in_array($view, $allowed, true)) $view = 'overview';

function adminCount($conn, $sql) {
    return (int)$conn->query($sql)->fetch_assoc()['n'];
}
$stats = [['Registered users', adminCount($conn, 'SELECT COUNT(*) n FROM users'), ''], ['Pending approvals', adminCount($conn, "SELECT COUNT(*) n FROM users WHERE status='pending' AND role<>'admin'"), ''], ['Available waste', adminCount($conn, "SELECT COUNT(*) n FROM waste_listings WHERE status='available'"), ''], ['Fertilizer products', adminCount($conn, "SELECT COUNT(*) n FROM fertilizer_products WHERE status='active'"), ''], ['Customer orders', adminCount($conn, 'SELECT COUNT(*) n FROM orders'), ''], ['Exchange requests', adminCount($conn, 'SELECT COUNT(*) n FROM exchange_requests'), '']];
$menus = ['overview' => 'Overview', 'users' => 'User management', 'waste' => 'Waste moderation', 'products' => 'Products', 'orders' => 'Orders', 'categories' => 'Categories', 'notifications' => 'Notifications'];
$adminUnread = adminCount($conn, "SELECT COUNT(*) n FROM notifications WHERE user_id={$uid} AND is_read=0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard | Agro Exchange</title>
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/dashboard.css">
<style>
.panel-heading { display:flex; justify-content:space-between; align-items:center; gap:16px; }
.panel-heading form { margin-left:auto; flex-shrink:0; }
.notification-row { display:flex; align-items:center; justify-content:space-between; gap:16px; }
.notification-content { flex:1; }
.notification-actions { display:flex; gap:8px; align-items:center; flex-shrink:0; margin-left:auto; white-space:nowrap; }
.notification-actions form { margin:0; }
</style>
</head>
<body class="dash-page admin-theme">
<div class="dash-layout">
<aside class="dash-sidebar">
<a class="dash-brand" href="admin.php"><img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange</a>
<div class="dash-profile"><div class="avatar">A</div><div><strong><?= e($me['full_name'] ?? 'Administrator') ?></strong><small>System administrator</small></div></div>
<div class="nav-caption">ADMINISTRATION</div>
<nav class="dash-nav">
<?php foreach ($menus as $key => $label): ?>
<a class="<?= $view === $key ? 'active' : '' ?>" href="admin.php?view=<?= $key ?>"><span><?= e($label) ?></span><?php if ($key === 'notifications' && $adminUnread > 0): ?><small class="nav-count"><?= number_format($adminUnread) ?></small><?php endif; ?></a>
<?php endforeach; ?>
</nav>
<div class="sidebar-bottom"><a href="index.php"><span>Visit public home</span></a><a class="logout-link" href="logout.php"><span>Log out</span></a></div>
</aside>
<main class="dash-content">
<header class="dash-topbar"><div><p class="breadcrumb">Agro Exchange / Administration</p><h1><?= e($menus[$view]) ?></h1></div><span class="admin-pill">ADMINISTRATOR</span></header>
<?php if ($notice): ?><div class="notice-success"><?= e($notice) ?></div><?php endif; ?>

<?php if ($view === 'overview'): ?>
<section class="welcome-banner"><div><span class="eyebrow">PLATFORM CONTROL CENTRE</span><h2>Welcome to the admin workspace.</h2><p>Monitor the Agro Exchange community, review content and keep the platform running smoothly.</p></div></section>
<section class="stats-grid"><?php foreach ($stats as $s): ?><div class="metric-card"><span><?= e($s[0]) ?></span><strong><?= number_format($s[1]) ?></strong><small>Platform summary</small></div><?php endforeach; ?></section>
<section class="section-heading"><div><h2>Administration tools</h2><p>Choose an area to manage.</p></div></section>
<div class="action-grid"><a class="action-card" href="admin.php?view=users"><b>Manage users</b><small>Activate or suspend accounts</small></a><a class="action-card" href="admin.php?view=waste"><b>Moderate listings</b><small>Review community waste posts</small></a><a class="action-card" href="admin.php?view=categories"><b>Manage categories</b><small>Maintain waste categories</small></a><a class="action-card" href="admin.php?view=notifications"><b>View notifications</b><small>Check new registrations and alerts</small></a></div>

<?php elseif ($view === 'users'): ?>
<div class="data-panel"><div class="panel-heading"><div><h2>Registered accounts</h2><p>Review account roles and access status.</p></div></div><div class="table-scroll"><table class="modern-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>District</th><th>Registered</th><th>Status</th><th>Action</th></tr></thead><tbody><?php $rs = $conn->query("SELECT u.user_id,u.full_name,u.email,u.role,u.status,u.created_at,d.district_name FROM users u LEFT JOIN districts d ON u.district_id=d.district_id ORDER BY (u.status='pending') DESC,u.created_at DESC"); while ($u = $rs->fetch_assoc()): ?><tr><td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e(ucfirst($u['role'])) ?></td><td><?= e($u['district_name'] ?? '—') ?></td><td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td><td><span class="status-tag"><?= e(ucfirst($u['status'])) ?></span></td><td><?php if ($u['role'] !== 'admin'): ?><form method="post"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>"><?php if ($u['status'] !== 'active'): ?><button class="mini-button" name="user_action" value="active">Approve / Activate</button><?php endif; ?><?php if ($u['status'] !== 'suspended'): ?><button class="mini-button secondary" name="user_action" value="suspended">Suspend</button><?php endif; ?></form><?php else: ?><small>Protected</small><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></div>

<?php elseif ($view === 'waste'): ?>
<div class="data-panel"><div class="panel-heading"><div><h2>Waste listing moderation</h2><p>Remove listings that should no longer appear in the marketplace.</p></div></div><div class="table-scroll"><table class="modern-table"><thead><tr><th>Listing</th><th>Provider</th><th>Quantity</th><th>Status</th><th>Action</th></tr></thead><tbody><?php $rs = $conn->query('SELECT w.listing_id,w.title,w.quantity_available,w.unit,w.status,u.full_name FROM waste_listings w JOIN users u ON w.provider_id=u.user_id ORDER BY w.created_at DESC'); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No waste listings yet.</td></tr>'; while ($w = $rs->fetch_assoc()): ?><tr><td><?= e($w['title']) ?></td><td><?= e($w['full_name']) ?></td><td><?= e($w['quantity_available']) ?> <?= e($w['unit']) ?></td><td><?= e(ucfirst($w['status'])) ?></td><td><?php if ($w['status'] !== 'removed'): ?><form method="post" onsubmit="return confirm('Do you want to remove this listing?');"><input type="hidden" name="listing_id" value="<?= $w['listing_id'] ?>"><button class="mini-button secondary" name="remove_listing" value="1">Remove</button></form><?php else: ?><small>Removed</small><?php endif; ?></td></tr><?php endwhile; ?></tbody></table></div></div>

<?php elseif ($view === 'products'): ?>
<div class="data-panel"><div class="panel-heading"><div><h2>Fertilizer marketplace products</h2><p>Monitor active and inactive products.</p></div></div><div class="table-scroll"><table class="modern-table"><thead><tr><th>Product</th><th>Producer</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead><tbody><?php $rs = $conn->query('SELECT p.*,u.full_name FROM fertilizer_products p JOIN users u ON p.producer_id=u.user_id ORDER BY p.created_at DESC'); if (!$rs->num_rows) echo '<tr><td colspan="6" class="empty-cell">No products yet.</td></tr>'; while ($p = $rs->fetch_assoc()): ?><tr><td><?= e($p['product_name']) ?></td><td><?= e($p['full_name']) ?></td><td><?= e($p['fertilizer_type']) ?></td><td>Rs. <?= number_format((float)$p['price'], 2) ?></td><td><?= e($p['stock_quantity']) ?></td><td><?= e(ucfirst($p['status'])) ?></td></tr><?php endwhile; ?></tbody></table></div></div>

<?php elseif ($view === 'orders'): ?>
<div class="data-panel"><div class="panel-heading"><div><h2>All customer orders</h2><p>Platform-wide order overview.</p></div></div><div class="table-scroll"><table class="modern-table"><thead><tr><th>Order</th><th>Buyer</th><th>Total</th><th>Date</th><th>Status</th></tr></thead><tbody><?php $rs = $conn->query('SELECT o.*,u.full_name FROM orders o JOIN users u ON o.buyer_id=u.user_id ORDER BY o.order_date DESC'); if (!$rs->num_rows) echo '<tr><td colspan="5" class="empty-cell">No orders yet.</td></tr>'; while ($o = $rs->fetch_assoc()): ?><tr><td>#<?= e($o['order_id']) ?></td><td><?= e($o['full_name']) ?></td><td>Rs. <?= number_format((float)$o['total_amount'], 2) ?></td><td><?= e(date('M j, Y', strtotime($o['order_date']))) ?></td><td><?= e(ucfirst($o['order_status'])) ?></td></tr><?php endwhile; ?></tbody></table></div></div>

<?php elseif ($view === 'notifications'): ?>
<div class="data-panel">
<div class="panel-heading"><div><h2>Admin notifications</h2><p>New registrations and important account updates appear here.</p></div><form method="post"><button class="mini-button" name="mark_all_read" value="1">Mark all as read</button></form></div>
<?php $st = $conn->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50'); $st->bind_param('i', $uid); $st->execute(); $ns = $st->get_result(); if (!$ns->num_rows): ?><p class="empty-cell">No notifications yet. New registrations will appear here.</p><?php else: while ($n = $ns->fetch_assoc()): ?>
<div class="notification-row <?= ((int)$n['is_read'] === 0 ? 'unread' : '') ?>">
<span class="notification-dot"></span>
<div class="notification-content"><b><?= e($n['content']) ?></b><small><?= e(date('M j, Y · g:i a', strtotime($n['created_at']))) ?></small><?php if (!empty($n['link'])): ?><a href="<?= e($n['link']) ?>">Open</a><?php endif; ?></div>
<div class="notification-actions">
<?php if (!(int)$n['is_read']): ?>
<form method="post"><input type="hidden" name="notification_id" value="<?= $n['notification_id'] ?>"><button class="mini-button secondary" name="mark_read" value="1">Mark read</button></form>
<?php else: ?>
<small>Read</small>
<?php endif; ?>
</div>
</div>
<?php endwhile; endif; ?>
</div>

<?php elseif ($view === 'categories'): ?>
<div class="data-panel"><div class="panel-heading"><div><h2>Waste categories</h2><p>Add and maintain the categories used in waste listings.</p></div></div><form class="dashboard-form" method="post"><div class="form-grid"><label>Category name<input name="category_name" required></label><label>Description<input name="description"></label></div><button class="primary-action" name="add_category">Add category</button></form><div class="category-list"><?php $rs = $conn->query('SELECT * FROM waste_categories ORDER BY category_name'); while ($c = $rs->fetch_assoc()): ?><div><b><?= e($c['category_name']) ?></b><small><?= e($c['description']) ?></small></div><?php endwhile; ?></div></div>
<?php endif; ?>
</main></div></body></html>
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
                                while ($r = $rs->fetch_assoc()): ?>
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
		
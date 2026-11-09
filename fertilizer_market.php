<?php

require_once 'config/db.php';
require_once 'includes/functions.php';


$type = trim($_GET['type'] ?? '');
$search = trim($_GET['q'] ?? '');


$sql = "SELECT p.*, u.full_name, d.district_name FROM fertilizer_products p JOIN users u ON p.producer_id = u.user_id LEFT JOIN districts d ON p.district_id = d.district_id WHERE p.status = 'active' AND p.stock_quantity > 0";
$params = [];
$types = '';


if ($type !== '') {
    $sql .= " AND p.fertilizer_type = ?";
    $params[] = $type;
    $types .= 's';
}


if ($search !== '') {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$sql .= " ORDER BY p.created_at DESC";
$st = $conn->prepare($sql);
if ($params) $st->bind_param($types, ...$params);
$st->execute();
$products = $st->get_result();

$pageTitle = "Fertilizer Market";
include 'includes/header.php';
?>

<section class="market-page">
    <div class="market-heading">
        <p class="market-eyebrow">AGRO EXCHANGE MARKETPLACE</p>
        <h1 class="section-title">Organic Fertilizer Marketplace</h1>
        <p class="market-intro">Discover locally produced organic fertilizers for healthier soil and more sustainable farming.</p>
    </div>

    <!-- Search and filter form -->
    <form method="get" class="market-filter panel">
        <div class="market-field">
            <label for="product-search">Search product</label>
            <input id="product-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Enter a product name">
        </div>

        <div class="market-field">
            <label for="fertilizer-type">Fertilizer type</label>
            <select id="fertilizer-type" name="type">
                <option value="">All types</option>
                <?php foreach (['Compost', 'Vermicompost', 'Bokashi', 'Liquid fertilizer'] as $t): ?>
                    <option value="<?= e($t) ?>" <?= strcasecmp($type, $t) === 0 ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="market-filter-action"><button type="submit">Filter products</button></div>
    </form>

    <!-- Product list -->
    <div class="market-results">
        <?php if ($products->num_rows): ?>
            <div class="market-results-heading">
                <h2>Available products</h2>
                <span><?= number_format($products->num_rows) ?> products</span>
            </div>

            <div class="grid market-product-grid">
                <?php while ($p = $products->fetch_assoc()): ?>
                    <article class="card market-product-card">
                        <span class="pill"><?= e($p['fertilizer_type']) ?></span>
                        <h3><?= e($p['product_name']) ?></h3>
                        <p><?= e($p['description']) ?></p>

                        <div class="product-meta">
                            <p><strong>Source:</strong> <?= e($p['source_materials']) ?></p>
                            <p><strong>Usage:</strong> <?= e($p['usage_instructions']) ?></p>
                            <p><strong>Producer:</strong> <?= e($p['full_name']) ?></p>
                            <p><strong>District:</strong> <?= e($p['district_name'] ?? 'Not specified') ?></p>
                        </div>

                        <p class="price">Rs. <?= number_format((float)$p['price'], 2) ?> <span>/ <?= e($p['unit']) ?></span></p>
                        <p class="stock-note">Available stock: <?= e($p['stock_quantity']) ?> <?= e($p['unit']) ?></p>

                        <!-- Show add to cart only for buyers -->
                        <?php if (($_SESSION['role'] ?? '') === 'buyer'): ?>
                            <form method="post" action="cart.php" class="market-cart-form">
                                <input type="hidden" name="add_product" value="<?= $p['product_id'] ?>">
                                <label for="quantity-<?= $p['product_id'] ?>">Quantity</label>
                                <div class="market-cart-controls">
                                    <input id="quantity-<?= $p['product_id'] ?>" type="number" name="quantity" min="1" max="<?= $p['stock_quantity'] ?>" value="1" required>
                                    <button type="submit">Add to cart</button>
                                </div>
                            </form>
                        <?php elseif (empty($_SESSION['user_id'])): ?>
                            <a class="btn" href="login.php">Login to buy</a>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <!-- No products found -->
            <div class="market-empty">
                <h2>No fertilizer products found</h2>
                <p>There are currently no products matching your search. Try another product name or choose “All types”.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
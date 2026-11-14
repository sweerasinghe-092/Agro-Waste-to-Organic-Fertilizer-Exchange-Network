<?php
// Home page
require_once 'config/db.php';
require_once 'includes/functions.php';

// Count active users
$users = (int)$conn->query("SELECT COUNT(*) n FROM users WHERE status = 'active'")->fetch_assoc()['n'];

// Count available waste listings
$listings = (int)$conn->query("SELECT COUNT(*) n FROM waste_listings WHERE status = 'available'")->fetch_assoc()['n'];

// Total quantity exchanged
$exchanged = (float)$conn->query("SELECT COALESCE(SUM(quantity_requested), 0) n FROM exchange_requests WHERE status = 'completed'")->fetch_assoc()['n'];

// Get latest 4 active products
$products = $conn->query("SELECT p.*, u.full_name FROM fertilizer_products p JOIN users u ON p.producer_id = u.user_id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 4");

$pageTitle = "Home";
include 'includes/header.php';
?>

<!-- Hero section -->
<section class="hero">
    <span class="eyebrow">A greener tomorrow starts here</span>
    <h1>From farm waste to a <span style="color:#c5e7a7">valuable resource.</span></h1>
    <p>One sustainable marketplace connecting agricultural waste providers, organic fertilizer producers and buyers. Give nature's leftovers a second life.</p>
    <div class="actions">
        <a class="btn" href="waste_listings.php">Explore Agro-Waste</a>
        <a class="btn btn-secondary" href="fertilizer_market.php">Browse Fertilizer</a>
    </div>
</section>

<!-- Impact numbers -->
<section class="home-section impact-section">
    <h2 class="section-title">Our impact at a glance</h2>
    <div class="grid">
        <div class="card stat-card"><span class="pill">Community</span><h2><?= $users ?></h2><p>Active registered users</p></div>
        <div class="card stat-card"><span class="pill">Resources</span><h2><?= $listings ?></h2><p>Waste listings available</p></div>
        <div class="card stat-card"><span class="pill">Circular economy</span><h2><?= number_format($exchanged, 2) ?></h2><p>Quantity exchanged (listed units)</p></div>
    </div>
</section>

<!-- How it works -->
<section class="home-section process-section">
    <h2 class="section-title">How Agro Exchange works</h2>
    <div class="grid">
        <div class="card">
            <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=700&q=80" alt="Agricultural fields">
            <div class="step-number">01</div>
            <h3>Providers list waste</h3>
            <p>Farmers and businesses share organic waste materials available for collection.</p>
        </div>
        <div class="card">
            <img src="https://images.unsplash.com/photo-1589923188900-85dae523342b?auto=format&fit=crop&w=700&q=80" alt="Compost and soil" onerror="this.src='https://images.unsplash.com/photo-1589923188900-85dae523342b?auto=format&fit=crop&w=700&q=80'">
            <div class="step-number">02</div>
            <h3>Producers request materials</h3>
            <p>Fertilizer makers discover suitable materials and request the quantities they need.</p>
        </div>
        <div class="card">
            <img src="https://images.unsplash.com/photo-1585314062604-1a357de8b000?auto=format&fit=crop&w=700&q=80" alt="Organic plant growing">
            <div class="step-number">03</div>
            <h3>Buy greener fertilizer</h3>
            <p>Buyers browse locally produced organic fertilizer and support sustainable agriculture.</p>
        </div>
    </div>
</section>

<!-- Latest products -->
<section class="home-section market-section">
    <h2 class="section-title">Fresh from the fertilizer market</h2>
    <div class="grid">
        <?php if ($products->num_rows): while ($p = $products->fetch_assoc()): ?>
            <div class="card">
                <img class="feature-image" src="https://images.unsplash.com/photo-1589923188900-85dae523342b?auto=format&fit=crop&w=700&q=80" alt="Organic soil fertilizer">
                <span class="pill"><?= e($p['fertilizer_type']) ?></span>
                <h3><?= e($p['product_name']) ?></h3>
                <p class="price">Rs. <?= number_format($p['price'], 2) ?> / <?= e($p['unit']) ?></p>
                <p class="small">Produced by <?= e($p['full_name']) ?></p>
                <a class="btn" href="fertilizer_market.php">View product market</a>
            </div>
        <?php endwhile; else: ?>
            <div class="card">
                <h3>Products coming soon</h3>
                <p>Our marketplace is growing. Check back soon for locally made organic fertilizer.</p>
                <a class="btn" href="fertilizer_market.php">Explore market</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Composting tips -->
<section class="home-section tips-section">
    <h2 class="section-title">Simple composting tips</h2>
    <div class="grid">
        <div class="card"><h3>Balance materials</h3><p>Mix dry leaves and crop residues with green materials like vegetable scraps.</p></div>
        <div class="card"><h3>Keep it moist</h3><p>Maintain moisture similar to a wrung-out sponge and avoid waterlogging.</p></div>
        <div class="card"><h3>Let air flow</h3><p>Turn the compost regularly to support airflow and healthy decomposition.</p></div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

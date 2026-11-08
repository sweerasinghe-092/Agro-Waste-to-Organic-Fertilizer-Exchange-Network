<?php
// Database connection
require_once 'config/db.php';
require_once 'includes/functions.php';

// Count active users
$users = 0;

$userResult = $conn->query(
    "SELECT COUNT(*) AS n
     FROM users
     WHERE status = 'active'"
);
 
if ($userResult) {
    $users = (int)$userResult->fetch_assoc()['n'];
}

// Count available waste listings
$listings = 0;

$listingResult = $conn->query(
    "SELECT COUNT(*) AS n
     FROM waste_listings
     WHERE status = 'available'"
);

if ($listingResult) {
    $listings = (int)$listingResult->fetch_assoc()['n'];
}

// Calculate exchanged quantity
$exchanged = 0;

$exchangeResult = $conn->query(
    "SELECT COALESCE(SUM(quantity_requested), 0) AS n
     FROM exchange_requests
     WHERE status = 'completed'"
);

if ($exchangeResult) {
    $exchanged = (float)$exchangeResult->fetch_assoc()['n'];
}

// Page title
$pageTitle = "Home";

include 'includes/header.php';
?>
<style>
/* Home Page CSS */

.hero {
    padding: 80px 8%;
    text-align: center;
    background:#173f2b;
    color: white;
    border-radius: 12px;
}

.hero .eyebrow {
    color:#c5e7a7;
    font-weight: bold;
}

.hero h1 {
    font-size: 42px;
    margin: 20px 0;
}

.hero h1 span {
    color:#c5e7a7;
}

.hero p {
    max-width: 700px;
    margin: 0 auto 25px;
    line-height: 1.7;
}

.actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 12px 22px;
    background:#4d9b55;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

.btn-secondary {
    background: white;
    color:#173f2b;
}

.home-section {
    padding: 45px 6%;
}

.section-title {
    text-align: center;
    color:#173f2b;
    margin-bottom: 30px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
}

.card img {
    width: 100%;
    height: 190px;
    object-fit: cover;
    border-radius: 8px;
}

.stat-card {
    text-align: center;
}

.stat-card h2 {
    font-size: 32px;
    color:#287442;
}

.pill {
    display: inline-block;
    padding: 6px 12px;
    background:#e3f2e5;
    color:#287442;
    border-radius: 20px;
    font-size: 13px;
}

.step-number {
    color:#287442;
    font-size: 25px;
    font-weight: bold;
    margin-top: 15px;
}

.price {
    color:#287442;
    font-weight: bold;
}

.small {
    font-size: 14px;
    color:#666;
}

@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }

    .hero h1 {
        font-size: 30px;
    }

    .home-section {
        padding: 30px 5%;
    }
}
</style>

<!-- Hero Section -->
<section class="hero">
    <span class="eyebrow">A greener tomorrow starts here</span>

    <h1>
        From farm waste to a
        <span>valuable resource.</span>
    </h1>

    <p>
        One sustainable marketplace connecting agricultural
        waste providers, organic fertilizer producers and buyers.
        Give nature's leftovers a second life.
    </p>

    <div class="actions">
        <a class="btn" href="waste_listings.php">
            Explore Agro-Waste
        </a>

        <a class="btn btn-secondary" href="fertilizer_market.php">
            Browse Fertilizer
        </a>
    </div>
</section>

<!-- Impact Numbers -->
<section class="home-section impact-section">
    <h2 class="section-title">Our impact at a glance</h2>

    <div class="grid">
        <div class="card stat-card">
            <span class="pill">Community</span>
            <h2><?= $users ?></h2></h2>
            <p>Active registered users</p>
        </div>

        <div class="card stat-card">
            <span class="pill">Resources</span>
            <h2><?= $listings ?></h2>
            <p>Waste listings available</p>
        </div>

        <div class="card stat-card">
            <span class="pill">Circular economy</span>
            <h2><?= number_format($exchanged, 2) ?></h2>
            <p>Quantity exchanged (listed units)</p>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="home-section process-section">
    <h2 class="section-title">How Agro Exchange works</h2>

    <div class="grid">
        <div class="card">
            <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=700&q=80"
                 alt="Agricultural fields">
            <div class="step-number">01</div>
            <h3>Providers list waste</h3>
            <p>Farmers and businesses share organic waste materials available for collection.</p>
        </div>

        <div class="card">
            <img src="https://images.unsplash.com/photo-1589923188900-85dae523342b?auto=format&fit=crop&w=700&q=80"
                 alt="Compost and soil">
            <div class="step-number">02</div>
            <h3>Producers request materials</h3>
            <p>Fertilizer makers discover suitable materials and request the quantities they need.</p>
        </div>

        <div class="card">
            <img src="https://images.unsplash.com/photo-1585314062604-1a357de8b000?auto=format&fit=crop&w=700&q=80"
                 alt="Organic plant growing">
            <div class="step-number">03</div>
            <h3>Buy greener fertilizer</h3>
            <p>Buyers browse locally produced organic fertilizer and support sustainable agriculture.</p>
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="home-section market-section">
    <h2 class="section-title">Fresh from the fertilizer market</h2>

    <div class="grid">
        <!-- Product cards will be added in Part 4 -->
    </div>
</section>

<!-- Composting Tips -->
<section class="home-section tips-section">
    <h2 class="section-title">Simple composting tips</h2>

    <div class="grid">
        <div class="card">
            <h3>Balance materials</h3>
            <p>Mix dry leaves and crop residues with green materials like vegetable scraps.</p>
        </div>

        <div class="card">
            <h3>Keep it moist</h3>
            <p>Maintain moisture similar to a wrung-out sponge and avoid waterlogging.</p>
        </div>

        <div class="card">
            <h3>Let air flow</h3>
            <p>Turn the compost regularly to support airflow and healthy decomposition.</p>
        </div>
    </div>
</section>


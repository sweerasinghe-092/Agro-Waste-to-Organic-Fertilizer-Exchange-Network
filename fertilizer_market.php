<?php
// Fertilizer market page

// Connect to the database
require_once 'config/db.php'; 
// Include common functions
require_once 'includes/functions.php'; 


// Get filter values from URL
// Get fertilizer type
$type = trim($_GET['type'] ?? ''); 
// Get search value
$search = trim($_GET['q'] ?? '');

// Get only active products with stock
$sql = "SELECT p.*, u.full_name, d.district_name FROM fertilizer_products p JOIN users u ON p.producer_id = u.user_id LEFT JOIN districts d ON p.district_id = d.district_id WHERE p.status = 'active' AND p.stock_quantity > 0";
// Store filter values
$params = []; 
// Store parameter data types
$types = ''; 

// Filter by fertilizer type
if ($type !== '') {
	// Add fertilizer type condition
    $sql .= " AND p.fertilizer_type = ?"; 
	// Store fertilizer type
    $params[] = $type; 
	// Set data type as string
    $types .= 's'; 
}

// Filter by product name
if ($search !== '') {
	 // Add product search condition
    $sql .= " AND p.product_name LIKE ?";
	// Store search value
    $params[] = "%$search%"; 
	// Set data type as string
    $types .= 's'; 
}

// Show newest products first
$sql .= " ORDER BY p.created_at DESC"; 
 // Prepare SQL query
$st = $conn->prepare($sql);
// Bind filter values
if ($params) $st->bind_param($types, ...$params); 
// Execute SQL query
$st->execute();
// Get query results 
$products = $st->get_result(); 
 // Set page title
$pageTitle = "Fertilizer Market";
// Include header
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

        <div class="market-filter-action">
            <button type="submit">Filter products</button>
        </div>
    </form>

    <!-- Display product list -->
    <div class="market-results">
		<!-- Check whether products exist -->
        <?php if ($products->num_rows): ?> 
            <div class="market-results-heading">
                <h2>Available products</h2>
				<!-- Display product count -->
                <span><?= number_format($products->num_rows) ?> products</span> 
            </div>

            <div class="grid market-product-grid">
				<!-- Fetch products one by one -->
                <?php while ($p = $products->fetch_assoc()): ?> 
                    <article class="card market-product-card">
						<!-- Display fertilizer type -->
                        <span class="pill"><?= e($p['fertilizer_type']) ?></span> 
						 <!-- Display product name -->
                        <h3><?= e($p['product_name']) ?></h3>
						 <!-- Display description -->
                        <p><?= e($p['description']) ?></p>

                        <div class="product-meta">
							<!-- Display source materials -->
                            <p><strong>Source:</strong> <?= e($p['source_materials']) ?></p> 
							<!-- Display usage instructions -->
                            <p><strong>Usage:</strong> <?= e($p['usage_instructions']) ?></p> 
							 <!-- Display producer name -->
                            <p><strong>Producer:</strong> <?= e($p['full_name']) ?></p>
							<!-- Display district -->
                            <p><strong>District:</strong> <?= e($p['district_name'] ?? 'Not specified') ?></p> 
                        </div>
						
						<!-- Display price -->
                        <p class="price">Rs. <?= number_format((float)$p['price'], 2) ?> <span>/ <?= e($p['unit']) ?></span></p> 
						<!-- Display stock -->
                        <p class="stock-note">Available stock: <?= e($p['stock_quantity']) ?> <?= e($p['unit']) ?></p> 

                        <!-- Show add to cart only for buyers -->
                        <?php if (($_SESSION['role'] ?? '') === 'buyer'): ?>
							<!-- Send data to cart.php -->
                            <form method="post" action="cart.php" class="market-cart-form"> 
								<!-- Store product ID -->
                                <input type="hidden" name="add_product" value="<?= $p['product_id'] ?>"> 
                                <label for="quantity-<?= $p['product_id'] ?>">Quantity</label>
                                <div class="market-cart-controls">
									<!-- Enter quantity -->
                                    <input id="quantity-<?= $p['product_id'] ?>" type="number" name="quantity" min="1" max="<?= $p['stock_quantity'] ?>" value="1" required> 
									<!-- Submit cart form -->
                                    <button type="submit">Add to cart</button> 
                                </div>
                            </form>
                        <?php elseif (empty($_SESSION['user_id'])): ?>
							<!-- Redirect guest to login -->
                            <a class="btn" href="login.php">Login to buy</a> 
                        <?php endif; ?>
                    </article>
				<!-- End product loop -->
                <?php endwhile; ?> 
            </div>
		 <!-- No products found -->
        <?php else: ?>
           
            <div class="market-empty">
                <h2>No fertilizer products found</h2>
                <p>There are currently no products matching your search. Try another product name or choose “All types”.</p>
            </div>
		<!-- End product availability condition -->
        <?php endif; ?> 
    </div>
</section>

<!-- Include footer -->
<?php include 'includes/footer.php'; ?> 
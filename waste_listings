<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Read the selected filters and keep them selected after submitting the form.
$search = trim($_GET['q'] ?? '');
$cat = (int)($_GET['category_id'] ?? 0);
$district = (int)($_GET['district_id'] ?? 0);

// Build one prepared query so user-entered search terms remain safe.
$sql = "SELECT w.*, c.category_name, d.district_name, u.full_name
        FROM waste_listings w
        JOIN waste_categories c ON w.category_id = c.category_id
        LEFT JOIN districts d ON w.district_id = d.district_id
        JOIN users u ON w.provider_id = u.user_id
        WHERE w.status = 'available'";
$params = [];
$types = '';
if ($search !== '') {
    $sql .= " AND (w.title LIKE ? OR w.description LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($cat > 0) {
    $sql .= " AND w.category_id = ?";
    $params[] = $cat;
    $types .= 'i';
}
if ($district > 0) {
    $sql .= " AND w.district_id = ?";
    $params[] = $district;
    $types .= 'i';
}
$sql .= " ORDER BY w.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$listings = $stmt->get_result();

$pageTitle = 'Find Waste';
include 'includes/header.php';
?>
<section class="market-page">
  <div class="market-heading">
    <p class="market-eyebrow">AGRO EXCHANGE MARKETPLACE</p>
    <h1 class="section-title">Find Agricultural Waste</h1>
    <p class="market-intro">Explore available agricultural by-products from local providers and give useful materials a second life.</p>
  </div>

  <form method="get" class="market-filter waste-filter panel">
    <div class="market-field">
      <label for="waste-search">Search waste</label>
      <input id="waste-search" type="search" name="q" value="<?=e($search)?>" placeholder="Enter a waste name or keyword">
    </div>
    <div class="market-field">
      <label for="waste-category">Waste category</label>
      <select id="waste-category" name="category_id">
        <option value="0">All categories</option>
        <?php $categories = $conn->query("SELECT category_id, category_name FROM waste_categories ORDER BY category_name"); ?>
        <?php while ($category = $categories->fetch_assoc()): ?>
          <option value="<?=$category['category_id']?>" <?=$cat === (int)$category['category_id'] ? 'selected' : ''?>><?=e($category['category_name'])?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="market-field">
      <label for="waste-district">District</label>
      <select id="waste-district" name="district_id">
        <option value="0">All districts</option>
        <?php $districts = districts($conn); ?>
        <?php while ($districtRow = $districts->fetch_assoc()): ?>
          <option value="<?=$districtRow['district_id']?>" <?=$district === (int)$districtRow['district_id'] ? 'selected' : ''?>><?=e($districtRow['district_name'])?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="market-filter-action"><button type="submit">Find waste</button></div>
  </form>

  <div class="market-results">
    <?php if ($listings->num_rows): ?>
      <div class="market-results-heading">
        <h2>Available waste listings</h2>
        <span><?=number_format($listings->num_rows)?> listings</span>
      </div>
      <div class="grid market-product-grid waste-listing-grid">
        <?php while ($waste = $listings->fetch_assoc()): ?>
          <article class="card market-product-card waste-listing-card">
            <span class="pill"><?=e($waste['category_name'])?></span>
            <h3><?=e($waste['title'])?></h3>
            <p><?=e($waste['description'])?></p>
            <div class="product-meta">
              <p><strong>Provider:</strong> <?=e($waste['full_name'])?></p>
              <p><strong>Location:</strong> <?=e($waste['district_name'] ?? 'Not specified')?></p>
              <p><strong>Quantity:</strong> <?=e($waste['quantity_available'])?> <?=e($waste['unit'])?></p>
            </div>
            <p class="price"><?=$waste['price_per_unit'] == 0 ? 'Free' : 'Rs. ' . number_format((float)$waste['price_per_unit'], 2)?> <span><?=$waste['price_per_unit'] == 0 ? '' : '/ ' . e($waste['unit'])?></span></p>
            <a class="btn" href="waste_details.php?id=<?=$waste['listing_id']?>">View details</a>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="market-empty">
        <h2>No waste listings found</h2>
        <p>There are currently no listings matching your search. Try another keyword, category or district.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php include 'includes/footer.php'; ?>

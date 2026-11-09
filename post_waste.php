<?php
// Post / Edit waste listing - only provider can access
require_once 'config/db.php';
require_once 'includes/functions.php';
require_role('provider');

$userId = (int)$_SESSION['user_id'];
$editId = (int)($_GET['edit'] ?? 0);
$error = '';
$success = '';
$existing = null;

// If editing, get old listing data
if ($editId) {
    $st = $conn->prepare("SELECT * FROM waste_listings WHERE listing_id = ? AND provider_id = ?");
    $st->bind_param("ii", $editId, $userId);
    $st->execute();
    $existing = $st->get_result()->fetch_assoc();
    if (!$existing) exit("Listing not found or access denied.");
}

// When form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['listing_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $category = (int)($_POST['category_id'] ?? 0);
    $district = (int)($_POST['district_id'] ?? 0);
    $qty = (float)($_POST['quantity_available'] ?? 0);
    $unit = $_POST['unit'] ?? 'kg';
    $price = (float)($_POST['price_per_unit'] ?? 0);
    $from = $_POST['available_from'] ?? null;
    $until = $_POST['available_until'] ?? null;
    $location = trim($_POST['pickup_location'] ?? '');

    // Check inputs
    if ($title === '' || $category < 1 || $district < 1 || $qty <= 0 || $price < 0 || !in_array($unit, ['kg', 'tons', 'bags'], true)) {
        $error = "Please enter valid listing details.";
    } else {
        $image = $existing['image'] ?? '';

        // Upload new image if selected
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = mime_content_type($_FILES['image']['tmp_name']);

            if (!isset($allowed[$mime]) || $_FILES['image']['size'] > 2 * 1024 * 1024) {
                $error = "Image must be JPG, PNG or WEBP and under 2 MB.";
            } else {
                $folder = 'uploads/waste/';
                if (!is_dir($folder)) mkdir($folder, 0775, true);
                $image = uniqid('waste_', true) . '.' . $allowed[$mime];
                move_uploaded_file($_FILES['image']['tmp_name'], $folder . $image);
                $image = $folder . $image;
            }
        }

        if (!$error) {
            // Update old listing
            if ($id) {
                $st = $conn->prepare("UPDATE waste_listings SET category_id = ?, district_id = ?, title = ?, description = ?, quantity_available = ?, unit = ?, price_per_unit = ?, available_from = NULLIF(?, ''), available_until = NULLIF(?, ''), pickup_location = ?, image = ? WHERE listing_id = ? AND provider_id = ?");
                $st->bind_param("iissdsdssssii", $category, $district, $title, $desc, $qty, $unit, $price, $from, $until, $location, $image, $id, $userId);
                $st->execute();
            }
            // Add new listing
            else {
                $st = $conn->prepare("INSERT INTO waste_listings (provider_id, category_id, district_id, title, description, quantity_available, unit, price_per_unit, available_from, available_until, pickup_location, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?)");
                $st->bind_param("iiissdsdssss", $userId, $category, $district, $title, $desc, $qty, $unit, $price, $from, $until, $location, $image);
                $st->execute();
            }
            header("Location: dashboard.php?posted=1");
            exit;
        }
    }
}

// Delete (remove) listing
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    $st = $conn->prepare("UPDATE waste_listings SET status = 'removed' WHERE listing_id = ? AND provider_id = ?");
    $st->bind_param("ii", $del, $userId);
    $st->execute();
    header("Location: dashboard.php");
    exit;
}

$pageTitle = "Post Waste";
include 'includes/header.php';
?>

<div class="form-box">
    <h2 class="section-title"><?= $existing ? 'Edit' : 'Post' ?> Waste Listing</h2>

    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>

    <form method="post" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="listing_id" value="<?= e($existing['listing_id'] ?? 0) ?>">

        <label>Listing title</label>
        <input name="title" required value="<?= e($existing['title'] ?? '') ?>">

        <label>Category</label>
        <select name="category_id" required>
            <option value="">Select category</option>
            <?php $cs = $conn->query("SELECT * FROM waste_categories"); while ($c = $cs->fetch_assoc()): ?>
                <option value="<?= $c['category_id'] ?>" <?= ($existing['category_id'] ?? 0) == $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Description</label>
        <textarea name="description"><?= e($existing['description'] ?? '') ?></textarea>

        <label>Quantity</label>
        <input type="number" name="quantity_available" min="0.01" step="0.01" required value="<?= e($existing['quantity_available'] ?? '') ?>">

        <label>Unit</label>
        <select name="unit"><option>kg</option><option>tons</option><option>bags</option></select>

        <label>Price per unit (0 = free)</label>
        <input type="number" name="price_per_unit" min="0" step="0.01" value="<?= e($existing['price_per_unit'] ?? 0) ?>">

        <label>District</label>
        <select name="district_id" required>
            <option value="">Select district</option>
            <?php $ds = districts($conn); while ($d = $ds->fetch_assoc()): ?>
                <option value="<?= $d['district_id'] ?>" <?= ($existing['district_id'] ?? 0) == $d['district_id'] ? 'selected' : '' ?>><?= e($d['district_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Pickup location</label>
        <input name="pickup_location" value="<?= e($existing['pickup_location'] ?? '') ?>">

        <label>Available from</label>
        <input type="date" name="available_from" value="<?= e($existing['available_from'] ?? '') ?>">

        <label>Available until</label>
        <input type="date" name="available_until" value="<?= e($existing['available_until'] ?? '') ?>">

        <label>Photo (JPG/PNG/WEBP, max 2MB)</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">

        <button>Save Listing</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
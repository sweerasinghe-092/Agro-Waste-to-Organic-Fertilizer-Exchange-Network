<?php
require_once 'config/db.php'; require_once 'includes/functions.php';

// Get waste id from URL
$id = (int)($_GET['id'] ?? 0);

// Get listing + provider contact details
$stmt = $conn->prepare("SELECT w.*, c.category_name, d.district_name, u.full_name, u.phone, u.email FROM waste_listings w JOIN waste_categories c ON w.category_id = c.category_id LEFT JOIN districts d ON w.district_id = d.district_id JOIN users u ON w.provider_id = u.user_id WHERE w.listing_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$w = $stmt->get_result()->fetch_assoc();
if (!$w) { http_response_code(404); exit("Waste listing not found."); }

// Check if user is logged in
$isLoggedIn = !empty($_SESSION['user_id']);
$isOwner = $isLoggedIn && (int)$_SESSION['user_id'] === (int)$w['provider_id'];
// Only logged in producer / buyer can see contact
$canSeeContact = $isLoggedIn && !$isOwner && in_array($_SESSION['role'] ?? '', ['producer', 'buyer'], true);

// Available dates - from database
$from = !empty($w['available_from']) ? date('M j, Y', strtotime($w['available_from'])) : '';
$until = !empty($w['available_until']) ? date('M j, Y', strtotime($w['available_until'])) : '';
$daysText = '';
if (!empty($w['available_from']) && !empty($w['available_until'])) {
    $days = (int)((strtotime($w['available_until']) - strtotime($w['available_from'])) / 86400) + 1;
    if ($days > 0) $daysText = " ($days days available)";
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('producer');
    $qty = (float)($_POST['quantity_requested'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $date = $_POST['pickup_date'] ?? '';
    if (!empty($w['available_from']) && $date < $w['available_from']) $msg = "Pickup date must be on or after " . $from . ".";
    elseif (!empty($w['available_until']) && $date > $w['available_until']) $msg = "Pickup date must be on or before " . $until . ".";
    elseif ($qty <= 0 || $qty > (float)$w['quantity_available']) $msg = "Enter a valid quantity not exceeding the available amount.";
    else {
        $st = $conn->prepare("INSERT INTO exchange_requests(listing_id, producer_id, quantity_requested, message, pickup_date) VALUES(?,?,?,?,?)");
        $st->bind_param("iidss", $id, $_SESSION['user_id'], $qty, $message, $date);
        $st->execute();
        notify_user($conn, (int)$w['provider_id'], 'exchange_request', 'A producer sent a request for ' . $w['title'] . '.', 'dashboard.php');
        $msg = "Your exchange request has been sent.";
    }
}
$pageTitle = "Waste Details";
include 'includes/header.php';
?>
<div class="panel">
<h2><?= e($w['title']) ?></h2>
<p class="pill"><?= e($w['category_name']) ?></p>
<p><?= e($w['description']) ?></p>
<p><b>Available quantity:</b> <?= e($w['quantity_available']) ?> <?= e($w['unit']) ?></p>
<p><b>Price:</b> <?= $w['price_per_unit'] == 0 ? 'Free' : 'Rs. ' . number_format($w['price_per_unit'], 2) . ' per ' . e($w['unit']) ?></p>

<!-- NEW in Commit 2 - Available dates -->
<?php if ($from || $until): ?>
<p><b>Available:</b> <?= e($from ?: 'Not specified') ?> <?= ($from && $until) ? ' - ' . e($until) : '' ?><?= e($daysText) ?></p>
<?php else: ?>
<p><b>Available:</b> Not specified</p>
<?php endif; ?>

<p><b>District:</b> <?= e($w['district_name'] ?? 'Not specified') ?></p>
<p><b>Pickup location:</b> <?= e($w['pickup_location']) ?></p>
<p><b>Provider:</b> <?= e($w['full_name']) ?></p>

<?php if ($canSeeContact): ?>
<div class="contact-box" style="background:#eef7f0; border:1px solid #cfe8d8; padding:14px; border-radius:10px; margin:16px 0;">
<h3 style="margin:0 0 8px;">Contact provider</h3>
<p style="margin:4px 0;"><b>Phone:</b> <a href="tel:<?= e($w['phone']) ?>"><?= e($w['phone']) ?></a></p>
<p style="margin:4px 0;"><b>Email:</b> <a href="mailto:<?= e($w['email']) ?>"><?= e($w['email']) ?></a></p>
<small>Please contact only about this waste listing.</small>
</div>
<?php elseif (!$isLoggedIn): ?>
<p><a href="login.php">Login to see provider contact details</a> and request this waste.</p>
<?php endif; ?>

<?php if ($msg): ?><p class="message"><?= e($msg) ?></p><?php endif; ?>

<?php if (($_SESSION['role'] ?? '') === 'producer'): ?>
<h3>Send Exchange Request</h3>
<form method="post" data-validate>
<label>Quantity required</label>
<input type="number" name="quantity_requested" min="0.01" max="<?= e($w['quantity_available']) ?>" step="0.01" required>
<label>Pickup date</label>
<input type="date" name="pickup_date" <?= !empty($w['available_from']) ? 'min="' . e($w['available_from']) . '"' : '' ?> <?= !empty($w['available_until']) ? 'max="' . e($w['available_until']) . '"' : '' ?> required>
<?php if ($from || $until): ?><small>Available from <?= e($from) ?> to <?= e($until) ?></small><?php endif; ?>
<label>Message</label>
<textarea name="message"></textarea>
<button>Send Request</button>
</form>
<?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
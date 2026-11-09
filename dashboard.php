<?php
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

?>


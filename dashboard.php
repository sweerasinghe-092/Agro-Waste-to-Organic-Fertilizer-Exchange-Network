<?php
// Stats + intro text for each role
$stats = [];
if ($role === 'provider') {
    $stats = [
        ['My listings', countFor($conn, 'SELECT COUNT(*) n FROM waste_listings WHERE provider_id = ?', $uid), 'listings'],
        ['Available', countFor($conn, "SELECT COUNT(*) n FROM waste_listings WHERE provider_id = ? AND status = 'available'", $uid), 'listings'],
        ['Requests', countFor($conn, 'SELECT COUNT(*) n FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id WHERE w.provider_id = ?', $uid), 'requests'],
        ['Pending', countFor($conn, "SELECT COUNT(*) n FROM exchange_requests r JOIN waste_listings w ON r.listing_id = w.listing_id WHERE w.provider_id = ? AND r.status = 'pending'", $uid), 'requests']
    ];
    $intro = 'Manage your agricultural waste, respond to producers and keep useful materials in circulation.';
} elseif ($role === 'producer') {
    $stats = [
        ['My products', countFor($conn, 'SELECT COUNT(*) n FROM fertilizer_products WHERE producer_id = ?', $uid), 'products'],
        ['Stock units', countFor($conn, 'SELECT COALESCE(SUM(stock_quantity), 0) n FROM fertilizer_products WHERE producer_id = ?', $uid), 'products'],
        ['Waste requests', countFor($conn, 'SELECT COUNT(*) n FROM exchange_requests WHERE producer_id = ?', $uid), 'requests'],
        ['Orders received', countFor($conn, 'SELECT COUNT(*) n FROM order_items oi JOIN fertilizer_products p ON oi.product_id = p.product_id JOIN orders o ON oi.order_id = o.order_id WHERE p.producer_id = ?', $uid), 'orders']
    ];
    $intro = 'Find raw materials, publish your organic fertilizer and manage customer orders.';
} else {
    $stats = [
        ['My orders', countFor($conn, 'SELECT COUNT(*) n FROM orders WHERE buyer_id = ?', $uid), 'orders'],
        ['Pending orders', countFor($conn, "SELECT COUNT(*) n FROM orders WHERE buyer_id = ? AND order_status = 'pending'", $uid), 'orders'],
        ['Available products', (int)$conn->query("SELECT COUNT(*) n FROM fertilizer_products WHERE status = 'active'")->fetch_assoc()['n'], 'market'],
        ['Available waste', (int)$conn->query("SELECT COUNT(*) n FROM waste_listings WHERE status = 'available'")->fetch_assoc()['n'], 'waste']
    ];
    $intro = 'Discover locally made organic fertilizer and follow your purchases in one place.';
}

$pageTitle = 'My Dashboard';
?>
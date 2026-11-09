<?php
// Dashboard - for provider, producer and buyer
require_once 'config/db.php';
require_once 'includes/functions.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'buyer';

// Admin goes to admin page
if ($role === 'admin') {
    header('Location: admin.php');
    exit;
}

// Block unknown roles
if (!in_array($role, ['provider', 'producer', 'buyer'], true)) {
    http_response_code(403);
    exit('Access denied.');
}

// Get my details
$meStmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
$meStmt->bind_param('i', $uid);
$meStmt->execute();
$me = $meStmt->get_result()->fetch_assoc();

// If account is not active, logout
if (!$me || $me['status'] !== 'active') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Which page to show
$view = $_GET['view'] ?? 'overview';

// Menu for each role
$menus = [
    'provider' => ['overview' => 'Overview', 'listings' => 'My waste listings', 'requests' => 'Incoming requests', 'profile' => 'My profile', 'notifications' => 'Notifications'],
    'producer' => ['overview' => 'Overview', 'products' => 'My fertilizer products', 'requests' => 'Waste requests', 'orders' => 'Orders received', 'profile' => 'My profile', 'notifications' => 'Notifications'],
    'buyer'    => ['overview' => 'Overview', 'orders' => 'My orders', 'cart' => 'My cart', 'profile' => 'My profile', 'notifications' => 'Notifications']
];
if (!isset($menus[$role][$view])) $view = 'overview';

$notice = '';
?>
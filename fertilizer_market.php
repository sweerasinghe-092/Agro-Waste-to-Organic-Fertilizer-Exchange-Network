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

//updated
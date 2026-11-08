<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = $pageTitle ?? "Agro Exchange";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | Agro Exchange</title>
<link rel="preconnect" href="https://images.unsplash.com">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
  <div class="header-left">
    <a class="brand" href="index.php"><img src="assets/agro-exchange-logo.svg" alt="" width="42" height="42"><span>Agro Exchange</span></a>
  </div>
  <nav>
    <a href="index.php">Home</a>
    <a href="waste_listings.php">Find Waste</a>
    <a href="fertilizer_market.php">Fertilizer Market</a>
    <?php if (!empty($_SESSION['user_id'])): ?>
      <a href="<?= (($_SESSION['role'] ?? '') === 'admin') ? 'admin.php' : 'dashboard.php' ?>">Dashboard</a>
      <a class="nav-cta" href="logout.php">Logout</a>
    <?php else: ?>
      <a href="register.php">Register</a>
      <a class="nav-cta" href="login.php">Login</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
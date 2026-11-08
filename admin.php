<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | Agro Exchange</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="dash-page admin-theme">
    <div class="dash-layout">
        <aside class="dash-sidebar">
            <a class="dash-brand" href="admin.php">
                <img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange
            </a>
            <div class="dash-profile">
                <div class="avatar">A</div>
                <div><strong>Administrator</strong><small>System administrator</small></div>
            </div>
            <div class="nav-caption">ADMINISTRATION</div>
            <nav class="dash-nav">
                <a class="active" href="admin.php?view=overview">Overview</a>
                <a href="admin.php?view=users">User management</a>
                <a href="admin.php?view=waste">Waste moderation</a>
                <a href="admin.php?view=products">Products</a>
                <a href="admin.php?view=orders">Orders</a>
                <a href="admin.php?view=categories">Categories</a>
                <a href="admin.php?view=notifications">Notifications</a>
            </nav>
            <div class="sidebar-bottom">
                <a href="index.php">Visit public home</a>
                <a class="logout-link" href="logout.php">Log out</a>
            </div>
        </aside>
        <main class="dash-content">
            <header class="dash-topbar">
                <div><p class="breadcrumb">Agro Exchange / Administration</p><h1>Overview</h1></div>
                <span class="admin-pill">ADMINISTRATOR</span>
            </header>
            <section class="welcome-banner">
                <h2>Welcome to the admin workspace.</h2>
                <p>Monitor the Agro Exchange community, review content and keep the platform running smoothly.</p>
            </section>
        </main>
    </div>
</body>
</html>
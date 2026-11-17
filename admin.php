if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['user_action'])){
        $id=(int)$_POST['user_id']; $action=$_POST['user_action'];
        if($id!==$uid && in_array($action,['active','suspended'],true)){
            $st=$conn->prepare("UPDATE users SET status=? WHERE user_id=? AND role<>'admin'");
            $st->bind_param('si',$action,$id); $st->execute();
            if($st->affected_rows>0){
                $msg=$action==='active'?'Your Agro Exchange account has been approved. You can now log in.':'Your Agro Exchange account has been suspended. Please contact the administrator.';
                notify_user($conn,$id,'account_status',$msg,'login.php');
            }
            $notice='User account status updated.';
        }
    }
    if(isset($_POST['remove_listing'])){
        $id=(int)$_POST['listing_id'];
        $st=$conn->prepare("UPDATE waste_listings SET status='removed' WHERE listing_id=?");
        $st->bind_param('i',$id); $st->execute(); $notice='Listing removed from public results.';
    }
}<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_role('admin');

$uid = (int)$_SESSION['user_id'];
$meSt = $conn->prepare('SELECT * FROM users WHERE user_id=?');
$meSt->bind_param('i', $uid);
$meSt->execute();
$me = $meSt->get_result()->fetch_assoc();

$view = $_GET['view'] ?? 'overview';
$allowed = ['overview','users','waste','products','orders','categories','notifications'];
if (!in_array($view, $allowed, true)) $view = 'overview';

function adminCount($conn, $sql){ return (int)$conn->query($sql)->fetch_assoc()['n']; }
$stats = [
    ['Registered users', adminCount($conn,'SELECT COUNT(*) n FROM users'),''],
    ['Pending approvals', adminCount($conn,"SELECT COUNT(*) n FROM users WHERE status='pending' AND role<>'admin'"),''],
    ['Available waste', adminCount($conn,"SELECT COUNT(*) n FROM waste_listings WHERE status='available'"),''],
    ['Fertilizer products', adminCount($conn,"SELECT COUNT(*) n FROM fertilizer_products WHERE status='active'"),''],
    ['Customer orders', adminCount($conn,'SELECT COUNT(*) n FROM orders'),''],
    ['Exchange requests', adminCount($conn,'SELECT COUNT(*) n FROM exchange_requests'),''],
];
$menus = ['overview'=>'Overview','users'=>'User management','waste'=>'Waste moderation','products'=>'Products','orders'=>'Orders','categories'=>'Categories','notifications'=>'Notifications'];
$adminUnread = adminCount($conn,"SELECT COUNT(*) n FROM notifications WHERE user_id={$uid} AND is_read=0");
$notice = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Agro Exchange</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="dash-page admin-theme">
<div class="dash-layout">
    <aside class="dash-sidebar">
        <a class="dash-brand" href="admin.php"><img src="assets/agro-exchange-logo.svg" alt="">Agro Exchange</a>
        <div class="dash-profile"><div class="avatar">A</div><div><strong><?= e($me['full_name'] ?? 'Administrator') ?></strong><small>System administrator</small></div></div>
        <nav class="dash-nav"><?php foreach($menus as $key=>$label): ?><a class="<?= $view===$key?'active':'' ?>" href="admin.php?view=<?= $key ?>"><?= e($label) ?><?php if($key==='notifications' && $adminUnread>0): ?><small><?= $adminUnread ?></small><?php endif; ?></a><?php endforeach; ?></nav>
        <div class="sidebar-bottom"><a href="logout.php">Log out</a></div>
    </aside>
    <main class="dash-content">
        <header class="dash-topbar"><h1><?= e($menus[$view]) ?></h1></header>
        <?php if($view==='overview'): ?>
        <section class="stats-grid"><?php foreach($stats as $s): ?><div class="metric-card"><span><?= e($s[0]) ?></span><strong><?= number_format($s[1]) ?></strong></div><?php endforeach; ?></section>
        <div class="action-grid">
            <a href="admin.php?view=users"><b>Manage users</b><small>Activate or suspend accounts</small></a>
            <a href="admin.php?view=waste"><b>Moderate listings</b><small>Review community waste posts</small></a>
            <a href="admin.php?view=categories"><b>Manage categories</b><small>Maintain waste categories</small></a>
            <a href="admin.php?view=notifications"><b>View notifications</b><small>Check new registrations</small></a>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
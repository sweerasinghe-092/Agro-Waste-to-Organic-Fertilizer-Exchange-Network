<?php
require_once 'config/db.php'; require_once 'includes/functions.php';
$id=(int)($_GET['id']??0); $stmt=$conn->prepare("SELECT w.*,c.category_name,d.district_name,u.full_name,u.phone FROM waste_listings w JOIN waste_categories c ON w.category_id=c.category_id LEFT JOIN districts d ON w.district_id=d.district_id JOIN users u ON w.provider_id=u.user_id WHERE w.listing_id=?");
$stmt->bind_param("i",$id);$stmt->execute();$w=$stmt->get_result()->fetch_assoc();
if(!$w){http_response_code(404);exit("Waste listing not found.");}
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 require_role('producer');
 $qty=(float)($_POST['quantity_requested']??0);$message=trim($_POST['message']??'');$date=$_POST['pickup_date']??'';
 if($qty<=0 || $qty>(float)$w['quantity_available'])$msg="Enter a valid quantity not exceeding the available amount.";
 else{
  $st=$conn->prepare("INSERT INTO exchange_requests(listing_id,producer_id,quantity_requested,message,pickup_date) VALUES(?,?,?,?,?)");
  $st->bind_param("iidss",$id,$_SESSION['user_id'],$qty,$message,$date);$st->execute();
  notify_user($conn,(int)$w['provider_id'],'exchange_request','A producer sent a request for '.$w['title'].'.','dashboard.php');
  $msg="Your exchange request has been sent.";
 }
}
$pageTitle="Waste Details";include 'includes/header.php';
?>
<div class="panel"><h2><?=e($w['title'])?></h2><p class="pill"><?=e($w['category_name'])?></p><p><?=e($w['description'])?></p>
<p><b>Available quantity:</b> <?=e($w['quantity_available'])?> <?=e($w['unit'])?></p><p><b>Price:</b> <?=$w['price_per_unit']==0?'Free':'Rs. '.number_format($w['price_per_unit'],2).' per '.e($w['unit'])?></p>
<p><b>District:</b> <?=e($w['district_name']??'Not specified')?></p><p><b>Pickup location:</b> <?=e($w['pickup_location'])?></p><p><b>Provider:</b> <?=e($w['full_name'])?></p>
<?php if($msg):?><p class="message"><?=e($msg)?></p><?php endif;?>
<?php if(($_SESSION['role']??'')==='producer'):?>
<h3>Send Exchange Request</h3><form method="post" data-validate><label>Quantity required</label><input type="number" name="quantity_requested" min="0.01" max="<?=e($w['quantity_available'])?>" step="0.01" required><label>Pickup date</label><input type="date" name="pickup_date" required><label>Message</label><textarea name="message"></textarea><button>Send Request</button></form>
<?php elseif(empty($_SESSION['user_id'])):?><p><a href="login.php">Login as a producer</a> to request this waste.</p><?php endif;?></div>
<?php include 'includes/footer.php'; ?>

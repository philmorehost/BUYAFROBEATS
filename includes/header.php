<?php
require_once __DIR__ . '/Core.php';
require_once __DIR__ . '/Auction.php';

use BAF\Core;

$core = Core::get_instance();
$site_title = str_replace(' ', '', $core->setting('site_title', 'BUYAFROBEATS'));
$title = $site_title . " — exclusive beat auctions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <link rel="icon" type="image/svg+xml" href="<?php echo $core->render_favicon(); ?>">
    <?php echo $core->render_seo($page_seo ?? []); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <?php echo $core->render_head_injection(); ?>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ live auctions</span></a>
        <div class="tabs">
            <a href="index" class="tab is-active">Auctions</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin/index" class="tab admin">Enter Studio</a>
            <?php else: ?>
                <a href="register" class="tab">Signup</a>
                <a href="login" class="tab">Login</a>
            <?php endif; ?>
        </div>
        <div class="spacer"></div>
        <div id="top-counter" class="counter">
            <span class="live-dot"></span> <b>0</b> live · <b>0</b> total bids
        </div>
    </div>
</div>

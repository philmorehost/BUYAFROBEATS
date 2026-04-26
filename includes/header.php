<?php
require_once __DIR__ . '/Core.php';
require_once __DIR__ . '/Auction.php';

use BAF\Core;

$core = Core::get_instance();
$site_title = $core->setting('site_title', 'BUYAFROBEATS');
$title = $site_title . " — exclusive beat auctions";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title><?php echo $title; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ live auctions</span></a>
        <div class="tabs">
            <a href="index.php" class="tab is-active">Auctions</a>
            <?php if ($core->is_admin()): ?>
                <a href="admin/index.php" class="tab admin">My Studio</a>
            <?php endif; ?>
        </div>
        <div class="spacer"></div>
        <div id="top-counter" class="counter">
            <span class="live-dot"></span> <b>0</b> live · <b>0</b> total bids
        </div>
    </div>
</div>

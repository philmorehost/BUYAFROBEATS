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
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <?php echo $core->render_head_injection(); ?>
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <a href="index" class="logo">
            <span class="dot"></span>
            <div class="logo-text"><?php echo $core->setting('site_title', 'BEATZAZA'); ?><span>.COM</span></div>
        </a>
        
        <nav class="nav-links">
            <a href="index">Auctions</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin/index" style="color: var(--accent);">Studio</a>
            <?php else: ?>
                <a href="login">Sign In</a>
            <?php endif; ?>
        </nav>

        <div style="flex: 1;"></div>

        <div id="top-counter" class="mono" style="font-size: 11px; color: var(--ink-mute);">
            <span style="color: var(--ok);">●</span> Live
        </div>
    </div>
</header>

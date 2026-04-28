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
    <?php
    $first_letter = strtoupper($site_title[0] ?? 'B');
    $favicon_svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='#ffa326'/><text x='50%' y='54%' dominant-baseline='central' text-anchor='middle' font-family='Space Grotesk, sans-serif' font-weight='700' font-size='60' fill='#1a1815'>{$first_letter}</text></svg>";
    ?>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php echo rawurlencode($favicon_svg); ?>">
    <?php echo $core->render_seo($page_seo ?? []); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php echo $core->render_head_injection(); ?>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ live auctions</span></a>
        <div class="tabs">
            <a href="index.php" class="tab is-active">Auctions</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="admin/index.php" class="tab admin">Enter Studio</a>
            <?php else: ?>
                <a href="register.php" class="tab">Signup</a>
                <a href="login.php" class="tab">Login</a>
            <?php endif; ?>
        </div>
        <div class="spacer"></div>
        <div id="top-counter" class="counter">
            <span class="live-dot"></span> <b>0</b> live · <b>0</b> total bids
        </div>
    </div>
</div>
<div class="share-sidebar">
    <?php 
    $current_url = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $share_title = urlencode($site_title . " — Exclusive Beat Auctions");
    ?>
    <a href="https://twitter.com/intent/tweet?text=<?php echo $share_title; ?>&url=<?php echo $current_url; ?>" target="_blank" title="Share on Twitter" class="share-btn twitter">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.84 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
    </a>
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>" target="_blank" title="Share on Facebook" class="share-btn facebook">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
    </a>
    <a href="https://api.whatsapp.com/send?text=<?php echo $share_title . " " . $current_url; ?>" target="_blank" title="Share on WhatsApp" class="share-btn whatsapp">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17.472 14.382c-.301-.15-1.767-.872-2.04-.971-.272-.099-.47-.15-.67.15-.199.301-.77 1.05-.94 1.25-.17.199-.34.22-.64.07-1.125-.56-2.122-1.076-2.954-1.802-.736-.639-1.22-1.428-1.36-1.67-.14-.241-.01-.371.11-.49.11-.109.241-.281.361-.421.121-.14.161-.241.241-.401.08-.16.04-.301-.02-.451-.06-.15-.47-1.13-.645-1.551-.169-.411-.339-.351-.47-.351-.129 0-.279-.011-.429-.011-.15 0-.39.06-.59.281-.2.221-.771.751-.771 1.832 0 1.08.79 2.121.9 2.271.11.15 1.551 2.372 3.75 3.321.52.221.93.351 1.25.451.52.161.99.141 1.37.081.42-.061 1.29-.531 1.47-1.04.181-.51.181-.941.131-1.04-.05-.099-.19-.15-.49-.301zM12 22.12c-1.82 0-3.6-.481-5.16-1.39l-.37-.21-3.84 1.01 1.03-3.74-.23-.37A9.851 9.851 0 011.88 12c0-5.441 4.43-9.871 9.87-9.871s9.87 4.43 9.87 9.87c0 5.441-4.43 9.871-9.87 9.871zM12 0C5.373 0 0 5.373 0 12c0 2.12.55 4.18 1.59 6L0 24l6.19-1.63A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>
    </a>
</div>

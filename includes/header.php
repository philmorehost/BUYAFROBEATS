<?php
require_once __DIR__ . '/Core.php';
require_once __DIR__ . '/Auction.php';

use BAF\Core;
$core = Core::get_instance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo $core->render_seo($page_seo ?? []); ?>
    <link rel="icon" type="image/svg+xml" href="<?php echo $core->render_favicon(); ?>">
    
    <!-- Speed: DNS Prefetch & Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://accounts.google.com">
    
    <!-- Critical Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Premium Design System -->
    <link rel="stylesheet" href="<?php echo $core->get_site_url(); ?>/assets/css/style.css?v=3.1">
    
    <!-- Google Auth -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <?php echo $core->render_head_injection(); ?>
    
    <style>
        /* Speed: Critical CSS for Top Layout */
        .topbar { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        body { visibility: hidden; opacity: 0; transition: opacity 0.3s ease; }
        body.is-ready { visibility: visible; opacity: 1; }
    </style>
</head>
<body>
    <script>document.body.classList.add('is-ready');</script>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="<?php echo $core->get_site_url(); ?>" class="logo">
                <?php echo $core->render_logo(); ?>
            </a>
            
            <nav class="tabs">
                <a href="<?php echo $core->get_site_url(); ?>" class="tab <?php echo (!isset($current_tab) || $current_tab === 'market') ? 'is-active' : ''; ?>">Auctions</a>
                <?php if ($core->is_admin()): ?>
                    <a href="<?php echo $core->get_site_url(); ?>/admin" class="tab <?php echo ($current_tab === 'studio') ? 'is-active' : ''; ?>">My Studio</a>
                    <a href="<?php echo $core->get_site_url(); ?>/admin/settings" class="tab <?php echo ($current_tab === 'settings') ? 'is-active' : ''; ?>">Settings</a>
                <?php endif; ?>
                <a href="#" class="tab" onclick="openPolicy('faq'); return false;">FAQ</a>
            </nav>

            <div class="spacer"></div>

            <div class="counter mono">
                <span class="live-dot" style="background:var(--ok)"></span>
                <b><?php echo count($core->db()->query("SELECT id FROM beats WHERE status = 'live'")->fetchAll()); ?></b> Live · <b>0</b> total bids
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-pill">
                    <span class="mono"><?php echo Core::escape($_SESSION['user_handle']); ?></span>
                    <a href="<?php echo $core->get_site_url(); ?>/api/auth/logout" class="btn btn-ghost" style="padding: 6px 12px;">Sign Out</a>
                </div>
            <?php else: ?>
                <div id="g_id_onload"
                     data-client_id="<?php echo $core->setting('google_oauth_client_id'); ?>"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleCredentialResponse"
                     data-auto_prompt="false">
                </div>
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="pill"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="medium"
                     data-logo_alignment="left">
                </div>
            <?php endif; ?>
        </div>
    </header>

<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: ../index.php');
    exit;
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Security check failed.");
    }
    
    $db = $core->db();
    $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token') continue;
        
        // Simple sanitization
        $val = trim($value);
        $stmt->execute([$key, $val, $val]);
    }

    $success = "Settings updated successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studio Settings — <?php echo Core::escape($core->setting('site_title', 'BEATZAZA')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3.0">
    <style>
        .settings-nav { display: flex; gap: 6px; margin-bottom: 24px; border-bottom: 1px solid var(--line); padding-bottom: 12px; overflow-x: auto; white-space: nowrap; }
        .settings-nav a { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-mute); text-decoration: none; padding: 8px 14px; border-radius: 8px; border: 1px solid transparent; }
        .settings-nav a.active { color: var(--accent); background: color-mix(in oklab, var(--accent) 12%, transparent); border-color: color-mix(in oklab, var(--accent) 30%, var(--line)); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
        
        .field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .help-text { font-size: 12px; color: var(--ink-mute); margin-top: 4px; }
        .help-text code { background: var(--bg-3); padding: 2px 5px; border-radius: 4px; color: var(--ink); }
    </style>
</head>
<body class="admin-page">
    <header class="topbar">
        <div class="topbar-inner">
            <a href="index.php" class="logo">
                <?php echo $core->render_logo(); ?> <span style="opacity: 0.5; margin-left: 8px; font-weight: 400;">Settings</span>
            </a>
            <nav class="tabs">
                <a href="index.php" class="tab">Overview</a>
                <a href="upload.php" class="tab">+ Upload Beat</a>
                <a href="settings.php" class="tab is-active">Settings</a>
            </nav>
            <div class="spacer"></div>
            <a href="logout.php" class="tab mono" style="font-size: 11px;">Logout</a>
        </div>
    </header>

    <main class="page">
        <div style="max-width: 900px; margin: 0 auto;">
            <div class="section-header">
                <h2>Studio Configuration</h2>
            </div>

            <?php if ($success): ?>
                <div style="background: color-mix(in oklab, var(--ok) 15%, transparent); color: var(--ok); padding: 12px 16px; border-radius: var(--radius-md); border: 1px solid var(--ok); margin-bottom: 24px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="settings-nav">
                <a href="#general" class="active">General</a>
                <a href="#auction">Auction Rules</a>
                <a href="#storage">Storage (Drive)</a>
                <a href="#payments">Payments</a>
                <a href="#auth">Auth (Google)</a>
                <a href="#seo">SEO & Analytics</a>
            </div>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

                <!-- General Tab -->
                <div id="general" class="tab-content active">
                    <div class="field">
                        <label>Site Name</label>
                        <input type="text" name="site_title" value="<?php echo Core::escape($core->setting('site_title', 'BEATZAZA')); ?>">
                        <p class="help-text">Branding displayed across the platform and emails.</p>
                    </div>
                    <div class="field">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" value="<?php echo Core::escape($core->setting('contact_email', 'hello@beatzaza.com')); ?>">
                        <p class="help-text">Used for support links and sender "Reply-To".</p>
                    </div>
                </div>

                <!-- Auction Tab -->
                <div id="auction" class="tab-content">
                    <div class="field-grid">
                        <div class="field">
                            <label>Default Duration (Min)</label>
                            <input type="number" name="auction_duration_min" value="<?php echo Core::escape($core->setting('auction_duration_min', '30')); ?>">
                        </div>
                        <div class="field">
                            <label>Anti-Snipe Window (Min)</label>
                            <input type="number" name="anti_snipe_min" value="<?php echo Core::escape($core->setting('anti_snipe_min', '2')); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label>Min Bid Increment ($)</label>
                        <input type="number" name="min_bid_increment" value="<?php echo Core::escape($core->setting('min_bid_increment', '5')); ?>">
                    </div>
                    <div class="field">
                        <label>Auction Rules Text</label>
                        <textarea name="auction_rules" style="height: 100px;"><?php echo Core::escape($core->setting('auction_rules', "Payment via Crypto only.\nExclusive 24-hour window.\nGoogle Drive delivery.")); ?></textarea>
                    </div>
                </div>

                <!-- Storage Tab -->
                <div id="storage" class="tab-content">
                    <div class="field">
                        <label>Google OAuth Client ID</label>
                        <input type="text" name="google_oauth_client_id" value="<?php echo Core::escape($core->setting('google_oauth_client_id')); ?>">
                    </div>
                    <div class="field">
                        <label>Google OAuth Client Secret</label>
                        <input type="password" name="google_oauth_client_secret" value="<?php echo Core::escape($core->setting('google_oauth_client_secret')); ?>">
                    </div>
                    <div class="field">
                        <label>Google Refresh Token</label>
                        <input type="password" name="google_refresh_token" value="<?php echo Core::escape($core->setting('google_refresh_token')); ?>">
                        <p class="help-text">Used for automated file sharing via Service Account or OAuth.</p>
                    </div>
                </div>

                <!-- Payments Tab -->
                <div id="payments" class="tab-content">
                    <div class="field">
                        <label>Plisio API Key</label>
                        <input type="password" name="plisio_api_key" value="<?php echo Core::escape($core->setting('plisio_api_key')); ?>">
                    </div>
                    <div class="field">
                        <label>Payment Window (Hours)</label>
                        <input type="number" name="payment_window_hours" value="<?php echo Core::escape($core->setting('payment_window_hours', '24')); ?>">
                        <p class="help-text">Winners must pay within this time before the cascade triggers.</p>
                    </div>
                </div>

                <!-- Auth Tab -->
                <div id="auth" class="tab-content">
                    <div class="field">
                        <label>Google Sign-In Client ID</label>
                        <input type="text" name="google_signin_client_id" value="<?php echo Core::escape($core->setting('google_signin_client_id')); ?>">
                        <p class="help-text">Required for the one-tap Google Auth on the homepage.</p>
                    </div>
                </div>

                <!-- SEO Tab -->
                <div id="seo" class="tab-content">
                    <div class="field">
                        <label>Global Meta Description</label>
                        <textarea name="global_meta_description"><?php echo Core::escape($core->setting('global_meta_description')); ?></textarea>
                    </div>
                    <div class="field">
                        <label>Google Analytics (G4 ID)</label>
                        <input type="text" name="ga4_id" value="<?php echo Core::escape($core->setting('ga4_id')); ?>" placeholder="G-XXXXXXXXXX">
                    </div>
                    <div class="field">
                        <label>Header Injection</label>
                        <textarea name="header_injection" style="height: 100px; font-family: monospace; font-size: 11px;"><?php echo Core::escape($core->setting('header_injection')); ?></textarea>
                    </div>
                </div>

                <div class="actions" style="margin-top: 40px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="padding: 14px 28px; font-size: 16px;">Save Changes →</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.querySelectorAll('.settings-nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.settings-nav a, .tab-content').forEach(el => el.classList.remove('active'));
                link.classList.add('active');
                document.querySelector(link.getAttribute('href')).classList.add('active');
                window.location.hash = link.getAttribute('href');
            });
        });

        if (window.location.hash) {
            const link = document.querySelector(`.settings-nav a[href="${window.location.hash}"]`);
            if (link) link.click();
        }
    </script>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login.php');
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
        $stmt->execute([$key, $value, $value]);
    }

    // Handle ads.txt file creation
    if (isset($_POST['ads_txt'])) {
        file_put_contents(__DIR__ . '/../ads.txt', $_POST['ads_txt']);
    }

    $success = "Settings updated successfully!";
    // Refresh settings in memory
    $core = Core::get_instance(); 
}

$ads_txt_content = file_exists(__DIR__ . '/../ads.txt') ? file_get_contents(__DIR__ . '/../ads.txt') : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Studio Settings — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-nav { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--line); padding-bottom: 12px; }
        .settings-nav a { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-mute); text-decoration: none; padding: 6px 12px; border-radius: 6px; }
        .settings-nav a.active { color: var(--accent); background: color-mix(in oklab, var(--accent) 10%, transparent); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index.php" class="tab">Dashboard</a>
            <a href="pages.php" class="tab">Pages</a>
            <a href="upload.php" class="tab">+ Upload Beat</a>
            <a href="settings.php" class="tab is-active">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout.php" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="panel" style="max-width: 1000px;">
        <h2>Studio Settings</h2>
        <p class="lead" style="margin-bottom: 32px;">Manage your platform configuration, SEO, and advertising.</p>

        <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <div class="settings-nav">
            <a href="#general" class="active">General</a>
            <a href="#seo">SEO</a>
            <a href="#smtp">SMTP</a>
            <a href="#social">Social</a>
            <a href="#ads">Ads & Analytics</a>
            <a href="#integration">Integrations</a>
                <a href="#payment">Payments</a>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

            <!-- General Tab -->
            <div id="general" class="tab-content active">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Site Identity</h3>
                <div class="field">
                    <label>Site Title</label>
                    <input type="text" name="site_title" value="<?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?>">
                    <p style="font-size: 11px; color: var(--ink-mute); margin: 4px 0 0;">
                        <b>Tip:</b> Use <code>SiteTitle</code> (PascalCase) to make the second part <span style="color:var(--accent)">orange</span>.
                    </p>
                </div>
            </div>

            <!-- SEO Tab -->
            <div id="seo" class="tab-content">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Global SEO Settings</h3>
                <div class="field">
                    <label>Meta Title Tag</label>
                    <input type="text" name="global_meta_title" value="<?php echo Core::escape($core->setting('global_meta_title')); ?>" placeholder="Default title for all pages">
                </div>
                <div class="field">
                    <label>Meta Description</label>
                    <textarea name="global_meta_description" placeholder="Brief summary of your site"><?php echo Core::escape($core->setting('global_meta_description')); ?></textarea>
                </div>
                <div class="field">
                    <label>Meta Keywords</label>
                    <input type="text" name="global_meta_keywords" value="<?php echo Core::escape($core->setting('global_meta_keywords')); ?>" placeholder="beats, auction, afrobeats">
                </div>
            </div>

            <!-- SMTP Tab -->
            <div id="smtp" class="tab-content">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Email (SMTP)</h3>
                <div style="display: grid; grid-template-columns: 1fr 100px; gap: 14px;">
                    <div class="field"><label>SMTP Host</label><input type="text" name="smtp_host" value="<?php echo Core::escape($core->setting('smtp_host')); ?>"></div>
                    <div class="field"><label>Port</label><input type="text" name="smtp_port" value="<?php echo Core::escape($core->setting('smtp_port')); ?>"></div>
                </div>
                <div class="field">
                    <label>Username / API Key</label>
                    <input type="text" name="smtp_user" value="<?php echo Core::escape($core->setting('smtp_user')); ?>">
                </div>
                <div class="field">
                    <label>Password / Secret</label>
                    <input type="password" name="smtp_pass" value="<?php echo Core::escape($core->setting('smtp_pass')); ?>">
                </div>
                <div class="field">
                    <label>From Email</label>
                    <input type="email" name="smtp_from" value="<?php echo Core::escape($core->setting('smtp_from')); ?>">
                </div>
            </div>

            <!-- Social Tab -->
            <div id="social" class="tab-content">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Social Media Links</h3>
                <div class="field">
                    <label>Instagram URL</label>
                    <input type="text" name="social_instagram" value="<?php echo Core::escape($core->setting('social_instagram')); ?>" placeholder="https://instagram.com/yourname">
                </div>
                <div class="field">
                    <label>Twitter / X URL</label>
                    <input type="text" name="social_twitter" value="<?php echo Core::escape($core->setting('social_twitter')); ?>" placeholder="https://twitter.com/yourname">
                </div>
                <div class="field">
                    <label>YouTube URL</label>
                    <input type="text" name="social_youtube" value="<?php echo Core::escape($core->setting('social_youtube')); ?>" placeholder="https://youtube.com/@yourname">
                </div>
            </div>

            <!-- Ads Tab -->
            <div id="ads" class="tab-content">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Google AdSense</h3>
                <div class="field">
                    <label>AdSense Publisher ID</label>
                    <input type="text" name="google_adsense_client" value="<?php echo Core::escape($core->setting('google_adsense_client')); ?>" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                </div>

                <h3 style="font-size: 14px; margin: 24px 0 12px; color: var(--accent);">ads.txt Content</h3>
                <div class="field">
                    <label>ads.txt</label>
                    <textarea name="ads_txt" style="height: 150px; font-family: monospace; font-size: 12px;"><?php echo Core::escape($ads_txt_content); ?></textarea>
                    <p style="font-size: 11px; color: var(--ink-mute); margin: 4px 0 0;">This content will be saved to your root <code>ads.txt</code> file.</p>
                </div>
            </div>

            <!-- Integration Tab -->
                <!-- Payments Tab -->
                <div id="payment" class="tab-content">
                    <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Plisio Integration</h3>
                    <div class="field">
                        <label>Plisio API Key</label>
                        <input type="password" name="plisio_api_key" value="<?php echo Core::escape($core->setting('plisio_api_key')); ?>" placeholder="Your Plisio Secret API Key">
                        <p style="font-size: 11px; color: var(--ink-mute); margin: 4px 0 0;">Get your key from the <a href="https://plisio.net/dashboard/settings/api" target="_blank" style="color:var(--accent)">Plisio Dashboard</a>.</p>
                    </div>
                </div>

            <div id="integration" class="tab-content">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Custom Code Injection</h3>
                <div class="field">
                    <label>Header Injection (inside &lt;head&gt;)</label>
                    <textarea name="header_injection" style="height: 120px; font-family: monospace; font-size: 12px;"><?php echo Core::escape($core->setting('header_injection')); ?></textarea>
                    <p style="font-size: 11px; color: var(--ink-mute); margin: 4px 0 0;">Perfect for Google Analytics or custom fonts.</p>
                </div>
                <div class="field">
                    <label>Footer Injection (before &lt;/body&gt;)</label>
                    <textarea name="footer_injection" style="height: 120px; font-family: monospace; font-size: 12px;"><?php echo Core::escape($core->setting('footer_injection')); ?></textarea>
                    <p style="font-size: 11px; color: var(--ink-mute); margin: 4px 0 0;">Ideal for live chat widgets or tracking scripts.</p>
                </div>
            </div>

            <div class="actions" style="margin-top:32px; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Save All Settings →</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelectorAll('.settings-nav a, .tab-content').forEach(el => el.classList.remove('active'));
            link.classList.add('active');
            document.querySelector(link.getAttribute('href')).classList.add('active');
        });
    });

    // Handle hash in URL for direct tab access
    if (window.location.hash) {
        const link = document.querySelector(`.settings-nav a[href="${window.location.hash}"]`);
        if (link) link.click();
    }
</script>

</body>
</html>

<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login');
    exit;
}

// Health Checks
$status = [
    'storage' => !empty($core->setting('google_drive_refresh_token')),
    'payments' => !empty($core->setting('plisio_api_key')),
    'auth' => !empty($core->setting('google_client_id')),
    'email' => true, // Placeholder
    'ads' => !empty($core->setting('adsense_pub_id')),
    'analytics' => !empty($core->setting('ga4_id'))
];

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
        :root { --sidebar-w: 220px; }
        .settings-layout { display: flex; gap: 40px; align-items: flex-start; max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .settings-nav { width: var(--sidebar-w); position: sticky; top: 100px; flex-shrink: 0; }
        .settings-nav a { display: block; padding: 10px 16px; font-size: 13px; font-weight: 500; color: var(--ink-dim); text-decoration: none; border-radius: 8px; transition: all 0.2s; border-left: 2px solid transparent; }
        .settings-nav a:hover { color: var(--accent); background: var(--bg-2); }
        .settings-nav a.active { color: var(--accent); background: color-mix(in oklab, var(--accent) 8%, transparent); border-left-color: var(--accent); }
        .settings-nav .num { font-family: 'JetBrains Mono', monospace; font-size: 10px; margin-right: 8px; opacity: 0.5; }
        
        .settings-main { flex: 1; }
        .section { margin-bottom: 80px; scroll-margin-top: 120px; }
        .section h2 { font-size: 24px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .section h2 .num { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--accent); border: 1px solid var(--accent); border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; }
        
        .status-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; margin-bottom: 32px; }
        .status-pill { padding: 12px; border-radius: 12px; background: var(--bg-2); border: 1px solid var(--line); display: flex; align-items: center; gap: 10px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-dot.ok { background: var(--ok); box-shadow: 0 0 8px var(--ok); }
        .status-dot.err { background: var(--ink-mute); }
        .status-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        .field { margin-bottom: 20px; position: relative; }
        .field label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; color: var(--ink-dim); }
        .field input, .field select, .field textarea { width: 100%; padding: 12px 16px; background: var(--bg-2); border: 1px solid var(--line); border-radius: 12px; font-size: 14px; color: var(--ink); transition: all 0.2s; }
        .field input:focus { border-color: var(--accent); outline: none; background: var(--bg-white); }
        .field .hint { font-size: 11px; color: var(--ink-mute); margin-top: 6px; line-height: 1.4; }
        
        .save-indicator { position: fixed; bottom: 24px; right: 24px; padding: 12px 20px; background: var(--ink); color: #fff; border-radius: 50px; font-size: 12px; font-weight: 600; display: none; align-items: center; gap: 10px; z-index: 10000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .save-indicator.is-saving { display: flex; }
        .spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .write-only { position: relative; }
        .write-only input { padding-right: 80px; }
        .write-only .mask-btn { position: absolute; right: 12px; top: 32px; font-size: 10px; font-weight: 700; color: var(--accent); cursor: pointer; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index" class="tab">Dashboard</a>
            <a href="upload" class="tab">+ Upload Beat</a>
            <a href="settings" class="tab is-active">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="settings-layout">
    <nav class="settings-nav">
        <a href="#status" class="active"><span class="num">00</span> Status</a>
        <a href="#site"><span class="num">01</span> Site</a>
        <a href="#seo"><span class="num">02</span> SEO</a>
        <a href="#auth"><span class="num">03</span> Auth</a>
        <a href="#ads"><span class="num">04</span> Ads</a>
        <a href="#custom"><span class="num">05</span> Custom code</a>
        <a href="#storage"><span class="num">06</span> Storage</a>
        <a href="#auction"><span class="num">07</span> Auction</a>
        <a href="#license"><span class="num">08</span> License</a>
        <a href="#email"><span class="num">09</span> Email</a>
        <a href="#payments"><span class="num">10</span> Payments</a>
    </nav>

    <main class="settings-main">
        <!-- 00 Status -->
        <section id="status" class="section">
            <h2><span class="num">00</span> System Status</h2>
            <div class="status-grid">
                <div class="status-pill"><div class="status-dot <?php echo $status['storage'] ? 'ok' : 'err'; ?>"></div><span class="status-label">Google Drive</span></div>
                <div class="status-pill"><div class="status-dot <?php echo $status['payments'] ? 'ok' : 'err'; ?>"></div><span class="status-label">Plisio</span></div>
                <div class="status-pill"><div class="status-dot <?php echo $status['auth'] ? 'ok' : 'err'; ?>"></div><span class="status-label">OAuth</span></div>
                <div class="status-pill"><div class="status-dot <?php echo $status['email'] ? 'ok' : 'err'; ?>"></div><span class="status-label">Email</span></div>
                <div class="status-pill"><div class="status-dot <?php echo $status['ads'] ? 'ok' : 'err'; ?>"></div><span class="status-label">AdSense</span></div>
                <div class="status-pill"><div class="status-dot <?php echo $status['analytics'] ? 'ok' : 'err'; ?>"></div><span class="status-label">GA4</span></div>
            </div>
        </section>

        <!-- 01 Site -->
        <section id="site" class="section">
            <h2><span class="num">01</span> Site</h2>
            <div class="field">
                <label>Site Title</label>
                <input type="text" data-key="site_title" value="<?php echo Core::escape($core->setting('site_title')); ?>">
            </div>
            <div class="field">
                <label>Site Tagline</label>
                <input type="text" data-key="site_tagline" value="<?php echo Core::escape($core->setting('site_tagline')); ?>">
            </div>
            <div class="field">
                <label>Contact Email</label>
                <input type="email" data-key="contact_email" value="<?php echo Core::escape($core->setting('contact_email')); ?>">
            </div>
        </section>

        <!-- 02 SEO -->
        <section id="seo" class="section">
            <h2><span class="num">02</span> SEO</h2>
            <div class="field">
                <label>Meta Description</label>
                <textarea data-key="meta_description"><?php echo Core::escape($core->setting('meta_description')); ?></textarea>
            </div>
            <div class="field">
                <label>GA4 Measurement ID</label>
                <input type="text" data-key="ga4_id" value="<?php echo Core::escape($core->setting('ga4_id')); ?>" placeholder="G-XXXXXXXX">
            </div>
        </section>

        <!-- 03 Auth -->
        <section id="auth" class="section">
            <h2><span class="num">03</span> Auth</h2>
            <div class="field">
                <label>Google Client ID</label>
                <input type="text" data-key="google_client_id" value="<?php echo Core::escape($core->setting('google_client_id')); ?>">
            </div>
            <div class="field">
                <label>Require Email Verification</label>
                <select data-key="require_email_verify">
                    <option value="1" <?php echo $core->setting('require_email_verify') == '1' ? 'selected' : ''; ?>>Yes</option>
                    <option value="0" <?php echo $core->setting('require_email_verify') == '0' ? 'selected' : ''; ?>>No</option>
                </select>
            </div>
        </section>

        <!-- 04 Ads -->
        <section id="ads" class="section">
            <h2><span class="num">04</span> Ads</h2>
            <div class="field">
                <label>AdSense Publisher ID</label>
                <input type="text" data-key="adsense_pub_id" value="<?php echo Core::escape($core->setting('adsense_pub_id')); ?>" placeholder="pub-xxxxxxxxxxxxxx">
            </div>
            <div class="field">
                <label>ads.txt Content</label>
                <textarea data-key="ads_txt" style="height: 100px; font-family: monospace;"><?php echo Core::escape($core->setting('ads_txt')); ?></textarea>
            </div>
        </section>

        <!-- 05 Custom code -->
        <section id="custom" class="section">
            <h2><span class="num">05</span> Custom Code</h2>
            <div class="field">
                <label>Header HTML (before &lt;/head&gt;)</label>
                <textarea data-key="custom_header" style="height: 100px; font-family: monospace;"><?php echo Core::escape($core->setting('custom_header')); ?></textarea>
            </div>
            <div class="field">
                <label>Custom CSS</label>
                <textarea data-key="custom_css" style="height: 100px; font-family: monospace;"><?php echo Core::escape($core->setting('custom_css')); ?></textarea>
            </div>
        </section>

        <!-- 06 Storage -->
        <section id="storage" class="section">
            <h2><span class="num">06</span> Storage</h2>
            <?php 
            $has_creds = !empty($core->setting('google_drive_client_id')) && !empty($core->setting('google_drive_client_secret'));
            $has_token = !empty($core->setting('google_drive_refresh_token'));
            ?>
            <div style="background: var(--bg-2); padding: 24px; border-radius: 16px; margin-bottom: 24px; border: 1px solid var(--line);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <div style="font-weight: 700; font-size: 16px; margin-bottom: 4px;"><?php echo $has_token ? 'Google Drive Connected' : 'Google Drive Not Connected'; ?></div>
                        <div style="font-size: 12px; color: var(--ink-dim);">Authenticate with Google to enable cloud uploads and file selection.</div>
                    </div>
                    <?php if ($has_creds): ?>
                        <a href="../api/google_drive_auth" class="btn btn-primary" style="font-size: 12px; padding: 10px 20px;">Connect →</a>
                    <?php endif; ?>
                </div>
                
                <div style="padding-top: 20px; border-top: 1px dashed var(--line);">
                    <h4 style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--ink-mute); margin-bottom: 12px;">Setup Instructions</h4>
                    <ol style="margin: 0; padding: 0 0 0 16px; font-size: 12px; color: var(--ink-dim); line-height: 1.6;">
                        <li style="margin-bottom: 8px;">Go to the <a href="https://console.cloud.google.com/" target="_blank" style="color:var(--accent); font-weight:600;">Google Cloud Console</a> and create a new project.</li>
                        <li style="margin-bottom: 8px;">Enable the <b>Google Drive API</b> for your project.</li>
                        <li style="margin-bottom: 8px;">Go to "Credentials" → "Create Credentials" → "OAuth Client ID".</li>
                        <li style="margin-bottom: 8px;">Select "Web Application" and add this Redirect URI:<br>
                            <code style="background:var(--bg-3); padding:4px 8px; border-radius:4px; font-family:'JetBrains Mono'; font-size:10px; display:inline-block; margin-top:4px; color:var(--ink);">
                                <?php 
                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                                    echo $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace('admin/settings', 'api/google_drive_callback', explode('?', $_SERVER['REQUEST_URI'])[0]);
                                ?>
                            </code>
                        </li>
                        <li>Copy the <b>Client ID</b> and <b>Client Secret</b> below, save, and then click "Connect".</li>
                    </ol>
                </div>
            </div>
            <div class="field write-only">
                <label>Drive Client ID</label>
                <input type="text" data-key="google_drive_client_id" value="<?php echo Core::escape($core->setting('google_drive_client_id')); ?>">
            </div>
            <div class="field write-only">
                <label>Drive Client Secret</label>
                <input type="password" data-key="google_drive_client_secret" value="<?php echo Core::escape($core->setting('google_drive_client_secret')); ?>">
            </div>
            <div class="field">
                <label>Refresh Token (Auto-filled)</label>
                <input type="text" readonly value="<?php echo $has_token ? '••••••••••••••••••••••••' : ''; ?>" style="background: var(--bg-3);">
            </div>
        </section>

        <!-- 07 Auction -->
        <section id="auction" class="section">
            <h2><span class="num">07</span> Auction</h2>
            <div class="field">
                <label>Default Duration (Minutes)</label>
                <input type="number" data-key="auction_duration" value="<?php echo Core::escape($core->setting('auction_duration', '30')); ?>">
            </div>
            <div class="field">
                <label>Anti-Snipe Window (Minutes)</label>
                <input type="number" data-key="auction_anti_snipe" value="<?php echo Core::escape($core->setting('auction_anti_snipe', '2')); ?>">
            </div>
            <div class="field">
                <label>Minimum Bid Increment ($)</label>
                <input type="number" data-key="auction_min_inc" value="<?php echo Core::escape($core->setting('auction_min_inc', '5')); ?>">
            </div>
        </section>

        <!-- 08 License -->
        <section id="license" class="section">
            <h2><span class="num">08</span> License</h2>
            <div class="field">
                <label>Required Credit Phrase</label>
                <input type="text" data-key="license_credit" value="<?php echo Core::escape($core->setting('license_credit', 'Produced by OBV')); ?>">
                <div class="hint">The verbatim text the buyer must include in their release metadata.</div>
            </div>
            <div class="field">
                <label>Download Window (Days)</label>
                <input type="number" data-key="license_window" value="<?php echo Core::escape($core->setting('license_window', '7')); ?>">
            </div>
        </section>

        <!-- 09 Email -->
        <section id="email" class="section">
            <h2><span class="num">09</span> Email</h2>
            <div class="field">
                <label>Sender Name</label>
                <input type="text" data-key="email_sender_name" value="<?php echo Core::escape($core->setting('email_sender_name', 'BEATZAZA')); ?>">
            </div>
            <div class="field">
                <label>From Address</label>
                <input type="email" data-key="email_from" value="<?php echo Core::escape($core->setting('email_from')); ?>">
            </div>
        </section>

        <!-- 10 Payments -->
        <section id="payments" class="section">
            <h2><span class="num">10</span> Payments</h2>
            <div class="field write-only">
                <label>Plisio API Key</label>
                <input type="password" data-key="plisio_api_key" value="<?php echo Core::escape($core->setting('plisio_api_key')); ?>">
            </div>
            <div class="field">
                <label>Payment Window (Hours)</label>
                <input type="number" data-key="payment_window" value="<?php echo Core::escape($core->setting('payment_window', '24')); ?>">
                <div class="hint">Time the winner has to pay before the cascade advances.</div>
            </div>
            <div class="field">
                <label>Required Confirmations</label>
                <input type="number" data-key="plisio_confirmations" value="<?php echo Core::escape($core->setting('plisio_confirmations', '2')); ?>">
            </div>
        </section>
    </main>
</div>

<div id="save-indicator" class="save-indicator">
    <div class="spinner"></div>
    <span>Saving changes...</span>
</div>

<script>
    // AJAX Auto-save
    const inputs = document.querySelectorAll('[data-key]');
    const indicator = document.getElementById('save-indicator');
    let saveTimeout;

    inputs.forEach(input => {
        input.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => save(input), 800);
        });
    });

    async function save(el) {
        indicator.classList.add('is-saving');
        const key = el.getAttribute('data-key');
        const value = el.value;

        try {
            const fd = new FormData();
            fd.append('key', key);
            fd.append('value', value);

            const res = await fetch('api/save_setting.php', {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            
            if (data.success) {
                el.style.borderColor = 'var(--ok)';
                setTimeout(() => el.style.borderColor = '', 1000);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setTimeout(() => indicator.classList.remove('is-saving'), 500);
        }
    }

    // Scroll Spy & Nav
    const sections = document.querySelectorAll('.section');
    const navLinks = document.querySelectorAll('.settings-nav a');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= sectionTop - 150) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(current)) {
                link.classList.add('active');
            }
        });
    });
</script>

</body>
</html>

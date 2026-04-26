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
        $stmt->execute([$key, $value, $value]);
    }
    $success = "Settings updated successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Studio Settings — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index.php" class="tab">Dashboard</a>
            <a href="upload.php" class="tab">+ Upload Beat</a>
            <a href="settings.php" class="tab is-active">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout.php" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="panel">
        <h2>Studio Settings</h2>
        <p class="lead">Configure your SMTP and site metadata.</p>

        <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
            <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">General</h3>
            <div class="field">
                <label>Site Title</label>
                <input type="text" name="site_title" value="<?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?>">
                <p style="font-size: 11px; color: var(--ink-mute); margin: 4px 0 0;">
                    <b>Tip:</b> Use <code>SiteTitle</code> (PascalCase) to make the second part <span style="color:var(--accent)">orange</span>.
                </p>
            </div>

            <h3 style="font-size: 14px; margin: 24px 0 12px; color: var(--accent);">SMTP Configuration</h3>
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

            <div class="actions" style="margin-top:24px; display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">Save Settings →</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>

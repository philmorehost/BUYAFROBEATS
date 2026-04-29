<?php
session_start();

$config_file = __DIR__ . '/../config.php';
if (file_exists($config_file)) {
    header('Location: ../index.php');
    exit;
}

$stage = isset($_GET['stage']) ? (int)$_GET['stage'] : 1;
$errors = [];

// Stage 2 Logic: Database Setup
if ($stage === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? '';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);

        $_SESSION['db_config'] = [
            'host' => $db_host,
            'name' => $db_name,
            'user' => $db_user,
            'pass' => $db_pass
        ];

        header('Location: ?stage=3');
        exit;
    } catch (PDOException $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
    }
}

// Stage 3 Logic: Admin Setup
if ($stage === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_user = $_POST['admin_user'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';

    $smtp_host = $_POST['smtp_host'] ?? '';
    $smtp_port = $_POST['smtp_port'] ?? '';
    $smtp_user = $_POST['smtp_user'] ?? '';
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_enc = $_POST['smtp_enc'] ?? 'tls';

    if (!empty($admin_user) && !empty($admin_email) && !empty($admin_pass)) {
        if (!isset($_SESSION['db_config'])) {
            $errors[] = "Database configuration lost. Please restart from stage 2.";
            $stage = 2;
        } else {
            $db = $_SESSION['db_config'];
            try {
                $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $hashed_pass = password_hash($admin_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
                $stmt->execute([$admin_user, $admin_email, $hashed_pass]);

                // Save SMTP settings
                $settings = [
                    'smtp_host' => $smtp_host,
                    'smtp_port' => $smtp_port,
                    'smtp_user' => $smtp_user,
                    'smtp_pass' => $smtp_pass,
                    'smtp_enc' => $smtp_enc,
                    'site_title' => 'BUYAFROBEATS',
                    'installed_at' => date('Y-m-d H:i:s')
                ];
                $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
                foreach ($settings as $k => $v) {
                    $stmt->execute([$k, $v]);
                }

                // Generate config.php
                $config_content = "<?php\n"
                    . "define('DB_HOST', '" . addslashes($db['host']) . "');\n"
                    . "define('DB_NAME', '" . addslashes($db['name']) . "');\n"
                    . "define('DB_USER', '" . addslashes($db['user']) . "');\n"
                    . "define('DB_PASS', '" . addslashes($db['pass']) . "');\n\n"
                    . "// Security Salt\n"
                    . "define('AUTH_SALT', '" . bin2hex(random_bytes(32)) . "');\n";

                file_put_contents(__DIR__ . '/../config.php', $config_content);

                header('Location: ?stage=4');
                exit;
            } catch (Exception $e) {
                $errors[] = "Setup failed: " . $e->getMessage();
            }
        }
    } else {
        $errors[] = "Please fill in all admin details.";
    }
}

// Helper function to check requirements
function check_requirements() {
    $reqs = [
        'PHP Version >= 8.2' => PHP_VERSION_ID >= 80200,
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'MBString Extension' => extension_loaded('mbstring'),
        'CURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'GD or Imagick Extension' => (extension_loaded('gd') || extension_loaded('imagick')),
        'Config directory writable' => is_writable(__DIR__ . '/..'),
        'Uploads directory writable' => is_writable(__DIR__ . '/../uploads'),
    ];
    return $reqs;
}

$requirements = check_requirements();
$can_proceed = !in_array(false, $requirements, true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUYAFROBEATS — Installer</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?php echo rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='#ffa326'/><text x='50%' y='54%' dominant-baseline='central' text-anchor='middle' font-family='Space Grotesk, sans-serif' font-weight='700' font-size='60' fill='#1a1815'>B</text></svg>"); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: oklch(0.16 0.012 65);
            --bg-2: oklch(0.20 0.013 65);
            --bg-3: oklch(0.24 0.014 65);
            --line: oklch(0.30 0.012 65);
            --ink: oklch(0.96 0.012 85);
            --ink-dim: oklch(0.72 0.014 75);
            --ink-mute: oklch(0.55 0.012 70);
            --accent: oklch(0.80 0.17 65);
            --accent-ink: oklch(0.18 0.02 60);
            --danger: oklch(0.70 0.18 28);
            --ok: oklch(0.80 0.16 150);
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { background: var(--bg-2); border: 1px solid var(--line); border-radius: 24px; padding: 40px; width: 100%; max-width: 500px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 22px; margin-bottom: 30px; letter-spacing: -0.02em; }
        .logo .dot { width: 12px; height: 12px; border-radius: 50%; background: var(--accent); }
        h1 { font-size: 28px; margin: 0 0 10px; letter-spacing: -0.02em; }
        p { color: var(--ink-dim); font-size: 15px; line-height: 1.6; margin: 0 0 24px; }
        .req-list { list-style: none; padding: 0; margin: 0 0 30px; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
        .req-item { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: var(--bg); border-bottom: 1px solid var(--line); }
        .req-item:last-child { border-bottom: 0; }
        .req-item .label { font-size: 14px; font-weight: 500; }
        .req-item .status { font-family: 'JetBrains Mono', monospace; font-size: 11px; text-transform: uppercase; font-weight: 600; padding: 4px 8px; border-radius: 999px; }
        .status.pass { background: color-mix(in oklab, var(--ok) 20%, transparent); color: var(--ok); }
        .status.fail { background: color-mix(in oklab, var(--danger) 20%, transparent); color: var(--danger); }
        .btn { display: inline-flex; align-items: center; justify-content: center; background: var(--accent); color: var(--accent-ink); border: 0; border-radius: 999px; padding: 14px 28px; font-weight: 600; cursor: pointer; text-decoration: none; width: 100%; transition: transform 0.1s; }
        .btn:hover { background: oklch(0.84 0.17 65); }
        .btn:active { transform: translateY(1px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .steps { display: flex; gap: 8px; margin-top: 30px; justify-content: center; }
        .step-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--line); }
        .step-dot.active { background: var(--accent); width: 18px; border-radius: 999px; }

        .field { margin-bottom: 16px; display:flex; flex-direction: column; gap: 6px; }
        .field label { font-family:'JetBrains Mono', monospace; font-size: 11px; color: var(--ink-mute); text-transform: uppercase; letter-spacing: 0.08em; text-align: left; }
        .field input, .field select { background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px; color: var(--ink); font: inherit; font-size: 14px; outline: none; }
        .field input:focus, .field select:focus { border-color: var(--accent); }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><span class="dot"></span> <span>BUY<span style="color:var(--accent)">AFROBEATS</span></span></div>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $err): ?>
                <div style="background: color-mix(in oklab, var(--danger) 15%, transparent); color: var(--danger); padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; border: 1px solid color-mix(in oklab, var(--danger) 30%, transparent);">
                    <?php echo $err; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($stage === 1 || empty($stage)): ?>
            <h1>Welcome to the Studio</h1>
            <p>Before we set up your auction platform, let's make sure your server is ready.</p>
            <div class="req-list">
                <?php foreach ($requirements as $label => $pass): ?>
                    <div class="req-item">
                        <span class="label"><?php echo $label; ?></span>
                        <span class="status <?php echo $pass ? 'pass' : 'fail'; ?>"><?php echo $pass ? 'OK' : 'MISSING'; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="?stage=2" class="btn" <?php echo !$can_proceed ? 'disabled onclick="return false;"' : ''; ?>>Start Installation →</a>
        <?php endif; ?>

        <?php if ($stage === 2): ?>
            <h1>Database Setup</h1>
            <p>Connect your MySQL database. We'll handle the tables.</p>
            
            <form method="POST">
                <div class="field">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="field">
                    <label>Database Name</label>
                    <input type="text" name="db_name" placeholder="buyafrobeats_db" required>
                </div>
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="db_pass" placeholder="••••••••">
                </div>
                <button type="submit" class="btn" style="margin-top: 10px;">Connect & Install Schema →</button>
            </form>
        <?php endif; ?>

        <?php if ($stage === 3): ?>
            <h1>Admin & SMTP</h1>
            <p>Create your account and configure email notifications.</p>

            <form method="POST">
                <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent); text-align: left;">Admin Account</h3>
                <div class="field">
                    <label>Username</label>
                    <input type="text" name="admin_user" placeholder="admin" required>
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="admin_email" placeholder="admin@buyafrobeats.com" required>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="admin_pass" placeholder="••••••••" required>
                </div>

                <h3 style="font-size: 14px; margin: 20px 0 12px; color: var(--accent); text-align: left;">SMTP Settings (Optional)</h3>
                <div style="display: grid; grid-template-columns: 1fr 100px; gap: 10px;">
                    <div class="field"><label>SMTP Host</label><input type="text" name="smtp_host" placeholder="smtp.resend.com"></div>
                    <div class="field"><label>Port</label><input type="text" name="smtp_port" placeholder="587"></div>
                </div>
                <div class="field"><label>User / API Key</label><input type="text" name="smtp_user" placeholder="resend_api_..."></div>
                <div class="field"><label>Pass / Token</label><input type="password" name="smtp_pass" placeholder="••••••••"></div>
                
                <button type="submit" class="btn" style="margin-top: 10px;">Complete Setup →</button>
            </form>
        <?php endif; ?>

        <?php if ($stage === 4): ?>
            <h1>Congratulations!</h1>
            <p>BUYAFROBEATS is now installed. Your studio is ready for the first drop.</p>
            
            <div style="background: var(--bg); border: 1px solid var(--line); border-radius: 14px; padding: 20px; margin-bottom: 30px; text-align: left;">
                <h4 style="margin: 0 0 10px; color: var(--accent); font-size: 14px;">Next Steps:</h4>
                <ul style="margin: 0; padding: 0 0 0 18px; font-size: 13px; color: var(--ink-dim); line-height: 1.8;">
                    <li>Delete the <code style="color: var(--accent);">install/</code> directory immediately.</li>
                    <li>Login to the <a href="../admin/" style="color: var(--ink); text-decoration: underline;">Admin Dashboard</a>.</li>
                    <li>Upload your first beat to start an auction.</li>
                </ul>
            </div>

            <a href="../index.php" class="btn">Go to Marketplace →</a>
        <?php endif; ?>

        <div class="steps">
            <div class="step-dot <?php echo $stage === 1 ? 'active' : ''; ?>"></div>
            <div class="step-dot <?php echo $stage === 2 ? 'active' : ''; ?>"></div>
            <div class="step-dot <?php echo $stage === 3 ? 'active' : ''; ?>"></div>
            <div class="step-dot <?php echo $stage === 4 ? 'active' : ''; ?>"></div>
        </div>
    </div>
</body>
</html>

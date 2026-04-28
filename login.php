<?php
require_once __DIR__ . '/includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    $stmt = $core->db()->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$user, $user]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_role'] = $u['role'];
        $_SESSION['username'] = $u['username'];
        header('Location: admin/index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: oklch(0.16 0.012 65);
            --bg-2: oklch(0.20 0.013 65);
            --line: oklch(0.30 0.012 65);
            --ink: oklch(0.96 0.012 85);
            --accent: oklch(0.80 0.17 65);
            --accent-ink: oklch(0.18 0.02 60);
        }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: var(--bg-2); border: 1px solid var(--line); border-radius: 24px; padding: 40px; width: 100%; max-width: 400px; }
        h1 { font-size: 24px; margin: 0 0 10px; text-align: center; }
        .field { margin-bottom: 16px; display:flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 11px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.08em; }
        .field input { background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px; color: var(--ink); font: inherit; outline: none; }
        .field input:focus { border-color: var(--accent); }
        .btn { background: var(--accent); color: var(--accent-ink); border: 0; border-radius: 999px; padding: 14px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; }
        .error { color: #ff6b6b; font-size: 13px; text-align: center; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Welcome Back</h1>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Username / Email</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log In →</button>
        </form>

        <?php
        $google_id = $core->setting('google_client_id');
        if ($google_id):
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $redirect_uri = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/google_callback.php';
            $google_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                'client_id' => $google_id,
                'redirect_uri' => $redirect_uri,
                'response_type' => 'code',
                'scope' => 'email profile',
                'prompt' => 'select_account'
            ]);
        ?>
            <div style="margin: 24px 0; display: flex; align-items: center; gap: 12px;">
                <div style="flex: 1; height: 1px; background: var(--line);"></div>
                <div style="font-size: 10px; color: var(--ink-mute); text-transform: uppercase; letter-spacing: 0.05em;">Or</div>
                <div style="flex: 1; height: 1px; background: var(--line);"></div>
            </div>
            <a href="<?php echo $google_url; ?>" class="btn" style="background: #fff; color: #000; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <svg width="18" height="18" viewBox="0 0 18 18"><path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285f4"/><path d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34a853"/><path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.712s.102-1.173.282-1.712V4.956H.957A8.998 8.998 0 0 0 0 9c0 1.497.366 2.91 1.008 4.156l2.956-2.444z" fill="#fbbc05"/><path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.956l3.007 2.332C4.672 5.164 6.656 3.58 9 3.58z" fill="#ea4335"/></svg>
                Continue with Google
            </a>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--ink-dim);">Don't have an account? <a href="register.php" style="color: var(--accent); text-decoration: none; font-weight: 600;">Sign up</a></div>
    </div>
</body>
</html>

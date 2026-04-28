<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($user) || empty($email) || empty($pass)) {
        $error = 'All fields are required.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $core->db()->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$user, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $core->db()->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            if ($stmt->execute([$user, $email, $hash])) {
                $uid = $core->db()->lastInsertId();
                $_SESSION['user_id'] = $uid;
                $_SESSION['user_role'] = 'user';
                $_SESSION['username'] = $user;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: oklch(0.16 0.012 65);
            --bg-2: oklch(0.20 0.013 65);
            --line: oklch(0.30 0.012 65);
            --ink: oklch(0.96 0.012 85);
            --ink-dim: oklch(0.72 0.014 75);
            --accent: oklch(0.80 0.17 65);
            --accent-ink: oklch(0.18 0.02 60);
        }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: 'Space Grotesk', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { background: var(--bg-2); border: 1px solid var(--line); border-radius: 24px; padding: 40px; width: 100%; max-width: 400px; }
        h1 { font-size: 24px; margin: 0 0 10px; text-align: center; }
        .field { margin-bottom: 16px; display:flex; flex-direction: column; gap: 6px; }
        .field label { font-size: 11px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.08em; }
        .field input { background: var(--bg); border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px; color: var(--ink); font: inherit; outline: none; width: 100%; box-sizing: border-box; }
        .field input:focus { border-color: var(--accent); }
        .btn { background: var(--accent); color: var(--accent-ink); border: 0; border-radius: 999px; padding: 14px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; font-family: inherit; }
        .error { color: #ff6b6b; font-size: 13px; text-align: center; margin-bottom: 16px; }
        .switch { text-align: center; margin-top: 20px; font-size: 13px; color: var(--ink-dim); }
        .switch a { color: var(--accent); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Create Account</h1>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Create Account →</button>
        </form>
        <div class="switch">Already have an account? <a href="login.php">Log in</a></div>
    </div>
</body>
</html>

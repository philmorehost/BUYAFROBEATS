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

    if (isset($_POST['delete_id'])) {
        $stmt = $core->db()->prepare("DELETE FROM faqs WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $success = "FAQ deleted!";
    } else {
        $stmt = $core->db()->prepare("INSERT INTO faqs (question, answer, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['question'], $_POST['answer'], (int)$_POST['sort_order']]);
        $success = "FAQ added successfully!";
    }
}

$faqs = $core->db()->query("SELECT * FROM faqs ORDER BY sort_order ASC, created_at DESC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Manage FAQs — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index.php" class="tab">Dashboard</a>
            <a href="pages.php" class="tab">Pages</a>
            <a href="faqs.php" class="tab is-active">FAQs</a>
            <a href="settings.php" class="tab">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout.php" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div style="display:grid; grid-template-columns: 1fr 350px; gap: 32px;">
        <div>
            <h2>Active FAQs</h2>
            <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
            
            <table class="log-table">
                <thead><tr><th>Order</th><th>Question / Answer</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($faqs as $f): ?>
                        <tr>
                            <td class="mono"><?php echo $f['sort_order']; ?></td>
                            <td>
                                <b><?php echo Core::escape($f['question']); ?></b><br>
                                <span style="font-size:12px; color:var(--ink-mute)"><?php echo Core::escape($f['answer']); ?></span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
                                    <input type="hidden" name="delete_id" value="<?php echo $f['id']; ?>">
                                    <button type="submit" class="btn" style="color:var(--danger); font-size:10px;" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Add New FAQ</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
                <div class="field">
                    <label>Question</label>
                    <input type="text" name="question" required>
                </div>
                <div class="field">
                    <label>Answer</label>
                    <textarea name="answer" style="height:100px;" required></textarea>
                </div>
                <div class="field">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="0">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:12px;">Add FAQ →</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>

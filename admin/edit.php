<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login');
    exit;
}

$id = $_GET['id'] ?? '';
$error = '';
$success = '';

$db = $core->db();
$stmt = $db->prepare("SELECT * FROM beats WHERE id = ?");
$stmt->execute([$id]);
$beat = $stmt->fetch();

if (!$beat) {
    header('Location: index');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new \Exception("Security check failed.");
        }
        
        $title = $_POST['title'] ?? '';
        $starting = $_POST['starting_bid'] ?? 0;
        $bpm = $_POST['bpm'] ?? 0;
        $key = $_POST['key_sig'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $duration = $_POST['duration'] ?? '';
        $status = $_POST['status'] ?? 'live';

        if (empty($title)) {
            throw new \Exception("Title cannot be empty.");
        }

        $stmt = $db->prepare("UPDATE beats SET title = ?, bpm = ?, key_sig = ?, genre = ?, duration = ?, starting_bid = ?, status = ? WHERE id = ?");
        $stmt->execute([strtoupper($title), $bpm, $key, $genre, $duration, $starting, $status, $id]);

        $success = "Beat updated successfully.";
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM beats WHERE id = ?");
        $stmt->execute([$id]);
        $beat = $stmt->fetch();
        
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Edit Beat — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index" class="tab">Dashboard</a>
            <a href="upload" class="tab">+ Upload Beat</a>
            <a href="settings" class="tab">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="panel">
        <h2>Edit beat: <?php echo Core::escape($beat['title']); ?></h2>
        
        <?php if ($error): ?><div style="color:var(--danger); margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

            <div class="field">
                <label>Beat Title</label>
                <input type="text" name="title" value="<?php echo Core::escape($beat['title']); ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="field"><label>Starting Bid ($)</label><input type="number" name="starting_bid" value="<?php echo $beat['starting_bid']; ?>" required></div>
                <div class="field"><label>BPM</label><input type="number" name="bpm" value="<?php echo $beat['bpm']; ?>"></div>
                <div class="field">
                    <label>Key</label>
                    <select name="key_sig">
                        <?php foreach(['C min','D min','F# min','A min','G maj','E maj'] as $k): ?>
                            <option <?php if($beat['key_sig'] === $k) echo 'selected'; ?>><?php echo $k; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Genre</label>
                    <select name="genre">
                        <?php foreach(['Afrobeats','Amapiano','Afro-Swing','Afro-House','Afro-Fusion','Afro-Pop'] as $g): ?>
                            <option <?php if($beat['genre'] === $g) echo 'selected'; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Duration</label><input type="text" name="duration" value="<?php echo Core::escape($beat['duration']); ?>"></div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="live" <?php if($beat['status'] === 'live') echo 'selected'; ?>>Live</option>
                        <option value="sold" <?php if($beat['status'] === 'sold') echo 'selected'; ?>>Sold</option>
                        <option value="expired" <?php if($beat['status'] === 'expired') echo 'selected'; ?>>Expired</option>
                    </select>
                </div>
            </div>

            <div class="actions" style="margin-top:24px; display:flex; justify-content:flex-end; gap:12px;">
                <a href="index" class="btn btn-ghost">Back to Dashboard</a>
                <button type="submit" class="btn btn-primary">Update Beat →</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>

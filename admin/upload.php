<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Storage.php';

use BAF\Core;
use BAF\Storage;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

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

        if (empty($title) || $starting <= 0 || !isset($_FILES['audio'])) {
            throw new \Exception("Please fill in all required fields and upload an audio file.");
        }

        $storage = new Storage();
        $filename = $storage->upload_audio($_FILES['audio']);

        $sample_filename = null;
        if (isset($_FILES['sample']) && $_FILES['sample']['error'] === UPLOAD_ERR_OK) {
            $sample_filename = $storage->upload_audio($_FILES['sample']);
        }

        $stmt = $core->db()->prepare("INSERT INTO beats (title, bpm, key_sig, genre, duration, starting_bid, current_bid, audio_path, sample_path, status)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'live')");
        $stmt->execute([strtoupper($title), $bpm, $key, $genre, $duration, $starting, $starting, $filename, $sample_filename]);

        $success = "Beat \"$title\" has been listed live!";
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
    <title>Upload New Beat — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index.php" class="tab">Dashboard</a>
            <a href="upload.php" class="tab is-active">+ Upload Beat</a>
            <a href="settings.php" class="tab">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout.php" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="panel">
        <div class="admin-banner"><span style="width:6,height:6,borderRadius:'50%',background:'var(--accent)',display:'inline-block'"></span> Admin · Listing a new drop</div>
        <h2>List a new beat</h2>
        <p class="lead">It goes live immediately. The 30-minute countdown starts on the first bid.</p>

        <?php if ($error): ?><div style="color:var(--danger); margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Main Audio File (HQ)</label>
                    <input type="file" name="audio" accept="audio/*" required>
                </div>
                <div class="field">
                    <label>Sample Audio (optional)</label>
                    <input type="file" name="sample" accept="audio/*">
                </div>
            </div>
            
            <div class="field">
                <label>Beat Title</label>
                <input type="text" name="title" placeholder="LAGOS RAIN" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="field"><label>Starting Bid ($)</label><input type="number" name="starting_bid" value="99" required></div>
                <div class="field"><label>BPM</label><input type="number" name="bpm" value="100"></div>
                <div class="field">
                    <label>Key</label>
                    <select name="key_sig">
                        <option>C min</option><option>D min</option><option>F# min</option><option>A min</option><option>G maj</option><option>E maj</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Genre</label>
                    <select name="genre">
                        <option>Afrobeats</option><option>Amapiano</option><option>Afro-Swing</option><option>Afro-House</option><option>Afro-Fusion</option><option>Afro-Pop</option>
                    </select>
                </div>
                <div class="field"><label>Duration</label><input type="text" name="duration" placeholder="2:48"></div>
            </div>

            <div class="actions" style="margin-top:24px; display:flex; justify-content:flex-end; gap:12px;">
                <a href="index.php" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary">Put it live →</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>

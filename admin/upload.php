<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Storage.php';

use BAF\Core;
use BAF\Storage;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: ../index.php');
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
        $starting = (float)($_POST['starting_bid'] ?? 0);
        $reserve = (float)($_POST['reserve_price'] ?? 0);
        $bpm = (int)($_POST['bpm'] ?? 0);
        $key = $_POST['key_sig'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $duration = $_POST['duration'] ?? '';
        
        $master_url = $_POST['master_url'] ?? '';
        $stems_url = $_POST['stems_url'] ?? '';
        $license_url = $_POST['license_url'] ?? '';
        $preview_url = $_POST['preview_url'] ?? '';

        // Google Drive Integration
        require_once __DIR__ . '/../includes/GoogleDrive.php';
        $drive = new \BAF\GoogleDrive();
        $root_parent = $core->setting('google_drive_parent_folder');
        
        // Create a specific folder for this auction
        $parent_folder = $drive->create_folder(strtoupper($title), $root_parent);
        if (!$parent_folder) $parent_folder = $root_parent; // Fallback to root if folder creation fails

        // Handle Master File
        if (!empty($_FILES['master_file']['name'])) {
            $fid = $drive->upload_file($_FILES['master_file']['tmp_name'], $_FILES['master_file']['name'], $_FILES['master_file']['type'], $parent_folder);
            if ($fid) $master_url = "https://drive.google.com/open?id=" . $fid;
        }

        // Handle Stems File
        if (!empty($_FILES['stems_file']['name'])) {
            $fid = $drive->upload_file($_FILES['stems_file']['tmp_name'], $_FILES['stems_file']['name'], $_FILES['stems_file']['type'], $parent_folder);
            if ($fid) $stems_url = "https://drive.google.com/open?id=" . $fid;
        }

        // Handle License File
        if (!empty($_FILES['license_file']['name'])) {
            $fid = $drive->upload_file($_FILES['license_file']['tmp_name'], $_FILES['license_file']['name'], $_FILES['license_file']['type'], $parent_folder);
            if ($fid) $license_url = "https://drive.google.com/open?id=" . $fid;
        }

        if (empty($title) || $starting <= 0) {
            throw new \Exception("Title and starting bid are required.");
        }

        if (empty($preview_url) && empty($_FILES['preview_file']['name'])) {
            throw new \Exception("A preview audio file or URL is required.");
        }

        // Handle local preview upload if provided
        $preview_path = null;
        if (!empty($_FILES['preview_file']['name'])) {
            $storage = new Storage();
            $preview_path = $storage->upload_audio($_FILES['preview_file']);
        }

        $stmt = $core->db()->prepare("INSERT INTO beats (title, bpm, key_sig, genre, duration, starting_bid, current_bid, reserve_price, audio_path, audio_url, master_url, stems_url, license_url, status, created_at)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'live', UTC_TIMESTAMP())");
        $stmt->execute([
            strtoupper($title), $bpm, $key, $genre, $duration, 
            $starting, $starting, $reserve,
            $preview_path, $preview_url,
            $master_url, $stems_url, $license_url
        ]);

        $success = "Beat \"$title\" is now live!";
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => true, 'message' => $success]);
            exit;
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload — <?php echo Core::escape($core->setting('site_title', 'BEATZAZA')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=3.0">
</head>
<body class="admin-page">
    <header class="topbar">
        <div class="topbar-inner">
            <a href="index.php" class="logo">
                <?php echo $core->render_logo(); ?> <span style="opacity: 0.5; margin-left: 8px; font-weight: 400;">Upload</span>
            </a>
            <nav class="tabs">
                <a href="index.php" class="tab">Overview</a>
                <a href="upload.php" class="tab is-active">+ Upload Beat</a>
                <a href="settings.php" class="tab">Settings</a>
            </nav>
            <div class="spacer"></div>
            <a href="logout.php" class="tab mono" style="font-size: 11px;">Logout</a>
        </div>
    </header>

    <main class="page">
        <div class="panel" style="max-width: 800px;">
            <div class="admin-banner"><span class="live-dot"></span> New Listing</div>
            <h2>Create Auction</h2>
            <p class="lead">Fill in the beat details. High-quality deliverables should be hosted on Google Drive.</p>

            <?php if ($error): ?><div style="color:var(--danger); margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

                <div class="field">
                    <label>Beat Title</label>
                    <input type="text" name="title" placeholder="e.g. BURNER BOY TYPE" required>
                </div>

                <div class="row3">
                    <div class="field"><label>Starting Bid ($)</label><input type="number" name="starting_bid" value="99"></div>
                    <div class="field"><label>Reserve Price ($)</label><input type="number" name="reserve_price" value="0"></div>
                    <div class="field"><label>BPM</label><input type="number" name="bpm" value="100"></div>
                </div>

                <div class="row3">
                    <div class="field">
                        <label>Key</label>
                        <input type="text" name="key_sig" placeholder="F# min">
                    </div>
                    <div class="field">
                        <label>Genre</label>
                        <select name="genre">
                            <option>Afrobeats</option>
                            <option>Amapiano</option>
                            <option>Afro-Swing</option>
                            <option>Afro-Fusion</option>
                        </select>
                    </div>
                    <div class="field"><label>Duration</label><input type="text" name="duration" placeholder="2:45"></div>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--line); margin: 32px 0;">
                <h3 style="font-size: 14px; color: var(--accent); margin-bottom: 20px; font-family: 'JetBrains Mono', monospace; text-transform: uppercase;">Deliverables (Google Drive)</h3>

                <div class="field">
                    <label>Master WAV</label>
                    <input type="file" name="master_file" accept=".wav,.mp3">
                    <p class="help-text">OR enter a Drive/Public URL below</p>
                    <input type="url" name="master_url" placeholder="https://drive.google.com/file/d/...">
                </div>
                <div class="field">
                    <label>Stems ZIP</label>
                    <input type="file" name="stems_file" accept=".zip,.rar">
                    <p class="help-text">OR enter a Drive/Public URL below</p>
                    <input type="url" name="stems_url" placeholder="https://drive.google.com/file/d/...">
                </div>
                <div class="field">
                    <label>License PDF</label>
                    <input type="file" name="license_file" accept=".pdf">
                    <p class="help-text">OR enter a Drive/Public URL below</p>
                    <input type="url" name="license_url" placeholder="https://drive.google.com/file/d/...">
                </div>

                <hr style="border: 0; border-top: 1px solid var(--line); margin: 32px 0;">
                <h3 style="font-size: 14px; color: var(--accent); margin-bottom: 20px; font-family: 'JetBrains Mono', monospace; text-transform: uppercase;">Public Preview</h3>

                <div class="field">
                    <label>Preview Audio (MP3)</label>
                    <input type="file" name="preview_file" accept="audio/*">
                    <p style="font-size: 11px; color: var(--ink-mute); margin-top: 4px;">OR enter a public URL below</p>
                </div>
                <div class="field">
                    <label>Preview URL</label>
                    <input type="url" name="preview_url" placeholder="https://...">
                </div>

                <div class="actions" style="margin-top: 40px;">
                    <a href="index.php" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">Go Live →</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Duration detection
        document.querySelector('input[name="preview_file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const audio = new Audio();
                audio.src = URL.createObjectURL(file);
                audio.onloadedmetadata = () => {
                    const m = Math.floor(audio.duration / 60);
                    const s = Math.floor(audio.duration % 60);
                    document.querySelector('input[name="duration"]').value = `${m}:${s.toString().padStart(2, '0')}`;
                };
            }
        });
    </script>
</body>
</html>

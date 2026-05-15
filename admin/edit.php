<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Storage.php';

use BAF\Core;
use BAF\Storage;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index');
    exit;
}

$db = $core->db();
$stmt = $db->prepare("SELECT * FROM beats WHERE id = ?");
$stmt->execute([$id]);
$beat = $stmt->fetch();

if (!$beat) {
    die("Beat not found.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new \Exception("Security check failed.");
        }

        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        // Extend execution time for large file uploads
        set_time_limit(1800);
        
        $title = $_POST['title'] ?? '';
        $starting = $_POST['starting_bid'] ?? 0;
        $bpm = $_POST['bpm'] ?? 0;
        $key = $_POST['key_sig'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $duration = $_POST['duration'] ?? '';

        if (empty($title)) {
            throw new \Exception("Please fill in the title.");
        }

        $storage = new Storage();
        $update_data = [
            'title' => strtoupper($title),
            'bpm' => $bpm,
            'key_sig' => $key,
            'genre' => $genre,
            'duration' => $duration,
            'starting_bid' => $starting,
        ];

        // Main audio
        if (!empty($_FILES['audio']['name'])) {
            if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $err_code = $_FILES['audio']['error'];
                $msg = "Error uploading audio file.";
                if ($err_code === UPLOAD_ERR_INI_SIZE) $msg .= " File exceeds server limit.";
                throw new \Exception($msg);
            }
            // Delete old local file if exists
            if (!empty($beat['audio_path'])) {
                $old_path = $storage->get_file_path($beat['audio_path']);
                if (file_exists($old_path)) @unlink($old_path);
            }
            $update_data['audio_path'] = $storage->upload_audio($_FILES['audio']);
            $update_data['audio_url'] = null;
        } elseif (!empty($_POST['audio_url'])) {
            $url = filter_var($_POST['audio_url'], FILTER_VALIDATE_URL);
            if (!$url) throw new \Exception("Invalid audio URL.");
            $update_data['audio_url'] = $url;
            $update_data['audio_path'] = null;
        }

        // Sample
        if (!empty($_FILES['sample']['name'])) {
            if ($_FILES['sample']['error'] === UPLOAD_ERR_OK) {
                if (!empty($beat['sample_path'])) {
                    $old_path = $storage->get_file_path($beat['sample_path']);
                    if (file_exists($old_path)) @unlink($old_path);
                }
                $update_data['sample_path'] = $storage->upload_audio($_FILES['sample']);
                $update_data['sample_url'] = null;
            }
        } elseif (!empty($_POST['sample_url'])) {
            $url = filter_var($_POST['sample_url'], FILTER_VALIDATE_URL);
            if (!$url) throw new \Exception("Invalid sample URL.");
            $update_data['sample_url'] = $url;
            $update_data['sample_path'] = null;
        }

        // Stems
        if (!empty($_FILES['stems']['name'])) {
            if ($_FILES['stems']['error'] === UPLOAD_ERR_OK) {
                if (!empty($beat['stems_path'])) {
                    $old_path = $storage->get_file_path($beat['stems_path']);
                    if (file_exists($old_path)) @unlink($old_path);
                }
                $update_data['stems_path'] = $storage->upload_audio($_FILES['stems']);
                $update_data['stems_url'] = null;
            }
        } elseif (!empty($_POST['stems_url'])) {
            $url = filter_var($_POST['stems_url'], FILTER_VALIDATE_URL);
            if (!$url) throw new \Exception("Invalid stems URL.");
            $update_data['stems_url'] = $url;
            $update_data['stems_path'] = null;
        }

        // Build SQL
        $sql = "UPDATE beats SET ";
        $params = [];
        foreach ($update_data as $col => $val) {
            $sql .= "`$col` = ?, ";
            $params[] = $val;
        }
        $sql = rtrim($sql, ', ');
        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $success = "Beat \"$title\" has been updated!";
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $success]);
            exit;
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
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
        <div class="admin-banner"><span style="width:6px; height:6px; border-radius:50%; background:var(--accent); display:inline-block;"></span> Admin · Editing beat</div>
        <h2>Edit Beat: <?php echo Core::escape($beat['title']); ?></h2>
        
        <div style="font-size: 11px; color: var(--ink-mute); margin-bottom: 20px; padding: 10px; background: var(--bg-2); border-radius: 8px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Server Max Upload: <b><?php echo ini_get('upload_max_filesize'); ?></b> / Post Limit: <b><?php echo ini_get('post_max_size'); ?></b>
        </div>

        <?php if ($error): ?><div style="color:var(--danger); margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Main Audio File (HQ)</label>
                    <input type="file" name="audio" accept="audio/*">
                    <small style="color: var(--ink-mute);"><?php echo $beat['audio_path'] ? 'Current: ' . $beat['audio_path'] : 'No local file'; ?></small>
                </div>
                <div class="field">
                    <label>OR Audio URL</label>
                    <input type="url" name="audio_url" value="<?php echo Core::escape($beat['audio_url']); ?>" placeholder="https://example.com/audio.mp3">
                </div>
                <div class="field" style="opacity: 0.5; pointer-events: none;">
                    <label>&nbsp;</label>
                    <small style="color: var(--ink-mute);">Upload OR paste URL</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Sample Audio (optional)</label>
                    <input type="file" name="sample" accept="audio/*">
                    <small style="color: var(--ink-mute);"><?php echo $beat['sample_path'] ? 'Current: ' . $beat['sample_path'] : 'No local file'; ?></small>
                </div>
                <div class="field">
                    <label>OR Sample URL</label>
                    <input type="url" name="sample_url" value="<?php echo Core::escape($beat['sample_url']); ?>" placeholder="https://example.com/sample.mp3">
                </div>
                <div class="field" style="opacity: 0.5; pointer-events: none;">
                    <label>&nbsp;</label>
                    <small style="color: var(--ink-mute);">Upload OR paste URL</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Stems (ZIP, optional)</label>
                    <input type="file" name="stems" accept=".zip">
                    <small style="color: var(--ink-mute);"><?php echo $beat['stems_path'] ? 'Current: ' . $beat['stems_path'] : 'No local file'; ?></small>
                </div>
                <div class="field">
                    <label>OR Stems URL</label>
                    <input type="url" name="stems_url" value="<?php echo Core::escape($beat['stems_url']); ?>" placeholder="https://example.com/stems.zip">
                </div>
                <div class="field" style="opacity: 0.5; pointer-events: none;">
                    <label>&nbsp;</label>
                    <small style="color: var(--ink-mute);">Upload OR paste URL</small>
                </div>
            </div>
            
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
                        <?php
                        $keys = ['C min', 'D min', 'F# min', 'A min', 'G maj', 'E maj'];
                        foreach ($keys as $k): ?>
                            <option <?php echo $beat['key_sig'] === $k ? 'selected' : ''; ?>><?php echo $k; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="field">
                    <label>Genre</label>
                    <select name="genre">
                        <?php
                        $genres = ['Afrobeats', 'Amapiano', 'Afro-Swing', 'Afro-House', 'Afro-Fusion', 'Afro-Pop'];
                        foreach ($genres as $g): ?>
                            <option <?php echo $beat['genre'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Duration</label><input type="text" name="duration" value="<?php echo Core::escape($beat['duration']); ?>" placeholder="2:48"></div>
            </div>

            <div class="actions" style="margin-top:24px; display:flex; justify-content:flex-end; gap:12px;">
                <a href="index" class="btn btn-ghost">Cancel</a>
                <button type="submit" id="submit-btn" class="btn btn-primary">Update Beat →</button>
            </div>

            <div id="progress-container" class="progress-wrap">
                <div id="progress-bar" class="progress-fill"></div>
            </div>
            <div id="progress-status" class="progress-text" style="display:none;">Uploading: 0%</div>
        </form>
    </div>
</div>

<script>
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submit-btn');
    const progressContainer = document.getElementById('progress-container');
    const progressBar = document.getElementById('progress-bar');
    const progressStatus = document.getElementById('progress-status');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();

        submitBtn.disabled = true;
        submitBtn.innerText = 'Updating...';
        
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                progressContainer.style.display = 'block';
                progressStatus.style.display = 'block';
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressStatus.innerText = `Uploading: ${percent}%`;
            }
        });

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        window.location.href = 'index?success=' + encodeURIComponent(response.message);
                    } else {
                        throw new Error(response.message);
                    }
                } catch (err) {
                    alert('Error: ' + err.message);
                    resetUI();
                }
            } else {
                alert('Server error: ' + xhr.status);
                resetUI();
            }
        };

        xhr.send(formData);
    });

    function resetUI() {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Update Beat →';
        progressContainer.style.display = 'none';
        progressStatus.style.display = 'none';
    }
</script>

</body>
</html>

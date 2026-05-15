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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
            throw new \Exception("Security check failed.");
        }

        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        // Extend execution time for large file uploads
        set_time_limit(1800); // 30 minutes for large stems
        
        $title = $_POST['title'] ?? '';
        $starting = $_POST['starting_bid'] ?? 0;
        $bpm = $_POST['bpm'] ?? 0;
        $key = $_POST['key_sig'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $duration = $_POST['duration'] ?? '';

        if (empty($title) || $starting <= 0) {
            throw new \Exception("Please fill in title and starting bid.");
        }

        // Main audio: file OR URL (required)
        $filename = null;
        $audio_url = null;
        if (!empty($_FILES['audio']['name'])) {
            if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $err_code = $_FILES['audio']['error'];
                $msg = "Error uploading audio file.";
                if ($err_code === UPLOAD_ERR_INI_SIZE || $err_code === UPLOAD_ERR_FORM_SIZE) {
                    $msg = "Audio file is too large for the server. Check your php.ini limits.";
                }
                throw new \Exception($msg);
            }
            $storage = new Storage();
            $filename = $storage->upload_audio($_FILES['audio']);
        } elseif (!empty($_POST['audio_url'])) {
            $audio_url = filter_var($_POST['audio_url'], FILTER_VALIDATE_URL);
            if (!$audio_url) {
                throw new \Exception("Invalid audio URL provided.");
            }
        } else {
            throw new \Exception("Please upload an audio file or provide an audio URL.");
        }

        // Sample: file OR URL (optional)
        $sample_filename = null;
        $sample_url = null;
        if (isset($_FILES['sample']) && $_FILES['sample']['error'] === UPLOAD_ERR_OK) {
            $storage = new Storage();
            $sample_filename = $storage->upload_audio($_FILES['sample']);
        } elseif (!empty($_POST['sample_url'])) {
            $sample_url = filter_var($_POST['sample_url'], FILTER_VALIDATE_URL);
            if (!$sample_url) {
                throw new \Exception("Invalid sample URL provided.");
            }
        }

        // Stems: file OR URL (optional)
        $stems_filename = null;
        $stems_url = null;
        if (isset($_FILES['stems']) && $_FILES['stems']['name']) {
            if ($_FILES['stems']['error'] !== UPLOAD_ERR_OK) {
                $err_code = $_FILES['stems']['error'];
                $msg = "Error uploading stems ZIP.";
                if ($err_code === UPLOAD_ERR_INI_SIZE || $err_code === UPLOAD_ERR_FORM_SIZE) {
                    $msg = "Stems ZIP is too large for the server. Server limit: " . ini_get('upload_max_filesize');
                }
                throw new \Exception($msg);
            }
            $storage = new Storage();
            $stems_filename = $storage->upload_audio($_FILES['stems']);
        } elseif (!empty($_POST['stems_url'])) {
            $stems_url = filter_var($_POST['stems_url'], FILTER_VALIDATE_URL);
            if (!$stems_url) {
                throw new \Exception("Invalid stems URL provided.");
            }
        }

        $stmt = $core->db()->prepare("INSERT INTO beats (title, bpm, key_sig, genre, duration, starting_bid, current_bid, audio_path, audio_url, sample_path, sample_url, stems_path, stems_url, status)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'live')");
        $stmt->execute([strtoupper($title), $bpm, $key, $genre, $duration, $starting, $starting, $filename, $audio_url, $sample_filename, $sample_url, $stems_filename, $stems_url]);

        $success = "Beat \"$title\" has been listed live!";

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
    <title>Upload New Beat — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index" class="tab">Dashboard</a>
            <a href="upload" class="tab is-active">+ Upload Beat</a>
            <a href="settings" class="tab">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="panel">
        <div class="admin-banner"><span style="width:6,height:6,borderRadius:'50%',background:'var(--accent)',display:'inline-block'"></span> Admin · Listing a new drop</div>
        <h2>List a new beat</h2>
        <p class="lead">It goes live immediately. The 30-minute countdown starts on the first bid.</p>
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
                </div>
                <div class="field">
                    <label>OR Audio URL</label>
                    <input type="url" name="audio_url" placeholder="https://example.com/audio.mp3">
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
                </div>
                <div class="field">
                    <label>OR Sample URL</label>
                    <input type="url" name="sample_url" placeholder="https://example.com/sample.mp3">
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
                </div>
                <div class="field">
                    <label>OR Stems URL</label>
                    <input type="url" name="stems_url" placeholder="https://example.com/stems.zip">
                </div>
                <div class="field" style="opacity: 0.5; pointer-events: none;">
                    <label>&nbsp;</label>
                    <small style="color: var(--ink-mute);">Upload OR paste URL</small>
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
                <a href="index" class="btn btn-ghost">Cancel</a>
                <button type="submit" id="submit-btn" class="btn btn-primary">Put it live →</button>
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

    // Audio Duration Auto-detection
    document.querySelector('input[name="audio"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const audio = new Audio();
            audio.src = URL.createObjectURL(file);
            audio.addEventListener('loadedmetadata', function() {
                const duration = Math.floor(audio.duration);
                const mins = Math.floor(duration / 60);
                const secs = duration % 60;
                const formatted = `${mins}:${secs.toString().padStart(2, '0')}`;
                document.querySelector('input[name="duration"]').value = formatted;
                URL.revokeObjectURL(audio.src);
            });
        }
    });

    // AJAX Upload with Progress
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();

        // UI state
        submitBtn.disabled = true;
        submitBtn.innerText = 'Uploading...';
        progressContainer.style.display = 'block';
        progressStatus.style.display = 'block';
        
        xhr.open('POST', 'upload.php', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressStatus.innerText = `Uploading: ${percent}% (${Math.round(e.loaded/1024/1024)}MB / ${Math.round(e.total/1024/1024)}MB)`;
                
                if (percent === 100) {
                    progressStatus.innerText = 'Processing on server... please wait.';
                }
            }
        });

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        progressStatus.innerText = 'Success! Redirecting...';
                        progressStatus.style.color = 'var(--ok)';
                        progressBar.style.background = 'var(--ok)';
                        setTimeout(() => {
                            window.location.href = 'index?success=' + encodeURIComponent(response.message);
                        }, 1000);
                    } else {
                        throw new Error(response.message || 'Upload failed');
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

        xhr.onerror = function() {
            alert('Network error occurred.');
            resetUI();
        };

        xhr.send(formData);
    });

    function resetUI() {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Put it live →';
        progressContainer.style.display = 'none';
        progressStatus.style.display = 'none';
        progressBar.style.width = '0%';
    }
</script>

</body>
</html>

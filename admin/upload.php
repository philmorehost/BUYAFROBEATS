<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

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
        
        $title = $_POST['title'] ?? '';
        $starting = $_POST['starting_bid'] ?? 0;
        $bpm = $_POST['bpm'] ?? 0;
        $key = $_POST['key_sig'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $duration = $_POST['duration'] ?? '';

        $audio_drive_id = $_POST['audio_drive_id'] ?? '';
        $sample_drive_id = $_POST['sample_drive_id'] ?? '';
        $stems_drive_id = $_POST['stems_drive_id'] ?? '';

        if (empty($title) || $starting <= 0) {
            throw new \Exception("Please fill in all required fields.");
        }

        // Google Drive Integration
        require_once __DIR__ . '/../includes/GoogleDrive.php';
        $drive = new \BAF\GoogleDrive();
        $parent_folder = $core->setting('google_drive_parent_folder');

        // Create a specific folder for this auction if files are being uploaded
        // If everything is already on Drive, we might skip this, but usually we want a folder.
        $auction_folder_id = $drive->create_folder(strtoupper($title), $parent_folder);
        if (!$auction_folder_id) {
            throw new \Exception("Failed to connect to Google Drive.");
        }

        // 1. Handle Master File
        $audio_url = null;
        if (!empty($audio_drive_id)) {
            $audio_url = \BAF\GoogleDrive::get_download_link($audio_drive_id);
        } elseif (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
            $uploaded_id = $drive->upload($_FILES['audio']['tmp_name'], $title . " - MASTER.wav", $_FILES['audio']['type'], $auction_folder_id);
            if (!$uploaded_id) throw new \Exception("Master file upload failed.");
            $audio_url = \BAF\GoogleDrive::get_download_link($uploaded_id);
        } else {
            throw new \Exception("Please provide a Master Audio file (Upload or Select).");
        }

        // 2. Handle Sample
        $sample_url = null;
        if (!empty($sample_drive_id)) {
            $sample_url = \BAF\GoogleDrive::get_download_link($sample_drive_id);
        } elseif (isset($_FILES['sample']) && $_FILES['sample']['error'] === UPLOAD_ERR_OK) {
            $uploaded_id = $drive->upload($_FILES['sample']['tmp_name'], $title . " - PREVIEW.mp3", $_FILES['sample']['type'], $auction_folder_id);
            if ($uploaded_id) $sample_url = \BAF\GoogleDrive::get_download_link($uploaded_id);
        }

        // 3. Handle Stems
        $stems_url = null;
        if (!empty($stems_drive_id)) {
            $stems_url = \BAF\GoogleDrive::get_download_link($stems_drive_id);
        } elseif (isset($_FILES['stems']) && $_FILES['stems']['error'] === UPLOAD_ERR_OK) {
            $uploaded_id = $drive->upload($_FILES['stems']['tmp_name'], $title . " - STEMS.zip", $_FILES['stems']['type'], $auction_folder_id);
            if ($uploaded_id) $stems_url = \BAF\GoogleDrive::get_download_link($uploaded_id);
        }

        $stmt = $core->db()->prepare("INSERT INTO beats (title, bpm, key_sig, genre, duration, starting_bid, current_bid, audio_url, sample_url, stems_url, google_drive_folder_id, status)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'live')");
        $stmt->execute([strtoupper($title), $bpm, $key, $genre, $duration, $starting, $starting, $audio_url, $sample_url, $stems_url, $auction_folder_id]);

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
    <style>
        .drive-browser { position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 9999; }
        .drive-browser.is-visible { display: flex; }
        .drive-content { width: 600px; max-height: 80vh; background: var(--bg-2); border: 1px solid var(--line); border-radius: 24px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .drive-header { padding: 20px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; }
        .drive-list { flex: 1; overflow-y: auto; padding: 10px; }
        .drive-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; cursor: pointer; transition: background 0.2s; }
        .drive-item:hover { background: var(--bg-3); }
        .drive-item .icon { width: 32px; height: 32px; border-radius: 8px; background: color-mix(in oklab, var(--accent) 15%, transparent); display: flex; align-items: center; justify-content: center; color: var(--accent); }
        .drive-item .name { flex: 1; font-size: 14px; font-weight: 500; }
        .drive-item .type { font-size: 10px; color: var(--ink-mute); font-family: 'JetBrains Mono', monospace; }
        .select-toggle { font-size: 11px; color: var(--accent); cursor: pointer; text-decoration: underline; margin-top: 4px; display: inline-block; }
        .selected-file { margin-top: 8px; font-size: 12px; color: var(--ok); background: color-mix(in oklab, var(--ok) 10%, transparent); padding: 8px 12px; border-radius: 8px; display: none; align-items: center; gap: 8px; }
    </style>
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
        <div class="admin-banner"><span style="width:6px;height:6px;border-radius:50%;background:var(--accent);display:inline-block"></span> Admin · Listing a new drop</div>
        <h2>List a new beat</h2>
        <p class="lead">Upload files or select directly from your Google Drive.</p>

        <?php if ($error): ?><div style="color:var(--danger); margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 24px;">
                <!-- Audio Field -->
                <div class="field">
                    <label>Main Audio File (HQ)</label>
                    <div class="upload-container">
                        <input type="file" name="audio" accept="audio/*" class="file-input">
                        <input type="hidden" name="audio_drive_id" class="drive-id-input">
                        <div class="select-toggle" onclick="openDriveBrowser('audio')">or Select from Drive ↓</div>
                        <div class="selected-file"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg> <span class="fname"></span></div>
                    </div>
                </div>
                <!-- Sample Field -->
                <div class="field">
                    <label>Sample Audio (optional)</label>
                    <div class="upload-container">
                        <input type="file" name="sample" accept="audio/*" class="file-input">
                        <input type="hidden" name="sample_drive_id" class="drive-id-input">
                        <div class="select-toggle" onclick="openDriveBrowser('sample')">or Select from Drive ↓</div>
                        <div class="selected-file"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg> <span class="fname"></span></div>
                    </div>
                </div>
                <!-- Stems Field -->
                <div class="field">
                    <label>Stems (ZIP, optional)</label>
                    <div class="upload-container">
                        <input type="file" name="stems" accept=".zip" class="file-input">
                        <input type="hidden" name="stems_drive_id" class="drive-id-input">
                        <div class="select-toggle" onclick="openDriveBrowser('stems')">or Select from Drive ↓</div>
                        <div class="selected-file"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg> <span class="fname"></span></div>
                    </div>
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
                <button type="submit" class="btn btn-primary">Put it live →</button>
            </div>
        </form>
    </div>
</div>

<!-- Drive Browser Modal -->
<div id="drive-modal" class="drive-browser">
    <div class="drive-content">
        <div class="drive-header">
            <div style="font-weight:700">Select File from Google Drive</div>
            <div style="cursor:pointer" onclick="closeDriveBrowser()">✕</div>
        </div>
        <div id="drive-list" class="drive-list">
            <!-- Loaded via AJAX -->
            <div style="text-align:center; padding:40px; color:var(--ink-mute)">Loading your files...</div>
        </div>
    </div>
</div>

<script>
    let currentTarget = null;

    function openDriveBrowser(target) {
        currentTarget = target;
        document.getElementById('drive-modal').classList.add('is-visible');
        loadDriveFiles();
    }

    function closeDriveBrowser() {
        document.getElementById('drive-modal').classList.remove('is-visible');
    }

    async function loadDriveFiles(folderId = null) {
        const list = document.getElementById('drive-list');
        list.innerHTML = `<div style="text-align:center; padding:40px; color:var(--ink-mute)">Loading...</div>`;
        
        try {
            const res = await fetch(`../api/google_drive_list?folder=${folderId || ''}`);
            const data = await res.json();
            
            if (data.error) {
                list.innerHTML = `<div style="text-align:center; padding:40px; color:var(--danger)">${data.error}</div>`;
                return;
            }

            let html = '';
            if (folderId) {
                html += `
                    <div class="drive-item" onclick="loadDriveFiles()">
                        <div class="icon">⤴</div>
                        <div class="name">.. / Back to Root</div>
                    </div>
                `;
            }

            if (data.files.length === 0) {
                html += `<div style="text-align:center; padding:40px; color:var(--ink-mute)">No files found.</div>`;
            } else {
                html += data.files.map(f => {
                    const isFolder = f.mimeType === 'application/vnd.google-apps.folder';
                    const action = isFolder ? `loadDriveFiles('${f.id}')` : `selectFile('${f.id}', '${f.name}')`;
                    return `
                        <div class="drive-item" onclick="${action}">
                            <div class="icon">
                                ${isFolder ? '📁' : '📄'}
                            </div>
                            <div class="name">${f.name}</div>
                            <div class="type">${isFolder ? 'Folder' : f.mimeType.split('/').pop()}</div>
                        </div>
                    `;
                }).join('');
            }
            list.innerHTML = html;

        } catch (err) {
            list.innerHTML = `<div style="text-align:center; padding:40px; color:var(--danger)">Network error.</div>`;
        }
    }

    function selectFile(id, name) {
        const field = document.querySelector(`[name="${currentTarget}_drive_id"]`);
        const container = field.closest('.upload-container');
        
        field.value = id;
        container.querySelector('.file-input').style.display = 'none';
        container.querySelector('.select-toggle').innerText = 'Change selection';
        
        const selected = container.querySelector('.selected-file');
        selected.style.display = 'flex';
        selected.querySelector('.fname').innerText = name;
        
        closeDriveBrowser();
    }

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
                document.querySelector('input[name="duration"]').value = `${mins}:${secs.toString().padStart(2, '0')}`;
                URL.revokeObjectURL(audio.src);
            });
        }
    });
</script>

</body>
</html>

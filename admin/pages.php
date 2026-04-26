<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/CMS.php';

use BAF\Core;
use BAF\CMS;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login.php');
    exit;
}

$cms = new CMS($core);
$success = '';
$error = '';

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
        die("Security check failed.");
    }

    $data = [
        'title' => $_POST['title'],
        'slug' => $_POST['slug'],
        'content' => $_POST['content'] ?? '',
        'meta_title' => $_POST['meta_title'] ?? '',
        'meta_description' => $_POST['meta_description'] ?? '',
        'meta_keywords' => $_POST['meta_keywords'] ?? '',
        'is_external' => isset($_POST['is_external']) ? 1 : 0,
        'external_url' => $_POST['external_url'] ?? '',
        'status' => $_POST['status'] ?? 'published'
    ];

    if ($action === 'create') {
        if ($cms->create_page($data)) {
            $success = "Page created successfully!";
            $action = 'list';
        } else {
            $error = "Failed to create page. Slug might already exist.";
        }
    } elseif ($action === 'edit' && $id) {
        if ($cms->update_page($id, $data)) {
            $success = "Page updated successfully!";
            $action = 'list';
        } else {
            $error = "Failed to update page.";
        }
    }
}

if ($action === 'delete' && $id) {
    if ($cms->delete_page($id)) {
        $success = "Page deleted successfully!";
    }
    $action = 'list';
}

$pages = $cms->get_all_pages(true);
$edit_page = ($action === 'edit' && $id) ? array_filter($pages, fn($p) => $p['id'] == $id)[0] : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Manage Pages — <?php echo Core::escape($core->setting('site_title', 'BUYAFROBEATS')); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .form-grid { display: flex; gap: 24px; align-items: flex-start; }
        .form-main { flex: 1; min-width: 0; }
        .form-side { width: 340px; flex-shrink: 0; position: sticky; top: 86px; }
        @media (max-width: 1000px) {
            .form-grid { flex-direction: column; }
            .form-side { width: 100%; position: static; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="logo"><span class="dot"></span><?php echo $core->render_logo(); ?><span class="sub">/ studio</span></a>
        <div class="tabs">
            <a href="index.php" class="tab">Dashboard</a>
            <a href="pages.php" class="tab is-active">Pages</a>
            <a href="upload.php" class="tab">+ Upload Beat</a>
            <a href="settings.php" class="tab">Settings</a>
        </div>
        <div class="spacer"></div>
        <a href="logout.php" class="tab" style="font-size: 11px;">Logout</a>
    </div>
</div>

<div class="page">
    <div class="page-header">
        <h2><?php echo $action === 'list' ? 'Internal & External Pages' : ($action === 'create' ? 'Create New Page' : 'Edit Page'); ?></h2>
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn btn-primary">+ Create Page</a>
        <?php else: ?>
            <a href="pages.php" class="btn btn-ghost">← Back to List</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?><div style="color:var(--ok); margin-bottom:20px;"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div style="color:var(--danger); margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>

    <?php if ($action === 'list'): ?>
        <table class="log-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug / URL</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                    <tr>
                        <td><b><?php echo Core::escape($p['title']); ?></b></td>
                        <td class="mono" style="font-size:12px"><?php echo $p['is_external'] ? Core::escape($p['external_url']) : Core::escape($p['slug']); ?></td>
                        <td><span class="chip" style="font-size:9px"><?php echo $p['is_external'] ? 'EXTERNAL' : 'INTERNAL'; ?></span></td>
                        <td><span class="status <?php echo $p['status'] === 'published' ? 'live' : 'open'; ?>" style="font-size:9px"><?php echo strtoupper($p['status']); ?></span></td>
                        <td style="text-align:right">
                            <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn" style="font-size:10px; padding: 4px 8px;">Edit</a>
                            <a href="?action=delete&id=<?php echo $p['id']; ?>" class="btn" style="font-size:10px; padding: 4px 8px; color:var(--danger)" onclick="return confirm('Delete this page?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Core::csrf_token(); ?>">
            <div class="form-grid">
                <div class="form-main">
                    <div class="panel">
                        <div class="field">
                            <label>Page Title</label>
                            <input type="text" name="title" value="<?php echo $edit_page ? Core::escape($edit_page['title']) : ''; ?>" required>
                        </div>
                        <div class="field">
                            <label>Slug (internal only)</label>
                            <input type="text" name="slug" value="<?php echo $edit_page ? Core::escape($edit_page['slug']) : ''; ?>" placeholder="about-us">
                        </div>
                        <div class="field">
                            <label>Content (HTML allowed)</label>
                            <textarea name="content" style="height: 400px;"><?php echo $edit_page ? Core::escape($edit_page['content']) : ''; ?></textarea>
                        </div>

                        <h3 style="font-size: 14px; margin: 32px 0 16px; color: var(--accent);">SEO (Internal Pages Only)</h3>
                        <div class="field">
                            <label>Meta Title</label>
                            <input type="text" name="meta_title" value="<?php echo $edit_page ? Core::escape($edit_page['meta_title']) : ''; ?>">
                        </div>
                        <div class="field">
                            <label>Meta Description</label>
                            <textarea name="meta_description"><?php echo $edit_page ? Core::escape($edit_page['meta_description']) : ''; ?></textarea>
                        </div>
                        <div class="field">
                            <label>Meta Keywords</label>
                            <input type="text" name="meta_keywords" value="<?php echo $edit_page ? Core::escape($edit_page['meta_keywords']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-side">
                    <div class="panel">
                        <h3 style="font-size: 14px; margin-bottom: 12px; color: var(--accent);">Publishing</h3>
                        <div class="field">
                            <label>Status</label>
                            <select name="status">
                                <option value="published" <?php echo ($edit_page && $edit_page['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="draft" <?php echo ($edit_page && $edit_page['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        
                        <h3 style="font-size: 14px; margin: 24px 0 12px; color: var(--accent);">External Link</h3>
                        <div class="field">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="is_external" value="1" <?php echo ($edit_page && $edit_page['is_external']) ? 'checked' : ''; ?>>
                                Is External URL?
                            </label>
                        </div>
                        <div class="field">
                            <label>External URL</label>
                            <input type="text" name="external_url" value="<?php echo $edit_page ? Core::escape($edit_page['external_url']) : ''; ?>" placeholder="https://...">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:20px;"><?php echo $edit_page ? 'Update Page' : 'Create Page'; ?> →</button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>

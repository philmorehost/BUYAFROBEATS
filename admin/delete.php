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

try {
    $db = $core->db();
    
    // Get beat details to delete files
    $stmt = $db->prepare("SELECT * FROM beats WHERE id = ?");
    $stmt->execute([$id]);
    $beat = $stmt->fetch();
    
    if (!$beat) {
        throw new \Exception("Beat not found.");
    }

    // Delete local files if they exist
    $storage = new Storage();
    $files_to_delete = ['audio_path', 'sample_path', 'stems_path'];
    foreach ($files_to_delete as $col) {
        if (!empty($beat[$col])) {
            $path = $storage->get_file_path($beat[$col]);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }

    // Delete from database
    $stmt = $db->prepare("DELETE FROM beats WHERE id = ?");
    $stmt->execute([$id]);

    // Also delete associated bids and activity? 
    // Usually better to keep them or cascade delete if DB is set up that way.
    // Let's at least delete bids to keep data clean.
    $stmt = $db->prepare("DELETE FROM bids WHERE beat_id = ?");
    $stmt->execute([$id]);

    header('Location: index?success=' . urlencode("Beat \"{$beat['title']}\" and its files have been deleted."));
} catch (\Exception $e) {
    header('Location: index?error=' . urlencode($e->getMessage()));
}
exit;

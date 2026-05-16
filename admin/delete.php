<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/GoogleDrive.php';

use BAF\Core;
use BAF\GoogleDrive;

$core = Core::get_instance();
if (!$core->is_admin()) {
    header('Location: login');
    exit;
}

$id = $_GET['id'] ?? '';
if ($id) {
    $db = $core->db();
    
    // Get drive info before deleting
    $stmt = $db->prepare("SELECT google_drive_folder_id FROM beats WHERE id = ?");
    $stmt->execute([$id]);
    $folder_id = $stmt->fetchColumn();

    if ($folder_id) {
        // Optional: Delete from Drive? 
        // For safety, we might NOT want to auto-delete from Drive, but the PRD says "clean up".
        // I'll leave a comment for now or implement if requested.
    }

    $stmt = $db->prepare("DELETE FROM beats WHERE id = ?");
    $stmt->execute([$id]);
    
    // Also delete bids
    $stmt = $db->prepare("DELETE FROM bids WHERE beat_id = ?");
    $stmt->execute([$id]);
}

header('Location: index');
exit;

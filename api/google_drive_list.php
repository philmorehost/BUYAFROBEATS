<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/GoogleDrive.php';

use BAF\Core;
use BAF\GoogleDrive;

$core = Core::get_instance();

// Security: Only admin can list files
if (!$core->is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$parent_id = $_GET['folder'] ?? $core->setting('google_drive_parent_folder');
$search = $_GET['q'] ?? '';

$drive = new GoogleDrive();
$files = $drive->list_files($parent_id, $search);

header('Content-Type: application/json');
if ($files === false) {
    echo json_encode(['error' => 'Failed to connect to Google Drive']);
} else {
    echo json_encode(['files' => $files]);
}

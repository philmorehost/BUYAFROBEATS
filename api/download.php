<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Storage.php';

use BAF\Core;
use BAF\Storage;

$core = Core::get_instance();
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid download token.");
}

$db = $core->db();
$stmt = $db->prepare("SELECT s.*, b.audio_path, b.title FROM sales s JOIN beats b ON s.beat_id = b.id WHERE s.download_token = ?");
$stmt->execute([$token]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Download link not found or expired.");
}

// Check expiry
if (strtotime($sale['expires_at']) < time()) {
    die("This download link has expired (7-day limit).");
}

$storage = new Storage();
$storage->serve_download($sale['audio_path'], $sale['title'] . '.wav');

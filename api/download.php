<?php
require_once __DIR__ . '/../includes/Core.php';

use BAF\Core;

$core = Core::get_instance();
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Invalid download token.");
}

$db = $core->db();
$stmt = $db->prepare("SELECT s.*, b.audio_path, b.audio_url, b.stems_path, b.stems_url, b.title FROM sales s JOIN beats b ON s.beat_id = b.id WHERE s.download_token = ?");
$stmt->execute([$token]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Download link not found or expired.");
}

// Check expiry
if (strtotime($sale['expires_at']) < time()) {
    die("This download link has expired (7-day limit).");
}

$type = $_GET['type'] ?? 'audio';
$filename = '';

if ($type === 'stems') {
    if (empty($sale['stems_path']) && empty($sale['stems_url'])) {
        die("Stems not available for this beat.");
    }
    $filename = $sale['title'] . '_Stems.zip';
    $local_file = $sale['stems_path'];
    $external_url = $sale['stems_url'];
} else {
    if (empty($sale['audio_path']) && empty($sale['audio_url'])) {
        die("Main audio not available for this beat.");
    }
    $filename = $sale['title'] . '.wav';
    $local_file = $sale['audio_path'];
    $external_url = $sale['audio_url'];
}

// Serve local file
if ($local_file) {
    $file = __DIR__ . '/../uploads/' . $local_file;
    if (!file_exists($file)) {
        die("File not found on server.");
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// Serve external URL
if ($external_url) {
    $ctx = stream_context_create(['http' => ['timeout' => 30]]);
    $content = @file_get_contents($external_url, false, $ctx);

    if ($content === false) {
        die("Unable to download file from external source. Please try again later.");
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
}

die("No file available for download.");


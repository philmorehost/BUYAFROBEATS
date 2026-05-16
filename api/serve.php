<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/GoogleDrive.php';

use BAF\Core;
use BAF\GoogleDrive;

$core = Core::get_instance();
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Missing file ID");
}

$db = $core->db();
$stmt = $db->prepare("SELECT * FROM beats WHERE id = ?");
$stmt->execute([$id]);
$beat = $stmt->fetch();

if (!$beat) {
    header("HTTP/1.1 404 Not Found");
    exit("Beat not found");
}

$sample_url = $beat['sample_url'];
if (empty($sample_url)) {
    // Fallback to local if drive URL is missing
    $file_path = __DIR__ . '/../uploads/audio/' . $beat['sample_path'];
    if (!file_exists($file_path)) {
        header("HTTP/1.1 404 Not Found");
        exit("Audio file not found");
    }
    
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}

// Proxy from Google Drive
$drive_id = GoogleDrive::extract_id($sample_url);
if (!$drive_id) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid Drive URL");
}

$drive = new GoogleDrive();
$token = $drive->get_access_token();

if (!$token) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Cloud storage authentication failed");
}

$ch = curl_init("https://www.googleapis.com/drive/v3/files/$drive_id?alt=media");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);

header('Content-Type: audio/mpeg');
header('Cache-Control: public, max-age=3600');

curl_exec($ch);
curl_close($ch);

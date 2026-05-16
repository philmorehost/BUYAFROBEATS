<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/GoogleDrive.php';

use BAF\Core;
use BAF\GoogleDrive;

$core = Core::get_instance();
$id = $_GET['id'] ?? '';

// Ensure no output buffering interferes with binary audio
if (ob_get_level()) ob_end_clean();

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

$url = "https://www.googleapis.com/drive/v3/files/$drive_id?alt=media";

// Handle Range headers for seeking
$headers = ["Authorization: Bearer $token"];
if (isset($_SERVER['HTTP_RANGE'])) {
    $headers[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_BUFFERSIZE, 16384); // Larger buffer for smoother streaming
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10s connection timeout
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 min max stream time

// Forward response headers from Google
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
    $len = strlen($header);
    $header_clean = trim($header);
    if (stripos($header_clean, 'Content-Type:') === 0 || 
        stripos($header_clean, 'Content-Length:') === 0 || 
        stripos($header_clean, 'Content-Range:') === 0 || 
        stripos($header_clean, 'Accept-Ranges:') === 0 ||
        stripos($header_clean, 'HTTP/') === 0) {
        header($header_clean);
    }
    return $len;
});

curl_exec($ch);
curl_close($ch);

<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/GoogleDrive.php';

use BAF\Core;
use BAF\GoogleDrive;

$core = Core::get_instance();
$token = $_GET['token'] ?? '';
$type = $_GET['type'] ?? 'master'; // master, stems, license

set_time_limit(0); // Allow long-running downloads

if (empty($token)) {
    die("Invalid download token.");
}

$db = $core->db();
$stmt = $db->prepare("SELECT s.*, b.title, b.audio_url, b.stems_url, b.license_url, b.audio_path, b.stems_path 
                      FROM sales s 
                      JOIN beats b ON s.beat_id = b.id 
                      WHERE s.download_token = ?");
$stmt->execute([$token]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Download link not found or expired.");
}

// Check expiry (24-hour limit for exclusivity)
if (strtotime($sale['expires_at']) < time()) {
    die("This download link has expired. Downloads are available for 24 hours after purchase.");
}

$target_url = null;
$local_path = null;
$filename = $sale['title'];

if ($type === 'stems') {
    $target_url = $sale['stems_url'];
    $local_path = $sale['stems_path'] ? __DIR__ . '/../uploads/audio/' . $sale['stems_path'] : null;
    $filename .= '_Stems.zip';
} elseif ($type === 'license') {
    // Redirect to the dynamic signed license viewer
    header("Location: ../license?id=" . $sale['delivery_id'] . "&token=" . $sale['download_token']);
    exit;
} else {
    $target_url = $sale['audio_url'];
    $local_path = $sale['audio_path'] ? __DIR__ . '/../uploads/audio/' . $sale['audio_path'] : null;
    $filename .= '.wav';
}

if ($target_url) {
    // Proxy from Google Drive
    $drive_id = GoogleDrive::extract_id($target_url);
    if ($drive_id) {
        $drive = new GoogleDrive();
        $token = $drive->get_access_token();
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $ch = curl_init("https://www.googleapis.com/drive/v3/files/$drive_id?alt=media");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_exec($ch);
        curl_close($ch);
        exit;
    }
}

// Fallback to local if drive fails or no URL
if ($local_path && file_exists($local_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($local_path));
    readfile($local_path);
    exit;
}

die("File not available.");

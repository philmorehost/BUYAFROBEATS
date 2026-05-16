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
    http_response_code(400);
    die("Invalid request.");
}

$db = $core->db();
$stmt = $db->prepare("SELECT s.*, b.title, b.master_url, b.stems_url, b.license_url, b.audio_path, b.stems_path 
                      FROM sales s 
                      JOIN beats b ON s.beat_id = b.id 
                      WHERE s.download_token = ?");
$stmt->execute([$token]);
$sale = $stmt->fetch();

if (!$sale) {
    http_response_code(404);
    die("Download link not found.");
}

// 24-hour window check
if (strtotime($sale['expires_at']) < time()) {
    http_response_code(403);
    die("This download link has expired (24-hour exclusivity window closed).");
}

// Authorization check (Ensure payment is completed)
if ($sale['payment_status'] !== 'completed') {
    http_response_code(403);
    die("Payment not yet confirmed.");
}

$file_url = '';
$file_path = '';
$ext = '';

switch ($type) {
    case 'stems':
        $file_url = $sale['stems_url'];
        $file_path = $sale['stems_path'];
        $ext = 'zip';
        break;
    case 'license':
        $file_url = $sale['license_url'];
        $ext = 'pdf';
        break;
    default:
        $file_url = $sale['master_url'];
        $file_path = $sale['audio_path'];
        $ext = 'wav';
        break;
}

$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sale['title']) . "_{$type}.{$ext}";

// Handle Local Files
if (!empty($file_path)) {
    $full_path = __DIR__ . '/../uploads/' . $file_path;
    if (file_exists($full_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($full_path));
        readfile($full_path);
        exit;
    }
}

// Handle Google Drive / External URLs
if (!empty($file_url)) {
    $drive_id = GoogleDrive::extract_id($file_url);
    
    if ($drive_id) {
        // Obfuscated Drive Download via Proxy
        $drive = new GoogleDrive();
        $download_url = $drive->get_download_link($drive_id);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $ch = curl_init($download_url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Stream directly to output
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 8192);
        
        // Pass through content-length if possible
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
            if (stripos($header, 'Content-Length:') === 0) {
                header($header);
            }
            return strlen($header);
        });

        curl_exec($ch);
        curl_close($ch);
        exit;
    } else {
        // Direct redirect for other external links (less secure but compatible)
        header("Location: $file_url");
        exit;
    }
}

die("No file available.");


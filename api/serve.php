<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();

$beat_id = $_GET['beat_id'] ?? null;
$file_type = $_GET['type'] ?? 'audio'; // audio, sample, stems
$download = isset($_GET['download']); // Force download instead of stream

if (!$beat_id || !in_array($file_type, ['audio', 'sample', 'stems'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

try {
    $stmt = $core->db()->prepare("SELECT * FROM beats WHERE id = ?");
    $stmt->execute([$beat_id]);
    $beat = $stmt->fetch();

    if (!$beat) {
        header('HTTP/1.1 404 Not Found');
        exit('Beat not found');
    }

    if ($beat['status'] !== 'live') {
        if ($beat['status'] === 'sold') {
            // Check if sold more than 24 hours ago
            if (strtotime($beat['ends_at']) < strtotime('-24 hours')) {
                header('HTTP/1.1 410 Gone');
                exit('Beat has vanished from our servers (24h exclusivity policy)');
            }
        } else {
            header('HTTP/1.1 410 Gone');
            exit('Beat is no longer available');
        }
    }

    // Determine which file/URL to serve
    $local_path_col = $file_type . '_path';
    $url_col = $file_type . '_url';

    $local_path = $beat[$local_path_col];
    $external_url = $beat[$url_col];

    if (!$local_path && !$external_url) {
        header('HTTP/1.1 404 Not Found');
        exit('File not available');
    }

    // Get filename for Content-Disposition
    $filename = '';
    if ($file_type === 'audio') {
        $filename = strtoupper($beat['title']) . '.mp3';
    } elseif ($file_type === 'sample') {
        $filename = strtoupper($beat['title']) . '_sample.mp3';
    } elseif ($file_type === 'stems') {
        $filename = strtoupper($beat['title']) . '_stems.zip';
    }

    // Serve local file
    if ($local_path) {
        $file = __DIR__ . '/../uploads/' . $local_path;
        if (!file_exists($file)) {
            header('HTTP/1.1 404 Not Found');
            exit('File not found');
        }

        $mime = 'application/octet-stream';
        if (preg_match('/\.mp3$/i', $local_path)) {
            $mime = 'audio/mpeg';
        } elseif (preg_match('/\.wav$/i', $local_path)) {
            $mime = 'audio/wav';
        } elseif (preg_match('/\.(aif|aiff)$/i', $local_path)) {
            $mime = 'audio/x-aiff';
        } elseif (preg_match('/\.zip$/i', $local_path)) {
            $mime = 'application/zip';
        }

        if ($download) {
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: public, max-age=3600');
        header('Accept-Ranges: bytes');
        readfile($file);
        exit;
    }

    // Proxy external URL (users never see the real URL)
    if ($external_url) {
        $mime = 'application/octet-stream';
        $ext = strtolower(pathinfo($external_url, PATHINFO_EXTENSION));
        if ($ext === 'mp3') {
            $mime = 'audio/mpeg';
        } elseif ($ext === 'wav') {
            $mime = 'audio/wav';
        } elseif (in_array($ext, ['aif', 'aiff'])) {
            $mime = 'audio/x-aiff';
        } elseif ($ext === 'zip') {
            $mime = 'application/zip';
        }

        if ($download) {
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        }

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=3600');
        
        // Use streaming instead of file_get_contents to save memory
        $ctx = stream_context_create(['http' => ['timeout' => 60]]);
        $handle = @fopen($external_url, 'rb', false, $ctx);
        
        if ($handle === false) {
            header('HTTP/1.1 503 Service Unavailable');
            exit('Unable to fetch file from external source');
        }

        // Try to get content length if available
        $meta = stream_get_meta_data($handle);
        if (isset($meta['wrapper_data'])) {
            foreach ($meta['wrapper_data'] as $header) {
                if (stripos($header, 'Content-Length:') === 0) {
                    header($header);
                }
            }
        }

        // Stream the file in 8KB chunks
        while (!feof($handle)) {
            echo fread($handle, 8192);
        }
        fclose($handle);
        exit;
    }
} catch (\Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Server error');
}

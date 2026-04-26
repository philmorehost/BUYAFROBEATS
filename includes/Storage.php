<?php
namespace BAF;

class Storage {
    private $upload_dir;

    public function __construct() {
        $this->upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    public function upload_audio($file) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed = ['wav', 'mp3', 'aif', 'aiff'];
        
        if (!in_array(strtolower($ext), $allowed)) {
            throw new \Exception("Invalid file type. Allowed: " . implode(', ', $allowed));
        }

        if ($file['size'] > 100 * 1024 * 1024) { // 100MB limit
            throw new \Exception("File is too large. Max 100MB.");
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $target = $this->upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $filename;
        }

        throw new \Exception("Failed to save uploaded file.");
    }

    public function get_file_path($filename) {
        return $this->upload_dir . $filename;
    }

    public function serve_download($filename, $friendly_name) {
        $path = $this->get_file_path($filename);
        if (file_exists($path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($friendly_name) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($path));
            readfile($path);
            exit;
        }
    }
}

<?php
namespace BAF;

class GoogleDrive {
    private $client_id;
    private $client_secret;
    private $refresh_token;

    public function __construct() {
        $core = Core::get_instance();
        $this->client_id = $core->setting('google_drive_client_id');
        $this->client_secret = $core->setting('google_drive_client_secret');
        $this->refresh_token = $core->setting('google_drive_refresh_token');
    }

    /**
     * Uploads a file to Google Drive.
     * Returns file ID on success, false on failure.
     */
    public function upload($file_path, $name, $mime_type, $parent_id = null) {
        $token = $this->get_access_token();
        if (!$token) return false;

        $metadata = ['name' => $name];
        if ($parent_id) {
            $metadata['parents'] = [$parent_id];
        }

        $boundary = '-------' . md5(time());
        $data = "--$boundary\r\n" .
                "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
                json_encode($metadata) . "\r\n" .
                "--$boundary\r\n" .
                "Content-Type: $mime_type\r\n\r\n" .
                file_get_contents($file_path) . "\r\n" .
                "--$boundary--";

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: multipart/related; boundary=$boundary",
            "Content-Length: " . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200) {
            $result = json_decode($response, true);
            return $result['id'] ?? false;
        }

        return false;
    }

    /**
     * Create a folder on Google Drive.
     * Returns Folder ID on success, false on failure.
     */
    public function create_folder($name, $parent_id = null) {
        $token = $this->get_access_token();
        if (!$token) return false;

        $metadata = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ];
        if ($parent_id) {
            $metadata['parents'] = [$parent_id];
        }

        $ch = curl_init('https://www.googleapis.com/drive/v3/files');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200) {
            $result = json_decode($response, true);
            return $result['id'] ?? false;
        }

        return false;
    }

    /**
     * Gets or refreshes the OAuth access token.
     */
    private function get_access_token() {
        if (!$this->client_id || !$this->client_secret || !$this->refresh_token) return false;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token,
            'grant_type' => 'refresh_token'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['access_token'] ?? false;
    }

    /**
     * Converts a standard Google Drive share link to a direct download link.
     */
    public static function to_direct_link($url) {
        $id = self::extract_id($url);
        return $id ? self::get_download_link($id) : $url;
    }

    /**
     * Extract ID from various Google Drive URL formats.
     */
    public static function extract_id($url) {
        if (preg_match('/[-\\w]{25,}/', $url, $matches)) {
            return $matches[0];
        }
        return false;
    }

    /**
     * Generates a direct download link for a file ID.
     */
    public static function get_download_link($id) {
        return "https://www.googleapis.com/drive/v3/files/$id?alt=media";
    }
}

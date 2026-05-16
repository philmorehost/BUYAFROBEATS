<?php
namespace BAF;

class GoogleDrive {
    private $api_key;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $core;

    public function __construct() {
        $this->core = Core::get_instance();
        $this->api_key = $this->core->setting('google_drive_api_key');
        $this->client_id = $this->core->setting('google_drive_client_id');
        $this->client_secret = $this->core->setting('google_drive_client_secret');
    }

    /**
     * Converts a standard Google Drive share link to a direct download link.
     */
    public static function to_direct_link($url) {
        $id = self::extract_id($url);
        return $id ? self::get_download_link($id) : $url;
    }

    /**
     * Returns a direct download link for a given File ID.
     */
    public static function get_download_link($id) {
        return "https://drive.google.com/uc?export=download&id=" . $id;
    }

    /**
     * Extracts File ID from a Drive URL.
     */
    public static function extract_id($url) {
        if (preg_match('/[-\w]{25,}/', $url, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Shares a file with a specific email address for a limited time (7 days).
     * Requires OAuth access token with Drive scope.
     */
    public function share_file($file_id, $email, $days = 7) {
        $token = $this->get_access_token();
        if (!$token) return false;

        $expiration = date('c', strtotime("+$days days"));
        
        $ch = curl_init("https://www.googleapis.com/drive/v3/files/{$file_id}/permissions");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        
        $data = [
            'role' => 'reader',
            'type' => 'user',
            'emailAddress' => $email,
            'expirationTime' => $expiration
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status === 200;
    }

    /**
     * Upload a file directly to Google Drive.
     * Returns File ID on success, false on failure.
     */
    public function upload_file($file_path, $filename, $mime_type, $parent_id = null) {
        $token = $this->get_access_token();
        if (!$token) return false;

        $metadata = ['name' => $filename];
        if ($parent_id) $metadata['parents'] = [$parent_id];

        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart';
        $boundary = '-------' . md5(time());
        
        $data = "--$boundary\r\n" .
                "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
                json_encode($metadata) . "\r\n" .
                "--$boundary\r\n" .
                "Content-Type: $mime_type\r\n\r\n" .
                file_get_contents($file_path) . "\r\n" .
                "--$boundary--";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: multipart/related; boundary=$boundary",
            "Content-Length: " . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200) {
            $res = json_decode($response, true);
            return $res['id'] ?? false;
        }
        return false;
    }

    /**
     * Revokes all expired permissions for a file.
     */
    public function cleanup_permissions($file_id) {
        // This is typically handled automatically by Google if expirationTime is set,
        // but can be manually triggered if needed.
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
        if ($parent_id) $metadata['parents'] = [$parent_id];

        $ch = curl_init('https://www.googleapis.com/drive/v3/files');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metadata));

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 200) {
            $res = json_decode($response, true);
            return $res['id'] ?? false;
        }
        return false;
    }

    /**
     * Gets or refreshes the OAuth access token.
     * Uses refresh token stored in settings.
     */
    private function get_access_token() {
        if ($this->access_token) return $this->access_token;

        $refresh_token = $this->core->setting('google_drive_refresh_token');
        if (!$refresh_token) return null;

        $ch = curl_init("https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ]));
        
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        if (isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            return $this->access_token;
        }

        return null;
    }
}

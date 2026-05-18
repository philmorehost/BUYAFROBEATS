<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();

// Only admin can initiate this
if (!$core->is_admin()) {
    die("Unauthorized.");
}

$client_id = $core->setting('google_drive_client_id');
if (!$client_id) {
    die("Please enter your Google Drive Client ID in settings first.");
}

$redirect_uri = $core->get_site_url() . '/api/google_drive_callback';

$params = [
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/drive.file',
    'access_type' => 'offline',
    'prompt' => 'consent' // Force consent to ensure we get a refresh token
];

$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);

header("Location: $auth_url");
exit;

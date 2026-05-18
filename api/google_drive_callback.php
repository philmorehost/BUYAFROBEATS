<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();

// 1. Check if admin is still logged in
if (!$core->is_admin()) {
    die("Unauthorized session.");
}

$code = $_GET['code'] ?? null;
if (!$code) {
    die("Authorization failed or denied.");
}

$client_id = $core->setting('google_drive_client_id');
$client_secret = $core->setting('google_drive_client_secret');

if (!$client_id || !$client_secret) {
    die("Google Drive Client ID or Secret missing in settings.");
}

// 2. Exchange code for tokens
$redirect_uri = $core->get_site_url() . '/api/google_drive_callback';

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $code,
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$token_data = json_decode($response, true);

if (!isset($token_data['refresh_token'])) {
    // If no refresh token, it might be because the user already authorized.
    // We forced 'consent' in the auth URL, so this shouldn't happen unless something is wrong.
    die("Failed to retrieve Refresh Token. Please try again or disconnect the app from your Google account first. Error: " . ($token_data['error_description'] ?? 'Unknown'));
}

// 3. Save the refresh token
$db = $core->db();
$stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES ('google_drive_refresh_token', ?) ON DUPLICATE KEY UPDATE `value` = ?");
$stmt->execute([$token_data['refresh_token'], $token_data['refresh_token']]);

// 4. Redirect back to settings
header("Location: " . $core->get_site_url() . "/admin/settings?success=1#storage");
exit;

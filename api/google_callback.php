<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();

// 1. Get code from Google
$code = $_GET['code'] ?? null;
if (!$code) {
    die("Authorization failed.");
}

$client_id = $core->setting('google_client_id');
$client_secret = $core->setting('google_client_secret');

if (!$client_id || !$client_secret) {
    die("Google Auth not configured.");
}

// 2. Exchange code for access token
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$redirect_uri = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/google_callback.php';

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

if (!isset($token_data['access_token'])) {
    die("Token exchange failed: " . ($token_data['error_description'] ?? 'Unknown error'));
}

// 3. Get user info
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_info_raw = curl_exec($ch);
$user_info = json_decode($user_info_raw, true);

if (!isset($user_info['email'])) {
    die("Failed to retrieve user email.");
}

// 4. Find or Create User
$email = $user_info['email'];
$name = $user_info['name'] ?? explode('@', $email)[0];
$username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));

$stmt = $core->db()->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Check if username taken
    $stmt = $core->db()->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        $username .= rand(100, 999);
    }

    $stmt = $core->db()->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->execute([$username, $email, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);

    $stmt = $core->db()->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
}

// 5. Login
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['username'] = $user['username'];

header('Location: ../admin/index.php');
exit;

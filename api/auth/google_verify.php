<?php
require_once __DIR__ . '/../../includes/Core.php';

use BAF\Core;

header('Content-Type: application/json');

$core = Core::get_instance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$credential = $_POST['credential'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

if (!Core::verify_csrf($csrf_token)) {
    echo json_encode(['success' => false, 'error' => 'Security check failed.']);
    exit;
}

// Google ID Token Verification
// Note: In a real production environment, you should use the Google Auth Library for PHP.
// For this refactor, we will use the Google Token Info endpoint for verification.
$ch = curl_init('https://oauth2.googleapis.com/tokeninfo?id_token=' . $credential);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$info = json_decode($response, true);

if (isset($info['error_description']) || !isset($info['email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid Google Token.']);
    exit;
}

$email = strtolower($info['email']);
$full_name = $info['name'] ?? 'User';
$google_id = $info['sub'];

// Check if user exists
$stmt = $core->db()->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Create new user
    // Generate @handle from name
    $handle = '@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $full_name)[0])) . rand(100, 999);
    
    $stmt = $core->db()->prepare("INSERT INTO users (email, google_id, handle, full_name, created_at) VALUES (?, ?, ?, ?, UTC_TIMESTAMP())");
    $stmt->execute([$email, $google_id, $handle, $full_name]);
    $user_id = $core->db()->lastInsertId();
    $user_handle = $handle;
} else {
    $user_id = $user['id'];
    $user_handle = $user['handle'];
    
    // Update Google ID if not set
    if (empty($user['google_id'])) {
        $stmt = $core->db()->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $stmt->execute([$google_id, $user_id]);
    }
}

// Set session
$_SESSION['user_id'] = $user_id;
$_SESSION['user_email'] = $email;
$_SESSION['user_handle'] = $user_handle;
$_SESSION['is_admin'] = (int)$user['is_admin'];

echo json_encode(['success' => true]);

<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

header('Content-Type: application/json');

$core = Core::get_instance();
$auction = new Auction($core);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Security check failed.']);
    exit;
}

$beat_id = $_POST['beat_id'] ?? null;
$handle = $_POST['handle'] ?? '';
$email = $_POST['email'] ?? '';
$amount = $_POST['amount'] ?? 0;
$honeypot = $_POST['website_url'] ?? ''; // Hidden field
$ip = $_SERVER['REMOTE_ADDR'];

if (!empty($honeypot)) {
    echo json_encode(['success' => false, 'error' => 'Bot detected.']);
    exit;
}

// Rate Limiting: Max 3 bids per 60 seconds per IP
$stmt = $core->db()->prepare("SELECT COUNT(*) FROM bids WHERE ip_address = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)");
$stmt->execute([$ip]);
$recent_bids = $stmt->fetchColumn();

if ($recent_bids >= 3) {
    echo json_encode(['success' => false, 'error' => 'Too many bid attempts. Please wait a minute.']);
    exit;
}

if (!$beat_id || empty($handle) || empty($email) || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

// Clean handle
if (strpos($handle, '@') !== 0) {
    $handle = '@' . $handle;
}
$handle = strtolower($handle);

// Get previous bidder info before placing new bid
$stmt = $core->db()->prepare("SELECT top_bidder, current_bid FROM beats WHERE id = ?");
$stmt->execute([$beat_id]);
$prev = $stmt->fetch();

$result = $auction->place_bid($beat_id, $handle, $email, $amount, $ip);

if ($result['success'] && !empty($prev['top_bidder']) && $prev['top_bidder'] !== $handle) {
    // Send outbid notification
    $stmt = $core->db()->prepare("SELECT bidder_email FROM bids WHERE beat_id = ? AND bidder_handle = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$beat_id, $prev['top_bidder']]);
    $prev_email = $stmt->fetchColumn();
    
    if ($prev_email) {
        require_once __DIR__ . '/../includes/Email.php';
        $email_svc = new \BAF\Email($core);
        $email_svc->notify_outbid($prev_email, 'Beat Auction', $amount);
    }
}

echo json_encode($result);

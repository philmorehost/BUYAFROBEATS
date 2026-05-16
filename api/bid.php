<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Auction.php';

use BAF\Core;
use BAF\Auction;

header('Content-Type: application/json');

$core = Core::get_instance();
$auction = new Auction($core);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Security check failed.']);
    exit;
}

// PRD: Authentication Required for Bidding
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'AUTH_REQUIRED', 'message' => 'Please sign in to place a bid.']);
    exit;
}

$beat_id = $_POST['beat_id'] ?? null;
$handle = $_SESSION['user_handle'] ?? '';
$email = $_SESSION['user_email'] ?? '';
$amount = (float)($_POST['amount'] ?? 0);
$ip = $_SERVER['REMOTE_ADDR'];

if (!$beat_id || empty($handle) || empty($email) || $amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Incomplete bid data.']);
    exit;
}

// Rate Limiting: Max 5 bids per minute per User
$stmt = $core->db()->prepare("SELECT COUNT(*) FROM bids WHERE bidder_handle = ? AND created_at > (UTC_TIMESTAMP() - INTERVAL 1 MINUTE)");
$stmt->execute([$handle]);
$recent_bids = $stmt->fetchColumn();

if ($recent_bids >= 5) {
    echo json_encode(['success' => false, 'error' => 'Too many bid attempts. Slow down!']);
    exit;
}

// Get previous bidder info before placing new bid
$stmt = $core->db()->prepare("SELECT title, top_bidder, current_bid FROM beats WHERE id = ?");
$stmt->execute([$beat_id]);
$beat_info = $stmt->fetch();

if (!$beat_info) {
    echo json_encode(['success' => false, 'error' => 'Beat not found.']);
    exit;
}

$result = $auction->place_bid($beat_id, $handle, $email, $amount, $ip);

if ($result['success'] && !empty($beat_info['top_bidder']) && $beat_info['top_bidder'] !== $handle) {
    // Send outbid notification to the previous top bidder
    $stmt = $core->db()->prepare("SELECT bidder_email FROM bids WHERE beat_id = ? AND bidder_handle = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$beat_id, $beat_info['top_bidder']]);
    $prev_email = $stmt->fetchColumn();
    
    if ($prev_email) {
        require_once __DIR__ . '/../includes/Email.php';
        $email_svc = new \BAF\Email($core);
        $email_svc->notify_outbid($prev_email, $beat_info['title'], $amount);
    }
}

echo json_encode($result);

<?php
require_once __DIR__ . '/../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();
header('Content-Type: application/json');

$beat_id = $_GET['id'] ?? 0;

if (!$beat_id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$stmt = $core->db()->prepare("SELECT bidder_handle, amount, created_at FROM bids WHERE beat_id = ? ORDER BY amount DESC LIMIT 20");
$stmt->execute([$beat_id]);
$bids = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo json_encode(['bids' => $bids]);

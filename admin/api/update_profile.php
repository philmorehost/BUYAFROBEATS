<?php
require_once __DIR__ . '/../../includes/Core.php';
use BAF\Core;

header('Content-Type: application/json');
$core = Core::get_instance();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Core::verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $email_notifications = isset($_POST['email_notifications']) ? (int)$_POST['email_notifications'] : 1;
    
    $stmt = $core->db()->prepare("UPDATE users SET email_notifications = ? WHERE id = ?");
    if ($stmt->execute([$email_notifications, $_SESSION['user_id']])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

<?php
require_once __DIR__ . '/../../includes/Core.php';
use BAF\Core;

$core = Core::get_instance();

header('Content-Type: application/json');

if (!$core->is_admin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!Core::verify_csrf($token)) {
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }

    $db = $core->db();
    
    // Batch Update Support
    $settings = $_POST['settings'] ?? null;
    if ($settings && is_string($settings)) {
        $settings = json_decode($settings, true);
    }

    if (is_array($settings)) {
        try {
            $db->beginTransaction();
            $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
            foreach ($settings as $key => $value) {
                if (!empty($key)) {
                    $stmt->execute([$key, $value, $value]);
                }
            }
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Batch settings updated']);
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // Single Update (Legacy/Auto-save)
    $key = $_POST['key'] ?? '';
    $value = $_POST['value'] ?? '';

    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'Missing key']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$key, $value, $value]);
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
}

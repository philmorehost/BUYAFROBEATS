<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Plisio.php';
require_once __DIR__ . '/../includes/Email.php';

use BAF\Core;
use BAF\Plisio;
use BAF\Email;

$core = Core::get_instance();
$plisio_api_key = $core->setting('plisio_api_key');

if (empty($plisio_api_key)) {
    http_response_code(500);
    exit;
}

$plisio = new Plisio($plisio_api_key);

// Plisio sends data via POST
$data = $_POST;

if ($plisio->verify_callback($data)) {
    $order_number = $data['order_number'];
    $status = $data['status'];

    $stmt = $core->db()->prepare("SELECT s.*, b.title, b.audio_path FROM sales s JOIN beats b ON s.beat_id = b.id WHERE s.delivery_id = ?");
    $stmt->execute([$order_number]);
    $sale = $stmt->fetch();

    if ($sale && $sale['payment_status'] !== 'completed') {
        if ($status === 'completed' || $status === 'mismatch') {
            // Update status
            $stmt = $core->db()->prepare("UPDATE sales SET payment_status = 'completed' WHERE id = ?");
            $stmt->execute([$sale['id']]);

            // Send receipt and download link
            $email_svc = new Email($core);
            $email_svc->send_payment_receipt($sale);
        } elseif ($status === 'expired' || $status === 'error') {
            $stmt = $core->db()->prepare("UPDATE sales SET payment_status = ? WHERE id = ?");
            $stmt->execute([$status, $sale['id']]);
        }
    }
}

http_response_code(200);
echo "OK";

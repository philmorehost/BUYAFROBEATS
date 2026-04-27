<?php
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/Plisio.php';

use BAF\Core;
use BAF\Plisio;

$core = Core::get_instance();
$delivery_id = $_GET['id'] ?? '';

$stmt = $core->db()->prepare("SELECT s.*, b.title FROM sales s JOIN beats b ON s.beat_id = b.id WHERE s.delivery_id = ?");
$stmt->execute([$delivery_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Invalid delivery ID.");
}

if ($sale['payment_status'] === 'completed') {
    header("Location: api/download.php?token=" . $sale['download_token']);
    exit;
}

$plisio_api_key = $core->setting('plisio_api_key');
if (empty($plisio_api_key)) {
    die("Payment gateway not configured. Please contact the administrator.");
}

$plisio = new Plisio($plisio_api_key);

// If invoice doesn't exist, create it
if (empty($sale['plisio_invoice_id'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);

    $invoice_data = [
        'source_currency' => 'USD',
        'source_amount' => $sale['price'],
        'order_number' => $sale['delivery_id'],
        'order_name' => 'Beat: ' . $sale['title'],
        'callback_url' => $base_url . '/api/webhook_plisio.php',
        'success_url' => $base_url . '/pay.php?id=' . $sale['delivery_id'] . '&status=success',
        'email' => $sale['winner_email']
    ];

    $response = $plisio->create_invoice($invoice_data);

    if ($response && $response['status'] === 'success') {
        $invoice_id = $response['data']['txn_id'];
        $invoice_url = $response['data']['invoice_url'];

        $stmt = $core->db()->prepare("UPDATE sales SET plisio_invoice_id = ?, plisio_invoice_url = ? WHERE id = ?");
        $stmt->execute([$invoice_id, $invoice_url, $sale['id']]);

        header("Location: " . $invoice_url);
        exit;
    } else {
        die("Failed to create payment invoice: " . ($response['data']['message'] ?? 'Unknown error'));
    }
} else {
    header("Location: " . $sale['plisio_invoice_url']);
    exit;
}

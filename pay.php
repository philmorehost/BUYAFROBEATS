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
    header("Location: api/download?token=" . $sale['download_token']);
    exit;
}

$plisio_api_key = $core->setting('plisio_api_key');
$is_localhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if (isset($_POST['simulate_payment']) && $is_localhost) {
    $stmt = $core->db()->prepare("UPDATE sales SET payment_status = 'completed' WHERE id = ?");
    $stmt->execute([$sale['id']]);
    
    // Send receipt
    require_once __DIR__ . '/includes/Email.php';
    $email_svc = new \BAF\Email($core);
    $email_svc->send_payment_receipt($sale);
    
    header("Location: api/download?token=" . $sale['download_token']);
    exit;
}

if (empty($plisio_api_key) && !$is_localhost) {
    die("Payment gateway not configured. Please contact the administrator.");
}

$plisio = new Plisio($plisio_api_key);

// If invoice doesn't exist, create it
if (empty($sale['plisio_invoice_id'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_path;

    $invoice_data = [
        'source_currency' => 'USD',
        'source_amount' => $sale['price'],
        'order_number' => $sale['delivery_id'],
        'order_name' => 'Beat: ' . $sale['title'],
        'callback_url' => $base_url . '/api/webhook_plisio',
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

// Minimalist Payment UI
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout — <?php echo $sale['title']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .checkout-box { max-width: 400px; margin: 100px auto; padding: 40px; background: var(--bg-2); border: 1px solid var(--line); border-radius: 24px; text-align: center; }
        .price { font-size: 32px; font-weight: 700; color: var(--accent); margin: 20px 0; }
    </style>
</head>
<body>
    <div class="checkout-box">
        <div class="logo"><span class="dot"></span> BUYAFROBEATS</div>
        <h3><?php echo $sale['title']; ?></h3>
        <div class="price">$<?php echo number_format($sale['price'], 2); ?></div>
        
        <?php if ($is_localhost): ?>
            <form method="POST">
                <button type="submit" name="simulate_payment" value="1" class="btn" style="width:100%; background: var(--ok); color: #000;">
                    Simulate Successful Payment (Test Mode) →
                </button>
            </form>
            <p style="font-size:11px; color:var(--ink-mute); margin-top:20px;">
                Note: This button is only visible on localhost for testing purposes.
            </p>
        <?php else: ?>
            <p>Please wait while we redirect you to the secure payment gateway...</p>
            <script>setTimeout(() => { window.location.reload(); }, 2000);</script>
        <?php endif; ?>
    </div>
</body>
</html>

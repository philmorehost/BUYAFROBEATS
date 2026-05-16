<?php
require_once __DIR__ . '/../includes/Core.php';
require_once __DIR__ . '/../includes/Plisio.php';
require_once __DIR__ . '/../includes/Email.php';
require_once __DIR__ . '/../includes/Auction.php';
require_once __DIR__ . '/../includes/GoogleDrive.php';

use BAF\Core;
use BAF\Plisio;
use BAF\Email;
use BAF\Auction;
use BAF\GoogleDrive;

$core = Core::get_instance();
$plisio_api_key = $core->setting('plisio_api_key');

if (empty($plisio_api_key)) {
    http_response_code(500);
    exit;
}

$plisio = new Plisio($plisio_api_key);
$data = $_POST;

if ($plisio->verify_callback($data)) {
    $delivery_id = $data['order_number'];
    $status = $data['status'];

    $stmt = $core->db()->prepare("SELECT s.*, b.title, b.master_url, b.stems_url, b.license_url 
                                  FROM sales s 
                                  JOIN beats b ON s.beat_id = b.id 
                                  WHERE s.delivery_id = ?");
    $stmt->execute([$delivery_id]);
    $sale = $stmt->fetch();

    if ($sale && $sale['payment_status'] !== 'completed') {
        if ($status === 'completed' || $status === 'mismatch') {
            // 1. Update status
            $stmt = $core->db()->prepare("UPDATE sales SET payment_status = 'completed' WHERE id = ?");
            $stmt->execute([$sale['id']]);

            // 2. Share Google Drive files (Deliverables)
            $drive = new GoogleDrive();
            $files_to_share = [$sale['master_url'], $sale['stems_url'], $sale['license_url']];
            foreach ($files_to_share as $url) {
                if (!empty($url)) {
                    $file_id = GoogleDrive::extract_id($url);
                    if ($file_id) {
                        $drive->share_file($file_id, $sale['winner_email']);
                    }
                }
            }

            // 3. Mark as shared
            $stmt = $core->db()->prepare("UPDATE sales SET drive_shared_at = UTC_TIMESTAMP() WHERE id = ?");
            $stmt->execute([$sale['id']]);

            // 4. Send delivery email
            $email_svc = new Email($core);
            $email_svc->send_payment_receipt($sale);

        } elseif ($status === 'expired' || $status === 'error') {
            // Trigger Cascade if the payment window expired
            $auction = new Auction($core);
            $auction->advance_cascade($delivery_id);
        }
    }
}

http_response_code(200);
echo "OK";

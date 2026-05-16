<?php
require_once __DIR__ . '/includes/Core.php';
require_once __DIR__ . '/includes/License.php';
use BAF\Core;
use BAF\License;

$core = Core::get_instance();
$license_svc = new License($core);

$delivery_id = $_GET['id'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($delivery_id) || empty($token)) {
    die("Invalid access.");
}

$stmt = $core->db()->prepare("SELECT * FROM sales WHERE delivery_id = ? AND download_token = ?");
$stmt->execute([$delivery_id, $token]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Access denied or record not found.");
}

$html = $license_svc->generate_signed_license($sale['id']);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Exclusive License — <?php echo $delivery_id; ?></title>
    <style>
        body { background: #f5f5f5; margin: 0; padding: 20px; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; margin-bottom:20px;">
        <button onclick="window.print()" style="padding:10px 20px; cursor:pointer; background:#000; color:#fff; border:0; border-radius:4px; font-weight:700;">Print to PDF / Save License</button>
    </div>
    <?php echo $html; ?>
</body>
</html>

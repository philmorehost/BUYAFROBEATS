<?php
namespace BAF;

class Email {
    private $core;

    public function __construct(Core $core) {
        $this->core = $core;
    }

    public function send($to, $subject, $message, $attachments = []) {
        $from = $this->core->setting('smtp_from', 'no-reply@buyafrobeats.com');
        $site_title = $this->core->setting('site_title', 'BUYAFROBEATS');

        $boundary = md5(time());
        $headers = "From: $site_title <$from>\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: $site_title-PHP\r\n";

        // Body
        $body = "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message . "\r\n";

        // Attachments
        foreach ($attachments as $filename => $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $content = chunk_split(base64_encode($content));
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
                $body .= "Content-Description: $filename\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$filename\"; size=" . filesize($path) . ";\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= $content . "\r\n";
            }
        }
        $body .= "--$boundary--";

        try {
            return mail($to, $subject, $body, $headers);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function notify_outbid($email, $beat_title, $new_bid) {
        $subject = "You've been outbid on $beat_title!";
        $msg = "<h1>Outbid!</h1><p>Someone just placed a higher bid of <b>$" . number_format($new_bid, 2) . "</b> on <b>$beat_title</b>.</p>";
        $msg .= "<p><a href='" . $this->get_site_url() . "'>Return to auction and bid again →</a></p>";
        return $this->send($email, $subject, $this->wrap_template($msg));
    }

    public function notify_win_payment($email, $beat_title, $amount, $delivery_id) {
        $subject = "Action Required: You won the auction for $beat_title!";
        $payment_url = $this->get_site_url() . "/pay.php?id=$delivery_id";
        $msg = "<h1>You Won!</h1><p>Congratulations! You are the highest bidder for <b>$beat_title</b> at <b>$" . number_format($amount, 2) . "</b>.</p>";
        $msg .= "<p>To claim your exclusive beat, please complete the payment using the link below:</p>";
        $msg .= "<a href='$payment_url' style='display:inline-block; background:#ffa326; color:#fff; padding:14px 28px; text-decoration:none; border-radius:999px; font-weight:bold;'>Complete Payment →</a>";
        $msg .= "<p><small>Delivery ID: $delivery_id<br>Please note: The beat will be held for you for 24 hours. After payment, you will have 24 hours to download your file before it is permanently removed from our server.</small></p>";
        return $this->send($email, $subject, $this->wrap_template($msg));
    }

    public function notify_admin_activity($type, $details) {
        $admin_email = $this->core->setting('contact_email');
        if (!$admin_email) return false;

        $subject = "[Activity] " . ucfirst($type) . " on " . ($details['beat_title'] ?? 'Beat');
        $msg = "<h1>New Activity</h1><p><b>Type:</b> " . ucfirst($type) . "</p>";
        foreach ($details as $k => $v) {
            $msg .= "<p><b>" . ucfirst(str_replace('_', ' ', $k)) . ":</b> $v</p>";
        }
        return $this->send($admin_email, $subject, $this->wrap_template($msg));
    }

    public function send_payment_receipt($sale) {
        $subject = "Payment Receipt & Download: " . $sale['title'];
        $download_url = $this->get_site_url() . "/api/download.php?token=" . $sale['download_token'];
        
        $license_path = $this->generate_license($sale);

        $msg = "
        <div style='background:#f9f9f9; padding:30px; border-radius:16px; border:1px solid #eee;'>
            <h2 style='margin-top:0;'>Payment Received</h2>
            <p>Thank you for your purchase. Your exclusive license for <b>" . Core::escape($sale['title']) . "</b> is attached.</p>

            <div style='background:#fff; padding:20px; border-radius:12px; margin:24px 0; border:1px solid #eee;'>
                <table style='width:100%; border-collapse:collapse;'>
                    <tr><td style='color:#666; padding-bottom:10px;'>Order ID:</td><td style='text-align:right; font-weight:bold;'>" . $sale['delivery_id'] . "</td></tr>
                    <tr><td style='color:#666; padding-bottom:10px;'>Item:</td><td style='text-align:right; font-weight:bold;'>" . Core::escape($sale['title']) . "</td></tr>
                    <tr><td style='color:#666; padding-bottom:10px;'>Amount:</td><td style='text-align:right; font-weight:bold; color:#ffa326;'>$" . number_format($sale['price'], 2) . "</td></tr>
                    <tr><td style='color:#666;'>Date:</td><td style='text-align:right; font-weight:bold;'>" . date('M d, Y') . "</td></tr>
                </table>
            </div>

            <p>Your exclusive master file is now available for download:</p>
            <a href='$download_url' style='display:inline-block; background:#000; color:#fff; padding:14px 28px; text-decoration:none; border-radius:999px; font-weight:bold;'>Download Master (.WAV)</a>

            " . (!empty($sale['stems_path']) || !empty($sale['stems_url']) ? "
            <p style='margin-top:10px;'>Track Stems are also included in your download package:</p>
            <a href='{$download_url}&type=stems' style='display:inline-block; background:#f0f0f0; color:#000; padding:10px 20px; text-decoration:none; border-radius:999px; font-size:13px; font-weight:bold;'>Download Stems (.ZIP)</a>" : "") . "

            <p style='margin-top:30px; color:#d93025; font-size:13px; font-weight:bold;'>CRITICAL: You must download this file within 24 hours. To ensure exclusivity, the file will be permanently deleted from our server after this window.</p>
        </div>";

        return $this->send($sale['winner_email'], $subject, $this->wrap_template($msg), [
            "Exclusive_License_" . $sale['delivery_id'] . ".txt" => $license_path
        ]);
    }

    private function generate_license($sale) {
        $site_title = $this->core->setting('site_title', 'BUYAFROBEATS');
        $date = date('F j, Y');
        
        $text = "====================================================\n";
        $text .= "         EXCLUSIVE AUDIO LICENSE AGREEMENT          \n";
        $text .= "====================================================\n\n";
        $text .= "DATE: $date\n";
        $text .= "LICENSEE: " . $sale['winner_handle'] . " (" . $sale['winner_email'] . ")\n";
        $text .= "LICENSOR: $site_title Studio\n";
        $text .= "ITEM: " . $sale['title'] . "\n";
        $text .= "DELIVERY ID: " . $sale['delivery_id'] . "\n";
        $text .= "PRICE PAID: $" . number_format($sale['price'], 2) . "\n\n";
        $text .= "TERMS OF EXCLUSIVITY:\n";
        $text .= "1. The Licensor hereby grants the Licensee a 100% exclusive, perpetual, \n";
        $text .= "   worldwide license to use the Item in any commercial or non-commercial \n";
        $text .= "   project. No other parties hold a license for this Item.\n";
        $text .= "2. The Licensor warrants that the Item has been removed from public sale \n";
        $text .= "   and all master files have been deleted from the server within 24 hours \n";
        $text .= "   of this transaction to protect Licensee's exclusivity.\n";
        $text .= "3. This document serves as the official Proof of Ownership.\n\n";
        $text .= "Signed,\n";
        $text .= "$site_title Studio Automation Sentinel\n";
        $text .= "Hash: " . md5($sale['delivery_id'] . AUTH_SALT) . "\n";

        $tmp_path = __DIR__ . '/../uploads/temp_license_' . $sale['delivery_id'] . '.txt';
        file_put_contents($tmp_path, $text);
        return $tmp_path;
    }

    private function get_site_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace(['/api', '/admin'], '', $base_path);
    }

    private function wrap_template($content) {
        $site_title = Core::escape($this->core->setting('site_title', 'BUYAFROBEATS'));
        return "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 12px;'>
            <div style='font-weight: bold; font-size: 20px; margin-bottom: 20px;'>$site_title</div>
            $content
            <div style='margin-top: 30px; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 10px;'>
                &copy; " . date('Y') . " $site_title Studio. All rights reserved.
            </div>
        </div>";
    }
}

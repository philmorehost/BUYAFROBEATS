<?php
namespace BAF;

// Mock Core class
class Core {
    private $settings = [];
    public function __construct($settings = []) {
        $this->settings = $settings;
    }
    public function setting($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }
    public static function escape($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// Mock mail function in BAF namespace to intercept calls
function mail($to, $subject, $message, $headers) {
    echo "Mail sent to $to with subject: $subject\n";
    echo "Headers:\n$headers\n";
    return true;
}

require_once __DIR__ . '/../includes/Email.php';

echo "Testing Email::send with NO SMTP settings...\n";
$coreNoSmtp = new Core([]);
$emailNoSmtp = new Email($coreNoSmtp);
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/tests/verify_email_logic.php';
$emailNoSmtp->send('test@example.com', 'Test Subject', '<p>Hello</p>');

echo "\n-------------------\n";

echo "Testing Email::send WITH SMTP settings...\n";
$coreWithSmtp = new Core(['smtp_host' => 'smtp.example.com']);
$emailWithSmtp = new Email($coreWithSmtp);
$emailWithSmtp->send('test2@example.com', 'Test Subject 2', '<p>Hello 2</p>');

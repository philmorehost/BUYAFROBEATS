<?php
$core = new class {
    public function render_logo() { return "LOGO"; }
    public function setting($k, $default="") { return $default; }
    public function db() { return null; }
    public function render_footer_injection() { return ""; }
};
class Core {
    public static function escape($s) { return htmlspecialchars($s); }
}
include "includes/footer.php";

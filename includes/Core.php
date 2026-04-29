<?php
namespace BAF;

class Core {
    private static $instance = null;
    private $db = null;
    private $settings = [];

    private function __construct() {
        $config_file = __DIR__ . '/../config.php';
        if (!file_exists($config_file)) {
            if (strpos($_SERVER['REQUEST_URI'], '/install/') === false) {
                header('Location: ./install/');
                exit;
            }
            return;
        }

        require_once $config_file;
        $this->init_db();
        $this->load_settings();
        $this->init_headers();
        $this->init_session();
    }

    private function init_headers() {
        if (!headers_sent()) {
            header("X-Frame-Options: SAMEORIGIN");
            header("X-Content-Type-Options: nosniff");
            header("X-XSS-Protection: 1; mode=block");
            header("Referrer-Policy: strict-origin-when-cross-origin");
            header("Content-Security-Policy: default-src 'self' https:; script-src 'self' 'unsafe-inline' https://pagead2.googlesyndication.com https://accounts.google.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; frame-src https://accounts.google.com https://www.youtube.com; connect-src 'self' https://accounts.google.com;");
        }
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_db() {
        try {
            $this->db = new \PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (\PDOException $e) {
            die("Database connection failed. Please check your config.php.");
        }
    }

    private function load_settings() {
        if (!$this->db) return;
        $stmt = $this->db->query("SELECT `key`, `value` FROM settings");
        while ($row = $stmt->fetch()) {
            $this->settings[$row['key']] = $row['value'];
        }
    }

    private function init_session() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            session_start();
        }

        // Prevent session fixation
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        } elseif (time() - $_SESSION['created_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created_at'] = time();
        }
    }

    public function db() {
        return $this->db;
    }

    public function setting($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    // Security Helpers
    public static function escape($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    public static function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verify_csrf($token) {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function is_admin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public function render_logo() {
        $title = trim($this->setting('site_title', 'BUYAFROBEATS'));

        // Use a static string for testing if provided (for debugging if needed)
        // $title = "Beat Zaza";

        // Clean title for joined output
        $clean_title = str_replace(' ', '', $title);
        
        // Use the original space position as the split point if it existed
        $space_index = strpos($title, ' ');

        $split_at = -1;

        if ($space_index !== false) {
            $split_at = $space_index;
        } else {
            // Find second capital letter (CapLock style)
            $caps_found = 0;
            for ($i = 0; $i < strlen($clean_title); $i++) {
                if (ctype_upper($clean_title[$i])) {
                    $caps_found++;
                    if ($caps_found === 2) {
                        $split_at = $i;
                        break;
                    }
                }
            }

            // Special handling for all-caps starting with BUY (e.g., BUYBEATS)
            if ($split_at === 1 && strpos(strtoupper($clean_title), 'BUY') === 0 && strlen($clean_title) > 3) {
                $split_at = 3;
            }
        }

        if ($split_at > 0 && $split_at < strlen($clean_title)) {
            $part1 = substr($clean_title, 0, $split_at);
            $part2 = substr($clean_title, $split_at);
            // Wrap in a span to prevent parent flexbox 'gap' from separating them
            return '<span>' . self::escape($part1) . '<span style="color:#ffa326">' . self::escape($part2) . '</span></span>';
        }
        
        return '<span>' . self::escape($clean_title) . '</span>';
    }

    public function render_seo($page_seo = []) {
        $site_title = $this->setting('site_title', 'BUYAFROBEATS');
        $site_title = str_replace(' ', '', $site_title);
        $title = !empty($page_seo['title']) ? $page_seo['title'] : $this->setting('global_meta_title', $site_title);
        $desc = !empty($page_seo['description']) ? $page_seo['description'] : $this->setting('global_meta_description', 'Exclusive beat auctions.');
        $keywords = !empty($page_seo['keywords']) ? $page_seo['keywords'] : $this->setting('global_meta_keywords', 'beats, auction, afrobeats');

        $html = "<title>" . self::escape($title) . "</title>\n";
        $html .= "    <meta name=\"description\" content=\"" . self::escape($desc) . "\">\n";
        $html .= "    <meta name=\"keywords\" content=\"" . self::escape($keywords) . "\">\n";
        
        // OpenGraph
        $html .= "    <meta property=\"og:title\" content=\"" . self::escape($title) . "\">\n";
        $html .= "    <meta property=\"og:description\" content=\"" . self::escape($desc) . "\">\n";
        $html .= "    <meta property=\"og:type\" content=\"website\">\n";
        
        // AdSense
        $adsense_id = $this->setting('google_adsense_client');
        if ($adsense_id) {
            $html .= "    <script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=" . self::escape($adsense_id) . "\" crossorigin=\"anonymous\"></script>\n";
        }

        return $html;
    }

    public function render_head_injection() {
        return $this->setting('header_injection', '');
    }

    public function render_footer_injection() {
        return $this->setting('footer_injection', '');
    }
}

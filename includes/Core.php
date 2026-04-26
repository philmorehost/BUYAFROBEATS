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
        $this->init_session();
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
}

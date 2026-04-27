<?php
// ============================================
// Configuração da Base de Dados
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'farmacia_db');
define('DB_PORT', '3306');

define('APP_NAME', 'FarmaciaPro');
define('APP_VERSION', '2.0');
define('APP_URL', 'http://localhost/farmacia');
define('CURRENCY', 'Kz');
define('TIMEZONE', 'Africa/Luanda');

date_default_timezone_set(TIMEZONE);

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]));
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConn(): PDO {
        return $this->conn;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $sql, array $params = []): int {
        $this->query($sql, $params);
        return (int) $this->conn->lastInsertId();
    }

    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }
}

function db(): Database {
    return Database::getInstance();
}

function formatCurrency(float $value): string {
    return CURRENCY . ' ' . number_format($value, 2, ',', '.');
}

function formatDate(string $date): string {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime(string $date): string {
    if (!$date) return '-';
    return date('d/m/Y H:i', strtotime($date));
}

function sanitize(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

<?php

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        self::loadEnv();

        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'yober_sport';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

        try {
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $isApi = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
            http_response_code(500);
            if ($isApi) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Database connection failed', 'detail' => $e->getMessage()]);
            } else {
                header('Content-Type: text/html; charset=utf-8');
                echo '<!DOCTYPE html><html><body style="font-family:sans-serif;background:#111;color:#fff;padding:2rem">';
                echo '<h1>Error de conexión a la base de datos</h1>';
                echo '<p>Revisa <code>backend/.env</code> (DB_HOST, DB_NAME=yober_sport, DB_USER, DB_PASSWORD).</p>';
                echo '<pre style="color:#f87171">' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '</body></html>';
            }
            exit;
        }
    }

    private static function loadEnv(): void {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

<?php


class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    private $conn;

    public function __construct() {
        $this->host = $this->env('DB_HOST', '127.0.0.1:3306');
        $this->db_name = $this->env('DB_NAME', 'fablab');
        $this->username = $this->env('DB_USER', 'root');
        $this->password = $this->env('DB_PASS', '');
    }

    private function env(string $name, string $default): string {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }

    public function getConnection() {
        if ($this->conn == null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                error_log('Database connection error: ' . $e->getMessage());
                throw new RuntimeException('Connexion base indisponible.', 0, $e);
            }
        }
        return $this->conn;
    }
}


function getDatabase() {
    $database = new Database();
    return $database->getConnection();
}
?>

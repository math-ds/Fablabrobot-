<?php


class Database {
    private $host = '127.0.0.1:3306';
    private $db_name = 'fablab';
    private $username = 'root'; 
    private $password = '';     
    private $charset = 'utf8mb4';
    private $conn;

    public function getConnection() {
        if ($this->conn == null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
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

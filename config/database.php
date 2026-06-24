<?php
class Database {
    private $host = "localhost";
    private $db_name = "db_qiu_schedule";
    private $username = "root";
    private $password = "";

    public $conn;

    // fungsi untuk mendapatkan koneksi database wak
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
            $this->conn->exec("SET time_zone = '+00:00'");

        } catch(PDOException $exception) {
            http_response_code(503);
            die(json_encode([
                "message" => "Tidak dapat terhubung ke database. Silakan coba lagi nanti."
            ]));
        }
        return $this->conn;
    }
}
?>

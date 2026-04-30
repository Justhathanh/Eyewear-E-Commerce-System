<?php
class Database {
    private $host = "127.0.0.1";
    private $db_name = "shop";
    private $username = "root";
    private $password = "";      
    private $port = "3307";      

    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
            $this->conn->set_charset("utf8");

            if ($this->conn->connect_error) {
                throw new Exception("Kết nối database thất bại: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}
?>
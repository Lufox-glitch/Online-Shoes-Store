<?php
/**
 * Database Configuration and Connection Class
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'online_shoes_store';
    private $db_user = 'root';
    private $db_pass = '';
    private $conn;

    /**
     * Connect to database
     */
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new mysqli($this->host, $this->db_user, $this->db_pass, $this->db_name);

            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection Error: " . $this->conn->connect_error);
            }

            // Set charset
            $this->conn->set_charset("utf8mb4");

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        }

        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function disconnect() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>

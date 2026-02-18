<?php
/**
 * Payment Model
 */

class Payment {
    private $conn;
    private $table = 'payments';

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Create new payment record
     */
    public function create($order_id, $payment_method, $amount, $payment_screenshot = null) {
        $query = "INSERT INTO " . $this->table . " 
                  (order_id, payment_method, amount, payment_screenshot, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $status = 'pending';
        $stmt->bind_param('isdss', $order_id, $payment_method, $amount, $payment_screenshot, $status);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Get payment by order ID
     */
    public function getByOrderId($order_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE order_id = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('i', $order_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Get payment by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Update payment status
     */
    public function updateStatus($id, $status, $transaction_id = null) {
        $query = "UPDATE " . $this->table . " SET status = ?, transaction_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('ssi', $status, $transaction_id, $id);

        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Get user payments
     */
    public function getUserPayments($user_id, $limit = 10, $offset = 0) {
        $query = "SELECT p.* FROM " . $this->table . " p 
                  JOIN orders o ON p.order_id = o.id 
                  WHERE o.user_id = ? 
                  ORDER BY p.created_at DESC 
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('iii', $user_id, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Get all payments (owner only)
     */
    public function getAll($limit = 10, $offset = 0) {
        $query = "SELECT * FROM " . $this->table . " 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();

        return $stmt->get_result();
    }
}
?>

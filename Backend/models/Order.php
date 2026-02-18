<?php
/**
 * Order Model Class
 */

class Order {
    private $conn;
    private $table = 'orders';

    public $id;
    public $user_id;
    public $order_number;
    public $total_amount;
    public $status;
    public $payment_method;
    public $shipping_address;
    public $notes;
    public $created_at;
    public $updated_at;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get all orders
     */
    public function getAll($limit = null, $offset = null) {
        $query = "SELECT o.*, u.first_name, u.last_name, u.email 
                  FROM " . $this->table . " o
                  LEFT JOIN users u ON o.user_id = u.id
                  WHERE o.deleted_at IS NULL
                  ORDER BY o.created_at DESC";

        if ($limit) {
            $query .= " LIMIT " . intval($limit);
            if ($offset) {
                $query .= " OFFSET " . intval($offset);
            }
        }

        return $this->conn->query($query);
    }

    /**
     * Get user orders
     */
    public function getUserOrders($userId, $limit = null, $offset = null) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = ? AND deleted_at IS NULL
                  ORDER BY created_at DESC";

        if ($limit) {
            $query .= " LIMIT " . intval($limit);
            if ($offset) {
                $query .= " OFFSET " . intval($offset);
            }
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Get order by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ? AND deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Create order
     */
    public function create() {
        // Build query dynamically if notes is null
        if ($this->notes === null) {
            $query = "INSERT INTO " . $this->table . " 
                      (user_id, order_number, total_amount, status, payment_method, shipping_address, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('isdsss',
                $this->user_id,
                $this->order_number,
                $this->total_amount,
                $this->status,
                $this->payment_method,
                $this->shipping_address
            );
        } else {
            $query = "INSERT INTO " . $this->table . " 
                      (user_id, order_number, total_amount, status, payment_method, shipping_address, notes, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('isdssss',
                $this->user_id,
                $this->order_number,
                $this->total_amount,
                $this->status,
                $this->payment_method,
                $this->shipping_address,
                $this->notes
            );
        }

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return true;
        }
        
        // Log error for debugging
        error_log("Order creation error: " . $stmt->error);
        return false;
    }

    /**
     * Update order status
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET status = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('si', $status, $id);
        
        return $stmt->execute();
    }

    /**
     * Get order statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as average_order_value,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
                  FROM " . $this->table . " WHERE deleted_at IS NULL";
        
        return $this->conn->query($query);
    }
}
?>

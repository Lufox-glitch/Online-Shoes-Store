<?php
/**
 * Delivery Model
 */

class Delivery {
    private $conn;
    private $table = 'delivery';

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Create delivery record
     */
    public function create($order_id, $estimated_delivery_date, $delivery_address = null) {
        // Auto-generate tracking number: TRK + timestamp + random number
        $tracking_number = 'TRK' . time() . rand(1000, 9999);
        
        $query = "INSERT INTO " . $this->table . " 
                  (order_id, estimated_delivery_date, delivery_status, tracking_number, created_at) 
                  VALUES (?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $status = 'pending';
        $stmt->bind_param('isss', $order_id, $estimated_delivery_date, $status, $tracking_number);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Get delivery by order ID
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
     * Get delivery by ID
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
     * Update delivery status
     */
    public function updateStatus($id, $status, $tracking_number = null, $actual_delivery_date = null) {
        // Build dynamic query - only update fields that are provided
        $updates = ['delivery_status = ?'];
        $params = [$status];
        $types = 's';
        
        if ($actual_delivery_date !== null) {
            $updates[] = 'actual_delivery_date = ?';
            $params[] = $actual_delivery_date;
            $types .= 's';
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $updates) . " 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Get user deliveries
     */
    public function getUserDeliveries($user_id, $limit = 10, $offset = 0) {
        $query = "SELECT d.* FROM " . $this->table . " d 
                  JOIN orders o ON d.order_id = o.id 
                  WHERE o.user_id = ? 
                  ORDER BY d.created_at DESC 
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
     * Get all deliveries (owner only)
     */
    public function getAll($limit = 10, $offset = 0) {
        $query = "SELECT d.*, o.total_amount, u.first_name, u.email FROM " . $this->table . " d 
                  JOIN orders o ON d.order_id = o.id 
                  JOIN users u ON o.user_id = u.id 
                  ORDER BY d.created_at DESC 
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Search delivery by tracking number (public access)
     */
    public function searchByTracking($tracking_number) {
        $query = "SELECT * FROM " . $this->table . " WHERE tracking_number = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('s', $tracking_number);
        $stmt->execute();

        return $stmt->get_result();
    }
}
?>


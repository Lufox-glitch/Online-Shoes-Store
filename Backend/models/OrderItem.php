<?php
/**
 * Order Item Model Class
 */

class OrderItem {
    private $conn;
    private $table = 'order_items';

    public $id;
    public $order_id;
    public $product_id;
    public $quantity;
    public $price;
    public $shoe_size;
    public $created_at;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get items by order ID
     */
    public function getByOrderId($order_id) {
        $query = "SELECT oi.*, p.name, p.sku 
                  FROM " . $this->table . " oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Create order item
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (order_id, product_id, quantity, price, shoe_size, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('iidis',
            $this->order_id,
            $this->product_id,
            $this->quantity,
            $this->price,
            $this->shoe_size
        );

        if ($stmt->execute()) {
            $this->id = $stmt->insert_id;
            return true;
        }
        return false;
    }

    /**
     * Delete order items by order ID
     */
    public function deleteByOrderId($order_id) {
        $query = "DELETE FROM " . $this->table . " WHERE order_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $order_id);
        
        return $stmt->execute();
    }
}
?>

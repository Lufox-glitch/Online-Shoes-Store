<?php
/**
 * Product Model Class
 */

class Product {
    private $conn;
    private $table = 'products';

    public $id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $category_id;
    public $image_url;
    public $sku;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get all products
     */
    public function getAll($limit = null, $offset = null, $category_id = null, $is_active = true) {
        $query = "SELECT p.*, c.name as category_name FROM " . $this->table . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.deleted_at IS NULL";

        if ($is_active) {
            $query .= " AND p.is_active = 1";
        }

        if ($category_id) {
            $query .= " AND p.category_id = " . intval($category_id);
        }

        $query .= " ORDER BY p.created_at DESC";

        if ($limit) {
            $query .= " LIMIT " . intval($limit);
            if ($offset) {
                $query .= " OFFSET " . intval($offset);
            }
        }

        return $this->conn->query($query);
    }

    /**
     * Get product by ID
     */
    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name FROM " . $this->table . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = ? AND p.deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Search products
     */
    public function search($keyword, $limit = 10) {
        $keyword = '%' . $this->conn->real_escape_string($keyword) . '%';
        $query = "SELECT id, name, price, image_url FROM " . $this->table . " 
                  WHERE (name LIKE ? OR description LIKE ?) 
                  AND is_active = 1 AND deleted_at IS NULL
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssi', $keyword, $keyword, $limit);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Create product
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (name, description, price, stock, category_id, image_url, sku, is_active, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssdiiisi',
            $this->name,
            $this->description,
            $this->price,
            $this->stock,
            $this->category_id,
            $this->image_url,
            $this->sku,
            $this->is_active
        );

        return $stmt->execute();
    }

    /**
     * Update product
     */
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, image_url = ?, sku = ?, is_active = ?, updated_at = NOW() 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ssdiiisii',
            $this->name,
            $this->description,
            $this->price,
            $this->stock,
            $this->category_id,
            $this->image_url,
            $this->sku,
            $this->is_active,
            $this->id
        );

        return $stmt->execute();
    }

    /**
     * Delete product (soft delete)
     */
    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET deleted_at = NOW() WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        
        return $stmt->execute();
    }

    /**
     * Update stock
     */
    public function updateStock($id, $quantity) {
        $query = "UPDATE " . $this->table . " SET stock = stock + ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ii', $quantity, $id);
        
        return $stmt->execute();
    }
}
?>

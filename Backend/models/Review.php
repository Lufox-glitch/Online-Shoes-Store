<?php
/**
 * Review Model
 */

class Review {
    private $conn;
    private $table = 'reviews';

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Create review
     */
    public function create($product_id, $user_id, $rating, $comment = null, $is_verified_purchase = false) {
        $query = "INSERT INTO " . $this->table . " 
                  (product_id, user_id, rating, comment, is_verified_purchase, created_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        // Convert to integer (0 or 1)
        $is_verified_purchase = intval($is_verified_purchase);

        $stmt->bind_param('iiiis', $product_id, $user_id, $rating, $is_verified_purchase, $comment);

        if ($stmt->execute()) {
            return $this->conn->insert_id;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Get reviews by product ID
     */
    public function getByProductId($product_id, $limit = 10, $offset = 0) {
        $query = "SELECT r.*, u.first_name, u.last_name FROM " . $this->table . " r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = ? 
                  ORDER BY r.created_at DESC 
                  LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('iii', $product_id, $limit, $offset);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Get product rating average
     */
    public function getAverageRating($product_id) {
        $query = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews FROM " . $this->table . " 
                  WHERE product_id = ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('i', $product_id);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Get review by ID
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
     * Update review
     */
    public function update($id, $rating, $comment = null) {
        $query = "UPDATE " . $this->table . " 
                  SET rating = ?, comment = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('isi', $rating, $comment, $id);

        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Delete review
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            return true;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    /**
     * Get user reviews
     */
    public function getUserReviews($user_id, $limit = 10, $offset = 0) {
        $query = "SELECT r.*, p.name FROM " . $this->table . " r 
                  JOIN products p ON r.product_id = p.id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC 
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
     * Check if user has purchased product
     */
    public function hasUserPurchased($user_id, $product_id) {
        $query = "SELECT oi.id FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.id 
                  WHERE o.user_id = ? AND oi.product_id = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Check if user has already reviewed product
     */
    public function hasUserReviewed($user_id, $product_id) {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE user_id = ? AND product_id = ? 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param('ii', $user_id, $product_id);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }
}
?>

<?php
/**
 * User Model Class
 */

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $email;
    public $first_name;
    public $last_name;
    public $phone;
    public $password;
    public $role;
    public $is_active;
    public $created_at;
    public $updated_at;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Get all users
     */
    public function getAll($role = null, $limit = null, $offset = null) {
        $query = "SELECT id, email, first_name, last_name, phone, role, is_active, created_at 
                  FROM " . $this->table . " WHERE deleted_at IS NULL";

        if ($role) {
            $query .= " AND role = '" . $this->conn->real_escape_string($role) . "'";
        }

        $query .= " ORDER BY created_at DESC";

        if ($limit) {
            $query .= " LIMIT " . intval($limit);
            if ($offset) {
                $query .= " OFFSET " . intval($offset);
            }
        }

        return $this->conn->query($query);
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT id, email, first_name, last_name, phone, role, is_active, created_at, updated_at 
                  FROM " . $this->table . " WHERE id = ? AND deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = ? AND deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        
        return $stmt->get_result();
    }

    /**
     * Create new user
     */
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (email, first_name, last_name, phone, password, role, is_active, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            return false;
        }

        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $role = $this->role ?? 'customer';
        $is_active = $this->is_active ?? 1;

        // 6 strings (email, first_name, last_name, phone, hashed_password, role) + 1 integer (is_active)
        $stmt->bind_param('ssssssi', 
            $this->email,
            $this->first_name,
            $this->last_name,
            $this->phone,
            $hashed_password,
            $role,
            $is_active
        );

        return $stmt->execute();
    }

    /**
     * Update user
     */
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET email = ?, first_name = ?, last_name = ?, phone = ?, role = ?, is_active = ?, updated_at = NOW() 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssssii', 
            $this->email,
            $this->first_name,
            $this->last_name,
            $this->phone,
            $this->role,
            $this->is_active,
            $this->id
        );

        return $stmt->execute();
    }

    /**
     * Update password
     */
    public function updatePassword($userId, $newPassword) {
        $query = "UPDATE " . $this->table . " SET password = ?, updated_at = NOW() WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $stmt->bind_param('si', $hashed_password, $userId);
        
        return $stmt->execute();
    }

    /**
     * Delete user (soft delete)
     */
    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET deleted_at = NOW() WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        
        return $stmt->execute();
    }

    /**
     * Verify password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>

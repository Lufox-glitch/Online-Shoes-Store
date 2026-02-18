<?php
/**
 * Authentication Middleware
 */

class Auth {
    private $conn;
    private $user = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Check if user is authenticated via session or token
     */
    public function isAuthenticated() {
        // Check session
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        // Check Bearer token
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            return $this->validateToken($token);
        }

        return false;
    }

    /**
     * Get current user
     */
    public function getUser() {
        if ($this->user === null) {
            if (isset($_SESSION['user_id'])) {
                $this->user = $this->getUserById($_SESSION['user_id']);
            }
        }
        return $this->user;
    }

    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        $query = "SELECT id, email, first_name, last_name, phone, role, created_at FROM users WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    /**
     * Validate JWT token (basic implementation)
     */
    private function validateToken($token) {
        // Implement proper JWT validation
        // This is a simple example
        return true;
    }

    /**
     * Check user role
     */
    public function hasRole($role) {
        $user = $this->getUser();
        return $user && $user['role'] === $role;
    }

    /**
     * Require authentication
     */
    public function require() {
        if (!$this->isAuthenticated()) {
            Response::unauthorized('Authentication required');
        }
    }

    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->require();
        if (!$this->hasRole($role)) {
            Response::error('Access denied', [], 403);
        }
    }
}
?>

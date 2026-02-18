<?php
/**
 * Global Configuration File
 */

// Enable error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shoes_store');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('APP_NAME', 'P&S Online Shoes');
define('APP_URL', 'http://localhost/Online-Shoes-Store');
define('API_URL', APP_URL . '/Backend/api');

// Session Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('REMEMBER_TIMEOUT', 604800); // 7 days

// Security Settings
define('JWT_SECRET', 'your_secret_key_change_in_production');
define('PASSWORD_MIN_LENGTH', 8);

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');

// Timezone
date_default_timezone_set('UTC');

// Configure session settings
ini_set('session.sid_length', '32');
ini_set('session.sid_bits_per_character', '5');

// Start session (will only start once)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Include Database Class
require_once __DIR__ . '/Database.php';

// Set headers only if not already sent
if (!headers_sent()) {
    // CORS Headers
    header('Access-Control-Allow-Origin: ' . APP_URL);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>

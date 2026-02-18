<?php
/**
 * Health Check / API Status Endpoint
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/Response.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    $result = $conn->query("SELECT 1");
    
    if ($result) {
        Response::success([
            'status' => 'OK',
            'app' => APP_NAME,
            'database' => 'Connected',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'API is operational');
    }
} catch (Exception $e) {
    Response::error('Database connection failed', ['error' => $e->getMessage()], 503);
}
?>

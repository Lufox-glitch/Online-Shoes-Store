<?php
/**
 * Simple API Test
 */

header('Content-Type: application/json');

// Test 1: Check if we can connect to database
require_once __DIR__ . '/config/config.php';

$db = new Database();
$conn = $db->connect();

if ($conn) {
    $result = [
        'success' => true,
        'message' => 'Database connection successful',
        'database' => 'online_shoes_store',
        'timestamp' => date('Y-m-d H:i:s')
    ];
} else {
    $result = [
        'success' => false,
        'message' => 'Database connection failed'
    ];
}

echo json_encode($result);
?>

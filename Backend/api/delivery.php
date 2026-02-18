<?php
/**
 * Delivery API Endpoints
 */

// Set JSON header first
header('Content-Type: application/json');

// Error handling - convert PHP errors to JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error',
        'error' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ]);
    exit;
});

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Fatal PHP Error',
            'error' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Start session FIRST (with proper error handling)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../utils/Response.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_GET['request'] ?? '', '/'));
$action = $request[0] ?? null;
$id = $request[1] ?? null;

// Initialize database
$db = new Database();
$conn = $db->connect();
$delivery = new Delivery($conn);
$auth = new Auth($conn);

try {
    switch ($action) {
        case 'create':
            $auth->require();
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            handleCreateDelivery($delivery, $auth);
            break;

        case 'get-order-delivery':
            $auth->require();
            if ($method !== 'GET' || !$id) {
                Response::error('Order ID required', [], 400);
            }
            handleGetOrderDelivery($delivery, $auth, $id);
            break;

        case 'get-by-id':
            $auth->require();
            if ($method !== 'GET' || !$id) {
                Response::error('Delivery ID required', [], 400);
            }
            handleGetDeliveryById($delivery, $id);
            break;

        case 'update-status':
            $auth->requireRole('owner');
            if ($method !== 'PUT' || !$id) {
                Response::error('Delivery ID required', [], 400);
            }
            handleUpdateDeliveryStatus($delivery, $id);
            break;

        case 'my-deliveries':
            $auth->require();
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetUserDeliveries($delivery, $auth);
            break;

        case 'list':
            $auth->requireRole('owner');
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetAllDeliveries($delivery);
            break;

        case 'search-tracking':
            if ($method !== 'GET' || !$id) {
                Response::error('Tracking number required', [], 400);
            }
            handleSearchTracking($delivery, $id);
            break;

        default:
            Response::error('Endpoint not found', [], 404);
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), [], 500);
}

/**
 * Handle create delivery
 */
function handleCreateDelivery($delivery, $auth) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['order_id']) || !isset($data['estimated_delivery_date'])) {
        Response::error('Missing required fields', [], 400);
    }

    $order_id = intval($data['order_id']);
    $estimated_delivery_date = htmlspecialchars($data['estimated_delivery_date']);
    $delivery_address = $data['delivery_address'] ?? null;

    // Validate date format
    if (!strtotime($estimated_delivery_date)) {
        Response::error('Invalid date format', [], 400);
    }

    try {
        $id = $delivery->create($order_id, $estimated_delivery_date, $delivery_address);
        Response::success(['id' => $id], 'Delivery record created successfully', 201);
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get delivery by order ID
 */
function handleGetOrderDelivery($delivery, $auth, $order_id) {
    try {
        $result = $delivery->getByOrderId($order_id);

        if ($result->num_rows === 0) {
            Response::error('Delivery record not found', [], 404);
        }

        $delivery_data = $result->fetch_assoc();
        Response::success($delivery_data, 'Delivery retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get delivery by delivery ID
 */
function handleGetDeliveryById($delivery, $delivery_id) {
    try {
        $result = $delivery->getById($delivery_id);

        if ($result->num_rows === 0) {
            Response::error('Delivery record not found', [], 404);
        }

        $delivery_data = $result->fetch_assoc();
        Response::success($delivery_data, 'Delivery retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle update delivery status
 */
function handleUpdateDeliveryStatus($delivery, $id) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['delivery_status'])) {
        Response::error('Delivery status is required', [], 400);
    }

    $status = htmlspecialchars($data['delivery_status']);
    $tracking_number = $data['tracking_number'] ?? null;
    $actual_delivery_date = $data['actual_delivery_date'] ?? null;

    $valid_statuses = ['pending', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        Response::error('Invalid delivery status', [], 400);
    }

    try {
        $delivery->updateStatus($id, $status, $tracking_number, $actual_delivery_date);
        
        // Get the order ID for this delivery
        $deliveryResult = $delivery->getById($id);
        if ($deliveryResult && $deliveryResult->num_rows > 0) {
            $deliveryRecord = $deliveryResult->fetch_assoc();
            $orderId = $deliveryRecord['order_id'];
            
            // Also update the order status to match delivery status
            // Map delivery status to order status
            $orderStatus = $status; // pending, processing, shipped, out_for_delivery, delivered, cancelled
            try {
                $order = new Order($GLOBALS['conn']);
                $order->updateStatus($orderId, $orderStatus);
            } catch (Exception $e) {
                error_log('Order update error: ' . $e->getMessage());
            }
            
            // If delivery is complete, mark payment as completed
            if ($status === 'delivered') {
                try {
                    $payment = new Payment($GLOBALS['conn']);
                    $paymentResult = $payment->getByOrderId($orderId);
                    if ($paymentResult && $paymentResult->num_rows > 0) {
                        $paymentRecord = $paymentResult->fetch_assoc();
                        $payment->updateStatus($paymentRecord['id'], 'completed');
                    }
                } catch (Exception $e) {
                    error_log('Payment update error: ' . $e->getMessage());
                }
            }
        }
        
        Response::success([], 'Delivery status updated successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get user deliveries
 */
function handleGetUserDeliveries($delivery, $auth) {
    $user = $auth->getUser();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    try {
        $result = $delivery->getUserDeliveries($user['id'], $limit, $offset);
        $deliveries = [];

        while ($row = $result->fetch_assoc()) {
            $deliveries[] = $row;
        }

        Response::success([
            'deliveries' => $deliveries,
            'limit' => $limit,
            'offset' => $offset
        ], 'Deliveries retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get all deliveries (owner only)
 */
function handleGetAllDeliveries($delivery) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    try {
        $result = $delivery->getAll($limit, $offset);
        $deliveries = [];

        while ($row = $result->fetch_assoc()) {
            $deliveries[] = $row;
        }

        Response::success([
            'deliveries' => $deliveries,
            'limit' => $limit,
            'offset' => $offset
        ], 'Deliveries retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle search by tracking number (public - no auth required)
 */
function handleSearchTracking($delivery, $tracking_number) {
    try {
        $tracking_number = htmlspecialchars($tracking_number);
        
        // Search for delivery by tracking number
        $result = $delivery->searchByTracking($tracking_number);
        
        if ($result->num_rows === 0) {
            Response::error('Tracking number not found', [], 404);
        }
        
        $delivery_data = $result->fetch_assoc();
        $order_id = $delivery_data['order_id'];
        
        // Get order details
        $order = new Order($GLOBALS['conn']);
        $orderResult = $order->getById($order_id);
        
        if ($orderResult && $orderResult->num_rows > 0) {
            $order_data = $orderResult->fetch_assoc();
            $delivery_data['order'] = $order_data;
        }
        
        Response::success($delivery_data, 'Tracking information retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}
?>


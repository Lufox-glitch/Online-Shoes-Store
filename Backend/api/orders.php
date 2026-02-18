<?php
/**
 * Orders API Endpoints
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
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Delivery.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_GET['request'] ?? '', '/'));
$action = $request[0] ?? null;
$id = $request[1] ?? null;

// Initialize database
$db = new Database();
$conn = $db->connect();
$order = new Order($conn);
$auth = new Auth($conn);

try {
    switch ($action) {
        case 'list':
            $auth->requireRole('owner');
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetAllOrders($order);
            break;

        case 'my-orders':
            $auth->require();
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetUserOrders($order, $auth);
            break;

        case 'detail':
            $auth->require();
            if ($method !== 'GET' || !$id) {
                Response::error('Order not found', [], 404);
            }
            handleGetOrderDetail($order, $id, $auth);
            break;

        case 'create':
            $auth->require();
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            handleCreateOrder($order, $auth);
            break;

        case 'update-status':
            $auth->requireRole('owner');
            if ($method !== 'PUT' || !$id) {
                Response::error('Order not found', [], 404);
            }
            handleUpdateOrderStatus($order, $id);
            break;

        case 'statistics':
            $auth->requireRole('owner');
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetOrderStatistics($order);
            break;

        default:
            Response::error('Endpoint not found', [], 404);
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), [], 500);
}

/**
 * Handle get all orders (owner only)
 */
function handleGetAllOrders($order) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $result = $order->getAll($limit, $offset);
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    Response::success([
        'orders' => $orders,
        'limit' => $limit,
        'offset' => $offset
    ], 'Orders retrieved successfully');
}

/**
 * Handle get user orders
 */
function handleGetUserOrders($order, $auth) {
    $user = $auth->getUser();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $result = $order->getUserOrders($user['id'], $limit, $offset);
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    Response::success([
        'orders' => $orders,
        'limit' => $limit,
        'offset' => $offset
    ], 'User orders retrieved successfully');
}

/**
 * Handle get order detail
 */
function handleGetOrderDetail($order, $id, $auth) {
    $result = $order->getById($id);

    if ($result->num_rows === 0) {
        Response::error('Order not found', [], 404);
    }

    $orderData = $result->fetch_assoc();
    $user = $auth->getUser();

    // Check permissions
    if ($user['role'] !== 'owner' && $user['id'] != $orderData['user_id']) {
        Response::error('Access denied', [], 403);
    }

    // Get order items
    $orderItem = new OrderItem($GLOBALS['conn']);
    $itemsResult = $orderItem->getByOrderId($id);
    $items = [];
    while ($row = $itemsResult->fetch_assoc()) {
        $items[] = $row;
    }

    $orderData['order_items'] = $items;

    Response::success($orderData, 'Order retrieved successfully');
}

/**
 * Handle create order
 */
function handleCreateOrder($order, $auth) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!$data) {
        Response::error('Invalid JSON input', [], 400);
    }
    
    $user = $auth->getUser();
    if (!$user) {
        Response::error('User not found. Please login first.', [], 401);
    }

    // Validate required fields
    $errors = Validator::required($data, ['items', 'total_amount', 'payment_method', 'shipping_address']);
    if (!empty($errors)) {
        Response::validation($errors);
    }

    // Validate amount
    if (!Validator::positive($data['total_amount'])) {
        Response::validation(['total_amount' => 'Total amount must be positive']);
    }

    // Validate items array
    if (!is_array($data['items']) || empty($data['items'])) {
        Response::validation(['items' => 'Order must contain at least one item']);
    }

    // Generate order number
    $order_number = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);

    // Create order
    $order->user_id = $user['id'];
    $order->order_number = $order_number;
    $order->total_amount = floatval($data['total_amount']);
    $order->status = 'pending';
    $order->payment_method = Validator::sanitize($data['payment_method']);
    $order->shipping_address = Validator::sanitize($data['shipping_address']);
    $order->notes = $data['notes'] ?? null;

    if ($order->create()) {
        // Create order items
        $db = new Database();
        $conn = $db->connect();
        $orderItem = new OrderItem($conn);
        
        foreach ($data['items'] as $item) {
            $orderItem->order_id = $order->id;
            $orderItem->product_id = intval($item['product_id']);
            $orderItem->quantity = intval($item['quantity']);
            $orderItem->price = floatval($item['price']);
            $orderItem->shoe_size = $item['size'] ?? null;
            
            if (!$orderItem->create()) {
                Response::error('Failed to add item to order', [], 400);
            }
        }

        Response::success([
            'id' => $order->id,
            'order_number' => $order_number
        ], 'Order created successfully', 201);
    } else {
        Response::error('Order creation failed', [], 400);
    }
}

/**
 * Handle update order status
 */
function handleUpdateOrderStatus($order, $id) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['status'])) {
        Response::validation(['status' => 'Status is required']);
    }

    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        Response::validation(['status' => 'Invalid status']);
    }

    if ($order->updateStatus($id, $data['status'])) {
        // Also update related payment status
        // If order is delivered/completed, mark payment as completed
        if ($data['status'] === 'delivered' || $data['status'] === 'completed') {
            try {
                $payment = new Payment($GLOBALS['conn']);
                $paymentResult = $payment->getByOrderId($id);
                if ($paymentResult && $paymentResult->num_rows > 0) {
                    $paymentRecord = $paymentResult->fetch_assoc();
                    $payment->updateStatus($paymentRecord['id'], 'completed');
                }
            } catch (Exception $e) {
                // Log error but don't fail the order update
                error_log('Payment update error: ' . $e->getMessage());
            }
        }
        
        // Also update related delivery status to match order status
        try {
            $delivery = new Delivery($GLOBALS['conn']);
            $deliveryResult = $delivery->getByOrderId($id);
            if ($deliveryResult && $deliveryResult->num_rows > 0) {
                $deliveryRecord = $deliveryResult->fetch_assoc();
                // Map order status to delivery status
                $deliveryStatus = $data['status']; // pending, processing, shipped, delivered, cancelled
                $delivery->updateStatus($deliveryRecord['id'], $deliveryStatus);
            }
        } catch (Exception $e) {
            // Log error but don't fail the order update
            error_log('Delivery update error: ' . $e->getMessage());
        }
        
        Response::success([], 'Order status updated successfully');
    } else {
        Response::error('Order status update failed', [], 400);
    }
}

/**
 * Handle get order statistics
 */
function handleGetOrderStatistics($order) {
    $result = $order->getStatistics();
    $stats = $result->fetch_assoc();

    Response::success($stats, 'Order statistics retrieved successfully');
}

$db->disconnect();
?>

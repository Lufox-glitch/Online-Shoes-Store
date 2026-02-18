<?php
/**
 * Payments API Endpoints
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
require_once __DIR__ . '/../models/Payment.php';
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
$payment = new Payment($conn);
$auth = new Auth($conn);

try {
    switch ($action) {
        case 'create':
            $auth->require();
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            handleCreatePayment($payment, $auth);
            break;

        case 'get-order-payment':
            $auth->require();
            if ($method !== 'GET' || !$id) {
                Response::error('Order ID required', [], 400);
            }
            handleGetOrderPayment($payment, $auth, $id);
            break;

        case 'update-status':
            $auth->requireRole('owner');
            if ($method !== 'PUT' || !$id) {
                Response::error('Payment ID required', [], 400);
            }
            handleUpdatePaymentStatus($payment, $id);
            break;

        case 'my-payments':
            $auth->require();
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetUserPayments($payment, $auth);
            break;

        case 'list':
            $auth->requireRole('owner');
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetAllPayments($payment);
            break;

        case 'update-by-order':
            $auth->requireRole('owner');
            if ($method !== 'PUT' || !$id) {
                Response::error('Order ID required', [], 400);
            }
            handleUpdatePaymentStatusByOrderId($payment, $id);
            break;

        default:
            Response::error('Endpoint not found', [], 404);
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), [], 500);
}

/**
 * Handle create payment
 */
function handleCreatePayment($payment, $auth) {
    // For FormData, use $_POST and $_FILES instead of json_decode
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;
    $payment_method = isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method']) : null;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;

    if (!$order_id || !$payment_method || !$amount) {
        Response::error('Missing required fields', [], 400);
    }

    $payment_screenshot = null;

    // Validate payment method
    $valid_methods = ['esewa', 'khalti', 'mobile-banking', 'cash-on-delivery'];
    if (!in_array(strtolower($payment_method), $valid_methods)) {
        Response::error('Invalid payment method', [], 400);
    }

    // For non-COD methods, screenshot is required
    if ($payment_method !== 'cash-on-delivery' && !isset($_FILES['payment_screenshot'])) {
        Response::error('Payment screenshot is required for this payment method', [], 400);
    }

    // Handle screenshot upload if provided (only for non-COD methods)
    if (isset($_FILES['payment_screenshot']) && $payment_method !== 'cash-on-delivery') {
        $screenshot = $_FILES['payment_screenshot'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = basename($screenshot['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            Response::error('Invalid file type. Only JPG, PNG, GIF allowed', [], 400);
        }

        if ($screenshot['size'] > 5 * 1024 * 1024) { // 5MB limit
            Response::error('File size exceeds 5MB limit', [], 400);
        }

        // Create uploads directory if it doesn't exist
        $uploads_dir = __DIR__ . '/../../payments-uploads';
        if (!is_dir($uploads_dir)) {
            if (!@mkdir($uploads_dir, 0777, true)) {
                Response::error('Failed to create uploads directory. Check server permissions.', [], 500);
            }
        }

        // Verify directory is writable
        if (!is_writable($uploads_dir)) {
            Response::error('Uploads directory is not writable. Check permissions.', [], 500);
        }

        // Generate unique filename
        $new_filename = 'payment_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_path = $uploads_dir . '/' . $new_filename;

        if (move_uploaded_file($screenshot['tmp_name'], $upload_path)) {
            $payment_screenshot = '/Online-Shoes-Store/payments-uploads/' . $new_filename;
        } else {
            Response::error('Failed to upload screenshot. Please try again.', [], 500);
        }
    }

    try {
        $id = $payment->create($order_id, $payment_method, $amount, $payment_screenshot);
        Response::success(['id' => $id], 'Payment created successfully', 201);
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get payment by order ID
 */
function handleGetOrderPayment($payment, $auth, $order_id) {
    $user = $auth->getUser();

    try {
        $result = $payment->getByOrderId($order_id);

        if ($result->num_rows === 0) {
            Response::error('Payment not found', [], 404);
        }

        $payment_data = $result->fetch_assoc();
        Response::success($payment_data, 'Payment retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle update payment status
 */
function handleUpdatePaymentStatus($payment, $id) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['status'])) {
        Response::error('Status is required', [], 400);
    }

    $status = htmlspecialchars($data['status']);
    $transaction_id = $data['transaction_id'] ?? null;

    $valid_statuses = ['pending', 'completed', 'failed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        Response::error('Invalid status', [], 400);
    }

    try {
        $payment->updateStatus($id, $status, $transaction_id);
        Response::success([], 'Payment status updated successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get user payments
 */
function handleGetUserPayments($payment, $auth) {
    $user = $auth->getUser();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    try {
        $result = $payment->getUserPayments($user['id'], $limit, $offset);
        $payments = [];

        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }

        Response::success([
            'payments' => $payments,
            'limit' => $limit,
            'offset' => $offset
        ], 'Payments retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get all payments (owner only)
 */
function handleGetAllPayments($payment) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    try {
        $result = $payment->getAll($limit, $offset);
        $payments = [];

        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }

        Response::success([
            'payments' => $payments,
            'limit' => $limit,
            'offset' => $offset
        ], 'Payments retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle update payment status by order ID
 */
function handleUpdatePaymentStatusByOrderId($payment, $order_id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $status = isset($input['status']) ? htmlspecialchars($input['status']) : null;

        if (!$status) {
            Response::error('Status required', [], 400);
        }

        // Get payment for this order
        $result = $payment->getByOrderId($order_id);
        $paymentRecord = $result->fetch_assoc();

        if (!$paymentRecord) {
            Response::error('Payment not found for this order', [], 404);
        }

        // Update the payment status
        $payment->updateStatus($paymentRecord['id'], $status);
        Response::success([], 'Payment status updated successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}
?>

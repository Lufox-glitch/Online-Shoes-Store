<?php
/**
 * Reviews API Endpoints
 */

// Start session FIRST
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../models/Order.php';
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
$review = new Review($conn);
$order = new Order($conn);
$auth = new Auth($conn);

try {
    switch ($action) {
        case 'create':
            $auth->require();
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            handleCreateReview($review, $auth, $order);
            break;

        case 'get-product-reviews':
            if ($method !== 'GET' || !$id) {
                Response::error('Product ID required', [], 400);
            }
            handleGetProductReviews($review, $id);
            break;

        case 'get-product-rating':
            if ($method !== 'GET' || !$id) {
                Response::error('Product ID required', [], 400);
            }
            handleGetProductRating($review, $id);
            break;

        case 'update':
            $auth->require();
            if ($method !== 'PUT' || !$id) {
                Response::error('Review ID required', [], 400);
            }
            handleUpdateReview($review, $id, $auth);
            break;

        case 'delete':
            $auth->require();
            if ($method !== 'DELETE' || !$id) {
                Response::error('Review ID required', [], 400);
            }
            handleDeleteReview($review, $id, $auth);
            break;

        case 'my-reviews':
            $auth->require();
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetUserReviews($review, $auth);
            break;

        default:
            Response::error('Endpoint not found', [], 404);
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), [], 500);
}

/**
 * Handle create review
 */
function handleCreateReview($review, $auth, $order) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['product_id']) || !isset($data['rating'])) {
        Response::error('Missing required fields', [], 400);
    }

    $product_id = intval($data['product_id']);
    $rating = intval($data['rating']);
    $comment = $data['comment'] ?? null;
    $user = $auth->getUser();
    $user_id = $user['id'];

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        Response::error('Rating must be between 1 and 5', [], 400);
    }

    // Check if user has purchased this product
    $has_purchased = $review->hasUserPurchased($user_id, $product_id);

    // Check if user has already reviewed
    if ($review->hasUserReviewed($user_id, $product_id)) {
        Response::error('You have already reviewed this product', [], 400);
    }

    try {
        $id = $review->create($product_id, $user_id, $rating, $comment, $has_purchased);
        Response::success(['id' => $id], 'Review created successfully', 201);
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get product reviews
 */
function handleGetProductReviews($review, $product_id) {
    $product_id = intval($product_id);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    try {
        $result = $review->getByProductId($product_id, $limit, $offset);
        $reviews = [];

        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        Response::success([
            'reviews' => $reviews,
            'limit' => $limit,
            'offset' => $offset
        ], 'Reviews retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get product rating
 */
function handleGetProductRating($review, $product_id) {
    $product_id = intval($product_id);

    try {
        $result = $review->getAverageRating($product_id);
        $rating_data = $result->fetch_assoc();

        Response::success($rating_data, 'Product rating retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle update review
 */
function handleUpdateReview($review, $id, $auth) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['rating'])) {
        Response::error('Rating is required', [], 400);
    }

    $rating = intval($data['rating']);
    $comment = $data['comment'] ?? null;

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        Response::error('Rating must be between 1 and 5', [], 400);
    }

    // Check ownership
    $result = $review->getById($id);
    if ($result->num_rows === 0) {
        Response::error('Review not found', [], 404);
    }

    $review_data = $result->fetch_assoc();
    $user = $auth->getUser();

    if ($review_data['user_id'] != $user['id']) {
        Response::error('Access denied', [], 403);
    }

    try {
        $review->update($id, $rating, $comment);
        Response::success([], 'Review updated successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle delete review
 */
function handleDeleteReview($review, $id, $auth) {
    // Check ownership
    $result = $review->getById($id);
    if ($result->num_rows === 0) {
        Response::error('Review not found', [], 404);
    }

    $review_data = $result->fetch_assoc();
    $user = $auth->getUser();

    if ($review_data['user_id'] != $user['id'] && $user['role'] !== 'owner') {
        Response::error('Access denied', [], 403);
    }

    try {
        $review->delete($id);
        Response::success([], 'Review deleted successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}

/**
 * Handle get user reviews
 */
function handleGetUserReviews($review, $auth) {
    $user = $auth->getUser();
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    try {
        $result = $review->getUserReviews($user['id'], $limit, $offset);
        $reviews = [];

        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        Response::success([
            'reviews' => $reviews,
            'limit' => $limit,
            'offset' => $offset
        ], 'User reviews retrieved successfully');
    } catch (Exception $e) {
        Response::error($e->getMessage(), [], 500);
    }
}
?>

<?php
/**
 * Products API Endpoints
 */

// Start session FIRST
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Product.php';
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
$product = new Product($conn);
$auth = new Auth($conn);

try {
    switch ($action) {
        case 'list':
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleGetProducts($product);
            break;

        case 'search':
            if ($method !== 'GET') {
                Response::error('Method not allowed', [], 405);
            }
            handleSearchProducts($product);
            break;

        case 'detail':
            if ($method !== 'GET' || !$id) {
                Response::error('Product not found', [], 404);
            }
            handleGetProductDetail($product, $id);
            break;

        case 'create':
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            $auth->requireRole('owner');
            handleCreateProduct($product);
            break;

        case 'update':
            if ($method !== 'PUT' || !$id) {
                Response::error('Product not found', [], 404);
            }
            $auth->requireRole('owner');
            handleUpdateProduct($product, $id);
            break;

        case 'delete':
            if ($method !== 'DELETE' || !$id) {
                Response::error('Product not found', [], 404);
            }
            $auth->requireRole('owner');
            handleDeleteProduct($product, $id);
            break;

        default:
            Response::error('Endpoint not found', [], 404);
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), [], 500);
}

/**
 * Handle get all products
 */
function handleGetProducts($product) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

    $result = $product->getAll($limit, $offset, $category_id);
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    Response::success([
        'products' => $products,
        'limit' => $limit,
        'offset' => $offset,
        'total' => count($products)
    ], 'Products retrieved successfully');
}

/**
 * Handle search products
 */
function handleSearchProducts($product) {
    $keyword = $_GET['q'] ?? '';

    if (empty($keyword)) {
        Response::validation(['q' => 'Search keyword is required']);
    }

    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $result = $product->search($keyword, $limit);
    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    Response::success(['products' => $products], 'Search results retrieved successfully');
}

/**
 * Handle get product detail
 */
function handleGetProductDetail($product, $id) {
    $result = $product->getById($id);

    if ($result->num_rows === 0) {
        Response::error('Product not found', [], 404);
    }

    Response::success($result->fetch_assoc(), 'Product retrieved successfully');
}

/**
 * Handle create product
 */
function handleCreateProduct($product) {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields (excluding sku - will be auto-generated)
    $errors = Validator::required($data, ['name', 'description', 'price', 'stock', 'category_id']);
    if (!empty($errors)) {
        Response::validation($errors);
    }

    // Validate numeric fields
    if (!Validator::positive($data['price'])) {
        Response::validation(['price' => 'Price must be a positive number']);
    }

    if (!Validator::numeric($data['stock']) || $data['stock'] < 0) {
        Response::validation(['stock' => 'Stock must be a non-negative number']);
    }

    // Handle image upload from base64
    $imageUrl = null;
    if (!empty($data['image']) && strpos($data['image'], 'data:image') === 0) {
        $imageUrl = handleBase64ImageUpload($data['image']);
        if (!$imageUrl) {
            Response::error('Failed to process image', [], 400);
        }
    }

    // Auto-generate SKU if not provided
    $sku = $data['sku'] ?? 'SKU-' . strtoupper(uniqid());

    // Create product
    $product->name = Validator::sanitize($data['name']);
    $product->description = Validator::sanitize($data['description']);
    $product->price = floatval($data['price']);
    $product->stock = intval($data['stock']);
    $product->category_id = intval($data['category_id']);
    $product->image_url = $imageUrl;
    $product->sku = Validator::sanitize($sku);
    $product->is_active = $data['is_active'] ?? 1;

    if ($product->create()) {
        Response::success([], 'Product created successfully', 201);
    } else {
        Response::error('Product creation failed', [], 400);
    }
}

/**
 * Handle base64 image upload
 */
function handleBase64ImageUpload($base64String) {
    try {
        // Extract MIME type and base64 data
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
            $mimeType = $matches[1];
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
            
            // Validate MIME type
            $allowedTypes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
            if (!in_array(strtolower($mimeType), $allowedTypes)) {
                return null;
            }
            
            // Decode base64
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                return null;
            }
            
            // Create uploads directory if not exists
            $uploadsDir = __DIR__ . '/../uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'product_' . time() . '_' . uniqid() . '.' . strtolower($mimeType);
            $filePath = $uploadsDir . '/' . $filename;
            
            // Save file
            if (file_put_contents($filePath, $imageData)) {
                // Return relative URL path for database storage
                return '/Online-Shoes-Store/Backend/uploads/' . $filename;
            }
        }
        return null;
    } catch (Exception $e) {
        error_log('Image upload error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Handle update product
 */
function handleUpdateProduct($product, $id) {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate numeric fields if provided
    if (isset($data['price']) && !Validator::positive($data['price'])) {
        Response::validation(['price' => 'Price must be a positive number']);
    }

    if (isset($data['stock']) && (!Validator::numeric($data['stock']) || $data['stock'] < 0)) {
        Response::validation(['stock' => 'Stock must be a non-negative number']);
    }

    // Get existing product
    $result = $product->getById($id);
    if ($result->num_rows === 0) {
        Response::error('Product not found', [], 404);
    }

    $existing = $result->fetch_assoc();

    // Handle image upload from base64
    $imageUrl = $existing['image_url'];
    if (!empty($data['image']) && strpos($data['image'], 'data:image') === 0) {
        $newImageUrl = handleBase64ImageUpload($data['image']);
        if ($newImageUrl) {
            $imageUrl = $newImageUrl;
        }
    }

    // Update product
    $product->id = $id;
    $product->name = Validator::sanitize($data['name'] ?? $existing['name']);
    $product->description = Validator::sanitize($data['description'] ?? $existing['description']);
    $product->price = floatval($data['price'] ?? $existing['price']);
    $product->stock = intval($data['stock'] ?? $existing['stock']);
    $product->category_id = intval($data['category_id'] ?? $existing['category_id']);
    $product->image_url = $imageUrl;
    $product->sku = Validator::sanitize($data['sku'] ?? $existing['sku']);
    $product->is_active = $data['is_active'] ?? $existing['is_active'];

    if ($product->update()) {
        Response::success([], 'Product updated successfully');
    } else {
        Response::error('Product update failed', [], 400);
    }
}

/**
 * Handle delete product
 */
function handleDeleteProduct($product, $id) {
    $result = $product->getById($id);
    if ($result->num_rows === 0) {
        Response::error('Product not found', [], 404);
    }

    if ($product->delete($id)) {
        Response::success([], 'Product deleted successfully');
    } else {
        Response::error('Product deletion failed', [], 400);
    }
}

$db->disconnect();
?>

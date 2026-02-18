<?php
/**
 * Authentication API Endpoints
 */

// Start session FIRST before any output
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_GET['request'] ?? '', '/'));
$action = $request[0] ?? null;

// Initialize database
$db = new Database();
$conn = $db->connect();
$user = new User($conn);

try {
    switch ($action) {
        case 'register':
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            handleRegister($user, $conn);
            break;

        case 'login':
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            handleLogin($user, $conn);
            break;

        case 'logout':
            handleLogout();
            break;

        case 'profile':
            $auth = new Auth($conn);
            $auth->require();
            handleGetProfile($auth);
            break;

        case 'update-profile':
            if ($method !== 'PUT') {
                Response::error('Method not allowed', [], 405);
            }
            $auth = new Auth($conn);
            $auth->require();
            handleUpdateProfile($user, $auth, $conn);
            break;

        case 'change-password':
            if ($method !== 'POST') {
                Response::error('Method not allowed', [], 405);
            }
            $auth = new Auth($conn);
            $auth->require();
            handleChangePassword($user, $auth, $conn);
            break;

        default:
            Response::error('Endpoint not found', [], 404);
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), [], 500);
}

/**
 * Handle user registration
 */
function handleRegister($user, $conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields (phone is optional)
    $errors = Validator::required($data, ['email', 'first_name', 'last_name', 'password', 'password_confirm']);
    if (!empty($errors)) {
        Response::validation($errors);
    }

    // Validate email
    if (!Validator::email($data['email'])) {
        Response::validation(['email' => 'Invalid email format']);
    }

    // Check if email exists
    $result = $user->getByEmail($data['email']);
    if ($result->num_rows > 0) {
        Response::error('Email already registered', [], 400);
    }

    // Validate password
    if (!Validator::password($data['password'])) {
        Response::validation(['password' => 'Password must be at least 8 characters with uppercase, lowercase, and number']);
    }

    if ($data['password'] !== $data['password_confirm']) {
        Response::validation(['password_confirm' => 'Passwords do not match']);
    }

    // Validate phone only if provided
    $phone = $data['phone'] ?? '';
    if (!empty($phone) && !Validator::phone($phone)) {
        Response::validation(['phone' => 'Invalid phone number']);
    }

    // Create user
    $user->email = $data['email'];
    $user->first_name = Validator::sanitize($data['first_name']);
    $user->last_name = Validator::sanitize($data['last_name']);
    $user->phone = Validator::sanitize($phone);
    $user->password = $data['password'];
    $user->role = $data['role'] ?? 'customer';
    $user->is_active = 1;

    if ($user->create()) {
        Response::success(['message' => 'Registration successful'], 'User registered successfully', 201);
    } else {
        Response::error('Registration failed', [], 400);
    }
}

/**
 * Handle user login
 */
function handleLogin($user, $conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    $errors = Validator::required($data, ['email', 'password']);
    if (!empty($errors)) {
        Response::validation($errors);
    }

    // Get user by email
    $result = $user->getByEmail($data['email']);

    if ($result->num_rows === 0) {
        Response::error('Invalid credentials', [], 401);
    }

    $userData = $result->fetch_assoc();

    // Verify password
    if (!$user->verifyPassword($data['password'], $userData['password'])) {
        Response::error('Invalid credentials', [], 401);
    }

    // Check if account is active
    if ($userData['is_active'] == 0) {
        Response::error('Account is inactive', [], 403);
    }

    // Set session
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['role'] = $userData['role'];

    // Handle remember me
    if ($data['remember_me'] ?? false) {
        $expiry = time() + (86400 * 7); // 7 days
        setcookie('user_token', $userData['id'], $expiry, '/');
    }

    Response::success([
        'user_id' => $userData['id'],
        'email' => $userData['email'],
        'first_name' => $userData['first_name'],
        'last_name' => $userData['last_name'],
        'role' => $userData['role']
    ], 'Login successful');
}

/**
 * Handle logout
 */
function handleLogout() {
    session_destroy();
    setcookie('user_token', '', time() - 3600, '/');
    Response::success([], 'Logout successful');
}

/**
 * Handle get user profile
 */
function handleGetProfile($auth) {
    $user = $auth->getUser();
    if (!$user) {
        Response::error('User not found', [], 404);
    }
    Response::success($user, 'Profile retrieved successfully');
}

/**
 * Handle update user profile
 */
function handleUpdateProfile($user, $auth, $conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    $currentUser = $auth->getUser();

    if (!$currentUser) {
        Response::error('User not found', [], 404);
    }

    // Validate email if provided
    if (isset($data['email']) && $data['email'] !== $currentUser['email']) {
        if (!Validator::email($data['email'])) {
            Response::validation(['email' => 'Invalid email format']);
        }
        $result = $user->getByEmail($data['email']);
        if ($result->num_rows > 0) {
            Response::error('Email already in use', [], 400);
        }
    }

    // Update user
    $user->id = $currentUser['id'];
    $user->email = $data['email'] ?? $currentUser['email'];
    $user->first_name = Validator::sanitize($data['first_name'] ?? $currentUser['first_name']);
    $user->last_name = Validator::sanitize($data['last_name'] ?? $currentUser['last_name']);
    $user->phone = Validator::sanitize($data['phone'] ?? $currentUser['phone']);
    $user->role = $currentUser['role'];
    $user->is_active = $currentUser['is_active'];

    if ($user->update()) {
        Response::success([], 'Profile updated successfully');
    } else {
        Response::error('Profile update failed', [], 400);
    }
}

/**
 * Handle change password
 */
function handleChangePassword($user, $auth, $conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    $currentUser = $auth->getUser();

    if (!$currentUser) {
        Response::error('User not found', [], 404);
    }

    // Validate required fields
    $errors = Validator::required($data, ['current_password', 'new_password', 'new_password_confirm']);
    if (!empty($errors)) {
        Response::validation($errors);
    }

    // Get user with password
    $result = $user->getByEmail($currentUser['email']);
    $userData = $result->fetch_assoc();

    // Verify current password
    if (!$user->verifyPassword($data['current_password'], $userData['password'])) {
        Response::error('Current password is incorrect', [], 401);
    }

    // Validate new password
    if (!Validator::password($data['new_password'])) {
        Response::validation(['new_password' => 'Password must be at least 8 characters with uppercase, lowercase, and number']);
    }

    if ($data['new_password'] !== $data['new_password_confirm']) {
        Response::validation(['new_password_confirm' => 'Passwords do not match']);
    }

    // Update password
    if ($user->updatePassword($currentUser['id'], $data['new_password'])) {
        Response::success([], 'Password changed successfully');
    } else {
        Response::error('Password change failed', [], 400);
    }
}

$db->disconnect();
?>

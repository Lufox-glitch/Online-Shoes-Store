<?php
/**
 * Response Handler Class
 * Standardized API response format
 */

class Response {
    /**
     * Send success response
     */
    public static function success($data = [], $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }

    /**
     * Send error response
     */
    public static function error($message = 'Error', $data = [], $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }

    /**
     * Send validation error
     */
    public static function validation($errors, $statusCode = 422) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => 'Validation Error',
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }

    /**
     * Send unauthorized error
     */
    public static function unauthorized($message = 'Unauthorized Access', $statusCode = 401) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
}
?>

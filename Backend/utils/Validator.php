<?php
/**
 * Input Validation Class
 */

class Validator {
    private $errors = [];

    /**
     * Validate email format
     */
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     */
    public static function password($password, $minLength = 8) {
        if (strlen($password) < $minLength) {
            return false;
        }
        // At least one uppercase, one lowercase, one number
        return preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    /**
     * Sanitize string input
     */
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate required fields
     */
    public static function required($data, $fields) {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field] ?? null)) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $errors;
    }

    /**
     * Validate phone number
     */
    public static function phone($phone) {
        // Remove all spaces, dashes, plus signs, and parentheses
        $digits = preg_replace('/[^\d]/', '', $phone);
        // Check if we have at least 7 digits (more flexible)
        return strlen($digits) >= 7;
    }

    /**
     * Validate numeric
     */
    public static function numeric($value) {
        return is_numeric($value);
    }

    /**
     * Validate positive number
     */
    public static function positive($value) {
        return is_numeric($value) && $value > 0;
    }
}
?>

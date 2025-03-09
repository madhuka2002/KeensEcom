<?php
// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access is not allowed.');
}

/**
 * Sanitize input data
 * @param mixed $data Input data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a secure random token
 * @param int $length Length of the token
 * @return string Generated token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Log system events
 * @param string $message Log message
 * @param string $type Log type (info, error, warning)
 */
function system_log($message, $type = 'info') {
    $log_dir = __DIR__ . '/../logs';
    
    // Ensure logs directory exists
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/' . $type . '_' . date('Y-m-d') . '.log';
    $log_message = date('Y-m-d H:i:s') . " | $type | $message\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body
 * @return bool
 */
function send_email($to, $subject, $message) {
    $headers = [
        'From: noreply@astroshop.com',
        'Reply-To: support@astroshop.com',
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate a secure password
 * @param int $length Password length
 * @return string Generated password
 */
function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+=-{}[]|:;<>,.?';
    $password = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    
    return $password;
}

/**
 * Redirect to a specified page
 * @param string $page Page to redirect to
 * @param array $params Optional query parameters
 */
function redirect($page, $params = []) {
    $query_string = $params ? '?' . http_build_query($params) : '';
    header("Location: $page$query_string");
    exit();
}

/**
 * Check password strength
 * @param string $password Password to check
 * @return int Strength score (0-100)
 */
function check_password_strength($password) {
    $strength = 0;
    
    // Length check
    if (strlen($password) >= 8) $strength += 25;
    if (strlen($password) >= 12) $strength += 25;
    
    // Complexity checks
    if (preg_match('/[a-z]/', $password)) $strength += 25;
    if (preg_match('/[A-Z]/', $password)) $strength += 25;
    if (preg_match('/[0-9]/', $password)) $strength += 25;
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $strength += 25;
    
    return min($strength, 100);
}

/**
 * Get client IP address
 * @return string Client IP address
 */
function get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
}

/**
 * Validate and format phone number
 * @param string $phone Phone number to validate
 * @return string|false Formatted phone number or false if invalid
 */
function validate_phone_number($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Check for valid length (assuming international format)
    if (strlen($phone) < 10 || strlen($phone) > 15) {
        return false;
    }
    
    return $phone;
}

/**
 * Generate a unique filename
 * @param string $original_filename Original filename
 * @return string Unique filename
 */
function generate_unique_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid('', true) . '.' . $extension;
}
<?php
namespace Game;

/**
 * Comprehensive Security Management System
 * 
 * Provides CSRF protection, input validation, rate limiting, secure headers,
 * and other security measures to protect the application from common attacks.
 * 
 * @package Game
 * @author Choose Your Path RPG Team
 * @version 1.0.0
 */
class SecurityManager 
{
    /** @var string CSRF token session key */
    private const CSRF_TOKEN_KEY = '_csrf_token';
    
    /** @var string Rate limiting cache prefix */
    private const RATE_LIMIT_PREFIX = 'rate_limit_';
    
    /** @var array Security configuration */
    private static $config = [
        'csrf_enabled' => true,
        'rate_limiting_enabled' => true,
        'secure_headers_enabled' => true,
        'input_validation_enabled' => true,
        'max_requests_per_minute' => 60,
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutes
    ];
    
    /** @var array Validation rules */
    private static $validationRules = [
        'username' => [
            'required' => true,
            'min_length' => 3,
            'max_length' => 50,
            'pattern' => '/^[a-zA-Z0-9_-]+$/',
            'sanitize' => 'string'
        ],
        'password' => [
            'required' => true,
            'min_length' => 6,
            'max_length' => 255,
            'sanitize' => 'password'
        ],
        'choice' => [
            'required' => true,
            'max_length' => 50,
            'pattern' => '/^[a-zA-Z0-9_-]+$/',
            'sanitize' => 'string'
        ]
    ];
    
    /**
     * Initialize security manager
     * 
     * @param array $config Security configuration
     */
    public static function init(array $config = []): void 
    {
        self::$config = array_merge(self::$config, $config);
        
        if (self::$config['secure_headers_enabled']) {
            self::setSecureHeaders();
        }
        
        // Start session securely if not already started
        if (session_status() === PHP_SESSION_NONE) {
            self::startSecureSession();
        }
    }
    
    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public static function generateCSRFToken(): string 
    {
        if (!self::$config['csrf_enabled']) {
            return '';
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::CSRF_TOKEN_KEY] = $token;
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function validateCSRFToken(string $token): bool 
    {
        if (!self::$config['csrf_enabled']) {
            return true;
        }
        
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return false;
        }
        
        return hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }
    
    /**
     * Get CSRF token HTML input field
     * 
     * @return string HTML input field
     */
    public static function getCSRFField(): string 
    {
        if (!self::$config['csrf_enabled']) {
            return '';
        }
        
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Check rate limiting for IP address
     * 
     * @param string $action Action being performed
     * @param string|null $identifier Custom identifier (defaults to IP)
     * @return bool True if within limits
     */
    public static function checkRateLimit(string $action, ?string $identifier = null): bool 
    {
        if (!self::$config['rate_limiting_enabled']) {
            return true;
        }
        
        $identifier = $identifier ?? self::getClientIP();
        $key = self::RATE_LIMIT_PREFIX . $action . '_' . md5($identifier);
        
        $current = CacheManager::get($key, 0);
        $limit = self::$config['max_requests_per_minute'];
        
        if ($current >= $limit) {
            return false;
        }
        
        CacheManager::set($key, $current + 1, 60); // 1 minute TTL
        
        return true;
    }
    
    /**
     * Check login attempt rate limiting
     * 
     * @param string $username Username attempting login
     * @return bool True if within limits
     */
    public static function checkLoginAttempts(string $username): bool 
    {
        $ip = self::getClientIP();
        $keyIP = self::RATE_LIMIT_PREFIX . 'login_ip_' . md5($ip);
        $keyUser = self::RATE_LIMIT_PREFIX . 'login_user_' . md5($username);
        
        $attemptsIP = CacheManager::get($keyIP, 0);
        $attemptsUser = CacheManager::get($keyUser, 0);
        
        $maxAttempts = self::$config['max_login_attempts'];
        
        return $attemptsIP < $maxAttempts && $attemptsUser < $maxAttempts;
    }
    
    /**
     * Record failed login attempt
     * 
     * @param string $username Username that failed login
     */
    public static function recordFailedLogin(string $username): void 
    {
        $ip = self::getClientIP();
        $keyIP = self::RATE_LIMIT_PREFIX . 'login_ip_' . md5($ip);
        $keyUser = self::RATE_LIMIT_PREFIX . 'login_user_' . md5($username);
        $duration = self::$config['lockout_duration'];
        
        $attemptsIP = CacheManager::get($keyIP, 0) + 1;
        $attemptsUser = CacheManager::get($keyUser, 0) + 1;
        
        CacheManager::set($keyIP, $attemptsIP, $duration);
        CacheManager::set($keyUser, $attemptsUser, $duration);
        
        // Log security event
        ErrorHandler::log('WARNING', "Failed login attempt for user: $username from IP: $ip", [
            'username' => $username,
            'ip' => $ip,
            'attempts_ip' => $attemptsIP,
            'attempts_user' => $attemptsUser
        ]);
    }
    
    /**
     * Clear login attempts for successful login
     * 
     * @param string $username Username that successfully logged in
     */
    public static function clearLoginAttempts(string $username): void 
    {
        $ip = self::getClientIP();
        $keyIP = self::RATE_LIMIT_PREFIX . 'login_ip_' . md5($ip);
        $keyUser = self::RATE_LIMIT_PREFIX . 'login_user_' . md5($username);
        
        CacheManager::delete($keyIP);
        CacheManager::delete($keyUser);
    }
    
    /**
     * Validate input data against rules
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     * @return array Validation result with errors
     */
    public static function validateInput(array $data, array $rules): array 
    {
        if (!self::$config['input_validation_enabled']) {
            return ['valid' => true, 'errors' => [], 'sanitized' => $data];
        }
        
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            // Check required
            if (($fieldRules['required'] ?? false) && empty($value)) {
                $errors[$field] = "Field '$field' is required";
                continue;
            }
            
            if ($value !== null && $value !== '') {
                // Check length
                if (isset($fieldRules['min_length']) && strlen($value) < $fieldRules['min_length']) {
                    $errors[$field] = "Field '$field' must be at least {$fieldRules['min_length']} characters";
                    continue;
                }
                
                if (isset($fieldRules['max_length']) && strlen($value) > $fieldRules['max_length']) {
                    $errors[$field] = "Field '$field' must not exceed {$fieldRules['max_length']} characters";
                    continue;
                }
                
                // Check pattern
                if (isset($fieldRules['pattern']) && !preg_match($fieldRules['pattern'], $value)) {
                    $errors[$field] = "Field '$field' contains invalid characters";
                    continue;
                }
                
                // Sanitize
                $sanitized[$field] = self::sanitizeValue($value, $fieldRules['sanitize'] ?? 'string');
            } else {
                $sanitized[$field] = $value;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }
    
    /**
     * Sanitize a value based on type
     * 
     * @param mixed $value Value to sanitize
     * @param string $type Sanitization type
     * @return mixed Sanitized value
     */
    public static function sanitizeValue($value, string $type) 
    {
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            
            case 'password':
                return $value; // Don't modify passwords
            
            case 'email':
                return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
            
            case 'int':
                return (int) $value;
            
            case 'float':
                return (float) $value;
            
            case 'url':
                return filter_var(trim($value), FILTER_SANITIZE_URL);
            
            default:
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Set secure HTTP headers
     */
    private static function setSecureHeaders(): void 
    {
        // Prevent XSS attacks
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:;");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // HTTPS enforcement (if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Start secure session
     */
    private static function startSecureSession(): void 
    {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private static function getClientIP(): string 
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get validation rules for a field
     * 
     * @param string $field Field name
     * @return array Validation rules
     */
    public static function getValidationRules(string $field): array 
    {
        return self::$validationRules[$field] ?? [];
    }
    
    /**
     * Check if request is from a bot
     * 
     * @return bool True if likely a bot
     */
    public static function isBot(): bool 
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }
}

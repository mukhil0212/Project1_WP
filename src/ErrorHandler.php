<?php
namespace Game;

/**
 * Centralized Error Handling System
 * 
 * Provides consistent error handling, logging, and user-friendly error messages
 * throughout the application. Implements different error levels and contexts.
 * 
 * @package Game
 * @author Choose Your Path RPG Team
 * @version 1.0.0
 */
class ErrorHandler 
{
    /** @var string Log file path */
    private const LOG_FILE = __DIR__ . '/../logs/error.log';
    
    /** @var array Error level mappings */
    private const ERROR_LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    /** @var int Current log level */
    private static $logLevel = 2; // WARNING and above
    
    /** @var bool Whether to display errors to users */
    private static $displayErrors = false;
    
    /**
     * Initialize error handler
     * 
     * @param bool $displayErrors Whether to show errors to users
     * @param int $logLevel Minimum log level to record
     */
    public static function init(bool $displayErrors = false, int $logLevel = 2): void 
    {
        self::$displayErrors = $displayErrors;
        self::$logLevel = $logLevel;
        
        // Ensure log directory exists
        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Set custom error and exception handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $severity Error severity level
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line number where error occurred
     * @return bool True to prevent default PHP error handler
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool 
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorType = self::getErrorType($severity);
        $context = [
            'file' => $file,
            'line' => $line,
            'severity' => $severity,
            'type' => $errorType
        ];
        
        self::log($errorType, $message, $context);
        
        // Display user-friendly error if enabled
        if (self::$displayErrors) {
            self::displayError($errorType, $message, $context);
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param \Throwable $exception The uncaught exception
     */
    public static function handleException(\Throwable $exception): void 
    {
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode()
        ];
        
        self::log('CRITICAL', $exception->getMessage(), $context);
        
        if (self::$displayErrors) {
            self::displayError('CRITICAL', $exception->getMessage(), $context);
        } else {
            self::displayGenericError();
        }
    }
    
    /**
     * Handle fatal errors during shutdown
     */
    public static function handleShutdown(): void 
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $context = [
                'file' => $error['file'],
                'line' => $error['line'],
                'type' => $error['type']
            ];
            
            self::log('CRITICAL', $error['message'], $context);
            
            if (self::$displayErrors) {
                self::displayError('CRITICAL', $error['message'], $context);
            } else {
                self::displayGenericError();
            }
        }
    }
    
    /**
     * Log an error message
     * 
     * @param string $level Error level
     * @param string $message Error message
     * @param array $context Additional context information
     */
    public static function log(string $level, string $message, array $context = []): void 
    {
        $levelNum = self::ERROR_LEVELS[$level] ?? 1;
        
        if ($levelNum < self::$logLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        error_log($logEntry, 3, self::LOG_FILE);
    }
    
    /**
     * Create a standardized error response
     * 
     * @param string $message Error message
     * @param string $code Error code
     * @param array $details Additional error details
     * @return array Standardized error response
     */
    public static function createErrorResponse(string $message, string $code = 'GENERAL_ERROR', array $details = []): array 
    {
        return [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'details' => $details,
                'timestamp' => time()
            ]
        ];
    }
    
    /**
     * Create a standardized success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @return array Standardized success response
     */
    public static function createSuccessResponse($data = null, string $message = 'Operation successful'): array 
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => time()
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
    
    /**
     * Validate required fields in data array
     * 
     * @param array $data Data to validate
     * @param array $required Required field names
     * @return array|null Error response if validation fails, null if valid
     */
    public static function validateRequired(array $data, array $required): ?array 
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return self::createErrorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                'VALIDATION_ERROR',
                ['missing_fields' => $missing]
            );
        }
        
        return null;
    }
    
    /**
     * Sanitize user input
     * 
     * @param mixed $input Input to sanitize
     * @return mixed Sanitized input
     */
    public static function sanitizeInput($input) 
    {
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
        
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return $input;
    }
    
    /**
     * Get error type string from PHP error constant
     * 
     * @param int $severity PHP error severity constant
     * @return string Error type string
     */
    private static function getErrorType(int $severity): string 
    {
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                return 'ERROR';
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'INFO';
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'DEBUG';
            default:
                return 'ERROR';
        }
    }
    
    /**
     * Display error to user
     * 
     * @param string $level Error level
     * @param string $message Error message
     * @param array $context Error context
     */
    private static function displayError(string $level, string $message, array $context): void 
    {
        if (php_sapi_name() === 'cli') {
            echo "ERROR [{$level}]: {$message}\n";
            if (isset($context['file'], $context['line'])) {
                echo "File: {$context['file']} Line: {$context['line']}\n";
            }
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; margin: 1rem; border-radius: 4px;'>";
            echo "<strong>Error [{$level}]:</strong> " . htmlspecialchars($message);
            if (isset($context['file'], $context['line'])) {
                echo "<br><small>File: " . htmlspecialchars($context['file']) . " Line: {$context['line']}</small>";
            }
            echo "</div>";
        }
    }
    
    /**
     * Display generic error message to user
     */
    private static function displayGenericError(): void 
    {
        if (php_sapi_name() === 'cli') {
            echo "An unexpected error occurred. Please try again later.\n";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; margin: 1rem; border-radius: 4px;'>";
            echo "<strong>Oops!</strong> Something went wrong. Please try again later.";
            echo "</div>";
        }
    }
    
    /**
     * Get recent error logs
     * 
     * @param int $lines Number of lines to retrieve
     * @return array Recent log entries
     */
    public static function getRecentLogs(int $lines = 50): array 
    {
        if (!file_exists(self::LOG_FILE)) {
            return [];
        }
        
        $content = file_get_contents(self::LOG_FILE);
        $logLines = explode("\n", $content);
        $logLines = array_filter($logLines); // Remove empty lines
        
        return array_slice($logLines, -$lines);
    }
    
    /**
     * Clear error logs
     * 
     * @return bool True on success
     */
    public static function clearLogs(): bool 
    {
        if (file_exists(self::LOG_FILE)) {
            return unlink(self::LOG_FILE);
        }
        return true;
    }
}

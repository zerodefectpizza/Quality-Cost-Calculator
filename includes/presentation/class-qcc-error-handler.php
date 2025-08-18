<?php
/**
 * QCC Error Handler
 * Zentrale Fehlerbehandlung für den Quality Cost Calculator
 * 
 * @package QualityCostCalculator
 * @subpackage Presentation
 * @since 2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QCC_Error_Handler {
    
    /**
     * Error types
     */
    const ERROR_TYPE_VALIDATION = 'validation';
    const ERROR_TYPE_CALCULATION = 'calculation';
    const ERROR_TYPE_TEMPLATE = 'template';
    const ERROR_TYPE_SYSTEM = 'system';
    const ERROR_TYPE_USER = 'user';
    
    /**
     * Error severity levels
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * Stored errors
     * @var array
     */
    private $errors = array();
    
    /**
     * Error log
     * @var array
     */
    private $error_log = array();
    
    /**
     * Debug mode
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Translation service
     * @var QCC_Translator
     */
    private $translator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->translator = $this->get_translator();
        $this->init_error_handling();
    }
    
    /**
     * Initialize error handling
     */
    private function init_error_handling() {
        // Set custom error handler for QCC
        set_error_handler(array($this, 'handle_php_error'), E_ALL);
        
        // Register shutdown function for fatal errors
        register_shutdown_function(array($this, 'handle_fatal_error'));
        
        // Hook into WordPress error handling
        add_action('wp_loaded', array($this, 'setup_wp_error_handling'));
    }
    
    /**
     * Add an error
     * 
     * @param string $type Error type
     * @param string $message Error message
     * @param string $severity Error severity
     * @param array $context Additional context
     * @return string Error ID
     */
    public function add_error($type, $message, $severity = self::SEVERITY_MEDIUM, $context = array()) {
        $error_id = uniqid('qcc_error_');
        
        $error = array(
            'id' => $error_id,
            'type' => $type,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'timestamp' => current_time('timestamp'),
            'trace' => $this->debug_mode ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : array()
        );
        
        $this->errors[$error_id] = $error;
        $this->log_error($error);
        
        // Trigger action for external handling
        do_action('qcc_error_added', $error);
        
        return $error_id;
    }
    
    /**
     * Handle validation errors
     * 
     * @param array $validation_errors Validation errors
     * @return array Error IDs
     */
    public function handle_validation_errors($validation_errors) {
        $error_ids = array();
        
        foreach ($validation_errors as $field => $field_errors) {
            foreach ($field_errors as $error_message) {
                $context = array(
                    'field' => $field,
                    'input_type' => 'validation'
                );
                
                $error_ids[] = $this->add_error(
                    self::ERROR_TYPE_VALIDATION,
                    $error_message,
                    self::SEVERITY_MEDIUM,
                    $context
                );
            }
        }
        
        return $error_ids;
    }
    
    /**
     * Handle calculation errors
     * 
     * @param Exception $exception Calculation exception
     * @return string Error ID
     */
    public function handle_calculation_error($exception) {
        $context = array(
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'calculation_step' => $this->get_calculation_step_from_trace($exception->getTrace())
        );
        
        return $this->add_error(
            self::ERROR_TYPE_CALCULATION,
            $exception->getMessage(),
            self::SEVERITY_HIGH,
            $context
        );
    }
    
    /**
     * Handle template errors
     * 
     * @param string $template_name Template name
     * @param Exception $exception Template exception
     * @return string Error ID
     */
    public function handle_template_error($template_name, $exception) {
        $context = array(
            'template' => $template_name,
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        );
        
        return $this->add_error(
            self::ERROR_TYPE_TEMPLATE,
            sprintf('Template error in %s: %s', $template_name, $exception->getMessage()),
            self::SEVERITY_HIGH,
            $context
        );
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        // Only handle QCC-related errors
        if (strpos($errfile, 'quality-cost-calculator') === false) {
            return false;
        }
        
        $severity = $this->get_severity_from_php_error($errno);
        
        $context = array(
            'php_error_type' => $errno,
            'file' => $errfile,
            'line' => $errline,
            'error_type' => $this->get_php_error_name($errno)
        );
        
        $this->add_error(
            self::ERROR_TYPE_SYSTEM,
            $errstr,
            $severity,
            $context
        );
        
        return true; // Don't execute PHP internal error handler
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
            // Only handle QCC-related fatal errors
            if (strpos($error['file'], 'quality-cost-calculator') !== false) {
                $context = array(
                    'php_error_type' => $error['type'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'fatal' => true
                );
                
                $this->add_error(
                    self::ERROR_TYPE_SYSTEM,
                    $error['message'],
                    self::SEVERITY_CRITICAL,
                    $context
                );
                
                // Try to show user-friendly error page
                $this->display_fatal_error_page();
            }
        }
    }
    
    /**
     * Get all errors
     * 
     * @param string|null $type Filter by error type
     * @param string|null $severity Filter by severity
     * @return array
     */
    public function get_errors($type = null, $severity = null) {
        $errors = $this->errors;
        
        if ($type) {
            $errors = array_filter($errors, function($error) use ($type) {
                return $error['type'] === $type;
            });
        }
        
        if ($severity) {
            $errors = array_filter($errors, function($error) use ($severity) {
                return $error['severity'] === $severity;
            });
        }
        
        return $errors;
    }
    
    /**
     * Get errors for display
     * 
     * @param string $format Display format (html, json, array)
     * @return mixed
     */
    public function get_errors_for_display($format = 'html') {
        $errors = $this->get_user_facing_errors();
        
        switch ($format) {
            case 'json':
                return wp_json_encode($errors);
                
            case 'html':
                return $this->format_errors_as_html($errors);
                
            case 'array':
            default:
                return $errors;
        }
    }
    
    /**
     * Clear errors
     * 
     * @param string|null $type Clear specific error type
     */
    public function clear_errors($type = null) {
        if ($type) {
            $this->errors = array_filter($this->errors, function($error) use ($type) {
                return $error['type'] !== $type;
            });
        } else {
            $this->errors = array();
        }
    }
    
    /**
     * Check if there are errors
     * 
     * @param string|null $type Check for specific error type
     * @param string|null $severity Check for specific severity
     * @return bool
     */
    public function has_errors($type = null, $severity = null) {
        return !empty($this->get_errors($type, $severity));
    }
    
    /**
     * Get error count
     * 
     * @param string|null $type Count specific error type
     * @param string|null $severity Count specific severity
     * @return int
     */
    public function get_error_count($type = null, $severity = null) {
        return count($this->get_errors($type, $severity));
    }
    
    /**
     * Create user-friendly error message
     * 
     * @param string $error_type Error type
     * @param string $technical_message Technical error message
     * @return string User-friendly message
     */
    public function create_user_friendly_message($error_type, $technical_message) {
        $messages = array(
            self::ERROR_TYPE_VALIDATION => $this->translator->get('validation_error', 'Please check your input values.'),
            self::ERROR_TYPE_CALCULATION => $this->translator->get('calculation_error', 'There was an error calculating the results. Please try again.'),
            self::ERROR_TYPE_TEMPLATE => $this->translator->get('display_error', 'There was an error displaying the results.'),
            self::ERROR_TYPE_SYSTEM => $this->translator->get('system_error', 'A system error occurred. Please try again later.'),
            self::ERROR_TYPE_USER => $technical_message // User errors are already user-friendly
        );
        
        return $messages[$error_type] ?? $this->translator->get('unknown_error', 'An unknown error occurred.');
    }
    
    /**
     * Log error to WordPress error log
     * 
     * @param array $error Error data
     */
    private function log_error($error) {
        $this->error_log[] = $error;
        
        if ($this->debug_mode || $error['severity'] === self::SEVERITY_CRITICAL) {
            $log_message = sprintf(
                '[QCC %s] %s: %s (Context: %s)',
                strtoupper($error['severity']),
                strtoupper($error['type']),
                $error['message'],
                wp_json_encode($error['context'])
            );
            
            error_log($log_message);
        }
    }
    
    /**
     * Get user-facing errors (filtered for security)
     * 
     * @return array
     */
    private function get_user_facing_errors() {
        $user_errors = array();
        
        foreach ($this->errors as $error) {
            $user_error = array(
                'id' => $error['id'],
                'type' => $error['type'],
                'message' => $this->create_user_friendly_message($error['type'], $error['message']),
                'severity' => $error['severity'],
                'timestamp' => $error['timestamp']
            );
            
            // Add safe context information
            if (isset($error['context']['field'])) {
                $user_error['field'] = $error['context']['field'];
            }
            
            $user_errors[] = $user_error;
        }
        
        return $user_errors;
    }
    
    /**
     * Format errors as HTML
     * 
     * @param array $errors Errors to format
     * @return string HTML output
     */
    private function format_errors_as_html($errors) {
        if (empty($errors)) {
            return '';
        }
        
        $html = '<div class="qcc-errors">';
        
        foreach ($errors as $error) {
            $severity_class = 'qcc-error-' . $error['severity'];
            $type_class = 'qcc-error-type-' . $error['type'];
            
            $html .= sprintf(
                '<div class="qcc-error-message %s %s" data-error-id="%s">
                    <div class="qcc-error-content">
                        <strong>%s:</strong> %s
                    </div>
                    <button type="button" class="qcc-error-dismiss" data-error-id="%s">×</button>
                </div>',
                esc_attr($severity_class),
                esc_attr($type_class),
                esc_attr($error['id']),
                esc_html(ucfirst($error['type'])),
                esc_html($error['message']),
                esc_attr($error['id'])
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get calculation step from trace
     * 
     * @param array $trace Stack trace
     * @return string|null
     */
    private function get_calculation_step_from_trace($trace) {
        foreach ($trace as $frame) {
            if (isset($frame['class']) && strpos($frame['class'], 'QCC_') !== false) {
                return $frame['class'] . '::' . $frame['function'];
            }
        }
        return null;
    }
    
    /**
     * Get severity from PHP error level
     * 
     * @param int $errno PHP error number
     * @return string
     */
    private function get_severity_from_php_error($errno) {
        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                return self::SEVERITY_CRITICAL;
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
                return self::SEVERITY_HIGH;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                return self::SEVERITY_MEDIUM;
                
            case E_STRICT:
            case E_DEPRECATED:
                return self::SEVERITY_LOW;
                
            default:
                return self::SEVERITY_MEDIUM;
        }
    }
    
    /**
     * Get PHP error name
     * 
     * @param int $errno PHP error number
     * @return string
     */
    private function get_php_error_name($errno) {
        $error_names = array(
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        );
        
        return $error_names[$errno] ?? 'UNKNOWN_ERROR';
    }
    
    /**
     * Display fatal error page
     */
    private function display_fatal_error_page() {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Calculator Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .error-container { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .error-title { color: #d63384; font-size: 24px; margin-bottom: 20px; }
        .error-message { color: #666; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">Calculator Error</h1>
        <p class="error-message">
            The Quality Cost Calculator encountered a critical error and cannot continue. 
            Please try refreshing the page or contact support if the problem persists.
        </p>
    </div>
</body>
</html>';
        exit;
    }
    
    /**
     * Setup WordPress error handling
     */
    public function setup_wp_error_handling() {
        // Hook into wp_die for better error handling
        add_filter('wp_die_handler', array($this, 'custom_wp_die_handler'));
    }
    
    /**
     * Custom wp_die handler
     * 
     * @param callable $handler Default handler
     * @return callable
     */
    public function custom_wp_die_handler($handler) {
        return array($this, 'handle_wp_die');
    }
    
    /**
     * Handle wp_die calls
     * 
     * @param string $message Error message
     * @param string $title Error title
     * @param array $args Arguments
     */
    public function handle_wp_die($message, $title = '', $args = array()) {
        // Log the wp_die for debugging
        $context = array(
            'title' => $title,
            'args' => $args,
            'wp_die' => true
        );
        
        $this->add_error(
            self::ERROR_TYPE_SYSTEM,
            $message,
            self::SEVERITY_HIGH,
            $context
        );
        
        // Call default handler
        _default_wp_die_handler($message, $title, $args);
    }
    
    /**
     * Get translator instance
     * 
     * @return QCC_Translator|null
     */
    private function get_translator() {
        if (class_exists('QCC_Translator')) {
            return new QCC_Translator();
        }
        
        // Fallback translator
        return new class {
            public function get($key, $default = '') {
                return $default;
            }
        };
    }
    
    /**
     * Get error statistics
     * 
     * @return array
     */
    public function get_error_statistics() {
        $stats = array(
            'total' => count($this->errors),
            'by_type' => array(),
            'by_severity' => array(),
            'recent_errors' => 0
        );
        
        $recent_threshold = current_time('timestamp') - (5 * MINUTE_IN_SECONDS);
        
        foreach ($this->errors as $error) {
            // Count by type
            $type = $error['type'];
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            
            // Count by severity
            $severity = $error['severity'];
            $stats['by_severity'][$severity] = ($stats['by_severity'][$severity] ?? 0) + 1;
            
            // Count recent errors
            if ($error['timestamp'] > $recent_threshold) {
                $stats['recent_errors']++;
            }
        }
        
        return $stats;
    }
}
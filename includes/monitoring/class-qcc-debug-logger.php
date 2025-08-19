<?php
/**
 * QCC Debug Logger
 * 
 * Advanced debugging and logging system with multiple output channels,
 * log levels, filtering, and performance-aware logging.
 * 
 * @package    QCC
 * @subpackage Monitoring
 * @since      3.0.0
 * @author     Quality Cost Calculator Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Debug Logger Class
 * 
 * @since 3.0.0
 */
class QCC_Debug_Logger {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Debug_Logger
     */
    private static $instance = null;
    
    /**
     * Log levels
     * 
     * @since 3.0.0
     * @var array
     */
    const LOG_LEVELS = array(
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    );
    
    /**
     * Configuration
     * 
     * @since 3.0.0
     * @var array
     */
    private $config = array();
    
    /**
     * Log handlers
     * 
     * @since 3.0.0
     * @var array
     */
    private $handlers = array();
    
    /**
     * Log buffer
     * 
     * @since 3.0.0
     * @var array
     */
    private $log_buffer = array();
    
    /**
     * Context data
     * 
     * @since 3.0.0
     * @var array
     */
    private $context = array();
    
    /**
     * Performance tracking
     * 
     * @since 3.0.0
     * @var array
     */
    private $performance = array();
    
    /**
     * Debug mode
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Logging enabled
     * 
     * @since 3.0.0
     * @var bool
     */
    private $logging_enabled = false;
    
    /**
     * Request ID
     * 
     * @since 3.0.0
     * @var string
     */
    private $request_id = '';
    
    /**
     * Log statistics
     * 
     * @since 3.0.0
     * @var array
     */
    private $stats = array(
        'total_logs' => 0,
        'logs_by_level' => array(),
        'buffer_flushes' => 0,
        'start_time' => 0,
        'start_memory' => 0
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->request_id = uniqid('qcc_', true);
        $this->stats['start_time'] = microtime(true);
        $this->stats['start_memory'] = memory_get_usage();
        
        $this->init_config();
        $this->init_context();
        $this->register_handlers();
        $this->register_hooks();
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Debug_Logger
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize configuration
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_config() {
        $defaults = array(
            'enabled' => $this->debug_mode || (defined('QCC_LOGGING') && QCC_LOGGING),
            'log_level' => $this->debug_mode ? 'debug' : 'error',
            'max_log_entries' => 1000,
            'max_log_file_size' => 5 * 1024 * 1024, // 5MB
            'log_rotation' => true,
            'buffer_size' => 50,
            'auto_flush' => true,
            'include_trace' => $this->debug_mode,
            'include_context' => true,
            'include_performance' => true,
            'channels' => array(
                'file' => array(
                    'enabled' => true,
                    'path' => WP_CONTENT_DIR . '/qcc-debug.log',
                    'format' => '[{timestamp}] [{level}] [{channel}] {message} {context}'
                ),
                'console' => array(
                    'enabled' => $this->debug_mode,
                    'admin_only' => true
                ),
                'email' => array(
                    'enabled' => false,
                    'recipients' => array(get_option('admin_email')),
                    'level_threshold' => 'error',
                    'rate_limit' => 3600 // seconds
                )
            ),
            'filters' => array(
                'exclude_functions' => array('wp_verify_nonce', 'wp_create_nonce'),
                'exclude_classes' => array(),
                'exclude_files' => array(),
                'include_only_qcc' => false
            ),
            'format' => array(
                'timestamp_format' => 'Y-m-d H:i:s',
                'include_microseconds' => true,
                'include_memory_usage' => true,
                'include_request_id' => true
            )
        );
        
        $this->config = apply_filters('qcc_debug_logger_config', $defaults);
        $this->logging_enabled = $this->config['enabled'];
    }
    
    /**
     * Initialize context data
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_context() {
        $this->context = array(
            'request_id' => $this->request_id,
            'session_id' => session_id() ?: 'no_session',
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $this->get_current_url(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'is_admin' => is_admin(),
            'is_ajax' => defined('DOING_AJAX') && DOING_AJAX,
            'is_cli' => defined('WP_CLI') && WP_CLI,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'qcc_version' => defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : 'unknown',
            'memory_start' => $this->stats['start_memory'],
            'time_start' => $this->stats['start_time']
        );
    }
    
    /**
     * Register log handlers
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_handlers() {
        if (!$this->logging_enabled) {
            return;
        }
        
        // File handler
        if ($this->config['channels']['file']['enabled']) {
            $this->add_handler('file', array($this, 'handle_file_log'));
        }
        
        // Console handler
        if ($this->config['channels']['console']['enabled']) {
            $this->add_handler('console', array($this, 'handle_console_log'));
        }
        
        // Email handler
        if ($this->config['channels']['email']['enabled']) {
            $this->add_handler('email', array($this, 'handle_email_log'));
        }
    }
    
    /**
     * Register WordPress hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_hooks() {
        if (!$this->logging_enabled) {
            return;
        }
        
        // Flush buffer on shutdown
        add_action('shutdown', array($this, 'flush_buffer'), 0);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('wp_ajax_qcc_debug_clear_log', array($this, 'ajax_clear_log'));
            add_action('wp_ajax_qcc_debug_download_log', array($this, 'ajax_download_log'));
            add_action('wp_ajax_qcc_debug_get_logs', array($this, 'ajax_get_logs'));
        }
        
        // Console output
        if ($this->config['channels']['console']['enabled']) {
            add_action('wp_footer', array($this, 'output_console_logs'));
            add_action('admin_footer', array($this, 'output_console_logs'));
        }
        
        // QCC specific hooks
        add_action('qcc_error', array($this, 'log_qcc_error'), 10, 2);
        add_action('qcc_warning', array($this, 'log_qcc_warning'), 10, 2);
        add_action('qcc_info', array($this, 'log_qcc_info'), 10, 2);
        add_action('qcc_debug', array($this, 'log_qcc_debug'), 10, 2);
    }
    
    /**
     * Add log handler
     * 
     * @since 3.0.0
     * @param string   $name Handler name
     * @param callable $handler Handler function
     * @return void
     */
    public function add_handler($name, $handler) {
        if (is_callable($handler)) {
            $this->handlers[$name] = $handler;
        }
    }
    
    /**
     * Remove log handler
     * 
     * @since 3.0.0
     * @param string $name Handler name
     * @return void
     */
    public function remove_handler($name) {
        unset($this->handlers[$name]);
    }
    
    /**
     * Backward compatible static logging method.
     *
     * Supports both the new signature log( $level, $message, $context, $channel )
     * and the legacy usage log( $message, $level_or_channel ).
     *
     * @since 3.0.1
     * @param mixed  $arg1    Log level or message depending on usage.
     * @param mixed  $arg2    Message or level/channel depending on usage.
     * @param array  $context Optional context data.
     * @param string $channel Optional channel when level is provided.
     * @return void
     */
    public static function log($arg1, $arg2 = '', $context = array(), $channel = 'qcc') {
        $instance = self::get_instance();

        // Determine if first argument is a valid level
        $arg1_lower = strtolower((string) $arg1);
        if (isset(self::LOG_LEVELS[$arg1_lower])) {
            $level   = $arg1_lower;
            $message = $arg2;
        } else {
            $message = $arg1;
            $arg2_lower = strtolower((string) $arg2);
            if (isset(self::LOG_LEVELS[$arg2_lower])) {
                $level = $arg2_lower;
            } else {
                $level   = 'debug';
                $channel = $arg2 ? $arg2 : $channel;
            }
        }

        $instance->add_log($level, $message, $context, $channel);
    }

    /**
     * Core logging implementation used by the static wrapper.
     *
     * @since 3.0.0
     * @param string $level   Log level
     * @param string $message Log message
     * @param array  $context Context data
     * @param string $channel Log channel
     * @return void
     */
    private function add_log($level, $message, $context = array(), $channel = 'qcc') {
        if (!$this->logging_enabled || !$this->should_log($level)) {
            return;
        }

        // Filter check
        if (!$this->passes_filters($context)) {
            return;
        }

        $log_entry = $this->create_log_entry($level, $message, $context, $channel);

        // Update statistics
        $this->update_stats($level);

        // Add to buffer
        $this->log_buffer[] = $log_entry;

        // Auto flush if buffer is full or for critical levels
        if ($this->config['auto_flush'] &&
            (count($this->log_buffer) >= $this->config['buffer_size'] ||
             in_array($level, array('emergency', 'alert', 'critical', 'error')))) {
            $this->flush_buffer();
        }
    }

    /**
     * Static shortcut for error level logging.
     *
     * @since 3.0.1
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param string $channel Optional log channel
     * @return void
     */
    public static function log_error($message, $context = array(), $channel = 'qcc') {
        self::log('error', $message, $context, $channel);
    }

    /**
     * Static shortcut for warning level logging.
     *
     * @since 3.0.1
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param string $channel Optional log channel
     * @return void
     */
    public static function log_warning($message, $context = array(), $channel = 'qcc') {
        self::log('warning', $message, $context, $channel);
    }

    /**
     * Static shortcut for info level logging.
     *
     * @since 3.0.1
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param string $channel Optional log channel
     * @return void
     */
    public static function log_info($message, $context = array(), $channel = 'qcc') {
        self::log('info', $message, $context, $channel);
    }

    /**
     * Static shortcut for debug level logging.
     *
     * @since 3.0.1
     * @param string $message Log message
     * @param array  $context Optional context data
     * @param string $channel Optional log channel
     * @return void
     */
    public static function log_debug($message, $context = array(), $channel = 'qcc') {
        self::log('debug', $message, $context, $channel);
    }
    
    /**
     * Create log entry
     * 
     * @since 3.0.0
     * @param string $level Log level
     * @param string $message Log message
     * @param array  $context Context data
     * @param string $channel Log channel
     * @return array Log entry
     */
    private function create_log_entry($level, $message, $context, $channel) {
        $timestamp = microtime(true);
        
        $entry = array(
            'timestamp' => $timestamp,
            'formatted_time' => $this->format_timestamp($timestamp),
            'level' => $level,
            'level_numeric' => self::LOG_LEVELS[$level] ?? 7,
            'message' => $message,
            'channel' => $channel,
            'context' => array_merge($this->context, $context),
            'memory_usage' => memory_get_usage(),
            'memory_peak' => memory_get_peak_usage(),
            'execution_time' => $timestamp - $this->context['time_start'],
            'request_id' => $this->request_id
        );
        
        // Add backtrace if enabled
        if ($this->config['include_trace']) {
            $entry['trace'] = $this->get_formatted_backtrace();
        }
        
        // Add performance data if enabled
        if ($this->config['include_performance']) {
            $entry['performance'] = $this->get_performance_data();
        }
        
        return $entry;
    }
    
    /**
     * Check if log level should be logged
     * 
     * @since 3.0.0
     * @param string $level Log level
     * @return bool True if should log
     */
    private function should_log($level) {
        $current_level = self::LOG_LEVELS[$this->config['log_level']] ?? 7;
        $message_level = self::LOG_LEVELS[$level] ?? 7;
        
        return $message_level <= $current_level;
    }
    
    /**
     * Check if log passes filters
     * 
     * @since 3.0.0
     * @param array $context Context data
     * @return bool True if passes filters
     */
    private function passes_filters($context) {
        $filters = $this->config['filters'];
        
        // Check if only QCC logs should be included
        if ($filters['include_only_qcc']) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            $is_qcc = false;
            
            foreach ($backtrace as $trace) {
                if (isset($trace['file']) && strpos($trace['file'], 'qcc') !== false) {
                    $is_qcc = true;
                    break;
                }
            }
            
            if (!$is_qcc) {
                return false;
            }
        }
        
        // Check excluded functions
        if (!empty($filters['exclude_functions'])) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            foreach ($backtrace as $trace) {
                if (isset($trace['function']) && in_array($trace['function'], $filters['exclude_functions'])) {
                    return false;
                }
            }
        }
        
        // Check excluded classes
        if (!empty($filters['exclude_classes'])) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            foreach ($backtrace as $trace) {
                if (isset($trace['class']) && in_array($trace['class'], $filters['exclude_classes'])) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Update statistics
     * 
     * @since 3.0.0
     * @param string $level Log level
     * @return void
     */
    private function update_stats($level) {
        $this->stats['total_logs']++;
        
        if (!isset($this->stats['logs_by_level'][$level])) {
            $this->stats['logs_by_level'][$level] = 0;
        }
        $this->stats['logs_by_level'][$level]++;
    }
    
    /**
     * Process log entry through handlers
     * 
     * @since 3.0.0
     * @param array $log_entry Log entry
     * @return void
     */
    private function process_log_entry($log_entry) {
        foreach ($this->handlers as $name => $handler) {
            try {
                call_user_func($handler, $log_entry);
            } catch (Exception $e) {
                // Prevent infinite loops in error handling
                error_log("QCC Debug Logger Handler Error [{$name}]: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Flush log buffer
     * 
     * @since 3.0.0
     * @return void
     */
    public function flush_buffer() {
        if (empty($this->log_buffer)) {
            return;
        }
        
        foreach ($this->log_buffer as $log_entry) {
            $this->process_log_entry($log_entry);
        }
        
        $this->log_buffer = array();
        $this->stats['buffer_flushes']++;
    }
    
    /**
     * File log handler
     * 
     * @since 3.0.0
     * @param array $log_entry Log entry
     * @return void
     */
    public function handle_file_log($log_entry) {
        $config = $this->config['channels']['file'];
        $log_file = $config['path'];
        
        // Check file size and rotate if necessary
        if ($this->config['log_rotation'] && file_exists($log_file)) {
            if (filesize($log_file) > $this->config['max_log_file_size']) {
                $this->rotate_log_file($log_file);
            }
        }
        
        // Format log message
        $formatted_message = $this->format_log_message($log_entry, $config['format']);
        
        // Ensure directory exists
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Write to file
        file_put_contents($log_file, $formatted_message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Console log handler
     * 
     * @since 3.0.0
     * @param array $log_entry Log entry
     * @return void
     */
    public function handle_console_log($log_entry) {
        // Store for later output in footer
        if (!isset($this->performance['console_logs'])) {
            $this->performance['console_logs'] = array();
        }
        
        $this->performance['console_logs'][] = $log_entry;
    }
    
    /**
     * Email log handler
     * 
     * @since 3.0.0
     * @param array $log_entry Log entry
     * @return void
     */
    public function handle_email_log($log_entry) {
        $config = $this->config['channels']['email'];
        
        // Check level threshold
        $threshold_level = self::LOG_LEVELS[$config['level_threshold']] ?? 3;
        if ($log_entry['level_numeric'] > $threshold_level) {
            return;
        }
        
        // Check rate limit
        $rate_limit_key = 'qcc_debug_email_' . date('Y-m-d-H');
        $sent_count = get_transient($rate_limit_key) ?: 0;
        
        if ($sent_count >= 5) { // Max 5 emails per hour
            return;
        }
        
        // Send email
        $subject = 'QCC Debug Alert - ' . strtoupper($log_entry['level']);
        $message = $this->format_email_message($log_entry);
        
        foreach ($config['recipients'] as $recipient) {
            wp_mail($recipient, $subject, $message);
        }
        
        // Update rate limit
        set_transient($rate_limit_key, $sent_count + 1, $config['rate_limit']);
    }
    
    /**
     * Format log message
     * 
     * @since 3.0.0
     * @param array  $log_entry Log entry
     * @param string $format Format template
     * @return string Formatted message
     */
    private function format_log_message($log_entry, $format) {
        $replacements = array(
            '{timestamp}' => $log_entry['formatted_time'],
            '{level}' => strtoupper($log_entry['level']),
            '{channel}' => $log_entry['channel'],
            '{message}' => $log_entry['message'],
            '{context}' => $this->format_context($log_entry['context']),
            '{memory}' => size_format($log_entry['memory_usage']),
            '{time}' => number_format($log_entry['execution_time'], 4) . 's',
            '{request_id}' => $log_entry['request_id']
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
    
    /**
     * Format email message
     * 
     * @since 3.0.0
     * @param array $log_entry Log entry
     * @return string Email message
     */
    private function format_email_message($log_entry) {
        $message = "QCC Debug Alert\n\n";
        $message .= "Level: " . strtoupper($log_entry['level']) . "\n";
        $message .= "Channel: " . $log_entry['channel'] . "\n";
        $message .= "Time: " . $log_entry['formatted_time'] . "\n";
        $message .= "Message: " . $log_entry['message'] . "\n\n";
        
        if (!empty($log_entry['context'])) {
            $message .= "Context:\n";
            foreach ($log_entry['context'] as $key => $value) {
                if (is_scalar($value)) {
                    $message .= "  {$key}: {$value}\n";
                }
            }
        }
        
        if (isset($log_entry['trace'])) {
            $message .= "\nStack Trace:\n" . implode("\n", $log_entry['trace']);
        }
        
        return $message;
    }
    
    /**
     * Format context for display
     * 
     * @since 3.0.0
     * @param array $context Context data
     * @return string Formatted context
     */
    private function format_context($context) {
        if (empty($context)) {
            return '';
        }
        
        $formatted = array();
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $formatted[] = "{$key}={$value}";
            } elseif (is_array($value)) {
                $formatted[] = "{$key}=" . json_encode($value);
            }
        }
        
        return '[' . implode(', ', $formatted) . ']';
    }
    
    /**
     * Format timestamp
     * 
     * @since 3.0.0
     * @param float $timestamp Timestamp
     * @return string Formatted timestamp
     */
    private function format_timestamp($timestamp) {
        $format = $this->config['format']['timestamp_format'];
        
        if ($this->config['format']['include_microseconds']) {
            $micro = sprintf("%06d", ($timestamp - floor($timestamp)) * 1000000);
            return date($format, $timestamp) . '.' . $micro;
        }
        
        return date($format, $timestamp);
    }
    
    /**
     * Get formatted backtrace
     * 
     * @since 3.0.0
     * @return array Formatted backtrace
     */
    private function get_formatted_backtrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $formatted = array();
        
        foreach ($backtrace as $i => $trace) {
            if ($i < 3) continue; // Skip logger calls
            
            $file = $trace['file'] ?? 'unknown';
            $line = $trace['line'] ?? 0;
            $function = $trace['function'] ?? 'unknown';
            $class = isset($trace['class']) ? $trace['class'] . '::' : '';
            
            $formatted[] = "#{$i} " . basename($file) . "({$line}): {$class}{$function}()";
        }
        
        return $formatted;
    }
    
    /**
     * Get performance data
     * 
     * @since 3.0.0
     * @return array Performance data
     */
    private function get_performance_data() {
        global $wpdb;
        
        return array(
            'memory_current' => memory_get_usage(),
            'memory_peak' => memory_get_peak_usage(),
            'db_queries' => $wpdb->num_queries ?? 0,
            'execution_time' => microtime(true) - $this->context['time_start'],
            'included_files' => count(get_included_files())
        );
    }
    
    /**
     * Get current URL
     * 
     * @since 3.0.0
     * @return string Current URL
     */
    private function get_current_url() {
        if (defined('WP_CLI') && WP_CLI) {
            return 'CLI';
        }
        
        if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['REQUEST_URI'])) {
            return 'Unknown';
        }
        
        return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get user IP address
     * 
     * @since 3.0.0
     * @return string User IP
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'REMOTE_ADDR');
        
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
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Rotate log file
     * 
     * @since 3.0.0
     * @param string $log_file Log file path
     * @return void
     */
    private function rotate_log_file($log_file) {
        $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        
        if (rename($log_file, $backup_file)) {
            // Keep only last 5 backup files
            $backup_pattern = dirname($log_file) . '/' . basename($log_file) . '.*.bak';
            $backup_files = glob($backup_pattern);
            
            if (count($backup_files) > 5) {
                sort($backup_files);
                $files_to_delete = array_slice($backup_files, 0, -5);
                
                foreach ($files_to_delete as $file) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Output console logs
     * 
     * @since 3.0.0
     * @return void
     */
    public function output_console_logs() {
        if (!$this->config['channels']['console']['enabled']) {
            return;
        }
        
        if ($this->config['channels']['console']['admin_only'] && !current_user_can('manage_options')) {
            return;
        }
        
        $console_logs = $this->performance['console_logs'] ?? array();
        
        if (empty($console_logs)) {
            return;
        }
        
        ?>
        <script>
        (function() {
            if (typeof console === 'undefined') return;
            
            console.groupCollapsed('🔍 QCC Debug Logs (%d entries)', <?php echo count($console_logs); ?>);
            
            <?php foreach ($console_logs as $log): ?>
            console.<?php echo $this->get_console_method($log['level']); ?>(
                '[<?php echo esc_js($log['formatted_time']); ?>] [<?php echo esc_js(strtoupper($log['level'])); ?>] [<?php echo esc_js($log['channel']); ?>] <?php echo esc_js($log['message']); ?>',
                <?php echo json_encode($this->prepare_console_context($log['context'])); ?>
            );
            <?php endforeach; ?>
            
            console.groupEnd();
            
            // Performance summary
            console.info('📊 QCC Debug Summary', {
                'Total Logs': <?php echo $this->stats['total_logs']; ?>,
                'Buffer Flushes': <?php echo $this->stats['buffer_flushes']; ?>,
                'Memory Usage': '<?php echo size_format(memory_get_usage()); ?>',
                'Execution Time': '<?php echo number_format(microtime(true) - $this->stats['start_time'], 3); ?>s'
            });
        })();
        </script>
        <?php
    }
    
    /**
     * Get console method for log level
     * 
     * @since 3.0.0
     * @param string $level Log level
     * @return string Console method
     */
    private function get_console_method($level) {
        switch ($level) {
            case 'emergency':
            case 'alert':
            case 'critical':
            case 'error':
                return 'error';
            case 'warning':
                return 'warn';
            case 'info':
            case 'notice':
                return 'info';
            case 'debug':
            default:
                return 'log';
        }
    }
    
    /**
     * Prepare context for console output
     * 
     * @since 3.0.0
     * @param array $context Context data
     * @return array Prepared context
     */
    private function prepare_console_context($context) {
        // Remove sensitive data and large objects
        $safe_context = array();
        
        foreach ($context as $key => $value) {
            if (in_array($key, array('user_agent', 'ip_address', 'session_id'))) {
                continue; // Skip sensitive data
            }
            
            if (is_scalar($value) || is_array($value)) {
                $safe_context[$key] = $value;
            }
        }
        
        return $safe_context;
    }
    
    /**
     * Convenience methods for different log levels
     */
    public function emergency($message, $context = array()) {
        self::log('emergency', $message, $context);
    }

    public function alert($message, $context = array()) {
        self::log('alert', $message, $context);
    }

    public function critical($message, $context = array()) {
        self::log('critical', $message, $context);
    }

    public function error($message, $context = array()) {
        self::log('error', $message, $context);
    }

    public function warning($message, $context = array()) {
        self::log('warning', $message, $context);
    }

    public function notice($message, $context = array()) {
        self::log('notice', $message, $context);
    }

    public function info($message, $context = array()) {
        self::log('info', $message, $context);
    }

    public function debug($message, $context = array()) {
        self::log('debug', $message, $context);
    }
    
    /**
     * QCC specific log handlers
     */
    public function log_qcc_error($message, $context = array()) {
        $this->error($message, $context);
    }
    
    public function log_qcc_warning($message, $context = array()) {
        $this->warning($message, $context);
    }
    
    public function log_qcc_info($message, $context = array()) {
        $this->info($message, $context);
    }
    
    public function log_qcc_debug($message, $context = array()) {
        $this->debug($message, $context);
    }
    
    /**
     * Add admin menu
     * 
     * @since 3.0.0
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'qcc-admin',
            'Debug Logger',
            'Debug Logs',
            'manage_options',
            'qcc-debug',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Render admin page
     * 
     * @since 3.0.0
     * @return void
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $stats = $this->get_statistics();
        
        ?>
        <div class="wrap">
            <h1>🔍 QCC Debug Logger</h1>
            
            <!-- Status Card -->
            <div class="notice notice-info" style="display: flex; align-items: center; padding: 15px;">
                <span style="font-size: 24px; margin-right: 15px;">
                    <?php echo $this->logging_enabled ? '✅' : '❌'; ?>
                </span>
                <div>
                    <strong>Status:</strong> 
                    <?php echo $this->logging_enabled ? 'Logging Enabled' : 'Logging Disabled'; ?>
                    <br>
                    <small>
                        Level: <code><?php echo strtoupper($this->config['log_level']); ?></code> | 
                        Total Logs: <strong><?php echo $stats['total_logs']; ?></strong> | 
                        Buffer Flushes: <strong><?php echo $stats['buffer_flushes']; ?></strong>
                    </small>
                </div>
            </div>
            
            <!-- Controls -->
            <div class="qcc-debug-controls" style="margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap;">
                <button id="qcc-refresh-logs" class="button button-primary">🔄 Refresh Logs</button>
                <button id="qcc-clear-logs" class="button">🗑️ Clear All Logs</button>
                <button id="qcc-download-logs" class="button">📥 Download Log File</button>
                <button id="qcc-test-logging" class="button">🧪 Test Logging</button>
                
                <select id="qcc-log-level-filter" style="margin-left: 20px;">
                    <option value="">All Levels</option>
                    <option value="emergency">Emergency</option>
                    <option value="alert">Alert</option>
                    <option value="critical">Critical</option>
                    <option value="error">Error</option>
                    <option value="warning">Warning</option>
                    <option value="notice">Notice</option>
                    <option value="info">Info</option>
                    <option value="debug">Debug</option>
                </select>
                
                <input type="text" id="qcc-log-search" placeholder="Search logs..." style="width: 300px;">
            </div>
            
            <!-- Statistics -->
            <div class="qcc-debug-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div class="card" style="padding: 15px; background: #f8f9fa; border-left: 4px solid #007cba;">
                    <h4 style="margin: 0 0 5px 0;">Total Logs</h4>
                    <span style="font-size: 24px; font-weight: bold;"><?php echo $stats['total_logs']; ?></span>
                </div>
                
                <div class="card" style="padding: 15px; background: #f8f9fa; border-left: 4px solid #00a32a;">
                    <h4 style="margin: 0 0 5px 0;">Memory Usage</h4>
                    <span style="font-size: 18px; font-weight: bold;"><?php echo size_format($stats['memory_usage']); ?></span>
                </div>
                
                <div class="card" style="padding: 15px; background: #f8f9fa; border-left: 4px solid #f56e28;">
                    <h4 style="margin: 0 0 5px 0;">Log File Size</h4>
                    <span style="font-size: 18px; font-weight: bold;"><?php echo $stats['file_size_formatted']; ?></span>
                </div>
                
                <div class="card" style="padding: 15px; background: #f8f9fa; border-left: 4px solid #8c8f94;">
                    <h4 style="margin: 0 0 5px 0;">Uptime</h4>
                    <span style="font-size: 18px; font-weight: bold;"><?php echo $stats['uptime']; ?>s</span>
                </div>
            </div>
            
            <!-- Log Level Distribution -->
            <?php if (!empty($stats['logs_by_level'])): ?>
            <div class="qcc-log-distribution" style="margin: 20px 0;">
                <h3>Log Level Distribution</h3>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($stats['logs_by_level'] as $level => $count): ?>
                        <div class="log-level-badge" style="
                            padding: 8px 12px; 
                            border-radius: 20px; 
                            background: <?php echo $this->get_level_color($level); ?>; 
                            color: white; 
                            font-weight: bold;
                            font-size: 12px;
                        ">
                            <?php echo strtoupper($level); ?>: <?php echo $count; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Log Display -->
            <div class="qcc-log-display" style="margin: 20px 0;">
                <h3>Recent Log Entries</h3>
                <div id="qcc-debug-logs" style="
                    background: #1e1e1e; 
                    color: #f0f0f0; 
                    padding: 15px; 
                    font-family: 'Consolas', 'Monaco', 'Courier New', monospace; 
                    font-size: 12px; 
                    height: 500px; 
                    overflow-y: auto;
                    border-radius: 5px;
                    border: 1px solid #ddd;
                ">
                    <div style="color: #888; text-align: center; padding: 50px;">
                        Loading logs...
                    </div>
                </div>
            </div>
            
            <!-- Configuration -->
            <div class="qcc-debug-config" style="margin: 30px 0;">
                <h3>Configuration</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Logging Enabled</th>
                        <td><?php echo $this->logging_enabled ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Log Level</th>
                        <td><code><?php echo strtoupper($this->config['log_level']); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row">File Logging</th>
                        <td><?php echo $this->config['channels']['file']['enabled'] ? '✅ Enabled' : '❌ Disabled'; ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Console Logging</th>
                        <td><?php echo $this->config['channels']['console']['enabled'] ? '✅ Enabled' : '❌ Disabled'; ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Log File Path</th>
                        <td><code><?php echo esc_html($this->config['channels']['file']['path']); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row">Buffer Size</th>
                        <td><?php echo $this->config['buffer_size']; ?> entries</td>
                    </tr>
                    <tr>
                        <th scope="row">Max File Size</th>
                        <td><?php echo size_format($this->config['max_log_file_size']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Include Trace</th>
                        <td><?php echo $this->config['include_trace'] ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                </table>
                
                <h4>Enable Logging</h4>
                <p>To enable debug logging, add these lines to your <code>wp-config.php</code>:</p>
                <pre style="background: #f1f1f1; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>// Enable QCC Debug Logging
define('QCC_LOGGING', true);

// For full debug mode with console output
define('QCC_DEBUG', true);</code></pre>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let isAutoRefresh = false;
            let refreshInterval;
            
            function loadLogs() {
                const level = $('#qcc-log-level-filter').val();
                const search = $('#qcc-log-search').val();
                
                $.post(ajaxurl, {
                    action: 'qcc_debug_get_logs',
                    level: level,
                    search: search,
                    nonce: '<?php echo wp_create_nonce('qcc_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#qcc-debug-logs').html(response.data.logs);
                        
                        // Auto-scroll to bottom
                        const container = document.getElementById('qcc-debug-logs');
                        container.scrollTop = container.scrollHeight;
                    } else {
                        $('#qcc-debug-logs').html('<div style="color: #ff6b6b; text-align: center; padding: 50px;">Error loading logs: ' + (response.data || 'Unknown error') + '</div>');
                    }
                }).fail(function() {
                    $('#qcc-debug-logs').html('<div style="color: #ff6b6b; text-align: center; padding: 50px;">Failed to load logs. Please try again.</div>');
                });
            }
            
            function startAutoRefresh() {
                if (!isAutoRefresh) {
                    isAutoRefresh = true;
                    $('#qcc-refresh-logs').text('⏸️ Stop Auto-Refresh');
                    refreshInterval = setInterval(loadLogs, 5000); // Refresh every 5 seconds
                }
            }
            
            function stopAutoRefresh() {
                if (isAutoRefresh) {
                    isAutoRefresh = false;
                    $('#qcc-refresh-logs').text('🔄 Refresh Logs');
                    clearInterval(refreshInterval);
                }
            }
            
            // Event handlers
            $('#qcc-refresh-logs').click(function() {
                if (isAutoRefresh) {
                    stopAutoRefresh();
                } else {
                    startAutoRefresh();
                }
            });
            
            $('#qcc-log-level-filter').change(function() {
                stopAutoRefresh();
                loadLogs();
            });
            
            $('#qcc-log-search').on('input', function() {
                stopAutoRefresh();
                loadLogs();
            });
            
            $('#qcc-clear-logs').click(function() {
                if (confirm('Are you sure you want to clear all logs? This action cannot be undone.')) {
                    $.post(ajaxurl, {
                        action: 'qcc_debug_clear_log',
                        nonce: '<?php echo wp_create_nonce('qcc_debug_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            loadLogs();
                            alert('Logs cleared successfully!');
                        } else {
                            alert('Error clearing logs: ' + (response.data || 'Unknown error'));
                        }
                    });
                }
            });
            
            $('#qcc-download-logs').click(function() {
                window.location = ajaxurl + '?action=qcc_debug_download_log&nonce=<?php echo wp_create_nonce('qcc_debug_nonce'); ?>';
            });
            
            $('#qcc-test-logging').click(function() {
                // Test all log levels
                $.post(ajaxurl, {
                    action: 'qcc_debug_test_logging',
                    nonce: '<?php echo wp_create_nonce('qcc_debug_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        setTimeout(loadLogs, 1000); // Reload logs after 1 second
                        alert('Test logs generated successfully!');
                    }
                });
            });
            
            // Initial load
            loadLogs();
        });
        </script>
        
        <style>
        .qcc-debug-controls button:hover,
        .qcc-debug-controls select:hover,
        .qcc-debug-controls input:hover {
            transform: translateY(-1px);
            transition: transform 0.2s ease;
        }
        
        #qcc-debug-logs::-webkit-scrollbar {
            width: 8px;
        }
        
        #qcc-debug-logs::-webkit-scrollbar-track {
            background: #2a2a2a;
        }
        
        #qcc-debug-logs::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        
        #qcc-debug-logs::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
        
        .log-level-badge {
            transition: transform 0.2s ease;
        }
        
        .log-level-badge:hover {
            transform: scale(1.05);
        }
        </style>
        <?php
    }
    
    /**
     * Get color for log level
     * 
     * @since 3.0.0
     * @param string $level Log level
     * @return string Color code
     */
    private function get_level_color($level) {
        $colors = array(
            'emergency' => '#8b0000',
            'alert' => '#dc143c',
            'critical' => '#ff0000',
            'error' => '#ff4500',
            'warning' => '#ffa500',
            'notice' => '#4169e1',
            'info' => '#008000',
            'debug' => '#808080'
        );
        
        return $colors[$level] ?? '#666666';
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_clear_log() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'qcc_debug_nonce')) {
            wp_die('Access denied');
        }
        
        // Clear file log
        if ($this->config['channels']['file']['enabled']) {
            $log_file = $this->config['channels']['file']['path'];
            if (file_exists($log_file)) {
                file_put_contents($log_file, '');
            }
        }
        
        // Clear buffer and stats
        $this->log_buffer = array();
        $this->stats['total_logs'] = 0;
        $this->stats['logs_by_level'] = array();
        
        wp_send_json_success();
    }
    
    public function ajax_download_log() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['nonce'], 'qcc_debug_nonce')) {
            wp_die('Access denied');
        }
        
        $log_file = $this->config['channels']['file']['path'];
        
        if (file_exists($log_file)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="qcc-debug-' . date('Y-m-d') . '.log"');
            header('Content-Length: ' . filesize($log_file));
            readfile($log_file);
        } else {
            wp_die('Log file not found');
        }
        
        exit;
    }
    
    public function ajax_get_logs() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'qcc_debug_nonce')) {
            wp_die('Access denied');
        }
        
        $level_filter = sanitize_text_field($_POST['level'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $logs = $this->get_recent_logs(100, $level_filter, $search);
        $formatted_logs = $this->format_logs_for_display($logs);
        
        wp_send_json_success(array('logs' => $formatted_logs));
    }
    
    /**
     * Get recent logs
     * 
     * @since 3.0.0
     * @param int    $limit Limit
     * @param string $level_filter Level filter
     * @param string $search Search term
     * @return array Recent logs
     */
    private function get_recent_logs($limit = 100, $level_filter = '', $search = '') {
        $logs = array();
        $log_file = $this->config['channels']['file']['path'];
        
        if (!file_exists($log_file)) {
            return $logs;
        }
        
        $file_logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$file_logs) {
            return $logs;
        }
        
        $file_logs = array_slice($file_logs, -$limit * 2); // Get more lines to account for filtering
        
        foreach ($file_logs as $line) {
            // Apply level filter
            if (!empty($level_filter) && stripos($line, '[' . strtoupper($level_filter) . ']') === false) {
                continue;
            }
            
            // Apply search filter
            if (!empty($search) && stripos($line, $search) === false) {
                continue;
            }
            
            $logs[] = $line;
        }
        
        return array_slice($logs, -$limit); // Final limit
    }
    
    /**
     * Format logs for display
     * 
     * @since 3.0.0
     * @param array $logs Log entries
     * @return string Formatted logs
     */
    private function format_logs_for_display($logs) {
        if (empty($logs)) {
            return '<div style="color: #888; text-align: center; padding: 50px;">No logs found matching your criteria</div>';
        }
        
        $output = '';
        
        foreach ($logs as $log) {
            $color = '#e0e0e0';
            
            // Color code by level
            if (stripos($log, '[EMERGENCY]') !== false || stripos($log, '[ALERT]') !== false) {
                $color = '#ff4757'; // Red
            } elseif (stripos($log, '[CRITICAL]') !== false || stripos($log, '[ERROR]') !== false) {
                $color = '#ff6b81'; // Light red
            } elseif (stripos($log, '[WARNING]') !== false) {
                $color = '#ffa726'; // Orange
            } elseif (stripos($log, '[NOTICE]') !== false) {
                $color = '#42a5f5'; // Blue
            } elseif (stripos($log, '[INFO]') !== false) {
                $color = '#66bb6a'; // Green
            } elseif (stripos($log, '[DEBUG]') !== false) {
                $color = '#90a4ae'; // Gray
            }
            
            $output .= '<div style="color: ' . $color . '; margin-bottom: 3px; font-family: monospace; line-height: 1.4; word-wrap: break-word;">' . 
                       esc_html($log) . '</div>';
        }
        
        return $output;
    }
    
    /**
     * Get logging statistics
     * 
     * @since 3.0.0
     * @return array Logging statistics
     */
    public function get_statistics() {
        $log_file = $this->config['channels']['file']['path'];
        $file_size = file_exists($log_file) ? filesize($log_file) : 0;
        
        return array(
            'enabled' => $this->logging_enabled,
            'log_level' => $this->config['log_level'],
            'total_logs' => $this->stats['total_logs'],
            'logs_by_level' => $this->stats['logs_by_level'],
            'buffer_size' => count($this->log_buffer),
            'buffer_flushes' => $this->stats['buffer_flushes'],
            'handlers_count' => count($this->handlers),
            'memory_usage' => memory_get_usage(),
            'file_size' => $file_size,
            'file_size_formatted' => size_format($file_size),
            'uptime' => number_format(microtime(true) - $this->stats['start_time'], 2),
            'request_id' => $this->request_id
        );
    }
    
    /**
     * Enable logging
     * 
     * @since 3.0.0
     * @return void
     */
    public function enable_logging() {
        $this->logging_enabled = true;
        $this->config['enabled'] = true;
        update_option('qcc_debug_logging_enabled', true);
    }
    
    /**
     * Disable logging
     * 
     * @since 3.0.0
     * @return void
     */
    public function disable_logging() {
        $this->logging_enabled = false;
        $this->config['enabled'] = false;
        update_option('qcc_debug_logging_enabled', false);
    }
    
    /**
     * Set log level
     * 
     * @since 3.0.0
     * @param string $level Log level
     * @return void
     */
    public function set_log_level($level) {
        if (array_key_exists($level, self::LOG_LEVELS)) {
            $this->config['log_level'] = $level;
            update_option('qcc_debug_log_level', $level);
        }
    }
    
    /**
     * Get log level
     * 
     * @since 3.0.0
     * @return string Current log level
     */
    public function get_log_level() {
        return $this->config['log_level'];
    }
    
    /**
     * Add context data
     * 
     * @since 3.0.0
     * @param array $context Context data to add
     * @return void
     */
    public function add_context($context) {
        $this->context = array_merge($this->context, $context);
    }
    
    /**
     * Remove context data
     * 
     * @since 3.0.0
     * @param string $key Context key to remove
     * @return void
     */
    public function remove_context($key) {
        unset($this->context[$key]);
    }
    
    /**
     * Get context data
     * 
     * @since 3.0.0
     * @return array Current context
     */
    public function get_context() {
        return $this->context;
    }
    
    /**
     * Check if logging is enabled
     * 
     * @since 3.0.0
     * @return bool True if enabled
     */
    public function is_enabled() {
        return $this->logging_enabled;
    }
    
    /**
     * Get log file path
     * 
     * @since 3.0.0
     * @return string Log file path
     */
    public function get_log_file_path() {
        return $this->config['channels']['file']['path'];
    }
    
    /**
     * Magic method to log with dynamic level
     * 
     * @since 3.0.0
     * @param string $method Method name (log level)
     * @param array  $arguments Method arguments
     * @return void
     */
    public function __call($method, $arguments) {
        if (array_key_exists($method, self::LOG_LEVELS)) {
            $message = $arguments[0] ?? '';
            $context = $arguments[1] ?? array();
            self::log($method, $message, $context);
        }
    }
    
    /**
     * Destructor - ensure buffer is flushed
     * 
     * @since 3.0.0
     */
    public function __destruct() {
        $this->flush_buffer();
    }
}
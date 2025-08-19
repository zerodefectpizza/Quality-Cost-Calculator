<?php
/**
 * QCC Error Tracker - Simplified Version
 * 
 * Simple yet effective error tracking and monitoring system for capturing,
 * categorizing, and reporting errors with essential features only.
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
 * QCC Error Tracker Class - Simplified
 * 
 * @since 3.0.0
 */
class QCC_Error_Tracker {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Error_Tracker
     */
    private static $instance = null;
    
    /**
     * Error storage
     * 
     * @since 3.0.0
     * @var array
     */
    private $errors = array();
    
    /**
     * Configuration
     * 
     * @since 3.0.0
     * @var array
     */
    private $config = array();
    
    /**
     * Error statistics
     * 
     * @since 3.0.0
     * @var array
     */
    private $stats = array(
        'total_errors' => 0,
        'errors_by_category' => array(),
        'errors_by_severity' => array(),
        'last_error_time' => 0
    );
    
    /**
     * Debug mode
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Error tracking enabled
     * 
     * @since 3.0.0
     * @var bool
     */
    private $tracking_enabled = false;
    
    /**
     * Request ID for error correlation
     * 
     * @since 3.0.0
     * @var string
     */
    private $request_id = '';
    
    /**
     * Error categories
     * 
     * @since 3.0.0
     * @var array
     */
    private $categories = array(
        'php' => array('name' => 'PHP Errors', 'icon' => '🐘', 'color' => '#8b5cf6'),
        'js' => array('name' => 'JavaScript', 'icon' => '🟨', 'color' => '#f59e0b'),
        'db' => array('name' => 'Database', 'icon' => '🗄️', 'color' => '#ef4444'),
        'calculation' => array('name' => 'Calculation', 'icon' => '🧮', 'color' => '#10b981'),
        'system' => array('name' => 'System', 'icon' => '⚙️', 'color' => '#6366f1')
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->request_id = uniqid('qcc_', true);
        
        $this->init_config();
        $this->register_hooks();
        $this->register_handlers();
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Error_Tracker
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
        $this->config = array(
            'enabled' => $this->debug_mode || (defined('QCC_ERROR_TRACKING') && QCC_ERROR_TRACKING),
            'max_errors' => 100,
            'max_error_age' => 7 * 24 * 3600, // 7 days
            'log_to_file' => true,
            'log_file' => WP_CONTENT_DIR . '/qcc-errors.log',
            'track_js_errors' => true,
            'email_on_critical' => false,
            'notification_email' => get_option('admin_email')
        );
        
        $this->tracking_enabled = $this->config['enabled'];
    }
    
    /**
     * Register WordPress hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_hooks() {
        if (!$this->tracking_enabled) {
            return;
        }
        
        // QCC specific hooks
        add_action('qcc_error', array($this, 'handle_qcc_error'), 10, 3);
        add_action('qcc_calculation_error', array($this, 'handle_calculation_error'), 10, 2);
        
        // JavaScript error tracking
        if ($this->config['track_js_errors']) {
            add_action('wp_footer', array($this, 'output_js_tracker'));
            add_action('wp_ajax_qcc_js_error', array($this, 'handle_js_error'));
            add_action('wp_ajax_nopriv_qcc_js_error', array($this, 'handle_js_error'));
        }
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_notices', array($this, 'show_error_notices'));
            add_action('wp_ajax_qcc_clear_errors', array($this, 'ajax_clear_errors'));
        }
        
        // Cleanup
        add_action('qcc_daily_cleanup', array($this, 'cleanup_old_errors'));
        if (!wp_next_scheduled('qcc_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'qcc_daily_cleanup');
        }
    }
    
    /**
     * Register basic error handlers
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_handlers() {
        if (!$this->tracking_enabled) {
            return;
        }
        
        // Fatal error handler
        register_shutdown_function(array($this, 'handle_fatal_error'));
        
        // Set error handler for PHP errors
        set_error_handler(array($this, 'handle_php_error'));
    }
    
    /**
     * Track error
     * 
     * @since 3.0.0
     * @param string $message Error message
     * @param string $category Error category
     * @param string $severity Error severity
     * @param array  $context Additional context
     * @return string|false Error ID or false on failure
     */
    public function track_error($message, $category = 'system', $severity = 'error', $context = array()) {
        if (!$this->tracking_enabled || empty($message)) {
            return false;
        }
        
        $error_id = md5($message . $category . date('Y-m-d'));
        
        $error_data = array(
            'id' => $error_id,
            'message' => $message,
            'category' => $category,
            'severity' => $severity,
            'timestamp' => time(),
            'url' => $this->get_current_url(),
            'user_id' => get_current_user_id(),
            'context' => $context,
            'request_id' => $this->request_id,
            'count' => 1,
            'first_seen' => time(),
            'last_seen' => time()
        );
        
        // Check if error already exists
        if (isset($this->errors[$error_id])) {
            $this->errors[$error_id]['count']++;
            $this->errors[$error_id]['last_seen'] = time();
        } else {
            $this->errors[$error_id] = $error_data;
        }
        
        // Update statistics
        $this->update_stats($error_data);
        
        // Log to file
        if ($this->config['log_to_file']) {
            $this->log_to_file($error_data);
        }
        
        // Send email for critical errors
        if ($severity === 'critical' && $this->config['email_on_critical']) {
            $this->send_critical_alert($error_data);
        }
        
        // Debug output
        if ($this->debug_mode) {
            error_log("QCC Error [{$severity}]: {$message}");
        }
        
        do_action('qcc_error_tracked', $error_data);
        
        return $error_id;
    }
    
    /**
     * Handle PHP errors
     * 
     * @since 3.0.0
     * @param int    $errno Error number
     * @param string $errstr Error string
     * @param string $errfile Error file
     * @param int    $errline Error line
     * @return bool
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        // Skip if error reporting is disabled
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $severity = 'error';
        if (in_array($errno, array(E_ERROR, E_PARSE, E_CORE_ERROR))) {
            $severity = 'critical';
        } elseif (in_array($errno, array(E_WARNING, E_USER_WARNING))) {
            $severity = 'warning';
        } elseif (in_array($errno, array(E_NOTICE, E_USER_NOTICE))) {
            $severity = 'notice';
        }
        
        $context = array(
            'file' => basename($errfile),
            'line' => $errline,
            'type' => $errno
        );
        
        $this->track_error($errstr, 'php', $severity, $context);
        
        return false; // Let WordPress handle it too
    }
    
    /**
     * Handle fatal errors
     * 
     * @since 3.0.0
     * @return void
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR))) {
            $context = array(
                'file' => basename($error['file']),
                'line' => $error['line'],
                'type' => $error['type']
            );
            
            $this->track_error($error['message'], 'php', 'critical', $context);
        }
    }
    
    /**
     * Handle QCC specific errors
     * 
     * @since 3.0.0
     * @param string $message Error message
     * @param string $category Category
     * @param array  $context Context
     * @return void
     */
    public function handle_qcc_error($message, $category = 'system', $context = array()) {
        $this->track_error($message, $category, 'error', $context);
    }
    
    /**
     * Handle calculation errors
     * 
     * @since 3.0.0
     * @param string $message Error message
     * @param array  $calculation_data Calculation data
     * @return void
     */
    public function handle_calculation_error($message, $calculation_data = array()) {
        $context = array(
            'calculation_type' => $calculation_data['type'] ?? 'unknown',
            'step' => $calculation_data['step'] ?? 'unknown'
        );
        
        $this->track_error($message, 'calculation', 'error', $context);
    }
    
    /**
     * Handle JavaScript errors via AJAX
     * 
     * @since 3.0.0
     * @return void
     */
    public function handle_js_error() {
        if (!$this->tracking_enabled) {
            wp_die('Error tracking disabled');
        }
        
        $message = sanitize_text_field($_POST['message'] ?? '');
        $file = sanitize_text_field($_POST['file'] ?? '');
        $line = intval($_POST['line'] ?? 0);
        
        if (empty($message)) {
            wp_die('Invalid error data');
        }
        
        $context = array(
            'file' => basename($file),
            'line' => $line,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $error_id = $this->track_error($message, 'js', 'error', $context);
        
        wp_send_json_success(array('error_id' => $error_id));
    }
    
    /**
     * Output JavaScript error tracker
     * 
     * @since 3.0.0
     * @return void
     */
    public function output_js_tracker() {
        if (!$this->config['track_js_errors']) {
            return;
        }
        
        ?>
        <script>
        window.addEventListener('error', function(e) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'qcc_js_error',
                    message: e.message || 'Unknown error',
                    file: e.filename || window.location.href,
                    line: e.lineno || 0
                })
            }).catch(() => {}); // Silently fail
        });
        </script>
        <?php
    }
    
    /**
     * Update statistics
     * 
     * @since 3.0.0
     * @param array $error_data Error data
     * @return void
     */
    private function update_stats($error_data) {
        $this->stats['total_errors']++;
        $this->stats['last_error_time'] = $error_data['timestamp'];
        
        $category = $error_data['category'];
        if (!isset($this->stats['errors_by_category'][$category])) {
            $this->stats['errors_by_category'][$category] = 0;
        }
        $this->stats['errors_by_category'][$category]++;
        
        $severity = $error_data['severity'];
        if (!isset($this->stats['errors_by_severity'][$severity])) {
            $this->stats['errors_by_severity'][$severity] = 0;
        }
        $this->stats['errors_by_severity'][$severity]++;
    }
    
    /**
     * Log error to file
     * 
     * @since 3.0.0
     * @param array $error_data Error data
     * @return bool True on success
     */
    private function log_to_file($error_data) {
        $log_entry = sprintf(
            "[%s] [%s] [%s] %s - %s\n",
            date('Y-m-d H:i:s', $error_data['timestamp']),
            strtoupper($error_data['severity']),
            strtoupper($error_data['category']),
            $error_data['message'],
            $error_data['url']
        );
        
        return file_put_contents($this->config['log_file'], $log_entry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Send critical error alert
     * 
     * @since 3.0.0
     * @param array $error_data Error data
     * @return bool True on success
     */
    private function send_critical_alert($error_data) {
        $subject = 'QCC Critical Error Alert';
        $message = "A critical error occurred in QCC:\n\n";
        $message .= "Error: " . $error_data['message'] . "\n";
        $message .= "Category: " . $error_data['category'] . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s', $error_data['timestamp']) . "\n";
        $message .= "URL: " . $error_data['url'] . "\n";
        
        return wp_mail($this->config['notification_email'], $subject, $message);
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
        
        if (!isset($_SERVER['HTTP_HOST'])) {
            return 'Unknown';
        }
        
        return (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get all errors
     * 
     * @since 3.0.0
     * @param array $filters Optional filters
     * @return array Errors
     */
    public function get_errors($filters = array()) {
        $errors = $this->errors;
        
        if (!empty($filters)) {
            $errors = array_filter($errors, function($error) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (isset($error[$key]) && $error[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            });
        }
        
        // Sort by timestamp (newest first)
        uasort($errors, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $errors;
    }
    
    /**
     * Get statistics
     * 
     * @since 3.0.0
     * @return array Statistics
     */
    public function get_statistics() {
        return $this->stats;
    }
    
    /**
     * Clear all errors
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear_all_errors() {
        $this->errors = array();
        $this->stats = array(
            'total_errors' => 0,
            'errors_by_category' => array(),
            'errors_by_severity' => array(),
            'last_error_time' => 0
        );
        
        if ($this->debug_mode) {
            error_log("QCC Error Tracker: Cleared all errors");
        }
    }
    
    /**
     * Cleanup old errors
     * 
     * @since 3.0.0
     * @return int Number of cleaned errors
     */
    public function cleanup_old_errors() {
        $cutoff_time = time() - $this->config['max_error_age'];
        $cleaned = 0;
        
        foreach ($this->errors as $error_id => $error) {
            if ($error['timestamp'] < $cutoff_time) {
                unset($this->errors[$error_id]);
                $cleaned++;
            }
        }
        
        // Limit total errors
        if (count($this->errors) > $this->config['max_errors']) {
            $excess = count($this->errors) - $this->config['max_errors'];
            $errors_by_time = $this->errors;
            uasort($errors_by_time, function($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            });
            
            $i = 0;
            foreach ($errors_by_time as $error_id => $error) {
                if ($i >= $excess) break;
                unset($this->errors[$error_id]);
                $cleaned++;
                $i++;
            }
        }
        
        return $cleaned;
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
            'Error Tracker',
            'Errors',
            'manage_options',
            'qcc-errors',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Show admin error notices
     * 
     * @since 3.0.0
     * @return void
     */
    public function show_error_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $critical_errors = $this->get_errors(array('severity' => 'critical'));
        if (!empty($critical_errors)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>⚠️ QCC Critical Error:</strong> ';
            echo count($critical_errors) . ' critical error(s) detected. ';
            echo '<a href="' . admin_url('admin.php?page=qcc-errors') . '">View Details</a></p>';
            echo '</div>';
        }
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
        $errors = array_slice($this->get_errors(), 0, 20);
        
        ?>
        <div class="wrap">
            <h1>🚨 QCC Error Tracker</h1>
            
            <!-- Status -->
            <div class="notice notice-info">
                <p>
                    <strong>Status:</strong> <?php echo $this->tracking_enabled ? '✅ Enabled' : '❌ Disabled'; ?> | 
                    <strong>Total Errors:</strong> <?php echo $stats['total_errors']; ?> | 
                    <strong>Last Error:</strong> <?php echo $stats['last_error_time'] ? human_time_diff($stats['last_error_time']) . ' ago' : 'None'; ?>
                </p>
            </div>
            
            <!-- Controls -->
            <p>
                <button id="qcc-clear-errors" class="button">🗑️ Clear All Errors</button>
                <button onclick="location.reload()" class="button">🔄 Refresh</button>
            </p>
            
            <!-- Statistics -->
            <?php if (!empty($stats['errors_by_category'])): ?>
            <h3>Error Distribution</h3>
            <div style="display: flex; gap: 10px; margin: 15px 0;">
                <?php foreach ($stats['errors_by_category'] as $category => $count): ?>
                    <?php $cat_info = $this->categories[$category] ?? array('name' => $category, 'icon' => '❓', 'color' => '#666'); ?>
                    <span style="
                        background: <?php echo $cat_info['color']; ?>; 
                        color: white; 
                        padding: 5px 10px; 
                        border-radius: 15px; 
                        font-size: 12px;
                    ">
                        <?php echo $cat_info['icon'] . ' ' . $cat_info['name']; ?>: <?php echo $count; ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Recent Errors -->
            <h3>Recent Errors</h3>
            <?php if (empty($errors)): ?>
                <div class="notice notice-success">
                    <p>🎉 No errors found! Your QCC plugin is running smoothly.</p>
                </div>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Category</th>
                            <th>Severity</th>
                            <th>Message</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errors as $error): ?>
                            <tr>
                                <td><?php echo date('M j, H:i', $error['timestamp']); ?></td>
                                <td>
                                    <?php 
                                    $cat = $this->categories[$error['category']] ?? array('name' => $error['category'], 'icon' => '❓');
                                    echo $cat['icon'] . ' ' . $cat['name']; 
                                    ?>
                                </td>
                                <td>
                                    <span style="
                                        padding: 2px 6px; 
                                        border-radius: 3px; 
                                        font-size: 11px; 
                                        background: <?php echo $this->get_severity_color($error['severity']); ?>; 
                                        color: white;
                                    ">
                                        <?php echo strtoupper($error['severity']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(substr($error['message'], 0, 100)); ?>
                                    <?php if (strlen($error['message']) > 100) echo '...'; ?>
                                    
                                    <?php if (!empty($error['context']['file'])): ?>
                                        <br><small style="color: #666;">
                                            📁 <?php echo esc_html($error['context']['file'] . ':' . ($error['context']['line'] ?? '?')); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo $error['count']; ?></strong>
                                    <?php if ($error['count'] > 1): ?>
                                        <br><small>Last: <?php echo human_time_diff($error['last_seen']); ?> ago</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Configuration Help -->
            <h3>Configuration</h3>
            <p>To enable error tracking, add this to your <code>wp-config.php</code>:</p>
            <pre style="background: #f1f1f1; padding: 10px; border-radius: 5px;"><code>define('QCC_ERROR_TRACKING', true);
define('QCC_DEBUG', true); // For detailed logging</code></pre>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#qcc-clear-errors').click(function() {
                if (confirm('Clear all errors? This cannot be undone.')) {
                    $.post(ajaxurl, {
                        action: 'qcc_clear_errors',
                        nonce: '<?php echo wp_create_nonce('qcc_clear_errors'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Unknown error'));
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get severity color
     * 
     * @since 3.0.0
     * @param string $severity Severity level
     * @return string Color code
     */
    private function get_severity_color($severity) {
        $colors = array(
            'critical' => '#dc2626',
            'error' => '#ef4444',
            'warning' => '#f59e0b',
            'notice' => '#3b82f6'
        );
        
        return $colors[$severity] ?? '#6b7280';
    }
    
    /**
     * AJAX handler for clearing errors
     * 
     * @since 3.0.0
     * @return void
     */
    public function ajax_clear_errors() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'qcc_clear_errors')) {
            wp_die('Access denied');
        }
        
        $this->clear_all_errors();
        
        // Clear log file
        if ($this->config['log_to_file'] && file_exists($this->config['log_file'])) {
            file_put_contents($this->config['log_file'], '');
        }
        
        wp_send_json_success();
    }
    
    /**
     * Enable tracking
     * 
     * @since 3.0.0
     * @return void
     */
    public function enable_tracking() {
        $this->tracking_enabled = true;
    }
    
    /**
     * Disable tracking
     * 
     * @since 3.0.0
     * @return void
     */
    public function disable_tracking() {
        $this->tracking_enabled = false;
    }
    
    /**
     * Check if tracking is enabled
     * 
     * @since 3.0.0
     * @return bool
     */
    public function is_enabled() {
        return $this->tracking_enabled;
    }
    
    /**
     * Get request ID
     * 
     * @since 3.0.0
     * @return string
     */
    public function get_request_id() {
        return $this->request_id;
    }
}
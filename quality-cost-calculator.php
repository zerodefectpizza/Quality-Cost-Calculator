<?php
/**
 * Plugin Name: Quality Cost Calculator
 * Plugin URI: https://github.com/yourusername/quality-cost-calculator
 * Description: A comprehensive web-based tool for calculating quality costs according to the Cost-of-Quality model with COGQ/COPQ analysis. Supports German, English, French, and Chinese with real-time calculations and interactive charts.
 * Version: 1.1.1
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quality-cost-calculator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Update URI: false
 */

// =============================================================================
// SECURITY & BASIC SETUP
// =============================================================================

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Prevent multiple inclusions
if (defined('QCC_PLUGIN_LOADED')) {
    return;
}
define('QCC_PLUGIN_LOADED', true);

// =============================================================================
// CONSTANTS & BASIC DEFINITIONS
// =============================================================================

// Plugin version and paths
if (!defined('QCC_PLUGIN_VERSION')) define('QCC_PLUGIN_VERSION', '1.1.1');
if (!defined('QCC_PLUGIN_FILE')) define('QCC_PLUGIN_FILE', __FILE__);
if (!defined('QCC_PLUGIN_URL')) define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('QCC_PLUGIN_PATH')) define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
if (!defined('QCC_PLUGIN_BASENAME')) define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
if (!defined('QCC_PLUGIN_DIR')) define('QCC_PLUGIN_DIR', dirname(QCC_PLUGIN_BASENAME));

// Database and options
if (!defined('QCC_DB_VERSION')) define('QCC_DB_VERSION', '1.0.0');
if (!defined('QCC_OPTION_PREFIX')) define('QCC_OPTION_PREFIX', 'qcc_');
if (!defined('QCC_TEXT_DOMAIN')) define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');

// Security
if (!defined('QCC_NONCE_ACTION')) define('QCC_NONCE_ACTION', 'qcc_security_nonce');
if (!defined('QCC_CAPABILITY')) define('QCC_CAPABILITY', 'manage_options');

// Debugging
if (!defined('QCC_DEBUG')) define('QCC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
if (!defined('QCC_DEBUG_VERBOSE')) define('QCC_DEBUG_VERBOSE', defined('WP_DEBUG_LOG') && WP_DEBUG_LOG);

// =============================================================================
// DEBUG LOGGER CLASS
// =============================================================================

if (!class_exists('QCC_Debug_Logger')) {
    class QCC_Debug_Logger {
        private static $log_file = null;
        private static $debug_enabled = null;
        private static $session_id = null;
        
        public static function init() {
            self::$debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
            self::$session_id = uniqid('qcc_', true);
            
            if (self::$debug_enabled) {
                // Only initialize log file if wp_upload_dir is available
                if (function_exists('wp_upload_dir')) {
                    $upload_dir = wp_upload_dir();
                    self::$log_file = $upload_dir['basedir'] . '/qcc-debug.log';
                    
                    if (!file_exists(dirname(self::$log_file))) {
                        wp_mkdir_p(dirname(self::$log_file));
                    }
                }
                
                self::log('=== QCC DEBUG SESSION STARTED [' . self::$session_id . '] ===', 'INIT');
                self::log('WordPress Version: ' . (function_exists('get_bloginfo') ? get_bloginfo('version') : 'Unknown'), 'INIT');
                self::log('PHP Version: ' . PHP_VERSION, 'INIT');
                self::log('Plugin Path: ' . QCC_PLUGIN_PATH, 'INIT');
                self::log('Plugin URL: ' . QCC_PLUGIN_URL, 'INIT');
                self::log('Memory Limit: ' . ini_get('memory_limit'), 'INIT');
            }
        }
        
        public static function log($message, $level = 'INFO') {
            if (!self::$debug_enabled) return;
            
            $timestamp = date('Y-m-d H:i:s');
            $memory = size_format(memory_get_usage(true));
            $session = self::$session_id ? '[' . substr(self::$session_id, -8) . ']' : '';
            
            $formatted_message = "[{$timestamp}] [{$level}] {$session} QCC: {$message} (Memory: {$memory})" . PHP_EOL;
            
            // Write to WordPress debug log
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log($formatted_message);
            }
            
            // Write to plugin-specific log if available
            if (self::$log_file && is_writable(dirname(self::$log_file))) {
                file_put_contents(self::$log_file, $formatted_message, FILE_APPEND | LOCK_EX);
            }
        }
        
        public static function log_error($message, $context = array()) {
            $context_str = !empty($context) ? ' | Context: ' . wp_json_encode($context) : '';
            self::log($message . $context_str, 'ERROR');
        }
        
        public static function log_warning($message, $context = array()) {
            $context_str = !empty($context) ? ' | Context: ' . wp_json_encode($context) : '';
            self::log($message . $context_str, 'WARNING');
        }
        
        public static function get_log_file_path() {
            return self::$log_file;
        }
        
        public static function get_session_id() {
            return self::$session_id;
        }
        
        public static function clear_log() {
            if (self::$log_file && file_exists(self::$log_file)) {
                unlink(self::$log_file);
                self::log('Debug log cleared', 'SYSTEM');
                return true;
            }
            return false;
        }
    }
}

// Initialize debug logger
QCC_Debug_Logger::init();
QCC_Debug_Logger::log('Plugin main file loading started', 'INIT');

// =============================================================================
// IMPROVED REQUIREMENTS CHECK
// =============================================================================

if (!function_exists('qcc_check_requirements')) {
    function qcc_check_requirements() {
        QCC_Debug_Logger::log('Starting system requirements check', 'CHECK');
        
        $errors = array();
        $warnings = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $error = sprintf(
                'Quality Cost Calculator requires PHP version 7.4 or higher. Current version: %s',
                PHP_VERSION
            );
            $errors[] = $error;
            QCC_Debug_Logger::log_error('PHP version check failed: ' . $error);
        } else {
            QCC_Debug_Logger::log('PHP version check passed: ' . PHP_VERSION, 'CHECK');
        }
        
        // Check WordPress version (only if get_bloginfo is available)
        if (function_exists('get_bloginfo')) {
            global $wp_version;
            if (version_compare($wp_version, '5.0', '<')) {
                $error = sprintf(
                    'Quality Cost Calculator requires WordPress version 5.0 or higher. Current version: %s',
                    $wp_version
                );
                $errors[] = $error;
                QCC_Debug_Logger::log_error('WordPress version check failed: ' . $error);
            } else {
                QCC_Debug_Logger::log('WordPress version check passed: ' . $wp_version, 'CHECK');
            }
        } else {
            QCC_Debug_Logger::log('WordPress version check skipped - get_bloginfo not available yet', 'CHECK');
        }
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit && $memory_limit !== '-1') {
            $memory_bytes = qcc_convert_memory_to_bytes($memory_limit);
            $required_bytes = qcc_convert_memory_to_bytes('64M');
            
            if ($memory_bytes < $required_bytes) {
                $warning = sprintf(
                    'Quality Cost Calculator recommends at least 64MB memory. Current limit: %s',
                    $memory_limit
                );
                $warnings[] = $warning;
                QCC_Debug_Logger::log_warning('Memory limit warning: ' . $warning);
            } else {
                QCC_Debug_Logger::log('Memory limit check passed: ' . $memory_limit, 'CHECK');
            }
        }
        
        // Check required directories
        $required_dirs = array(
            QCC_PLUGIN_PATH . 'includes/',
            QCC_PLUGIN_PATH . 'assets/',
            QCC_PLUGIN_PATH . 'templates/'
        );
        
        foreach ($required_dirs as $dir) {
            if (!is_dir($dir)) {
                $error = sprintf('Required directory missing: %s', $dir);
                $errors[] = $error;
                QCC_Debug_Logger::log_error('Directory check failed: ' . $error);
            } else {
                QCC_Debug_Logger::log('Directory check passed: ' . $dir, 'CHECK');
            }
        }
        
        // Check WordPress functions (only check if WordPress is loaded)
        if (function_exists('wp_enqueue_script')) {
            $required_functions = array('wp_enqueue_script', 'wp_enqueue_style', 'add_shortcode', 'wp_create_nonce');
            foreach ($required_functions as $func) {
                if (!function_exists($func)) {
                    $error = sprintf('Required WordPress function missing: %s', $func);
                    $errors[] = $error;
                    QCC_Debug_Logger::log_error('WordPress function check failed: ' . $error);
                } else {
                    QCC_Debug_Logger::log('WordPress function check passed: ' . $func, 'CHECK');
                }
            }
        } else {
            QCC_Debug_Logger::log('WordPress function check skipped - WordPress not fully loaded yet', 'CHECK');
        }
        
        if (!empty($errors)) {
            QCC_Debug_Logger::log_error('System requirements check failed', array('errors' => $errors, 'warnings' => $warnings));
            return array('errors' => $errors, 'warnings' => $warnings);
        }
        
        if (!empty($warnings)) {
            QCC_Debug_Logger::log_warning('System requirements check passed with warnings', array('warnings' => $warnings));
            return array('errors' => array(), 'warnings' => $warnings);
        }
        
        QCC_Debug_Logger::log('All system requirements checks passed', 'CHECK');
        return array('errors' => array(), 'warnings' => array());
    }
}

// Helper function for memory conversion
if (!function_exists('qcc_convert_memory_to_bytes')) {
    function qcc_convert_memory_to_bytes($value) {
        if (empty($value) || $value === '-1') return -1;
        
        $value = trim($value);
        $last = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}

// =============================================================================
// MAIN PLUGIN CLASS
// =============================================================================

if (!class_exists('QualityCostCalculator')) {
    
    class QualityCostCalculator {
        
        private static $instance = null;
        private $initialized = false;
        private $activation_errors = array();
        private $load_errors = array();
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            QCC_Debug_Logger::log('QualityCostCalculator constructor called', 'INIT');
            
            // Register activation/deactivation hooks immediately
            register_activation_hook(QCC_PLUGIN_FILE, array($this, 'activate'));
            register_deactivation_hook(QCC_PLUGIN_FILE, array($this, 'deactivate'));
            
            // Initialize on WordPress init hook (when all WordPress functions are available)
            add_action('init', array($this, 'init'), 10);
            
            // Add shortcode early
            add_action('init', array($this, 'register_shortcode'), 5);
            
            // Admin hooks
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_notices', array($this, 'admin_notices'));
            
            // Plugin action links
            add_filter('plugin_action_links_' . QCC_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
            
            QCC_Debug_Logger::log('QualityCostCalculator constructor completed', 'INIT');
        }
        
        public function init() {
            QCC_Debug_Logger::log('Starting plugin initialization on init hook', 'INIT');
            
            try {
                // Check if already initialized
                if ($this->initialized) {
                    QCC_Debug_Logger::log('Plugin already initialized, skipping', 'INIT');
                    return;
                }
                
                // Now check requirements when WordPress is fully loaded
                $requirement_check = qcc_check_requirements();
                
                if (!empty($requirement_check['errors'])) {
                    QCC_Debug_Logger::log_error('Plugin cannot initialize due to requirement failures', $requirement_check['errors']);
                    $this->load_errors = $requirement_check['errors'];
                    return;
                }
                
                // Load text domain
                $this->load_textdomain();
                
                // Set up hooks
                $this->setup_hooks();
                
                // Mark as initialized
                $this->initialized = true;
                
                QCC_Debug_Logger::log('Plugin initialization completed successfully', 'INIT');
                
            } catch (Exception $e) {
                QCC_Debug_Logger::log_error('Plugin initialization failed: ' . $e->getMessage());
                $this->activation_errors[] = 'Initialization failed: ' . $e->getMessage();
            }
        }
        
        public function register_shortcode() {
            QCC_Debug_Logger::log('Registering shortcode', 'SHORTCODE');
            add_shortcode('quality_cost_calculator', array($this, 'render_shortcode'));
        }
        
        public function render_shortcode($atts = array()) {
            QCC_Debug_Logger::log('Rendering shortcode', 'SHORTCODE');
            
            // Parse attributes
            $atts = shortcode_atts(array(
                'language' => 'en',
                'currency' => 'EUR',
                'unit' => '1000000'
            ), $atts, 'quality_cost_calculator');
            
            // Enqueue scripts and styles
            $this->enqueue_frontend_assets();
            
            // Return the HTML content from the original file
            ob_start();
            include_once QCC_PLUGIN_PATH . 'templates/calculator.php';
            return ob_get_clean();
        }
        
        private function load_textdomain() {
            QCC_Debug_Logger::log('Loading text domain', 'I18N');
            
            $domain = QCC_TEXT_DOMAIN;
            $locale = apply_filters('plugin_locale', get_locale(), $domain);
            
            // Try to load from WordPress languages directory first
            $wp_lang_file = WP_LANG_DIR . "/plugins/{$domain}-{$locale}.mo";
            if (file_exists($wp_lang_file)) {
                $loaded = load_textdomain($domain, $wp_lang_file);
                QCC_Debug_Logger::log("Loaded text domain from WP languages: {$wp_lang_file} - " . ($loaded ? 'Success' : 'Failed'), 'I18N');
                if ($loaded) return;
            }
            
            // Fallback to plugin languages directory
            $plugin_lang_dir = QCC_PLUGIN_DIR . '/languages/';
            $loaded = load_plugin_textdomain($domain, false, $plugin_lang_dir);
            QCC_Debug_Logger::log("Loaded text domain from plugin: {$plugin_lang_dir} - " . ($loaded ? 'Success' : 'Failed'), 'I18N');
        }
        
        private function setup_hooks() {
            QCC_Debug_Logger::log('Setting up hooks', 'HOOKS');
            
            // Frontend hooks
            if (!is_admin()) {
                add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_frontend_scripts'));
                add_action('wp_head', array($this, 'add_frontend_head'));
            }
            
            // Admin hooks
            if (is_admin()) {
                add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
                add_action('admin_menu', array($this, 'add_admin_menu'));
            }
        }
        
        public function maybe_enqueue_frontend_scripts() {
            global $post;
            
            // Only load on pages with our shortcode
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
                $this->enqueue_frontend_assets();
            }
        }
        
        private function enqueue_frontend_assets() {
            QCC_Debug_Logger::log('Enqueuing frontend assets', 'SCRIPT');
            
            // Enqueue Chart.js from CDN
            wp_enqueue_script(
                'chart-js',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            // Check if assets exist and enqueue them
            $js_file = QCC_PLUGIN_PATH . 'assets/quality-cost-calculator.js';
            $css_file = QCC_PLUGIN_PATH . 'assets/quality-cost-calculator.css';
            
            if (file_exists($js_file)) {
                wp_enqueue_script(
                    'qcc-frontend',
                    QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js',
                    array('jquery', 'chart-js'),
                    QCC_PLUGIN_VERSION,
                    true
                );
                
                // Localize script
                wp_localize_script('qcc-frontend', 'qcc_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(QCC_NONCE_ACTION),
                    'debug' => QCC_DEBUG,
                    'plugin_version' => QCC_PLUGIN_VERSION
                ));
            } else {
                QCC_Debug_Logger::log_warning('Frontend JavaScript file not found: ' . $js_file);
            }
            
            if (file_exists($css_file)) {
                wp_enqueue_style(
                    'qcc-frontend',
                    QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css',
                    array(),
                    QCC_PLUGIN_VERSION
                );
            } else {
                QCC_Debug_Logger::log_warning('Frontend CSS file not found: ' . $css_file);
            }
        }
        
        public function add_frontend_head() {
            global $post;
            
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
                echo '<style>:root { --qcc-debug: ' . (QCC_DEBUG ? '1' : '0') . '; --qcc-version: "' . QCC_PLUGIN_VERSION . '"; }</style>' . "\n";
                
                if (QCC_DEBUG) {
                    echo '<script>console.log("QCC Debug Mode Active - Version ' . QCC_PLUGIN_VERSION . '");</script>' . "\n";
                }
            }
        }
        
        public function enqueue_admin_scripts($hook) {
            // Only load on our admin pages
            if (strpos($hook, 'quality-cost-calculator') !== false) {
                QCC_Debug_Logger::log('Enqueuing admin scripts', 'SCRIPT');
                
                wp_enqueue_style(
                    'qcc-admin',
                    QCC_PLUGIN_URL . 'assets/admin.css',
                    array(),
                    QCC_PLUGIN_VERSION
                );
                
                wp_enqueue_script(
                    'qcc-admin',
                    QCC_PLUGIN_URL . 'assets/admin.js',
                    array('jquery'),
                    QCC_PLUGIN_VERSION,
                    true
                );
                
                wp_localize_script('qcc-admin', 'qcc_admin', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce(QCC_NONCE_ACTION),
                    'debug' => QCC_DEBUG
                ));
            }
        }
        
        public function add_admin_menu() {
            add_options_page(
                'Quality Cost Calculator Settings',
                'Quality Cost Calculator',
                QCC_CAPABILITY,
                'quality-cost-calculator',
                array($this, 'admin_page')
            );
        }
        
        public function admin_page() {
            ?>
            <div class="wrap">
                <h1>Quality Cost Calculator</h1>
                
                <div class="card">
                    <h2>How to Use</h2>
                    <p>To display the Quality Cost Calculator on any page or post, use the following shortcode:</p>
                    <code>[quality_cost_calculator]</code>
                    
                    <h3>Shortcode Parameters</h3>
                    <ul>
                        <li><code>language</code> - Default language (en, fr, es, zh)</li>
                        <li><code>currency</code> - Default currency (EUR, USD, CNY)</li>
                        <li><code>unit</code> - Default unit (1000000 for millions, 1000000000 for billions)</li>
                    </ul>
                    
                    <h3>Example with Parameters</h3>
                    <code>[quality_cost_calculator language="en" currency="USD" unit="1000000000"]</code>
                </div>
                
                <?php if (QCC_DEBUG): ?>
                <div class="card">
                    <h2>Debug Information</h2>
                    <p><strong>Plugin Version:</strong> <?php echo QCC_PLUGIN_VERSION; ?></p>
                    <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?></p>
                    <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                    <p><strong>Memory Usage:</strong> <?php echo size_format(memory_get_usage(true)); ?></p>
                    <p><strong>Plugin Initialized:</strong> <?php echo $this->initialized ? 'Yes' : 'No'; ?></p>
                    
                    <?php if (!empty($this->load_errors)): ?>
                    <h3>Load Errors</h3>
                    <ul>
                        <?php foreach ($this->load_errors as $error): ?>
                        <li style="color: red;"><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <?php 
                    $log_file = QCC_Debug_Logger::get_log_file_path();
                    if ($log_file && file_exists($log_file)): 
                    ?>
                    <p><strong>Debug Log:</strong> <?php echo size_format(filesize($log_file)); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php
        }
        
        public function admin_init() {
            QCC_Debug_Logger::log('Admin initialization', 'ADMIN');
        }
        
        public function admin_notices() {
            // Show load errors
            if (!empty($this->load_errors)) {
                echo '<div class="notice notice-error">';
                echo '<h3>Quality Cost Calculator - Load Errors</h3>';
                echo '<ul>';
                foreach ($this->load_errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            // Show debug info for administrators in debug mode
            if (QCC_DEBUG && current_user_can('administrator') && isset($_GET['page']) && $_GET['page'] === 'quality-cost-calculator') {
                echo '<div class="notice notice-info">';
                echo '<h4>🔧 QCC Debug Information</h4>';
                echo '<p><strong>Plugin Version:</strong> ' . QCC_PLUGIN_VERSION . '</p>';
                echo '<p><strong>Memory Usage:</strong> ' . size_format(memory_get_usage(true)) . ' / ' . ini_get('memory_limit') . '</p>';
                echo '<p><strong>Initialization Status:</strong> ' . ($this->initialized ? '<span style="color: green;">✓ Success</span>' : '<span style="color: red;">✗ Failed</span>') . '</p>';
                echo '</div>';
            }
        }
        
        public function plugin_action_links($links) {
            $settings_link = sprintf(
                '<a href="%s">%s</a>',
                admin_url('options-general.php?page=quality-cost-calculator'),
                esc_html__('Settings', 'quality-cost-calculator')
            );
            
            array_unshift($links, $settings_link);
            
            if (QCC_DEBUG && current_user_can('administrator')) {
                $debug_link = sprintf(
                    '<a href="%s" style="color: #d63638;">%s</a>',
                    admin_url('options-general.php?page=quality-cost-calculator'),
                    '🔧 Debug'
                );
                array_unshift($links, $debug_link);
            }
            
            return $links;
        }
        
        public function activate() {
            QCC_Debug_Logger::log('=== PLUGIN ACTIVATION STARTED ===', 'ACTIVATION');
            
            try {
                // Create directories
                $upload_dir = wp_upload_dir();
                $directories = array(
                    $upload_dir['basedir'] . '/quality-cost-calculator-cache',
                    $upload_dir['basedir'] . '/quality-cost-calculator-exports'
                );
                
                foreach ($directories as $dir) {
                    if (!file_exists($dir)) {
                        wp_mkdir_p($dir);
                        // Add index.php for security
                        file_put_contents($dir . '/index.php', '<?php // Silence is golden');
                    }
                }
                
                // Set activation timestamp
                update_option(QCC_OPTION_PREFIX . 'activation_time', time());
                update_option(QCC_OPTION_PREFIX . 'version', QCC_PLUGIN_VERSION);
                
                // Flush rewrite rules
                flush_rewrite_rules();
                
                QCC_Debug_Logger::log('Plugin activation completed successfully', 'ACTIVATION');
                
            } catch (Exception $e) {
                QCC_Debug_Logger::log_error('Plugin activation failed: ' . $e->getMessage());
                
                deactivate_plugins(QCC_PLUGIN_BASENAME);
                wp_die(
                    '<h1>Plugin Activation Error</h1>' .
                    '<p>An error occurred during activation:</p>' .
                    '<p><strong>' . esc_html($e->getMessage()) . '</strong></p>' .
                    '<p><a href="' . admin_url('plugins.php') . '">Return to Plugins</a></p>'
                );
            }
        }
        
        public function deactivate() {
            QCC_Debug_Logger::log('=== PLUGIN DEACTIVATION STARTED ===', 'DEACTIVATION');
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            QCC_Debug_Logger::log('Plugin deactivation completed successfully', 'DEACTIVATION');
        }
        
        public function get_version() {
            return QCC_PLUGIN_VERSION;
        }
        
        public function is_loaded() {
            return $this->initialized;
        }
    }
}

// =============================================================================
// INITIALIZATION
// =============================================================================

// Initialize the plugin
function qcc_init_plugin() {
    QCC_Debug_Logger::log('Initializing plugin instance', 'MAIN');
    
    try {
        $plugin = QualityCostCalculator::get_instance();
        QCC_Debug_Logger::log('Plugin instance created successfully', 'MAIN');
        return $plugin;
    } catch (Exception $e) {
        QCC_Debug_Logger::log_error('Failed to create plugin instance: ' . $e->getMessage());
        return false;
    }
}

// Global function to get plugin instance
function qcc_get_instance() {
    static $instance = null;
    if ($instance === null) {
        $instance = QualityCostCalculator::get_instance();
    }
    return $instance;
}

// Start the plugin
add_action('plugins_loaded', 'qcc_init_plugin', 5);

QCC_Debug_Logger::log('Plugin main file processing completed successfully', 'MAIN');
QCC_Debug_Logger::log('Final memory usage: ' . size_format(memory_get_usage(true)), 'MEMORY');

// End of file
QCC_Debug_Logger::log('=== END OF MAIN PLUGIN FILE ===', 'MAIN');
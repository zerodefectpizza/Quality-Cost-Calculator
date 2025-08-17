<?php
/**
 * Plugin Name: Quality Cost Calculator
 * Plugin URI: https://github.com/your-username/quality-cost-calculator
 * Description: Professional Quality Cost Calculator with COGQ/COPQ analysis, multi-language support, and advanced reporting features.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * Text Domain: quality-cost-calculator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package QualityCostCalculator
 * @version 2.0.0
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Plugin security check
if (!function_exists('add_action')) {
    exit('WordPress environment required.');
}

/**
 * Plugin Constants
 */
define('QCC_PLUGIN_VERSION', '2.0.0');
define('QCC_PLUGIN_FILE', __FILE__);
define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('QCC_PLUGIN_DIR', dirname(__FILE__));
define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');
define('QCC_MIN_PHP_VERSION', '7.4');
define('QCC_MIN_WP_VERSION', '5.0');

/**
 * Environment Constants
 */
if (!defined('QCC_DEBUG')) {
    define('QCC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);
}

if (!defined('QCC_DEVELOPMENT')) {
    define('QCC_DEVELOPMENT', defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development');
}

/**
 * Database Constants
 */
define('QCC_DB_VERSION', '1.0');
define('QCC_OPTION_PREFIX', 'qcc_');
define('QCC_TRANSIENT_PREFIX', 'qcc_transient_');

/**
 * Asset Constants
 */
define('QCC_ASSETS_VERSION', QCC_PLUGIN_VERSION . (QCC_DEBUG ? '-' . time() : ''));
define('QCC_CSS_URL', QCC_PLUGIN_URL . 'assets/css/');
define('QCC_JS_URL', QCC_PLUGIN_URL . 'assets/js/');
define('QCC_IMG_URL', QCC_PLUGIN_URL . 'assets/images/');

/**
 * Main Plugin Class
 */
class QCC_Plugin {
    
    /**
     * Plugin instance
     * 
     * @var QCC_Plugin|null
     */
    private static $instance = null;
    
    /**
     * Plugin version
     * 
     * @var string
     */
    public $version = QCC_PLUGIN_VERSION;
    
    /**
     * Bootstrap instance
     * 
     * @var QCC_Bootstrap|null
     */
    private $bootstrap = null;
    
    /**
     * Plugin activation status
     * 
     * @var bool
     */
    private $activated = false;
    
    /**
     * Get singleton instance
     * 
     * @return QCC_Plugin
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to enforce singleton
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Check system requirements first
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load core files
        $this->load_core_files();
        
        // Initialize WordPress hooks
        $this->init_hooks();
        
        // Initialize plugin
        $this->init_plugin();
    }
    
    /**
     * Check system requirements
     * 
     * @return bool
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, QCC_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), QCC_MIN_WP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        // Check required PHP extensions
        $required_extensions = array('json', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                add_action('admin_notices', function() use ($extension) {
                    $this->extension_notice($extension);
                });
                return false;
            }
        }
        
        // Check file permissions
        if (!is_writable(WP_CONTENT_DIR)) {
            add_action('admin_notices', array($this, 'permissions_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Load core plugin files
     */
    private function load_core_files() {
        $core_files = array(
            'includes/core/class-qcc-service-container.php',
            'includes/core/class-qcc-configuration.php',
            'includes/core/class-qcc-bootstrap.php'
        );
        
        foreach ($core_files as $file) {
            $file_path = QCC_PLUGIN_PATH . $file;
            
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                // Log missing file error
                if (QCC_DEBUG) {
                    error_log("QCC: Core file missing - {$file}");
                }
                
                // Show admin notice for missing files
                add_action('admin_notices', function() use ($file) {
                    $this->missing_file_notice($file);
                });
                
                return;
            }
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin lifecycle hooks
        register_activation_hook(QCC_PLUGIN_FILE, array('QCC_Bootstrap', 'activate'));
        register_deactivation_hook(QCC_PLUGIN_FILE, array('QCC_Bootstrap', 'deactivate'));
        register_uninstall_hook(QCC_PLUGIN_FILE, array('QCC_Bootstrap', 'uninstall'));
        
        // WordPress initialization hooks
        add_action('plugins_loaded', array($this, 'plugins_loaded'), 10);
        add_action('init', array($this, 'wp_init'), 10);
        add_action('admin_init', array($this, 'admin_init'), 10);
        
        // Plugin meta links
        add_filter('plugin_action_links_' . QCC_PLUGIN_BASENAME, array($this, 'add_action_links'));
        add_filter('plugin_row_meta', array($this, 'add_row_meta'), 10, 2);
        
        // AJAX hooks
        add_action('wp_ajax_qcc_system_check', array($this, 'ajax_system_check'));
        add_action('wp_ajax_qcc_plugin_status', array($this, 'ajax_plugin_status'));
        
        // Cleanup hooks
        add_action('wp_loaded', array($this, 'cleanup_old_data'));
        add_action('upgrader_process_complete', array($this, 'plugin_upgrade'), 10, 2);
    }
    
    /**
     * Initialize plugin after WordPress is loaded
     */
    private function init_plugin() {
        // Load text domain early
        $this->load_textdomain();
        
        // Initialize bootstrap
        if (class_exists('QCC_Bootstrap')) {
            $this->bootstrap = QCC_Bootstrap::get_instance();
            $this->activated = QCC_Bootstrap::initialize();
            
            if (!$this->activated && QCC_DEBUG) {
                error_log('QCC: Bootstrap initialization failed');
            }
        } else {
            if (QCC_DEBUG) {
                error_log('QCC: Bootstrap class not found');
            }
            add_action('admin_notices', array($this, 'bootstrap_missing_notice'));
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        $domain = QCC_TEXT_DOMAIN;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
        
        // Try WordPress languages directory first
        $wp_lang_file = WP_LANG_DIR . "/plugins/{$domain}-{$locale}.mo";
        if (file_exists($wp_lang_file)) {
            load_textdomain($domain, $wp_lang_file);
            return true;
        }
        
        // Fallback to plugin languages directory
        $plugin_lang_dir = QCC_PLUGIN_DIR . '/languages/';
        if (load_plugin_textdomain($domain, false, $plugin_lang_dir)) {
            return true;
        }
        
        // Log translation loading failure in debug mode
        if (QCC_DEBUG) {
            error_log("QCC: Failed to load translations for locale: {$locale}");
        }
        
        return false;
    }
    
    /**
     * Plugins loaded hook
     */
    public function plugins_loaded() {
        // Check for plugin conflicts
        $this->check_plugin_conflicts();
        
        // Load compatibility layers
        $this->load_compatibility();
        
        // Fire plugins loaded action
        do_action('qcc_plugins_loaded');
    }
    
    /**
     * WordPress init hook
     */
    public function wp_init() {
        // Initialize frontend features
        if (!is_admin()) {
            $this->init_frontend();
        }
        
        // Initialize shared features
        $this->init_shared();
        
        // Fire init action
        do_action('qcc_init');
    }
    
    /**
     * Admin init hook
     */
    public function admin_init() {
        // Initialize admin features
        $this->init_admin();
        
        // Check for plugin updates
        $this->check_plugin_update();
        
        // Fire admin init action
        do_action('qcc_admin_init');
    }
    
    /**
     * Initialize frontend features
     */
    private function init_frontend() {
        // Only initialize if shortcode is present
        if ($this->page_has_shortcode()) {
            // Load frontend assets conditionally
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
            
            // Add frontend meta
            add_action('wp_head', array($this, 'add_frontend_meta'));
        }
    }
    
    /**
     * Initialize admin features
     */
    private function init_admin() {
        // Load admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Initialize shared features
     */
    private function init_shared() {
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Custom post types and taxonomies if needed
        $this->register_post_types();
        
        // Custom capabilities
        $this->add_custom_capabilities();
    }
    
    /**
     * Check if current page has QCC shortcode
     * 
     * @return bool
     */
    private function page_has_shortcode() {
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return false;
        }
        
        return has_shortcode($post->post_content, 'quality_cost_calculator');
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Chart.js from CDN with fallback
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Plugin CSS
        wp_enqueue_style(
            'qcc-frontend',
            QCC_CSS_URL . 'quality-cost-calculator.css',
            array(),
            QCC_ASSETS_VERSION
        );
        
        // Plugin JavaScript
        wp_enqueue_script(
            'qcc-frontend',
            QCC_JS_URL . 'quality-cost-calculator.js',
            array('jquery', 'chart-js'),
            QCC_ASSETS_VERSION,
            true
        );
        
        // Localize script with configuration
        wp_localize_script('qcc-frontend', 'qcc_config', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_nonce'),
            'debug' => QCC_DEBUG,
            'version' => QCC_PLUGIN_VERSION,
            'text_domain' => QCC_TEXT_DOMAIN,
            'currency_symbols' => array(
                'EUR' => '€',
                'USD' => '$',
                'CNY' => '¥'
            ),
            'supported_languages' => array('en', 'de', 'fr', 'es', 'zh'),
            'rest_url' => rest_url('qcc/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on QCC admin pages
        if (strpos($hook, 'quality-cost-calculator') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'qcc-admin',
            QCC_CSS_URL . 'admin.css',
            array('wp-admin', 'dashicons'),
            QCC_ASSETS_VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'qcc-admin',
            QCC_JS_URL . 'admin.js',
            array('jquery', 'wp-util'),
            QCC_ASSETS_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('qcc-admin', 'qcc_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_admin_nonce'),
            'strings' => array(
                'confirm_reset' => __('Are you sure you want to reset all settings?', QCC_TEXT_DOMAIN),
                'settings_saved' => __('Settings saved successfully.', QCC_TEXT_DOMAIN),
                'error_occurred' => __('An error occurred. Please try again.', QCC_TEXT_DOMAIN)
            )
        ));
    }
    
    /**
     * Add frontend meta information
     */
    public function add_frontend_meta() {
        echo '<meta name="qcc-version" content="' . esc_attr(QCC_PLUGIN_VERSION) . '">' . "\n";
        echo '<meta name="qcc-text-domain" content="' . esc_attr(QCC_TEXT_DOMAIN) . '">' . "\n";
        
        if (QCC_DEBUG) {
            echo '<!-- QCC Debug Mode Enabled -->' . "\n";
            echo '<script>console.log("QCC Debug Mode - Version ' . QCC_PLUGIN_VERSION . '");</script>' . "\n";
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Quality Cost Calculator', QCC_TEXT_DOMAIN),
            __('QCC Settings', QCC_TEXT_DOMAIN),
            'manage_options',
            'quality-cost-calculator',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ($this->bootstrap && method_exists($this->bootstrap, 'render_admin_page')) {
            $this->bootstrap->render_admin_page();
        } else {
            // Fallback admin page
            include QCC_PLUGIN_PATH . 'templates/admin/settings-page.php';
        }
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('qcc_settings_group', 'qcc_feature_flags');
        register_setting('qcc_settings_group', 'qcc_default_language');
        register_setting('qcc_settings_group', 'qcc_default_currency');
        register_setting('qcc_settings_group', 'qcc_default_unit');
        register_setting('qcc_settings_group', 'qcc_debug_mode');
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('qcc/v1', '/calculate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_calculate'),
            'permission_callback' => array($this, 'rest_permission_check'),
            'args' => array(
                'revenue' => array(
                    'required' => true,
                    'type' => 'number',
                    'minimum' => 0
                ),
                'quality_percentage' => array(
                    'required' => true,
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 100
                )
            )
        ));
        
        register_rest_route('qcc/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_status'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * REST API calculate endpoint
     */
    public function rest_calculate($request) {
        try {
            $params = $request->get_params();
            
            // Get calculation engine
            if ($this->bootstrap) {
                $calculator = $this->bootstrap->get_service('calculation_engine');
                if ($calculator && method_exists($calculator, 'calculate')) {
                    return rest_ensure_response($calculator->calculate($params));
                }
            }
            
            // Fallback calculation
            return rest_ensure_response($this->fallback_calculation($params));
            
        } catch (Exception $e) {
            return new WP_Error('calculation_failed', $e->getMessage(), array('status' => 500));
        }
    }
    
    /**
     * REST API status endpoint
     */
    public function rest_status($request) {
        return rest_ensure_response(array(
            'version' => QCC_PLUGIN_VERSION,
            'status' => $this->activated ? 'active' : 'inactive',
            'bootstrap_loaded' => $this->bootstrap !== null,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'debug_mode' => QCC_DEBUG
        ));
    }
    
    /**
     * REST API permission check
     */
    public function rest_permission_check($request) {
        return current_user_can('read');
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        // Register calculation history post type if needed
        if (apply_filters('qcc_enable_calculation_history', false)) {
            register_post_type('qcc_calculation', array(
                'labels' => array(
                    'name' => __('Calculations', QCC_TEXT_DOMAIN),
                    'singular_name' => __('Calculation', QCC_TEXT_DOMAIN)
                ),
                'public' => false,
                'show_ui' => current_user_can('manage_options'),
                'supports' => array('title', 'custom-fields'),
                'capability_type' => 'post',
                'capabilities' => array(
                    'create_posts' => 'do_not_allow'
                ),
                'map_meta_cap' => true
            ));
        }
    }
    
    /**
     * Add custom capabilities
     */
    private function add_custom_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_qcc_settings');
            $role->add_cap('view_qcc_calculations');
        }
    }
    
    /**
     * Check for plugin conflicts
     */
    private function check_plugin_conflicts() {
        $conflicting_plugins = array(
            'old-quality-calculator/old-quality-calculator.php',
            'quality-calc/quality-calc.php'
        );
        
        foreach ($conflicting_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                add_action('admin_notices', function() use ($plugin) {
                    $this->plugin_conflict_notice($plugin);
                });
            }
        }
    }
    
    /**
     * Load compatibility layers
     */
    private function load_compatibility() {
        // Load compatibility for older WordPress versions
        if (version_compare(get_bloginfo('version'), '5.5', '<')) {
            include_once QCC_PLUGIN_PATH . 'includes/compatibility/wp-5.4-compat.php';
        }
        
        // Load PHP compatibility
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            include_once QCC_PLUGIN_PATH . 'includes/compatibility/php-7.4-compat.php';
        }
    }
    
    /**
     * Check for plugin updates
     */
    private function check_plugin_update() {
        $current_version = get_option('qcc_version', '0.0.0');
        
        if (version_compare($current_version, QCC_PLUGIN_VERSION, '<')) {
            $this->run_plugin_update($current_version, QCC_PLUGIN_VERSION);
            update_option('qcc_version', QCC_PLUGIN_VERSION);
        }
    }
    
    /**
     * Run plugin update procedures
     */
    private function run_plugin_update($from_version, $to_version) {
        // Run version-specific updates
        if (version_compare($from_version, '2.0.0', '<')) {
            $this->update_to_2_0_0();
        }
        
        // Clear any cached data
        $this->clear_plugin_cache();
        
        // Log update
        if (QCC_DEBUG) {
            error_log("QCC: Updated from {$from_version} to {$to_version}");
        }
    }
    
    /**
     * Update to version 2.0.0
     */
    private function update_to_2_0_0() {
        // Migrate old settings format to new format
        $old_settings = get_option('quality_cost_calculator_settings', array());
        if (!empty($old_settings)) {
            // Convert to new format
            $new_settings = array(
                'qcc_default_language' => $old_settings['language'] ?? 'en',
                'qcc_default_currency' => $old_settings['currency'] ?? 'EUR',
                'qcc_default_unit' => $old_settings['unit'] ?? '1000000'
            );
            
            foreach ($new_settings as $key => $value) {
                update_option($key, $value);
            }
            
            // Remove old settings
            delete_option('quality_cost_calculator_settings');
        }
    }
    
    /**
     * Clear plugin cache
     */
    private function clear_plugin_cache() {
        // Clear WordPress transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_qcc_%' 
            OR option_name LIKE '_transient_timeout_qcc_%'"
        );
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('qcc');
        }
    }
    
    /**
     * Cleanup old data
     */
    public function cleanup_old_data() {
        // Only run once per day
        if (get_transient('qcc_cleanup_done')) {
            return;
        }
        
        // Clean up old log files
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/qcc-logs/';
        if (is_dir($log_dir)) {
            $this->cleanup_old_logs($log_dir);
        }
        
        // Clean up expired transients
        $this->cleanup_expired_transients();
        
        // Set cleanup flag
        set_transient('qcc_cleanup_done', true, DAY_IN_SECONDS);
    }
    
    /**
     * Cleanup old log files
     */
    private function cleanup_old_logs($log_dir) {
        $files = glob($log_dir . '*.log');
        $cutoff_time = time() - (30 * DAY_IN_SECONDS); // 30 days
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                unlink($file);
            }
        }
    }
    
    /**
     * Cleanup expired transients
     */
    private function cleanup_expired_transients() {
        global $wpdb;
        
        $wpdb->query("
            DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_qcc_%' 
            AND option_value < UNIX_TIMESTAMP()
        ");
    }
    
    /**
     * Fallback calculation method
     */
    private function fallback_calculation($data) {
        $revenue = floatval($data['revenue'] ?? 0);
        $quality_percentage = floatval($data['quality_percentage'] ?? 6);
        
        $total_quality_cost = $revenue * ($quality_percentage / 100);
        
        return array(
            'total_quality_cost' => $total_quality_cost,
            'cogq' => $total_quality_cost * 0.3, // 30% assumption
            'copq' => $total_quality_cost * 0.7, // 70% assumption
            'prevention_cost' => $total_quality_cost * 0.1,
            'appraisal_cost' => $total_quality_cost * 0.2,
            'internal_defect_cost' => $total_quality_cost * 0.3,
            'external_defect_cost' => $total_quality_cost * 0.4,
            'fallback' => true
        );
    }
    
    /**
     * Plugin upgrade handler
     */
    public function plugin_upgrade($upgrader, $options) {
        if ($options['type'] === 'plugin' && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin === QCC_PLUGIN_BASENAME) {
                    // Plugin was upgraded
                    $this->clear_plugin_cache();
                    break;
                }
            }
        }
    }
    
    /**
     * AJAX system check
     */
    public function ajax_system_check() {
        check_ajax_referer('qcc_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', QCC_TEXT_DOMAIN));
        }
        
        $checks = array(
            'php_version' => version_compare(PHP_VERSION, QCC_MIN_PHP_VERSION, '>='),
            'wp_version' => version_compare(get_bloginfo('version'), QCC_MIN_WP_VERSION, '>='),
            'bootstrap_loaded' => $this->bootstrap !== null,
            'plugin_activated' => $this->activated,
            'writable_uploads' => is_writable(wp_upload_dir()['basedir']),
            'memory_limit' => $this->check_memory_limit()
        );
        
        wp_send_json_success($checks);
    }
    
    /**
     * AJAX plugin status
     */
    public function ajax_plugin_status() {
        check_ajax_referer('qcc_admin_nonce', 'nonce');
        
        $status = array(
            'version' => QCC_PLUGIN_VERSION,
            'activated' => $this->activated,
            'bootstrap_status' => $this->bootstrap ? $this->bootstrap->get_status() : null,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * Check memory limit
     */
    private function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        return $memory_limit_bytes >= wp_convert_hr_to_bytes('128M');
    }
    
    /**
     * Add plugin action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=quality-cost-calculator') . '">' . 
                        __('Settings', QCC_TEXT_DOMAIN) . '</a>';
        array_unshift($links, $settings_link);
        
        if (QCC_DEBUG) {
            $debug_link = '<a href="#" style="color: orange;">' . __('Debug Mode', QCC_TEXT_DOMAIN) . '</a>';
            array_unshift($links, $debug_link);
        }
        
        return $links;
    }
    
    /**
     * Add plugin row meta
     */
    public function add_row_meta($links, $file) {
        if ($file === QCC_PLUGIN_BASENAME) {
            $links[] = '<a href="https://github.com/your-username/quality-cost-calculator" target="_blank">' . 
                      __('GitHub', QCC_TEXT_DOMAIN) . '</a>';
            $links[] = '<a href="https://your-website.com/qcc-documentation" target="_blank">' . 
                      __('Documentation', QCC_TEXT_DOMAIN) . '</a>';
            $links[] = '<a href="https://your-website.com/qcc-support" target="_blank">' . 
                      __('Support', QCC_TEXT_DOMAIN) . '</a>';
        }
        
        return $links;
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Quality Cost Calculator requires PHP %s or higher. You are running version %s.', QCC_TEXT_DOMAIN),
            QCC_MIN_PHP_VERSION,
            PHP_VERSION
        );
        echo ' <a href="https://wordpress.org/support/update-php/" target="_blank">' . 
             __('Learn how to update PHP', QCC_TEXT_DOMAIN) . '</a>';
        echo '</p></div>';
    }
    
    /**
     * WordPress version notice
     */
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Quality Cost Calculator requires WordPress %s or higher. You are running version %s.', QCC_TEXT_DOMAIN),
            QCC_MIN_WP_VERSION,
            get_bloginfo('version')
        );
        echo ' <a href="' . admin_url('update-core.php') . '">' . 
             __('Update WordPress', QCC_TEXT_DOMAIN) . '</a>';
        echo '</p></div>';
    }
    
    /**
     * PHP extension notice
     */
    public function extension_notice($extension) {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Quality Cost Calculator requires the PHP %s extension. Please contact your hosting provider.', QCC_TEXT_DOMAIN),
            '<strong>' . $extension . '</strong>'
        );
        echo '</p></div>';
    }
    
    /**
     * File permissions notice
     */
    public function permissions_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Quality Cost Calculator requires write permissions to %s. Please check your file permissions.', QCC_TEXT_DOMAIN),
            '<code>' . WP_CONTENT_DIR . '</code>'
        );
        echo '</p></div>';
    }
    
    /**
     * Missing file notice
     */
    public function missing_file_notice($file) {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Quality Cost Calculator is missing a required file: %s. Please reinstall the plugin.', QCC_TEXT_DOMAIN),
            '<code>' . $file . '</code>'
        );
        echo '</p></div>';
    }
    
    /**
     * Bootstrap missing notice
     */
    public function bootstrap_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        __('Quality Cost Calculator bootstrap class is missing. Please reinstall the plugin.', QCC_TEXT_DOMAIN);
        echo '</p></div>';
    }
    
    /**
     * Plugin conflict notice
     */
    public function plugin_conflict_notice($plugin) {
        echo '<div class="notice notice-warning"><p>';
        printf(
            __('Quality Cost Calculator detected a conflicting plugin: %s. Please deactivate it to avoid issues.', QCC_TEXT_DOMAIN),
            '<strong>' . $plugin . '</strong>'
        );
        echo '</p></div>';
    }
    
    /**
     * Get plugin instance (for external access)
     */
    public function get_bootstrap() {
        return $this->bootstrap;
    }
    
    /**
     * Check if plugin is properly activated
     */
    public function is_activated() {
        return $this->activated;
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get plugin status for debugging
     */
    public function get_status() {
        return array(
            'version' => $this->version,
            'activated' => $this->activated,
            'bootstrap_loaded' => $this->bootstrap !== null,
            'requirements_met' => $this->check_requirements(),
            'text_domain_loaded' => is_textdomain_loaded(QCC_TEXT_DOMAIN),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'debug_mode' => QCC_DEBUG,
            'development_mode' => QCC_DEVELOPMENT
        );
    }
}

/**
 * Helper Functions
 */

/**
 * Get main plugin instance
 * 
 * @return QCC_Plugin
 */
function qcc_plugin() {
    return QCC_Plugin::get_instance();
}

/**
 * Get bootstrap instance
 * 
 * @return QCC_Bootstrap|null
 */
function qcc_bootstrap() {
    $plugin = qcc_plugin();
    return $plugin ? $plugin->get_bootstrap() : null;
}

/**
 * Get service from container
 * 
 * @param string $service_name Service name
 * @return mixed|null Service instance or null
 */
function qcc_service($service_name) {
    $bootstrap = qcc_bootstrap();
    return $bootstrap ? $bootstrap->get_service($service_name) : null;
}

/**
 * Check if QCC is properly loaded
 * 
 * @return bool
 */
function qcc_is_loaded() {
    $plugin = qcc_plugin();
    return $plugin && $plugin->is_activated();
}

/**
 * Get QCC feature flag
 * 
 * @param string $flag_name Flag name
 * @return bool Flag value
 */
function qcc_feature_enabled($flag_name) {
    $bootstrap = qcc_bootstrap();
    return $bootstrap ? $bootstrap->get_feature_flag($flag_name) : false;
}

/**
 * Log QCC message (only in debug mode)
 * 
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 */
function qcc_log($message, $level = 'info') {
    if (!QCC_DEBUG) {
        return;
    }
    
    $log_message = sprintf('[QCC] [%s] %s', strtoupper($level), $message);
    error_log($log_message);
}

/**
 * Get QCC configuration value
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value
 * @return mixed Configuration value
 */
function qcc_config($key, $default = null) {
    $config_service = qcc_service('config');
    return $config_service ? $config_service->get($key, $default) : $default;
}

/**
 * Translate QCC string
 * 
 * @param string $text Text to translate
 * @param string $context Translation context
 * @return string Translated text
 */
function qcc_translate($text, $context = '') {
    if (empty($context)) {
        return __($text, QCC_TEXT_DOMAIN);
    } else {
        return _x($text, $context, QCC_TEXT_DOMAIN);
    }
}

/**
 * Get QCC asset URL
 * 
 * @param string $path Asset path
 * @param string $type Asset type (css, js, images)
 * @return string Asset URL
 */
function qcc_asset_url($path, $type = 'css') {
    $base_url = QCC_PLUGIN_URL . 'assets/';
    
    switch ($type) {
        case 'css':
            return QCC_CSS_URL . $path;
        case 'js':
            return QCC_JS_URL . $path;
        case 'images':
            return QCC_IMG_URL . $path;
        default:
            return $base_url . $path;
    }
}

/**
 * Check if user can access QCC features
 * 
 * @param string $capability Capability to check
 * @return bool User has capability
 */
function qcc_user_can($capability = 'read') {
    switch ($capability) {
        case 'manage_settings':
            return current_user_can('manage_options');
        case 'view_calculations':
            return current_user_can('read');
        case 'export_data':
            return current_user_can('export');
        default:
            return current_user_can($capability);
    }
}

/**
 * Format currency value
 * 
 * @param float $value Numeric value
 * @param string $currency Currency code
 * @param string $unit Unit (millions, billions)
 * @return string Formatted currency
 */
function qcc_format_currency($value, $currency = 'EUR', $unit = '1000000') {
    $formatted_value = number_format($value / intval($unit), 2, '.', ',');
    
    $currency_symbols = array(
        'EUR' => '€',
        'USD' => ',
        'CNY' => '¥'
    );
    
    $symbol = $currency_symbols[$currency] ?? $currency;
    
    $unit_labels = array(
        '1000000' => qcc_translate('M', 'million abbreviation'),
        '1000000000' => qcc_translate('B', 'billion abbreviation')
    );
    
    $unit_label = $unit_labels[$unit] ?? '';
    
    return $formatted_value . ' ' . $symbol . ' ' . $unit_label;
}

/**
 * Validate calculation input
 * 
 * @param array $input Input data
 * @return array|true Validation errors or true if valid
 */
function qcc_validate_input($input) {
    $errors = array();
    
    // Validate revenue
    if (!isset($input['revenue']) || !is_numeric($input['revenue']) || $input['revenue'] < 0) {
        $errors[] = qcc_translate('Revenue must be a positive number.');
    }
    
    // Validate quality percentage
    if (!isset($input['quality_percentage']) || !is_numeric($input['quality_percentage']) || 
        $input['quality_percentage'] < 0 || $input['quality_percentage'] > 100) {
        $errors[] = qcc_translate('Quality percentage must be between 0 and 100.');
    }
    
    // Validate percentage breakdown
    $percentages = array('prevention', 'appraisal', 'internal_defect', 'external_defect');
    $total_percentage = 0;
    
    foreach ($percentages as $key) {
        if (isset($input[$key])) {
            if (!is_numeric($input[$key]) || $input[$key] < 0 || $input[$key] > 100) {
                $errors[] = sprintf(qcc_translate('%s percentage must be between 0 and 100.'), ucfirst($key));
            } else {
                $total_percentage += floatval($input[$key]);
            }
        }
    }
    
    // Check if percentages sum to 100
    if (abs($total_percentage - 100) > 0.01) {
        $errors[] = qcc_translate('Percentage values must sum to 100%.');
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Safe JSON encode with error handling
 * 
 * @param mixed $data Data to encode
 * @return string JSON string or error message
 */
function qcc_json_encode($data) {
    $json = json_encode($data);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        qcc_log('JSON encoding error: ' . json_last_error_msg(), 'error');
        return json_encode(array('error' => 'JSON encoding failed'));
    }
    
    return $json;
}

/**
 * Safe JSON decode with error handling
 * 
 * @param string $json JSON string
 * @param bool $associative Return associative array
 * @return mixed Decoded data or null on error
 */
function qcc_json_decode($json, $associative = true) {
    $data = json_decode($json, $associative);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        qcc_log('JSON decoding error: ' . json_last_error_msg(), 'error');
        return null;
    }
    
    return $data;
}

/**
 * Check if current request is AJAX
 * 
 * @return bool Is AJAX request
 */
function qcc_is_ajax() {
    return defined('DOING_AJAX') && DOING_AJAX;
}

/**
 * Check if current request is REST API
 * 
 * @return bool Is REST request
 */
function qcc_is_rest() {
    return defined('REST_REQUEST') && REST_REQUEST;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function qcc_get_client_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
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
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Emergency fallback shortcode
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function qcc_emergency_shortcode($atts) {
    $atts = shortcode_atts(array(
        'currency' => 'EUR',
        'language' => 'en',
        'unit' => '1000000'
    ), $atts);
    
    ob_start();
    ?>
    <div class="qcc-emergency-fallback" style="border: 1px solid #ddd; padding: 20px; margin: 20px 0;">
        <h3><?php echo qcc_translate('Quality Cost Calculator'); ?></h3>
        <p><?php echo qcc_translate('The calculator is currently loading. If this message persists, please contact the administrator.'); ?></p>
        <div class="qcc-status">
            <strong><?php echo qcc_translate('Debug Information:'); ?></strong><br>
            Plugin Version: <?php echo QCC_PLUGIN_VERSION; ?><br>
            Loaded: <?php echo qcc_is_loaded() ? qcc_translate('Yes') : qcc_translate('No'); ?><br>
            Time: <?php echo current_time('Y-m-d H:i:s'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Register emergency fallback shortcode
add_shortcode('quality_cost_calculator', 'qcc_emergency_shortcode');

/**
 * Initialize the plugin
 */
function qcc_init() {
    // Initialize main plugin instance
    QCC_Plugin::get_instance();
    
    // Add emergency hooks
    add_action('wp_footer', function() {
        if (QCC_DEBUG && qcc_is_loaded()) {
            echo '<!-- QCC Debug: Plugin loaded successfully -->';
        }
    });
    
    // Add admin bar debug info
    if (QCC_DEBUG) {
        add_action('admin_bar_menu', function($wp_admin_bar) {
            if (!is_admin()) return;
            
            $plugin = qcc_plugin();
            $status = $plugin ? $plugin->get_status() : array();
            
            $wp_admin_bar->add_node(array(
                'id' => 'qcc-debug',
                'title' => 'QCC: ' . ($status['activated'] ? 'Active' : 'Inactive'),
                'href' => admin_url('options-general.php?page=quality-cost-calculator'),
                'meta' => array(
                    'title' => 'Quality Cost Calculator Debug Info'
                )
            ));
        }, 100);
    }
}

// Initialize plugin when WordPress is ready
add_action('plugins_loaded', 'qcc_init', 0);

/**
 * Activation hook fallback
 */
register_activation_hook(__FILE__, function() {
    // Ensure minimum requirements
    if (version_compare(PHP_VERSION, QCC_MIN_PHP_VERSION, '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(sprintf(
            __('Quality Cost Calculator requires PHP %s or higher.', QCC_TEXT_DOMAIN),
            QCC_MIN_PHP_VERSION
        ));
    }
    
    // Log activation
    if (QCC_DEBUG) {
        error_log('QCC: Plugin activated - Version ' . QCC_PLUGIN_VERSION);
    }
});

/**
 * Deactivation hook fallback
 */
register_deactivation_hook(__FILE__, function() {
    // Log deactivation
    if (QCC_DEBUG) {
        error_log('QCC: Plugin deactivated');
    }
});

// End of file - quality-cost-calculator.php
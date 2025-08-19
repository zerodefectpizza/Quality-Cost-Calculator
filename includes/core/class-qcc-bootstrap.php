<?php
/**
 * QCC Bootstrap Class — Consolidated (cleaned)
 * 
 * This file merges the two previous variants:
 * - class-qcc-bootstrap.php (modern)
 * - class-qcc-bootstrap - ausgelagert.php (legacy/extracted)
 * 
 * Strategy: Keep the modern architecture and hooks.
 * Legacy-only helpers were removed unless still referenced.
 * 
 * Date: 2025-08-18
 */


/**
 * QCC Bootstrap Class - Modern Plugin Initialization
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap Class for Quality Cost Calculator
 * 
 * Handles plugin initialization, service loading, and lifecycle management
 */
class QCC_Bootstrap {
    
    /**
     * Plugin instance
     * 
     * @var QCC_Bootstrap|null
     */
    private static $instance = null;
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container|null
     */
    private $container = null;
    
    /**
     * Plugin initialization status
     * 
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Feature flags
     * 
     * @var array
     */
    private $feature_flags = array();
    
    /**
     * Get singleton instance
     * 
     * @return QCC_Bootstrap
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Initialize feature flags
        $this->load_feature_flags();
    }
    
    /**
     * Initialize the plugin
     * 
     * @return bool True on success, false on failure
     */
    public static function initialize() {
        $instance = self::get_instance();
        return $instance->init();
    }
    
    /**
     * Main initialization method
     * 
     * @return bool
     */
    private function init() {
        if ($this->initialized) {
            return true;
        }
        
        try {
            // Load essential services
            $this->load_core_services();
            
            // Load text domain
            $this->load_textdomain();
            
            // Setup WordPress hooks
            $this->setup_hooks();
            
            // Initialize services based on context
            $this->initialize_context_services();
            
            $this->initialized = true;
            
            // Log successful initialization in debug mode
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC: Bootstrap initialization completed successfully');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('QCC: Bootstrap initialization failed - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load feature flags from options
     */
    private function load_feature_flags() {
        $default_flags = array(
            'use_new_shortcode_architecture' => false,
            'use_service_container' => true,
            'enable_performance_monitoring' => defined('WP_DEBUG') && WP_DEBUG,
            'use_modular_rendering' => false,
            'enable_advanced_caching' => true,
            'use_new_calculation_engine' => false
        );
        
        $saved_flags = get_option('qcc_feature_flags', array());
        $this->feature_flags = array_merge($default_flags, $saved_flags);
    }
    
    /**
     * Load core services
     */
    private function load_core_services() {
        // Load service container if enabled
        if ($this->get_feature_flag('use_service_container')) {
            $this->load_service_container();
        }
        
        // Load configuration service
        $this->load_configuration_service();
        
        // Load performance monitoring if enabled
        if ($this->get_feature_flag('enable_performance_monitoring')) {
            $this->load_performance_monitor();
        }
    }
    
    /**
     * Load service container
     */
    private function load_service_container() {
        if (!class_exists('QCC_Service_Container')) {
            $container_file = QCC_PLUGIN_PATH . 'includes/core/class-qcc-service-container.php';
            if (file_exists($container_file)) {
                require_once $container_file;
            }
        }
        
        if (class_exists('QCC_Service_Container')) {
            $this->container = new QCC_Service_Container();
            $this->register_core_services();
        }
    }
    
    /**
     * Register core services in container
     */
    private function register_core_services() {
        if (!$this->container) {
            return;
        }
        
        // Register configuration service
        $this->container->register('config', function() {
            return new QCC_Configuration();
        });
        
        // Register translation service
        $this->container->register('translator', function() {
            return new QCC_Translation_Service();
        });
        
        // Register shortcode controller (conditional)
        if ($this->get_feature_flag('use_new_shortcode_architecture')) {
            $this->container->register('shortcode_controller', function() {
                return new QCC_Shortcode_Controller();
            });
        } else {
            $this->container->register('shortcode_controller', function() {
                return new QCC_Shortcode_Legacy();
            });
        }
        
        // Register calculation engine (conditional)
        if ($this->get_feature_flag('use_new_calculation_engine')) {
            $this->container->register('calculation_engine', function() {
                return new QCC_Calculation_Engine();
            });
        }
    }
    
    /**
     * Load configuration service
     */
    private function load_configuration_service() {
        if (!class_exists('QCC_Configuration')) {
            $config_service_file = QCC_PLUGIN_PATH . 'includes/services/class-qcc-configuration.php';
            if (file_exists($config_service_file)) {
                require_once $config_service_file;
            }
        }
    }
    
    /**
     * Load performance monitor
     */
    private function load_performance_monitor() {
        if (!class_exists('QCC_Performance_Monitor')) {
            $monitor_file = QCC_PLUGIN_PATH . 'includes/monitoring/class-qcc-performance-monitor.php';
            if (file_exists($monitor_file)) {
                require_once $monitor_file;
                QCC_Performance_Monitor::start_tracking();
            }
        }
    }
    
    /**
     * Load text domain for internationalization
     */
    private function load_textdomain() {
        $domain = QCC_TEXT_DOMAIN;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);
        
        // Try WordPress languages directory first
        $wp_lang_file = WP_LANG_DIR . "/plugins/{$domain}-{$locale}.mo";
        if (file_exists($wp_lang_file)) {
            load_textdomain($domain, $wp_lang_file);
            return;
        }
        
        // Fallback to plugin languages directory
        $plugin_lang_dir = QCC_PLUGIN_DIR . '/languages/';
        load_plugin_textdomain($domain, false, $plugin_lang_dir);
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Frontend hooks
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_frontend_assets'));
            add_action('wp_head', array($this, 'add_frontend_meta'));
        }
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_menu', array($this, 'register_admin_pages'));
            add_action('admin_init', array($this, 'register_settings'));
        }
        
        // AJAX hooks
        add_action('wp_ajax_qcc_calculate', array($this, 'handle_ajax_calculation'));
        add_action('wp_ajax_nopriv_qcc_calculate', array($this, 'handle_ajax_calculation'));
        
        // Shortcode registration
        add_action('init', array($this, 'register_shortcodes'));
    }
    
    /**
     * Initialize services based on current context
     */
    private function initialize_context_services() {
        if (is_admin()) {
            $this->initialize_admin_services();
        } else {
            $this->initialize_frontend_services();
        }
        
        // Initialize services needed in both contexts
        $this->initialize_shared_services();
    }
    
    /**
     * Initialize admin-specific services
     */
    private function initialize_admin_services() {
        // Load admin controller
        if (!class_exists('QCC_Admin_Controller')) {
            $admin_file = QCC_PLUGIN_PATH . 'includes/admin/class-qcc-admin-controller.php';
            if (file_exists($admin_file)) {
                require_once $admin_file;
            }
        }
        
        // Initialize admin controller
        if (class_exists('QCC_Admin_Controller')) {
            if ($this->container) {
                $this->container->register('admin_controller', function() {
                    return new QCC_Admin_Controller();
                });
            }
        }
    }
    
    /**
     * Initialize frontend-specific services
     */
    private function initialize_frontend_services() {
        // Only load frontend services if shortcode is present on current page
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            $this->load_shortcode_services();
        }
    }
    
    /**
     * Load shortcode-related services
     */
    private function load_shortcode_services() {
        if ($this->get_feature_flag('use_new_shortcode_architecture')) {
            $this->load_new_shortcode_services();
        } else {
            $this->load_legacy_shortcode_services();
        }
    }
    
    /**
     * Load new modular shortcode services
     */
    private function load_new_shortcode_services() {
        $services = array(
            'QCC_Shortcode_Controller' => 'presentation/class-qcc-shortcode-controller.php',
            'QCC_HTML_Orchestrator' => 'rendering/orchestration/class-qcc-html-orchestrator.php',
            'QCC_Translation_Service' => 'translation/class-qcc-translation-service.php'
        );
        
        foreach ($services as $class => $file) {
            if (!class_exists($class)) {
                $full_path = QCC_PLUGIN_PATH . 'includes/' . $file;
                if (file_exists($full_path)) {
                    require_once $full_path;
                }
            }
        }
    }
    
    /**
     * Load legacy shortcode services
     */
    private function load_legacy_shortcode_services() {
        if (!class_exists('QCC_Shortcode_Legacy')) {
            $legacy_file = QCC_PLUGIN_PATH . 'includes/legacy/class-qcc-shortcode-legacy.php';
            if (file_exists($legacy_file)) {
                require_once $legacy_file;
            } else {
                // Fallback to current shortcode file
                $current_file = QCC_PLUGIN_PATH . 'includes/class-qcc-shortcode.php';
                if (file_exists($current_file)) {
                    require_once $current_file;
                }
            }
        }
    }
    
    /**
     * Initialize shared services
     */
    private function initialize_shared_services() {
        // Services needed in both admin and frontend contexts
        
        // Translation service
        if (!class_exists('QCC_Translation_Service')) {
            $translation_file = QCC_PLUGIN_PATH . 'includes/translation/class-qcc-translation-service.php';
            if (file_exists($translation_file)) {
                require_once $translation_file;
            }
        }
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        $shortcode_controller = $this->get_service('shortcode_controller');
        
        if ($shortcode_controller) {
            add_shortcode('quality_cost_calculator', array($shortcode_controller, 'render'));
        } else {
            // Fallback shortcode registration
            add_shortcode('quality_cost_calculator', array($this, 'fallback_shortcode_render'));
        }
    }
    
    /**
     * Fallback shortcode render method
     */
    public function fallback_shortcode_render($atts) {
        return '<div class="qcc-error">' . 
               __('Quality Cost Calculator is currently unavailable. Please check plugin configuration.', 'quality-cost-calculator') . 
               '</div>';
    }
    
    /**
     * Maybe enqueue frontend assets
     */
    public function maybe_enqueue_frontend_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            $this->enqueue_frontend_assets();
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    private function enqueue_frontend_assets() {
        // Chart.js from CDN
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Plugin JavaScript
        wp_enqueue_script(
            'qcc-frontend',
            QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js',
            array('jquery', 'chart-js'),
            QCC_PLUGIN_VERSION,
            true
        );
        
        // Plugin CSS
        wp_enqueue_style(
            'qcc-frontend',
            QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css',
            array(),
            QCC_PLUGIN_VERSION
        );
        
        // Localize script
        wp_localize_script('qcc-frontend', 'qcc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_nonce'),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'version' => QCC_PLUGIN_VERSION,
            'feature_flags' => $this->get_public_feature_flags()
        ));
    }
    
    /**
     * Add frontend meta information
     */
    public function add_frontend_meta() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            echo '<meta name="qcc-version" content="' . esc_attr(QCC_PLUGIN_VERSION) . '">' . "\n";
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<script>console.log("QCC Debug Mode - Version ' . QCC_PLUGIN_VERSION . '");</script>' . "\n";
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'quality-cost-calculator') !== false) {
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
        }
    }
    
    /**
     * Register admin pages
     */
    public function register_admin_pages() {
        $admin_controller = $this->get_service('admin_controller');
        
        if ($admin_controller && method_exists($admin_controller, 'register_pages')) {
            $admin_controller->register_pages();
        } else {
            // Fallback admin page registration
            add_options_page(
                __('Quality Cost Calculator', 'quality-cost-calculator'),
                __('QCC Settings', 'quality-cost-calculator'),
                'manage_options',
                'quality-cost-calculator',
                array($this, 'render_admin_page')
            );
        }
    }
    
    /**
     * Render fallback admin page
     */
    public function render_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Quality Cost Calculator Settings', 'quality-cost-calculator') . '</h1>';
        echo '<p>' . __('Admin interface is loading...', 'quality-cost-calculator') . '</p>';
        echo '</div>';
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('qcc_settings', 'qcc_feature_flags');
        register_setting('qcc_settings', 'qcc_default_language');
        register_setting('qcc_settings', 'qcc_default_currency');
        register_setting('qcc_settings', 'qcc_default_unit');
    }
    
    /**
     * Handle AJAX calculation requests
     */
    public function handle_ajax_calculation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qcc_nonce')) {
            wp_die(__('Security check failed', 'quality-cost-calculator'));
        }
        
        $calculation_engine = $this->get_service('calculation_engine');
        
        if ($calculation_engine && method_exists($calculation_engine, 'calculate')) {
            $result = $calculation_engine->calculate($_POST);
        } else {
            // Fallback calculation
            $result = $this->fallback_calculation($_POST);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Fallback calculation method
     */
    private function fallback_calculation($data) {
        // Simple fallback calculation
        $revenue = floatval($data['revenue'] ?? 0);
        $quality_percentage = floatval($data['quality_percentage'] ?? 0);
        
        return array(
            'total_cost' => $revenue * ($quality_percentage / 100),
            'cogq' => 0,
            'copq' => 0,
            'message' => __('Using fallback calculation', 'quality-cost-calculator')
        );
    }
    
    /**
     * Get service from container
     */
    public function get_service($service_name) {
        if ($this->container && method_exists($this->container, 'get')) {
            try {
                return $this->container->get($service_name);
            } catch (Exception $e) {
                error_log('QCC: Failed to get service ' . $service_name . ' - ' . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Get feature flag value
     */
    public function get_feature_flag($flag_name) {
        return $this->feature_flags[$flag_name] ?? false;
    }
    
    /**
     * Set feature flag value
     */
    public function set_feature_flag($flag_name, $value) {
        $this->feature_flags[$flag_name] = $value;
        update_option('qcc_feature_flags', $this->feature_flags);
    }
    
    /**
     * Get public feature flags (safe for frontend)
     */
    private function get_public_feature_flags() {
        return array(
            'use_modular_rendering' => $this->get_feature_flag('use_modular_rendering'),
            'enable_advanced_caching' => $this->get_feature_flag('enable_advanced_caching')
        );
    }
    
    /**
     * Plugin activation handler
     */
    public static function activate() {
        try {
            // Run activation procedures
            self::run_activation_procedures();
            
            // Set activation flag and timestamp
            update_option('qcc_activated', true);
            update_option('qcc_activation_time', current_time('timestamp'));
            update_option('qcc_version', QCC_PLUGIN_VERSION);
            
            // Create necessary database tables if needed
            self::create_database_tables();
            
            // Set default options
            self::set_default_options();
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            error_log('QCC: Activation failed - ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Plugin deactivation handler
     */
    public static function deactivate() {
        try {
            // Clean up temporary data
            delete_transient('qcc_system_check');
            delete_transient('qcc_performance_data');
            
            // Stop performance monitoring
            if (class_exists('QCC_Performance_Monitor')) {
                QCC_Performance_Monitor::stop_tracking();
            }
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
        } catch (Exception $e) {
            error_log('QCC: Deactivation failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Plugin uninstall handler
     */
    public static function uninstall() {
        try {
            // Remove all plugin options
            $options_to_remove = array(
                'qcc_activated',
                'qcc_activation_time',
                'qcc_version',
                'qcc_feature_flags',
                'qcc_default_language',
                'qcc_default_currency',
                'qcc_default_unit',
                'qcc_settings'
            );
            
            foreach ($options_to_remove as $option) {
                delete_option($option);
            }
            
            // Remove all transients
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_qcc_%' 
                OR option_name LIKE '_transient_timeout_qcc_%'"
            );
            
            // Remove uploaded files
            self::cleanup_uploaded_files();
            
            // Drop custom tables if they exist
            self::drop_database_tables();
            
        } catch (Exception $e) {
            error_log('QCC: Uninstall failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Run activation procedures
     */
    private static function run_activation_procedures() {
        // Check system requirements
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            throw new Exception('PHP 7.4 or higher required');
        }
        
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            throw new Exception('WordPress 5.0 or higher required');
        }
        
        // Check required directories are writable
        $upload_dir = wp_upload_dir();
        if (!wp_is_writable($upload_dir['basedir'])) {
            throw new Exception('Upload directory is not writable');
        }
    }
    
    /**
     * Create database tables if needed
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example: Create calculations history table
        $table_name = $wpdb->prefix . 'qcc_calculations';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0,
            calculation_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Update database version
        update_option('qcc_db_version', '1.0');
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'qcc_default_language' => 'en',
            'qcc_default_currency' => 'EUR',
            'qcc_default_unit' => '1000000',
            'qcc_feature_flags' => array(
                'use_new_shortcode_architecture' => false,
                'use_service_container' => true,
                'enable_performance_monitoring' => false,
                'use_modular_rendering' => false,
                'enable_advanced_caching' => true,
                'use_new_calculation_engine' => false
            )
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Cleanup uploaded files
     */
    private static function cleanup_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $directories_to_remove = array(
            $upload_dir['basedir'] . '/qcc-cache',
            $upload_dir['basedir'] . '/qcc-exports',
            $upload_dir['basedir'] . '/qcc-temp'
        );
        
        foreach ($directories_to_remove as $dir) {
            if (is_dir($dir)) {
                self::remove_directory_recursive($dir);
            }
        }
    }
    
    /**
     * Drop database tables
     */
    private static function drop_database_tables() {
        global $wpdb;
        
        $tables_to_drop = array(
            $wpdb->prefix . 'qcc_calculations'
        );
        
        foreach ($tables_to_drop as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove directory recursively
     */
    private static function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::remove_directory_recursive($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Get plugin status for debugging
     */
    public function get_status() {
        return array(
            'version' => QCC_PLUGIN_VERSION,
            'initialized' => $this->initialized,
            'feature_flags' => $this->feature_flags,
            'services_loaded' => $this->container ? count($this->container->get_registered_services()) : 0,
            'memory_usage' => memory_get_usage(true),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        );
    }
    
    /**
     * Is plugin properly initialized?
     */
    public function is_initialized() {
        return $this->initialized;
    }
    
    /**
     * Get service container
     */
    public function get_container() {
        return $this->container;
    }
}
<?php
/**
 * QCC Bootstrap Class - Ausgelagerte Plugin-Initialisierung
 * 
 * Datei: includes/core/class-qcc-bootstrap.php
 * 
 * Übernimmt die komplette Plugin-Initialisierung aus der Hauptdatei
 * 
 * @package QualityCostCalculator
 * @subpackage Core
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Bootstrap Class
 * 
 * Zentrale Plugin-Initialisierung und Service-Management
 */
class QCC_Bootstrap {
    
    /**
     * Plugin instance
     * 
     * @var QCC_Bootstrap
     */
    private static $instance = null;
    
    /**
     * Initialization state
     * 
     * @var bool
     */
    private static $initialized = false;
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Auto-setup system
     * 
     * @var QCC_Auto_Setup
     */
    private $auto_setup;
    
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
     * Initialize plugin system
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        $instance = self::get_instance();
        $instance->initialize();
        
        self::$initialized = true;
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin initialized successfully');
        }
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        // Private constructor for singleton
    }
    
    /**
     * Main initialization logic
     */
    private function initialize() {
        try {
            // Initialize service container
            $this->init_service_container();
            
            // Initialize auto-setup system
            $this->init_auto_setup();
            
            // Load core services
            $this->load_core_services();
            
            // Initialize WordPress integration
            $this->init_wordpress_integration();
            
            // Initialize frontend/admin
            $this->init_user_interface();
            
            // Hook into WordPress lifecycle
            $this->setup_lifecycle_hooks();
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Bootstrap Error: ' . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Initialize service container
     */
    private function init_service_container() {
        if (class_exists('QCC_Service_Container')) {
            $this->container = new QCC_Service_Container();
            $this->container->register_core_services();
        }
    }
    
    /**
     * Initialize auto-setup system
     */
    private function init_auto_setup() {
        if (class_exists('QCC_Auto_Setup')) {
            $this->auto_setup = new QCC_Auto_Setup();
            $this->auto_setup->init();
        } else {
            // Fallback to embedded auto-setup
            $this->init_embedded_auto_setup();
        }
    }
    
    /**
     * Load core services
     */
    private function load_core_services() {
        $services = array(
            'cache' => 'QCC_Cache_Service',
            'translator' => 'QCC_Translator', 
            'validator' => 'QCC_Validator',
            'calculator' => 'QCC_Calculator'
        );
        
        foreach ($services as $key => $class) {
            if (class_exists($class)) {
                if ($this->container) {
                    $this->container->register($key, $class);
                }
            }
        }
    }
    
    /**
     * Initialize WordPress integration
     */
    private function init_wordpress_integration() {
        // Shortcode registration
        if (class_exists('QCC_Shortcode')) {
            new QCC_Shortcode();
        }
        
        // Admin integration
        if (is_admin() && class_exists('QCC_Admin')) {
            $admin = new QCC_Admin();
            $admin->init();
        }
        
        // REST API
        if (class_exists('QCC_API')) {
            $api = new QCC_API();
            $api->init();
        }
        
        // Asset management
        if (class_exists('QCC_Assets')) {
            $assets = new QCC_Assets();
            $assets->init();
        }
    }
    
    /**
     * Initialize user interface
     */
    private function init_user_interface() {
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Template hooks
        add_action('qcc_template_loaded', array($this, 'setup_template_hooks'));
    }
    
    /**
     * Setup WordPress lifecycle hooks
     */
    private function setup_lifecycle_hooks() {
        // Plugin actions
        add_action('qcc_daily_cleanup', array($this, 'daily_cleanup'));
        
        // Emergency recovery
        add_action('admin_init', array($this, 'handle_emergency_recovery'));
        
        // Performance monitoring
        if (QCC_DEBUG && class_exists('QCC_Performance_Monitor')) {
            $monitor = new QCC_Performance_Monitor();
            $monitor->init();
        }
    }
    
    /**
     * Plugin activation handler
     */
    public static function activate() {
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin activation started');
        }
        
        // Create required database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('qcc_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'qcc_daily_cleanup');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin activation completed');
        }
    }
    
    /**
     * Plugin deactivation handler
     */
    public static function deactivate() {
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin deactivation started');
        }
        
        // Clear scheduled hooks
        wp_clear_scheduled_hook('qcc_daily_cleanup');
        
        // Clear cache
        if (class_exists('QCC_Cache_Service')) {
            $cache = new QCC_Cache_Service();
            $cache->clear_all();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin deactivation completed');
        }
    }
    
    /**
     * Plugin uninstall handler
     */
    public static function uninstall() {
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin uninstall started');
        }
        
        // Remove all options
        $options = array(
            'qcc_default_language',
            'qcc_default_currency', 
            'qcc_default_unit',
            'qcc_cache_enabled',
            'qcc_debug_enabled'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove all transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_qcc_%' 
             OR option_name LIKE '_transient_timeout_qcc_%'"
        );
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin uninstall completed');
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if ($this->should_load_frontend_assets()) {
            wp_enqueue_style(
                'qcc-frontend', 
                QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css', 
                array(), 
                QCC_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'qcc-frontend', 
                QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js', 
                array('jquery'), 
                QCC_PLUGIN_VERSION, 
                true
            );
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
        }
    }
    
    /**
     * Daily cleanup routine
     */
    public function daily_cleanup() {
        // Clear expired cache
        if ($this->container && $this->container->has('cache')) {
            $cache = $this->container->get('cache');
            $cache->cleanup_expired();
        }
        
        // Log cleanup
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Daily cleanup completed');
        }
    }
    
    /**
     * Handle emergency recovery
     */
    public function handle_emergency_recovery() {
        if (isset($_GET['qcc_emergency_reset']) && current_user_can('administrator')) {
            if ($_GET['qcc_emergency_reset'] === 'confirm' && 
                wp_verify_nonce($_GET['_wpnonce'], 'qcc_emergency_reset')) {
                
                $this->emergency_reset();
                wp_redirect(admin_url('plugins.php?qcc_reset=success'));
                exit;
            }
        }
    }
    
    /**
     * Emergency reset functionality
     */
    private function emergency_reset() {
        // Reset to safe defaults
        update_option('qcc_default_language', 'en');
        update_option('qcc_default_currency', 'EUR');
        update_option('qcc_default_unit', '1000000');
        
        // Clear cache
        if ($this->container && $this->container->has('cache')) {
            $cache = $this->container->get('cache');
            $cache->clear_all();
        }
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Emergency reset completed');
        }
    }
    
    /**
     * Embedded auto-setup fallback
     */
    private function init_embedded_auto_setup() {
        add_action('init', function() {
            $required_files = array(
                'includes/class-qcc-calculator.php' => $this->get_calculator_template(),
                'includes/class-qcc-validator.php' => $this->get_validator_template(),
                'templates/calculator.php' => $this->get_template_content()
            );
            
            foreach ($required_files as $file => $content) {
                $file_path = QCC_PLUGIN_PATH . $file;
                if (!file_exists($file_path)) {
                    wp_mkdir_p(dirname($file_path));
                    file_put_contents($file_path, $content);
                    
                    if (QCC_DEBUG) {
                        error_log("QCC Bootstrap: Auto-created {$file}");
                    }
                }
            }
        }, 999);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'qcc_default_language' => 'en',
            'qcc_default_currency' => 'EUR',
            'qcc_default_unit' => '1000000',
            'qcc_cache_enabled' => true,
            'qcc_debug_enabled' => false
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    /**
     * Create database tables if needed
     */
    private static function create_database_tables() {
        // Currently no custom tables needed
        // Placeholder for future database requirements
    }
    
    /**
     * Check if frontend assets should be loaded
     */
    private function should_load_frontend_assets() {
        global $post;
        
        // Load on pages with shortcode
        if ($post && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            return true;
        }
        
        // Load on QCC admin pages
        if (is_admin() && isset($_GET['page']) && 
            strpos($_GET['page'], 'quality-cost-calculator') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get calculator template content
     */
    private function get_calculator_template() {
        return '<?php
class QCC_Calculator {
    public function calculate($input) {
        $total_quality_cost = $input["revenue"] * ($input["quality_percentage"] / 100);
        
        return array(
            "total_quality_cost" => $total_quality_cost,
            "prevention_cost" => ($total_quality_cost * $input["prevention"]) / 100,
            "appraisal_cost" => ($total_quality_cost * $input["appraisal"]) / 100,
            "internal_defect_cost" => ($total_quality_cost * $input["internal_defect"]) / 100,
            "external_defect_cost" => ($total_quality_cost * $input["external_defect"]) / 100
        );
    }
}';
    }
    
    /**
     * Get validator template content
     */
    private function get_validator_template() {
        return '<?php
class QCC_Validator {
    public function validate($input) {
        $total = $input["prevention"] + $input["appraisal"] + $input["internal_defect"] + $input["external_defect"];
        return abs($total - 100) < 0.01;
    }
}';
    }
    
    /**
     * Get basic template content
     */
    private function get_template_content() {
        return '<div class="qcc-calculator">
    <h3>Quality Cost Calculator</h3>
    <p>Template auto-generated. Please install complete template files.</p>
</div>';
    }
}
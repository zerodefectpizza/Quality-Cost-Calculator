<?php
/**
 * QCC Legacy Bootstrap - Fallback System for Existing Implementation
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy Bootstrap Class
 * 
 * Provides robust fallback to existing plugin implementation
 * Used when new architecture is not available or fails
 */
class QCC_Legacy_Bootstrap {
    
    /**
     * Legacy instance
     * 
     * @var QCC_Legacy_Bootstrap|null
     */
    private static $instance = null;
    
    /**
     * Legacy initialization status
     * 
     * @var bool
     */
    private $legacy_initialized = false;
    
    /**
     * Legacy services
     * 
     * @var array
     */
    private $legacy_services = array();
    
    /**
     * Get singleton instance
     * 
     * @return QCC_Legacy_Bootstrap
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        // Initialize legacy system immediately
        $this->initialize_legacy_system();
    }
    
    /**
     * Initialize legacy system
     * 
     * @return bool True on success, false on failure
     */
    public static function initialize() {
        $instance = self::get_instance();
        return $instance->init_legacy();
    }
    
    /**
     * Main legacy initialization
     * 
     * @return bool
     */
    private function init_legacy() {
        if ($this->legacy_initialized) {
            return true;
        }
        
        try {
            // Log legacy fallback usage
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC: Using Legacy Bootstrap fallback system');
            }
            
            // Load legacy core classes
            $this->load_legacy_core_classes();
            
            // Setup legacy hooks
            $this->setup_legacy_hooks();
            
            // Initialize legacy services
            $this->initialize_legacy_services();
            
            // Register legacy shortcode
            $this->register_legacy_shortcode();
            
            $this->legacy_initialized = true;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC: Legacy Bootstrap initialization completed successfully');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('QCC: Legacy Bootstrap initialization failed - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Initialize legacy system from existing implementation
     */
    private function initialize_legacy_system() {
        // This method sets up the legacy system immediately when class is instantiated
        add_action('init', array($this, 'ensure_legacy_compatibility'), 5);
    }
    
    /**
     * Ensure legacy compatibility
     */
    public function ensure_legacy_compatibility() {
        // Make sure legacy shortcode is always available
        if (!shortcode_exists('quality_cost_calculator')) {
            $this->register_legacy_shortcode();
        }
        
        // Ensure legacy admin interface
        if (is_admin()) {
            $this->ensure_legacy_admin();
        }
    }
    
    /**
     * Load legacy core classes
     */
    private function load_legacy_core_classes() {
        $legacy_classes = array(
            'QCC_Core' => 'class-qcc-core.php',
            'QCC_Admin' => 'class-qcc-admin.php',
            'QCC_Ajax' => 'class-qcc-ajax.php',
            'QCC_I18n' => 'class-qcc-i18n.php',
            'QCC_Cache' => 'class-qcc-cache.php',
            'QCC_Validator' => 'class-qcc-validator.php',
            'QCC_Translator' => 'class-qcc-translator.php'
        );
        
        foreach ($legacy_classes as $class_name => $file_name) {
            if (!class_exists($class_name)) {
                $file_path = QCC_PLUGIN_PATH . 'includes/' . $file_name;
                if (file_exists($file_path)) {
                    require_once $file_path;
                    
                    if (class_exists($class_name)) {
                        $this->legacy_services[$class_name] = $class_name;
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("QCC Legacy: Loaded {$class_name} from {$file_name}");
                        }
                    }
                }
            } else {
                $this->legacy_services[$class_name] = $class_name;
            }
        }
    }
    
    /**
     * Load legacy shortcode
     */
    private function load_legacy_shortcode() {
        // Try to load from legacy directory first
        $legacy_shortcode_file = QCC_PLUGIN_PATH . 'includes/legacy/class-qcc-shortcode-legacy.php';
        if (file_exists($legacy_shortcode_file)) {
            require_once $legacy_shortcode_file;
            return 'QCC_Shortcode_Legacy';
        }
        
        // Fallback to current shortcode file
        $current_shortcode_file = QCC_PLUGIN_PATH . 'includes/class-qcc-shortcode.php';
        if (file_exists($current_shortcode_file)) {
            require_once $current_shortcode_file;
            return 'QCC_Shortcode';
        }
        
        return false;
    }
    
    /**
     * Setup legacy hooks
     */
    private function setup_legacy_hooks() {
        // Frontend hooks
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_legacy_frontend_assets'));
            add_action('wp_head', array($this, 'add_legacy_frontend_meta'));
        }
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_legacy_admin_assets'));
            add_action('admin_menu', array($this, 'register_legacy_admin_pages'));
        }
        
        // AJAX hooks
        add_action('wp_ajax_qcc_calculate', array($this, 'handle_legacy_ajax'));
        add_action('wp_ajax_nopriv_qcc_calculate', array($this, 'handle_legacy_ajax'));
    }
    
    /**
     * Initialize legacy services
     */
    private function initialize_legacy_services() {
        // Initialize QCC_Core if available
        if (class_exists('QCC_Core')) {
            $core_instance = QCC_Core::get_instance();
            if ($core_instance) {
                $this->legacy_services['core_instance'] = $core_instance;
            }
        }
        
        // Initialize QCC_Admin if available and in admin
        if (is_admin() && class_exists('QCC_Admin')) {
            $admin_instance = new QCC_Admin();
            $this->legacy_services['admin_instance'] = $admin_instance;
        }
        
        // Initialize QCC_I18n if available
        if (class_exists('QCC_I18n')) {
            $i18n_instance = new QCC_I18n();
            $this->legacy_services['i18n_instance'] = $i18n_instance;
        }
    }
    
    /**
     * Register legacy shortcode
     */
    private function register_legacy_shortcode() {
        $shortcode_class = $this->load_legacy_shortcode();
        
        if ($shortcode_class && class_exists($shortcode_class)) {
            // Remove existing shortcode if registered
            if (shortcode_exists('quality_cost_calculator')) {
                remove_shortcode('quality_cost_calculator');
            }
            
            // Register legacy shortcode
            if (method_exists($shortcode_class, 'render_calculator')) {
                $instance = new $shortcode_class();
                add_shortcode('quality_cost_calculator', array($instance, 'render_calculator'));
            } else {
                // Fallback shortcode method
                add_shortcode('quality_cost_calculator', array($this, 'fallback_shortcode_render'));
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("QCC Legacy: Registered shortcode with class {$shortcode_class}");
            }
        } else {
            // Emergency fallback
            add_shortcode('quality_cost_calculator', array($this, 'emergency_shortcode_render'));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC Legacy: Using emergency shortcode fallback');
            }
        }
    }
    
    /**
     * Fallback shortcode render method
     */
    public function fallback_shortcode_render($atts) {
        $atts = shortcode_atts(array(
            'language' => 'en',
            'currency' => 'EUR',
            'unit' => '1000000'
        ), $atts, 'quality_cost_calculator');
        
        // Try to use template if available
        $template_file = QCC_PLUGIN_PATH . 'templates/calculator-template.php';
        if (file_exists($template_file)) {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }
        
        // Basic HTML fallback
        return $this->get_basic_calculator_html($atts);
    }
    
    /**
     * Emergency shortcode render (minimal functionality)
     */
    public function emergency_shortcode_render($atts) {
        return '<div class="qcc-emergency-mode">' .
               '<h3>' . __('Quality Cost Calculator', 'quality-cost-calculator') . '</h3>' .
               '<p>' . __('Calculator is temporarily unavailable. Please try again later.', 'quality-cost-calculator') . '</p>' .
               '<p><small>' . __('Emergency mode active', 'quality-cost-calculator') . '</small></p>' .
               '</div>';
    }
    
    /**
     * Get basic calculator HTML
     */
    private function get_basic_calculator_html($atts) {
        $html = '<div class="qcc-calculator-legacy">';
        $html .= '<h3>' . __('Quality Cost Calculator', 'quality-cost-calculator') . '</h3>';
        $html .= '<p>' . __('Legacy mode - basic functionality available', 'quality-cost-calculator') . '</p>';
        $html .= '<div class="qcc-notice">';
        $html .= __('Note: Using legacy fallback mode. Some features may be limited.', 'quality-cost-calculator');
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Enqueue legacy frontend assets
     */
    public function enqueue_legacy_frontend_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            // Enqueue existing assets
            wp_enqueue_script(
                'chart-js',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            $js_file = QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js';
            $css_file = QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css';
            
            wp_enqueue_script(
                'qcc-legacy-frontend',
                $js_file,
                array('jquery', 'chart-js'),
                QCC_PLUGIN_VERSION,
                true
            );
            
            wp_enqueue_style(
                'qcc-legacy-frontend',
                $css_file,
                array(),
                QCC_PLUGIN_VERSION
            );
            
            // Localize script
            wp_localize_script('qcc-legacy-frontend', 'qcc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('qcc_nonce'),
                'legacy_mode' => true,
                'version' => QCC_PLUGIN_VERSION
            ));
        }
    }
    
    /**
     * Add legacy frontend meta
     */
    public function add_legacy_frontend_meta() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            echo '<meta name="qcc-mode" content="legacy">' . "\n";
            echo '<meta name="qcc-version" content="' . esc_attr(QCC_PLUGIN_VERSION) . '">' . "\n";
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<script>console.log("QCC Legacy Mode Active - Version ' . QCC_PLUGIN_VERSION . '");</script>' . "\n";
            }
        }
    }
    
    /**
     * Enqueue legacy admin assets
     */
    public function enqueue_legacy_admin_assets($hook) {
        if (strpos($hook, 'quality-cost-calculator') !== false) {
            wp_enqueue_style(
                'qcc-legacy-admin',
                QCC_PLUGIN_URL . 'assets/admin.css',
                array(),
                QCC_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'qcc-legacy-admin',
                QCC_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                QCC_PLUGIN_VERSION,
                true
            );
        }
    }
    
    /**
     * Register legacy admin pages
     */
    public function register_legacy_admin_pages() {
        if (class_exists('QCC_Admin') && isset($this->legacy_services['admin_instance'])) {
            // Use existing admin implementation
            if (method_exists($this->legacy_services['admin_instance'], 'add_admin_menu')) {
                $this->legacy_services['admin_instance']->add_admin_menu();
            }
        } else {
            // Fallback admin page
            add_options_page(
                __('Quality Cost Calculator', 'quality-cost-calculator'),
                __('QCC Settings', 'quality-cost-calculator'),
                'manage_options',
                'quality-cost-calculator',
                array($this, 'render_legacy_admin_page')
            );
        }
    }
    
    /**
     * Render legacy admin page
     */
    public function render_legacy_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Quality Cost Calculator - Legacy Mode', 'quality-cost-calculator') . '</h1>';
        echo '<div class="notice notice-warning">';
        echo '<p>' . __('Plugin is running in legacy mode. Some features may be limited.', 'quality-cost-calculator') . '</p>';
        echo '</div>';
        echo '<p>' . __('Legacy admin interface is active.', 'quality-cost-calculator') . '</p>';
        echo '</div>';
    }
    
    /**
     * Ensure legacy admin
     */
    private function ensure_legacy_admin() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Make sure admin menu exists
        add_action('admin_menu', array($this, 'register_legacy_admin_pages'), 20);
    }
    
    /**
     * Handle legacy AJAX requests
     */
    public function handle_legacy_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qcc_nonce')) {
            wp_die(__('Security check failed', 'quality-cost-calculator'));
        }
        
        // Try to use existing AJAX handler
        if (class_exists('QCC_Ajax')) {
            $ajax_instance = new QCC_Ajax();
            if (method_exists($ajax_instance, 'handle_calculation')) {
                return $ajax_instance->handle_calculation();
            }
        }
        
        // Fallback AJAX calculation
        $revenue = floatval($_POST['revenue'] ?? 0);
        $quality_percentage = floatval($_POST['quality_percentage'] ?? 0);
        
        $result = array(
            'total_cost' => $revenue * ($quality_percentage / 100),
            'cogq' => 0,
            'copq' => 0,
            'legacy_mode' => true,
            'message' => __('Using legacy calculation', 'quality-cost-calculator')
        );
        
        wp_send_json_success($result);
    }
    
    /**
     * Legacy activation handler
     */
    public static function activate() {
        try {
            // Set legacy mode flag
            update_option('qcc_legacy_mode', true);
            update_option('qcc_legacy_activation_time', current_time('timestamp'));
            
            // Run legacy activation if available
            if (class_exists('QCC_Core')) {
                $core = QCC_Core::get_instance();
                if (method_exists($core, 'activate')) {
                    $core->activate();
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC: Legacy activation completed');
            }
            
        } catch (Exception $e) {
            error_log('QCC: Legacy activation failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Legacy deactivation handler
     */
    public static function deactivate() {
        try {
            // Clean up legacy data
            delete_transient('qcc_legacy_cache');
            
            // Run legacy deactivation if available
            if (class_exists('QCC_Core')) {
                $core = QCC_Core::get_instance();
                if (method_exists($core, 'deactivate')) {
                    $core->deactivate();
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC: Legacy deactivation completed');
            }
            
        } catch (Exception $e) {
            error_log('QCC: Legacy deactivation failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Legacy uninstall handler
     */
    public static function uninstall() {
        try {
            // Remove legacy options
            delete_option('qcc_legacy_mode');
            delete_option('qcc_legacy_activation_time');
            
            // Run legacy uninstall if available
            if (class_exists('QCC_Core')) {
                $core = QCC_Core::get_instance();
                if (method_exists($core, 'uninstall')) {
                    $core->uninstall();
                }
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC: Legacy uninstall completed');
            }
            
        } catch (Exception $e) {
            error_log('QCC: Legacy uninstall failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Get legacy status
     */
    public function get_legacy_status() {
        return array(
            'legacy_mode' => true,
            'initialized' => $this->legacy_initialized,
            'loaded_services' => array_keys($this->legacy_services),
            'shortcode_registered' => shortcode_exists('quality_cost_calculator'),
            'version' => QCC_PLUGIN_VERSION,
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    /**
     * Is legacy system initialized?
     */
    public function is_initialized() {
        return $this->legacy_initialized;
    }
    
    /**
     * Get legacy service
     */
    public function get_legacy_service($service_name) {
        return isset($this->legacy_services[$service_name]) ? $this->legacy_services[$service_name] : null;
    }
}
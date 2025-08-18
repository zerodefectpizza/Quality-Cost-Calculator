<?php
/**
 * QCC Main Integration Hook
 * 
 * Main integration file that hooks the new rendering system into
 * the existing WordPress shortcode infrastructure.
 *
 * @package QualityCostCalculator
 * @subpackage Integration
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main integration class that replaces or extends the existing shortcode handler
 */
class QCC_Main_Integration {
    
    private static $instance = null;
    private $integration_bridge;
    private $legacy_shortcode_handler;
    private $is_legacy_mode = false;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_integration();
    }
    
    /**
     * Initialize the integration system
     */
    private function init_integration() {
        try {
            // Initialize service container first
            QCC_Service_Container_Setup::init();
            
            // Initialize integration bridge
            $this->integration_bridge = QCC_Service_Container::get('shortcode_integration');
            
            // Setup WordPress hooks
            $this->setup_hooks();
            
            // Warmup critical services
            QCC_Service_Container_Setup::warmup();
            
        } catch (Exception $e) {
            error_log('QCC Main Integration Error: ' . $e->getMessage());
            $this->fallback_to_legacy_mode();
        }
    }
    
    /**
     * Setup WordPress hooks and filters
     */
    private function setup_hooks() {
        // Replace or hook into existing shortcode
        add_action('init', array($this, 'register_shortcode'), 15); // After original registration
        
        // Admin hooks for settings and debug
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_filter('the_content', array($this, 'process_shortcode_in_content'), 11);
        
        // AJAX hooks for dynamic functionality
        add_action('wp_ajax_qcc_render_component', array($this, 'ajax_render_component'));
        add_action('wp_ajax_nopriv_qcc_render_component', array($this, 'ajax_render_component'));
        
        // Integration status hooks
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Debug hooks
        if (WP_DEBUG) {
            add_action('wp_footer', array($this, 'output_debug_dashboard'));
        }
    }
    
    /**
     * Register the quality cost calculator shortcode
     */
    public function register_shortcode() {
        // Check if shortcode already exists (backward compatibility)
        global $shortcode_tags;
        
        if (isset($shortcode_tags['quality_cost_calculator'])) {
            // Store existing handler for fallback
            $this->legacy_shortcode_handler = $shortcode_tags['quality_cost_calculator'];
        }
        
        // Register our integrated shortcode handler
        add_shortcode('quality_cost_calculator', array($this, 'handle_shortcode'));
        
        // Also register alternative shortcode names for flexibility
        add_shortcode('qcc_calculator', array($this, 'handle_shortcode'));
        add_shortcode('cost_quality_calculator', array($this, 'handle_shortcode'));
    }
    
    /**
     * Main shortcode handler
     */
    public function handle_shortcode($atts = array(), $content = '') {
        // Track shortcode usage
        $this->track_shortcode_usage($atts);
        
        try {
            // Use integration bridge to render
            return $this->integration_bridge->render_calculator($atts, $content);
            
        } catch (Exception $e) {
            error_log('QCC Shortcode Error: ' . $e->getMessage());
            
            // Fallback to legacy handler if available
            if ($this->legacy_shortcode_handler && is_callable($this->legacy_shortcode_handler)) {
                return call_user_func($this->legacy_shortcode_handler, $atts, $content);
            }
            
            // Ultimate fallback
            return $this->render_error_message($e->getMessage());
        }
    }
    
    /**
     * Process shortcode in content with enhanced features
     */
    public function process_shortcode_in_content($content) {
        // Only process if content contains our shortcode
        if (false === strpos($content, '[quality_cost_calculator') && 
            false === strpos($content, '[qcc_calculator') &&
            false === strpos($content, '[cost_quality_calculator')) {
            return $content;
        }
        
        // Add container wrapper for enhanced styling
        $content = preg_replace_callback(
            '/(\[(?:quality_cost_calculator|qcc_calculator|cost_quality_calculator)[^\]]*\])/i',
            function($matches) {
                $shortcode = $matches[1];
                return '<div class="qcc-shortcode-wrapper">' . do_shortcode($shortcode) . '</div>';
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * AJAX handler for dynamic component rendering
     */
    public function ajax_render_component() {
        check_ajax_referer('qcc_ajax_nonce', 'nonce');
        
        $component_type = sanitize_text_field($_POST['component_type']);
        $component_data = $_POST['component_data'] ?? array();
        $calculator_id = sanitize_text_field($_POST['calculator_id']);
        
        try {
            $component_registry = QCC_Service_Container::get('component_registry');
            
            if ($component_registry->has($component_type)) {
                $component = $component_registry->get($component_type);
                $html = $component->render($component_data);
                
                wp_send_json_success(array(
                    'html' => $html,
                    'component_type' => $component_type,
                    'calculator_id' => $calculator_id
                ));
            } else {
                wp_send_json_error('Component not found: ' . $component_type);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Render error: ' . $e->getMessage());
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue on pages that might have the shortcode
        if (!$this->should_enqueue_assets()) {
            return;
        }
        
        // Main calculator CSS
        wp_enqueue_style(
            'qcc-calculator',
            plugins_url('assets/css/qcc-calculator.css', QCC_PLUGIN_FILE),
            array(),
            QCC_VERSION
        );
        
        // Main calculator JavaScript
        wp_enqueue_script(
            'qcc-calculator',
            plugins_url('assets/js/qcc-calculator.js', QCC_PLUGIN_FILE),
            array('jquery'),
            QCC_VERSION,
            true
        );
        
        // Chart.js for charts functionality
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('qcc-calculator', 'qcc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_ajax_nonce'),
            'integration_mode' => $this->is_legacy_mode ? 'legacy' : 'atomic'
        ));
    }
    
    /**
     * Enqueue admin scripts for settings page
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'qcc') === false) {
            return;
        }
        
        wp_enqueue_script(
            'qcc-admin',
            plugins_url('assets/js/qcc-admin.js', QCC_PLUGIN_FILE),
            array('jquery'),
            QCC_VERSION,
            true
        );
        
        wp_localize_script('qcc-admin', 'qcc_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_admin_nonce')
        ));
    }
    
    /**
     * Add admin menu for integration settings
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('QCC Integration Settings', 'quality-cost-calculator'),
            __('QCC Integration', 'quality-cost-calculator'),
            'manage_options',
            'qcc-integration',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        $status = QCC_Service_Container_Setup::get_status();
        $stats = $this->integration_bridge ? $this->integration_bridge->get_integration_stats() : array();
        
        ?>
        <div class="wrap">
            <h1><?php _e('QCC Integration Settings', 'quality-cost-calculator'); ?></h1>
            
            <div class="qcc-admin-grid">
                <div class="qcc-admin-section">
                    <h2><?php _e('System Status', 'quality-cost-calculator'); ?></h2>
                    <table class="widefat">
                        <tr>
                            <td><?php _e('Service Container', 'quality-cost-calculator'); ?></td>
                            <td><?php echo $status['initialized'] ? '✅ Initialized' : '❌ Not Initialized'; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Integration Mode', 'quality-cost-calculator'); ?></td>
                            <td><?php echo $this->is_legacy_mode ? '🔄 Legacy' : '⚡ Atomic'; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Total Services', 'quality-cost-calculator'); ?></td>
                            <td><?php echo $status['total_services'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Memory Usage', 'quality-cost-calculator'); ?></td>
                            <td><?php echo size_format($status['memory_usage'] ?? 0); ?></td>
                        </tr>
                    </table>
                </div>
                
                <div class="qcc-admin-section">
                    <h2><?php _e('Usage Statistics', 'quality-cost-calculator'); ?></h2>
                    <table class="widefat">
                        <tr>
                            <td><?php _e('Total Renders', 'quality-cost-calculator'); ?></td>
                            <td><?php echo $stats['total_renders'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Atomic Renders', 'quality-cost-calculator'); ?></td>
                            <td><?php echo $stats['atomic_renders'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Legacy Renders', 'quality-cost-calculator'); ?></td>
                            <td><?php echo $stats['legacy_renders'] ?? 0; ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Average Render Time', 'quality-cost-calculator'); ?></td>
                            <td><?php echo round($stats['average_render_time'] ?? 0, 4); ?>s</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('qcc_integration');
                do_settings_sections('qcc_integration');
                submit_button();
                ?>
            </form>
            
            <?php if (WP_DEBUG): ?>
            <div class="qcc-admin-section">
                <h2><?php _e('Debug Information', 'quality-cost-calculator'); ?></h2>
                <div class="qcc-debug-container">
                    <?php QCC_Service_Container_Setup::debug_services(); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .qcc-admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .qcc-admin-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
        }
        .qcc-debug-container {
            background: #f1f1f1;
            padding: 10px;
            font-family: monospace;
            white-space: pre-wrap;
        }
        </style>
        <?php
    }
    
    /**
     * Add dashboard widget for quick status
     */
    public function add_dashboard_widget() {
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'qcc_integration_status',
                __('QCC Integration Status', 'quality-cost-calculator'),
                array($this, 'render_dashboard_widget')
            );
        }
    }
    
    /**
     * Render dashboard widget content
     */
    public function render_dashboard_widget() {
        $status = QCC_Service_Container_Setup::get_status();
        $stats = $this->integration_bridge ? $this->integration_bridge->get_integration_stats() : array();
        
        echo '<div class="qcc-dashboard-widget">';
        echo '<p><strong>Status:</strong> ' . ($status['initialized'] ? '✅ Active' : '❌ Error') . '</p>';
        echo '<p><strong>Mode:</strong> ' . ($this->is_legacy_mode ? 'Legacy' : 'Atomic Design') . '</p>';
        echo '<p><strong>Total Renders:</strong> ' . ($stats['total_renders'] ?? 0) . '</p>';
        echo '<p><a href="' . admin_url('options-general.php?page=qcc-integration') . '">View Details →</a></p>';
        echo '</div>';
    }
    
    /**
     * Output debug dashboard in footer
     */
    public function output_debug_dashboard() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $status = QCC_Service_Container_Setup::get_status();
        
        echo '<div id="qcc-debug-dashboard" style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; font-size: 12px; z-index: 9999;">
            <strong>QCC Debug</strong><br>
            Mode: ' . ($this->is_legacy_mode ? 'Legacy' : 'Atomic') . '<br>
            Services: ' . ($status['total_services'] ?? 0) . '<br>
            Memory: ' . size_format($status['memory_usage'] ?? 0) . '
        </div>';
    }
    
    /**
     * Check if we should enqueue assets on current page
     */
    private function should_enqueue_assets() {
        global $post;
        
        // Always enqueue on admin pages
        if (is_admin()) {
            return true;
        }
        
        // Check if current post/page contains shortcode
        if ($post && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            return true;
        }
        
        // Check for alternative shortcode names
        if ($post && (has_shortcode($post->post_content, 'qcc_calculator') || 
                     has_shortcode($post->post_content, 'cost_quality_calculator'))) {
            return true;
        }
        
        // For widgets and other dynamic content
        if (is_active_widget(false, false, 'text') || is_active_widget(false, false, 'custom_html')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Track shortcode usage for analytics
     */
    private function track_shortcode_usage($atts) {
        $usage_data = get_option('qcc_usage_data', array());
        $today = date('Y-m-d');
        
        if (!isset($usage_data[$today])) {
            $usage_data[$today] = 0;
        }
        
        $usage_data[$today]++;
        
        // Keep only last 30 days
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        foreach ($usage_data as $date => $count) {
            if ($date < $cutoff) {
                unset($usage_data[$date]);
            }
        }
        
        update_option('qcc_usage_data', $usage_data);
    }
    
    /**
     * Fallback to legacy mode
     */
    private function fallback_to_legacy_mode() {
        $this->is_legacy_mode = true;
        
        // Try to load legacy shortcode handler
        if (class_exists('QCC_Shortcode_Renderer')) {
            $this->legacy_shortcode_handler = array(new QCC_Shortcode_Renderer(), 'render_calculator');
        }
    }
    
    /**
     * Render error message
     */
    private function render_error_message($error_message) {
        if (WP_DEBUG) {
            return sprintf(
                '<div class="qcc-error" style="padding: 15px; border: 2px solid #dc3545; background: #f8d7da; color: #721c24;">
                    <strong>QCC Error:</strong> %s
                </div>',
                esc_html($error_message)
            );
        }
        
        return '<div class="qcc-error">' . __('Calculator temporarily unavailable.', 'quality-cost-calculator') . '</div>';
    }
    
    /**
     * Get integration instance (for external access)
     */
    public function get_integration_bridge() {
        return $this->integration_bridge;
    }
    
    /**
     * Check if running in legacy mode
     */
    public function is_legacy_mode() {
        return $this->is_legacy_mode;
    }
    
    /**
     * Force switch to atomic mode (for testing)
     */
    public function force_atomic_mode() {
        $this->is_legacy_mode = false;
        
        try {
            $this->integration_bridge = QCC_Service_Container::get('shortcode_integration');
            return true;
        } catch (Exception $e) {
            $this->fallback_to_legacy_mode();
            return false;
        }
    }
    
    /**
     * Force switch to legacy mode (for testing)
     */
    public function force_legacy_mode() {
        $this->is_legacy_mode = true;
        return true;
    }
    
    /**
     * Get detailed system diagnostics
     */
    public function get_system_diagnostics() {
        $diagnostics = array(
            'timestamp' => current_time('mysql'),
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'qcc_version' => defined('QCC_VERSION') ? QCC_VERSION : 'unknown',
            'integration_mode' => $this->is_legacy_mode ? 'legacy' : 'atomic',
            'service_container' => QCC_Service_Container_Setup::get_status(),
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'active_plugins' => get_option('active_plugins'),
            'theme' => get_template(),
            'errors' => array()
        );
        
        // Test critical components
        $critical_tests = array(
            'service_container' => 'QCC_Service_Container_Setup::is_initialized',
            'html_orchestrator' => function() { 
                try {
                    return QCC_Service_Container::get('html_orchestrator') !== null;
                } catch (Exception $e) {
                    return false;
                }
            },
            'component_registry' => function() {
                try {
                    return QCC_Service_Container::get('component_registry') !== null;
                } catch (Exception $e) {
                    return false;
                }
            }
        );
        
        foreach ($critical_tests as $test_name => $test) {
            try {
                $result = is_callable($test) ? call_user_func($test) : false;
                $diagnostics['tests'][$test_name] = $result;
            } catch (Exception $e) {
                $diagnostics['tests'][$test_name] = false;
                $diagnostics['errors'][] = $test_name . ': ' . $e->getMessage();
            }
        }
        
        return $diagnostics;
    }
    
    /**
     * Export diagnostics for support
     */
    public function export_diagnostics() {
        $diagnostics = $this->get_system_diagnostics();
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="qcc-diagnostics-' . date('Y-m-d-H-i-s') . '.json"');
        
        echo wp_json_encode($diagnostics, JSON_PRETTY_PRINT);
        exit;
    }
}

// Initialize the main integration when WordPress is ready
add_action('plugins_loaded', function() {
    if (class_exists('QCC_Service_Container_Setup')) {
        QCC_Main_Integration::get_instance();
    } else {
        error_log('QCC: Service Container Setup class not found. Integration aborted.');
    }
}, 20);
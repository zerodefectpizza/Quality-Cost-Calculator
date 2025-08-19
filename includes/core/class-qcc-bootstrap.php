<?php
/**
 * QCC Bootstrap - Neue Hauptarchitektur
 * 
 * SPEICHERN ALS: wp-content/plugins/quality-cost-calculator/includes/core/class-qcc-bootstrap.php
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Bootstrap Class - Moderne Architektur
 */
class QCC_Bootstrap {
    
    /**
     * Single instance
     * @var QCC_Bootstrap|null
     */
    private static $instance = null;
    
    /**
     * Service container
     * @var QCC_Service_Container|null
     */
    private $container = null;
    
    /**
     * Initialization status
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin (called from main file)
     */
    public static function initialize() {
        $instance = self::get_instance();
        return $instance->init();
    }
    
    /**
     * Main initialization method
     */
    private function init() {
        if ($this->initialized) {
            return true;
        }
        
        try {
            // Load service container
            $this->init_service_container();
            
            // Setup WordPress hooks
            $this->setup_hooks();
            
            // Register shortcodes
            $this->register_shortcodes();
            
            $this->initialized = true;
            
            if (QCC_DEBUG) {
                error_log('QCC Bootstrap: Neue Architektur erfolgreich initialisiert');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('QCC Bootstrap Error: ' . $e->getMessage());
            return $this->emergency_fallback();
        }
    }
    
    /**
     * Initialize service container
     */
    private function init_service_container() {
        // Load service container if not already loaded
        if (!class_exists('QCC_Service_Container')) {
            $container_file = QCC_PLUGIN_PATH . 'includes/core/class-qcc-service-container.php';
            if (file_exists($container_file)) {
                require_once $container_file;
            }
        }
        
        if (class_exists('QCC_Service_Container')) {
            $this->container = new QCC_Service_Container();
            
            // Register core services
            $this->register_core_services();
        }
    }
    
    /**
     * Register core services
     */
    private function register_core_services() {
        // Calculator service
        $this->container->register('calculator', function() {
            return $this->create_calculator_service();
        });
        
        // Validator service
        $this->container->register('validator', function() {
            return $this->create_validator_service();
        });
        
        // Renderer service
        $this->container->register('renderer', function() {
            return $this->create_renderer_service();
        });
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Frontend hooks
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        }
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_menu', array($this, 'register_admin_menu'));
        }
        
        // AJAX hooks
        add_action('wp_ajax_qcc_calculate', array($this, 'handle_ajax_calculation'));
        add_action('wp_ajax_nopriv_qcc_calculate', array($this, 'handle_ajax_calculation'));
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        // Main shortcode
        add_shortcode('quality_cost_calculator', array($this, 'render_shortcode'));
        
        // Alternative shortcodes
        add_shortcode('qcc_calculator', array($this, 'render_shortcode'));
        add_shortcode('cost_quality_calculator', array($this, 'render_shortcode'));
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Shortcodes erfolgreich registriert');
        }
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts = array(), $content = '') {
        try {
            $renderer = $this->container ? $this->container->get('renderer') : null;
            
            if ($renderer) {
                return $renderer->render($atts, $content);
            }
            
            // Fallback rendering
            return $this->render_basic_calculator($atts);
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Shortcode Error: ' . $e->getMessage());
            }
            return $this->render_error_message($e->getMessage());
        }
    }
    
    /**
     * Handle AJAX calculation
     */
    public function handle_ajax_calculation() {
        try {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'qcc_nonce')) {
                wp_die('Security check failed');
            }
            
            // Get calculator service
            $calculator = $this->container ? $this->container->get('calculator') : null;
            
            if (!$calculator) {
                $calculator = $this->create_calculator_service();
            }
            
            // Prepare input data
            $input_data = array(
                'revenue' => floatval($_POST['revenue']),
                'quality_basis' => floatval($_POST['quality_basis']),
                'prevention' => floatval($_POST['prevention']),
                'appraisal' => floatval($_POST['appraisal']),
                'internal_defect' => floatval($_POST['internal_defect']),
                'external_defect' => floatval($_POST['external_defect'])
            );
            
            // Calculate results
            $results = $calculator->calculate($input_data);
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        // CSS
        $css_file = QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css';
        if (file_exists(QCC_PLUGIN_PATH . 'assets/quality-cost-calculator.css')) {
            wp_enqueue_style('qcc-frontend', $css_file, array(), QCC_PLUGIN_VERSION);
        }
        
        // JavaScript
        $js_file = QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js';
        if (file_exists(QCC_PLUGIN_PATH . 'assets/quality-cost-calculator.js')) {
            wp_enqueue_script('qcc-frontend', $js_file, array('jquery'), QCC_PLUGIN_VERSION, true);
            
            // Localize script
            wp_localize_script('qcc-frontend', 'qcc_config', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('qcc_nonce'),
                'strings' => array(
                    'calculating' => __('Calculating...', QCC_TEXT_DOMAIN),
                    'error' => __('Calculation failed', QCC_TEXT_DOMAIN)
                )
            ));
        }
    }
    
    /**
     * Create calculator service
     */
    private function create_calculator_service() {
        // Use existing calculator if available
        if (class_exists('QCC_Calculation_Engine')) {
            return new QCC_Calculation_Engine();
        }
        
        // Create basic calculator
        return new class {
            public function calculate($data) {
                $revenue = $data['revenue'];
                $quality_basis = $data['quality_basis'] / 100;
                
                $total_quality_costs = $revenue * $quality_basis;
                
                $prevention_cost = $total_quality_costs * ($data['prevention'] / 100);
                $appraisal_cost = $total_quality_costs * ($data['appraisal'] / 100);
                $internal_cost = $total_quality_costs * ($data['internal_defect'] / 100);
                $external_cost = $total_quality_costs * ($data['external_defect'] / 100);
                
                return array(
                    'total_quality_costs' => round($total_quality_costs, 2),
                    'cogq' => round($prevention_cost + $appraisal_cost, 2),
                    'copq' => round($internal_cost + $external_cost, 2),
                    'prevention_cost' => round($prevention_cost, 2),
                    'appraisal_cost' => round($appraisal_cost, 2),
                    'internal_defect_cost' => round($internal_cost, 2),
                    'external_defect_cost' => round($external_cost, 2)
                );
            }
        };
    }
    
    /**
     * Create validator service
     */
    private function create_validator_service() {
        return new class {
            private $errors = array();
            
            public function validate($data) {
                $this->errors = array();
                
                if (!is_numeric($data['revenue']) || $data['revenue'] < 0) {
                    $this->errors[] = 'Revenue must be a positive number';
                }
                
                $percentages = array('quality_basis', 'prevention', 'appraisal', 'internal_defect', 'external_defect');
                foreach ($percentages as $field) {
                    if (!is_numeric($data[$field]) || $data[$field] < 0 || $data[$field] > 100) {
                        $this->errors[] = $field . ' must be between 0 and 100';
                    }
                }
                
                return empty($this->errors);
            }
            
            public function get_errors() {
                return $this->errors;
            }
        };
    }
    
    /**
     * Create renderer service
     */
    private function create_renderer_service() {
        return new class {
            public function render($atts, $content = '') {
                // Try to use existing template
                $template_file = QCC_PLUGIN_PATH . 'templates/calculator.php';
                if (file_exists($template_file)) {
                    ob_start();
                    $attributes = $atts; // Make available to template
                    include $template_file;
                    return ob_get_clean();
                }
                
                // Fallback to basic HTML
                return $this->render_basic_html($atts);
            }
            
            private function render_basic_html($atts) {
                $defaults = array(
                    'currency' => '€',
                    'default_revenue' => 1000000,
                    'default_quality_basis' => 3
                );
                $atts = wp_parse_args($atts, $defaults);
                
                ob_start();
                ?>
                <div class="qcc-calculator" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 600px; margin: 20px 0; background: #fff;">
                    <h3 style="margin-top: 0; color: #333;"><?php _e('Quality Cost Calculator', QCC_TEXT_DOMAIN); ?></h3>
                    
                    <form id="qcc-form" class="qcc-form">
                        <?php wp_nonce_field('qcc_nonce', 'qcc_nonce'); ?>
                        
                        <div class="qcc-input-group" style="margin-bottom: 15px;">
                            <label for="qcc_revenue" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('Annual Revenue', QCC_TEXT_DOMAIN); ?> (<?php echo esc_html($atts['currency']); ?>):</label>
                            <input type="number" id="qcc_revenue" name="revenue" value="<?php echo esc_attr($atts['default_revenue']); ?>" min="0" step="1000" required style="width: 100%; max-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="qcc-input-group" style="margin-bottom: 15px;">
                            <label for="qcc_quality_basis" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('Quality Cost Basis', QCC_TEXT_DOMAIN); ?> (% <?php _e('of Revenue', QCC_TEXT_DOMAIN); ?>):</label>
                            <input type="number" id="qcc_quality_basis" name="quality_basis" value="<?php echo esc_attr($atts['default_quality_basis']); ?>" min="0" max="100" step="0.1" required style="width: 100%; max-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="qcc-input-group" style="margin-bottom: 15px;">
                            <label for="qcc_prevention" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('Prevention', QCC_TEXT_DOMAIN); ?> (%):</label>
                            <input type="number" id="qcc_prevention" name="prevention" value="40" min="0" max="100" step="0.1" required style="width: 100%; max-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="qcc-input-group" style="margin-bottom: 15px;">
                            <label for="qcc_appraisal" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('Appraisal', QCC_TEXT_DOMAIN); ?> (%):</label>
                            <input type="number" id="qcc_appraisal" name="appraisal" value="30" min="0" max="100" step="0.1" required style="width: 100%; max-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="qcc-input-group" style="margin-bottom: 15px;">
                            <label for="qcc_internal" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('Internal Defects', QCC_TEXT_DOMAIN); ?> (%):</label>
                            <input type="number" id="qcc_internal" name="internal_defect" value="20" min="0" max="100" step="0.1" required style="width: 100%; max-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="qcc-input-group" style="margin-bottom: 20px;">
                            <label for="qcc_external" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('External Defects', QCC_TEXT_DOMAIN); ?> (%):</label>
                            <input type="number" id="qcc_external" name="external_defect" value="10" min="0" max="100" step="0.1" required style="width: 100%; max-width: 200px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                        
                        <div class="qcc-submit-group">
                            <button type="submit" class="qcc-submit-btn" style="background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;"><?php _e('Calculate', QCC_TEXT_DOMAIN); ?></button>
                        </div>
                    </form>
                    
                    <div id="qcc-results" class="qcc-results" style="display: none; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #0073aa;">
                        <h4 style="margin-top: 0; color: #0073aa;"><?php _e('Results', QCC_TEXT_DOMAIN); ?>:</h4>
                        <div class="qcc-results-content"></div>
                    </div>
                </div>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#qcc-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        var formData = {
                            action: 'qcc_calculate',
                            nonce: $('#qcc_nonce').val(),
                            revenue: $('#qcc_revenue').val(),
                            quality_basis: $('#qcc_quality_basis').val(),
                            prevention: $('#qcc_prevention').val(),
                            appraisal: $('#qcc_appraisal').val(),
                            internal_defect: $('#qcc_internal').val(),
                            external_defect: $('#qcc_external').val()
                        };
                        
                        $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                            if (response.success) {
                                var results = response.data;
                                var html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">' +
                                          '<div style="padding: 10px; background: white; border-radius: 4px;"><strong><?php _e("Total Quality Costs", QCC_TEXT_DOMAIN); ?>:</strong><br>' + 
                                          '<span style="font-size: 18px; color: #0073aa;">' + results.total_quality_costs.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                          '<div style="padding: 10px; background: white; border-radius: 4px;"><strong><?php _e("COGQ (Cost of Good Quality)", QCC_TEXT_DOMAIN); ?>:</strong><br>' + 
                                          '<span style="font-size: 18px; color: #28a745;">' + results.cogq.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                          '<div style="padding: 10px; background: white; border-radius: 4px;"><strong><?php _e("COPQ (Cost of Poor Quality)", QCC_TEXT_DOMAIN); ?>:</strong><br>' + 
                                          '<span style="font-size: 18px; color: #dc3545;">' + results.copq.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                          '<div style="padding: 10px; background: white; border-radius: 4px;"><strong><?php _e("Prevention Costs", QCC_TEXT_DOMAIN); ?>:</strong><br>' + 
                                          '<span style="font-size: 16px;">' + results.prevention_cost.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                          '</div>';
                                
                                $('#qcc-results .qcc-results-content').html(html);
                                $('#qcc-results').show();
                            } else {
                                alert('<?php _e("Calculation failed. Please check your input.", QCC_TEXT_DOMAIN); ?>');
                            }
                        });
                    });
                });
                </script>
                <?php
                return ob_get_clean();
            }
        };
    }
    
    /**
     * Should load frontend assets
     */
    private function should_load_frontend_assets() {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            return has_shortcode($post->post_content, 'quality_cost_calculator') ||
                   has_shortcode($post->post_content, 'qcc_calculator') ||
                   has_shortcode($post->post_content, 'cost_quality_calculator');
        }
        
        return false;
    }
    
    /**
     * Emergency fallback
     */
    private function emergency_fallback() {
        add_shortcode('quality_cost_calculator', function($atts) {
            return '<div class="qcc-emergency" style="padding: 20px; border: 1px solid #ff6b6b; background: #ffe6e6; color: #d63031; border-radius: 5px;">
                <strong>Quality Cost Calculator:</strong> Bootstrap initialization failed.
                <br><small>Running in emergency mode. Some features may be limited.</small>
            </div>';
        });
        
        return false;
    }
    
    /**
     * Render error message
     */
    private function render_error_message($message) {
        return '<div class="qcc-error" style="padding: 15px; border: 1px solid #ff6b6b; background: #ffe6e6; color: #d63031; border-radius: 4px;">
            <strong>Error:</strong> ' . esc_html($message) . '
        </div>';
    }
    
    /**
     * Render basic calculator (used as fallback)
     */
    private function render_basic_calculator($atts) {
        $renderer = $this->create_renderer_service();
        return $renderer->render($atts);
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options
        add_option('qcc_version', QCC_PLUGIN_VERSION);
        add_option('qcc_activated', true);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin activated with new architecture');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clean up temporary data
        delete_transient('qcc_system_check');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin deactivated');
        }
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove all options
        delete_option('qcc_version');
        delete_option('qcc_activated');
        delete_option('qcc_settings');
        
        // Remove cache
        wp_cache_delete('qcc_config');
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin uninstalled');
        }
    }
    
    /**
     * Get service container
     */
    public function get_container() {
        return $this->container;
    }
    
    /**
     * Check if initialized
     */
    public function is_initialized() {
        return $this->initialized;
    }
}
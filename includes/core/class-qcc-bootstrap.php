<?php
/**
 * QCC Bootstrap - Super-sichere Version ohne Syntax-Fehler
 * 
 * ERSETZEN: wp-content/plugins/quality-cost-calculator/includes/core/class-qcc-bootstrap.php
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_Bootstrap 
{
    private static $instance = null;
    private $container = null;
    private $initialized = false;
    
    public static function get_instance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function initialize() 
    {
        $instance = self::get_instance();
        return $instance->init();
    }
    
    private function init() 
    {
        if ($this->initialized) {
            return true;
        }
        
        try {
            $this->init_service_container();
            $this->setup_hooks();
            $this->register_shortcodes();
            
            $this->initialized = true;
            
            if (QCC_DEBUG) {
                error_log('QCC Bootstrap: Initialisierung erfolgreich');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('QCC Bootstrap Error: ' . $e->getMessage());
            return $this->emergency_fallback();
        }
    }
    
    private function init_service_container() 
    {
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
    
    private function register_core_services() 
    {
        $this->container->register('calculator', array($this, 'create_calculator_service'));
        $this->container->register('validator', array($this, 'create_validator_service'));
        $this->container->register('renderer', array($this, 'create_renderer_service'));
    }
    
    private function setup_hooks() 
    {
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        }
        
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_menu', array($this, 'register_admin_menu'));
        }
        
        add_action('wp_ajax_qcc_calculate', array($this, 'handle_ajax_calculation'));
        add_action('wp_ajax_nopriv_qcc_calculate', array($this, 'handle_ajax_calculation'));
    }
    
    private function register_shortcodes() 
    {
        add_shortcode('quality_cost_calculator', array($this, 'render_shortcode'));
        add_shortcode('qcc_calculator', array($this, 'render_shortcode'));
        add_shortcode('cost_quality_calculator', array($this, 'render_shortcode'));
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Shortcodes registriert');
        }
    }
    
    public function render_shortcode($atts = array(), $content = '') 
    {
        try {
            $renderer = $this->container ? $this->container->get('renderer') : null;
            
            if ($renderer) {
                return $renderer->render($atts, $content);
            }
            
            return $this->render_basic_calculator($atts);
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Shortcode Error: ' . $e->getMessage());
            }
            return $this->render_error_message($e->getMessage());
        }
    }
    
    public function handle_ajax_calculation() 
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'qcc_nonce')) {
                wp_die('Security check failed');
            }
            
            $calculator = $this->container ? $this->container->get('calculator') : null;
            
            if (!$calculator) {
                $calculator = $this->create_calculator_service();
            }
            
            $input_data = $this->prepare_input_data($_POST);
            $results = $calculator->calculate($input_data);
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    private function prepare_input_data($post_data) 
    {
        $data = array();
        
        $data['revenue'] = $this->get_post_value($post_data, 'revenue', 0);
        $data['quality_basis'] = $this->get_post_value($post_data, 'quality_basis', 0);
        $data['prevention'] = $this->get_post_value($post_data, 'prevention', 0);
        $data['appraisal'] = $this->get_post_value($post_data, 'appraisal', 0);
        $data['internal_defect'] = $this->get_post_value($post_data, 'internal_defect', 0);
        $data['external_defect'] = $this->get_post_value($post_data, 'external_defect', 0);
        
        return $data;
    }
    
    private function get_post_value($post_data, $key, $default = 0) 
    {
        if (isset($post_data[$key])) {
            return floatval($post_data[$key]);
        }
        return $default;
    }
    
    public function enqueue_frontend_assets() 
    {
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        $css_file = QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css';
        if (file_exists(QCC_PLUGIN_PATH . 'assets/quality-cost-calculator.css')) {
            wp_enqueue_style('qcc-frontend', $css_file, array(), QCC_PLUGIN_VERSION);
        }
        
        $js_file = QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js';
        if (file_exists(QCC_PLUGIN_PATH . 'assets/quality-cost-calculator.js')) {
            wp_enqueue_script('qcc-frontend', $js_file, array('jquery'), QCC_PLUGIN_VERSION, true);
            
            $config = array();
            $config['ajax_url'] = admin_url('admin-ajax.php');
            $config['nonce'] = wp_create_nonce('qcc_nonce');
            
            $config['strings'] = array();
            $config['strings']['calculating'] = __('Calculating...', QCC_TEXT_DOMAIN);
            $config['strings']['error'] = __('Calculation failed', QCC_TEXT_DOMAIN);
            
            $config['default_values'] = array();
            $config['default_values']['revenue'] = 140;
            $config['default_values']['quality_percentage'] = 6;
            $config['default_values']['prevention'] = 10;
            $config['default_values']['appraisal'] = 20;
            $config['default_values']['internal_defect'] = 30;
            $config['default_values']['external_defect'] = 40;
            $config['default_values']['lost_sales'] = 5;
            $config['default_values']['customer_churn'] = 2;
            $config['default_values']['market_share_loss'] = 1;
            $config['default_values']['productivity_loss'] = 3;
            
            $config['preserve_values'] = true;
            $config['auto_calculate'] = false;
            $config['live_validation'] = false;
            $config['debug'] = QCC_DEBUG;
            
            wp_localize_script('qcc-frontend', 'qcc_config', $config);
        } else {
            wp_add_inline_script('jquery', $this->get_fallback_js());
        }
    }
    
    public function enqueue_admin_assets($hook) 
    {
        if (strpos($hook, 'quality-cost-calculator') === false) {
            return;
        }
        
        $admin_css = QCC_PLUGIN_URL . 'assets/admin-style.css';
        if (file_exists(QCC_PLUGIN_PATH . 'assets/admin-style.css')) {
            wp_enqueue_style('qcc-admin', $admin_css, array(), QCC_PLUGIN_VERSION);
        }
    }
    
    public function register_admin_menu() 
    {
        add_menu_page(
            __('Quality Cost Calculator', QCC_TEXT_DOMAIN),
            __('QCC Settings', QCC_TEXT_DOMAIN),
            'manage_options',
            'quality-cost-calculator',
            array($this, 'render_admin_page'),
            'dashicons-chart-line',
            80
        );
    }
    
    public function render_admin_page() 
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', QCC_TEXT_DOMAIN));
        }
        
        if (isset($_POST['submit']) && check_admin_referer('qcc_settings_nonce')) {
            $this->save_admin_settings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', QCC_TEXT_DOMAIN) . '</p></div>';
        }
        
        $settings = get_option('qcc_settings', $this->get_default_settings());
        
        ?>
        <div class="wrap">
            <h1><?php _e('Quality Cost Calculator Settings', QCC_TEXT_DOMAIN); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('qcc_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_currency"><?php _e('Default Currency', QCC_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <input type="text" id="default_currency" name="default_currency" 
                                   value="<?php echo esc_attr($settings['default_currency']); ?>" 
                                   class="small-text" />
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_revenue"><?php _e('Default Revenue', QCC_TEXT_DOMAIN); ?></label>
                        </th>
                        <td>
                            <input type="number" id="default_revenue" name="default_revenue" 
                                   value="<?php echo esc_attr($settings['default_revenue']); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Plugin Status', QCC_TEXT_DOMAIN); ?></h2>
            <div class="qcc-status-info">
                <?php $this->render_plugin_status(); ?>
            </div>
        </div>
        <?php
    }
    
    private function get_default_settings() 
    {
        $defaults = array();
        $defaults['default_currency'] = '€';
        $defaults['default_revenue'] = 1000000;
        $defaults['default_quality_basis'] = 3;
        $defaults['enable_charts'] = true;
        $defaults['enable_export'] = true;
        return $defaults;
    }
    
    private function save_admin_settings() 
    {
        $settings = array();
        $settings['default_currency'] = sanitize_text_field($_POST['default_currency']);
        $settings['default_revenue'] = intval($_POST['default_revenue']);
        $settings['default_quality_basis'] = floatval($_POST['default_quality_basis']);
        $settings['enable_charts'] = isset($_POST['enable_charts']);
        $settings['enable_export'] = isset($_POST['enable_export']);
        
        update_option('qcc_settings', $settings);
    }
    
    private function render_plugin_status() 
    {
        $bootstrap_status = $this->is_initialized() ? '✅ Active' : '❌ Failed';
        $container_status = $this->container ? '✅ Loaded' : '❌ Missing';
        $services_count = $this->container ? count($this->container->get_service_names()) : 0;
        
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Component</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';
        echo '<tr><td>Bootstrap System</td><td>' . $bootstrap_status . '</td><td>Main plugin initialization</td></tr>';
        echo '<tr><td>Service Container</td><td>' . $container_status . '</td><td>' . $services_count . ' services registered</td></tr>';
        echo '<tr><td>Plugin Version</td><td>✅ ' . QCC_PLUGIN_VERSION . '</td><td>Current version</td></tr>';
        echo '</tbody>';
        echo '</table>';
    }
    
    public function create_calculator_service() 
    {
        if (class_exists('QCC_Calculation_Engine')) {
            return new QCC_Calculation_Engine();
        }
        
        return new QCC_Basic_Calculator();
    }
    
    public function create_validator_service() 
    {
        return new QCC_Basic_Validator();
    }
    
    public function create_renderer_service() 
    {
        return new QCC_Template_Renderer();
    }
    
    private function should_load_frontend_assets() 
    {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            return has_shortcode($post->post_content, 'quality_cost_calculator') ||
                   has_shortcode($post->post_content, 'qcc_calculator') ||
                   has_shortcode($post->post_content, 'cost_quality_calculator');
        }
        
        return false;
    }
    
    private function emergency_fallback() 
    {
        add_shortcode('quality_cost_calculator', array($this, 'render_emergency_shortcode'));
        add_shortcode('qcc_calculator', array($this, 'render_emergency_shortcode'));
        add_shortcode('cost_quality_calculator', array($this, 'render_emergency_shortcode'));
        
        return false;
    }
    
    public function render_emergency_shortcode($atts) 
    {
        return '<div class="qcc-emergency" style="padding: 20px; border: 1px solid #ff6b6b; background: #ffe6e6; color: #d63031; border-radius: 5px;">
            <strong>Quality Cost Calculator:</strong> Bootstrap initialization failed.
            <br><small>Running in emergency mode.</small>
        </div>';
    }
    
    private function render_error_message($message) 
    {
        return '<div class="qcc-error" style="padding: 15px; border: 1px solid #ff6b6b; background: #ffe6e6; color: #d63031; border-radius: 4px;">
            <strong>Error:</strong> ' . esc_html($message) . '
        </div>';
    }
    
    private function render_basic_calculator($atts) 
    {
        $renderer = $this->create_renderer_service();
        return $renderer->render($atts);
    }
    
    private function get_fallback_js() 
    {
        return "
        // QCC STRONG PROTECTION JavaScript - Überschreibt alle anderen Scripts
        (function() {
            console.log('QCC: STRONG PROTECTION aktiv - Override-Modus');
            
            // Standard-Werte FEST definiert
            var QCC_PROTECTED_DEFAULTS = {
                'qcc-revenue': 140,
                'revenue': 140,
                'qcc-quality-percentage': 6,
                'quality_percentage': 6,
                'quality-percentage': 6,
                'qcc-prevention': 10,
                'prevention': 10,
                'prevention-costs': 10,
                'qcc-appraisal': 20,
                'appraisal': 20,
                'appraisal-costs': 20,
                'qcc-internal-defect': 30,
                'internal_defect': 30,
                'internal-defect': 30,
                'internal-defect-costs': 30,
                'qcc-external-defect': 40,
                'external_defect': 40,
                'external-defect': 40,
                'external-defect-costs': 40,
                'qcc-lost-sales': 5,
                'lost_sales': 5,
                'lost-sales': 5,
                'qcc-customer-churn': 2,
                'customer_churn': 2,
                'customer-churn': 2,
                'qcc-market-share-loss': 1,
                'market_share_loss': 1,
                'market-share-loss': 1,
                'qcc-productivity-loss': 3,
                'productivity_loss': 3,
                'productivity-loss': 3
            };
            
            // SCHUTZ-FUNKTIONEN
            function forceSetDefaultValues() {
                for (var id in QCC_PROTECTED_DEFAULTS) {
                    var element = document.getElementById(id);
                    if (element && element.type === 'number') {
                        var newValue = QCC_PROTECTED_DEFAULTS[id];
                        if (element.value !== newValue.toString()) {
                            element.value = newValue;
                            console.log('QCC: FORCED default value for ' + id + ' = ' + newValue);
                        }
                    }
                }
                
                // Auch per name-Attribut suchen
                for (var name in QCC_PROTECTED_DEFAULTS) {
                    var elements = document.querySelectorAll('input[name=\"' + name + '\"]');
                    elements.forEach(function(element) {
                        if (element.type === 'number') {
                            var newValue = QCC_PROTECTED_DEFAULTS[name];
                            if (element.value !== newValue.toString()) {
                                element.value = newValue;
                                console.log('QCC: FORCED default value by name ' + name + ' = ' + newValue);
                            }
                        }
                    });
                }
            }
            
            // OVERRIDE alle Form-Reset-Funktionen
            function protectAgainstResets() {
                // Override native form reset
                var forms = document.querySelectorAll('form');
                forms.forEach(function(form) {
                    form.addEventListener('reset', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log('QCC: BLOCKED form reset event');
                        setTimeout(forceSetDefaultValues, 10);
                        return false;
                    }, true);
                });
                
                // Override QCC reset functions
                if (window.QCC && window.QCC.reset) {
                    var originalReset = window.QCC.reset;
                    window.QCC.reset = function() {
                        console.log('QCC: BLOCKED QCC.reset() call');
                        forceSetDefaultValues();
                        return false;
                    };
                }
                
                // Override resetDefaults function
                if (window.resetDefaults) {
                    var originalResetDefaults = window.resetDefaults;
                    window.resetDefaults = function() {
                        console.log('QCC: BLOCKED resetDefaults() call');
                        forceSetDefaultValues();
                        return false;
                    };
                }
                
                // Override any other reset functions
                ['resetForm', 'clearForm', 'clearValues', 'resetCalculator'].forEach(function(funcName) {
                    if (window[funcName]) {
                        window[funcName] = function() {
                            console.log('QCC: BLOCKED ' + funcName + '() call');
                            forceSetDefaultValues();
                            return false;
                        };
                    }
                });
            }
            
            // MUTATION OBSERVER - Überwacht DOM-Änderungen
            function setupMutationObserver() {
                if (typeof MutationObserver !== 'undefined') {
                    var observer = new MutationObserver(function(mutations) {
                        var needsCheck = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                needsCheck = true;
                            }
                            if (mutation.type === 'childList') {
                                needsCheck = true;
                            }
                        });
                        if (needsCheck) {
                            setTimeout(forceSetDefaultValues, 50);
                        }
                    });
                    
                    observer.observe(document.body, {
                        attributes: true,
                        childList: true,
                        subtree: true,
                        attributeFilter: ['value']
                    });
                    
                    console.log('QCC: MutationObserver aktiv');
                }
            }
            
            // INPUT EVENT PROTECTION
            function protectInputEvents() {
                document.addEventListener('input', function(e) {
                    if (e.target.type === 'number') {
                        var id = e.target.id || e.target.name;
                        if (QCC_PROTECTED_DEFAULTS[id] && (!e.target.value || e.target.value === '')) {
                            setTimeout(function() {
                                e.target.value = QCC_PROTECTED_DEFAULTS[id];
                                console.log('QCC: RESTORED empty field ' + id);
                            }, 10);
                        }
                    }
                }, true);
            }
            
            // AGGRESSIVE TIMING PROTECTION
            function setupAggressiveProtection() {
                // Sofort setzen
                forceSetDefaultValues();
                
                // Multiple Timeouts
                setTimeout(forceSetDefaultValues, 50);
                setTimeout(forceSetDefaultValues, 100);
                setTimeout(forceSetDefaultValues, 200);
                setTimeout(forceSetDefaultValues, 500);
                setTimeout(forceSetDefaultValues, 1000);
                setTimeout(forceSetDefaultValues, 2000);
                setTimeout(forceSetDefaultValues, 5000);
                
                // Interval für kontinuierlichen Schutz
                setInterval(function() {
                    forceSetDefaultValues();
                }, 3000);
                
                console.log('QCC: Aggressive protection timers aktiv');
            }
            
            // MAIN INITIALIZATION
            function initStrongProtection() {
                console.log('QCC: Strong Protection wird initialisiert...');
                
                forceSetDefaultValues();
                protectAgainstResets();
                protectInputEvents();
                setupMutationObserver();
                setupAggressiveProtection();
                
                console.log('QCC: Strong Protection vollständig aktiv!');
            }
            
            // IMMEDIATE EXECUTION
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initStrongProtection);
            } else {
                initStrongProtection();
            }
            
            // JQUERY READY BACKUP
            if (window.jQuery) {
                jQuery(document).ready(function() {
                    setTimeout(initStrongProtection, 100);
                });
            }
            
        })();
        ";
    }
    
    public static function activate() 
    {
        add_option('qcc_version', QCC_PLUGIN_VERSION);
        add_option('qcc_activated', true);
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin activated');
        }
    }
    
    public static function deactivate() 
    {
        delete_transient('qcc_system_check');
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin deactivated');
        }
    }
    
    public static function uninstall() 
    {
        delete_option('qcc_version');
        delete_option('qcc_activated');
        delete_option('qcc_settings');
        wp_cache_delete('qcc_config');
        
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin uninstalled');
        }
    }
    
    public function get_container() 
    {
        return $this->container;
    }
    
    public function is_initialized() 
    {
        return $this->initialized;
    }
}

class QCC_Basic_Calculator 
{
    public function calculate($data) 
    {
        $revenue = $data['revenue'];
        $quality_basis = $data['quality_basis'] / 100;
        
        $total_quality_costs = $revenue * $quality_basis;
        
        $prevention_cost = $total_quality_costs * ($data['prevention'] / 100);
        $appraisal_cost = $total_quality_costs * ($data['appraisal'] / 100);
        $internal_cost = $total_quality_costs * ($data['internal_defect'] / 100);
        $external_cost = $total_quality_costs * ($data['external_defect'] / 100);
        
        $results = array();
        $results['total_quality_costs'] = round($total_quality_costs, 2);
        $results['cogq'] = round($prevention_cost + $appraisal_cost, 2);
        $results['copq'] = round($internal_cost + $external_cost, 2);
        $results['prevention_cost'] = round($prevention_cost, 2);
        $results['appraisal_cost'] = round($appraisal_cost, 2);
        $results['internal_defect_cost'] = round($internal_cost, 2);
        $results['external_defect_cost'] = round($external_cost, 2);
        
        return $results;
    }
}

class QCC_Basic_Validator 
{
    private $errors = array();
    
    public function validate($data) 
    {
        $this->errors = array();
        
        if (!is_numeric($data['revenue']) || $data['revenue'] < 0) {
            $this->errors[] = 'Revenue must be a positive number';
        }
        
        $fields = array('quality_basis', 'prevention', 'appraisal', 'internal_defect', 'external_defect');
        foreach ($fields as $field) {
            if (!is_numeric($data[$field]) || $data[$field] < 0 || $data[$field] > 100) {
                $this->errors[] = $field . ' must be between 0 and 100';
            }
        }
        
        return empty($this->errors);
    }
    
    public function get_errors() 
    {
        return $this->errors;
    }
}

class QCC_Template_Renderer 
{
    public function render($atts, $content = '') 
    {
        $template_file = QCC_PLUGIN_PATH . 'templates/calculator.php';
        if (file_exists($template_file)) {
            return $this->render_with_template($template_file, $atts);
        }
        
        return $this->render_basic_html($atts);
    }
    
    private function render_with_template($template_file, $atts) 
    {
        ob_start();
        
        $defaults = array();
        $defaults['currency'] = '€';
        $defaults['default_revenue'] = 140;
        $defaults['default_quality_basis'] = 6;
        $attributes = wp_parse_args($atts, $defaults);
        
        $data = array();
        $data['id'] = 'qcc-calculator-' . uniqid();
        $data['currency'] = $attributes['currency'];
        $data['unit'] = 'billions';
        
        $data['default_values'] = array();
        $data['default_values']['revenue'] = floatval($attributes['default_revenue']);
        $data['default_values']['quality_percentage'] = floatval($attributes['default_quality_basis']);
        $data['default_values']['prevention'] = 10;
        $data['default_values']['appraisal'] = 20;
        $data['default_values']['internal_defect'] = 30;
        $data['default_values']['external_defect'] = 40;
        $data['default_values']['lost_sales'] = 5;
        $data['default_values']['customer_churn'] = 2;
        $data['default_values']['market_share_loss'] = 1;
        $data['default_values']['productivity_loss'] = 3;
        
        $data['sections'] = array();
        $data['sections']['basic'] = array(
            'title' => 'Basic Parameters',
            'description' => 'Revenue and Quality Cost',
            'fields' => array('revenue', 'quality_percentage'),
            'icon' => 'calculator',
            'collapsible' => false
        );
        $data['sections']['cogq'] = array(
            'title' => 'Cost of Good Quality',
            'description' => 'Prevention and Appraisal Costs',
            'fields' => array('prevention', 'appraisal'),
            'icon' => 'shield-check',
            'collapsible' => true,
            'color' => 'green'
        );
        $data['sections']['copq'] = array(
            'title' => 'Cost of Poor Quality',
            'description' => 'Internal and External Defects',
            'fields' => array('internal_defect', 'external_defect'),
            'icon' => 'alert-triangle',
            'collapsible' => true,
            'color' => 'red'
        );
        $data['sections']['opportunity'] = array(
            'title' => 'Opportunity Costs',
            'description' => 'Additional Business Impact',
            'fields' => array('lost_sales', 'customer_churn', 'market_share_loss', 'productivity_loss'),
            'icon' => 'trending-up',
            'collapsible' => true,
            'color' => 'blue',
            'optional' => true
        );
        
        $data['layout'] = 'vertical';
        $data['validation_mode'] = 'live';
        $data['auto_calculate'] = true;
        $data['show_reset'] = true;
        $data['show_save'] = false;
        $data['style'] = 'default';
        
        $helpers = array();
        $helpers['generate_section_id'] = array($this, 'generate_section_id');
        $helpers['get_currency_symbol'] = array($this, 'get_currency_symbol');
        $helpers['escape'] = 'esc_html';
        $helpers['generate_id'] = array($this, 'generate_id');
        $helpers['build_css_classes'] = array($this, 'build_css_classes');
        
        $translator = new QCC_Basic_Translator();
        
        if (QCC_DEBUG) {
            error_log('QCC Template Data: ' . print_r($data['default_values'], true));
        }
        
        include $template_file;
        return ob_get_clean();
    }
    
    public function generate_section_id($data) 
    {
        return 'qcc-section-' . uniqid();
    }
    
    public function get_currency_symbol($currency) 
    {
        if ($currency === 'EUR') return '€';
        if ($currency === 'USD') return '$';
        if ($currency === 'GBP') return '£';
        return '€';
    }
    
    public function generate_id($prefix = 'qcc') 
    {
        return $prefix . '-' . uniqid();
    }
    
    public function build_css_classes($base_classes, $conditional_classes = array()) 
    {
        $classes = is_array($base_classes) ? $base_classes : array($base_classes);
        
        foreach ($conditional_classes as $class => $condition) {
            if ($condition) {
                $classes[] = $class;
            }
        }
        
        return implode(' ', $classes);
    }
    
    private function render_basic_html($atts) 
    {
        $defaults = array();
        $defaults['currency'] = '€';
        $defaults['default_revenue'] = 1000000;
        $defaults['default_quality_basis'] = 3;
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
                    <label for="qcc_quality_basis" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e('Quality Cost Basis', QCC_TEXT_DOMAIN); ?> (%):</label>
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
            
            <div id="qcc-results" class="qcc-results" style="display: none; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 6px;">
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
                                  '<div style="padding: 10px; background: white; border-radius: 4px;"><strong>Total Quality Costs:</strong><br>' + 
                                  '<span style="font-size: 18px; color: #0073aa;">' + results.total_quality_costs.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                  '<div style="padding: 10px; background: white; border-radius: 4px;"><strong>COGQ:</strong><br>' + 
                                  '<span style="font-size: 18px; color: #28a745;">' + results.cogq.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                  '<div style="padding: 10px; background: white; border-radius: 4px;"><strong>COPQ:</strong><br>' + 
                                  '<span style="font-size: 18px; color: #dc3545;">' + results.copq.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                  '<div style="padding: 10px; background: white; border-radius: 4px;"><strong>Prevention:</strong><br>' + 
                                  '<span style="font-size: 16px;">' + results.prevention_cost.toLocaleString() + ' <?php echo esc_js($atts['currency']); ?></span></div>' +
                                  '</div>';
                        
                        $('#qcc-results .qcc-results-content').html(html);
                        $('#qcc-results').show();
                    } else {
                        alert('Calculation failed. Please check your input.');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

class QCC_Basic_Translator 
{
    public function get($key) 
    {
        $translations = array();
        $translations['basic_parameters'] = 'Basic Parameters';
        $translations['revenue_and_quality_cost'] = 'Revenue and Quality Cost';
        $translations['cost_of_good_quality'] = 'Cost of Good Quality';
        $translations['prevention_and_appraisal_costs'] = 'Prevention and Appraisal Costs';
        $translations['cost_of_poor_quality'] = 'Cost of Poor Quality';
        $translations['internal_and_external_defects'] = 'Internal and External Defects';
        $translations['revenue'] = 'Revenue';
        $translations['quality_cost_percentage'] = 'Quality Cost Basis';
        $translations['prevention_costs'] = 'Prevention Costs';
        $translations['appraisal_costs'] = 'Appraisal Costs';
        $translations['internal_defect_costs'] = 'Internal Defect Costs';
        $translations['external_defect_costs'] = 'External Defect Costs';
        $translations['calculate'] = 'Calculate';
        $translations['reset'] = 'Reset';
        
        if (isset($translations[$key])) {
            return $translations[$key];
        }
        return $key;
    }
}
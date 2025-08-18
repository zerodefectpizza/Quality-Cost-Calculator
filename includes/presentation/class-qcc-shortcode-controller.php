<?php
/**
 * QCC Shortcode Controller
 * Hauptsteuerung für den Quality Cost Calculator Shortcode
 * 
 * @package QualityCostCalculator
 * @subpackage Presentation
 * @since 2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QCC_Shortcode_Controller {
    
    /**
     * Shortcode name
     */
    const SHORTCODE_NAME = 'quality_cost_calculator';
    
    /**
     * Default shortcode attributes
     * @var array
     */
    private $default_attributes = array(
        'theme' => 'default',
        'language' => 'auto',
        'currency' => 'EUR',
        'unit' => 'millions',
        'layout' => 'two-column',
        'width' => 'max-width',
        'show_header' => 'true',
        'show_controls' => 'true',
        'show_chart' => 'true',
        'show_export' => 'true',
        'cache_duration' => '0',
        'debug' => 'false'
    );
    
    /**
     * Response builder
     * @var QCC_Response_Builder
     */
    private $response_builder;
    
    /**
     * Error handler
     * @var QCC_Error_Handler
     */
    private $error_handler;
    
    /**
     * Template router
     * @var QCC_Template_Router
     */
    private $template_router;
    
    /**
     * Calculation engine
     * @var QCC_Calculation_Engine
     */
    private $calculation_engine;
    
    /**
     * Validation engine
     * @var QCC_Validation_Engine
     */
    private $validation_engine;
    
    /**
     * Translation service
     * @var QCC_Translator
     */
    private $translator;
    
    /**
     * Cache of rendered shortcodes to prevent duplicate processing
     * @var array
     */
    private static $rendered_cache = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_dependencies();
        $this->register_shortcode();
        $this->register_ajax_handlers();
        $this->register_hooks();
    }
    
    /**
     * Initialize dependencies
     */
    private function init_dependencies() {
        $this->response_builder = new QCC_Response_Builder();
        $this->error_handler = new QCC_Error_Handler();
        $this->template_router = new QCC_Template_Router();
        $this->calculation_engine = new QCC_Calculation_Engine();
        $this->validation_engine = new QCC_Validation_Engine();
        $this->translator = new QCC_Translator();
    }
    
    /**
     * Register shortcode
     */
    private function register_shortcode() {
        add_shortcode(self::SHORTCODE_NAME, array($this, 'render_calculator'));
        
        // Register alternative shortcode names for backward compatibility
        add_shortcode('qcc_calculator', array($this, 'render_calculator'));
        add_shortcode('cost_of_quality', array($this, 'render_calculator'));
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // For logged-in and non-logged-in users
        add_action('wp_ajax_qcc_calculate', array($this, 'handle_ajax_calculation'));
        add_action('wp_ajax_nopriv_qcc_calculate', array($this, 'handle_ajax_calculation'));
        
        add_action('wp_ajax_qcc_export', array($this, 'handle_ajax_export'));
        add_action('wp_ajax_nopriv_qcc_export', array($this, 'handle_ajax_export'));
        
        add_action('wp_ajax_qcc_validate', array($this, 'handle_ajax_validation'));
        add_action('wp_ajax_nopriv_qcc_validate', array($this, 'handle_ajax_validation'));
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'add_schema_markup'));
        add_filter('the_content', array($this, 'enhance_content_with_calculator'));
    }
    
    /**
     * Main shortcode rendering function
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered calculator HTML
     */
    public function render_calculator($atts = array(), $content = '') {
        // Start performance timing
        $start_time = microtime(true);
        define('QCC_START_TIME', $start_time);
        
        try {
            // Parse and validate attributes
            $attributes = $this->parse_shortcode_attributes($atts);
            
            // Generate unique instance ID
            $instance_id = $this->generate_instance_id($attributes);
            
            // Check cache if enabled
            if ($attributes['cache_duration'] > 0) {
                $cached_output = $this->get_cached_output($instance_id);
                if ($cached_output !== false) {
                    return $cached_output;
                }
            }
            
            // Setup language and localization
            $this->setup_localization($attributes);
            
            // Prepare calculation data
            $calculation_data = $this->prepare_calculation_data($attributes);
            
            // Build response configuration
            $response_config = array(
                'type' => QCC_Response_Builder::TYPE_HTML,
                'template' => $this->determine_template($attributes),
                'cache_duration' => (int) $attributes['cache_duration']
            );
            
            // Build and render response
            $response = $this->response_builder->build_calculator_response($calculation_data, $response_config);
            
            // Enhance output with instance-specific data
            $enhanced_output = $this->enhance_output($response['content'], $attributes, $instance_id);
            
            // Cache output if enabled
            if ($attributes['cache_duration'] > 0) {
                $this->cache_output($instance_id, $enhanced_output, $attributes['cache_duration']);
            }
            
            // Log rendering for analytics
            $this->log_shortcode_render($attributes, microtime(true) - $start_time);
            
            return $enhanced_output;
            
        } catch (Exception $e) {
            $this->error_handler->handle_calculation_error($e);
            return $this->render_error_fallback($e, $attributes ?? array());
        }
    }
    
    /**
     * Handle AJAX calculation requests
     */
    public function handle_ajax_calculation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qcc_calculate_nonce')) {
            wp_die('Security check failed', 'QCC Error', array('response' => 403));
        }
        
        try {
            // Get and validate input data
            $input_data = $this->get_ajax_input_data();
            $validation_result = $this->validation_engine->validate_input($input_data);
            
            if (!$validation_result['valid']) {
                $response = array(
                    'success' => false,
                    'errors' => $validation_result['errors'],
                    'field_errors' => $validation_result['field_errors']
                );
            } else {
                // Perform calculations
                $calculation_result = $this->calculation_engine->calculate($input_data);
                
                $response = array(
                    'success' => true,
                    'data' => $calculation_result,
                    'formatted' => $this->format_calculation_results($calculation_result, $input_data),
                    'charts' => $this->prepare_chart_data($calculation_result)
                );
            }
            
            // Build JSON response
            $json_response = $this->response_builder->build_calculator_response(
                $response, 
                array('type' => QCC_Response_Builder::TYPE_JSON)
            );
            
            $this->response_builder->send_response($json_response);
            
        } catch (Exception $e) {
            $this->error_handler->handle_calculation_error($e);
            
            wp_send_json_error(array(
                'message' => 'Calculation failed',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ));
        }
    }
    
    /**
     * Handle AJAX export requests
     */
    public function handle_ajax_export() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qcc_export_nonce')) {
            wp_die('Security check failed', 'QCC Error', array('response' => 403));
        }
        
        try {
            $export_data = $this->get_ajax_export_data();
            $export_format = sanitize_text_field($_POST['format'] ?? 'csv');
            
            // Validate export format
            $allowed_formats = array('csv', 'json', 'xml', 'pdf');
            if (!in_array($export_format, $allowed_formats)) {
                wp_send_json_error(array('message' => 'Invalid export format'));
            }
            
            // Build export response
            $response_type = 'TYPE_' . strtoupper($export_format);
            $export_response = $this->response_builder->build_calculator_response(
                $export_data,
                array('type' => constant('QCC_Response_Builder::' . $response_type))
            );
            
            $this->response_builder->send_response($export_response);
            
        } catch (Exception $e) {
            $this->error_handler->handle_calculation_error($e);
            wp_send_json_error(array('message' => 'Export failed'));
        }
    }
    
    /**
     * Handle AJAX validation requests
     */
    public function handle_ajax_validation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'qcc_validate_nonce')) {
            wp_die('Security check failed', 'QCC Error', array('response' => 403));
        }
        
        try {
            $input_data = $this->get_ajax_input_data();
            $validation_result = $this->validation_engine->validate_input($input_data);
            
            wp_send_json_success(array(
                'valid' => $validation_result['valid'],
                'errors' => $validation_result['errors'],
                'field_errors' => $validation_result['field_errors'],
                'warnings' => $validation_result['warnings'] ?? array()
            ));
            
        } catch (Exception $e) {
            $this->error_handler->handle_calculation_error($e);
            wp_send_json_error(array('message' => 'Validation failed'));
        }
    }
    
    /**
     * Parse shortcode attributes
     * 
     * @param array $atts Raw attributes
     * @return array Parsed and validated attributes
     */
    private function parse_shortcode_attributes($atts) {
        // Merge with defaults
        $attributes = shortcode_atts($this->default_attributes, $atts);
        
        // Convert string booleans to actual booleans
        $boolean_fields = array('show_header', 'show_controls', 'show_chart', 'show_export', 'debug');
        foreach ($boolean_fields as $field) {
            $attributes[$field] = filter_var($attributes[$field], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Convert numeric fields
        $attributes['cache_duration'] = (int) $attributes['cache_duration'];
        
        // Validate theme
        $valid_themes = array('default', 'dark', 'light', 'modern', 'minimal');
        if (!in_array($attributes['theme'], $valid_themes)) {
            $attributes['theme'] = 'default';
        }
        
        // Validate language
        if ($attributes['language'] === 'auto') {
            $attributes['language'] = $this->detect_language();
        }
        
        // Validate currency
        $valid_currencies = array('EUR', 'USD', 'CNY');
        if (!in_array($attributes['currency'], $valid_currencies)) {
            $attributes['currency'] = 'EUR';
        }
        
        // Validate unit
        $valid_units = array('millions', 'billions');
        if (!in_array($attributes['unit'], $valid_units)) {
            $attributes['unit'] = 'millions';
        }
        
        // Validate layout
        $valid_layouts = array('single-column', 'two-column', 'grid');
        if (!in_array($attributes['layout'], $valid_layouts)) {
            $attributes['layout'] = 'two-column';
        }
        
        return $attributes;
    }
    
    /**
     * Generate unique instance ID
     * 
     * @param array $attributes Shortcode attributes
     * @return string Unique instance ID
     */
    private function generate_instance_id($attributes) {
        $unique_data = array(
            'attributes' => $attributes,
            'page_id' => get_the_ID(),
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('timestamp')
        );
        
        return 'qcc_' . substr(md5(serialize($unique_data)), 0, 8);
    }
    
    /**
     * Setup localization based on attributes
     * 
     * @param array $attributes Shortcode attributes
     */
    private function setup_localization($attributes) {
        $this->translator->set_language($attributes['language']);
        
        // Set currency and unit formatting
        $this->translator->set_currency($attributes['currency']);
        $this->translator->set_unit($attributes['unit']);
    }
    
    /**
     * Prepare calculation data
     * 
     * @param array $attributes Shortcode attributes
     * @return array Calculation data
     */
    private function prepare_calculation_data($attributes) {
        $default_values = $this->get_default_calculation_values();
        
        // Check if we have input data (from form submission or AJAX)
        $input_data = $this->get_input_data_from_request();
        
        if (!empty($input_data)) {
            // Validate input data
            $validation_result = $this->validation_engine->validate_input($input_data);
            
            if ($validation_result['valid']) {
                // Perform calculations
                $calculations = $this->calculation_engine->calculate($input_data);
                
                return array(
                    'inputs' => $input_data,
                    'calculations' => $calculations,
                    'cogq' => $calculations['cogq'] ?? array(),
                    'copq' => $calculations['copq'] ?? array(),
                    'totals' => $calculations['totals'] ?? array(),
                    'charts' => $this->prepare_chart_data($calculations),
                    'currency' => $attributes['currency'],
                    'unit' => $attributes['unit'],
                    'validation' => $validation_result
                );
            } else {
                // Return input data with validation errors
                return array(
                    'inputs' => $input_data,
                    'calculations' => array(),
                    'currency' => $attributes['currency'],
                    'unit' => $attributes['unit'],
                    'validation' => $validation_result
                );
            }
        }
        
        // Return default values for initial display
        return array(
            'inputs' => $default_values,
            'calculations' => array(),
            'currency' => $attributes['currency'],
            'unit' => $attributes['unit'],
            'validation' => array('valid' => true, 'errors' => array())
        );
    }
    
    /**
     * Determine template based on attributes
     * 
     * @param array $attributes Shortcode attributes
     * @return string Template name
     */
    private function determine_template($attributes) {
        // Check for errors that require emergency template
        if ($this->error_handler->has_errors('critical')) {
            return 'emergency-fallback';
        }
        
        // Return layout-specific template
        switch ($attributes['layout']) {
            case 'single-column':
                return 'single-column';
            case 'grid':
                return 'responsive-grid';
            case 'two-column':
            default:
                return 'main-container';
        }
    }
    
    /**
     * Enhance output with instance-specific data
     * 
     * @param string $output Generated HTML output
     * @param array $attributes Shortcode attributes
     * @param string $instance_id Instance ID
     * @return string Enhanced output
     */
    private function enhance_output($output, $attributes, $instance_id) {
        // Add instance-specific container
        $container_attributes = array(
            'id' => $instance_id,
            'class' => 'qcc-calculator-instance',
            'data-instance' => $instance_id,
            'data-theme' => $attributes['theme'],
            'data-language' => $attributes['language'],
            'data-currency' => $attributes['currency'],
            'data-unit' => $attributes['unit']
        );
        
        $container_attrs = '';
        foreach ($container_attributes as $key => $value) {
            $container_attrs .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }
        
        // Wrap output in instance container
        $wrapped_output = '<div' . $container_attrs . '>' . $output . '</div>';
        
        // Add instance-specific JavaScript
        $wrapped_output .= $this->generate_instance_javascript($instance_id, $attributes);
        
        // Add instance-specific CSS if needed
        if ($attributes['theme'] !== 'default') {
            $wrapped_output .= $this->generate_instance_css($instance_id, $attributes);
        }
        
        return $wrapped_output;
    }
    
    /**
     * Generate instance-specific JavaScript
     * 
     * @param string $instance_id Instance ID
     * @param array $attributes Shortcode attributes
     * @return string JavaScript code
     */
    private function generate_instance_javascript($instance_id, $attributes) {
        $js_config = array(
            'instance_id' => $instance_id,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => array(
                'calculate' => wp_create_nonce('qcc_calculate_nonce'),
                'export' => wp_create_nonce('qcc_export_nonce'),
                'validate' => wp_create_nonce('qcc_validate_nonce')
            ),
            'language' => $attributes['language'],
            'currency' => $attributes['currency'],
            'unit' => $attributes['unit'],
            'debug' => $attributes['debug'],
            'translations' => $this->translator->get_js_translations()
        );
        
        return '<script type="text/javascript">
            window.QCC = window.QCC || {};
            window.QCC.instances = window.QCC.instances || {};
            window.QCC.instances["' . esc_js($instance_id) . '"] = ' . wp_json_encode($js_config) . ';
            
            // Initialize calculator instance when DOM is ready
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof window.QCC.initInstance === "function") {
                    window.QCC.initInstance("' . esc_js($instance_id) . '");
                }
            });
        </script>';
    }
    
    /**
     * Generate instance-specific CSS
     * 
     * @param string $instance_id Instance ID
     * @param array $attributes Shortcode attributes
     * @return string CSS code
     */
    private function generate_instance_css($instance_id, $attributes) {
        $theme_styles = $this->get_theme_styles($attributes['theme']);
        
        if (empty($theme_styles)) {
            return '';
        }
        
        // Scope styles to this instance
        $scoped_styles = '';
        foreach ($theme_styles as $selector => $styles) {
            $scoped_selector = '#' . $instance_id . ' ' . $selector;
            $scoped_styles .= $scoped_selector . ' { ' . $styles . ' } ';
        }
        
        return '<style type="text/css">' . $scoped_styles . '</style>';
    }
    
    /**
     * Get theme styles
     * 
     * @param string $theme Theme name
     * @return array Theme styles
     */
    private function get_theme_styles($theme) {
        $themes = array(
            'dark' => array(
                '.qcc-container' => 'background-color: #2d3748; color: #e2e8f0;',
                '.qcc-input-section' => 'background-color: #4a5568; border-color: #718096;',
                '.qcc-results-section' => 'background-color: #4a5568; border-color: #718096;'
            ),
            'light' => array(
                '.qcc-container' => 'background-color: #ffffff; color: #333333;',
                '.qcc-input-section' => 'background-color: #f8f9fa; border-color: #e9ecef;',
                '.qcc-results-section' => 'background-color: #f8f9fa; border-color: #e9ecef;'
            ),
            'modern' => array(
                '.qcc-container' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;',
                '.qcc-input-section' => 'background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px);',
                '.qcc-results-section' => 'background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px);'
            )
        );
        
        return $themes[$theme] ?? array();
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        // Only enqueue on pages that contain the shortcode
        if ($this->page_contains_shortcode()) {
            wp_enqueue_script(
                'qcc-calculator',
                plugin_dir_url(__FILE__) . '../assets/js/qcc-calculator.js',
                array('jquery'),
                '2.0',
                true
            );
            
            wp_enqueue_script(
                'chart-js',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            wp_enqueue_style(
                'qcc-calculator',
                plugin_dir_url(__FILE__) . '../assets/css/qcc-calculator.css',
                array(),
                '2.0'
            );
        }
    }
    
    /**
     * Check if current page contains shortcode
     * 
     * @return bool
     */
    private function page_contains_shortcode() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        return has_shortcode($post->post_content, self::SHORTCODE_NAME) ||
               has_shortcode($post->post_content, 'qcc_calculator') ||
               has_shortcode($post->post_content, 'cost_of_quality');
    }
    
    /**
     * Add schema markup for SEO
     */
    public function add_schema_markup() {
        if ($this->page_contains_shortcode()) {
            echo '<script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebApplication",
                "name": "Quality Cost Calculator",
                "description": "Calculate and analyze Cost of Quality metrics using the COGQ/COPQ model",
                "applicationCategory": "BusinessApplication",
                "operatingSystem": "Web Browser",
                "offers": {
                    "@type": "Offer",
                    "price": "0",
                    "priceCurrency": "USD"
                }
            }
            </script>';
        }
    }
    
    /**
     * Get input data from request
     * 
     * @return array Input data
     */
    private function get_input_data_from_request() {
        $input_data = array();
        
        // Check POST data
        if (!empty($_POST)) {
            $fields = array('revenue', 'quality_percentage', 'prevention_costs', 'appraisal_costs', 'internal_defect_costs', 'external_defect_costs');
            
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $input_data[$field] = floatval($_POST[$field]);
                }
            }
        }
        
        return $input_data;
    }
    
    /**
     * Get AJAX input data
     * 
     * @return array Input data
     */
    private function get_ajax_input_data() {
        $input_data = array();
        $fields = array('revenue', 'quality_percentage', 'prevention_costs', 'appraisal_costs', 'internal_defect_costs', 'external_defect_costs');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $input_data[$field] = floatval($_POST[$field]);
            }
        }
        
        return $input_data;
    }
    
    /**
     * Get AJAX export data
     * 
     * @return array Export data
     */
    private function get_ajax_export_data() {
        $calculation_data = json_decode(stripslashes($_POST['calculation_data'] ?? '{}'), true);
        return $calculation_data ?: array();
    }
    
    /**
     * Get default calculation values
     * 
     * @return array Default values
     */
    private function get_default_calculation_values() {
        return array(
            'revenue' => 140,
            'quality_percentage' => 6,
            'prevention_costs' => 10,
            'appraisal_costs' => 20,
            'internal_defect_costs' => 30,
            'external_defect_costs' => 40
        );
    }
    
    /**
     * Format calculation results for display
     * 
     * @param array $results Calculation results
     * @param array $input_data Input data
     * @return array Formatted results
     */
    private function format_calculation_results($results, $input_data) {
        $currency = $input_data['currency'] ?? 'EUR';
        $unit = $input_data['unit'] ?? 'millions';
        
        $formatted = array();
        
        foreach ($results as $category => $values) {
            if (is_array($values)) {
                $formatted[$category] = array();
                foreach ($values as $key => $value) {
                    $formatted[$category][$key] = $this->format_currency_value($value, $currency, $unit);
                }
            } else {
                $formatted[$category] = $this->format_currency_value($values, $currency, $unit);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Format currency value
     * 
     * @param float $value Value to format
     * @param string $currency Currency code
     * @param string $unit Unit
     * @return string Formatted value
     */
    private function format_currency_value($value, $currency, $unit) {
        $symbols = array('EUR' => '€', 'USD' => ', 'CNY' => '¥');
        $symbol = $symbols[$currency] ?? $currency;
        $unit_short = $unit === 'billions' ? 'Mrd.' : 'Mio.';
        
        return $symbol . ' ' . number_format($value, 2, '.', ',') . ' ' . $unit_short;
    }
    
    /**
     * Prepare chart data
     * 
     * @param array $calculations Calculation results
     * @return array Chart data
     */
    private function prepare_chart_data($calculations) {
        if (empty($calculations)) {
            return array();
        }
        
        return array(
            'labels' => array(
                $this->translator->get('prevention_costs', 'Prevention Costs'),
                $this->translator->get('appraisal_costs', 'Appraisal Costs'),
                $this->translator->get('internal_defect_costs', 'Internal Defect Costs'),
                $this->translator->get('external_defect_costs', 'External Defect Costs')
            ),
            'data' => array(
                $calculations['cogq']['prevention'] ?? 0,
                $calculations['cogq']['appraisal'] ?? 0,
                $calculations['copq']['internal'] ?? 0,
                $calculations['copq']['external'] ?? 0
            ),
            'backgroundColor' => array('#449775', '#6bc27f', '#dc3545', '#ff6b6b'),
            'borderColor' => array('#357a61', '#5ba06b', '#c82333', '#ff5252'),
            'borderWidth' => 2
        );
    }
    
    /**
     * Detect user language
     * 
     * @return string Language code
     */
    private function detect_language() {
        // Try WordPress locale first
        $wp_locale = get_locale();
        $language_map = array(
            'en_US' => 'en',
            'de_DE' => 'de',
            'fr_FR' => 'fr',
            'es_ES' => 'es',
            'zh_CN' => 'zh'
        );
        
        if (isset($language_map[$wp_locale])) {
            return $language_map[$wp_locale];
        }
        
        // Try browser language
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $supported_langs = array('en', 'de', 'fr', 'es', 'zh');
            
            if (in_array($browser_lang, $supported_langs)) {
                return $browser_lang;
            }
        }
        
        return 'en'; // Default fallback
    }
    
    /**
     * Render error fallback
     * 
     * @param Exception $exception Exception that occurred
     * @param array $attributes Shortcode attributes
     * @return string Error HTML
     */
    private function render_error_fallback($exception, $attributes) {
        $error_data = array(
            'error_message' => $exception->getMessage(),
            'error_type' => get_class($exception),
            'show_debug' => $attributes['debug'] ?? false,
            'calculator_data' => $this->get_default_calculation_values()
        );
        
        try {
            return $this->template_router->render_emergency_fallback($error_data);
        } catch (Exception $e) {
            // Ultimate fallback - plain HTML
            return '<div class="qcc-error">
                <h3>Calculator Error</h3>
                <p>The Quality Cost Calculator encountered an error and cannot display properly.</p>
                <p>Please try refreshing the page or contact support.</p>
            </div>';
        }
    }
    
    /**
     * Enhance content with calculator context
     * 
     * @param string $content Post content
     * @return string Enhanced content
     */
    public function enhance_content_with_calculator($content) {
        // Add calculator-specific context when shortcode is present
        if (has_shortcode($content, self::SHORTCODE_NAME)) {
            // Add CSS class to body for styling
            add_filter('body_class', function($classes) {
                $classes[] = 'has-qcc-calculator';
                return $classes;
            });
        }
        
        return $content;
    }
    
    /**
     * Get cached output
     * 
     * @param string $instance_id Instance ID
     * @return string|false Cached output or false
     */
    private function get_cached_output($instance_id) {
        return get_transient('qcc_output_' . $instance_id);
    }
    
    /**
     * Cache output
     * 
     * @param string $instance_id Instance ID
     * @param string $output Output to cache
     * @param int $duration Cache duration in seconds
     */
    private function cache_output($instance_id, $output, $duration) {
        set_transient('qcc_output_' . $instance_id, $output, $duration);
    }
    
    /**
     * Log shortcode render for analytics
     * 
     * @param array $attributes Shortcode attributes
     * @param float $render_time Render time in seconds
     */
    private function log_shortcode_render($attributes, $render_time) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[QCC] Shortcode rendered in %.3fs with attributes: %s',
                $render_time,
                wp_json_encode($attributes)
            ));
        }
    }
}
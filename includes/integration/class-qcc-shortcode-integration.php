php

  QCC Shortcode Integration Bridge
  
  Integration layer that connects the new Atomic Design rendering system
  with the existing shortcode infrastructure while maintaining backward compatibility.
 
  @package QualityCostCalculator
  @subpackage Integration
  @since 3.0.0
 

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Shortcode_Integration {
    
    private $html_orchestrator;
    private $legacy_renderer;
    private $feature_flags;
    private $compatibility_mode = false;
    
    public function __construct() {
        $this-init_components();
        $this-setup_feature_flags();
        $this-setup_hooks();
    }
    
    
      Main integration method - bridges old and new systems
     
    public function render_calculator($atts = array(), $content = '') {
        try {
             Parse and validate shortcode attributes
            $config = $this-parse_shortcode_attributes($atts);
            
             Check if new rendering system should be used
            if ($this-should_use_new_renderer($config)) {
                return $this-render_with_new_system($config);
            } else {
                return $this-render_with_legacy_system($config);
            }
            
        } catch (Exception $e) {
             Fallback to legacy system on error
            error_log('QCC Integration Error ' . $e-getMessage());
            return $this-render_emergency_fallback($atts);
        }
    }
    
    
      Parse shortcode attributes with backward compatibility
     
    private function parse_shortcode_attributes($atts) {
        $defaults = array(
             Legacy attributes (maintained for backward compatibility)
            'language' = 'en',
            'currency' = 'EUR', 
            'unit' = '1000000',
            'theme' = 'default',
            'layout' = 'two_column',
            
             Legacy feature flags
            'show_charts' = true,
            'show_export' = true,
            'show_controls' = true,
            
             New rendering system flags
            'use_new_renderer' = true,
            'enable_atomic_design' = true,
            'template_override' = false,
            
             Performance options
            'cache_enabled' = true,
            'lazy_loading' = true,
            'inline_assets' = false,
            
             Backward compatibility
            'legacy_mode' = false,
            'force_fallback' = false
        );
        
        $config = shortcode_atts($defaults, $atts, 'quality_cost_calculator');
        
         Convert legacy boolean strings to actual booleans
        foreach (['show_charts', 'show_export', 'show_controls', 'use_new_renderer', 'legacy_mode'] as $bool_key) {
            $config[$bool_key] = filter_var($config[$bool_key], FILTER_VALIDATE_BOOLEAN);
        }
        
         Legacy attribute mapping
        $config = $this-map_legacy_attributes($config);
        
        return $config;
    }
    
    
      Map legacy attributes to new system
     
    private function map_legacy_attributes($config) {
         Map old attribute names to new ones
        $legacy_mappings = array(
            'show_charts' = 'include_charts',
            'show_export' = 'include_export', 
            'show_controls' = 'include_controls'
        );
        
        foreach ($legacy_mappings as $legacy_key = $new_key) {
            if (isset($config[$legacy_key])) {
                $config[$new_key] = $config[$legacy_key];
            }
        }
        
         Handle legacy layout names
        $layout_mappings = array(
            'single' = 'single_column',
            'double' = 'two_column', 
            'triple' = 'three_column',
            'tabs' = 'tabbed'
        );
        
        if (isset($layout_mappings[$config['layout']])) {
            $config['layout'] = $layout_mappings[$config['layout']];
        }
        
        return $config;
    }
    
    
      Determine if new rendering system should be used
     
    private function should_use_new_renderer($config) {
         Force legacy mode
        if ($config['legacy_mode']  $config['force_fallback']) {
            return false;
        }
        
         Feature flag check
        if (!$this-feature_flags['atomic_rendering_enabled']) {
            return false;
        }
        
         User preference
        if (!$config['use_new_renderer']) {
            return false;
        }
        
         Check if all required components are available
        if (!$this-verify_new_system_dependencies()) {
            return false;
        }
        
        return true;
    }
    
    
      Render using new atomic design system
     
    private function render_with_new_system($config) {
         Add integration metadata
        $config['integration_mode'] = 'atomic';
        $config['calculator_id'] = 'qcc-' . uniqid();
        
         Use HTML Orchestrator for rendering
        return $this-html_orchestrator-orchestrate($config);
    }
    
    
      Render using legacy system
     
    private function render_with_legacy_system($config) {
         Ensure legacy renderer is available
        if (!$this-legacy_renderer) {
            return $this-render_emergency_fallback($config);
        }
        
         Add legacy metadata
        $config['integration_mode'] = 'legacy';
        
         Use legacy rendering method
        return $this-legacy_renderer-render_calculator($config);
    }
    
    
      Emergency fallback renderer
     
    private function render_emergency_fallback($config) {
        $error_message = __('Calculator temporarily unavailable. Please try again later.', 'quality-cost-calculator');
        
        if (WP_DEBUG) {
            $error_message .= 'brsmallIntegration Error Check error logs for details.small';
        }
        
        return sprintf(
            'div class=qcc-calculator-error style=padding 20px; border 2px solid #dc3545; background #f8d7da; color #721c24; border-radius 4px;
                h3%sh3
                p%sp
            div',
            __('Quality Cost Calculator', 'quality-cost-calculator'),
            $error_message
        );
    }
    
    
      Verify new system dependencies
     
    private function verify_new_system_dependencies() {
        $required_components = array(
            'html_orchestrator',
            'component_registry', 
            'template_router',
            'form_builder',
            'display_builder'
        );
        
        foreach ($required_components as $component) {
            if (!$this-is_component_available($component)) {
                error_log(QCC Integration Missing component {$component});
                return false;
            }
        }
        
        return true;
    }
    
    
      Check if component is available
     
    private function is_component_available($component_name) {
        try {
            $component = QCC_Service_Containerget($component_name);
            return $component !== null;
        } catch (Exception $e) {
            return false;
        }
    }
    
    
      Initialize components
     
    private function init_components() {
        try {
             Initialize new rendering system
            $this-html_orchestrator = QCC_Service_Containerget('html_orchestrator');
            
             Keep reference to legacy renderer for fallback
            if (class_exists('QCC_Shortcode_Renderer_Legacy')) {
                $this-legacy_renderer = new QCC_Shortcode_Renderer_Legacy();
            }
            
        } catch (Exception $e) {
            error_log('QCC Integration Init Error ' . $e-getMessage());
            $this-compatibility_mode = true;
        }
    }
    
    
      Setup feature flags
     
    private function setup_feature_flags() {
        $this-feature_flags = array(
            'atomic_rendering_enabled' = get_option('qcc_atomic_rendering', true),
            'template_system_enabled' = get_option('qcc_template_system', true),
            'performance_mode_enabled' = get_option('qcc_performance_mode', true),
            'debug_mode_enabled' = defined('WP_DEBUG') && WP_DEBUG
        );
        
         Allow filtering of feature flags
        $this-feature_flags = apply_filters('qcc_feature_flags', $this-feature_flags);
    }
    
    
      Setup WordPress hooks
     
    private function setup_hooks() {
         Admin settings for integration options
        add_action('admin_init', array($this, 'register_integration_settings'));
        
         AJAX handlers for dynamic functionality
        add_action('wp_ajax_qcc_switch_renderer', array($this, 'handle_renderer_switch'));
        add_action('wp_ajax_nopriv_qcc_switch_renderer', array($this, 'handle_renderer_switch'));
        
         Debug hooks
        if ($this-feature_flags['debug_mode_enabled']) {
            add_action('wp_footer', array($this, 'output_debug_info'));
        }
        
         Compatibility hooks
        add_filter('qcc_shortcode_attributes', array($this, 'filter_shortcode_attributes'), 10, 1);
        add_action('qcc_before_render', array($this, 'before_render_compatibility_check'));
    }
    
    
      Register integration settings
     
    public function register_integration_settings() {
        register_setting('qcc_integration', 'qcc_atomic_rendering');
        register_setting('qcc_integration', 'qcc_template_system');
        register_setting('qcc_integration', 'qcc_performance_mode');
        
        add_settings_section(
            'qcc_integration_section',
            __('Rendering System Settings', 'quality-cost-calculator'),
            array($this, 'integration_section_callback'),
            'qcc_integration'
        );
        
        add_settings_field(
            'qcc_atomic_rendering',
            __('Enable Atomic Rendering', 'quality-cost-calculator'),
            array($this, 'atomic_rendering_callback'),
            'qcc_integration',
            'qcc_integration_section'
        );
    }
    
    
      Handle renderer switch AJAX
     
    public function handle_renderer_switch() {
        check_ajax_referer('qcc_renderer_switch', 'nonce');
        
        $renderer_type = sanitize_text_field($_POST['renderer_type']);
        $calculator_id = sanitize_text_field($_POST['calculator_id']);
        
        if ($renderer_type === 'atomic') {
            $config = array('use_new_renderer' = true);
            $html = $this-render_with_new_system($config);
        } else {
            $config = array('use_new_renderer' = false);
            $html = $this-render_with_legacy_system($config);
        }
        
        wp_send_json_success(array(
            'html' = $html,
            'renderer' = $renderer_type
        ));
    }
    
    
      Output debug information
     
    public function output_debug_info() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $debug_info = array(
            'integration_mode' = $this-compatibility_mode  'compatibility'  'normal',
            'feature_flags' = $this-feature_flags,
            'available_components' = $this-get_available_components(),
            'memory_usage' = memory_get_usage(true),
            'peak_memory' = memory_get_peak_usage(true)
        );
        
        echo 'scriptconsole.log(QCC Integration Debug, ' . wp_json_encode($debug_info) . ');script';
    }
    
    
      Get available components for debug
     
    private function get_available_components() {
        $components = array();
        $test_components = array(
            'html_orchestrator', 'component_registry', 'template_router',
            'form_builder', 'display_builder', 'control_builder', 'layout_builder'
        );
        
        foreach ($test_components as $component) {
            $components[$component] = $this-is_component_available($component);
        }
        
        return $components;
    }
    
    
      Filter shortcode attributes for compatibility
     
    public function filter_shortcode_attributes($atts) {
         Add backward compatibility transformations
        if (isset($atts['color_scheme'])) {
            $atts['theme'] = $atts['color_scheme'];
            unset($atts['color_scheme']);
        }
        
        return $atts;
    }
    
    
      Before render compatibility check
     
    public function before_render_compatibility_check() {
         Perform any necessary compatibility checks or setup
        if ($this-compatibility_mode) {
             Log compatibility issues
            error_log('QCC Running in compatibility mode due to component initialization issues');
        }
    }
    
    
      Admin section callback
     
    public function integration_section_callback() {
        echo 'p' . __('Configure rendering system options for the Quality Cost Calculator.', 'quality-cost-calculator') . 'p';
    }
    
    
      Atomic rendering setting callback
     
    public function atomic_rendering_callback() {
        $value = get_option('qcc_atomic_rendering', true);
        printf(
            'input type=checkbox name=qcc_atomic_rendering value=1 %s  %s',
            checked(1, $value, false),
            __('Use new atomic design rendering system (recommended)', 'quality-cost-calculator')
        );
    }
    
    
      Get integration statistics
     
    public function get_integration_stats() {
        return array(
            'total_renders' = get_option('qcc_total_renders', 0),
            'atomic_renders' = get_option('qcc_atomic_renders', 0),
            'legacy_renders' = get_option('qcc_legacy_renders', 0),
            'error_count' = get_option('qcc_render_errors', 0),
            'average_render_time' = get_option('qcc_avg_render_time', 0)
        );
    }
    
    
      Track render statistics
     
    private function track_render_stats($render_type, $render_time) {
        $total_renders = get_option('qcc_total_renders', 0) + 1;
        update_option('qcc_total_renders', $total_renders);
        
        if ($render_type === 'atomic') {
            $atomic_renders = get_option('qcc_atomic_renders', 0) + 1;
            update_option('qcc_atomic_renders', $atomic_renders);
        } else {
            $legacy_renders = get_option('qcc_legacy_renders', 0) + 1;
            update_option('qcc_legacy_renders', $legacy_renders);
        }
        
         Update average render time
        $current_avg = get_option('qcc_avg_render_time', 0);
        $new_avg = (($current_avg  ($total_renders - 1)) + $render_time)  $total_renders;
        update_option('qcc_avg_render_time', $new_avg);
    }
    
    
      Public method to get feature flag status
     
    public function is_feature_enabled($feature) {
        return $this-feature_flags[$feature]  false;
    }
    
    
      Enabledisable features dynamically
     
    public function set_feature_flag($feature, $enabled) {
        $this-feature_flags[$feature] = $enabled;
        update_option('qcc_' . $feature, $enabled);
    }
}
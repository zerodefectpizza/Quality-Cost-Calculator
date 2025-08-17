<?php
/**
 * QCC HTML Orchestrator
 * 
 * Central coordinator for all rendering components. Manages component registry,
 * template coordination, asset integration and performance optimization.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Orchestration
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_HTML_Orchestrator {
    
    private $component_registry;
    private $template_router;
    private $layout_coordinator;
    private $asset_manager;
    private $translator;
    private $cache_manager;
    
    private $builders = array();
    private $render_cache = array();
    private $performance_metrics = array();
    
    public function __construct() {
        $this->init_dependencies();
        $this->init_builders();
        $this->setup_hooks();
    }
    
    /**
     * Main orchestration method - renders complete calculator
     */
    public function orchestrate($config = array()) {
        $start_time = microtime(true);
        
        try {
            // Validate configuration
            $config = $this->validate_and_normalize_config($config);
            
            // Check cache first
            $cache_key = $this->generate_cache_key($config);
            if ($cached_html = $this->get_cached_html($cache_key)) {
                $this->track_performance('cache_hit', microtime(true) - $start_time);
                return $cached_html;
            }
            
            // Build components
            $components = $this->build_components($config);
            
            // Coordinate layout
            $layout_html = $this->coordinate_layout($components, $config);
            
            // Integrate assets
            $final_html = $this->integrate_assets($layout_html, $config);
            
            // Cache result
            $this->cache_html($cache_key, $final_html);
            
            // Track performance
            $this->track_performance('full_render', microtime(true) - $start_time);
            
            return $final_html;
            
        } catch (Exception $e) {
            $this->handle_orchestration_error($e, $config);
            return $this->render_fallback($config);
        }
    }
    
    /**
     * Build all required components
     */
    private function build_components($config) {
        $components = array();
        $component_configs = $this->extract_component_configs($config);
        
        // Build header if requested
        if ($config['include_header'] ?? true) {
            $components['header'] = $this->build_header_component($component_configs['header'] ?? array());
        }
        
        // Build control panel
        if ($config['include_controls'] ?? true) {
            $components['controls'] = $this->build_controls_component($component_configs['controls'] ?? array());
        }
        
        // Build form section
        if ($config['include_form'] ?? true) {
            $components['form'] = $this->build_form_component($component_configs['form'] ?? array());
        }
        
        // Build results display
        if ($config['include_results'] ?? true) {
            $components['results'] = $this->build_results_component($component_configs['results'] ?? array());
        }
        
        // Build charts section
        if ($config['include_charts'] ?? true) {
            $components['charts'] = $this->build_charts_component($component_configs['charts'] ?? array());
        }
        
        // Build status display
        if ($config['include_status'] ?? true) {
            $components['status'] = $this->build_status_component($component_configs['status'] ?? array());
        }
        
        return $components;
    }
    
    /**
     * Build header component
     */
    private function build_header_component($config) {
        $header_html = array();
        
        if ($config['show_title'] ?? true) {
            $title = $config['title'] ?? $this->translator->get('quality_cost_calculator');
            $header_html[] = sprintf('<h2 class="qcc-calculator-title">%s</h2>', esc_html($title));
        }
        
        if ($config['show_description'] ?? true) {
            $description = $config['description'] ?? $this->translator->get('calculator_description');
            $header_html[] = sprintf('<p class="qcc-calculator-description">%s</p>', esc_html($description));
        }
        
        if ($config['show_help_link'] ?? false) {
            $help_url = $config['help_url'] ?? '#';
            $header_html[] = sprintf(
                '<a href="%s" class="qcc-help-link" target="_blank">%s %s</a>',
                esc_url($help_url),
                $this->get_icon_svg('help-circle'),
                esc_html($this->translator->get('help'))
            );
        }
        
        return sprintf(
            '<div class="qcc-calculator-header">%s</div>',
            implode("\n", $header_html)
        );
    }
    
    /**
     * Build controls component using Control Builder
     */
    private function build_controls_component($config) {
        if (!isset($this->builders['control'])) {
            $this->builders['control'] = $this->component_registry->get('control_builder');
        }
        
        $control_config = array_merge(array(
            'type' => 'full',
            'current_language' => $config['language'] ?? 'en',
            'current_currency' => $config['currency'] ?? 'EUR',
            'current_unit' => $config['unit'] ?? '1000000',
            'export_formats' => array('pdf', 'csv', 'json'),
            'layout' => 'compact'
        ), $config);
        
        return $this->builders['control']->build($control_config);
    }
    
    /**
     * Build form component using Form Builder
     */
    private function build_form_component($config) {
        if (!isset($this->builders['form'])) {
            $this->builders['form'] = $this->component_registry->get('form_builder');
        }
        
        $form_config = array_merge(array(
            'type' => 'full',
            'include_cogq' => true,
            'include_copq' => true,
            'include_opportunity' => $config['enable_opportunity'] ?? false,
            'validation_real_time' => true,
            'default_values' => $this->get_default_calculation_values()
        ), $config);
        
        return $this->builders['form']->build($form_config);
    }
    
    /**
     * Build results component using Display Builder
     */
    private function build_results_component($config) {
        if (!isset($this->builders['display'])) {
            $this->builders['display'] = $this->component_registry->get('display_builder');
        }
        
        $results_config = array_merge(array(
            'type' => 'results_only',
            'layout' => 'grid',
            'include_trends' => $config['show_trends'] ?? false,
            'results' => $this->get_initial_results_data()
        ), $config);
        
        return $this->builders['display']->build($results_config);
    }
    
    /**
     * Build charts component using Display Builder
     */
    private function build_charts_component($config) {
        if (!isset($this->builders['display'])) {
            $this->builders['display'] = $this->component_registry->get('display_builder');
        }
        
        $charts_config = array_merge(array(
            'type' => 'charts_only',
            'chart_type' => 'bar',
            'include_breakdown' => true,
            'data' => $this->get_initial_chart_data()
        ), $config);
        
        return $this->builders['display']->build($charts_config);
    }
    
    /**
     * Build status component using Display Builder
     */
    private function build_status_component($config) {
        if (!isset($this->builders['display'])) {
            $this->builders['display'] = $this->component_registry->get('display_builder');
        }
        
        $status_config = array_merge(array(
            'type' => 'status_only',
            'validation' => array('valid' => true, 'messages' => array()),
            'calculation_status' => 'ready',
            'show_progress' => false
        ), $config);
        
        return $this->builders['display']->build($status_config);
    }
    
    /**
     * Coordinate layout using Layout Builder
     */
    private function coordinate_layout($components, $config) {
        if (!isset($this->builders['layout'])) {
            $this->builders['layout'] = $this->component_registry->get('layout_builder');
        }
        
        $layout_config = array(
            'type' => 'complete',
            'layout_structure' => $config['layout'] ?? 'two_column',
            'components' => $components
        );
        
        return $this->builders['layout']->build($layout_config);
    }
    
    /**
     * Integrate assets into final HTML
     */
    private function integrate_assets($html, $config) {
        $asset_config = array(
            'include_css' => $config['include_css'] ?? true,
            'include_js' => $config['include_js'] ?? true,
            'minify' => $config['minify_assets'] ?? false,
            'inline' => $config['inline_assets'] ?? false
        );
        
        $assets_html = array();
        
        // CSS Assets
        if ($asset_config['include_css']) {
            $assets_html[] = $this->generate_css_assets($config);
        }
        
        // JavaScript Assets
        if ($asset_config['include_js']) {
            $assets_html[] = $this->generate_js_assets($config);
        }
        
        // Wrap in main container
        $container_classes = $this->get_main_container_classes($config);
        $container_id = 'qcc-calculator-' . uniqid();
        
        return sprintf(
            '<div id="%s" class="%s" data-config="%s">
                %s
                %s
            </div>',
            esc_attr($container_id),
            esc_attr($container_classes),
            esc_attr(wp_json_encode($this->get_client_config($config))),
            $html,
            implode("\n", $assets_html)
        );
    }
    
    /**
     * Generate CSS assets
     */
    private function generate_css_assets($config) {
        $required_css = array(
            'qcc-base.css',
            'qcc-form-builder.css',
            'qcc-display-builder.css',
            'qcc-control-builder.css',
            'qcc-layout-builder.css'
        );
        
        if ($config['inline_assets'] ?? false) {
            return $this->asset_manager->generate_inline_css($required_css, $config);
        } else {
            return $this->asset_manager->generate_css_links($required_css, $config);
        }
    }
    
    /**
     * Generate JavaScript assets
     */
    private function generate_js_assets($config) {
        $required_js = array(
            'qcc-calculator.js',
            'qcc-charts.js',
            'qcc-controls.js',
            'qcc-validation.js',
            'qcc-export.js'
        );
        
        $js_config = array(
            'calculator_id' => $config['calculator_id'] ?? uniqid('qcc_'),
            'currency' => $config['currency'] ?? 'EUR',
            'unit' => $config['unit'] ?? '1000000',
            'language' => $config['language'] ?? 'en',
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_calculator'),
            'translations' => $this->get_js_translations()
        );
        
        if ($config['inline_assets'] ?? false) {
            return $this->asset_manager->generate_inline_js($required_js, $js_config, $config);
        } else {
            return $this->asset_manager->generate_js_scripts($required_js, $js_config, $config);
        }
    }
    
    /**
     * Extract component configurations from main config
     */
    private function extract_component_configs($config) {
        return array(
            'header' => $config['header'] ?? array(),
            'controls' => $config['controls'] ?? array(),
            'form' => $config['form'] ?? array(),
            'results' => $config['results'] ?? array(),
            'charts' => $config['charts'] ?? array(),
            'status' => $config['status'] ?? array()
        );
    }
    
    /**
     * Get default calculation values
     */
    private function get_default_calculation_values() {
        return array(
            'revenue' => 140,
            'quality_percentage' => 6,
            'prevention' => 10,
            'appraisal' => 20,
            'internal_defect' => 30,
            'external_defect' => 40
        );
    }
    
    /**
     * Get initial results data
     */
    private function get_initial_results_data() {
        $defaults = $this->get_default_calculation_values();
        $quality_costs = ($defaults['revenue'] * $defaults['quality_percentage']) / 100;
        
        return array(
            'cogq' => array(
                'prevention' => ($quality_costs * $defaults['prevention']) / 100,
                'appraisal' => ($quality_costs * $defaults['appraisal']) / 100,
                'total' => ($quality_costs * ($defaults['prevention'] + $defaults['appraisal'])) / 100
            ),
            'copq' => array(
                'internal' => ($quality_costs * $defaults['internal_defect']) / 100,
                'external' => ($quality_costs * $defaults['external_defect']) / 100,
                'total' => ($quality_costs * ($defaults['internal_defect'] + $defaults['external_defect'])) / 100
            )
        );
    }
    
    /**
     * Get initial chart data
     */
    private function get_initial_chart_data() {
        $results = $this->get_initial_results_data();
        
        return array(
            'comparison' => array(
                'labels' => array('COGQ', 'COPQ'),
                'datasets' => array(
                    array(
                        'data' => array($results['cogq']['total'], $results['copq']['total']),
                        'backgroundColor' => array('#28A745', '#DC3545')
                    )
                )
            ),
            'cogq' => array(
                'labels' => array('Prevention', 'Appraisal'),
                'datasets' => array(
                    array(
                        'data' => array($results['cogq']['prevention'], $results['cogq']['appraisal']),
                        'backgroundColor' => array('#2E8B57', '#4682B4')
                    )
                )
            ),
            'copq' => array(
                'labels' => array('Internal Defects', 'External Defects'),
                'datasets' => array(
                    array(
                        'data' => array($results['copq']['internal'], $results['copq']['external']),
                        'backgroundColor' => array('#FF6347', '#DC143C')
                    )
                )
            )
        );
    }
    
    /**
     * Get main container classes
     */
    private function get_main_container_classes($config) {
        $classes = array('qcc-calculator-container');
        
        $classes[] = 'qcc-theme-' . ($config['theme'] ?? 'default');
        $classes[] = 'qcc-layout-' . ($config['layout'] ?? 'two_column');
        
        if ($config['responsive'] ?? true) {
            $classes[] = 'qcc-responsive';
        }
        
        if ($config['rtl'] ?? false) {
            $classes[] = 'qcc-rtl';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get client-side configuration
     */
    private function get_client_config($config) {
        return array(
            'currency' => $config['currency'] ?? 'EUR',
            'unit' => $config['unit'] ?? '1000000',
            'language' => $config['language'] ?? 'en',
            'real_time_validation' => $config['real_time_validation'] ?? true,
            'auto_calculate' => $config['auto_calculate'] ?? true,
            'chart_animations' => $config['chart_animations'] ?? true
        );
    }
    
    /**
     * Get JavaScript translations
     */
    private function get_js_translations() {
        return array(
            'validation_error' => $this->translator->get('validation_error'),
            'calculation_error' => $this->translator->get('calculation_error'),
            'export_success' => $this->translator->get('export_success'),
            'export_error' => $this->translator->get('export_error'),
            'loading' => $this->translator->get('loading')
        );
    }
    
    /**
     * Validate and normalize configuration
     */
    private function validate_and_normalize_config($config) {
        $defaults = array(
            'language' => 'en',
            'currency' => 'EUR',
            'unit' => '1000000',
            'layout' => 'two_column',
            'theme' => 'default',
            'responsive' => true,
            'include_header' => true,
            'include_controls' => true,
            'include_form' => true,
            'include_results' => true,
            'include_charts' => true,
            'include_status' => true,
            'include_css' => true,
            'include_js' => true,
            'cache_enabled' => true
        );
        
        return array_merge($defaults, $config);
    }
    
    /**
     * Generate cache key
     */
    private function generate_cache_key($config) {
        $key_data = array(
            'config_hash' => md5(serialize($config)),
            'language' => $config['language'],
            'theme' => $config['theme'],
            'layout' => $config['layout']
        );
        
        return 'qcc_orchestrator_' . md5(serialize($key_data));
    }
    
    /**
     * Get cached HTML
     */
    private function get_cached_html($cache_key) {
        if (!$this->cache_manager || !($this->cache_manager instanceof QCC_Cache_Manager)) {
            return false;
        }
        
        return $this->cache_manager->get($cache_key);
    }
    
    /**
     * Cache HTML
     */
    private function cache_html($cache_key, $html) {
        if ($this->cache_manager && ($this->cache_manager instanceof QCC_Cache_Manager)) {
            $this->cache_manager->set($cache_key, $html, 3600); // 1 hour
        }
    }
    
    /**
     * Track performance metrics
     */
    private function track_performance($metric, $value) {
        $this->performance_metrics[$metric] = $value;
        
        // Log to performance monitor if available
        if (class_exists('QCC_Performance_Monitor')) {
            QCC_Performance_Monitor::track('orchestrator_' . $metric, $value);
        }
    }
    
    /**
     * Handle orchestration errors
     */
    private function handle_orchestration_error($exception, $config) {
        error_log(sprintf(
            'QCC Orchestrator Error: %s in %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));
        
        if (class_exists('QCC_Error_Tracker')) {
            QCC_Error_Tracker::log_error('orchestrator_error', $exception, $config);
        }
    }
    
    /**
     * Render fallback HTML
     */
    private function render_fallback($config) {
        return sprintf(
            '<div class="qcc-calculator-error">
                <p>%s</p>
                <small>%s</small>
            </div>',
            esc_html($this->translator->get('calculator_temporarily_unavailable')),
            esc_html($this->translator->get('please_try_again_later'))
        );
    }
    
    /**
     * Initialize dependencies
     */
    private function init_dependencies() {
        $this->component_registry = QCC_Service_Container::get('component_registry');
        $this->template_router = QCC_Service_Container::get('template_router');
        $this->layout_coordinator = QCC_Service_Container::get('layout_coordinator');
        $this->asset_manager = QCC_Service_Container::get('asset_manager');
        $this->translator = QCC_Service_Container::get('translator');
        $this->cache_manager = QCC_Service_Container::get('cache_manager');
    }
    
    /**
     * Initialize builders
     */
    private function init_builders() {
        // Builders are loaded lazily when needed
        $this->builders = array();
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_conditional_assets'));
        add_filter('qcc_orchestrator_config', array($this, 'filter_orchestrator_config'));
    }
    
    /**
     * Enqueue conditional assets
     */
    public function enqueue_conditional_assets() {
        // Only enqueue if calculator is being used on current page
        if ($this->is_calculator_on_page()) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
    }
    
    /**
     * Check if calculator is on current page
     */
    private function is_calculator_on_page() {
        global $post;
        return $post && has_shortcode($post->post_content, 'quality_cost_calculator');
    }
    
    /**
     * Filter orchestrator configuration
     */
    public function filter_orchestrator_config($config) {
        // Allow themes and plugins to modify orchestrator config
        return $config;
    }
    
    /**
     * Get SVG icon
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'help-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        return $this->performance_metrics;
    }
}
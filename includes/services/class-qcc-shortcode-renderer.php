<?php
/**
 * QCC Shortcode Renderer Service
 * 
 * Handles all rendering logic for the shortcode system.
 * Uses modern template architecture with component system.
 *
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode rendering service using modern architecture
 */
class QCC_Shortcode_Renderer {
    
    /**
     * @var QCC_Service_Container Service container
     */
    private $container;
    
    /**
     * @var QCC_Template_Router Template routing system
     */
    private $template_router;
    
    /**
     * @var QCC_Layout_Builder Layout construction service
     */
    private $layout_builder;
    
    /**
     * @var QCC_Component_Registry Component registry
     */
    private $component_registry;
    
    /**
     * @var QCC_Translator Translation service
     */
    private $translator;
    
    /**
     * @var QCC_Asset_Manager Asset management
     */
    private $asset_manager;
    
    /**
     * @var array Rendering statistics
     */
    private $rendering_stats = array();
    
    /**
     * Initialize renderer with service dependencies
     * 
     * @param QCC_Service_Container $container Service container
     */
    public function __construct($container) {
        $this->container = $container;
        $this->init_services();
        $this->setup_hooks();
    }
    
    /**
     * Initialize required services
     */
    private function init_services() {
        try {
            $this->template_router = $this->container->get('template_router');
            $this->layout_builder = $this->container->get('layout_builder');
            $this->component_registry = $this->container->get('component_registry');
            $this->translator = $this->container->get('translator');
            $this->asset_manager = $this->container->get('asset_manager');
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Shortcode Renderer: Service initialization failed - ' . $e->getMessage());
            }
            throw new Exception('Failed to initialize shortcode renderer services');
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_conditional_assets'));
        add_filter('qcc_renderer_config', array($this, 'filter_renderer_config'));
    }
    
    /**
     * Main rendering method
     * 
     * @param array $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    public function render($atts, $content = '') {
        $start_time = microtime(true);
        
        try {
            // Build component configuration
            $component_config = $this->build_component_config($atts);
            
            // Determine rendering strategy
            $strategy = $this->determine_strategy($atts, $component_config);
            
            // Execute rendering
            $html = $this->execute_rendering($strategy, $component_config, $content);
            
            // Enqueue required assets
            $this->enqueue_required_assets($component_config);
            
            // Record performance stats
            $this->record_performance_stats($start_time, $strategy);
            
            return $html;
            
        } catch (Exception $e) {
            return $this->handle_rendering_error($e, $atts);
        }
    }
    
    /**
     * Build comprehensive component configuration
     * 
     * @param array $atts Shortcode attributes
     * @return array Component configuration
     */
    private function build_component_config($atts) {
        return array(
            'layout' => $this->get_layout_config($atts),
            'header' => $this->get_header_config($atts),
            'controls' => $this->get_controls_config($atts),
            'form' => $this->get_form_config($atts),
            'results' => $this->get_results_config($atts),
            'charts' => $this->get_charts_config($atts),
            'global' => $this->get_global_config($atts)
        );
    }
    
    /**
     * Get layout configuration
     * 
     * @param array $atts Attributes
     * @return array Layout config
     */
    private function get_layout_config($atts) {
        $layout_configs = array(
            'single_column' => array(
                'container_class' => 'qcc-layout-single',
                'grid_columns' => 1,
                'section_spacing' => 'normal'
            ),
            'two_column' => array(
                'container_class' => 'qcc-layout-two-column',
                'grid_columns' => 2,
                'section_spacing' => 'compact',
                'responsive_breakpoint' => '768px'
            ),
            'dashboard' => array(
                'container_class' => 'qcc-layout-dashboard',
                'grid_columns' => 'auto',
                'section_spacing' => 'tight',
                'card_style' => 'elevated'
            ),
            'compact' => array(
                'container_class' => 'qcc-layout-compact',
                'grid_columns' => 1,
                'section_spacing' => 'minimal',
                'hide_labels' => true
            )
        );
        
        $layout = $atts['layout'] ?? 'two_column';
        $config = $layout_configs[$layout] ?? $layout_configs['two_column'];
        
        // Override columns if explicitly set
        if (isset($atts['columns']) && $atts['columns'] !== 'auto') {
            $config['grid_columns'] = intval($atts['columns']);
        }
        
        return $config;
    }
    
    /**
     * Get header component configuration
     * 
     * @param array $atts Attributes
     * @return array Header config
     */
    private function get_header_config($atts) {
        return array(
            'show_title' => true,
            'title' => $this->translator->get('calculator.title', 'Quality Cost Calculator'),
            'subtitle' => $this->translator->get('calculator.subtitle', 'Professional COGQ/COPQ Analysis'),
            'show_help' => true,
            'help_tooltip' => $this->translator->get('calculator.help_tooltip', 'Calculate Cost of Good Quality (COGQ) and Cost of Poor Quality (COPQ)'),
            'style' => $atts['style'] ?? 'default'
        );
    }
    
    /**
     * Get controls component configuration
     * 
     * @param array $atts Attributes
     * @return array Controls config
     */
    private function get_controls_config($atts) {
        return array(
            'show_language_selector' => true,
            'show_currency_selector' => true,
            'show_units_selector' => true,
            'show_reset_button' => true,
            'language' => $atts['language'] ?? 'auto',
            'currency' => $atts['currency'] ?? 'EUR',
            'units' => $atts['units'] ?? 'pieces',
            'available_currencies' => array('EUR', 'USD', 'GBP', 'JPY', 'CNY'),
            'available_units' => array('pieces', 'kg', 'liters', 'hours')
        );
    }
    
    /**
     * Get form component configuration
     * 
     * @param array $atts Attributes
     * @return array Form config
     */
    private function get_form_config($atts) {
        return array(
            'sections' => array(
                'basic_inputs' => array(
                    'title' => $this->translator->get('form.basic_title', 'Basic Parameters'),
                    'fields' => array('production_volume', 'unit_price', 'production_cost')
                ),
                'cogq_inputs' => array(
                    'title' => $this->translator->get('form.cogq_title', 'Cost of Good Quality (COGQ)'),
                    'show' => $atts['show_cogq'] ?? true,
                    'fields' => array('prevention_cost', 'appraisal_cost'),
                    'color_scheme' => 'green'
                ),
                'copq_inputs' => array(
                    'title' => $this->translator->get('form.copq_title', 'Cost of Poor Quality (COPQ)'),
                    'show' => $atts['show_copq'] ?? true,
                    'fields' => array('internal_failure_cost', 'external_failure_cost'),
                    'color_scheme' => 'red'
                )
            ),
            'validation' => array(
                'real_time' => true,
                'show_tooltips' => true,
                'highlight_errors' => true
            ),
            'layout' => $atts['layout'] ?? 'two_column'
        );
    }
    
    /**
     * Get results component configuration
     * 
     * @param array $atts Attributes
     * @return array Results config
     */
    private function get_results_config($atts) {
        return array(
            'show_cogq_section' => $atts['show_cogq'] ?? true,
            'show_copq_section' => $atts['show_copq'] ?? true,
            'show_totals' => true,
            'show_percentages' => true,
            'show_ratios' => true,
            'card_layout' => 'grid',
            'animation' => 'fade-in',
            'cogq_config' => array(
                'color_scheme' => 'green',
                'icon' => 'check-circle',
                'title' => $this->translator->get('results.cogq_title', 'Cost of Good Quality'),
                'fields' => array(
                    'prevention_cost' => $this->translator->get('results.prevention_cost', 'Prevention Costs'),
                    'appraisal_cost' => $this->translator->get('results.appraisal_cost', 'Appraisal Costs'),
                    'total_cogq' => $this->translator->get('results.total_cogq', 'Total COGQ')
                )
            ),
            'copq_config' => array(
                'color_scheme' => 'red',
                'icon' => 'alert-circle',
                'title' => $this->translator->get('results.copq_title', 'Cost of Poor Quality'),
                'fields' => array(
                    'internal_failure' => $this->translator->get('results.internal_failure', 'Internal Failure'),
                    'external_failure' => $this->translator->get('results.external_failure', 'External Failure'),
                    'total_copq' => $this->translator->get('results.total_copq', 'Total COPQ')
                )
            ),
            'summary_config' => array(
                'show_total_quality_cost' => true,
                'show_roi_calculation' => true,
                'show_recommendations' => true
            )
        );
    }
    
    /**
     * Get charts component configuration
     * 
     * @param array $atts Attributes
     * @return array Charts config
     */
    private function get_charts_config($atts) {
        return array(
            'enabled_charts' => array('pie', 'bar', 'trend'),
            'chart_library' => 'chartjs',
            'responsive' => true,
            'animations' => true,
            'color_schemes' => array(
                'cogq' => array('#28a745', '#20c997', '#17a2b8'),
                'copq' => array('#dc3545', '#fd7e14', '#ffc107')
            ),
            'export_options' => array('png', 'svg', 'pdf')
        );
    }
    
    /**
     * Get global configuration
     * 
     * @param array $atts Attributes
     * @return array Global config
     */
    private function get_global_config($atts) {
        return array(
            'language' => $atts['language'] ?? 'auto',
            'currency' => $atts['currency'] ?? 'EUR',
            'debug_mode' => QCC_DEBUG,
            'nonce' => wp_create_nonce('qcc_calculation_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'force_legacy_mode' => $atts['force_legacy_mode'] ?? false
        );
    }
    
    /**
     * Determine rendering strategy based on config
     * 
     * @param array $atts Attributes
     * @param array $config Component configuration
     * @return string Strategy
     */
    private function determine_strategy($atts, $config) {
        // Force legacy mode if requested
        if ($config['global']['force_legacy_mode']) {
            return 'legacy_compatibility';
        }
        
        // Check if advanced features are used
        if ($this->uses_advanced_features($config)) {
            return 'modern_components';
        }
        
        // Default: Modern layout with legacy fallback
        return 'modern_layout';
    }
    
    /**
     * Check if configuration uses advanced features
     * 
     * @param array $config Configuration
     * @return bool True if advanced features detected
     */
    private function uses_advanced_features($config) {
        $advanced_indicators = array(
            $config['layout']['grid_columns'] > 2,
            $config['layout']['card_style'] === 'elevated',
            $config['results']['animation'] !== 'none',
            count($config['charts']['enabled_charts']) > 2
        );
        
        return in_array(true, $advanced_indicators);
    }
    
    /**
     * Execute the rendering process
     * 
     * @param string $strategy Rendering strategy
     * @param array $config Component configuration
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    private function execute_rendering($strategy, $config, $content) {
        switch ($strategy) {
            case 'modern_components':
                return $this->render_modern_components($config, $content);
                
            case 'modern_layout':
                return $this->render_modern_layout($config, $content);
                
            case 'legacy_compatibility':
                return $this->render_legacy_compatibility($config, $content);
                
            default:
                throw new Exception('Unknown rendering strategy: ' . $strategy);
        }
    }
    
    /**
     * Render using modern component system
     * 
     * @param array $config Configuration
     * @param string $content Content
     * @return string HTML
     */
    private function render_modern_components($config, $content) {
        // Use layout builder for complete component assembly
        return $this->layout_builder->build_complete_layout($config, $content);
    }
    
    /**
     * Render using modern layout with template router
     * 
     * @param array $config Configuration
     * @param string $content Content
     * @return string HTML
     */
    private function render_modern_layout($config, $content) {
        // Use template router for section-based rendering
        return $this->template_router->route_calculator_template($config, $content);
    }
    
    /**
     * Render with legacy compatibility
     * 
     * @param array $config Configuration
     * @param string $content Content
     * @return string HTML
     */
    private function render_legacy_compatibility($config, $content) {
        // Load legacy template with modern data
        $template_path = QCC_PLUGIN_PATH . 'templates/calculator.php';
        
        if (!file_exists($template_path)) {
            throw new Exception('Legacy template not found');
        }
        
        // Extract config for template
        extract($config);
        
        // Capture output
        ob_start();
        include $template_path;
        return ob_get_clean();
    }
    
    /**
     * Enqueue assets required for rendering
     * 
     * @param array $config Component configuration
     */
    private function enqueue_required_assets($config) {
        if ($this->asset_manager) {
            $this->asset_manager->enqueue_calculator_assets($config);
        }
    }
    
    /**
     * Enqueue conditional assets
     */
    public function enqueue_conditional_assets() {
        // Only enqueue if calculator is being used
        global $post;
        if ($post && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            wp_enqueue_script('qcc-calculator-modern', 
                QCC_PLUGIN_URL . 'assets/js/calculator-modern.js', 
                array('jquery'), 
                QCC_PLUGIN_VERSION, 
                true
            );
        }
    }
    
    /**
     * Filter renderer configuration
     * 
     * @param array $config Configuration
     * @return array Filtered configuration
     */
    public function filter_renderer_config($config) {
        // Allow themes and plugins to modify renderer config
        return apply_filters('qcc_shortcode_renderer_config', $config);
    }
    
    /**
     * Handle rendering errors
     * 
     * @param Exception $e Exception
     * @param array $atts Original attributes
     * @return string Error HTML
     */
    private function handle_rendering_error($e, $atts) {
        if (QCC_DEBUG) {
            error_log('QCC Shortcode Renderer Error: ' . $e->getMessage());
        }
        
        // Try to fallback to basic rendering
        try {
            return $this->render_error_fallback($atts, $e->getMessage());
        } catch (Exception $fallback_error) {
            return '<div class="qcc-renderer-error">Calculator rendering failed. Please check system configuration.</div>';
        }
    }
    
    /**
     * Render error fallback
     * 
     * @param array $atts Attributes
     * @param string $error_message Error message
     * @return string Fallback HTML
     */
    private function render_error_fallback($atts, $error_message) {
        return '<div class="qcc-error-fallback" style="max-width: 600px; margin: 20px auto; padding: 20px; border: 2px solid #ffc107; border-radius: 8px; background: #fff3cd; color: #856404;">
            <h4>' . $this->translator->get('error.rendering_failed', 'Calculator Rendering Issue') . '</h4>
            <p>' . $this->translator->get('error.fallback_message', 'The calculator encountered a rendering issue. Please refresh the page or contact support.') . '</p>
            ' . (QCC_DEBUG ? '<small>Debug: ' . esc_html($error_message) . '</small>' : '') . '
        </div>';
    }
    
    /**
     * Record performance statistics
     * 
     * @param float $start_time Start time
     * @param string $strategy Rendering strategy
     */
    private function record_performance_stats($start_time, $strategy) {
        $execution_time = microtime(true) - $start_time;
        
        $this->rendering_stats[] = array(
            'strategy' => $strategy,
            'execution_time' => $execution_time,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => time()
        );
        
        // Log performance if debug mode
        if (QCC_DEBUG && $execution_time > 1.0) {
            error_log(sprintf('QCC Renderer Performance: %s strategy took %.3f seconds', $strategy, $execution_time));
        }
    }
    
    /**
     * Get rendering statistics
     * 
     * @return array Statistics
     */
    public function get_rendering_stats() {
        return $this->rendering_stats;
    }
}
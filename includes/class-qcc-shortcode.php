<?php
/**
 * QCC Shortcode Handler - Clean & Focused
 * 
 * Lightweight shortcode registration and coordination.
 * Delegates rendering to specialized services for maintainability.
 *
 * @package QualityCostCalculator
 * @subpackage Shortcodes
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main shortcode handler - coordinating, not rendering
 */
class QCC_Shortcode {
    
    /**
     * @var QCC_Shortcode_Renderer Rendering service
     */
    private $renderer_service;
    
    /**
     * @var QCC_Fallback_Renderer Emergency fallback
     */
    private $fallback_renderer;
    
    /**
     * @var QCC_Translator Translation service
     */
    private $translator;
    
    /**
     * @var bool Modern systems availability
     */
    private $modern_systems_available = false;
    
    /**
     * Initialize shortcode system
     */
    public function __construct() {
        $this->init_services();
        $this->register_shortcodes();
    }
    
    /**
     * Initialize required services
     */
    private function init_services() {
        try {
            // Check if service container is available
            if (class_exists('QCC_Service_Container_Setup') && 
                QCC_Service_Container_Setup::is_initialized()) {
                
                $container = QCC_Service_Container_Setup::get_container();
                
                // Get modern services
                $this->renderer_service = $container->get('shortcode_renderer');
                $this->translator = $container->get('translator');
                $this->modern_systems_available = true;
                
                if (QCC_DEBUG) {
                    error_log('QCC Shortcode: Modern systems loaded successfully');
                }
                
            } else {
                // Fallback mode
                $this->init_fallback_mode();
            }
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Shortcode Service Init Error: ' . $e->getMessage());
            }
            $this->init_fallback_mode();
        }
    }
    
    /**
     * Initialize fallback mode
     */
    private function init_fallback_mode() {
        // Load basic translator
        if (class_exists('QCC_Translator')) {
            $this->translator = new QCC_Translator();
        }
        
        // Load fallback renderer
        $this->fallback_renderer = new QCC_Fallback_Renderer($this->translator);
        $this->modern_systems_available = false;
        
        if (QCC_DEBUG) {
            error_log('QCC Shortcode: Fallback mode activated');
        }
    }
    
    /**
     * Register WordPress shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('quality_cost_calculator', array($this, 'handle_shortcode'));
        add_shortcode('qcc_calculator', array($this, 'handle_shortcode'));
        add_shortcode('cost_quality_calculator', array($this, 'handle_shortcode'));
        
        // COGQ/COPQ specific shortcodes
        add_shortcode('cogq_calculator', array($this, 'handle_shortcode'));
        add_shortcode('copq_calculator', array($this, 'handle_shortcode'));
    }
    
    /**
     * Main shortcode handler - coordination only
     * 
     * @param array|string $atts Shortcode attributes
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    public function handle_shortcode($atts = array(), $content = '') {
        try {
            // Validate and normalize attributes
            $validated_atts = $this->validate_attributes($atts);
            
            // Determine rendering strategy
            $rendering_strategy = $this->determine_rendering_strategy($validated_atts);
            
            // Delegate to appropriate renderer
            return $this->delegate_rendering($rendering_strategy, $validated_atts, $content);
            
        } catch (Exception $e) {
            return $this->handle_error($e);
        }
    }
    
    /**
     * Validate and normalize shortcode attributes
     * 
     * @param array|string $atts Raw attributes
     * @return array Validated attributes
     */
    private function validate_attributes($atts) {
        // Default attributes
        $defaults = array(
            'language'          => 'auto',
            'currency'          => 'EUR',
            'units'             => 'pieces',
            'renderer'          => 'auto',
            'layout'            => 'two_column',
            'sections'          => 'header,controls,form,results,charts',
            'columns'           => 'auto',
            'style'             => 'default',
            'show_cogq'         => 'true',
            'show_copq'         => 'true',
            'modern'            => 'auto',
            'theme'             => 'auto'
        );
        
        // Merge with defaults
        $validated = shortcode_atts($defaults, $atts, 'quality_cost_calculator');
        
        // Validation rules
        $valid_languages = array('auto', 'en', 'de', 'fr', 'es', 'zh');
        $valid_currencies = array('EUR', 'USD', 'GBP', 'JPY', 'CNY');
        $valid_renderers = array('auto', 'modern', 'legacy', 'fallback');
        $valid_layouts = array('single_column', 'two_column', 'dashboard', 'compact');
        
        // Apply validation
        if (!in_array($validated['language'], $valid_languages)) {
            $validated['language'] = 'auto';
        }
        
        if (!in_array($validated['currency'], $valid_currencies)) {
            $validated['currency'] = 'EUR';
        }
        
        if (!in_array($validated['renderer'], $valid_renderers)) {
            $validated['renderer'] = 'auto';
        }
        
        if (!in_array($validated['layout'], $valid_layouts)) {
            $validated['layout'] = 'two_column';
        }
        
        // Boolean conversions
        $validated['show_cogq'] = $this->parse_boolean($validated['show_cogq']);
        $validated['show_copq'] = $this->parse_boolean($validated['show_copq']);
        
        return $validated;
    }
    
    /**
     * Determine optimal rendering strategy
     * 
     * @param array $atts Validated attributes
     * @return string Rendering strategy
     */
    private function determine_rendering_strategy($atts) {
        // URL parameter override (for testing)
        if (isset($_GET['qcc_renderer']) && current_user_can('manage_options')) {
            return sanitize_text_field($_GET['qcc_renderer']);
        }
        
        // Explicit renderer selection
        if ($atts['renderer'] !== 'auto') {
            return $atts['renderer'];
        }
        
        // Auto-detection logic
        if ($this->modern_systems_available) {
            // Check if advanced features are requested
            if ($this->requires_advanced_features($atts)) {
                return 'modern';
            }
            
            // Check admin preference
            $admin_preference = get_option('qcc_default_renderer', 'auto');
            if ($admin_preference === 'modern') {
                return 'modern';
            }
        }
        
        // Default: Use what's available
        return $this->modern_systems_available ? 'modern' : 'fallback';
    }
    
    /**
     * Check if attributes require advanced features
     * 
     * @param array $atts Attributes
     * @return bool True if advanced features needed
     */
    private function requires_advanced_features($atts) {
        $advanced_features = array(
            'layout' => array('dashboard', 'compact'),
            'sections' => array('header,controls,form,results,charts,export'),
            'columns' => array('3', '4'),
            'style' => array('modern', 'corporate', 'minimal')
        );
        
        foreach ($advanced_features as $key => $values) {
            if (isset($atts[$key]) && in_array($atts[$key], $values)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Delegate rendering to appropriate service
     * 
     * @param string $strategy Rendering strategy
     * @param array $atts Validated attributes
     * @param string $content Shortcode content
     * @return string Rendered HTML
     */
    private function delegate_rendering($strategy, $atts, $content) {
        switch ($strategy) {
            case 'modern':
                if ($this->renderer_service) {
                    return $this->renderer_service->render($atts, $content);
                }
                // Fallback if modern renderer failed
                return $this->fallback_renderer->render($atts, $content);
                
            case 'legacy':
                // Legacy mode through modern renderer (if available)
                if ($this->renderer_service) {
                    $atts['force_legacy_mode'] = true;
                    return $this->renderer_service->render($atts, $content);
                }
                return $this->fallback_renderer->render($atts, $content);
                
            case 'fallback':
            default:
                return $this->fallback_renderer->render($atts, $content);
        }
    }
    
    /**
     * Handle rendering errors
     * 
     * @param Exception $e Exception
     * @return string Error HTML
     */
    private function handle_error($e) {
        if (QCC_DEBUG) {
            error_log('QCC Shortcode Error: ' . $e->getMessage());
        }
        
        // Try emergency fallback
        try {
            if ($this->fallback_renderer) {
                return $this->fallback_renderer->render_emergency_fallback(array(), $e->getMessage());
            }
        } catch (Exception $fallback_error) {
            if (QCC_DEBUG) {
                error_log('QCC Fallback Error: ' . $fallback_error->getMessage());
            }
        }
        
        // Last resort: Static error message
        return '<div class="qcc-error" style="padding: 20px; border: 2px solid #dc3545; border-radius: 8px; background: #f8d7da; color: #721c24; text-align: center; max-width: 600px; margin: 20px auto;">
            <h3>Quality Cost Calculator - System Error</h3>
            <p>The calculator system encountered an error and cannot be displayed.</p>
            <small>Error Reference: ' . esc_html(substr($e->getMessage(), 0, 50)) . '</small>
        </div>';
    }
    
    /**
     * Parse boolean values from strings
     * 
     * @param string|bool $value Value to parse
     * @return bool Parsed boolean
     */
    private function parse_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower(trim($value));
        return in_array($value, array('true', '1', 'yes', 'on'));
    }
    
    /**
     * Get supported languages
     * 
     * @return array Supported language codes
     */
    public function get_supported_languages() {
        if ($this->translator) {
            return $this->translator->get_supported_languages();
        }
        
        return array('en', 'de'); // Fallback
    }
    
    /**
     * Get system status for debugging
     * 
     * @return array System status
     */
    public function get_system_status() {
        return array(
            'modern_systems_available' => $this->modern_systems_available,
            'renderer_service_loaded' => $this->renderer_service !== null,
            'fallback_renderer_loaded' => $this->fallback_renderer !== null,
            'translator_loaded' => $this->translator !== null,
            'service_container_available' => class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized()
        );
    }
}
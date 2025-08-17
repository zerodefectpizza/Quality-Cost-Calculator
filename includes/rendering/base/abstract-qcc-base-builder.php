<?php
/**
 * QCC Base Builder Abstract Class
 * 
 * Abstract base class für alle Builder-Components.
 * Erweitert QCC_Base_Component um builder-spezifische Funktionalitäten.
 * 
 * @package QualityCostCalculator
 * @subpackage Base
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Class QCC_Base_Builder
 * 
 * @since 3.0.0
 * @abstract
 */
abstract class QCC_Base_Builder extends QCC_Base_Component implements QCC_Builder {
    
    /**
     * Sub-Sections Definition
     * 
     * @since 3.0.0
     * @var array
     */
    protected $sub_sections = array();
    
    /**
     * Builder-Dependencies
     * 
     * @since 3.0.0
     * @var array
     */
    protected $dependencies = array(
        'services' => array('translator', 'validator', 'template_manager'),
        'molecules' => array(),
        'atoms' => array()
    );
    
    /**
     * Build-Modi
     * 
     * @since 3.0.0
     * @var array
     */
    protected $available_modes = array(
        'edit' => array(
            'description' => 'Full interactive editing mode',
            'features' => array('validation', 'help_text', 'live_calculation'),
            'sections' => array('all')
        ),
        'readonly' => array(
            'description' => 'Display-only mode',
            'features' => array('export', 'print'),
            'sections' => array('results', 'inputs')
        ),
        'preview' => array(
            'description' => 'Quick preview mode',
            'features' => array('basic_display'),
            'sections' => array('results')
        )
    );
    
    /**
     * Layout-Optionen
     * 
     * @since 3.0.0
     * @var array
     */
    protected $layout_options = array(
        'single-column' => array(
            'description' => 'Vertical stacked layout',
            'breakpoints' => array('mobile', 'tablet'),
            'sections_per_row' => 1
        ),
        'two-column' => array(
            'description' => 'Side-by-side layout',
            'breakpoints' => array('desktop'),
            'sections_per_row' => 2
        ),
        'grid' => array(
            'description' => 'Responsive grid layout',
            'breakpoints' => array('desktop', 'wide'),
            'sections_per_row' => 'auto'
        )
    );
    
    /**
     * Builder-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $builder_type = 'form';
    
    /**
     * Performance-Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    protected $performance_config = array(
        'cache_sections' => true,
        'cache_duration' => 3600,
        'lazy_load_sections' => array(),
        'preload_assets' => array(),
        'async_validation' => false
    );
    
    /**
     * Responsive-Breakpoints
     * 
     * @since 3.0.0
     * @var array
     */
    protected $responsive_breakpoints = array(
        'mobile' => array('max-width' => '767px'),
        'tablet' => array('min-width' => '768px', 'max-width' => '1023px'),
        'desktop' => array('min-width' => '1024px', 'max-width' => '1439px'),
        'wide' => array('min-width' => '1440px')
    );
    
    /**
     * Feature-Support
     * 
     * @since 3.0.0
     * @var array
     */
    protected $supported_features = array(
        'live_validation' => true,
        'drag_drop' => false,
        'section_reordering' => false,
        'custom_layouts' => true,
        'theme_overrides' => true
    );
    
    /**
     * Hook-Definitionen
     * 
     * @since 3.0.0
     * @var array
     */
    protected $hooks = array(
        'actions' => array(),
        'filters' => array()
    );
    
    /**
     * Build-Context
     * 
     * @since 3.0.0
     * @var array
     */
    protected $build_context = array();
    
    /**
     * Build-Metriken
     * 
     * @since 3.0.0
     * @var array
     */
    protected $build_metrics = array(
        'start_time' => 0,
        'sections_rendered' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'components_loaded' => 0
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_builder_properties();
        $this->register_hooks();
        $this->init_build_metrics();
    }
    
    /**
     * Initialisiert Builder-Eigenschaften
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_builder_properties() {
        $this->validate_sub_sections();
        $this->setup_default_hooks();
    }
    
    /**
     * Validiert Sub-Section-Definitionen
     * 
     * @since 3.0.0
     * @return void
     */
    protected function validate_sub_sections() {
        foreach ($this->sub_sections as $name => $definition) {
            if (!isset($definition['title']) || !isset($definition['molecules'])) {
                wp_die(sprintf(
                    __('Invalid sub-section definition for "%s" in builder "%s"', 'quality-cost-calculator'),
                    $name,
                    $this->component_name
                ));
            }
        }
    }
    
    /**
     * Setup Standard-Hooks
     * 
     * @since 3.0.0
     * @return void
     */
    protected function setup_default_hooks() {
        $builder_name = str_replace('-', '_', $this->component_name);
        
        $this->hooks['actions'] = array_merge($this->hooks['actions'], array(
            'qcc_' . $builder_name . '_before_build' => array('config'),
            'qcc_' . $builder_name . '_after_section' => array('section_name', 'html'),
            'qcc_' . $builder_name . '_after_build' => array('html', 'config')
        ));
        
        $this->hooks['filters'] = array_merge($this->hooks['filters'], array(
            'qcc_' . $builder_name . '_section_data' => array('data', 'section_name'),
            'qcc_' . $builder_name . '_final_html' => array('html', 'config')
        ));
    }
    
    /**
     * Initialisiert Build-Metriken
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_build_metrics() {
        $this->build_metrics['start_time'] = microtime(true);
    }
    
    /**
     * Builder-Rendering
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @param array $attributes HTML attributes
     * @return string Rendered HTML
     */
    public function render($data = array(), $attributes = array()) {
        // Standard-Config erstellen
        $config = array(
            'mode' => 'edit',
            'layout' => 'single-column',
            'sections' => array_keys($this->sub_sections),
            'validation' => true
        );
        
        return $this->build($config);
    }
    
    /**
     * Build-Prozess
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return string Complete section HTML
     */
    public function build($config) {
        // Config-Validierung
        $validation = $this->validate_config($config);
        if (is_wp_error($validation)) {
            return $this->handle_build_error($validation, $config);
        }
        
        // Performance-Optimierung
        $config = $this->optimize_build_performance($config);
        
        // Build-Context erstellen
        $this->build_context = $this->create_build_context($config);
        
        // Before build hook
        do_action('qcc_before_builder_build', $this, $config);
        
        // Cache-Check
        if ($this->performance_config['cache_sections']) {
            $cache_key = $this->get_cache_key($config);
            $cached = $this->cache_manager ? $this->cache_manager->get($cache_key) : false;
            if ($cached !== false) {
                $this->build_metrics['cache_hits']++;
                return $cached;
            }
            $this->build_metrics['cache_misses']++;
        }
        
        try {
            // Sections in korrekter Reihenfolge rendern
            $build_order = $this->get_build_order($config);
            $rendered_sections = array();
            
            foreach ($build_order as $section_name) {
                if (in_array($section_name, $config['sections'])) {
                    $section_data = $this->get_section_data($section_name, $config);
                    $rendered_sections[$section_name] = $this->render_section($section_name, $section_data, $config);
                    $this->build_metrics['sections_rendered']++;
                }
            }
            
            // Sections zu finalem HTML assemblieren
            $html = $this->assemble_builder_html($rendered_sections, $config);
            
            // Cache speichern
            if ($this->performance_config['cache_sections'] && $this->cache_manager) {
                $this->cache_manager->set($cache_key, $html, $this->performance_config['cache_duration']);
            }
            
            // After build hook
            do_action('qcc_after_builder_build', $this, $html, $config);
            
            return apply_filters('qcc_builder_final_html', $html, $config, $this);
            
        } catch (Exception $e) {
            return $this->handle_build_error(
                new WP_Error('build_exception', $e->getMessage()),
                $config
            );
        }
    }
    
    /**
     * Sub-Sections getter
     * 
     * @since 3.0.0
     * @return array Sub-section definitions
     */
    public function get_sub_sections() {
        return $this->sub_sections;
    }
    
    /**
     * Dependencies getter
     * 
     * @since 3.0.0
     * @return array Dependencies
     */
    public function get_dependencies() {
        return $this->dependencies;
    }
    
    /**
     * Build-Order ermitteln
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return array Ordered section names
     */
    public function get_build_order($config) {
        $sections = $this->sub_sections;
        
        // Nach Order-Eigenschaft sortieren
        uasort($sections, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });
        
        $order = array_keys($sections);
        
        // Mode-spezifische Anpassungen
        if ($config['mode'] === 'readonly') {
            // Results zuerst in readonly-mode
            if (in_array('results', $order)) {
                $order = array_diff($order, array('results'));
                array_unshift($order, 'results');
            }
        }
        
        return apply_filters('qcc_builder_build_order', $order, $config, $this);
    }
    
    /**
     * Section-Rendering
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $data Section data
     * @param array $config Build configuration
     * @return string Section HTML
     */
    public function render_section($section_name, $data, $config) {
        if (!isset($this->sub_sections[$section_name])) {
            return $this->handle_build_error(
                new WP_Error('section_not_found', 'Section not found: ' . $section_name),
                $config
            );
        }
        
        $section_def = $this->sub_sections[$section_name];
        
        // Before section hook
        do_action('qcc_before_builder_section', $section_name, $this, $data, $config);
        
        // Section-Template rendern
        $template_data = array(
            'section_name' => $section_name,
            'section_definition' => $section_def,
            'data' => $data,
            'config' => $config,
            'builder' => $this
        );
        
        $html = $this->render_section_template($section_name, $template_data);
        
        // After section hook
        do_action('qcc_after_builder_section', $section_name, $html, $this, $config);
        
        return apply_filters('qcc_builder_section_html', $html, $section_name, $config, $this);
    }
    
    /**
     * Section-Template-Rendering
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $template_data Template data
     * @return string Section HTML
     */
    protected function render_section_template($section_name, $template_data) {
        $template_path = 'builders/sections/' . $section_name . '.php';
        
        if ($this->template_manager && $this->template_manager->template_exists($template_path)) {
            return $this->template_manager->render($template_path, $template_data);
        }
        
        return $this->render_section_fallback($section_name, $template_data);
    }
    
    /**
     * Section-Fallback-Rendering
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $template_data Template data
     * @return string Fallback HTML
     */
    protected function render_section_fallback($section_name, $template_data) {
        $section_def = $template_data['section_definition'];
        
        return sprintf(
            '<section class="qcc-builder-section qcc-section-%s">
                <h3>%s</h3>
                <div class="qcc-section-content">%s</div>
            </section>',
            esc_attr($section_name),
            esc_html($section_def['title']),
            __('Section template not available', 'quality-cost-calculator')
        );
    }
    
    /**
     * Section-Data ermitteln
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $config Build configuration
     * @return array Section data
     */
    protected function get_section_data($section_name, $config) {
        $data = array(
            'section_name' => $section_name,
            'config' => $config
        );
        
        return apply_filters('qcc_builder_section_data', $data, $section_name, $this);
    }
    
    /**
     * Builder-HTML assemblieren
     * 
     * @since 3.0.0
     * @param array $rendered_sections Rendered sections
     * @param array $config Build configuration
     * @return string Complete builder HTML
     */
    protected function assemble_builder_html($rendered_sections, $config) {
        $accessibility = $this->create_accessibility_structure($config);
        
        $container_attrs = array_merge(
            array(
                'class' => $this->get_builder_css_classes($config),
                'data-builder' => $this->component_name,
                'data-builder-type' => $this->builder_type,
                'data-mode' => $config['mode'],
                'data-layout' => $config['layout']
            ),
            $accessibility['container_attributes']
        );
        
        $attr_string = '';
        foreach ($container_attrs as $key => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<div%s>', $attr_string);
        
        foreach ($rendered_sections as $section_name => $section_html) {
            $html .= $section_html;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Builder-CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return string CSS classes
     */
    protected function get_builder_css_classes($config) {
        $classes = array(
            'qcc-builder',
            'qcc-builder--' . $this->builder_type,
            'qcc-builder--' . $config['mode'],
            'qcc-builder--' . $config['layout']
        );
        
        return implode(' ', $classes);
    }
    
    /**
     * Config-Validierung
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return true|WP_Error
     */
    public function validate_config($config) {
        if (!isset($config['mode']) || !isset($this->available_modes[$config['mode']])) {
            return new WP_Error('invalid_mode', 'Invalid build mode');
        }
        
        if (!isset($config['layout']) || !isset($this->layout_options[$config['layout']])) {
            return new WP_Error('invalid_layout', 'Invalid layout option');
        }
        
        if (!isset($config['sections']) || !is_array($config['sections'])) {
            return new WP_Error('invalid_sections', 'Sections must be array');
        }
        
        return true;
    }
    
    /**
     * Available modes getter
     * 
     * @since 3.0.0
     * @return array Available modes
     */
    public function get_available_modes() {
        return $this->available_modes;
    }
    
    /**
     * Layout options getter
     * 
     * @since 3.0.0
     * @return array Layout options
     */
    public function get_layout_options() {
        return $this->layout_options;
    }
    
    /**
     * Hooks getter
     * 
     * @since 3.0.0
     * @return array Hook definitions
     */
    public function get_hooks() {
        return $this->hooks;
    }
    
    /**
     * Hooks registrieren
     * 
     * @since 3.0.0
     * @return void
     */
    public function register_hooks() {
        // Implementierung in Child-Classes falls benötigt
    }
    
    /**
     * Performance-Config getter
     * 
     * @since 3.0.0
     * @return array Performance configuration
     */
    public function get_performance_config() {
        return $this->performance_config;
    }
    
    /**
     * Build-Context erstellen
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return array Build context
     */
    public function create_build_context($config) {
        return array(
            'builder_name' => $this->component_name,
            'builder_type' => $this->builder_type,
            'build_time' => current_time('timestamp'),
            'user_id' => get_current_user_id(),
            'user_capabilities' => $this->get_user_capabilities(),
            'screen_size' => $this->detect_screen_size(),
            'language' => get_locale(),
            'theme_support' => $this->check_theme_support(),
            'config' => $config
        );
    }
    
    /**
     * User-Capabilities ermitteln
     * 
     * @since 3.0.0
     * @return array User capabilities
     */
    protected function get_user_capabilities() {
        $user = wp_get_current_user();
        return $user ? $user->allcaps : array();
    }
    
    /**
     * Screen-Size detection
     * 
     * @since 3.0.0
     * @return string Screen size
     */
    protected function detect_screen_size() {
        // Vereinfachte Detection - in realem Code über JavaScript
        return wp_is_mobile() ? 'mobile' : 'desktop';
    }
    
    /**
     * Theme-Support prüfen
     * 
     * @since 3.0.0
     * @return array Theme support info
     */
    protected function check_theme_support() {
        return array(
            'qcc_templates' => $this->has_theme_override(),
            'responsive' => current_theme_supports('responsive-embeds'),
            'accessibility' => current_theme_supports('accessibility-ready')
        );
    }
    
    /**
     * Build-Performance optimieren
     * 
     * @since 3.0.0
     * @param array $config Original configuration
     * @return array Optimized configuration
     */
    public function optimize_build_performance($config) {
        // Nicht benötigte Sections entfernen
        $mode_sections = $this->available_modes[$config['mode']]['sections'];
        if ($mode_sections !== array('all')) {
            $config['sections'] = array_intersect($config['sections'], $mode_sections);
        }
        
        // Lazy-loading für große Sections
        foreach ($this->performance_config['lazy_load_sections'] as $section) {
            if (in_array($section, $config['sections'])) {
                $config['lazy_sections'][] = $section;
            }
        }
        
        return $config;
    }
    
    /**
     * Build-Error-Handling
     * 
     * @since 3.0.0
     * @param WP_Error $error Error details
     * @param array $config Build configuration
     * @return string Fallback HTML
     */
    public function handle_build_error($error, $config) {
        $error_html = sprintf(
            '<div class="qcc-builder-error" data-builder="%s">',
            esc_attr($this->component_name)
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $error_html .= sprintf(
                '<strong>%s:</strong> %s',
                esc_html($error->get_error_code()),
                esc_html($error->get_error_message())
            );
        } else {
            $error_html .= __('Builder could not be rendered', 'quality-cost-calculator');
        }
        
        $error_html .= '</div>';
        
        return $error_html;
    }
    
    /**
     * Builder-Type getter
     * 
     * @since 3.0.0
     * @return string Builder type
     */
    public function get_builder_type() {
        return $this->builder_type;
    }
    
    /**
     * Feature-Support-Prüfung
     * 
     * @since 3.0.0
     * @param string $feature Feature name
     * @return bool True wenn unterstützt
     */
    public function supports_feature($feature) {
        return isset($this->supported_features[$feature]) && $this->supported_features[$feature];
    }
    
    /**
     * Responsive-Breakpoints getter
     * 
     * @since 3.0.0
     * @return array Breakpoint definitions
     */
    public function get_responsive_breakpoints() {
        return $this->responsive_breakpoints;
    }
    
    /**
     * Accessibility-Struktur erstellen
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return array Accessibility structure
     */
    public function create_accessibility_structure($config) {
        return array(
            'container_attributes' => array(
                'role' => 'main',
                'aria-label' => sprintf(__('%s Calculator', 'quality-cost-calculator'), $this->component_name),
                'aria-describedby' => $this->component_name . '-instructions'
            ),
            'section_attributes' => array(
                'role' => 'region',
                'aria-labelledby' => 'section-heading'
            ),
            'navigation' => array(
                'skip_links' => true,
                'section_navigation' => true
            )
        );
    }
    
    /**
     * Builder-Metriken getter
     * 
     * @since 3.0.0
     * @return array Builder metrics
     */
    public function get_builder_metrics() {
        $this->build_metrics['build_time'] = microtime(true) - $this->build_metrics['start_time'];
        $this->build_metrics['memory_usage'] = memory_get_peak_usage(true);
        
        return $this->build_metrics;
    }
    
    /**
     * Required fields für Builders
     * 
     * @since 3.0.0
     * @return array Required fields
     */
    protected function get_required_fields() {
        return array();
    }
    
    /**
     * Assets mit Dependencies
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $builder_assets = array(
            'css' => array($this->component_name . '.css', 'builders.css'),
            'js' => array($this->component_name . '.js', 'builders.js'),
            'dependencies' => array('qcc-builders', 'qcc-molecules', 'qcc-atoms')
        );
        
        return array_merge_recursive($base_assets, $builder_assets);
    }
}
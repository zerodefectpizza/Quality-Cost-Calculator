<?php
/**
 * QCC Base Component Abstract Class
 * 
 * Abstract base class für alle Rendering-Components im Quality Cost Calculator.
 * Stellt gemeinsame Funktionalitäten und Standard-Implementierungen bereit.
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
 * Abstract Class QCC_Base_Component
 * 
 * Gemeinsame Basis für alle Rendering-Components (Atoms, Molecules, Builders).
 * Implementiert Standard-Funktionalitäten die alle Components benötigen.
 * 
 * @since 3.0.0
 * @abstract
 */
abstract class QCC_Base_Component implements QCC_Renderable, QCC_Templatable {
    
    /**
     * Service-Container für Dependency-Injection
     * 
     * @since 3.0.0
     * @var object
     */
    protected $service_container;
    
    /**
     * Translation-Service
     * 
     * @since 3.0.0
     * @var QCC_Translation_Service
     */
    protected $translator;
    
    /**
     * Template-Manager
     * 
     * @since 3.0.0
     * @var QCC_Template_Manager
     */
    protected $template_manager;
    
    /**
     * Asset-Manager
     * 
     * @since 3.0.0
     * @var QCC_Asset_Manager
     */
    protected $asset_manager;
    
    /**
     * Cache-Manager
     * 
     * @since 3.0.0
     * @var QCC_Cache_Manager
     */
    protected $cache_manager;
    
    /**
     * Component-Name (auto-generiert aus Klassenname)
     * 
     * @since 3.0.0
     * @var string
     */
    protected $component_name;
    
    /**
     * Component-Typ (atom/molecule/builder)
     * 
     * @since 3.0.0
     * @var string
     */
    protected $component_type;
    
    /**
     * Template-Pfad
     * 
     * @since 3.0.0
     * @var string
     */
    protected $template_path;
    
    /**
     * Ob Component cacheable ist
     * 
     * @since 3.0.0
     * @var bool
     */
    protected $is_cacheable = true;
    
    /**
     * Cache-Duration in Sekunden
     * 
     * @since 3.0.0
     * @var int
     */
    protected $cache_duration = 3600;
    
    /**
     * Constructor
     * 
     * Initialisiert Service-Dependencies und Component-Eigenschaften.
     * 
     * @since 3.0.0
     */
    public function __construct() {
        $this->init_services();
        $this->init_component_properties();
        $this->register_template_hooks();
    }
    
    /**
     * Initialisiert Service-Dependencies
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_services() {
        try {
            $this->service_container = QCC_Service_Container::get_instance();
            $this->translator = $this->service_container->get('translator');
            $this->template_manager = $this->service_container->get('template_manager');
            $this->asset_manager = $this->service_container->get('asset_manager');
            $this->cache_manager = $this->service_container->get('cache_manager');
        } catch (Exception $e) {
            // Fallback für Tests oder wenn Services nicht verfügbar
            $this->init_fallback_services();
        }
    }
    
    /**
     * Initialisiert Fallback-Services
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_fallback_services() {
        $this->translator = null;
        $this->template_manager = null;
        $this->asset_manager = null;
        $this->cache_manager = null;
    }
    
    /**
     * Initialisiert Component-Eigenschaften
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_component_properties() {
        $this->component_name = $this->generate_component_name();
        $this->component_type = $this->detect_component_type();
        $this->template_path = $this->generate_template_path();
    }
    
    /**
     * Generiert Component-Name aus Klassenname
     * 
     * @since 3.0.0
     * @return string Component name in kebab-case
     */
    protected function generate_component_name() {
        $class_name = get_class($this);
        $name = str_replace('QCC_', '', $class_name);
        $name = str_replace('_', '-', $name);
        return strtolower($name);
    }
    
    /**
     * Erkennt Component-Typ aus Klassenname
     * 
     * @since 3.0.0
     * @return string Component type (atom/molecule/builder)
     */
    protected function detect_component_type() {
        $class_name = get_class($this);
        
        if (strpos($class_name, '_Atom') !== false || 
            strpos($class_name, '_Input') !== false || 
            strpos($class_name, '_Button') !== false ||
            strpos($class_name, '_Label') !== false) {
            return 'atom';
        }
        
        if (strpos($class_name, '_Molecule') !== false ||
            strpos($class_name, '_Group') !== false ||
            strpos($class_name, '_Panel') !== false) {
            return 'molecule';
        }
        
        if (strpos($class_name, '_Builder') !== false) {
            return 'builder';
        }
        
        return 'component';
    }
    
    /**
     * Generiert Template-Pfad
     * 
     * @since 3.0.0
     * @return string Template file path
     */
    protected function generate_template_path() {
        return sprintf(
            '%s/%s.php',
            $this->component_type . 's',
            $this->component_name
        );
    }
    
    /**
     * Standard Cache-Key-Generierung
     * 
     * @since 3.0.0
     * @param array $data Component-Daten
     * @return string Unique cache key
     */
    public function get_cache_key($data) {
        $key_parts = array(
            'qcc',
            $this->component_type,
            $this->component_name,
            md5(serialize($data)),
            get_locale(),
            get_current_blog_id()
        );
        
        return implode('_', $key_parts);
    }
    
    /**
     * Standard Asset-Definition
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        return array(
            'css' => array(),
            'js' => array(),
            'dependencies' => array('qcc-core')
        );
    }
    
    /**
     * Standard Data-Validation
     * 
     * @since 3.0.0
     * @param array $data Component-Daten
     * @return true|WP_Error
     */
    public function validate_data($data) {
        if (!is_array($data)) {
            return new WP_Error(
                'invalid_data_type',
                __('Component data must be an array', 'quality-cost-calculator'),
                array('component' => $this->component_name)
            );
        }
        
        $required_fields = $this->get_required_fields();
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(__('Required field "%s" is missing', 'quality-cost-calculator'), $field),
                    array('field' => $field, 'component' => $this->component_name)
                );
            }
        }
        
        return $this->validate_field_values($data);
    }
    
    /**
     * Validiert Feld-Werte (überschreibbar in Child-Classes)
     * 
     * @since 3.0.0
     * @param array $data Component-Daten
     * @return true|WP_Error
     */
    protected function validate_field_values($data) {
        return true;
    }
    
    /**
     * Standard Input-Sanitization
     * 
     * @since 3.0.0
     * @param mixed $value Input-Wert
     * @return mixed Sanitized value
     */
    public function sanitize_input($value) {
        if (is_string($value)) {
            return sanitize_text_field($value);
        }
        
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_array($value)) {
            return array_map(array($this, 'sanitize_input'), $value);
        }
        
        return $value;
    }
    
    /**
     * Bereitet Template-Daten vor
     * 
     * @since 3.0.0
     * @param array $data Raw component data
     * @param array $attributes HTML attributes
     * @return array Prepared template data
     */
    public function prepare_template_data($data, $attributes) {
        $template_data = array(
            'component_name' => $this->component_name,
            'component_type' => $this->component_type,
            'data' => $this->sanitize_template_data($data),
            'attributes' => $this->prepare_attributes($attributes),
            'css_classes' => $this->get_css_classes($data),
            'unique_id' => $this->generate_unique_id($data),
            'translator' => $this->translator
        );
        
        return apply_filters('qcc_prepare_template_data', $template_data, $this);
    }
    
    /**
     * Sanitisiert Template-Daten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @return array Sanitized data
     */
    protected function sanitize_template_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            $sanitized[sanitize_key($key)] = $this->sanitize_input($value);
        }
        
        return $sanitized;
    }
    
    /**
     * Bereitet HTML-Attribute vor
     * 
     * @since 3.0.0
     * @param array $attributes Raw attributes
     * @return array Prepared attributes
     */
    protected function prepare_attributes($attributes) {
        $prepared = array();
        
        foreach ($attributes as $key => $value) {
            $sanitized_key = sanitize_key($key);
            
            if ($sanitized_key === 'class') {
                $prepared[$sanitized_key] = sanitize_html_class($value);
            } elseif ($sanitized_key === 'id') {
                $prepared[$sanitized_key] = sanitize_html_class($value);
            } else {
                $prepared[$sanitized_key] = esc_attr($value);
            }
        }
        
        return $prepared;
    }
    
    /**
     * Generiert CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string CSS classes
     */
    public function get_css_classes($data) {
        $classes = array(
            'qcc-component',
            'qcc-' . $this->component_type,
            'qcc-' . $this->component_name
        );
        
        // Status-basierte Klassen
        if (isset($data['error']) && $data['error']) {
            $classes[] = 'qcc-error';
        }
        
        if (isset($data['required']) && $data['required']) {
            $classes[] = 'qcc-required';
        }
        
        if (isset($data['disabled']) && $data['disabled']) {
            $classes[] = 'qcc-disabled';
        }
        
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Generiert unique ID
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Unique ID
     */
    protected function generate_unique_id($data) {
        if (isset($data['id'])) {
            return sanitize_html_class($data['id']);
        }
        
        return sprintf(
            'qcc-%s-%s',
            $this->component_name,
            substr(uniqid(), -6)
        );
    }
    
    /**
     * Error-Handling
     * 
     * @since 3.0.0
     * @param WP_Error $error Error object
     * @return string Error HTML
     */
    public function handle_error($error) {
        $error_html = sprintf(
            '<div class="qcc-component-error qcc-error" data-component="%s">',
            esc_attr($this->component_name)
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $error_html .= sprintf(
                '<strong>%s:</strong> %s',
                esc_html($error->get_error_code()),
                esc_html($error->get_error_message())
            );
        } else {
            $error_html .= __('Component could not be rendered', 'quality-cost-calculator');
        }
        
        $error_html .= '</div>';
        
        return $error_html;
    }
    
    /**
     * Component-Name getter
     * 
     * @since 3.0.0
     * @return string Component name
     */
    public function get_component_name() {
        return $this->component_name;
    }
    
    /**
     * Component-Typ getter
     * 
     * @since 3.0.0
     * @return string Component type
     */
    public function get_component_type() {
        return $this->component_type;
    }
    
    /**
     * Cacheable-Status getter
     * 
     * @since 3.0.0
     * @return bool True wenn cacheable
     */
    public function is_cacheable() {
        return $this->is_cacheable;
    }
    
    /**
     * Template-Name getter
     * 
     * @since 3.0.0
     * @return string Template name
     */
    public function get_template_name() {
        return $this->template_path;
    }
    
    // QCC_Templatable Interface Implementation
    
    /**
     * Template-Pfad getter
     * 
     * @since 3.0.0
     * @return string Template path
     */
    public function get_template_path() {
        return $this->template_path;
    }
    
    /**
     * Template-Variablen
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array Template variables
     */
    public function get_template_vars($data) {
        return $this->prepare_template_data($data, array());
    }
    
    /**
     * Template-Existenz prüfen
     * 
     * @since 3.0.0
     * @return bool True wenn Template existiert
     */
    public function template_exists() {
        return $this->template_manager && 
               $this->template_manager->template_exists($this->template_path);
    }
    
    /**
     * Fallback-Template
     * 
     * @since 3.0.0
     * @return string Fallback template path
     */
    public function get_fallback_template() {
        return 'components/fallback.php';
    }
    
    /**
     * Template-Rendering
     * 
     * @since 3.0.0
     * @param array $data Template data
     * @return string Rendered HTML
     */
    public function render_template($data) {
        if (!$this->template_manager) {
            return $this->render_fallback_html($data);
        }
        
        $template_vars = $this->get_template_vars($data);
        
        if ($this->has_theme_override()) {
            return $this->template_manager->render($this->get_theme_override_path(), $template_vars);
        }
        
        if ($this->template_exists()) {
            return $this->template_manager->render($this->template_path, $template_vars);
        }
        
        return $this->template_manager->render($this->get_fallback_template(), $template_vars);
    }
    
    /**
     * Theme-Override-Pfad
     * 
     * @since 3.0.0
     * @return string Theme override path
     */
    public function get_theme_override_path() {
        return sprintf(
            'qcc-templates/%s',
            $this->template_path
        );
    }
    
    /**
     * Theme-Override-Existenz
     * 
     * @since 3.0.0
     * @return bool True wenn Override existiert
     */
    public function has_theme_override() {
        $theme_path = get_template_directory() . '/' . $this->get_theme_override_path();
        return file_exists($theme_path);
    }
    
    /**
     * Template-Hooks
     * 
     * @since 3.0.0
     * @return array Hook definitions
     */
    public function get_template_hooks() {
        return array(
            'before_render' => 'qcc_before_component_render',
            'after_render' => 'qcc_after_component_render',
            'template_vars' => 'qcc_component_template_vars'
        );
    }
    
    /**
     * Template-Hooks registrieren
     * 
     * @since 3.0.0
     * @return void
     */
    public function register_template_hooks() {
        // Implementierung in Child-Classes falls benötigt
    }
    
    /**
     * Template-Context
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array Template context
     */
    public function get_template_context($data) {
        return array(
            'component' => $this,
            'data' => $data,
            'wp_context' => array(
                'locale' => get_locale(),
                'is_admin' => is_admin(),
                'current_user_id' => get_current_user_id()
            )
        );
    }
    
    /**
     * Template-Cache-Prüfung
     * 
     * @since 3.0.0
     * @param string $cache_key Cache key
     * @return bool True wenn cacheable
     */
    public function is_template_cacheable($cache_key) {
        return $this->is_cacheable && !is_user_logged_in();
    }
    
    /**
     * Template-Cache-Key
     * 
     * @since 3.0.0
     * @param array $data Template data
     * @return string Cache key
     */
    public function get_template_cache_key($data) {
        return $this->get_cache_key($data) . '_template';
    }
    
    /**
     * Fallback-HTML-Rendering (überschreibbar)
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Fallback HTML
     */
    protected function render_fallback_html($data) {
        return sprintf(
            '<div class="qcc-fallback" data-component="%s">%s</div>',
            esc_attr($this->component_name),
            __('Component template not available', 'quality-cost-calculator')
        );
    }
    
    /**
     * Required fields (überschreibbar in Child-Classes)
     * 
     * @since 3.0.0
     * @return array Required field names
     */
    abstract protected function get_required_fields();
}
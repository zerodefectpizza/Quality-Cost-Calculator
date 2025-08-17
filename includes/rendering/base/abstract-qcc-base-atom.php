<?php
/**
 * QCC Base Atom Abstract Class
 * 
 * Abstract base class für alle Atom-Components.
 * Erweitert QCC_Base_Component um atom-spezifische Funktionalitäten.
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
 * Abstract Class QCC_Base_Atom
 * 
 * @since 3.0.0
 * @abstract
 */
abstract class QCC_Base_Atom extends QCC_Base_Component implements QCC_Atom {
    
    /**
     * Standard HTML-Attribute
     * 
     * @since 3.0.0
     * @var array
     */
    protected $default_attributes = array();
    
    /**
     * Basis-CSS-Klasse
     * 
     * @since 3.0.0
     * @var string
     */
    protected $base_css_class = '';
    
    /**
     * Input-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $input_type = 'text';
    
    /**
     * Ob Atom interaktiv ist
     * 
     * @since 3.0.0
     * @var bool
     */
    protected $is_interactive = true;
    
    /**
     * Required fields
     * 
     * @since 3.0.0
     * @var array
     */
    protected $required_fields = array('id', 'name');
    
    /**
     * Optional fields mit Defaults
     * 
     * @since 3.0.0
     * @var array
     */
    protected $optional_fields = array(
        'value' => '',
        'placeholder' => '',
        'description' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false
    );
    
    /**
     * Validation-Rules für Frontend
     * 
     * @since 3.0.0
     * @var array
     */
    protected $validation_rules = array();
    
    /**
     * JavaScript-Events
     * 
     * @since 3.0.0
     * @var array
     */
    protected $javascript_events = array();
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_atom_properties();
    }
    
    /**
     * Initialisiert Atom-Eigenschaften
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_atom_properties() {
        if (empty($this->base_css_class)) {
            $this->base_css_class = 'qcc-' . $this->component_name;
        }
        
        $this->default_attributes = array_merge(array(
            'class' => $this->base_css_class,
            'type' => $this->input_type
        ), $this->default_attributes);
    }
    
    /**
     * Atom-Rendering mit Caching
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @param array $attributes HTML attributes
     * @return string Rendered HTML
     */
    public function render($data = array(), $attributes = array()) {
        // Data-Validation
        $validation = $this->validate_data($data);
        if (is_wp_error($validation)) {
            return $this->handle_error($validation);
        }
        
        // Merge optional fields
        $data = array_merge($this->optional_fields, $data);
        
        // Cache-Check
        if ($this->is_cacheable) {
            $cache_key = $this->get_cache_key($data);
            $cached = $this->cache_manager ? $this->cache_manager->get($cache_key) : false;
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Before render hook
        do_action('qcc_before_atom_render', $this, $data);
        
        // Template-Rendering
        $html = $this->render_template($data);
        
        // Fallback wenn Template fehlschlägt
        if (empty($html)) {
            $html = $this->render_fallback_html($data);
        }
        
        // Cache speichern
        if ($this->is_cacheable && $this->cache_manager) {
            $this->cache_manager->set($cache_key, $html, $this->cache_duration);
        }
        
        // After render hook
        do_action('qcc_after_atom_render', $this, $html, $data);
        
        return $html;
    }
    
    /**
     * Standard-Attribute getter
     * 
     * @since 3.0.0
     * @return array Default attributes
     */
    public function get_default_attributes() {
        return $this->default_attributes;
    }
    
    /**
     * Required fields getter
     * 
     * @since 3.0.0
     * @return array Required field names
     */
    public function get_required_fields() {
        return $this->required_fields;
    }
    
    /**
     * Optional fields getter
     * 
     * @since 3.0.0
     * @return array Optional fields mit defaults
     */
    public function get_optional_fields() {
        return $this->optional_fields;
    }
    
    /**
     * Basis-CSS-Klasse getter
     * 
     * @since 3.0.0
     * @return string Base CSS class
     */
    public function get_base_css_class() {
        return $this->base_css_class;
    }
    
    /**
     * Status-CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array Status CSS classes
     */
    public function get_status_css_classes($data) {
        $classes = array();
        
        if (!empty($data['error'])) {
            $classes[] = 'qcc-error';
            $classes[] = 'qcc-invalid';
        }
        
        if (!empty($data['required'])) {
            $classes[] = 'qcc-required';
        }
        
        if (!empty($data['disabled'])) {
            $classes[] = 'qcc-disabled';
        }
        
        if (!empty($data['readonly'])) {
            $classes[] = 'qcc-readonly';
        }
        
        if (!empty($data['success'])) {
            $classes[] = 'qcc-success';
            $classes[] = 'qcc-valid';
        }
        
        return $classes;
    }
    
    /**
     * CSS-Klassen mit Status
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Complete CSS classes
     */
    public function get_css_classes($data) {
        $base_classes = parent::get_css_classes($data);
        $status_classes = implode(' ', $this->get_status_css_classes($data));
        
        return trim($base_classes . ' ' . $status_classes);
    }
    
    /**
     * Einzelwert-Validierung
     * 
     * @since 3.0.0
     * @param mixed $value Value to validate
     * @param string $field_name Field context
     * @return true|WP_Error
     */
    public function validate_value($value, $field_name) {
        // Empty value check für required fields
        if (empty($value) && in_array($field_name, $this->required_fields)) {
            return new WP_Error(
                'required_field_empty',
                sprintf(__('Field "%s" is required', 'quality-cost-calculator'), $field_name)
            );
        }
        
        return $this->validate_field_specific($value, $field_name);
    }
    
    /**
     * Field-spezifische Validierung (überschreibbar)
     * 
     * @since 3.0.0
     * @param mixed $value Value to validate
     * @param string $field_name Field context
     * @return true|WP_Error
     */
    protected function validate_field_specific($value, $field_name) {
        return true;
    }
    
    /**
     * Wert-Formatierung für Display
     * 
     * @since 3.0.0
     * @param mixed $value Raw value
     * @param string $field_name Field context
     * @param array $options Formatting options
     * @return string Formatted value
     */
    public function format_value($value, $field_name, $options = array()) {
        if (empty($value)) {
            return '';
        }
        
        return $this->format_field_specific($value, $field_name, $options);
    }
    
    /**
     * Field-spezifische Formatierung (überschreibbar)
     * 
     * @since 3.0.0
     * @param mixed $value Raw value
     * @param string $field_name Field context
     * @param array $options Formatting options
     * @return string Formatted value
     */
    protected function format_field_specific($value, $field_name, $options = array()) {
        return esc_html($value);
    }
    
    /**
     * Input-Parsing
     * 
     * @since 3.0.0
     * @param string $input User input
     * @param string $field_name Field context
     * @return mixed Parsed value
     */
    public function parse_input($input, $field_name) {
        if (empty($input)) {
            return '';
        }
        
        return $this->parse_field_specific($input, $field_name);
    }
    
    /**
     * Field-spezifisches Input-Parsing (überschreibbar)
     * 
     * @since 3.0.0
     * @param string $input User input
     * @param string $field_name Field context
     * @return mixed Parsed value
     */
    protected function parse_field_specific($input, $field_name) {
        return sanitize_text_field($input);
    }
    
    /**
     * Input-Typ getter
     * 
     * @since 3.0.0
     * @return string Input type
     */
    public function get_input_type() {
        return $this->input_type;
    }
    
    /**
     * Interactive-Status getter
     * 
     * @since 3.0.0
     * @return bool True wenn interactive
     */
    public function is_interactive() {
        return $this->is_interactive;
    }
    
    /**
     * JavaScript-Events getter
     * 
     * @since 3.0.0
     * @return array Event definitions
     */
    public function get_javascript_events() {
        return array_merge(array(
            'change' => 'qcc_' . str_replace('-', '_', $this->component_name) . '_changed',
            'blur' => 'qcc_validate_field',
            'focus' => 'qcc_highlight_field'
        ), $this->javascript_events);
    }
    
    /**
     * Accessibility-Attribute
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array ARIA attributes
     */
    public function get_accessibility_attributes($data) {
        $attributes = array();
        
        if (!empty($data['label'])) {
            $attributes['aria-label'] = $data['label'];
        }
        
        if (!empty($data['required'])) {
            $attributes['aria-required'] = 'true';
        }
        
        if (!empty($data['description'])) {
            $attributes['aria-describedby'] = $data['id'] . '-description';
        }
        
        if (!empty($data['error'])) {
            $attributes['aria-invalid'] = 'true';
            $attributes['aria-describedby'] = $data['id'] . '-error';
        }
        
        return $attributes;
    }
    
    /**
     * Help-Text generieren
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Help text HTML
     */
    public function get_help_text($data) {
        if (empty($data['description'])) {
            return '';
        }
        
        return sprintf(
            '<small class="qcc-help-text" id="%s-description">%s</small>',
            esc_attr($data['id']),
            esc_html($data['description'])
        );
    }
    
    /**
     * Frontend-Validation-Rules
     * 
     * @since 3.0.0
     * @return array Validation rules
     */
    public function get_frontend_validation_rules() {
        $rules = array(
            'type' => $this->input_type
        );
        
        if (in_array('value', $this->required_fields)) {
            $rules['required'] = true;
        }
        
        return array_merge($rules, $this->validation_rules);
    }
    
    /**
     * Unique ID generieren
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Unique ID
     */
    public function generate_unique_id($data) {
        if (!empty($data['id'])) {
            return sanitize_html_class($data['id']);
        }
        
        $base = !empty($data['name']) ? $data['name'] : $this->component_name;
        
        return sprintf(
            'qcc-%s-%s',
            sanitize_html_class($base),
            substr(uniqid(), -6)
        );
    }
    
    /**
     * Template-Daten für Atoms vorbereiten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @param array $attributes HTML attributes
     * @return array Template data
     */
    public function prepare_template_data($data, $attributes) {
        $base_data = parent::prepare_template_data($data, $attributes);
        
        // Merge default attributes
        $merged_attributes = array_merge(
            $this->get_default_attributes(),
            $attributes,
            $this->get_accessibility_attributes($data)
        );
        
        $atom_data = array(
            'input_type' => $this->get_input_type(),
            'is_interactive' => $this->is_interactive(),
            'validation_rules' => $this->get_frontend_validation_rules(),
            'javascript_events' => $this->get_javascript_events(),
            'help_text' => $this->get_help_text($data),
            'formatted_value' => $this->format_value($data['value'] ?? '', 'value'),
            'merged_attributes' => $merged_attributes
        );
        
        return array_merge($base_data, $atom_data);
    }
    
    /**
     * Fallback-HTML für Atoms
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Fallback HTML
     */
    protected function render_fallback_html($data) {
        if (!$this->is_interactive) {
            return sprintf(
                '<span class="%s" data-component="%s">%s</span>',
                esc_attr($this->get_css_classes($data)),
                esc_attr($this->component_name),
                esc_html($data['value'] ?? '')
            );
        }
        
        $attributes = array_merge(
            $this->get_default_attributes(),
            $this->get_accessibility_attributes($data)
        );
        
        $attribute_string = '';
        foreach ($attributes as $key => $value) {
            $attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        return sprintf(
            '<input%s value="%s" class="%s" data-component="%s">',
            $attribute_string,
            esc_attr($data['value'] ?? ''),
            esc_attr($this->get_css_classes($data)),
            esc_attr($this->component_name)
        );
    }
    
    /**
     * Assets für Atoms
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $atom_assets = array(
            'css' => array($this->component_name . '.css'),
            'js' => $this->is_interactive ? array($this->component_name . '.js') : array(),
            'dependencies' => array('qcc-atoms')
        );
        
        return array_merge_recursive($base_assets, $atom_assets);
    }
}
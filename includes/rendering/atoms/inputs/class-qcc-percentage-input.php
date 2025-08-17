<?php
/**
 * QCC Percentage Input Atom
 * 
 * Input-Atom für Prozent-Eingaben im Quality Cost Calculator.
 * Validiert Werte zwischen 0-100% mit Decimal-Support.
 * 
 * @package QualityCostCalculator
 * @subpackage Atoms
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QCC_Percentage_Input
 * 
 * @since 3.0.0
 */
class QCC_Percentage_Input extends QCC_Base_Atom {
    
    /**
     * Component-spezifische Eigenschaften
     * 
     * @since 3.0.0
     */
    protected $input_type = 'number';
    protected $base_css_class = 'qcc-percentage-input';
    protected $is_interactive = true;
    
    /**
     * Required fields
     * 
     * @since 3.0.0
     * @var array
     */
    protected $required_fields = array('id', 'name', 'label');
    
    /**
     * Optional fields mit Defaults
     * 
     * @since 3.0.0
     * @var array
     */
    protected $optional_fields = array(
        'value' => 0,
        'min' => 0,
        'max' => 100,
        'step' => 0.01,
        'placeholder' => '',
        'description' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'show_symbol' => true,
        'decimal_places' => 2
    );
    
    /**
     * Standard-Attribute
     * 
     * @since 3.0.0
     * @var array
     */
    protected $default_attributes = array(
        'type' => 'number',
        'min' => '0',
        'max' => '100',
        'step' => '0.01',
        'autocomplete' => 'off',
        'inputmode' => 'decimal'
    );
    
    /**
     * Validation-Rules für Frontend
     * 
     * @since 3.0.0
     * @var array
     */
    protected $validation_rules = array(
        'required' => false,
        'type' => 'number',
        'min' => 0,
        'max' => 100,
        'step' => 0.01,
        'pattern' => '^[0-9]+(\.[0-9]{1,2})?$'
    );
    
    /**
     * JavaScript-Events
     * 
     * @since 3.0.0
     * @var array
     */
    protected $javascript_events = array(
        'input' => 'qcc_percentage_input_changed',
        'change' => 'qcc_percentage_changed',
        'blur' => 'qcc_validate_percentage',
        'focus' => 'qcc_highlight_percentage_field'
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_percentage_specific();
    }
    
    /**
     * Percentage-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_percentage_specific() {
        // Template-Pfad setzen
        $this->template_path = 'atoms/inputs/percentage-input.php';
        
        // Lokalisierte Validation-Rules
        $this->validation_rules['title'] = __('Please enter a percentage between 0 and 100', 'quality-cost-calculator');
    }
    
    /**
     * Field-spezifische Validierung
     * 
     * @since 3.0.0
     * @param mixed $value Value to validate
     * @param string $field_name Field context
     * @return true|WP_Error
     */
    protected function validate_field_specific($value, $field_name) {
        if ($field_name !== 'value') {
            return true;
        }
        
        // Numeric check
        if (!is_numeric($value)) {
            return new WP_Error(
                'percentage_not_numeric',
                __('Percentage value must be numeric', 'quality-cost-calculator')
            );
        }
        
        $numeric_value = floatval($value);
        
        // Range check
        if ($numeric_value < 0) {
            return new WP_Error(
                'percentage_too_low',
                __('Percentage cannot be negative', 'quality-cost-calculator')
            );
        }
        
        if ($numeric_value > 100) {
            return new WP_Error(
                'percentage_too_high',
                __('Percentage cannot exceed 100%', 'quality-cost-calculator')
            );
        }
        
        // Decimal places check
        $decimal_places = $this->count_decimal_places($numeric_value);
        if ($decimal_places > 2) {
            return new WP_Error(
                'percentage_too_precise',
                __('Percentage can have maximum 2 decimal places', 'quality-cost-calculator')
            );
        }
        
        return true;
    }
    
    /**
     * Decimal-Stellen zählen
     * 
     * @since 3.0.0
     * @param float $number Number to check
     * @return int Decimal places
     */
    private function count_decimal_places($number) {
        $decimal = $number - floor($number);
        if ($decimal == 0) {
            return 0;
        }
        
        $decimal_string = (string) $decimal;
        $decimal_part = explode('.', $decimal_string)[1] ?? '';
        
        return strlen(rtrim($decimal_part, '0'));
    }
    
    /**
     * Field-spezifische Formatierung
     * 
     * @since 3.0.0
     * @param mixed $value Raw value
     * @param string $field_name Field context
     * @param array $options Formatting options
     * @return string Formatted value
     */
    protected function format_field_specific($value, $field_name, $options = array()) {
        if ($field_name !== 'value' || empty($value)) {
            return parent::format_field_specific($value, $field_name, $options);
        }
        
        $numeric_value = floatval($value);
        $decimal_places = $options['decimal_places'] ?? $this->optional_fields['decimal_places'];
        $show_symbol = $options['show_symbol'] ?? $this->optional_fields['show_symbol'];
        
        // Formatierung basierend auf Locale
        $formatted = number_format_i18n($numeric_value, $decimal_places);
        
        // Prozent-Symbol hinzufügen
        if ($show_symbol) {
            $formatted .= '%';
        }
        
        return $formatted;
    }
    
    /**
     * Field-spezifisches Input-Parsing
     * 
     * @since 3.0.0
     * @param string $input User input
     * @param string $field_name Field context
     * @return mixed Parsed value
     */
    protected function parse_field_specific($input, $field_name) {
        if ($field_name !== 'value') {
            return parent::parse_field_specific($input, $field_name);
        }
        
        // Prozent-Symbol entfernen
        $cleaned = str_replace('%', '', $input);
        
        // Whitespace entfernen
        $cleaned = trim($cleaned);
        
        // Locale-spezifische Decimal-Separator handhaben
        $decimal_separator = localeconv()['decimal_point'] ?? '.';
        if ($decimal_separator !== '.') {
            $cleaned = str_replace($decimal_separator, '.', $cleaned);
        }
        
        // Thousands-Separator entfernen
        $thousands_separator = localeconv()['thousands_sep'] ?? ',';
        if ($thousands_separator) {
            $cleaned = str_replace($thousands_separator, '', $cleaned);
        }
        
        // Zu Float konvertieren
        return empty($cleaned) ? 0 : floatval($cleaned);
    }
    
    /**
     * Accessibility-Attribute für Percentage-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array ARIA attributes
     */
    public function get_accessibility_attributes($data) {
        $base_attributes = parent::get_accessibility_attributes($data);
        
        $percentage_attributes = array(
            'aria-label' => sprintf(
                __('%s (percentage between %d and %d)', 'quality-cost-calculator'),
                $data['label'] ?? __('Percentage', 'quality-cost-calculator'),
                $data['min'] ?? 0,
                $data['max'] ?? 100
            ),
            'role' => 'spinbutton',
            'aria-valuemin' => $data['min'] ?? 0,
            'aria-valuemax' => $data['max'] ?? 100,
            'aria-valuenow' => $data['value'] ?? 0
        );
        
        if (!empty($data['step'])) {
            $percentage_attributes['aria-step'] = $data['step'];
        }
        
        return array_merge($base_attributes, $percentage_attributes);
    }
    
    /**
     * Help-Text für Percentage-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Help text HTML
     */
    public function get_help_text($data) {
        if (!empty($data['description'])) {
            return parent::get_help_text($data);
        }
        
        // Standard-Help-Text für Percentage-Inputs
        $help_text = sprintf(
            __('Enter a percentage value between %d%% and %d%%', 'quality-cost-calculator'),
            $data['min'] ?? 0,
            $data['max'] ?? 100
        );
        
        if (!empty($data['step']) && $data['step'] < 1) {
            $help_text .= ' ' . __('(decimal values allowed)', 'quality-cost-calculator');
        }
        
        return sprintf(
            '<small class="qcc-help-text" id="%s-description">%s</small>',
            esc_attr($data['id']),
            esc_html($help_text)
        );
    }
    
    /**
     * Frontend-Validation-Rules mit Percentage-spezifischen Regeln
     * 
     * @since 3.0.0
     * @return array Validation rules
     */
    public function get_frontend_validation_rules() {
        $base_rules = parent::get_frontend_validation_rules();
        
        $percentage_rules = array(
            'percentage' => true,
            'range' => array(
                'min' => $this->optional_fields['min'],
                'max' => $this->optional_fields['max']
            ),
            'step' => $this->optional_fields['step'],
            'decimal_places' => $this->optional_fields['decimal_places'],
            'messages' => array(
                'required' => __('Percentage value is required', 'quality-cost-calculator'),
                'range' => __('Percentage must be between {min}% and {max}%', 'quality-cost-calculator'),
                'step' => __('Please enter a valid percentage increment', 'quality-cost-calculator'),
                'decimal' => __('Too many decimal places', 'quality-cost-calculator')
            )
        );
        
        return array_merge($base_rules, $percentage_rules);
    }
    
    /**
     * Template-Daten für Percentage-Input vorbereiten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @param array $attributes HTML attributes
     * @return array Template data
     */
    public function prepare_template_data($data, $attributes) {
        $base_data = parent::prepare_template_data($data, $attributes);
        
        // Percentage-spezifische Template-Daten
        $percentage_data = array(
            'raw_value' => $data['value'] ?? 0,
            'formatted_value' => $this->format_value($data['value'] ?? 0, 'value'),
            'display_value' => $this->format_value($data['value'] ?? 0, 'value', array('show_symbol' => false)),
            'min_value' => $data['min'] ?? $this->optional_fields['min'],
            'max_value' => $data['max'] ?? $this->optional_fields['max'],
            'step_value' => $data['step'] ?? $this->optional_fields['step'],
            'decimal_places' => $data['decimal_places'] ?? $this->optional_fields['decimal_places'],
            'show_symbol' => $data['show_symbol'] ?? $this->optional_fields['show_symbol'],
            'percentage_symbol' => '%',
            'is_percentage_input' => true
        );
        
        return array_merge($base_data, $percentage_data);
    }
    
    /**
     * Fallback-HTML für Percentage-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Fallback HTML
     */
    protected function render_fallback_html($data) {
        $attributes = array_merge(
            $this->get_default_attributes(),
            $this->get_accessibility_attributes($data),
            array(
                'id' => $data['id'],
                'name' => $data['name'],
                'value' => $data['value'] ?? 0,
                'class' => $this->get_css_classes($data)
            )
        );
        
        // Percentage-spezifische Attribute
        if (isset($data['min'])) $attributes['min'] = $data['min'];
        if (isset($data['max'])) $attributes['max'] = $data['max'];
        if (isset($data['step'])) $attributes['step'] = $data['step'];
        if (isset($data['placeholder'])) $attributes['placeholder'] = $data['placeholder'];
        if (!empty($data['required'])) $attributes['required'] = 'required';
        if (!empty($data['disabled'])) $attributes['disabled'] = 'disabled';
        if (!empty($data['readonly'])) $attributes['readonly'] = 'readonly';
        
        $attribute_string = '';
        foreach ($attributes as $key => $value) {
            $attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<div class="qcc-percentage-input-wrapper">');
        
        // Label
        if (!empty($data['label'])) {
            $html .= sprintf(
                '<label for="%s" class="qcc-percentage-label">%s</label>',
                esc_attr($data['id']),
                esc_html($data['label'])
            );
        }
        
        // Input mit Prozent-Symbol
        $html .= '<div class="qcc-percentage-input-group">';
        $html .= sprintf('<input%s>', $attribute_string);
        
        if ($data['show_symbol'] ?? true) {
            $html .= '<span class="qcc-percentage-symbol">%</span>';
        }
        
        $html .= '</div>';
        
        // Help-Text
        $html .= $this->get_help_text($data);
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Assets für Percentage-Input
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $percentage_assets = array(
            'css' => array('percentage-input.css'),
            'js' => array('percentage-input.js', 'percentage-validation.js'),
            'dependencies' => array('qcc-input-base', 'qcc-validation')
        );
        
        return array_merge_recursive($base_assets, $percentage_assets);
    }
}
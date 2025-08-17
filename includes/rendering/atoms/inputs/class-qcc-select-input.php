<?php
/**
 * QCC Select Input Atom
 * 
 * Select-Atom für Dropdown-Auswahlen im Quality Cost Calculator.
 * Unterstützt Single/Multi-Select, Optgroups und Dynamic Options.
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
 * Class QCC_Select_Input
 * 
 * @since 3.0.0
 */
class QCC_Select_Input extends QCC_Base_Atom {
    
    /**
     * Component-spezifische Eigenschaften
     * 
     * @since 3.0.0
     */
    protected $input_type = 'select';
    protected $base_css_class = 'qcc-select-input';
    protected $is_interactive = true;
    
    /**
     * Required fields
     * 
     * @since 3.0.0
     * @var array
     */
    protected $required_fields = array('id', 'name', 'label', 'options');
    
    /**
     * Optional fields mit Defaults
     * 
     * @since 3.0.0
     * @var array
     */
    protected $optional_fields = array(
        'value' => '',
        'multiple' => false,
        'size' => 1,
        'placeholder' => '',
        'description' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'searchable' => false,
        'clearable' => false,
        'show_icons' => false,
        'group_options' => false,
        'max_selections' => null,
        'min_selections' => null
    );
    
    /**
     * Standard-Attribute
     * 
     * @since 3.0.0
     * @var array
     */
    protected $default_attributes = array(
        'autocomplete' => 'off'
    );
    
    /**
     * Validation-Rules für Frontend
     * 
     * @since 3.0.0
     * @var array
     */
    protected $validation_rules = array(
        'required' => false,
        'type' => 'select'
    );
    
    /**
     * JavaScript-Events
     * 
     * @since 3.0.0
     * @var array
     */
    protected $javascript_events = array(
        'change' => 'qcc_select_changed',
        'focus' => 'qcc_select_focused',
        'blur' => 'qcc_validate_select'
    );
    
    /**
     * Standard-Optionen für häufige Use-Cases
     * 
     * @since 3.0.0
     * @var array
     */
    protected $standard_option_sets = array(
        'currency' => array(
            'EUR' => 'Euro (€)',
            'USD' => 'US Dollar ($)',
            'CNY' => 'Chinese Yuan (¥)',
            'GBP' => 'British Pound (£)',
            'JPY' => 'Japanese Yen (¥)'
        ),
        'unit' => array(
            '1000000' => 'Millions',
            '1000000000' => 'Billions'
        ),
        'language' => array(
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español',
            'zh' => '中文'
        ),
        'boolean' => array(
            '1' => 'Yes',
            '0' => 'No'
        )
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_select_specific();
    }
    
    /**
     * Select-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_select_specific() {
        // Template-Pfad setzen
        $this->template_path = 'atoms/inputs/select-input.php';
        
        // Lokalisierte Standard-Optionen
        $this->localize_standard_options();
    }
    
    /**
     * Standard-Optionen lokalisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function localize_standard_options() {
        $this->standard_option_sets['unit'] = array(
            '1000000' => __('Millions', 'quality-cost-calculator'),
            '1000000000' => __('Billions', 'quality-cost-calculator')
        );
        
        $this->standard_option_sets['boolean'] = array(
            '1' => __('Yes', 'quality-cost-calculator'),
            '0' => __('No', 'quality-cost-calculator')
        );
    }
    
    /**
     * Standard-Optionen abrufen
     * 
     * @since 3.0.0
     * @param string $set Option set name
     * @return array Options
     */
    public function get_standard_options($set) {
        return $this->standard_option_sets[$set] ?? array();
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
        if ($field_name === 'value') {
            return $this->validate_select_value($value);
        }
        
        if ($field_name === 'options') {
            return $this->validate_options($value);
        }
        
        return true;
    }
    
    /**
     * Select-Wert validieren
     * 
     * @since 3.0.0
     * @param mixed $value Selected value(s)
     * @return true|WP_Error
     */
    private function validate_select_value($value) {
        // Für Multiple-Select kann value ein Array sein
        if (is_array($value)) {
            foreach ($value as $single_value) {
                if (!is_scalar($single_value)) {
                    return new WP_Error(
                        'select_invalid_value',
                        __('Select value must be scalar', 'quality-cost-calculator')
                    );
                }
            }
        } elseif (!empty($value) && !is_scalar($value)) {
            return new WP_Error(
                'select_invalid_value',
                __('Select value must be scalar', 'quality-cost-calculator')
            );
        }
        
        return true;
    }
    
    /**
     * Optionen validieren
     * 
     * @since 3.0.0
     * @param array $options Options array
     * @return true|WP_Error
     */
    private function validate_options($options) {
        if (!is_array($options)) {
            return new WP_Error(
                'select_options_not_array',
                __('Select options must be an array', 'quality-cost-calculator')
            );
        }
        
        if (empty($options)) {
            return new WP_Error(
                'select_options_empty',
                __('Select must have at least one option', 'quality-cost-calculator')
            );
        }
        
        return true;
    }
    
    /**
     * Optionen mit Werten validieren
     * 
     * @since 3.0.0
     * @param mixed $value Selected value(s)
     * @param array $options Available options
     * @return true|WP_Error
     */
    public function validate_value_against_options($value, $options) {
        if (empty($value)) {
            return true;
        }
        
        $flat_options = $this->flatten_options($options);
        $values_to_check = is_array($value) ? $value : array($value);
        
        foreach ($values_to_check as $single_value) {
            if (!array_key_exists($single_value, $flat_options)) {
                return new WP_Error(
                    'select_value_not_in_options',
                    sprintf(__('Value "%s" is not a valid option', 'quality-cost-calculator'), $single_value)
                );
            }
        }
        
        return true;
    }
    
    /**
     * Optionen flach machen (Optgroups auflösen)
     * 
     * @since 3.0.0
     * @param array $options Hierarchical options
     * @return array Flat options
     */
    private function flatten_options($options) {
        $flat = array();
        
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                // Optgroup
                $flat = array_merge($flat, $this->flatten_options($value));
            } else {
                // Regular option
                $flat[$key] = $value;
            }
        }
        
        return $flat;
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
        if ($field_name === 'value' && !empty($value) && isset($options['options'])) {
            return $this->format_selected_value($value, $options['options']);
        }
        
        return parent::format_field_specific($value, $field_name, $options);
    }
    
    /**
     * Selected value formatieren
     * 
     * @since 3.0.0
     * @param mixed $value Selected value(s)
     * @param array $options Available options
     * @return string Formatted display value
     */
    public function format_selected_value($value, $options) {
        if (empty($value)) {
            return '';
        }
        
        $flat_options = $this->flatten_options($options);
        
        if (is_array($value)) {
            // Multiple selection
            $labels = array();
            foreach ($value as $single_value) {
                if (isset($flat_options[$single_value])) {
                    $labels[] = $flat_options[$single_value];
                }
            }
            return implode(', ', $labels);
        } else {
            // Single selection
            return $flat_options[$value] ?? $value;
        }
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
        if ($field_name === 'value') {
            return $this->parse_select_input($input);
        }
        
        return parent::parse_field_specific($input, $field_name);
    }
    
    /**
     * Select-Input parsen
     * 
     * @since 3.0.0
     * @param mixed $input User input
     * @return mixed Parsed value
     */
    public function parse_select_input($input) {
        // Bereits korrekt formatiert
        if (is_array($input) || is_scalar($input)) {
            return $input;
        }
        
        // String zu Array konvertieren falls nötig (für Multiple)
        if (is_string($input) && strpos($input, ',') !== false) {
            return array_map('trim', explode(',', $input));
        }
        
        return $input;
    }
    
    /**
     * Optionen für Template rendern
     * 
     * @since 3.0.0
     * @param array $options Raw options
     * @param mixed $selected_value Currently selected value(s)
     * @return string Options HTML
     */
    public function render_options($options, $selected_value = null) {
        $html = '';
        $selected_values = is_array($selected_value) ? $selected_value : array($selected_value);
        
        foreach ($options as $value => $label) {
            if (is_array($label)) {
                // Optgroup
                $html .= sprintf('<optgroup label="%s">', esc_attr($value));
                $html .= $this->render_options($label, $selected_value);
                $html .= '</optgroup>';
            } else {
                // Regular option
                $selected = in_array($value, $selected_values) ? ' selected="selected"' : '';
                $html .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($value),
                    $selected,
                    esc_html($label)
                );
            }
        }
        
        return $html;
    }
    
    /**
     * Accessibility-Attribute für Select-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array ARIA attributes
     */
    public function get_accessibility_attributes($data) {
        $base_attributes = parent::get_accessibility_attributes($data);
        
        $select_attributes = array(
            'aria-label' => $data['label'] ?? __('Select Option', 'quality-cost-calculator'),
            'role' => $data['multiple'] ? 'listbox' : 'combobox'
        );
        
        if ($data['multiple'] ?? false) {
            $select_attributes['aria-multiselectable'] = 'true';
            
            if (!empty($data['max_selections'])) {
                $select_attributes['aria-description'] = sprintf(
                    __('Maximum %d selections allowed', 'quality-cost-calculator'),
                    $data['max_selections']
                );
            }
        }
        
        if ($data['searchable'] ?? false) {
            $select_attributes['aria-autocomplete'] = 'list';
        }
        
        return array_merge($base_attributes, $select_attributes);
    }
    
    /**
     * Help-Text für Select-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Help text HTML
     */
    public function get_help_text($data) {
        if (!empty($data['description'])) {
            return parent::get_help_text($data);
        }
        
        $help_parts = array();
        
        if ($data['multiple'] ?? false) {
            $help_parts[] = __('Multiple selections allowed', 'quality-cost-calculator');
            
            if (!empty($data['max_selections'])) {
                $help_parts[] = sprintf(
                    __('Maximum: %d selections', 'quality-cost-calculator'),
                    $data['max_selections']
                );
            }
        }
        
        if ($data['searchable'] ?? false) {
            $help_parts[] = __('Type to search options', 'quality-cost-calculator');
        }
        
        if (empty($help_parts)) {
            return '';
        }
        
        return sprintf(
            '<small class="qcc-help-text" id="%s-description">%s</small>',
            esc_attr($data['id']),
            esc_html(implode('. ', $help_parts))
        );
    }
    
    /**
     * Frontend-Validation-Rules mit Select-spezifischen Regeln
     * 
     * @since 3.0.0
     * @return array Validation rules
     */
    public function get_frontend_validation_rules() {
        $base_rules = parent::get_frontend_validation_rules();
        
        $select_rules = array(
            'select' => true,
            'messages' => array(
                'required' => __('Please select an option', 'quality-cost-calculator'),
                'invalid' => __('Selected value is not valid', 'quality-cost-calculator'),
                'max_selections' => __('Too many selections', 'quality-cost-calculator'),
                'min_selections' => __('Not enough selections', 'quality-cost-calculator')
            )
        );
        
        return array_merge($base_rules, $select_rules);
    }
    
    /**
     * Template-Daten für Select-Input vorbereiten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @param array $attributes HTML attributes
     * @return array Template data
     */
    public function prepare_template_data($data, $attributes) {
        $base_data = parent::prepare_template_data($data, $attributes);
        
        $options = $data['options'] ?? array();
        $selected_value = $data['value'] ?? '';
        
        $select_data = array(
            'options' => $options,
            'selected_value' => $selected_value,
            'rendered_options' => $this->render_options($options, $selected_value),
            'formatted_selection' => $this->format_selected_value($selected_value, $options),
            'is_multiple' => $data['multiple'] ?? false,
            'select_size' => $data['size'] ?? 1,
            'is_searchable' => $data['searchable'] ?? false,
            'is_clearable' => $data['clearable'] ?? false,
            'show_icons' => $data['show_icons'] ?? false,
            'has_optgroups' => $this->has_optgroups($options),
            'option_count' => count($this->flatten_options($options)),
            'max_selections' => $data['max_selections'] ?? null,
            'min_selections' => $data['min_selections'] ?? null,
            'placeholder_text' => $data['placeholder'] ?? __('Select...', 'quality-cost-calculator'),
            'is_select_input' => true
        );
        
        return array_merge($base_data, $select_data);
    }
    
    /**
     * Prüfen ob Optionen Optgroups enthalten
     * 
     * @since 3.0.0
     * @param array $options Options array
     * @return bool True wenn Optgroups vorhanden
     */
    private function has_optgroups($options) {
        foreach ($options as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Fallback-HTML für Select-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Fallback HTML
     */
    protected function render_fallback_html($data) {
        $options = $data['options'] ?? array();
        $selected_value = $data['value'] ?? '';
        
        $attributes = array_merge(
            $this->get_default_attributes(),
            $this->get_accessibility_attributes($data),
            array(
                'id' => $data['id'],
                'name' => $data['name'],
                'class' => $this->get_css_classes($data)
            )
        );
        
        // Select-spezifische Attribute
        if ($data['multiple'] ?? false) {
            $attributes['multiple'] = 'multiple';
            $attributes['name'] .= '[]';
        }
        
        if (isset($data['size']) && $data['size'] > 1) {
            $attributes['size'] = $data['size'];
        }
        
        if (!empty($data['required'])) $attributes['required'] = 'required';
        if (!empty($data['disabled'])) $attributes['disabled'] = 'disabled';
        if (!empty($data['readonly'])) $attributes['readonly'] = 'readonly';
        
        $attribute_string = '';
        foreach ($attributes as $key => $value) {
            $attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<div class="qcc-select-input-wrapper">');
        
        // Label
        if (!empty($data['label'])) {
            $html .= sprintf(
                '<label for="%s" class="qcc-select-label">%s</label>',
                esc_attr($data['id']),
                esc_html($data['label'])
            );
        }
        
        // Select-Element
        $html .= sprintf('<select%s>', $attribute_string);
        
        // Placeholder-Option
        if (!empty($data['placeholder']) && !($data['multiple'] ?? false)) {
            $html .= sprintf(
                '<option value="" disabled%s>%s</option>',
                empty($selected_value) ? ' selected' : '',
                esc_html($data['placeholder'])
            );
        }
        
        // Optionen rendern
        $html .= $this->render_options($options, $selected_value);
        
        $html .= '</select>';
        
        // Help-Text
        $html .= $this->get_help_text($data);
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Assets für Select-Input
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $select_assets = array(
            'css' => array('select-input.css'),
            'js' => array('select-input.js'),
            'dependencies' => array('qcc-input-base', 'qcc-validation')
        );
        
        return array_merge_recursive($base_assets, $select_assets);
    }
}
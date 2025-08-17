<?php
/**
 * QCC Currency Input Atom
 * 
 * Input-Atom für Währungs-Eingaben im Quality Cost Calculator.
 * Unterstützt verschiedene Währungen, Units und Formatierung.
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
 * Class QCC_Currency_Input
 * 
 * @since 3.0.0
 */
class QCC_Currency_Input extends QCC_Base_Atom {
    
    /**
     * Component-spezifische Eigenschaften
     * 
     * @since 3.0.0
     */
    protected $input_type = 'number';
    protected $base_css_class = 'qcc-currency-input';
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
        'currency' => 'EUR',
        'unit' => 1000000,
        'min' => 0,
        'max' => 999999999999,
        'step' => 0.01,
        'placeholder' => '',
        'description' => '',
        'required' => false,
        'disabled' => false,
        'readonly' => false,
        'show_symbol' => true,
        'show_unit' => true,
        'decimal_places' => 2,
        'thousands_separator' => true
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
        'step' => '0.01',
        'autocomplete' => 'off',
        'inputmode' => 'decimal'
    );
    
    /**
     * Unterstützte Währungen
     * 
     * @since 3.0.0
     * @var array
     */
    protected $supported_currencies = array(
        'EUR' => array(
            'symbol' => '€',
            'name' => 'Euro',
            'position' => 'after',
            'decimal_places' => 2
        ),
        'USD' => array(
            'symbol' => '$',
            'name' => 'US Dollar',
            'position' => 'before',
            'decimal_places' => 2
        ),
        'CNY' => array(
            'symbol' => '¥',
            'name' => 'Chinese Yuan',
            'position' => 'before',
            'decimal_places' => 2
        ),
        'GBP' => array(
            'symbol' => '£',
            'name' => 'British Pound',
            'position' => 'before',
            'decimal_places' => 2
        ),
        'JPY' => array(
            'symbol' => '¥',
            'name' => 'Japanese Yen',
            'position' => 'before',
            'decimal_places' => 0
        )
    );
    
    /**
     * Unterstützte Units
     * 
     * @since 3.0.0
     * @var array
     */
    protected $supported_units = array(
        1 => array('name' => 'ones', 'short' => '', 'multiplier' => 1),
        1000 => array('name' => 'thousands', 'short' => 'K', 'multiplier' => 1000),
        1000000 => array('name' => 'millions', 'short' => 'M', 'multiplier' => 1000000),
        1000000000 => array('name' => 'billions', 'short' => 'B', 'multiplier' => 1000000000)
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
        'step' => 0.01,
        'currency' => true
    );
    
    /**
     * JavaScript-Events
     * 
     * @since 3.0.0
     * @var array
     */
    protected $javascript_events = array(
        'input' => 'qcc_currency_input_changed',
        'change' => 'qcc_currency_changed',
        'blur' => 'qcc_validate_currency',
        'focus' => 'qcc_highlight_currency_field'
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_currency_specific();
    }
    
    /**
     * Currency-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_currency_specific() {
        // Template-Pfad setzen
        $this->template_path = 'atoms/inputs/currency-input.php';
        
        // Lokalisierte Unit-Namen laden
        $this->load_localized_units();
    }
    
    /**
     * Lokalisierte Unit-Namen laden
     * 
     * @since 3.0.0
     * @return void
     */
    private function load_localized_units() {
        $this->supported_units[1000]['name'] = __('Thousands', 'quality-cost-calculator');
        $this->supported_units[1000000]['name'] = __('Millions', 'quality-cost-calculator');
        $this->supported_units[1000000000]['name'] = __('Billions', 'quality-cost-calculator');
    }
    
    /**
     * Currency-Info abrufen
     * 
     * @since 3.0.0
     * @param string $currency Currency code
     * @return array Currency info
     */
    public function get_currency_info($currency) {
        return $this->supported_currencies[$currency] ?? $this->supported_currencies['EUR'];
    }
    
    /**
     * Unit-Info abrufen
     * 
     * @since 3.0.0
     * @param int $unit Unit value
     * @return array Unit info
     */
    public function get_unit_info($unit) {
        return $this->supported_units[$unit] ?? $this->supported_units[1000000];
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
            return $this->validate_currency_value($value);
        }
        
        if ($field_name === 'currency') {
            return $this->validate_currency_code($value);
        }
        
        if ($field_name === 'unit') {
            return $this->validate_unit_value($value);
        }
        
        return true;
    }
    
    /**
     * Currency-Wert validieren
     * 
     * @since 3.0.0
     * @param mixed $value Currency value
     * @return true|WP_Error
     */
    private function validate_currency_value($value) {
        if (!is_numeric($value)) {
            return new WP_Error(
                'currency_not_numeric',
                __('Currency value must be numeric', 'quality-cost-calculator')
            );
        }
        
        $numeric_value = floatval($value);
        
        if ($numeric_value < 0) {
            return new WP_Error(
                'currency_negative',
                __('Currency value cannot be negative', 'quality-cost-calculator')
            );
        }
        
        if ($numeric_value > 999999999999) {
            return new WP_Error(
                'currency_too_large',
                __('Currency value is too large', 'quality-cost-calculator')
            );
        }
        
        return true;
    }
    
    /**
     * Currency-Code validieren
     * 
     * @since 3.0.0
     * @param string $currency Currency code
     * @return true|WP_Error
     */
    private function validate_currency_code($currency) {
        if (!isset($this->supported_currencies[$currency])) {
            return new WP_Error(
                'currency_unsupported',
                sprintf(__('Currency "%s" is not supported', 'quality-cost-calculator'), $currency)
            );
        }
        
        return true;
    }
    
    /**
     * Unit-Wert validieren
     * 
     * @since 3.0.0
     * @param int $unit Unit value
     * @return true|WP_Error
     */
    private function validate_unit_value($unit) {
        if (!isset($this->supported_units[$unit])) {
            return new WP_Error(
                'unit_unsupported',
                sprintf(__('Unit "%s" is not supported', 'quality-cost-calculator'), $unit)
            );
        }
        
        return true;
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
        
        $currency = $options['currency'] ?? $this->optional_fields['currency'];
        $unit = $options['unit'] ?? $this->optional_fields['unit'];
        $show_symbol = $options['show_symbol'] ?? $this->optional_fields['show_symbol'];
        $show_unit = $options['show_unit'] ?? $this->optional_fields['show_unit'];
        $decimal_places = $options['decimal_places'] ?? $this->optional_fields['decimal_places'];
        $thousands_separator = $options['thousands_separator'] ?? $this->optional_fields['thousands_separator'];
        
        return $this->format_currency_value($value, $currency, $unit, array(
            'show_symbol' => $show_symbol,
            'show_unit' => $show_unit,
            'decimal_places' => $decimal_places,
            'thousands_separator' => $thousands_separator
        ));
    }
    
    /**
     * Currency-Wert formatieren
     * 
     * @since 3.0.0
     * @param float $value Raw value
     * @param string $currency Currency code
     * @param int $unit Unit multiplier
     * @param array $options Formatting options
     * @return string Formatted currency
     */
    public function format_currency_value($value, $currency, $unit, $options = array()) {
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        // Wert in Display-Unit konvertieren
        $display_value = $value / $unit_info['multiplier'];
        
        // Decimal-Places von Currency übernehmen falls nicht gesetzt
        $decimal_places = $options['decimal_places'] ?? $currency_info['decimal_places'];
        
        // Formatierung
        if ($options['thousands_separator'] ?? true) {
            $formatted = number_format_i18n($display_value, $decimal_places);
        } else {
            $formatted = number_format($display_value, $decimal_places, '.', '');
        }
        
        // Currency-Symbol hinzufügen
        if ($options['show_symbol'] ?? true) {
            if ($currency_info['position'] === 'before') {
                $formatted = $currency_info['symbol'] . $formatted;
            } else {
                $formatted = $formatted . ' ' . $currency_info['symbol'];
            }
        }
        
        // Unit-Suffix hinzufügen
        if ($options['show_unit'] ?? true && !empty($unit_info['short'])) {
            $formatted .= ' ' . $unit_info['short'];
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
        
        return $this->parse_currency_input($input);
    }
    
    /**
     * Currency-Input parsen
     * 
     * @since 3.0.0
     * @param string $input User input
     * @return float Parsed value
     */
    public function parse_currency_input($input) {
        // Currency-Symbole entfernen
        $cleaned = $input;
        foreach ($this->supported_currencies as $currency_info) {
            $cleaned = str_replace($currency_info['symbol'], '', $cleaned);
        }
        
        // Unit-Suffixe entfernen
        foreach ($this->supported_units as $unit_info) {
            if (!empty($unit_info['short'])) {
                $cleaned = str_replace($unit_info['short'], '', $cleaned);
            }
        }
        
        // Whitespace entfernen
        $cleaned = trim($cleaned);
        
        // Locale-spezifische Separatoren handhaben
        $locale_conv = localeconv();
        $decimal_separator = $locale_conv['decimal_point'] ?? '.';
        $thousands_separator = $locale_conv['thousands_sep'] ?? ',';
        
        // Thousands-Separator entfernen
        if ($thousands_separator) {
            $cleaned = str_replace($thousands_separator, '', $cleaned);
        }
        
        // Decimal-Separator normalisieren
        if ($decimal_separator !== '.') {
            $cleaned = str_replace($decimal_separator, '.', $cleaned);
        }
        
        return empty($cleaned) ? 0 : floatval($cleaned);
    }
    
    /**
     * Accessibility-Attribute für Currency-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array ARIA attributes
     */
    public function get_accessibility_attributes($data) {
        $base_attributes = parent::get_accessibility_attributes($data);
        
        $currency = $data['currency'] ?? $this->optional_fields['currency'];
        $unit = $data['unit'] ?? $this->optional_fields['unit'];
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        $currency_attributes = array(
            'aria-label' => sprintf(
                __('%s (in %s %s)', 'quality-cost-calculator'),
                $data['label'] ?? __('Currency Amount', 'quality-cost-calculator'),
                $currency_info['name'],
                $unit_info['name']
            ),
            'role' => 'spinbutton',
            'aria-valuemin' => $data['min'] ?? 0,
            'aria-valuemax' => $data['max'] ?? 999999999999,
            'aria-valuenow' => $data['value'] ?? 0
        );
        
        return array_merge($base_attributes, $currency_attributes);
    }
    
    /**
     * Help-Text für Currency-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Help text HTML
     */
    public function get_help_text($data) {
        if (!empty($data['description'])) {
            return parent::get_help_text($data);
        }
        
        $currency = $data['currency'] ?? $this->optional_fields['currency'];
        $unit = $data['unit'] ?? $this->optional_fields['unit'];
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        $help_text = sprintf(
            __('Enter amount in %s (%s)', 'quality-cost-calculator'),
            $currency_info['name'],
            $unit_info['name']
        );
        
        return sprintf(
            '<small class="qcc-help-text" id="%s-description">%s</small>',
            esc_attr($data['id']),
            esc_html($help_text)
        );
    }
    
    /**
     * Frontend-Validation-Rules mit Currency-spezifischen Regeln
     * 
     * @since 3.0.0
     * @return array Validation rules
     */
    public function get_frontend_validation_rules() {
        $base_rules = parent::get_frontend_validation_rules();
        
        $currency_rules = array(
            'currency' => true,
            'supported_currencies' => array_keys($this->supported_currencies),
            'supported_units' => array_keys($this->supported_units),
            'messages' => array(
                'required' => __('Currency amount is required', 'quality-cost-calculator'),
                'numeric' => __('Please enter a valid currency amount', 'quality-cost-calculator'),
                'min' => __('Amount cannot be negative', 'quality-cost-calculator'),
                'max' => __('Amount is too large', 'quality-cost-calculator'),
                'currency' => __('Invalid currency format', 'quality-cost-calculator')
            )
        );
        
        return array_merge($base_rules, $currency_rules);
    }
    
    /**
     * Template-Daten für Currency-Input vorbereiten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @param array $attributes HTML attributes
     * @return array Template data
     */
    public function prepare_template_data($data, $attributes) {
        $base_data = parent::prepare_template_data($data, $attributes);
        
        $currency = $data['currency'] ?? $this->optional_fields['currency'];
        $unit = $data['unit'] ?? $this->optional_fields['unit'];
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        $currency_data = array(
            'raw_value' => $data['value'] ?? 0,
            'display_value' => ($data['value'] ?? 0) / $unit_info['multiplier'],
            'formatted_value' => $this->format_value($data['value'] ?? 0, 'value', $data),
            'currency_code' => $currency,
            'currency_symbol' => $currency_info['symbol'],
            'currency_name' => $currency_info['name'],
            'currency_position' => $currency_info['position'],
            'unit_value' => $unit,
            'unit_name' => $unit_info['name'],
            'unit_short' => $unit_info['short'],
            'unit_multiplier' => $unit_info['multiplier'],
            'show_symbol' => $data['show_symbol'] ?? $this->optional_fields['show_symbol'],
            'show_unit' => $data['show_unit'] ?? $this->optional_fields['show_unit'],
            'decimal_places' => $data['decimal_places'] ?? $currency_info['decimal_places'],
            'thousands_separator' => $data['thousands_separator'] ?? $this->optional_fields['thousands_separator'],
            'supported_currencies' => $this->supported_currencies,
            'supported_units' => $this->supported_units,
            'is_currency_input' => true
        );
        
        return array_merge($base_data, $currency_data);
    }
    
    /**
     * Fallback-HTML für Currency-Input
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return string Fallback HTML
     */
    protected function render_fallback_html($data) {
        $currency = $data['currency'] ?? $this->optional_fields['currency'];
        $unit = $data['unit'] ?? $this->optional_fields['unit'];
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        $display_value = ($data['value'] ?? 0) / $unit_info['multiplier'];
        
        $attributes = array_merge(
            $this->get_default_attributes(),
            $this->get_accessibility_attributes($data),
            array(
                'id' => $data['id'],
                'name' => $data['name'],
                'value' => $display_value,
                'class' => $this->get_css_classes($data)
            )
        );
        
        // Currency-spezifische Attribute
        if (isset($data['min'])) $attributes['min'] = $data['min'] / $unit_info['multiplier'];
        if (isset($data['max'])) $attributes['max'] = $data['max'] / $unit_info['multiplier'];
        if (isset($data['step'])) $attributes['step'] = $data['step'];
        if (isset($data['placeholder'])) $attributes['placeholder'] = $data['placeholder'];
        if (!empty($data['required'])) $attributes['required'] = 'required';
        if (!empty($data['disabled'])) $attributes['disabled'] = 'disabled';
        if (!empty($data['readonly'])) $attributes['readonly'] = 'readonly';
        
        $attribute_string = '';
        foreach ($attributes as $key => $value) {
            $attribute_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<div class="qcc-currency-input-wrapper">');
        
        // Label
        if (!empty($data['label'])) {
            $html .= sprintf(
                '<label for="%s" class="qcc-currency-label">%s</label>',
                esc_attr($data['id']),
                esc_html($data['label'])
            );
        }
        
        // Input-Group mit Currency-Symbol
        $html .= '<div class="qcc-currency-input-group">';
        
        // Currency-Symbol vor Input
        if (($data['show_symbol'] ?? true) && $currency_info['position'] === 'before') {
            $html .= sprintf('<span class="qcc-currency-symbol qcc-symbol-before">%s</span>', esc_html($currency_info['symbol']));
        }
        
        $html .= sprintf('<input%s>', $attribute_string);
        
        // Currency-Symbol nach Input
        if (($data['show_symbol'] ?? true) && $currency_info['position'] === 'after') {
            $html .= sprintf('<span class="qcc-currency-symbol qcc-symbol-after">%s</span>', esc_html($currency_info['symbol']));
        }
        
        // Unit-Anzeige
        if (($data['show_unit'] ?? true) && !empty($unit_info['short'])) {
            $html .= sprintf('<span class="qcc-unit-indicator">%s</span>', esc_html($unit_info['short']));
        }
        
        $html .= '</div>';
        
        // Help-Text
        $html .= $this->get_help_text($data);
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Assets für Currency-Input
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $currency_assets = array(
            'css' => array('currency-input.css'),
            'js' => array('currency-input.js', 'currency-formatting.js'),
            'dependencies' => array('qcc-input-base', 'qcc-validation', 'qcc-formatting')
        );
        
        return array_merge_recursive($base_assets, $currency_assets);
    }
}
<?php
/**
 * QCC Currency Selector Molecule
 * 
 * Molecule-Component für Currency + Unit Selection.
 * Kombiniert Currency-Select und Unit-Select zu funktionaler Einheit.
 * 
 * @package QualityCostCalculator
 * @subpackage Molecules
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QCC_Currency_Selector
 * 
 * @since 3.0.0
 */
class QCC_Currency_Selector extends QCC_Base_Molecule {
    
    /**
     * Child-Components Definition
     * 
     * @since 3.0.0
     * @var array
     */
    protected $child_components = array(
        'currency_select' => array(
            'type' => 'atom',
            'class' => 'QCC_Select_Input',
            'required' => true
        ),
        'unit_select' => array(
            'type' => 'atom',
            'class' => 'QCC_Select_Input',
            'required' => true
        ),
        'preview' => array(
            'type' => 'atom',
            'class' => 'QCC_Currency_Preview',
            'required' => false
        )
    );
    
    /**
     * Layout-Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    protected $layout_config = array(
        'orientation' => 'horizontal',
        'spacing' => 'small',
        'alignment' => 'center',
        'wrap_children' => true,
        'equal_width' => true
    );
    
    /**
     * Molecule-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $molecule_type = 'control';
    
    /**
     * Event-Propagation-Rules
     * 
     * @since 3.0.0
     * @var array
     */
    protected $event_propagation_rules = array(
        'currency_select.change' => array('preview.update', 'unit_select.filter'),
        'unit_select.change' => array('preview.update'),
        'currency_selector.reset' => array('currency_select.reset', 'unit_select.reset', 'preview.clear')
    );
    
    /**
     * Unterstützte Währungen mit Metadaten
     * 
     * @since 3.0.0
     * @var array
     */
    protected $supported_currencies = array(
        'EUR' => array(
            'name' => 'Euro',
            'symbol' => '€',
            'flag' => '🇪🇺',
            'position' => 'after',
            'decimal_places' => 2,
            'default_units' => array(1000000, 1000000000)
        ),
        'USD' => array(
            'name' => 'US Dollar',
            'symbol' => '$',
            'flag' => '🇺🇸',
            'position' => 'before',
            'decimal_places' => 2,
            'default_units' => array(1000000, 1000000000)
        ),
        'CNY' => array(
            'name' => 'Chinese Yuan',
            'symbol' => '¥',
            'flag' => '🇨🇳',
            'position' => 'before',
            'decimal_places' => 2,
            'default_units' => array(1000000, 1000000000)
        ),
        'GBP' => array(
            'name' => 'British Pound',
            'symbol' => '£',
            'flag' => '🇬🇧',
            'position' => 'before',
            'decimal_places' => 2,
            'default_units' => array(1000000, 1000000000)
        ),
        'JPY' => array(
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'flag' => '🇯🇵',
            'position' => 'before',
            'decimal_places' => 0,
            'default_units' => array(1000000, 1000000000)
        )
    );
    
    /**
     * Unterstützte Units
     * 
     * @since 3.0.0
     * @var array
     */
    protected $supported_units = array(
        1000000 => array(
            'name' => 'Millions',
            'short' => 'M',
            'multiplier' => 1000000,
            'description' => 'Values in millions'
        ),
        1000000000 => array(
            'name' => 'Billions',
            'short' => 'B',
            'multiplier' => 1000000000,
            'description' => 'Values in billions'
        )
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_currency_selector_specific();
    }
    
    /**
     * Currency-Selector-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_currency_selector_specific() {
        // Template-Pfad setzen
        $this->layout_template = 'molecules/currency-selector.php';
        
        // Lokalisierte Unit-Namen laden
        $this->localize_units();
    }
    
    /**
     * Units lokalisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function localize_units() {
        $this->supported_units[1000000]['name'] = __('Millions', 'quality-cost-calculator');
        $this->supported_units[1000000]['description'] = __('Values in millions', 'quality-cost-calculator');
        
        $this->supported_units[1000000000]['name'] = __('Billions', 'quality-cost-calculator');
        $this->supported_units[1000000000]['description'] = __('Values in billions', 'quality-cost-calculator');
    }
    
    /**
     * Currency-Optionen für Select generieren
     * 
     * @since 3.0.0
     * @param bool $show_flags Include flag emojis
     * @param bool $show_symbols Include currency symbols
     * @return array Currency options
     */
    public function get_currency_options($show_flags = true, $show_symbols = true) {
        $options = array();
        
        foreach ($this->supported_currencies as $code => $info) {
            $label_parts = array();
            
            if ($show_flags && !empty($info['flag'])) {
                $label_parts[] = $info['flag'];
            }
            
            $label_parts[] = $info['name'];
            
            if ($show_symbols) {
                $label_parts[] = '(' . $info['symbol'] . ')';
            }
            
            $options[$code] = implode(' ', $label_parts);
        }
        
        return $options;
    }
    
    /**
     * Unit-Optionen für Select generieren
     * 
     * @since 3.0.0
     * @param string $currency Currency code für filtering
     * @return array Unit options
     */
    public function get_unit_options($currency = null) {
        $options = array();
        $available_units = $this->supported_units;
        
        // Currency-spezifische Filterung
        if ($currency && isset($this->supported_currencies[$currency]['default_units'])) {
            $currency_units = $this->supported_currencies[$currency]['default_units'];
            $available_units = array_intersect_key($available_units, array_flip($currency_units));
        }
        
        foreach ($available_units as $value => $info) {
            $options[$value] = sprintf(
                '%s (%s)',
                $info['name'],
                $info['short']
            );
        }
        
        return $options;
    }
    
    /**
     * Data-Mapping zu einzelnem Child
     * 
     * @since 3.0.0
     * @param array $data Original data
     * @param string $child_name Child name
     * @param array $child_definition Child definition
     * @return array Child-specific data
     */
    protected function map_data_to_child($data, $child_name, $child_definition) {
        $base_id = $data['id'] ?? $this->generate_unique_id($data);
        
        switch ($child_name) {
            case 'currency_select':
                return array(
                    'id' => $base_id . '-currency',
                    'name' => ($data['name'] ?? $base_id) . '[currency]',
                    'label' => $data['currency_label'] ?? __('Currency', 'quality-cost-calculator'),
                    'value' => $data['currency'] ?? 'EUR',
                    'options' => $this->get_currency_options(
                        $data['show_flags'] ?? true,
                        $data['show_symbols'] ?? true
                    ),
                    'required' => $data['required'] ?? true,
                    'disabled' => $data['disabled'] ?? false,
                    'class' => 'qcc-currency-selector-currency'
                );
                
            case 'unit_select':
                $currency = $data['currency'] ?? 'EUR';
                return array(
                    'id' => $base_id . '-unit',
                    'name' => ($data['name'] ?? $base_id) . '[unit]',
                    'label' => $data['unit_label'] ?? __('Unit', 'quality-cost-calculator'),
                    'value' => $data['unit'] ?? 1000000,
                    'options' => $this->get_unit_options($currency),
                    'required' => $data['required'] ?? true,
                    'disabled' => $data['disabled'] ?? false,
                    'class' => 'qcc-currency-selector-unit'
                );
                
            case 'preview':
                return array(
                    'id' => $base_id . '-preview',
                    'currency' => $data['currency'] ?? 'EUR',
                    'unit' => $data['unit'] ?? 1000000,
                    'sample_value' => $data['sample_value'] ?? 1.5,
                    'show_preview' => $data['show_preview'] ?? true,
                    'class' => 'qcc-currency-selector-preview'
                );
                
            default:
                return parent::map_data_to_child($data, $child_name, $child_definition);
        }
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
     * Sample-Value formatieren für Preview
     * 
     * @since 3.0.0
     * @param float $value Sample value
     * @param string $currency Currency code
     * @param int $unit Unit multiplier
     * @return string Formatted sample
     */
    public function format_sample_value($value, $currency, $unit) {
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        // Wert in gewählter Unit anzeigen
        $display_value = $value * $unit_info['multiplier'];
        
        // Formatierung
        $formatted = number_format_i18n($display_value, $currency_info['decimal_places']);
        
        // Currency-Symbol hinzufügen
        if ($currency_info['position'] === 'before') {
            $formatted = $currency_info['symbol'] . ' ' . $formatted;
        } else {
            $formatted = $formatted . ' ' . $currency_info['symbol'];
        }
        
        return $formatted;
    }
    
    /**
     * Currency-Selector-Validierung
     * 
     * @since 3.0.0
     * @param array $data Currency-Selector data
     * @return true|WP_Error
     */
    public function validate_currency_selector($data) {
        // Basis-Validierung
        $base_validation = $this->validate_data($data);
        if (is_wp_error($base_validation)) {
            return $base_validation;
        }
        
        // Currency-Validierung
        $currency = $data['currency'] ?? '';
        if (!isset($this->supported_currencies[$currency])) {
            return new WP_Error(
                'currency_invalid',
                sprintf(__('Currency "%s" is not supported', 'quality-cost-calculator'), $currency)
            );
        }
        
        // Unit-Validierung
        $unit = $data['unit'] ?? 0;
        if (!isset($this->supported_units[$unit])) {
            return new WP_Error(
                'unit_invalid',
                sprintf(__('Unit "%s" is not supported', 'quality-cost-calculator'), $unit)
            );
        }
        
        // Currency-Unit-Kompatibilität
        $currency_info = $this->get_currency_info($currency);
        if (isset($currency_info['default_units']) && !in_array($unit, $currency_info['default_units'])) {
            return new WP_Error(
                'currency_unit_incompatible',
                __('Selected unit is not compatible with selected currency', 'quality-cost-calculator')
            );
        }
        
        return true;
    }
    
    /**
     * Currency-Selector-spezifische CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array CSS classes
     */
    public function get_molecule_css_classes($data) {
        $classes = parent::get_molecule_css_classes($data);
        
        // Layout-spezifische Klassen
        $classes[] = 'qcc-currency-selector--' . $this->layout_config['orientation'];
        
        if ($this->layout_config['equal_width']) {
            $classes[] = 'qcc-currency-selector--equal-width';
        }
        
        // Feature-Klassen
        if ($data['show_preview'] ?? true) {
            $classes[] = 'qcc-currency-selector--with-preview';
        }
        
        if ($data['show_flags'] ?? true) {
            $classes[] = 'qcc-currency-selector--with-flags';
        }
        
        return $classes;
    }
    
    /**
     * Container-Attribute für Currency-Selector
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array Container attributes
     */
    public function get_container_attributes($data) {
        $base_attributes = parent::get_container_attributes($data);
        
        $currency_attributes = array(
            'data-currency' => $data['currency'] ?? 'EUR',
            'data-unit' => $data['unit'] ?? 1000000,
            'data-supported-currencies' => wp_json_encode(array_keys($this->supported_currencies)),
            'data-supported-units' => wp_json_encode(array_keys($this->supported_units))
        );
        
        return array_merge($base_attributes, $currency_attributes);
    }
    
    /**
     * JavaScript-Events für Currency-Selector
     * 
     * @since 3.0.0
     * @return array JavaScript events
     */
    public function get_javascript_events() {
        return array(
            'currency_changed' => 'qcc_currency_selector_currency_changed',
            'unit_changed' => 'qcc_currency_selector_unit_changed',
            'selector_reset' => 'qcc_currency_selector_reset',
            'preview_update' => 'qcc_currency_selector_preview_update'
        );
    }
    
    /**
     * Currency-Selector-Werte extrahieren
     * 
     * @since 3.0.0
     * @param array $form_data Form data
     * @param string $field_name Field name
     * @return array Extracted values
     */
    public function extract_values($form_data, $field_name) {
        $field_data = $form_data[$field_name] ?? array();
        
        return array(
            'currency' => $field_data['currency'] ?? 'EUR',
            'unit' => intval($field_data['unit'] ?? 1000000)
        );
    }
    
    /**
     * Default-Werte für Currency-Selector
     * 
     * @since 3.0.0
     * @param string $region Region code für Defaults
     * @return array Default values
     */
    public function get_default_values($region = null) {
        // Region-basierte Defaults
        $region_defaults = array(
            'US' => array('currency' => 'USD', 'unit' => 1000000),
            'GB' => array('currency' => 'GBP', 'unit' => 1000000),
            'CN' => array('currency' => 'CNY', 'unit' => 1000000),
            'JP' => array('currency' => 'JPY', 'unit' => 1000000),
            'EU' => array('currency' => 'EUR', 'unit' => 1000000)
        );
        
        if ($region && isset($region_defaults[$region])) {
            return $region_defaults[$region];
        }
        
        // Browser-Locale-basierte Detection
        $locale = get_locale();
        
        if (strpos($locale, 'en_US') === 0) {
            return $region_defaults['US'];
        } elseif (strpos($locale, 'en_GB') === 0) {
            return $region_defaults['GB'];
        } elseif (strpos($locale, 'zh') === 0) {
            return $region_defaults['CN'];
        } elseif (strpos($locale, 'ja') === 0) {
            return $region_defaults['JP'];
        }
        
        // Default: EUR
        return $region_defaults['EU'];
    }
    
    /**
     * Template-Daten für Currency-Selector vorbereiten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @param array $attributes HTML attributes
     * @return array Template data
     */
    public function prepare_template_data($data, $attributes) {
        $base_data = parent::prepare_template_data($data, $attributes);
        
        $currency = $data['currency'] ?? 'EUR';
        $unit = $data['unit'] ?? 1000000;
        $currency_info = $this->get_currency_info($currency);
        $unit_info = $this->get_unit_info($unit);
        
        $currency_data = array(
            'current_currency' => $currency,
            'current_unit' => $unit,
            'currency_info' => $currency_info,
            'unit_info' => $unit_info,
            'currency_options' => $this->get_currency_options(
                $data['show_flags'] ?? true,
                $data['show_symbols'] ?? true
            ),
            'unit_options' => $this->get_unit_options($currency),
            'sample_formatted' => $this->format_sample_value(
                $data['sample_value'] ?? 1.5,
                $currency,
                $unit
            ),
            'show_preview' => $data['show_preview'] ?? true,
            'show_flags' => $data['show_flags'] ?? true,
            'show_symbols' => $data['show_symbols'] ?? true,
            'supported_currencies' => $this->supported_currencies,
            'supported_units' => $this->supported_units,
            'is_currency_selector' => true
        );
        
        return array_merge($base_data, $currency_data);
    }
    
    /**
     * Required fields für Currency-Selector
     * 
     * @since 3.0.0
     * @return array Required fields
     */
    protected function get_required_fields() {
        return array('id');
    }
    
    /**
     * Assets für Currency-Selector
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $currency_assets = array(
            'css' => array('currency-selector.css'),
            'js' => array('currency-selector.js', 'currency-unit-sync.js'),
            'dependencies' => array('qcc-select-input', 'qcc-currency-formatting')
        );
        
        return array_merge_recursive($base_assets, $currency_assets);
    }
    
    /**
     * Factory-Methode für verschiedene Currency-Selector-Konfigurationen
     * 
     * @since 3.0.0
     * @param string $preset Preset name
     * @param array $data Additional data
     * @return QCC_Currency_Selector Configured selector
     */
    public static function create_preset($preset, $data = array()) {
        $presets = array(
            'simple' => array(
                'show_flags' => false,
                'show_symbols' => true,
                'show_preview' => false
            ),
            'full' => array(
                'show_flags' => true,
                'show_symbols' => true,
                'show_preview' => true
            ),
            'compact' => array(
                'show_flags' => true,
                'show_symbols' => false,
                'show_preview' => false,
                'layout_config' => array('orientation' => 'horizontal', 'equal_width' => true)
            )
        );
        
        if (isset($presets[$preset])) {
            $data = array_merge($presets[$preset], $data);
        }
        
        return new self();
    }
}
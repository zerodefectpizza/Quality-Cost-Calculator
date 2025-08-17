<?php
/**
 * QCC Control Builder Service
 * 
 * Handles generation of control panels including language selection,
 * currency selection, export controls, and other interactive elements.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Builders
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Control_Builder extends QCC_Base_Builder {
    
    private $control_configs = array();
    private $supported_languages = array();
    private $supported_currencies = array();
    private $export_formats = array();
    
    public function __construct() {
        parent::__construct();
        $this->init_control_configs();
    }
    
    /**
     * Build complete control section
     */
    public function build($config = array()) {
        $control_type = $config['type'] ?? 'full';
        
        switch ($control_type) {
            case 'language_only':
                return $this->build_language_controls($config);
            case 'currency_only':
                return $this->build_currency_controls($config);
            case 'export_only':
                return $this->build_export_controls($config);
            case 'settings_only':
                return $this->build_settings_controls($config);
            default:
                return $this->build_full_controls($config);
        }
    }
    
    /**
     * Build language selection controls
     */
    public function build_language_controls($config) {
        $current_language = $config['current_language'] ?? 'en';
        $layout = $config['layout'] ?? 'dropdown';
        
        $controls_html = array();
        
        if ($layout === 'dropdown') {
            $controls_html[] = $this->generate_language_dropdown($current_language, $config);
        } else {
            $controls_html[] = $this->generate_language_flags($current_language, $config);
        }
        
        return $this->wrap_control_section('language', $controls_html);
    }
    
    /**
     * Build currency selection controls
     */
    public function build_currency_controls($config) {
        $current_currency = $config['current_currency'] ?? 'EUR';
        $current_unit = $config['current_unit'] ?? '1000000';
        
        $controls_html = array();
        
        $controls_html[] = $this->generate_currency_selector($current_currency, $config);
        $controls_html[] = $this->generate_unit_selector($current_unit, $config);
        
        return $this->wrap_control_section('currency', $controls_html);
    }
    
    /**
     * Build export controls
     */
    public function build_export_controls($config) {
        $enabled_formats = $config['export_formats'] ?? array('pdf', 'csv', 'json');
        $layout = $config['layout'] ?? 'buttons';
        
        $controls_html = array();
        
        if ($layout === 'dropdown') {
            $controls_html[] = $this->generate_export_dropdown($enabled_formats, $config);
        } else {
            $controls_html[] = $this->generate_export_buttons($enabled_formats, $config);
        }
        
        return $this->wrap_control_section('export', $controls_html);
    }
    
    /**
     * Build settings controls
     */
    public function build_settings_controls($config) {
        $available_settings = $config['settings'] ?? array();
        
        $controls_html = array();
        
        foreach ($available_settings as $setting => $setting_config) {
            $controls_html[] = $this->generate_setting_control($setting, $setting_config);
        }
        
        return $this->wrap_control_section('settings', $controls_html);
    }
    
    /**
     * Generate language dropdown
     */
    private function generate_language_dropdown($current_language, $config) {
        $dropdown_id = 'qcc-language-selector-' . uniqid();
        $options_html = array();
        
        foreach ($this->supported_languages as $code => $language_data) {
            $selected = ($code === $current_language) ? ' selected' : '';
            $options_html[] = sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($code),
                $selected,
                esc_html($language_data['name'])
            );
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-language-control">
                <label for="%s" class="qcc-control-label">
                    %s %s
                </label>
                <select id="%s" name="qcc_language" class="qcc-language-dropdown">
                    %s
                </select>
            </div>',
            esc_attr($dropdown_id),
            $this->get_icon_svg('globe'),
            esc_html($this->translator->get('language')),
            esc_attr($dropdown_id),
            implode("\n", $options_html)
        );
    }
    
    /**
     * Generate language flags
     */
    private function generate_language_flags($current_language, $config) {
        $flags_html = array();
        
        foreach ($this->supported_languages as $code => $language_data) {
            $active_class = ($code === $current_language) ? ' qcc-flag-active' : '';
            
            $flags_html[] = sprintf(
                '<button type="button" class="qcc-language-flag%s" data-language="%s" title="%s">
                    <img src="%s" alt="%s" width="24" height="16">
                    <span class="qcc-flag-label">%s</span>
                </button>',
                $active_class,
                esc_attr($code),
                esc_attr($language_data['name']),
                esc_url($language_data['flag_url']),
                esc_attr($language_data['name']),
                esc_html($language_data['short'])
            );
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-language-flags">
                <div class="qcc-control-label">
                    %s %s
                </div>
                <div class="qcc-flags-container">
                    %s
                </div>
            </div>',
            $this->get_icon_svg('globe'),
            esc_html($this->translator->get('language')),
            implode("\n", $flags_html)
        );
    }
    
    /**
     * Generate currency selector
     */
    private function generate_currency_selector($current_currency, $config) {
        $selector_id = 'qcc-currency-selector-' . uniqid();
        $options_html = array();
        
        foreach ($this->supported_currencies as $code => $currency_data) {
            $selected = ($code === $current_currency) ? ' selected' : '';
            $options_html[] = sprintf(
                '<option value="%s"%s>%s (%s)</option>',
                esc_attr($code),
                $selected,
                esc_html($currency_data['symbol']),
                esc_html($currency_data['name'])
            );
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-currency-control">
                <label for="%s" class="qcc-control-label">
                    %s %s
                </label>
                <select id="%s" name="qcc_currency" class="qcc-currency-dropdown">
                    %s
                </select>
            </div>',
            esc_attr($selector_id),
            $this->get_icon_svg('dollar-sign'),
            esc_html($this->translator->get('currency')),
            esc_attr($selector_id),
            implode("\n", $options_html)
        );
    }
    
    /**
     * Generate unit selector
     */
    private function generate_unit_selector($current_unit, $config) {
        $selector_id = 'qcc-unit-selector-' . uniqid();
        $units = array(
            '1000000' => $this->translator->get('millions'),
            '1000000000' => $this->translator->get('billions')
        );
        
        $options_html = array();
        foreach ($units as $value => $label) {
            $selected = ($value === $current_unit) ? ' selected' : '';
            $options_html[] = sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                $selected,
                esc_html($label)
            );
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-unit-control">
                <label for="%s" class="qcc-control-label">
                    %s %s
                </label>
                <select id="%s" name="qcc_unit" class="qcc-unit-dropdown">
                    %s
                </select>
            </div>',
            esc_attr($selector_id),
            $this->get_icon_svg('hash'),
            esc_html($this->translator->get('unit')),
            esc_attr($selector_id),
            implode("\n", $options_html)
        );
    }
    
    /**
     * Generate export buttons
     */
    private function generate_export_buttons($enabled_formats, $config) {
        $buttons_html = array();
        
        $export_configs = array(
            'pdf' => array(
                'label' => 'export_pdf',
                'icon' => 'file-text',
                'class' => 'qcc-export-pdf',
                'color' => '#DC3545'
            ),
            'csv' => array(
                'label' => 'export_csv',
                'icon' => 'download',
                'class' => 'qcc-export-csv',
                'color' => '#28A745'
            ),
            'json' => array(
                'label' => 'export_json',
                'icon' => 'code',
                'class' => 'qcc-export-json',
                'color' => '#6F42C1'
            ),
            'print' => array(
                'label' => 'print',
                'icon' => 'printer',
                'class' => 'qcc-print',
                'color' => '#6C757D'
            )
        );
        
        foreach ($enabled_formats as $format) {
            if (isset($export_configs[$format])) {
                $config_data = $export_configs[$format];
                $buttons_html[] = sprintf(
                    '<button type="button" class="qcc-export-button %s" data-export-type="%s" style="border-color: %s;">
                        %s
                        <span>%s</span>
                    </button>',
                    esc_attr($config_data['class']),
                    esc_attr($format),
                    esc_attr($config_data['color']),
                    $this->get_icon_svg($config_data['icon']),
                    esc_html($this->translator->get($config_data['label']))
                );
            }
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-export-controls">
                <div class="qcc-control-label">
                    %s %s
                </div>
                <div class="qcc-export-buttons">
                    %s
                </div>
            </div>',
            $this->get_icon_svg('share'),
            esc_html($this->translator->get('export')),
            implode("\n", $buttons_html)
        );
    }
    
    /**
     * Generate export dropdown
     */
    private function generate_export_dropdown($enabled_formats, $config) {
        $dropdown_id = 'qcc-export-selector-' . uniqid();
        $options_html = array('<option value="">' . esc_html($this->translator->get('select_export_format')) . '</option>');
        
        $export_labels = array(
            'pdf' => 'export_pdf',
            'csv' => 'export_csv',
            'json' => 'export_json',
            'print' => 'print'
        );
        
        foreach ($enabled_formats as $format) {
            if (isset($export_labels[$format])) {
                $options_html[] = sprintf(
                    '<option value="%s">%s</option>',
                    esc_attr($format),
                    esc_html($this->translator->get($export_labels[$format]))
                );
            }
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-export-dropdown-control">
                <label for="%s" class="qcc-control-label">
                    %s %s
                </label>
                <select id="%s" name="qcc_export" class="qcc-export-dropdown">
                    %s
                </select>
                <button type="button" class="qcc-export-execute" disabled>
                    %s %s
                </button>
            </div>',
            esc_attr($dropdown_id),
            $this->get_icon_svg('share'),
            esc_html($this->translator->get('export')),
            esc_attr($dropdown_id),
            implode("\n", $options_html),
            $this->get_icon_svg('download'),
            esc_html($this->translator->get('download'))
        );
    }
    
    /**
     * Generate setting control
     */
    private function generate_setting_control($setting_key, $setting_config) {
        $control_type = $setting_config['type'] ?? 'toggle';
        $current_value = $setting_config['value'] ?? false;
        $control_id = 'qcc-setting-' . $setting_key . '-' . uniqid();
        
        switch ($control_type) {
            case 'toggle':
                return $this->generate_toggle_control($control_id, $setting_key, $setting_config, $current_value);
            case 'select':
                return $this->generate_select_control($control_id, $setting_key, $setting_config, $current_value);
            case 'number':
                return $this->generate_number_control($control_id, $setting_key, $setting_config, $current_value);
            default:
                return '';
        }
    }
    
    /**
     * Generate toggle control
     */
    private function generate_toggle_control($control_id, $setting_key, $config, $current_value) {
        $checked = $current_value ? ' checked' : '';
        
        return sprintf(
            '<div class="qcc-control-group qcc-toggle-control">
                <label for="%s" class="qcc-toggle-label">
                    <input type="checkbox" id="%s" name="qcc_setting_%s" class="qcc-toggle-input"%s>
                    <span class="qcc-toggle-slider"></span>
                    <span class="qcc-toggle-text">%s</span>
                </label>
                %s
            </div>',
            esc_attr($control_id),
            esc_attr($control_id),
            esc_attr($setting_key),
            $checked,
            esc_html($this->translator->get($config['label'])),
            isset($config['description']) ? '<small class="qcc-control-description">' . esc_html($this->translator->get($config['description'])) . '</small>' : ''
        );
    }
    
    /**
     * Generate select control
     */
    private function generate_select_control($control_id, $setting_key, $config, $current_value) {
        $options_html = array();
        
        foreach ($config['options'] as $value => $label) {
            $selected = ($value === $current_value) ? ' selected' : '';
            $options_html[] = sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                $selected,
                esc_html($this->translator->get($label))
            );
        }
        
        return sprintf(
            '<div class="qcc-control-group qcc-select-control">
                <label for="%s" class="qcc-control-label">%s</label>
                <select id="%s" name="qcc_setting_%s" class="qcc-setting-select">
                    %s
                </select>
                %s
            </div>',
            esc_attr($control_id),
            esc_html($this->translator->get($config['label'])),
            esc_attr($control_id),
            esc_attr($setting_key),
            implode("\n", $options_html),
            isset($config['description']) ? '<small class="qcc-control-description">' . esc_html($this->translator->get($config['description'])) . '</small>' : ''
        );
    }
    
    /**
     * Generate number control
     */
    private function generate_number_control($control_id, $setting_key, $config, $current_value) {
        $min = isset($config['min']) ? ' min="' . esc_attr($config['min']) . '"' : '';
        $max = isset($config['max']) ? ' max="' . esc_attr($config['max']) . '"' : '';
        $step = isset($config['step']) ? ' step="' . esc_attr($config['step']) . '"' : '';
        
        return sprintf(
            '<div class="qcc-control-group qcc-number-control">
                <label for="%s" class="qcc-control-label">%s</label>
                <input type="number" id="%s" name="qcc_setting_%s" class="qcc-setting-number" value="%s"%s%s%s>
                %s
            </div>',
            esc_attr($control_id),
            esc_html($this->translator->get($config['label'])),
            esc_attr($control_id),
            esc_attr($setting_key),
            esc_attr($current_value),
            $min,
            $max,
            $step,
            isset($config['description']) ? '<small class="qcc-control-description">' . esc_html($this->translator->get($config['description'])) . '</small>' : ''
        );
    }
    
    /**
     * Wrap control section
     */
    private function wrap_control_section($section_type, $controls_html) {
        return sprintf(
            '<div class="qcc-control-section qcc-%s-controls">%s</div>',
            esc_attr($section_type),
            implode("\n", $controls_html)
        );
    }
    
    /**
     * Build full controls combining all components
     */
    private function build_full_controls($config) {
        $sections = array();
        
        if ($config['include_language'] ?? true) {
            $sections[] = $this->build_language_controls($config);
        }
        
        if ($config['include_currency'] ?? true) {
            $sections[] = $this->build_currency_controls($config);
        }
        
        if ($config['include_export'] ?? true) {
            $sections[] = $this->build_export_controls($config);
        }
        
        if ($config['include_settings'] ?? false) {
            $sections[] = $this->build_settings_controls($config);
        }
        
        return sprintf(
            '<div class="qcc-controls-panel">
                <div class="qcc-controls-header">
                    <h3 class="qcc-controls-title">%s %s</h3>
                </div>
                <div class="qcc-controls-body">
                    %s
                </div>
            </div>',
            $this->get_icon_svg('settings'),
            esc_html($this->translator->get('controls')),
            implode("\n", $sections)
        );
    }
    
    /**
     * Initialize control configurations
     */
    private function init_control_configs() {
        $this->supported_languages = array(
            'en' => array('name' => 'English', 'short' => 'EN', 'flag_url' => $this->get_flag_url('en')),
            'de' => array('name' => 'Deutsch', 'short' => 'DE', 'flag_url' => $this->get_flag_url('de')),
            'fr' => array('name' => 'Français', 'short' => 'FR', 'flag_url' => $this->get_flag_url('fr')),
            'es' => array('name' => 'Español', 'short' => 'ES', 'flag_url' => $this->get_flag_url('es')),
            'zh' => array('name' => '中文', 'short' => 'ZH', 'flag_url' => $this->get_flag_url('zh'))
        );
        
        $this->supported_currencies = array(
            'EUR' => array('name' => 'Euro', 'symbol' => '€'),
            'USD' => array('name' => 'US Dollar', 'symbol' => '$'),
            'CNY' => array('name' => 'Chinese Yuan', 'symbol' => '¥')
        );
        
        $this->export_formats = array('pdf', 'csv', 'json', 'print');
    }
    
    /**
     * Get flag URL for language
     */
    private function get_flag_url($language_code) {
        return plugins_url('assets/images/flags/' . $language_code . '.png', QCC_PLUGIN_FILE);
    }
    
    /**
     * Get SVG icon
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'globe' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="m2 12c0 2.8 1.1 5.3 3 7.2c1.9 1.9 4.4 3 7.2 3"></path></svg>',
            'dollar-sign' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
            'hash' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>',
            'share' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16,6 12,2 8,6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>',
            'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m11-7h-6m-6 0H1m17-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM7 21a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"></path></svg>',
            'file-text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10,9 9,9 8,9"></polyline></svg>',
            'download' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7,10 12,15 17,10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
            'code' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"></polyline><polyline points="8,6 2,12 8,18"></polyline></svg>',
            'printer' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 6,2 18,2 18,9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get required dependencies
     */
    public function get_dependencies() {
        return array('translator', 'template_manager', 'asset_manager');
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-control-builder.css'),
            'js' => array('qcc-controls.js'),
            'dependencies' => array()
        );
    }
}
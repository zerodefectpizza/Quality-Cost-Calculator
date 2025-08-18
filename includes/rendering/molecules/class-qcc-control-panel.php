<?php
/**
 * QCC Control Panel Molecule
 * 
 * Molecule component that combines multiple control elements
 * including language, currency, export controls and settings.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Molecules
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Control_Panel extends QCC_Base_Molecule {
    
    protected $template_name = 'molecules/control-panel.php';
    protected $css_base_class = 'qcc-control-panel';
    protected $required_fields = array();
    
    private $display_factory;
    private $control_builder;
    
    public function __construct() {
        parent::__construct();
        $this->display_factory = QCC_Service_Container::get('display_factory');
        $this->control_builder = QCC_Service_Container::get('control_builder');
    }
    
    /**
     * Get child components for this molecule
     */
    public function get_child_components() {
        return array('button', 'select_input');
    }
    
    /**
     * Render complete control panel
     */
    public function render($data = array(), $attributes = array()) {
        $panel_config = $this->prepare_panel_config($data, $attributes);
        return $this->generate_panel_html($panel_config);
    }
    
    /**
     * Prepare panel configuration
     */
    private function prepare_panel_config($data, $attributes) {
        return array(
            'title' => $data['title'] ?? $this->translator->get('controls'),
            'layout' => $data['layout'] ?? 'horizontal', // 'horizontal', 'vertical', 'compact'
            'collapsible' => $data['collapsible'] ?? false,
            'collapsed' => $data['collapsed'] ?? false,
            'show_title' => $data['show_title'] ?? true,
            
            // Control sections
            'language_controls' => $data['language_controls'] ?? true,
            'currency_controls' => $data['currency_controls'] ?? true,
            'export_controls' => $data['export_controls'] ?? true,
            'settings_controls' => $data['settings_controls'] ?? false,
            'reset_controls' => $data['reset_controls'] ?? true,
            
            // Current values
            'current_language' => $data['current_language'] ?? 'en',
            'current_currency' => $data['current_currency'] ?? 'EUR',
            'current_unit' => $data['current_unit'] ?? '1000000',
            
            // Available options
            'available_languages' => $data['available_languages'] ?? array('en', 'de', 'fr', 'es', 'zh'),
            'available_currencies' => $data['available_currencies'] ?? array('EUR', 'USD', 'CNY'),
            'export_formats' => $data['export_formats'] ?? array('pdf', 'csv', 'json'),
            
            // Settings
            'settings' => $data['settings'] ?? array(),
            
            'css_classes' => $this->build_css_classes($data),
            'attributes' => array_merge($this->get_default_attributes(), $attributes)
        );
    }
    
    /**
     * Generate panel HTML
     */
    private function generate_panel_html($config) {
        $panel_parts = array();
        
        // Panel header
        if ($config['show_title'] || $config['collapsible']) {
            $panel_parts[] = $this->render_panel_header($config);
        }
        
        // Panel content
        $panel_parts[] = $this->render_panel_content($config);
        
        return sprintf(
            '<div class="%s" %s>%s</div>',
            esc_attr($config['css_classes']),
            $this->build_attributes_string($config['attributes']),
            implode("\n", $panel_parts)
        );
    }
    
    /**
     * Render panel header
     */
    private function render_panel_header($config) {
        $header_parts = array();
        
        if ($config['show_title']) {
            $header_parts[] = sprintf(
                '<h3 class="qcc-control-panel-title">%s %s</h3>',
                $this->get_icon_svg('settings'),
                esc_html($config['title'])
            );
        }
        
        if ($config['collapsible']) {
            $header_parts[] = $this->display_factory->create_button(array(
                'label' => $this->translator->get($config['collapsed'] ? 'expand' : 'collapse'),
                'icon' => $config['collapsed'] ? 'chevron-down' : 'chevron-up',
                'variant' => 'ghost',
                'size' => 'small',
                'action' => 'toggle_panel',
                'aria_expanded' => !$config['collapsed']
            ));
        }
        
        return sprintf(
            '<div class="qcc-control-panel-header">%s</div>',
            implode("\n", $header_parts)
        );
    }
    
    /**
     * Render panel content
     */
    private function render_panel_content($config) {
        $content_sections = array();
        
        // Language controls section
        if ($config['language_controls']) {
            $content_sections[] = $this->render_language_section($config);
        }
        
        // Currency controls section
        if ($config['currency_controls']) {
            $content_sections[] = $this->render_currency_section($config);
        }
        
        // Export controls section
        if ($config['export_controls']) {
            $content_sections[] = $this->render_export_section($config);
        }
        
        // Settings controls section
        if ($config['settings_controls']) {
            $content_sections[] = $this->render_settings_section($config);
        }
        
        // Reset controls section
        if ($config['reset_controls']) {
            $content_sections[] = $this->render_reset_section($config);
        }
        
        $collapsed_class = $config['collapsed'] ? ' qcc-panel-content--collapsed' : '';
        
        return sprintf(
            '<div class="qcc-control-panel-content%s">%s</div>',
            $collapsed_class,
            implode("\n", $content_sections)
        );
    }
    
    /**
     * Render language controls section
     */
    private function render_language_section($config) {
        $language_options = array();
        
        $language_names = array(
            'en' => 'English',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español',
            'zh' => '中文'
        );
        
        foreach ($config['available_languages'] as $lang_code) {
            $language_options[] = sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($lang_code),
                ($lang_code === $config['current_language']) ? ' selected' : '',
                esc_html($language_names[$lang_code] ?? $lang_code)
            );
        }
        
        return sprintf(
            '<div class="qcc-control-section qcc-language-section">
                <label class="qcc-control-label">
                    %s %s
                </label>
                <select name="qcc_language" class="qcc-language-select" data-action="change_language">
                    %s
                </select>
            </div>',
            $this->get_icon_svg('globe'),
            esc_html($this->translator->get('language')),
            implode("\n", $language_options)
        );
    }
    
    /**
     * Render currency controls section
     */
    private function render_currency_section($config) {
        $currency_options = array();
        
        $currency_names = array(
            'EUR' => '€ Euro',
            'USD' => '$ US Dollar',
            'CNY' => '¥ Chinese Yuan'
        );
        
        foreach ($config['available_currencies'] as $currency_code) {
            $currency_options[] = sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($currency_code),
                ($currency_code === $config['current_currency']) ? ' selected' : '',
                esc_html($currency_names[$currency_code] ?? $currency_code)
            );
        }
        
        $unit_options = array(
            '<option value="1000000"' . ($config['current_unit'] === '1000000' ? ' selected' : '') . '>' . esc_html($this->translator->get('millions')) . '</option>',
            '<option value="1000000000"' . ($config['current_unit'] === '1000000000' ? ' selected' : '') . '>' . esc_html($this->translator->get('billions')) . '</option>'
        );
        
        return sprintf(
            '<div class="qcc-control-section qcc-currency-section">
                <div class="qcc-control-group">
                    <label class="qcc-control-label">
                        %s %s
                    </label>
                    <select name="qcc_currency" class="qcc-currency-select" data-action="change_currency">
                        %s
                    </select>
                </div>
                <div class="qcc-control-group">
                    <label class="qcc-control-label">
                        %s %s
                    </label>
                    <select name="qcc_unit" class="qcc-unit-select" data-action="change_unit">
                        %s
                    </select>
                </div>
            </div>',
            $this->get_icon_svg('dollar-sign'),
            esc_html($this->translator->get('currency')),
            implode("\n", $currency_options),
            $this->get_icon_svg('hash'),
            esc_html($this->translator->get('unit')),
            implode("\n", $unit_options)
        );
    }
    
    /**
     * Render export controls section
     */
    private function render_export_section($config) {
        $export_buttons = array();
        
        $export_configs = array(
            'pdf' => array('label' => 'export_pdf', 'icon' => 'file-text', 'color' => '#DC3545'),
            'csv' => array('label' => 'export_csv', 'icon' => 'download', 'color' => '#28A745'),
            'json' => array('label' => 'export_json', 'icon' => 'code', 'color' => '#6F42C1')
        );
        
        foreach ($config['export_formats'] as $format) {
            if (isset($export_configs[$format])) {
                $export_config = $export_configs[$format];
                $export_buttons[] = $this->display_factory->create_button(array(
                    'label' => $this->translator->get($export_config['label']),
                    'icon' => $export_config['icon'],
                    'variant' => 'outline',
                    'size' => 'small',
                    'action' => 'export_' . $format
                ));
            }
        }
        
        return sprintf(
            '<div class="qcc-control-section qcc-export-section">
                <div class="qcc-control-label">
                    %s %s
                </div>
                <div class="qcc-export-buttons">
                    %s
                </div>
            </div>',
            $this->get_icon_svg('share'),
            esc_html($this->translator->get('export')),
            implode("\n", $export_buttons)
        );
    }
    
    /**
     * Render settings controls section
     */
    private function render_settings_section($config) {
        if (empty($config['settings'])) {
            return '';
        }
        
        $setting_controls = array();
        
        foreach ($config['settings'] as $setting_key => $setting_config) {
            $setting_controls[] = $this->render_setting_control($setting_key, $setting_config);
        }
        
        return sprintf(
            '<div class="qcc-control-section qcc-settings-section">
                <div class="qcc-control-label">
                    %s %s
                </div>
                <div class="qcc-settings-controls">
                    %s
                </div>
            </div>',
            $this->get_icon_svg('sliders'),
            esc_html($this->translator->get('settings')),
            implode("\n", $setting_controls)
        );
    }
    
    /**
     * Render setting control
     */
    private function render_setting_control($setting_key, $setting_config) {
        $control_type = $setting_config['type'] ?? 'toggle';
        $control_id = 'qcc-setting-' . $setting_key;
        
        switch ($control_type) {
            case 'toggle':
                return $this->render_toggle_setting($control_id, $setting_key, $setting_config);
            case 'select':
                return $this->render_select_setting($control_id, $setting_key, $setting_config);
            case 'range':
                return $this->render_range_setting($control_id, $setting_key, $setting_config);
            default:
                return '';
        }
    }
    
    /**
     * Render toggle setting
     */
    private function render_toggle_setting($control_id, $setting_key, $config) {
        $checked = ($config['value'] ?? false) ? ' checked' : '';
        
        return sprintf(
            '<div class="qcc-setting-control qcc-toggle-setting">
                <label for="%s" class="qcc-toggle-label">
                    <input type="checkbox" id="%s" name="qcc_setting_%s" class="qcc-toggle-input"%s data-action="toggle_setting">
                    <span class="qcc-toggle-slider"></span>
                    <span class="qcc-toggle-text">%s</span>
                </label>
            </div>',
            esc_attr($control_id),
            esc_attr($control_id),
            esc_attr($setting_key),
            $checked,
            esc_html($this->translator->get($config['label']))
        );
    }
    
    /**
     * Render select setting
     */
    private function render_select_setting($control_id, $setting_key, $config) {
        $options_html = array();
        
        foreach ($config['options'] as $value => $label) {
            $selected = ($value === ($config['value'] ?? '')) ? ' selected' : '';
            $options_html[] = sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                $selected,
                esc_html($this->translator->get($label))
            );
        }
        
        return sprintf(
            '<div class="qcc-setting-control qcc-select-setting">
                <label for="%s" class="qcc-setting-label">%s</label>
                <select id="%s" name="qcc_setting_%s" class="qcc-setting-select" data-action="change_setting">
                    %s
                </select>
            </div>',
            esc_attr($control_id),
            esc_html($this->translator->get($config['label'])),
            esc_attr($control_id),
            esc_attr($setting_key),
            implode("\n", $options_html)
        );
    }
    
    /**
     * Render range setting
     */
    private function render_range_setting($control_id, $setting_key, $config) {
        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 100;
        $step = $config['step'] ?? 1;
        $value = $config['value'] ?? $min;
        
        return sprintf(
            '<div class="qcc-setting-control qcc-range-setting">
                <label for="%s" class="qcc-setting-label">%s</label>
                <div class="qcc-range-wrapper">
                    <input type="range" id="%s" name="qcc_setting_%s" class="qcc-range-input" 
                           min="%s" max="%s" step="%s" value="%s" data-action="change_setting">
                    <span class="qcc-range-value">%s</span>
                </div>
            </div>',
            esc_attr($control_id),
            esc_html($this->translator->get($config['label'])),
            esc_attr($control_id),
            esc_attr($setting_key),
            esc_attr($min),
            esc_attr($max),
            esc_attr($step),
            esc_attr($value),
            esc_html($value)
        );
    }
    
    /**
     * Render reset controls section
     */
    private function render_reset_section($config) {
        $reset_buttons = array();
        
        // Reset values button
        $reset_buttons[] = $this->display_factory->create_button(array(
            'label' => $this->translator->get('reset_values'),
            'icon' => 'refresh-cw',
            'variant' => 'outline',
            'size' => 'small',
            'action' => 'reset_values'
        ));
        
        // Reset to defaults button
        $reset_buttons[] = $this->display_factory->create_button(array(
            'label' => $this->translator->get('reset_defaults'),
            'icon' => 'rotate-ccw',
            'variant' => 'outline',
            'size' => 'small',
            'action' => 'reset_defaults'
        ));
        
        return sprintf(
            '<div class="qcc-control-section qcc-reset-section">
                <div class="qcc-control-label">
                    %s %s
                </div>
                <div class="qcc-reset-buttons">
                    %s
                </div>
            </div>',
            $this->get_icon_svg('rotate-ccw'),
            esc_html($this->translator->get('reset')),
            implode("\n", $reset_buttons)
        );
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes($data) {
        $classes = array($this->css_base_class);
        
        // Layout class
        $layout = $data['layout'] ?? 'horizontal';
        $classes[] = $this->css_base_class . '--' . sanitize_html_class($layout);
        
        // Collapsible class
        if ($data['collapsible'] ?? false) {
            $classes[] = $this->css_base_class . '--collapsible';
        }
        
        // Collapsed class
        if ($data['collapsed'] ?? false) {
            $classes[] = $this->css_base_class . '--collapsed';
        }
        
        // Section count class
        $section_count = 0;
        $section_count += ($data['language_controls'] ?? true) ? 1 : 0;
        $section_count += ($data['currency_controls'] ?? true) ? 1 : 0;
        $section_count += ($data['export_controls'] ?? true) ? 1 : 0;
        $section_count += ($data['settings_controls'] ?? false) ? 1 : 0;
        $section_count += ($data['reset_controls'] ?? true) ? 1 : 0;
        
        $classes[] = $this->css_base_class . '--sections-' . $section_count;
        
        // Custom classes
        if (!empty($data['css_classes'])) {
            if (is_array($data['css_classes'])) {
                $classes = array_merge($classes, $data['css_classes']);
            } else {
                $classes[] = $data['css_classes'];
            }
        }
        
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Build attributes string
     */
    private function build_attributes_string($attributes) {
        $attr_string = '';
        
        foreach ($attributes as $name => $value) {
            if ($name !== 'class') {
                $attr_string .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
            }
        }
        
        return $attr_string;
    }
    
    /**
     * Get icon SVG
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m11-7h-6m-6 0H1m17-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM7 21a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"></path></svg>',
            'globe' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="m2 12c0 2.8 1.1 5.3 3 7.2c1.9 1.9 4.4 3 7.2 3"></path></svg>',
            'dollar-sign' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
            'hash' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>',
            'share' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16,6 12,2 8,6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>',
            'sliders' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>',
            'rotate-ccw' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1,4 1,10 7,10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>',
            'refresh-cw' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,4 23,10 17,10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>',
            'chevron-up' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18,15 12,9 6,15"></polyline></svg>',
            'chevron-down' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 12,15 18,9"></polyline></svg>',
            'file-text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10,9 9,9 8,9"></polyline></svg>',
            'download' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7,10 12,15 17,10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
            'code' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16,18 22,12 16,6"></polyline><polyline points="8,6 2,12 8,18"></polyline></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get layout template for molecule
     */
    public function get_layout_template() {
        return $this->template_name;
    }
    
    /**
     * Map data to children components
     */
    public function map_data_to_children($data) {
        return array(
            'button' => $data['buttons'] ?? array(),
            'select_input' => $data['selects'] ?? array()
        );
    }
    
    /**
     * Assemble child components
     */
    public function assemble_components($components) {
        return implode("\n", $components);
    }
    
    /**
     * Get required fields for validation
     */
    protected function get_required_fields() {
        return $this->required_fields;
    }
    
    /**
     * Get default attributes
     */
    public function get_default_attributes() {
        return array('class' => $this->css_base_class);
    }
    
    /**
     * Get template name
     */
    public function get_template_name() {
        return $this->template_name;
    }
    
    /**
     * Sanitize input value
     */
    public function sanitize_input($value) {
        if (is_array($value)) {
            return array_map(array($this, 'sanitize_input'), $value);
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-control-panel.css'),
            'js' => array('qcc-control-panel.js'),
            'dependencies' => array()
        );
    }
}
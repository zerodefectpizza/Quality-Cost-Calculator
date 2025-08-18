<?php
/**
 * QCC Input Factory
 * 
 * Factory for creating input atom components with proper configuration
 * and dependency injection.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms\Factories
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Input_Factory {
    
    private $component_registry;
    private $translator;
    private $input_types = array();
    private $default_configs = array();
    
    public function __construct() {
        $this->component_registry = QCC_Service_Container::get('component_registry');
        $this->translator = QCC_Service_Container::get('translator');
        $this->init_input_types();
        $this->init_default_configs();
    }
    
    /**
     * Create input component by type
     */
    public function create($type, $config = array()) {
        if (!$this->is_supported_type($type)) {
            throw new Exception("Unsupported input type: {$type}");
        }
        
        $input_config = $this->prepare_input_config($type, $config);
        $component = $this->get_input_component($type);
        
        return $component->render($input_config['data'], $input_config['attributes']);
    }
    
    /**
     * Create percentage input
     */
    public function create_percentage($config = array()) {
        return $this->create('percentage', $config);
    }
    
    /**
     * Create currency input
     */
    public function create_currency($config = array()) {
        return $this->create('currency', $config);
    }
    
    /**
     * Create select input
     */
    public function create_select($config = array()) {
        return $this->create('select', $config);
    }
    
    /**
     * Create text input
     */
    public function create_text($config = array()) {
        return $this->create('text', $config);
    }
    
    /**
     * Create input group with label and validation
     */
    public function create_input_group($type, $config = array()) {
        $input_html = $this->create($type, $config);
        
        $group_parts = array();
        
        // Label
        if (!empty($config['label'])) {
            $group_parts[] = $this->create_label($config);
        }
        
        // Input
        $group_parts[] = $input_html;
        
        // Help text
        if (!empty($config['help'])) {
            $group_parts[] = $this->create_help_text($config['help']);
        }
        
        // Validation message
        if (!empty($config['error'])) {
            $group_parts[] = $this->create_error_message($config['error']);
        }
        
        return $this->wrap_input_group($group_parts, $config);
    }
    
    /**
     * Create multiple related inputs
     */
    public function create_input_set($inputs_config) {
        $input_set = array();
        
        foreach ($inputs_config as $input_config) {
            $type = $input_config['type'];
            unset($input_config['type']);
            
            if (!empty($input_config['group'])) {
                $input_set[] = $this->create_input_group($type, $input_config);
            } else {
                $input_set[] = $this->create($type, $input_config);
            }
        }
        
        return $this->wrap_input_set($input_set);
    }
    
    /**
     * Get supported input types
     */
    public function get_supported_types() {
        return array_keys($this->input_types);
    }
    
    /**
     * Check if input type is supported
     */
    public function is_supported_type($type) {
        return isset($this->input_types[$type]);
    }
    
    /**
     * Get input component instance
     */
    private function get_input_component($type) {
        $component_name = $this->input_types[$type]['component'];
        return $this->component_registry->get($component_name);
    }
    
    /**
     * Prepare input configuration
     */
    private function prepare_input_config($type, $config) {
        $default_config = $this->default_configs[$type] ?? array();
        $merged_config = array_merge($default_config, $config);
        
        return array(
            'data' => $this->extract_data_config($merged_config),
            'attributes' => $this->extract_attributes_config($merged_config)
        );
    }
    
    /**
     * Extract data configuration
     */
    private function extract_data_config($config) {
        $data_keys = array(
            'id', 'name', 'value', 'label', 'placeholder', 'required', 'disabled', 'readonly',
            'min', 'max', 'step', 'pattern', 'options', 'multiple', 'currency', 'unit',
            'precision', 'validation', 'error', 'help', 'icon', 'size'
        );
        
        $data = array();
        foreach ($data_keys as $key) {
            if (isset($config[$key])) {
                $data[$key] = $config[$key];
            }
        }
        
        return $data;
    }
    
    /**
     * Extract attributes configuration
     */
    private function extract_attributes_config($config) {
        $attribute_keys = array(
            'class', 'style', 'data-*', 'aria-*', 'autocomplete', 'autofocus', 'tabindex'
        );
        
        $attributes = array();
        foreach ($config as $key => $value) {
            if (in_array($key, $attribute_keys) || 
                strpos($key, 'data-') === 0 || 
                strpos($key, 'aria-') === 0) {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Create label element
     */
    private function create_label($config) {
        $label_text = $config['label'];
        $required_indicator = '';
        
        if (!empty($config['required'])) {
            $required_indicator = ' <span class="qcc-required" aria-label="required">*</span>';
        }
        
        return sprintf(
            '<label class="qcc-input-label" for="%s">%s%s</label>',
            esc_attr($config['id'] ?? ''),
            esc_html($label_text),
            $required_indicator
        );
    }
    
    /**
     * Create help text
     */
    private function create_help_text($help_text) {
        return sprintf(
            '<small class="qcc-input-help">%s</small>',
            esc_html($help_text)
        );
    }
    
    /**
     * Create error message
     */
    private function create_error_message($error_text) {
        return sprintf(
            '<div class="qcc-input-error" role="alert">%s %s</div>',
            $this->get_icon_svg('alert-circle'),
            esc_html($error_text)
        );
    }
    
    /**
     * Wrap input group
     */
    private function wrap_input_group($group_parts, $config) {
        $group_classes = array('qcc-input-group');
        
        if (!empty($config['error'])) {
            $group_classes[] = 'qcc-input-group--error';
        }
        
        if (!empty($config['required'])) {
            $group_classes[] = 'qcc-input-group--required';
        }
        
        if (!empty($config['size'])) {
            $group_classes[] = 'qcc-input-group--' . sanitize_html_class($config['size']);
        }
        
        return sprintf(
            '<div class="%s">%s</div>',
            esc_attr(implode(' ', $group_classes)),
            implode("\n", $group_parts)
        );
    }
    
    /**
     * Wrap input set
     */
    private function wrap_input_set($input_set) {
        return sprintf(
            '<div class="qcc-input-set">%s</div>',
            implode("\n", $input_set)
        );
    }
    
    /**
     * Create COGQ input section
     */
    public function create_cogq_inputs($default_values = array()) {
        return $this->create_input_set(array(
            array(
                'type' => 'percentage',
                'id' => 'qcc_prevention',
                'name' => 'prevention',
                'label' => $this->translator->get('prevention_costs'),
                'value' => $default_values['prevention'] ?? 10,
                'help' => $this->translator->get('prevention_costs_help'),
                'group' => true,
                'required' => true
            ),
            array(
                'type' => 'percentage',
                'id' => 'qcc_appraisal',
                'name' => 'appraisal',
                'label' => $this->translator->get('appraisal_costs'),
                'value' => $default_values['appraisal'] ?? 20,
                'help' => $this->translator->get('appraisal_costs_help'),
                'group' => true,
                'required' => true
            )
        ));
    }
    
    /**
     * Create COPQ input section
     */
    public function create_copq_inputs($default_values = array()) {
        return $this->create_input_set(array(
            array(
                'type' => 'percentage',
                'id' => 'qcc_internal_defect',
                'name' => 'internal_defect',
                'label' => $this->translator->get('internal_defect_costs'),
                'value' => $default_values['internal_defect'] ?? 30,
                'help' => $this->translator->get('internal_defect_costs_help'),
                'group' => true,
                'required' => true
            ),
            array(
                'type' => 'percentage',
                'id' => 'qcc_external_defect',
                'name' => 'external_defect',
                'label' => $this->translator->get('external_defect_costs'),
                'value' => $default_values['external_defect'] ?? 40,
                'help' => $this->translator->get('external_defect_costs_help'),
                'group' => true,
                'required' => true
            )
        ));
    }
    
    /**
     * Create revenue and quality percentage inputs
     */
    public function create_base_inputs($default_values = array()) {
        return $this->create_input_set(array(
            array(
                'type' => 'currency',
                'id' => 'qcc_revenue',
                'name' => 'revenue',
                'label' => $this->translator->get('revenue'),
                'value' => $default_values['revenue'] ?? 140,
                'help' => $this->translator->get('revenue_help'),
                'group' => true,
                'required' => true
            ),
            array(
                'type' => 'percentage',
                'id' => 'qcc_quality_percentage',
                'name' => 'quality_percentage',
                'label' => $this->translator->get('quality_percentage'),
                'value' => $default_values['quality_percentage'] ?? 6,
                'help' => $this->translator->get('quality_percentage_help'),
                'group' => true,
                'required' => true
            )
        ));
    }
    
    /**
     * Initialize input types
     */
    private function init_input_types() {
        $this->input_types = array(
            'percentage' => array(
                'component' => 'percentage_input',
                'validation' => array('min' => 0, 'max' => 100)
            ),
            'currency' => array(
                'component' => 'currency_input',
                'validation' => array('min' => 0)
            ),
            'select' => array(
                'component' => 'select_input',
                'validation' => array()
            ),
            'text' => array(
                'component' => 'text_input',
                'validation' => array()
            )
        );
    }
    
    /**
     * Initialize default configurations
     */
    private function init_default_configs() {
        $this->default_configs = array(
            'percentage' => array(
                'min' => 0,
                'max' => 100,
                'step' => 0.01,
                'precision' => 2
            ),
            'currency' => array(
                'min' => 0,
                'step' => 0.01,
                'precision' => 2
            ),
            'select' => array(
                'options' => array()
            ),
            'text' => array(
                'maxlength' => 255
            )
        );
    }
    
    /**
     * Get icon SVG
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'alert-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get required dependencies
     */
    public function get_dependencies() {
        return array('component_registry', 'translator');
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-input-factory.css'),
            'js' => array('qcc-input-factory.js'),
            'dependencies' => array()
        );
    }
}
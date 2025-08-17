<?php
/**
 * QCC Input Group Molecule
 * 
 * Molecule-Component für Input-Groups: Label + Input + Validation + Help.
 * Kombiniert Atoms zu funktionalen Form-Elementen.
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
 * Class QCC_Input_Group
 * 
 * @since 3.0.0
 */
class QCC_Input_Group extends QCC_Base_Molecule {
    
    /**
     * Child-Components Definition
     * 
     * @since 3.0.0
     * @var array
     */
    protected $child_components = array(
        'label' => array(
            'type' => 'atom',
            'class' => 'QCC_Label',
            'required' => true
        ),
        'input' => array(
            'type' => 'atom',
            'class' => 'QCC_Input',
            'required' => true
        ),
        'validation' => array(
            'type' => 'atom',
            'class' => 'QCC_Validation_Message',
            'required' => false
        ),
        'help' => array(
            'type' => 'atom',
            'class' => 'QCC_Help_Text',
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
        'orientation' => 'vertical',
        'spacing' => 'medium',
        'alignment' => 'left',
        'wrap_children' => true,
        'label_position' => 'top'
    );
    
    /**
     * Molecule-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $molecule_type = 'form';
    
    /**
     * Event-Propagation-Rules
     * 
     * @since 3.0.0
     * @var array
     */
    protected $event_propagation_rules = array(
        'input.change' => array('validation.validate'),
        'input.blur' => array('validation.display', 'help.hide'),
        'input.focus' => array('help.show'),
        'input.invalid' => array('validation.show_error'),
        'input.valid' => array('validation.show_success')
    );
    
    /**
     * Input-Type-Mapping
     * 
     * @since 3.0.0
     * @var array
     */
    protected $input_type_mapping = array(
        'percentage' => 'QCC_Percentage_Input',
        'currency' => 'QCC_Currency_Input',
        'select' => 'QCC_Select_Input',
        'text' => 'QCC_Text_Input',
        'number' => 'QCC_Number_Input'
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_input_group_specific();
    }
    
    /**
     * Input-Group-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_input_group_specific() {
        // Template-Pfad setzen
        $this->layout_template = 'molecules/input-group.php';
        
        // Dynamic Input-Type-Support
        $this->setup_dynamic_input_types();
    }
    
    /**
     * Dynamic Input-Types setup
     * 
     * @since 3.0.0
     * @return void
     */
    private function setup_dynamic_input_types() {
        // Child-Component für Input dynamisch setzen basierend auf input_type
        add_filter('qcc_input_group_child_components', array($this, 'resolve_input_type'), 10, 2);
    }
    
    /**
     * Input-Type auflösen
     * 
     * @since 3.0.0
     * @param array $children Current children
     * @param array $data Component data
     * @return array Updated children
     */
    public function resolve_input_type($children, $data) {
        $input_type = $data['input_type'] ?? 'text';
        
        if (isset($this->input_type_mapping[$input_type])) {
            $children['input']['class'] = $this->input_type_mapping[$input_type];
        }
        
        return $children;
    }
    
    /**
     * Child-Components mit Dynamic-Input-Support
     * 
     * @since 3.0.0
     * @return array Child component definitions
     */
    public function get_child_components() {
        return apply_filters('qcc_input_group_child_components', $this->child_components, array());
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
            case 'label':
                return array(
                    'id' => $base_id . '-label',
                    'for' => $base_id,
                    'text' => $data['label'] ?? '',
                    'required' => $data['required'] ?? false,
                    'class' => 'qcc-input-group-label'
                );
                
            case 'input':
                $input_data = array(
                    'id' => $base_id,
                    'name' => $data['name'] ?? $base_id,
                    'label' => $data['label'] ?? '',
                    'value' => $data['value'] ?? '',
                    'placeholder' => $data['placeholder'] ?? '',
                    'required' => $data['required'] ?? false,
                    'disabled' => $data['disabled'] ?? false,
                    'readonly' => $data['readonly'] ?? false,
                    'class' => 'qcc-input-group-input'
                );
                
                // Input-type-spezifische Daten
                $input_type = $data['input_type'] ?? 'text';
                
                if ($input_type === 'percentage') {
                    $input_data = array_merge($input_data, array(
                        'min' => $data['min'] ?? 0,
                        'max' => $data['max'] ?? 100,
                        'step' => $data['step'] ?? 0.01
                    ));
                } elseif ($input_type === 'currency') {
                    $input_data = array_merge($input_data, array(
                        'currency' => $data['currency'] ?? 'EUR',
                        'unit' => $data['unit'] ?? 1000000,
                        'min' => $data['min'] ?? 0
                    ));
                } elseif ($input_type === 'select') {
                    $input_data = array_merge($input_data, array(
                        'options' => $data['options'] ?? array(),
                        'multiple' => $data['multiple'] ?? false
                    ));
                }
                
                return $input_data;
                
            case 'validation':
                return array(
                    'id' => $base_id . '-validation',
                    'target_field' => $base_id,
                    'show_success' => $data['show_success'] ?? true,
                    'show_errors' => $data['show_errors'] ?? true,
                    'validation_rules' => $data['validation_rules'] ?? array(),
                    'class' => 'qcc-input-group-validation'
                );
                
            case 'help':
                return array(
                    'id' => $base_id . '-help',
                    'text' => $data['help_text'] ?? $data['description'] ?? '',
                    'target_field' => $base_id,
                    'show_on_focus' => $data['show_help_on_focus'] ?? true,
                    'class' => 'qcc-input-group-help'
                );
                
            default:
                return parent::map_data_to_child($data, $child_name, $child_definition);
        }
    }
    
    /**
     * Template-basierte Assembly mit Input-Group-Layout
     * 
     * @since 3.0.0
     * @param array $rendered_children Rendered children
     * @param array $data Original data
     * @return string Template HTML
     */
    protected function render_template_assembly($rendered_children, $data) {
        $template_vars = array(
            'children' => $rendered_children,
            'data' => $data,
            'molecule' => $this,
            'container_attributes' => $this->get_container_attributes($data),
            'css_classes' => $this->get_input_group_css_classes($data),
            'layout_config' => $this->get_layout_config($data),
            'field_id' => $data['id'] ?? $this->generate_unique_id($data),
            'input_type' => $data['input_type'] ?? 'text',
            'is_required' => $data['required'] ?? false,
            'has_error' => !empty($data['error']),
            'error_message' => $data['error'] ?? '',
            'label_position' => $this->layout_config['label_position']
        );
        
        if ($this->template_manager && $this->template_manager->template_exists($this->layout_template)) {
            return $this->template_manager->render($this->layout_template, $template_vars);
        }
        
        return $this->render_input_group_fallback($rendered_children, $template_vars);
    }
    
    /**
     * Input-Group-spezifisches Fallback-Layout
     * 
     * @since 3.0.0
     * @param array $rendered_children Rendered children
     * @param array $template_vars Template variables
     * @return string Fallback HTML
     */
    protected function render_input_group_fallback($rendered_children, $template_vars) {
        $container_attrs = $template_vars['container_attributes'];
        $attr_string = '';
        
        foreach ($container_attrs as $key => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<div%s>', $attr_string);
        
        // Label
        if (!empty($rendered_children['label'])) {
            $html .= $rendered_children['label'];
        }
        
        // Input-Wrapper
        $html .= '<div class="qcc-input-group-input-wrapper">';
        $html .= $rendered_children['input'] ?? '';
        
        // Inline-Validation
        if (!empty($rendered_children['validation'])) {
            $html .= $rendered_children['validation'];
        }
        
        $html .= '</div>';
        
        // Help-Text
        if (!empty($rendered_children['help'])) {
            $html .= $rendered_children['help'];
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Input-Group-spezifische CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array CSS classes
     */
    public function get_input_group_css_classes($data) {
        $classes = $this->get_molecule_css_classes($data);
        
        // Input-Type-spezifische Klassen
        $input_type = $data['input_type'] ?? 'text';
        $classes[] = 'qcc-input-group--' . $input_type;
        
        // State-Klassen
        if (!empty($data['error'])) {
            $classes[] = 'qcc-input-group--error';
        }
        
        if (!empty($data['success'])) {
            $classes[] = 'qcc-input-group--success';
        }
        
        if ($data['required'] ?? false) {
            $classes[] = 'qcc-input-group--required';
        }
        
        if ($data['disabled'] ?? false) {
            $classes[] = 'qcc-input-group--disabled';
        }
        
        // Label-Position
        $label_position = $this->layout_config['label_position'];
        $classes[] = 'qcc-input-group--label-' . $label_position;
        
        return $classes;
    }
    
    /**
     * Container-Attribute für Input-Group
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array Container attributes
     */
    public function get_container_attributes($data) {
        $base_attributes = parent::get_container_attributes($data);
        
        $input_group_attributes = array(
            'data-input-type' => $data['input_type'] ?? 'text',
            'data-required' => $data['required'] ?? false ? 'true' : 'false'
        );
        
        if (!empty($data['validation_rules'])) {
            $input_group_attributes['data-validation'] = wp_json_encode($data['validation_rules']);
        }
        
        return array_merge($base_attributes, $input_group_attributes);
    }
    
    /**
     * Input-Group-Validierung
     * 
     * @since 3.0.0
     * @param array $data Input-Group data
     * @return true|WP_Error
     */
    public function validate_input_group($data) {
        // Basis-Validierung
        $base_validation = $this->validate_data($data);
        if (is_wp_error($base_validation)) {
            return $base_validation;
        }
        
        // Required-Field-Check
        if (($data['required'] ?? false) && empty($data['value'])) {
            return new WP_Error(
                'input_group_required',
                sprintf(__('Field "%s" is required', 'quality-cost-calculator'), $data['label'] ?? 'Unknown')
            );
        }
        
        // Input-Type-spezifische Validierung
        $input_type = $data['input_type'] ?? 'text';
        $input_child = $this->get_child('input');
        
        if ($input_child && method_exists($input_child, 'validate_value')) {
            $input_validation = $input_child->validate_value($data['value'] ?? '', 'value');
            if (is_wp_error($input_validation)) {
                return $input_validation;
            }
        }
        
        return true;
    }
    
    /**
     * Input-Group-Wert extrahieren
     * 
     * @since 3.0.0
     * @param array $form_data Form data
     * @param string $field_name Field name
     * @return mixed Field value
     */
    public function extract_value($form_data, $field_name) {
        $input_child = $this->get_child('input');
        
        if ($input_child && method_exists($input_child, 'parse_input')) {
            return $input_child->parse_input($form_data[$field_name] ?? '', 'value');
        }
        
        return $form_data[$field_name] ?? '';
    }
    
    /**
     * Input-Group-spezifische Event-Handling
     * 
     * @since 3.0.0
     * @return array JavaScript event handlers
     */
    public function get_javascript_events() {
        return array(
            'qcc_input_group_change' => 'qcc_handle_input_group_change',
            'qcc_input_group_validate' => 'qcc_validate_input_group',
            'qcc_input_group_focus' => 'qcc_input_group_focus_handler',
            'qcc_input_group_blur' => 'qcc_input_group_blur_handler'
        );
    }
    
    /**
     * Layout-Config mit Input-Group-spezifischen Optionen
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array Layout configuration
     */
    public function get_layout_config($data) {
        $config = parent::get_layout_config($data);
        
        // Label-Position basierend auf Input-Type
        $input_type = $data['input_type'] ?? 'text';
        
        if (in_array($input_type, array('checkbox', 'radio'))) {
            $config['label_position'] = 'after';
        } elseif ($input_type === 'hidden') {
            $config['label_position'] = 'none';
        }
        
        // Responsive-Anpassungen
        if ($data['responsive'] ?? true) {
            $config['mobile_layout'] = 'stacked';
            $config['tablet_layout'] = 'inline';
        }
        
        return $config;
    }
    
    /**
     * Required fields für Input-Group
     * 
     * @since 3.0.0
     * @return array Required fields
     */
    protected function get_required_fields() {
        return array('id', 'label', 'input_type');
    }
    
    /**
     * Assets mit Input-Type-spezifischen Dependencies
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $input_group_assets = array(
            'css' => array('input-group.css'),
            'js' => array('input-group.js', 'input-group-validation.js'),
            'dependencies' => array('qcc-form-validation', 'qcc-event-handling')
        );
        
        return array_merge_recursive($base_assets, $input_group_assets);
    }
    
    /**
     * Kollabierbar-Status für komplexe Input-Groups
     * 
     * @since 3.0.0
     * @return bool
     */
    public function is_collapsible() {
        return false; // Input-Groups sind normalerweise nicht kollabierbar
    }
    
    /**
     * Factory-Methode für verschiedene Input-Group-Typen
     * 
     * @since 3.0.0
     * @param string $input_type Input type
     * @param array $data Base data
     * @return QCC_Input_Group Configured input group
     */
    public static function create_for_type($input_type, $data = array()) {
        $data['input_type'] = $input_type;
        
        // Type-spezifische Defaults
        switch ($input_type) {
            case 'percentage':
                $data = array_merge(array(
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01,
                    'help_text' => __('Enter percentage between 0-100%', 'quality-cost-calculator')
                ), $data);
                break;
                
            case 'currency':
                $data = array_merge(array(
                    'currency' => 'EUR',
                    'unit' => 1000000,
                    'min' => 0,
                    'help_text' => __('Enter currency amount', 'quality-cost-calculator')
                ), $data);
                break;
        }
        
        $instance = new self();
        return $instance;
    }
}
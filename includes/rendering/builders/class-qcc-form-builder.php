<?php
/**
 * QCC Form Builder
 * 
 * Builder-Component für QCC Calculator-Formulare.
 * Erstellt komplette COGQ/COPQ/Opportunity-Form-Sections.
 * 
 * @package QualityCostCalculator
 * @subpackage Builders
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QCC_Form_Builder
 * 
 * @since 3.0.0
 */
class QCC_Form_Builder extends QCC_Base_Builder {
    
    /**
     * Sub-Sections Definition
     * 
     * @since 3.0.0
     * @var array
     */
    protected $sub_sections = array(
        'header' => array(
            'title' => 'Calculator Settings',
            'molecules' => array('currency_selector'),
            'required' => true,
            'order' => 1,
            'collapsible' => false
        ),
        'cogq' => array(
            'title' => 'Cost of Good Quality (COGQ)',
            'molecules' => array('prevention_input_group', 'appraisal_input_group'),
            'required' => true,
            'order' => 2,
            'collapsible' => true,
            'description' => 'Costs invested to prevent and detect quality issues'
        ),
        'copq' => array(
            'title' => 'Cost of Poor Quality (COPQ)',
            'molecules' => array('internal_defect_group', 'external_defect_group'),
            'required' => true,
            'order' => 3,
            'collapsible' => true,
            'description' => 'Costs resulting from quality failures'
        ),
        'opportunity' => array(
            'title' => 'Opportunity Costs',
            'molecules' => array('lost_sales_group', 'customer_churn_group', 'market_share_group'),
            'required' => false,
            'order' => 4,
            'collapsible' => true,
            'description' => 'Additional costs from quality-related losses'
        ),
        'validation' => array(
            'title' => 'Validation Summary',
            'molecules' => array('validation_summary'),
            'required' => false,
            'order' => 5,
            'collapsible' => false
        )
    );
    
    /**
     * Builder-Dependencies
     * 
     * @since 3.0.0
     * @var array
     */
    protected $dependencies = array(
        'services' => array('translator', 'validator', 'calculator', 'template_manager'),
        'molecules' => array('input_group', 'currency_selector'),
        'atoms' => array('percentage_input', 'currency_input')
    );
    
    /**
     * Builder-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $builder_type = 'form';
    
    /**
     * Form-spezifische Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    protected $form_config = array(
        'method' => 'POST',
        'action' => '',
        'enctype' => 'application/x-www-form-urlencoded',
        'novalidate' => false,
        'autocomplete' => 'off',
        'live_validation' => true,
        'auto_save' => true,
        'show_progress' => true
    );
    
    /**
     * Field-Definitionen für alle Sections
     * 
     * @since 3.0.0
     * @var array
     */
    protected $field_definitions = array(
        'revenue' => array(
            'type' => 'currency',
            'label' => 'Total Revenue',
            'description' => 'Annual company revenue',
            'required' => true,
            'validation' => array('min' => 1, 'type' => 'currency'),
            'default' => 140000000,
            'section' => 'header'
        ),
        'quality_percentage' => array(
            'type' => 'percentage',
            'label' => 'Quality Cost Percentage',
            'description' => 'Percentage of revenue allocated to quality costs',
            'required' => true,
            'validation' => array('min' => 0.1, 'max' => 25, 'type' => 'percentage'),
            'default' => 6,
            'section' => 'header'
        ),
        'prevention' => array(
            'type' => 'percentage',
            'label' => 'Prevention Costs',
            'description' => 'Percentage allocated to quality prevention',
            'required' => true,
            'validation' => array('min' => 0, 'max' => 100, 'type' => 'percentage'),
            'default' => 10,
            'section' => 'cogq'
        ),
        'appraisal' => array(
            'type' => 'percentage',
            'label' => 'Appraisal Costs',
            'description' => 'Percentage allocated to quality assessment',
            'required' => true,
            'validation' => array('min' => 0, 'max' => 100, 'type' => 'percentage'),
            'default' => 20,
            'section' => 'cogq'
        ),
        'internal_defect' => array(
            'type' => 'percentage',
            'label' => 'Internal Defect Costs',
            'description' => 'Percentage of costs from internal quality failures',
            'required' => true,
            'validation' => array('min' => 0, 'max' => 100, 'type' => 'percentage'),
            'default' => 30,
            'section' => 'copq'
        ),
        'external_defect' => array(
            'type' => 'percentage',
            'label' => 'External Defect Costs',
            'description' => 'Percentage of costs from external quality failures',
            'required' => true,
            'validation' => array('min' => 0, 'max' => 100, 'type' => 'percentage'),
            'default' => 40,
            'section' => 'copq'
        ),
        'lost_sales' => array(
            'type' => 'percentage',
            'label' => 'Lost Sales',
            'description' => 'Revenue lost due to quality issues',
            'required' => false,
            'validation' => array('min' => 0, 'max' => 50, 'type' => 'percentage'),
            'default' => 5,
            'section' => 'opportunity'
        ),
        'customer_churn' => array(
            'type' => 'percentage',
            'label' => 'Customer Churn',
            'description' => 'Customer loss due to quality issues',
            'required' => false,
            'validation' => array('min' => 0, 'max' => 25, 'type' => 'percentage'),
            'default' => 2,
            'section' => 'opportunity'
        ),
        'market_share_loss' => array(
            'type' => 'percentage',
            'label' => 'Market Share Loss',
            'description' => 'Market position lost due to quality',
            'required' => false,
            'validation' => array('min' => 0, 'max' => 20, 'type' => 'percentage'),
            'default' => 1,
            'section' => 'opportunity'
        )
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_form_builder_specific();
    }
    
    /**
     * Form-Builder-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_form_builder_specific() {
        // Field-Definitionen lokalisieren
        $this->localize_field_definitions();
        
        // Form-Action setzen
        $this->form_config['action'] = admin_url('admin-ajax.php');
        
        // Hooks registrieren
        $this->register_form_hooks();
    }
    
    /**
     * Field-Definitionen lokalisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function localize_field_definitions() {
        foreach ($this->field_definitions as $key => &$definition) {
            $definition['label'] = __($definition['label'], 'quality-cost-calculator');
            $definition['description'] = __($definition['description'], 'quality-cost-calculator');
        }
        
        foreach ($this->sub_sections as $key => &$section) {
            $section['title'] = __($section['title'], 'quality-cost-calculator');
            if (isset($section['description'])) {
                $section['description'] = __($section['description'], 'quality-cost-calculator');
            }
        }
    }
    
    /**
     * Form-spezifische Hooks registrieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_form_hooks() {
        // AJAX-Handler für Form-Submission
        add_action('wp_ajax_qcc_form_submit', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_qcc_form_submit', array($this, 'handle_form_submission'));
        
        // AJAX-Handler für Live-Validation
        add_action('wp_ajax_qcc_validate_field', array($this, 'handle_field_validation'));
        add_action('wp_ajax_nopriv_qcc_validate_field', array($this, 'handle_field_validation'));
    }
    
    /**
     * Section-Data für spezifische Section ermitteln
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $config Build configuration
     * @return array Section data
     */
    protected function get_section_data($section_name, $config) {
        $base_data = parent::get_section_data($section_name, $config);
        $form_data = $config['form_data'] ?? array();
        $currency = $config['currency'] ?? 'EUR';
        $unit = $config['unit'] ?? 1000000;
        
        switch ($section_name) {
            case 'header':
                return array_merge($base_data, array(
                    'currency_selector' => array(
                        'id' => 'qcc-currency-selector',
                        'name' => 'qcc_currency',
                        'currency' => $currency,
                        'unit' => $unit,
                        'show_preview' => true
                    ),
                    'revenue_field' => $this->get_field_data('revenue', $form_data),
                    'quality_percentage_field' => $this->get_field_data('quality_percentage', $form_data)
                ));
                
            case 'cogq':
                return array_merge($base_data, array(
                    'prevention_field' => $this->get_field_data('prevention', $form_data),
                    'appraisal_field' => $this->get_field_data('appraisal', $form_data),
                    'section_total' => $this->calculate_section_total(array('prevention', 'appraisal'), $form_data),
                    'validation_rule' => 'cogq_percentage_sum'
                ));
                
            case 'copq':
                return array_merge($base_data, array(
                    'internal_defect_field' => $this->get_field_data('internal_defect', $form_data),
                    'external_defect_field' => $this->get_field_data('external_defect', $form_data),
                    'section_total' => $this->calculate_section_total(array('internal_defect', 'external_defect'), $form_data),
                    'validation_rule' => 'copq_percentage_sum'
                ));
                
            case 'opportunity':
                return array_merge($base_data, array(
                    'lost_sales_field' => $this->get_field_data('lost_sales', $form_data),
                    'customer_churn_field' => $this->get_field_data('customer_churn', $form_data),
                    'market_share_loss_field' => $this->get_field_data('market_share_loss', $form_data),
                    'section_total' => $this->calculate_section_total(array('lost_sales', 'customer_churn', 'market_share_loss'), $form_data),
                    'is_optional' => true
                ));
                
            case 'validation':
                return array_merge($base_data, array(
                    'total_percentage' => $this->calculate_total_percentage($form_data),
                    'validation_status' => $this->get_validation_status($form_data),
                    'missing_fields' => $this->get_missing_required_fields($form_data)
                ));
                
            default:
                return $base_data;
        }
    }
    
    /**
     * Field-Data für Template vorbereiten
     * 
     * @since 3.0.0
     * @param string $field_name Field name
     * @param array $form_data Current form data
     * @return array Field data
     */
    private function get_field_data($field_name, $form_data) {
        $definition = $this->field_definitions[$field_name] ?? array();
        $value = $form_data[$field_name] ?? $definition['default'] ?? '';
        
        return array(
            'id' => 'qcc-' . $field_name,
            'name' => 'qcc_form[' . $field_name . ']',
            'label' => $definition['label'] ?? ucfirst(str_replace('_', ' ', $field_name)),
            'description' => $definition['description'] ?? '',
            'value' => $value,
            'input_type' => $definition['type'] ?? 'text',
            'required' => $definition['required'] ?? false,
            'validation_rules' => $definition['validation'] ?? array(),
            'field_name' => $field_name
        );
    }
    
    /**
     * Section-Total berechnen
     * 
     * @since 3.0.0
     * @param array $field_names Field names für Total
     * @param array $form_data Form data
     * @return float Section total
     */
    private function calculate_section_total($field_names, $form_data) {
        $total = 0;
        
        foreach ($field_names as $field_name) {
            $value = $form_data[$field_name] ?? $this->field_definitions[$field_name]['default'] ?? 0;
            $total += floatval($value);
        }
        
        return $total;
    }
    
    /**
     * Gesamt-Percentage berechnen
     * 
     * @since 3.0.0
     * @param array $form_data Form data
     * @return float Total percentage
     */
    private function calculate_total_percentage($form_data) {
        $percentage_fields = array('prevention', 'appraisal', 'internal_defect', 'external_defect');
        return $this->calculate_section_total($percentage_fields, $form_data);
    }
    
    /**
     * Validation-Status ermitteln
     * 
     * @since 3.0.0
     * @param array $form_data Form data
     * @return array Validation status
     */
    private function get_validation_status($form_data) {
        $total_percentage = $this->calculate_total_percentage($form_data);
        $is_valid = abs($total_percentage - 100) < 0.01; // Floating-point tolerance
        
        return array(
            'is_valid' => $is_valid,
            'total_percentage' => $total_percentage,
            'message' => $is_valid 
                ? __('All percentages add up correctly to 100%', 'quality-cost-calculator')
                : sprintf(__('Percentages add up to %.2f%% instead of 100%%', 'quality-cost-calculator'), $total_percentage)
        );
    }
    
    /**
     * Fehlende Required-Fields ermitteln
     * 
     * @since 3.0.0
     * @param array $form_data Form data
     * @return array Missing fields
     */
    private function get_missing_required_fields($form_data) {
        $missing = array();
        
        foreach ($this->field_definitions as $field_name => $definition) {
            if ($definition['required'] && empty($form_data[$field_name])) {
                $missing[] = array(
                    'name' => $field_name,
                    'label' => $definition['label'],
                    'section' => $definition['section']
                );
            }
        }
        
        return $missing;
    }
    
    /**
     * Form-Submission-Handler
     * 
     * @since 3.0.0
     * @return void
     */
    public function handle_form_submission() {
        // Nonce-Verification
        if (!wp_verify_nonce($_POST['qcc_form_nonce'] ?? '', 'qcc_form_submit')) {
            wp_die(__('Security check failed', 'quality-cost-calculator'));
        }
        
        $form_data = $_POST['qcc_form'] ?? array();
        
        // Form-Validation
        $validation_result = $this->validate_form_data($form_data);
        
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array(
                'message' => $validation_result->get_error_message(),
                'errors' => $validation_result->get_error_data()
            ));
            return;
        }
        
        // Berechnungen durchführen
        $calculator = $this->service_container->get('calculator');
        $results = $calculator->calculate_quality_costs($form_data);
        
        // Erfolgreiche Response
        wp_send_json_success(array(
            'message' => __('Form submitted successfully', 'quality-cost-calculator'),
            'results' => $results,
            'form_data' => $form_data
        ));
    }
    
    /**
     * Field-Validation-Handler
     * 
     * @since 3.0.0
     * @return void
     */
    public function handle_field_validation() {
        $field_name = $_POST['field_name'] ?? '';
        $field_value = $_POST['field_value'] ?? '';
        
        if (empty($field_name)) {
            wp_send_json_error(array('message' => 'Field name required'));
            return;
        }
        
        $validation_result = $this->validate_field($field_name, $field_value);
        
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array(
                'field' => $field_name,
                'message' => $validation_result->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'field' => $field_name,
                'message' => __('Field is valid', 'quality-cost-calculator')
            ));
        }
    }
    
    /**
     * Form-Data validieren
     * 
     * @since 3.0.0
     * @param array $form_data Form data
     * @return true|WP_Error
     */
    public function validate_form_data($form_data) {
        $errors = array();
        
        // Einzelne Felder validieren
        foreach ($this->field_definitions as $field_name => $definition) {
            $value = $form_data[$field_name] ?? '';
            $field_validation = $this->validate_field($field_name, $value);
            
            if (is_wp_error($field_validation)) {
                $errors[$field_name] = $field_validation->get_error_message();
            }
        }
        
        // Percentage-Sum-Validation
        $percentage_sum = $this->calculate_total_percentage($form_data);
        if (abs($percentage_sum - 100) > 0.01) {
            $errors['percentage_sum'] = sprintf(
                __('All percentages must add up to 100%%. Current total: %.2f%%', 'quality-cost-calculator'),
                $percentage_sum
            );
        }
        
        if (!empty($errors)) {
            return new WP_Error('form_validation_failed', __('Form validation failed', 'quality-cost-calculator'), $errors);
        }
        
        return true;
    }
    
    /**
     * Einzelnes Field validieren
     * 
     * @since 3.0.0
     * @param string $field_name Field name
     * @param mixed $value Field value
     * @return true|WP_Error
     */
    public function validate_field($field_name, $value) {
        if (!isset($this->field_definitions[$field_name])) {
            return new WP_Error('field_not_found', 'Field definition not found');
        }
        
        $definition = $this->field_definitions[$field_name];
        
        // Required-Check
        if ($definition['required'] && empty($value)) {
            return new WP_Error('field_required', sprintf(__('Field "%s" is required', 'quality-cost-calculator'), $definition['label']));
        }
        
        // Type-spezifische Validation
        $validation_rules = $definition['validation'] ?? array();
        
        if ($definition['type'] === 'percentage') {
            if (!is_numeric($value) || $value < 0 || $value > 100) {
                return new WP_Error('invalid_percentage', __('Percentage must be between 0 and 100', 'quality-cost-calculator'));
            }
        }
        
        if ($definition['type'] === 'currency') {
            if (!is_numeric($value) || $value < 0) {
                return new WP_Error('invalid_currency', __('Currency value must be positive', 'quality-cost-calculator'));
            }
        }
        
        // Min/Max-Validation
        if (isset($validation_rules['min']) && $value < $validation_rules['min']) {
            return new WP_Error('value_too_low', sprintf(__('Value must be at least %s', 'quality-cost-calculator'), $validation_rules['min']));
        }
        
        if (isset($validation_rules['max']) && $value > $validation_rules['max']) {
            return new WP_Error('value_too_high', sprintf(__('Value must be at most %s', 'quality-cost-calculator'), $validation_rules['max']));
        }
        
        return true;
    }
    
    /**
     * Section-Template mit Form-spezifischen Daten rendern
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $template_data Template data
     * @return string Section HTML
     */
    protected function render_section_template($section_name, $template_data) {
        $template_path = 'builders/sections/form-' . $section_name . '.php';
        
        if ($this->template_manager && $this->template_manager->template_exists($template_path)) {
            return $this->template_manager->render($template_path, $template_data);
        }
        
        return $this->render_form_section_fallback($section_name, $template_data);
    }
    
    /**
     * Form-Section-Fallback-Rendering
     * 
     * @since 3.0.0
     * @param string $section_name Section name
     * @param array $template_data Template data
     * @return string Fallback HTML
     */
    protected function render_form_section_fallback($section_name, $template_data) {
        $section_def = $template_data['section_definition'];
        $section_data = $template_data['data'];
        
        $html = sprintf(
            '<fieldset class="qcc-form-section qcc-form-section--%s">',
            esc_attr($section_name)
        );
        
        // Section-Header
        $html .= sprintf('<legend class="qcc-form-section-title">%s</legend>', esc_html($section_def['title']));
        
        if (!empty($section_def['description'])) {
            $html .= sprintf('<p class="qcc-form-section-description">%s</p>', esc_html($section_def['description']));
        }
        
        // Section-Content basierend auf Type
        switch ($section_name) {
            case 'header':
                $html .= $this->render_header_section_content($section_data);
                break;
                
            case 'cogq':
            case 'copq':
                $html .= $this->render_percentage_section_content($section_data, $section_name);
                break;
                
            case 'opportunity':
                $html .= $this->render_opportunity_section_content($section_data);
                break;
                
            case 'validation':
                $html .= $this->render_validation_section_content($section_data);
                break;
        }
        
        $html .= '</fieldset>';
        
        return $html;
    }
    
    /**
     * Header-Section-Content rendern
     * 
     * @since 3.0.0
     * @param array $data Section data
     * @return string HTML
     */
    private function render_header_section_content($data) {
        // Currency-Selector würde hier gerendert werden
        return '<div class="qcc-header-section">Currency and basic settings would go here</div>';
    }
    
    /**
     * Percentage-Section-Content rendern
     * 
     * @since 3.0.0
     * @param array $data Section data
     * @param string $section_name Section name
     * @return string HTML
     */
    private function render_percentage_section_content($data, $section_name) {
        return sprintf(
            '<div class="qcc-percentage-section">%s section inputs would go here</div>',
            esc_html(ucfirst($section_name))
        );
    }
    
    /**
     * Opportunity-Section-Content rendern
     * 
     * @since 3.0.0
     * @param array $data Section data
     * @return string HTML
     */
    private function render_opportunity_section_content($data) {
        return '<div class="qcc-opportunity-section">Opportunity cost inputs would go here</div>';
    }
    
    /**
     * Validation-Section-Content rendern
     * 
     * @since 3.0.0
     * @param array $data Section data
     * @return string HTML
     */
    private function render_validation_section_content($data) {
        $status = $data['validation_status'] ?? array();
        $is_valid = $status['is_valid'] ?? false;
        
        return sprintf(
            '<div class="qcc-validation-section %s">
                <p class="qcc-validation-message">%s</p>
            </div>',
            $is_valid ? 'qcc-valid' : 'qcc-invalid',
            esc_html($status['message'] ?? '')
        );
    }
    
    /**
     * Builder-HTML mit Form-Wrapper assemblieren
     * 
     * @since 3.0.0
     * @param array $rendered_sections Rendered sections
     * @param array $config Build configuration
     * @return string Complete form HTML
     */
    protected function assemble_builder_html($rendered_sections, $config) {
        $form_attributes = array(
            'id' => 'qcc-calculator-form',
            'class' => $this->get_form_css_classes($config),
            'method' => $this->form_config['method'],
            'action' => $this->form_config['action'],
            'enctype' => $this->form_config['enctype']
        );
        
        if ($this->form_config['novalidate']) {
            $form_attributes['novalidate'] = 'novalidate';
        }
        
        if ($this->form_config['autocomplete'] === 'off') {
            $form_attributes['autocomplete'] = 'off';
        }
        
        $form_attr_string = '';
        foreach ($form_attributes as $key => $value) {
            $form_attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<form%s>', $form_attr_string);
        
        // Hidden Fields
        $html .= wp_nonce_field('qcc_form_submit', 'qcc_form_nonce', true, false);
        $html .= '<input type="hidden" name="action" value="qcc_form_submit">';
        
        // Sections
        foreach ($rendered_sections as $section_name => $section_html) {
            $html .= $section_html;
        }
        
        // Submit-Button
        $html .= '<div class="qcc-form-submit-section">';
        $html .= sprintf(
            '<button type="submit" class="qcc-form-submit-button">%s</button>',
            __('Calculate Quality Costs', 'quality-cost-calculator')
        );
        $html .= '</div>';
        
        $html .= '</form>';
        
        return $html;
    }
    
    /**
     * Form-CSS-Klassen generieren
     * 
     * @since 3.0.0
     * @param array $config Build configuration
     * @return string CSS classes
     */
    private function get_form_css_classes($config) {
        $classes = array(
            'qcc-form',
            'qcc-calculator-form',
            'qcc-form--' . $config['mode']
        );
        
        if ($this->form_config['live_validation']) {
            $classes[] = 'qcc-form--live-validation';
        }
        
        if ($this->form_config['show_progress']) {
            $classes[] = 'qcc-form--with-progress';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Required fields für Form-Builder
     * 
     * @since 3.0.0
     * @return array Required fields
     */
    protected function get_required_fields() {
        return array();
    }
    
    /**
     * Assets für Form-Builder
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $form_assets = array(
            'css' => array('form-builder.css', 'form-sections.css', 'form-validation.css'),
            'js' => array('form-builder.js', 'form-validation.js', 'form-calculations.js'),
            'dependencies' => array('jquery', 'qcc-input-group', 'qcc-currency-selector')
        );
        
        return array_merge_recursive($base_assets, $form_assets);
    }
    
    /**
     * Default-Form-Data abrufen
     * 
     * @since 3.0.0
     * @return array Default form values
     */
    public function get_default_form_data() {
        $defaults = array();
        
        foreach ($this->field_definitions as $field_name => $definition) {
            $defaults[$field_name] = $definition['default'] ?? '';
        }
        
        return $defaults;
    }
    
    /**
     * Form-Data aus Request extrahieren
     * 
     * @since 3.0.0
     * @param array $request_data Request data
     * @return array Extracted form data
     */
    public function extract_form_data($request_data) {
        $form_data = $request_data['qcc_form'] ?? array();
        $extracted = array();
        
        foreach ($this->field_definitions as $field_name => $definition) {
            $value = $form_data[$field_name] ?? $definition['default'] ?? '';
            
            // Type-spezifisches Parsing
            switch ($definition['type']) {
                case 'percentage':
                case 'currency':
                    $extracted[$field_name] = floatval($value);
                    break;
                    
                default:
                    $extracted[$field_name] = sanitize_text_field($value);
                    break;
            }
        }
        
        return $extracted;
    }
    
    /**
     * Form-Progress berechnen
     * 
     * @since 3.0.0
     * @param array $form_data Current form data
     * @return array Progress information
     */
    public function calculate_form_progress($form_data) {
        $total_fields = 0;
        $completed_fields = 0;
        $required_fields = 0;
        $completed_required = 0;
        
        foreach ($this->field_definitions as $field_name => $definition) {
            $total_fields++;
            
            if ($definition['required']) {
                $required_fields++;
            }
            
            $value = $form_data[$field_name] ?? '';
            if (!empty($value)) {
                $completed_fields++;
                
                if ($definition['required']) {
                    $completed_required++;
                }
            }
        }
        
        return array(
            'total_fields' => $total_fields,
            'completed_fields' => $completed_fields,
            'required_fields' => $required_fields,
            'completed_required' => $completed_required,
            'overall_progress' => $total_fields > 0 ? round(($completed_fields / $total_fields) * 100, 1) : 0,
            'required_progress' => $required_fields > 0 ? round(($completed_required / $required_fields) * 100, 1) : 0,
            'is_complete' => $completed_required === $required_fields && $this->calculate_total_percentage($form_data) == 100
        );
    }
    
    /**
     * Form-Builder für spezifische Modi konfigurieren
     * 
     * @since 3.0.0
     * @param string $mode Build mode
     * @return void
     */
    public function configure_for_mode($mode) {
        switch ($mode) {
            case 'readonly':
                $this->form_config['live_validation'] = false;
                $this->form_config['auto_save'] = false;
                
                // Alle Felder auf readonly setzen
                foreach ($this->field_definitions as &$definition) {
                    $definition['readonly'] = true;
                }
                break;
                
            case 'preview':
                $this->form_config['show_progress'] = false;
                
                // Nur wichtigste Sections anzeigen
                $this->sub_sections = array_intersect_key(
                    $this->sub_sections,
                    array_flip(array('cogq', 'copq', 'validation'))
                );
                break;
                
            case 'simple':
                // Opportunity-Section entfernen
                unset($this->sub_sections['opportunity']);
                
                // Validation-Section vereinfachen
                $this->sub_sections['validation']['collapsible'] = false;
                break;
        }
    }
    
    /**
     * Form-Sections filtern basierend auf Berechtigungen
     * 
     * @since 3.0.0
     * @param array $user_capabilities User capabilities
     * @return void
     */
    public function filter_sections_by_capabilities($user_capabilities) {
        // Beispiel: Opportunity-Costs nur für bestimmte Rollen
        if (!in_array('qcc_advanced', $user_capabilities)) {
            unset($this->sub_sections['opportunity']);
        }
        
        // Header-Section nur für Admins editierbar
        if (!in_array('qcc_admin', $user_capabilities)) {
            $this->sub_sections['header']['readonly'] = true;
        }
    }
    
    /**
     * Custom Validation-Rules hinzufügen
     * 
     * @since 3.0.0
     * @param string $field_name Field name
     * @param callable $validator Validator function
     * @return void
     */
    public function add_custom_validator($field_name, $validator) {
        if (!isset($this->field_definitions[$field_name])) {
            return;
        }
        
        if (!isset($this->field_definitions[$field_name]['custom_validators'])) {
            $this->field_definitions[$field_name]['custom_validators'] = array();
        }
        
        $this->field_definitions[$field_name]['custom_validators'][] = $validator;
    }
    
    /**
     * Form-Builder für AJAX-Modus konfigurieren
     * 
     * @since 3.0.0
     * @return void
     */
    public function enable_ajax_mode() {
        $this->form_config['ajax_submit'] = true;
        $this->form_config['live_validation'] = true;
        $this->form_config['auto_save'] = true;
        
        // JavaScript-Events für AJAX
        add_action('wp_footer', array($this, 'output_ajax_javascript'));
    }
    
    /**
     * AJAX-JavaScript ausgeben
     * 
     * @since 3.0.0
     * @return void
     */
    public function output_ajax_javascript() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // AJAX Form-Submission
            $('#qcc-calculator-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Handle success
                            console.log('Form submitted successfully', response.data);
                        } else {
                            // Handle errors
                            console.error('Form submission failed', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                    }
                });
            });
            
            // Live-Validation
            $('.qcc-form input, .qcc-form select').on('blur', function() {
                var fieldName = $(this).attr('name').replace('qcc_form[', '').replace(']', '');
                var fieldValue = $(this).val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'qcc_validate_field',
                        field_name: fieldName,
                        field_value: fieldValue
                    },
                    success: function(response) {
                        // Handle validation response
                        console.log('Field validation:', response);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Factory-Methode für verschiedene Form-Builder-Konfigurationen
     * 
     * @since 3.0.0
     * @param string $preset Preset name
     * @param array $config Additional configuration
     * @return QCC_Form_Builder Configured form builder
     */
    public static function create_preset($preset, $config = array()) {
        $instance = new self();
        
        switch ($preset) {
            case 'full':
                // Alle Sections aktiviert
                break;
                
            case 'basic':
                $instance->configure_for_mode('simple');
                break;
                
            case 'readonly':
                $instance->configure_for_mode('readonly');
                break;
                
            case 'ajax':
                $instance->enable_ajax_mode();
                break;
        }
        
        return $instance;
    }
}
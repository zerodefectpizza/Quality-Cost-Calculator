<?php
/**
 * QCC Input Validator - Basis-Input-Validierung
 *
 * @package QualityCostCalculator
 * @subpackage Business\Validation
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Input Validator for Quality Cost Calculator
 * 
 * Handles basic input validation including data types, ranges, and required fields.
 * Provides data sanitization and normalization for calculation inputs.
 */
class QCC_Input_Validator {
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Required fields for complete calculation
     * 
     * @var array
     */
    private $required_fields = array(
        'revenue',
        'quality_percentage',
        'prevention',
        'appraisal',
        'internal_defect',
        'external_defect'
    );
    
    /**
     * Field validation rules
     * 
     * @var array
     */
    private $validation_rules = array(
        'revenue' => array(
            'type' => 'numeric',
            'min' => 1,
            'max' => 999999999999, // 999 Billion
            'required' => true
        ),
        'quality_percentage' => array(
            'type' => 'percentage',
            'min' => 0,
            'max' => 100,
            'required' => true
        ),
        'prevention' => array(
            'type' => 'percentage',
            'min' => 0,
            'max' => 100,
            'required' => true
        ),
        'appraisal' => array(
            'type' => 'percentage',
            'min' => 0,
            'max' => 100,
            'required' => true
        ),
        'internal_defect' => array(
            'type' => 'percentage',
            'min' => 0,
            'max' => 100,
            'required' => true
        ),
        'external_defect' => array(
            'type' => 'percentage',
            'min' => 0,
            'max' => 100,
            'required' => true
        ),
        'currency' => array(
            'type' => 'string',
            'allowed_values' => array('EUR', 'USD', 'CNY'),
            'required' => false,
            'default' => 'EUR'
        ),
        'unit' => array(
            'type' => 'string',
            'allowed_values' => array('1000000', '1000000000'),
            'required' => false,
            'default' => '1000000000'
        ),
        'language' => array(
            'type' => 'string',
            'allowed_values' => array('en', 'de', 'fr', 'es', 'zh'),
            'required' => false,
            'default' => 'en'
        )
    );
    
    /**
     * ROI-specific validation rules
     * 
     * @var array
     */
    private $roi_validation_rules = array(
        'investment' => array(
            'type' => 'numeric',
            'min' => 0,
            'max' => 999999999,
            'required' => true
        ),
        'annual_savings' => array(
            'type' => 'numeric',
            'min' => 0,
            'max' => 999999999,
            'required' => true
        ),
        'timeframe_years' => array(
            'type' => 'integer',
            'min' => 1,
            'max' => 20,
            'required' => false,
            'default' => 3
        )
    );
    
    /**
     * Constructor
     * 
     * @param QCC_Service_Container $container Service container
     */
    public function __construct($container = null) {
        $this->container = $container;
    }
    
    /**
     * Validate input data
     * 
     * @param array $input Input data to validate
     * @param array $options Validation options
     * @return array Validation result
     */
    public function validate($input, $options = array()) {
        $errors = array();
        $warnings = array();
        $normalized_data = array();
        
        // Determine validation rules based on options
        $rules = $this->get_validation_rules($options);
        
        // Validate each field
        foreach ($rules as $field => $rule) {
            $field_result = $this->validate_field($field, $input[$field] ?? null, $rule, $options);
            
            if (!$field_result['is_valid']) {
                $errors = array_merge($errors, $field_result['errors']);
            }
            
            if (isset($field_result['warnings'])) {
                $warnings = array_merge($warnings, $field_result['warnings']);
            }
            
            if (isset($field_result['normalized_value'])) {
                $normalized_data[$field] = $field_result['normalized_value'];
            }
        }
        
        // Add any additional fields that weren't in rules (pass-through)
        foreach ($input as $field => $value) {
            if (!isset($rules[$field]) && !isset($normalized_data[$field])) {
                $normalized_data[$field] = $value;
            }
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_data' => empty($errors) ? $normalized_data : null
        );
    }
    
    /**
     * Validate individual field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rule Validation rule
     * @param array $options Validation options
     * @return array Field validation result
     */
    private function validate_field($field, $value, $rule, $options) {
        $errors = array();
        $warnings = array();
        $normalized_value = $value;
        
        // Check required fields
        if (isset($rule['required']) && $rule['required'] && ($value === null || $value === '')) {
            $errors[$field . '_required'] = sprintf(
                __('Field "%s" is required', 'quality-cost-calculator'),
                $this->get_field_display_name($field)
            );
            return array('is_valid' => false, 'errors' => $errors);
        }
        
        // Apply default value if field is empty
        if (($value === null || $value === '') && isset($rule['default'])) {
            $normalized_value = $rule['default'];
            $value = $normalized_value;
        }
        
        // Skip further validation if value is still empty
        if ($value === null || $value === '') {
            return array(
                'is_valid' => true,
                'normalized_value' => $normalized_value
            );
        }
        
        // Validate by type
        switch ($rule['type']) {
            case 'numeric':
                $type_result = $this->validate_numeric($field, $value, $rule);
                break;
            case 'percentage':
                $type_result = $this->validate_percentage($field, $value, $rule);
                break;
            case 'integer':
                $type_result = $this->validate_integer($field, $value, $rule);
                break;
            case 'string':
                $type_result = $this->validate_string($field, $value, $rule);
                break;
            default:
                $type_result = array('is_valid' => true, 'normalized_value' => $value);
        }
        
        if (!$type_result['is_valid']) {
            $errors = array_merge($errors, $type_result['errors']);
        }
        
        if (isset($type_result['warnings'])) {
            $warnings = array_merge($warnings, $type_result['warnings']);
        }
        
        if (isset($type_result['normalized_value'])) {
            $normalized_value = $type_result['normalized_value'];
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_value' => $normalized_value
        );
    }
    
    /**
     * Validate numeric field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rule Validation rule
     * @return array Validation result
     */
    private function validate_numeric($field, $value, $rule) {
        $errors = array();
        $warnings = array();
        
        // Normalize numeric value (remove commas, handle localization)
        $normalized_value = $this->normalize_numeric($value);
        
        if (!is_numeric($normalized_value)) {
            $errors[$field . '_not_numeric'] = sprintf(
                __('Field "%s" must be a valid number', 'quality-cost-calculator'),
                $this->get_field_display_name($field)
            );
            return array('is_valid' => false, 'errors' => $errors);
        }
        
        $numeric_value = floatval($normalized_value);
        
        // Check minimum value
        if (isset($rule['min']) && $numeric_value < $rule['min']) {
            $errors[$field . '_too_small'] = sprintf(
                __('Field "%s" must be at least %s', 'quality-cost-calculator'),
                $this->get_field_display_name($field),
                number_format($rule['min'])
            );
        }
        
        // Check maximum value
        if (isset($rule['max']) && $numeric_value > $rule['max']) {
            $errors[$field . '_too_large'] = sprintf(
                __('Field "%s" must not exceed %s', 'quality-cost-calculator'),
                $this->get_field_display_name($field),
                number_format($rule['max'])
            );
        }
        
        // Add warnings for unusual values
        if ($field === 'revenue' && $numeric_value < 100000) {
            $warnings[$field . '_low_revenue'] = __('Revenue seems unusually low. Please verify the amount.', 'quality-cost-calculator');
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_value' => $numeric_value
        );
    }
    
    /**
     * Validate percentage field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rule Validation rule
     * @return array Validation result
     */
    private function validate_percentage($field, $value, $rule) {
        $errors = array();
        $warnings = array();
        
        // First validate as numeric
        $numeric_result = $this->validate_numeric($field, $value, $rule);
        
        if (!$numeric_result['is_valid']) {
            return $numeric_result;
        }
        
        $percentage_value = $numeric_result['normalized_value'];
        
        // Additional percentage-specific validations
        if ($percentage_value < 0) {
            $errors[$field . '_negative'] = sprintf(
                __('Percentage "%s" cannot be negative', 'quality-cost-calculator'),
                $this->get_field_display_name($field)
            );
        }
        
        if ($percentage_value > 100) {
            $errors[$field . '_over_100'] = sprintf(
                __('Percentage "%s" cannot exceed 100%%', 'quality-cost-calculator'),
                $this->get_field_display_name($field)
            );
        }
        
        // Add warnings for unusual percentage values
        if ($field === 'quality_percentage' && $percentage_value > 20) {
            $warnings[$field . '_high_percentage'] = __('Quality cost percentage seems high. Industry average is 4-8%.', 'quality-cost-calculator');
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => array_merge($numeric_result['warnings'] ?? array(), $warnings),
            'normalized_value' => $percentage_value
        );
    }
    
    /**
     * Validate integer field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rule Validation rule
     * @return array Validation result
     */
    private function validate_integer($field, $value, $rule) {
        $errors = array();
        
        $normalized_value = $this->normalize_numeric($value);
        
        if (!is_numeric($normalized_value) || floor($normalized_value) != $normalized_value) {
            $errors[$field . '_not_integer'] = sprintf(
                __('Field "%s" must be a whole number', 'quality-cost-calculator'),
                $this->get_field_display_name($field)
            );
            return array('is_valid' => false, 'errors' => $errors);
        }
        
        $integer_value = intval($normalized_value);
        
        // Check range
        if (isset($rule['min']) && $integer_value < $rule['min']) {
            $errors[$field . '_too_small'] = sprintf(
                __('Field "%s" must be at least %d', 'quality-cost-calculator'),
                $this->get_field_display_name($field),
                $rule['min']
            );
        }
        
        if (isset($rule['max']) && $integer_value > $rule['max']) {
            $errors[$field . '_too_large'] = sprintf(
                __('Field "%s" must not exceed %d', 'quality-cost-calculator'),
                $this->get_field_display_name($field),
                $rule['max']
            );
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'normalized_value' => $integer_value
        );
    }
    
    /**
     * Validate string field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rule Validation rule
     * @return array Validation result
     */
    private function validate_string($field, $value, $rule) {
        $errors = array();
        
        $string_value = sanitize_text_field(strval($value));
        
        // Check allowed values
        if (isset($rule['allowed_values']) && !in_array($string_value, $rule['allowed_values'])) {
            $errors[$field . '_invalid_value'] = sprintf(
                __('Field "%s" must be one of: %s', 'quality-cost-calculator'),
                $this->get_field_display_name($field),
                implode(', ', $rule['allowed_values'])
            );
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'normalized_value' => $string_value
        );
    }
    
    /**
     * Get validation rules based on options
     * 
     * @param array $options Validation options
     * @return array Combined validation rules
     */
    private function get_validation_rules($options) {
        $rules = $this->validation_rules;
        
        // Add ROI rules if in ROI mode
        if (isset($options['roi_mode']) && $options['roi_mode']) {
            $rules = array_merge($rules, $this->roi_validation_rules);
        }
        
        // Modify rules based on strict mode
        if (isset($options['strict_mode']) && $options['strict_mode']) {
            foreach ($rules as $field => &$rule) {
                if ($field === 'quality_percentage') {
                    $rule['max'] = 15; // Stricter limit in strict mode
                }
            }
        }
        
        return $rules;
    }
    
    /**
     * Normalize numeric input
     * 
     * @param mixed $value Input value
     * @return string Normalized numeric string
     */
    private function normalize_numeric($value) {
        // Convert to string first
        $string_value = strval($value);
        
        // Remove common thousand separators and non-numeric characters
        $normalized = preg_replace('/[^\d.,\-]/', '', $string_value);
        
        // Handle European number format (comma as decimal separator)
        if (substr_count($normalized, ',') === 1 && substr_count($normalized, '.') === 0) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            // Remove thousand separators (commas)
            $normalized = str_replace(',', '', $normalized);
        }
        
        return $normalized;
    }
    
    /**
     * Get display name for field
     * 
     * @param string $field Field name
     * @return string Display name
     */
    private function get_field_display_name($field) {
        $display_names = array(
            'revenue' => __('Revenue', 'quality-cost-calculator'),
            'quality_percentage' => __('Quality Cost Percentage', 'quality-cost-calculator'),
            'prevention' => __('Prevention Costs', 'quality-cost-calculator'),
            'appraisal' => __('Appraisal Costs', 'quality-cost-calculator'),
            'internal_defect' => __('Internal Defect Costs', 'quality-cost-calculator'),
            'external_defect' => __('External Defect Costs', 'quality-cost-calculator'),
            'currency' => __('Currency', 'quality-cost-calculator'),
            'unit' => __('Unit', 'quality-cost-calculator'),
            'language' => __('Language', 'quality-cost-calculator'),
            'investment' => __('Investment Amount', 'quality-cost-calculator'),
            'annual_savings' => __('Annual Savings', 'quality-cost-calculator'),
            'timeframe_years' => __('Timeframe (Years)', 'quality-cost-calculator')
        );
        
        return $display_names[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
    
    /**
     * Get validator status
     * 
     * @return array Validator status
     */
    public function get_status() {
        return array(
            'version' => '2.0.0',
            'validator_type' => 'input',
            'total_rules' => count($this->validation_rules),
            'roi_rules' => count($this->roi_validation_rules),
            'required_fields' => $this->required_fields,
            'supported_types' => array('numeric', 'percentage', 'integer', 'string')
        );
    }
}
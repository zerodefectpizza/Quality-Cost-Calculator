<?php
/**
 * QCC Percentage Validator - Prozent-spezifische Validierung
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
 * Percentage Validator for Quality Cost Calculator
 * 
 * Handles percentage-specific validation including precision handling,
 * sum validation, floating-point tolerance, and percentage normalization.
 */
class QCC_Percentage_Validator {
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Floating point tolerance for percentage calculations
     * 
     * @var float
     */
    private $tolerance = 0.001;
    
    /**
     * Strict mode tolerance (tighter precision)
     * 
     * @var float
     */
    private $strict_tolerance = 0.0001;
    
    /**
     * Percentage field groups that must sum to 100%
     * 
     * @var array
     */
    private $percentage_groups = array(
        'quality_cost_breakdown' => array(
            'fields' => array('prevention', 'appraisal', 'internal_defect', 'external_defect'),
            'target_sum' => 100.0,
            'required' => true,
            'description' => __('Quality cost breakdown percentages', 'quality-cost-calculator')
        ),
        'cogq_breakdown' => array(
            'fields' => array('prevention', 'appraisal'),
            'target_sum' => null, // Variable sum based on context
            'required' => false,
            'description' => __('Cost of Good Quality breakdown', 'quality-cost-calculator')
        ),
        'copq_breakdown' => array(
            'fields' => array('internal_defect', 'external_defect'),
            'target_sum' => null, // Variable sum based on context
            'required' => false,
            'description' => __('Cost of Poor Quality breakdown', 'quality-cost-calculator')
        )
    );
    
    /**
     * Percentage field validation rules
     * 
     * @var array
     */
    private $percentage_rules = array(
        'prevention' => array(
            'min' => 0.0,
            'max' => 100.0,
            'soft_min' => 2.0,    // Warning below this
            'soft_max' => 60.0,   // Warning above this
            'precision' => 2,
            'description' => __('Prevention costs percentage', 'quality-cost-calculator')
        ),
        'appraisal' => array(
            'min' => 0.0,
            'max' => 100.0,
            'soft_min' => 5.0,
            'soft_max' => 50.0,
            'precision' => 2,
            'description' => __('Appraisal costs percentage', 'quality-cost-calculator')
        ),
        'internal_defect' => array(
            'min' => 0.0,
            'max' => 100.0,
            'soft_min' => 0.0,
            'soft_max' => 60.0,
            'precision' => 2,
            'description' => __('Internal defect costs percentage', 'quality-cost-calculator')
        ),
        'external_defect' => array(
            'min' => 0.0,
            'max' => 100.0,
            'soft_min' => 0.0,
            'soft_max' => 40.0,
            'precision' => 2,
            'description' => __('External defect costs percentage', 'quality-cost-calculator')
        ),
        'quality_percentage' => array(
            'min' => 0.1,
            'max' => 100.0,
            'soft_min' => 1.0,
            'soft_max' => 20.0,
            'precision' => 2,
            'description' => __('Overall quality cost percentage', 'quality-cost-calculator')
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
     * Validate percentage data
     * 
     * @param array $input Input data to validate
     * @param array $options Validation options
     * @return array Validation result
     */
    public function validate($input, $options = array()) {
        $errors = array();
        $warnings = array();
        $normalized_data = $input;
        
        // Set tolerance based on options
        $tolerance = $this->get_tolerance($options);
        
        // Validate individual percentage fields
        $field_result = $this->validate_percentage_fields($input, $options);
        if (!$field_result['is_valid']) {
            $errors = array_merge($errors, $field_result['errors']);
        }
        if (isset($field_result['warnings'])) {
            $warnings = array_merge($warnings, $field_result['warnings']);
        }
        if (isset($field_result['normalized_data'])) {
            $normalized_data = array_merge($normalized_data, $field_result['normalized_data']);
        }
        
        // Validate percentage group sums
        $group_result = $this->validate_percentage_groups($normalized_data, $options, $tolerance);
        if (!$group_result['is_valid']) {
            $errors = array_merge($errors, $group_result['errors']);
        }
        if (isset($group_result['warnings'])) {
            $warnings = array_merge($warnings, $group_result['warnings']);
        }
        
        // Validate percentage relationships
        $relationship_result = $this->validate_percentage_relationships($normalized_data, $options);
        if (!$relationship_result['is_valid']) {
            $errors = array_merge($errors, $relationship_result['errors']);
        }
        if (isset($relationship_result['warnings'])) {
            $warnings = array_merge($warnings, $relationship_result['warnings']);
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_data' => empty($errors) ? $normalized_data : null,
            'percentage_analysis' => $this->generate_percentage_analysis($normalized_data)
        );
    }
    
    /**
     * Validate individual percentage fields
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_percentage_fields($input, $options) {
        $errors = array();
        $warnings = array();
        $normalized_data = array();
        
        foreach ($this->percentage_rules as $field => $rules) {
            if (!isset($input[$field])) {
                continue; // Skip missing fields (handled by input validator)
            }
            
            $value = $input[$field];
            $field_result = $this->validate_single_percentage($field, $value, $rules, $options);
            
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
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_data' => $normalized_data
        );
    }
    
    /**
     * Validate single percentage field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param array $rules Validation rules
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_single_percentage($field, $value, $rules, $options) {
        $errors = array();
        $warnings = array();
        
        // Normalize percentage value
        $normalized_value = $this->normalize_percentage($value);
        
        if ($normalized_value === false) {
            $errors[$field . '_invalid_percentage'] = sprintf(
                __('Field "%s" must be a valid percentage value', 'quality-cost-calculator'),
                $rules['description']
            );
            return array('is_valid' => false, 'errors' => $errors);
        }
        
        // Apply precision rounding
        $precision = $rules['precision'] ?? 2;
        $normalized_value = round($normalized_value, $precision);
        
        // Hard limits validation
        if ($normalized_value < $rules['min']) {
            $errors[$field . '_below_minimum'] = sprintf(
                __('%s (%.2f%%) cannot be below %.2f%%', 'quality-cost-calculator'),
                $rules['description'],
                $normalized_value,
                $rules['min']
            );
        }
        
        if ($normalized_value > $rules['max']) {
            $errors[$field . '_above_maximum'] = sprintf(
                __('%s (%.2f%%) cannot exceed %.2f%%', 'quality-cost-calculator'),
                $rules['description'],
                $normalized_value,
                $rules['max']
            );
        }
        
        // Soft limits validation (warnings)
        if (isset($rules['soft_min']) && $normalized_value < $rules['soft_min']) {
            $warnings[$field . '_below_recommended'] = sprintf(
                __('%s (%.2f%%) is below recommended minimum of %.2f%%', 'quality-cost-calculator'),
                $rules['description'],
                $normalized_value,
                $rules['soft_min']
            );
        }
        
        if (isset($rules['soft_max']) && $normalized_value > $rules['soft_max']) {
            $warnings[$field . '_above_recommended'] = sprintf(
                __('%s (%.2f%%) is above recommended maximum of %.2f%%', 'quality-cost-calculator'),
                $rules['description'],
                $normalized_value,
                $rules['soft_max']
            );
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_value' => $normalized_value
        );
    }
    
    /**
     * Validate percentage groups
     * 
     * @param array $data Normalized data
     * @param array $options Validation options
     * @param float $tolerance Floating point tolerance
     * @return array Validation result
     */
    private function validate_percentage_groups($data, $options, $tolerance) {
        $errors = array();
        $warnings = array();
        
        foreach ($this->percentage_groups as $group_name => $group_config) {
            $group_result = $this->validate_percentage_group($group_name, $group_config, $data, $tolerance, $options);
            
            if (!$group_result['is_valid']) {
                $errors = array_merge($errors, $group_result['errors']);
            }
            
            if (isset($group_result['warnings'])) {
                $warnings = array_merge($warnings, $group_result['warnings']);
            }
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Validate single percentage group
     * 
     * @param string $group_name Group name
     * @param array $group_config Group configuration
     * @param array $data Data
     * @param float $tolerance Tolerance
     * @param array $options Options
     * @return array Validation result
     */
    private function validate_percentage_group($group_name, $group_config, $data, $tolerance, $options) {
        $errors = array();
        $warnings = array();
        
        // Calculate sum of group fields
        $sum = 0.0;
        $missing_fields = array();
        
        foreach ($group_config['fields'] as $field) {
            if (isset($data[$field])) {
                $sum += floatval($data[$field]);
            } elseif ($group_config['required']) {
                $missing_fields[] = $field;
            }
        }
        
        // Check for missing required fields
        if (!empty($missing_fields) && $group_config['required']) {
            $errors[$group_name . '_missing_fields'] = sprintf(
                __('%s requires all fields: %s', 'quality-cost-calculator'),
                $group_config['description'],
                implode(', ', $missing_fields)
            );
            return array('is_valid' => false, 'errors' => $errors);
        }
        
        // Validate sum if target is specified
        if ($group_config['target_sum'] !== null) {
            $target = $group_config['target_sum'];
            $difference = abs($sum - $target);
            
            if ($difference > $tolerance) {
                $errors[$group_name . '_sum_invalid'] = sprintf(
                    __('%s must sum to %.2f%%. Current sum: %.2f%% (difference: %.3f%%)', 'quality-cost-calculator'),
                    $group_config['description'],
                    $target,
                    $sum,
                    $difference
                );
            } elseif ($difference > ($tolerance / 10)) { // Warning threshold
                $warnings[$group_name . '_sum_warning'] = sprintf(
                    __('%s sum (%.3f%%) is close to but not exactly %.2f%%', 'quality-cost-calculator'),
                    $group_config['description'],
                    $sum,
                    $target
                );
            }
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Validate percentage relationships
     * 
     * @param array $data Normalized data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_percentage_relationships($data, $options) {
        $errors = array();
        $warnings = array();
        
        // COGQ vs COPQ relationship
        $prevention = $data['prevention'] ?? 0;
        $appraisal = $data['appraisal'] ?? 0;
        $internal_defect = $data['internal_defect'] ?? 0;
        $external_defect = $data['external_defect'] ?? 0;
        
        $cogq_total = $prevention + $appraisal;
        $copq_total = $internal_defect + $external_defect;
        
        // Prevention should be meaningful portion of COGQ
        if ($cogq_total > 0) {
            $prevention_ratio = $prevention / $cogq_total;
            
            if ($prevention_ratio < 0.2) {
                $warnings['prevention_low_in_cogq'] = sprintf(
                    __('Prevention is only %.1f%% of COGQ. Industry best practice is 40-60%%', 'quality-cost-calculator'),
                    $prevention_ratio * 100
                );
            } elseif ($prevention_ratio > 0.8) {
                $warnings['prevention_high_in_cogq'] = sprintf(
                    __('Prevention is %.1f%% of COGQ. Consider balanced approach with appraisal activities', 'quality-cost-calculator'),
                    $prevention_ratio * 100
                );
            }
        }
        
        // Internal vs External defect relationship
        if ($copq_total > 0) {
            $external_ratio = $external_defect / $copq_total;
            
            if ($external_ratio > 0.7) {
                $errors['external_defects_dominant'] = sprintf(
                    __('External defects (%.1f%%) dominate COPQ. This indicates quality issues reaching customers', 'quality-cost-calculator'),
                    $external_ratio * 100
                );
            } elseif ($external_ratio > 0.5) {
                $warnings['external_defects_high'] = sprintf(
                    __('External defects (%.1f%%) are high. Focus on preventing customer-facing quality issues', 'quality-cost-calculator'),
                    $external_ratio * 100
                );
            }
        }
        
        // Overall quality percentage relationships
        if (isset($data['quality_percentage'])) {
            $quality_pct = $data['quality_percentage'];
            $calculated_total = $cogq_total + $copq_total;
            
            // The breakdown percentages should align with quality investment strategy
            if ($quality_pct > 10 && $cogq_total < 30) {
                $warnings['low_cogq_high_quality_costs'] = sprintf(
                    __('High quality costs (%.1f%%) but low COGQ investment (%.1f%%). Consider increasing prevention/appraisal', 'quality-cost-calculator'),
                    $quality_pct,
                    $cogq_total
                );
            }
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Generate percentage analysis
     * 
     * @param array $data Validated data
     * @return array Percentage analysis
     */
    private function generate_percentage_analysis($data) {
        $prevention = $data['prevention'] ?? 0;
        $appraisal = $data['appraisal'] ?? 0;
        $internal_defect = $data['internal_defect'] ?? 0;
        $external_defect = $data['external_defect'] ?? 0;
        
        $cogq_total = $prevention + $appraisal;
        $copq_total = $internal_defect + $external_defect;
        $total_quality = $cogq_total + $copq_total;
        
        return array(
            'cogq_percentage' => $cogq_total,
            'copq_percentage' => $copq_total,
            'cogq_copq_ratio' => $copq_total > 0 ? $cogq_total / $copq_total : null,
            'prevention_focus' => $cogq_total > 0 ? ($prevention / $cogq_total) * 100 : 0,
            'external_impact' => $copq_total > 0 ? ($external_defect / $copq_total) * 100 : 0,
            'balance_score' => $this->calculate_balance_score($cogq_total, $copq_total, $prevention, $external_defect),
            'precision_analysis' => array(
                'sum_precision' => $total_quality,
                'rounding_applied' => $this->check_rounding_applied($data),
                'tolerance_used' => $this->get_tolerance(array())
            )
        );
    }
    
    /**
     * Calculate balance score (0-100)
     * 
     * @param float $cogq_total COGQ total
     * @param float $copq_total COPQ total
     * @param float $prevention Prevention percentage
     * @param float $external_defect External defect percentage
     * @return int Balance score
     */
    private function calculate_balance_score($cogq_total, $copq_total, $prevention, $external_defect) {
        $score = 100;
        
        // COGQ/COPQ balance (optimal: 40-60% COGQ)
        $total = $cogq_total + $copq_total;
        if ($total > 0) {
            $cogq_ratio = $cogq_total / $total;
            $optimal_deviation = abs($cogq_ratio - 0.5) * 100; // Deviation from 50%
            $score -= min(30, $optimal_deviation);
        }
        
        // Prevention focus (optimal: 40-60% of COGQ)
        if ($cogq_total > 0) {
            $prevention_ratio = $prevention / $cogq_total;
            $prevention_deviation = abs($prevention_ratio - 0.5) * 100;
            $score -= min(25, $prevention_deviation);
        }
        
        // External defect control (optimal: <30% of COPQ)
        if ($copq_total > 0) {
            $external_ratio = $external_defect / $copq_total;
            if ($external_ratio > 0.3) {
                $score -= ($external_ratio - 0.3) * 100;
            }
        }
        
        return max(0, intval($score));
    }
    
    /**
     * Normalize percentage value
     * 
     * @param mixed $value Input value
     * @return float|false Normalized percentage or false on error
     */
    private function normalize_percentage($value) {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        // Handle string percentages
        if (is_string($value)) {
            // Remove percentage symbol and spaces
            $cleaned = trim(str_replace('%', '', $value));
            
            // Handle decimal separators
            $cleaned = str_replace(',', '.', $cleaned);
            
            if (is_numeric($cleaned)) {
                return floatval($cleaned);
            }
        }
        
        return false;
    }
    
    /**
     * Get tolerance based on options
     * 
     * @param array $options Validation options
     * @return float Tolerance value
     */
    private function get_tolerance($options) {
        if (isset($options['strict_mode']) && $options['strict_mode']) {
            return $this->strict_tolerance;
        }
        
        return $this->tolerance;
    }
    
    /**
     * Check if rounding was applied
     * 
     * @param array $data Percentage data
     * @return bool True if rounding was applied
     */
    private function check_rounding_applied($data) {
        foreach ($this->percentage_rules as $field => $rules) {
            if (isset($data[$field])) {
                $precision = $rules['precision'] ?? 2;
                $value = $data[$field];
                $rounded = round($value, $precision);
                
                if (abs($value - $rounded) > 0.00001) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get validator status
     * 
     * @return array Validator status
     */
    public function get_status() {
        return array(
            'version' => '2.0.0',
            'validator_type' => 'percentage',
            'tolerance' => $this->tolerance,
            'strict_tolerance' => $this->strict_tolerance,
            'percentage_groups' => array_keys($this->percentage_groups),
            'percentage_fields' => array_keys($this->percentage_rules),
            'supported_features' => array(
                'precision_handling',
                'floating_point_tolerance',
                'group_sum_validation',
                'relationship_validation',
                'balance_scoring'
            )
        );
    }
}
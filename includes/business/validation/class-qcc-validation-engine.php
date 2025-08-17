<?php
/**
 * QCC Validation Engine - Haupt-Validator Service
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
 * Validation Engine for Quality Cost Calculator
 * 
 * Coordinates all validation services and provides unified validation API.
 * Handles caching, error aggregation, and orchestration of specialized validators.
 */
class QCC_Validation_Engine {
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Configuration service
     * 
     * @var QCC_Configuration
     */
    private $config;
    
    /**
     * Registered validators
     * 
     * @var array
     */
    private $validators = array();
    
    /**
     * Validation cache
     * 
     * @var array
     */
    private $validation_cache = array();
    
    /**
     * Performance tracking
     * 
     * @var array
     */
    private $performance = array(
        'validations_performed' => 0,
        'cache_hits' => 0,
        'validation_time' => 0
    );
    
    /**
     * Default validator types
     * 
     * @var array
     */
    private $validator_types = array(
        'input' => 'QCC_Input_Validator',
        'business' => 'QCC_Business_Validator',
        'percentage' => 'QCC_Percentage_Validator'
    );
    
    /**
     * Constructor
     * 
     * @param QCC_Service_Container $container Service container
     */
    public function __construct($container = null) {
        $this->container = $container;
        $this->load_dependencies();
        $this->register_default_validators();
    }
    
    /**
     * Main validation method
     * 
     * @param array $input Input data to validate
     * @param array $options Validation options
     * @return array|true Validation result (true if valid, array of errors if invalid)
     */
    public function validate($input, $options = array()) {
        $start_time = microtime(true);
        
        try {
            // Check cache if enabled
            $cache_key = $this->generate_cache_key($input, $options);
            if ($this->is_cache_enabled() && isset($this->validation_cache[$cache_key])) {
                $this->performance['cache_hits']++;
                return $this->validation_cache[$cache_key];
            }
            
            // Perform validation
            $validation_result = $this->perform_validation($input, $options);
            
            // Cache results if enabled
            if ($this->is_cache_enabled()) {
                $this->validation_cache[$cache_key] = $validation_result;
            }
            
            // Update performance tracking
            $this->performance['validations_performed']++;
            $this->performance['validation_time'] += microtime(true) - $start_time;
            
            return $validation_result;
            
        } catch (Exception $e) {
            // Log error in debug mode
            if ($this->config && $this->config->get('debug_mode', false)) {
                error_log('QCC Validation Engine Error: ' . $e->getMessage());
            }
            
            // Return validation failure
            return array(
                'is_valid' => false,
                'errors' => array('validation_engine_error' => $e->getMessage()),
                'data' => null
            );
        }
    }
    
    /**
     * Validate input for complete calculation
     * 
     * @param array $input Complete calculation input
     * @return array Validation result with normalized data
     */
    public function validate_complete_input($input) {
        $validation_options = array(
            'validate_percentage_sum' => true,
            'normalize_data' => true,
            'strict_mode' => $this->config ? $this->config->get('strict_validation', false) : false
        );
        
        return $this->validate($input, $validation_options);
    }
    
    /**
     * Validate ROI-specific inputs
     * 
     * @param array $roi_params ROI calculation parameters
     * @return array Validation result
     */
    public function validate_roi_inputs($roi_params) {
        $validation_options = array(
            'roi_mode' => true,
            'require_investment' => true,
            'require_savings' => true
        );
        
        return $this->validate($roi_params, $validation_options);
    }
    
    /**
     * Perform the actual validation
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function perform_validation($input, $options) {
        $errors = array();
        $warnings = array();
        $normalized_data = $input;
        
        // Run validators in sequence
        foreach ($this->validator_types as $type => $class_name) {
            $validator = $this->get_validator($type);
            
            if ($validator) {
                $validator_result = $this->run_validator($validator, $normalized_data, $options);
                
                if (!$validator_result['is_valid']) {
                    $errors = array_merge($errors, $validator_result['errors']);
                }
                
                if (isset($validator_result['warnings'])) {
                    $warnings = array_merge($warnings, $validator_result['warnings']);
                }
                
                if (isset($validator_result['normalized_data'])) {
                    $normalized_data = $validator_result['normalized_data'];
                }
            } else {
                // Log missing validator in debug mode
                if ($this->config && $this->config->get('debug_mode', false)) {
                    error_log("QCC: Validator '{$type}' not available");
                }
            }
        }
        
        // Additional cross-validator checks
        $cross_validation_result = $this->perform_cross_validation($normalized_data, $options);
        if (!$cross_validation_result['is_valid']) {
            $errors = array_merge($errors, $cross_validation_result['errors']);
        }
        
        $is_valid = empty($errors);
        
        return array(
            'is_valid' => $is_valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'data' => $is_valid ? $normalized_data : null,
            'validation_summary' => $this->generate_validation_summary($errors, $warnings)
        );
    }
    
    /**
     * Run individual validator
     * 
     * @param object $validator Validator instance
     * @param array $data Data to validate
     * @param array $options Validation options
     * @return array Validator result
     */
    private function run_validator($validator, $data, $options) {
        try {
            if (method_exists($validator, 'validate')) {
                return $validator->validate($data, $options);
            } else {
                return array(
                    'is_valid' => false,
                    'errors' => array('validator_missing_method' => 'Validator does not implement validate method')
                );
            }
        } catch (Exception $e) {
            return array(
                'is_valid' => false,
                'errors' => array('validator_exception' => $e->getMessage())
            );
        }
    }
    
    /**
     * Perform cross-validation checks
     * 
     * @param array $data Normalized data
     * @param array $options Validation options
     * @return array Cross-validation result
     */
    private function perform_cross_validation($data, $options) {
        $errors = array();
        
        // Percentage sum validation (if enabled)
        if (isset($options['validate_percentage_sum']) && $options['validate_percentage_sum']) {
            $percentage_sum = 
                ($data['prevention'] ?? 0) + 
                ($data['appraisal'] ?? 0) + 
                ($data['internal_defect'] ?? 0) + 
                ($data['external_defect'] ?? 0);
            
            if (abs($percentage_sum - 100) > 0.01) { // Allow small floating point errors
                $errors['percentage_sum'] = sprintf(
                    __('Quality cost percentages must sum to 100%%. Current sum: %.2f%%', 'quality-cost-calculator'),
                    $percentage_sum
                );
            }
        }
        
        // ROI validation (if in ROI mode)
        if (isset($options['roi_mode']) && $options['roi_mode']) {
            if (isset($options['require_investment']) && !isset($data['investment'])) {
                $errors['missing_investment'] = __('Investment amount is required for ROI calculation', 'quality-cost-calculator');
            }
            
            if (isset($options['require_savings']) && !isset($data['annual_savings'])) {
                $errors['missing_savings'] = __('Annual savings amount is required for ROI calculation', 'quality-cost-calculator');
            }
        }
        
        // Business logic validation
        if (isset($data['revenue']) && isset($data['quality_percentage'])) {
            $quality_cost = $data['revenue'] * ($data['quality_percentage'] / 100);
            $revenue_ratio = $quality_cost / $data['revenue'];
            
            if ($revenue_ratio > 0.25) { // 25% quality costs might be unrealistic
                $errors['high_quality_ratio'] = __('Quality costs exceed 25% of revenue, please verify inputs', 'quality-cost-calculator');
            }
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors
        );
    }
    
    /**
     * Get validator instance
     * 
     * @param string $type Validator type
     * @return object|null Validator instance
     */
    private function get_validator($type) {
        // Check if already loaded
        if (isset($this->validators[$type])) {
            return $this->validators[$type];
        }
        
        // Try to get from service container
        if ($this->container && $this->container->has($type . '_validator')) {
            $validator = $this->container->get($type . '_validator');
            $this->validators[$type] = $validator;
            return $validator;
        }
        
        // Try direct class instantiation
        $class_name = $this->validator_types[$type] ?? null;
        if ($class_name && class_exists($class_name)) {
            $validator = new $class_name($this->container);
            $this->validators[$type] = $validator;
            return $validator;
        }
        
        return null;
    }
    
    /**
     * Register default validators
     */
    private function register_default_validators() {
        // Validators will be lazy-loaded when needed
        // This method can be extended to pre-register specific validators
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load configuration service
        if ($this->container && $this->container->has('config')) {
            $this->config = $this->container->get('config');
        }
    }
    
    /**
     * Generate validation summary
     * 
     * @param array $errors Validation errors
     * @param array $warnings Validation warnings
     * @return array Validation summary
     */
    private function generate_validation_summary($errors, $warnings) {
        return array(
            'total_errors' => count($errors),
            'total_warnings' => count($warnings),
            'severity' => $this->get_validation_severity($errors, $warnings),
            'has_critical_errors' => $this->has_critical_errors($errors),
            'validation_score' => $this->calculate_validation_score($errors, $warnings)
        );
    }
    
    /**
     * Get validation severity
     * 
     * @param array $errors Errors
     * @param array $warnings Warnings
     * @return string Severity level
     */
    private function get_validation_severity($errors, $warnings) {
        if ($this->has_critical_errors($errors)) {
            return 'critical';
        } elseif (!empty($errors)) {
            return 'error';
        } elseif (!empty($warnings)) {
            return 'warning';
        } else {
            return 'success';
        }
    }
    
    /**
     * Check for critical errors
     * 
     * @param array $errors Validation errors
     * @return bool Has critical errors
     */
    private function has_critical_errors($errors) {
        $critical_error_keys = array('validation_engine_error', 'percentage_sum', 'missing_investment');
        
        foreach ($critical_error_keys as $key) {
            if (isset($errors[$key])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate validation score (0-100)
     * 
     * @param array $errors Validation errors
     * @param array $warnings Validation warnings
     * @return int Validation score
     */
    private function calculate_validation_score($errors, $warnings) {
        $score = 100;
        $score -= count($errors) * 25; // Each error reduces score by 25
        $score -= count($warnings) * 5; // Each warning reduces score by 5
        
        return max(0, $score);
    }
    
    /**
     * Generate cache key for validation
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return string Cache key
     */
    private function generate_cache_key($input, $options) {
        $cache_data = array_merge($input, $options);
        return 'qcc_validation_' . md5(serialize($cache_data));
    }
    
    /**
     * Check if validation caching is enabled
     * 
     * @return bool Cache enabled status
     */
    private function is_cache_enabled() {
        return $this->config ? $this->config->get('cache_validations', true) : true;
    }
    
    /**
     * Clear validation cache
     */
    public function clear_cache() {
        $this->validation_cache = array();
    }
    
    /**
     * Get validation performance statistics
     * 
     * @return array Performance statistics
     */
    public function get_performance_stats() {
        $cache_hit_rate = $this->performance['validations_performed'] > 0 
            ? ($this->performance['cache_hits'] / $this->performance['validations_performed']) * 100 
            : 0;
            
        return array(
            'validations_performed' => $this->performance['validations_performed'],
            'cache_hits' => $this->performance['cache_hits'],
            'cache_hit_rate' => round($cache_hit_rate, 2),
            'total_validation_time' => round($this->performance['validation_time'] * 1000, 2),
            'average_validation_time' => $this->performance['validations_performed'] > 0 
                ? round(($this->performance['validation_time'] / $this->performance['validations_performed']) * 1000, 2) 
                : 0,
            'cache_size' => count($this->validation_cache),
            'registered_validators' => array_keys($this->validator_types)
        );
    }
    
    /**
     * Get engine status for debugging
     * 
     * @return array Engine status
     */
    public function get_status() {
        return array(
            'version' => '2.0.0',
            'container_available' => $this->container !== null,
            'config_loaded' => $this->config !== null,
            'cache_enabled' => $this->is_cache_enabled(),
            'validators_available' => array_keys($this->validator_types),
            'loaded_validators' => array_keys($this->validators),
            'performance' => $this->get_performance_stats()
        );
    }
}
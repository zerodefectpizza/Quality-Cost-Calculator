<?php
/**
 * QCC Calculation Engine - Haupt-Calculator Service
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculation Engine for Quality Cost Calculator
 * 
 * Coordinates all calculation services and provides unified calculation API.
 * Handles validation, caching, and orchestration of specialized calculators.
 */
class QCC_Calculation_Engine {
    
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
     * Validation engine
     * 
     * @var QCC_Validation_Engine
     */
    private $validator;
    
    /**
     * Calculation cache
     * 
     * @var array
     */
    private $cache = array();
    
    /**
     * Performance tracking
     * 
     * @var array
     */
    private $performance = array(
        'calculations_performed' => 0,
        'cache_hits' => 0,
        'calculation_time' => 0
    );
    
    /**
     * Supported calculation types
     * 
     * @var array
     */
    private $calculator_types = array(
        'cogq' => 'QCC_COGQ_Calculator',
        'copq' => 'QCC_COPQ_Calculator', 
        'opportunity' => 'QCC_Opportunity_Calculator',
        'roi' => 'QCC_ROI_Calculator'
    );
    
    /**
     * Constructor
     * 
     * @param QCC_Service_Container $container Service container
     */
    public function __construct($container = null) {
        $this->container = $container;
        $this->load_dependencies();
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load configuration service
        if ($this->container && $this->container->has('config')) {
            $this->config = $this->container->get('config');
        } else {
            $this->config = new QCC_Configuration();
        }
        
        // Load validation engine
        if ($this->container && $this->container->has('validator')) {
            $this->validator = $this->container->get('validator');
        } else {
            // Create fallback validator
            $this->validator = $this->create_fallback_validator();
        }
    }
    
    /**
     * Main calculation method
     * 
     * @param array $input Calculation input data
     * @param array $options Calculation options
     * @return array Calculation results
     * @throws Exception If validation fails or calculation error occurs
     */
    public function calculate($input, $options = array()) {
        $start_time = microtime(true);
        
        try {
            // Normalize and validate input
            $normalized_input = $this->normalize_input($input);
            $validation_result = $this->validate_input($normalized_input);
            
            if ($validation_result !== true) {
                throw new Exception('Validation failed: ' . implode(', ', $validation_result));
            }
            
            // Check cache if enabled
            $cache_key = $this->generate_cache_key($normalized_input, $options);
            if ($this->is_cache_enabled() && isset($this->cache[$cache_key])) {
                $this->performance['cache_hits']++;
                return $this->add_metadata($this->cache[$cache_key], $start_time, true);
            }
            
            // Perform calculations
            $results = $this->perform_calculations($normalized_input, $options);
            
            // Cache results if enabled
            if ($this->is_cache_enabled()) {
                $this->cache[$cache_key] = $results;
            }
            
            // Update performance tracking
            $this->performance['calculations_performed']++;
            
            return $this->add_metadata($results, $start_time, false);
            
        } catch (Exception $e) {
            // Log error and re-throw
            if ($this->config->get('debug_mode', false)) {
                error_log('QCC Calculation Engine Error: ' . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Normalize input data
     * 
     * @param array $input Raw input data
     * @return array Normalized input data
     */
    private function normalize_input($input) {
        $normalized = array();
        
        // Core financial data
        $normalized['revenue'] = $this->normalize_number($input['revenue'] ?? 0);
        $normalized['quality_percentage'] = $this->normalize_percentage($input['quality_percentage'] ?? $this->config->get('default_quality_percentage', 6));
        
        // Quality cost breakdown percentages
        $normalized['prevention'] = $this->normalize_percentage($input['prevention'] ?? $this->config->get('default_prevention', 10));
        $normalized['appraisal'] = $this->normalize_percentage($input['appraisal'] ?? $this->config->get('default_appraisal', 20));
        $normalized['internal_defect'] = $this->normalize_percentage($input['internal_defect'] ?? $this->config->get('default_internal_defect', 30));
        $normalized['external_defect'] = $this->normalize_percentage($input['external_defect'] ?? $this->config->get('default_external_defect', 40));
        
        // Localization settings
        $normalized['currency'] = $input['currency'] ?? $this->config->get('default_currency', 'EUR');
        $normalized['unit'] = $input['unit'] ?? $this->config->get('default_unit', '1000000000');
        $normalized['language'] = $input['language'] ?? $this->config->get('default_language', 'en');
        
        // Advanced options
        $normalized['include_opportunity'] = $input['include_opportunity'] ?? true;
        $normalized['include_roi'] = $input['include_roi'] ?? true;
        $normalized['include_analysis'] = $input['include_analysis'] ?? true;
        
        return $normalized;
    }
    
    /**
     * Validate input data
     * 
     * @param array $input Normalized input data
     * @return array|true Validation errors or true if valid
     */
    private function validate_input($input) {
        if (!$this->validator) {
            return true; // Skip validation if no validator available
        }
        
        return $this->validator->validate($input);
    }
    
    /**
     * Perform all calculations
     * 
     * @param array $input Normalized input data
     * @param array $options Calculation options
     * @return array Complete calculation results
     */
    private function perform_calculations($input, $options) {
        // Start with basic calculations
        $results = $this->calculate_basic_metrics($input);
        
        // Perform specialized calculations
        $calculator_results = $this->run_specialized_calculators($input, $results);
        
        // Merge all results
        $complete_results = array_merge($results, $calculator_results);
        
        // Add summary analysis
        if ($input['include_analysis']) {
            $complete_results['analysis'] = $this->generate_analysis($complete_results);
        }
        
        // Add recommendations
        $complete_results['recommendations'] = $this->generate_recommendations($complete_results);
        
        return $complete_results;
    }
    
    /**
     * Calculate basic financial metrics
     * 
     * @param array $input Normalized input data
     * @return array Basic calculation results
     */
    private function calculate_basic_metrics($input) {
        $revenue = $input['revenue'];
        $quality_percentage = $input['quality_percentage'];
        
        // Core calculations
        $total_quality_cost = $revenue * ($quality_percentage / 100);
        $quality_cost_ratio = $total_quality_cost / $revenue;
        
        // Industry benchmarks for comparison
        $industry_benchmark = $this->get_industry_benchmark($revenue);
        $benchmark_comparison = $quality_percentage - $industry_benchmark;
        
        return array(
            // Input echo
            'input' => $input,
            
            // Core metrics
            'revenue' => $revenue,
            'quality_percentage' => $quality_percentage,
            'total_quality_cost' => $total_quality_cost,
            'quality_cost_ratio' => $quality_cost_ratio,
            
            // Benchmarking
            'industry_benchmark' => $industry_benchmark,
            'benchmark_comparison' => $benchmark_comparison,
            'benchmark_status' => $this->get_benchmark_status($benchmark_comparison),
            
            // Unit conversions
            'revenue_display' => $this->format_currency($revenue, $input['currency'], $input['unit']),
            'quality_cost_display' => $this->format_currency($total_quality_cost, $input['currency'], $input['unit']),
            
            // Calculation metadata
            'calculation_base' => array(
                'method' => 'standard_quality_cost',
                'version' => '2.0.0',
                'precision' => 2
            )
        );
    }
    
    /**
     * Run specialized calculator services
     * 
     * @param array $input Normalized input data
     * @param array $basic_results Basic calculation results
     * @return array Combined calculator results
     */
    private function run_specialized_calculators($input, $basic_results) {
        $calculator_results = array();
        
        foreach ($this->calculator_types as $type => $class_name) {
            try {
                $calculator = $this->get_calculator($type);
                if ($calculator && method_exists($calculator, 'calculate')) {
                    $result = $calculator->calculate($input, $basic_results);
                    $calculator_results[$type] = $result;
                    
                    // Add type-specific analysis if supported
                    if (method_exists($calculator, 'analyze')) {
                        $calculator_results[$type . '_analysis'] = $calculator->analyze($result);
                    }
                } else {
                    // Log missing calculator in debug mode
                    if ($this->config->get('debug_mode', false)) {
                        error_log("QCC: Calculator '{$type}' not available, using fallback");
                    }
                    $calculator_results[$type] = $this->get_fallback_calculation($type, $input, $basic_results);
                }
            } catch (Exception $e) {
                // Log calculator error and use fallback
                if ($this->config->get('debug_mode', false)) {
                    error_log("QCC: Calculator '{$type}' failed: " . $e->getMessage());
                }
                $calculator_results[$type] = $this->get_fallback_calculation($type, $input, $basic_results);
            }
        }
        
        return $calculator_results;
    }
    
    /**
     * Get calculator service instance
     * 
     * @param string $type Calculator type
     * @return object|null Calculator instance
     */
    private function get_calculator($type) {
        // Try to get from service container first
        if ($this->container && $this->container->has($type . '_calculator')) {
            return $this->container->get($type . '_calculator');
        }
        
        // Try direct class instantiation
        $class_name = $this->calculator_types[$type] ?? null;
        if ($class_name && class_exists($class_name)) {
            return new $class_name($this->container);
        }
        
        return null;
    }
    
    /**
     * Generate comprehensive analysis
     * 
     * @param array $results Complete calculation results
     * @return array Analysis summary
     */
    private function generate_analysis($results) {
        $analysis = array(
            'overall_score' => $this->calculate_overall_score($results),
            'key_insights' => $this->extract_key_insights($results),
            'risk_assessment' => $this->assess_risks($results),
            'improvement_potential' => $this->calculate_improvement_potential($results)
        );
        
        return $analysis;
    }
    
    /**
     * Generate actionable recommendations
     * 
     * @param array $results Complete calculation results
     * @return array Recommendations
     */
    private function generate_recommendations($results) {
        $recommendations = array();
        
        // COGQ/COPQ balance recommendations
        if (isset($results['cogq']['total_cogq']) && isset($results['copq']['total_copq'])) {
            $cogq_copq_ratio = $results['cogq']['total_cogq'] / ($results['copq']['total_copq'] + 0.01); // Avoid division by zero
            
            if ($cogq_copq_ratio < 0.3) {
                $recommendations[] = array(
                    'priority' => 'high',
                    'category' => 'balance',
                    'title' => __('Increase Prevention Investment', 'quality-cost-calculator'),
                    'description' => __('Your COGQ is very low compared to COPQ. Consider investing more in prevention activities.', 'quality-cost-calculator'),
                    'expected_impact' => 'high'
                );
            }
        }
        
        // Quality percentage recommendations
        if ($results['benchmark_comparison'] > 2) {
            $recommendations[] = array(
                'priority' => 'medium',
                'category' => 'benchmarking',
                'title' => __('Quality Costs Above Industry Average', 'quality-cost-calculator'),
                'description' => __('Your quality costs are above industry benchmark. Focus on defect reduction and process improvement.', 'quality-cost-calculator'),
                'expected_impact' => 'medium'
            );
        }
        
        // Add opportunity-based recommendations if available
        if (isset($results['opportunity'])) {
            $recommendations = array_merge($recommendations, $this->get_opportunity_recommendations($results['opportunity']));
        }
        
        return $recommendations;
    }
    
    /**
     * Get industry benchmark for revenue size
     * 
     * @param float $revenue Company revenue
     * @return float Industry benchmark percentage
     */
    private function get_industry_benchmark($revenue) {
        // Industry benchmarks based on company size
        if ($revenue < 10000000) { // < 10M
            return 8.0;
        } elseif ($revenue < 100000000) { // 10M - 100M
            return 6.5;
        } elseif ($revenue < 1000000000) { // 100M - 1B
            return 5.5;
        } else { // > 1B
            return 4.5;
        }
    }
    
    /**
     * Get benchmark status
     * 
     * @param float $comparison Benchmark comparison value
     * @return string Status description
     */
    private function get_benchmark_status($comparison) {
        if ($comparison <= -1) {
            return 'excellent';
        } elseif ($comparison <= 0) {
            return 'good';
        } elseif ($comparison <= 2) {
            return 'average';
        } else {
            return 'needs_improvement';
        }
    }
    
    /**
     * Calculate overall quality score
     * 
     * @param array $results Complete calculation results
     * @return array Overall score metrics
     */
    private function calculate_overall_score($results) {
        $scores = array();
        
        // Benchmark score (40% weight)
        $benchmark_score = max(0, 100 - ($results['benchmark_comparison'] * 10));
        $scores['benchmark'] = min(100, $benchmark_score);
        
        // COGQ/COPQ balance score (35% weight)
        if (isset($results['cogq']['total_cogq']) && isset($results['copq']['total_copq'])) {
            $balance_ratio = $results['cogq']['total_cogq'] / ($results['copq']['total_copq'] + $results['cogq']['total_cogq']);
            $scores['balance'] = min(100, max(0, (0.4 - abs($balance_ratio - 0.4)) * 250));
        } else {
            $scores['balance'] = 50; // Default neutral score
        }
        
        // Prevention focus score (25% weight)
        if (isset($results['cogq']['prevention_ratio'])) {
            $prevention_ratio = $results['cogq']['prevention_ratio'];
            $scores['prevention'] = min(100, max(0, $prevention_ratio * 2));
        } else {
            $scores['prevention'] = 50;
        }
        
        // Calculate weighted average
        $overall_score = (
            $scores['benchmark'] * 0.40 +
            $scores['balance'] * 0.35 +
            $scores['prevention'] * 0.25
        );
        
        return array(
            'overall' => round($overall_score, 1),
            'components' => $scores,
            'grade' => $this->get_score_grade($overall_score)
        );
    }
    
    /**
     * Get score grade
     * 
     * @param float $score Overall score
     * @return string Grade letter
     */
    private function get_score_grade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Create fallback validator
     * 
     * @return object Basic validator
     */
    private function create_fallback_validator() {
        return new class {
            public function validate($input) {
                $errors = array();
                
                if (!isset($input['revenue']) || $input['revenue'] <= 0) {
                    $errors[] = 'Revenue must be positive';
                }
                
                if (!isset($input['quality_percentage']) || $input['quality_percentage'] < 0 || $input['quality_percentage'] > 100) {
                    $errors[] = 'Quality percentage must be between 0 and 100';
                }
                
                return empty($errors) ? true : $errors;
            }
        };
    }
    
    /**
     * Get fallback calculation for missing calculator
     * 
     * @param string $type Calculator type
     * @param array $input Input data
     * @param array $basic_results Basic results
     * @return array Fallback calculation
     */
    private function get_fallback_calculation($type, $input, $basic_results) {
        $total_quality_cost = $basic_results['total_quality_cost'];
        
        switch ($type) {
            case 'cogq':
                $prevention = $total_quality_cost * ($input['prevention'] / 100);
                $appraisal = $total_quality_cost * ($input['appraisal'] / 100);
                return array(
                    'prevention_cost' => $prevention,
                    'appraisal_cost' => $appraisal,
                    'total_cogq' => $prevention + $appraisal,
                    'fallback' => true
                );
                
            case 'copq':
                $internal = $total_quality_cost * ($input['internal_defect'] / 100);
                $external = $total_quality_cost * ($input['external_defect'] / 100);
                return array(
                    'internal_defect_cost' => $internal,
                    'external_defect_cost' => $external,
                    'total_copq' => $internal + $external,
                    'fallback' => true
                );
                
            default:
                return array('fallback' => true, 'message' => "Calculator '{$type}' not available");
        }
    }
    
    /**
     * Generate cache key
     * 
     * @param array $input Normalized input data
     * @param array $options Calculation options
     * @return string Cache key
     */
    private function generate_cache_key($input, $options) {
        $cache_data = array_merge($input, $options);
        unset($cache_data['language']); // Exclude UI language from cache key
        
        return 'qcc_calc_' . md5(serialize($cache_data));
    }
    
    /**
     * Check if caching is enabled
     * 
     * @return bool Cache enabled status
     */
    private function is_cache_enabled() {
        return $this->config->get('cache_calculations', true);
    }
    
    /**
     * Add calculation metadata
     * 
     * @param array $results Calculation results
     * @param float $start_time Start time
     * @param bool $from_cache From cache flag
     * @return array Results with metadata
     */
    private function add_metadata($results, $start_time, $from_cache) {
        $execution_time = microtime(true) - $start_time;
        $this->performance['calculation_time'] += $execution_time;
        
        $results['metadata'] = array(
            'calculation_time' => round($execution_time * 1000, 2), // ms
            'from_cache' => $from_cache,
            'timestamp' => current_time('timestamp'),
            'version' => '2.0.0',
            'engine' => 'QCC_Calculation_Engine'
        );
        
        return $results;
    }
    
    /**
     * Normalize number input
     * 
     * @param mixed $value Input value
     * @return float Normalized number
     */
    private function normalize_number($value) {
        return floatval(str_replace(',', '', $value));
    }
    
    /**
     * Normalize percentage input
     * 
     * @param mixed $value Input value
     * @return float Normalized percentage
     */
    private function normalize_percentage($value) {
        $number = $this->normalize_number($value);
        return max(0, min(100, $number));
    }
    
    /**
     * Format currency for display
     * 
     * @param float $amount Amount
     * @param string $currency Currency code
     * @param string $unit Unit multiplier
     * @return string Formatted currency
     */
    private function format_currency($amount, $currency, $unit) {
        $unit_divisor = intval($unit);
        $formatted_amount = $amount / $unit_divisor;
        
        $currency_symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'CNY' => '¥'
        );
        
        $symbol = $currency_symbols[$currency] ?? $currency;
        $unit_suffix = $unit_divisor >= 1000000000 ? 'B' : 'M';
        
        return number_format($formatted_amount, 2) . ' ' . $symbol . ' ' . $unit_suffix;
    }
    
    /**
     * Clear calculation cache
     */
    public function clear_cache() {
        $this->cache = array();
    }
    
    /**
     * Get calculation performance statistics
     * 
     * @return array Performance statistics
     */
    public function get_performance_stats() {
        $cache_hit_rate = $this->performance['calculations_performed'] > 0 
            ? ($this->performance['cache_hits'] / $this->performance['calculations_performed']) * 100 
            : 0;
            
        return array(
            'calculations_performed' => $this->performance['calculations_performed'],
            'cache_hits' => $this->performance['cache_hits'],
            'cache_hit_rate' => round($cache_hit_rate, 2),
            'total_calculation_time' => round($this->performance['calculation_time'] * 1000, 2),
            'average_calculation_time' => $this->performance['calculations_performed'] > 0 
                ? round(($this->performance['calculation_time'] / $this->performance['calculations_performed']) * 1000, 2) 
                : 0,
            'cache_size' => count($this->cache),
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    /**
     * Export calculation results for external use
     * 
     * @param array $results Calculation results
     * @param string $format Export format
     * @return array|string Exported data
     */
    public function export_results($results, $format = 'array') {
        switch ($format) {
            case 'json':
                return json_encode($results, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->convert_to_csv($results);
            default:
                return $results;
        }
    }
    
    /**
     * Convert results to CSV format
     * 
     * @param array $results Calculation results
     * @return string CSV data
     */
    private function convert_to_csv($results) {
        $csv_data = array();
        $csv_data[] = 'Metric,Value,Unit';
        
        $csv_data[] = 'Revenue,' . $results['revenue'] . ',' . $results['input']['currency'];
        $csv_data[] = 'Quality Percentage,' . $results['quality_percentage'] . ',%';
        $csv_data[] = 'Total Quality Cost,' . $results['total_quality_cost'] . ',' . $results['input']['currency'];
        
        if (isset($results['cogq'])) {
            $csv_data[] = 'Total COGQ,' . $results['cogq']['total_cogq'] . ',' . $results['input']['currency'];
            $csv_data[] = 'Prevention Cost,' . $results['cogq']['prevention_cost'] . ',' . $results['input']['currency'];
            $csv_data[] = 'Appraisal Cost,' . $results['cogq']['appraisal_cost'] . ',' . $results['input']['currency'];
        }
        
        if (isset($results['copq'])) {
            $csv_data[] = 'Total COPQ,' . $results['copq']['total_copq'] . ',' . $results['input']['currency'];
            $csv_data[] = 'Internal Defect Cost,' . $results['copq']['internal_defect_cost'] . ',' . $results['input']['currency'];
            $csv_data[] = 'External Defect Cost,' . $results['copq']['external_defect_cost'] . ',' . $results['input']['currency'];
        }
        
        return implode("\n", $csv_data);
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
            'validator_available' => $this->validator !== null,
            'cache_enabled' => $this->is_cache_enabled(),
            'calculators_registered' => array_keys($this->calculator_types),
            'performance' => $this->get_performance_stats()
        );
    }
}
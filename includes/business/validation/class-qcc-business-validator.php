<?php
/**
 * QCC Business Validator - Geschäftsregeln und Cross-Field-Validierung
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
 * Business Validator for Quality Cost Calculator
 * 
 * Handles business logic validation including cross-field validation, 
 * industry benchmarks, and financial plausibility checks.
 */
class QCC_Business_Validator {
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Industry benchmarks by company size
     * 
     * @var array
     */
    private $industry_benchmarks = array(
        'small' => array(          // < 10M revenue
            'quality_percentage' => array('min' => 6.0, 'max' => 12.0, 'average' => 8.0),
            'cogq_ratio' => array('min' => 0.25, 'max' => 0.45, 'average' => 0.35),
            'prevention_ratio' => array('min' => 0.05, 'max' => 0.25, 'average' => 0.15)
        ),
        'medium' => array(         // 10M - 100M revenue
            'quality_percentage' => array('min' => 4.5, 'max' => 9.0, 'average' => 6.5),
            'cogq_ratio' => array('min' => 0.30, 'max' => 0.50, 'average' => 0.40),
            'prevention_ratio' => array('min' => 0.10, 'max' => 0.30, 'average' => 0.20)
        ),
        'large' => array(          // 100M - 1B revenue
            'quality_percentage' => array('min' => 3.5, 'max' => 7.5, 'average' => 5.5),
            'cogq_ratio' => array('min' => 0.35, 'max' => 0.55, 'average' => 0.45),
            'prevention_ratio' => array('min' => 0.15, 'max' => 0.35, 'average' => 0.25)
        ),
        'enterprise' => array(     // > 1B revenue
            'quality_percentage' => array('min' => 2.5, 'max' => 6.5, 'average' => 4.5),
            'cogq_ratio' => array('min' => 0.40, 'max' => 0.60, 'average' => 0.50),
            'prevention_ratio' => array('min' => 0.20, 'max' => 0.40, 'average' => 0.30)
        )
    );
    
    /**
     * Business rule severity levels
     * 
     * @var array
     */
    private $severity_thresholds = array(
        'critical' => array(
            'quality_percentage_max' => 25.0,  // Over 25% is critical
            'cogq_ratio_min' => 0.05,          // Under 5% COGQ is critical
            'prevention_ratio_min' => 0.02     // Under 2% prevention is critical
        ),
        'warning' => array(
            'quality_percentage_max' => 15.0,  // Over 15% is warning
            'cogq_ratio_min' => 0.15,          // Under 15% COGQ is warning
            'prevention_ratio_min' => 0.05     // Under 5% prevention is warning
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
     * Validate business rules
     * 
     * @param array $input Normalized input data
     * @param array $options Validation options
     * @return array Validation result
     */
    public function validate($input, $options = array()) {
        $errors = array();
        $warnings = array();
        $normalized_data = $input;
        
        // Perform business rule validations
        $percentage_result = $this->validate_percentage_distribution($input, $options);
        $benchmark_result = $this->validate_industry_benchmarks($input, $options);
        $balance_result = $this->validate_cogq_copq_balance($input, $options);
        $plausibility_result = $this->validate_financial_plausibility($input, $options);
        $roi_result = $this->validate_roi_business_rules($input, $options);
        
        // Aggregate results
        $all_results = array($percentage_result, $benchmark_result, $balance_result, $plausibility_result, $roi_result);
        
        foreach ($all_results as $result) {
            if (!$result['is_valid']) {
                $errors = array_merge($errors, $result['errors']);
            }
            if (isset($result['warnings'])) {
                $warnings = array_merge($warnings, $result['warnings']);
            }
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'normalized_data' => empty($errors) ? $normalized_data : null,
            'business_insights' => $this->generate_business_insights($input)
        );
    }
    
    /**
     * Validate percentage distribution
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_percentage_distribution($input, $options) {
        $errors = array();
        $warnings = array();
        
        // Extract percentages
        $prevention = $input['prevention'] ?? 0;
        $appraisal = $input['appraisal'] ?? 0;
        $internal_defect = $input['internal_defect'] ?? 0;
        $external_defect = $input['external_defect'] ?? 0;
        
        $total_percentage = $prevention + $appraisal + $internal_defect + $external_defect;
        
        // Check if percentages sum to 100%
        $tolerance = isset($options['strict_mode']) && $options['strict_mode'] ? 0.01 : 0.1;
        
        if (abs($total_percentage - 100) > $tolerance) {
            $errors['percentage_sum_invalid'] = sprintf(
                __('Quality cost percentages must sum to 100%%. Current sum: %.2f%%', 'quality-cost-calculator'),
                $total_percentage
            );
        }
        
        // Validate individual percentage ranges
        $cogq_total = $prevention + $appraisal;
        $copq_total = $internal_defect + $external_defect;
        
        // COGQ should not be too low (companies need some quality investment)
        if ($cogq_total < 10) {
            $warnings['cogq_too_low'] = sprintf(
                __('Combined COGQ (Prevention + Appraisal) is only %.1f%%. Consider increasing quality investment.', 'quality-cost-calculator'),
                $cogq_total
            );
        }
        
        // COPQ should not be too high (indicates poor quality management)
        if ($copq_total > 80) {
            $errors['copq_too_high'] = sprintf(
                __('Combined COPQ (Internal + External Defects) is %.1f%%. This indicates severe quality issues.', 'quality-cost-calculator'),
                $copq_total
            );
        }
        
        // Prevention should be significant portion of COGQ
        if ($cogq_total > 0 && ($prevention / $cogq_total) < 0.3) {
            $warnings['prevention_ratio_low'] = sprintf(
                __('Prevention costs are only %.1f%% of total COGQ. Industry best practice is 40-60%%.', 'quality-cost-calculator'),
                ($prevention / $cogq_total) * 100
            );
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Validate against industry benchmarks
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_industry_benchmarks($input, $options) {
        $errors = array();
        $warnings = array();
        
        $revenue = $input['revenue'] ?? 0;
        $quality_percentage = $input['quality_percentage'] ?? 0;
        
        if ($revenue <= 0) {
            return array('is_valid' => true); // Skip if no revenue
        }
        
        // Determine company size category
        $size_category = $this->get_company_size_category($revenue);
        $benchmarks = $this->industry_benchmarks[$size_category];
        
        // Validate quality percentage against industry benchmark
        if ($quality_percentage > $benchmarks['quality_percentage']['max']) {
            $errors['quality_percentage_above_benchmark'] = sprintf(
                __('Quality costs (%.1f%%) significantly exceed industry benchmark for %s companies (%.1f%%).', 'quality-cost-calculator'),
                $quality_percentage,
                $size_category,
                $benchmarks['quality_percentage']['max']
            );
        } elseif ($quality_percentage > $benchmarks['quality_percentage']['average'] * 1.5) {
            $warnings['quality_percentage_high'] = sprintf(
                __('Quality costs (%.1f%%) are above industry average for %s companies (%.1f%%).', 'quality-cost-calculator'),
                $quality_percentage,
                $size_category,
                $benchmarks['quality_percentage']['average']
            );
        }
        
        // Calculate COGQ ratio for benchmark comparison
        $prevention = $input['prevention'] ?? 0;
        $appraisal = $input['appraisal'] ?? 0;
        $cogq_ratio = ($prevention + $appraisal) / 100;
        
        if ($cogq_ratio < $benchmarks['cogq_ratio']['min']) {
            $warnings['cogq_ratio_below_benchmark'] = sprintf(
                __('COGQ ratio (%.1f%%) is below industry minimum for %s companies (%.1f%%).', 'quality-cost-calculator'),
                $cogq_ratio * 100,
                $size_category,
                $benchmarks['cogq_ratio']['min'] * 100
            );
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Validate COGQ/COPQ balance
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_cogq_copq_balance($input, $options) {
        $errors = array();
        $warnings = array();
        
        $prevention = $input['prevention'] ?? 0;
        $appraisal = $input['appraisal'] ?? 0;
        $internal_defect = $input['internal_defect'] ?? 0;
        $external_defect = $input['external_defect'] ?? 0;
        
        $cogq_total = $prevention + $appraisal;
        $copq_total = $internal_defect + $external_defect;
        $total_quality = $cogq_total + $copq_total;
        
        if ($total_quality <= 0) {
            return array('is_valid' => true); // Skip if no quality costs
        }
        
        $cogq_ratio = $cogq_total / $total_quality;
        $copq_ratio = $copq_total / $total_quality;
        
        // Critical imbalances
        if ($cogq_ratio < 0.15) { // Less than 15% COGQ
            $errors['cogq_critically_low'] = sprintf(
                __('COGQ is only %.1f%% of total quality costs. This indicates severe under-investment in quality prevention.', 'quality-cost-calculator'),
                $cogq_ratio * 100
            );
        }
        
        if ($copq_ratio > 0.85) { // More than 85% COPQ
            $errors['copq_critically_high'] = sprintf(
                __('COPQ is %.1f%% of total quality costs. This indicates critical quality management problems.', 'quality-cost-calculator'),
                $copq_ratio * 100
            );
        }
        
        // Warning thresholds
        if ($cogq_ratio < 0.25 && !isset($errors['cogq_critically_low'])) {
            $warnings['cogq_balance_warning'] = sprintf(
                __('COGQ (%.1f%%) is below recommended minimum of 25%%. Consider increasing prevention investment.', 'quality-cost-calculator'),
                $cogq_ratio * 100
            );
        }
        
        // External defect costs should not dominate
        if ($external_defect > ($internal_defect * 2) && $external_defect > 30) {
            $warnings['external_defects_high'] = sprintf(
                __('External defect costs (%.1f%%) are significantly higher than internal defects. This indicates quality issues reaching customers.', 'quality-cost-calculator'),
                $external_defect
            );
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Validate financial plausibility
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_financial_plausibility($input, $options) {
        $errors = array();
        $warnings = array();
        
        $revenue = $input['revenue'] ?? 0;
        $quality_percentage = $input['quality_percentage'] ?? 0;
        
        if ($revenue <= 0) {
            return array('is_valid' => true);
        }
        
        $total_quality_cost = $revenue * ($quality_percentage / 100);
        
        // Critical thresholds
        if ($quality_percentage > 25) {
            $errors['quality_costs_implausible'] = sprintf(
                __('Quality costs of %.1f%% of revenue (%.0f) seem implausibly high. Please verify inputs.', 'quality-cost-calculator'),
                $quality_percentage,
                $total_quality_cost
            );
        }
        
        // Warning thresholds
        if ($quality_percentage > 15) {
            $warnings['quality_costs_high'] = sprintf(
                __('Quality costs of %.1f%% are above typical range (2-10%%). Verify this reflects your actual situation.', 'quality-cost-calculator'),
                $quality_percentage
            );
        }
        
        // Revenue-based plausibility checks
        if ($revenue < 100000 && $quality_percentage > 12) {
            $warnings['small_company_high_costs'] = __('High quality cost percentage for small revenue. Small companies often have proportionally higher quality costs.', 'quality-cost-calculator');
        }
        
        if ($revenue > 1000000000 && $quality_percentage < 2) {
            $warnings['large_company_low_costs'] = __('Very low quality costs for large enterprise. Large companies typically have quality costs of 3-6%.', 'quality-cost-calculator');
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Validate ROI business rules
     * 
     * @param array $input Input data
     * @param array $options Validation options
     * @return array Validation result
     */
    private function validate_roi_business_rules($input, $options) {
        $errors = array();
        $warnings = array();
        
        // Only validate if ROI parameters are present
        if (!isset($input['investment']) || !isset($input['annual_savings'])) {
            return array('is_valid' => true);
        }
        
        $investment = $input['investment'];
        $annual_savings = $input['annual_savings'];
        $revenue = $input['revenue'] ?? 0;
        
        // Investment should not exceed reasonable limits
        if ($revenue > 0 && ($investment / $revenue) > 0.1) {
            $warnings['investment_high_vs_revenue'] = sprintf(
                __('Investment (%.0f) is %.1f%% of revenue. Ensure this is realistic for your organization.', 'quality-cost-calculator'),
                $investment,
                ($investment / $revenue) * 100
            );
        }
        
        // Annual savings should be plausible
        if ($revenue > 0 && ($annual_savings / $revenue) > 0.05) {
            $warnings['savings_high_vs_revenue'] = sprintf(
                __('Annual savings (%.0f) represent %.1f%% of revenue. Verify this target is achievable.', 'quality-cost-calculator'),
                $annual_savings,
                ($annual_savings / $revenue) * 100
            );
        }
        
        // Investment vs savings ratio
        if ($annual_savings > 0 && ($investment / $annual_savings) > 5) {
            $warnings['investment_savings_ratio'] = sprintf(
                __('Investment is %.1f times annual savings. Payback period will be over 5 years.', 'quality-cost-calculator'),
                $investment / $annual_savings
            );
        }
        
        // Unrealistically high savings
        if ($annual_savings > $investment * 2) {
            $warnings['savings_vs_investment'] = __('Annual savings are more than twice the investment. Verify these projections are realistic.', 'quality-cost-calculator');
        }
        
        return array(
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Generate business insights
     * 
     * @param array $input Input data
     * @return array Business insights
     */
    private function generate_business_insights($input) {
        $revenue = $input['revenue'] ?? 0;
        $quality_percentage = $input['quality_percentage'] ?? 0;
        $prevention = $input['prevention'] ?? 0;
        $appraisal = $input['appraisal'] ?? 0;
        $internal_defect = $input['internal_defect'] ?? 0;
        $external_defect = $input['external_defect'] ?? 0;
        
        $size_category = $this->get_company_size_category($revenue);
        $benchmarks = $this->industry_benchmarks[$size_category];
        
        $cogq_total = $prevention + $appraisal;
        $copq_total = $internal_defect + $external_defect;
        
        return array(
            'company_size_category' => $size_category,
            'industry_benchmark' => $benchmarks['quality_percentage']['average'],
            'benchmark_comparison' => $quality_percentage - $benchmarks['quality_percentage']['average'],
            'cogq_copq_ratio' => $copq_total > 0 ? $cogq_total / $copq_total : 0,
            'prevention_focus_score' => $cogq_total > 0 ? ($prevention / $cogq_total) * 100 : 0,
            'quality_maturity_level' => $this->assess_quality_maturity($input, $benchmarks),
            'improvement_priority' => $this->get_improvement_priority($input)
        );
    }
    
    /**
     * Get company size category
     * 
     * @param float $revenue Company revenue
     * @return string Size category
     */
    private function get_company_size_category($revenue) {
        if ($revenue < 10000000) {
            return 'small';
        } elseif ($revenue < 100000000) {
            return 'medium';
        } elseif ($revenue < 1000000000) {
            return 'large';
        } else {
            return 'enterprise';
        }
    }
    
    /**
     * Assess quality maturity level
     * 
     * @param array $input Input data
     * @param array $benchmarks Industry benchmarks
     * @return string Maturity level
     */
    private function assess_quality_maturity($input, $benchmarks) {
        $prevention = $input['prevention'] ?? 0;
        $appraisal = $input['appraisal'] ?? 0;
        $quality_percentage = $input['quality_percentage'] ?? 0;
        
        $cogq_ratio = ($prevention + $appraisal) / 100;
        $prevention_ratio = $prevention > 0 ? $prevention / ($prevention + $appraisal) : 0;
        
        $score = 0;
        
        // Quality percentage vs benchmark
        if ($quality_percentage <= $benchmarks['quality_percentage']['average']) $score += 25;
        
        // COGQ ratio
        if ($cogq_ratio >= $benchmarks['cogq_ratio']['average']) $score += 25;
        
        // Prevention focus
        if ($prevention_ratio >= 0.4) $score += 25;
        if ($prevention_ratio >= 0.6) $score += 10;
        
        // Balance
        if ($cogq_ratio >= 0.3 && $cogq_ratio <= 0.7) $score += 15;
        
        if ($score >= 85) return 'advanced';
        if ($score >= 65) return 'mature';
        if ($score >= 40) return 'developing';
        return 'basic';
    }
    
    /**
     * Get improvement priority
     * 
     * @param array $input Input data
     * @return string Improvement priority
     */
    private function get_improvement_priority($input) {
        $prevention = $input['prevention'] ?? 0;
        $external_defect = $input['external_defect'] ?? 0;
        $cogq_total = ($input['prevention'] ?? 0) + ($input['appraisal'] ?? 0);
        
        if ($external_defect > 35) {
            return 'customer_facing_quality';
        } elseif ($prevention < 10) {
            return 'prevention_investment';
        } elseif ($cogq_total < 25) {
            return 'quality_investment';
        } else {
            return 'process_optimization';
        }
    }
    
    /**
     * Get validator status
     * 
     * @return array Validator status
     */
    public function get_status() {
        return array(
            'version' => '2.0.0',
            'validator_type' => 'business',
            'industry_benchmarks' => array_keys($this->industry_benchmarks),
            'severity_levels' => array_keys($this->severity_thresholds),
            'validation_categories' => array(
                'percentage_distribution',
                'industry_benchmarks',
                'cogq_copq_balance',
                'financial_plausibility',
                'roi_business_rules'
            )
        );
    }
}
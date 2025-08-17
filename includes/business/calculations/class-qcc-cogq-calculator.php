<?php
/**
 * QCC COGQ Calculator - Cost of Good Quality
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * COGQ Calculator for Quality Cost Calculator
 * 
 * Calculates Cost of Good Quality (Prevention + Appraisal Costs)
 * Provides analysis and optimization recommendations
 */
class QCC_COGQ_Calculator {
    
    /**
     * Configuration service
     * 
     * @var QCC_Configuration
     */
    private $config;
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Industry benchmarks for COGQ
     * 
     * @var array
     */
    private $benchmarks = array(
        'prevention_optimal' => array('min' => 40, 'max' => 60),
        'appraisal_optimal' => array('min' => 20, 'max' => 40),
        'prevention_minimum' => 30,
        'appraisal_maximum' => 50
    );
    
    /**
     * Constructor
     * 
     * @param QCC_Service_Container $container Service container
     */
    public function __construct($container = null) {
        $this->container = $container;
        $this->config = $container ? $container->get('config') : new QCC_Configuration();
    }
    
    /**
     * Calculate COGQ metrics
     * 
     * @param array $input Input data
     * @param array $basic_results Basic calculation results
     * @return array COGQ calculation results
     */
    public function calculate($input, $basic_results) {
        $total_quality_cost = $basic_results['total_quality_cost'];
        $revenue = $basic_results['revenue'];
        
        // Get percentages from input or configuration defaults
        $prevention_percentage = $input['prevention'] ?? $this->config->get('default_prevention', 10);
        $appraisal_percentage = $input['appraisal'] ?? $this->config->get('default_appraisal', 20);
        
        // Calculate absolute costs
        $prevention_cost = $total_quality_cost * ($prevention_percentage / 100);
        $appraisal_cost = $total_quality_cost * ($appraisal_percentage / 100);
        $total_cogq = $prevention_cost + $appraisal_cost;
        
        // Calculate ratios and percentages
        $cogq_percentage = ($total_cogq / $revenue) * 100;
        $prevention_ratio = $total_cogq > 0 ? ($prevention_cost / $total_cogq) * 100 : 0;
        $appraisal_ratio = $total_cogq > 0 ? ($appraisal_cost / $total_cogq) * 100 : 0;
        
        // Calculate efficiency metrics
        $efficiency_metrics = $this->calculate_efficiency_metrics($prevention_cost, $appraisal_cost, $revenue);
        
        // Industry benchmarking
        $benchmark_analysis = $this->analyze_against_benchmarks($prevention_ratio, $appraisal_ratio, $cogq_percentage);
        
        // ROI estimates for COGQ
        $roi_estimates = $this->estimate_cogq_roi($prevention_cost, $appraisal_cost, $basic_results);
        
        return array(
            // Core COGQ metrics
            'prevention_cost' => $prevention_cost,
            'appraisal_cost' => $appraisal_cost,
            'total_cogq' => $total_cogq,
            
            // Percentages and ratios
            'cogq_percentage' => $cogq_percentage,
            'prevention_ratio' => $prevention_ratio,
            'appraisal_ratio' => $appraisal_ratio,
            'prevention_percentage_input' => $prevention_percentage,
            'appraisal_percentage_input' => $appraisal_percentage,
            
            // Advanced metrics
            'efficiency_metrics' => $efficiency_metrics,
            'benchmark_analysis' => $benchmark_analysis,
            'roi_estimates' => $roi_estimates,
            
            // Formatted display values
            'prevention_cost_display' => $this->format_currency($prevention_cost, $input),
            'appraisal_cost_display' => $this->format_currency($appraisal_cost, $input),
            'total_cogq_display' => $this->format_currency($total_cogq, $input),
            
            // Calculation metadata
            'calculation_method' => 'standard_cogq',
            'calculation_timestamp' => current_time('timestamp')
        );
    }
    
    /**
     * Calculate efficiency metrics
     * 
     * @param float $prevention_cost Prevention cost
     * @param float $appraisal_cost Appraisal cost
     * @param float $revenue Total revenue
     * @return array Efficiency metrics
     */
    private function calculate_efficiency_metrics($prevention_cost, $appraisal_cost, $revenue) {
        // Prevention efficiency (how much prevention per revenue dollar)
        $prevention_efficiency = $revenue > 0 ? ($prevention_cost / $revenue) * 1000 : 0;
        
        // Appraisal efficiency
        $appraisal_efficiency = $revenue > 0 ? ($appraisal_cost / $revenue) * 1000 : 0;
        
        // Prevention to appraisal ratio (ideal is 1.5-2.0)
        $prevention_appraisal_ratio = $appraisal_cost > 0 ? $prevention_cost / $appraisal_cost : 0;
        
        // Quality investment intensity
        $quality_investment_intensity = $revenue > 0 ? (($prevention_cost + $appraisal_cost) / $revenue) * 100 : 0;
        
        return array(
            'prevention_efficiency' => $prevention_efficiency,
            'appraisal_efficiency' => $appraisal_efficiency,
            'prevention_appraisal_ratio' => $prevention_appraisal_ratio,
            'quality_investment_intensity' => $quality_investment_intensity,
            'efficiency_score' => $this->calculate_efficiency_score($prevention_appraisal_ratio, $quality_investment_intensity)
        );
    }
    
    /**
     * Calculate efficiency score
     * 
     * @param float $prevention_appraisal_ratio Prevention to appraisal ratio
     * @param float $quality_investment_intensity Quality investment intensity
     * @return array Efficiency score details
     */
    private function calculate_efficiency_score($prevention_appraisal_ratio, $quality_investment_intensity) {
        $score = 0;
        $factors = array();
        
        // Prevention/Appraisal ratio scoring (40% weight)
        if ($prevention_appraisal_ratio >= 1.5 && $prevention_appraisal_ratio <= 2.5) {
            $ratio_score = 100;
            $factors['ratio_status'] = 'optimal';
        } elseif ($prevention_appraisal_ratio >= 1.0 && $prevention_appraisal_ratio < 1.5) {
            $ratio_score = 75;
            $factors['ratio_status'] = 'good';
        } elseif ($prevention_appraisal_ratio >= 0.5 && $prevention_appraisal_ratio < 1.0) {
            $ratio_score = 50;
            $factors['ratio_status'] = 'needs_improvement';
        } else {
            $ratio_score = 25;
            $factors['ratio_status'] = 'poor';
        }
        
        // Investment intensity scoring (60% weight)
        if ($quality_investment_intensity >= 2 && $quality_investment_intensity <= 4) {
            $intensity_score = 100;
            $factors['intensity_status'] = 'optimal';
        } elseif ($quality_investment_intensity >= 1.5 && $quality_investment_intensity < 2) {
            $intensity_score = 80;
            $factors['intensity_status'] = 'low';
        } elseif ($quality_investment_intensity > 4 && $quality_investment_intensity <= 6) {
            $intensity_score = 80;
            $factors['intensity_status'] = 'high';
        } else {
            $intensity_score = 40;
            $factors['intensity_status'] = $quality_investment_intensity < 1.5 ? 'very_low' : 'very_high';
        }
        
        // Calculate weighted score
        $score = ($ratio_score * 0.4) + ($intensity_score * 0.6);
        
        return array(
            'score' => round($score, 1),
            'grade' => $this->get_efficiency_grade($score),
            'factors' => $factors
        );
    }
    
    /**
     * Get efficiency grade
     * 
     * @param float $score Efficiency score
     * @return string Grade letter
     */
    private function get_efficiency_grade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Analyze against industry benchmarks
     * 
     * @param float $prevention_ratio Prevention ratio
     * @param float $appraisal_ratio Appraisal ratio
     * @param float $cogq_percentage COGQ percentage
     * @return array Benchmark analysis
     */
    private function analyze_against_benchmarks($prevention_ratio, $appraisal_ratio, $cogq_percentage) {
        $analysis = array();
        
        // Prevention ratio analysis
        if ($prevention_ratio >= $this->benchmarks['prevention_optimal']['min'] && 
            $prevention_ratio <= $this->benchmarks['prevention_optimal']['max']) {
            $analysis['prevention_status'] = 'optimal';
            $analysis['prevention_message'] = __('Prevention ratio is within optimal range', 'quality-cost-calculator');
        } elseif ($prevention_ratio < $this->benchmarks['prevention_minimum']) {
            $analysis['prevention_status'] = 'low';
            $analysis['prevention_message'] = __('Prevention ratio is below recommended minimum', 'quality-cost-calculator');
        } else {
            $analysis['prevention_status'] = 'high';
            $analysis['prevention_message'] = __('Prevention ratio is above optimal range', 'quality-cost-calculator');
        }
        
        // Appraisal ratio analysis
        if ($appraisal_ratio >= $this->benchmarks['appraisal_optimal']['min'] && 
            $appraisal_ratio <= $this->benchmarks['appraisal_optimal']['max']) {
            $analysis['appraisal_status'] = 'optimal';
            $analysis['appraisal_message'] = __('Appraisal ratio is within optimal range', 'quality-cost-calculator');
        } elseif ($appraisal_ratio > $this->benchmarks['appraisal_maximum']) {
            $analysis['appraisal_status'] = 'high';
            $analysis['appraisal_message'] = __('Appraisal ratio is above recommended maximum', 'quality-cost-calculator');
        } else {
            $analysis['appraisal_status'] = 'low';
            $analysis['appraisal_message'] = __('Appraisal ratio is below optimal range', 'quality-cost-calculator');
        }
        
        // Overall COGQ assessment
        $analysis['overall_assessment'] = $this->get_overall_cogq_assessment($prevention_ratio, $appraisal_ratio);
        
        return $analysis;
    }
    
    /**
     * Get overall COGQ assessment
     * 
     * @param float $prevention_ratio Prevention ratio
     * @param float $appraisal_ratio Appraisal ratio
     * @return array Overall assessment
     */
    private function get_overall_cogq_assessment($prevention_ratio, $appraisal_ratio) {
        if ($prevention_ratio >= 40 && $prevention_ratio <= 60 && $appraisal_ratio >= 20 && $appraisal_ratio <= 40) {
            return array(
                'status' => 'excellent',
                'message' => __('Excellent COGQ balance between prevention and appraisal', 'quality-cost-calculator'),
                'priority' => 'maintain'
            );
        } elseif ($prevention_ratio >= 30 && $appraisal_ratio <= 50) {
            return array(
                'status' => 'good',
                'message' => __('Good COGQ structure with room for optimization', 'quality-cost-calculator'),
                'priority' => 'optimize'
            );
        } elseif ($prevention_ratio < 30) {
            return array(
                'status' => 'needs_attention',
                'message' => __('Low prevention investment may lead to higher defect costs', 'quality-cost-calculator'),
                'priority' => 'increase_prevention'
            );
        } else {
            return array(
                'status' => 'unbalanced',
                'message' => __('COGQ structure needs rebalancing', 'quality-cost-calculator'),
                'priority' => 'rebalance'
            );
        }
    }
    
    /**
     * Estimate COGQ ROI
     * 
     * @param float $prevention_cost Prevention cost
     * @param float $appraisal_cost Appraisal cost
     * @param array $basic_results Basic calculation results
     * @return array ROI estimates
     */
    private function estimate_cogq_roi($prevention_cost, $appraisal_cost, $basic_results) {
        $total_quality_cost = $basic_results['total_quality_cost'];
        
        // Industry average ROI multipliers for quality investments
        $prevention_roi_multiplier = 4.0; // $1 prevention saves $4 in defects
        $appraisal_roi_multiplier = 2.5;   // $1 appraisal saves $2.5 in external failures
        
        // Calculate potential savings
        $prevention_savings = $prevention_cost * $prevention_roi_multiplier;
        $appraisal_savings = $appraisal_cost * $appraisal_roi_multiplier;
        $total_savings = $prevention_savings + $appraisal_savings;
        
        // Calculate ROI percentages
        $prevention_roi = $prevention_cost > 0 ? (($prevention_savings - $prevention_cost) / $prevention_cost) * 100 : 0;
        $appraisal_roi = $appraisal_cost > 0 ? (($appraisal_savings - $appraisal_cost) / $appraisal_cost) * 100 : 0;
        $total_cogq_investment = $prevention_cost + $appraisal_cost;
        $total_roi = $total_cogq_investment > 0 ? (($total_savings - $total_cogq_investment) / $total_cogq_investment) * 100 : 0;
        
        return array(
            'prevention_savings' => $prevention_savings,
            'appraisal_savings' => $appraisal_savings,
            'total_savings' => $total_savings,
            'prevention_roi' => $prevention_roi,
            'appraisal_roi' => $appraisal_roi,
            'total_roi' => $total_roi,
            'net_benefit' => $total_savings - $total_cogq_investment,
            'payback_period_months' => $this->calculate_payback_period($total_cogq_investment, $total_savings)
        );
    }
    
    /**
     * Calculate payback period
     * 
     * @param float $investment Total investment
     * @param float $annual_savings Annual savings
     * @return float Payback period in months
     */
    private function calculate_payback_period($investment, $annual_savings) {
        if ($annual_savings <= $investment) {
            return 12; // Default to 12 months if savings are low
        }
        
        $monthly_savings = $annual_savings / 12;
        return $monthly_savings > 0 ? $investment / $monthly_savings : 12;
    }
    
    /**
     * Analyze COGQ data and provide insights
     * 
     * @param array $cogq_results COGQ calculation results
     * @return array Analysis and recommendations
     */
    public function analyze($cogq_results) {
        $analysis = array();
        
        // Prevention vs Appraisal analysis
        if ($cogq_results['prevention_ratio'] < 30) {
            $analysis[] = array(
                'type' => 'warning',
                'category' => 'prevention',
                'title' => __('Low Prevention Investment', 'quality-cost-calculator'),
                'message' => __('Prevention costs are low compared to appraisal. Consider investing more in prevention activities like training, process design, and quality planning.', 'quality-cost-calculator'),
                'impact' => 'high',
                'effort' => 'medium'
            );
        }
        
        if ($cogq_results['appraisal_ratio'] > 70) {
            $analysis[] = array(
                'type' => 'alert',
                'category' => 'appraisal',
                'title' => __('High Appraisal Costs', 'quality-cost-calculator'),
                'message' => __('Appraisal costs are very high, indicating reactive quality management. Focus on prevention to reduce inspection needs.', 'quality-cost-calculator'),
                'impact' => 'high',
                'effort' => 'high'
            );
        }
        
        // Efficiency analysis
        if (isset($cogq_results['efficiency_metrics']['efficiency_score']['score'])) {
            $efficiency_score = $cogq_results['efficiency_metrics']['efficiency_score']['score'];
            
            if ($efficiency_score >= 80) {
                $analysis[] = array(
                    'type' => 'success',
                    'category' => 'efficiency',
                    'title' => __('Efficient COGQ Structure', 'quality-cost-calculator'),
                    'message' => __('Your COGQ investments show good efficiency. Maintain current balance and focus on continuous improvement.', 'quality-cost-calculator'),
                    'impact' => 'medium',
                    'effort' => 'low'
                );
            } elseif ($efficiency_score < 60) {
                $analysis[] = array(
                    'type' => 'warning',
                    'category' => 'efficiency',
                    'title' => __('COGQ Efficiency Improvement Needed', 'quality-cost-calculator'),
                    'message' => __('Your COGQ structure could be more efficient. Review prevention/appraisal balance and investment levels.', 'quality-cost-calculator'),
                    'impact' => 'high',
                    'effort' => 'medium'
                );
            }
        }
        
        // ROI analysis
        if (isset($cogq_results['roi_estimates']['total_roi'])) {
            $total_roi = $cogq_results['roi_estimates']['total_roi'];
            
            if ($total_roi > 200) {
                $analysis[] = array(
                    'type' => 'success',
                    'category' => 'roi',
                    'title' => __('Excellent COGQ ROI', 'quality-cost-calculator'),
                    'message' => sprintf(__('Your COGQ investments show excellent ROI of %.1f%%. Consider increasing investment to maximize returns.', 'quality-cost-calculator'), $total_roi),
                    'impact' => 'high',
                    'effort' => 'low'
                );
            } elseif ($total_roi < 100) {
                $analysis[] = array(
                    'type' => 'alert',
                    'category' => 'roi',
                    'title' => __('Low COGQ ROI', 'quality-cost-calculator'),
                    'message' => sprintf(__('COGQ ROI of %.1f%% is below expectations. Review investment allocation and effectiveness.', 'quality-cost-calculator'), $total_roi),
                    'impact' => 'high',
                    'effort' => 'high'
                );
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get optimization recommendations
     * 
     * @param array $cogq_results COGQ calculation results
     * @return array Optimization recommendations
     */
    public function get_optimization_recommendations($cogq_results) {
        $recommendations = array();
        
        $prevention_ratio = $cogq_results['prevention_ratio'];
        $appraisal_ratio = $cogq_results['appraisal_ratio'];
        
        // Prevention optimization
        if ($prevention_ratio < 40) {
            $target_increase = 40 - $prevention_ratio;
            $recommendations[] = array(
                'category' => 'prevention',
                'action' => 'increase',
                'target_change' => $target_increase,
                'description' => sprintf(__('Increase prevention activities by %.1f percentage points', 'quality-cost-calculator'), $target_increase),
                'expected_benefit' => 'Reduced defect costs and improved quality'
            );
        }
        
        // Appraisal optimization
        if ($appraisal_ratio > 40) {
            $target_decrease = $appraisal_ratio - 40;
            $recommendations[] = array(
                'category' => 'appraisal',
                'action' => 'decrease',
                'target_change' => $target_decrease,
                'description' => sprintf(__('Reduce appraisal activities by %.1f percentage points', 'quality-cost-calculator'), $target_decrease),
                'expected_benefit' => 'Lower inspection costs while maintaining quality'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Format currency for display
     * 
     * @param float $amount Amount to format
     * @param array $input Input data with currency and unit information
     * @return string Formatted currency
     */
    private function format_currency($amount, $input) {
        $currency = $input['currency'] ?? 'EUR';
        $unit = $input['unit'] ?? '1000000000';
        
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
     * Get calculator status
     * 
     * @return array Calculator status
     */
    public function get_status() {
        return array(
            'name' => 'COGQ_Calculator',
            'version' => '2.0.0',
            'config_loaded' => $this->config !== null,
            'container_available' => $this->container !== null,
            'benchmarks_loaded' => !empty($this->benchmarks),
            'calculation_methods' => array('calculate', 'analyze', 'get_optimization_recommendations')
        );
    }
}
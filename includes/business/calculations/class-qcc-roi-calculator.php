<?php
/**
 * QCC ROI Calculator - ROI and Investment Analysis
 *
 * @package QualityCostCalculator
 * @subpackage Business\Calculations
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ROI Calculator for Quality Cost Calculator
 * 
 * Handles Return on Investment calculations for quality improvement initiatives.
 * Provides payback period, NPV, and break-even analysis.
 */
class QCC_ROI_Calculator {
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container
     */
    private $container;
    
    /**
     * Constructor
     * 
     * @param QCC_Service_Container $container Service container
     */
    public function __construct($container = null) {
        $this->container = $container;
    }
    
    /**
     * Calculate ROI metrics
     * 
     * @param array $input Normalized input data
     * @param array $basic_results Basic calculation results from engine
     * @return array ROI calculation results
     */
    public function calculate($input, $basic_results) {
        // Extract key values
        $total_quality_cost = $basic_results['total_quality_cost'];
        $current_copq = $this->extract_copq_from_input($input, $total_quality_cost);
        
        // Default improvement scenarios
        $improvement_scenarios = $this->get_default_improvement_scenarios($current_copq);
        
        // Calculate ROI for each scenario
        $roi_results = array();
        foreach ($improvement_scenarios as $scenario_name => $scenario) {
            $roi_results[$scenario_name] = $this->calculate_scenario_roi($scenario);
        }
        
        // Add custom scenario if investment data provided
        if (isset($input['custom_investment']) && isset($input['custom_savings'])) {
            $custom_scenario = array(
                'investment' => $input['custom_investment'],
                'annual_savings' => $input['custom_savings'],
                'timeframe_years' => $input['timeframe_years'] ?? 3
            );
            $roi_results['custom'] = $this->calculate_scenario_roi($custom_scenario);
        }
        
        // Generate ROI summary
        $roi_summary = $this->generate_roi_summary($roi_results, $current_copq);
        
        return array(
            'scenarios' => $roi_results,
            'summary' => $roi_summary,
            'current_copq' => $current_copq,
            'improvement_potential' => $this->calculate_improvement_potential($current_copq),
            'recommended_scenario' => $this->get_recommended_scenario($roi_results)
        );
    }
    
    /**
     * Calculate ROI for a specific scenario
     * 
     * @param array $scenario Scenario parameters
     * @return array ROI metrics
     */
    private function calculate_scenario_roi($scenario) {
        $investment = $scenario['investment'];
        $annual_savings = $scenario['annual_savings'];
        $timeframe_years = $scenario['timeframe_years'] ?? 3;
        $discount_rate = $scenario['discount_rate'] ?? 0.08;
        
        // Basic ROI calculations
        $simple_roi = $this->calculate_simple_roi($investment, $annual_savings);
        $payback_period = $this->calculate_payback_period($investment, $annual_savings);
        $net_present_value = $this->calculate_npv($investment, $annual_savings, $timeframe_years, $discount_rate);
        $total_savings = $annual_savings * $timeframe_years;
        $net_benefit = $total_savings - $investment;
        
        return array(
            'investment' => $investment,
            'annual_savings' => $annual_savings,
            'timeframe_years' => $timeframe_years,
            'simple_roi' => $simple_roi,
            'payback_period_months' => $payback_period,
            'payback_period_years' => $payback_period / 12,
            'net_present_value' => $net_present_value,
            'total_savings' => $total_savings,
            'net_benefit' => $net_benefit,
            'roi_grade' => $this->get_roi_grade($simple_roi, $payback_period),
            'is_profitable' => $simple_roi > 0 && $net_present_value > 0
        );
    }
    
    /**
     * Calculate simple ROI percentage
     * 
     * @param float $investment Initial investment
     * @param float $annual_savings Annual savings
     * @return float ROI percentage
     */
    private function calculate_simple_roi($investment, $annual_savings) {
        if ($investment <= 0) return 0;
        return (($annual_savings - $investment) / $investment) * 100;
    }
    
    /**
     * Calculate payback period in months
     * 
     * @param float $investment Initial investment
     * @param float $annual_savings Annual savings
     * @return float|null Payback period in months
     */
    private function calculate_payback_period($investment, $annual_savings) {
        if ($annual_savings <= 0) return null;
        $monthly_savings = $annual_savings / 12;
        return $investment / $monthly_savings;
    }
    
    /**
     * Calculate Net Present Value
     * 
     * @param float $investment Initial investment
     * @param float $annual_savings Annual savings
     * @param int $years Investment timeframe
     * @param float $discount_rate Discount rate
     * @return float Net Present Value
     */
    private function calculate_npv($investment, $annual_savings, $years, $discount_rate) {
        $npv = -$investment; // Initial investment (negative cash flow)
        
        for ($year = 1; $year <= $years; $year++) {
            $present_value = $annual_savings / pow(1 + $discount_rate, $year);
            $npv += $present_value;
        }
        
        return $npv;
    }
    
    /**
     * Extract COPQ value from input data
     * 
     * @param array $input Input data
     * @param float $total_quality_cost Total quality cost
     * @return float Current COPQ
     */
    private function extract_copq_from_input($input, $total_quality_cost) {
        $internal_percentage = $input['internal_defect'] ?? 30;
        $external_percentage = $input['external_defect'] ?? 40;
        $copq_percentage = $internal_percentage + $external_percentage;
        
        return $total_quality_cost * ($copq_percentage / 100);
    }
    
    /**
     * Get default improvement scenarios
     * 
     * @param float $current_copq Current COPQ value
     * @return array Improvement scenarios
     */
    private function get_default_improvement_scenarios($current_copq) {
        return array(
            'conservative' => array(
                'investment' => $current_copq * 0.15, // 15% of current COPQ
                'annual_savings' => $current_copq * 0.20, // 20% COPQ reduction
                'timeframe_years' => 3,
                'reduction_percentage' => 20,
                'description' => __('Conservative improvement with low risk', 'quality-cost-calculator')
            ),
            'moderate' => array(
                'investment' => $current_copq * 0.25, // 25% of current COPQ
                'annual_savings' => $current_copq * 0.35, // 35% COPQ reduction
                'timeframe_years' => 3,
                'reduction_percentage' => 35,
                'description' => __('Balanced approach with moderate investment', 'quality-cost-calculator')
            ),
            'aggressive' => array(
                'investment' => $current_copq * 0.40, // 40% of current COPQ
                'annual_savings' => $current_copq * 0.50, // 50% COPQ reduction
                'timeframe_years' => 3,
                'reduction_percentage' => 50,
                'description' => __('Ambitious improvement with higher investment', 'quality-cost-calculator')
            )
        );
    }
    
    /**
     * Generate ROI summary
     * 
     * @param array $roi_results All ROI scenario results
     * @param float $current_copq Current COPQ value
     * @return array ROI summary
     */
    private function generate_roi_summary($roi_results, $current_copq) {
        $profitable_scenarios = array_filter($roi_results, function($scenario) {
            return $scenario['is_profitable'];
        });
        
        $best_roi = null;
        $fastest_payback = null;
        
        foreach ($roi_results as $name => $scenario) {
            if ($scenario['is_profitable']) {
                if ($best_roi === null || $scenario['simple_roi'] > $best_roi['simple_roi']) {
                    $best_roi = array_merge($scenario, array('scenario_name' => $name));
                }
                
                if ($fastest_payback === null || $scenario['payback_period_months'] < $fastest_payback['payback_period_months']) {
                    $fastest_payback = array_merge($scenario, array('scenario_name' => $name));
                }
            }
        }
        
        return array(
            'total_scenarios' => count($roi_results),
            'profitable_scenarios' => count($profitable_scenarios),
            'profitability_rate' => (count($profitable_scenarios) / count($roi_results)) * 100,
            'best_roi_scenario' => $best_roi,
            'fastest_payback_scenario' => $fastest_payback,
            'average_payback_months' => $this->calculate_average_payback($roi_results),
            'investment_recommendation' => $this->get_investment_recommendation($profitable_scenarios, $current_copq)
        );
    }
    
    /**
     * Calculate improvement potential
     * 
     * @param float $current_copq Current COPQ value
     * @return array Improvement potential metrics
     */
    private function calculate_improvement_potential($current_copq) {
        // Industry best practices suggest COPQ can be reduced by 30-70%
        $conservative_potential = $current_copq * 0.30;
        $aggressive_potential = $current_copq * 0.70;
        
        return array(
            'current_copq' => $current_copq,
            'conservative_savings' => $conservative_potential,
            'aggressive_savings' => $aggressive_potential,
            'potential_range' => array(
                'min' => $conservative_potential,
                'max' => $aggressive_potential
            )
        );
    }
    
    /**
     * Get recommended scenario
     * 
     * @param array $roi_results All ROI scenarios
     * @return array|null Recommended scenario
     */
    private function get_recommended_scenario($roi_results) {
        $profitable_scenarios = array_filter($roi_results, function($scenario) {
            return $scenario['is_profitable'] && $scenario['payback_period_months'] <= 24; // Max 2 years payback
        });
        
        if (empty($profitable_scenarios)) {
            return null;
        }
        
        // Find scenario with best balance of ROI and payback period
        $best_scenario = null;
        $best_score = 0;
        
        foreach ($profitable_scenarios as $name => $scenario) {
            // Score based on ROI (60%) and inverse payback period (40%)
            $roi_score = min(100, max(0, $scenario['simple_roi'])) / 100;
            $payback_score = $scenario['payback_period_months'] > 0 ? (24 / $scenario['payback_period_months']) : 0;
            $payback_score = min(1, $payback_score);
            
            $combined_score = ($roi_score * 0.6) + ($payback_score * 0.4);
            
            if ($combined_score > $best_score) {
                $best_score = $combined_score;
                $best_scenario = array_merge($scenario, array(
                    'scenario_name' => $name,
                    'recommendation_score' => $combined_score
                ));
            }
        }
        
        return $best_scenario;
    }
    
    /**
     * Get ROI grade
     * 
     * @param float $roi ROI percentage
     * @param float $payback_months Payback period in months
     * @return string ROI grade
     */
    private function get_roi_grade($roi, $payback_months) {
        if ($roi < 0) return 'F';
        
        if ($roi >= 100 && $payback_months <= 12) return 'A+';
        if ($roi >= 50 && $payback_months <= 18) return 'A';
        if ($roi >= 25 && $payback_months <= 24) return 'B';
        if ($roi >= 10 && $payback_months <= 36) return 'C';
        if ($roi > 0 && $payback_months <= 48) return 'D';
        
        return 'F';
    }
    
    /**
     * Calculate average payback period
     * 
     * @param array $roi_results ROI results
     * @return float Average payback in months
     */
    private function calculate_average_payback($roi_results) {
        $payback_periods = array_filter(array_column($roi_results, 'payback_period_months'));
        
        if (empty($payback_periods)) {
            return 0;
        }
        
        return array_sum($payback_periods) / count($payback_periods);
    }
    
    /**
     * Get investment recommendation
     * 
     * @param array $profitable_scenarios Profitable scenarios
     * @param float $current_copq Current COPQ
     * @return string Investment recommendation
     */
    private function get_investment_recommendation($profitable_scenarios, $current_copq) {
        if (empty($profitable_scenarios)) {
            return __('Current scenarios do not show profitable ROI. Consider reducing investment costs or increasing improvement targets.', 'quality-cost-calculator');
        }
        
        $scenario_count = count($profitable_scenarios);
        
        if ($scenario_count >= 3) {
            return __('Excellent investment opportunity. All scenarios show positive ROI. Recommend starting with moderate approach.', 'quality-cost-calculator');
        } elseif ($scenario_count >= 2) {
            return __('Good investment potential. Multiple scenarios are profitable. Recommend conservative start with scaling option.', 'quality-cost-calculator');
        } else {
            return __('Limited but viable investment opportunity. Proceed carefully with the profitable scenario.', 'quality-cost-calculator');
        }
    }
}
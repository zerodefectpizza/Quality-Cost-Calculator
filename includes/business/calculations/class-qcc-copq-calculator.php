<?php
/**
 * QCC COPQ Calculator - Cost of Poor Quality
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * COPQ Calculator for Quality Cost Calculator
 * 
 * Calculates Cost of Poor Quality (Internal + External Defects)
 * Provides impact analysis and reduction strategies
 */
class QCC_COPQ_Calculator {
    
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
     * Industry impact multipliers
     * 
     * @var array
     */
    private $impact_multipliers = array(
        'customer_retention' => 0.15,      // 15% of external defects affect retention
        'market_share' => 0.08,            // 8% impact on market share
        'brand_reputation' => 0.12,        // 12% brand damage
        'employee_morale' => 0.05,         // 5% impact on employee satisfaction
        'productivity_loss' => 0.25,       // 25% productivity impact from rework
        'supplier_relationships' => 0.06   // 6% impact on supplier costs
    );
    
    /**
     * COPQ benchmarks by industry
     * 
     * @var array
     */
    private $industry_benchmarks = array(
        'manufacturing' => array('min' => 2.5, 'avg' => 4.0, 'max' => 8.0),
        'services' => array('min' => 1.5, 'avg' => 3.0, 'max' => 6.0),
        'healthcare' => array('min' => 3.0, 'avg' => 5.5, 'max' => 12.0),
        'software' => array('min' => 2.0, 'avg' => 4.5, 'max' => 10.0),
        'default' => array('min' => 2.0, 'avg' => 4.0, 'max' => 8.0)
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
     * Calculate COPQ metrics
     * 
     * @param array $input Input data
     * @param array $basic_results Basic calculation results
     * @return array COPQ calculation results
     */
    public function calculate($input, $basic_results) {
        $total_quality_cost = $basic_results['total_quality_cost'];
        $revenue = $basic_results['revenue'];
        
        // Get percentages from input or configuration defaults
        $internal_defect_percentage = $input['internal_defect'] ?? $this->config->get('default_internal_defect', 30);
        $external_defect_percentage = $input['external_defect'] ?? $this->config->get('default_external_defect', 40);
        
        // Calculate absolute costs
        $internal_defect_cost = $total_quality_cost * ($internal_defect_percentage / 100);
        $external_defect_cost = $total_quality_cost * ($external_defect_percentage / 100);
        $total_copq = $internal_defect_cost + $external_defect_cost;
        
        // Calculate ratios and percentages
        $copq_percentage = ($total_copq / $revenue) * 100;
        $internal_ratio = $total_copq > 0 ? ($internal_defect_cost / $total_copq) * 100 : 0;
        $external_ratio = $total_copq > 0 ? ($external_defect_cost / $total_copq) * 100 : 0;
        
        // Calculate detailed impact metrics
        $impact_metrics = $this->calculate_comprehensive_impact_metrics($internal_defect_cost, $external_defect_cost, $revenue);
        
        // Industry benchmarking
        $benchmark_analysis = $this->analyze_against_industry_benchmarks($copq_percentage, $input);
        
        // Risk assessment
        $risk_assessment = $this->assess_copq_risks($internal_ratio, $external_ratio, $copq_percentage);
        
        // Cost breakdown analysis
        $cost_breakdown = $this->analyze_cost_breakdown($internal_defect_cost, $external_defect_cost);
        
        // Hidden costs estimation
        $hidden_costs = $this->estimate_hidden_costs($external_defect_cost, $revenue);
        
        return array(
            // Core COPQ metrics
            'internal_defect_cost' => $internal_defect_cost,
            'external_defect_cost' => $external_defect_cost,
            'total_copq' => $total_copq,
            
            // Percentages and ratios
            'copq_percentage' => $copq_percentage,
            'internal_ratio' => $internal_ratio,
            'external_ratio' => $external_ratio,
            'internal_defect_percentage_input' => $internal_defect_percentage,
            'external_defect_percentage_input' => $external_defect_percentage,
            
            // Advanced analytics
            'impact_metrics' => $impact_metrics,
            'benchmark_analysis' => $benchmark_analysis,
            'risk_assessment' => $risk_assessment,
            'cost_breakdown' => $cost_breakdown,
            'hidden_costs' => $hidden_costs,
            
            // Formatted display values
            'internal_defect_cost_display' => $this->format_currency($internal_defect_cost, $input),
            'external_defect_cost_display' => $this->format_currency($external_defect_cost, $input),
            'total_copq_display' => $this->format_currency($total_copq, $input),
            
            // Calculation metadata
            'calculation_method' => 'comprehensive_copq',
            'calculation_timestamp' => current_time('timestamp')
        );
    }
    
    /**
     * Calculate comprehensive impact metrics
     * 
     * @param float $internal_defect_cost Internal defect cost
     * @param float $external_defect_cost External defect cost
     * @param float $revenue Total revenue
     * @return array Comprehensive impact metrics
     */
    private function calculate_comprehensive_impact_metrics($internal_defect_cost, $external_defect_cost, $revenue) {
        // Direct business impacts
        $customer_retention_impact = $external_defect_cost * $this->impact_multipliers['customer_retention'];
        $market_share_impact = $external_defect_cost * $this->impact_multipliers['market_share'];
        $brand_reputation_impact = $external_defect_cost * $this->impact_multipliers['brand_reputation'];
        
        // Internal organizational impacts
        $employee_morale_impact = ($internal_defect_cost + $external_defect_cost) * $this->impact_multipliers['employee_morale'];
        $productivity_loss = $internal_defect_cost * $this->impact_multipliers['productivity_loss'];
        $supplier_relationship_impact = ($internal_defect_cost + $external_defect_cost) * $this->impact_multipliers['supplier_relationships'];
        
        // Calculate opportunity costs
        $lost_sales_potential = $external_defect_cost * 1.5; // Conservative estimate: 1.5x external defects in lost sales
        $innovation_opportunity_cost = ($internal_defect_cost + $external_defect_cost) * 0.3; // 30% could go to innovation
        
        // Calculate total business impact
        $total_direct_impact = $customer_retention_impact + $market_share_impact + $brand_reputation_impact;
        $total_indirect_impact = $employee_morale_impact + $productivity_loss + $supplier_relationship_impact;
        $total_opportunity_cost = $lost_sales_potential + $innovation_opportunity_cost;
        $total_business_impact = $total_direct_impact + $total_indirect_impact + $total_opportunity_cost;
        
        // Calculate impact intensity (percentage of revenue)
        $impact_intensity = $revenue > 0 ? ($total_business_impact / $revenue) * 100 : 0;
        
        return array(
            // Direct business impacts
            'customer_retention_impact' => $customer_retention_impact,
            'market_share_impact' => $market_share_impact,
            'brand_reputation_impact' => $brand_reputation_impact,
            'total_direct_impact' => $total_direct_impact,
            
            // Internal impacts
            'employee_morale_impact' => $employee_morale_impact,
            'productivity_loss' => $productivity_loss,
            'supplier_relationship_impact' => $supplier_relationship_impact,
            'total_indirect_impact' => $total_indirect_impact,
            
            // Opportunity costs
            'lost_sales_potential' => $lost_sales_potential,
            'innovation_opportunity_cost' => $innovation_opportunity_cost,
            'total_opportunity_cost' => $total_opportunity_cost,
            
            // Summary metrics
            'total_business_impact' => $total_business_impact,
            'impact_intensity' => $impact_intensity,
            'impact_multiplier' => $revenue > 0 ? $total_business_impact / ($internal_defect_cost + $external_defect_cost) : 1,
            
            // Impact distribution
            'impact_distribution' => array(
                'direct_percentage' => $total_business_impact > 0 ? ($total_direct_impact / $total_business_impact) * 100 : 0,
                'indirect_percentage' => $total_business_impact > 0 ? ($total_indirect_impact / $total_business_impact) * 100 : 0,
                'opportunity_percentage' => $total_business_impact > 0 ? ($total_opportunity_cost / $total_business_impact) * 100 : 0
            )
        );
    }
    
    /**
     * Analyze against industry benchmarks
     * 
     * @param float $copq_percentage COPQ percentage
     * @param array $input Input data
     * @return array Benchmark analysis
     */
    private function analyze_against_industry_benchmarks($copq_percentage, $input) {
        // Determine industry (could be expanded with input parameter)
        $industry = $input['industry'] ?? 'default';
        $benchmark = $this->industry_benchmarks[$industry] ?? $this->industry_benchmarks['default'];
        
        // Analyze position against benchmarks
        if ($copq_percentage <= $benchmark['min']) {
            $position = 'excellent';
            $message = __('COPQ is excellent - below industry minimum', 'quality-cost-calculator');
        } elseif ($copq_percentage <= $benchmark['avg']) {
            $position = 'good';
            $message = __('COPQ is good - below industry average', 'quality-cost-calculator');
        } elseif ($copq_percentage <= $benchmark['max']) {
            $position = 'average';
            $message = __('COPQ is average - within industry range', 'quality-cost-calculator');
        } else {
            $position = 'poor';
            $message = __('COPQ is poor - above industry maximum', 'quality-cost-calculator');
        }
        
        // Calculate improvement potential
        $improvement_potential = max(0, $copq_percentage - $benchmark['min']);
        $potential_savings = ($improvement_potential / 100) * ($input['revenue'] ?? 0);
        
        return array(
            'industry' => $industry,
            'benchmark' => $benchmark,
            'current_position' => $position,
            'message' => $message,
            'percentile_estimate' => $this->estimate_percentile($copq_percentage, $benchmark),
            'improvement_potential_percentage' => $improvement_potential,
            'potential_annual_savings' => $potential_savings,
            'target_copq' => $benchmark['min']
        );
    }
    
    /**
     * Estimate percentile position
     * 
     * @param float $copq_percentage Current COPQ percentage
     * @param array $benchmark Industry benchmark
     * @return float Estimated percentile
     */
    private function estimate_percentile($copq_percentage, $benchmark) {
        if ($copq_percentage <= $benchmark['min']) {
            return 90 + (($benchmark['min'] - $copq_percentage) / $benchmark['min']) * 10;
        } elseif ($copq_percentage <= $benchmark['avg']) {
            $range = $benchmark['avg'] - $benchmark['min'];
            $position = ($benchmark['avg'] - $copq_percentage) / $range;
            return 50 + ($position * 40);
        } elseif ($copq_percentage <= $benchmark['max']) {
            $range = $benchmark['max'] - $benchmark['avg'];
            $position = ($benchmark['max'] - $copq_percentage) / $range;
            return 10 + ($position * 40);
        } else {
            return max(1, 10 - (($copq_percentage - $benchmark['max']) / $benchmark['max']) * 9);
        }
    }
    
    /**
     * Assess COPQ risks
     * 
     * @param float $internal_ratio Internal defect ratio
     * @param float $external_ratio External defect ratio
     * @param float $copq_percentage COPQ percentage
     * @return array Risk assessment
     */
    private function assess_copq_risks($internal_ratio, $external_ratio, $copq_percentage) {
        $risks = array();
        $risk_score = 0;
        
        // External defect risk (highest priority)
        if ($external_ratio > 60) {
            $risks[] = array(
                'type' => 'critical',
                'category' => 'customer_impact',
                'title' => __('High Customer Impact Risk', 'quality-cost-calculator'),
                'description' => __('High external defect costs indicate quality issues reaching customers', 'quality-cost-calculator'),
                'probability' => 'high',
                'impact' => 'critical'
            );
            $risk_score += 40;
        } elseif ($external_ratio > 40) {
            $risks[] = array(
                'type' => 'high',
                'category' => 'customer_impact',
                'title' => __('Moderate Customer Impact Risk', 'quality-cost-calculator'),
                'description' => __('External defect costs may affect customer satisfaction', 'quality-cost-calculator'),
                'probability' => 'medium',
                'impact' => 'high'
            );
            $risk_score += 25;
        }
        
        // Internal process risk
        if ($internal_ratio > 70) {
            $risks[] = array(
                'type' => 'high',
                'category' => 'process_efficiency',
                'title' => __('Process Inefficiency Risk', 'quality-cost-calculator'),
                'description' => __('High internal defect costs suggest significant process problems', 'quality-cost-calculator'),
                'probability' => 'high',
                'impact' => 'medium'
            );
            $risk_score += 30;
        }
        
        // Overall COPQ risk
        if ($copq_percentage > 6) {
            $risks[] = array(
                'type' => 'medium',
                'category' => 'financial',
                'title' => __('Financial Impact Risk', 'quality-cost-calculator'),
                'description' => __('COPQ percentage is above sustainable levels', 'quality-cost-calculator'),
                'probability' => 'medium',
                'impact' => 'medium'
            );
            $risk_score += 20;
        }
        
        // Calculate overall risk level
        $overall_risk_level = $this->calculate_risk_level($risk_score);
        
        return array(
            'risks' => $risks,
            'risk_score' => $risk_score,
            'overall_risk_level' => $overall_risk_level,
            'risk_mitigation_priority' => $this->get_risk_mitigation_priority($risks)
        );
    }
    
    /**
     * Calculate overall risk level
     * 
     * @param float $risk_score Risk score
     * @return array Risk level information
     */
    private function calculate_risk_level($risk_score) {
        if ($risk_score >= 80) {
            return array('level' => 'critical', 'color' => 'red', 'action' => 'immediate');
        } elseif ($risk_score >= 60) {
            return array('level' => 'high', 'color' => 'orange', 'action' => 'urgent');
        } elseif ($risk_score >= 40) {
            return array('level' => 'medium', 'color' => 'yellow', 'action' => 'planned');
        } elseif ($risk_score >= 20) {
            return array('level' => 'low', 'color' => 'green', 'action' => 'monitor');
        } else {
            return array('level' => 'minimal', 'color' => 'blue', 'action' => 'maintain');
        }
    }
    
    /**
     * Get risk mitigation priority
     * 
     * @param array $risks Risk array
     * @return array Prioritized mitigation strategies
     */
    private function get_risk_mitigation_priority($risks) {
        $priorities = array();
        
        foreach ($risks as $risk) {
            if ($risk['category'] === 'customer_impact') {
                $priorities[] = array(
                    'priority' => 1,
                    'action' => 'focus_on_external_quality',
                    'description' => __('Prioritize reduction of external defects', 'quality-cost-calculator')
                );
            } elseif ($risk['category'] === 'process_efficiency') {
                $priorities[] = array(
                    'priority' => 2,
                    'action' => 'improve_internal_processes',
                    'description' => __('Implement process improvement initiatives', 'quality-cost-calculator')
                );
            }
        }
        
        return $priorities;
    }
    
    /**
     * Analyze cost breakdown
     * 
     * @param float $internal_defect_cost Internal defect cost
     * @param float $external_defect_cost External defect cost
     * @return array Cost breakdown analysis
     */
    private function analyze_cost_breakdown($internal_defect_cost, $external_defect_cost) {
        $total_copq = $internal_defect_cost + $external_defect_cost;
        
        // Industry typical breakdown for comparison
        $typical_internal_ratio = 45; // Typical 45% internal, 55% external
        $typical_external_ratio = 55;
        
        $current_internal_ratio = $total_copq > 0 ? ($internal_defect_cost / $total_copq) * 100 : 0;
        $current_external_ratio = $total_copq > 0 ? ($external_defect_cost / $total_copq) * 100 : 0;
        
        // Analysis
        $internal_variance = $current_internal_ratio - $typical_internal_ratio;
        $external_variance = $current_external_ratio - $typical_external_ratio;
        
        return array(
            'current_breakdown' => array(
                'internal_ratio' => $current_internal_ratio,
                'external_ratio' => $current_external_ratio
            ),
            'typical_breakdown' => array(
                'internal_ratio' => $typical_internal_ratio,
                'external_ratio' => $typical_external_ratio
            ),
            'variance_analysis' => array(
                'internal_variance' => $internal_variance,
                'external_variance' => $external_variance,
                'breakdown_assessment' => $this->assess_breakdown($internal_variance, $external_variance)
            )
        );
    }
    
    /**
     * Assess breakdown pattern
     * 
     * @param float $internal_variance Internal variance from typical
     * @param float $external_variance External variance from typical
     * @return array Breakdown assessment
     */
    private function assess_breakdown($internal_variance, $external_variance) {
        if (abs($internal_variance) <= 10 && abs($external_variance) <= 10) {
            return array(
                'status' => 'typical',
                'message' => __('COPQ breakdown is typical for industry', 'quality-cost-calculator')
            );
        } elseif ($internal_variance > 10) {
            return array(
                'status' => 'internal_heavy',
                'message' => __('Higher than typical internal defects - focus on process improvement', 'quality-cost-calculator')
            );
        } elseif ($external_variance > 10) {
            return array(
                'status' => 'external_heavy',
                'message' => __('Higher than typical external defects - critical customer impact risk', 'quality-cost-calculator')
            );
        } else {
            return array(
                'status' => 'unusual',
                'message' => __('Unusual COPQ breakdown pattern - requires investigation', 'quality-cost-calculator')
            );
        }
    }
    
    /**
     * Estimate hidden costs
     * 
     * @param float $external_defect_cost External defect cost
     * @param float $revenue Total revenue
     * @return array Hidden costs estimation
     */
    private function estimate_hidden_costs($external_defect_cost, $revenue) {
        // Hidden costs are typically 3-5x the visible external costs
        $hidden_cost_multiplier = 4.0;
        $estimated_hidden_costs = $external_defect_cost * $hidden_cost_multiplier;
        
        // Break down hidden costs
        $customer_acquisition_replacement = $external_defect_cost * 1.5;
        $warranty_extended_costs = $external_defect_cost * 0.8;
        $regulatory_compliance_costs = $external_defect_cost * 0.4;
        $management_time_costs = $external_defect_cost * 0.6;
        $reputation_recovery_costs = $external_defect_cost * 0.7;
        
        return array(
            'total_estimated_hidden_costs' => $estimated_hidden_costs,
            'hidden_cost_multiplier' => $hidden_cost_multiplier,
            'hidden_cost_breakdown' => array(
                'customer_acquisition_replacement' => $customer_acquisition_replacement,
                'warranty_extended_costs' => $warranty_extended_costs,
                'regulatory_compliance_costs' => $regulatory_compliance_costs,
                'management_time_costs' => $management_time_costs,
                'reputation_recovery_costs' => $reputation_recovery_costs
            ),
            'total_true_copq' => $external_defect_cost + $estimated_hidden_costs,
            'hidden_cost_percentage' => $revenue > 0 ? ($estimated_hidden_costs / $revenue) * 100 : 0
        );
    }
    
    /**
     * Analyze COPQ data and provide insights
     * 
     * @param array $copq_results COPQ calculation results
     * @return array Analysis and recommendations
     */
    public function analyze($copq_results) {
        $analysis = array();
        
        // External defect analysis
        if ($copq_results['external_ratio'] > 60) {
            $analysis[] = array(
                'type' => 'critical',
                'category' => 'external_defects',
                'title' => __('Critical: High External Defect Costs', 'quality-cost-calculator'),
                'message' => __('External defects are critically high, indicating quality issues reaching customers. This poses significant risk to customer satisfaction and brand reputation.', 'quality-cost-calculator'),
                'impact' => 'critical',
                'effort' => 'high',
                'timeframe' => 'immediate'
            );
        } elseif ($copq_results['external_ratio'] > 40) {
            $analysis[] = array(
                'type' => 'warning',
                'category' => 'external_defects',
                'title' => __('Warning: Elevated External Defect Costs', 'quality-cost-calculator'),
                'message' => __('External defect costs are above optimal levels. Focus on preventing defects from reaching customers.', 'quality-cost-calculator'),
                'impact' => 'high',
                'effort' => 'medium',
                'timeframe' => 'short_term'
            );
        }
        
        // Internal defect analysis
        if ($copq_results['internal_ratio'] > 70) {
            $analysis[] = array(
                'type' => 'warning',
                'category' => 'internal_processes',
                'title' => __('High Internal Defect Costs', 'quality-cost-calculator'),
                'message' => __('Internal defect costs suggest significant process improvement opportunities. Focus on root cause analysis and process optimization.', 'quality-cost-calculator'),
                'impact' => 'medium',
                'effort' => 'medium',
                'timeframe' => 'medium_term'
            );
        }
        
        // Overall COPQ analysis
        if ($copq_results['copq_percentage'] > 6) {
            $analysis[] = array(
                'type' => 'alert',
                'category' => 'financial_impact',
                'title' => __('COPQ Above Sustainable Levels', 'quality-cost-calculator'),
                'message' => __('COPQ percentage is above industry benchmarks. Implement comprehensive quality improvement program.', 'quality-cost-calculator'),
                'impact' => 'high',
                'effort' => 'high',
                'timeframe' => 'long_term'
            );
        }
        
        // Benchmark analysis
        if (isset($copq_results['benchmark_analysis']['current_position'])) {
            $position = $copq_results['benchmark_analysis']['current_position'];
            
            if ($position === 'excellent') {
                $analysis[] = array(
                    'type' => 'success',
                    'category' => 'benchmarking',
                    'title' => __('Excellent COPQ Performance', 'quality-cost-calculator'),
                    'message' => __('Your COPQ is excellent compared to industry benchmarks. Focus on maintaining current performance.', 'quality-cost-calculator'),
                    'impact' => 'positive',
                    'effort' => 'low',
                    'timeframe' => 'ongoing'
                );
            } elseif ($position === 'poor') {
                $analysis[] = array(
                    'type' => 'critical',
                    'category' => 'benchmarking',
                    'title' => __('COPQ Below Industry Standards', 'quality-cost-calculator'),
                    'message' => __('COPQ performance is below industry standards. Immediate action required to improve quality systems.', 'quality-cost-calculator'),
                    'impact' => 'critical',
                    'effort' => 'high',
                    'timeframe' => 'immediate'
                );
            }
        }
        
        // Hidden costs analysis
        if (isset($copq_results['hidden_costs']['total_estimated_hidden_costs'])) {
            $hidden_percentage = $copq_results['hidden_costs']['hidden_cost_percentage'];
            
            if ($hidden_percentage > 2) {
                $analysis[] = array(
                    'type' => 'info',
                    'category' => 'hidden_costs',
                    'title' => __('Significant Hidden Quality Costs', 'quality-cost-calculator'),
                    'message' => sprintf(__('Estimated hidden costs represent %.1f%% of revenue. Consider comprehensive cost impact when prioritizing quality improvements.', 'quality-cost-calculator'), $hidden_percentage),
                    'impact' => 'medium',
                    'effort' => 'low',
                    'timeframe' => 'planning'
                );
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get reduction strategies
     * 
     * @param array $copq_results COPQ calculation results
     * @return array Reduction strategies
     */
    public function get_reduction_strategies($copq_results) {
        $strategies = array();
        
        $internal_ratio = $copq_results['internal_ratio'];
        $external_ratio = $copq_results['external_ratio'];
        
        // Internal defect reduction strategies
        if ($internal_ratio > 50) {
            $strategies[] = array(
                'category' => 'internal_improvement',
                'priority' => 'high',
                'strategy' => 'process_optimization',
                'title' => __('Process Optimization Program', 'quality-cost-calculator'),
                'description' => __('Implement systematic process improvement to reduce internal defects', 'quality-cost-calculator'),
                'expected_reduction' => 20,
                'implementation_time' => '3-6 months',
                'investment_level' => 'medium'
            );
            
            $strategies[] = array(
                'category' => 'internal_improvement',
                'priority' => 'medium',
                'strategy' => 'employee_training',
                'title' => __('Enhanced Training Program', 'quality-cost-calculator'),
                'description' => __('Comprehensive quality training to reduce human errors', 'quality-cost-calculator'),
                'expected_reduction' => 15,
                'implementation_time' => '2-4 months',
                'investment_level' => 'low'
            );
        }
        
        // External defect reduction strategies
        if ($external_ratio > 40) {
            $strategies[] = array(
                'category' => 'external_prevention',
                'priority' => 'critical',
                'strategy' => 'enhanced_testing',
                'title' => __('Enhanced Testing and QA', 'quality-cost-calculator'),
                'description' => __('Strengthen testing processes to catch defects before customer delivery', 'quality-cost-calculator'),
                'expected_reduction' => 30,
                'implementation_time' => '1-3 months',
                'investment_level' => 'medium'
            );
            
            $strategies[] = array(
                'category' => 'external_prevention',
                'priority' => 'high',
                'strategy' => 'supplier_quality',
                'title' => __('Supplier Quality Management', 'quality-cost-calculator'),
                'description' => __('Improve supplier quality to reduce incoming defects', 'quality-cost-calculator'),
                'expected_reduction' => 25,
                'implementation_time' => '3-9 months',
                'investment_level' => 'high'
            );
        }
        
        return $strategies;
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
            'USD' => ',
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
            'name' => 'COPQ_Calculator',
            'version' => '2.0.0',
            'config_loaded' => $this->config !== null,
            'container_available' => $this->container !== null,
            'impact_multipliers_loaded' => !empty($this->impact_multipliers),
            'benchmarks_loaded' => !empty($this->industry_benchmarks),
            'calculation_methods' => array('calculate', 'analyze', 'get_reduction_strategies')
        );
    }
}
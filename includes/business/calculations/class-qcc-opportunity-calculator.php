<?php
/**
 * QCC Opportunity Calculator - Improvement Potential
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Opportunity Calculator for Quality Cost Calculator
 * 
 * Calculates improvement potential and optimization opportunities
 * Provides strategic recommendations for quality cost optimization
 */
class QCC_Opportunity_Calculator {
    
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
     * Optimization targets based on industry best practices
     * 
     * @var array
     */
    private $optimization_targets = array(
        'total_quality_cost' => array(
            'excellent' => 3.0,    // 3% of revenue
            'good' => 4.5,         // 4.5% of revenue
            'acceptable' => 6.0    // 6% of revenue
        ),
        'cogq_copq_ratio' => array(
            'optimal' => 0.6,      // 60% COGQ, 40% COPQ
            'good' => 0.5,         // 50% COGQ, 50% COPQ
            'minimum' => 0.3       // 30% COGQ, 70% COPQ
        ),
        'prevention_ratio' => array(
            'optimal' => 50,       // 50% of COGQ
            'good' => 40,          // 40% of COGQ
            'minimum' => 30        // 30% of COGQ
        )
    );
    
    /**
     * Implementation difficulty factors
     * 
     * @var array
     */
    private $implementation_factors = array(
        'process_improvement' => array('difficulty' => 0.6, 'time_months' => 6),
        'technology_upgrade' => array('difficulty' => 0.8, 'time_months' => 12),
        'training_programs' => array('difficulty' => 0.4, 'time_months' => 3),
        'organizational_change' => array('difficulty' => 0.9, 'time_months' => 18),
        'supplier_development' => array('difficulty' => 0.7, 'time_months' => 9)
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
     * Calculate improvement opportunities
     * 
     * @param array $input Input data
     * @param array $basic_results Basic calculation results
     * @return array Opportunity calculation results
     */
    public function calculate($input, $basic_results) {
        $revenue = $basic_results['revenue'];
        $current_quality_percentage = $basic_results['quality_percentage'];
        
        // Calculate current state metrics
        $current_state = $this->analyze_current_state($input, $basic_results);
        
        // Calculate optimization potential
        $optimization_scenarios = $this->calculate_optimization_scenarios($current_state, $revenue);
        
        // Calculate implementation roadmap
        $implementation_roadmap = $this->create_implementation_roadmap($optimization_scenarios, $current_state);
        
        // Calculate financial impact
        $financial_impact = $this->calculate_financial_impact($optimization_scenarios, $revenue);
        
        // Calculate risk-adjusted opportunities
        $risk_adjusted_opportunities = $this->calculate_risk_adjusted_opportunities($optimization_scenarios);
        
        // Generate strategic recommendations
        $strategic_recommendations = $this->generate_strategic_recommendations($current_state, $optimization_scenarios);
        
        return array(
            // Current state analysis
            'current_state' => $current_state,
            
            // Optimization scenarios
            'optimization_scenarios' => $optimization_scenarios,
            
            // Implementation planning
            'implementation_roadmap' => $implementation_roadmap,
            
            // Financial analysis
            'financial_impact' => $financial_impact,
            'risk_adjusted_opportunities' => $risk_adjusted_opportunities,
            
            // Strategic guidance
            'strategic_recommendations' => $strategic_recommendations,
            
            // Quick wins identification
            'quick_wins' => $this->identify_quick_wins($current_state, $optimization_scenarios),
            
            // Long-term opportunities
            'long_term_opportunities' => $this->identify_long_term_opportunities($optimization_scenarios),
            
            // Formatted display values
            'total_potential_savings_display' => $this->format_currency($financial_impact['total_annual_savings'] ?? 0, $input),
            
            // Calculation metadata
            'calculation_method' => 'comprehensive_opportunity',
            'calculation_timestamp' => current_time('timestamp')
        );
    }
    
    /**
     * Analyze current state
     * 
     * @param array $input Input data
     * @param array $basic_results Basic calculation results
     * @return array Current state analysis
     */
    private function analyze_current_state($input, $basic_results) {
        $total_quality_cost = $basic_results['total_quality_cost'];
        $revenue = $basic_results['revenue'];
        $quality_percentage = $basic_results['quality_percentage'];
        
        // Get cost breakdown
        $prevention_percentage = $input['prevention'] ?? $this->config->get('default_prevention', 10);
        $appraisal_percentage = $input['appraisal'] ?? $this->config->get('default_appraisal', 20);
        $internal_defect_percentage = $input['internal_defect'] ?? $this->config->get('default_internal_defect', 30);
        $external_defect_percentage = $input['external_defect'] ?? $this->config->get('default_external_defect', 40);
        
        // Calculate current ratios
        $cogq_percentage = $prevention_percentage + $appraisal_percentage;
        $copq_percentage = $internal_defect_percentage + $external_defect_percentage;
        $cogq_copq_ratio = $copq_percentage > 0 ? $cogq_percentage / $copq_percentage : 0;
        $prevention_ratio = $cogq_percentage > 0 ? ($prevention_percentage / $cogq_percentage) * 100 : 0;
        
        // Assess current performance against targets
        $performance_assessment = $this->assess_performance($quality_percentage, $cogq_copq_ratio, $prevention_ratio);
        
        return array(
            'quality_cost_percentage' => $quality_percentage,
            'total_quality_cost' => $total_quality_cost,
            'cogq_percentage' => $cogq_percentage,
            'copq_percentage' => $copq_percentage,
            'cogq_copq_ratio' => $cogq_copq_ratio,
            'prevention_ratio' => $prevention_ratio,
            'cost_breakdown' => array(
                'prevention' => $prevention_percentage,
                'appraisal' => $appraisal_percentage,
                'internal_defect' => $internal_defect_percentage,
                'external_defect' => $external_defect_percentage
            ),
            'performance_assessment' => $performance_assessment
        );
    }
    
    /**
     * Assess current performance against targets
     * 
     * @param float $quality_percentage Quality cost percentage
     * @param float $cogq_copq_ratio COGQ/COPQ ratio
     * @param float $prevention_ratio Prevention ratio
     * @return array Performance assessment
     */
    private function assess_performance($quality_percentage, $cogq_copq_ratio, $prevention_ratio) {
        $assessment = array();
        
        // Quality cost percentage assessment
        if ($quality_percentage <= $this->optimization_targets['total_quality_cost']['excellent']) {
            $assessment['quality_cost_level'] = 'excellent';
        } elseif ($quality_percentage <= $this->optimization_targets['total_quality_cost']['good']) {
            $assessment['quality_cost_level'] = 'good';
        } elseif ($quality_percentage <= $this->optimization_targets['total_quality_cost']['acceptable']) {
            $assessment['quality_cost_level'] = 'acceptable';
        } else {
            $assessment['quality_cost_level'] = 'needs_improvement';
        }
        
        // COGQ/COPQ ratio assessment
        if ($cogq_copq_ratio >= $this->optimization_targets['cogq_copq_ratio']['optimal']) {
            $assessment['balance_level'] = 'optimal';
        } elseif ($cogq_copq_ratio >= $this->optimization_targets['cogq_copq_ratio']['good']) {
            $assessment['balance_level'] = 'good';
        } elseif ($cogq_copq_ratio >= $this->optimization_targets['cogq_copq_ratio']['minimum']) {
            $assessment['balance_level'] = 'minimum';
        } else {
            $assessment['balance_level'] = 'poor';
        }
        
        // Prevention ratio assessment
        if ($prevention_ratio >= $this->optimization_targets['prevention_ratio']['optimal']) {
            $assessment['prevention_level'] = 'optimal';
        } elseif ($prevention_ratio >= $this->optimization_targets['prevention_ratio']['good']) {
            $assessment['prevention_level'] = 'good';
        } elseif ($prevention_ratio >= $this->optimization_targets['prevention_ratio']['minimum']) {
            $assessment['prevention_level'] = 'minimum';
        } else {
            $assessment['prevention_level'] = 'inadequate';
        }
        
        // Overall maturity score
        $assessment['overall_maturity'] = $this->calculate_maturity_score($assessment);
        
        return $assessment;
    }
    
    /**
     * Calculate maturity score
     * 
     * @param array $assessment Performance assessment
     * @return array Maturity score details
     */
    private function calculate_maturity_score($assessment) {
        $scores = array();
        
        // Quality cost level score
        switch ($assessment['quality_cost_level']) {
            case 'excellent': $scores['quality_cost'] = 100; break;
            case 'good': $scores['quality_cost'] = 80; break;
            case 'acceptable': $scores['quality_cost'] = 60; break;
            default: $scores['quality_cost'] = 30;
        }
        
        // Balance level score
        switch ($assessment['balance_level']) {
            case 'optimal': $scores['balance'] = 100; break;
            case 'good': $scores['balance'] = 75; break;
            case 'minimum': $scores['balance'] = 50; break;
            default: $scores['balance'] = 25;
        }
        
        // Prevention level score
        switch ($assessment['prevention_level']) {
            case 'optimal': $scores['prevention'] = 100; break;
            case 'good': $scores['prevention'] = 75; break;
            case 'minimum': $scores['prevention'] = 50; break;
            default: $scores['prevention'] = 25;
        }
        
        // Calculate weighted overall score
        $overall_score = ($scores['quality_cost'] * 0.4) + ($scores['balance'] * 0.35) + ($scores['prevention'] * 0.25);
        
        return array(
            'score' => round($overall_score, 1),
            'grade' => $this->get_maturity_grade($overall_score),
            'components' => $scores,
            'maturity_level' => $this->get_maturity_level($overall_score)
        );
    }
    
    /**
     * Get maturity grade
     * 
     * @param float $score Maturity score
     * @return string Grade letter
     */
    private function get_maturity_grade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D+';
        if ($score >= 40) return 'D';
        return 'F';
    }
    
    /**
     * Get maturity level
     * 
     * @param float $score Maturity score
     * @return string Maturity level
     */
    private function get_maturity_level($score) {
        if ($score >= 85) return 'world_class';
        if ($score >= 70) return 'advanced';
        if ($score >= 55) return 'developing';
        if ($score >= 40) return 'basic';
        return 'initial';
    }
    
    /**
     * Calculate optimization scenarios
     * 
     * @param array $current_state Current state analysis
     * @param float $revenue Total revenue
     * @return array Optimization scenarios
     */
    private function calculate_optimization_scenarios($current_state, $revenue) {
        $scenarios = array();
        
        // Scenario 1: Quick Wins (3-6 months)
        $scenarios['quick_wins'] = $this->calculate_quick_wins_scenario($current_state, $revenue);
        
        // Scenario 2: Balanced Improvement (6-12 months)
        $scenarios['balanced_improvement'] = $this->calculate_balanced_scenario($current_state, $revenue);
        
        // Scenario 3: World Class Transformation (12-24 months)
        $scenarios['world_class'] = $this->calculate_world_class_scenario($current_state, $revenue);
        
        // Scenario 4: Conservative Approach (minimal risk)
        $scenarios['conservative'] = $this->calculate_conservative_scenario($current_state, $revenue);
        
        return $scenarios;
    }
    
    /**
     * Calculate quick wins scenario
     * 
     * @param array $current_state Current state
     * @param float $revenue Revenue
     * @return array Quick wins scenario
     */
    private function calculate_quick_wins_scenario($current_state, $revenue) {
        $current_quality_percentage = $current_state['quality_cost_percentage'];
        
        // Target: 10-15% improvement in 3-6 months
        $improvement_percentage = 12;
        $target_quality_percentage = $current_quality_percentage * (1 - $improvement_percentage/100);
        
        // Focus on low-hanging fruit
        $target_breakdown = array(
            'prevention' => min($current_state['cost_breakdown']['prevention'] * 1.2, 15), // 20% increase in prevention
            'appraisal' => $current_state['cost_breakdown']['appraisal'],
            'internal_defect' => $current_state['cost_breakdown']['internal_defect'] * 0.85, // 15% reduction
            'external_defect' => $current_state['cost_breakdown']['external_defect'] * 0.90  // 10% reduction
        );
        
        // Normalize to ensure sum equals target
        $target_breakdown = $this->normalize_breakdown($target_breakdown, $target_quality_percentage);
        
        return array(
            'name' => 'Quick Wins',
            'timeframe' => '3-6 months',
            'difficulty' => 'low',
            'target_quality_percentage' => $target_quality_percentage,
            'improvement_percentage' => $improvement_percentage,
            'target_breakdown' => $target_breakdown,
            'annual_savings' => ($current_quality_percentage - $target_quality_percentage) * $revenue / 100,
            'implementation_cost' => $revenue * 0.002, // 0.2% of revenue
            'risk_level' => 'low',
            'confidence' => 85
        );
    }
    
    /**
     * Calculate balanced improvement scenario
     * 
     * @param array $current_state Current state
     * @param float $revenue Revenue
     * @return array Balanced scenario
     */
    private function calculate_balanced_scenario($current_state, $revenue) {
        $current_quality_percentage = $current_state['quality_cost_percentage'];
        
        // Target: 25-30% improvement in 6-12 months
        $improvement_percentage = 27;
        $target_quality_percentage = $current_quality_percentage * (1 - $improvement_percentage/100);
        
        // Balanced approach to COGQ/COPQ
        $target_breakdown = array(
            'prevention' => 18, // Increase prevention focus
            'appraisal' => 12,  // Maintain appraisal
            'internal_defect' => 20, // Reduce internal defects
            'external_defect' => 15  // Significantly reduce external defects
        );
        
        // Normalize to target
        $target_breakdown = $this->normalize_breakdown($target_breakdown, $target_quality_percentage);
        
        return array(
            'name' => 'Balanced Improvement',
            'timeframe' => '6-12 months',
            'difficulty' => 'medium',
            'target_quality_percentage' => $target_quality_percentage,
            'improvement_percentage' => $improvement_percentage,
            'target_breakdown' => $target_breakdown,
            'annual_savings' => ($current_quality_percentage - $target_quality_percentage) * $revenue / 100,
            'implementation_cost' => $revenue * 0.005, // 0.5% of revenue
            'risk_level' => 'medium',
            'confidence' => 75
        );
    }
    
    /**
     * Calculate world class scenario
     * 
     * @param array $current_state Current state
     * @param float $revenue Revenue
     * @return array World class scenario
     */
    private function calculate_world_class_scenario($current_state, $revenue) {
        // Target: World-class levels (3% of revenue)
        $target_quality_percentage = $this->optimization_targets['total_quality_cost']['excellent'];
        $current_quality_percentage = $current_state['quality_cost_percentage'];
        $improvement_percentage = (($current_quality_percentage - $target_quality_percentage) / $current_quality_percentage) * 100;
        
        // World-class breakdown
        $target_breakdown = array(
            'prevention' => 20, // High prevention focus
            'appraisal' => 10,  // Reduced appraisal needs
            'internal_defect' => 12, // Minimal internal defects
            'external_defect' => 8   // Minimal external defects
        );
        
        // Normalize to target
        $target_breakdown = $this->normalize_breakdown($target_breakdown, $target_quality_percentage);
        
        return array(
            'name' => 'World Class Transformation',
            'timeframe' => '12-24 months',
            'difficulty' => 'high',
            'target_quality_percentage' => $target_quality_percentage,
            'improvement_percentage' => $improvement_percentage,
            'target_breakdown' => $target_breakdown,
            'annual_savings' => ($current_quality_percentage - $target_quality_percentage) * $revenue / 100,
            'implementation_cost' => $revenue * 0.012, // 1.2% of revenue
            'risk_level' => 'high',
            'confidence' => 60
        );
    }
    
    /**
     * Calculate conservative scenario
     * 
     * @param array $current_state Current state
     * @param float $revenue Revenue
     * @return array Conservative scenario
     */
    private function calculate_conservative_scenario($current_state, $revenue) {
        $current_quality_percentage = $current_state['quality_cost_percentage'];
        
        // Target: 8-10% improvement with minimal risk
        $improvement_percentage = 9;
        $target_quality_percentage = $current_quality_percentage * (1 - $improvement_percentage/100);
        
        // Conservative changes
        $target_breakdown = array(
            'prevention' => $current_state['cost_breakdown']['prevention'] * 1.1, // 10% increase
            'appraisal' => $current_state['cost_breakdown']['appraisal'] * 1.05,   // 5% increase
            'internal_defect' => $current_state['cost_breakdown']['internal_defect'] * 0.92, // 8% reduction
            'external_defect' => $current_state['cost_breakdown']['external_defect'] * 0.95  // 5% reduction
        );
        
        // Normalize to target
        $target_breakdown = $this->normalize_breakdown($target_breakdown, $target_quality_percentage);
        
        return array(
            'name' => 'Conservative Approach',
            'timeframe' => '6-9 months',
            'difficulty' => 'low',
            'target_quality_percentage' => $target_quality_percentage,
            'improvement_percentage' => $improvement_percentage,
            'target_breakdown' => $target_breakdown,
            'annual_savings' => ($current_quality_percentage - $target_quality_percentage) * $revenue / 100,
            'implementation_cost' => $revenue * 0.001, // 0.1% of revenue
            'risk_level' => 'very_low',
            'confidence' => 95
        );
    }
    
    /**
     * Normalize breakdown to target percentage
     * 
     * @param array $breakdown Cost breakdown
     * @param float $target_percentage Target total percentage
     * @return array Normalized breakdown
     */
    private function normalize_breakdown($breakdown, $target_percentage) {
        $current_total = array_sum($breakdown);
        $factor = $target_percentage / $current_total;
        
        return array(
            'prevention' => $breakdown['prevention'] * $factor,
            'appraisal' => $breakdown['appraisal'] * $factor,
            'internal_defect' => $breakdown['internal_defect'] * $factor,
            'external_defect' => $breakdown['external_defect'] * $factor
        );
    }
    
    /**
     * Create implementation roadmap
     * 
     * @param array $scenarios Optimization scenarios
     * @param array $current_state Current state
     * @return array Implementation roadmap
     */
    private function create_implementation_roadmap($scenarios, $current_state) {
        $roadmap = array();
        
        // Recommended scenario based on current maturity
        $recommended_scenario = $this->select_recommended_scenario($scenarios, $current_state);
        
        // Phase-based implementation
        $phases = $this->create_implementation_phases($recommended_scenario);
        
        // Resource requirements
        $resource_requirements = $this->calculate_resource_requirements($recommended_scenario);
        
        // Success metrics and milestones
        $success_metrics = $this->define_success_metrics($recommended_scenario, $current_state);
        
        return array(
            'recommended_scenario' => $recommended_scenario,
            'implementation_phases' => $phases,
            'resource_requirements' => $resource_requirements,
            'success_metrics' => $success_metrics,
            'total_duration' => $recommended_scenario['timeframe'],
            'total_investment' => $recommended_scenario['implementation_cost']
        );
    }
    
    /**
     * Select recommended scenario based on maturity
     * 
     * @param array $scenarios Available scenarios
     * @param array $current_state Current state
     * @return array Recommended scenario
     */
    private function select_recommended_scenario($scenarios, $current_state) {
        $maturity_score = $current_state['performance_assessment']['overall_maturity']['score'];
        
        if ($maturity_score >= 70) {
            return $scenarios['world_class'];
        } elseif ($maturity_score >= 50) {
            return $scenarios['balanced_improvement'];
        } elseif ($maturity_score >= 30) {
            return $scenarios['quick_wins'];
        } else {
            return $scenarios['conservative'];
        }
    }
    
    /**
     * Create implementation phases
     * 
     * @param array $scenario Selected scenario
     * @return array Implementation phases
     */
    private function create_implementation_phases($scenario) {
        $phases = array();
        
        // Phase 1: Foundation (25% of timeline)
        $phases[] = array(
            'phase' => 1,
            'name' => 'Foundation & Quick Wins',
            'duration_percentage' => 25,
            'focus' => 'Establish measurement systems and implement quick wins',
            'activities' => array(
                'Implement quality cost tracking system',
                'Train key personnel on quality cost concepts',
                'Identify and implement quick wins',
                'Establish baseline measurements'
            ),
            'expected_improvement' => 15
        );
        
        // Phase 2: Process Improvement (40% of timeline)
        $phases[] = array(
            'phase' => 2,
            'name' => 'Process Improvement',
            'duration_percentage' => 40,
            'focus' => 'Systematic process improvements and defect reduction',
            'activities' => array(
                'Implement process improvement projects',
                'Enhance prevention activities',
                'Reduce internal defects through process optimization',
                'Strengthen supplier quality management'
            ),
            'expected_improvement' => 50
        );
        
        // Phase 3: Optimization (35% of timeline)
        $phases[] = array(
            'phase' => 3,
            'name' => 'Optimization & Sustainment',
            'duration_percentage' => 35,
            'focus' => 'Optimize balance and sustain improvements',
            'activities' => array(
                'Optimize COGQ/COPQ balance',
                'Implement advanced quality techniques',
                'Establish continuous improvement culture',
                'Create sustainability mechanisms'
            ),
            'expected_improvement' => 35
        );
        
        return $phases;
    }
    
    /**
     * Calculate resource requirements
     * 
     * @param array $scenario Selected scenario
     * @return array Resource requirements
     */
    private function calculate_resource_requirements($scenario) {
        $implementation_cost = $scenario['implementation_cost'];
        
        return array(
            'total_investment' => $implementation_cost,
            'investment_breakdown' => array(
                'training_and_development' => $implementation_cost * 0.3,
                'process_improvement_projects' => $implementation_cost * 0.4,
                'technology_and_tools' => $implementation_cost * 0.2,
                'consulting_and_support' => $implementation_cost * 0.1
            ),
            'human_resources' => array(
                'dedicated_project_manager' => '1.0 FTE',
                'quality_improvement_team' => '2-3 FTE',
                'part_time_subject_experts' => '3-5 people @ 20% time',
                'executive_sponsor_time' => '5-10 hours/month'
            ),
            'timeline_distribution' => array(
                'year_1' => $implementation_cost * 0.6,
                'year_2' => $implementation_cost * 0.4
            )
        );
    }
    
    /**
     * Define success metrics
     * 
     * @param array $scenario Selected scenario
     * @param array $current_state Current state
     * @return array Success metrics
     */
    private function define_success_metrics($scenario, $current_state) {
        $current_percentage = $current_state['quality_cost_percentage'];
        $target_percentage = $scenario['target_quality_percentage'];
        
        return array(
            'primary_metrics' => array(
                'quality_cost_reduction' => array(
                    'baseline' => $current_percentage,
                    'target' => $target_percentage,
                    'measurement_frequency' => 'monthly'
                ),
                'cogq_copq_balance' => array(
                    'baseline' => $current_state['cogq_copq_ratio'],
                    'target' => $this->optimization_targets['cogq_copq_ratio']['optimal'],
                    'measurement_frequency' => 'quarterly'
                ),
                'prevention_focus' => array(
                    'baseline' => $current_state['prevention_ratio'],
                    'target' => $this->optimization_targets['prevention_ratio']['optimal'],
                    'measurement_frequency' => 'quarterly'
                )
            ),
            'secondary_metrics' => array(
                'customer_satisfaction' => 'quarterly survey',
                'employee_engagement' => 'annual survey',
                'process_efficiency' => 'monthly KPIs',
                'defect_rates' => 'weekly tracking'
            ),
            'milestones' => array(
                '3_months' => '20% of target improvement achieved',
                '6_months' => '50% of target improvement achieved',
                '12_months' => '80% of target improvement achieved',
                '18_months' => '100% of target improvement achieved'
            )
        );
    }
    
    /**
     * Calculate financial impact
     * 
     * @param array $scenarios Optimization scenarios
     * @param float $revenue Revenue
     * @return array Financial impact analysis
     */
    private function calculate_financial_impact($scenarios, $revenue) {
        $financial_analysis = array();
        
        foreach ($scenarios as $name => $scenario) {
            $annual_savings = $scenario['annual_savings'];
            $implementation_cost = $scenario['implementation_cost'];
            
            // Calculate ROI and payback period
            $roi = $implementation_cost > 0 ? (($annual_savings - $implementation_cost) / $implementation_cost) * 100 : 0;
            $payback_months = $annual_savings > 0 ? ($implementation_cost / ($annual_savings / 12)) : 999;
            
            // Calculate NPV (assuming 3-year benefit period, 10% discount rate)
            $npv = $this->calculate_npv($annual_savings, $implementation_cost, 3, 0.10);
            
            $financial_analysis[$name] = array(
                'annual_savings' => $annual_savings,
                'implementation_cost' => $implementation_cost,
                'net_annual_benefit' => $annual_savings - ($implementation_cost / 2), // Amortize over 2 years
                'roi_percentage' => $roi,
                'payback_period_months' => min($payback_months, 999),
                'npv_3_years' => $npv,
                'break_even_month' => min($payback_months, 999)
            );
        }
        
        // Find best financial scenario
        $best_scenario = $this->find_best_financial_scenario($financial_analysis);
        
        return array(
            'scenario_analysis' => $financial_analysis,
            'best_financial_scenario' => $best_scenario,
            'total_annual_savings' => $financial_analysis[$best_scenario]['annual_savings'] ?? 0
        );
    }
    
    /**
     * Calculate Net Present Value
     * 
     * @param float $annual_savings Annual savings
     * @param float $initial_investment Initial investment
     * @param int $years Number of years
     * @param float $discount_rate Discount rate
     * @return float NPV
     */
    /**
     * Calculate Net Present Value
     * 
     * @param float $annual_savings Annual savings
     * @param float $initial_investment Initial investment
     * @param int $years Number of years
     * @param float $discount_rate Discount rate
     * @return float NPV
     */
    private function calculate_npv($annual_savings, $initial_investment, $years, $discount_rate) {
        $npv = -$initial_investment;
        
        for ($year = 1; $year <= $years; $year++) {
            $npv += $annual_savings / pow(1 + $discount_rate, $year);
        }
        
        return $npv;
    }
    
    /**
     * Find best financial scenario
     * 
     * @param array $financial_analysis Financial analysis
     * @return string Best scenario name
     */
    private function find_best_financial_scenario($financial_analysis) {
        $best_scenario = '';
        $best_score = -999999;
        
        foreach ($financial_analysis as $name => $analysis) {
            // Score based on NPV and payback period
            $score = $analysis['npv_3_years'] - ($analysis['payback_period_months'] * 1000);
            
            if ($score > $best_score) {
                $best_score = $score;
                $best_scenario = $name;
            }
        }
        
        return $best_scenario;
    }
    
    /**
     * Calculate risk-adjusted opportunities
     * 
     * @param array $scenarios Optimization scenarios
     * @return array Risk-adjusted analysis
     */
    private function calculate_risk_adjusted_opportunities($scenarios) {
        $risk_adjusted = array();
        
        foreach ($scenarios as $name => $scenario) {
            $confidence = $scenario['confidence'] / 100;
            $risk_multiplier = $this->get_risk_multiplier($scenario['risk_level']);
            
            $adjusted_savings = $scenario['annual_savings'] * $confidence * $risk_multiplier;
            $adjusted_cost = $scenario['implementation_cost'] / $risk_multiplier; // Higher risk = higher effective cost
            
            $risk_adjusted[$name] = array(
                'original_savings' => $scenario['annual_savings'],
                'adjusted_savings' => $adjusted_savings,
                'risk_factor' => $risk_multiplier,
                'confidence_factor' => $confidence,
                'adjusted_roi' => $adjusted_cost > 0 ? (($adjusted_savings - $adjusted_cost) / $adjusted_cost) * 100 : 0,
                'risk_score' => $this->calculate_risk_score($scenario)
            );
        }
        
        return $risk_adjusted;
    }
    
    /**
     * Get risk multiplier
     * 
     * @param string $risk_level Risk level
     * @return float Risk multiplier
     */
    private function get_risk_multiplier($risk_level) {
        $multipliers = array(
            'very_low' => 0.95,
            'low' => 0.90,
            'medium' => 0.80,
            'high' => 0.65,
            'very_high' => 0.50
        );
        
        return $multipliers[$risk_level] ?? 0.75;
    }
    
    /**
     * Calculate risk score
     * 
     * @param array $scenario Scenario data
     * @return float Risk score
     */
    private function calculate_risk_score($scenario) {
        $risk_factors = array(
            'very_low' => 10,
            'low' => 25,
            'medium' => 50,
            'high' => 75,
            'very_high' => 90
        );
        
        $base_risk = $risk_factors[$scenario['risk_level']] ?? 50;
        $confidence_adjustment = (100 - $scenario['confidence']) * 0.5;
        
        return min(100, $base_risk + $confidence_adjustment);
    }
    
    /**
     * Generate strategic recommendations
     * 
     * @param array $current_state Current state
     * @param array $scenarios Optimization scenarios
     * @return array Strategic recommendations
     */
    private function generate_strategic_recommendations($current_state, $scenarios) {
        $recommendations = array();
        
        $maturity_level = $current_state['performance_assessment']['overall_maturity']['maturity_level'];
        
        // Maturity-based recommendations
        switch ($maturity_level) {
            case 'initial':
                $recommendations[] = array(
                    'priority' => 'critical',
                    'category' => 'foundation',
                    'title' => __('Establish Quality Cost Foundation', 'quality-cost-calculator'),
                    'description' => __('Focus on establishing basic quality cost measurement and tracking systems', 'quality-cost-calculator'),
                    'timeframe' => 'immediate'
                );
                break;
                
            case 'basic':
                $recommendations[] = array(
                    'priority' => 'high',
                    'category' => 'improvement',
                    'title' => __('Implement Systematic Improvements', 'quality-cost-calculator'),
                    'description' => __('Focus on systematic process improvements and defect reduction initiatives', 'quality-cost-calculator'),
                    'timeframe' => 'short_term'
                );
                break;
                
            case 'developing':
                $recommendations[] = array(
                    'priority' => 'high',
                    'category' => 'optimization',
                    'title' => __('Optimize COGQ/COPQ Balance', 'quality-cost-calculator'),
                    'description' => __('Focus on optimizing the balance between prevention and failure costs', 'quality-cost-calculator'),
                    'timeframe' => 'medium_term'
                );
                break;
                
            case 'advanced':
                $recommendations[] = array(
                    'priority' => 'medium',
                    'category' => 'excellence',
                    'title' => __('Pursue World-Class Performance', 'quality-cost-calculator'),
                    'description' => __('Implement advanced quality techniques to achieve world-class performance levels', 'quality-cost-calculator'),
                    'timeframe' => 'long_term'
                );
                break;
                
            case 'world_class':
                $recommendations[] = array(
                    'priority' => 'low',
                    'category' => 'sustain',
                    'title' => __('Sustain Excellence', 'quality-cost-calculator'),
                    'description' => __('Focus on sustaining world-class performance and continuous innovation', 'quality-cost-calculator'),
                    'timeframe' => 'ongoing'
                );
                break;
        }
        
        // Performance gap recommendations
        if ($current_state['performance_assessment']['prevention_level'] === 'inadequate') {
            $recommendations[] = array(
                'priority' => 'critical',
                'category' => 'prevention',
                'title' => __('Increase Prevention Investment', 'quality-cost-calculator'),
                'description' => __('Dramatically increase prevention activities to reduce downstream costs', 'quality-cost-calculator'),
                'timeframe' => 'immediate'
            );
        }
        
        if ($current_state['performance_assessment']['balance_level'] === 'poor') {
            $recommendations[] = array(
                'priority' => 'high',
                'category' => 'balance',
                'title' => __('Rebalance Quality Investments', 'quality-cost-calculator'),
                'description' => __('Shift focus from reactive to proactive quality management', 'quality-cost-calculator'),
                'timeframe' => 'short_term'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Identify quick wins
     * 
     * @param array $current_state Current state
     * @param array $scenarios Optimization scenarios
     * @return array Quick wins
     */
    private function identify_quick_wins($current_state, $scenarios) {
        $quick_wins = array();
        
        // High impact, low effort opportunities
        if ($current_state['cost_breakdown']['external_defect'] > 25) {
            $quick_wins[] = array(
                'opportunity' => 'external_defect_reduction',
                'title' => __('Enhanced Final Inspection', 'quality-cost-calculator'),
                'description' => __('Strengthen final quality checks to prevent defects reaching customers', 'quality-cost-calculator'),
                'impact' => 'high',
                'effort' => 'low',
                'timeframe' => '1-3 months',
                'expected_savings_percentage' => 15
            );
        }
        
        if ($current_state['cost_breakdown']['internal_defect'] > 20) {
            $quick_wins[] = array(
                'opportunity' => 'process_standardization',
                'title' => __('Process Standardization', 'quality-cost-calculator'),
                'description' => __('Standardize key processes to reduce variation and defects', 'quality-cost-calculator'),
                'impact' => 'medium',
                'effort' => 'low',
                'timeframe' => '2-4 months',
                'expected_savings_percentage' => 10
            );
        }
        
        if ($current_state['cost_breakdown']['prevention'] < 15) {
            $quick_wins[] = array(
                'opportunity' => 'training_program',
                'title' => __('Quality Training Program', 'quality-cost-calculator'),
                'description' => __('Implement comprehensive quality training for key personnel', 'quality-cost-calculator'),
                'impact' => 'medium',
                'effort' => 'low',
                'timeframe' => '1-2 months',
                'expected_savings_percentage' => 8
            );
        }
        
        return $quick_wins;
    }
    
    /**
     * Identify long-term opportunities
     * 
     * @param array $scenarios Optimization scenarios
     * @return array Long-term opportunities
     */
    private function identify_long_term_opportunities($scenarios) {
        $opportunities = array();
        
        // Technology-enabled opportunities
        $opportunities[] = array(
            'category' => 'technology',
            'title' => __('Digital Quality Management System', 'quality-cost-calculator'),
            'description' => __('Implement comprehensive digital quality management platform', 'quality-cost-calculator'),
            'impact' => 'very_high',
            'investment_level' => 'high',
            'timeframe' => '12-18 months',
            'expected_improvement' => 35
        );
        
        // Organizational transformation
        $opportunities[] = array(
            'category' => 'culture',
            'title' => __('Quality Culture Transformation', 'quality-cost-calculator'),
            'description' => __('Transform organizational culture to embed quality in all processes', 'quality-cost-calculator'),
            'impact' => 'very_high',
            'investment_level' => 'medium',
            'timeframe' => '18-24 months',
            'expected_improvement' => 40
        );
        
        // Supply chain integration
        $opportunities[] = array(
            'category' => 'supply_chain',
            'title' => __('Integrated Supply Chain Quality', 'quality-cost-calculator'),
            'description' => __('Integrate quality management across entire supply chain', 'quality-cost-calculator'),
            'impact' => 'high',
            'investment_level' => 'high',
            'timeframe' => '15-24 months',
            'expected_improvement' => 25
        );
        
        return $opportunities;
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
            'name' => 'Opportunity_Calculator',
            'version' => '2.0.0',
            'config_loaded' => $this->config !== null,
            'container_available' => $this->container !== null,
            'optimization_targets_loaded' => !empty($this->optimization_targets),
            'implementation_factors_loaded' => !empty($this->implementation_factors),
            'calculation_methods' => array('calculate')
        );
    }
}
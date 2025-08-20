<?php
/**
 * QCC Quality Cost Factory
 * 
 * Entschlackte Factory für CoGQ/CoPQ Components.
 * Fokus auf Kernfunktionalität ohne Overhead.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Factories
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Quality_Cost_Factory {
    
    private $container;
    private $translator;
    
    public function __construct($container = null) {
        $this->container = $container ?: $this->get_fallback_container();
        $this->translator = $this->get_translator();
    }
    
    /**
     * Create complete CoGQ/CoPQ section
     */
    public function create_complete_section($config = array()) {
        $config = array_merge(array(
            'layout' => 'side_by_side',
            'cogq_data' => array(),
            'copq_data' => array(),
            'currency' => 'EUR',
            'unit' => 'M'
        ), $config);
        
        $html = '<div class="qcc-quality-cost-section" data-layout="' . esc_attr($config['layout']) . '">';
        
        if ($config['layout'] === 'side_by_side') {
            $html .= '<div class="qcc-side-by-side-container">';
            $html .= $this->create_cogq_card($config['cogq_data'], $config);
            $html .= $this->create_copq_card($config['copq_data'], $config);
            $html .= '</div>';
        } else {
            $html .= $this->create_cogq_card($config['cogq_data'], $config);
            $html .= $this->create_copq_card($config['copq_data'], $config);
        }
        
        $html .= $this->create_summary_card($config);
        $html .= $this->get_styles();
        $html .= $this->get_scripts();
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Create CoGQ card
     */
    public function create_cogq_card($data = array(), $config = array()) {
        $data = array_merge(array(
            'prevention_cost' => 0,
            'appraisal_cost' => 0,
            'total_cogq' => 0,
            'percentage' => 0
        ), $data);
        
        $currency = $config['currency'] ?? 'EUR';
        $symbol = $this->get_currency_symbol($currency);
        $unit = $config['unit'] ?? 'M';
        
        return sprintf('
        <div class="qcc-quality-card qcc-cogq-card">
            <div class="qcc-card-header">
                <div class="qcc-card-icon">🛡️</div>
                <h3>%s</h3>
            </div>
            
            <div class="qcc-card-body">
                <div class="qcc-total-value">
                    <span class="qcc-currency">%s</span>
                    <span class="qcc-amount" data-update="cogq_total">%s</span>
                    <span class="qcc-unit">%s</span>
                </div>
                
                <div class="qcc-breakdown">
                    <div class="qcc-breakdown-item">
                        <span>%s</span>
                        <span data-update="prevention_cost">%s%s</span>
                    </div>
                    <div class="qcc-breakdown-item">
                        <span>%s</span>
                        <span data-update="appraisal_cost">%s%s</span>
                    </div>
                </div>
                
                <div class="qcc-percentage">
                    <span>%s: </span>
                    <span data-update="cogq_percentage">%s%%</span>
                </div>
            </div>
        </div>',
            esc_html($this->translator->get('cost_of_good_quality')),
            esc_html($symbol),
            number_format($data['total_cogq'], 1),
            esc_html($unit),
            esc_html($this->translator->get('prevention_costs')),
            esc_html($symbol),
            number_format($data['prevention_cost'], 1),
            esc_html($this->translator->get('appraisal_costs')),
            esc_html($symbol),
            number_format($data['appraisal_cost'], 1),
            esc_html($this->translator->get('of_total')),
            number_format($data['percentage'], 1)
        );
    }
    
    /**
     * Create CoPQ card
     */
    public function create_copq_card($data = array(), $config = array()) {
        $data = array_merge(array(
            'internal_failure_cost' => 0,
            'external_failure_cost' => 0,
            'total_copq' => 0,
            'percentage' => 0
        ), $data);
        
        $currency = $config['currency'] ?? 'EUR';
        $symbol = $this->get_currency_symbol($currency);
        $unit = $config['unit'] ?? 'M';
        
        return sprintf('
        <div class="qcc-quality-card qcc-copq-card">
            <div class="qcc-card-header">
                <div class="qcc-card-icon">⚠️</div>
                <h3>%s</h3>
            </div>
            
            <div class="qcc-card-body">
                <div class="qcc-total-value">
                    <span class="qcc-currency">%s</span>
                    <span class="qcc-amount" data-update="copq_total">%s</span>
                    <span class="qcc-unit">%s</span>
                </div>
                
                <div class="qcc-breakdown">
                    <div class="qcc-breakdown-item">
                        <span>%s</span>
                        <span data-update="internal_failure_cost">%s%s</span>
                    </div>
                    <div class="qcc-breakdown-item">
                        <span>%s</span>
                        <span data-update="external_failure_cost">%s%s</span>
                    </div>
                </div>
                
                <div class="qcc-percentage">
                    <span>%s: </span>
                    <span data-update="copq_percentage">%s%%</span>
                </div>
            </div>
        </div>',
            esc_html($this->translator->get('cost_of_poor_quality')),
            esc_html($symbol),
            number_format($data['total_copq'], 1),
            esc_html($unit),
            esc_html($this->translator->get('internal_failure_costs')),
            esc_html($symbol),
            number_format($data['internal_failure_cost'], 1),
            esc_html($this->translator->get('external_failure_costs')),
            esc_html($symbol),
            number_format($data['external_failure_cost'], 1),
            esc_html($this->translator->get('of_total')),
            number_format($data['percentage'], 1)
        );
    }
    
    /**
     * Create summary card
     */
    public function create_summary_card($config = array()) {
        return sprintf('
        <div class="qcc-quality-card qcc-summary-card">
            <div class="qcc-card-header">
                <div class="qcc-card-icon">📊</div>
                <h3>%s</h3>
            </div>
            
            <div class="qcc-card-body">
                <div class="qcc-ratio-section">
                    <div class="qcc-ratio-label">%s</div>
                    <div class="qcc-ratio-bar">
                        <div class="qcc-ratio-cogq" data-update="cogq_bar" style="width: 50%%">
                            <span>CoGQ</span>
                        </div>
                        <div class="qcc-ratio-copq" data-update="copq_bar" style="width: 50%%">
                            <span>CoPQ</span>
                        </div>
                    </div>
                </div>
                
                <div class="qcc-metrics">
                    <div class="qcc-metric">
                        <span class="qcc-metric-label">%s</span>
                        <span class="qcc-metric-value" data-update="total_cost">%s0.0M</span>
                    </div>
                    <div class="qcc-metric">
                        <span class="qcc-metric-label">%s</span>
                        <span class="qcc-metric-value" data-update="quality_ratio">1.0</span>
                    </div>
                </div>
            </div>
        </div>',
            esc_html($this->translator->get('quality_summary')),
            esc_html($this->translator->get('cogq_vs_copq')),
            esc_html($this->translator->get('total_cost')),
            esc_html($this->get_currency_symbol($config['currency'] ?? 'EUR')),
            esc_html($this->translator->get('quality_ratio'))
        );
    }
    
    /**
     * Get CSS styles
     */
    private function get_styles() {
        return '<style>
        .qcc-quality-cost-section { margin: 20px 0; }
        .qcc-side-by-side-container { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .qcc-side-by-side-container { grid-template-columns: 1fr; }
        }
        
        .qcc-quality-card {
            border-radius: 12px;
            padding: 24px;
            color: white;
            margin-bottom: 20px;
            transition: transform 0.2s ease;
        }
        .qcc-quality-card:hover { transform: translateY(-2px); }
        
        .qcc-cogq-card { background: linear-gradient(135deg, #2E8B57, #32CD32); }
        .qcc-copq-card { background: linear-gradient(135deg, #DC143C, #FF6347); }
        .qcc-summary-card { background: linear-gradient(135deg, #4169E1, #6495ED); }
        
        .qcc-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .qcc-card-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        .qcc-card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .qcc-total-value {
            text-align: center;
            margin-bottom: 20px;
        }
        .qcc-amount {
            font-size: 32px;
            font-weight: 700;
            margin: 0 4px;
        }
        .qcc-currency, .qcc-unit {
            font-size: 20px;
            font-weight: 500;
        }
        
        .qcc-breakdown {
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 16px;
            margin-bottom: 16px;
        }
        .qcc-breakdown-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .qcc-percentage {
            text-align: center;
            padding-top: 12px;
            border-top: 1px solid rgba(255,255,255,0.3);
        }
        
        .qcc-ratio-bar {
            display: flex;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 12px 0;
        }
        .qcc-ratio-cogq {
            background: #2E8B57;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            transition: width 0.5s ease;
        }
        .qcc-ratio-copq {
            background: #DC143C;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            transition: width 0.5s ease;
        }
        
        .qcc-metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }
        .qcc-metric {
            text-align: center;
            padding: 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .qcc-metric-label {
            display: block;
            font-size: 12px;
            opacity: 0.8;
            margin-bottom: 4px;
        }
        .qcc-metric-value {
            font-size: 16px;
            font-weight: 600;
        }
        </style>';
    }
    
    /**
     * Get JavaScript for live updates
     */
    private function get_scripts() {
        return '<script>
        (function() {
            if (window.QCC_QualityCostUpdater) return;
            
            window.QCC_QualityCostUpdater = {
                update: function(data) {
                    // Update CoGQ values
                    this.updateElement("cogq_total", this.formatNumber(data.cogq_total));
                    this.updateElement("prevention_cost", this.formatNumber(data.prevention_cost));
                    this.updateElement("appraisal_cost", this.formatNumber(data.appraisal_cost));
                    this.updateElement("cogq_percentage", data.cogq_percentage.toFixed(1) + "%");
                    
                    // Update CoPQ values
                    this.updateElement("copq_total", this.formatNumber(data.copq_total));
                    this.updateElement("internal_failure_cost", this.formatNumber(data.internal_failure_cost));
                    this.updateElement("external_failure_cost", this.formatNumber(data.external_failure_cost));
                    this.updateElement("copq_percentage", data.copq_percentage.toFixed(1) + "%");
                    
                    // Update summary
                    this.updateElement("total_cost", this.formatNumber(data.total_cost));
                    this.updateElement("quality_ratio", data.quality_ratio.toFixed(2));
                    
                    // Update ratio bar
                    this.updateRatioBar(data.cogq_percentage, data.copq_percentage);
                },
                
                updateElement: function(selector, value) {
                    var elements = document.querySelectorAll("[data-update=\"" + selector + "\"]");
                    for (var i = 0; i < elements.length; i++) {
                        elements[i].textContent = value;
                        elements[i].classList.add("qcc-updating");
                        setTimeout(function(el) {
                            el.classList.remove("qcc-updating");
                        }, 300, elements[i]);
                    }
                },
                
                updateRatioBar: function(cogqPercent, copqPercent) {
                    var cogqBar = document.querySelector("[data-update=\"cogq_bar\"]");
                    var copqBar = document.querySelector("[data-update=\"copq_bar\"]");
                    
                    if (cogqBar) cogqBar.style.width = cogqPercent + "%";
                    if (copqBar) copqBar.style.width = copqPercent + "%";
                },
                
                formatNumber: function(num) {
                    return parseFloat(num).toFixed(1);
                }
            };
            
            // Listen for updates
            document.addEventListener("qcc_calculation_complete", function(e) {
                if (e.detail) {
                    QCC_QualityCostUpdater.update(e.detail);
                }
            });
        })();
        </script>';
    }
    
    /**
     * Get translator service
     */
    private function get_translator() {
        try {
            return $this->container->get('translator');
        } catch (Exception $e) {
            return new QCC_Basic_Translator();
        }
    }
    
    /**
     * Get fallback container
     */
    private function get_fallback_container() {
        return new class {
            public function get($service) {
                throw new Exception('Service not available');
            }
        };
    }
    
    /**
     * Get currency symbol
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'Fr',
            'CAD' => 'C$'
        );
        
        return $symbols[$currency] ?? $currency;
    }
}

/**
 * Basic Translator fallback
 */
class QCC_Basic_Translator {
    private $translations = array(
        'cost_of_good_quality' => 'Cost of Good Quality (CoGQ)',
        'cost_of_poor_quality' => 'Cost of Poor Quality (CoPQ)',
        'prevention_costs' => 'Prevention Costs',
        'appraisal_costs' => 'Appraisal Costs',
        'internal_failure_costs' => 'Internal Failure Costs',
        'external_failure_costs' => 'External Failure Costs',
        'of_total' => 'of Total',
        'quality_summary' => 'Quality Cost Summary',
        'cogq_vs_copq' => 'CoGQ vs CoPQ Ratio',
        'total_cost' => 'Total Cost',
        'quality_ratio' => 'Quality Ratio'
    );
    
    public function get($key) {
        return $this->translations[$key] ?? $key;
    }
}
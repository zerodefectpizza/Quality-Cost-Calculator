<?php
/**
 * QCC Quality Summary Card Atom
 * 
 * Kombinierte Übersichtskarte für Total Quality Cost.
 * Zeigt COGQ vs COPQ Verhältnis mit intelligenten Empfehlungen.
 * 
 * DATEI SPEICHERN ALS:
 * includes/rendering/atoms/class-qcc-quality-summary-card.php
 * 
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Quality_Summary_Card extends QCC_Base_Atom {
    
    /**
     * Component name
     * @var string
     */
    protected $component_name = 'quality_summary_card';
    
    /**
     * Default component data
     * @var array
     */
    protected $default_data = array(
        'title' => 'Total Quality Cost',
        'icon' => '📊',
        'total_quality_cost' => 0,
        'cogq_total' => 0,
        'copq_total' => 0,
        'cogq_percentage' => 0,
        'copq_percentage' => 0,
        'currency' => 'EUR',
        'show_ratio' => true,
        'show_recommendation' => true,
        'show_trend' => false,
        'trend_data' => array(),
        'size' => 'normal' // compact, normal, large
    );
    
    /**
     * Render quality summary card
     * 
     * @param array $data Component data
     * @param array $attributes HTML attributes
     * @return string Rendered HTML
     */
    public function render($data = array(), $attributes = array()) {
        $data = array_merge($this->default_data, $data);
        $attributes = array_merge(array(
            'class' => 'qcc-quality-summary-card qcc-summary-card--' . $data['size'],
            'data-component' => 'quality_summary_card'
        ), $attributes);
        
        $currency_symbol = $this->get_currency_symbol($data['currency']);
        $card_id = 'qcc-summary-card-' . uniqid();
        
        // Calculate recommendation
        $recommendation = $this->get_recommendation($data);
        
        ob_start();
        ?>
        <div <?php echo $this->render_attributes($attributes); ?> id="<?php echo esc_attr($card_id); ?>">
            
            <!-- Card Header -->
            <div class="qcc-summary-header">
                <div class="qcc-summary-icon">
                    <?php echo esc_html($data['icon']); ?>
                </div>
                <div class="qcc-summary-title-area">
                    <h4 class="qcc-summary-title">
                        <?php echo esc_html($data['title']); ?>
                    </h4>
                    <div class="qcc-total-amount" data-update="total_quality_cost">
                        <?php echo esc_html($currency_symbol . ' ' . number_format($data['total_quality_cost'], 2)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Card Body -->
            <div class="qcc-summary-body">
                
                <?php if ($data['show_ratio']): ?>
                <!-- Ratio Visualization -->
                <div class="qcc-summary-breakdown">
                    <div class="qcc-ratio-container">
                        <div class="qcc-ratio-bar">
                            <div class="qcc-cogq-portion" 
                                 data-update="cogq_portion"
                                 style="width: <?php echo esc_attr($data['cogq_percentage']); ?>%"
                                 title="COGQ: <?php echo esc_attr(number_format($data['cogq_percentage'], 1)); ?>%"></div>
                            <div class="qcc-copq-portion" 
                                 data-update="copq_portion"
                                 style="width: <?php echo esc_attr($data['copq_percentage']); ?>%"
                                 title="COPQ: <?php echo esc_attr(number_format($data['copq_percentage'], 1)); ?>%"></div>
                        </div>
                        
                        <div class="qcc-ratio-labels">
                            <div class="qcc-cogq-label">
                                <span class="qcc-color-indicator qcc-cogq-color"></span>
                                <span class="qcc-label-text">COGQ</span>
                                <span class="qcc-label-percentage" data-update="cogq_percentage">
                                    <?php echo esc_html(number_format($data['cogq_percentage'], 1)); ?>%
                                </span>
                            </div>
                            <div class="qcc-copq-label">
                                <span class="qcc-color-indicator qcc-copq-color"></span>
                                <span class="qcc-label-text">COPQ</span>
                                <span class="qcc-label-percentage" data-update="copq_percentage">
                                    <?php echo esc_html(number_format($data['copq_percentage'], 1)); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Amounts -->
                    <div class="qcc-detailed-amounts">
                        <div class="qcc-amount-item qcc-cogq-amount">
                            <span class="qcc-amount-label">COGQ Total:</span>
                            <span class="qcc-amount-value" data-update="cogq_total_formatted">
                                <?php echo esc_html($currency_symbol . ' ' . number_format($data['cogq_total'], 2)); ?>
                            </span>
                        </div>
                        <div class="qcc-amount-item qcc-copq-amount">
                            <span class="qcc-amount-label">COPQ Total:</span>
                            <span class="qcc-amount-value" data-update="copq_total_formatted">
                                <?php echo esc_html($currency_symbol . ' ' . number_format($data['copq_total'], 2)); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Card Footer -->
            <?php if ($data['show_recommendation']): ?>
            <div class="qcc-summary-footer">
                <div class="qcc-recommendation qcc-recommendation--<?php echo esc_attr($recommendation['type']); ?>">
                    <span class="qcc-recommendation-icon"><?php echo esc_html($recommendation['icon']); ?></span>
                    <span class="qcc-recommendation-text"><?php echo esc_html($recommendation['text']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Quality Summary Card CSS -->
        <style>
        .qcc-quality-summary-card {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            max-width: 100%;
            margin: 0;
        }
        
        .qcc-quality-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }
        
        .qcc-summary-header {
            padding: 20px 20px 15px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .qcc-summary-icon {
            font-size: 24px;
            background: rgba(255, 255, 255, 0.2);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .qcc-summary-title-area {
            flex: 1;
        }
        
        .qcc-summary-title {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
            opacity: 0.9;
        }
        
        .qcc-total-amount {
            font-size: 32px;
            font-weight: 700;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
            line-height: 1.1;
        }
        
        .qcc-summary-body {
            padding: 0 20px 20px;
        }
        
        .qcc-ratio-container {
            margin-bottom: 20px;
        }
        
        .qcc-ratio-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            overflow: hidden;
            display: flex;
            margin-bottom: 12px;
        }
        
        .qcc-cogq-portion {
            background: #28a745;
            transition: width 0.8s ease;
            min-width: 2px;
        }
        
        .qcc-copq-portion {
            background: #dc3545;
            transition: width 0.8s ease;
            min-width: 2px;
        }
        
        .qcc-ratio-labels {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .qcc-cogq-label,
        .qcc-copq-label {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .qcc-color-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .qcc-cogq-color {
            background: #28a745;
        }
        
        .qcc-copq-color {
            background: #dc3545;
        }
        
        .qcc-label-percentage {
            font-weight: 600;
            font-family: monospace;
        }
        
        .qcc-detailed-amounts {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 12px;
        }
        
        .qcc-amount-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .qcc-amount-item:last-child {
            margin-bottom: 0;
        }
        
        .qcc-amount-label {
            opacity: 0.9;
        }
        
        .qcc-amount-value {
            font-weight: 600;
            font-family: monospace;
        }
        
        .qcc-summary-footer {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .qcc-recommendation {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        
        .qcc-recommendation--positive {
            color: #d4edda;
        }
        
        .qcc-recommendation--warning {
            color: #fff3cd;
        }
        
        .qcc-recommendation--danger {
            color: #f8d7da;
        }
        
        .qcc-recommendation-icon {
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .qcc-recommendation-text {
            line-height: 1.4;
        }
        
        /* Size Variants */
        .qcc-summary-card--compact {
            transform: scale(0.9);
        }
        
        .qcc-summary-card--large .qcc-summary-header {
            padding: 25px 25px 20px;
        }
        
        .qcc-summary-card--large .qcc-total-amount {
            font-size: 36px;
        }
        
        .qcc-summary-card--large .qcc-summary-body {
            padding: 0 25px 25px;
        }
        
        /* Update Animations */
        .qcc-quality-summary-card .qcc-total-amount {
            transition: all 0.5s ease;
        }
        
        .qcc-quality-summary-card.qcc-updating .qcc-total-amount {
            transform: scale(1.05);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .qcc-summary-header {
                padding: 15px 15px 12px;
                gap: 12px;
            }
            
            .qcc-summary-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .qcc-summary-title {
                font-size: 16px;
            }
            
            .qcc-total-amount {
                font-size: 28px;
            }
            
            .qcc-summary-body {
                padding: 0 15px 15px;
            }
            
            .qcc-summary-footer {
                padding: 12px 15px;
            }
            
            .qcc-ratio-labels {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .qcc-detailed-amounts {
                padding: 10px;
            }
        }
        </style>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get intelligent recommendation based on data
     * 
     * @param array $data Card data
     * @return array Recommendation data
     */
    private function get_recommendation($data) {
        $copq_percentage = $data['copq_percentage'];
        $cogq_percentage = $data['cogq_percentage'];
        $total_cost = $data['total_quality_cost'];
        
        // No data yet
        if ($total_cost <= 0) {
            return array(
                'type' => 'info',
                'icon' => 'ℹ️',
                'text' => 'Enter values to see quality cost analysis'
            );
        }
        
        // Excellent balance (COGQ significantly higher than COPQ)
        if ($cogq_percentage > 70 && $copq_percentage < 30) {
            return array(
                'type' => 'positive',
                'icon' => '🎯',
                'text' => 'Excellent prevention focus! Quality investment is paying off.'
            );
        }
        
        // Good balance (COGQ higher than COPQ)
        if ($cogq_percentage > $copq_percentage && $cogq_percentage > 50) {
            return array(
                'type' => 'positive',
                'icon' => '✅',
                'text' => 'Good balance of prevention and failure costs.'
            );
        }
        
        // Warning: COPQ higher than COGQ
        if ($copq_percentage > $cogq_percentage && $copq_percentage < 70) {
            return array(
                'type' => 'warning',
                'icon' => '📈',
                'text' => 'Focus on prevention to reduce failure costs.'
            );
        }
        
        // Critical: Very high COPQ
        if ($copq_percentage > 70) {
            return array(
                'type' => 'danger',
                'icon' => '🚨',
                'text' => 'Critical: High failure costs! Increase prevention investment.'
            );
        }
        
        // Balanced (close to 50/50)
        return array(
            'type' => 'warning',
            'icon' => '⚖️',
            'text' => 'Balanced costs. Consider optimizing prevention strategies.'
        );
    }
    
    /**
     * Get currency symbol
     * 
     * @param string $currency Currency code
     * @return string Currency symbol
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥'
        );
        
        return $symbols[$currency] ?? $currency;
    }
}

/**
 * Register Quality Summary Card with Component Registry
 */
add_action('qcc_register_components', function() {
    if (class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized()) {
        try {
            $container = QCC_Service_Container_Setup::get_container();
            $component_registry = $container->get('component_registry');
            
            if ($component_registry && method_exists($component_registry, 'register_atom')) {
                $component_registry->register_atom('quality_summary_card', 'QCC_Quality_Summary_Card', array(
                    'translator'
                ));
                
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log('QCC: Quality Summary Card Atom registered successfully');
                }
            }
        } catch (Exception $e) {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC: Failed to register Quality Summary Card: ' . $e->getMessage());
            }
        }
    }
});
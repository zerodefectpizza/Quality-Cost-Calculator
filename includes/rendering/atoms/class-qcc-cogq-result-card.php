<?php
/**
 * QCC COGQ Result Card Atom
 * 
 * Spezialisierte Atomic Component für Cost of Good Quality (COGQ) Darstellung.
 * Grünes Design mit Prevention und Appraisal Kosten.
 * 
 * DATEI SPEICHERN ALS:
 * includes/rendering/atoms/class-qcc-cogq-result-card.php
 * 
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_COGQ_Result_Card extends QCC_Base_Atom {
    
    /**
     * Component name
     * @var string
     */
    protected $component_name = 'cogq_result_card';
    
    /**
     * Default component data
     * @var array
     */
    protected $default_data = array(
        'title' => 'Cost of Good Quality (COGQ)',
        'icon' => '✅',
        'color_scheme' => 'green',
        'prevention_cost' => 0,
        'appraisal_cost' => 0,
        'total_cogq' => 0,
        'percentage' => 0,
        'currency' => 'EUR',
        'show_breakdown' => true,
        'show_percentage' => true,
        'animated' => true,
        'size' => 'normal' // compact, normal, large
    );
    
    /**
     * Render COGQ result card
     * 
     * @param array $data Component data
     * @param array $attributes HTML attributes
     * @return string Rendered HTML
     */
    public function render($data = array(), $attributes = array()) {
        $data = array_merge($this->default_data, $data);
        $attributes = array_merge(array(
            'class' => 'qcc-cogq-result-card qcc-result-card--green',
            'data-component' => 'cogq_result_card',
            'data-animated' => $data['animated'] ? 'true' : 'false'
        ), $attributes);
        
        $currency_symbol = $this->get_currency_symbol($data['currency']);
        $card_id = 'qcc-cogq-card-' . uniqid();
        
        ob_start();
        ?>
        <div <?php echo $this->render_attributes($attributes); ?> id="<?php echo esc_attr($card_id); ?>">
            
            <!-- Card Header -->
            <div class="qcc-result-card__header qcc-cogq-header">
                <div class="qcc-result-card__icon">
                    <?php echo esc_html($data['icon']); ?>
                </div>
                <div class="qcc-result-card__title-area">
                    <h4 class="qcc-result-card__title">
                        <?php echo esc_html($data['title']); ?>
                    </h4>
                    <?php if ($data['show_percentage']): ?>
                    <div class="qcc-result-card__percentage" data-update="cogq_percentage">
                        <?php echo esc_html(number_format($data['percentage'], 1)); ?>%
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Card Body -->
            <div class="qcc-result-card__body">
                
                <!-- Total COGQ Display -->
                <div class="qcc-result-card__total">
                    <div class="qcc-result-card__total-label">
                        Total COGQ
                    </div>
                    <div class="qcc-result-card__total-value qcc-cogq-total" data-update="total_cogq">
                        <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                        <span class="qcc-amount"><?php echo esc_html(number_format($data['total_cogq'], 2)); ?></span>
                    </div>
                </div>
                
                <?php if ($data['show_breakdown']): ?>
                <!-- COGQ Breakdown -->
                <div class="qcc-result-card__breakdown">
                    
                    <!-- Prevention Costs -->
                    <div class="qcc-breakdown-item qcc-prevention-item">
                        <div class="qcc-breakdown-item__label">
                            <span class="qcc-breakdown-icon">🛡️</span>
                            Prevention Costs
                        </div>
                        <div class="qcc-breakdown-item__value" data-update="prevention_cost">
                            <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                            <span class="qcc-amount"><?php echo esc_html(number_format($data['prevention_cost'], 2)); ?></span>
                        </div>
                        <div class="qcc-breakdown-item__bar">
                            <div class="qcc-bar qcc-bar--prevention" 
                                 style="width: <?php echo $data['total_cogq'] > 0 ? esc_attr(($data['prevention_cost'] / $data['total_cogq']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Appraisal Costs -->
                    <div class="qcc-breakdown-item qcc-appraisal-item">
                        <div class="qcc-breakdown-item__label">
                            <span class="qcc-breakdown-icon">🔍</span>
                            Appraisal Costs
                        </div>
                        <div class="qcc-breakdown-item__value" data-update="appraisal_cost">
                            <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                            <span class="qcc-amount"><?php echo esc_html(number_format($data['appraisal_cost'], 2)); ?></span>
                        </div>
                        <div class="qcc-breakdown-item__bar">
                            <div class="qcc-bar qcc-bar--appraisal" 
                                 style="width: <?php echo $data['total_cogq'] > 0 ? esc_attr(($data['appraisal_cost'] / $data['total_cogq']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Card Footer (optional insights) -->
            <div class="qcc-result-card__footer qcc-cogq-footer">
                <div class="qcc-insight">
                    <span class="qcc-insight__icon">💡</span>
                    <span class="qcc-insight__text">Investment in quality prevention</span>
                </div>
            </div>
            
        </div>
        
        <!-- COGQ Card CSS -->
        <style>
        .qcc-cogq-result-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            max-width: 100%;
            margin: 0;
        }
        
        .qcc-cogq-result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .qcc-cogq-header {
            padding: 20px 20px 10px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .qcc-result-card__icon {
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
        
        .qcc-result-card__title-area {
            flex: 1;
        }
        
        .qcc-result-card__title {
            margin: 0 0 5px 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
            line-height: 1.3;
        }
        
        .qcc-result-card__percentage {
            font-size: 14px;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 8px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 500;
        }
        
        .qcc-result-card__body {
            padding: 0 20px 20px;
        }
        
        .qcc-result-card__total {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .qcc-result-card__total-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .qcc-result-card__total-value {
            font-size: 28px;
            font-weight: 700;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
            line-height: 1.2;
        }
        
        .qcc-result-card__breakdown {
            margin-top: 20px;
        }
        
        .qcc-breakdown-item {
            margin-bottom: 12px;
        }
        
        .qcc-breakdown-item__label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            margin-bottom: 4px;
            opacity: 0.95;
        }
        
        .qcc-breakdown-icon {
            font-size: 16px;
            min-width: 20px;
        }
        
        .qcc-breakdown-item__value {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
        }
        
        .qcc-breakdown-item__bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .qcc-bar {
            height: 100%;
            transition: width 0.8s ease;
            border-radius: 2px;
        }
        
        .qcc-bar--prevention {
            background: rgba(255, 255, 255, 0.8);
        }
        
        .qcc-bar--appraisal {
            background: rgba(255, 255, 255, 0.6);
        }
        
        .qcc-result-card__footer {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .qcc-insight {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            opacity: 0.9;
        }
        
        .qcc-insight__icon {
            font-size: 14px;
            min-width: 16px;
        }
        
        /* Update Animations */
        .qcc-cogq-result-card[data-animated="true"] .qcc-amount {
            transition: all 0.5s ease;
        }
        
        .qcc-cogq-result-card.qcc-updating .qcc-amount {
            transform: scale(1.1);
            color: #fff;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .qcc-cogq-header {
                padding: 15px 15px 10px;
                gap: 12px;
            }
            
            .qcc-result-card__icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .qcc-result-card__title {
                font-size: 16px;
            }
            
            .qcc-result-card__total-value {
                font-size: 24px;
            }
            
            .qcc-result-card__body {
                padding: 0 15px 15px;
            }
            
            .qcc-result-card__footer {
                padding: 12px 15px;
            }
        }
        </style>
        
        <?php
        return ob_get_clean();
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
 * Register COGQ Result Card with Component Registry
 */
add_action('qcc_register_components', function() {
    if (class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized()) {
        try {
            $container = QCC_Service_Container_Setup::get_container();
            $component_registry = $container->get('component_registry');
            
            if ($component_registry && method_exists($component_registry, 'register_atom')) {
                $component_registry->register_atom('cogq_result_card', 'QCC_COGQ_Result_Card', array(
                    'translator'
                ));
                
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log('QCC: COGQ Result Card Atom registered successfully');
                }
            }
        } catch (Exception $e) {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC: Failed to register COGQ Result Card: ' . $e->getMessage());
            }
        }
    }
});
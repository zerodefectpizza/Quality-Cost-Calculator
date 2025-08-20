<?php
/**
 * QCC COPQ Result Card Atom
 * 
 * Spezialisierte Atomic Component für Cost of Poor Quality (COPQ) Darstellung.
 * Rotes Design mit Internal und External Failure Kosten.
 * 
 * DATEI SPEICHERN ALS:
 * includes/rendering/atoms/class-qcc-copq-result-card.php
 * 
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_COPQ_Result_Card extends QCC_Base_Atom {
    
    /**
     * Component name
     * @var string
     */
    protected $component_name = 'copq_result_card';
    
    /**
     * Default component data
     * @var array
     */
    protected $default_data = array(
        'title' => 'Cost of Poor Quality (COPQ)',
        'icon' => '❌',
        'color_scheme' => 'red',
        'internal_failure_cost' => 0,
        'external_failure_cost' => 0,
        'total_copq' => 0,
        'percentage' => 0,
        'currency' => 'EUR',
        'show_breakdown' => true,
        'show_percentage' => true,
        'animated' => true,
        'size' => 'normal' // compact, normal, large
    );
    
    /**
     * Render COPQ result card
     * 
     * @param array $data Component data
     * @param array $attributes HTML attributes
     * @return string Rendered HTML
     */
    public function render($data = array(), $attributes = array()) {
        $data = array_merge($this->default_data, $data);
        $attributes = array_merge(array(
            'class' => 'qcc-copq-result-card qcc-result-card--red',
            'data-component' => 'copq_result_card',
            'data-animated' => $data['animated'] ? 'true' : 'false'
        ), $attributes);
        
        $currency_symbol = $this->get_currency_symbol($data['currency']);
        $card_id = 'qcc-copq-card-' . uniqid();
        
        ob_start();
        ?>
        <div <?php echo $this->render_attributes($attributes); ?> id="<?php echo esc_attr($card_id); ?>">
            
            <!-- Card Header -->
            <div class="qcc-result-card__header qcc-copq-header">
                <div class="qcc-result-card__icon">
                    <?php echo esc_html($data['icon']); ?>
                </div>
                <div class="qcc-result-card__title-area">
                    <h4 class="qcc-result-card__title">
                        <?php echo esc_html($data['title']); ?>
                    </h4>
                    <?php if ($data['show_percentage']): ?>
                    <div class="qcc-result-card__percentage" data-update="copq_percentage">
                        <?php echo esc_html(number_format($data['percentage'], 1)); ?>%
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Card Body -->
            <div class="qcc-result-card__body">
                
                <!-- Total COPQ Display -->
                <div class="qcc-result-card__total">
                    <div class="qcc-result-card__total-label">
                        Total COPQ
                    </div>
                    <div class="qcc-result-card__total-value qcc-copq-total" data-update="total_copq">
                        <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                        <span class="qcc-amount"><?php echo esc_html(number_format($data['total_copq'], 2)); ?></span>
                    </div>
                </div>
                
                <?php if ($data['show_breakdown']): ?>
                <!-- COPQ Breakdown -->
                <div class="qcc-result-card__breakdown">
                    
                    <!-- Internal Failure Costs -->
                    <div class="qcc-breakdown-item qcc-internal-item">
                        <div class="qcc-breakdown-item__label">
                            <span class="qcc-breakdown-icon">🔧</span>
                            Internal Failure
                        </div>
                        <div class="qcc-breakdown-item__value" data-update="internal_failure_cost">
                            <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                            <span class="qcc-amount"><?php echo esc_html(number_format($data['internal_failure_cost'], 2)); ?></span>
                        </div>
                        <div class="qcc-breakdown-item__bar">
                            <div class="qcc-bar qcc-bar--internal" 
                                 style="width: <?php echo $data['total_copq'] > 0 ? esc_attr(($data['internal_failure_cost'] / $data['total_copq']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- External Failure Costs -->
                    <div class="qcc-breakdown-item qcc-external-item">
                        <div class="qcc-breakdown-item__label">
                            <span class="qcc-breakdown-icon">🏢</span>
                            External Failure
                        </div>
                        <div class="qcc-breakdown-item__value" data-update="external_failure_cost">
                            <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                            <span class="qcc-amount"><?php echo esc_html(number_format($data['external_failure_cost'], 2)); ?></span>
                        </div>
                        <div class="qcc-breakdown-item__bar">
                            <div class="qcc-bar qcc-bar--external" 
                                 style="width: <?php echo $data['total_copq'] > 0 ? esc_attr(($data['external_failure_cost'] / $data['total_copq']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                </div>
                <?php endif; ?>
                
            </div>
            
            <!-- Card Footer (optional insights) -->
            <div class="qcc-result-card__footer qcc-copq-footer">
                <div class="qcc-insight">
                    <span class="qcc-insight__icon">⚠️</span>
                    <span class="qcc-insight__text">Cost of quality failures</span>
                </div>
            </div>
            
        </div>
        
        <!-- COPQ Card CSS -->
        <style>
        .qcc-copq-result-card {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            max-width: 100%;
            margin: 0;
        }
        
        .qcc-copq-result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }
        
        .qcc-copq-header {
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
        
        /* COPQ-specific bar colors */
        .qcc-bar--internal {
            background: rgba(255, 255, 255, 0.8);
        }
        
        .qcc-bar--external {
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
        .qcc-copq-result-card[data-animated="true"] .qcc-amount {
            transition: all 0.5s ease;
        }
        
        .qcc-copq-result-card.qcc-updating .qcc-amount {
            transform: scale(1.1);
            color: #fff;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .qcc-copq-header {
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
 * Register COPQ Result Card with Component Registry
 */
add_action('qcc_register_components', function() {
    if (class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized()) {
        try {
            $container = QCC_Service_Container_Setup::get_container();
            $component_registry = $container->get('component_registry');
            
            if ($component_registry && method_exists($component_registry, 'register_atom')) {
                $component_registry->register_atom('copq_result_card', 'QCC_COPQ_Result_Card', array(
                    'translator'
                ));
                
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log('QCC: COPQ Result Card Atom registered successfully');
                }
            }
        } catch (Exception $e) {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC: Failed to register COPQ Result Card: ' . $e->getMessage());
            }
        }
    }
});
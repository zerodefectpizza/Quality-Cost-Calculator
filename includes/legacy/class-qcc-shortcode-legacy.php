<?php
/**
 * Shortcode functionality for Quality Cost Calculator - ERWEITERT MIT DEUTSCHER SPRACHE
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode Class - Vollständig übersetzt für deutsche Sprache
 */
class QCC_Shortcode_Legacy {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('quality_cost_calculator', array($this, 'render_calculator'));
        
        // Debug logging
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log('QCC_Shortcode_Legacy initialized and shortcode registered', 'SHORTCODE');
        }
        
        // Verify shortcode registration
        if (shortcode_exists('quality_cost_calculator')) {
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log('Shortcode [quality_cost_calculator] successfully registered', 'SHORTCODE');
            }
        } else {
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log_error('Failed to register shortcode [quality_cost_calculator]');
            }
        }
    }
    
    /**
     * Render calculator shortcode
     */
    public function render_calculator($atts) {
        // Parse attributes with better defaults
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'language' => $this->get_default_language(),
            'currency' => $this->get_default_currency(),
            'unit' => $this->get_default_unit()
        ), $atts);
        
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log('Shortcode render_calculator called with attributes: ' . wp_json_encode($atts), 'SHORTCODE');
        }
        
        // Start output buffering
        ob_start();
        
        try {
            // Check if template file exists
            $template_file = $this->get_template_path();
            
            if ($template_file && file_exists($template_file)) {
                // Include the template
                include $template_file;
                if (class_exists('QCC_Debug_Logger')) {
                    QCC_Debug_Logger::log('Calculator template loaded from: ' . $template_file, 'SHORTCODE');
                }
            } else {
                // Use enhanced fallback HTML with German support
                echo $this->get_enhanced_fallback_calculator_html($atts);
                if (class_exists('QCC_Debug_Logger')) {
                    QCC_Debug_Logger::log('Using enhanced fallback calculator HTML with German support (template missing)', 'SHORTCODE');
                }
            }
            
        } catch (Exception $e) {
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log_error('Shortcode rendering failed: ' . $e->getMessage());
            }
            
            // Emergency fallback
            echo $this->get_emergency_fallback_html($atts, $e->getMessage());
        }
        
        return ob_get_clean();
    }
    
    /**
     * Get template path
     */
    private function get_template_path() {
        if (defined('QCC_PLUGIN_PATH')) {
            return QCC_PLUGIN_PATH . 'templates/calculator.php';
        }
        return false;
    }
    
    /**
     * Get default language
     */
    private function get_default_language() {
        if (function_exists('qcc_get_option')) {
            return qcc_get_option('default_language', 'en');
        }
        return get_option('qcc_default_language', 'en');
    }
    
    /**
     * Get default currency
     */
    private function get_default_currency() {
        if (function_exists('qcc_get_option')) {
            return qcc_get_option('default_currency', 'EUR');
        }
        return get_option('qcc_default_currency', 'EUR');
    }
    
    /**
     * Get default unit
     */
    private function get_default_unit() {
        if (function_exists('qcc_get_option')) {
            return qcc_get_option('default_unit', '1000000');
        }
        return get_option('qcc_default_unit', '1000000');
    }
    
    /**
     * Get enhanced fallback calculator HTML - ERWEITERT MIT DEUTSCHER SPRACHE
     */
    private function get_enhanced_fallback_calculator_html($atts) {
        $default_values = $this->get_default_values();
        $currency_symbol = $this->get_currency_symbol($atts['currency']);
        $unit_name = $this->get_unit_name($atts['unit'], $atts['language']);
        
        $html = '<div id="quality-cost-calculator" class="qcc-container" style="max-width: 1200px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.6;">';
        
        // Header with language-specific content
        $header_title = $this->get_translation($atts['language'], 'title');
        $header_subtitle = $this->get_translation($atts['language'], 'subtitle');
        
        $html .= '<div class="qcc-header" style="background: linear-gradient(135deg, #449775, #2c5f47); color: white; padding: 30px; text-align: center; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
        $html .= '<h1 style="margin: 0 0 10px 0; font-size: 2.5rem; font-weight: 600;">' . esc_html($header_title) . '</h1>';
        $html .= '<p style="margin: 0; font-size: 1.1rem; opacity: 0.9;">' . esc_html($header_subtitle) . '</p>';
        $html .= '</div>';
        
        // Controls section with German labels
        $html .= $this->get_controls_html($atts, $currency_symbol, $unit_name);
        
        // Input section with German translations
        $html .= $this->get_input_section_html($default_values, $currency_symbol, $unit_name, $atts['language']);
        
        // Results section with German translations
        $html .= $this->get_results_section_html($currency_symbol, $unit_name, $atts['language']);
        
        // Status message
        $html .= $this->get_status_message_html($atts);
        
        // Enhanced JavaScript with German support
        $html .= $this->get_enhanced_javascript_with_german($default_values, $atts);
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get controls HTML section - ERWEITERT MIT DEUTSCHER SPRACHE
     */
    private function get_controls_html($atts, $currency_symbol, $unit_name) {
        $html = '<div class="qcc-controls" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
        
        // Language selector with German
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-language" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">' . $this->get_translation($atts['language'], 'lang-label') . '</label>';
        $html .= '<select id="qcc-language" style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; background: white; transition: border-color 0.3s;">';
        $html .= '<option value="en"' . selected($atts['language'], 'en', false) . '>English</option>';
        $html .= '<option value="de"' . selected($atts['language'], 'de', false) . '>Deutsch</option>';
        $html .= '<option value="fr"' . selected($atts['language'], 'fr', false) . '>Français</option>';
        $html .= '<option value="es"' . selected($atts['language'], 'es', false) . '>Español</option>';
        $html .= '<option value="zh"' . selected($atts['language'], 'zh', false) . '>中文</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        // Currency selector
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-currency" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">' . $this->get_translation($atts['language'], 'currency-label') . '</label>';
        $html .= '<select id="qcc-currency" style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; background: white; transition: border-color 0.3s;">';
        $html .= '<option value="EUR"' . selected($atts['currency'], 'EUR', false) . '>Euro (€)</option>';
        $html .= '<option value="USD"' . selected($atts['currency'], 'USD', false) . '>US-Dollar ($)</option>';
        $html .= '<option value="CNY"' . selected($atts['currency'], 'CNY', false) . '>Renminbi (¥)</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        // Unit selector
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-unit" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">' . $this->get_translation($atts['language'], 'unit-label') . '</label>';
        $html .= '<select id="qcc-unit" style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; background: white; transition: border-color 0.3s;">';
        $html .= '<option value="1000000"' . selected($atts['unit'], '1000000', false) . '>' . $this->get_translation($atts['language'], 'millions') . '</option>';
        $html .= '<option value="1000000000"' . selected($atts['unit'], '1000000000', false) . '>' . $this->get_translation($atts['language'], 'billions') . '</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        // Opportunity costs toggle
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-opportunity-toggle" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">' . $this->get_translation($atts['language'], 'opportunity-toggle-label') . '</label>';
        $html .= '<select id="qcc-opportunity-toggle" style="width: 100%; padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px; background: white; transition: border-color 0.3s;">';
        $html .= '<option value="disabled">' . $this->get_translation($atts['language'], 'disabled') . '</option>';
        $html .= '<option value="enabled">' . $this->get_translation($atts['language'], 'enabled') . '</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get input section HTML - ERWEITERT MIT DEUTSCHER SPRACHE
     */
    private function get_input_section_html($default_values, $currency_symbol, $unit_name, $language) {
        $html = '<div class="qcc-input-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">';
        $html .= '<h2 style="color: #449775; margin-bottom: 20px; border-bottom: 3px solid #449775; padding-bottom: 10px; font-size: 1.4rem; font-weight: 600;">' . $this->get_translation($language, 'input-title') . '</h2>';
        
        // Basic inputs with German labels
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px;">';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-revenue" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">' . $this->get_translation($language, 'revenue-label') . ' (' . $currency_symbol . ' ' . $unit_name . '):</label>';
        $html .= '<input type="number" id="qcc-revenue" value="' . $default_values['revenue'] . '" step="0.01" min="0" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 16px; transition: border-color 0.3s;">';
        $html .= '</div>';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-quality-percentage" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">' . $this->get_translation($language, 'quality-percentage-label') . '</label>';
        $html .= '<input type="number" id="qcc-quality-percentage" value="' . $default_values['quality_percentage'] . '" step="0.01" min="0" max="100" style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 16px; transition: border-color 0.3s;">';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Cost distribution with enhanced COGQ/COPQ visualization and German labels
        $html .= '<div class="qcc-cost-distribution" style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-top: 20px;">';
        $html .= '<h3 style="margin-bottom: 15px; color: #449775; font-weight: 600; text-align: center;">' . $this->get_translation($language, 'cost-distribution-title') . '</h3>';
        
        // COGQ Section - Kosten guter Qualität
        $html .= '<div style="background: linear-gradient(135deg, #e8f5e8, #f0f8f0); border: 2px solid #28a745; border-radius: 8px; padding: 15px; margin-bottom: 15px;">';
        $html .= '<h4 style="color: #28a745; margin-bottom: 10px; text-align: center;">' . $this->get_translation($language, 'cogq-title') . '</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-prevention" style="display: block; margin-bottom: 5px; font-weight: 500; color: #28a745;">' . $this->get_translation($language, 'prevention-label') . '</label>';
        $html .= '<input type="number" id="qcc-prevention" value="' . $default_values['prevention'] . '" step="0.01" min="0" max="100" style="width: 100%; padding: 10px; border: 2px solid #28a745; border-radius: 4px; font-size: 14px;">';
        $html .= '<small style="color: #666; font-size: 12px;">' . $this->get_translation($language, 'prevention-description') . '</small>';
        $html .= '</div>';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-appraisal" style="display: block; margin-bottom: 5px; font-weight: 500; color: #17a2b8;">' . $this->get_translation($language, 'appraisal-label') . '</label>';
        $html .= '<input type="number" id="qcc-appraisal" value="' . $default_values['appraisal'] . '" step="0.01" min="0" max="100" style="width: 100%; padding: 10px; border: 2px solid #17a2b8; border-radius: 4px; font-size: 14px;">';
        $html .= '<small style="color: #666; font-size: 12px;">' . $this->get_translation($language, 'appraisal-description') . '</small>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // COPQ Section - Kosten schlechter Qualität
        $html .= '<div style="background: linear-gradient(135deg, #fdf2e9, #fef8f8); border: 2px solid #dc3545; border-radius: 8px; padding: 15px;">';
        $html .= '<h4 style="color: #dc3545; margin-bottom: 10px; text-align: center;">' . $this->get_translation($language, 'copq-title') . '</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-internal-defect" style="display: block; margin-bottom: 5px; font-weight: 500; color: #ffc107;">' . $this->get_translation($language, 'internal-defect-label') . '</label>';
        $html .= '<input type="number" id="qcc-internal-defect" value="' . $default_values['internal_defect'] . '" step="0.01" min="0" max="100" style="width: 100%; padding: 10px; border: 2px solid #ffc107; border-radius: 4px; font-size: 14px;">';
        $html .= '<small style="color: #666; font-size: 12px;">' . $this->get_translation($language, 'internal-description') . '</small>';
        $html .= '</div>';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-external-defect" style="display: block; margin-bottom: 5px; font-weight: 500; color: #dc3545;">' . $this->get_translation($language, 'external-defect-label') . '</label>';
        $html .= '<input type="number" id="qcc-external-defect" value="' . $default_values['external_defect'] . '" step="0.01" min="0" max="100" style="width: 100%; padding: 10px; border: 2px solid #dc3545; border-radius: 4px; font-size: 14px;">';
        $html .= '<small style="color: #666; font-size: 12px;">' . $this->get_translation($language, 'external-description') . '</small>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div id="qcc-percentage-error" style="color: #dc3545; margin-top: 15px; display: none; font-weight: bold; background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; text-align: center;">⚠️ ' . $this->get_translation($language, 'percentage-error') . '</div>';
        $html .= '</div>';
        
        // Opportunity costs section (initially hidden) with German labels
        $html .= '<div id="qcc-opportunity-section" style="border: 2px solid #6c757d; border-radius: 8px; padding: 20px; margin-top: 20px; opacity: 0.5; display: none;">';
        $html .= '<h3 style="color: #6c757d; margin-bottom: 15px; text-align: center;">' . $this->get_translation($language, 'opportunity-factors-title') . '</h3>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        $opportunity_costs = array(
            'lost-sales' => array($this->get_translation($language, 'lost-sales-label'), $default_values['lost_sales'], $this->get_translation($language, 'lost-sales-description')),
            'customer-churn' => array($this->get_translation($language, 'customer-churn-label'), $default_values['customer_churn'], $this->get_translation($language, 'customer-churn-description')),
            'market-share-loss' => array($this->get_translation($language, 'market-share-loss-label'), $default_values['market_share_loss'], $this->get_translation($language, 'market-share-description')),
            'productivity-loss' => array($this->get_translation($language, 'productivity-loss-label'), $default_values['productivity_loss'], $this->get_translation($language, 'productivity-description'))
        );
        
        foreach ($opportunity_costs as $id => $data) {
            $html .= '<div class="qcc-input-group">';
            $html .= '<label for="qcc-' . $id . '" style="display: block; margin-bottom: 5px; font-weight: 500; color: #6c757d;">' . $data[0] . '</label>';
            $html .= '<input type="number" id="qcc-' . $id . '" value="' . $data[1] . '" step="0.01" min="0" max="100" style="width: 100%; padding: 10px; border: 2px solid #6c757d; border-radius: 4px; font-size: 14px;">';
            $html .= '<small style="color: #666; font-size: 12px;">' . $data[2] . '</small>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get results section HTML - ERWEITERT MIT DEUTSCHER SPRACHE
     */
    private function get_results_section_html($currency_symbol, $unit_name, $language) {
        $html = '<div class="qcc-results-section" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">';
        $html .= '<h2 style="color: #449775; margin-bottom: 20px; border-bottom: 3px solid #449775; padding-bottom: 10px; font-size: 1.4rem; font-weight: 600;">' . $this->get_translation($language, 'results-title') . ' (' . $currency_symbol . ' ' . $unit_name . ')</h2>';
        
        // COGQ Results - Ergebnisse für Kosten guter Qualität
        $html .= '<div style="background: #e8f5e8; border: 2px solid #28a745; border-radius: 8px; padding: 20px; margin-bottom: 20px;">';
        $html .= '<h3 style="color: #28a745; margin-bottom: 15px; text-align: center;">' . $this->get_translation($language, 'cogq-results-title') . '</h3>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        $html .= '<div style="padding: 15px; background: white; border-radius: 5px; border-left: 4px solid #28a745; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px; color: #333;">' . $this->get_translation($language, 'prevention-cost-label') . '</div>';
        $html .= '<div id="qcc-prevention-cost" style="font-size: 1.4rem; font-weight: bold; color: #28a745;">0.00</div>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 15px; background: white; border-radius: 5px; border-left: 4px solid #17a2b8; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px; color: #333;">' . $this->get_translation($language, 'appraisal-cost-label') . '</div>';
        $html .= '<div id="qcc-appraisal-cost" style="font-size: 1.4rem; font-weight: bold; color: #17a2b8;">0.00</div>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 15px; background: #28a745; color: white; border-radius: 5px; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px;">' . $this->get_translation($language, 'total-cogq-label') . '</div>';
        $html .= '<div id="qcc-total-cogq" style="font-size: 1.6rem; font-weight: bold;">0.00</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // COPQ Results - Ergebnisse für Kosten schlechter Qualität
        $html .= '<div style="background: #fdf2e9; border: 2px solid #dc3545; border-radius: 8px; padding: 20px; margin-bottom: 20px;">';
        $html .= '<h3 style="color: #dc3545; margin-bottom: 15px; text-align: center;">' . $this->get_translation($language, 'copq-results-title') . '</h3>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';
        
        $html .= '<div style="padding: 15px; background: white; border-radius: 5px; border-left: 4px solid #ffc107; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px; color: #333;">' . $this->get_translation($language, 'internal-defect-cost-label') . '</div>';
        $html .= '<div id="qcc-internal-defect-cost" style="font-size: 1.4rem; font-weight: bold; color: #ffc107;">0.00</div>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 15px; background: white; border-radius: 5px; border-left: 4px solid #dc3545; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px; color: #333;">' . $this->get_translation($language, 'external-defect-cost-label') . '</div>';
        $html .= '<div id="qcc-external-defect-cost" style="font-size: 1.4rem; font-weight: bold; color: #dc3545;">0.00</div>';
        $html .= '</div>';
        
        $html .= '<div style="padding: 15px; background: #dc3545; color: white; border-radius: 5px; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 8px;">' . $this->get_translation($language, 'total-copq-label') . '</div>';
        $html .= '<div id="qcc-total-copq" style="font-size: 1.6rem; font-weight: bold;">0.00</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Total Quality Cost with German labels
        $html .= '<div style="background: linear-gradient(135deg, #449775, #2c5f47); color: white; padding: 20px; border-radius: 8px; text-align: center;">';
        $html .= '<h3 style="color: white; margin-bottom: 15px; border-bottom: 2px solid rgba(255,255,255,0.3); padding-bottom: 10px;">' . $this->get_translation($language, 'total-quality-cost-title') . '</h3>';
        $html .= '<div style="font-size: 2rem; font-weight: bold;" id="qcc-total-quality-cost">0.00</div>';
        $html .= '<div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">' . $this->get_translation($language, 'revenue-percentage-label') . ' <span id="qcc-percentage-of-revenue">0.0%</span></div>';
        $html .= '</div>';
        
        // Opportunity costs results (hidden initially) with German labels
        $html .= '<div id="qcc-opportunity-results" style="background: #f8f9fa; border: 2px solid #6c757d; border-radius: 8px; padding: 20px; margin-top: 20px; display: none;">';
        $html .= '<h3 style="color: #6c757d; margin-bottom: 15px; text-align: center;">' . $this->get_translation($language, 'opportunity-costs-title') . '</h3>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">';
        
        $opportunity_results = array(
            'lost-sales-cost' => $this->get_translation($language, 'lost-sales-cost-label'),
            'customer-churn-cost' => $this->get_translation($language, 'customer-churn-cost-label'),
            'market-share-cost' => $this->get_translation($language, 'market-share-cost-label'),
            'productivity-cost' => $this->get_translation($language, 'productivity-cost-label')
        );
        
        foreach ($opportunity_results as $id => $label) {
            $html .= '<div style="padding: 10px; background: white; border-radius: 5px; text-align: center;">';
            $html .= '<div style="font-weight: bold; margin-bottom: 5px; font-size: 12px; color: #666;">' . $label . '</div>';
            $html .= '<div id="qcc-' . $id . '" style="font-size: 1.1rem; font-weight: bold; color: #6c757d;">0.00</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div style="margin-top: 15px; padding: 15px; background: #6c757d; color: white; border-radius: 5px; text-align: center;">';
        $html .= '<div style="font-weight: bold; margin-bottom: 5px;">' . $this->get_translation($language, 'total-opportunity-cost-label') . '</div>';
        $html .= '<div id="qcc-total-opportunity-cost" style="font-size: 1.4rem; font-weight: bold;">0.00</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Action buttons with German labels
        $html .= '<div style="text-align: center; margin: 20px 0;">';
        $html .= '<button onclick="QCC.calculate()" style="background: #449775; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin: 0 10px; transition: all 0.3s;" onmouseover="this.style.background=\'#357a63\'; this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.background=\'#449775\'; this.style.transform=\'translateY(0)\'">🔄 ' . $this->get_translation($language, 'calculate-button') . '</button>';
        $html .= '<button onclick="QCC.reset()" style="background: #6c757d; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin: 0 10px; transition: all 0.3s;" onmouseover="this.style.background=\'#545b62\'; this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.background=\'#6c757d\'; this.style.transform=\'translateY(0)\'">↺ ' . $this->get_translation($language, 'reset-button') . '</button>';
        $html .= '<button onclick="QCC.exportCSV()" style="background: #17a2b8; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin: 0 10px; transition: all 0.3s;" onmouseover="this.style.background=\'#138496\'; this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.background=\'#17a2b8\'; this.style.transform=\'translateY(0)\'">📄 ' . $this->get_translation($language, 'export-csv') . '</button>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get status message HTML
     */
    private function get_status_message_html($atts) {
        $plugin_status = $this->get_plugin_status();
        
        $html = '<div style="background: ' . ($plugin_status['is_working'] ? '#d4edda' : '#f8d7da') . '; border: 1px solid ' . ($plugin_status['is_working'] ? '#c3e6cb' : '#f5c6cb') . '; color: ' . ($plugin_status['is_working'] ? '#155724' : '#721c24') . '; padding: 15px; border-radius: 8px; margin: 20px 0;">';
        $html .= '<h4 style="margin-bottom: 10px;">' . ($plugin_status['is_working'] ? '🎉 ' . $this->get_translation($atts['language'], 'status-operational') : '⚠️ ' . $this->get_translation($atts['language'], 'status-issues')) . '</h4>';
        $html .= '<p><strong>' . $plugin_status['message'] . '</strong></p>';
        
        if (!empty($plugin_status['details'])) {
            $html .= '<p><strong>' . $this->get_translation($atts['language'], 'details-label') . '</strong></p>';
            $html .= '<ul style="margin: 5px 0 5px 20px;">';
            foreach ($plugin_status['details'] as $detail) {
                $html .= '<li>' . esc_html($detail) . '</li>';
            }
            $html .= '</ul>';
        }
        
        $html .= '<p style="margin-top: 10px; font-size: 14px; opacity: 0.8;">';
        $html .= $this->get_translation($atts['language'], 'version-label') . ': ' . (defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : '1.1.0') . ' | ';
        $html .= $this->get_translation($atts['language'], 'language-label') . ': ' . strtoupper($atts['language']) . ' | ';
        $html .= $this->get_translation($atts['language'], 'currency-label') . ': ' . $atts['currency'] . ' | ';
        $html .= $this->get_translation($atts['language'], 'mode-label') . ': ' . ($plugin_status['using_fallback'] ? $this->get_translation($atts['language'], 'fallback-mode') : $this->get_translation($atts['language'], 'template-mode'));
        $html .= '</p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get enhanced JavaScript with German support - ERWEITERT MIT DEUTSCHER SPRACHE
     */
    private function get_enhanced_javascript_with_german($default_values, $atts) {
        $translations_json = wp_json_encode($this->get_all_translations());
        
        $html = '<script>
        // Enhanced QCC Calculator JavaScript mit vollständiger deutscher Sprachunterstützung
        const QCC = {
            currency: "' . $this->get_currency_symbol($atts['currency']) . '",
            unit: parseFloat("' . $atts['unit'] . '"),
            unitName: "' . $this->get_unit_name($atts['unit'], $atts['language']) . '",
            opportunityEnabled: false,
            initialized: false,
            currentLanguage: "' . $atts['language'] . '",
            translations: ' . $translations_json . ',
            
            init: function() {
                console.log("QCC Calculator mit deutscher Sprachunterstützung wird initialisiert...");
                
                try {
                    this.bindEvents();
                    this.setupOpportunityToggle();
                    this.calculate();
                    this.initialized = true;
                    
                    console.log("QCC Calculator erfolgreich initialisiert!");
                    
                    // Show initialization success in current language
                    this.showMessage(this.t("calculator-ready"), "success");
                    
                } catch (error) {
                    console.error("QCC Initialisierungsfehler:", error);
                    this.showMessage(this.t("warning-features") + " " + error.message, "warning");
                }
            },
            
            // Translation helper function
            t: function(key) {
                return this.translations[this.currentLanguage] && this.translations[this.currentLanguage][key] 
                    ? this.translations[this.currentLanguage][key] 
                    : (this.translations["en"][key] || key);
            },
            
            bindEvents: function() {
                const inputs = ["revenue", "quality-percentage", "prevention", "appraisal", "internal-defect", "external-defect"];
                const opportunityInputs = ["lost-sales", "customer-churn", "market-share-loss", "productivity-loss"];
                const selects = ["currency", "unit", "language"];
                
                // Bind main inputs
                inputs.forEach(id => {
                    const element = document.getElementById("qcc-" + id);
                    if (element) {
                        element.addEventListener("input", () => this.calculate());
                        element.addEventListener("focus", (e) => this.highlightInput(e.target, true));
                        element.addEventListener("blur", (e) => this.highlightInput(e.target, false));
                    }
                });
                
                // Bind opportunity inputs
                opportunityInputs.forEach(id => {
                    const element = document.getElementById("qcc-" + id);
                    if (element) {
                        element.addEventListener("input", () => this.calculate());
                    }
                });
                
                // Bind selects
                selects.forEach(id => {
                    const element = document.getElementById("qcc-" + id);
                    if (element) {
                        if (id === "language") {
                            element.addEventListener("change", () => this.changeLanguage());
                        } else {
                            element.addEventListener("change", () => this.updateSettings());
                        }
                    }
                });
                
                // Bind opportunity toggle
                const opportunityToggle = document.getElementById("qcc-opportunity-toggle");
                if (opportunityToggle) {
                    opportunityToggle.addEventListener("change", () => this.toggleOpportunity());
                }
            },
            
            changeLanguage: function() {
                const languageSelect = document.getElementById("qcc-language");
                if (languageSelect) {
                    this.currentLanguage = languageSelect.value;
                    this.updateAllLabels();
                    this.calculate();
                    this.showMessage(this.t("language-changed"), "info");
                }
            },
            
            updateAllLabels: function() {
                // Update all text elements with new language
                const elements = {
                    "qcc-title": "title",
                    "qcc-subtitle": "subtitle",
                    "qcc-input-title": "input-title",
                    "qcc-results-title": "results-title",
                    "qcc-revenue-label": "revenue-label",
                    "qcc-quality-percentage-label": "quality-percentage-label",
                    "qcc-prevention-label": "prevention-label",
                    "qcc-appraisal-label": "appraisal-label",
                    "qcc-internal-defect-label": "internal-defect-label",
                    "qcc-external-defect-label": "external-defect-label"
                };
                
                Object.keys(elements).forEach(elementId => {
                    const element = document.getElementById(elementId);
                    if (element) {
                        element.textContent = this.t(elements[elementId]);
                    }
                });
            },
            
            highlightInput: function(element, highlight) {
                if (highlight) {
                    element.style.borderColor = "#449775";
                    element.style.boxShadow = "0 0 0 2px rgba(68, 151, 117, 0.2)";
                } else {
                    element.style.borderColor = "#e0e0e0";
                    element.style.boxShadow = "none";
                }
            },
            
            setupOpportunityToggle: function() {
                // Initialize opportunity costs as disabled
                const opportunitySection = document.getElementById("qcc-opportunity-section");
                const opportunityResults = document.getElementById("qcc-opportunity-results");
                
                if (opportunitySection) {
                    opportunitySection.style.display = "none";
                }
                if (opportunityResults) {
                    opportunityResults.style.display = "none";
                }
            },
            
            toggleOpportunity: function() {
                const toggle = document.getElementById("qcc-opportunity-toggle");
                const opportunitySection = document.getElementById("qcc-opportunity-section");
                const opportunityResults = document.getElementById("qcc-opportunity-results");
                
                this.opportunityEnabled = toggle && toggle.value === "enabled";
                
                if (this.opportunityEnabled) {
                    if (opportunitySection) {
                        opportunitySection.style.display = "block";
                        opportunitySection.style.opacity = "1";
                    }
                    if (opportunityResults) {
                        opportunityResults.style.display = "block";
                    }
                    this.showMessage(this.t("opportunity-enabled"), "info");
                } else {
                    if (opportunitySection) {
                        opportunitySection.style.display = "none";
                    }
                    if (opportunityResults) {
                        opportunityResults.style.display = "none";
                    }
                    this.showMessage(this.t("opportunity-disabled"), "info");
                }
                
                this.calculate();
            },
            
            updateSettings: function() {
                const currencySelect = document.getElementById("qcc-currency");
                const unitSelect = document.getElementById("qcc-unit");
                
                if (currencySelect) {
                    this.currency = currencySelect.value === "EUR" ? "€" : (currencySelect.value === "USD" ? "$" : "¥");
                }
                
                if (unitSelect) {
                    this.unit = parseFloat(unitSelect.value);
                    this.unitName = unitSelect.options[unitSelect.selectedIndex].text;
                }
                
                this.calculate();
                this.showMessage(this.t("settings-updated"), "success");
            },
            
            calculate: function() {
                try {
                    const revenue = parseFloat(document.getElementById("qcc-revenue").value) || 0;
                    const qualityPercentage = parseFloat(document.getElementById("qcc-quality-percentage").value) || 0;
                    const prevention = parseFloat(document.getElementById("qcc-prevention").value) || 0;
                    const appraisal = parseFloat(document.getElementById("qcc-appraisal").value) || 0;
                    const internalDefect = parseFloat(document.getElementById("qcc-internal-defect").value) || 0;
                    const externalDefect = parseFloat(document.getElementById("qcc-external-defect").value) || 0;
                    
                    // Validate percentages
                    const total = prevention + appraisal + internalDefect + externalDefect;
                    const isValid = Math.abs(total - 100) < 0.01;
                    
                    this.showPercentageValidation(isValid, total);
                    
                    if (!isValid) {
                        this.clearResults();
                        return;
                    }
                    
                    // Calculate main values
                    const revenueInUnit = revenue * this.unit;
                    const totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
                    
                    const preventionCost = (totalQualityCost * prevention) / 100;
                    const appraisalCost = (totalQualityCost * appraisal) / 100;
                    const internalDefectCost = (totalQualityCost * internalDefect) / 100;
                    const externalDefectCost = (totalQualityCost * externalDefect) / 100;
                    
                    // COGQ and COPQ totals
                    const totalCOGQ = preventionCost + appraisalCost;
                    const totalCOPQ = internalDefectCost + externalDefectCost;
                    
                    // Update main results
                    this.updateResult("qcc-prevention-cost", preventionCost);
                    this.updateResult("qcc-appraisal-cost", appraisalCost);
                    this.updateResult("qcc-internal-defect-cost", internalDefectCost);
                    this.updateResult("qcc-external-defect-cost", externalDefectCost);
                    this.updateResult("qcc-total-cogq", totalCOGQ);
                    this.updateResult("qcc-total-copq", totalCOPQ);
                    this.updateResult("qcc-total-quality-cost", totalQualityCost);
                    
                    // Calculate percentage of revenue
                    const percentageOfRevenue = totalQualityCost > 0 ? ((totalQualityCost / revenueInUnit) * 100).toFixed(1) : "0.0";
                    const percentageElement = document.getElementById("qcc-percentage-of-revenue");
                    if (percentageElement) {
                        percentageElement.textContent = percentageOfRevenue + "%";
                    }
                    
                    // Handle opportunity costs if enabled
                    if (this.opportunityEnabled) {
                        this.calculateOpportunityCosts(revenueInUnit, totalQualityCost);
                    }
                    
                    // Show calculation summary in current language
                    this.showCalculationSummary(totalQualityCost, totalCOGQ, totalCOPQ, percentageOfRevenue);
                    
                } catch (error) {
                    console.error("Berechnungsfehler:", error);
                    this.showMessage(this.t("calculation-error") + ": " + error.message, "error");
                }
            },
            
            calculateOpportunityCosts: function(revenue, totalQualityCost) {
                const lostSales = parseFloat(document.getElementById("qcc-lost-sales").value) || 0;
                const customerChurn = parseFloat(document.getElementById("qcc-customer-churn").value) || 0;
                const marketShareLoss = parseFloat(document.getElementById("qcc-market-share-loss").value) || 0;
                const productivityLoss = parseFloat(document.getElementById("qcc-productivity-loss").value) || 0;
                
                const lostSalesCost = (revenue * lostSales) / 100;
                const customerChurnCost = (revenue * customerChurn) / 100;
                const marketShareCost = (revenue * marketShareLoss) / 100;
                const productivityCost = (revenue * productivityLoss) / 100;
                const totalOpportunityCost = lostSalesCost + customerChurnCost + marketShareCost + productivityCost;
                
                this.updateResult("qcc-lost-sales-cost", lostSalesCost);
                this.updateResult("qcc-customer-churn-cost", customerChurnCost);
                this.updateResult("qcc-market-share-cost", marketShareCost);
                this.updateResult("qcc-productivity-cost", productivityCost);
                this.updateResult("qcc-total-opportunity-cost", totalOpportunityCost);
            },
            
            showPercentageValidation: function(isValid, total) {
                const errorElement = document.getElementById("qcc-percentage-error");
                const costSection = document.querySelector(".qcc-cost-distribution");
                
                if (!isValid) {
                    if (errorElement) {
                        errorElement.style.display = "block";
                        errorElement.innerHTML = `⚠️ ${this.t("percentage-error-detailed").replace("{total}", total.toFixed(1))}`;
                    }
                    if (costSection) {
                        costSection.style.borderColor = "#dc3545";
                        costSection.style.backgroundColor = "#fdf2f2";
                    }
                } else {
                    if (errorElement) {
                        errorElement.style.display = "none";
                    }
                    if (costSection) {
                        costSection.style.borderColor = "#e0e0e0";
                        costSection.style.backgroundColor = "transparent";
                    }
                }
            },
            
            updateResult: function(elementId, value) {
                const element = document.getElementById(elementId);
                if (element) {
                    const formattedValue = (value / this.unit).toFixed(2);
                    element.textContent = formattedValue;
                    
                    // Add animation effect
                    element.style.transition = "all 0.3s ease";
                    element.style.transform = "scale(1.05)";
                    setTimeout(() => {
                        element.style.transform = "scale(1)";
                    }, 300);
                }
            },
            
            clearResults: function() {
                const resultIds = [
                    "qcc-prevention-cost", "qcc-appraisal-cost", "qcc-internal-defect-cost", 
                    "qcc-external-defect-cost", "qcc-total-cogq", "qcc-total-copq", "qcc-total-quality-cost",
                    "qcc-lost-sales-cost", "qcc-customer-churn-cost", "qcc-market-share-cost", 
                    "qcc-productivity-cost", "qcc-total-opportunity-cost"
                ];
                
                resultIds.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = "0.00";
                });
                
                const percentageElement = document.getElementById("qcc-percentage-of-revenue");
                if (percentageElement) percentageElement.textContent = "0.0%";
            },
            
            reset: function() {
                if (!confirm(this.t("reset-confirmation"))) {
                    return;
                }
                
                const defaults = ' . wp_json_encode($default_values) . ';
                
                Object.keys(defaults).forEach(key => {
                    const element = document.getElementById("qcc-" + key.replace("_", "-"));
                    if (element) {
                        element.value = defaults[key];
                    }
                });
                
                // Reset selects
                const languageSelect = document.getElementById("qcc-language");
                const currencySelect = document.getElementById("qcc-currency");
                const unitSelect = document.getElementById("qcc-unit");
                const opportunityToggle = document.getElementById("qcc-opportunity-toggle");
                
                if (languageSelect) languageSelect.value = "' . $atts['language'] . '";
                if (currencySelect) currencySelect.value = "' . $atts['currency'] . '";
                if (unitSelect) unitSelect.value = "' . $atts['unit'] . '";
                if (opportunityToggle) {
                    opportunityToggle.value = "disabled";
                    this.toggleOpportunity();
                }
                
                this.calculate();
                this.showMessage(this.t("reset-success"), "success");
            },
            
            exportCSV: function() {
                try {
                    const data = this.gatherExportData();
                    const csvContent = this.formatCSV(data);
                    
                    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
                    const link = document.createElement("a");
                    
                    if (link.download !== undefined) {
                        const url = URL.createObjectURL(blob);
                        link.setAttribute("href", url);
                        link.setAttribute("download", "qualitaetskosten_" + this.currentLanguage + "_" + new Date().toISOString().split("T")[0] + ".csv");
                        link.style.visibility = "hidden";
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        this.showMessage(this.t("export-success"), "success");
                    }
                } catch (error) {
                    console.error("CSV Export Fehler:", error);
                    this.showMessage(this.t("export-failed") + ": " + error.message, "error");
                }
            },
            
            gatherExportData: function() {
                return {
                    revenue: document.getElementById("qcc-revenue").value,
                    qualityPercentage: document.getElementById("qcc-quality-percentage").value,
                    prevention: document.getElementById("qcc-prevention").value,
                    appraisal: document.getElementById("qcc-appraisal").value,
                    internalDefect: document.getElementById("qcc-internal-defect").value,
                    externalDefect: document.getElementById("qcc-external-defect").value,
                    preventionCost: document.getElementById("qcc-prevention-cost").textContent,
                    appraisalCost: document.getElementById("qcc-appraisal-cost").textContent,
                    internalDefectCost: document.getElementById("qcc-internal-defect-cost").textContent,
                    externalDefectCost: document.getElementById("qcc-external-defect-cost").textContent,
                    totalCOGQ: document.getElementById("qcc-total-cogq").textContent,
                    totalCOPQ: document.getElementById("qcc-total-copq").textContent,
                    totalQualityCost: document.getElementById("qcc-total-quality-cost").textContent
                };
            },
            
            formatCSV: function(data) {
                const rows = [
                    [this.t("parameter"), this.t("value")],
                    [this.t("revenue-csv") + " (" + this.currency + " " + this.unitName + ")", data.revenue],
                    [this.t("quality-basis-csv") + " (%)", data.qualityPercentage + "%"],
                    [this.t("prevention-percentage-csv") + " (%)", data.prevention + "%"],
                    [this.t("appraisal-percentage-csv") + " (%)", data.appraisal + "%"],
                    [this.t("internal-percentage-csv") + " (%)", data.internalDefect + "%"],
                    [this.t("external-percentage-csv") + " (%)", data.externalDefect + "%"],
                    ["", ""],
                    [this.t("calculated-results-csv") + " (" + this.currency + " " + this.unitName + ")", ""],
                    [this.t("prevention-cost-csv") + " (COGQ)", data.preventionCost],
                    [this.t("appraisal-cost-csv") + " (COGQ)", data.appraisalCost],
                    [this.t("internal-cost-csv") + " (COPQ)", data.internalDefectCost],
                    [this.t("external-cost-csv") + " (COPQ)", data.externalDefectCost],
                    [this.t("total-cogq-csv"), data.totalCOGQ],
                    [this.t("total-copq-csv"), data.totalCOPQ],
                    [this.t("total-quality-csv"), data.totalQualityCost]
                ];
                
                return rows.map(row => row.join(",")).join("\\n");
            },
            
            showMessage: function(message, type = "info") {
                // Create or update message element
                let messageEl = document.getElementById("qcc-message");
                if (!messageEl) {
                    messageEl = document.createElement("div");
                    messageEl.id = "qcc-message";
                    messageEl.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        max-width: 300px;
                        padding: 15px;
                        border-radius: 8px;
                        font-weight: 500;
                        z-index: 10000;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    `;
                    document.body.appendChild(messageEl);
                }
                
                // Set message styling based on type
                const styles = {
                    success: { bg: "#d4edda", border: "#c3e6cb", color: "#155724" },
                    error: { bg: "#f8d7da", border: "#f5c6cb", color: "#721c24" },
                    warning: { bg: "#fff3cd", border: "#ffeaa7", color: "#856404" },
                    info: { bg: "#d1ecf1", border: "#bee5eb", color: "#0c5460" }
                };
                
                const style = styles[type] || styles.info;
                messageEl.style.backgroundColor = style.bg;
                messageEl.style.borderLeft = `4px solid ${style.border}`;
                messageEl.style.color = style.color;
                messageEl.textContent = message;
                
                // Auto-hide after 4 seconds
                setTimeout(() => {
                    if (messageEl && messageEl.parentNode) {
                        messageEl.style.opacity = "0";
                        messageEl.style.transform = "translateX(100%)";
                        setTimeout(() => {
                            if (messageEl && messageEl.parentNode) {
                                messageEl.parentNode.removeChild(messageEl);
                            }
                        }, 300);
                    }
                }, 4000);
            },
            
            showCalculationSummary: function(totalCost, cogq, copq, percentage) {
                if (this.initialized && totalCost > 0) {
                    const cogqPercent = ((cogq / totalCost) * 100).toFixed(1);
                    const copqPercent = ((copq / totalCost) * 100).toFixed(1);
                    
                    this.showMessage(
                        this.t("calculation-complete")
                            .replace("{total}", (totalCost/this.unit).toFixed(2))
                            .replace("{currency}", this.currency)
                            .replace("{percentage}", percentage)
                            .replace("{cogq}", cogqPercent)
                            .replace("{copq}", copqPercent),
                        "success"
                    );
                }
            }
        };
        
        // Initialize when DOM is ready
        document.addEventListener("DOMContentLoaded", function() {
            QCC.init();
        });
        
        // Add enhanced CSS for better interaction
        const style = document.createElement("style");
        style.textContent = `
            .qcc-container input:focus {
                outline: none !important;
                border-color: #449775 !important;
                box-shadow: 0 0 0 2px rgba(68, 151, 117, 0.2) !important;
            }
            .qcc-container select:focus {
                outline: none !important;
                border-color: #449775 !important;
            }
            .qcc-container button:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
            }
            .qcc-container button:active {
                transform: translateY(0) !important;
            }
            .qcc-container input[type="number"]::-webkit-outer-spin-button,
            .qcc-container input[type="number"]::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .qcc-container input[type="number"] {
                -moz-appearance: textfield;
            }
        `;
        document.head.appendChild(style);
        </script>';
        
        return $html;
    }
    
    /**
     * Get emergency fallback HTML for critical errors - MIT DEUTSCHER SPRACHE
     */
    private function get_emergency_fallback_html($atts, $error_message) {
        $html = '<div style="max-width: 800px; margin: 20px auto; padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; font-family: Arial, sans-serif;">';
        $html .= '<h2 style="color: #721c24; margin-bottom: 15px;">🚨 ' . $this->get_translation($atts['language'], 'error-title') . '</h2>';
        $html .= '<p style="color: #721c24; font-weight: bold;">' . $this->get_translation($atts['language'], 'error-description') . '</p>';
        $html .= '<p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; font-family: monospace;">' . esc_html($error_message) . '</p>';
        
        $html .= '<h3 style="color: #721c24; margin-top: 20px;">' . $this->get_translation($atts['language'], 'troubleshooting-title') . '</h3>';
        $html .= '<ol style="color: #721c24;">';
        $html .= '<li>' . $this->get_translation($atts['language'], 'troubleshooting-1') . '</li>';
        $html .= '<li>' . $this->get_translation($atts['language'], 'troubleshooting-2') . '</li>';
        $html .= '<li>' . $this->get_translation($atts['language'], 'troubleshooting-3') . '</li>';
        $html .= '<li>' . $this->get_translation($atts['language'], 'troubleshooting-4') . '</li>';
        $html .= '</ol>';
        
        if (current_user_can('administrator') && defined('QCC_DEBUG') && QCC_DEBUG) {
            $html .= '<h4 style="color: #856404;">' . $this->get_translation($atts['language'], 'debug-info-title') . '</h4>';
            $html .= '<ul style="color: #856404; font-size: 12px;">';
            $html .= '<li>' . $this->get_translation($atts['language'], 'plugin-version') . ': ' . (defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : 'Unknown') . '</li>';
            $html .= '<li>' . $this->get_translation($atts['language'], 'wp-version') . ': ' . get_bloginfo('version') . '</li>';
            $html .= '<li>' . $this->get_translation($atts['language'], 'php-version') . ': ' . PHP_VERSION . '</li>';
            $html .= '<li>' . $this->get_translation($atts['language'], 'shortcode-attributes') . ': ' . esc_html(wp_json_encode($atts)) . '</li>';
            $html .= '<li>' . $this->get_translation($atts['language'], 'template-path') . ': ' . esc_html($this->get_template_path()) . '</li>';
            $html .= '<li>' . $this->get_translation($atts['language'], 'template-exists') . ': ' . (file_exists($this->get_template_path()) ? $this->get_translation($atts['language'], 'yes') : $this->get_translation($atts['language'], 'no')) . '</li>';
            $html .= '</ul>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * VOLLSTÄNDIGE DEUTSCHE ÜBERSETZUNGEN - Helper methods for data processing
     */
    private function get_default_values() {
        if (function_exists('qcc_get_default_values')) {
            return qcc_get_default_values();
        }
        
        return array(
            'revenue' => 140,
            'quality_percentage' => 6,
            'prevention' => 10,
            'appraisal' => 20,
            'internal_defect' => 30,
            'external_defect' => 40,
            'lost_sales' => 5,
            'customer_churn' => 2,
            'market_share_loss' => 1,
            'productivity_loss' => 3
        );
    }
    
    private function get_currency_symbol($currency) {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'CNY' => '¥'
        );
        return isset($symbols[$currency]) ? $symbols[$currency] : '€';
    }
    
    private function get_unit_name($unit, $language) {
        $units = array(
            'en' => array(
                '1000000' => 'Millions',
                '1000000000' => 'Billions'
            ),
            'de' => array(
                '1000000' => 'Millionen',
                '1000000000' => 'Milliarden'
            ),
            'fr' => array(
                '1000000' => 'Millions',
                '1000000000' => 'Milliards'
            ),
            'es' => array(
                '1000000' => 'Millones',
                '1000000000' => 'Miles de millones'
            ),
            'zh' => array(
                '1000000' => '百万',
                '1000000000' => '十亿'
            )
        );
        
        return isset($units[$language][$unit]) ? $units[$language][$unit] : $units['en'][$unit];
    }
    
    /**
     * Get translation for specific key and language - DEUTSCHE ÜBERSETZUNGEN HINZUGEFÜGT
     */
    private function get_translation($language, $key) {
        $translations = $this->get_all_translations();
        
        if (isset($translations[$language][$key])) {
            return $translations[$language][$key];
        }
        
        // Fallback to English
        if (isset($translations['en'][$key])) {
            return $translations['en'][$key];
        }
        
        // Last fallback
        return ucfirst(str_replace('-', ' ', $key));
    }
    
    /**
     * Get all translations - ERWEITERT MIT VOLLSTÄNDIGEN DEUTSCHEN ÜBERSETZUNGEN
     */
    private function get_all_translations() {
        return array(
            'en' => array(
                'title' => "Quality Cost Calculator",
                'subtitle' => "Cost of Quality Analysis Tool (COGQ/COPQ)",
                'lang-label' => "Language:",
                'currency-label' => "Currency:",
                'unit-label' => "Display Unit:",
                'opportunity-toggle-label' => "Opportunity Costs:",
                'input-title' => "Input Parameters",
                'results-title' => "Calculated Results",
                'revenue-label' => "Revenue",
                'quality-percentage-label' => "Quality Cost Basis (% of Revenue):",
                'cost-distribution-title' => "Cost Distribution (Must total 100%)",
                'cogq-title' => "Cost of Good Quality (COGQ)",
                'copq-title' => "Cost of Poor Quality (COPQ)",
                'prevention-label' => "Prevention Costs (%):",
                'appraisal-label' => "Appraisal Costs (%):",
                'internal-defect-label' => "Internal Defect Costs (%):",
                'external-defect-label' => "External Defect Costs (%):",
                'prevention-description' => "Training, QM systems, process improvement",
                'appraisal-description' => "Inspections, testing, audits",
                'internal-description' => "Rework, scrap, downtime",
                'external-description' => "Warranty, returns, complaints",
                'opportunity-factors-title' => "Opportunity Cost Factors (% of Revenue)",
                'lost-sales-label' => "Lost Sales (%):",
                'customer-churn-label' => "Customer Churn (%):",
                'market-share-loss-label' => "Market Share Loss (%):",
                'productivity-loss-label' => "Productivity Loss (%):",
                'lost-sales-description' => "Revenue lost due to quality issues",
                'customer-churn-description' => "Customers lost due to poor quality",
                'market-share-description' => "Market position lost",
                'productivity-description' => "Efficiency reduction",
                'cogq-results-title' => "Cost of Good Quality (COGQ)",
                'copq-results-title' => "Cost of Poor Quality (COPQ)",
                'prevention-cost-label' => "Prevention Cost:",
                'appraisal-cost-label' => "Appraisal Cost:",
                'internal-defect-cost-label' => "Internal Defect Cost:",
                'external-defect-cost-label' => "External Defect Cost:",
                'total-cogq-label' => "Total COGQ:",
                'total-copq-label' => "Total COPQ:",
                'total-quality-cost-title' => "Total Quality Cost",
                'revenue-percentage-label' => "% of Revenue:",
                'opportunity-costs-title' => "Opportunity Costs",
                'lost-sales-cost-label' => "Lost Sales Cost:",
                'customer-churn-cost-label' => "Customer Churn Cost:",
                'market-share-cost-label' => "Market Share Cost:",
                'productivity-cost-label' => "Productivity Cost:",
                'total-opportunity-cost-label' => "Total Opportunity Cost:",
                'percentage-error' => "Values do not add up to 100%. Please adjust your inputs.",
                'percentage-error-detailed' => "Values add up to {total}% but must equal 100%. Please adjust your inputs.",
                'calculate-button' => "Calculate",
                'reset-button' => "Reset",
                'export-csv' => "Export CSV",
                'millions' => "Millions",
                'billions' => "Billions",
                'enabled' => "Enabled",
                'disabled' => "Disabled",
                'calculator-ready' => "Calculator ready! Enter your values and calculations will update automatically.",
                'warning-features' => "Warning: Some calculator features may not work properly.",
                'language-changed' => "Language changed successfully.",
                'opportunity-enabled' => "Opportunity costs analysis enabled. Additional factors will be calculated.",
                'opportunity-disabled' => "Opportunity costs analysis disabled.",
                'settings-updated' => "Settings updated. Calculations refreshed.",
                'calculation-error' => "Calculation error",
                'reset-confirmation' => "Reset all values to defaults? This will clear your current inputs.",
                'reset-success' => "Calculator reset to default values.",
                'export-success' => "CSV export completed successfully!",
                'export-failed' => "CSV export failed",
                'calculation-complete' => "Calculation complete! Total: {total} {currency} ({percentage}% of revenue). COGQ: {cogq}%, COPQ: {copq}%",
                'status-operational' => "Plugin Status: Operational",
                'status-issues' => "Plugin Status: Issues Detected",
                'details-label' => "Details:",
                'version-label' => "Version",
                'language-label' => "Language",
                'mode-label' => "Mode",
                'fallback-mode' => "Fallback",
                'template-mode' => "Template",
                'error-title' => "Quality Cost Calculator - Error",
                'error-description' => "The calculator could not be loaded due to a technical issue:",
                'troubleshooting-title' => "Troubleshooting Steps:",
                'troubleshooting-1' => "Refresh the page and try again",
                'troubleshooting-2' => "Check if all plugin files are properly uploaded",
                'troubleshooting-3' => "Verify WordPress and PHP version requirements",
                'troubleshooting-4' => "Contact your site administrator",
                'debug-info-title' => "Debug Information (Admin Only):",
                'plugin-version' => "Plugin Version",
                'wp-version' => "WordPress Version",
                'php-version' => "PHP Version",
                'shortcode-attributes' => "Shortcode Attributes",
                'template-path' => "Template Path",
                'template-exists' => "Template Exists",
                'yes' => "Yes",
                'no' => "No",
                // CSV Export Labels
                'parameter' => "Parameter",
                'value' => "Value",
                'revenue-csv' => "Revenue",
                'quality-basis-csv' => "Quality Cost Basis",
                'prevention-percentage-csv' => "Prevention Percentage",
                'appraisal-percentage-csv' => "Appraisal Percentage",
                'internal-percentage-csv' => "Internal Defect Percentage",
                'external-percentage-csv' => "External Defect Percentage",
                'calculated-results-csv' => "Calculated Results",
                'prevention-cost-csv' => "Prevention Cost",
                'appraisal-cost-csv' => "Appraisal Cost",
                'internal-cost-csv' => "Internal Defect Cost",
                'external-cost-csv' => "External Defect Cost",
                'total-cogq-csv' => "Total COGQ",
                'total-copq-csv' => "Total COPQ",
                'total-quality-csv' => "Total Quality Cost"
            ),
            'de' => array(
                'title' => "Qualitätskostenrechner",
                'subtitle' => "Werkzeug zur Qualitätskostenanalyse (COGQ/COPQ)",
                'lang-label' => "Sprache:",
                'currency-label' => "Währung:",
                'unit-label' => "Anzeigeeinheit:",
                'opportunity-toggle-label' => "Opportunitätskosten:",
                'input-title' => "Eingabeparameter",
                'results-title' => "Berechnete Ergebnisse",
                'revenue-label' => "Umsatz",
                'quality-percentage-label' => "Qualitätskostenbasis (% vom Umsatz):",
                'cost-distribution-title' => "Kostenverteilung (Muss 100% ergeben)",
                'cogq-title' => "Kosten guter Qualität (COGQ)",
                'copq-title' => "Kosten schlechter Qualität (COPQ)",
                'prevention-label' => "Präventionskosten (%):",
                'appraisal-label' => "Bewertungskosten (%):",
                'internal-defect-label' => "Interne Fehlerkosten (%):",
                'external-defect-label' => "Externe Fehlerkosten (%):",
                'prevention-description' => "Schulungen, QM-Systeme, Prozessverbesserung",
                'appraisal-description' => "Inspektionen, Tests, Audits",
                'internal-description' => "Nacharbeit, Ausschuss, Ausfallzeiten",
                'external-description' => "Garantie, Rücksendungen, Beschwerden",
                'opportunity-factors-title' => "Opportunitätskostenfaktoren (% vom Umsatz)",
                'lost-sales-label' => "Entgangene Verkäufe (%):",
                'customer-churn-label' => "Kundenverlust (%):",
                'market-share-loss-label' => "Marktanteilsverlust (%):",
                'productivity-loss-label' => "Produktivitätsverlust (%):",
                'lost-sales-description' => "Durch Qualitätsprobleme entgangener Umsatz",
                'customer-churn-description' => "Durch schlechte Qualität verlorene Kunden",
                'market-share-description' => "Verlorene Marktposition",
                'productivity-description' => "Effizienzverringerung",
                'cogq-results-title' => "Kosten guter Qualität (COGQ)",
                'copq-results-title' => "Kosten schlechter Qualität (COPQ)",
                'prevention-cost-label' => "Präventionskosten:",
                'appraisal-cost-label' => "Bewertungskosten:",
                'internal-defect-cost-label' => "Interne Fehlerkosten:",
                'external-defect-cost-label' => "Externe Fehlerkosten:",
                'total-cogq-label' => "Gesamt COGQ:",
                'total-copq-label' => "Gesamt COPQ:",
                'total-quality-cost-title' => "Gesamte Qualitätskosten",
                'revenue-percentage-label' => "% vom Umsatz:",
                'opportunity-costs-title' => "Opportunitätskosten",
                'lost-sales-cost-label' => "Kosten entgangener Verkäufe:",
                'customer-churn-cost-label' => "Kundenverlustkosten:",
                'market-share-cost-label' => "Marktanteilskosten:",
                'productivity-cost-label' => "Produktivitätskosten:",
                'total-opportunity-cost-label' => "Gesamte Opportunitätskosten:",
                'percentage-error' => "Werte ergeben nicht 100%. Bitte passen Sie Ihre Eingaben an.",
                'percentage-error-detailed' => "Werte ergeben {total}%, müssen aber 100% sein. Bitte passen Sie Ihre Eingaben an.",
                'calculate-button' => "Berechnen",
                'reset-button' => "Zurücksetzen",
                'export-csv' => "CSV exportieren",
                'millions' => "Millionen",
                'billions' => "Milliarden",
                'enabled' => "Aktiviert",
                'disabled' => "Deaktiviert",
                'calculator-ready' => "Rechner bereit! Geben Sie Ihre Werte ein und die Berechnungen werden automatisch aktualisiert.",
                'warning-features' => "Warnung: Einige Rechner-Funktionen funktionieren möglicherweise nicht ordnungsgemäß.",
                'language-changed' => "Sprache erfolgreich geändert.",
                'opportunity-enabled' => "Opportunitätskostenanalyse aktiviert. Zusätzliche Faktoren werden berechnet.",
                'opportunity-disabled' => "Opportunitätskostenanalyse deaktiviert.",
                'settings-updated' => "Einstellungen aktualisiert. Berechnungen neu geladen.",
                'calculation-error' => "Berechnungsfehler",
                'reset-confirmation' => "Alle Werte auf Standardwerte zurücksetzen? Dies löscht Ihre aktuellen Eingaben.",
                'reset-success' => "Rechner auf Standardwerte zurückgesetzt.",
                'export-success' => "CSV-Export erfolgreich abgeschlossen!",
                'export-failed' => "CSV-Export fehlgeschlagen",
                'calculation-complete' => "Berechnung abgeschlossen! Gesamt: {total} {currency} ({percentage}% vom Umsatz). COGQ: {cogq}%, COPQ: {copq}%",
                'status-operational' => "Plugin-Status: Funktionsfähig",
                'status-issues' => "Plugin-Status: Probleme erkannt",
                'details-label' => "Details:",
                'version-label' => "Version",
                'language-label' => "Sprache",
                'mode-label' => "Modus",
                'fallback-mode' => "Fallback",
                'template-mode' => "Template",
                'error-title' => "Qualitätskostenrechner - Fehler",
                'error-description' => "Der Rechner konnte aufgrund eines technischen Problems nicht geladen werden:",
                'troubleshooting-title' => "Fehlerbehebungsschritte:",
                'troubleshooting-1' => "Seite aktualisieren und erneut versuchen",
                'troubleshooting-2' => "Überprüfen, ob alle Plugin-Dateien ordnungsgemäß hochgeladen wurden",
                'troubleshooting-3' => "WordPress- und PHP-Versionsanforderungen überprüfen",
                'troubleshooting-4' => "Site-Administrator kontaktieren",
                'debug-info-title' => "Debug-Informationen (nur Admin):",
                'plugin-version' => "Plugin-Version",
                'wp-version' => "WordPress-Version",
                'php-version' => "PHP-Version",
                'shortcode-attributes' => "Shortcode-Attribute",
                'template-path' => "Template-Pfad",
                'template-exists' => "Template vorhanden",
                'yes' => "Ja",
                'no' => "Nein",
                // CSV Export Labels - German
                'parameter' => "Parameter",
                'value' => "Wert",
                'revenue-csv' => "Umsatz",
                'quality-basis-csv' => "Qualitätskostenbasis",
                'prevention-percentage-csv' => "Präventionsanteil",
                'appraisal-percentage-csv' => "Bewertungsanteil",
                'internal-percentage-csv' => "Interne Fehlerkosten-Anteil",
                'external-percentage-csv' => "Externe Fehlerkosten-Anteil",
                'calculated-results-csv' => "Berechnete Ergebnisse",
                'prevention-cost-csv' => "Präventionskosten",
                'appraisal-cost-csv' => "Bewertungskosten",
                'internal-cost-csv' => "Interne Fehlerkosten",
                'external-cost-csv' => "Externe Fehlerkosten",
                'total-cogq-csv' => "Gesamt COGQ",
                'total-copq-csv' => "Gesamt COPQ",
                'total-quality-csv' => "Gesamte Qualitätskosten"
            ),
            'fr' => array(
                'title' => "Calculateur de Coûts de Qualité",
                'subtitle' => "Outil d'Analyse des Coûts de Qualité (COGQ/COPQ)",
                'lang-label' => "Langue:",
                'currency-label' => "Devise:",
                'unit-label' => "Unité d'Affichage:",
                'opportunity-toggle-label' => "Coûts d'Opportunité:",
                'input-title' => "Paramètres d'Entrée",
                'results-title' => "Résultats Calculés",
                'revenue-label' => "Chiffre d'Affaires",
                'quality-percentage-label' => "Base de Coût Qualité (% du CA):",
                'cost-distribution-title' => "Distribution des Coûts (Doit totaliser 100%)",
                'cogq-title' => "Coût de Bonne Qualité (COGQ)",
                'copq-title' => "Coût de Mauvaise Qualité (COPQ)",
                'prevention-label' => "Coûts de Prévention (%):",
                'appraisal-label' => "Coûts d'Évaluation (%):",
                'internal-defect-label' => "Coûts de Défauts Internes (%):",
                'external-defect-label' => "Coûts de Défauts Externes (%):",
                'prevention-description' => "Formation, systèmes QM, amélioration des processus",
                'appraisal-description' => "Inspections, tests, audits",
                'internal-description' => "Reprise, rebut, temps d'arrêt",
                'external-description' => "Garantie, retours, plaintes",
                'opportunity-factors-title' => "Facteurs de Coût d'Opportunité (% du CA)",
                'lost-sales-label' => "Ventes Perdues (%):",
                'customer-churn-label' => "Perte de Clients (%):",
                'market-share-loss-label' => "Perte de Part de Marché (%):",
                'productivity-loss-label' => "Perte de Productivité (%):",
                'lost-sales-description' => "Revenus perdus dus aux problèmes de qualité",
                'customer-churn-description' => "Clients perdus dus à la mauvaise qualité",
                'market-share-description' => "Position de marché perdue",
                'productivity-description' => "Réduction d'efficacité",
                'cogq-results-title' => "Coût de Bonne Qualité (COGQ)",
                'copq-results-title' => "Coût de Mauvaise Qualité (COPQ)",
                'prevention-cost-label' => "Coût de Prévention:",
                'appraisal-cost-label' => "Coût d'Évaluation:",
                'internal-defect-cost-label' => "Coût de Défaut Interne:",
                'external-defect-cost-label' => "Coût de Défaut Externe:",
                'total-cogq-label' => "Total COGQ:",
                'total-copq-label' => "Total COPQ:",
                'total-quality-cost-title' => "Coût Total de Qualité",
                'revenue-percentage-label' => "% du CA:",
                'opportunity-costs-title' => "Coûts d'Opportunité",
                'lost-sales-cost-label' => "Coût des Ventes Perdues:",
                'customer-churn-cost-label' => "Coût de Perte de Clients:",
                'market-share-cost-label' => "Coût de Part de Marché:",
                'productivity-cost-label' => "Coût de Productivité:",
                'total-opportunity-cost-label' => "Coût Total d'Opportunité:",
                'percentage-error' => "Les valeurs ne totalisent pas 100%. Veuillez ajuster vos entrées.",
                'percentage-error-detailed' => "Les valeurs totalisent {total}% mais doivent être égales à 100%. Veuillez ajuster vos entrées.",
                'calculate-button' => "Calculer",
                'reset-button' => "Réinitialiser",
                'export-csv' => "Exporter CSV",
                'millions' => "Millions",
                'billions' => "Milliards",
                'enabled' => "Activé",
                'disabled' => "Désactivé",
                'calculator-ready' => "Calculateur prêt! Entrez vos valeurs et les calculs se mettront à jour automatiquement.",
                'warning-features' => "Attention: Certaines fonctionnalités du calculateur peuvent ne pas fonctionner correctement.",
                'language-changed' => "Langue changée avec succès.",
                'opportunity-enabled' => "Analyse des coûts d'opportunité activée. Des facteurs supplémentaires seront calculés.",
                'opportunity-disabled' => "Analyse des coûts d'opportunité désactivée.",
                'settings-updated' => "Paramètres mis à jour. Calculs actualisés.",
                'calculation-error' => "Erreur de calcul",
                'reset-confirmation' => "Réinitialiser toutes les valeurs par défaut? Cela effacera vos entrées actuelles.",
                'reset-success' => "Calculateur réinitialisé aux valeurs par défaut.",
                'export-success' => "Export CSV terminé avec succès!",
                'export-failed' => "Échec de l'export CSV",
                'calculation-complete' => "Calcul terminé! Total: {total} {currency} ({percentage}% du revenu). COGQ: {cogq}%, COPQ: {copq}%",
                'status-operational' => "Statut du Plugin: Opérationnel",
                'status-issues' => "Statut du Plugin: Problèmes Détectés",
                'details-label' => "Détails:",
                'version-label' => "Version",
                'language-label' => "Langue",
                'mode-label' => "Mode",
                'fallback-mode' => "Secours",
                'template-mode' => "Template",
                'error-title' => "Calculateur de Coûts de Qualité - Erreur",
                'error-description' => "Le calculateur n'a pas pu être chargé en raison d'un problème technique:",
                'troubleshooting-title' => "Étapes de Dépannage:",
                'troubleshooting-1' => "Actualiser la page et réessayer",
                'troubleshooting-2' => "Vérifier que tous les fichiers du plugin sont correctement téléchargés",
                'troubleshooting-3' => "Vérifier les exigences de version WordPress et PHP",
                'troubleshooting-4' => "Contacter votre administrateur de site",
                'debug-info-title' => "Informations de Débogage (Admin Seulement):",
                'plugin-version' => "Version du Plugin",
                'wp-version' => "Version WordPress",
                'php-version' => "Version PHP",
                'shortcode-attributes' => "Attributs Shortcode",
                'template-path' => "Chemin du Template",
                'template-exists' => "Template Existe",
                'yes' => "Oui",
                'no' => "Non",
                // CSV Export Labels - French
                'parameter' => "Paramètre",
                'value' => "Valeur",
                'revenue-csv' => "Chiffre d'Affaires",
                'quality-basis-csv' => "Base de Coût Qualité",
                'prevention-percentage-csv' => "Pourcentage Prévention",
                'appraisal-percentage-csv' => "Pourcentage Évaluation",
                'internal-percentage-csv' => "Pourcentage Défauts Internes",
                'external-percentage-csv' => "Pourcentage Défauts Externes",
                'calculated-results-csv' => "Résultats Calculés",
                'prevention-cost-csv' => "Coût de Prévention",
                'appraisal-cost-csv' => "Coût d'Évaluation",
                'internal-cost-csv' => "Coût de Défaut Interne",
                'external-cost-csv' => "Coût de Défaut Externe",
                'total-cogq-csv' => "Total COGQ",
                'total-copq-csv' => "Total COPQ",
                'total-quality-csv' => "Coût Total de Qualité"
            ),
            'es' => array(
                'title' => "Calculadora de Costos de Calidad",
                'subtitle' => "Herramienta de Análisis de Costos de Calidad (COGQ/COPQ)",
                'lang-label' => "Idioma:",
                'currency-label' => "Moneda:",
                'unit-label' => "Unidad de Visualización:",
                'opportunity-toggle-label' => "Costos de Oportunidad:",
                'input-title' => "Parámetros de Entrada",
                'results-title' => "Resultados Calculados",
                'revenue-label' => "Ingresos",
                'quality-percentage-label' => "Base de Costo de Calidad (% de Ingresos):",
                'cost-distribution-title' => "Distribución de Costos (Debe sumar 100%)",
                'cogq-title' => "Costo de Buena Calidad (COGQ)",
                'copq-title' => "Costo de Mala Calidad (COPQ)",
                'prevention-label' => "Costos de Prevención (%):",
                'appraisal-label' => "Costos de Evaluación (%):",
                'internal-defect-label' => "Costos de Defectos Internos (%):",
                'external-defect-label' => "Costos de Defectos Externos (%):",
                'prevention-description' => "Capacitación, sistemas QM, mejora de procesos",
                'appraisal-description' => "Inspecciones, pruebas, auditorías",
                'internal-description' => "Retrabajo, desecho, tiempo de inactividad",
                'external-description' => "Garantía, devoluciones, quejas",
                'opportunity-factors-title' => "Factores de Costo de Oportunidad (% de Ingresos)",
                'lost-sales-label' => "Ventas Perdidas (%):",
                'customer-churn-label' => "Pérdida de Clientes (%):",
                'market-share-loss-label' => "Pérdida de Cuota de Mercado (%):",
                'productivity-loss-label' => "Pérdida de Productividad (%):",
                'lost-sales-description' => "Ingresos perdidos debido a problemas de calidad",
                'customer-churn-description' => "Clientes perdidos debido a mala calidad",
                'market-share-description' => "Posición de mercado perdida",
                'productivity-description' => "Reducción de eficiencia",
                'cogq-results-title' => "Costo de Buena Calidad (COGQ)",
                'copq-results-title' => "Costo de Mala Calidad (COPQ)",
                'prevention-cost-label' => "Costo de Prevención:",
                'appraisal-cost-label' => "Costo de Evaluación:",
                'internal-defect-cost-label' => "Costo de Defecto Interno:",
                'external-defect-cost-label' => "Costo de Defecto Externo:",
                'total-cogq-label' => "Total COGQ:",
                'total-copq-label' => "Total COPQ:",
                'total-quality-cost-title' => "Costo Total de Calidad",
                'revenue-percentage-label' => "% de Ingresos:",
                'opportunity-costs-title' => "Costos de Oportunidad",
                'lost-sales-cost-label' => "Costo de Ventas Perdidas:",
                'customer-churn-cost-label' => "Costo de Pérdida de Clientes:",
                'market-share-cost-label' => "Costo de Cuota de Mercado:",
                'productivity-cost-label' => "Costo de Productividad:",
                'total-opportunity-cost-label' => "Costo Total de Oportunidad:",
                'percentage-error' => "Los valores no suman 100%. Ajuste sus entradas.",
                'percentage-error-detailed' => "Los valores suman {total}% pero deben ser igual a 100%. Ajuste sus entradas.",
                'calculate-button' => "Calcular",
                'reset-button' => "Restablecer",
                'export-csv' => "Exportar CSV",
                'millions' => "Millones",
                'billions' => "Miles de millones",
                'enabled' => "Habilitado",
                'disabled' => "Deshabilitado",
                'calculator-ready' => "¡Calculadora lista! Ingrese sus valores y los cálculos se actualizarán automáticamente.",
                'warning-features' => "Advertencia: Algunas características de la calculadora pueden no funcionar correctamente.",
                'language-changed' => "Idioma cambiado exitosamente.",
                'opportunity-enabled' => "Análisis de costos de oportunidad habilitado. Se calcularán factores adicionales.",
                'opportunity-disabled' => "Análisis de costos de oportunidad deshabilitado.",
                'settings-updated' => "Configuraciones actualizadas. Cálculos actualizados.",
                'calculation-error' => "Error de cálculo",
                'reset-confirmation' => "¿Restablecer todos los valores a los predeterminados? Esto borrará sus entradas actuales.",
                'reset-success' => "Calculadora restablecida a valores predeterminados.",
                'export-success' => "¡Exportación CSV completada exitosamente!",
                'export-failed' => "Falló la exportación CSV",
                'calculation-complete' => "¡Cálculo completado! Total: {total} {currency} ({percentage}% de ingresos). COGQ: {cogq}%, COPQ: {copq}%",
                'status-operational' => "Estado del Plugin: Operacional",
                'status-issues' => "Estado del Plugin: Problemas Detectados",
                'details-label' => "Detalles:",
                'version-label' => "Versión",
                'language-label' => "Idioma",
                'mode-label' => "Modo",
                'fallback-mode' => "Respaldo",
                'template-mode' => "Plantilla",
                'error-title' => "Calculadora de Costos de Calidad - Error",
                'error-description' => "La calculadora no pudo cargarse debido a un problema técnico:",
                'troubleshooting-title' => "Pasos de Solución de Problemas:",
                'troubleshooting-1' => "Actualizar la página e intentar de nuevo",
                'troubleshooting-2' => "Verificar que todos los archivos del plugin estén subidos correctamente",
                'troubleshooting-3' => "Verificar los requisitos de versión de WordPress y PHP",
                'troubleshooting-4' => "Contactar al administrador del sitio",
                'debug-info-title' => "Información de Depuración (Solo Admin):",
                'plugin-version' => "Versión del Plugin",
                'wp-version' => "Versión de WordPress",
                'php-version' => "Versión de PHP",
                'shortcode-attributes' => "Atributos del Shortcode",
                'template-path' => "Ruta de la Plantilla",
                'template-exists' => "Plantilla Existe",
                'yes' => "Sí",
                'no' => "No",
                // CSV Export Labels - Spanish
                'parameter' => "Parámetro",
                'value' => "Valor",
                'revenue-csv' => "Ingresos",
                'quality-basis-csv' => "Base de Costo de Calidad",
                'prevention-percentage-csv' => "Porcentaje Prevención",
                'appraisal-percentage-csv' => "Porcentaje Evaluación",
                'internal-percentage-csv' => "Porcentaje Defectos Internos",
                'external-percentage-csv' => "Porcentaje Defectos Externos",
                'calculated-results-csv' => "Resultados Calculados",
                'prevention-cost-csv' => "Costo de Prevención",
                'appraisal-cost-csv' => "Costo de Evaluación",
                'internal-cost-csv' => "Costo de Defecto Interno",
                'external-cost-csv' => "Costo de Defecto Externo",
                'total-cogq-csv' => "Total COGQ",
                'total-copq-csv' => "Total COPQ",
                'total-quality-csv' => "Costo Total de Calidad"
            ),
            'zh' => array(
                'title' => "质量成本计算器",
                'subtitle' => "质量成本分析工具 (COGQ/COPQ)",
                'lang-label' => "语言:",
                'currency-label' => "货币:",
                'unit-label' => "显示单位:",
                'opportunity-toggle-label' => "机会成本:",
                'input-title' => "输入参数",
                'results-title' => "计算结果",
                'revenue-label' => "收入",
                'quality-percentage-label' => "质量成本基础 (收入的%):",
                'cost-distribution-title' => "成本分布 (必须总计100%)",
                'cogq-title' => "良好质量成本 (COGQ)",
                'copq-title' => "质量差成本 (COPQ)",
                'prevention-label' => "预防成本 (%):",
                'appraisal-label' => "评估成本 (%):",
                'internal-defect-label' => "内部缺陷成本 (%):",
                'external-defect-label' => "外部缺陷成本 (%):",
                'prevention-description' => "培训、质量管理系统、流程改进",
                'appraisal-description' => "检查、测试、审计",
                'internal-description' => "返工、废料、停机时间",
                'external-description' => "保修、退货、投诉",
                'opportunity-factors-title' => "机会成本因子 (收入的%)",
                'lost-sales-label' => "销售损失 (%):",
                'customer-churn-label' => "客户流失 (%):",
                'market-share-loss-label' => "市场份额损失 (%):",
                'productivity-loss-label' => "生产力损失 (%):",
                'lost-sales-description' => "因质量问题造成的收入损失",
                'customer-churn-description' => "因质量差而失去的客户",
                'market-share-description' => "失去的市场地位",
                'productivity-description' => "效率降低",
                'cogq-results-title' => "良好质量成本 (COGQ)",
                'copq-results-title' => "质量差成本 (COPQ)",
                'prevention-cost-label' => "预防成本:",
                'appraisal-cost-label' => "评估成本:",
                'internal-defect-cost-label' => "内部缺陷成本:",
                'external-defect-cost-label' => "外部缺陷成本:",
                'total-cogq-label' => "总计 COGQ:",
                'total-copq-label' => "总计 COPQ:",
                'total-quality-cost-title' => "总质量成本",
                'revenue-percentage-label' => "占收入比例:",
                'opportunity-costs-title' => "机会成本",
                'lost-sales-cost-label' => "销售损失成本:",
                'customer-churn-cost-label' => "客户流失成本:",
                'market-share-cost-label' => "市场份额成本:",
                'productivity-cost-label' => "生产力成本:",
                'total-opportunity-cost-label' => "总机会成本:",
                'percentage-error' => "数值总和不等于100%。请调整您的输入。",
                'percentage-error-detailed' => "数值总和为{total}%，但必须等于100%。请调整您的输入。",
                'calculate-button' => "计算",
                'reset-button' => "重置",
                'export-csv' => "导出CSV",
                'millions' => "百万",
                'billions' => "十亿",
                'enabled' => "启用",
                'disabled' => "禁用",
                'calculator-ready' => "计算器就绪！输入您的值，计算将自动更新。",
                'warning-features' => "警告：某些计算器功能可能无法正常工作。",
                'language-changed' => "语言更改成功。",
                'opportunity-enabled' => "机会成本分析已启用。将计算额外因子。",
                'opportunity-disabled' => "机会成本分析已禁用。",
                'settings-updated' => "设置已更新。计算已刷新。",
                'calculation-error' => "计算错误",
                'reset-confirmation' => "将所有值重置为默认值？这将清除您当前的输入。",
                'reset-success' => "计算器已重置为默认值。",
                'export-success' => "CSV导出成功完成！",
                'export-failed' => "CSV导出失败",
                'calculation-complete' => "计算完成！总计：{total} {currency} (收入的{percentage}%)。COGQ：{cogq}%，COPQ：{copq}%",
                'status-operational' => "插件状态：运行正常",
                'status-issues' => "插件状态：检测到问题",
                'details-label' => "详细信息:",
                'version-label' => "版本",
                'language-label' => "语言",
                'mode-label' => "模式",
                'fallback-mode' => "备用",
                'template-mode' => "模板",
                'error-title' => "质量成本计算器 - 错误",
                'error-description' => "由于技术问题，计算器无法加载：",
                'troubleshooting-title' => "故障排除步骤：",
                'troubleshooting-1' => "刷新页面并重试",
                'troubleshooting-2' => "检查所有插件文件是否正确上传",
                'troubleshooting-3' => "验证WordPress和PHP版本要求",
                'troubleshooting-4' => "联系您的站点管理员",
                'debug-info-title' => "调试信息（仅管理员）：",
                'plugin-version' => "插件版本",
                'wp-version' => "WordPress版本",
                'php-version' => "PHP版本",
                'shortcode-attributes' => "短代码属性",
                'template-path' => "模板路径",
                'template-exists' => "模板存在",
                'yes' => "是",
                'no' => "否",
                // CSV Export Labels - Chinese
                'parameter' => "参数",
                'value' => "值",
                'revenue-csv' => "收入",
                'quality-basis-csv' => "质量成本基础",
                'prevention-percentage-csv' => "预防百分比",
                'appraisal-percentage-csv' => "评估百分比",
                'internal-percentage-csv' => "内部缺陷百分比",
                'external-percentage-csv' => "外部缺陷百分比",
                'calculated-results-csv' => "计算结果",
                'prevention-cost-csv' => "预防成本",
                'appraisal-cost-csv' => "评估成本",
                'internal-cost-csv' => "内部缺陷成本",
                'external-cost-csv' => "外部缺陷成本",
                'total-cogq-csv' => "总计COGQ",
                'total-copq-csv' => "总计COPQ",
                'total-quality-csv' => "总质量成本"
            )
        );
    }
    
    private function get_plugin_status() {
        $status = array(
            'is_working' => true,
            'using_fallback' => !file_exists($this->get_template_path()),
            'message' => '',
            'details' => array()
        );
        
        if ($status['using_fallback']) {
            $status['message'] = 'Calculator is working in fallback mode with German language support. All calculations are functional.';
            $status['details'][] = 'Template file not found - using built-in fallback HTML with German translations';
            $status['details'][] = 'All core functionality including German language is available';
        } else {
            $status['message'] = 'Calculator is fully operational with template system and German language support.';
        }
        
        // Check for potential issues
        if (!shortcode_exists('quality_cost_calculator')) {
            $status['is_working'] = false;
            $status['message'] = 'Shortcode registration failed.';
            $status['details'][] = 'The [quality_cost_calculator] shortcode is not registered';
        }
        
        if (!wp_script_is('jquery', 'registered')) {
            $status['details'][] = 'Warning: jQuery may not be available';
        }
        
        return $status;
    }
}

// =============================================================================
// ZUSÄTZLICHE DEUTSCHE HILFSFUNKTIONEN
// =============================================================================

/**
 * Deutsche Hilfsfunktion für Währungsformatierung
 */
if (!function_exists('qcc_format_german_currency')) {
    function qcc_format_german_currency($amount, $currency = 'EUR', $unit = 1000000) {
        $formatted_amount = number_format($amount / $unit, 2, ',', '.');
        
        switch ($currency) {
            case 'EUR':
                return $formatted_amount . ' €';
            case 'USD':
                return $formatted_amount . ' $';
            case 'CNY':
                return $formatted_amount . ' ¥';
            default:
                return $formatted_amount . ' ' . $currency;
        }
    }
}

/**
 * Deutsche Hilfsfunktion für Prozentvalidierung
 */
if (!function_exists('qcc_validate_german_percentages')) {
    function qcc_validate_german_percentages($prevention, $bewertung, $interne_fehler, $externe_fehler) {
        $gesamt = $prevention + $bewertung + $interne_fehler + $externe_fehler;
        $ist_gueltig = abs($gesamt - 100) < 0.01;
        
        if (!$ist_gueltig) {
            return array(
                'gueltig' => false,
                'fehlermeldung' => 'Die Werte ergeben ' . number_format($gesamt, 1, ',', '.') . '%, müssen aber 100% betragen.',
                'gesamt' => $gesamt
            );
        }
        
        return array(
            'gueltig' => true,
            'gesamt' => $gesamt
        );
    }
}

/**
 * Deutsche Qualitätskostenberechnung
 */
if (!function_exists('qcc_berechne_qualitaetskosten')) {
    function qcc_berechne_qualitaetskosten($umsatz, $qualitaets_prozent, $praevention, $bewertung, $interne_fehler, $externe_fehler) {
        $gesamt_qualitaetskosten = ($umsatz * $qualitaets_prozent) / 100;
        
        $kosten = array(
            'gesamt_qualitaetskosten' => $gesamt_qualitaetskosten,
            'praeventionskosten' => ($gesamt_qualitaetskosten * $praevention) / 100,
            'bewertungskosten' => ($gesamt_qualitaetskosten * $bewertung) / 100,
            'interne_fehlerkosten' => ($gesamt_qualitaetskosten * $interne_fehler) / 100,
            'externe_fehlerkosten' => ($gesamt_qualitaetskosten * $externe_fehler) / 100
        );
        
        // COGQ und COPQ berechnen
        $kosten['cogq'] = $kosten['praeventionskosten'] + $kosten['bewertungskosten'];
        $kosten['copq'] = $kosten['interne_fehlerkosten'] + $kosten['externe_fehlerkosten'];
        
        // Prozentuale Anteile
        $kosten['cogq_prozent'] = ($kosten['cogq'] / $gesamt_qualitaetskosten) * 100;
        $kosten['copq_prozent'] = ($kosten['copq'] / $gesamt_qualitaetskosten) * 100;
        $kosten['umsatz_prozent'] = ($gesamt_qualitaetskosten / $umsatz) * 100;
        
        return $kosten;
    }
}

// =============================================================================
// DEUTSCHE LOGGING-FUNKTIONEN
// =============================================================================

if (class_exists('QCC_Debug_Logger')) {
    QCC_Debug_Logger::log('QCC_Shortcode_Legacy Klasse mit vollständiger deutscher Sprachunterstützung geladen', 'SHORTCODE_DE');
    QCC_Debug_Logger::log('Deutsche Übersetzungen für alle UI-Elemente hinzugefügt', 'SHORTCODE_DE');
    QCC_Debug_Logger::log('Deutsche Hilfsfunktionen für Währungsformatierung und Berechnungen verfügbar', 'SHORTCODE_DE');
    QCC_Debug_Logger::log('COGQ/COPQ Analyse mit deutschen Begriffen implementiert', 'SHORTCODE_DE');
}

?>
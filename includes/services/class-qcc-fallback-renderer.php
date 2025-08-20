<?php
/**
 * QCC Fallback Renderer
 * 
 * Provides reliable fallback rendering when modern systems are unavailable.
 * Self-contained with minimal dependencies for maximum reliability.
 *
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fallback renderer - minimal dependencies, maximum reliability
 */
class QCC_Fallback_Renderer {
    
    /**
     * @var QCC_Translator|null Translation service (optional)
     */
    private $translator;
    
    /**
     * @var array Cached translations
     */
    private $translations = array();
    
    /**
     * @var array Default configuration
     */
    private $default_config = array();
    
    /**
     * Initialize fallback renderer
     * 
     * @param QCC_Translator|null $translator Optional translator
     */
    public function __construct($translator = null) {
        $this->translator = $translator;
        $this->init_default_config();
        $this->load_translations();
    }
    
    /**
     * Initialize default configuration
     */
    private function init_default_config() {
        $this->default_config = array(
            'language' => 'en',
            'currency' => 'EUR',
            'units' => 'pieces',
            'show_cogq' => true,
            'show_copq' => true,
            'layout' => 'two_column',
            'style' => 'default'
        );
    }
    
    /**
     * Load translations with fallback
     */
    private function load_translations() {
        // Basic translations with fallback values
        $this->translations = array(
            'en' => array(
                'title' => 'Quality Cost Calculator',
                'subtitle' => 'COGQ/COPQ Analysis',
                'production_volume' => 'Production Volume',
                'unit_price' => 'Unit Price',
                'production_cost' => 'Production Cost',
                'prevention_cost' => 'Prevention Cost',
                'appraisal_cost' => 'Appraisal Cost',
                'internal_failure' => 'Internal Failure Cost',
                'external_failure' => 'External Failure Cost',
                'cogq_title' => 'Cost of Good Quality (COGQ)',
                'copq_title' => 'Cost of Poor Quality (COPQ)',
                'total_cogq' => 'Total COGQ',
                'total_copq' => 'Total COPQ',
                'total_quality_cost' => 'Total Quality Cost',
                'cogq_percentage' => 'COGQ Percentage',
                'copq_percentage' => 'COPQ Percentage',
                'calculate' => 'Calculate',
                'reset' => 'Reset',
                'export_csv' => 'Export CSV',
                'currency' => 'Currency',
                'language' => 'Language',
                'units' => 'Units'
            ),
            'de' => array(
                'title' => 'Qualitätskostenrechner',
                'subtitle' => 'COGQ/COPQ Analyse',
                'production_volume' => 'Produktionsvolumen',
                'unit_price' => 'Stückpreis',
                'production_cost' => 'Produktionskosten',
                'prevention_cost' => 'Präventionskosten',
                'appraisal_cost' => 'Bewertungskosten',
                'internal_failure' => 'Interne Fehlerkosten',
                'external_failure' => 'Externe Fehlerkosten',
                'cogq_title' => 'Kosten guter Qualität (COGQ)',
                'copq_title' => 'Kosten schlechter Qualität (COPQ)',
                'total_cogq' => 'Gesamt COGQ',
                'total_copq' => 'Gesamt COPQ',
                'total_quality_cost' => 'Gesamte Qualitätskosten',
                'cogq_percentage' => 'COGQ Prozent',
                'copq_percentage' => 'COPQ Prozent',
                'calculate' => 'Berechnen',
                'reset' => 'Zurücksetzen',
                'export_csv' => 'CSV Export',
                'currency' => 'Währung',
                'language' => 'Sprache',
                'units' => 'Einheiten'
            )
        );
    }
    
    /**
     * Main render method
     * 
     * @param array $atts Attributes
     * @param string $content Content
     * @return string Rendered HTML
     */
    public function render($atts, $content = '') {
        try {
            // Merge with defaults
            $config = array_merge($this->default_config, $atts);
            
            // Build HTML structure
            $html = $this->build_calculator_html($config);
            
            return $html;
            
        } catch (Exception $e) {
            return $this->render_emergency_fallback($atts, $e->getMessage());
        }
    }
    
    /**
     * Build complete calculator HTML
     * 
     * @param array $config Configuration
     * @return string HTML
     */
    private function build_calculator_html($config) {
        $calculator_id = 'qcc-calculator-' . uniqid();
        $language = $this->detect_language($config['language']);
        
        $html = '<div class="qcc-calculator-fallback" id="' . $calculator_id . '" data-language="' . $language . '" data-currency="' . $config['currency'] . '">';
        
        // Inline CSS
        $html .= $this->get_inline_css();
        
        // Header
        $html .= $this->render_header($config, $language);
        
        // Controls
        $html .= $this->render_controls($config, $language);
        
        // Main content
        $html .= '<div class="qcc-main-content">';
        
        if ($config['layout'] === 'two_column') {
            $html .= '<div class="qcc-two-column-layout">';
            $html .= '<div class="qcc-column-left">' . $this->render_form($config, $language) . '</div>';
            $html .= '<div class="qcc-column-right">' . $this->render_results($config, $language) . '</div>';
            $html .= '</div>';
        } else {
            $html .= $this->render_form($config, $language);
            $html .= $this->render_results($config, $language);
        }
        
        $html .= '</div>'; // main-content
        
        // Inline JavaScript
        $html .= $this->get_inline_javascript($calculator_id, $config);
        
        $html .= '</div>'; // calculator-fallback
        
        return $html;
    }
    
    /**
     * Render header section
     * 
     * @param array $config Configuration
     * @param string $language Language
     * @return string HTML
     */
    private function render_header($config, $language) {
        $html = '<div class="qcc-header">';
        $html .= '<h3 class="qcc-title">' . $this->t($language, 'title') . '</h3>';
        $html .= '<p class="qcc-subtitle">' . $this->t($language, 'subtitle') . '</p>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render controls section
     * 
     * @param array $config Configuration
     * @param string $language Language
     * @return string HTML
     */
    private function render_controls($config, $language) {
        $html = '<div class="qcc-controls">';
        
        // Language selector
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-language">' . $this->t($language, 'language') . ':</label>';
        $html .= '<select id="qcc-language" name="language">';
        $html .= '<option value="en"' . selected($language, 'en', false) . '>English</option>';
        $html .= '<option value="de"' . selected($language, 'de', false) . '>Deutsch</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        // Currency selector
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-currency">' . $this->t($language, 'currency') . ':</label>';
        $html .= '<select id="qcc-currency" name="currency">';
        $html .= '<option value="EUR"' . selected($config['currency'], 'EUR', false) . '>Euro (€)</option>';
        $html .= '<option value="USD"' . selected($config['currency'], 'USD', false) . '>US Dollar ($)</option>';
        $html .= '<option value="GBP"' . selected($config['currency'], 'GBP', false) . '>British Pound (£)</option>';
        $html .= '<option value="JPY"' . selected($config['currency'], 'JPY', false) . '>Japanese Yen (¥)</option>';
        $html .= '<option value="CNY"' . selected($config['currency'], 'CNY', false) . '>Chinese Yuan (¥)</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        // Units selector
        $html .= '<div class="qcc-control-group">';
        $html .= '<label for="qcc-units">' . $this->t($language, 'units') . ':</label>';
        $html .= '<select id="qcc-units" name="units">';
        $html .= '<option value="pieces"' . selected($config['units'], 'pieces', false) . '>Pieces</option>';
        $html .= '<option value="kg"' . selected($config['units'], 'kg', false) . '>Kilograms</option>';
        $html .= '<option value="liters"' . selected($config['units'], 'liters', false) . '>Liters</option>';
        $html .= '<option value="hours"' . selected($config['units'], 'hours', false) . '>Hours</option>';
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render form section
     * 
     * @param array $config Configuration
     * @param string $language Language
     * @return string HTML
     */
    private function render_form($config, $language) {
        $html = '<div class="qcc-form-section">';
        
        // Basic inputs
        $html .= '<div class="qcc-input-section">';
        $html .= '<h4>Basic Parameters</h4>';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-production-volume">' . $this->t($language, 'production_volume') . ':</label>';
        $html .= '<input type="number" id="qcc-production-volume" name="production_volume" min="0" step="1" placeholder="10000">';
        $html .= '</div>';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-unit-price">' . $this->t($language, 'unit_price') . ':</label>';
        $html .= '<input type="number" id="qcc-unit-price" name="unit_price" min="0" step="0.01" placeholder="25.00">';
        $html .= '</div>';
        
        $html .= '<div class="qcc-input-group">';
        $html .= '<label for="qcc-production-cost">' . $this->t($language, 'production_cost') . ':</label>';
        $html .= '<input type="number" id="qcc-production-cost" name="production_cost" min="0" step="0.01" placeholder="15.00">';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // COGQ inputs
        if ($config['show_cogq']) {
            $html .= '<div class="qcc-input-section qcc-cogq-section">';
            $html .= '<h4 style="color: #28a745;">' . $this->t($language, 'cogq_title') . '</h4>';
            
            $html .= '<div class="qcc-input-group">';
            $html .= '<label for="qcc-prevention-cost">' . $this->t($language, 'prevention_cost') . ':</label>';
            $html .= '<input type="number" id="qcc-prevention-cost" name="prevention_cost" min="0" step="0.01" placeholder="5000">';
            $html .= '</div>';
            
            $html .= '<div class="qcc-input-group">';
            $html .= '<label for="qcc-appraisal-cost">' . $this->t($language, 'appraisal_cost') . ':</label>';
            $html .= '<input type="number" id="qcc-appraisal-cost" name="appraisal_cost" min="0" step="0.01" placeholder="3000">';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        // COPQ inputs
        if ($config['show_copq']) {
            $html .= '<div class="qcc-input-section qcc-copq-section">';
            $html .= '<h4 style="color: #dc3545;">' . $this->t($language, 'copq_title') . '</h4>';
            
            $html .= '<div class="qcc-input-group">';
            $html .= '<label for="qcc-internal-failure">' . $this->t($language, 'internal_failure') . ':</label>';
            $html .= '<input type="number" id="qcc-internal-failure" name="internal_failure" min="0" step="0.01" placeholder="2000">';
            $html .= '</div>';
            
            $html .= '<div class="qcc-input-group">';
            $html .= '<label for="qcc-external-failure">' . $this->t($language, 'external_failure') . ':</label>';
            $html .= '<input type="number" id="qcc-external-failure" name="external_failure" min="0" step="0.01" placeholder="1500">';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        // Action buttons
        $html .= '<div class="qcc-actions">';
        $html .= '<button type="button" id="qcc-calculate" class="qcc-btn qcc-btn-primary">' . $this->t($language, 'calculate') . '</button>';
        $html .= '<button type="button" id="qcc-reset" class="qcc-btn qcc-btn-secondary">' . $this->t($language, 'reset') . '</button>';
        $html .= '<button type="button" id="qcc-export" class="qcc-btn qcc-btn-success">' . $this->t($language, 'export_csv') . '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render results section
     * 
     * @param array $config Configuration
     * @param string $language Language
     * @return string HTML
     */
    private function render_results($config, $language) {
        $html = '<div class="qcc-results-section" id="qcc-results" style="display: none;">';
        
        // COGQ Results
        if ($config['show_cogq']) {
            $html .= '<div class="qcc-result-card qcc-cogq-card">';
            $html .= '<h4 style="color: #28a745;">' . $this->t($language, 'cogq_title') . '</h4>';
            $html .= '<div class="qcc-result-item">';
            $html .= '<span class="qcc-result-label">' . $this->t($language, 'prevention_cost') . ':</span>';
            $html .= '<span class="qcc-result-value" id="result-prevention">--</span>';
            $html .= '</div>';
            $html .= '<div class="qcc-result-item">';
            $html .= '<span class="qcc-result-label">' . $this->t($language, 'appraisal_cost') . ':</span>';
            $html .= '<span class="qcc-result-value" id="result-appraisal">--</span>';
            $html .= '</div>';
            $html .= '<div class="qcc-result-total">';
            $html .= '<span class="qcc-result-label">' . $this->t($language, 'total_cogq') . ':</span>';
            $html .= '<span class="qcc-result-value" id="result-total-cogq">--</span>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // COPQ Results
        if ($config['show_copq']) {
            $html .= '<div class="qcc-result-card qcc-copq-card">';
            $html .= '<h4 style="color: #dc3545;">' . $this->t($language, 'copq_title') . '</h4>';
            $html .= '<div class="qcc-result-item">';
            $html .= '<span class="qcc-result-label">' . $this->t($language, 'internal_failure') . ':</span>';
            $html .= '<span class="qcc-result-value" id="result-internal-failure">--</span>';
            $html .= '</div>';
            $html .= '<div class="qcc-result-item">';
            $html .= '<span class="qcc-result-label">' . $this->t($language, 'external_failure') . ':</span>';
            $html .= '<span class="qcc-result-value" id="result-external-failure">--</span>';
            $html .= '</div>';
            $html .= '<div class="qcc-result-total">';
            $html .= '<span class="qcc-result-label">' . $this->t($language, 'total_copq') . ':</span>';
            $html .= '<span class="qcc-result-value" id="result-total-copq">--</span>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Summary Results
        $html .= '<div class="qcc-result-card qcc-summary-card">';
        $html .= '<h4>Summary</h4>';
        $html .= '<div class="qcc-result-item">';
        $html .= '<span class="qcc-result-label">' . $this->t($language, 'total_quality_cost') . ':</span>';
        $html .= '<span class="qcc-result-value" id="result-total-quality-cost">--</span>';
        $html .= '</div>';
        $html .= '<div class="qcc-result-item">';
        $html .= '<span class="qcc-result-label">' . $this->t($language, 'cogq_percentage') . ':</span>';
        $html .= '<span class="qcc-result-value" id="result-cogq-percentage">--</span>';
        $html .= '</div>';
        $html .= '<div class="qcc-result-item">';
        $html .= '<span class="qcc-result-label">' . $this->t($language, 'copq_percentage') . ':</span>';
        $html .= '<span class="qcc-result-value" id="result-copq-percentage">--</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get inline CSS
     * 
     * @return string CSS
     */
    private function get_inline_css() {
        return '<style>
        .qcc-calculator-fallback {
            max-width: 1000px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
        }
        .qcc-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .qcc-title {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
        }
        .qcc-subtitle {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .qcc-controls {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            flex-wrap: wrap;
        }
        .qcc-control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .qcc-control-group label {
            font-weight: 500;
            font-size: 14px;
            color: #495057;
        }
        .qcc-control-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background: white;
        }
        .qcc-two-column-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 768px) {
            .qcc-two-column-layout {
                grid-template-columns: 1fr;
            }
            .qcc-controls {
                flex-direction: column;
            }
        }
        .qcc-input-section {
            margin-bottom: 25px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: white;
        }
        .qcc-input-section h4 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f3f4;
        }
        .qcc-cogq-section {
            border-left: 4px solid #28a745;
        }
        .qcc-copq-section {
            border-left: 4px solid #dc3545;
        }
        .qcc-input-group {
            margin-bottom: 15px;
        }
        .qcc-input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }
        .qcc-input-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.15s ease-in-out;
        }
        .qcc-input-group input:focus {
            border-color: #80bdff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .qcc-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .qcc-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }
        .qcc-btn-primary {
            background: #007bff;
            color: white;
        }
        .qcc-btn-primary:hover {
            background: #0056b3;
        }
        .qcc-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .qcc-btn-secondary:hover {
            background: #545b62;
        }
        .qcc-btn-success {
            background: #28a745;
            color: white;
        }
        .qcc-btn-success:hover {
            background: #1e7e34;
        }
        .qcc-results-section {
            margin-top: 30px;
        }
        .qcc-result-card {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .qcc-result-card h4 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f3f4;
        }
        .qcc-cogq-card {
            border-left: 4px solid #28a745;
        }
        .qcc-copq-card {
            border-left: 4px solid #dc3545;
        }
        .qcc-summary-card {
            border-left: 4px solid #17a2b8;
        }
        .qcc-result-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .qcc-result-total {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding: 12px 0;
            border-top: 2px solid #e9ecef;
            font-weight: 600;
            font-size: 18px;
        }
        .qcc-result-label {
            color: #495057;
        }
        .qcc-result-value {
            font-weight: 600;
            color: #212529;
        }
        </style>';
    }
    
    /**
     * Get inline JavaScript
     * 
     * @param string $calculator_id Calculator ID
     * @param array $config Configuration
     * @return string JavaScript
     */
    private function get_inline_javascript($calculator_id, $config) {
        return '<script>
        (function() {
            const calculator = document.getElementById("' . $calculator_id . '");
            if (!calculator) return;
            
            const inputs = {
                productionVolume: calculator.querySelector("#qcc-production-volume"),
                unitPrice: calculator.querySelector("#qcc-unit-price"),
                productionCost: calculator.querySelector("#qcc-production-cost"),
                preventionCost: calculator.querySelector("#qcc-prevention-cost"),
                appraisalCost: calculator.querySelector("#qcc-appraisal-cost"),
                internalFailure: calculator.querySelector("#qcc-internal-failure"),
                externalFailure: calculator.querySelector("#qcc-external-failure")
            };
            
            const results = calculator.querySelector("#qcc-results");
            const calculateBtn = calculator.querySelector("#qcc-calculate");
            const resetBtn = calculator.querySelector("#qcc-reset");
            const exportBtn = calculator.querySelector("#qcc-export");
            const currencySelect = calculator.querySelector("#qcc-currency");
            
            let currentCurrency = "' . $config['currency'] . '";
            
            // Currency symbol mapping
            const currencySymbols = {
                EUR: "€",
                USD: "$",
                GBP: "£",
                JPY: "¥",
                CNY: "¥"
            };
            
            function formatCurrency(value) {
                const symbol = currencySymbols[currentCurrency] || "€";
                return symbol + " " + parseFloat(value).toLocaleString("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            function formatPercentage(value) {
                return parseFloat(value).toFixed(1) + "%";
            }
            
            function getInputValue(input) {
                return input ? parseFloat(input.value) || 0 : 0;
            }
            
            function updateResult(elementId, value, isPercentage = false) {
                const element = calculator.querySelector("#" + elementId);
                if (element) {
                    element.textContent = isPercentage ? formatPercentage(value) : formatCurrency(value);
                }
            }
            
            function calculateResults() {
                const data = {
                    productionVolume: getInputValue(inputs.productionVolume),
                    unitPrice: getInputValue(inputs.unitPrice),
                    productionCost: getInputValue(inputs.productionCost),
                    preventionCost: getInputValue(inputs.preventionCost),
                    appraisalCost: getInputValue(inputs.appraisalCost),
                    internalFailure: getInputValue(inputs.internalFailure),
                    externalFailure: getInputValue(inputs.externalFailure)
                };
                
                // Validate required fields
                if (data.productionVolume <= 0 || data.unitPrice <= 0) {
                    alert("Please enter valid production volume and unit price.");
                    return;
                }
                
                // Calculate totals
                const totalCOGQ = data.preventionCost + data.appraisalCost;
                const totalCOPQ = data.internalFailure + data.externalFailure;
                const totalQualityCost = totalCOGQ + totalCOPQ;
                
                // Calculate percentages
                const cogqPercentage = totalQualityCost > 0 ? (totalCOGQ / totalQualityCost) * 100 : 0;
                const copqPercentage = totalQualityCost > 0 ? (totalCOPQ / totalQualityCost) * 100 : 0;
                
                // Update results
                updateResult("result-prevention", data.preventionCost);
                updateResult("result-appraisal", data.appraisalCost);
                updateResult("result-total-cogq", totalCOGQ);
                updateResult("result-internal-failure", data.internalFailure);
                updateResult("result-external-failure", data.externalFailure);
                updateResult("result-total-copq", totalCOPQ);
                updateResult("result-total-quality-cost", totalQualityCost);
                updateResult("result-cogq-percentage", cogqPercentage, true);
                updateResult("result-copq-percentage", copqPercentage, true);
                
                // Show results
                if (results) {
                    results.style.display = "block";
                    results.scrollIntoView({ behavior: "smooth", block: "nearest" });
                }
                
                // Store results for export
                calculator.calculationResults = {
                    ...data,
                    totalCOGQ,
                    totalCOPQ,
                    totalQualityCost,
                    cogqPercentage,
                    copqPercentage,
                    currency: currentCurrency,
                    timestamp: new Date().toISOString()
                };
            }
            
            function resetCalculator() {
                // Reset all inputs
                Object.values(inputs).forEach(input => {
                    if (input) input.value = "";
                });
                
                // Hide results
                if (results) {
                    results.style.display = "none";
                }
                
                // Clear stored results
                delete calculator.calculationResults;
            }
            
            function exportToCSV() {
                if (!calculator.calculationResults) {
                    alert("Please calculate results first.");
                    return;
                }
                
                const data = calculator.calculationResults;
                const csvContent = [
                    ["Metric", "Value", "Currency"],
                    ["Production Volume", data.productionVolume, "units"],
                    ["Unit Price", data.unitPrice, data.currency],
                    ["Production Cost", data.productionCost, data.currency],
                    ["Prevention Cost", data.preventionCost, data.currency],
                    ["Appraisal Cost", data.appraisalCost, data.currency],
                    ["Total COGQ", data.totalCOGQ, data.currency],
                    ["Internal Failure Cost", data.internalFailure, data.currency],
                    ["External Failure Cost", data.externalFailure, data.currency],
                    ["Total COPQ", data.totalCOPQ, data.currency],
                    ["Total Quality Cost", data.totalQualityCost, data.currency],
                    ["COGQ Percentage", data.cogqPercentage.toFixed(1), "%"],
                    ["COPQ Percentage", data.copqPercentage.toFixed(1), "%"],
                    ["Generated", data.timestamp, ""]
                ].map(row => row.map(cell => "\\"" + cell + "\\"").join(",")).join("\\n");
                
                const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = "quality_cost_analysis_" + new Date().toISOString().split("T")[0] + ".csv";
                link.click();
            }
            
            function updateCurrency() {
                currentCurrency = currencySelect.value;
                calculator.setAttribute("data-currency", currentCurrency);
                
                // Recalculate if results exist
                if (calculator.calculationResults) {
                    calculateResults();
                }
            }
            
            // Event listeners
            if (calculateBtn) {
                calculateBtn.addEventListener("click", calculateResults);
            }
            
            if (resetBtn) {
                resetBtn.addEventListener("click", resetCalculator);
            }
            
            if (exportBtn) {
                exportBtn.addEventListener("click", exportToCSV);
            }
            
            if (currencySelect) {
                currencySelect.addEventListener("change", updateCurrency);
            }
            
            // Auto-calculate on input change (debounced)
            let calculateTimeout;
            Object.values(inputs).forEach(input => {
                if (input) {
                    input.addEventListener("input", function() {
                        clearTimeout(calculateTimeout);
                        calculateTimeout = setTimeout(() => {
                            if (getInputValue(inputs.productionVolume) > 0 && getInputValue(inputs.unitPrice) > 0) {
                                calculateResults();
                            }
                        }, 500);
                    });
                }
            });
            
            // Language switching
            const languageSelect = calculator.querySelector("#qcc-language");
            if (languageSelect) {
                languageSelect.addEventListener("change", function() {
                    // For fallback renderer, we would need to reload with new language
                    // This is a simplified implementation
                    calculator.setAttribute("data-language", this.value);
                    console.log("Language changed to:", this.value);
                });
            }
            
        })();
        </script>';
    }
    
    /**
     * Render emergency fallback
     * 
     * @param array $atts Attributes
     * @param string $error_message Error message
     * @return string Emergency HTML
     */
    public function render_emergency_fallback($atts, $error_message) {
        return '<div class="qcc-emergency-fallback" style="max-width: 600px; margin: 20px auto; padding: 20px; border: 2px solid #dc3545; border-radius: 8px; background: #f8d7da; color: #721c24; text-align: center;">
            <h3>🚨 Quality Cost Calculator - Emergency Mode</h3>
            <p>The calculator system encountered a critical error and is running in emergency mode.</p>
            <p><strong>Error:</strong> ' . esc_html($error_message) . '</p>
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; color: #856404;">
                <p><strong>Emergency Calculator:</strong></p>
                <div style="text-align: left; margin-top: 10px;">
                    <strong>COGQ Formula:</strong> Prevention Costs + Appraisal Costs<br>
                    <strong>COPQ Formula:</strong> Internal Failure + External Failure<br>
                    <strong>Total Quality Cost:</strong> COGQ + COPQ<br>
                    <strong>COGQ %:</strong> (COGQ / Total Quality Cost) × 100<br>
                    <strong>COPQ %:</strong> (COPQ / Total Quality Cost) × 100
                </div>
            </div>
            <p style="margin-top: 15px; font-size: 14px;">Please refresh the page or contact support if this problem persists.</p>
        </div>';
    }
    
    /**
     * Translate text with fallback
     * 
     * @param string $language Language code
     * @param string $key Translation key
     * @param string $fallback Fallback text
     * @return string Translated text
     */
    private function t($language, $key, $fallback = null) {
        // Try translator service first
        if ($this->translator) {
            try {
                return $this->translator->get($key, $fallback);
            } catch (Exception $e) {
                // Fall through to manual translations
            }
        }
        
        // Use manual translations
        if (isset($this->translations[$language][$key])) {
            return $this->translations[$language][$key];
        }
        
        // Fallback to English
        if ($language !== 'en' && isset($this->translations['en'][$key])) {
            return $this->translations['en'][$key];
        }
        
        // Final fallback
        return $fallback ?: $key;
    }
    
    /**
     * Detect language from config
     * 
     * @param string $language_config Language configuration
     * @return string Detected language
     */
    private function detect_language($language_config) {
        if ($language_config === 'auto') {
            // Try to detect from WordPress locale
            $locale = get_locale();
            if (strpos($locale, 'de') === 0) {
                return 'de';
            }
            return 'en';
        }
        
        return in_array($language_config, array('en', 'de')) ? $language_config : 'en';
    }
    
    /**
     * Get system information for debugging
     * 
     * @return array System info
     */
    public function get_system_info() {
        return array(
            'renderer_type' => 'fallback',
            'translator_available' => $this->translator !== null,
            'supported_languages' => array_keys($this->translations),
            'default_config' => $this->default_config,
            'version' => '2.0.0'
        );
    }
}
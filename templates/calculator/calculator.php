<?php
/**
 * Quality Cost Calculator Template - Updated with German Language
 * 
 * This template renders the Quality Cost Calculator interface
 * Called by the [quality_cost_calculator] shortcode
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}


// Debug in calculator.php hinzufügen:
echo '<div style="background: orange; padding: 10px;">
🔍 Template-Debug:
- Results-Section existiert: ' . (file_exists('templates/calculator/sections/results-section.php') ? 'JA' : 'NEIN') . '
- Template-Manager: ' . (isset($template_manager) ? 'AKTIV' : 'FEHLT') . '
- Template-Router: ' . (class_exists('QCC_Template_Router') ? 'VORHANDEN' : 'FEHLT') . '
</div>';




// Get shortcode attributes (passed from the shortcode function)
$default_language = isset($atts['language']) ? $atts['language'] : 'en';
$default_currency = isset($atts['currency']) ? $atts['currency'] : 'EUR';
$default_unit = isset($atts['unit']) ? $atts['unit'] : '1000000';

?>

<div id="qcc-calculator-container" class="qcc-calculator-wrapper">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Lato:wght@300;400;700&display=swap');
        
        .qcc-calculator-wrapper {
            --color-primary: #449775;
            --color-secondary: #E7F9DE;
            --color-dark: #141C14;
            --color-gray: #353535;
            --color-light: #DFEEC0;
        }

        .qcc-calculator-wrapper * {
            box-sizing: border-box;
        }

        .qcc-calculator-wrapper {
            font-family: 'Lato', sans-serif;
            background-color: #f8f9fa;
            color: var(--color-dark);
            line-height: 1.6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .qcc-header {
            background: linear-gradient(135deg, var(--color-primary), var(--color-dark));
            color: white;
            padding: 30px 0;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .qcc-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            margin-top: 0;
        }

        .qcc-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .qcc-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .qcc-control-group {
            display: flex;
            flex-direction: column;
        }

        .qcc-control-group label {
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--color-dark);
        }

        .qcc-control-group select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s;
        }

        .qcc-control-group select:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .qcc-main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
            margin-bottom: 30px;
        }

        .qcc-input-section, .qcc-results-section, .qcc-chart-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .qcc-section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--color-dark);
            border-bottom: 3px solid var(--color-primary);
            padding-bottom: 10px;
            margin-top: 0;
        }

        .qcc-input-group {
            margin-bottom: 20px;
        }

        .qcc-input-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--color-dark);
        }

        .qcc-input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .qcc-input-group input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .qcc-input-group input.error {
            border-color: #dc3545;
            background-color: #ffeaea;
        }

        .qcc-cost-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .qcc-cost-section {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
        }

        .qcc-cost-section.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .qcc-cost-section.error {
            border-color: #dc3545;
        }

        .qcc-percentage-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--color-dark);
        }

        .qcc-percentage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .qcc-error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            font-weight: 500;
            display: none;
        }

        .qcc-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .qcc-result-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid var(--color-primary);
            text-align: center;
        }

        .qcc-result-label {
            font-weight: 500;
            color: var(--color-dark);
            margin-bottom: 8px;
            font-size: 14px;
        }

        .qcc-result-value {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.4rem;
            color: var(--color-primary);
        }

        .qcc-charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .qcc-chart-item {
            display: flex;
            flex-direction: column;
        }

        .qcc-chart-item h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--color-dark);
            text-align: center;
            margin-top: 0;
        }

        .qcc-chart-container {
            position: relative;
            height: 300px;
        }

        .qcc-export-section {
            text-align: center;
            margin-top: 20px;
        }

        .qcc-export-btn {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 0 10px;
        }

        .qcc-export-btn:hover {
            background: #3a7d63;
        }

        @media (max-width: 768px) {
            .qcc-controls {
                grid-template-columns: 1fr;
            }
            
            .qcc-cost-sections {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .qcc-percentage-grid {
                grid-template-columns: 1fr;
            }
            
            .qcc-results-grid {
                grid-template-columns: 1fr;
            }
            
            .qcc-charts-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .qcc-header h1 {
                font-size: 2rem;
            }
        }
    </style>

    <div class="qcc-header">
        <h1 id="qcc-title">Quality Cost Calculator</h1>
        <p id="qcc-subtitle">Cost of Quality Analysis Tool</p>
    </div>

    <div class="qcc-controls">
        <div class="qcc-control-group">
            <label for="qcc-language" id="qcc-lang-label">Language:</label>
            <select id="qcc-language">
                <option value="en" <?php selected($default_language, 'en'); ?>>English</option>
                <option value="de" <?php selected($default_language, 'de'); ?>>Deutsch</option>
                <option value="fr" <?php selected($default_language, 'fr'); ?>>Français</option>
                <option value="es" <?php selected($default_language, 'es'); ?>>Español</option>
                <option value="zh" <?php selected($default_language, 'zh'); ?>>中文</option>
            </select>
        </div>
        <div class="qcc-control-group">
            <label for="qcc-currency" id="qcc-currency-label">Currency:</label>
            <select id="qcc-currency">
                <option value="€" <?php selected($default_currency, 'EUR'); ?>>Euro (€)</option>
                <option value="$" <?php selected($default_currency, 'USD'); ?>>US-Dollar ($)</option>
                <option value="¥" <?php selected($default_currency, 'CNY'); ?>>Renminbi (¥)</option>
            </select>
        </div>
        <div class="qcc-control-group">
            <label for="qcc-unit" id="qcc-unit-label">Display Unit:</label>
            <select id="qcc-unit">
                <option value="1000000" <?php selected($default_unit, '1000000'); ?>>Millions</option>
                <option value="1000000000" <?php selected($default_unit, '1000000000'); ?>>Billions</option>
            </select>
        </div>
        <div class="qcc-control-group">
            <label for="qcc-opportunity-toggle" id="qcc-opportunity-toggle-label">Opportunity Costs:</label>
            <select id="qcc-opportunity-toggle">
                <option value="disabled">Disabled</option>
                <option value="enabled">Enabled</option>
            </select>
        </div>
    </div>

    <div class="qcc-main-content">
        <div class="qcc-input-section">
            <h2 class="qcc-section-title" id="qcc-input-title">Input Parameters</h2>
            
            <div class="qcc-input-group">
                <label for="qcc-revenue" id="qcc-revenue-label">Revenue:</label>
                <input type="number" id="qcc-revenue" value="140" step="0.01">
            </div>
            
            <div class="qcc-input-group">
                <label for="qcc-quality-percentage" id="qcc-quality-percentage-label">Quality Cost Basis (% of Revenue):</label>
                <input type="number" id="qcc-quality-percentage" value="6" step="0.01" min="0" max="100">
            </div>
            
            <div class="qcc-cost-sections">
                <div class="qcc-cost-section" id="qcc-cost-distribution-section">
                    <div class="qcc-percentage-title" id="qcc-cost-distribution-title">Cost Distribution (%):</div>
                    
                    <div class="qcc-percentage-grid">
                        <div class="qcc-input-group">
                            <label for="qcc-prevention" id="qcc-prevention-label">Prevention Costs:</label>
                            <input type="number" id="qcc-prevention" value="10" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="qcc-input-group">
                            <label for="qcc-appraisal" id="qcc-appraisal-label">Appraisal Costs:</label>
                            <input type="number" id="qcc-appraisal" value="20" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="qcc-input-group">
                            <label for="qcc-internal-defect" id="qcc-internal-defect-label">Internal Defect Costs:</label>
                            <input type="number" id="qcc-internal-defect" value="30" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="qcc-input-group">
                            <label for="qcc-external-defect" id="qcc-external-defect-label">External Defect Costs:</label>
                            <input type="number" id="qcc-external-defect" value="40" step="0.01" min="0" max="100">
                        </div>
                    </div>
                    
                    <div class="qcc-error-message" id="qcc-percentage-error">
                        Values do not add up to 100%
                    </div>
                </div>

                <div class="qcc-cost-section disabled" id="qcc-opportunity-section">
                    <div class="qcc-percentage-title" id="qcc-opportunity-factors-title">Opportunity Cost Factors (%):</div>
                    
                    <div class="qcc-percentage-grid">
                        <div class="qcc-input-group">
                            <label for="qcc-lost-sales" id="qcc-lost-sales-label">Lost Sales:</label>
                            <input type="number" id="qcc-lost-sales" value="5" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="qcc-input-group">
                            <label for="qcc-customer-churn" id="qcc-customer-churn-label">Customer Churn:</label>
                            <input type="number" id="qcc-customer-churn" value="2" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="qcc-input-group">
                            <label for="qcc-market-share-loss" id="qcc-market-share-loss-label">Market Share Loss:</label>
                            <input type="number" id="qcc-market-share-loss" value="1" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="qcc-input-group">
                            <label for="qcc-productivity-loss" id="qcc-productivity-loss-label">Productivity Loss:</label>
                            <input type="number" id="qcc-productivity-loss" value="3" step="0.01" min="0" max="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="qcc-results-section" id="qcc-results-section">
            <h2 class="qcc-section-title" id="qcc-results-title">Calculated Results</h2>
            
            <div class="qcc-results-columns" id="qcc-results-columns">
                <!-- Direct Quality Costs -->
                <div class="qcc-results-column">
                    <h3 id="qcc-direct-costs-title">Direct Quality Costs</h3>
                    <div class="qcc-results-grid">
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-prevention-cost-label">Prevention Cost:</span>
                            <span class="qcc-result-value" id="qcc-prevention-cost">0.00</span>
                        </div>
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-appraisal-cost-label">Appraisal Cost:</span>
                            <span class="qcc-result-value" id="qcc-appraisal-cost">0.00</span>
                        </div>
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-internal-defect-cost-label">Internal Defect Cost:</span>
                            <span class="qcc-result-value" id="qcc-internal-defect-cost">0.00</span>
                        </div>
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-external-defect-cost-label">External Defect Cost:</span>
                            <span class="qcc-result-value" id="qcc-external-defect-cost">0.00</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--color-primary);">
                        <div class="qcc-result-item" style="background: var(--color-primary); color: white; border-left: none;">
                            <span class="qcc-result-label" id="qcc-total-quality-cost-label" style="color: white;">Total Quality Cost:</span>
                            <span class="qcc-result-value" id="qcc-total-quality-cost" style="color: white;">0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Opportunity Costs (initially hidden) -->
                <div class="qcc-results-column qcc-opportunity-results" id="qcc-opportunity-results" style="display: none;">
                    <h3 id="qcc-opportunity-costs-title">Opportunity Costs</h3>
                    <div class="qcc-results-grid">
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-lost-sales-cost-label">Lost Sales Cost:</span>
                            <span class="qcc-result-value" id="qcc-lost-sales-cost">0.00</span>
                        </div>
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-customer-churn-cost-label">Customer Churn Cost:</span>
                            <span class="qcc-result-value" id="qcc-customer-churn-cost">0.00</span>
                        </div>
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-market-share-cost-label">Market Share Cost:</span>
                            <span class="qcc-result-value" id="qcc-market-share-cost">0.00</span>
                        </div>
                        <div class="qcc-result-item">
                            <span class="qcc-result-label" id="qcc-productivity-cost-label">Productivity Cost:</span>
                            <span class="qcc-result-value" id="qcc-productivity-cost">0.00</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--color-primary);">
                        <div class="qcc-result-item" style="background: var(--color-primary); color: white; border-left: none;">
                            <span class="qcc-result-label" id="qcc-total-opportunity-cost-label" style="color: white;">Total Opportunity Cost:</span>
                            <span class="qcc-result-value" id="qcc-total-opportunity-cost" style="color: white;">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Impact (shown when opportunity costs are enabled) -->
            <div class="qcc-total-impact" id="qcc-total-impact" style="display: none; margin-top: 20px; padding: 20px; background: linear-gradient(135deg, var(--color-primary), var(--color-dark)); color: white; border-radius: 8px; text-align: center;">
                <h3 id="qcc-total-impact-title" style="color: white; border-bottom: 2px solid rgba(255,255,255,0.3); margin-bottom: 15px;">Total Cost of Poor Quality</h3>
                <div class="qcc-result-item" style="background: transparent; border: none;">
                    <span class="qcc-result-label" style="color: white; font-size: 16px;" id="qcc-total-coq-label">Combined Impact:</span>
                    <span class="qcc-result-value" style="color: white; font-size: 1.8rem;" id="qcc-total-coq">0.00</span>
                </div>
                <div style="margin-top: 10px; font-size: 14px; opacity: 0.9;">
                    <span id="qcc-revenue-percentage-label">% of Revenue:</span> <span id="qcc-revenue-percentage">0.0%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="qcc-chart-section">
        <h2 class="qcc-section-title" id="qcc-chart-title">Quality Cost Distribution</h2>
        <div class="qcc-charts-container">
            <div class="qcc-chart-item">
                <h3 id="qcc-bar-chart-title">Cost Values</h3>
                <div class="qcc-chart-container">
                    <canvas id="qcc-qualityChart"></canvas>
                </div>
            </div>
            <div class="qcc-chart-item">
                <h3 id="qcc-pie-chart-title">Percentage Distribution</h3>
                <div class="qcc-chart-container">
                    <canvas id="qcc-pieChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="qcc-export-section">
        <button class="qcc-export-btn" onclick="qccExportToCSV()" id="qcc-export-csv">Export to CSV</button>
        <button class="qcc-export-btn" onclick="qccExportToPDF()" id="qcc-export-pdf">Export to PDF</button>
    </div>

    <script>
        // Quality Cost Calculator JavaScript - Updated with German translations
        (function() {
            'use strict';

            // Translation object - UPDATED with complete German translations
            const qccTranslations = {
                en: {
                    title: "Quality Cost Calculator",
                    subtitle: "Cost of Quality Analysis Tool",
                    "lang-label": "Language:",
                    "currency-label": "Currency:",
                    "unit-label": "Display Unit:",
                    "input-title": "Input Parameters",
                    "results-title": "Calculated Results",
                    "chart-title": "Quality Cost Distribution",
                    "revenue-label": "Revenue:",
                    "quality-percentage-label": "Quality Cost Basis (% of Revenue):",
                    "cost-distribution-title": "Cost Distribution (%):",
                    "prevention-label": "Prevention Costs:",
                    "appraisal-label": "Appraisal Costs:",
                    "internal-defect-label": "Internal Defect Costs:",
                    "external-defect-label": "External Defect Costs:",
                    "total-quality-cost-label": "Total Quality Cost:",
                    "prevention-cost-label": "Prevention Cost:",
                    "appraisal-cost-label": "Appraisal Cost:",
                    "internal-defect-cost-label": "Internal Defect Cost:",
                    "external-defect-cost-label": "External Defect Cost:",
                    "percentage-error": "Values do not add up to 100%",
                    "export-csv": "Export to CSV",
                    "export-pdf": "Export to PDF",
                    "bar-chart-title": "Cost Values",
                    "pie-chart-title": "Percentage Distribution",
                    "opportunity-toggle-label": "Opportunity Costs:",
                    "opportunity-factors-title": "Opportunity Cost Factors (%):",
                    "lost-sales-label": "Lost Sales:",
                    "customer-churn-label": "Customer Churn:",
                    "market-share-loss-label": "Market Share Loss:",
                    "productivity-loss-label": "Productivity Loss:",
                    "direct-costs-title": "Direct Quality Costs",
                    "opportunity-costs-title": "Opportunity Costs",
                    "lost-sales-cost-label": "Lost Sales Cost:",
                    "customer-churn-cost-label": "Customer Churn Cost:",
                    "market-share-cost-label": "Market Share Cost:",
                    "productivity-cost-label": "Productivity Cost:",
                    "total-opportunity-cost-label": "Total Opportunity Cost:",
                    "total-impact-title": "Total Cost of Poor Quality",
                    "total-coq-label": "Combined Impact:",
                    "revenue-percentage-label": "% of Revenue:",
                    "prevention": "Prevention",
                    "appraisal": "Appraisal",
                    "internal-defect": "Internal Defect",
                    "external-defect": "External Defect"
                },
                de: {
                    title: "Qualitätskostenrechner",
                    subtitle: "Werkzeug zur Qualitätskostenanalyse",
                    "lang-label": "Sprache:",
                    "currency-label": "Währung:",
                    "unit-label": "Anzeigeeinheit:",
                    "input-title": "Eingabeparameter",
                    "results-title": "Berechnete Ergebnisse",
                    "chart-title": "Qualitätskostenverteilung",
                    "revenue-label": "Umsatz:",
                    "quality-percentage-label": "Qualitätskostenbasis (% vom Umsatz):",
                    "cost-distribution-title": "Kostenverteilung (%):",
                    "prevention-label": "Präventionskosten:",
                    "appraisal-label": "Bewertungskosten:",
                    "internal-defect-label": "Interne Fehlerkosten:",
                    "external-defect-label": "Externe Fehlerkosten:",
                    "total-quality-cost-label": "Gesamte Qualitätskosten:",
                    "prevention-cost-label": "Präventionskosten:",
                    "appraisal-cost-label": "Bewertungskosten:",
                    "internal-defect-cost-label": "Interne Fehlerkosten:",
                    "external-defect-cost-label": "Externe Fehlerkosten:",
                    "percentage-error": "Werte ergeben nicht 100%",
                    "export-csv": "Als CSV exportieren",
                    "export-pdf": "Als PDF exportieren",
                    "bar-chart-title": "Kostenwerte",
                    "pie-chart-title": "Prozentuale Verteilung",
                    "opportunity-toggle-label": "Opportunitätskosten:",
                    "opportunity-factors-title": "Opportunitätskostenfaktoren (%):",
                    "lost-sales-label": "Entgangene Verkäufe:",
                    "customer-churn-label": "Kundenverlust:",
                    "market-share-loss-label": "Marktanteilsverlust:",
                    "productivity-loss-label": "Produktivitätsverlust:",
                    "direct-costs-title": "Direkte Qualitätskosten",
                    "opportunity-costs-title": "Opportunitätskosten",
                    "lost-sales-cost-label": "Kosten entgangener Verkäufe:",
                    "customer-churn-cost-label": "Kundenverlustkosten:",
                    "market-share-cost-label": "Marktanteilskosten:",
                    "productivity-cost-label": "Produktivitätskosten:",
                    "total-opportunity-cost-label": "Gesamte Opportunitätskosten:",
                    "total-impact-title": "Gesamtkosten schlechter Qualität",
                    "total-coq-label": "Kombinierte Auswirkung:",
                    "revenue-percentage-label": "% des Umsatzes:",
                    "prevention": "Prävention",
                    "appraisal": "Bewertung",
                    "internal-defect": "Interner Fehler",
                    "external-defect": "Externer Fehler"
                },
                fr: {
                    title: "Calculateur de Coûts de Qualité",
                    subtitle: "Outil d'Analyse des Coûts de Qualité",
                    "lang-label": "Langue:",
                    "currency-label": "Devise:",
                    "unit-label": "Unité d'Affichage:",
                    "input-title": "Paramètres d'Entrée",
                    "results-title": "Résultats Calculés",
                    "chart-title": "Distribution des Coûts de Qualité",
                    "revenue-label": "Chiffre d'Affaires:",
                    "quality-percentage-label": "Base de Coût Qualité (% du CA):",
                    "cost-distribution-title": "Distribution des Coûts (%):",
                    "prevention-label": "Coûts de Prévention:",
                    "appraisal-label": "Coûts d'Évaluation:",
                    "internal-defect-label": "Coûts de Défauts Internes:",
                    "external-defect-label": "Coûts de Défauts Externes:",
                    "total-quality-cost-label": "Coût Total de Qualité:",
                    "prevention-cost-label": "Coût de Prévention:",
                    "appraisal-cost-label": "Coût d'Évaluation:",
                    "internal-defect-cost-label": "Coût de Défaut Interne:",
                    "external-defect-cost-label": "Coût de Défaut Externe:",
                    "percentage-error": "Les valeurs ne totalisent pas 100%",
                    "export-csv": "Exporter en CSV",
                    "export-pdf": "Exporter en PDF",
                    "bar-chart-title": "Valeurs de Coût",
                    "pie-chart-title": "Distribution en Pourcentage",
                    "opportunity-toggle-label": "Coûts d'Opportunité:",
                    "opportunity-factors-title": "Facteurs de Coût d'Opportunité (%):",
                    "lost-sales-label": "Ventes Perdues:",
                    "customer-churn-label": "Perte de Clients:",
                    "market-share-loss-label": "Perte de Part de Marché:",
                    "productivity-loss-label": "Perte de Productivité:",
                    "direct-costs-title": "Coûts Directs de Qualité",
                    "opportunity-costs-title": "Coûts d'Opportunité",
                    "lost-sales-cost-label": "Coût des Ventes Perdues:",
                    "customer-churn-cost-label": "Coût de Perte de Clients:",
                    "market-share-cost-label": "Coût de Part de Marché:",
                    "productivity-cost-label": "Coût de Productivité:",
                    "total-opportunity-cost-label": "Coût Total d'Opportunité:",
                    "total-impact-title": "Coût Total de la Mauvaise Qualité",
                    "total-coq-label": "Impact Combiné:",
                    "revenue-percentage-label": "% du CA:",
                    "prevention": "Prévention",
                    "appraisal": "Évaluation",
                    "internal-defect": "Défaut Interne",
                    "external-defect": "Défaut Externe"
                },
                es: {
                    title: "Calculadora de Costos de Calidad",
                    subtitle: "Herramienta de Análisis de Costos de Calidad",
                    "lang-label": "Idioma:",
                    "currency-label": "Moneda:",
                    "unit-label": "Unidad de Visualización:",
                    "input-title": "Parámetros de Entrada",
                    "results-title": "Resultados Calculados",
                    "chart-title": "Distribución de Costos de Calidad",
                    "revenue-label": "Ingresos:",
                    "quality-percentage-label": "Base de Costo de Calidad (% de Ingresos):",
                    "cost-distribution-title": "Distribución de Costos (%):",
                    "prevention-label": "Costos de Prevención:",
                    "appraisal-label": "Costos de Evaluación:",
                    "internal-defect-label": "Costos de Defectos Internos:",
                    "external-defect-label": "Costos de Defectos Externos:",
                    "total-quality-cost-label": "Costo Total de Calidad:",
                    "prevention-cost-label": "Costo de Prevención:",
                    "appraisal-cost-label": "Costo de Evaluación:",
                    "internal-defect-cost-label": "Costo de Defecto Interno:",
                    "external-defect-cost-label": "Costo de Defecto Externo:",
                    "percentage-error": "Los valores no suman 100%",
                    "export-csv": "Exportar a CSV",
                    "export-pdf": "Exportar a PDF",
                    "bar-chart-title": "Valores de Costo",
                    "pie-chart-title": "Distribución Porcentual",
                    "opportunity-toggle-label": "Costos de Oportunidad:",
                    "opportunity-factors-title": "Factores de Costo de Oportunidad (%):",
                    "lost-sales-label": "Ventas Perdidas:",
                    "customer-churn-label": "Pérdida de Clientes:",
                    "market-share-loss-label": "Pérdida de Cuota de Mercado:",
                    "productivity-loss-label": "Pérdida de Productividad:",
                    "direct-costs-title": "Costos Directos de Calidad",
                    "opportunity-costs-title": "Costos de Oportunidad",
                    "lost-sales-cost-label": "Costo de Ventas Perdidas:",
                    "customer-churn-cost-label": "Costo de Pérdida de Clientes:",
                    "market-share-cost-label": "Costo de Cuota de Mercado:",
                    "productivity-cost-label": "Costo de Productividad:",
                    "total-opportunity-cost-label": "Costo Total de Oportunidad:",
                    "total-impact-title": "Costo Total de Mala Calidad",
                    "total-coq-label": "Impacto Combinado:",
                    "revenue-percentage-label": "% de Ingresos:",
                    "prevention": "Prevención",
                    "appraisal": "Evaluación",
                    "internal-defect": "Defecto Interno",
                    "external-defect": "Defecto Externo"
                },
                zh: {
                    title: "质量成本计算器",
                    subtitle: "质量成本分析工具",
                    "lang-label": "语言:",
                    "currency-label": "货币:",
                    "unit-label": "显示单位:",
                    "input-title": "输入参数",
                    "results-title": "计算结果",
                    "chart-title": "质量成本分布",
                    "revenue-label": "收入:",
                    "quality-percentage-label": "质量成本基础 (收入的%):",
                    "cost-distribution-title": "成本分布 (%):",
                    "prevention-label": "预防成本:",
                    "appraisal-label": "评估成本:",
                    "internal-defect-label": "内部缺陷成本:",
                    "external-defect-label": "外部缺陷成本:",
                    "total-quality-cost-label": "总质量成本:",
                    "prevention-cost-label": "预防成本:",
                    "appraisal-cost-label": "评估成本:",
                    "internal-defect-cost-label": "内部缺陷成本:",
                    "external-defect-cost-label": "外部缺陷成本:",
                    "percentage-error": "数值总和不等于100%",
                    "export-csv": "导出CSV",
                    "export-pdf": "导出PDF",
                    "bar-chart-title": "成本值",
                    "pie-chart-title": "百分比分布",
                    "opportunity-toggle-label": "机会成本:",
                    "opportunity-factors-title": "机会成本因子 (%):",
                    "lost-sales-label": "销售损失:",
                    "customer-churn-label": "客户流失:",
                    "market-share-loss-label": "市场份额损失:",
                    "productivity-loss-label": "生产力损失:",
                    "direct-costs-title": "直接质量成本",
                    "opportunity-costs-title": "机会成本",
                    "lost-sales-cost-label": "销售损失成本:",
                    "customer-churn-cost-label": "客户流失成本:",
                    "market-share-cost-label": "市场份额成本:",
                    "productivity-cost-label": "生产力成本:",
                    "total-opportunity-cost-label": "总机会成本:",
                    "total-impact-title": "质量差的总成本",
                    "total-coq-label": "综合影响:",
                    "revenue-percentage-label": "占收入比例:",
                    "prevention": "预防",
                    "appraisal": "评估",
                    "internal-defect": "内部缺陷",
                    "external-defect": "外部缺陷"
                }
            };

            let qccChart;
            let qccPieChart;
            let qccCurrentLanguage = '<?php echo esc_js($default_language); ?>';
            let qccOpportunityEnabled = false;

            // Initialize the calculator
            function qccInit() {
                qccSetupEventListeners();
                qccUpdateTranslations();
                qccUpdateCurrencyIndicators();
                qccCalculate();
                qccInitChart();
            }

            // Setup event listeners
            function qccSetupEventListeners() {
                document.getElementById('qcc-language').addEventListener('change', qccUpdateTranslations);
                document.getElementById('qcc-currency').addEventListener('change', function() {
                    qccUpdateCurrencyIndicators();
                    qccCalculate();
                });
                document.getElementById('qcc-unit').addEventListener('change', function() {
                    qccUpdateCurrencyIndicators();
                    qccCalculate();
                });
                document.getElementById('qcc-opportunity-toggle').addEventListener('change', qccToggleOpportunityCosts);
                
                const inputs = ['qcc-revenue', 'qcc-quality-percentage', 'qcc-prevention', 'qcc-appraisal', 'qcc-internal-defect', 'qcc-external-defect', 'qcc-lost-sales', 'qcc-customer-churn', 'qcc-market-share-loss', 'qcc-productivity-loss'];
                inputs.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.addEventListener('input', qccCalculate);
                    }
                });
            }

            // Update translations
            function qccUpdateTranslations() {
                qccCurrentLanguage = document.getElementById('qcc-language').value;
                const lang = qccTranslations[qccCurrentLanguage];
                
                Object.keys(lang).forEach(key => {
                    const element = document.getElementById('qcc-' + key);
                    if (element && key !== 'revenue-label' && key !== 'results-title') {
                        if (element.tagName === 'INPUT' || element.tagName === 'BUTTON') {
                            element.value = lang[key];
                        } else {
                            element.textContent = lang[key];
                        }
                    }
                });
                
                qccUpdateCurrencyIndicators();
                qccCalculate();
                qccUpdateChart();
            }

            // Update currency indicators in headers
            function qccUpdateCurrencyIndicators() {
                const currency = document.getElementById('qcc-currency').value;
                const unit = parseFloat(document.getElementById('qcc-unit').value);
                const unitName = unit === 1000000 ? 'Millions' : 'Billions';
                
                // Update revenue label
                const revenueLabel = document.getElementById('qcc-revenue-label');
                if (revenueLabel) {
                    const currentLang = qccTranslations[qccCurrentLanguage];
                    const baseRevenueText = currentLang['revenue-label'].replace(':', '');
                    revenueLabel.textContent = `${baseRevenueText} (${currency} ${unitName}):`;
                }
                
                // Update results title
                const resultsTitle = document.getElementById('qcc-results-title');
                if (resultsTitle) {
                    const currentLang = qccTranslations[qccCurrentLanguage];
                    const baseResultsText = currentLang['results-title'];
                    resultsTitle.textContent = `${baseResultsText} (${currency} ${unitName})`;
                }
            }

            // Validate percentage inputs
            function qccValidatePercentages() {
                const prevention = parseFloat(document.getElementById('qcc-prevention').value) || 0;
                const appraisal = parseFloat(document.getElementById('qcc-appraisal').value) || 0;
                const internalDefect = parseFloat(document.getElementById('qcc-internal-defect').value) || 0;
                const externalDefect = parseFloat(document.getElementById('qcc-external-defect').value) || 0;
                
                const total = prevention + appraisal + internalDefect + externalDefect;
                const isValid = Math.abs(total - 100) < 0.01;
                
                const container = document.getElementById('qcc-cost-distribution-section');
                const errorMsg = document.getElementById('qcc-percentage-error');
                const inputs = ['qcc-prevention', 'qcc-appraisal', 'qcc-internal-defect', 'qcc-external-defect'];
                
                if (!isValid) {
                    container.classList.add('error');
                    errorMsg.style.display = 'block';
                    errorMsg.textContent = qccTranslations[qccCurrentLanguage]['percentage-error'];
                    inputs.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) input.classList.add('error');
                    });
                } else {
                    container.classList.remove('error');
                    errorMsg.style.display = 'none';
                    inputs.forEach(id => {
                        const input = document.getElementById(id);
                        if (input) input.classList.remove('error');
                    });
                }
                
                return isValid;
            }

            // Format number without currency symbol and unit (shown in header)
            function qccFormatValue(value) {
                const unit = parseFloat(document.getElementById('qcc-unit').value);
                const formattedValue = (value / unit).toFixed(2);
                return formattedValue;
            }

            // Toggle opportunity costs section
            function qccToggleOpportunityCosts() {
                const toggle = document.getElementById('qcc-opportunity-toggle');
                const opportunitySection = document.getElementById('qcc-opportunity-section');
                const resultsColumns = document.getElementById('qcc-results-columns');
                const opportunityResults = document.getElementById('qcc-opportunity-results');
                const totalImpact = document.getElementById('qcc-total-impact');
                
                qccOpportunityEnabled = toggle.value === 'enabled';
                
                if (qccOpportunityEnabled) {
                    opportunitySection.classList.remove('disabled');
                    resultsColumns.style.display = 'grid';
                    resultsColumns.style.gridTemplateColumns = '1fr 1fr';
                    resultsColumns.style.gap = '20px';
                    opportunityResults.style.display = 'block';
                    totalImpact.style.display = 'block';
                } else {
                    opportunitySection.classList.add('disabled');
                    resultsColumns.style.display = 'block';
                    resultsColumns.style.gridTemplateColumns = '1fr';
                    resultsColumns.style.gap = '0';
                    opportunityResults.style.display = 'none';
                    totalImpact.style.display = 'none';
                }
                
                qccCalculate();
            }

            // Calculate opportunity costs
            function qccCalculateOpportunityCosts(revenue) {
                const lostSales = parseFloat(document.getElementById('qcc-lost-sales').value) || 0;
                const customerChurn = parseFloat(document.getElementById('qcc-customer-churn').value) || 0;
                const marketShareLoss = parseFloat(document.getElementById('qcc-market-share-loss').value) || 0;
                const productivityLoss = parseFloat(document.getElementById('qcc-productivity-loss').value) || 0;
                
                const lostSalesCost = (revenue * lostSales) / 100;
                const customerChurnCost = (revenue * customerChurn) / 100;
                const marketShareCost = (revenue * marketShareLoss) / 100;
                const productivityCost = (revenue * productivityLoss) / 100;
                
                return {
                    lostSalesCost,
                    customerChurnCost,
                    marketShareCost,
                    productivityCost,
                    total: lostSalesCost + customerChurnCost + marketShareCost + productivityCost
                };
            }

            // Calculate all values
            function qccCalculate() {
                const revenue = parseFloat(document.getElementById('qcc-revenue').value) || 0;
                const qualityPercentage = parseFloat(document.getElementById('qcc-quality-percentage').value) || 0;
                const prevention = parseFloat(document.getElementById('qcc-prevention').value) || 0;
                const appraisal = parseFloat(document.getElementById('qcc-appraisal').value) || 0;
                const internalDefect = parseFloat(document.getElementById('qcc-internal-defect').value) || 0;
                const externalDefect = parseFloat(document.getElementById('qcc-external-defect').value) || 0;
                
                const unit = parseFloat(document.getElementById('qcc-unit').value);
                const revenueInUnit = revenue * unit;
                
                // Calculate direct quality costs
                const totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
                const preventionCost = (totalQualityCost * prevention) / 100;
                const appraisalCost = (totalQualityCost * appraisal) / 100;
                const internalDefectCost = (totalQualityCost * internalDefect) / 100;
                const externalDefectCost = (totalQualityCost * externalDefect) / 100;
                
                // Update direct quality cost display
                document.getElementById('qcc-total-quality-cost').textContent = qccFormatValue(totalQualityCost);
                document.getElementById('qcc-prevention-cost').textContent = qccFormatValue(preventionCost);
                document.getElementById('qcc-appraisal-cost').textContent = qccFormatValue(appraisalCost);
                document.getElementById('qcc-internal-defect-cost').textContent = qccFormatValue(internalDefectCost);
                document.getElementById('qcc-external-defect-cost').textContent = qccFormatValue(externalDefectCost);
                
                // Calculate and update opportunity costs if enabled
                if (qccOpportunityEnabled) {
                    const opportunityCosts = qccCalculateOpportunityCosts(revenueInUnit);
                    
                    document.getElementById('qcc-lost-sales-cost').textContent = qccFormatValue(opportunityCosts.lostSalesCost);
                    document.getElementById('qcc-customer-churn-cost').textContent = qccFormatValue(opportunityCosts.customerChurnCost);
                    document.getElementById('qcc-market-share-cost').textContent = qccFormatValue(opportunityCosts.marketShareCost);
                    document.getElementById('qcc-productivity-cost').textContent = qccFormatValue(opportunityCosts.productivityCost);
                    document.getElementById('qcc-total-opportunity-cost').textContent = qccFormatValue(opportunityCosts.total);
                    
                    // Calculate total impact
                    const totalImpact = totalQualityCost + opportunityCosts.total;
                    const revenuePercentage = ((totalImpact / revenueInUnit) * 100).toFixed(1);
                    
                    document.getElementById('qcc-total-coq').textContent = qccFormatValue(totalImpact);
                    document.getElementById('qcc-revenue-percentage').textContent = revenuePercentage + '%';
                }
                
                // Validate percentages after updating values
                qccValidatePercentages();
                qccUpdateChart();
            }

            // Initialize charts
            function qccInitChart() {
                // Initialize bar chart
                const ctx = document.getElementById('qcc-qualityChart');
                if (ctx && typeof Chart !== 'undefined') {
                    try {
                        qccChart = new Chart(ctx.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: ['Prevention', 'Appraisal', 'Internal Defect', 'External Defect'],
                                datasets: [{
                                    label: 'Quality Costs',
                                    data: [0, 0, 0, 0],
                                    backgroundColor: [
                                        '#449775',
                                        '#E7F9DE',
                                        '#353535',
                                        '#DFEEC0'
                                    ],
                                    borderColor: [
                                        '#449775',
                                        '#E7F9DE',
                                        '#353535',
                                        '#DFEEC0'
                                    ],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                const currency = document.getElementById('qcc-currency').value;
                                                const unit = parseFloat(document.getElementById('qcc-unit').value);
                                                const unitName = unit === 1000000 ? 'M' : 'B';
                                                return currency + (value / unit).toFixed(1) + unitName;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } catch (error) {
                        console.log('Bar chart initialization failed:', error);
                    }
                }

                // Initialize pie chart
                const pieCtx = document.getElementById('qcc-pieChart');
                if (pieCtx && typeof Chart !== 'undefined') {
                    try {
                        qccPieChart = new Chart(pieCtx.getContext('2d'), {
                            type: 'pie',
                            data: {
                                labels: ['Prevention', 'Appraisal', 'Internal Defect', 'External Defect'],
                                datasets: [{
                                    data: [25, 25, 25, 25],
                                    backgroundColor: [
                                        '#449775',
                                        '#E7F9DE',
                                        '#353535',
                                        '#DFEEC0'
                                    ],
                                    borderColor: [
                                        '#449775',
                                        '#E7F9DE',
                                        '#353535',
                                        '#DFEEC0'
                                    ],
                                    borderWidth: 2
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            padding: 15,
                                            font: {
                                                family: 'Lato',
                                                size: 12
                                            }
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.label + ': ' + context.parsed.toFixed(1) + '%';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } catch (error) {
                        console.log('Pie chart initialization failed:', error);
                    }
                }

                qccUpdateChart();
            }

            // Update chart data
            function qccUpdateChart() {
                const lang = qccTranslations[qccCurrentLanguage];
                const prevention = parseFloat(document.getElementById('qcc-prevention').value) || 0;
                const appraisal = parseFloat(document.getElementById('qcc-appraisal').value) || 0;
                const internalDefect = parseFloat(document.getElementById('qcc-internal-defect').value) || 0;
                const externalDefect = parseFloat(document.getElementById('qcc-external-defect').value) || 0;
                
                const labels = [
                    lang['prevention'],
                    lang['appraisal'],
                    lang['internal-defect'],
                    lang['external-defect']
                ];

                // Update bar chart with absolute values
                if (qccChart && qccChart.data) {
                    const revenue = parseFloat(document.getElementById('qcc-revenue').value) || 0;
                    const qualityPercentage = parseFloat(document.getElementById('qcc-quality-percentage').value) || 0;
                    const unit = parseFloat(document.getElementById('qcc-unit').value);
                    const revenueInUnit = revenue * unit;
                    const totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
                    
                    const data = [
                        (totalQualityCost * prevention) / 100,
                        (totalQualityCost * appraisal) / 100,
                        (totalQualityCost * internalDefect) / 100,
                        (totalQualityCost * externalDefect) / 100
                    ];
                    
                    qccChart.data.labels = labels;
                    qccChart.data.datasets[0].data = data;
                    qccChart.update();
                }

                // Update pie chart with percentage values
                if (qccPieChart && qccPieChart.data) {
                    const percentageData = [prevention, appraisal, internalDefect, externalDefect];
                    
                    qccPieChart.data.labels = labels;
                    qccPieChart.data.datasets[0].data = percentageData;
                    qccPieChart.update();
                }
            }

            // Export functions
            window.qccExportToCSV = function() {
                const revenue = parseFloat(document.getElementById('qcc-revenue').value) || 0;
                const qualityPercentage = parseFloat(document.getElementById('qcc-quality-percentage').value) || 0;
                const prevention = parseFloat(document.getElementById('qcc-prevention').value) || 0;
                const appraisal = parseFloat(document.getElementById('qcc-appraisal').value) || 0;
                const internalDefect = parseFloat(document.getElementById('qcc-internal-defect').value) || 0;
                const externalDefect = parseFloat(document.getElementById('qcc-external-defect').value) || 0;
                
                const unit = parseFloat(document.getElementById('qcc-unit').value);
                const revenueInUnit = revenue * unit;
                const totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
                
                const data = [
                    ['Parameter', 'Value'],
                    ['Revenue', qccFormatValue(revenueInUnit)],
                    ['Quality Cost Basis (%)', qualityPercentage + '%'],
                    ['Total Quality Cost', qccFormatValue(totalQualityCost)],
                    ['Prevention Cost', qccFormatValue((totalQualityCost * prevention) / 100)],
                    ['Appraisal Cost', qccFormatValue((totalQualityCost * appraisal) / 100)],
                    ['Internal Defect Cost', qccFormatValue((totalQualityCost * internalDefect) / 100)],
                    ['External Defect Cost', qccFormatValue((totalQualityCost * externalDefect) / 100)]
                ];
                
                const csv = data.map(row => row.join(',')).join('\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('hidden', '');
                a.setAttribute('href', url);
                a.setAttribute('download', 'quality_costs.csv');
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            };

            window.qccExportToPDF = function() {
                window.print();
            };

            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', qccInit);
            } else {
                qccInit();
            }

        })();
    </script>
</div>
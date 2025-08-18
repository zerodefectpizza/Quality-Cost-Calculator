<?php
/**
 * Template: Emergency Fallback Layout
 * Notfall-Template wenn andere Templates fehlschlagen oder nicht verfügbar sind
 * 
 * @param array $data Fallback-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$error_message = $data['error_message'] ?? '';
$original_template = $data['original_template'] ?? '';
$debug_info = $data['debug_info'] ?? array();
$show_debug = $data['show_debug'] ?? false;
$calculator_data = $data['calculator_data'] ?? array();
$id = $data['id'] ?? 'qcc-emergency-fallback';
$css_classes = $attributes['css_classes'] ?? '';

// Fallback-Werte für Calculator
$default_values = array(
    'revenue' => 140,
    'quality_percentage' => 6,
    'prevention_costs' => 10,
    'appraisal_costs' => 20,
    'internal_defect_costs' => 30,
    'external_defect_costs' => 40
);

$values = array_merge($default_values, $calculator_data);

// Berechnungen
$total_quality_cost = ($values['revenue'] * $values['quality_percentage']) / 100;
$prevention_cost = ($total_quality_cost * $values['prevention_costs']) / 100;
$appraisal_cost = ($total_quality_cost * $values['appraisal_costs']) / 100;
$internal_defect_cost = ($total_quality_cost * $values['internal_defect_costs']) / 100;
$external_defect_cost = ($total_quality_cost * $values['external_defect_costs']) / 100;
$cogq_total = $prevention_cost + $appraisal_cost;
$copq_total = $internal_defect_cost + $external_defect_cost;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quality Cost Calculator - Emergency Mode</title>
    <style>
        /* Emergency Fallback Styles - Inline für maximale Kompatibilität */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .emergency-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .emergency-header {
            background: linear-gradient(135deg, #ff6b6b, #ffd93d);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .emergency-header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .emergency-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .emergency-content {
            padding: 30px;
        }
        
        .calculator-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .input-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .result-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .result-card.cogq {
            border-left: 4px solid #28a745;
        }
        
        .result-card.copq {
            border-left: 4px solid #dc3545;
        }
        
        .result-label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .result-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .calculate-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        
        .calculate-btn:hover {
            background: #0056b3;
        }
        
        .error-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .error-title {
            color: #856404;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .error-message {
            color: #664d03;
            margin-bottom: 15px;
        }
        
        .debug-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .debug-toggle {
            background: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .status-indicator {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        @media (max-width: 768px) {
            .input-grid,
            .results-grid {
                grid-template-columns: 1fr;
            }
            
            .emergency-content {
                padding: 20px;
            }
            
            .result-value {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div id="<?php echo esc_attr($id); ?>" class="emergency-container <?php echo esc_attr($css_classes); ?>">
        
        <!-- Emergency Header -->
        <div class="emergency-header">
            <h1>⚠️ Quality Cost Calculator - Emergency Mode</h1>
            <p>Das System läuft im Notfallmodus - Grundfunktionen sind verfügbar</p>
        </div>
        
        <div class="emergency-content">
            
            <!-- Status Indicator -->
            <div class="status-indicator status-warning">
                <strong>Template Error:</strong> Das ursprüngliche Template konnte nicht geladen werden. 
                <?php if ($original_template): ?>
                    (Template: <?php echo esc_html($original_template); ?>)
                <?php endif; ?>
            </div>
            
            <?php if ($error_message): ?>
            <!-- Error Section -->
            <div class="error-section">
                <div class="error-title">Fehlerdetails:</div>
                <div class="error-message"><?php echo esc_html($error_message); ?></div>
                
                <?php if ($show_debug && !empty($debug_info)): ?>
                <button class="debug-toggle" onclick="toggleDebugInfo()">Debug-Informationen anzeigen</button>
                <div id="debug-info" class="debug-section" style="display: none;">
                    <?php foreach ($debug_info as $key => $value): ?>
                        <div><strong><?php echo esc_html($key); ?>:</strong> <?php echo esc_html(is_array($value) ? json_encode($value) : $value); ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Status Info -->
            <div class="status-indicator status-info">
                <strong>Notfallmodus aktiv:</strong> Der Calculator funktioniert mit Grundfunktionen. 
                Alle Berechnungen werden korrekt durchgeführt, jedoch mit vereinfachter Darstellung.
            </div>
            
            <!-- Calculator Section -->
            <div class="calculator-section">
                <h2 style="margin-bottom: 20px; color: #333;">Quality Cost Calculator</h2>
                
                <!-- Input Grid -->
                <div class="input-grid">
                    <!-- Left Column: Basic Inputs -->
                    <div>
                        <h3 style="margin-bottom: 15px; color: #555;">Basis-Parameter</h3>
                        
                        <div class="input-group">
                            <label for="revenue">Umsatz (Mrd. €)</label>
                            <input type="number" id="revenue" value="<?php echo esc_attr($values['revenue']); ?>" step="0.01" min="0">
                        </div>
                        
                        <div class="input-group">
                            <label for="quality-percentage">Qualitätskosten % vom Umsatz</label>
                            <input type="number" id="quality-percentage" value="<?php echo esc_attr($values['quality_percentage']); ?>" step="0.01" min="0" max="100">
                        </div>
                    </div>
                    
                    <!-- Right Column: Cost Distribution -->
                    <div>
                        <h3 style="margin-bottom: 15px; color: #555;">Kostenverteilung (%)</h3>
                        
                        <div class="input-group">
                            <label for="prevention-costs">Präventionskosten (%)</label>
                            <input type="number" id="prevention-costs" value="<?php echo esc_attr($values['prevention_costs']); ?>" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="input-group">
                            <label for="appraisal-costs">Bewertungskosten (%)</label>
                            <input type="number" id="appraisal-costs" value="<?php echo esc_attr($values['appraisal_costs']); ?>" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="input-group">
                            <label for="internal-defect-costs">Interne Fehlerkosten (%)</label>
                            <input type="number" id="internal-defect-costs" value="<?php echo esc_attr($values['internal_defect_costs']); ?>" step="0.01" min="0" max="100">
                        </div>
                        
                        <div class="input-group">
                            <label for="external-defect-costs">Externe Fehlerkosten (%)</label>
                            <input type="number" id="external-defect-costs" value="<?php echo esc_attr($values['external_defect_costs']); ?>" step="0.01" min="0" max="100">
                        </div>
                    </div>
                </div>
                
                <!-- Calculate Button -->
                <button class="calculate-btn" onclick="calculateResults()">🔄 Neu berechnen</button>
                
                <!-- Validation Message -->
                <div id="validation-message" style="margin-top: 15px; padding: 10px; border-radius: 6px; display: none;"></div>
                
                <!-- Results Grid -->
                <div class="results-grid">
                    <!-- Total Quality Cost -->
                    <div class="result-card">
                        <div class="result-label">Gesamte Qualitätskosten</div>
                        <div class="result-value" id="total-quality-cost"><?php echo number_format($total_quality_cost, 2); ?> Mrd. €</div>
                    </div>
                    
                    <!-- COGQ Results -->
                    <div class="result-card cogq">
                        <div class="result-label">Präventionskosten</div>
                        <div class="result-value" id="prevention-cost"><?php echo number_format($prevention_cost, 2); ?> Mrd. €</div>
                    </div>
                    
                    <div class="result-card cogq">
                        <div class="result-label">Bewertungskosten</div>
                        <div class="result-value" id="appraisal-cost"><?php echo number_format($appraisal_cost, 2); ?> Mrd. €</div>
                    </div>
                    
                    <div class="result-card cogq">
                        <div class="result-label">COGQ Gesamt</div>
                        <div class="result-value" id="cogq-total"><?php echo number_format($cogq_total, 2); ?> Mrd. €</div>
                    </div>
                    
                    <!-- COPQ Results -->
                    <div class="result-card copq">
                        <div class="result-label">Interne Fehlerkosten</div>
                        <div class="result-value" id="internal-defect-cost"><?php echo number_format($internal_defect_cost, 2); ?> Mrd. €</div>
                    </div>
                    
                    <div class="result-card copq">
                        <div class="result-label">Externe Fehlerkosten</div>
                        <div class="result-value" id="external-defect-cost"><?php echo number_format($external_defect_cost, 2); ?> Mrd. €</div>
                    </div>
                    
                    <div class="result-card copq">
                        <div class="result-label">COPQ Gesamt</div>
                        <div class="result-value" id="copq-total"><?php echo number_format($copq_total, 2); ?> Mrd. €</div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div style="background: #e9ecef; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="margin-bottom: 15px; color: #495057;">ℹ️ Information</h3>
                <p style="margin-bottom: 10px; color: #6c757d;">
                    <strong>Was ist passiert?</strong> Das normale Template-System konnte nicht geladen werden. 
                    Mögliche Ursachen: Datei nicht gefunden, Syntax-Fehler, oder Server-Probleme.
                </p>
                <p style="margin-bottom: 10px; color: #6c757d;">
                    <strong>Was funktioniert?</strong> Alle Berechnungen funktionieren normal. 
                    Die Darstellung ist vereinfacht, aber alle Funktionen sind verfügbar.
                </p>
                <p style="color: #6c757d;">
                    <strong>Nächste Schritte:</strong> Kontaktieren Sie den Administrator, 
                    um das Template-Problem zu beheben.
                </p>
            </div>
            
        </div>
    </div>
    
    <script>
        // Emergency Mode JavaScript - Minimale Funktionalität
        function calculateResults() {
            // Input-Werte holen
            const revenue = parseFloat(document.getElementById('revenue').value) || 0;
            const qualityPercentage = parseFloat(document.getElementById('quality-percentage').value) || 0;
            const preventionCosts = parseFloat(document.getElementById('prevention-costs').value) || 0;
            const appraisalCosts = parseFloat(document.getElementById('appraisal-costs').value) || 0;
            const internalDefectCosts = parseFloat(document.getElementById('internal-defect-costs').value) || 0;
            const externalDefectCosts = parseFloat(document.getElementById('external-defect-costs').value) || 0;
            
            // Validierung
            const totalPercentage = preventionCosts + appraisalCosts + internalDefectCosts + externalDefectCosts;
            const validationMessage = document.getElementById('validation-message');
            
            if (Math.abs(totalPercentage - 100) > 0.01) {
                validationMessage.style.display = 'block';
                validationMessage.style.background = '#f8d7da';
                validationMessage.style.color = '#721c24';
                validationMessage.style.border = '1px solid #f5c6cb';
                validationMessage.textContent = `Fehler: Die Prozentsätze ergeben ${totalPercentage.toFixed(2)}% statt 100%`;
                return;
            } else {
                validationMessage.style.display = 'none';
            }
            
            // Berechnungen
            const totalQualityCost = (revenue * qualityPercentage) / 100;
            const preventionCost = (totalQualityCost * preventionCosts) / 100;
            const appraisalCost = (totalQualityCost * appraisalCosts) / 100;
            const internalDefectCost = (totalQualityCost * internalDefectCosts) / 100;
            const externalDefectCost = (totalQualityCost * externalDefectCosts) / 100;
            const cogqTotal = preventionCost + appraisalCost;
            const copqTotal = internalDefectCost + externalDefectCost;
            
            // Ergebnisse aktualisieren
            document.getElementById('total-quality-cost').textContent = formatCurrency(totalQualityCost);
            document.getElementById('prevention-cost').textContent = formatCurrency(preventionCost);
            document.getElementById('appraisal-cost').textContent = formatCurrency(appraisalCost);
            document.getElementById('internal-defect-cost').textContent = formatCurrency(internalDefectCost);
            document.getElementById('external-defect-cost').textContent = formatCurrency(externalDefectCost);
            document.getElementById('cogq-total').textContent = formatCurrency(cogqTotal);
            document.getElementById('copq-total').textContent = formatCurrency(copqTotal);
            
            // Erfolgs-Nachricht
            validationMessage.style.display = 'block';
            validationMessage.style.background = '#d4edda';
            validationMessage.style.color = '#155724';
            validationMessage.style.border = '1px solid #c3e6cb';
            validationMessage.textContent = '✓ Berechnungen erfolgreich aktualisiert';
            
            setTimeout(() => {
                validationMessage.style.display = 'none';
            }, 3000);
        }
        
        function formatCurrency(value) {
            return value.toFixed(2) + ' Mrd. €';
        }
        
        function toggleDebugInfo() {
            const debugInfo = document.getElementById('debug-info');
            if (debugInfo.style.display === 'none') {
                debugInfo.style.display = 'block';
            } else {
                debugInfo.style.display = 'none';
            }
        }
        
        // Auto-calculate on input change
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    // Verzögerte Berechnung für bessere UX
                    clearTimeout(this.timer);
                    this.timer = setTimeout(calculateResults, 500);
                });
            });
        });
        
        // Warnung bei ungültiger Eingabe
        function showValidationWarning(message) {
            const validationMessage = document.getElementById('validation-message');
            validationMessage.style.display = 'block';
            validationMessage.style.background = '#fff3cd';
            validationMessage.style.color = '#856404';
            validationMessage.style.border = '1px solid #ffeaa7';
            validationMessage.textContent = '⚠️ ' + message;
        }
        
        // Console-Log für Debugging
        console.log('QCC Emergency Mode aktiviert');
        console.log('Template Error:', <?php echo json_encode($error_message); ?>);
        console.log('Original Template:', <?php echo json_encode($original_template); ?>);
        <?php if ($show_debug): ?>
        console.log('Debug Info:', <?php echo json_encode($debug_info); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
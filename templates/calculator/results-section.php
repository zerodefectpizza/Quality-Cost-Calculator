<?php
/**
 * QCC Results Section Template
 * 
 * Ergebnisse-Anzeige für Calculator-Berechnungen
 * Live-Update mit animierten Wert-Änderungen
 * 
 * Template-Pfad: templates/calculator/sections/results-section.php
 * 
 * Verfügbare Variablen:
 * @var array $data - Template-Daten
 * @var array $attributes - HTML-Attribute
 * @var array $helpers - Helper-Funktionen
 * @var QCC_Translator $translator - Übersetzungs-Service
 * @var QCC_Template_Manager $template_manager - Template-Manager
 * @var array $debug - Debug-Informationen (nur wenn WP_DEBUG)
 * 
 * @package QCC
 * @subpackage Templates/Sections
 * @since 2.0.0
 */

// Security: Template-Direktzugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Section-Daten vorbereiten
$section_id = $data['id'] ?? $helpers['generate_section_id']($data);
$title = $data['title'] ?? $translator->get('calculation_results');
$description = $data['description'] ?? $translator->get('your_quality_cost_breakdown');
$layout = $data['layout'] ?? 'cards'; // cards, table, mixed
$live_update = $data['live_update'] ?? true;
$show_explanations = $data['show_explanations'] ?? true;
$currency = $data['currency'] ?? 'EUR';
$unit = $data['unit'] ?? 'billions';
$currency_symbol = $helpers['get_currency_symbol']($currency);

// Berechnungs-Ergebnisse (können von JavaScript aktualisiert werden)
$results = array_merge(array(
    'total_quality_cost' => 0,
    'prevention_cost' => 0,
    'appraisal_cost' => 0,
    'internal_defect_cost' => 0,
    'external_defect_cost' => 0,
    'cogq_total' => 0,
    'copq_total' => 0,
    'cogq_percentage' => 0,
    'copq_percentage' => 0,
    'opportunity_cost' => 0,
    'total_impact' => 0,
    'roi_improvement' => 0
), $data['results'] ?? array());

// Result-Cards-Konfiguration
$result_cards = $data['result_cards'] ?? array(
    'summary' => array(
        'title' => $translator->get('cost_summary'),
        'cards' => array(
            'total_quality_cost' => array(
                'label' => $translator->get('total_quality_cost'),
                'value' => $results['total_quality_cost'],
                'type' => 'currency',
                'color' => 'primary',
                'icon' => '💰',
                'description' => $translator->get('total_quality_cost_desc')
            ),
            'cogq_total' => array(
                'label' => $translator->get('cost_of_good_quality'),
                'value' => $results['cogq_total'],
                'type' => 'currency',
                'color' => 'success',
                'icon' => '✅',
                'description' => $translator->get('cogq_desc'),
                'percentage' => $results['cogq_percentage']
            ),
            'copq_total' => array(
                'label' => $translator->get('cost_of_poor_quality'),
                'value' => $results['copq_total'],
                'type' => 'currency',
                'color' => 'danger',
                'icon' => '⚠️',
                'description' => $translator->get('copq_desc'),
                'percentage' => $results['copq_percentage']
            )
        )
    ),
    'breakdown' => array(
        'title' => $translator->get('detailed_breakdown'),
        'cards' => array(
            'prevention_cost' => array(
                'label' => $translator->get('prevention_costs'),
                'value' => $results['prevention_cost'],
                'type' => 'currency',
                'color' => 'success',
                'icon' => '🛡️',
                'category' => 'cogq'
            ),
            'appraisal_cost' => array(
                'label' => $translator->get('appraisal_costs'),
                'value' => $results['appraisal_cost'],
                'type' => 'currency',
                'color' => 'success',
                'icon' => '🔍',
                'category' => 'cogq'
            ),
            'internal_defect_cost' => array(
                'label' => $translator->get('internal_defect_costs'),
                'value' => $results['internal_defect_cost'],
                'type' => 'currency',
                'color' => 'warning',
                'icon' => '🔧',
                'category' => 'copq'
            ),
            'external_defect_cost' => array(
                'label' => $translator->get('external_defect_costs'),
                'value' => $results['external_defect_cost'],
                'type' => 'currency',
                'color' => 'danger',
                'icon' => '🚨',
                'category' => 'copq'
            )
        )
    )
);

if (!empty($results['opportunity_cost']) || $data['show_opportunity'] ?? false) {
    $result_cards['opportunity'] = array(
        'title' => $translator->get('opportunity_impact'),
        'cards' => array(
            'opportunity_cost' => array(
                'label' => $translator->get('opportunity_costs'),
                'value' => $results['opportunity_cost'],
                'type' => 'currency',
                'color' => 'info',
                'icon' => '📈',
                'description' => $translator->get('opportunity_cost_desc')
            ),
            'total_impact' => array(
                'label' => $translator->get('total_business_impact'),
                'value' => $results['total_impact'],
                'type' => 'currency',
                'color' => 'primary',
                'icon' => '💼',
                'description' => $translator->get('total_impact_desc')
            )
        )
    );
}

// CSS-Klassen für Results-Section
$results_classes = $helpers['build_css_classes'](
    array('qcc-results-section'),
    array(
        'qcc-results-section--' . $layout => true,
        'qcc-results-section--live-update' => $live_update,
        'qcc-results-section--with-explanations' => $show_explanations,
        'qcc-results-section--loading' => $data['loading'] ?? false
    )
);
?>

<!-- QCC Results Section Start -->
<section id="<?php echo esc_attr($section_id); ?>" 
         class="<?php echo esc_attr($results_classes); ?>"
         role="region"
         aria-label="<?php echo esc_attr($translator->get('calculation_results')); ?>"
         data-currency="<?php echo esc_attr($currency); ?>"
         data-unit="<?php echo esc_attr($unit); ?>">

    <!-- Section Header -->
    <div class="qcc-results-header">
        <div class="qcc-results-title-row">
            <h2 class="qcc-results-title">
                <?php echo $helpers['escape']($title); ?>
                
                <?php if ($live_update): ?>
                <span class="qcc-live-indicator" 
                      title="<?php echo esc_attr($translator->get('live_updates_enabled')); ?>"
                      aria-label="<?php echo esc_attr($translator->get('live_updates_enabled')); ?>">
                    <span class="qcc-live-dot"></span>
                    <?php echo $helpers['escape']($translator->get('live')); ?>
                </span>
                <?php endif; ?>
            </h2>
            
            <?php if ($data['show_export'] ?? true): ?>
            <div class="qcc-results-actions">
                <?php 
                echo $template_manager->render('components/control-button', array(
                    'type' => 'export',
                    'text' => $translator->get('export_results'),
                    'icon' => '📊',
                    'onclick' => 'QCC.exportResults()',
                    'class' => 'qcc-button--outline qcc-button--small'
                ));
                ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($description)): ?>
        <p class="qcc-results-description">
            <?php echo $helpers['escape']($description); ?>
        </p>
        <?php endif; ?>

        <?php if ($data['show_last_updated'] ?? true): ?>
        <div class="qcc-results-meta">
            <span class="qcc-last-updated" id="qcc-last-updated">
                <?php echo $helpers['escape']($translator->get('last_updated')); ?>: 
                <time id="qcc-update-time" datetime="<?php echo date('c'); ?>">
                    <?php echo $helpers['escape']($translator->get('not_calculated_yet')); ?>
                </time>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Results Content -->
    <div class="qcc-results-content">

        <?php if ($data['loading'] ?? false): ?>
        <!-- Loading State -->
        <div class="qcc-results-loading" id="qcc-results-loading">
            <div class="qcc-loading-spinner">
                <div class="qcc-spinner"></div>
            </div>
            <p class="qcc-loading-text">
                <?php echo $helpers['escape']($translator->get('calculating')); ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="qcc-results-data" id="qcc-results-data" <?php echo ($data['loading'] ?? false) ? 'style="display: none;"' : ''; ?>>

            <?php foreach ($result_cards as $section_key => $section_config): ?>
            
            <!-- Result Card Group -->
            <div class="qcc-result-group qcc-result-group--<?php echo esc_attr($section_key); ?>">
                
                <div class="qcc-result-group-header">
                    <h3 class="qcc-result-group-title">
                        <?php echo $helpers['escape']($section_config['title']); ?>
                    </h3>
                    
                    <?php if ($section_key === 'summary' && $show_explanations): ?>
                    <button type="button" 
                            class="qcc-explanation-toggle"
                            onclick="QCC.toggleExplanations()"
                            aria-label="<?php echo esc_attr($translator->get('toggle_explanations')); ?>">
                        <span class="qcc-explanation-icon">ℹ️</span>
                        <span class="qcc-explanation-text"><?php echo $helpers['escape']($translator->get('explanations')); ?></span>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="qcc-result-cards">
                    <?php foreach ($section_config['cards'] as $card_key => $card_config): ?>
                    
                    <!-- Individual Result Card -->
                    <div class="qcc-result-card qcc-result-card--<?php echo esc_attr($card_config['color']); ?>"
                         data-card="<?php echo esc_attr($card_key); ?>"
                         data-category="<?php echo esc_attr($card_config['category'] ?? 'general'); ?>">
                        
                        <div class="qcc-result-card-header">
                            <?php if (!empty($card_config['icon'])): ?>
                            <span class="qcc-result-card-icon" aria-hidden="true">
                                <?php echo $helpers['escape']($card_config['icon']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <div class="qcc-result-card-title-area">
                                <h4 class="qcc-result-card-title">
                                    <?php echo $helpers['escape']($card_config['label']); ?>
                                </h4>
                                
                                <?php if (!empty($card_config['percentage'])): ?>
                                <span class="qcc-result-card-percentage">
                                    (<span data-value="percentage-<?php echo esc_attr($card_key); ?>">
                                        <?php echo $helpers['format_percentage']($card_config['percentage']); ?>
                                    </span>)
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="qcc-result-card-body">
                            <div class="qcc-result-card-value" 
                                 id="qcc-result-<?php echo esc_attr($card_key); ?>"
                                 data-value="<?php echo esc_attr($card_config['value']); ?>"
                                 data-type="<?php echo esc_attr($card_config['type']); ?>">
                                
                                <?php if ($card_config['type'] === 'currency'): ?>
                                    <?php echo $helpers['format_currency']($card_config['value'], $currency); ?>
                                <?php elseif ($card_config['type'] === 'percentage'): ?>
                                    <?php echo $helpers['format_percentage']($card_config['value']); ?>
                                <?php else: ?>
                                    <?php echo $helpers['escape']($card_config['value']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($card_config['trend'])): ?>
                            <div class="qcc-result-card-trend qcc-trend--<?php echo esc_attr($card_config['trend']['direction']); ?>">
                                <span class="qcc-trend-icon" aria-hidden="true">
                                    <?php echo $card_config['trend']['direction'] === 'up' ? '↗️' : '↘️'; ?>
                                </span>
                                <span class="qcc-trend-text">
                                    <?php echo $helpers['escape']($card_config['trend']['text']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($card_config['description']) && $show_explanations): ?>
                        <div class="qcc-result-card-footer qcc-explanation-content" style="display: none;">
                            <p class="qcc-result-card-description">
                                <?php echo $helpers['escape']($card_config['description']); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                    </div>

                    <?php endforeach; ?>
                </div>

            </div>

            <?php endforeach; ?>

            <!-- ========== NEUE CoGQ/CoPQ BOXEN SEKTION ========== -->
            <!-- Eingefügt nach den bestehenden result_cards -->
            
            <!-- CoGQ/CoPQ Breakdown Section -->
            <div class="qcc-result-group qcc-result-group--cogq-copq">
                <div class="qcc-result-group-header">
                    <h3 class="qcc-result-group-title">
                        Quality Cost Breakdown
                    </h3>
                </div>

                <!-- CoGQ/CoPQ Container -->
                <div class="qcc-cogq-copq-container">
                    
                    <!-- Cost of Good Quality (CoGQ) Box -->
                    <div class="qcc-quality-box qcc-cogq-section">
                        <div class="qcc-quality-box-header">
                            <h4 class="qcc-subsection-title">
                                <span class="qcc-quality-box-icon">✅</span>
                                Cost of Good Quality (CoGQ)
                            </h4>
                        </div>
                        
                        <div class="qcc-quality-box-content">
                            <!-- Prevention Cost -->
                            <div class="qcc-result-item qcc-cogq-item">
                                <div class="qcc-result-label">
                                    <span class="qcc-result-icon">🛡️</span>
                                    Prevention Costs
                                </div>
                                <div class="qcc-result-value" id="qcc-cogq-prevention">
                                    <?php echo $helpers['format_currency']($results['prevention_cost'], $currency); ?>
                                </div>
                            </div>
                            
                            <!-- Appraisal Cost -->
                            <div class="qcc-result-item qcc-cogq-item">
                                <div class="qcc-result-label">
                                    <span class="qcc-result-icon">🔍</span>
                                    Appraisal Costs
                                </div>
                                <div class="qcc-result-value" id="qcc-cogq-appraisal">
                                    <?php echo $helpers['format_currency']($results['appraisal_cost'], $currency); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CoGQ Total -->
                        <div class="qcc-result-item qcc-cogq-total">
                            <div class="qcc-result-label">
                                <strong>Total CoGQ</strong>
                            </div>
                            <div class="qcc-result-value" id="qcc-cogq-total">
                                <strong><?php echo $helpers['format_currency']($results['cogq_total'], $currency); ?></strong>
                            </div>
                        </div>
                        
                        <!-- CoGQ Percentage -->
                        <div class="qcc-quality-percentage">
                            <span id="qcc-cogq-percentage">
                                <?php echo $results['cogq_percentage']; ?>%
                            </span>
                            <small>of total quality cost</small>
                        </div>
                    </div>

                    <!-- Cost of Poor Quality (CoPQ) Box -->
                    <div class="qcc-quality-box qcc-copq-section">
                        <div class="qcc-quality-box-header">
                            <h4 class="qcc-subsection-title">
                                <span class="qcc-quality-box-icon">⚠️</span>
                                Cost of Poor Quality (CoPQ)
                            </h4>
                        </div>
                        
                        <div class="qcc-quality-box-content">
                            <!-- Internal Defect Cost -->
                            <div class="qcc-result-item qcc-copq-item">
                                <div class="qcc-result-label">
                                    <span class="qcc-result-icon">🔧</span>
                                    Internal Defects
                                </div>
                                <div class="qcc-result-value" id="qcc-copq-internal">
                                    <?php echo $helpers['format_currency']($results['internal_defect_cost'], $currency); ?>
                                </div>
                            </div>
                            
                            <!-- External Defect Cost -->
                            <div class="qcc-result-item qcc-copq-item">
                                <div class="qcc-result-label">
                                    <span class="qcc-result-icon">🚨</span>
                                    External Defects
                                </div>
                                <div class="qcc-result-value" id="qcc-copq-external">
                                    <?php echo $helpers['format_currency']($results['external_defect_cost'], $currency); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CoPQ Total -->
                        <div class="qcc-result-item qcc-copq-total">
                            <div class="qcc-result-label">
                                <strong>Total CoPQ</strong>
                            </div>
                            <div class="qcc-result-value" id="qcc-copq-total">
                                <strong><?php echo $helpers['format_currency']($results['copq_total'], $currency); ?></strong>
                            </div>
                        </div>
                        
                        <!-- CoPQ Percentage -->
                        <div class="qcc-quality-percentage">
                            <span id="qcc-copq-percentage">
                                <?php echo $results['copq_percentage']; ?>%
                            </span>
                            <small>of total quality cost</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ========== ENDE CoGQ/CoPQ BOXEN SEKTION ========== -->

            <?php if ($data['show_recommendations'] ?? true): ?>
            <!-- Recommendations Section -->
            <div class="qcc-recommendations" id="qcc-recommendations">
                <h3 class="qcc-recommendations-title">
                    <?php echo $helpers['escape']($translator->get('recommendations')); ?>
                </h3>
                
                <div class="qcc-recommendations-content" id="qcc-recommendations-content">
                    <!-- Wird von JavaScript basierend auf Berechnungen gefüllt -->
                    <p class="qcc-recommendations-placeholder">
                        <?php echo $helpers['escape']($translator->get('recommendations_will_appear_after_calculation')); ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($data['show_benchmarks'] ?? false): ?>
            <!-- Industry Benchmarks -->
            <div class="qcc-benchmarks">
                <h3 class="qcc-benchmarks-title">
                    <?php echo $helpers['escape']($translator->get('industry_benchmarks')); ?>
                </h3>
                
                <div class="qcc-benchmark-items">
                    <div class="qcc-benchmark-item">
                        <span class="qcc-benchmark-label"><?php echo $helpers['escape']($translator->get('industry_average')); ?>:</span>
                        <span class="qcc-benchmark-value">8-12%</span>
                    </div>
                    <div class="qcc-benchmark-item">
                        <span class="qcc-benchmark-label"><?php echo $helpers['escape']($translator->get('best_in_class')); ?>:</span>
                        <span class="qcc-benchmark-value">4-6%</span>
                    </div>
                    <div class="qcc-benchmark-item">
                        <span class="qcc-benchmark-label"><?php echo $helpers['escape']($translator->get('cogq_copq_ratio')); ?>:</span>
                        <span class="qcc-benchmark-value">60:40</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

    </div>

    <?php if ($data['show_actions'] ?? true): ?>
    <!-- Results Actions -->
    <div class="qcc-results-actions-bar">
        
        <div class="qcc-actions-left">
            <?php if ($data['show_save_scenario'] ?? false): ?>
            <button type="button" 
                    class="qcc-button qcc-button--outline"
                    onclick="QCC.saveScenario()"
                    aria-label="<?php echo esc_attr($translator->get('save_scenario')); ?>">
                <span class="qcc-button-icon">💾</span>
                <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('save_scenario')); ?></span>
            </button>
            <?php endif; ?>
            
            <?php if ($data['show_compare'] ?? false): ?>
            <button type="button" 
                    class="qcc-button qcc-button--outline"
                    onclick="QCC.compareScenarios()"
                    aria-label="<?php echo esc_attr($translator->get('compare_scenarios')); ?>">
                <span class="qcc-button-icon">📊</span>
                <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('compare')); ?></span>
            </button>
            <?php endif; ?>
        </div>

        <div class="qcc-actions-right">
            <?php if ($data['show_print'] ?? true): ?>
            <button type="button" 
                    class="qcc-button qcc-button--outline"
                    onclick="QCC.printResults()"
                    aria-label="<?php echo esc_attr($translator->get('print_results')); ?>">
                <span class="qcc-button-icon">🖨️</span>
                <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('print')); ?></span>
            </button>
            <?php endif; ?>
            
            <button type="button" 
                    class="qcc-button qcc-button--primary"
                    onclick="QCC.generateReport()"
                    aria-label="<?php echo esc_attr($translator->get('generate_detailed_report')); ?>">
                <span class="qcc-button-icon">📄</span>
                <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('detailed_report')); ?></span>
            </button>
        </div>

    </div>
    <?php endif; ?>

</section>
<!-- QCC Results Section End -->

<!-- Results Section Styles -->
<style>
.qcc-results-section {
    background: var(--qcc-results-bg, #ffffff);
    border-radius: var(--qcc-results-radius, 12px);
    box-shadow: var(--qcc-results-shadow, 0 2px 12px rgba(0,0,0,0.08));
    overflow: hidden;
}

.qcc-results-section--loading {
    pointer-events: none;
    opacity: 0.7;
}

.qcc-results-header {
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid var(--qcc-color-border, #e5e7eb);
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.qcc-results-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.qcc-results-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--qcc-color-heading, #1f2937);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.qcc-live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--qcc-color-success-light, #dcfce7);
    color: var(--qcc-color-success, #16a34a);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.qcc-live-dot {
    width: 8px;
    height: 8px;
    background: var(--qcc-color-success, #16a34a);
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.qcc-results-description {
    margin: 0;
    color: var(--qcc-color-text-muted, #6b7280);
    line-height: 1.6;
}

.qcc-results-meta {
    margin-top: 1rem;
    font-size: 0.875rem;
    color: var(--qcc-color-text-muted, #6b7280);
}

.qcc-results-content {
    padding: 2rem;
}

.qcc-results-loading {
    text-align: center;
    padding: 3rem 2rem;
}

.qcc-loading-spinner {
    margin: 0 auto 1rem;
    width: 40px;
    height: 40px;
}

.qcc-spinner {
    width: 100%;
    height: 100%;
    border: 3px solid var(--qcc-color-border, #e5e7eb);
    border-top: 3px solid var(--qcc-color-primary, #3b82f6);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.qcc-loading-text {
    margin: 0;
    color: var(--qcc-color-text-muted, #6b7280);
    font-weight: 500;
}

.qcc-result-group {
    margin-bottom: 3rem;
}

.qcc-result-group:last-child {
    margin-bottom: 0;
}

.qcc-result-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.qcc-result-group-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: var(--qcc-color-heading, #1f2937);
}

.qcc-explanation-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: 1px solid var(--qcc-color-border, #e5e7eb);
    border-radius: 6px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.qcc-explanation-toggle:hover {
    background: var(--qcc-color-bg-light, #f9fafb);
    border-color: var(--qcc-color-primary, #3b82f6);
}

.qcc-result-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}

.qcc-result-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--qcc-card-accent, #e5e7eb);
    transition: all 0.3s ease;
}

.qcc-result-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.qcc-result-card--primary::before {
    background: var(--qcc-color-primary, #3b82f6);
}

.qcc-result-card--success::before {
    background: var(--qcc-color-success, #10b981);
}

.qcc-result-card--danger::before {
    background: var(--qcc-color-error, #ef4444);
}

.qcc-result-card--warning::before {
    background: var(--qcc-color-warning, #f59e0b);
}

.qcc-result-card--info::before {
    background: var(--qcc-color-info, #3b82f6);
}

.qcc-result-card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.qcc-result-card-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.qcc-result-card-title-area {
    flex: 1;
}

.qcc-result-card-title {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: var(--qcc-color-text, #374151);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.qcc-result-card-percentage {
    font-size: 0.75rem;
    color: var(--qcc-color-text-muted, #6b7280);
}

.qcc-result-card-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--qcc-color-heading, #1f2937);
    line-height: 1.2;
    margin-bottom: 0.5rem;
    font-variant-numeric: tabular-nums;
}

.qcc-result-card-trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.qcc-trend--up {
    color: var(--qcc-color-success, #10b981);
}

.qcc-trend--down {
    color: var(--qcc-color-error, #ef4444);
}

.qcc-result-card-footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--qcc-color-border, #e5e7eb);
}

.qcc-result-card-description {
    margin: 0;
    font-size: 0.875rem;
    color: var(--qcc-color-text-muted, #6b7280);
    line-height: 1.5;
}

/* ===== NEUE CoGQ/CoPQ BOX STYLES ===== */
.qcc-cogq-copq-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.qcc-quality-box {
    border-radius: 12px;
    padding: 1.5rem;
    border: 2px solid;
    position: relative;
    transition: all 0.3s ease;
}

.qcc-quality-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

/* CoGQ Box - Grün/Erfolg Styling */
.qcc-cogq-section {
    background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
    border-color: #16a34a;
}

/* CoPQ Box - Orange/Warnung Styling */
.qcc-copq-section {
    background: linear-gradient(135deg, #fff2f2, #fef8f8);
    border-color: #dc3545;
}

.qcc-quality-box-header {
    margin-bottom: 1rem;
    text-align: center;
}

.qcc-subsection-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    padding-bottom: 8px;
    border-bottom: 2px solid;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.qcc-cogq-section .qcc-subsection-title {
    border-bottom-color: #16a34a;
    color: #16a34a;
}

.qcc-copq-section .qcc-subsection-title {
    border-bottom-color: #dc3545;
    color: #dc3545;
}

.qcc-quality-box-icon {
    font-size: 1.2em;
}

.qcc-quality-box-content {
    margin-bottom: 1rem;
}

.qcc-result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    margin-bottom: 8px;
    border-radius: 8px;
    border-left: 4px solid;
    transition: all 0.2s ease;
    background: rgba(255,255,255,0.7);
}

.qcc-result-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.qcc-cogq-item {
    border-left-color: #16a34a;
    background: rgba(22, 163, 74, 0.1);
}

.qcc-copq-item {
    border-left-color: #dc3545;
    background: rgba(220, 53, 69, 0.1);
}

.qcc-result-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.qcc-result-icon {
    font-size: 1rem;
}

.qcc-result-value {
    font-weight: 600;
    font-size: 1.1rem;
    color: #1f2937;
    font-variant-numeric: tabular-nums;
}

/* Total Rows - Hervorgehoben */
.qcc-cogq-total,
.qcc-copq-total {
    background: var(--qcc-total-bg) !important;
    color: white !important;
    border-left: none !important;
    margin-top: 0.5rem;
    border-radius: 8px;
}

.qcc-cogq-total {
    --qcc-total-bg: #16a34a;
}

.qcc-copq-total {
    --qcc-total-bg: #dc3545;
}

.qcc-cogq-total .qcc-result-label,
.qcc-cogq-total .qcc-result-value,
.qcc-copq-total .qcc-result-label,
.qcc-copq-total .qcc-result-value {
    color: white !important;
    font-weight: 700 !important;
}

/* Percentage Display */
.qcc-quality-percentage {
    text-align: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.qcc-quality-percentage span {
    font-size: 1.5rem;
    font-weight: 700;
    display: block;
    margin-bottom: 0.25rem;
}

.qcc-cogq-section .qcc-quality-percentage span {
    color: #16a34a;
}

.qcc-copq-section .qcc-quality-percentage span {
    color: #dc3545;
}

.qcc-quality-percentage small {
    color: #6b7280;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.qcc-recommendations {
    background: var(--qcc-recommendations-bg, #f0f9ff);
    border: 1px solid var(--qcc-recommendations-border, #bae6fd);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.qcc-recommendations-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--qcc-color-primary, #3b82f6);
}

.qcc-recommendations-placeholder {
    margin: 0;
    color: var(--qcc-color-text-muted, #6b7280);
    font-style: italic;
}

.qcc-benchmarks {
    background: var(--qcc-benchmarks-bg, #fefce8);
    border: 1px solid var(--qcc-benchmarks-border, #fde047);
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 2rem;
}

.qcc-benchmarks-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--qcc-color-warning, #f59e0b);
}

.qcc-benchmark-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.qcc-benchmark-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--qcc-benchmark-item-bg, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--qcc-color-border, #e5e7eb);
}

.qcc-benchmark-label {
    font-weight: 500;
    color: var(--qcc-color-text, #374151);
}

.qcc-benchmark-value {
    font-weight: 600;
    color: var(--qcc-color-warning, #f59e0b);
}

.qcc-results-actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--qcc-color-border, #e5e7eb);
    background: var(--qcc-results-footer-bg, #f9fafb);
}

.qcc-actions-left,
.qcc-actions-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.qcc-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    font-size: 0.875rem;
}

.qcc-button--primary {
    background: var(--qcc-color-primary, #3b82f6);
    color: white;
}

.qcc-button--primary:hover {
    background: var(--qcc-color-primary-dark, #2563eb);
}

.qcc-button--outline {
    background: transparent;
    border-color: var(--qcc-color-border, #e5e7eb);
    color: var(--qcc-color-text, #374151);
}

.qcc-button--outline:hover {
    background: var(--qcc-color-bg-light, #f9fafb);
    border-color: var(--qcc-color-primary, #3b82f6);
}

.qcc-button--small {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

/* Animation Classes for JavaScript */
.qcc-result-card-value.qcc-updating,
.qcc-result-value.qcc-updating {
    animation: valueUpdate 0.5s ease-in-out;
}

@keyframes valueUpdate {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); background: var(--qcc-color-primary-light, #dbeafe); }
    100% { transform: scale(1); }
}

.qcc-explanation-content.qcc-show {
    display: block !important;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        padding-top: 0;
    }
    to {
        opacity: 1;
        max-height: 200px;
        padding-top: 1rem;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-results-header,
    .qcc-results-content,
    .qcc-results-actions-bar {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .qcc-results-title-row {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .qcc-result-cards {
        grid-template-columns: 1fr;
    }
    
    /* CoGQ/CoPQ responsive */
    .qcc-cogq-copq-container {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .qcc-results-actions-bar {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .qcc-actions-left,
    .qcc-actions-right {
        justify-content: center;
    }
    
    .qcc-benchmark-items {
        grid-template-columns: 1fr;
    }
    
    .qcc-result-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .qcc-result-value {
        align-self: flex-end;
    }
}

@media (max-width: 480px) {
    .qcc-result-card {
        padding: 1rem;
    }
    
    .qcc-result-card-value {
        font-size: 1.5rem;
    }
    
    .qcc-quality-box {
        padding: 1rem;
    }
    
    .qcc-button {
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
    }
}

/* Print Styles */
@media print {
    .qcc-results-actions-bar,
    .qcc-explanation-toggle {
        display: none !important;
    }
    
    .qcc-explanation-content {
        display: block !important;
    }
    
    .qcc-result-cards {
        grid-template-columns: repeat(2, 1fr);
        break-inside: avoid;
    }
    
    .qcc-result-card {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #ccc;
    }
    
    .qcc-cogq-copq-container {
        grid-template-columns: 1fr 1fr;
        break-inside: avoid;
    }
    
    .qcc-quality-box {
        break-inside: avoid;
        box-shadow: none;
        border: 2px solid #333;
    }
}
</style>

<?php if ($debug['debug_mode'] ?? false): ?>
<!-- Debug Information -->
<div class="qcc-debug-info" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
    <details>
        <summary>🐛 QCC Results Section Debug Info</summary>
        <pre><?php echo $helpers['escape'](print_r(array(
            'template' => 'sections/results-section',
            'section_id' => $section_id,
            'layout' => $layout,
            'live_update' => $live_update,
            'currency' => $currency,
            'unit' => $unit,
            'result_cards_groups' => count($result_cards),
            'total_cards' => array_sum(array_map(function($group) { return count($group['cards']); }, $result_cards)),
            'show_explanations' => $show_explanations,
            'results_sample' => array_slice($results, 0, 3, true),
            'cogq_copq_added' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ), true)); ?></pre>
    </details>
</div>
<?php endif; ?>

<?php
/**
 * ✅ SCHRITT 1 ABGESCHLOSSEN: Template mit CoGQ/CoPQ Boxen erweitert!
 * 
 * 🚀 Was hinzugefügt wurde:
 * 
 * 1. ✅ HTML-STRUKTUR:
 *    - CoGQ Box (Grün) mit Prevention + Appraisal
 *    - CoPQ Box (Rot) mit Internal + External  
 *    - Grid-Container für nebeneinander Layout
 *    - Alle Element-IDs für JavaScript-Integration
 * 
 * 2. ✅ CSS-STYLING:
 *    - CoGQ: Grüner Gradient-Hintergrund
 *    - CoPQ: Roter Gradient-Hintergrund
 *    - Hover-Effekte und Animationen
 *    - Responsive Design (Mobile: untereinander)
 *    - Update-Animationen für JavaScript
 * 
 * 3. ✅ ELEMENT-IDS:
 *    - qcc-cogq-prevention: Prevention in CoGQ Box
 *    - qcc-cogq-appraisal: Appraisal in CoGQ Box
 *    - qcc-cogq-total: Total CoGQ
 *    - qcc-cogq-percentage: CoGQ Percentage
 *    - qcc-copq-internal: Internal in CoPQ Box
 *    - qcc-copq-external: External in CoPQ Box
 *    - qcc-copq-total: Total CoPQ
 *    - qcc-copq-percentage: CoPQ Percentage
 * 
 * 4. ✅ INTEGRATION:
 *    - Nahtlos in bestehende Template-Struktur
 *    - Verwendet bestehende $helpers und $results
 *    - Folgt WordPress und QCC Coding Standards
 *    - Responsive und Print-optimiert
 * 
 * 📋 STATUS NACH SCHRITT 1:
 * 
 * ✅ Die CoGQ/CoPQ Boxen werden angezeigt
 * ✅ Styling ist vollständig implementiert  
 * ✅ Responsive Design funktioniert
 * ❌ Live-Updates fehlen noch (JavaScript)
 * ❌ Werte werden noch nicht automatisch berechnet
 * 
 * 🎯 NÄCHSTE SCHRITTE:
 * 
 * SCHRITT 2: JavaScript für Live-Updates erweitern
 * SCHRITT 3: Testing der kompletten Funktionalität
 * SCHRITT 4: Feintuning und Optimierungen
 * 
 * 💡 USAGE:
 * Diese Datei ersetzt die bestehende:
 * templates/calculator/sections/results-section.php
 * 
 * Die CoGQ/CoPQ Boxen erscheinen automatisch nach den 
 * bestehenden Result-Cards und vor den Recommendations.
 */
?>
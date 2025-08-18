<?php
/**
 * QCC Chart Section Template
 * 
 * Chart-Visualisierung für Calculator-Ergebnisse
 * Unterstützt Doughnut, Bar, Line und Radar Charts
 * 
 * Template-Pfad: templates/calculator/sections/chart-section.php
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
$title = $data['title'] ?? $translator->get('cost_visualization');
$description = $data['description'] ?? $translator->get('visual_breakdown_of_costs');
$chart_type = $data['chart_type'] ?? 'doughnut'; // doughnut, bar, line, radar
$responsive = $data['responsive'] ?? true;
$show_legend = $data['show_legend'] ?? true;
$show_values = $data['show_values'] ?? true;
$animation = $data['animation'] ?? true;
$currency = $data['currency'] ?? 'EUR';
$unit = $data['unit'] ?? 'billions';

// Chart-Typen-Konfiguration
$available_chart_types = array(
    'doughnut' => array(
        'label' => $translator->get('doughnut_chart'),
        'icon' => '🍩',
        'description' => $translator->get('show_proportional_breakdown')
    ),
    'bar' => array(
        'label' => $translator->get('bar_chart'),
        'icon' => '📊',
        'description' => $translator->get('compare_cost_categories')
    ),
    'line' => array(
        'label' => $translator->get('line_chart'),
        'icon' => '📈',
        'description' => $translator->get('show_cost_trends')
    ),
    'radar' => array(
        'label' => $translator->get('radar_chart'),
        'icon' => '🎯',
        'description' => $translator->get('multi_dimensional_view')
    )
);

// Chart-Daten vorbereiten (werden von JavaScript aktualisiert)
$chart_datasets = array(
    'cogq_copq' => array(
        'label' => $translator->get('cogq_vs_copq'),
        'data' => array(
            array(
                'label' => $translator->get('cost_of_good_quality'),
                'value' => 0,
                'color' => '#10b981',
                'category' => 'cogq'
            ),
            array(
                'label' => $translator->get('cost_of_poor_quality'),
                'value' => 0,
                'color' => '#ef4444',
                'category' => 'copq'
            )
        )
    ),
    'detailed_breakdown' => array(
        'label' => $translator->get('detailed_cost_breakdown'),
        'data' => array(
            array(
                'label' => $translator->get('prevention_costs'),
                'value' => 0,
                'color' => '#16a34a',
                'category' => 'cogq'
            ),
            array(
                'label' => $translator->get('appraisal_costs'),
                'value' => 0,
                'color' => '#22c55e',
                'category' => 'cogq'
            ),
            array(
                'label' => $translator->get('internal_defect_costs'),
                'value' => 0,
                'color' => '#f59e0b',
                'category' => 'copq'
            ),
            array(
                'label' => $translator->get('external_defect_costs'),
                'value' => 0,
                'color' => '#dc2626',
                'category' => 'copq'
            )
        )
    )
);

// Aktives Dataset
$active_dataset = $data['active_dataset'] ?? 'cogq_copq';
$current_dataset = $chart_datasets[$active_dataset] ?? $chart_datasets['cogq_copq'];

// CSS-Klassen für Chart-Section
$chart_classes = $helpers['build_css_classes'](
    array('qcc-chart-section'),
    array(
        'qcc-chart-section--' . $chart_type => true,
        'qcc-chart-section--responsive' => $responsive,
        'qcc-chart-section--with-legend' => $show_legend,
        'qcc-chart-section--animated' => $animation
    )
);

// Chart-Container-ID für JavaScript
$chart_canvas_id = 'qcc-chart-' . $section_id;
?>

<!-- QCC Chart Section Start -->
<section id="<?php echo esc_attr($section_id); ?>" 
         class="<?php echo esc_attr($chart_classes); ?>"
         role="region"
         aria-label="<?php echo esc_attr($translator->get('chart_visualization')); ?>"
         data-chart-type="<?php echo esc_attr($chart_type); ?>"
         data-dataset="<?php echo esc_attr($active_dataset); ?>"
         data-currency="<?php echo esc_attr($currency); ?>"
         data-unit="<?php echo esc_attr($unit); ?>">

    <!-- Section Header -->
    <div class="qcc-chart-header">
        <div class="qcc-chart-title-row">
            <h2 class="qcc-chart-title">
                <?php echo $helpers['escape']($title); ?>
            </h2>
            
            <?php if ($data['show_controls'] ?? true): ?>
            <div class="qcc-chart-controls">
                
                <!-- Chart Type Selector -->
                <div class="qcc-chart-control-group">
                    <label for="qcc-chart-type-select" class="qcc-control-label">
                        <?php echo $helpers['escape']($translator->get('chart_type')); ?>
                    </label>
                    <select id="qcc-chart-type-select" 
                            class="qcc-control-select"
                            onchange="QCC.changeChartType(this.value)"
                            aria-label="<?php echo esc_attr($translator->get('select_chart_type')); ?>">
                        <?php foreach ($available_chart_types as $type_key => $type_config): ?>
                        <option value="<?php echo esc_attr($type_key); ?>" 
                                <?php selected($chart_type, $type_key); ?>>
                            <?php echo $helpers['escape']($type_config['icon'] . ' ' . $type_config['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Dataset Selector -->
                <div class="qcc-chart-control-group">
                    <label for="qcc-dataset-select" class="qcc-control-label">
                        <?php echo $helpers['escape']($translator->get('data_view')); ?>
                    </label>
                    <select id="qcc-dataset-select" 
                            class="qcc-control-select"
                            onchange="QCC.changeDataset(this.value)"
                            aria-label="<?php echo esc_attr($translator->get('select_data_view')); ?>">
                        <?php foreach ($chart_datasets as $dataset_key => $dataset_config): ?>
                        <option value="<?php echo esc_attr($dataset_key); ?>" 
                                <?php selected($active_dataset, $dataset_key); ?>>
                            <?php echo $helpers['escape']($dataset_config['label']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Chart Actions -->
                <div class="qcc-chart-actions">
                    <button type="button" 
                            class="qcc-chart-action-btn"
                            onclick="QCC.downloadChart('png')"
                            title="<?php echo esc_attr($translator->get('download_chart_as_image')); ?>"
                            aria-label="<?php echo esc_attr($translator->get('download_chart_as_image')); ?>">
                        📸
                    </button>
                    
                    <button type="button" 
                            class="qcc-chart-action-btn"
                            onclick="QCC.toggleChartFullscreen()"
                            title="<?php echo esc_attr($translator->get('toggle_fullscreen')); ?>"
                            aria-label="<?php echo esc_attr($translator->get('toggle_fullscreen')); ?>">
                        ⛶
                    </button>
                </div>

            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($description)): ?>
        <p class="qcc-chart-description">
            <?php echo $helpers['escape']($description); ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Chart Container -->
    <div class="qcc-chart-container">
        
        <!-- Chart Canvas -->
        <div class="qcc-chart-canvas-wrapper">
            <canvas id="<?php echo esc_attr($chart_canvas_id); ?>" 
                    class="qcc-chart-canvas"
                    role="img"
                    aria-label="<?php echo esc_attr($translator->get('quality_cost_chart')); ?>"
                    width="400" 
                    height="400">
                <!-- Fallback für Browser ohne Canvas-Support -->
                <div class="qcc-chart-fallback">
                    <p><?php echo $helpers['escape']($translator->get('chart_not_supported')); ?></p>
                    <div class="qcc-chart-fallback-data">
                        <?php foreach ($current_dataset['data'] as $data_point): ?>
                        <div class="qcc-fallback-item">
                            <span class="qcc-fallback-label"><?php echo $helpers['escape']($data_point['label']); ?>:</span>
                            <span class="qcc-fallback-value" data-currency="<?php echo esc_attr($currency); ?>">
                                <?php echo $helpers['format_currency']($data_point['value'], $currency); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </canvas>
            
            <!-- Loading Overlay -->
            <div class="qcc-chart-loading" id="qcc-chart-loading" style="display: none;">
                <div class="qcc-chart-spinner">
                    <div class="qcc-spinner"></div>
                </div>
                <p class="qcc-loading-text">
                    <?php echo $helpers['escape']($translator->get('updating_chart')); ?>
                </p>
            </div>
        </div>

        <?php if ($show_legend && $data['custom_legend'] ?? true): ?>
        <!-- Custom Legend -->
        <div class="qcc-chart-legend" id="qcc-chart-legend">
            <h3 class="qcc-legend-title">
                <?php echo $helpers['escape']($translator->get('legend')); ?>
            </h3>
            
            <div class="qcc-legend-items" id="qcc-legend-items">
                <?php foreach ($current_dataset['data'] as $index => $data_point): ?>
                <div class="qcc-legend-item" 
                     data-index="<?php echo esc_attr($index); ?>"
                     data-category="<?php echo esc_attr($data_point['category']); ?>">
                    
                    <div class="qcc-legend-color" 
                         style="background-color: <?php echo esc_attr($data_point['color']); ?>"></div>
                    
                    <div class="qcc-legend-content">
                        <div class="qcc-legend-label">
                            <?php echo $helpers['escape']($data_point['label']); ?>
                        </div>
                        <div class="qcc-legend-value" 
                             data-field="<?php echo esc_attr(strtolower(str_replace(' ', '_', $data_point['label']))); ?>">
                            <?php echo $helpers['format_currency']($data_point['value'], $currency); ?>
                        </div>
                        <div class="qcc-legend-percentage" 
                             data-field="percentage-<?php echo esc_attr($index); ?>">
                            0%
                        </div>
                    </div>
                    
                    <button type="button" 
                            class="qcc-legend-toggle"
                            onclick="QCC.toggleDataPoint(<?php echo esc_attr($index); ?>)"
                            aria-label="<?php echo esc_attr(sprintf($translator->get('toggle_data_point'), $data_point['label'])); ?>">
                        👁️
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <?php if ($data['show_insights'] ?? true): ?>
    <!-- Chart Insights -->
    <div class="qcc-chart-insights" id="qcc-chart-insights">
        <h3 class="qcc-insights-title">
            <?php echo $helpers['escape']($translator->get('key_insights')); ?>
        </h3>
        
        <div class="qcc-insights-content" id="qcc-insights-content">
            <!-- Wird von JavaScript basierend auf Chart-Daten gefüllt -->
            <div class="qcc-insight-placeholder">
                <p><?php echo $helpers['escape']($translator->get('insights_will_appear_after_calculation')); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($data['show_benchmarks'] ?? false): ?>
    <!-- Benchmark Comparison -->
    <div class="qcc-chart-benchmarks">
        <h3 class="qcc-benchmarks-title">
            <?php echo $helpers['escape']($translator->get('benchmark_comparison')); ?>
        </h3>
        
        <div class="qcc-benchmark-chart-container">
            <canvas id="qcc-benchmark-chart" 
                    class="qcc-benchmark-chart"
                    width="400" 
                    height="200">
            </canvas>
        </div>
    </div>
    <?php endif; ?>

</section>
<!-- QCC Chart Section End -->

<!-- Chart Configuration for JavaScript -->
<script type="application/json" id="qcc-chart-config-<?php echo esc_attr($section_id); ?>">
{
    "canvasId": "<?php echo esc_js($chart_canvas_id); ?>",
    "chartType": "<?php echo esc_js($chart_type); ?>",
    "activeDataset": "<?php echo esc_js($active_dataset); ?>",
    "currency": "<?php echo esc_js($currency); ?>",
    "unit": "<?php echo esc_js($unit); ?>",
    "responsive": <?php echo json_encode($responsive); ?>,
    "showLegend": <?php echo json_encode($show_legend); ?>,
    "showValues": <?php echo json_encode($show_values); ?>,
    "animation": <?php echo json_encode($animation); ?>,
    "datasets": <?php echo wp_json_encode($chart_datasets); ?>,
    "translations": {
        "cogq": "<?php echo esc_js($translator->get('cost_of_good_quality')); ?>",
        "copq": "<?php echo esc_js($translator->get('cost_of_poor_quality')); ?>",
        "total": "<?php echo esc_js($translator->get('total')); ?>",
        "percentage": "<?php echo esc_js($translator->get('percentage')); ?>",
        "noData": "<?php echo esc_js($translator->get('no_data_available')); ?>"
    },
    "colors": {
        "cogq": "#10b981",
        "copq": "#ef4444", 
        "prevention": "#16a34a",
        "appraisal": "#22c55e",
        "internal": "#f59e0b",
        "external": "#dc2626",
        "opportunity": "#3b82f6"
    }
}
</script>

<!-- Chart Section Styles -->
<style>
.qcc-chart-section {
    background: var(--qcc-chart-bg, #ffffff);
    border-radius: var(--qcc-chart-radius, 12px);
    box-shadow: var(--qcc-chart-shadow, 0 2px 12px rgba(0,0,0,0.08));
    overflow: hidden;
}

.qcc-chart-header {
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid var(--qcc-color-border, #e5e7eb);
    background: var(--qcc-chart-header-bg, #f8fafc);
}

.qcc-chart-title-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    margin-bottom: 0.5rem;
}

.qcc-chart-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--qcc-color-heading, #1f2937);
}

.qcc-chart-controls {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
}

.qcc-chart-control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 140px;
}

.qcc-control-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--qcc-color-text, #374151);
}

.qcc-control-select {
    background: var(--qcc-input-bg, #ffffff);
    border: 1px solid var(--qcc-color-border, #d1d5db);
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: var(--qcc-color-text, #374151);
}

.qcc-control-select:focus {
    outline: none;
    border-color: var(--qcc-color-primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.qcc-chart-actions {
    display: flex;
    gap: 0.5rem;
}

.qcc-chart-action-btn {
    background: var(--qcc-button-bg, #f3f4f6);
    border: 1px solid var(--qcc-color-border, #d1d5db);
    border-radius: 6px;
    padding: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qcc-chart-action-btn:hover {
    background: var(--qcc-button-hover-bg, #e5e7eb);
    border-color: var(--qcc-color-primary, #3b82f6);
}

.qcc-chart-description {
    margin: 0;
    color: var(--qcc-color-text-muted, #6b7280);
    line-height: 1.6;
}

.qcc-chart-container {
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 2rem;
    align-items: start;
}

.qcc-chart-section--responsive .qcc-chart-container {
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.qcc-chart-canvas-wrapper {
    position: relative;
    max-width: 100%;
    background: var(--qcc-canvas-bg, #ffffff);
    border-radius: 8px;
    border: 1px solid var(--qcc-color-border, #e5e7eb);
    overflow: hidden;
}

.qcc-chart-canvas {
    display: block;
    max-width: 100%;
    height: auto;
}

.qcc-chart-fallback {
    padding: 2rem;
    text-align: center;
    background: var(--qcc-fallback-bg, #f9fafb);
}

.qcc-chart-fallback-data {
    margin-top: 1rem;
    display: grid;
    gap: 0.5rem;
}

.qcc-fallback-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: var(--qcc-fallback-item-bg, #ffffff);
    border-radius: 4px;
    border: 1px solid var(--qcc-color-border, #e5e7eb);
}

.qcc-fallback-label {
    font-weight: 500;
}

.qcc-fallback-value {
    font-weight: 600;
    color: var(--qcc-color-primary, #3b82f6);
}

.qcc-chart-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
}

.qcc-chart-spinner {
    margin-bottom: 1rem;
}

.qcc-spinner {
    width: 40px;
    height: 40px;
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

.qcc-chart-legend {
    background: var(--qcc-legend-bg, #f9fafb);
    border: 1px solid var(--qcc-color-border, #e5e7eb);
    border-radius: 8px;
    padding: 1.5rem;
    min-width: 280px;
    max-width: 320px;
}

.qcc-legend-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--qcc-color-heading, #1f2937);
}

.qcc-legend-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.qcc-legend-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--qcc-legend-item-bg, #ffffff);
    border-radius: 6px;
    border: 1px solid var(--qcc-color-border, #e5e7eb);
    transition: all 0.2s ease;
}

.qcc-legend-item:hover {
    border-color: var(--qcc-color-primary, #3b82f6);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.qcc-legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    flex-shrink: 0;
}

.qcc-legend-content {
    flex: 1;
}

.qcc-legend-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--qcc-color-text, #374151);
    margin-bottom: 0.25rem;
}

.qcc-legend-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--qcc-color-heading, #1f2937);
    font-variant-numeric: tabular-nums;
}

.qcc-legend-percentage {
    font-size: 0.75rem;
    color: var(--qcc-color-text-muted, #6b7280);
    font-variant-numeric: tabular-nums;
}

.qcc-legend-toggle {
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    font-size: 0.875rem;
}

.qcc-legend-toggle:hover {
    background: var(--qcc-button-hover-bg, #e5e7eb);
}

.qcc-legend-item.qcc-hidden {
    opacity: 0.5;
}

.qcc-legend-item.qcc-hidden .qcc-legend-toggle::after {
    content: ' (ausgeblendet)';
    font-size: 0.75rem;
    color: var(--qcc-color-text-muted, #6b7280);
}

.qcc-chart-insights {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--qcc-insights-bg, #f0f9ff);
    border: 1px solid var(--qcc-insights-border, #bae6fd);
    border-radius: 8px;
}

.qcc-insights-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--qcc-color-primary, #3b82f6);
}

.qcc-insights-content {
    font-size: 0.875rem;
    line-height: 1.6;
}

.qcc-insight-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: var(--qcc-insight-item-bg, rgba(255, 255, 255, 0.7));
    border-radius: 6px;
}

.qcc-insight-icon {
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.qcc-insight-text {
    color: var(--qcc-color-text, #374151);
}

.qcc-insight-placeholder {
    text-align: center;
    color: var(--qcc-color-text-muted, #6b7280);
    font-style: italic;
}

.qcc-chart-benchmarks {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--qcc-benchmarks-bg, #fefce8);
    border: 1px solid var(--qcc-benchmarks-border, #fde047);
    border-radius: 8px;
}

.qcc-benchmarks-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem;
    color: var(--qcc-color-warning, #f59e0b);
}

.qcc-benchmark-chart-container {
    position: relative;
    background: var(--qcc-benchmark-chart-bg, #ffffff);
    border-radius: 6px;
    border: 1px solid var(--qcc-color-border, #e5e7eb);
    overflow: hidden;
}

.qcc-benchmark-chart {
    display: block;
    width: 100%;
    max-width: 100%;
    height: auto;
}

/* Chart Type Specific Styles */
.qcc-chart-section--doughnut .qcc-chart-canvas-wrapper {
    aspect-ratio: 1;
    max-width: 500px;
    margin: 0 auto;
}

.qcc-chart-section--bar .qcc-chart-canvas-wrapper,
.qcc-chart-section--line .qcc-chart-canvas-wrapper {
    aspect-ratio: 16/9;
    max-width: 700px;
}

.qcc-chart-section--radar .qcc-chart-canvas-wrapper {
    aspect-ratio: 1;
    max-width: 600px;
    margin: 0 auto;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .qcc-chart-container {
        grid-template-columns: 1fr;
    }
    
    .qcc-chart-legend {
        max-width: none;
        min-width: auto;
    }
    
    .qcc-legend-items {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        display: grid;
    }
}

@media (max-width: 768px) {
    .qcc-chart-header,
    .qcc-chart-container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .qcc-chart-title-row {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .qcc-chart-controls {
        width: 100%;
        justify-content: space-between;
    }
    
    .qcc-chart-control-group {
        min-width: 120px;
        flex: 1;
    }
    
    .qcc-legend-items {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .qcc-chart-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .qcc-chart-control-group {
        min-width: auto;
    }
    
    .qcc-chart-actions {
        justify-content: center;
        margin-top: 0.5rem;
    }
}

/* Print Styles */
@media print {
    .qcc-chart-controls,
    .qcc-chart-actions,
    .qcc-legend-toggle {
        display: none !important;
    }
    
    .qcc-chart-container {
        grid-template-columns: 1fr;
        break-inside: avoid;
    }
    
    .qcc-chart-canvas-wrapper {
        max-width: 100%;
        page-break-inside: avoid;
    }
    
    .qcc-legend-item {
        break-inside: avoid;
    }
}

/* Animation Classes */
.qcc-chart-canvas.qcc-updating {
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.qcc-legend-item.qcc-highlight {
    background: var(--qcc-color-primary-light, #dbeafe);
    border-color: var(--qcc-color-primary, #3b82f6);
    animation: highlight 0.5s ease-in-out;
}

@keyframes highlight {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

/* Fullscreen Mode */
.qcc-chart-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: var(--qcc-fullscreen-bg, #ffffff);
    display: flex;
    flex-direction: column;
    padding: 2rem;
}

.qcc-chart-fullscreen .qcc-chart-container {
    flex: 1;
    grid-template-columns: 1fr;
    max-width: 1000px;
    margin: 0 auto;
}

.qcc-chart-fullscreen .qcc-chart-canvas-wrapper {
    max-width: none;
    width: 100%;
    height: 70vh;
}
</style>

<?php if ($debug['debug_mode'] ?? false): ?>
<!-- Debug Information -->
<div class="qcc-debug-info" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
    <details>
        <summary>🐛 QCC Chart Section Debug Info</summary>
        <pre><?php echo $helpers['escape'](print_r(array(
            'template' => 'sections/chart-section',
            'section_id' => $section_id,
            'chart_type' => $chart_type,
            'active_dataset' => $active_dataset,
            'canvas_id' => $chart_canvas_id,
            'responsive' => $responsive,
            'show_legend' => $show_legend,
            'show_values' => $show_values,
            'animation' => $animation,
            'currency' => $currency,
            'unit' => $unit,
            'available_chart_types' => array_keys($available_chart_types),
            'datasets_count' => count($chart_datasets),
            'current_dataset_points' => count($current_dataset['data']),
            'timestamp' => date('Y-m-d H:i:s')
        ), true)); ?></pre>
    </details>
</div>
<?php endif; ?>

<?php
/**
 * Template-Dokumentation:
 * 
 * Erforderliche Daten:
 * - Keine (verwendet intelligente Defaults)
 * 
 * Optionale Daten:
 * - $data['title'] - Section-Titel
 * - $data['description'] - Section-Beschreibung
 * - $data['chart_type'] - Chart-Typ (doughnut, bar, line, radar)
 * - $data['active_dataset'] - Aktives Dataset (cogq_copq, detailed_breakdown)
 * - $data['responsive'] - Responsive Chart aktiviert
 * - $data['show_legend'] - Legend anzeigen
 * - $data['show_values'] - Werte in Chart anzeigen
 * - $data['animation'] - Chart-Animationen aktiviert
 * - $data['currency'] - Aktuelle Währung
 * - $data['unit'] - Aktuelle Einheit
 * - $data['show_controls'] - Chart-Controls anzeigen
 * - $data['custom_legend'] - Custom Legend verwenden
 * - $data['show_insights'] - Insights-Section anzeigen
 * - $data['show_benchmarks'] - Benchmark-Chart anzeigen
 * 
 * Chart-Typen:
 * - doughnut - Kreisdiagramm für proportionale Darstellung
 * - bar - Balkendiagramm für Vergleiche
 * - line - Liniendiagramm für Trends
 * - radar - Netzdiagramm für multidimensionale Ansicht
 * 
 * Datasets:
 * - cogq_copq - COGQ vs COPQ Übersicht
 * - detailed_breakdown - Detaillierte 4-Kategorie-Aufschlüsselung
 * 
 * JavaScript-Integration:
 * - QCC.initChart(sectionId) - Chart initialisieren
 * - QCC.updateChart(data) - Chart-Daten aktualisieren
 * - QCC.changeChartType(type) - Chart-Typ wechseln
 * - QCC.changeDataset(dataset) - Dataset wechseln
 * - QCC.toggleDataPoint(index) - Datenpunkt ein-/ausblenden
 * - QCC.downloadChart(format) - Chart als Bild herunterladen
 * - QCC.toggleChartFullscreen() - Vollbild-Modus
 * 
 * Chart.js Integration:
 * - Automatische Chart.js-Initialisierung basierend auf Konfiguration
 * - Responsive Chart-Sizing
 * - Custom Legend-Integration
 * - Animation-Controls
 * - Data-Update-Mechanismen
 * 
 * CSS-Klassen:
 * - .qcc-chart-section - Basis-Chart-Section
 * - .qcc-chart-container - Chart-Container mit Grid
 * - .qcc-chart-canvas-wrapper - Canvas-Wrapper
 * - .qcc-chart-legend - Custom Legend
 * - .qcc-legend-item - Legend-Eintrag
 * - .qcc-chart-insights - Insights-Section
 * - .qcc-chart-benchmarks - Benchmark-Section
 * 
 * Accessibility:
 * - role="img" für Chart-Canvas
 * - aria-label für Chart-Beschreibung
 * - Canvas-Fallback für Browser ohne Support
 * - Keyboard-Navigation für Legend
 * - Screen-Reader-freundliche Datentabelle als Fallback
 * 
 * Performance:
 * - Template-Caching automatisch
 * - Chart.js mit optimalen Performance-Settings
 * - Lazy Chart-Initialization
 * - Efficient Data-Update-Mechanismen
 * - Responsive Image-Sizing
 * 
 * Responsive Features:
 * - Mobile-First Chart-Layouts
 * - Adaptive Legend-Positioning
 * - Touch-friendly Controls
 * - Fullscreen-Mode für detaillierte Analyse
 * - Print-optimierte Styles
 */
?>
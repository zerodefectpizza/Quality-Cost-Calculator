<?php
/**
 * Template: Main Container Layout
 * Haupt-Container für den Quality Cost Calculator
 * 
 * @param array $data Layout-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$id = $data['id'] ?? 'qcc-calculator';
$theme = $data['theme'] ?? 'default';
$language = $data['language'] ?? 'en';
$currency = $data['currency'] ?? 'EUR';
$unit = $data['unit'] ?? 'millions';
$css_classes = $attributes['css_classes'] ?? '';
$layout_type = $data['layout_type'] ?? 'two-column'; // single-column, two-column, grid
$container_width = $data['container_width'] ?? 'max-width';
$responsive = $data['responsive'] ?? true;

// Theme-spezifische CSS-Klassen
$theme_class = 'qcc-theme-' . $theme;
$layout_class = 'qcc-layout-' . $layout_type;
$width_class = 'qcc-width-' . $container_width;

// Container-Attribute
$container_attributes = array(
    'id' => $id,
    'class' => "qcc-container {$theme_class} {$layout_class} {$width_class} {$css_classes}",
    'data-language' => $language,
    'data-currency' => $currency,
    'data-unit' => $unit,
    'data-theme' => $theme
);

if (isset($data['config'])) {
    $container_attributes['data-config'] = wp_json_encode($data['config']);
}
?>

<div <?php echo $this->render_attributes($container_attributes); ?>>
    
    <!-- Container Header -->
    <?php if (isset($data['header']) && $data['header']): ?>
    <header class="qcc-container-header">
        <?php echo $data['header']; ?>
    </header>
    <?php endif; ?>
    
    <!-- Status Messages Container -->
    <div id="qcc-status-container" class="qcc-status-container">
        <!-- Status messages will be inserted here -->
    </div>
    
    <!-- Main Content Area -->
    <main class="qcc-main-content" role="main">
        
        <!-- Controls Section -->
        <?php if (isset($data['controls']) && $data['controls']): ?>
        <section class="qcc-controls-section" aria-label="<?php echo esc_attr($translator->get('controls_section', 'Calculator Controls')); ?>">
            <?php echo $data['controls']; ?>
        </section>
        <?php endif; ?>
        
        <!-- Primary Content Grid -->
        <div class="qcc-content-grid">
            
            <!-- Input Section -->
            <?php if (isset($data['input_section']) && $data['input_section']): ?>
            <section class="qcc-input-section" aria-label="<?php echo esc_attr($translator->get('input_section', 'Input Parameters')); ?>">
                <div class="qcc-section-wrapper">
                    <?php echo $data['input_section']; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Results Section -->
            <?php if (isset($data['results_section']) && $data['results_section']): ?>
            <section class="qcc-results-section" aria-label="<?php echo esc_attr($translator->get('results_section', 'Calculated Results')); ?>">
                <div class="qcc-section-wrapper">
                    <?php echo $data['results_section']; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Additional Sections -->
            <?php if (isset($data['additional_sections']) && is_array($data['additional_sections'])): ?>
                <?php foreach ($data['additional_sections'] as $section_id => $section_content): ?>
                <section class="qcc-additional-section qcc-section-<?php echo esc_attr($section_id); ?>">
                    <div class="qcc-section-wrapper">
                        <?php echo $section_content; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>
            
        </div>
        
        <!-- Chart Section (Full Width) -->
        <?php if (isset($data['chart_section']) && $data['chart_section']): ?>
        <section class="qcc-chart-section" aria-label="<?php echo esc_attr($translator->get('chart_section', 'Cost Visualization')); ?>">
            <div class="qcc-section-wrapper">
                <?php echo $data['chart_section']; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Action Buttons Section -->
        <?php if (isset($data['actions']) && $data['actions']): ?>
        <section class="qcc-actions-section">
            <div class="qcc-section-wrapper">
                <?php echo $data['actions']; ?>
            </div>
        </section>
        <?php endif; ?>
        
    </main>
    
    <!-- Container Footer -->
    <?php if (isset($data['footer']) && $data['footer']): ?>
    <footer class="qcc-container-footer">
        <?php echo $data['footer']; ?>
    </footer>
    <?php endif; ?>
    
    <!-- Loading Overlay -->
    <div id="qcc-loading-overlay" class="qcc-loading-overlay" style="display: none;">
        <div class="qcc-loading-spinner">
            <svg class="qcc-spinner" viewBox="0 0 50 50">
                <circle class="qcc-spinner-path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"/>
            </svg>
            <div class="qcc-loading-text">
                <?php echo esc_html($translator->get('loading', 'Loading...')); ?>
            </div>
        </div>
    </div>
    
</div>

<style>
/* Base Container Styles */
.qcc-container {
    position: relative;
    font-family: 'Montserrat', 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background-color: #ffffff;
    color: #353535;
    line-height: 1.6;
    box-sizing: border-box;
}

.qcc-container *,
.qcc-container *::before,
.qcc-container *::after {
    box-sizing: inherit;
}

/* Container Width Variants */
.qcc-width-max-width {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.qcc-width-full-width {
    width: 100%;
    padding: 20px;
}

.qcc-width-contained {
    max-width: 960px;
    margin: 0 auto;
    padding: 20px;
}

.qcc-width-narrow {
    max-width: 768px;
    margin: 0 auto;
    padding: 20px;
}

/* Container Background & Shadow */
.qcc-container {
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin: 20px auto;
    overflow: hidden;
}

/* Header Styles */
.qcc-container-header {
    margin-bottom: 30px;
}

/* Status Container */
.qcc-status-container {
    margin-bottom: 20px;
}

.qcc-status-container:empty {
    margin-bottom: 0;
}

/* Main Content */
.qcc-main-content {
    position: relative;
}

/* Controls Section */
.qcc-controls-section {
    margin-bottom: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 2px solid #E7F9DE;
}

/* Content Grid Layouts */
.qcc-layout-single-column .qcc-content-grid {
    display: block;
}

.qcc-layout-two-column .qcc-content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.qcc-layout-grid .qcc-content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Section Styles */
.qcc-input-section,
.qcc-results-section,
.qcc-additional-section {
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.qcc-section-wrapper {
    padding: 25px;
}

/* Chart Section (Full Width) */
.qcc-chart-section {
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    margin-top: 20px;
}

.qcc-layout-two-column .qcc-chart-section,
.qcc-layout-grid .qcc-chart-section {
    grid-column: 1 / -1;
}

/* Actions Section */
.qcc-actions-section {
    margin-top: 30px;
    text-align: center;
}

/* Footer */
.qcc-container-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
    text-align: center;
    font-size: 14px;
    color: #666;
}

/* Loading Overlay */
.qcc-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.9);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: inherit;
}

.qcc-loading-spinner {
    text-align: center;
}

.qcc-spinner {
    width: 40px;
    height: 40px;
    margin-bottom: 15px;
    animation: qcc-spin 1s linear infinite;
}

.qcc-spinner-path {
    stroke: #449775;
    stroke-dasharray: 90, 150;
    stroke-dashoffset: 0;
    stroke-linecap: round;
    animation: qcc-dash 1.5s ease-in-out infinite;
}

@keyframes qcc-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes qcc-dash {
    0% {
        stroke-dasharray: 1, 150;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -35;
    }
    100% {
        stroke-dasharray: 90, 150;
        stroke-dashoffset: -124;
    }
}

.qcc-loading-text {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

/* Theme Variants */
.qcc-theme-default {
    /* Default theme already applied above */
}

.qcc-theme-dark {
    background-color: #2d3748;
    color: #e2e8f0;
}

.qcc-theme-dark .qcc-input-section,
.qcc-theme-dark .qcc-results-section,
.qcc-theme-dark .qcc-additional-section,
.qcc-theme-dark .qcc-chart-section {
    background-color: #4a5568;
    border-color: #718096;
}

.qcc-theme-dark .qcc-controls-section {
    background-color: #4a5568;
    border-color: #718096;
}

.qcc-theme-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.qcc-theme-modern .qcc-input-section,
.qcc-theme-modern .qcc-results-section,
.qcc-theme-modern .qcc-additional-section,
.qcc-theme-modern .qcc-chart-section {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-width-max-width,
    .qcc-width-contained,
    .qcc-width-narrow,
    .qcc-width-full-width {
        padding: 15px;
        margin: 10px auto;
    }
    
    .qcc-layout-two-column .qcc-content-grid,
    .qcc-layout-grid .qcc-content-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .qcc-section-wrapper {
        padding: 20px;
    }
    
    .qcc-controls-section {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .qcc-container {
        border-radius: 0;
        margin: 0;
    }
    
    .qcc-width-max-width,
    .qcc-width-contained,
    .qcc-width-narrow,
    .qcc-width-full-width {
        padding: 10px;
    }
    
    .qcc-section-wrapper {
        padding: 15px;
    }
}

/* Print Styles */
@media print {
    .qcc-container {
        box-shadow: none;
        border: 1px solid #ccc;
        margin: 0;
        padding: 20px;
    }
    
    .qcc-loading-overlay {
        display: none !important;
    }
    
    .qcc-controls-section {
        display: none;
    }
    
    .qcc-actions-section {
        display: none;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-container {
        border: 2px solid #000;
    }
    
    .qcc-input-section,
    .qcc-results-section,
    .qcc-additional-section,
    .qcc-chart-section {
        border: 1px solid #000;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-spinner {
        animation: none;
    }
    
    .qcc-spinner-path {
        animation: none;
        stroke-dasharray: none;
    }
    
    * {
        transition: none !important;
        animation: none !important;
    }
}

/* Focus Management */
.qcc-container:focus-within {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

/* Screen Reader Support */
.qcc-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>

<?php
// Helper method für Attribut-Rendering (falls nicht in der Klasse vorhanden)
if (!method_exists($this, 'render_attributes')) {
    function render_attributes($attributes) {
        $output = array();
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $output[] = esc_attr($key);
                }
            } else {
                $output[] = esc_attr($key) . '="' . esc_attr($value) . '"';
            }
        }
        return implode(' ', $output);
    }
}
?>
<?php
/**
 * QCC Form Section Template
 * 
 * Input-Formulare für Calculator-Eingaben
 * Organisiert in COGQ, COPQ und Opportunity Cost Bereiche
 * 
 * Template-Pfad: templates/calculator/sections/form-section.php
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
$title = $data['title'] ?? $translator->get('input_parameters');
$description = $data['description'] ?? $translator->get('enter_company_data');
$layout = $data['layout'] ?? 'vertical'; // vertical, horizontal, tabs
$validation_mode = $data['validation_mode'] ?? 'live'; // live, submit, manual

// Form-Konfiguration
$currency = $data['currency'] ?? 'EUR';
$unit = $data['unit'] ?? 'billions';
$unit_multiplier = ($unit === 'millions') ? 1000000 : 1000000000;
$currency_symbol = $helpers['get_currency_symbol']($currency);

// Standard-Werte
$default_values = array_merge(array(
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
), $data['default_values'] ?? array());

// Form-Sections definieren
$form_sections = $data['sections'] ?? array(
    'basic' => array(
        'title' => $translator->get('basic_parameters'),
        'description' => $translator->get('revenue_and_quality_cost'),
        'fields' => array('revenue', 'quality_percentage'),
        'icon' => 'calculator',
        'collapsible' => false
    ),
    'cogq' => array(
        'title' => $translator->get('cost_of_good_quality'),
        'description' => $translator->get('prevention_and_appraisal_costs'),
        'fields' => array('prevention', 'appraisal'),
        'icon' => 'shield-check',
        'collapsible' => true,
        'color' => 'green'
    ),
    'copq' => array(
        'title' => $translator->get('cost_of_poor_quality'),
        'description' => $translator->get('internal_and_external_defects'),
        'fields' => array('internal_defect', 'external_defect'),
        'icon' => 'alert-triangle',
        'collapsible' => true,
        'color' => 'red'
    ),
    'opportunity' => array(
        'title' => $translator->get('opportunity_costs'),
        'description' => $translator->get('additional_business_impact'),
        'fields' => array('lost_sales', 'customer_churn', 'market_share_loss', 'productivity_loss'),
        'icon' => 'trending-up',
        'collapsible' => true,
        'color' => 'blue',
        'optional' => true
    )
);

// Field-Definitionen
$field_definitions = array(
    'revenue' => array(
        'type' => 'currency',
        'label' => $translator->get('revenue'),
        'placeholder' => '140',
        'help' => $translator->get('total_company_revenue'),
        'required' => true,
        'min' => 0,
        'step' => 0.01,
        'unit_suffix' => $translator->get($unit)
    ),
    'quality_percentage' => array(
        'type' => 'percentage',
        'label' => $translator->get('quality_cost_percentage'),
        'placeholder' => '6',
        'help' => $translator->get('percentage_of_revenue_for_quality'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'prevention' => array(
        'type' => 'percentage',
        'label' => $translator->get('prevention_costs'),
        'placeholder' => '10',
        'help' => $translator->get('prevention_costs_description'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'appraisal' => array(
        'type' => 'percentage',
        'label' => $translator->get('appraisal_costs'),
        'placeholder' => '20',
        'help' => $translator->get('appraisal_costs_description'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'internal_defect' => array(
        'type' => 'percentage',
        'label' => $translator->get('internal_defect_costs'),
        'placeholder' => '30',
        'help' => $translator->get('internal_defect_costs_description'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'external_defect' => array(
        'type' => 'percentage',
        'label' => $translator->get('external_defect_costs'),
        'placeholder' => '40',
        'help' => $translator->get('external_defect_costs_description'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'lost_sales' => array(
        'type' => 'percentage',
        'label' => $translator->get('lost_sales'),
        'placeholder' => '5',
        'help' => $translator->get('lost_sales_description'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'customer_churn' => array(
        'type' => 'percentage',
        'label' => $translator->get('customer_churn'),
        'placeholder' => '2',
        'help' => $translator->get('customer_churn_description'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'market_share_loss' => array(
        'type' => 'percentage',
        'label' => $translator->get('market_share_loss'),
        'placeholder' => '1',
        'help' => $translator->get('market_share_loss_description'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'productivity_loss' => array(
        'type' => 'percentage',
        'label' => $translator->get('productivity_loss'),
        'placeholder' => '3',
        'help' => $translator->get('productivity_loss_description'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    )
);

// CSS-Klassen für Form-Section
$form_classes = $helpers['build_css_classes'](
    array('qcc-form-section'),
    array(
        'qcc-form-section--' . $layout => true,
        'qcc-form-section--live-validation' => $validation_mode === 'live',
        'qcc-form-section--' . ($data['style'] ?? 'default') => true
    )
);
?>

<!-- QCC Form Section Start -->
<section id="<?php echo esc_attr($section_id); ?>" 
         class="<?php echo esc_attr($form_classes); ?>"
         role="region"
         aria-label="<?php echo esc_attr($translator->get('calculator_inputs')); ?>">

    <!-- Section Header -->
    <div class="qcc-form-header">
        <h2 class="qcc-form-title">
            <?php echo $helpers['escape']($title); ?>
        </h2>
        
        <?php if (!empty($description)): ?>
        <p class="qcc-form-description">
            <?php echo $helpers['escape']($description); ?>
        </p>
        <?php endif; ?>

        <!-- Validation Summary -->
        <div id="qcc-validation-summary" 
             class="qcc-validation-summary" 
             style="display: none;"
             role="alert" 
             aria-live="polite">
        </div>
    </div>

    <!-- Form Container -->
    <form id="qcc-calculator-form" 
          class="qcc-calculator-form"
          onsubmit="return QCC.handleFormSubmit(event)"
          novalidate>

        <?php if ($layout === 'tabs'): ?>
        <!-- Tab Navigation -->
        <div class="qcc-form-tabs" role="tablist">
            <?php foreach ($form_sections as $section_key => $section_config): ?>
            <button type="button" 
                    class="qcc-tab-button <?php echo $section_key === array_key_first($form_sections) ? 'qcc-tab-button--active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $section_key === array_key_first($form_sections) ? 'true' : 'false'; ?>"
                    aria-controls="qcc-tab-panel-<?php echo esc_attr($section_key); ?>"
                    onclick="QCC.switchTab('<?php echo esc_attr($section_key); ?>')">
                
                <?php if (!empty($section_config['icon'])): ?>
                <span class="qcc-tab-icon" aria-hidden="true">
                    <?php echo $helpers['escape']($section_config['icon']); ?>
                </span>
                <?php endif; ?>
                
                <span class="qcc-tab-text">
                    <?php echo $helpers['escape']($section_config['title']); ?>
                </span>
                
                <?php if (!empty($section_config['optional'])): ?>
                <span class="qcc-tab-optional">(<?php echo $helpers['escape']($translator->get('optional')); ?>)</span>
                <?php endif; ?>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form Sections -->
        <div class="qcc-form-content">
            <?php foreach ($form_sections as $section_key => $section_config): ?>
            
            <div id="qcc-<?php echo esc_attr($layout === 'tabs' ? 'tab-panel-' : 'section-'); ?><?php echo esc_attr($section_key); ?>" 
                 class="qcc-form-group qcc-form-group--<?php echo esc_attr($section_key); ?> <?php echo $layout === 'tabs' && $section_key !== array_key_first($form_sections) ? 'qcc-form-group--hidden' : ''; ?>"
                 <?php if ($layout === 'tabs'): ?>
                 role="tabpanel"
                 aria-labelledby="qcc-tab-<?php echo esc_attr($section_key); ?>"
                 <?php endif; ?>
                 data-color="<?php echo esc_attr($section_config['color'] ?? 'default'); ?>">

                <?php if ($layout !== 'tabs'): ?>
                <!-- Section Header (non-tabs) -->
                <div class="qcc-group-header <?php echo !empty($section_config['collapsible']) ? 'qcc-group-header--collapsible' : ''; ?>"
                     <?php if (!empty($section_config['collapsible'])): ?>
                     onclick="QCC.toggleSection('<?php echo esc_attr($section_key); ?>')"
                     <?php endif; ?>>
                    
                    <div class="qcc-group-title-row">
                        <?php if (!empty($section_config['icon'])): ?>
                        <span class="qcc-group-icon qcc-group-icon--<?php echo esc_attr($section_config['color'] ?? 'default'); ?>" aria-hidden="true">
                            <?php echo $helpers['escape']($section_config['icon']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <h3 class="qcc-group-title">
                            <?php echo $helpers['escape']($section_config['title']); ?>
                            
                            <?php if (!empty($section_config['optional'])): ?>
                            <span class="qcc-group-optional">(<?php echo $helpers['escape']($translator->get('optional')); ?>)</span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if (!empty($section_config['collapsible'])): ?>
                        <span class="qcc-group-toggle" aria-hidden="true">▼</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($section_config['description'])): ?>
                    <p class="qcc-group-description">
                        <?php echo $helpers['escape']($section_config['description']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Fields Container -->
                <div class="qcc-group-fields" id="qcc-fields-<?php echo esc_attr($section_key); ?>">
                    
                    <?php foreach ($section_config['fields'] as $field_name): ?>
                        <?php 
                        $field_config = $field_definitions[$field_name] ?? array();
                        $field_value = $data['values'][$field_name] ?? $default_values[$field_name] ?? '';
                        
                        // Field-spezifische Daten aufbereiten
                        $field_data = array_merge($field_config, array(
                            'name' => $field_name,
                            'id' => 'qcc-' . $field_name,
                            'value' => $field_value,
                            'currency_symbol' => $currency_symbol,
                            'validation_group' => $field_config['group'] ?? null,
                            'data_attributes' => array(
                                'data-field' => $field_name,
                                'data-type' => $field_config['type'],
                                'data-validation' => $validation_mode
                            )
                        ));
                        ?>
                        
                        <div class="qcc-field-wrapper qcc-field-wrapper--<?php echo esc_attr($field_config['type']); ?>">
                            <?php
                            echo $template_manager->render('components/input-field', $field_data);
                            ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($section_key === 'cogq' || $section_key === 'copq'): ?>
                    <!-- Percentage Total Display -->
                    <div class="qcc-percentage-total" 
                         id="qcc-percentage-total-<?php echo esc_attr($section_key); ?>"
                         role="status"
                         aria-live="polite">
                        <div class="qcc-percentage-total-content">
                            <span class="qcc-percentage-total-label">
                                <?php echo $helpers['escape']($translator->get('total')); ?>:
                            </span>
                            <span class="qcc-percentage-total-value" id="qcc-total-value-<?php echo esc_attr($section_key); ?>">
                                0%
                            </span>
                        </div>
                        
                        <div class="qcc-percentage-validation" 
                             id="qcc-validation-<?php echo esc_attr($section_key); ?>"
                             style="display: none;">
                            <span class="qcc-validation-icon">⚠️</span>
                            <span class="qcc-validation-message"></span>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div>

            <?php endforeach; ?>
        </div>

        <!-- Form Actions -->
        <div class="qcc-form-actions">
            
            <div class="qcc-form-actions-left">
                <?php if ($data['show_reset'] ?? true): ?>
                <button type="button" 
                        class="qcc-button qcc-button--secondary"
                        onclick="QCC.resetForm()"
                        aria-label="<?php echo esc_attr($translator->get('reset_form')); ?>">
                    <span class="qcc-button-icon">↺</span>
                    <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('reset')); ?></span>
                </button>
                <?php endif; ?>
                
                <?php if ($data['show_save'] ?? false): ?>
                <button type="button" 
                        class="qcc-button qcc-button--outline"
                        onclick="QCC.saveForm()"
                        aria-label="<?php echo esc_attr($translator->get('save_form')); ?>">
                    <span class="qcc-button-icon">💾</span>
                    <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('save')); ?></span>
                </button>
                <?php endif; ?>
            </div>

            <div class="qcc-form-actions-right">
                <?php if ($data['auto_calculate'] ?? true): ?>
                <div class="qcc-auto-calculate-info">
                    <span class="qcc-auto-calculate-icon">⚡</span>
                    <span class="qcc-auto-calculate-text">
                        <?php echo $helpers['escape']($translator->get('auto_calculation_enabled')); ?>
                    </span>
                </div>
                <?php else: ?>
                <button type="submit" 
                        class="qcc-button qcc-button--primary qcc-calculate-button"
                        id="qcc-calculate-button">
                    <span class="qcc-button-icon">🧮</span>
                    <span class="qcc-button-text"><?php echo $helpers['escape']($translator->get('calculate')); ?></span>
                    <span class="qcc-button-loading" style="display: none;">
                        <span class="qcc-spinner"></span>
                    </span>
                </button>
                <?php endif; ?>
            </div>

        </div>

    </form>

</section>
<!-- QCC Form Section End -->

<!-- Form Section Styles -->
<style>
.qcc-form-section {
    background: var(--qcc-form-bg, #ffffff);
    border-radius: var(--qcc-form-radius, 12px);
    box-shadow: var(--qcc-form-shadow, 0 2px 12px rgba(0,0,0,0.08));
    overflow: hidden;
}

.qcc-form-header {
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid var(--qcc-color-border, #e5e7eb);
    background: var(--qcc-form-header-bg, #f9fafb);
}

.qcc-form-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: var(--qcc-color-heading, #1f2937);
}

.qcc-form-description {
    margin: 0;
    color: var(--qcc-color-text-muted, #6b7280);
    line-height: 1.6;
}

.qcc-validation-summary {
    background: var(--qcc-color-error-bg, #fef2f2);
    border: 1px solid var(--qcc-color-error-border, #fecaca);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    color: var(--qcc-color-error, #dc2626);
}

.qcc-calculator-form {
    padding: 0;
}

/* Tab Layout */
.qcc-form-tabs {
    display: flex;
    background: var(--qcc-tab-bg, #f3f4f6);
    border-bottom: 1px solid var(--qcc-color-border, #e5e7eb);
    overflow-x: auto;
}

.qcc-tab-button {
    flex: 1;
    min-width: 200px;
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 3px solid transparent;
}

.qcc-tab-button:hover {
    background: var(--qcc-tab-hover-bg, #e5e7eb);
}

.qcc-tab-button--active {
    background: var(--qcc-tab-active-bg, #ffffff);
    border-bottom-color: var(--qcc-color-primary, #3b82f6);
    color: var(--qcc-color-primary, #3b82f6);
}

.qcc-tab-text {
    font-weight: 500;
}

.qcc-tab-optional {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Form Content */
.qcc-form-content {
    padding: 2rem;
}

.qcc-form-group {
    margin-bottom: 2rem;
}

.qcc-form-group--hidden {
    display: none;
}

.qcc-form-group[data-color="green"] {
    border-left: 4px solid var(--qcc-color-success, #10b981);
}

.qcc-form-group[data-color="red"] {
    border-left: 4px solid var(--qcc-color-error, #ef4444);
}

.qcc-form-group[data-color="blue"] {
    border-left: 4px solid var(--qcc-color-primary, #3b82f6);
}

.qcc-group-header {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--qcc-group-header-bg, #f9fafb);
    border-radius: 8px;
}

.qcc-group-header--collapsible {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.qcc-group-header--collapsible:hover {
    background: var(--qcc-group-header-hover-bg, #f3f4f6);
}

.qcc-group-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.qcc-group-icon {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 1rem;
}

.qcc-group-icon--green {
    background: var(--qcc-color-success-light, #dcfce7);
    color: var(--qcc-color-success, #10b981);
}

.qcc-group-icon--red {
    background: var(--qcc-color-error-light, #fee2e2);
    color: var(--qcc-color-error, #ef4444);
}

.qcc-group-icon--blue {
    background: var(--qcc-color-primary-light, #dbeafe);
    color: var(--qcc-color-primary, #3b82f6);
}

.qcc-group-title {
    flex: 1;
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--qcc-color-heading, #1f2937);
}

.qcc-group-optional {
    font-size: 0.875rem;
    font-weight: 400;
    opacity: 0.7;
}

.qcc-group-toggle {
    font-size: 0.875rem;
    opacity: 0.6;
    transition: transform 0.2s ease;
}

.qcc-group-header--collapsed .qcc-group-toggle {
    transform: rotate(-90deg);
}

.qcc-group-description {
    margin: 0.5rem 0 0;
    font-size: 0.875rem;
    color: var(--qcc-color-text-muted, #6b7280);
    line-height: 1.5;
}

.qcc-group-fields {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.qcc-field-wrapper {
    grid-column: span 1;
}

.qcc-field-wrapper--currency[data-field="revenue"] {
    grid-column: span 2;
}

.qcc-percentage-total {
    grid-column: 1 / -1;
    background: var(--qcc-percentage-total-bg, #f8fafc);
    border: 2px solid var(--qcc-color-border, #e5e7eb);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.qcc-percentage-total-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.125rem;
    font-weight: 600;
}

.qcc-percentage-total-value {
    color: var(--qcc-color-primary, #3b82f6);
}

.qcc-percentage-validation {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--qcc-color-warning, #f59e0b);
    font-size: 0.875rem;
}

/* Form Actions */
.qcc-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--qcc-color-border, #e5e7eb);
    background: var(--qcc-form-footer-bg, #f9fafb);
}

.qcc-form-actions-left,
.qcc-form-actions-right {
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
}

.qcc-button--primary {
    background: var(--qcc-color-primary, #3b82f6);
    color: white;
}

.qcc-button--primary:hover {
    background: var(--qcc-color-primary-dark, #2563eb);
}

.qcc-button--secondary {
    background: var(--qcc-color-secondary, #6b7280);
    color: white;
}

.qcc-button--outline {
    background: transparent;
    border-color: var(--qcc-color-border, #e5e7eb);
    color: var(--qcc-color-text, #374151);
}

.qcc-button--outline:hover {
    background: var(--qcc-color-bg-light, #f9fafb);
}

.qcc-auto-calculate-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--qcc-color-success, #10b981);
}

.qcc-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-form-header,
    .qcc-form-content,
    .qcc-form-actions {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .qcc-group-fields {
        grid-template-columns: 1fr;
    }
    
    .qcc-field-wrapper--currency[data-field="revenue"] {
        grid-column: span 1;
    }
    
    .qcc-form-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .qcc-form-actions-left,
    .qcc-form-actions-right {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .qcc-tab-button {
        min-width: 150px;
        padding: 0.75rem 1rem;
    }
    
    .qcc-tab-text {
        font-size: 0.875rem;
    }
}
</style>

<?php if ($debug['debug_mode'] ?? false): ?>
<!-- Debug Information -->
<div class="qcc-debug-info" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
    <details>
        <summary>🐛 QCC Form Section Debug Info</summary>
        <pre><?php echo $helpers['escape'](print_r(array(
            'template' => 'sections/form-section',
            'section_id' => $section_id,
            'layout' => $layout,
            'validation_mode' => $validation_mode,
            'currency' => $currency,
            'unit' => $unit,
            'sections_count' => count($form_sections),
            'fields_count' => count($field_definitions),
            'default_values' => $default_values,
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
 * - $data['layout'] - Layout-Typ (vertical, horizontal, tabs)
 * - $data['validation_mode'] - Validierung (live, submit, manual)
 * - $data['currency'] - Aktuelle Währung
 * - $data['unit'] - Aktuelle Einheit (millions, billions)
 * - $data['default_values'] - Standard-Werte für Felder
 * - $data['values'] - Aktuelle Werte für Felder
 * - $data['sections'] - Custom Section-Konfiguration
 * - $data['auto_calculate'] - Auto-Berechnung aktiviert
 * - $data['show_reset'] - Reset-Button anzeigen
 * - $data['show_save'] - Save-Button anzeigen
 * - $data['style'] - Form-Style (default, modern, compact)
 * 
 * Form-Sections:
 * - basic - Revenue und Quality Percentage
 * - cogq - Prevention und Appraisal Costs
 * - copq - Internal und External Defect Costs
 * - opportunity - Lost Sales, Customer Churn, etc.
 * 
 * Field-Types:
 * - currency - Währungs-Eingabe mit Symbol
 * - percentage - Prozent-Eingabe mit Validation
 * 
 * CSS-Klassen:
 * - .qcc-form-section - Basis-Form-Section
 * - .qcc-form-group - Section-Gruppe
 * - .qcc-group-header - Gruppen-Header
 * - .qcc-group-fields - Felder-Container
 * - .qcc-percentage-total - Prozent-Summen-Anzeige
 * - .qcc-form-actions - Aktions-Buttons
 * 
 * JavaScript-Integration:
 * - QCC.handleFormSubmit(event) - Form-Submit-Handler
 * - QCC.switchTab(sectionKey) - Tab-Wechsel
 * - QCC.toggleSection(sectionKey) - Section ein-/ausklappen
 * - QCC.resetForm() - Formular zurücksetzen
 * - QCC.saveForm() - Formular speichern
 * 
 * Validation:
 * - Live-Validation für Prozent-Summen
 * - Required-Field-Validation
 * - Range-Validation für Min/Max-Werte
 * - Custom Validation-Rules
 * 
 * Accessibility:
 * - role="region" für Section
 * - role="tablist/tab/tabpanel" für Tabs
 * - aria-live für dynamische Inhalte
 * - Keyboard-Navigation-Support
 * - Screen-Reader-Labels
 * 
 * Performance:
 * - Template-Caching automatisch
 * - Conditional Rendering für optionale Bereiche
 * - Optimized DOM-Struktur
 * - CSS Grid für responsive Layout
 */
?>
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

// =============================================================================
// DEBUG CODE - TRANSLATION SERVICE DIAGNOSE
// =============================================================================

if (WP_DEBUG || (defined('QCC_DEBUG') && QCC_DEBUG)) {
    echo '<div style="background: #f0f0f0; border: 2px solid #dc3545; padding: 15px; margin: 15px 0; font-family: monospace; font-size: 12px; border-radius: 8px;">';
    echo '<h4 style="color: #dc3545; margin: 0 0 10px 0;">🔍 QCC Translation Debug:</h4>';
    
    // 1. Prüfe ob $translator Variable verfügbar ist
    echo '<strong>1. Translator Variable:</strong><br>';
    if (isset($translator)) {
        echo '✅ $translator ist verfügbar: ' . get_class($translator) . '<br>';
        echo '   Current Language: ' . $translator->get_current_language() . '<br>';
        
        // Test einige Translation Keys
        $test_keys = array('cogq_title', 'copq_title', 'prevention_costs', 'appraisal_costs', 'basic_parameters');
        foreach ($test_keys as $key) {
            $value = $translator->get($key);
            echo '   Key "' . $key . '": "' . $value . '"<br>';
        }
    } else {
        echo '❌ $translator ist NICHT verfügbar<br>';
    }
    
    // 2. Prüfe Service Container
    echo '<br><strong>2. Service Container:</strong><br>';
    if (class_exists('QCC_Service_Container_Setup')) {
        echo '✅ Service Container Setup verfügbar<br>';
        try {
            $container = QCC_Service_Container_Setup::get_container();
            if ($container) {
                echo '✅ Container ist initialisiert<br>';
                
                // Prüfe ob Translator Service verfügbar ist
                if (method_exists($container, 'get')) {
                    try {
                        $service_translator = $container->get('translator');
                        echo '✅ Translator Service verfügbar: ' . get_class($service_translator) . '<br>';
                        echo '   Service Language: ' . $service_translator->get_current_language() . '<br>';
                    } catch (Exception $e) {
                        echo '❌ Translator Service fehlt: ' . $e->getMessage() . '<br>';
                    }
                } else {
                    echo '❌ Container->get() Methode nicht verfügbar<br>';
                }
            } else {
                echo '❌ Container ist NULL<br>';
            }
        } catch (Exception $e) {
            echo '❌ Container Error: ' . $e->getMessage() . '<br>';
        }
    } else {
        echo '❌ Service Container Setup nicht verfügbar<br>';
    }
    
    // 3. Prüfe Translation Classes
    echo '<br><strong>3. Translation Classes:</strong><br>';
    $translation_classes = array(
        'QCC_Translator',
        'QCC_German_Translations', 
        'QCC_English_Translations'
    );
    
    foreach ($translation_classes as $class) {
        if (class_exists($class)) {
            echo '✅ ' . $class . ' ist verfügbar<br>';
        } else {
            echo '❌ ' . $class . ' ist NICHT verfügbar<br>';
        }
    }
    
    // 4. Fallback Test - Direct Translation
    echo '<br><strong>4. Direct Translation Test:</strong><br>';
    if (class_exists('QCC_German_Translations')) {
        $german = new QCC_German_Translations();
        $translations = $german->get_translations();
        echo '✅ German Translations geladen: ' . count($translations) . ' Keys<br>';
        
        // Test cogq_title Key
        if (isset($translations['cogq_title'])) {
            echo '✅ cogq_title gefunden: "' . $translations['cogq_title'] . '"<br>';
        } else {
            echo '❌ cogq_title NICHT gefunden in German Translations<br>';
            // Suche ähnliche Keys
            $similar = array_filter(array_keys($translations), function($k) {
                return strpos($k, 'cogq') !== false || strpos($k, 'prevention') !== false;
            });
            echo '   Ähnliche Keys: ' . implode(', ', array_slice($similar, 0, 5)) . '<br>';
        }
    }
    
    // 5. Template Variables Check
    echo '<br><strong>5. Template Variables:</strong><br>';
    $template_vars = array('data', 'attributes', 'helpers', 'translator', 'template_manager', 'debug');
    foreach ($template_vars as $var) {
        if (isset($$var)) {
            echo '✅ $' . $var . ' ist verfügbar (' . gettype($$var) . ')<br>';
        } else {
            echo '❌ $' . $var . ' ist NICHT verfügbar<br>';
        }
    }
    
    echo '</div>';
}

// =============================================================================
// QUICK FIX FUNCTION - SOFORT VERWENDEN
// =============================================================================

if (!function_exists('qcc_debug_translate')) {
    function qcc_debug_translate($key, $language = 'de') {
        // 1. Versuche Service Container
        if (class_exists('QCC_Service_Container_Setup')) {
            try {
                $container = QCC_Service_Container_Setup::get_container();
                if ($container && method_exists($container, 'get')) {
                    $translator = $container->get('translator');
                    return $translator->get($key);
                }
            } catch (Exception $e) {
                // Ignoriere und gehe zu Fallback
            }
        }
        
        // 2. Versuche direkte Translation Class
        if ($language === 'de' && class_exists('QCC_German_Translations')) {
            $german = new QCC_German_Translations();
            $translations = $german->get_translations();
            if (isset($translations[$key])) {
                return $translations[$key];
            }
        }
        
        if (class_exists('QCC_English_Translations')) {
            $english = new QCC_English_Translations();
            $translations = $english->get_translations();
            if (isset($translations[$key])) {
                return $translations[$key];
            }
        }
        
        // 3. Hard-coded Fallback für wichtigste Keys
        $fallbacks = array(
            'de' => array(
                'input_parameters' => 'Eingabeparameter',
                'enter_company_data' => 'Geben Sie Ihre Unternehmensdaten ein',
                'basic_parameters' => 'Grundparameter',
                'revenue_and_quality_cost' => 'Umsatz und Qualitätskosten',
                'cost_of_good_quality' => 'Kosten guter Qualität',
                'prevention_and_appraisal_costs' => 'Präventions- und Prüfkosten',
                'cost_of_poor_quality' => 'Kosten schlechter Qualität',
                'internal_and_external_defects' => 'Interne und externe Fehler',
                'opportunity_costs' => 'Opportunitätskosten',
                'additional_business_impact' => 'Zusätzliche Geschäftsauswirkungen',
                'cogq_title' => 'Kosten guter Qualität (CoGQ)',
                'copq_title' => 'Kosten schlechter Qualität (CoPQ)',
                'prevention_costs' => 'Präventionskosten',
                'appraisal_costs' => 'Prüfkosten',
                'internal_defect_costs' => 'Interne Fehlerkosten',
                'external_defect_costs' => 'Externe Fehlerkosten',
                'revenue' => 'Umsatz',
                'quality_cost_percentage' => 'Qualitätskostenprozentsatz',
                'total_company_revenue' => 'Gesamtumsatz des Unternehmens',
                'percentage_of_revenue_for_quality' => 'Prozentsatz des Umsatzes für Qualität',
                'prevention_costs_description' => 'Kosten zur Fehlervermeidung',
                'appraisal_costs_description' => 'Kosten zur Qualitätsprüfung',
                'internal_defect_costs_description' => 'Interne Fehlerkosten',
                'external_defect_costs_description' => 'Externe Fehlerkosten',
                'lost_sales' => 'Verlorene Verkäufe',
                'customer_churn' => 'Kundenabwanderung',
                'market_share_loss' => 'Marktanteilsverlust',
                'productivity_loss' => 'Produktivitätsverlust',
                'calculator_inputs' => 'Rechner-Eingaben',
                'optional' => 'Optional',
                'total' => 'Gesamt',
                'reset_form' => 'Formular zurücksetzen',
                'save_form' => 'Formular speichern',
                'reset' => 'Zurücksetzen',
                'save' => 'Speichern',
                'auto_calculation_enabled' => 'Automatische Berechnung aktiviert',
                'calculate' => 'Berechnen',
                'billions' => 'Milliarden',
                'millions' => 'Millionen'
            ),
            'en' => array(
                'input_parameters' => 'Input Parameters',
                'enter_company_data' => 'Enter your company data',
                'basic_parameters' => 'Basic Parameters',
                'revenue_and_quality_cost' => 'Revenue and Quality Cost',
                'cost_of_good_quality' => 'Cost of Good Quality',
                'prevention_and_appraisal_costs' => 'Prevention and Appraisal Costs',
                'cost_of_poor_quality' => 'Cost of Poor Quality',
                'internal_and_external_defects' => 'Internal and External Defects',
                'opportunity_costs' => 'Opportunity Costs',
                'additional_business_impact' => 'Additional Business Impact',
                'cogq_title' => 'Cost of Good Quality (CoGQ)',
                'copq_title' => 'Cost of Poor Quality (CoPQ)',
                'prevention_costs' => 'Prevention Costs',
                'appraisal_costs' => 'Appraisal Costs',
                'internal_defect_costs' => 'Internal Defect Costs',
                'external_defect_costs' => 'External Defect Costs',
                'revenue' => 'Revenue',
                'quality_cost_percentage' => 'Quality Cost Percentage',
                'total_company_revenue' => 'Total Company Revenue',
                'percentage_of_revenue_for_quality' => 'Percentage of Revenue for Quality',
                'prevention_costs_description' => 'Costs to prevent defects',
                'appraisal_costs_description' => 'Costs to evaluate quality',
                'internal_defect_costs_description' => 'Internal defect costs',
                'external_defect_costs_description' => 'External defect costs',
                'lost_sales' => 'Lost Sales',
                'customer_churn' => 'Customer Churn',
                'market_share_loss' => 'Market Share Loss',
                'productivity_loss' => 'Productivity Loss',
                'calculator_inputs' => 'Calculator Inputs',
                'optional' => 'Optional',
                'total' => 'Total',
                'reset_form' => 'Reset Form',
                'save_form' => 'Save Form',
                'reset' => 'Reset',
                'save' => 'Save',
                'auto_calculation_enabled' => 'Auto calculation enabled',
                'calculate' => 'Calculate',
                'billions' => 'Billions',
                'millions' => 'Millions'
            )
        );
        
        if (isset($fallbacks[$language][$key])) {
            return $fallbacks[$language][$key];
        }
        
        if (isset($fallbacks['en'][$key])) {
            return $fallbacks['en'][$key];
        }
        
        // 4. Last Resort: Format key nicely
        return ucwords(str_replace('_', ' ', $key));
    }
}

// =============================================================================
// TEMPLATE MAIN CODE - MIT QUICK FIX
// =============================================================================

// Section-Daten vorbereiten (MIT QUICK FIX)
$section_id = $data['id'] ?? ($helpers['generate_section_id']($data) ?? 'qcc-form-section');
$title = $data['title'] ?? qcc_debug_translate('input_parameters', 'de');
$description = $data['description'] ?? qcc_debug_translate('enter_company_data', 'de');
$layout = $data['layout'] ?? 'vertical'; // vertical, horizontal, tabs
$validation_mode = $data['validation_mode'] ?? 'live'; // live, submit, manual

// Form-Konfiguration
$currency = $data['currency'] ?? 'EUR';
$unit = $data['unit'] ?? 'billions';
$unit_multiplier = ($unit === 'millions') ? 1000000 : 1000000000;

// Helper für Currency Symbol
if (isset($helpers['get_currency_symbol'])) {
    $currency_symbol = $helpers['get_currency_symbol']($currency);
} else {
    $currency_symbols = array('EUR' => '€', 'USD' => '$', 'GBP' => '£', 'JPY' => '¥');
    $currency_symbol = $currency_symbols[$currency] ?? $currency;
}

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

// Form-Sections definieren (MIT QUICK FIX)
$form_sections = $data['sections'] ?? array(
    'basic' => array(
        'title' => qcc_debug_translate('basic_parameters', 'de'),
        'description' => qcc_debug_translate('revenue_and_quality_cost', 'de'),
        'fields' => array('revenue', 'quality_percentage'),
        'icon' => '🧮',
        'collapsible' => false
    ),
    'cogq' => array(
        'title' => qcc_debug_translate('cost_of_good_quality', 'de'),
        'description' => qcc_debug_translate('prevention_and_appraisal_costs', 'de'),
        'fields' => array('prevention', 'appraisal'),
        'icon' => '🛡️',
        'collapsible' => true,
        'color' => 'green'
    ),
    'copq' => array(
        'title' => qcc_debug_translate('cost_of_poor_quality', 'de'),
        'description' => qcc_debug_translate('internal_and_external_defects', 'de'),
        'fields' => array('internal_defect', 'external_defect'),
        'icon' => '⚠️',
        'collapsible' => true,
        'color' => 'red'
    ),
    'opportunity' => array(
        'title' => qcc_debug_translate('opportunity_costs', 'de'),
        'description' => qcc_debug_translate('additional_business_impact', 'de'),
        'fields' => array('lost_sales', 'customer_churn', 'market_share_loss', 'productivity_loss'),
        'icon' => '📈',
        'collapsible' => true,
        'color' => 'blue',
        'optional' => true
    )
);

// Field-Definitionen (MIT QUICK FIX)
$field_definitions = array(
    'revenue' => array(
        'type' => 'currency',
        'label' => qcc_debug_translate('revenue', 'de'),
        'placeholder' => '140',
        'help' => qcc_debug_translate('total_company_revenue', 'de'),
        'required' => true,
        'min' => 0,
        'step' => 0.01,
        'unit_suffix' => qcc_debug_translate($unit, 'de')
    ),
    'quality_percentage' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('quality_cost_percentage', 'de'),
        'placeholder' => '6',
        'help' => qcc_debug_translate('percentage_of_revenue_for_quality', 'de'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'prevention' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('prevention_costs', 'de'),
        'placeholder' => '10',
        'help' => qcc_debug_translate('prevention_costs_description', 'de'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'appraisal' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('appraisal_costs', 'de'),
        'placeholder' => '20',
        'help' => qcc_debug_translate('appraisal_costs_description', 'de'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'internal_defect' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('internal_defect_costs', 'de'),
        'placeholder' => '30',
        'help' => qcc_debug_translate('internal_defect_costs_description', 'de'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'external_defect' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('external_defect_costs', 'de'),
        'placeholder' => '40',
        'help' => qcc_debug_translate('external_defect_costs_description', 'de'),
        'required' => true,
        'min' => 0,
        'max' => 100,
        'step' => 0.1,
        'group' => 'quality_distribution'
    ),
    'lost_sales' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('lost_sales', 'de'),
        'placeholder' => '5',
        'help' => qcc_debug_translate('lost_sales_description', 'de'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'customer_churn' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('customer_churn', 'de'),
        'placeholder' => '2',
        'help' => qcc_debug_translate('customer_churn_description', 'de'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'market_share_loss' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('market_share_loss', 'de'),
        'placeholder' => '1',
        'help' => qcc_debug_translate('market_share_loss_description', 'de'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    ),
    'productivity_loss' => array(
        'type' => 'percentage',
        'label' => qcc_debug_translate('productivity_loss', 'de'),
        'placeholder' => '3',
        'help' => qcc_debug_translate('productivity_loss_description', 'de'),
        'required' => false,
        'min' => 0,
        'max' => 100,
        'step' => 0.1
    )
);

// CSS-Klassen für Form-Section
if (isset($helpers['build_css_classes'])) {
    $form_classes = $helpers['build_css_classes'](
        array('qcc-form-section'),
        array(
            'qcc-form-section--' . $layout => true,
            'qcc-form-section--live-validation' => $validation_mode === 'live',
            'qcc-form-section--' . ($data['style'] ?? 'default') => true
        )
    );
} else {
    $form_classes = 'qcc-form-section qcc-form-section--' . $layout;
}

// Helper Escape Function
if (isset($helpers['escape'])) {
    $escape_func = $helpers['escape'];
} else {
    $escape_func = 'esc_html';
}
?>

<!-- QCC Form Section Start -->
<section id="<?php echo esc_attr($section_id); ?>" 
         class="<?php echo esc_attr($form_classes); ?>"
         role="region"
         aria-label="<?php echo esc_attr(qcc_debug_translate('calculator_inputs', 'de')); ?>">

    <!-- Section Header -->
    <div class="qcc-form-header">
        <h2 class="qcc-form-title">
            <?php echo $escape_func($title); ?>
        </h2>
        
        <?php if (!empty($description)): ?>
        <p class="qcc-form-description">
            <?php echo $escape_func($description); ?>
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
                    <?php echo $escape_func($section_config['icon']); ?>
                </span>
                <?php endif; ?>
                
                <span class="qcc-tab-text">
                    <?php echo $escape_func($section_config['title']); ?>
                </span>
                
                <?php if (!empty($section_config['optional'])): ?>
                <span class="qcc-tab-optional">(<?php echo $escape_func(qcc_debug_translate('optional', 'de')); ?>)</span>
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
                            <?php echo $escape_func($section_config['icon']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <h3 class="qcc-group-title">
                            <?php echo $escape_func($section_config['title']); ?>
                            
                            <?php if (!empty($section_config['optional'])): ?>
                            <span class="qcc-group-optional">(<?php echo $escape_func(qcc_debug_translate('optional', 'de')); ?>)</span>
                            <?php endif; ?>
                        </h3>
                        
                        <?php if (!empty($section_config['collapsible'])): ?>
                        <span class="qcc-group-toggle" aria-hidden="true">▼</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($section_config['description'])): ?>
                    <p class="qcc-group-description">
                        <?php echo $escape_func($section_config['description']); ?>
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
                        ?>
                        
                        <div class="qcc-field-wrapper qcc-field-wrapper--<?php echo esc_attr($field_config['type'] ?? 'text'); ?>">
                            
                            <!-- Basic Input Field -->
                            <div class="qcc-input-field">
                                <label for="qcc-<?php echo esc_attr($field_name); ?>" class="qcc-field-label">
                                    <?php echo $escape_func($field_config['label'] ?? ucwords(str_replace('_', ' ', $field_name))); ?>
                                    <?php if ($field_config['required'] ?? false): ?>
                                    <span class="qcc-required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <div class="qcc-input-container">
                                    <?php if ($field_config['type'] === 'currency'): ?>
                                    <span class="qcc-input-prefix"><?php echo esc_html($currency_symbol); ?></span>
                                    <?php endif; ?>
                                    
                                    <input type="number" 
                                           id="qcc-<?php echo esc_attr($field_name); ?>"
                                           name="<?php echo esc_attr($field_name); ?>"
                                           value="<?php echo esc_attr($field_value); ?>"
                                           placeholder="<?php echo esc_attr($field_config['placeholder'] ?? ''); ?>"
                                           min="<?php echo esc_attr($field_config['min'] ?? ''); ?>"
                                           max="<?php echo esc_attr($field_config['max'] ?? ''); ?>"
                                           step="<?php echo esc_attr($field_config['step'] ?? '1'); ?>"
                                           class="qcc-input qcc-input--<?php echo esc_attr($field_config['type'] ?? 'text'); ?>"
                                           data-field="<?php echo esc_attr($field_name); ?>"
                                           data-type="<?php echo esc_attr($field_config['type'] ?? 'text'); ?>"
                                           data-validation="<?php echo esc_attr($validation_mode); ?>"
                                           <?php if ($field_config['required'] ?? false): ?>required<?php endif; ?>
                                           onchange="QCC.validateField(this)"
                                           oninput="QCC.handleFieldInput(this)">
                                    
                                    <?php if ($field_config['type'] === 'percentage'): ?>
                                    <span class="qcc-input-suffix">%</span>
                                    <?php elseif (isset($field_config['unit_suffix'])): ?>
                                    <span class="qcc-input-suffix"><?php echo esc_html($field_config['unit_suffix']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($field_config['help'])): ?>
                                <div class="qcc-field-help">
                                    <?php echo $escape_func($field_config['help']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="qcc-field-validation" style="display: none;">
                                    <span class="qcc-validation-icon">⚠️</span>
                                    <span class="qcc-validation-message"></span>
                                </div>
                            </div>
                        
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
                                <?php echo $escape_func(qcc_debug_translate('total', 'de')); ?>:
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
                        aria-label="<?php echo esc_attr(qcc_debug_translate('reset_form', 'de')); ?>">
                    <span class="qcc-button-icon">↻</span>
                    <span class="qcc-button-text"><?php echo $escape_func(qcc_debug_translate('reset', 'de')); ?></span>
                </button>
                <?php endif; ?>
                
                <?php if ($data['show_save'] ?? false): ?>
                <button type="button" 
                        class="qcc-button qcc-button--outline"
                        onclick="QCC.saveForm()"
                        aria-label="<?php echo esc_attr(qcc_debug_translate('save_form', 'de')); ?>">
                    <span class="qcc-button-icon">💾</span>
                    <span class="qcc-button-text"><?php echo $escape_func(qcc_debug_translate('save', 'de')); ?></span>
                </button>
                <?php endif; ?>
            </div>

            <div class="qcc-form-actions-right">
                <?php if ($data['auto_calculate'] ?? true): ?>
                <div class="qcc-auto-calculate-info">
                    <span class="qcc-auto-calculate-icon">⚡</span>
                    <span class="qcc-auto-calculate-text">
                        <?php echo $escape_func(qcc_debug_translate('auto_calculation_enabled', 'de')); ?>
                    </span>
                </div>
                <?php else: ?>
                <button type="submit" 
                        class="qcc-button qcc-button--primary qcc-calculate-button"
                        id="qcc-calculate-button">
                    <span class="qcc-button-icon">🧮</span>
                    <span class="qcc-button-text"><?php echo $escape_func(qcc_debug_translate('calculate', 'de')); ?></span>
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
:root {
    --qcc-form-bg: #ffffff;
    --qcc-form-radius: 12px;
    --qcc-form-shadow: 0 2px 12px rgba(0,0,0,0.08);
    --qcc-color-border: #e5e7eb;
    --qcc-form-header-bg: #f9fafb;
    --qcc-color-heading: #1f2937;
    --qcc-color-text-muted: #6b7280;
    --qcc-color-error-bg: #fef2f2;
    --qcc-color-error-border: #fecaca;
    --qcc-color-error: #dc2626;
    --qcc-color-success: #10b981;
    --qcc-color-primary: #3b82f6;
    --qcc-color-primary-dark: #2563eb;
    --qcc-color-secondary: #6b7280;
    --qcc-color-text: #374151;
    --qcc-color-bg-light: #f9fafb;
    --qcc-color-success-light: #dcfce7;
    --qcc-color-error-light: #fee2e2;
    --qcc-color-primary-light: #dbeafe;
    --qcc-color-warning: #f59e0b;
}

.qcc-form-section {
    background: var(--qcc-form-bg);
    border-radius: var(--qcc-form-radius);
    box-shadow: var(--qcc-form-shadow);
    overflow: hidden;
    margin: 20px 0;
}

.qcc-form-header {
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid var(--qcc-color-border);
    background: var(--qcc-form-header-bg);
}

.qcc-form-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.5rem;
    color: var(--qcc-color-heading);
}

.qcc-form-description {
    margin: 0;
    color: var(--qcc-color-text-muted);
    line-height: 1.6;
}

.qcc-validation-summary {
    background: var(--qcc-color-error-bg);
    border: 1px solid var(--qcc-color-error-border);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    color: var(--qcc-color-error);
}

.qcc-calculator-form {
    padding: 0;
}

/* Tab Layout */
.qcc-form-tabs {
    display: flex;
    background: #f3f4f6;
    border-bottom: 1px solid var(--qcc-color-border);
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
    background: #e5e7eb;
}

.qcc-tab-button--active {
    background: #ffffff;
    border-bottom-color: var(--qcc-color-primary);
    color: var(--qcc-color-primary);
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
    border-left: 4px solid var(--qcc-color-success);
}

.qcc-form-group[data-color="red"] {
    border-left: 4px solid var(--qcc-color-error);
}

.qcc-form-group[data-color="blue"] {
    border-left: 4px solid var(--qcc-color-primary);
}

.qcc-group-header {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--qcc-form-header-bg);
    border-radius: 8px;
}

.qcc-group-header--collapsible {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.qcc-group-header--collapsible:hover {
    background: #f3f4f6;
}

.qcc-group-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.qcc-group-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 1.2rem;
}

.qcc-group-icon--green {
    background: var(--qcc-color-success-light);
    color: var(--qcc-color-success);
}

.qcc-group-icon--red {
    background: var(--qcc-color-error-light);
    color: var(--qcc-color-error);
}

.qcc-group-icon--blue {
    background: var(--qcc-color-primary-light);
    color: var(--qcc-color-primary);
}

.qcc-group-title {
    flex: 1;
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--qcc-color-heading);
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
    color: var(--qcc-color-text-muted);
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

/* Input Fields */
.qcc-input-field {
    width: 100%;
}

.qcc-field-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--qcc-color-heading);
    font-size: 0.875rem;
}

.qcc-required {
    color: var(--qcc-color-error);
    margin-left: 0.25rem;
}

.qcc-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.qcc-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--qcc-color-border);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: white;
}

.qcc-input:focus {
    outline: none;
    border-color: var(--qcc-color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.qcc-input:invalid {
    border-color: var(--qcc-color-error);
}

.qcc-input-prefix,
.qcc-input-suffix {
    position: absolute;
    font-weight: 500;
    color: var(--qcc-color-text-muted);
    pointer-events: none;
    z-index: 1;
}

.qcc-input-prefix {
    left: 1rem;
}

.qcc-input-suffix {
    right: 1rem;
}

.qcc-input:has(~ .qcc-input-prefix) {
    padding-left: 2.5rem;
}

.qcc-input:has(~ .qcc-input-suffix) {
    padding-right: 2.5rem;
}

.qcc-field-help {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--qcc-color-text-muted);
    line-height: 1.4;
}

.qcc-field-validation {
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--qcc-color-error);
    font-size: 0.75rem;
}

.qcc-percentage-total {
    grid-column: 1 / -1;
    background: #f8fafc;
    border: 2px solid var(--qcc-color-border);
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
    color: var(--qcc-color-primary);
    font-size: 1.25rem;
}

.qcc-percentage-validation {
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--qcc-color-warning);
    font-size: 0.875rem;
}

/* Form Actions */
.qcc-form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--qcc-color-border);
    background: var(--qcc-form-header-bg);
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
    font-size: 0.875rem;
}

.qcc-button--primary {
    background: var(--qcc-color-primary);
    color: white;
    border-color: var(--qcc-color-primary);
}

.qcc-button--primary:hover {
    background: var(--qcc-color-primary-dark);
    border-color: var(--qcc-color-primary-dark);
}

.qcc-button--secondary {
    background: var(--qcc-color-secondary);
    color: white;
    border-color: var(--qcc-color-secondary);
}

.qcc-button--secondary:hover {
    background: #4b5563;
    border-color: #4b5563;
}

.qcc-button--outline {
    background: transparent;
    border-color: var(--qcc-color-border);
    color: var(--qcc-color-text);
}

.qcc-button--outline:hover {
    background: var(--qcc-color-bg-light);
    border-color: var(--qcc-color-primary);
}

.qcc-auto-calculate-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--qcc-color-success);
    background: var(--qcc-color-success-light);
    padding: 0.5rem 1rem;
    border-radius: 6px;
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
    
    .qcc-field-wrapper--currency {
        grid-column: span 1 !important;
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
    
    .qcc-form-title {
        font-size: 1.25rem;
    }
}
</style>

<!-- Basic JavaScript Functions -->
<script>
if (typeof QCC === 'undefined') {
    window.QCC = {};
}

QCC.handleFormSubmit = function(event) {
    event.preventDefault();
    console.log('QCC: Form submitted');
    return false;
};

QCC.switchTab = function(sectionKey) {
    // Hide all tab panels
    const panels = document.querySelectorAll('[id^="qcc-tab-panel-"]');
    panels.forEach(panel => {
        panel.classList.add('qcc-form-group--hidden');
    });
    
    // Show selected panel
    const targetPanel = document.getElementById('qcc-tab-panel-' + sectionKey);
    if (targetPanel) {
        targetPanel.classList.remove('qcc-form-group--hidden');
    }
    
    // Update tab buttons
    const buttons = document.querySelectorAll('.qcc-tab-button');
    buttons.forEach(button => {
        button.classList.remove('qcc-tab-button--active');
        button.setAttribute('aria-selected', 'false');
    });
    
    const activeButton = document.querySelector('[aria-controls="qcc-tab-panel-' + sectionKey + '"]');
    if (activeButton) {
        activeButton.classList.add('qcc-tab-button--active');
        activeButton.setAttribute('aria-selected', 'true');
    }
    
    console.log('QCC: Switched to tab', sectionKey);
};

QCC.toggleSection = function(sectionKey) {
    const fields = document.getElementById('qcc-fields-' + sectionKey);
    const header = document.querySelector('[onclick*="' + sectionKey + '"]');
    
    if (fields && header) {
        if (fields.style.display === 'none') {
            fields.style.display = 'grid';
            header.classList.remove('qcc-group-header--collapsed');
        } else {
            fields.style.display = 'none';
            header.classList.add('qcc-group-header--collapsed');
        }
    }
    
    console.log('QCC: Toggled section', sectionKey);
};

QCC.resetForm = function() {
    const form = document.getElementById('qcc-calculator-form');
    if (form) {
        form.reset();
        console.log('QCC: Form reset');
    }
};

QCC.saveForm = function() {
    console.log('QCC: Save form function called');
    // Implement save functionality
};

QCC.validateField = function(field) {
    console.log('QCC: Validating field', field.name);
    // Implement field validation
};

QCC.handleFieldInput = function(field) {
    console.log('QCC: Field input', field.name, field.value);
    // Implement live input handling
};

console.log('QCC: Form section JavaScript loaded');
</script>

<?php if (WP_DEBUG || (defined('QCC_DEBUG') && QCC_DEBUG)): ?>
<!-- Debug Information -->
<div class="qcc-debug-info" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 12px;">
    <details>
        <summary>🛠️ QCC Form Section Debug Info</summary>
        <pre><?php echo esc_html(print_r(array(
            'template' => 'sections/form-section',
            'section_id' => $section_id,
            'layout' => $layout,
            'validation_mode' => $validation_mode,
            'currency' => $currency,
            'unit' => $unit,
            'sections_count' => count($form_sections),
            'fields_count' => count($field_definitions),
            'default_values' => $default_values,
            'debug_function_available' => function_exists('qcc_debug_translate'),
            'sample_translations' => array(
                'cogq_title' => qcc_debug_translate('cogq_title', 'de'),
                'copq_title' => qcc_debug_translate('copq_title', 'de'),
                'prevention_costs' => qcc_debug_translate('prevention_costs', 'de')
            ),
            'timestamp' => date('Y-m-d H:i:s')
        ), true)); ?></pre>
    </details>
</div>
<?php endif; ?>
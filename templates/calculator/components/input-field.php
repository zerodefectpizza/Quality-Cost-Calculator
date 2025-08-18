<?php
/**
 * QCC Input Field Component Template
 * 
 * Universelle Input-Komponente für alle Calculator-Eingaben
 * Unterstützt Currency, Percentage, Number und Text-Inputs
 * 
 * Template-Pfad: templates/calculator/components/input-field.php
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
 * @subpackage Templates/Components
 * @since 2.0.0
 */

// Security: Template-Direktzugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Component-Daten vorbereiten
$field_id = $data['id'] ?? $helpers['generate_id']('qcc-input');
$field_name = $data['name'] ?? $field_id;
$field_type = $data['type'] ?? 'text';
$field_label = $data['label'] ?? '';
$field_value = $data['value'] ?? '';
$field_placeholder = $data['placeholder'] ?? '';
$field_help = $data['help'] ?? '';
$field_required = $data['required'] ?? false;
$field_disabled = $data['disabled'] ?? false;
$field_readonly = $data['readonly'] ?? false;

// Validation-Einstellungen
$validation_group = $data['validation_group'] ?? null;
$min_value = $data['min'] ?? null;
$max_value = $data['max'] ?? null;
$step_value = $data['step'] ?? null;

// Type-spezifische Einstellungen
$currency_symbol = $data['currency_symbol'] ?? '€';
$unit_suffix = $data['unit_suffix'] ?? '';
$show_unit_inside = $data['show_unit_inside'] ?? true;

// Error-Handling
$field_error = $data['error'] ?? '';
$has_error = !empty($field_error);

// CSS-Klassen für Input-Field
$field_classes = $helpers['build_css_classes'](
    array('qcc-input-field'),
    array(
        'qcc-input-field--' . $field_type => true,
        'qcc-input-field--required' => $field_required,
        'qcc-input-field--disabled' => $field_disabled,
        'qcc-input-field--readonly' => $field_readonly,
        'qcc-input-field--error' => $has_error,
        'qcc-input-field--with-help' => !empty($field_help),
        'qcc-input-field--validation-group' => !empty($validation_group)
    )
);

// Input-Attribute vorbereiten
$input_attributes = array_merge(
    array(
        'id' => $field_id,
        'name' => $field_name,
        'type' => $field_type === 'currency' || $field_type === 'percentage' ? 'number' : $field_type,
        'value' => $field_value,
        'placeholder' => $field_placeholder,
        'required' => $field_required ? 'required' : null,
        'disabled' => $field_disabled ? 'disabled' : null,
        'readonly' => $field_readonly ? 'readonly' : null,
        'aria-describedby' => ($field_help ? $field_id . '-help' : '') . ($has_error ? ' ' . $field_id . '-error' : ''),
        'aria-invalid' => $has_error ? 'true' : 'false'
    ),
    $data['data_attributes'] ?? array()
);

// Type-spezifische Attribute
switch ($field_type) {
    case 'currency':
    case 'percentage':
    case 'number':
        if ($min_value !== null) $input_attributes['min'] = $min_value;
        if ($max_value !== null) $input_attributes['max'] = $max_value;
        if ($step_value !== null) $input_attributes['step'] = $step_value;
        $input_attributes['inputmode'] = 'decimal';
        break;
        
    case 'email':
        $input_attributes['inputmode'] = 'email';
        break;
        
    case 'tel':
        $input_attributes['inputmode'] = 'tel';
        break;
}

// Validation-Events
$validation_events = array();
if ($validation_group) {
    $validation_events[] = 'onchange="QCC.validateGroup(\'' . esc_js($validation_group) . '\')"';
    $validation_events[] = 'oninput="QCC.validateGroup(\'' . esc_js($validation_group) . '\')"';
}

if ($field_type === 'currency' || $field_type === 'percentage') {
    $validation_events[] = 'oninput="QCC.updateCalculations()"';
}

// Icon für Input-Type
$type_icons = array(
    'currency' => '💰',
    'percentage' => '%',
    'email' => '📧',
    'tel' => '📞',
    'number' => '#️⃣',
    'text' => '📝'
);
$type_icon = $type_icons[$field_type] ?? $type_icons['text'];
?>

<!-- QCC Input Field Component Start -->
<div class="<?php echo esc_attr($field_classes); ?>" 
     data-field="<?php echo esc_attr($field_name); ?>"
     data-type="<?php echo esc_attr($field_type); ?>"
     <?php if ($validation_group): ?>data-validation-group="<?php echo esc_attr($validation_group); ?>"<?php endif; ?>>

    <!-- Field Label -->
    <?php if (!empty($field_label)): ?>
    <label for="<?php echo esc_attr($field_id); ?>" class="qcc-input-label">
        
        <?php if ($data['show_type_icon'] ?? true): ?>
        <span class="qcc-input-type-icon" aria-hidden="true">
            <?php echo $helpers['escape']($type_icon); ?>
        </span>
        <?php endif; ?>

        <span class="qcc-input-label-text">
            <?php echo $helpers['escape']($field_label); ?>
            
            <?php if ($field_required): ?>
            <span class="qcc-input-required" aria-label="<?php echo esc_attr($translator->get('required_field')); ?>">*</span>
            <?php endif; ?>
        </span>

        <?php if (!empty($unit_suffix) && !$show_unit_inside): ?>
        <span class="qcc-input-unit-label">
            (<?php echo $helpers['escape']($unit_suffix); ?>)
        </span>
        <?php endif; ?>

    </label>
    <?php endif; ?>

    <!-- Input Container -->
    <div class="qcc-input-container">
        
        <?php if ($field_type === 'currency' && $show_unit_inside): ?>
        <!-- Currency Symbol Prefix -->
        <div class="qcc-input-prefix">
            <span class="qcc-currency-symbol"><?php echo $helpers['escape']($currency_symbol); ?></span>
        </div>
        <?php endif; ?>

        <!-- Main Input Element -->
        <?php
        echo $template_manager->render('atoms/input', array_merge($input_attributes, array(
            'class' => $helpers['build_css_classes'](
                array('qcc-input-element'),
                array(
                    'qcc-input--' . $field_type => true,
                    'qcc-input--error' => $has_error,
                    'qcc-input--with-prefix' => $field_type === 'currency' && $show_unit_inside,
                    'qcc-input--with-suffix' => ($field_type === 'percentage' || !empty($unit_suffix)) && $show_unit_inside
                )
            ),
            'events' => implode(' ', $validation_events)
        )));
        ?>

        <?php if (($field_type === 'percentage' || !empty($unit_suffix)) && $show_unit_inside): ?>
        <!-- Percentage or Unit Suffix -->
        <div class="qcc-input-suffix">
            <?php if ($field_type === 'percentage'): ?>
            <span class="qcc-percentage-symbol">%</span>
            <?php elseif (!empty($unit_suffix)): ?>
            <span class="qcc-unit-symbol"><?php echo $helpers['escape']($unit_suffix); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($data['show_validation_icon'] ?? true): ?>
        <!-- Validation Status Icon -->
        <div class="qcc-input-validation-icon" 
             id="<?php echo esc_attr($field_id); ?>-validation"
             aria-hidden="true">
            <span class="qcc-validation-success">✓</span>
            <span class="qcc-validation-error">✗</span>
            <span class="qcc-validation-loading">⟳</span>
        </div>
        <?php endif; ?>

    </div>

    <!-- Field Help Text -->
    <?php if (!empty($field_help)): ?>
    <div class="qcc-input-help" id="<?php echo esc_attr($field_id); ?>-help">
        <span class="qcc-help-icon" aria-hidden="true">ℹ️</span>
        <span class="qcc-help-text"><?php echo $helpers['escape']($field_help); ?></span>
        
        <?php if ($field_type === 'percentage' && $validation_group): ?>
        <div class="qcc-percentage-hint">
            <small class="qcc-hint-text">
                <?php echo $helpers['escape']($translator->get('percentage_group_must_equal_100')); ?>
            </small>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if ($has_error): ?>
    <div class="qcc-input-error" 
         id="<?php echo esc_attr($field_id); ?>-error"
         role="alert"
         aria-live="polite">
        <span class="qcc-error-icon" aria-hidden="true">⚠️</span>
        <span class="qcc-error-text"><?php echo $helpers['escape']($field_error); ?></span>
    </div>
    <?php endif; ?>

    <!-- Dynamic Validation Message -->
    <div class="qcc-input-validation-message" 
         id="<?php echo esc_attr($field_id); ?>-validation-message"
         role="alert"
         aria-live="polite"
         style="display: none;">
    </div>

    <?php if ($field_type === 'currency' && ($data['show_live_calculation'] ?? false)): ?>
    <!-- Live Calculation Display -->
    <div class="qcc-input-live-calc" 
         id="<?php echo esc_attr($field_id); ?>-live-calc">
        <div class="qcc-live-calc-label">
            <?php echo $helpers['escape']($translator->get('calculated_amount')); ?>:
        </div>
        <div class="qcc-live-calc-value" data-field="<?php echo esc_attr($field_name); ?>-calculated">
            <?php echo $helpers['format_currency'](0, $data['currency'] ?? 'EUR'); ?>
        </div>
    </div>
    <?php endif; ?>

</div>
<!-- QCC Input Field Component End -->

<!-- Input Field Component Styles -->
<style>
.qcc-input-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    width: 100%;
}

.qcc-input-field--disabled {
    opacity: 0.6;
    pointer-events: none;
}

.qcc-input-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: var(--qcc-color-label, #374151);
    cursor: pointer;
    line-height: 1.5;
}

.qcc-input-type-icon {
    font-size: 1rem;
    opacity: 0.7;
}

.qcc-input-label-text {
    flex: 1;
}

.qcc-input-required {
    color: var(--qcc-color-error, #ef4444);
    font-weight: 600;
    margin-left: 0.25rem;
}

.qcc-input-unit-label {
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--qcc-color-text-muted, #6b7280);
}

.qcc-input-container {
    position: relative;
    display: flex;
    align-items: center;
    background: var(--qcc-input-bg, #ffffff);
    border: 1px solid var(--qcc-color-border, #d1d5db);
    border-radius: var(--qcc-input-radius, 8px);
    transition: all 0.2s ease;
    overflow: hidden;
}

.qcc-input-container:focus-within {
    border-color: var(--qcc-color-primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.qcc-input-field--error .qcc-input-container {
    border-color: var(--qcc-color-error, #ef4444);
}

.qcc-input-field--error .qcc-input-container:focus-within {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.qcc-input-prefix,
.qcc-input-suffix {
    display: flex;
    align-items: center;
    padding: 0 0.75rem;
    background: var(--qcc-input-addon-bg, #f9fafb);
    border-right: 1px solid var(--qcc-color-border, #d1d5db);
    color: var(--qcc-color-text-muted, #6b7280);
    font-weight: 500;
    font-size: 0.875rem;
    min-height: 2.5rem;
}

.qcc-input-suffix {
    border-right: none;
    border-left: 1px solid var(--qcc-color-border, #d1d5db);
}

.qcc-currency-symbol,
.qcc-percentage-symbol,
.qcc-unit-symbol {
    font-weight: 600;
    color: var(--qcc-color-text, #374151);
}

.qcc-input-element {
    flex: 1;
    border: none;
    outline: none;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    color: var(--qcc-color-text, #374151);
    background: transparent;
    min-height: 2.5rem;
    box-sizing: border-box;
}

.qcc-input-element::placeholder {
    color: var(--qcc-color-placeholder, #9ca3af);
    opacity: 1;
}

.qcc-input--with-prefix {
    padding-left: 0.5rem;
}

.qcc-input--with-suffix {
    padding-right: 0.5rem;
}

.qcc-input-validation-icon {
    position: absolute;
    right: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    pointer-events: none;
}

.qcc-input-validation-icon > span {
    display: none;
    font-size: 1rem;
    line-height: 1;
}

.qcc-input-field--error .qcc-validation-error,
.qcc-input-field--valid .qcc-validation-success,
.qcc-input-field--validating .qcc-validation-loading {
    display: block;
}

.qcc-validation-success {
    color: var(--qcc-color-success, #10b981);
}

.qcc-validation-error {
    color: var(--qcc-color-error, #ef4444);
}

.qcc-validation-loading {
    color: var(--qcc-color-primary, #3b82f6);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.qcc-input-help {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--qcc-color-text-muted, #6b7280);
    line-height: 1.5;
}

.qcc-help-icon {
    font-size: 0.875rem;
    opacity: 0.7;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.qcc-percentage-hint {
    margin-top: 0.25rem;
    width: 100%;
}

.qcc-hint-text {
    font-size: 0.75rem;
    color: var(--qcc-color-warning, #f59e0b);
    font-style: italic;
}

.qcc-input-error {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--qcc-color-error, #ef4444);
    line-height: 1.5;
}

.qcc-error-icon {
    font-size: 0.875rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.qcc-input-validation-message {
    font-size: 0.875rem;
    line-height: 1.5;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qcc-input-validation-message.qcc-validation-error-msg {
    background: var(--qcc-color-error-bg, #fef2f2);
    color: var(--qcc-color-error, #ef4444);
    border: 1px solid var(--qcc-color-error-border, #fecaca);
}

.qcc-input-validation-message.qcc-validation-warning-msg {
    background: var(--qcc-color-warning-bg, #fffbeb);
    color: var(--qcc-color-warning, #f59e0b);
    border: 1px solid var(--qcc-color-warning-border, #fed7aa);
}

.qcc-input-validation-message.qcc-validation-info-msg {
    background: var(--qcc-color-info-bg, #eff6ff);
    color: var(--qcc-color-info, #3b82f6);
    border: 1px solid var(--qcc-color-info-border, #bfdbfe);
}

.qcc-input-live-calc {
    background: var(--qcc-live-calc-bg, #f0f9ff);
    border: 1px solid var(--qcc-live-calc-border, #bae6fd);
    border-radius: 6px;
    padding: 0.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.qcc-live-calc-label {
    color: var(--qcc-color-text-muted, #6b7280);
    font-weight: 500;
}

.qcc-live-calc-value {
    color: var(--qcc-color-primary, #3b82f6);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

/* Type-specific styles */
.qcc-input-field--currency .qcc-input-element {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.qcc-input-field--percentage .qcc-input-element {
    text-align: center;
    font-variant-numeric: tabular-nums;
}

.qcc-input-field--number .qcc-input-element {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

/* Validation group styles */
.qcc-input-field--validation-group[data-validation-group="quality_distribution"] {
    position: relative;
}

.qcc-input-field--validation-group[data-validation-group="quality_distribution"]::after {
    content: '';
    position: absolute;
    left: -4px;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--qcc-color-warning, #f59e0b);
    border-radius: 2px;
    opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-input-label {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .qcc-input-element {
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    .qcc-input-live-calc {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
}

/* Focus and hover states */
.qcc-input-field:hover .qcc-input-container {
    border-color: var(--qcc-color-border-hover, #9ca3af);
}

.qcc-input-field--readonly .qcc-input-container {
    background: var(--qcc-input-readonly-bg, #f9fafb);
    color: var(--qcc-color-text-muted, #6b7280);
}

/* Animation for validation state changes */
.qcc-input-field {
    transition: all 0.2s ease;
}

.qcc-input-validation-icon {
    transition: opacity 0.2s ease;
}

.qcc-input-validation-message {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
        max-height: 0;
    }
    to {
        opacity: 1;
        transform: translateY(0);
        max-height: 100px;
    }
}
</style>

<?php if ($debug['debug_mode'] ?? false): ?>
<!-- Debug Information -->
<div class="qcc-debug-info" style="margin-top: 10px; padding: 8px; background: #f0f0f0; border: 1px solid #ccc; font-family: monospace; font-size: 11px;">
    <details>
        <summary>🐛 Input Field Debug Info</summary>
        <pre><?php echo $helpers['escape'](print_r(array(
            'template' => 'components/input-field',
            'field_id' => $field_id,
            'field_name' => $field_name,
            'field_type' => $field_type,
            'validation_group' => $validation_group,
            'has_error' => $has_error,
            'field_required' => $field_required,
            'input_attributes_count' => count($input_attributes),
            'timestamp' => date('H:i:s')
        ), true)); ?></pre>
    </details>
</div>
<?php endif; ?>

<?php
/**
 * Component-Dokumentation:
 * 
 * Erforderliche Daten:
 * - $data['label'] - Field-Label
 * 
 * Optionale Daten:
 * - $data['id'] - Field-ID (auto-generated wenn leer)
 * - $data['name'] - Field-Name (verwendet ID als Fallback)
 * - $data['type'] - Input-Typ (text, currency, percentage, number, email, tel)
 * - $data['value'] - Aktueller Wert
 * - $data['placeholder'] - Placeholder-Text
 * - $data['help'] - Hilfetext unter dem Input
 * - $data['required'] - Pflichtfeld
 * - $data['disabled'] - Deaktiviert
 * - $data['readonly'] - Nur-Lesen
 * - $data['error'] - Fehlermeldung
 * - $data['min'] - Minimum-Wert (für Zahlen)
 * - $data['max'] - Maximum-Wert (für Zahlen)
 * - $data['step'] - Schritt-Wert (für Zahlen)
 * - $data['currency_symbol'] - Währungssymbol (für currency-type)
 * - $data['unit_suffix'] - Einheiten-Suffix
 * - $data['show_unit_inside'] - Einheit im Input anzeigen
 * - $data['validation_group'] - Validation-Gruppe für zusammengehörige Felder
 * - $data['show_type_icon'] - Type-Icon anzeigen
 * - $data['show_validation_icon'] - Validation-Icon anzeigen
 * - $data['show_live_calculation'] - Live-Berechnung anzeigen
 * - $data['data_attributes'] - Zusätzliche Data-Attribute
 * 
 * Input-Typen:
 * - text - Standard-Text-Input
 * - currency - Währungs-Input mit Symbol
 * - percentage - Prozent-Input mit %-Symbol
 * - number - Zahlen-Input
 * - email - E-Mail-Input
 * - tel - Telefon-Input
 * 
 * Validation-Gruppen:
 * - quality_distribution - COGQ/COPQ Prozent-Verteilung (muss 100% ergeben)
 * 
 * CSS-Klassen:
 * - .qcc-input-field - Basis-Component
 * - .qcc-input-field--{type} - Type-spezifische Styles
 * - .qcc-input-field--required - Pflichtfeld-Styling
 * - .qcc-input-field--error - Error-State
 * - .qcc-input-field--valid - Valid-State
 * - .qcc-input-field--validating - Validating-State
 * - .qcc-input-container - Input-Container
 * - .qcc-input-element - Haupteingabe-Element
 * - .qcc-input-prefix/suffix - Währungs-/Prozent-Symbole
 * 
 * JavaScript-Integration:
 * - QCC.validateGroup(groupName) - Validation-Gruppe prüfen
 * - QCC.updateCalculations() - Berechnungen aktualisieren
 * - Live-Validation bei oninput/onchange
 * - Automatische Fehlermeldungen-Anzeige
 * 
 * Accessibility:
 * - aria-describedby für Help-Text und Error-Messages
 * - aria-invalid für Error-State
 * - role="alert" für Error-Messages
 * - aria-live="polite" für dynamische Validation-Messages
 * - Proper Label-Association
 * - Screen-Reader-freundliche Required-Indicators
 * 
 * Performance:
 * - Template-Caching durch Template-Manager
 * - Optimierte CSS-Selektoren
 * - Efficient DOM-Struktur
 * - Lazy Validation-Icon-Updates
 * 
 * Mobile-Optimierung:
 * - Touch-friendly Input-Sizing
 * - Appropriate inputmode für verschiedene Typen
 * - iOS Zoom-Prevention mit font-size: 16px
 * - Responsive Layout-Anpassungen
 */
?>
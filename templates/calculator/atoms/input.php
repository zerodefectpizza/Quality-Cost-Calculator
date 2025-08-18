<?php
/**
 * Template: Input Atom
 * Basis-Input-Element (kleinste wiederverwendbare Einheit)
 * 
 * @param array $data Input-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$type = $data['type'] ?? 'text'; // text, number, email, password, tel, url, search
$id = $data['id'] ?? uniqid('qcc-input-');
$name = $data['name'] ?? $id;
$value = $data['value'] ?? '';
$placeholder = $data['placeholder'] ?? '';
$required = $data['required'] ?? false;
$disabled = $data['disabled'] ?? false;
$readonly = $data['readonly'] ?? false;
$min = $data['min'] ?? null;
$max = $data['max'] ?? null;
$step = $data['step'] ?? null;
$pattern = $data['pattern'] ?? null;
$maxlength = $data['maxlength'] ?? null;
$autocomplete = $data['autocomplete'] ?? null;
$css_classes = $attributes['css_classes'] ?? '';
$size = $data['size'] ?? 'medium'; // small, medium, large
$variant = $data['variant'] ?? 'default'; // default, filled, outline
$icon = $data['icon'] ?? '';
$icon_position = $data['icon_position'] ?? 'left'; // left, right
$error = $data['error'] ?? false;
$success = $data['success'] ?? false;

// HTML-Attribute sammeln
$html_attributes = array(
    'type' => $type,
    'id' => $id,
    'name' => $name,
    'value' => $value
);

if ($placeholder) $html_attributes['placeholder'] = $placeholder;
if ($required) $html_attributes['required'] = 'required';
if ($disabled) $html_attributes['disabled'] = 'disabled';
if ($readonly) $html_attributes['readonly'] = 'readonly';
if ($min !== null) $html_attributes['min'] = $min;
if ($max !== null) $html_attributes['max'] = $max;
if ($step !== null) $html_attributes['step'] = $step;
if ($pattern) $html_attributes['pattern'] = $pattern;
if ($maxlength) $html_attributes['maxlength'] = $maxlength;
if ($autocomplete) $html_attributes['autocomplete'] = $autocomplete;

// Accessibility Attribute
if (isset($data['aria_label'])) {
    $html_attributes['aria-label'] = $data['aria_label'];
}
if (isset($data['aria_describedby'])) {
    $html_attributes['aria-describedby'] = $data['aria_describedby'];
}
if ($error) {
    $html_attributes['aria-invalid'] = 'true';
}

// Data-Attribute
if (isset($data['data_attributes'])) {
    foreach ($data['data_attributes'] as $key => $val) {
        $html_attributes['data-' . $key] = $val;
    }
}

// CSS-Klassen zusammenstellen
$input_classes = array(
    'qcc-input',
    'qcc-input-' . $size,
    'qcc-input-' . $variant
);

if ($error) $input_classes[] = 'qcc-input-error';
if ($success) $input_classes[] = 'qcc-input-success';
if ($disabled) $input_classes[] = 'qcc-input-disabled';
if ($readonly) $input_classes[] = 'qcc-input-readonly';
if ($icon) $input_classes[] = 'qcc-input-with-icon qcc-input-icon-' . $icon_position;
if ($css_classes) $input_classes[] = $css_classes;

$class_string = implode(' ', $input_classes);

// Container-Klassen
$container_classes = array('qcc-input-container');
if ($icon) $container_classes[] = 'qcc-has-icon qcc-has-icon-' . $icon_position;
if ($error) $container_classes[] = 'qcc-has-error';
if ($success) $container_classes[] = 'qcc-has-success';

// Attribute-String erstellen
$attr_string = '';
foreach ($html_attributes as $attr => $val) {
    $attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($val) . '"';
}
?>

<div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" data-input-container>
    
    <?php if ($icon && $icon_position === 'left'): ?>
    <span class="qcc-input-icon qcc-input-icon-left" aria-hidden="true">
        <?php echo $this->render_icon($icon); ?>
    </span>
    <?php endif; ?>
    
    <input class="<?php echo esc_attr($class_string); ?>"<?php echo $attr_string; ?> />
    
    <?php if ($icon && $icon_position === 'right'): ?>
    <span class="qcc-input-icon qcc-input-icon-right" aria-hidden="true">
        <?php echo $this->render_icon($icon); ?>
    </span>
    <?php endif; ?>
    
    <?php if ($error || $success): ?>
    <span class="qcc-input-status-icon" aria-hidden="true">
        <?php if ($error): ?>
            <?php echo $this->render_icon('error'); ?>
        <?php elseif ($success): ?>
            <?php echo $this->render_icon('success'); ?>
        <?php endif; ?>
    </span>
    <?php endif; ?>
    
    <?php if (isset($data['suffix'])): ?>
    <span class="qcc-input-suffix">
        <?php echo esc_html($data['suffix']); ?>
    </span>
    <?php endif; ?>
    
    <?php if (isset($data['prefix'])): ?>
    <span class="qcc-input-prefix">
        <?php echo esc_html($data['prefix']); ?>
    </span>
    <?php endif; ?>
    
</div>

<style>
/* Input Container */
.qcc-input-container {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 100%;
}

/* Base Input Styles */
.qcc-input {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 14px;
    line-height: 1.4;
    transition: all 0.2s ease;
    background-color: #fff;
    color: #333;
    box-sizing: border-box;
}

.qcc-input:focus {
    outline: none;
    border-color: #449775;
    box-shadow: 0 0 0 3px rgba(68, 151, 117, 0.1);
}

.qcc-input::placeholder {
    color: #999;
    opacity: 1;
}

/* Input Sizes */
.qcc-input-small {
    padding: 6px 10px;
    font-size: 12px;
    min-height: 28px;
}

.qcc-input-medium {
    padding: 8px 12px;
    font-size: 14px;
    min-height: 36px;
}

.qcc-input-large {
    padding: 12px 16px;
    font-size: 16px;
    min-height: 44px;
}

/* Input Variants */
.qcc-input-default {
    /* Default styles already applied above */
}

.qcc-input-filled {
    background-color: #f8f9fa;
    border: 1px solid transparent;
}

.qcc-input-filled:focus {
    background-color: #fff;
    border-color: #449775;
}

.qcc-input-outline {
    background-color: transparent;
    border: 2px solid #ddd;
}

.qcc-input-outline:focus {
    border-color: #449775;
}

/* Input States */
.qcc-input-error {
    border-color: #dc3545;
}

.qcc-input-error:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.qcc-input-success {
    border-color: #28a745;
}

.qcc-input-success:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
}

.qcc-input-disabled {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.7;
}

.qcc-input-readonly {
    background-color: #f8f9fa;
    cursor: default;
}

/* Icon Positioning */
.qcc-input-with-icon.qcc-input-icon-left {
    padding-left: 36px;
}

.qcc-input-with-icon.qcc-input-icon-right {
    padding-right: 36px;
}

.qcc-input-small.qcc-input-with-icon.qcc-input-icon-left {
    padding-left: 30px;
}

.qcc-input-small.qcc-input-with-icon.qcc-input-icon-right {
    padding-right: 30px;
}

.qcc-input-large.qcc-input-with-icon.qcc-input-icon-left {
    padding-left: 42px;
}

.qcc-input-large.qcc-input-with-icon.qcc-input-icon-right {
    padding-right: 42px;
}

/* Input Icons */
.qcc-input-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    pointer-events: none;
    z-index: 2;
}

.qcc-input-icon-left {
    left: 10px;
}

.qcc-input-icon-right {
    right: 10px;
}

.qcc-input-icon svg,
.qcc-input-icon img {
    width: 16px;
    height: 16px;
}

.qcc-input-small .qcc-input-icon svg,
.qcc-input-small .qcc-input-icon img {
    width: 14px;
    height: 14px;
}

.qcc-input-large .qcc-input-icon svg,
.qcc-input-large .qcc-input-icon img {
    width: 18px;
    height: 18px;
}

/* Status Icons */
.qcc-input-status-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
}

.qcc-has-error .qcc-input-status-icon {
    color: #dc3545;
}

.qcc-has-success .qcc-input-status-icon {
    color: #28a745;
}

.qcc-input-status-icon svg,
.qcc-input-status-icon img {
    width: 16px;
    height: 16px;
}

/* Prefix/Suffix */
.qcc-input-prefix,
.qcc-input-suffix {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    padding: 4px 8px;
    font-size: 12px;
    color: #666;
    z-index: 2;
}

.qcc-input-prefix {
    left: 0;
    border-right: none;
    border-radius: 6px 0 0 6px;
}

.qcc-input-suffix {
    right: 0;
    border-left: none;
    border-radius: 0 6px 6px 0;
}

.qcc-input-container:has(.qcc-input-prefix) .qcc-input {
    padding-left: 60px;
    border-radius: 0 6px 6px 0;
}

.qcc-input-container:has(.qcc-input-suffix) .qcc-input {
    padding-right: 60px;
    border-radius: 6px 0 0 6px;
}

/* Number Input Arrows */
.qcc-input[type="number"] {
    -moz-appearance: textfield;
}

.qcc-input[type="number"]::-webkit-outer-spin-button,
.qcc-input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Search Input */
.qcc-input[type="search"] {
    -webkit-appearance: none;
}

.qcc-input[type="search"]::-webkit-search-decoration,
.qcc-input[type="search"]::-webkit-search-cancel-button,
.qcc-input[type="search"]::-webkit-search-results-button,
.qcc-input[type="search"]::-webkit-search-results-decoration {
    -webkit-appearance: none;
}

/* Password Input */
.qcc-input[type="password"] {
    font-family: monospace;
}

/* File Input */
.qcc-input[type="file"] {
    padding: 4px;
    border: 1px dashed #ddd;
    background-color: #f8f9fa;
}

.qcc-input[type="file"]::-webkit-file-upload-button {
    background-color: #449775;
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 4px;
    margin-right: 8px;
    cursor: pointer;
}

/* Range Input */
.qcc-input[type="range"] {
    padding: 0;
    background: transparent;
    border: none;
    height: 20px;
}

.qcc-input[type="range"]::-webkit-slider-track {
    background: #ddd;
    height: 4px;
    border-radius: 2px;
}

.qcc-input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    background: #449775;
    height: 16px;
    width: 16px;
    border-radius: 50%;
    cursor: pointer;
}

.qcc-input[type="range"]::-moz-range-track {
    background: #ddd;
    height: 4px;
    border-radius: 2px;
    border: none;
}

.qcc-input[type="range"]::-moz-range-thumb {
    background: #449775;
    height: 16px;
    width: 16px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
}

/* Color Input */
.qcc-input[type="color"] {
    width: 50px;
    height: 36px;
    padding: 2px;
    border: 1px solid #ddd;
    cursor: pointer;
}

.qcc-input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

.qcc-input[type="color"]::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

/* Focus Enhancements */
.qcc-input:focus-visible {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

/* Dark Theme */
.qcc-theme-dark .qcc-input {
    background-color: #4a5568;
    border-color: #718096;
    color: #e2e8f0;
}

.qcc-theme-dark .qcc-input:focus {
    border-color: #63b3ed;
    box-shadow: 0 0 0 3px rgba(99, 179, 237, 0.1);
}

.qcc-theme-dark .qcc-input::placeholder {
    color: #a0aec0;
}

.qcc-theme-dark .qcc-input-filled {
    background-color: #2d3748;
}

.qcc-theme-dark .qcc-input-disabled {
    background-color: #2d3748;
    color: #718096;
}

.qcc-theme-dark .qcc-input-prefix,
.qcc-theme-dark .qcc-input-suffix {
    background-color: #2d3748;
    border-color: #718096;
    color: #a0aec0;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-input {
        border-width: 2px;
        border-color: #000;
    }
    
    .qcc-input:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 2px #0066cc;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-input {
        transition: none;
    }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .qcc-input {
        font-size: 16px; /* Prevents zoom on iOS */
        min-height: 44px; /* Better touch targets */
    }
    
    .qcc-input-small {
        min-height: 36px;
        font-size: 14px;
    }
    
    .qcc-input-large {
        min-height: 52px;
        font-size: 18px;
    }
}

/* Print Styles */
@media print {
    .qcc-input {
        border: 1px solid #000 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    
    .qcc-input-icon,
    .qcc-input-status-icon {
        display: none;
    }
}

/* Animation for Validation States */
.qcc-input.qcc-validation-shake {
    animation: qcc-input-shake 0.5s ease-in-out;
}

@keyframes qcc-input-shake {
    0%, 20%, 50%, 80%, 100% {
        transform: translateX(0);
    }
    10%, 30%, 70%, 90% {
        transform: translateX(-5px);
    }
    40%, 60% {
        transform: translateX(5px);
    }
}

/* Loading State */
.qcc-input-loading {
    position: relative;
    pointer-events: none;
}

.qcc-input-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: qcc-input-shimmer 1.5s infinite;
}

@keyframes qcc-input-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Validation Tooltip */
.qcc-input-validation-tooltip {
    position: absolute;
    bottom: 100%;
    left: 0;
    background: #dc3545;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    margin-bottom: 4px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
    pointer-events: none;
}

.qcc-input-validation-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 10px;
    border: 4px solid transparent;
    border-top-color: #dc3545;
}

.qcc-input-error:focus + .qcc-input-validation-tooltip,
.qcc-input-container:hover .qcc-input-validation-tooltip {
    opacity: 1;
    transform: translateY(0);
}

/* Input Group Styles */
.qcc-input-group {
    display: flex;
    align-items: stretch;
}

.qcc-input-group .qcc-input {
    border-radius: 0;
    border-right: none;
}

.qcc-input-group .qcc-input:first-child {
    border-radius: 6px 0 0 6px;
}

.qcc-input-group .qcc-input:last-child {
    border-radius: 0 6px 6px 0;
    border-right: 1px solid #ddd;
}

.qcc-input-group .qcc-input:only-child {
    border-radius: 6px;
    border-right: 1px solid #ddd;
}
</style>

<?php
/**
 * Helper-Funktion zum Rendern von Icons
 * Diese wird von der button.php wiederverwendet
 */
if (!method_exists($this, 'render_icon')) {
    function render_icon($icon) {
        // Standard Input-Icons
        $input_icons = array(
            'search' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
            'email' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
            'phone' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>',
            'user' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
            'lock' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>',
            'error' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
            'success' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
            'eye' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>',
            'eye-off' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>'
        );
        
        if (isset($input_icons[$icon])) {
            return $input_icons[$icon];
        }
        
        // Fallback zu Standard-Icon-System
        return $this->render_standard_icon($icon);
    }
    
    function render_standard_icon($icon) {
        // SVG Icons
        if (strpos($icon, '<svg') === 0) {
            return $icon;
        }
        
        // Font Awesome Icons
        if (strpos($icon, 'fa-') === 0 || strpos($icon, 'fas ') === 0) {
            return '<i class="' . esc_attr($icon) . '"></i>';
        }
        
        // Emoji Icons
        if (mb_strlen($icon) === 1 || preg_match('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', $icon)) {
            return '<span class="qcc-emoji-icon">' . esc_html($icon) . '</span>';
        }
        
        // Text Icons
        return '<span class="qcc-text-icon">' . esc_html($icon) . '</span>';
    }
}
?>
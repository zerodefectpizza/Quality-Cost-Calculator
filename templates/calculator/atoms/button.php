<?php
/**
 * Template: Button Atom
 * Basis-Button-Element (kleinste wiederverwendbare Einheit)
 * 
 * @param array $data Button-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$text = $data['text'] ?? '';
$type = $data['type'] ?? 'button'; // button, submit, reset
$variant = $data['variant'] ?? 'primary'; // primary, secondary, outline, ghost, link
$size = $data['size'] ?? 'medium'; // small, medium, large
$id = $data['id'] ?? uniqid('qcc-btn-');
$disabled = $data['disabled'] ?? false;
$loading = $data['loading'] ?? false;
$icon = $data['icon'] ?? '';
$icon_position = $data['icon_position'] ?? 'left'; // left, right, only
$css_classes = $attributes['css_classes'] ?? '';
$aria_label = $data['aria_label'] ?? '';

// Zusätzliche HTML-Attribute
$html_attributes = array();
if ($disabled || $loading) {
    $html_attributes['disabled'] = 'disabled';
    $html_attributes['aria-disabled'] = 'true';
}
if ($aria_label) {
    $html_attributes['aria-label'] = $aria_label;
}
if (isset($data['data_attributes'])) {
    foreach ($data['data_attributes'] as $key => $value) {
        $html_attributes['data-' . $key] = $value;
    }
}
if (isset($data['onclick'])) {
    $html_attributes['onclick'] = $data['onclick'];
}

// CSS-Klassen zusammenstellen
$button_classes = array(
    'qcc-btn',
    'qcc-btn-' . $variant,
    'qcc-btn-' . $size
);

if ($loading) {
    $button_classes[] = 'qcc-btn-loading';
}
if ($icon && $icon_position === 'only') {
    $button_classes[] = 'qcc-btn-icon-only';
}
if ($css_classes) {
    $button_classes[] = $css_classes;
}

$class_string = implode(' ', $button_classes);

// Attribute-String erstellen
$attr_string = '';
foreach ($html_attributes as $attr => $value) {
    $attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
}
?>

<button type="<?php echo esc_attr($type); ?>" 
        id="<?php echo esc_attr($id); ?>"
        class="<?php echo esc_attr($class_string); ?>"
        <?php echo $attr_string; ?>>
        
    <?php if ($loading): ?>
    <span class="qcc-btn-spinner" aria-hidden="true">
        <svg class="qcc-spinner" width="16" height="16" viewBox="0 0 50 50">
            <circle class="qcc-spinner-path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-miterlimit="10"/>
        </svg>
    </span>
    <?php endif; ?>
    
    <?php if ($icon && ($icon_position === 'left' || $icon_position === 'only') && !$loading): ?>
    <span class="qcc-btn-icon qcc-btn-icon-left" aria-hidden="true">
        <?php echo $this->render_icon($icon); ?>
    </span>
    <?php endif; ?>
    
    <?php if ($text && $icon_position !== 'only'): ?>
    <span class="qcc-btn-text">
        <?php echo esc_html($text); ?>
    </span>
    <?php endif; ?>
    
    <?php if ($icon && $icon_position === 'right' && !$loading): ?>
    <span class="qcc-btn-icon qcc-btn-icon-right" aria-hidden="true">
        <?php echo $this->render_icon($icon); ?>
    </span>
    <?php endif; ?>
    
    <?php if (isset($data['badge'])): ?>
    <span class="qcc-btn-badge" aria-label="<?php echo esc_attr($data['badge']); ?>">
        <?php echo esc_html($data['badge']); ?>
    </span>
    <?php endif; ?>
    
</button>

<style>
/* Base Button Styles */
.qcc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: none;
    border-radius: 6px;
    font-family: inherit;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
    user-select: none;
    vertical-align: middle;
}

.qcc-btn:focus {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

.qcc-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

/* Button Sizes */
.qcc-btn-small {
    padding: 6px 12px;
    font-size: 12px;
    min-height: 28px;
}

.qcc-btn-medium {
    padding: 8px 16px;
    font-size: 14px;
    min-height: 36px;
}

.qcc-btn-large {
    padding: 12px 24px;
    font-size: 16px;
    min-height: 44px;
}

/* Icon-only buttons */
.qcc-btn-icon-only {
    padding: 8px;
    width: 36px;
    height: 36px;
}

.qcc-btn-icon-only.qcc-btn-small {
    width: 28px;
    height: 28px;
    padding: 6px;
}

.qcc-btn-icon-only.qcc-btn-large {
    width: 44px;
    height: 44px;
    padding: 12px;
}

/* Button Variants */
.qcc-btn-primary {
    background-color: #449775;
    color: white;
    border: 1px solid #449775;
}

.qcc-btn-primary:hover:not(:disabled) {
    background-color: #357a61;
    border-color: #357a61;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(68, 151, 117, 0.3);
}

.qcc-btn-primary:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 6px rgba(68, 151, 117, 0.3);
}

.qcc-btn-secondary {
    background-color: #6c757d;
    color: white;
    border: 1px solid #6c757d;
}

.qcc-btn-secondary:hover:not(:disabled) {
    background-color: #545b62;
    border-color: #545b62;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.qcc-btn-outline {
    background-color: transparent;
    color: #449775;
    border: 1px solid #449775;
}

.qcc-btn-outline:hover:not(:disabled) {
    background-color: #449775;
    color: white;
    transform: translateY(-1px);
}

.qcc-btn-ghost {
    background-color: transparent;
    color: #449775;
    border: 1px solid transparent;
}

.qcc-btn-ghost:hover:not(:disabled) {
    background-color: rgba(68, 151, 117, 0.1);
    border-color: rgba(68, 151, 117, 0.2);
}

.qcc-btn-link {
    background-color: transparent;
    color: #449775;
    border: none;
    text-decoration: underline;
    padding: 4px 8px;
}

.qcc-btn-link:hover:not(:disabled) {
    color: #357a61;
    text-decoration: none;
}

/* Icon Styles */
.qcc-btn-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.qcc-btn-icon svg,
.qcc-btn-icon img {
    width: 16px;
    height: 16px;
}

.qcc-btn-small .qcc-btn-icon svg,
.qcc-btn-small .qcc-btn-icon img {
    width: 14px;
    height: 14px;
}

.qcc-btn-large .qcc-btn-icon svg,
.qcc-btn-large .qcc-btn-icon img {
    width: 18px;
    height: 18px;
}

/* Loading Spinner */
.qcc-btn-loading {
    pointer-events: none;
}

.qcc-btn-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
}

.qcc-spinner {
    animation: qcc-spin 1s linear infinite;
}

.qcc-spinner-path {
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

/* Badge */
.qcc-btn-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background-color: #dc3545;
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

/* Ripple Effect */
.qcc-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
    pointer-events: none;
}

.qcc-btn:active:not(:disabled)::before {
    width: 300px;
    height: 300px;
}

/* Success/Error States */
.qcc-btn-success {
    background-color: #28a745;
    color: white;
    border: 1px solid #28a745;
}

.qcc-btn-success:hover:not(:disabled) {
    background-color: #218838;
    border-color: #218838;
}

.qcc-btn-danger {
    background-color: #dc3545;
    color: white;
    border: 1px solid #dc3545;
}

.qcc-btn-danger:hover:not(:disabled) {
    background-color: #c82333;
    border-color: #c82333;
}

.qcc-btn-warning {
    background-color: #ffc107;
    color: #212529;
    border: 1px solid #ffc107;
}

.qcc-btn-warning:hover:not(:disabled) {
    background-color: #e0a800;
    border-color: #e0a800;
}

/* Dark Theme */
.qcc-theme-dark .qcc-btn-secondary {
    background-color: #4a5568;
    border-color: #4a5568;
    color: #e2e8f0;
}

.qcc-theme-dark .qcc-btn-outline {
    color: #e2e8f0;
    border-color: #e2e8f0;
}

.qcc-theme-dark .qcc-btn-ghost {
    color: #e2e8f0;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-btn {
        border-width: 2px;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-btn {
        transition: none;
    }
    
    .qcc-btn:hover {
        transform: none;
    }
    
    .qcc-spinner {
        animation: none;
    }
    
    .qcc-spinner-path {
        animation: none;
        stroke-dasharray: none;
    }
}

/* Print Styles */
@media print {
    .qcc-btn {
        background: transparent !important;
        color: #000 !important;
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .qcc-btn {
        min-height: 44px; /* Better touch targets */
    }
    
    .qcc-btn-small {
        min-height: 36px;
    }
    
    .qcc-btn-large {
        min-height: 52px;
    }
}

/* Keyboard Navigation */
.qcc-btn:focus-visible {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

/* Screen Reader Support */
.qcc-btn[aria-label]:empty::after {
    content: attr(aria-label);
    clip: rect(0 0 0 0);
    clip-path: inset(50%);
    height: 1px;
    overflow: hidden;
    position: absolute;
    white-space: nowrap;
    width: 1px;
}
</style>

<?php
/**
 * Helper-Funktion zum Rendern von Icons
 * Diese Funktion sollte in der entsprechenden Klasse implementiert werden
 */
if (!method_exists($this, 'render_icon')) {
    function render_icon($icon) {
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
        
        // Standard Icons (as text)
        $standard_icons = array(
            'check' => '✓',
            'times' => '×',
            'plus' => '+',
            'minus' => '−',
            'arrow-left' => '←',
            'arrow-right' => '→',
            'arrow-up' => '↑',
            'arrow-down' => '↓',
            'download' => '↓',
            'upload' => '↑',
            'search' => '🔍',
            'edit' => '✏️',
            'delete' => '🗑️',
            'save' => '💾',
            'print' => '🖨️',
            'email' => '✉️',
            'phone' => '📞',
            'home' => '🏠',
            'user' => '👤',
            'settings' => '⚙️',
            'info' => 'ℹ',
            'warning' => '⚠',
            'error' => '⚠',
            'success' => '✓'
        );
        
        if (isset($standard_icons[$icon])) {
            return '<span class="qcc-standard-icon">' . $standard_icons[$icon] . '</span>';
        }
        
        // Fallback: return as text
        return '<span class="qcc-text-icon">' . esc_html($icon) . '</span>';
    }
}
?>
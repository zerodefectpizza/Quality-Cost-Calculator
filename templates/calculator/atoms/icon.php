<?php
/**
 * Template: Icon Atom
 * Basis-Icon-Element für verschiedene Icon-Typen
 * 
 * @param array $data Icon-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$icon = $data['icon'] ?? '';
$type = $data['type'] ?? 'auto'; // auto, svg, font, emoji, image
$size = $data['size'] ?? 'medium'; // tiny, small, medium, large, xlarge
$color = $data['color'] ?? '';
$id = $data['id'] ?? '';
$title = $data['title'] ?? '';
$aria_label = $data['aria_label'] ?? '';
$aria_hidden = $data['aria_hidden'] ?? true;
$css_classes = $attributes['css_classes'] ?? '';
$clickable = $data['clickable'] ?? false;
$spin = $data['spin'] ?? false;
$pulse = $data['pulse'] ?? false;
$flip = $data['flip'] ?? ''; // horizontal, vertical, both
$rotate = $data['rotate'] ?? ''; // 90, 180, 270

// Icon-Typ automatisch erkennen wenn 'auto'
if ($type === 'auto') {
    $type = $this->detect_icon_type($icon);
}

// CSS-Klassen zusammenstellen
$icon_classes = array(
    'qcc-icon',
    'qcc-icon-' . $type,
    'qcc-icon-' . $size
);

if ($spin) $icon_classes[] = 'qcc-icon-spin';
if ($pulse) $icon_classes[] = 'qcc-icon-pulse';
if ($flip) $icon_classes[] = 'qcc-icon-flip-' . $flip;
if ($rotate) $icon_classes[] = 'qcc-icon-rotate-' . $rotate;
if ($clickable) $icon_classes[] = 'qcc-icon-clickable';
if ($css_classes) $icon_classes[] = $css_classes;

$class_string = implode(' ', $icon_classes);

// HTML-Attribute
$html_attributes = array();
if ($id) $html_attributes['id'] = $id;
if ($title) $html_attributes['title'] = $title;
if ($aria_label) {
    $html_attributes['aria-label'] = $aria_label;
    $html_attributes['role'] = 'img';
    $aria_hidden = false; // Wenn aria-label vorhanden, nicht verstecken
}
if ($aria_hidden) $html_attributes['aria-hidden'] = 'true';
if ($color) $html_attributes['style'] = 'color: ' . $color . ';';

// Event-Handler
if (isset($data['onclick'])) {
    $html_attributes['onclick'] = $data['onclick'];
}
if ($clickable && !isset($data['onclick'])) {
    $html_attributes['tabindex'] = '0';
    $html_attributes['role'] = 'button';
}

// Data-Attribute
if (isset($data['data_attributes'])) {
    foreach ($data['data_attributes'] as $key => $value) {
        $html_attributes['data-' . $key] = $value;
    }
}

// Attribute-String
$attr_string = '';
foreach ($html_attributes as $attr => $value) {
    if (is_bool($value)) {
        if ($value) $attr_string .= ' ' . esc_attr($attr);
    } else {
        $attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
    }
}
?>

<?php if ($type === 'svg'): ?>
    <?php echo $this->render_svg_icon($icon, $class_string, $attr_string); ?>
    
<?php elseif ($type === 'font'): ?>
    <i class="<?php echo esc_attr($class_string . ' ' . $icon); ?>"<?php echo $attr_string; ?>></i>
    
<?php elseif ($type === 'emoji'): ?>
    <span class="<?php echo esc_attr($class_string); ?>"<?php echo $attr_string; ?>>
        <?php echo esc_html($icon); ?>
    </span>
    
<?php elseif ($type === 'image'): ?>
    <img class="<?php echo esc_attr($class_string); ?>" 
         src="<?php echo esc_url($icon); ?>" 
         alt="<?php echo esc_attr($aria_label ?: $title ?: ''); ?>"
         <?php echo $attr_string; ?> />
         
<?php else: ?>
    <!-- Fallback: Text Icon -->
    <span class="<?php echo esc_attr($class_string); ?>"<?php echo $attr_string; ?>>
        <?php echo esc_html($icon); ?>
    </span>
<?php endif; ?>

<style>
/* Base Icon Styles */
.qcc-icon {
    display: inline-block;
    vertical-align: middle;
    line-height: 1;
    color: currentColor;
    fill: currentColor;
    flex-shrink: 0;
}

/* Icon Sizes */
.qcc-icon-tiny {
    width: 12px;
    height: 12px;
    font-size: 12px;
}

.qcc-icon-small {
    width: 16px;
    height: 16px;
    font-size: 16px;
}

.qcc-icon-medium {
    width: 20px;
    height: 20px;
    font-size: 20px;
}

.qcc-icon-large {
    width: 24px;
    height: 24px;
    font-size: 24px;
}

.qcc-icon-xlarge {
    width: 32px;
    height: 32px;
    font-size: 32px;
}

/* SVG Icons */
.qcc-icon-svg {
    /* Size wird über width/height gesetzt */
}

.qcc-icon-svg svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* Font Icons */
.qcc-icon-font {
    font-style: normal;
    font-weight: normal;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Emoji Icons */
.qcc-icon-emoji {
    font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif;
    font-style: normal;
    font-weight: normal;
    line-height: 1;
}

/* Image Icons */
.qcc-icon-image {
    object-fit: contain;
}

/* Clickable Icons */
.qcc-icon-clickable {
    cursor: pointer;
    border-radius: 4px;
    padding: 4px;
    transition: all 0.2s ease;
}

.qcc-icon-clickable:hover {
    background-color: rgba(0, 0, 0, 0.05);
    transform: scale(1.1);
}

.qcc-icon-clickable:focus {
    outline: 2px solid #449775;
    outline-offset: 2px;
    background-color: rgba(68, 151, 117, 0.1);
}

.qcc-icon-clickable:active {
    transform: scale(0.95);
}

/* Animations */
.qcc-icon-spin {
    animation: qcc-icon-spin 1s linear infinite;
}

.qcc-icon-pulse {
    animation: qcc-icon-pulse 2s infinite ease-in-out;
}

@keyframes qcc-icon-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes qcc-icon-pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.05);
    }
}

/* Transformations */
.qcc-icon-flip-horizontal {
    transform: scaleX(-1);
}

.qcc-icon-flip-vertical {
    transform: scaleY(-1);
}

.qcc-icon-flip-both {
    transform: scale(-1, -1);
}

.qcc-icon-rotate-90 {
    transform: rotate(90deg);
}

.qcc-icon-rotate-180 {
    transform: rotate(180deg);
}

.qcc-icon-rotate-270 {
    transform: rotate(270deg);
}

/* Combined Transformations */
.qcc-icon-spin.qcc-icon-flip-horizontal {
    animation: qcc-icon-spin-flip-h 1s linear infinite;
}

@keyframes qcc-icon-spin-flip-h {
    0% { transform: scaleX(-1) rotate(0deg); }
    100% { transform: scaleX(-1) rotate(360deg); }
}

/* Color Variants */
.qcc-icon-primary {
    color: #449775;
}

.qcc-icon-secondary {
    color: #6c757d;
}

.qcc-icon-success {
    color: #28a745;
}

.qcc-icon-danger {
    color: #dc3545;
}

.qcc-icon-warning {
    color: #ffc107;
}

.qcc-icon-info {
    color: #17a2b8;
}

.qcc-icon-light {
    color: #f8f9fa;
}

.qcc-icon-dark {
    color: #343a40;
}

.qcc-icon-muted {
    color: #6c757d;
    opacity: 0.7;
}

/* States */
.qcc-icon-disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}

.qcc-icon-loading {
    opacity: 0.6;
}

/* Badge/Notification Dot */
.qcc-icon-container {
    position: relative;
    display: inline-block;
}

.qcc-icon-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 8px;
    height: 8px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 8px;
    padding: 2px;
}

.qcc-icon-badge-with-number {
    min-width: 16px;
    height: 16px;
    border-radius: 8px;
    font-size: 10px;
    font-weight: 600;
    line-height: 1;
    padding: 2px 4px;
}

/* Dark Theme */
.qcc-theme-dark .qcc-icon-clickable:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.qcc-theme-dark .qcc-icon-clickable:focus {
    background-color: rgba(68, 151, 117, 0.2);
}

.qcc-theme-dark .qcc-icon-light {
    color: #343a40;
}

.qcc-theme-dark .qcc-icon-dark {
    color: #f8f9fa;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-icon {
        filter: contrast(2);
    }
    
    .qcc-icon-clickable:focus {
        outline: 3px solid #000;
        background-color: #ffff00;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-icon-spin,
    .qcc-icon-pulse {
        animation: none;
    }
    
    .qcc-icon-clickable {
        transition: none;
    }
    
    .qcc-icon-clickable:hover {
        transform: none;
    }
}

/* Print Styles */
@media print {
    .qcc-icon {
        color: #000 !important;
        fill: #000 !important;
    }
    
    .qcc-icon-spin,
    .qcc-icon-pulse {
        animation: none;
    }
    
    .qcc-icon-badge {
        display: none;
    }
}

/* Accessibility Enhancements */
.qcc-icon[role="button"] {
    cursor: pointer;
}

.qcc-icon[role="button"]:focus {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

/* Icon Groups */
.qcc-icon-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.qcc-icon-group .qcc-icon {
    flex-shrink: 0;
}

/* Icon with Text */
.qcc-icon-with-text {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.qcc-icon-with-text .qcc-icon {
    flex-shrink: 0;
}

/* Responsive Icons */
@media (max-width: 768px) {
    .qcc-icon-responsive-hide {
        display: none;
    }
    
    .qcc-icon-clickable {
        padding: 8px; /* Larger touch targets */
        min-width: 44px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
}

/* Loading Shimmer Effect */
.qcc-icon-shimmer {
    position: relative;
    overflow: hidden;
}

.qcc-icon-shimmer::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: qcc-icon-shimmer 1.5s infinite;
}

@keyframes qcc-icon-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Icon Stack */
.qcc-icon-stack {
    position: relative;
    display: inline-block;
}

.qcc-icon-stack .qcc-icon-stack-base {
    position: relative;
    z-index: 1;
}

.qcc-icon-stack .qcc-icon-stack-overlay {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 2;
}

/* Icon Border */
.qcc-icon-bordered {
    border: 1px solid currentColor;
    border-radius: 4px;
    padding: 4px;
}

.qcc-icon-circle {
    border-radius: 50%;
}

/* Icon Background */
.qcc-icon-bg {
    background-color: currentColor;
    color: white;
    border-radius: 4px;
    padding: 4px;
}

.qcc-icon-bg.qcc-icon-circle {
    border-radius: 50%;
}
</style>

<?php
/**
 * Helper-Funktionen für Icon-Rendering
 */

// Icon-Typ automatisch erkennen
if (!method_exists($this, 'detect_icon_type')) {
    function detect_icon_type($icon) {
        // SVG-Icon
        if (strpos($icon, '<svg') === 0) {
            return 'svg';
        }
        
        // Font Awesome oder ähnliche Icon-Fonts
        if (strpos($icon, 'fa-') !== false || 
            strpos($icon, 'icon-') !== false || 
            strpos($icon, 'material-icons') !== false) {
            return 'font';
        }
        
        // Bild-URL
        if (filter_var($icon, FILTER_VALIDATE_URL) || 
            preg_match('/\.(jpg|jpeg|png|gif|svg|webp)$/i', $icon)) {
            return 'image';
        }
        
        // Emoji (Unicode-Bereich)
        if (preg_match('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', $icon)) {
            return 'emoji';
        }
        
        // Standard-Text
        return 'font';
    }
}

// SVG-Icon rendern
if (!method_exists($this, 'render_svg_icon')) {
    function render_svg_icon($icon, $class_string, $attr_string) {
        // Standard-SVG-Icons
        $svg_icons = array(
            'check' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
            'times' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>',
            'plus' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
            'minus' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 13H5v-2h14v2z"/></svg>',
            'arrow-up' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/></svg>',
            'arrow-down' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>',
            'arrow-left' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/></svg>',
            'arrow-right' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>',
            'info' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
            'warning' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
            'success' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
            'error' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z"/></svg>',
            'search' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>',
            'settings' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>',
            'download' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>',
            'upload' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>',
            'edit' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>',
            'delete' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>',
            'copy' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>',
            'print' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>',
            'save' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>',
            'home' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
            'user' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>',
            'loading' => '<svg viewBox="0 0 50 50" fill="currentColor"><path d="M25.251,6.461c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615V6.461z"><animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/></path></svg>'
        );
        
        if (isset($svg_icons[$icon])) {
            $svg_content = $svg_icons[$icon];
        } else if (strpos($icon, '<svg') === 0) {
            $svg_content = $icon;
        } else {
            // Fallback SVG
            $svg_content = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>';
        }
        
        return '<span class="' . esc_attr($class_string) . '"' . $attr_string . '>' . $svg_content . '</span>';
    }
}
?>
<?php
/**
 * Template: Label Atom
 * Basis-Label-Element für Formulare
 * 
 * @param array $data Label-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$text = $data['text'] ?? '';
$for = $data['for'] ?? '';
$id = $data['id'] ?? '';
$required = $data['required'] ?? false;
$optional = $data['optional'] ?? false;
$help_text = $data['help_text'] ?? '';
$tooltip = $data['tooltip'] ?? '';
$size = $data['size'] ?? 'medium'; // small, medium, large
$weight = $data['weight'] ?? 'normal'; // normal, medium, bold
$css_classes = $attributes['css_classes'] ?? '';
$position = $data['position'] ?? 'top'; // top, left, inline
$alignment = $data['alignment'] ?? 'left'; // left, center, right

// CSS-Klassen zusammenstellen
$label_classes = array(
    'qcc-label',
    'qcc-label-' . $size,
    'qcc-label-' . $weight,
    'qcc-label-' . $position,
    'qcc-label-align-' . $alignment
);

if ($required) $label_classes[] = 'qcc-label-required';
if ($optional) $label_classes[] = 'qcc-label-optional';
if ($help_text) $label_classes[] = 'qcc-label-with-help';
if ($tooltip) $label_classes[] = 'qcc-label-with-tooltip';
if ($css_classes) $label_classes[] = $css_classes;

$class_string = implode(' ', $label_classes);

// HTML-Attribute
$html_attributes = array();
if ($for) $html_attributes['for'] = $for;
if ($id) $html_attributes['id'] = $id;

// Accessibility
if ($help_text) {
    $help_id = $id ? $id . '-help' : uniqid('qcc-help-');
    $html_attributes['aria-describedby'] = $help_id;
}

// Attribute-String erstellen
$attr_string = '';
foreach ($html_attributes as $attr => $value) {
    $attr_string .= ' ' . esc_attr($attr) . '="' . esc_attr($value) . '"';
}
?>

<label class="<?php echo esc_attr($class_string); ?>"<?php echo $attr_string; ?>>
    
    <span class="qcc-label-text">
        <?php echo esc_html($text); ?>
        
        <?php if ($required): ?>
        <span class="qcc-label-required-indicator" aria-label="<?php echo esc_attr($translator->get('required_field', 'Required field')); ?>">
            *
        </span>
        <?php endif; ?>
        
        <?php if ($optional): ?>
        <span class="qcc-label-optional-indicator">
            (<?php echo esc_html($translator->get('optional', 'optional')); ?>)
        </span>
        <?php endif; ?>
        
        <?php if ($tooltip): ?>
        <span class="qcc-label-tooltip-trigger" 
              data-tooltip="<?php echo esc_attr($tooltip); ?>"
              aria-label="<?php echo esc_attr($translator->get('help_information', 'Help information')); ?>"
              tabindex="0">
            <svg class="qcc-tooltip-icon" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
            </svg>
        </span>
        <?php endif; ?>
    </span>
    
    <?php if ($help_text): ?>
    <span class="qcc-label-help-text" id="<?php echo esc_attr($help_id ?? ''); ?>">
        <?php echo esc_html($help_text); ?>
    </span>
    <?php endif; ?>
    
    <!-- Tooltip Content (hidden by default) -->
    <?php if ($tooltip): ?>
    <div class="qcc-label-tooltip" role="tooltip" aria-hidden="true">
        <div class="qcc-tooltip-content">
            <?php echo esc_html($tooltip); ?>
        </div>
        <div class="qcc-tooltip-arrow"></div>
    </div>
    <?php endif; ?>
    
</label>

<style>
/* Base Label Styles */
.qcc-label {
    display: block;
    color: #333;
    font-family: inherit;
    cursor: pointer;
    position: relative;
    margin-bottom: 4px;
}

.qcc-label-text {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

/* Label Sizes */
.qcc-label-small {
    font-size: 12px;
}

.qcc-label-medium {
    font-size: 14px;
}

.qcc-label-large {
    font-size: 16px;
}

/* Label Weights */
.qcc-label-normal {
    font-weight: 400;
}

.qcc-label-medium {
    font-weight: 500;
}

.qcc-label-bold {
    font-weight: 600;
}

/* Label Positions */
.qcc-label-top {
    display: block;
    margin-bottom: 6px;
}

.qcc-label-left {
    display: inline-block;
    width: 120px;
    vertical-align: top;
    margin-right: 12px;
    margin-bottom: 0;
}

.qcc-label-inline {
    display: inline;
    margin-left: 8px;
    margin-bottom: 0;
}

/* Label Alignment */
.qcc-label-align-left {
    text-align: left;
}

.qcc-label-align-center {
    text-align: center;
}

.qcc-label-align-right {
    text-align: right;
}

/* Required Indicator */
.qcc-label-required-indicator {
    color: #dc3545;
    font-weight: bold;
    margin-left: 2px;
}

.qcc-label-required .qcc-label-text {
    font-weight: 500;
}

/* Optional Indicator */
.qcc-label-optional-indicator {
    color: #6c757d;
    font-weight: 400;
    font-size: 0.9em;
    margin-left: 4px;
}

/* Help Text */
.qcc-label-help-text {
    display: block;
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
    line-height: 1.4;
}

.qcc-label-left .qcc-label-help-text {
    margin-top: 2px;
}

/* Tooltip */
.qcc-label-tooltip-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: help;
    color: #6c757d;
    transition: color 0.2s ease;
    border-radius: 50%;
    padding: 2px;
}

.qcc-label-tooltip-trigger:hover,
.qcc-label-tooltip-trigger:focus {
    color: #449775;
    background-color: rgba(68, 151, 117, 0.1);
}

.qcc-label-tooltip-trigger:focus {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

.qcc-tooltip-icon {
    width: 14px;
    height: 14px;
}

/* Tooltip Content */
.qcc-label-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.4;
    white-space: nowrap;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
    margin-bottom: 8px;
    max-width: 250px;
    white-space: normal;
}

.qcc-tooltip-arrow {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #333;
}

.qcc-label-tooltip-trigger:hover + .qcc-label-tooltip,
.qcc-label-tooltip-trigger:focus + .qcc-label-tooltip,
.qcc-label-tooltip-trigger:hover ~ .qcc-label-tooltip,
.qcc-label-tooltip-trigger:focus ~ .qcc-label-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-4px);
}

/* States */
.qcc-label:hover {
    color: #449775;
}

.qcc-label.qcc-label-disabled {
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.7;
}

.qcc-label.qcc-label-error {
    color: #dc3545;
}

.qcc-label.qcc-label-success {
    color: #28a745;
}

/* Dark Theme */
.qcc-theme-dark .qcc-label {
    color: #e2e8f0;
}

.qcc-theme-dark .qcc-label:hover {
    color: #63b3ed;
}

.qcc-theme-dark .qcc-label-help-text {
    color: #a0aec0;
}

.qcc-theme-dark .qcc-label-optional-indicator {
    color: #a0aec0;
}

.qcc-theme-dark .qcc-label-tooltip-trigger {
    color: #a0aec0;
}

.qcc-theme-dark .qcc-label-tooltip-trigger:hover,
.qcc-theme-dark .qcc-label-tooltip-trigger:focus {
    color: #63b3ed;
    background-color: rgba(99, 179, 237, 0.1);
}

.qcc-theme-dark .qcc-label-tooltip {
    background: #1a202c;
    color: #e2e8f0;
}

.qcc-theme-dark .qcc-tooltip-arrow {
    border-top-color: #1a202c;
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-label-left {
        display: block;
        width: 100%;
        margin-right: 0;
        margin-bottom: 6px;
    }
    
    .qcc-label-tooltip {
        max-width: 200px;
        font-size: 11px;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-label {
        color: #000;
        font-weight: 600;
    }
    
    .qcc-label-required-indicator {
        color: #cc0000;
    }
    
    .qcc-label-tooltip {
        background: #000;
        border: 1px solid #fff;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-label-tooltip {
        transition: none;
    }
    
    .qcc-label-tooltip-trigger {
        transition: none;
    }
}

/* Print Styles */
@media print {
    .qcc-label-tooltip,
    .qcc-label-tooltip-trigger {
        display: none;
    }
    
    .qcc-label {
        color: #000 !important;
    }
    
    .qcc-label-help-text {
        color: #666 !important;
    }
}

/* Form Layout Integration */
.qcc-form-row .qcc-label-left {
    width: 150px;
}

.qcc-form-row-narrow .qcc-label-left {
    width: 100px;
}

.qcc-form-row-wide .qcc-label-left {
    width: 200px;
}

/* Animation for Required Fields */
.qcc-label-required .qcc-label-required-indicator {
    animation: qcc-required-pulse 2s infinite ease-in-out;
}

@keyframes qcc-required-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}

/* Focus Within Support */
.qcc-form-group:focus-within .qcc-label {
    color: #449775;
}

.qcc-theme-dark .qcc-form-group:focus-within .qcc-label {
    color: #63b3ed;
}

/* Validation States */
.qcc-input-error + .qcc-label,
.qcc-form-group.qcc-has-error .qcc-label {
    color: #dc3545;
}

.qcc-input-success + .qcc-label,
.qcc-form-group.qcc-has-success .qcc-label {
    color: #28a745;
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

<script>
// Label Tooltip JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Handle tooltip positioning
    const tooltipTriggers = document.querySelectorAll('.qcc-label-tooltip-trigger');
    
    tooltipTriggers.forEach(trigger => {
        const tooltip = trigger.parentNode.querySelector('.qcc-label-tooltip');
        if (!tooltip) return;
        
        function positionTooltip() {
            const triggerRect = trigger.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            
            // Check if tooltip would overflow on the right
            if (triggerRect.left + tooltipRect.width / 2 > viewportWidth - 10) {
                tooltip.style.left = 'auto';
                tooltip.style.right = '0';
                tooltip.style.transform = 'translateX(0)';
                
                // Adjust arrow position
                const arrow = tooltip.querySelector('.qcc-tooltip-arrow');
                if (arrow) {
                    arrow.style.left = 'auto';
                    arrow.style.right = '12px';
                    arrow.style.transform = 'translateX(0)';
                }
            }
            // Check if tooltip would overflow on the left
            else if (triggerRect.left - tooltipRect.width / 2 < 10) {
                tooltip.style.left = '0';
                tooltip.style.right = 'auto';
                tooltip.style.transform = 'translateX(0)';
                
                // Adjust arrow position
                const arrow = tooltip.querySelector('.qcc-tooltip-arrow');
                if (arrow) {
                    arrow.style.left = '12px';
                    arrow.style.right = 'auto';
                    arrow.style.transform = 'translateX(0)';
                }
            }
        }
        
        // Position tooltip on hover/focus
        trigger.addEventListener('mouseenter', positionTooltip);
        trigger.addEventListener('focus', positionTooltip);
        
        // Handle keyboard navigation
        trigger.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                trigger.blur();
            }
        });
    });
    
    // Close tooltips when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.qcc-label-tooltip-trigger')) {
            tooltipTriggers.forEach(trigger => {
                if (document.activeElement === trigger) {
                    trigger.blur();
                }
            });
        }
    });
});

// Label API for JavaScript
window.QccLabel = {
    // Show/hide tooltip programmatically
    showTooltip: function(labelId) {
        const label = document.getElementById(labelId);
        if (label) {
            const trigger = label.querySelector('.qcc-label-tooltip-trigger');
            if (trigger) {
                trigger.focus();
            }
        }
    },
    
    hideTooltip: function(labelId) {
        const label = document.getElementById(labelId);
        if (label) {
            const trigger = label.querySelector('.qcc-label-tooltip-trigger');
            if (trigger) {
                trigger.blur();
            }
        }
    },
    
    // Update label text
    setText: function(labelId, text) {
        const label = document.getElementById(labelId);
        if (label) {
            const textElement = label.querySelector('.qcc-label-text');
            if (textElement) {
                // Preserve indicators and tooltip triggers
                const indicators = textElement.querySelectorAll('.qcc-label-required-indicator, .qcc-label-optional-indicator, .qcc-label-tooltip-trigger');
                textElement.childNodes.forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE) {
                        node.textContent = text;
                    }
                });
            }
        }
    },
    
    // Update help text
    setHelpText: function(labelId, helpText) {
        const label = document.getElementById(labelId);
        if (label) {
            let helpElement = label.querySelector('.qcc-label-help-text');
            if (helpText) {
                if (!helpElement) {
                    helpElement = document.createElement('span');
                    helpElement.className = 'qcc-label-help-text';
                    label.appendChild(helpElement);
                }
                helpElement.textContent = helpText;
            } else if (helpElement) {
                helpElement.remove();
            }
        }
    },
    
    // Set required state
    setRequired: function(labelId, required = true) {
        const label = document.getElementById(labelId);
        if (label) {
            if (required) {
                label.classList.add('qcc-label-required');
                const textElement = label.querySelector('.qcc-label-text');
                if (textElement && !textElement.querySelector('.qcc-label-required-indicator')) {
                    const indicator = document.createElement('span');
                    indicator.className = 'qcc-label-required-indicator';
                    indicator.textContent = '*';
                    indicator.setAttribute('aria-label', 'Required field');
                    textElement.appendChild(indicator);
                }
            } else {
                label.classList.remove('qcc-label-required');
                const indicator = label.querySelector('.qcc-label-required-indicator');
                if (indicator) {
                    indicator.remove();
                }
            }
        }
    }
};
</script>
<?php
/**
 * Template: Two Column Layout
 * 2-Spalten Layout für Desktop-Ansicht des Quality Cost Calculators
 * 
 * @param array $data Layout-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$id = $data['id'] ?? 'qcc-two-column';
$left_content = $data['left_content'] ?? '';
$right_content = $data['right_content'] ?? '';
$left_width = $data['left_width'] ?? '50%';
$right_width = $data['right_width'] ?? '50%';
$gap = $data['gap'] ?? '30px';
$vertical_align = $data['vertical_align'] ?? 'start'; // start, center, end, stretch
$responsive_breakpoint = $data['breakpoint'] ?? '768px';
$css_classes = $attributes['css_classes'] ?? '';
$reverse_mobile = $data['reverse_mobile'] ?? false;
$equal_height = $data['equal_height'] ?? false;

// CSS Custom Properties für dynamische Werte
$style_vars = array(
    '--qcc-left-width' => $left_width,
    '--qcc-right-width' => $right_width,
    '--qcc-column-gap' => $gap,
    '--qcc-breakpoint' => $responsive_breakpoint
);

$style_string = '';
foreach ($style_vars as $property => $value) {
    $style_string .= $property . ': ' . $value . '; ';
}
?>

<div id="<?php echo esc_attr($id); ?>" 
     class="qcc-two-column-layout <?php echo esc_attr($css_classes); ?> 
            <?php echo $reverse_mobile ? 'qcc-reverse-mobile' : ''; ?>
            <?php echo $equal_height ? 'qcc-equal-height' : ''; ?>
            qcc-align-<?php echo esc_attr($vertical_align); ?>"
     style="<?php echo esc_attr($style_string); ?>">
     
    <!-- Left Column -->
    <div class="qcc-column qcc-column-left" role="region" aria-label="<?php echo esc_attr($translator->get('left_column', 'Left Column')); ?>">
        <div class="qcc-column-content">
            <?php echo $left_content; ?>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="qcc-column qcc-column-right" role="region" aria-label="<?php echo esc_attr($translator->get('right_column', 'Right Column')); ?>">
        <div class="qcc-column-content">
            <?php echo $right_content; ?>
        </div>
    </div>
    
    <!-- Full Width Section (optional) -->
    <?php if (isset($data['full_width_content']) && $data['full_width_content']): ?>
    <div class="qcc-column qcc-column-full" role="region" aria-label="<?php echo esc_attr($translator->get('full_width_section', 'Full Width Section')); ?>">
        <div class="qcc-column-content">
            <?php echo $data['full_width_content']; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<style>
.qcc-two-column-layout {
    display: grid;
    grid-template-columns: var(--qcc-left-width, 1fr) var(--qcc-right-width, 1fr);
    gap: var(--qcc-column-gap, 30px);
    width: 100%;
    box-sizing: border-box;
}

/* Column Alignment */
.qcc-align-start {
    align-items: start;
}

.qcc-align-center {
    align-items: center;
}

.qcc-align-end {
    align-items: end;
}

.qcc-align-stretch {
    align-items: stretch;
}

/* Equal Height Columns */
.qcc-equal-height .qcc-column {
    display: flex;
    flex-direction: column;
}

.qcc-equal-height .qcc-column-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Column Base Styles */
.qcc-column {
    position: relative;
    min-width: 0; /* Prevents overflow issues */
}

.qcc-column-content {
    width: 100%;
    height: 100%;
}

/* Full Width Section */
.qcc-column-full {
    grid-column: 1 / -1;
    margin-top: 20px;
}

/* Responsive Behavior */
@media (max-width: 768px) {
    .qcc-two-column-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    /* Reverse order on mobile if specified */
    .qcc-reverse-mobile {
        display: flex;
        flex-direction: column-reverse;
    }
    
    .qcc-reverse-mobile .qcc-column-full {
        order: 0;
        margin-top: 0;
        margin-bottom: 20px;
    }
}

@media (max-width: 480px) {
    .qcc-two-column-layout {
        gap: 15px;
    }
}

/* Custom Breakpoint Support */
@media (max-width: var(--qcc-breakpoint, 768px)) {
    .qcc-two-column-layout {
        grid-template-columns: 1fr;
    }
}

/* Animation for Content Loading */
.qcc-column {
    animation: qcc-column-fade-in 0.3s ease-out;
}

@keyframes qcc-column-fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Print Styles */
@media print {
    .qcc-two-column-layout {
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        page-break-inside: avoid;
    }
    
    .qcc-column {
        page-break-inside: avoid;
    }
}

/* Accessibility */
.qcc-column:focus-within {
    outline: 2px solid #449775;
    outline-offset: 2px;
    border-radius: 4px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-column {
        border: 1px solid;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-column {
        animation: none;
    }
}

/* Grid Debugging (Development only) */
.qcc-debug .qcc-two-column-layout {
    outline: 2px dashed #ff0000;
}

.qcc-debug .qcc-column {
    outline: 1px dashed #00ff00;
    background-color: rgba(0, 255, 0, 0.1);
}

/* Variant: Sidebar Layout */
.qcc-sidebar-left {
    grid-template-columns: 300px 1fr;
}

.qcc-sidebar-right {
    grid-template-columns: 1fr 300px;
}

@media (max-width: 768px) {
    .qcc-sidebar-left,
    .qcc-sidebar-right {
        grid-template-columns: 1fr;
    }
}

/* Variant: 70/30 Split */
.qcc-split-70-30 {
    grid-template-columns: 70% 30%;
}

.qcc-split-30-70 {
    grid-template-columns: 30% 70%;
}

@media (max-width: 768px) {
    .qcc-split-70-30,
    .qcc-split-30-70 {
        grid-template-columns: 1fr;
    }
}

/* Variant: Golden Ratio */
.qcc-golden-ratio {
    grid-template-columns: 1.618fr 1fr;
}

@media (max-width: 768px) {
    .qcc-golden-ratio {
        grid-template-columns: 1fr;
    }
}

/* Content Overflow Handling */
.qcc-column-content {
    overflow-wrap: break-word;
    word-wrap: break-word;
    hyphens: auto;
}

/* Loading State */
.qcc-two-column-layout.qcc-loading .qcc-column {
    opacity: 0.6;
    pointer-events: none;
}

.qcc-two-column-layout.qcc-loading .qcc-column::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: qcc-shimmer 1.5s infinite;
}

@keyframes qcc-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>

<script>
// Two Column Layout JavaScript API
window.QccTwoColumn = {
    // Resize columns dynamically
    resize: function(layoutId, leftWidth, rightWidth) {
        const layout = document.getElementById(layoutId);
        if (layout) {
            layout.style.setProperty('--qcc-left-width', leftWidth);
            layout.style.setProperty('--qcc-right-width', rightWidth);
        }
    },
    
    // Toggle mobile reverse
    toggleMobileReverse: function(layoutId) {
        const layout = document.getElementById(layoutId);
        if (layout) {
            layout.classList.toggle('qcc-reverse-mobile');
        }
    },
    
    // Set loading state
    setLoading: function(layoutId, loading = true) {
        const layout = document.getElementById(layoutId);
        if (layout) {
            if (loading) {
                layout.classList.add('qcc-loading');
            } else {
                layout.classList.remove('qcc-loading');
            }
        }
    },
    
    // Equalize column heights manually
    equalizeHeights: function(layoutId) {
        const layout = document.getElementById(layoutId);
        if (layout && window.innerWidth > 768) {
            const columns = layout.querySelectorAll('.qcc-column:not(.qcc-column-full)');
            let maxHeight = 0;
            
            // Reset heights
            columns.forEach(col => col.style.height = 'auto');
            
            // Find max height
            columns.forEach(col => {
                maxHeight = Math.max(maxHeight, col.offsetHeight);
            });
            
            // Set equal heights
            columns.forEach(col => col.style.height = maxHeight + 'px');
        }
    },
    
    // Responsive breakpoint change
    setBreakpoint: function(layoutId, breakpoint) {
        const layout = document.getElementById(layoutId);
        if (layout) {
            layout.style.setProperty('--qcc-breakpoint', breakpoint);
        }
    }
};

// Auto-equalize heights on window resize
document.addEventListener('DOMContentLoaded', function() {
    const layouts = document.querySelectorAll('.qcc-two-column-layout.qcc-equal-height');
    
    function handleResize() {
        layouts.forEach(layout => {
            if (layout.id) {
                QccTwoColumn.equalizeHeights(layout.id);
            }
        });
    }
    
    // Debounced resize handler
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResize, 150);
    });
    
    // Initial equalization
    handleResize();
});
</script>
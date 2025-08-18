<?php
/**
 * Template: Responsive Grid Layout
 * Flexibles Grid-System für verschiedene Bildschirmgrößen
 * 
 * @param array $data Grid-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$id = $data['id'] ?? 'qcc-responsive-grid';
$items = $data['items'] ?? array();
$columns = $data['columns'] ?? array('xl' => 4, 'lg' => 3, 'md' => 2, 'sm' => 1);
$gap = $data['gap'] ?? '20px';
$min_item_width = $data['min_item_width'] ?? '250px';
$max_item_width = $data['max_item_width'] ?? '1fr';
$auto_fit = $data['auto_fit'] ?? true;
$equal_height = $data['equal_height'] ?? true;
$css_classes = $attributes['css_classes'] ?? '';
$grid_type = $data['grid_type'] ?? 'auto'; // auto, fixed, masonry

// Responsive Breakpoints
$breakpoints = $data['breakpoints'] ?? array(
    'xl' => '1200px',
    'lg' => '992px', 
    'md' => '768px',
    'sm' => '576px'
);

// CSS Custom Properties
$style_vars = array(
    '--qcc-grid-gap' => $gap,
    '--qcc-min-item-width' => $min_item_width,
    '--qcc-max-item-width' => $max_item_width,
    '--qcc-columns-xl' => $columns['xl'] ?? 4,
    '--qcc-columns-lg' => $columns['lg'] ?? 3,
    '--qcc-columns-md' => $columns['md'] ?? 2,
    '--qcc-columns-sm' => $columns['sm'] ?? 1
);

$style_string = '';
foreach ($style_vars as $property => $value) {
    $style_string .= $property . ': ' . $value . '; ';
}
?>

<div id="<?php echo esc_attr($id); ?>" 
     class="qcc-responsive-grid qcc-grid-<?php echo esc_attr($grid_type); ?> <?php echo esc_attr($css_classes); ?>
            <?php echo $auto_fit ? 'qcc-auto-fit' : 'qcc-fixed-columns'; ?>
            <?php echo $equal_height ? 'qcc-equal-height' : ''; ?>"
     style="<?php echo esc_attr($style_string); ?>"
     data-grid-type="<?php echo esc_attr($grid_type); ?>">
     
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $index => $item): ?>
        <div class="qcc-grid-item qcc-grid-item-<?php echo esc_attr($index); ?> 
                    <?php echo isset($item['css_class']) ? esc_attr($item['css_class']) : ''; ?>
                    <?php echo isset($item['span']) ? 'qcc-span-' . esc_attr($item['span']) : ''; ?>"
             data-index="<?php echo esc_attr($index); ?>"
             <?php if (isset($item['span'])): ?>
             style="grid-column: span <?php echo esc_attr($item['span']); ?>;"
             <?php endif; ?>>
             
            <div class="qcc-grid-item-content">
                <?php if (isset($item['header'])): ?>
                <div class="qcc-grid-item-header">
                    <?php echo $item['header']; ?>
                </div>
                <?php endif; ?>
                
                <div class="qcc-grid-item-body">
                    <?php echo $item['content'] ?? ''; ?>
                </div>
                
                <?php if (isset($item['footer'])): ?>
                <div class="qcc-grid-item-footer">
                    <?php echo $item['footer']; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($item['overlay'])): ?>
            <div class="qcc-grid-item-overlay">
                <?php echo $item['overlay']; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Placeholder für leeres Grid -->
        <div class="qcc-grid-placeholder">
            <div class="qcc-placeholder-content">
                <div class="qcc-placeholder-icon">📊</div>
                <div class="qcc-placeholder-text">
                    <?php echo esc_html($translator->get('no_items', 'No items to display')); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
</div>

<style>
/* Base Grid Styles */
.qcc-responsive-grid {
    display: grid;
    gap: var(--qcc-grid-gap, 20px);
    width: 100%;
    box-sizing: border-box;
}

/* Auto-fit Grid (responsive columns based on min-width) */
.qcc-auto-fit {
    grid-template-columns: repeat(auto-fit, minmax(var(--qcc-min-item-width, 250px), var(--qcc-max-item-width, 1fr)));
}

/* Fixed Columns Grid (specific column counts) */
.qcc-fixed-columns {
    grid-template-columns: repeat(var(--qcc-columns-xl, 4), 1fr);
}

/* Grid Items */
.qcc-grid-item {
    position: relative;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.qcc-grid-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Equal Height Items */
.qcc-equal-height .qcc-grid-item {
    display: flex;
    flex-direction: column;
}

.qcc-equal-height .qcc-grid-item-content {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.qcc-equal-height .qcc-grid-item-body {
    flex: 1;
}

/* Grid Item Content */
.qcc-grid-item-content {
    padding: 20px;
    height: 100%;
}

.qcc-grid-item-header {
    margin-bottom: 15px;
    font-weight: 600;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 10px;
}

.qcc-grid-item-body {
    margin-bottom: 15px;
}

.qcc-grid-item-footer {
    margin-top: auto;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

/* Grid Item Overlay */
.qcc-grid-item-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 10;
}

.qcc-grid-item:hover .qcc-grid-item-overlay {
    opacity: 1;
}

/* Spanning Items */
.qcc-span-2 {
    grid-column: span 2;
}

.qcc-span-3 {
    grid-column: span 3;
}

.qcc-span-4 {
    grid-column: span 4;
}

.qcc-span-full {
    grid-column: 1 / -1;
}

/* Responsive Breakpoints */
@media (max-width: 1200px) {
    .qcc-fixed-columns {
        grid-template-columns: repeat(var(--qcc-columns-lg, 3), 1fr);
    }
}

@media (max-width: 992px) {
    .qcc-fixed-columns {
        grid-template-columns: repeat(var(--qcc-columns-md, 2), 1fr);
    }
    
    .qcc-span-3,
    .qcc-span-4 {
        grid-column: span 2;
    }
}

@media (max-width: 768px) {
    .qcc-fixed-columns {
        grid-template-columns: repeat(var(--qcc-columns-sm, 1), 1fr);
    }
    
    .qcc-responsive-grid {
        gap: 15px;
    }
    
    .qcc-span-2,
    .qcc-span-3,
    .qcc-span-4 {
        grid-column: span 1;
    }
    
    .qcc-grid-item-content {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .qcc-responsive-grid {
        gap: 10px;
    }
    
    .qcc-grid-item-content {
        padding: 12px;
    }
}

/* Masonry Grid (requires JavaScript) */
.qcc-grid-masonry {
    column-count: var(--qcc-columns-xl, 4);
    column-gap: var(--qcc-grid-gap, 20px);
    column-fill: balance;
}

.qcc-grid-masonry .qcc-grid-item {
    display: inline-block;
    width: 100%;
    margin-bottom: var(--qcc-grid-gap, 20px);
    break-inside: avoid;
}

@media (max-width: 1200px) {
    .qcc-grid-masonry {
        column-count: var(--qcc-columns-lg, 3);
    }
}

@media (max-width: 992px) {
    .qcc-grid-masonry {
        column-count: var(--qcc-columns-md, 2);
    }
}

@media (max-width: 768px) {
    .qcc-grid-masonry {
        column-count: var(--qcc-columns-sm, 1);
    }
}

/* Grid Loading State */
.qcc-responsive-grid.qcc-loading .qcc-grid-item {
    opacity: 0.6;
    pointer-events: none;
}

.qcc-responsive-grid.qcc-loading .qcc-grid-item::after {
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

/* Empty Grid Placeholder */
.qcc-grid-placeholder {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.qcc-placeholder-icon {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.qcc-placeholder-text {
    font-size: 16px;
    font-weight: 500;
}

/* Animation for Items */
.qcc-grid-item {
    animation: qcc-grid-item-fade-in 0.3s ease-out;
}

@keyframes qcc-grid-item-fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Staggered Animation */
.qcc-grid-item:nth-child(1) { animation-delay: 0.1s; }
.qcc-grid-item:nth-child(2) { animation-delay: 0.2s; }
.qcc-grid-item:nth-child(3) { animation-delay: 0.3s; }
.qcc-grid-item:nth-child(4) { animation-delay: 0.4s; }
.qcc-grid-item:nth-child(5) { animation-delay: 0.5s; }
.qcc-grid-item:nth-child(6) { animation-delay: 0.6s; }

/* Print Styles */
@media print {
    .qcc-responsive-grid {
        display: block;
        columns: 2;
        column-gap: 20px;
    }
    
    .qcc-grid-item {
        break-inside: avoid;
        margin-bottom: 20px;
        box-shadow: none;
        border: 1px solid #ccc;
    }
    
    .qcc-grid-item-overlay {
        display: none;
    }
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
    .qcc-grid-item {
        border: 2px solid #000;
    }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .qcc-grid-item {
        animation: none;
        transition: none;
    }
    
    .qcc-grid-item:hover {
        transform: none;
    }
}

/* Accessibility */
.qcc-grid-item:focus-within {
    outline: 2px solid #449775;
    outline-offset: 2px;
}

/* Dark Theme */
.qcc-theme-dark .qcc-grid-item {
    background: #4a5568;
    border-color: #718096;
    color: #e2e8f0;
}

.qcc-theme-dark .qcc-grid-item-header,
.qcc-theme-dark .qcc-grid-item-footer {
    border-color: #718096;
}
</style>

<script>
// Responsive Grid JavaScript API
window.QccGrid = {
    // Initialize masonry layout
    initMasonry: function(gridId) {
        const grid = document.getElementById(gridId);
        if (grid && grid.classList.contains('qcc-grid-masonry')) {
            // Simple masonry implementation
            this.repositionMasonryItems(gridId);
        }
    },
    
    // Reposition masonry items
    repositionMasonryItems: function(gridId) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        
        const items = grid.querySelectorAll('.qcc-grid-item');
        const gap = parseInt(getComputedStyle(grid).getPropertyValue('--qcc-grid-gap')) || 20;
        
        // Reset positions
        items.forEach(item => {
            item.style.transform = '';
        });
        
        // Calculate positions (simplified masonry)
        let columnHeights = [];
        const columnCount = parseInt(getComputedStyle(grid).columnCount);
        
        for (let i = 0; i < columnCount; i++) {
            columnHeights[i] = 0;
        }
        
        items.forEach((item, index) => {
            const columnIndex = index % columnCount;
            const yPos = columnHeights[columnIndex];
            
            item.style.transform = `translateY(${yPos}px)`;
            columnHeights[columnIndex] += item.offsetHeight + gap;
        });
    },
    
    // Add new item to grid
    addItem: function(gridId, itemHtml, position = 'end') {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        
        const newItem = document.createElement('div');
        newItem.className = 'qcc-grid-item';
        newItem.innerHTML = itemHtml;
        
        if (position === 'start') {
            grid.insertBefore(newItem, grid.firstChild);
        } else {
            grid.appendChild(newItem);
        }
        
        // Reinitialize masonry if needed
        if (grid.classList.contains('qcc-grid-masonry')) {
            setTimeout(() => this.repositionMasonryItems(gridId), 100);
        }
    },
    
    // Remove item from grid
    removeItem: function(gridId, itemIndex) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        
        const item = grid.querySelector(`.qcc-grid-item-${itemIndex}`);
        if (item) {
            item.style.animation = 'qcc-grid-item-fade-out 0.3s ease-in forwards';
            setTimeout(() => {
                item.remove();
                if (grid.classList.contains('qcc-grid-masonry')) {
                    this.repositionMasonryItems(gridId);
                }
            }, 300);
        }
    },
    
    // Set loading state
    setLoading: function(gridId, loading = true) {
        const grid = document.getElementById(gridId);
        if (grid) {
            if (loading) {
                grid.classList.add('qcc-loading');
            } else {
                grid.classList.remove('qcc-loading');
            }
        }
    },
    
    // Update grid columns
    updateColumns: function(gridId, columns) {
        const grid = document.getElementById(gridId);
        if (grid) {
            Object.keys(columns).forEach(breakpoint => {
                grid.style.setProperty(`--qcc-columns-${breakpoint}`, columns[breakpoint]);
            });
            
            if (grid.classList.contains('qcc-grid-masonry')) {
                setTimeout(() => this.repositionMasonryItems(gridId), 100);
            }
        }
    },
    
    // Filter grid items
    filterItems: function(gridId, filterFn) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        
        const items = grid.querySelectorAll('.qcc-grid-item');
        items.forEach((item, index) => {
            const show = filterFn(item, index);
            if (show) {
                item.style.display = '';
                item.style.animation = 'qcc-grid-item-fade-in 0.3s ease-out';
            } else {
                item.style.display = 'none';
            }
        });
        
        if (grid.classList.contains('qcc-grid-masonry')) {
            setTimeout(() => this.repositionMasonryItems(gridId), 100);
        }
    },
    
    // Sort grid items
    sortItems: function(gridId, compareFn) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        
        const items = Array.from(grid.querySelectorAll('.qcc-grid-item'));
        items.sort(compareFn);
        
        items.forEach(item => grid.appendChild(item));
        
        if (grid.classList.contains('qcc-grid-masonry')) {
            setTimeout(() => this.repositionMasonryItems(gridId), 100);
        }
    }
};

// Auto-initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all masonry grids
    document.querySelectorAll('.qcc-grid-masonry').forEach(grid => {
        if (grid.id) {
            QccGrid.initMasonry(grid.id);
        }
    });
    
    // Handle window resize for masonry
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            document.querySelectorAll('.qcc-grid-masonry').forEach(grid => {
                if (grid.id) {
                    QccGrid.repositionMasonryItems(grid.id);
                }
            });
        }, 150);
    });
});

// Additional animations
const additionalStyles = `
@keyframes qcc-grid-item-fade-out {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-20px);
    }
}
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
</script>
<?php
/**
 * Template: Control Button Component
 * Wiederverwendbare Button-Komponente für Controls (Export, Reset, Calculate, etc.)
 * 
 * @param array $data Button-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$label = $data['label'] ?? 'Button';
$action = $data['action'] ?? '';
$type = $data['type'] ?? 'primary'; // primary, secondary, export, danger, success
$size = $data['size'] ?? 'medium'; // small, medium, large
$icon = $data['icon'] ?? '';
$id = $data['id'] ?? uniqid('qcc-btn-');
$disabled = $data['disabled'] ?? false;
$loading = $data['loading'] ?? false;
$css_classes = $attributes['css_classes'] ?? '';

// Button-Typ CSS-Klassen
$type_class = '';
switch ($type) {
    case 'primary':
        $type_class = 'qcc-btn-primary';
        break;
    case 'secondary':
        $type_class = 'qcc-btn-secondary';
        break;
    case 'export':
        $type_class = 'qcc-btn-export';
        break;
    case 'danger':
        $type_class = 'qcc-btn-danger';
        break;
    case 'success':
        $type_class = 'qcc-btn-success';
        break;
    default:
        $type_class = 'qcc-btn-primary';
}

// Button-Größe CSS-Klassen
$size_class = '';
switch ($size) {
    case 'small':
        $size_class = 'qcc-btn-small';
        break;
    case 'large':
        $size_class = 'qcc-btn-large';
        break;
    default:
        $size_class = 'qcc-btn-medium';
}

// HTML-Attribute sammeln
$html_attributes = array();
if ($disabled || $loading) {
    $html_attributes[] = 'disabled="disabled"';
}
if ($action) {
    $html_attributes[] = 'data-action="' . esc_attr($action) . '"';
}
if (isset($data['data']) && is_array($data['data'])) {
    foreach ($data['data'] as $key => $value) {
        $html_attributes[] = 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
}
if (isset($data['onclick'])) {
    $html_attributes[] = 'onclick="' . esc_attr($data['onclick']) . '"';
}
?>

<button type="<?php echo esc_attr($data['button_type'] ?? 'button'); ?>" 
        id="<?php echo esc_attr($id); ?>"
        class="qcc-btn <?php echo esc_attr($type_class . ' ' . $size_class . ' ' . $css_classes); ?> <?php echo $loading ? 'qcc-btn-loading' : ''; ?>"
        <?php echo implode(' ', $html_attributes); ?>>
        
    <?php if ($loading): ?>
    <span class="qcc-btn-spinner">
        <svg class="qcc-spinner" viewBox="0 0 50 50">
            <circle class="qcc-spinner-path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"/>
        </svg>
    </span>
    <?php endif; ?>
    
    <?php if ($icon && !$loading): ?>
    <span class="qcc-btn-icon">
        <?php if (strpos($icon, 'fa-') === 0 || strpos($icon, 'fas ') === 0): ?>
            <i class="<?php echo esc_attr($icon); ?>"></i>
        <?php elseif (strpos($icon, '<svg') === 0): ?>
            <?php echo $icon; // SVG direkt ausgeben ?>
        <?php else: ?>
            <span class="qcc-btn-emoji"><?php echo esc_html($icon); ?></span>
        <?php endif; ?>
    </span>
    <?php endif; ?>
    
    <span class="qcc-btn-text">
        <?php echo esc_html($label); ?>
    </span>
    
    <?php if (isset($data['badge'])): ?>
    <span class="qcc-btn-badge">
        <?php echo esc_html($data['badge']); ?>
    </span>
    <?php endif; ?>
    
    <?php if (isset($data['dropdown']) && $data['dropdown']): ?>
    <span class="qcc-btn-dropdown-arrow">
        <svg width="12" height="12" viewBox="0 0 12 12">
            <path d="M6 8L2 4h8l-4 4z" fill="currentColor"/>
        </svg>
    </span>
    <?php endif; ?>
</button>

<?php if (isset($data['dropdown']) && $data['dropdown'] && isset($data['dropdown_items'])): ?>
<div class="qcc-btn-dropdown-menu" id="<?php echo esc_attr($id); ?>-dropdown">
    <?php foreach ($data['dropdown_items'] as $item): ?>
    <button type="button" 
            class="qcc-btn-dropdown-item"
            data-action="<?php echo esc_attr($item['action'] ?? ''); ?>"
            <?php if (isset($item['data'])): ?>
                <?php foreach ($item['data'] as $key => $value): ?>
                    data-<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
                <?php endforeach; ?>
            <?php endif; ?>>
        <?php if (isset($item['icon'])): ?>
        <span class="qcc-dropdown-item-icon"><?php echo esc_html($item['icon']); ?></span>
        <?php endif; ?>
        <?php echo esc_html($item['label']); ?>
    </button>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.qcc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-family: inherit;
    position: relative;
    overflow: hidden;
}

.qcc-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

.qcc-btn:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(68, 151, 117, 0.3);
}

/* Button Größen */
.qcc-btn-small {
    padding: 8px 12px;
    font-size: 12px;
}

.qcc-btn-medium {
    padding: 12px 20px;
    font-size: 14px;
}

.qcc-btn-large {
    padding: 16px 28px;
    font-size: 16px;
}

/* Button Typen */
.qcc-btn-primary {
    background-color: #449775;
    color: white;
}

.qcc-btn-primary:hover:not(:disabled) {
    background-color: #357a61;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(68, 151, 117, 0.3);
}

.qcc-btn-secondary {
    background-color: #6c757d;
    color: white;
}

.qcc-btn-secondary:hover:not(:disabled) {
    background-color: #545b62;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
}

.qcc-btn-export {
    background-color: #141C14;
    color: white;
}

.qcc-btn-export:hover:not(:disabled) {
    background-color: #000;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(20, 28, 20, 0.3);
}

.qcc-btn-danger {
    background-color: #dc3545;
    color: white;
}

.qcc-btn-danger:hover:not(:disabled) {
    background-color: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.qcc-btn-success {
    background-color: #28a745;
    color: white;
}

.qcc-btn-success:hover:not(:disabled) {
    background-color: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

/* Icon Styling */
.qcc-btn-icon {
    display: flex;
    align-items: center;
}

.qcc-btn-emoji {
    font-size: 1.2em;
}

/* Loading State */
.qcc-btn-loading {
    pointer-events: none;
}

.qcc-btn-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
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
    background-color: rgba(255, 255, 255, 0.3);
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    margin-left: 4px;
}

/* Dropdown */
.qcc-btn-dropdown-arrow {
    margin-left: 4px;
    transition: transform 0.3s ease;
}

.qcc-btn[aria-expanded="true"] .qcc-btn-dropdown-arrow {
    transform: rotate(180deg);
}

.qcc-btn-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 150px;
    z-index: 1000;
    display: none;
}

.qcc-btn-dropdown-menu.qcc-show {
    display: block;
}

.qcc-btn-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 15px;
    border: none;
    background: none;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.qcc-btn-dropdown-item:hover {
    background-color: #f8f9fa;
}

.qcc-btn-dropdown-item:first-child {
    border-radius: 6px 6px 0 0;
}

.qcc-btn-dropdown-item:last-child {
    border-radius: 0 0 6px 6px;
}

.qcc-dropdown-item-icon {
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-btn-medium {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .qcc-btn-large {
        padding: 14px 24px;
        font-size: 15px;
    }
    
    .qcc-btn-text {
        display: none;
    }
    
    .qcc-btn.qcc-btn-text-mobile .qcc-btn-text {
        display: inline;
    }
}

/* Ripple Effect */
.qcc-btn::after {
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
}

.qcc-btn:active::after {
    width: 300px;
    height: 300px;
}
</style>

<script>
// Dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownButtons = document.querySelectorAll('.qcc-btn[data-action*="dropdown"]');
    
    dropdownButtons.forEach(button => {
        const dropdown = document.getElementById(button.id + '-dropdown');
        if (dropdown) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = dropdown.classList.contains('qcc-show');
                
                // Close all dropdowns
                document.querySelectorAll('.qcc-btn-dropdown-menu').forEach(menu => {
                    menu.classList.remove('qcc-show');
                });
                
                // Toggle current dropdown
                if (!isOpen) {
                    dropdown.classList.add('qcc-show');
                    button.setAttribute('aria-expanded', 'true');
                } else {
                    button.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.qcc-btn-dropdown-menu').forEach(menu => {
            menu.classList.remove('qcc-show');
        });
        document.querySelectorAll('.qcc-btn[aria-expanded="true"]').forEach(btn => {
            btn.setAttribute('aria-expanded', 'false');
        });
    });
});
</script>
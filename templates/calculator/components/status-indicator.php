<?php
/**
 * Template: Status Indicator Component
 * Status-Anzeige für Validation, Erfolg, Fehler, Warnings, etc.
 * 
 * @param array $data Status-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$status = $data['status'] ?? 'info'; // success, error, warning, info, loading
$message = $data['message'] ?? '';
$title = $data['title'] ?? '';
$id = $data['id'] ?? uniqid('qcc-status-');
$dismissible = $data['dismissible'] ?? false;
$auto_hide = $data['auto_hide'] ?? false;
$auto_hide_delay = $data['auto_hide_delay'] ?? 5000;
$icon = $data['icon'] ?? '';
$css_classes = $attributes['css_classes'] ?? '';
$visible = $data['visible'] ?? true;

// Status-spezifische Icons (falls nicht überschrieben)
if (empty($icon)) {
    switch ($status) {
        case 'success':
            $icon = '✓';
            break;
        case 'error':
            $icon = '✕';
            break;
        case 'warning':
            $icon = '⚠';
            break;
        case 'info':
            $icon = 'ℹ';
            break;
        case 'loading':
            $icon = '⟳';
            break;
    }
}

// Status-spezifische CSS-Klassen
$status_class = 'qcc-status-' . $status;

// Sichtbarkeit
$visibility_style = $visible ? '' : 'display: none;';
?>

<div id="<?php echo esc_attr($id); ?>" 
     class="qcc-status-indicator <?php echo esc_attr($status_class . ' ' . $css_classes); ?>"
     style="<?php echo esc_attr($visibility_style); ?>"
     data-status="<?php echo esc_attr($status); ?>"
     <?php if ($auto_hide): ?>
     data-auto-hide="<?php echo esc_attr($auto_hide_delay); ?>"
     <?php endif; ?>
     role="alert"
     aria-live="polite">
     
    <div class="qcc-status-content">
        <?php if ($icon): ?>
        <div class="qcc-status-icon">
            <?php if ($status === 'loading'): ?>
            <div class="qcc-status-spinner">
                <svg class="qcc-spinner" viewBox="0 0 50 50">
                    <circle class="qcc-spinner-path" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="2" stroke-miterlimit="10"/>
                </svg>
            </div>
            <?php elseif (strpos($icon, 'fa-') === 0 || strpos($icon, 'fas ') === 0): ?>
            <i class="<?php echo esc_attr($icon); ?>"></i>
            <?php elseif (strpos($icon, '<svg') === 0): ?>
            <?php echo $icon; // SVG direkt ausgeben ?>
            <?php else: ?>
            <span class="qcc-status-emoji"><?php echo esc_html($icon); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="qcc-status-text">
            <?php if ($title): ?>
            <div class="qcc-status-title">
                <?php echo esc_html($title); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
            <div class="qcc-status-message">
                <?php echo esc_html($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($data['details']) && is_array($data['details'])): ?>
            <div class="qcc-status-details">
                <ul class="qcc-status-details-list">
                    <?php foreach ($data['details'] as $detail): ?>
                    <li><?php echo esc_html($detail); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($dismissible): ?>
        <button type="button" 
                class="qcc-status-dismiss" 
                aria-label="<?php echo esc_attr($translator->get('dismiss_status', 'Dismiss')); ?>"
                onclick="this.closest('.qcc-status-indicator').style.display='none';">
            <svg width="14" height="14" viewBox="0 0 14 14">
                <path d="M13 1L1 13M1 1l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <?php endif; ?>
    </div>
    
    <?php if (isset($data['actions']) && is_array($data['actions'])): ?>
    <div class="qcc-status-actions">
        <?php foreach ($data['actions'] as $action): ?>
        <button type="button" 
                class="qcc-status-action-btn <?php echo esc_attr($action['class'] ?? ''); ?>"
                data-action="<?php echo esc_attr($action['action'] ?? ''); ?>"
                <?php if (isset($action['data'])): ?>
                    <?php foreach ($action['data'] as $key => $value): ?>
                        data-<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
                    <?php endforeach; ?>
                <?php endif; ?>>
            <?php if (isset($action['icon'])): ?>
            <span class="qcc-action-icon"><?php echo esc_html($action['icon']); ?></span>
            <?php endif; ?>
            <?php echo esc_html($action['label']); ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($data['progress'])): ?>
    <div class="qcc-status-progress">
        <div class="qcc-progress-bar">
            <div class="qcc-progress-fill" 
                 style="width: <?php echo esc_attr($data['progress']['value']); ?>%"
                 data-progress="<?php echo esc_attr($data['progress']['value']); ?>">
            </div>
        </div>
        <?php if (isset($data['progress']['label'])): ?>
        <div class="qcc-progress-label">
            <?php echo esc_html($data['progress']['label']); ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.qcc-status-indicator {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    position: relative;
    animation: qcc-status-slide-in 0.3s ease-out;
}

@keyframes qcc-status-slide-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.qcc-status-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.qcc-status-icon {
    flex-shrink: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
}

.qcc-status-text {
    flex: 1;
}

.qcc-status-title {
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 14px;
}

.qcc-status-message {
    font-size: 14px;
    line-height: 1.4;
}

.qcc-status-details {
    margin-top: 8px;
}

.qcc-status-details-list {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
}

.qcc-status-details-list li {
    margin-bottom: 4px;
}

.qcc-status-dismiss {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.3s ease;
    opacity: 0.6;
    flex-shrink: 0;
}

.qcc-status-dismiss:hover {
    opacity: 1;
    background-color: rgba(0, 0, 0, 0.1);
}

.qcc-status-actions {
    margin-top: 15px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.qcc-status-action-btn {
    padding: 6px 12px;
    border: 1px solid currentColor;
    background: transparent;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 4px;
}

.qcc-status-action-btn:hover {
    background: currentColor;
    color: white;
}

/* Status-spezifische Styles */
.qcc-status-success {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.qcc-status-error {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.qcc-status-warning {
    background-color: #fff3cd;
    color: #856404;
    border-color: #ffeaa7;
}

.qcc-status-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
}

.qcc-status-loading {
    background-color: #f8f9fa;
    color: #495057;
    border-color: #dee2e6;
}

/* Spinner für Loading Status */
.qcc-status-spinner {
    width: 20px;
    height: 20px;
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

/* Progress Bar */
.qcc-status-progress {
    margin-top: 12px;
}

.qcc-progress-bar {
    width: 100%;
    height: 6px;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 3px;
    overflow: hidden;
}

.qcc-progress-fill {
    height: 100%;
    background-color: currentColor;
    border-radius: 3px;
    transition: width 0.3s ease;
    opacity: 0.8;
}

.qcc-progress-label {
    font-size: 12px;
    margin-top: 4px;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-status-indicator {
        padding: 12px 16px;
    }
    
    .qcc-status-content {
        gap: 8px;
    }
    
    .qcc-status-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .qcc-status-action-btn {
        justify-content: center;
    }
}

/* Auto-hide Animation */
.qcc-status-indicator[data-auto-hide] {
    position: relative;
    overflow: hidden;
}

.qcc-status-indicator[data-auto-hide]::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background-color: currentColor;
    opacity: 0.3;
    animation: qcc-auto-hide-progress var(--auto-hide-duration, 5s) linear;
}

@keyframes qcc-auto-hide-progress {
    from { width: 100%; }
    to { width: 0%; }
}

.qcc-status-indicator.qcc-hiding {
    animation: qcc-status-slide-out 0.3s ease-in forwards;
}

@keyframes qcc-status-slide-out {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}

/* Accessibility */
.qcc-status-indicator[role="alert"] {
    position: relative;
}

.qcc-status-indicator:focus-within {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

/* Dark Theme Support */
@media (prefers-color-scheme: dark) {
    .qcc-status-success {
        background-color: rgba(40, 167, 69, 0.2);
        color: #90ee90;
        border-color: rgba(40, 167, 69, 0.3);
    }
    
    .qcc-status-error {
        background-color: rgba(220, 53, 69, 0.2);
        color: #ffb3ba;
        border-color: rgba(220, 53, 69, 0.3);
    }
    
    .qcc-status-warning {
        background-color: rgba(255, 193, 7, 0.2);
        color: #ffeb9c;
        border-color: rgba(255, 193, 7, 0.3);
    }
    
    .qcc-status-info {
        background-color: rgba(23, 162, 184, 0.2);
        color: #9ce8f5;
        border-color: rgba(23, 162, 184, 0.3);
    }
}
</style>

<script>
// Auto-hide functionality
document.addEventListener('DOMContentLoaded', function() {
    const autoHideStatuses = document.querySelectorAll('.qcc-status-indicator[data-auto-hide]');
    
    autoHideStatuses.forEach(status => {
        const delay = parseInt(status.dataset.autoHide) || 5000;
        
        // Set CSS variable for animation duration
        status.style.setProperty('--auto-hide-duration', delay + 'ms');
        
        setTimeout(() => {
            status.classList.add('qcc-hiding');
            setTimeout(() => {
                status.style.display = 'none';
            }, 300); // Animation duration
        }, delay);
    });
});

// Status API für JavaScript
window.QccStatus = {
    show: function(id, options = {}) {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'block';
            element.classList.remove('qcc-hiding');
            
            // Update content if provided
            if (options.message) {
                const messageEl = element.querySelector('.qcc-status-message');
                if (messageEl) messageEl.textContent = options.message;
            }
            
            if (options.title) {
                const titleEl = element.querySelector('.qcc-status-title');
                if (titleEl) titleEl.textContent = options.title;
            }
            
            if (options.status) {
                element.className = element.className.replace(/qcc-status-\w+/, 'qcc-status-' + options.status);
                element.dataset.status = options.status;
            }
            
            // Auto-hide if specified
            if (options.autoHide) {
                const delay = options.autoHideDelay || 5000;
                element.dataset.autoHide = delay;
                element.style.setProperty('--auto-hide-duration', delay + 'ms');
                
                setTimeout(() => {
                    this.hide(id);
                }, delay);
            }
        }
    },
    
    hide: function(id) {
        const element = document.getElementById(id);
        if (element) {
            element.classList.add('qcc-hiding');
            setTimeout(() => {
                element.style.display = 'none';
            }, 300);
        }
    },
    
    update: function(id, options = {}) {
        this.show(id, options);
    },
    
    updateProgress: function(id, progress, label = '') {
        const element = document.getElementById(id);
        if (element) {
            const progressFill = element.querySelector('.qcc-progress-fill');
            const progressLabel = element.querySelector('.qcc-progress-label');
            
            if (progressFill) {
                progressFill.style.width = progress + '%';
                progressFill.dataset.progress = progress;
            }
            
            if (progressLabel && label) {
                progressLabel.textContent = label;
            }
        }
    }
};
</script>
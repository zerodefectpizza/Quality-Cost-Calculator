<?php
/**
 * QCC Header Section Template
 * 
 * Calculator Header mit Titel, Beschreibung und globalen Controls
 * Responsive Design mit Language/Currency-Switching
 * 
 * Template-Pfad: templates/calculator/sections/header-section.php
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

// Section-Daten vorbereiten
$section_id = $data['id'] ?? $helpers['generate_section_id']($data);
$title = $data['title'] ?? $translator->get('quality_cost_calculator');
$subtitle = $data['subtitle'] ?? $translator->get('calculate_cogq_copq_costs');
$description = $data['description'] ?? '';
$show_controls = $data['show_controls'] ?? true;
$show_progress = $data['show_progress'] ?? false;
$current_step = $data['current_step'] ?? 1;
$total_steps = $data['total_steps'] ?? 3;

// Language und Currency Settings
$current_language = $data['language'] ?? 'en';
$current_currency = $data['currency'] ?? 'EUR';
$current_unit = $data['unit'] ?? 'billions';

$available_languages = $data['available_languages'] ?? array(
    'en' => 'English',
    'de' => 'Deutsch', 
    'fr' => 'Français',
    'es' => 'Español',
    'zh' => '中文'
);

$available_currencies = $data['available_currencies'] ?? array(
    'EUR' => '€ Euro',
    'USD' => '$ US Dollar',
    'CNY' => '¥ Renminbi',
    'GBP' => '£ British Pound'
);

$available_units = $data['available_units'] ?? array(
    'millions' => $translator->get('millions'),
    'billions' => $translator->get('billions')
);

// CSS-Klassen für Header
$header_classes = $helpers['build_css_classes'](
    array('qcc-header-section'),
    array(
        'qcc-header-section--with-controls' => $show_controls,
        'qcc-header-section--with-progress' => $show_progress,
        'qcc-header-section--compact' => $data['compact'] ?? false,
        'qcc-header-section--centered' => $data['centered'] ?? false,
        'qcc-header-section--' . ($data['style'] ?? 'default') => true
    )
);

// Progress-Berechnung
$progress_percentage = $show_progress ? round(($current_step / $total_steps) * 100) : 0;
?>

<!-- QCC Header Section Start -->
<header id="<?php echo esc_attr($section_id); ?>" 
        class="<?php echo esc_attr($header_classes); ?>"
        role="banner"
        aria-label="<?php echo esc_attr($translator->get('calculator_header')); ?>">

    <?php if ($show_progress): ?>
    <!-- Progress Indicator -->
    <div class="qcc-header-progress" 
         role="progressbar" 
         aria-valuenow="<?php echo esc_attr($current_step); ?>"
         aria-valuemin="1" 
         aria-valuemax="<?php echo esc_attr($total_steps); ?>"
         aria-label="<?php echo esc_attr($translator->get('calculation_progress')); ?>">
        
        <div class="qcc-progress-bar">
            <div class="qcc-progress-fill" 
                 style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
        </div>
        
        <div class="qcc-progress-text">
            <?php echo $helpers['escape'](sprintf(
                $translator->get('step_x_of_y'), 
                $current_step, 
                $total_steps
            )); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header Content -->
    <div class="qcc-header-content">
        
        <!-- Left Side: Title & Description -->
        <div class="qcc-header-main">
            
            <?php if (!empty($data['logo_url'])): ?>
            <div class="qcc-header-logo">
                <img src="<?php echo esc_url($data['logo_url']); ?>" 
                     alt="<?php echo esc_attr($data['logo_alt'] ?? $translator->get('calculator_logo')); ?>"
                     class="qcc-logo-image">
            </div>
            <?php endif; ?>

            <div class="qcc-header-text">
                <h1 class="qcc-header-title">
                    <?php echo $helpers['escape']($title); ?>
                    
                    <?php if (!empty($data['beta']) && $data['beta']): ?>
                    <span class="qcc-beta-badge" aria-label="<?php echo esc_attr($translator->get('beta_version')); ?>">
                        Beta
                    </span>
                    <?php endif; ?>
                </h1>

                <?php if (!empty($subtitle)): ?>
                <p class="qcc-header-subtitle">
                    <?php echo $helpers['escape']($subtitle); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($description)): ?>
                <div class="qcc-header-description">
                    <?php echo wp_kses_post($description); ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <?php if ($show_controls): ?>
        <!-- Right Side: Global Controls -->
        <div class="qcc-header-controls">
            
            <!-- Language Selector -->
            <div class="qcc-control-group qcc-control-group--language">
                <label for="qcc-language-select" class="qcc-control-label">
                    <?php echo $helpers['escape']($translator->get('language')); ?>
                </label>
                
                <select id="qcc-language-select" 
                        class="qcc-control-select"
                        name="language"
                        onchange="QCC.changeLanguage(this.value)"
                        aria-label="<?php echo esc_attr($translator->get('select_language')); ?>">
                    <?php foreach ($available_languages as $lang_code => $lang_name): ?>
                    <option value="<?php echo esc_attr($lang_code); ?>" 
                            <?php selected($current_language, $lang_code); ?>>
                        <?php echo $helpers['escape']($lang_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Currency Selector -->
            <div class="qcc-control-group qcc-control-group--currency">
                <label for="qcc-currency-select" class="qcc-control-label">
                    <?php echo $helpers['escape']($translator->get('currency')); ?>
                </label>
                
                <select id="qcc-currency-select" 
                        class="qcc-control-select"
                        name="currency"
                        onchange="QCC.changeCurrency(this.value)"
                        aria-label="<?php echo esc_attr($translator->get('select_currency')); ?>">
                    <?php foreach ($available_currencies as $curr_code => $curr_name): ?>
                    <option value="<?php echo esc_attr($curr_code); ?>" 
                            <?php selected($current_currency, $curr_code); ?>>
                        <?php echo $helpers['escape']($curr_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Unit Selector -->
            <div class="qcc-control-group qcc-control-group--unit">
                <label for="qcc-unit-select" class="qcc-control-label">
                    <?php echo $helpers['escape']($translator->get('unit')); ?>
                </label>
                
                <select id="qcc-unit-select" 
                        class="qcc-control-select"
                        name="unit"
                        onchange="QCC.changeUnit(this.value)"
                        aria-label="<?php echo esc_attr($translator->get('select_unit')); ?>">
                    <?php foreach ($available_units as $unit_code => $unit_name): ?>
                    <option value="<?php echo esc_attr($unit_code); ?>" 
                            <?php selected($current_unit, $unit_code); ?>>
                        <?php echo $helpers['escape']($unit_name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($data['show_help_button'] ?? true): ?>
            <!-- Help Button -->
            <div class="qcc-control-group qcc-control-group--help">
                <?php 
                echo $template_manager->render('components/control-button', array(
                    'type' => 'help',
                    'icon' => 'help-circle',
                    'text' => $translator->get('help'),
                    'onclick' => 'QCC.showHelp()',
                    'aria_label' => $translator->get('show_help')
                ));
                ?>
            </div>
            <?php endif; ?>

            <?php if ($data['show_export_button'] ?? false): ?>
            <!-- Export Button -->
            <div class="qcc-control-group qcc-control-group--export">
                <?php 
                echo $template_manager->render('components/control-button', array(
                    'type' => 'export',
                    'icon' => 'download',
                    'text' => $translator->get('export'),
                    'onclick' => 'QCC.showExportDialog()',
                    'aria_label' => $translator->get('export_results')
                ));
                ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div>

    <?php if (!empty($data['notifications'])): ?>
    <!-- Header Notifications -->
    <div class="qcc-header-notifications">
        <?php foreach ($data['notifications'] as $notification): ?>
        <div class="qcc-notification qcc-notification--<?php echo esc_attr($notification['type'] ?? 'info'); ?>"
             role="alert"
             aria-live="polite">
            
            <?php if (!empty($notification['icon'])): ?>
            <span class="qcc-notification-icon" aria-hidden="true">
                <?php echo $helpers['escape']($notification['icon']); ?>
            </span>
            <?php endif; ?>
            
            <div class="qcc-notification-content">
                <?php if (!empty($notification['title'])): ?>
                <div class="qcc-notification-title">
                    <?php echo $helpers['escape']($notification['title']); ?>
                </div>
                <?php endif; ?>
                
                <div class="qcc-notification-message">
                    <?php echo $helpers['escape']($notification['message']); ?>
                </div>
            </div>
            
            <?php if ($notification['dismissible'] ?? false): ?>
            <button type="button" 
                    class="qcc-notification-dismiss"
                    onclick="this.parentElement.style.display='none'"
                    aria-label="<?php echo esc_attr($translator->get('dismiss_notification')); ?>">
                ×
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($data['breadcrumbs'])): ?>
    <!-- Breadcrumb Navigation -->
    <nav class="qcc-breadcrumbs" aria-label="<?php echo esc_attr($translator->get('breadcrumb_navigation')); ?>">
        <ol class="qcc-breadcrumb-list">
            <?php foreach ($data['breadcrumbs'] as $index => $crumb): ?>
            <li class="qcc-breadcrumb-item">
                <?php if (!empty($crumb['url']) && !($crumb['current'] ?? false)): ?>
                <a href="<?php echo esc_url($crumb['url']); ?>" 
                   class="qcc-breadcrumb-link">
                    <?php echo $helpers['escape']($crumb['title']); ?>
                </a>
                <?php else: ?>
                <span class="qcc-breadcrumb-current" aria-current="page">
                    <?php echo $helpers['escape']($crumb['title']); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($index < count($data['breadcrumbs']) - 1): ?>
                <span class="qcc-breadcrumb-separator" aria-hidden="true">›</span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

</header>
<!-- QCC Header Section End -->

<!-- Header Section Styles -->
<style>
.qcc-header-section {
    background: var(--qcc-header-bg, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
    color: var(--qcc-header-color, #ffffff);
    padding: var(--qcc-header-padding, 2rem 0);
    margin-bottom: var(--qcc-header-margin, 2rem);
    border-radius: var(--qcc-header-radius, 12px);
    box-shadow: var(--qcc-header-shadow, 0 4px 20px rgba(0,0,0,0.1));
}

.qcc-header-section--compact {
    --qcc-header-padding: 1.5rem 0;
}

.qcc-header-section--centered .qcc-header-content {
    text-align: center;
    flex-direction: column;
    align-items: center;
}

.qcc-header-progress {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0 0 12px 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.qcc-progress-bar {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.qcc-progress-fill {
    background: var(--qcc-color-success, #48bb78);
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.qcc-progress-text {
    font-size: 0.875rem;
    text-align: center;
    opacity: 0.9;
}

.qcc-header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.qcc-header-main {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.qcc-header-logo img {
    max-height: 60px;
    width: auto;
}

.qcc-header-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.qcc-beta-badge {
    background: var(--qcc-color-warning, #ed8936);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.qcc-header-subtitle {
    font-size: 1.25rem;
    margin: 0 0 1rem;
    opacity: 0.9;
    font-weight: 300;
}

.qcc-header-description {
    font-size: 1rem;
    opacity: 0.8;
    line-height: 1.6;
}

.qcc-header-controls {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
}

.qcc-control-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 120px;
}

.qcc-control-label {
    font-size: 0.875rem;
    font-weight: 500;
    opacity: 0.9;
}

.qcc-control-select {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    backdrop-filter: blur(10px);
}

.qcc-control-select:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.5);
}

.qcc-control-select option {
    background: var(--qcc-color-bg, #ffffff);
    color: var(--qcc-color-text, #333333);
}

.qcc-header-notifications {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.qcc-notification {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.qcc-notification--warning {
    background: rgba(237, 137, 54, 0.2);
    border-color: rgba(237, 137, 54, 0.3);
}

.qcc-notification--error {
    background: rgba(229, 62, 62, 0.2);
    border-color: rgba(229, 62, 62, 0.3);
}

.qcc-notification--success {
    background: rgba(72, 187, 120, 0.2);
    border-color: rgba(72, 187, 120, 0.3);
}

.qcc-notification-content {
    flex: 1;
}

.qcc-notification-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.qcc-notification-dismiss {
    background: none;
    border: none;
    color: inherit;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    opacity: 0.7;
}

.qcc-notification-dismiss:hover {
    opacity: 1;
}

.qcc-breadcrumbs {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.qcc-breadcrumb-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.qcc-breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qcc-breadcrumb-link {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: color 0.2s ease;
}

.qcc-breadcrumb-link:hover {
    color: white;
    text-decoration: underline;
}

.qcc-breadcrumb-current {
    color: white;
    font-weight: 500;
}

.qcc-breadcrumb-separator {
    opacity: 0.6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-header-content {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .qcc-header-main {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .qcc-header-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .qcc-header-controls {
        justify-content: center;
        width: 100%;
    }
    
    .qcc-control-group {
        min-width: 100px;
    }
}

@media (max-width: 480px) {
    .qcc-header-section {
        margin: 0 -1rem 2rem;
        border-radius: 0;
    }
    
    .qcc-header-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .qcc-control-group {
        min-width: auto;
    }
}
</style>

<?php if ($debug['debug_mode'] ?? false): ?>
<!-- Debug Information -->
<div class="qcc-debug-info" style="margin-top: 20px; padding: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; font-family: monospace; font-size: 12px; color: rgba(255,255,255,0.8);">
    <details>
        <summary>🐛 QCC Header Section Debug Info</summary>
        <pre><?php echo $helpers['escape'](print_r(array(
            'template' => 'sections/header-section',
            'section_id' => $section_id,
            'current_language' => $current_language,
            'current_currency' => $current_currency,
            'current_unit' => $current_unit,
            'show_controls' => $show_controls,
            'show_progress' => $show_progress,
            'progress_percentage' => $progress_percentage,
            'notifications_count' => count($data['notifications'] ?? array()),
            'timestamp' => date('Y-m-d H:i:s')
        ), true)); ?></pre>
    </details>
</div>
<?php endif; ?>

<?php
/**
 * Template-Dokumentation:
 * 
 * Erforderliche Daten:
 * - Keine (verwendet intelligente Defaults)
 * 
 * Optionale Daten:
 * - $data['title'] - Header-Titel
 * - $data['subtitle'] - Header-Untertitel
 * - $data['description'] - Header-Beschreibung
 * - $data['logo_url'] - Logo-URL
 * - $data['logo_alt'] - Logo-Alt-Text
 * - $data['beta'] - Beta-Badge anzeigen
 * - $data['show_controls'] - Global Controls anzeigen
 * - $data['show_progress'] - Progress-Bar anzeigen
 * - $data['current_step'] - Aktueller Schritt
 * - $data['total_steps'] - Gesamt-Schritte
 * - $data['language'] - Aktuelle Sprache
 * - $data['currency'] - Aktuelle Währung
 * - $data['unit'] - Aktuelle Einheit
 * - $data['available_languages'] - Verfügbare Sprachen
 * - $data['available_currencies'] - Verfügbare Währungen
 * - $data['available_units'] - Verfügbare Einheiten
 * - $data['notifications'] - Array von Benachrichtigungen
 * - $data['breadcrumbs'] - Breadcrumb-Navigation
 * - $data['compact'] - Kompakte Darstellung
 * - $data['centered'] - Zentrierte Darstellung
 * - $data['style'] - Header-Style (default, modern, minimal)
 * 
 * CSS-Klassen:
 * - .qcc-header-section - Basis-Header
 * - .qcc-header-content - Header-Content-Container
 * - .qcc-header-main - Titel & Beschreibung
 * - .qcc-header-controls - Global Controls
 * - .qcc-header-progress - Progress-Indikator
 * - .qcc-header-notifications - Benachrichtigungen
 * - .qcc-breadcrumbs - Breadcrumb-Navigation
 * 
 * JavaScript-Integration:
 * - QCC.changeLanguage(language) - Sprache ändern
 * - QCC.changeCurrency(currency) - Währung ändern
 * - QCC.changeUnit(unit) - Einheit ändern
 * - QCC.showHelp() - Hilfe anzeigen
 * - QCC.showExportDialog() - Export-Dialog
 * 
 * Accessibility:
 * - role="banner" für Header
 * - role="progressbar" für Progress
 * - aria-label für alle interaktiven Elemente
 * - Keyboard-Navigation-Support
 * - Screen-Reader-freundlich
 * 
 * Performance:
 * - CSS Gradient-Backgrounds
 * - Backdrop-Filter für moderne Browser
 * - Optimierte Responsive-Breakpoints
 * - Template-Caching automatisch
 */
?>
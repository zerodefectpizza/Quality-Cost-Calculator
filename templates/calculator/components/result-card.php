<?php
/**
 * Template: Result Card Component
 * Ergebnis-Karte für Qualitätskostenberechnungen
 * 
 * @param array $data Component-Daten
 * @param array $attributes HTML-Attribute
 * @param QCC_Translator $translator Übersetzungsservice
 */

// Daten extrahieren
$label = $data['label'] ?? '';
$value = $data['value'] ?? 0;
$type = $data['type'] ?? 'default'; // cogq, copq, default
$id = $data['id'] ?? uniqid('qcc-result-');
$currency_symbol = $data['currency_symbol'] ?? '€';
$unit_label = $data['unit_label'] ?? 'Mrd.';
$css_classes = $attributes['css_classes'] ?? '';

// CSS-Klassen basierend auf Typ
$type_class = '';
switch ($type) {
    case 'cogq':
        $type_class = 'qcc-result-card-cogq';
        break;
    case 'copq':
        $type_class = 'qcc-result-card-copq';
        break;
    case 'opportunity':
        $type_class = 'qcc-result-card-opportunity';
        break;
    default:
        $type_class = 'qcc-result-card-default';
}

// Wert formatieren
$formatted_value = number_format($value, 2, ',', '.');
?>

<div id="<?php echo esc_attr($id); ?>" 
     class="qcc-result-card <?php echo esc_attr($type_class . ' ' . $css_classes); ?>"
     data-type="<?php echo esc_attr($type); ?>"
     data-value="<?php echo esc_attr($value); ?>">
     
    <div class="qcc-result-card-header">
        <h4 class="qcc-result-card-label">
            <?php echo esc_html($label); ?>
        </h4>
        
        <?php if (isset($data['icon'])): ?>
        <div class="qcc-result-card-icon">
            <i class="<?php echo esc_attr($data['icon']); ?>"></i>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="qcc-result-card-body">
        <div class="qcc-result-card-value">
            <span class="qcc-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
            <span class="qcc-value-number" data-value="<?php echo esc_attr($value); ?>">
                <?php echo esc_html($formatted_value); ?>
            </span>
            <span class="qcc-unit-label"><?php echo esc_html($unit_label); ?></span>
        </div>
        
        <?php if (isset($data['percentage'])): ?>
        <div class="qcc-result-card-percentage">
            <?php echo esc_html($data['percentage']); ?>%
        </div>
        <?php endif; ?>
        
        <?php if (isset($data['trend'])): ?>
        <div class="qcc-result-card-trend qcc-trend-<?php echo esc_attr($data['trend']['direction']); ?>">
            <i class="qcc-icon-trend-<?php echo esc_attr($data['trend']['direction']); ?>"></i>
            <span><?php echo esc_html($data['trend']['value']); ?>%</span>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($data['description'])): ?>
    <div class="qcc-result-card-footer">
        <p class="qcc-result-card-description">
            <?php echo esc_html($data['description']); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <?php if (isset($data['actions']) && is_array($data['actions'])): ?>
    <div class="qcc-result-card-actions">
        <?php foreach ($data['actions'] as $action): ?>
        <button type="button" 
                class="qcc-result-card-action-btn <?php echo esc_attr($action['class'] ?? ''); ?>"
                data-action="<?php echo esc_attr($action['action'] ?? ''); ?>"
                <?php if (isset($action['data'])): ?>
                    <?php foreach ($action['data'] as $key => $value): ?>
                        data-<?php echo esc_attr($key); ?>="<?php echo esc_attr($value); ?>"
                    <?php endforeach; ?>
                <?php endif; ?>>
            <?php if (isset($action['icon'])): ?>
            <i class="<?php echo esc_attr($action['icon']); ?>"></i>
            <?php endif; ?>
            <?php echo esc_html($action['label']); ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.qcc-result-card {
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    border: 2px solid #E7F9DE;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.qcc-result-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.qcc-result-card-cogq {
    border-left: 4px solid #449775;
}

.qcc-result-card-copq {
    border-left: 4px solid #dc3545;
}

.qcc-result-card-opportunity {
    border-left: 4px solid #ffc107;
}

.qcc-result-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.qcc-result-card-label {
    font-size: 14px;
    font-weight: 600;
    color: #666;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.qcc-result-card-icon {
    color: #449775;
    font-size: 18px;
}

.qcc-result-card-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #141C14;
    text-align: center;
    margin-bottom: 10px;
}

.qcc-currency-symbol,
.qcc-unit-label {
    font-size: 1.2rem;
    color: #666;
}

.qcc-value-number {
    margin: 0 5px;
}

.qcc-result-card-percentage {
    text-align: center;
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.qcc-result-card-trend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 600;
}

.qcc-trend-up {
    color: #28a745;
}

.qcc-trend-down {
    color: #dc3545;
}

.qcc-trend-neutral {
    color: #6c757d;
}

.qcc-result-card-description {
    font-size: 12px;
    color: #666;
    text-align: center;
    margin: 0;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.qcc-result-card-actions {
    margin-top: 15px;
    display: flex;
    gap: 8px;
    justify-content: center;
}

.qcc-result-card-action-btn {
    padding: 6px 12px;
    border: 1px solid #449775;
    background: transparent;
    color: #449775;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.qcc-result-card-action-btn:hover {
    background: #449775;
    color: white;
}

/* Animation für Wertänderungen */
.qcc-value-number {
    transition: all 0.3s ease;
}

.qcc-result-card.qcc-calculating .qcc-value-number {
    opacity: 0.7;
    transform: scale(0.98);
}

/* Responsive Design */
@media (max-width: 768px) {
    .qcc-result-card-value {
        font-size: 1.5rem;
    }
    
    .qcc-currency-symbol,
    .qcc-unit-label {
        font-size: 1rem;
    }
}
</style>
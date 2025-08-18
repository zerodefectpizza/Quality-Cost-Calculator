<?php
/**
 * QCC Display Factory
 * 
 * Factory for creating display atom components including result cards,
 * charts, status displays and buttons with proper configuration.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms\Factories
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Display_Factory {
    
    private $component_registry;
    private $translator;
    private $display_types = array();
    private $default_configs = array();
    
    public function __construct() {
        $this->component_registry = QCC_Service_Container::get('component_registry');
        $this->translator = QCC_Service_Container::get('translator');
        $this->init_display_types();
        $this->init_default_configs();
    }
    
    /**
     * Create display component by type
     */
    public function create($type, $config = array()) {
        if (!$this->is_supported_type($type)) {
            throw new Exception("Unsupported display type: {$type}");
        }
        
        $display_config = $this->prepare_display_config($type, $config);
        $component = $this->get_display_component($type);
        
        return $component->render($display_config['data'], $display_config['attributes']);
    }
    
    /**
     * Create result card
     */
    public function create_result_card($config = array()) {
        return $this->create('result_card', $config);
    }
    
    /**
     * Create chart container
     */
    public function create_chart($config = array()) {
        return $this->create('chart_container', $config);
    }
    
    /**
     * Create status display
     */
    public function create_status($config = array()) {
        return $this->create('status_display', $config);
    }
    
    /**
     * Create button
     */
    public function create_button($config = array()) {
        return $this->create('button', $config);
    }
    
    /**
     * Create COGQ result cards
     */
    public function create_cogq_cards($data = array()) {
        $cards = array();
        
        // Prevention costs card
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('prevention_costs'),
            'value' => $data['prevention'] ?? 0,
            'color' => '#2E8B57',
            'icon' => 'shield-check',
            'trend' => $data['prevention_trend'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000'
        ));
        
        // Appraisal costs card
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('appraisal_costs'),
            'value' => $data['appraisal'] ?? 0,
            'color' => '#4682B4',
            'icon' => 'search',
            'trend' => $data['appraisal_trend'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000'
        ));
        
        // Total COGQ card
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('total_cogq'),
            'value' => $data['total'] ?? 0,
            'color' => '#228B22',
            'icon' => 'check-circle',
            'highlight' => true,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000'
        ));
        
        return $this->wrap_card_group($cards, 'cogq');
    }
    
    /**
     * Create COPQ result cards
     */
    public function create_copq_cards($data = array()) {
        $cards = array();
        
        // Internal defect costs card
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('internal_defect_costs'),
            'value' => $data['internal'] ?? 0,
            'color' => '#FF6347',
            'icon' => 'alert-triangle',
            'trend' => $data['internal_trend'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000'
        ));
        
        // External defect costs card
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('external_defect_costs'),
            'value' => $data['external'] ?? 0,
            'color' => '#DC143C',
            'icon' => 'x-circle',
            'trend' => $data['external_trend'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000'
        ));
        
        // Total COPQ card
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('total_copq'),
            'value' => $data['total'] ?? 0,
            'color' => '#B22222',
            'icon' => 'alert-circle',
            'highlight' => true,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000'
        ));
        
        return $this->wrap_card_group($cards, 'copq');
    }
    
    /**
     * Create comparison chart
     */
    public function create_comparison_chart($data = array()) {
        return $this->create_chart(array(
            'chart_id' => 'qcc-comparison-chart-' . uniqid(),
            'chart_type' => 'bar',
            'title' => $this->translator->get('cogq_vs_copq'),
            'data' => array(
                'labels' => array('COGQ', 'COPQ'),
                'datasets' => array(
                    array(
                        'label' => $this->translator->get('costs'),
                        'data' => array($data['cogq'] ?? 0, $data['copq'] ?? 0),
                        'backgroundColor' => array('#28A745', '#DC3545'),
                        'borderColor' => array('#1E7E34', '#C82333'),
                        'borderWidth' => 1
                    )
                )
            ),
            'options' => array(
                'responsive' => true,
                'plugins' => array(
                    'legend' => array('display' => false),
                    'title' => array(
                        'display' => true,
                        'text' => $this->translator->get('cost_comparison')
                    )
                ),
                'scales' => array(
                    'y' => array(
                        'beginAtZero' => true,
                        'title' => array(
                            'display' => true,
                            'text' => $this->get_currency_label($data['currency'] ?? 'EUR', $data['unit'] ?? '1000000')
                        )
                    )
                )
            )
        ));
    }
    
    /**
     * Create breakdown chart
     */
    public function create_breakdown_chart($data = array(), $type = 'cogq') {
        $chart_config = array(
            'chart_id' => 'qcc-breakdown-' . $type . '-' . uniqid(),
            'chart_type' => 'doughnut',
            'responsive' => true
        );
        
        if ($type === 'cogq') {
            $chart_config['title'] = $this->translator->get('cogq_breakdown');
            $chart_config['data'] = array(
                'labels' => array(
                    $this->translator->get('prevention_costs'),
                    $this->translator->get('appraisal_costs')
                ),
                'datasets' => array(
                    array(
                        'data' => array($data['prevention'] ?? 0, $data['appraisal'] ?? 0),
                        'backgroundColor' => array('#2E8B57', '#4682B4'),
                        'borderWidth' => 2
                    )
                )
            );
        } else {
            $chart_config['title'] = $this->translator->get('copq_breakdown');
            $chart_config['data'] = array(
                'labels' => array(
                    $this->translator->get('internal_defect_costs'),
                    $this->translator->get('external_defect_costs')
                ),
                'datasets' => array(
                    array(
                        'data' => array($data['internal'] ?? 0, $data['external'] ?? 0),
                        'backgroundColor' => array('#FF6347', '#DC143C'),
                        'borderWidth' => 2
                    )
                )
            );
        }
        
        return $this->create_chart($chart_config);
    }
    
    /**
     * Create export buttons
     */
    public function create_export_buttons($formats = array('pdf', 'csv', 'json')) {
        $buttons = array();
        
        $button_configs = array(
            'pdf' => array(
                'label' => $this->translator->get('export_pdf'),
                'icon' => 'file-text',
                'variant' => 'outline',
                'action' => 'export_pdf'
            ),
            'csv' => array(
                'label' => $this->translator->get('export_csv'),
                'icon' => 'download',
                'variant' => 'outline',
                'action' => 'export_csv'
            ),
            'json' => array(
                'label' => $this->translator->get('export_json'),
                'icon' => 'code',
                'variant' => 'outline',
                'action' => 'export_json'
            ),
            'print' => array(
                'label' => $this->translator->get('print'),
                'icon' => 'printer',
                'variant' => 'outline',
                'action' => 'print'
            )
        );
        
        foreach ($formats as $format) {
            if (isset($button_configs[$format])) {
                $buttons[] = $this->create_button($button_configs[$format]);
            }
        }
        
        return $this->wrap_button_group($buttons, 'export');
    }
    
    /**
     * Create validation status display
     */
    public function create_validation_status($validation_state = array()) {
        $status = $validation_state['valid'] ?? true ? 'success' : 'error';
        $message = $validation_state['valid'] ?? true 
            ? $this->translator->get('validation_passed')
            : $this->translator->get('validation_failed');
        
        $config = array(
            'status' => $status,
            'message' => $message,
            'compact' => true,
            'animate' => true
        );
        
        if (!empty($validation_state['messages'])) {
            $config['messages'] = $validation_state['messages'];
        }
        
        return $this->create_status($config);
    }
    
    /**
     * Create loading display
     */
    public function create_loading_display($message = '') {
        return $this->create_status(array(
            'status' => 'loading',
            'message' => $message ?: $this->translator->get('calculating'),
            'progress' => null,
            'compact' => false,
            'animate' => true
        ));
    }
    
    /**
     * Get supported display types
     */
    public function get_supported_types() {
        return array_keys($this->display_types);
    }
    
    /**
     * Check if display type is supported
     */
    public function is_supported_type($type) {
        return isset($this->display_types[$type]);
    }
    
    /**
     * Get display component instance
     */
    private function get_display_component($type) {
        $component_name = $this->display_types[$type]['component'];
        return $this->component_registry->get($component_name);
    }
    
    /**
     * Prepare display configuration
     */
    private function prepare_display_config($type, $config) {
        $default_config = $this->default_configs[$type] ?? array();
        $merged_config = array_merge($default_config, $config);
        
        return array(
            'data' => $this->extract_data_config($merged_config, $type),
            'attributes' => $this->extract_attributes_config($merged_config)
        );
    }
    
    /**
     * Extract data configuration
     */
    private function extract_data_config($config, $type) {
        $common_keys = array('id', 'css_classes', 'loading', 'error');
        
        $type_specific_keys = array(
            'result_card' => array('title', 'value', 'subtitle', 'icon', 'color', 'trend', 'trend_label', 'highlight', 'currency', 'unit', 'precision'),
            'chart_container' => array('chart_id', 'chart_type', 'width', 'height', 'responsive', 'maintain_aspect_ratio', 'data', 'options', 'title', 'description', 'legend', 'fallback_message'),
            'status_display' => array('status', 'message', 'messages', 'title', 'icon', 'dismissible', 'progress', 'progress_label', 'actions', 'compact', 'animate', 'auto_hide'),
            'button' => array('label', 'icon', 'icon_position', 'variant', 'size', 'state', 'action', 'url', 'target', 'type', 'disabled', 'aria_label', 'aria_describedby', 'role', 'aria_expanded', 'aria_pressed')
        );
        
        $data_keys = array_merge($common_keys, $type_specific_keys[$type] ?? array());
        
        $data = array();
        foreach ($data_keys as $key) {
            if (isset($config[$key])) {
                $data[$key] = $config[$key];
            }
        }
        
        return $data;
    }
    
    /**
     * Extract attributes configuration
     */
    private function extract_attributes_config($config) {
        $attribute_keys = array('class', 'style', 'title', 'tabindex');
        
        $attributes = array();
        foreach ($config as $key => $value) {
            if (in_array($key, $attribute_keys) || 
                strpos($key, 'data-') === 0 || 
                strpos($key, 'aria-') === 0) {
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Wrap card group
     */
    private function wrap_card_group($cards, $group_type) {
        return sprintf(
            '<div class="qcc-card-group qcc-card-group--%s">%s</div>',
            esc_attr($group_type),
            implode("\n", $cards)
        );
    }
    
    /**
     * Wrap button group
     */
    private function wrap_button_group($buttons, $group_type) {
        return sprintf(
            '<div class="qcc-button-group qcc-button-group--%s">%s</div>',
            esc_attr($group_type),
            implode("\n", $buttons)
        );
    }
    
    /**
     * Get currency label for charts
     */
    private function get_currency_label($currency, $unit) {
        $currency_symbol = $this->get_currency_symbol($currency);
        $unit_name = $this->get_unit_name($unit);
        
        return sprintf('%s (%s)', $currency_symbol, $unit_name);
    }
    
    /**
     * Get currency symbol
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'EUR' => '€',
            'USD' => ',
            'CNY' => '¥',
            'GBP' => '£',
            'JPY' => '¥'
        );
        
        return $symbols[$currency] ?? $currency;
    }
    
    /**
     * Get unit name
     */
    private function get_unit_name($unit) {
        $units = array(
            '1000000' => $this->translator->get('millions'),
            '1000000000' => $this->translator->get('billions')
        );
        
        return $units[$unit] ?? 'Units';
    }
    
    /**
     * Initialize display types
     */
    private function init_display_types() {
        $this->display_types = array(
            'result_card' => array(
                'component' => 'result_card'
            ),
            'chart_container' => array(
                'component' => 'chart_container'
            ),
            'status_display' => array(
                'component' => 'status_display'
            ),
            'button' => array(
                'component' => 'button'
            )
        );
    }
    
    /**
     * Initialize default configurations
     */
    private function init_default_configs() {
        $this->default_configs = array(
            'result_card' => array(
                'currency' => 'EUR',
                'unit' => '1000000',
                'precision' => 2,
                'highlight' => false,
                'loading' => false
            ),
            'chart_container' => array(
                'width' => 400,
                'height' => 300,
                'responsive' => true,
                'maintain_aspect_ratio' => true,
                'legend' => true,
                'loading' => false
            ),
            'status_display' => array(
                'compact' => false,
                'dismissible' => false,
                'animate' => true
            ),
            'button' => array(
                'type' => 'button',
                'variant' => 'default',
                'size' => 'medium',
                'icon_position' => 'left',
                'target' => '_self',
                'disabled' => false,
                'loading' => false
            )
        );
    }
    
    /**
     * Get required dependencies
     */
    public function get_dependencies() {
        return array('component_registry', 'translator');
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-display-factory.css'),
            'js' => array('qcc-display-factory.js'),
            'dependencies' => array('chart.js')
        );
    }
}
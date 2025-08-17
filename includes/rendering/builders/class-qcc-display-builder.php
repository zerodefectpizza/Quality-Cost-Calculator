<?php
/**
 * QCC Display Builder Service
 * 
 * Handles automatic generation of result displays, charts, status indicators
 * and export UI components for the Quality Cost Calculator.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Builders
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Display_Builder extends QCC_Base_Builder {
    
    private $result_cards = array();
    private $chart_configs = array();
    private $status_indicators = array();
    private $export_components = array();
    
    public function __construct() {
        parent::__construct();
        $this->init_display_configs();
    }
    
    /**
     * Build complete display section
     */
    public function build($config = array()) {
        $display_type = $config['type'] ?? 'full';
        
        switch ($display_type) {
            case 'results_only':
                return $this->build_results_display($config);
            case 'charts_only':
                return $this->build_chart_display($config);
            case 'status_only':
                return $this->build_status_display($config);
            case 'export_only':
                return $this->build_export_display($config);
            default:
                return $this->build_full_display($config);
        }
    }
    
    /**
     * Build results display with automatic card generation
     */
    public function build_results_display($config) {
        $results_data = $config['results'] ?? array();
        $layout = $config['layout'] ?? 'grid';
        
        $cards_html = array();
        
        // Generate COGQ cards
        if (isset($results_data['cogq'])) {
            $cards_html[] = $this->generate_cogq_cards($results_data['cogq']);
        }
        
        // Generate COPQ cards
        if (isset($results_data['copq'])) {
            $cards_html[] = $this->generate_copq_cards($results_data['copq']);
        }
        
        // Generate opportunity cards
        if (isset($results_data['opportunity'])) {
            $cards_html[] = $this->generate_opportunity_cards($results_data['opportunity']);
        }
        
        return $this->wrap_results_layout($cards_html, $layout);
    }
    
    /**
     * Build chart display with Chart.js integration
     */
    public function build_chart_display($config) {
        $chart_type = $config['chart_type'] ?? 'bar';
        $data = $config['data'] ?? array();
        
        $chart_html = array();
        
        // Main comparison chart
        $chart_html[] = $this->generate_comparison_chart($data, $chart_type);
        
        // Breakdown charts if requested
        if ($config['include_breakdown'] ?? true) {
            $chart_html[] = $this->generate_breakdown_charts($data);
        }
        
        // Trend charts if data available
        if (isset($config['trend_data'])) {
            $chart_html[] = $this->generate_trend_chart($config['trend_data']);
        }
        
        return $this->wrap_chart_layout($chart_html);
    }
    
    /**
     * Build status display with validation messages
     */
    public function build_status_display($config) {
        $validation_state = $config['validation'] ?? array();
        $calculation_status = $config['calculation_status'] ?? 'ready';
        
        $status_html = array();
        
        // Validation status
        $status_html[] = $this->generate_validation_status($validation_state);
        
        // Calculation status
        $status_html[] = $this->generate_calculation_status($calculation_status);
        
        // Progress indicator if needed
        if ($config['show_progress'] ?? false) {
            $status_html[] = $this->generate_progress_indicator($config['progress'] ?? 0);
        }
        
        return $this->wrap_status_layout($status_html);
    }
    
    /**
     * Build export display with PDF/CSV options
     */
    public function build_export_display($config) {
        $export_options = $config['export_options'] ?? array('pdf', 'csv', 'json');
        
        $export_html = array();
        
        foreach ($export_options as $option) {
            $export_html[] = $this->generate_export_button($option, $config);
        }
        
        return $this->wrap_export_layout($export_html);
    }
    
    /**
     * Generate COGQ result cards
     */
    private function generate_cogq_cards($cogq_data) {
        $cards = array();
        
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('prevention_costs'),
            'value' => $cogq_data['prevention'] ?? 0,
            'color' => '#2E8B57',
            'icon' => 'shield-check',
            'trend' => $cogq_data['prevention_trend'] ?? null
        ));
        
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('appraisal_costs'),
            'value' => $cogq_data['appraisal'] ?? 0,
            'color' => '#4682B4',
            'icon' => 'search',
            'trend' => $cogq_data['appraisal_trend'] ?? null
        ));
        
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('total_cogq'),
            'value' => $cogq_data['total'] ?? 0,
            'color' => '#228B22',
            'icon' => 'check-circle',
            'highlight' => true
        ));
        
        return implode("\n", $cards);
    }
    
    /**
     * Generate COPQ result cards
     */
    private function generate_copq_cards($copq_data) {
        $cards = array();
        
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('internal_defect_costs'),
            'value' => $copq_data['internal'] ?? 0,
            'color' => '#FF6347',
            'icon' => 'alert-triangle',
            'trend' => $copq_data['internal_trend'] ?? null
        ));
        
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('external_defect_costs'),
            'value' => $copq_data['external'] ?? 0,
            'color' => '#DC143C',
            'icon' => 'x-circle',
            'trend' => $copq_data['external_trend'] ?? null
        ));
        
        $cards[] = $this->create_result_card(array(
            'title' => $this->translator->get('total_copq'),
            'value' => $copq_data['total'] ?? 0,
            'color' => '#B22222',
            'icon' => 'alert-circle',
            'highlight' => true
        ));
        
        return implode("\n", $cards);
    }
    
    /**
     * Generate opportunity cost cards
     */
    private function generate_opportunity_cards($opportunity_data) {
        $cards = array();
        
        if (isset($opportunity_data['lost_sales'])) {
            $cards[] = $this->create_result_card(array(
                'title' => $this->translator->get('lost_sales'),
                'value' => $opportunity_data['lost_sales'],
                'color' => '#FF8C00',
                'icon' => 'trending-down'
            ));
        }
        
        if (isset($opportunity_data['customer_churn'])) {
            $cards[] = $this->create_result_card(array(
                'title' => $this->translator->get('customer_churn'),
                'value' => $opportunity_data['customer_churn'],
                'color' => '#FF4500',
                'icon' => 'user-minus'
            ));
        }
        
        if (isset($opportunity_data['market_share_loss'])) {
            $cards[] = $this->create_result_card(array(
                'title' => $this->translator->get('market_share_loss'),
                'value' => $opportunity_data['market_share_loss'],
                'color' => '#FF1493',
                'icon' => 'bar-chart-2'
            ));
        }
        
        return implode("\n", $cards);
    }
    
    /**
     * Create individual result card
     */
    private function create_result_card($config) {
        $card_id = 'qcc-card-' . uniqid();
        $highlight_class = ($config['highlight'] ?? false) ? ' qcc-card-highlight' : '';
        $trend_indicator = $this->generate_trend_indicator($config['trend'] ?? null);
        
        return sprintf(
            '<div class="qcc-result-card%s" style="border-left: 4px solid %s;">
                <div class="qcc-card-header">
                    <span class="qcc-card-icon">%s</span>
                    <h3 class="qcc-card-title">%s</h3>
                    %s
                </div>
                <div class="qcc-card-value" id="%s-value">%s</div>
                <div class="qcc-card-subtitle">%s</div>
            </div>',
            $highlight_class,
            esc_attr($config['color']),
            $this->get_icon_svg($config['icon']),
            esc_html($config['title']),
            $trend_indicator,
            esc_attr($card_id),
            $this->format_currency_value($config['value']),
            esc_html($config['subtitle'] ?? '')
        );
    }
    
    /**
     * Generate comparison chart
     */
    private function generate_comparison_chart($data, $chart_type) {
        $chart_id = 'qcc-comparison-chart-' . uniqid();
        
        return sprintf(
            '<div class="qcc-chart-container">
                <canvas id="%s" width="400" height="200"></canvas>
                <script>
                    QCC.Charts.createComparisonChart("%s", %s, "%s");
                </script>
            </div>',
            esc_attr($chart_id),
            esc_attr($chart_id),
            wp_json_encode($data),
            esc_attr($chart_type)
        );
    }
    
    /**
     * Generate breakdown charts
     */
    private function generate_breakdown_charts($data) {
        $cogq_chart_id = 'qcc-cogq-breakdown-' . uniqid();
        $copq_chart_id = 'qcc-copq-breakdown-' . uniqid();
        
        return sprintf(
            '<div class="qcc-breakdown-charts">
                <div class="qcc-chart-half">
                    <h4>%s</h4>
                    <canvas id="%s" width="200" height="200"></canvas>
                </div>
                <div class="qcc-chart-half">
                    <h4>%s</h4>
                    <canvas id="%s" width="200" height="200"></canvas>
                </div>
                <script>
                    QCC.Charts.createBreakdownChart("%s", %s);
                    QCC.Charts.createBreakdownChart("%s", %s);
                </script>
            </div>',
            esc_html($this->translator->get('cogq_breakdown')),
            esc_attr($cogq_chart_id),
            esc_html($this->translator->get('copq_breakdown')),
            esc_attr($copq_chart_id),
            esc_attr($cogq_chart_id),
            wp_json_encode($data['cogq'] ?? array()),
            esc_attr($copq_chart_id),
            wp_json_encode($data['copq'] ?? array())
        );
    }
    
    /**
     * Generate validation status
     */
    private function generate_validation_status($validation_state) {
        $status_class = $validation_state['valid'] ? 'qcc-status-valid' : 'qcc-status-invalid';
        $status_icon = $validation_state['valid'] ? 'check-circle' : 'alert-circle';
        $status_text = $validation_state['valid'] 
            ? $this->translator->get('validation_passed')
            : $this->translator->get('validation_failed');
        
        $messages_html = '';
        if (!empty($validation_state['messages'])) {
            $messages = array_map(function($msg) {
                return '<li>' . esc_html($msg) . '</li>';
            }, $validation_state['messages']);
            $messages_html = '<ul class="qcc-validation-messages">' . implode('', $messages) . '</ul>';
        }
        
        return sprintf(
            '<div class="qcc-status-indicator %s">
                <span class="qcc-status-icon">%s</span>
                <span class="qcc-status-text">%s</span>
                %s
            </div>',
            esc_attr($status_class),
            $this->get_icon_svg($status_icon),
            esc_html($status_text),
            $messages_html
        );
    }
    
    /**
     * Generate export button
     */
    private function generate_export_button($type, $config) {
        $button_config = array(
            'pdf' => array('label' => 'export_pdf', 'icon' => 'file-text', 'class' => 'qcc-export-pdf'),
            'csv' => array('label' => 'export_csv', 'icon' => 'download', 'class' => 'qcc-export-csv'),
            'json' => array('label' => 'export_json', 'icon' => 'code', 'class' => 'qcc-export-json')
        );
        
        if (!isset($button_config[$type])) {
            return '';
        }
        
        $btn_config = $button_config[$type];
        
        return sprintf(
            '<button type="button" class="qcc-export-button %s" data-export-type="%s">
                %s
                <span>%s</span>
            </button>',
            esc_attr($btn_config['class']),
            esc_attr($type),
            $this->get_icon_svg($btn_config['icon']),
            esc_html($this->translator->get($btn_config['label']))
        );
    }
    
    /**
     * Format currency value for display
     */
    private function format_currency_value($value) {
        if (!is_numeric($value)) {
            return esc_html($value);
        }
        
        $currency_symbol = $this->get_currency_symbol();
        $unit_name = $this->get_unit_name();
        
        return sprintf(
            '%s%.2f %s',
            $currency_symbol,
            floatval($value),
            $unit_name
        );
    }
    
    /**
     * Generate trend indicator
     */
    private function generate_trend_indicator($trend) {
        if ($trend === null) {
            return '';
        }
        
        $trend_class = $trend > 0 ? 'qcc-trend-up' : ($trend < 0 ? 'qcc-trend-down' : 'qcc-trend-neutral');
        $trend_icon = $trend > 0 ? 'trending-up' : ($trend < 0 ? 'trending-down' : 'minus');
        
        return sprintf(
            '<span class="qcc-trend-indicator %s">%s</span>',
            esc_attr($trend_class),
            $this->get_icon_svg($trend_icon)
        );
    }
    
    /**
     * Wrap results in layout
     */
    private function wrap_results_layout($cards_html, $layout) {
        $layout_class = 'qcc-results-' . $layout;
        
        return sprintf(
            '<div class="qcc-results-display %s">%s</div>',
            esc_attr($layout_class),
            implode("\n", $cards_html)
        );
    }
    
    /**
     * Wrap charts in layout
     */
    private function wrap_chart_layout($charts_html) {
        return sprintf(
            '<div class="qcc-charts-display">%s</div>',
            implode("\n", $charts_html)
        );
    }
    
    /**
     * Wrap status in layout
     */
    private function wrap_status_layout($status_html) {
        return sprintf(
            '<div class="qcc-status-display">%s</div>',
            implode("\n", $status_html)
        );
    }
    
    /**
     * Wrap export in layout
     */
    private function wrap_export_layout($export_html) {
        return sprintf(
            '<div class="qcc-export-display">%s</div>',
            implode("\n", $export_html)
        );
    }
    
    /**
     * Build full display combining all components
     */
    private function build_full_display($config) {
        $components = array();
        
        if ($config['include_results'] ?? true) {
            $components[] = $this->build_results_display($config);
        }
        
        if ($config['include_charts'] ?? true) {
            $components[] = $this->build_chart_display($config);
        }
        
        if ($config['include_status'] ?? true) {
            $components[] = $this->build_status_display($config);
        }
        
        if ($config['include_export'] ?? true) {
            $components[] = $this->build_export_display($config);
        }
        
        return sprintf(
            '<div class="qcc-full-display">%s</div>',
            implode("\n", $components)
        );
    }
    
    /**
     * Initialize display configurations
     */
    private function init_display_configs() {
        $this->result_cards = array(
            'cogq' => array('prevention', 'appraisal', 'total'),
            'copq' => array('internal', 'external', 'total'),
            'opportunity' => array('lost_sales', 'customer_churn', 'market_share_loss')
        );
        
        $this->chart_configs = array(
            'comparison' => array('type' => 'bar', 'responsive' => true),
            'breakdown' => array('type' => 'doughnut', 'responsive' => true),
            'trend' => array('type' => 'line', 'responsive' => true)
        );
    }
    
    /**
     * Get SVG icon
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'check-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
            'alert-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
            'trending-up' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>',
            'trending-down' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 18l2.29-2.29-4.88-4.88-4 4L2 7.41 3.41 6l6 6 4-4 6.3 6.29L22 12v6z"/></svg>',
            'shield-check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z"/></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get required dependencies
     */
    public function get_dependencies() {
        return array('translator', 'template_manager', 'asset_manager');
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-display-builder.css'),
            'js' => array('qcc-charts.js', 'qcc-export.js'),
            'dependencies' => array('chart.js')
        );
    }
}
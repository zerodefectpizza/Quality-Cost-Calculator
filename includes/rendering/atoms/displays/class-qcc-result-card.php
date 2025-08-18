<?php
/**
 * QCC Result Card Atom
 * 
 * Atomic component for displaying individual result values with
 * titles, trends, and visual styling.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms\Displays
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Result_Card extends QCC_Base_Atom {
    
    protected $template_name = 'atoms/result-card.php';
    protected $css_base_class = 'qcc-result-card';
    protected $required_fields = array('title', 'value');
    
    protected $default_attributes = array(
        'class' => 'qcc-result-card'
    );
    
    /**
     * Render result card
     */
    public function render($data = array(), $attributes = array()) {
        $validation = $this->validate_data($data);
        if (is_wp_error($validation)) {
            return $this->render_error($validation);
        }
        
        $card_config = $this->prepare_card_config($data, $attributes);
        return $this->generate_card_html($card_config);
    }
    
    /**
     * Prepare card configuration
     */
    private function prepare_card_config($data, $attributes) {
        return array(
            'id' => $data['id'] ?? 'qcc-card-' . uniqid(),
            'title' => $data['title'],
            'value' => $data['value'],
            'subtitle' => $data['subtitle'] ?? '',
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? '#2563eb',
            'trend' => $data['trend'] ?? null,
            'trend_label' => $data['trend_label'] ?? '',
            'highlight' => $data['highlight'] ?? false,
            'loading' => $data['loading'] ?? false,
            'error' => $data['error'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? '1000000',
            'precision' => $data['precision'] ?? 2,
            'css_classes' => $this->build_css_classes($data),
            'attributes' => array_merge($this->get_default_attributes(), $attributes)
        );
    }
    
    /**
     * Generate card HTML
     */
    private function generate_card_html($config) {
        if ($config['error']) {
            return $this->render_error_card($config);
        }
        
        $card_parts = array();
        
        // Card header
        $card_parts[] = $this->render_card_header($config);
        
        // Card value
        $card_parts[] = $this->render_card_value($config);
        
        // Card footer (subtitle, trend)
        if (!empty($config['subtitle']) || $config['trend'] !== null) {
            $card_parts[] = $this->render_card_footer($config);
        }
        
        // Loading overlay
        if ($config['loading']) {
            $card_parts[] = $this->render_loading_overlay();
        }
        
        return sprintf(
            '<div id="%s" class="%s" style="border-left-color: %s;" %s>%s</div>',
            esc_attr($config['id']),
            esc_attr($config['css_classes']),
            esc_attr($config['color']),
            $this->build_attributes_string($config['attributes']),
            implode("\n", $card_parts)
        );
    }
    
    /**
     * Render card header
     */
    private function render_card_header($config) {
        $header_parts = array();
        
        // Icon
        if ($config['icon']) {
            $header_parts[] = sprintf(
                '<span class="qcc-card-icon" style="color: %s;">%s</span>',
                esc_attr($config['color']),
                $this->get_icon_svg($config['icon'])
            );
        }
        
        // Title
        $header_parts[] = sprintf(
            '<h3 class="qcc-card-title">%s</h3>',
            esc_html($config['title'])
        );
        
        // Trend indicator
        if ($config['trend'] !== null) {
            $header_parts[] = $this->render_trend_indicator($config['trend'], $config['trend_label']);
        }
        
        return sprintf(
            '<div class="qcc-card-header">%s</div>',
            implode("\n", $header_parts)
        );
    }
    
    /**
     * Render card value
     */
    private function render_card_value($config) {
        $formatted_value = $this->format_value($config['value'], $config);
        
        return sprintf(
            '<div class="qcc-card-value" data-raw-value="%s">%s</div>',
            esc_attr($config['value']),
            $formatted_value
        );
    }
    
    /**
     * Render card footer
     */
    private function render_card_footer($config) {
        $footer_parts = array();
        
        if (!empty($config['subtitle'])) {
            $footer_parts[] = sprintf(
                '<div class="qcc-card-subtitle">%s</div>',
                esc_html($config['subtitle'])
            );
        }
        
        return empty($footer_parts) ? '' : sprintf(
            '<div class="qcc-card-footer">%s</div>',
            implode("\n", $footer_parts)
        );
    }
    
    /**
     * Render trend indicator
     */
    private function render_trend_indicator($trend, $trend_label) {
        if ($trend === null || $trend == 0) {
            return '';
        }
        
        $trend_direction = $trend > 0 ? 'up' : 'down';
        $trend_icon = $trend > 0 ? 'trending-up' : 'trending-down';
        $trend_class = 'qcc-trend-' . $trend_direction;
        
        $trend_text = '';
        if (!empty($trend_label)) {
            $trend_text = sprintf(' <span class="qcc-trend-label">%s</span>', esc_html($trend_label));
        }
        
        return sprintf(
            '<span class="qcc-trend-indicator %s" title="%s">
                %s%s
            </span>',
            esc_attr($trend_class),
            esc_attr(sprintf('Trend: %+.1f%%', $trend)),
            $this->get_icon_svg($trend_icon),
            $trend_text
        );
    }
    
    /**
     * Render loading overlay
     */
    private function render_loading_overlay() {
        return '<div class="qcc-card-loading">
            <div class="qcc-spinner"></div>
        </div>';
    }
    
    /**
     * Render error card
     */
    private function render_error_card($config) {
        return sprintf(
            '<div id="%s" class="%s qcc-card-error">
                <div class="qcc-card-header">
                    <span class="qcc-card-icon qcc-error-icon">%s</span>
                    <h3 class="qcc-card-title">%s</h3>
                </div>
                <div class="qcc-card-value">%s</div>
            </div>',
            esc_attr($config['id']),
            esc_attr($config['css_classes']),
            $this->get_icon_svg('alert-triangle'),
            esc_html($config['title']),
            esc_html($config['error'])
        );
    }
    
    /**
     * Format value for display
     */
    private function format_value($value, $config) {
        if (!is_numeric($value)) {
            return esc_html($value);
        }
        
        $numeric_value = floatval($value);
        $currency_symbol = $this->get_currency_symbol($config['currency']);
        $unit_divisor = floatval($config['unit']);
        $precision = intval($config['precision']);
        
        // Apply unit scaling
        $scaled_value = $numeric_value / $unit_divisor;
        
        // Format number
        $formatted_number = number_format($scaled_value, $precision);
        
        // Add currency symbol
        return sprintf('%s%s', $currency_symbol, $formatted_number);
    }
    
    /**
     * Get currency symbol
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'CNY' => '¥',
            'GBP' => '£',
            'JPY' => '¥'
        );
        
        return $symbols[$currency] ?? $currency;
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes($data) {
        $classes = array($this->css_base_class);
        
        // Highlight class
        if ($data['highlight'] ?? false) {
            $classes[] = $this->css_base_class . '--highlight';
        }
        
        // Loading class
        if ($data['loading'] ?? false) {
            $classes[] = $this->css_base_class . '--loading';
        }
        
        // Error class
        if (!empty($data['error'])) {
            $classes[] = $this->css_base_class . '--error';
        }
        
        // Trend classes
        if (isset($data['trend'])) {
            if ($data['trend'] > 0) {
                $classes[] = $this->css_base_class . '--trend-up';
            } elseif ($data['trend'] < 0) {
                $classes[] = $this->css_base_class . '--trend-down';
            } else {
                $classes[] = $this->css_base_class . '--trend-neutral';
            }
        }
        
        // Custom classes
        if (!empty($data['css_classes'])) {
            if (is_array($data['css_classes'])) {
                $classes = array_merge($classes, $data['css_classes']);
            } else {
                $classes[] = $data['css_classes'];
            }
        }
        
        return implode(' ', array_filter($classes));
    }
    
    /**
     * Build attributes string
     */
    private function build_attributes_string($attributes) {
        $attr_string = '';
        
        foreach ($attributes as $name => $value) {
            if ($name !== 'class') { // Class handled separately
                $attr_string .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
            }
        }
        
        return $attr_string;
    }
    
    /**
     * Get icon SVG
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'dollar-sign' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
            'trending-up' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline><polyline points="17,6 23,6 23,12"></polyline></svg>',
            'trending-down' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,18 13.5,8.5 8.5,13.5 1,6"></polyline><polyline points="17,18 23,18 23,12"></polyline></svg>',
            'shield-check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg>',
            'alert-triangle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'check-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>',
            'x-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'search' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.35-4.35"></path></svg>',
            'bar-chart' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get required fields for validation
     */
    protected function get_required_fields() {
        return $this->required_fields;
    }
    
    /**
     * Get default attributes
     */
    public function get_default_attributes() {
        return $this->default_attributes;
    }
    
    /**
     * Get template name
     */
    public function get_template_name() {
        return $this->template_name;
    }
    
    /**
     * Sanitize input value
     */
    public function sanitize_input($value) {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-result-card.css'),
            'js' => array(),
            'dependencies' => array()
        );
    }
}
<?php
/**
 * QCC Chart Container Atom
 * 
 * Atomic component for Chart.js container with responsive
 * behavior and interactive features.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms\Displays
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Chart_Container extends QCC_Base_Atom {
    
    protected $template_name = 'atoms/chart-container.php';
    protected $css_base_class = 'qcc-chart-container';
    protected $required_fields = array('chart_id', 'chart_type');
    
    protected $default_attributes = array(
        'class' => 'qcc-chart-container'
    );
    
    /**
     * Render chart container
     */
    public function render($data = array(), $attributes = array()) {
        $validation = $this->validate_data($data);
        if (is_wp_error($validation)) {
            return $this->render_error($validation);
        }
        
        $chart_config = $this->prepare_chart_config($data, $attributes);
        return $this->generate_chart_html($chart_config);
    }
    
    /**
     * Prepare chart configuration
     */
    private function prepare_chart_config($data, $attributes) {
        return array(
            'chart_id' => $data['chart_id'],
            'chart_type' => $data['chart_type'],
            'width' => $data['width'] ?? 400,
            'height' => $data['height'] ?? 300,
            'responsive' => $data['responsive'] ?? true,
            'maintain_aspect_ratio' => $data['maintain_aspect_ratio'] ?? true,
            'data' => $data['data'] ?? array(),
            'options' => $data['options'] ?? array(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'legend' => $data['legend'] ?? true,
            'loading' => $data['loading'] ?? false,
            'error' => $data['error'] ?? null,
            'fallback_message' => $data['fallback_message'] ?? '',
            'css_classes' => $this->build_css_classes($data),
            'attributes' => array_merge($this->get_default_attributes(), $attributes),
            'chart_config' => $this->build_chart_js_config($data)
        );
    }
    
    /**
     * Generate chart HTML
     */
    private function generate_chart_html($config) {
        if ($config['error']) {
            return $this->render_error_chart($config);
        }
        
        $container_parts = array();
        
        // Chart title
        if (!empty($config['title'])) {
            $container_parts[] = $this->render_chart_title($config);
        }
        
        // Chart canvas wrapper
        $container_parts[] = $this->render_chart_canvas($config);
        
        // Chart description
        if (!empty($config['description'])) {
            $container_parts[] = $this->render_chart_description($config);
        }
        
        // Loading overlay
        if ($config['loading']) {
            $container_parts[] = $this->render_loading_overlay();
        }
        
        // Chart initialization script
        $container_parts[] = $this->render_chart_script($config);
        
        return sprintf(
            '<div class="%s" %s>%s</div>',
            esc_attr($config['css_classes']),
            $this->build_attributes_string($config['attributes']),
            implode("\n", $container_parts)
        );
    }
    
    /**
     * Render chart title
     */
    private function render_chart_title($config) {
        return sprintf(
            '<div class="qcc-chart-header">
                <h4 class="qcc-chart-title">%s</h4>
            </div>',
            esc_html($config['title'])
        );
    }
    
    /**
     * Render chart canvas
     */
    private function render_chart_canvas($config) {
        $canvas_attributes = array(
            'id' => esc_attr($config['chart_id']),
            'width' => esc_attr($config['width']),
            'height' => esc_attr($config['height']),
            'role' => 'img',
            'aria-label' => !empty($config['title']) ? esc_attr($config['title']) : 'Chart'
        );
        
        // Add accessibility description
        if (!empty($config['description'])) {
            $canvas_attributes['aria-describedby'] = esc_attr($config['chart_id'] . '-description');
        }
        
        $canvas_attrs = '';
        foreach ($canvas_attributes as $name => $value) {
            $canvas_attrs .= sprintf(' %s="%s"', $name, $value);
        }
        
        return sprintf(
            '<div class="qcc-chart-wrapper">
                <canvas class="qcc-chart-canvas"%s>
                    %s
                </canvas>
            </div>',
            $canvas_attrs,
            esc_html($config['fallback_message'] ?: 'Chart not supported in this browser.')
        );
    }
    
    /**
     * Render chart description
     */
    private function render_chart_description($config) {
        return sprintf(
            '<div class="qcc-chart-footer">
                <p id="%s-description" class="qcc-chart-description">%s</p>
            </div>',
            esc_attr($config['chart_id']),
            esc_html($config['description'])
        );
    }
    
    /**
     * Render loading overlay
     */
    private function render_loading_overlay() {
        return '<div class="qcc-chart-loading">
            <div class="qcc-spinner"></div>
            <span class="qcc-loading-text">Loading chart...</span>
        </div>';
    }
    
    /**
     * Render error chart
     */
    private function render_error_chart($config) {
        return sprintf(
            '<div class="%s qcc-chart-error">
                <div class="qcc-chart-error-content">
                    <span class="qcc-error-icon">%s</span>
                    <h4>Chart Error</h4>
                    <p>%s</p>
                </div>
            </div>',
            esc_attr($config['css_classes']),
            $this->get_icon_svg('alert-triangle'),
            esc_html($config['error'])
        );
    }
    
    /**
     * Render chart initialization script
     */
    private function render_chart_script($config) {
        if (empty($config['data'])) {
            return '';
        }
        
        $chart_config_json = wp_json_encode($config['chart_config']);
        
        return sprintf(
            '<script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    if (typeof Chart !== "undefined") {
                        QCC.Charts.create("%s", %s);
                    } else {
                        console.warn("Chart.js library not loaded");
                    }
                });
            </script>',
            esc_js($config['chart_id']),
            $chart_config_json
        );
    }
    
    /**
     * Build Chart.js configuration
     */
    private function build_chart_js_config($data) {
        $default_options = $this->get_default_chart_options($data['chart_type'], $data);
        $custom_options = $data['options'] ?? array();
        
        return array(
            'type' => $data['chart_type'],
            'data' => $data['data'] ?? array(),
            'options' => array_merge_recursive($default_options, $custom_options)
        );
    }
    
    /**
     * Get default Chart.js options
     */
    private function get_default_chart_options($chart_type, $data) {
        $base_options = array(
            'responsive' => $data['responsive'] ?? true,
            'maintainAspectRatio' => $data['maintain_aspect_ratio'] ?? true,
            'plugins' => array(
                'legend' => array(
                    'display' => $data['legend'] ?? true,
                    'position' => 'bottom'
                ),
                'tooltip' => array(
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false
                )
            ),
            'interaction' => array(
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false
            )
        );
        
        // Chart type specific options
        switch ($chart_type) {
            case 'bar':
            case 'horizontalBar':
                $base_options['scales'] = array(
                    'y' => array(
                        'beginAtZero' => true,
                        'grid' => array('display' => true)
                    ),
                    'x' => array(
                        'grid' => array('display' => false)
                    )
                );
                break;
                
            case 'line':
                $base_options['scales'] = array(
                    'y' => array(
                        'beginAtZero' => true,
                        'grid' => array('display' => true)
                    ),
                    'x' => array(
                        'grid' => array('display' => true)
                    )
                );
                $base_options['elements'] = array(
                    'line' => array(
                        'tension' => 0.2
                    )
                );
                break;
                
            case 'pie':
            case 'doughnut':
                $base_options['plugins']['legend']['position'] = 'right';
                break;
        }
        
        return $base_options;
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes($data) {
        $classes = array($this->css_base_class);
        
        // Chart type class
        $classes[] = $this->css_base_class . '--' . sanitize_html_class($data['chart_type']);
        
        // Responsive class
        if ($data['responsive'] ?? true) {
            $classes[] = $this->css_base_class . '--responsive';
        }
        
        // Loading class
        if ($data['loading'] ?? false) {
            $classes[] = $this->css_base_class . '--loading';
        }
        
        // Error class
        if (!empty($data['error'])) {
            $classes[] = $this->css_base_class . '--error';
        }
        
        // Legend class
        if ($data['legend'] ?? true) {
            $classes[] = $this->css_base_class . '--with-legend';
        }
        
        // Size classes
        if (isset($data['size'])) {
            $classes[] = $this->css_base_class . '--' . sanitize_html_class($data['size']);
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
            'alert-triangle' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'bar-chart' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
            'pie-chart' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>'
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
        if (is_array($value)) {
            return array_map(array($this, 'sanitize_input'), $value);
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-chart-container.css'),
            'js' => array('qcc-charts.js'),
            'dependencies' => array('chart.js')
        );
    }
}
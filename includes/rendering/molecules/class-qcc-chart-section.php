<?php
/**
 * QCC Chart Section Molecule
 * 
 * Molecule component that combines chart container with legend,
 * controls, and additional chart-related functionality.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Molecules
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Chart_Section extends QCC_Base_Molecule {
    
    protected $template_name = 'molecules/chart-section.php';
    protected $css_base_class = 'qcc-chart-section';
    protected $required_fields = array('charts');
    
    private $display_factory;
    
    public function __construct() {
        parent::__construct();
        $this->display_factory = QCC_Service_Container::get('display_factory');
    }
    
    /**
     * Get child components for this molecule
     */
    public function get_child_components() {
        return array('chart_container', 'button');
    }
    
    /**
     * Render complete chart section
     */
    public function render($data = array(), $attributes = array()) {
        $validation = $this->validate_data($data);
        if (is_wp_error($validation)) {
            return $this->render_error($validation);
        }
        
        $section_config = $this->prepare_section_config($data, $attributes);
        return $this->generate_section_html($section_config);
    }
    
    /**
     * Prepare section configuration
     */
    private function prepare_section_config($data, $attributes) {
        return array(
            'charts' => $data['charts'],
            'title' => $data['title'] ?? $this->translator->get('charts'),
            'subtitle' => $data['subtitle'] ?? '',
            'show_controls' => $data['show_controls'] ?? true,
            'show_legend' => $data['show_legend'] ?? true,
            'show_export' => $data['show_export'] ?? true,
            'layout' => $data['layout'] ?? 'grid', // 'grid', 'tabs', 'carousel'
            'responsive' => $data['responsive'] ?? true,
            'loading' => $data['loading'] ?? false,
            'error' => $data['error'] ?? null,
            'css_classes' => $this->build_css_classes($data),
            'attributes' => array_merge($this->get_default_attributes(), $attributes)
        );
    }
    
    /**
     * Generate section HTML
     */
    private function generate_section_html($config) {
        if ($config['error']) {
            return $this->render_error_section($config);
        }
        
        $section_parts = array();
        
        // Section header
        $section_parts[] = $this->render_section_header($config);
        
        // Chart controls
        if ($config['show_controls']) {
            $section_parts[] = $this->render_chart_controls($config);
        }
        
        // Charts container
        $section_parts[] = $this->render_charts_container($config);
        
        // Loading overlay
        if ($config['loading']) {
            $section_parts[] = $this->render_loading_overlay();
        }
        
        return sprintf(
            '<section class="%s" %s>%s</section>',
            esc_attr($config['css_classes']),
            $this->build_attributes_string($config['attributes']),
            implode("\n", $section_parts)
        );
    }
    
    /**
     * Render section header
     */
    private function render_section_header($config) {
        if (empty($config['title']) && empty($config['subtitle'])) {
            return '';
        }
        
        $header_parts = array();
        
        if (!empty($config['title'])) {
            $header_parts[] = sprintf(
                '<h3 class="qcc-chart-section-title">%s</h3>',
                esc_html($config['title'])
            );
        }
        
        if (!empty($config['subtitle'])) {
            $header_parts[] = sprintf(
                '<p class="qcc-chart-section-subtitle">%s</p>',
                esc_html($config['subtitle'])
            );
        }
        
        return sprintf(
            '<div class="qcc-chart-section-header">%s</div>',
            implode("\n", $header_parts)
        );
    }
    
    /**
     * Render chart controls
     */
    private function render_chart_controls($config) {
        $control_buttons = array();
        
        // Chart type selector
        $control_buttons[] = $this->render_chart_type_selector();
        
        // Export controls
        if ($config['show_export']) {
            $control_buttons[] = $this->render_export_controls();
        }
        
        // Fullscreen toggle
        $control_buttons[] = $this->display_factory->create_button(array(
            'label' => $this->translator->get('fullscreen'),
            'icon' => 'maximize',
            'variant' => 'outline',
            'size' => 'small',
            'action' => 'toggle_fullscreen'
        ));
        
        return sprintf(
            '<div class="qcc-chart-controls">%s</div>',
            implode("\n", $control_buttons)
        );
    }
    
    /**
     * Render chart type selector
     */
    private function render_chart_type_selector() {
        $chart_types = array(
            'bar' => $this->translator->get('bar_chart'),
            'line' => $this->translator->get('line_chart'),
            'pie' => $this->translator->get('pie_chart'),
            'doughnut' => $this->translator->get('doughnut_chart')
        );
        
        $options_html = array();
        foreach ($chart_types as $type => $label) {
            $options_html[] = sprintf(
                '<option value="%s">%s</option>',
                esc_attr($type),
                esc_html($label)
            );
        }
        
        return sprintf(
            '<div class="qcc-chart-type-selector">
                <label for="qcc-chart-type">%s</label>
                <select id="qcc-chart-type" name="chart_type" class="qcc-chart-type-select">
                    %s
                </select>
            </div>',
            esc_html($this->translator->get('chart_type')),
            implode("\n", $options_html)
        );
    }
    
    /**
     * Render export controls
     */
    private function render_export_controls() {
        return $this->display_factory->create_export_buttons(array('pdf', 'png', 'svg'));
    }
    
    /**
     * Render charts container
     */
    private function render_charts_container($config) {
        $layout = $config['layout'];
        
        switch ($layout) {
            case 'tabs':
                return $this->render_tabbed_charts($config);
            case 'carousel':
                return $this->render_carousel_charts($config);
            default:
                return $this->render_grid_charts($config);
        }
    }
    
    /**
     * Render grid layout charts
     */
    private function render_grid_charts($config) {
        $chart_items = array();
        
        foreach ($config['charts'] as $chart_config) {
            $chart_items[] = sprintf(
                '<div class="qcc-chart-item">%s</div>',
                $this->display_factory->create_chart($chart_config)
            );
        }
        
        return sprintf(
            '<div class="qcc-charts-grid">%s</div>',
            implode("\n", $chart_items)
        );
    }
    
    /**
     * Render tabbed charts
     */
    private function render_tabbed_charts($config) {
        $tabs = array();
        $tab_contents = array();
        $active_tab = '';
        
        foreach ($config['charts'] as $index => $chart_config) {
            $tab_id = 'chart-tab-' . $index;
            $chart_title = $chart_config['title'] ?? 'Chart ' . ($index + 1);
            
            if (empty($active_tab)) {
                $active_tab = $tab_id;
            }
            
            $active_class = ($tab_id === $active_tab) ? ' qcc-tab-active' : '';
            
            $tabs[] = sprintf(
                '<button type="button" class="qcc-chart-tab%s" data-tab="%s">%s</button>',
                $active_class,
                esc_attr($tab_id),
                esc_html($chart_title)
            );
            
            $tab_contents[] = sprintf(
                '<div class="qcc-chart-tab-content%s" id="%s">%s</div>',
                $active_class,
                esc_attr($tab_id),
                $this->display_factory->create_chart($chart_config)
            );
        }
        
        return sprintf(
            '<div class="qcc-charts-tabs">
                <div class="qcc-chart-tab-nav">%s</div>
                <div class="qcc-chart-tab-body">%s</div>
            </div>',
            implode("\n", $tabs),
            implode("\n", $tab_contents)
        );
    }
    
    /**
     * Render carousel charts
     */
    private function render_carousel_charts($config) {
        $chart_slides = array();
        
        foreach ($config['charts'] as $index => $chart_config) {
            $active_class = ($index === 0) ? ' qcc-slide-active' : '';
            
            $chart_slides[] = sprintf(
                '<div class="qcc-chart-slide%s" data-slide="%d">%s</div>',
                $active_class,
                $index,
                $this->display_factory->create_chart($chart_config)
            );
        }
        
        // Navigation controls
        $prev_button = $this->display_factory->create_button(array(
            'label' => $this->translator->get('previous'),
            'icon' => 'chevron-left',
            'variant' => 'outline',
            'size' => 'small',
            'action' => 'carousel_prev',
            'aria_label' => $this->translator->get('previous_chart')
        ));
        
        $next_button = $this->display_factory->create_button(array(
            'label' => $this->translator->get('next'),
            'icon' => 'chevron-right',
            'icon_position' => 'right',
            'variant' => 'outline',
            'size' => 'small',
            'action' => 'carousel_next',
            'aria_label' => $this->translator->get('next_chart')
        ));
        
        return sprintf(
            '<div class="qcc-charts-carousel">
                <div class="qcc-carousel-container">%s</div>
                <div class="qcc-carousel-controls">
                    %s
                    <span class="qcc-carousel-indicator"></span>
                    %s
                </div>
            </div>',
            implode("\n", $chart_slides),
            $prev_button,
            $next_button
        );
    }
    
    /**
     * Render loading overlay
     */
    private function render_loading_overlay() {
        return '<div class="qcc-chart-section-loading">
            <div class="qcc-spinner"></div>
            <span class="qcc-loading-text">' . esc_html($this->translator->get('loading_charts')) . '</span>
        </div>';
    }
    
    /**
     * Render error section
     */
    private function render_error_section($config) {
        return sprintf(
            '<section class="%s qcc-chart-section-error">
                <div class="qcc-error-content">
                    <span class="qcc-error-icon">%s</span>
                    <h3>%s</h3>
                    <p>%s</p>
                </div>
            </section>',
            esc_attr($config['css_classes']),
            $this->get_icon_svg('alert-triangle'),
            esc_html($this->translator->get('chart_error')),
            esc_html($config['error'])
        );
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes($data) {
        $classes = array($this->css_base_class);
        
        // Layout class
        $layout = $data['layout'] ?? 'grid';
        $classes[] = $this->css_base_class . '--' . sanitize_html_class($layout);
        
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
        
        // Controls class
        if ($data['show_controls'] ?? true) {
            $classes[] = $this->css_base_class . '--with-controls';
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
            if ($name !== 'class') {
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
            'maximize' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path></svg>',
            'chevron-left' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>',
            'chevron-right' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"></polyline></svg>',
            'alert-triangle' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get layout template for molecule
     */
    public function get_layout_template() {
        return $this->template_name;
    }
    
    /**
     * Map data to children components
     */
    public function map_data_to_children($data) {
        return array(
            'chart_container' => $data['charts'] ?? array(),
            'button' => $data['controls'] ?? array()
        );
    }
    
    /**
     * Assemble child components
     */
    public function assemble_components($components) {
        return implode("\n", $components);
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
        return array('class' => $this->css_base_class);
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
            'css' => array('qcc-chart-section.css'),
            'js' => array('qcc-chart-section.js'),
            'dependencies' => array('chart.js')
        );
    }
}
<?php
/**
 * QCC Layout Builder Service
 * 
 * Handles responsive layout generation including containers, grids,
 * sections and mobile-first layout adaptations.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Builders
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Layout_Builder extends QCC_Base_Builder {
    
    private $layout_configs = array();
    private $breakpoints = array();
    private $grid_system = array();
    
    public function __construct() {
        parent::__construct();
        $this->init_layout_configs();
    }
    
    /**
     * Build complete layout
     */
    public function build($config = array()) {
        $layout_type = $config['type'] ?? 'default';
        
        switch ($layout_type) {
            case 'container_only':
                return $this->build_container($config);
            case 'grid_only':
                return $this->build_grid($config);
            case 'section_only':
                return $this->build_section($config);
            case 'responsive_only':
                return $this->build_responsive_wrapper($config);
            default:
                return $this->build_complete_layout($config);
        }
    }
    
    /**
     * Build responsive container
     */
    public function build_container($config) {
        $container_type = $config['container_type'] ?? 'fluid';
        $content = $config['content'] ?? '';
        $attributes = $config['attributes'] ?? array();
        
        $container_classes = $this->get_container_classes($container_type, $config);
        $container_attributes = $this->build_attributes($attributes);
        
        return sprintf(
            '<div class="%s"%s>%s</div>',
            esc_attr($container_classes),
            $container_attributes,
            $content
        );
    }
    
    /**
     * Build CSS grid layout
     */
    public function build_grid($config) {
        $grid_type = $config['grid_type'] ?? 'auto';
        $columns = $config['columns'] ?? 12;
        $gap = $config['gap'] ?? '1rem';
        $items = $config['items'] ?? array();
        
        $grid_classes = $this->get_grid_classes($grid_type, $columns, $config);
        $grid_styles = $this->get_grid_styles($columns, $gap, $config);
        
        $items_html = array();
        foreach ($items as $item) {
            $items_html[] = $this->build_grid_item($item);
        }
        
        return sprintf(
            '<div class="%s" style="%s">%s</div>',
            esc_attr($grid_classes),
            esc_attr($grid_styles),
            implode("\n", $items_html)
        );
    }
    
    /**
     * Build semantic section
     */
    public function build_section($config) {
        $section_type = $config['section_type'] ?? 'default';
        $content = $config['content'] ?? '';
        $header = $config['header'] ?? '';
        $footer = $config['footer'] ?? '';
        
        $section_classes = $this->get_section_classes($section_type, $config);
        $section_html = array();
        
        if (!empty($header)) {
            $section_html[] = sprintf('<header class="qcc-section-header">%s</header>', $header);
        }
        
        $section_html[] = sprintf('<div class="qcc-section-content">%s</div>', $content);
        
        if (!empty($footer)) {
            $section_html[] = sprintf('<footer class="qcc-section-footer">%s</footer>', $footer);
        }
        
        return sprintf(
            '<section class="%s">%s</section>',
            esc_attr($section_classes),
            implode("\n", $section_html)
        );
    }
    
    /**
     * Build responsive wrapper
     */
    public function build_responsive_wrapper($config) {
        $breakpoint_configs = $config['breakpoints'] ?? array();
        $content = $config['content'] ?? '';
        
        $responsive_classes = $this->get_responsive_classes($breakpoint_configs);
        $responsive_styles = $this->get_responsive_styles($breakpoint_configs);
        
        return sprintf(
            '<div class="%s" style="%s">%s</div>',
            esc_attr($responsive_classes),
            esc_attr($responsive_styles),
            $content
        );
    }
    
    /**
     * Build complete calculator layout
     */
    public function build_complete_layout($config) {
        $layout_structure = $config['layout_structure'] ?? 'two_column';
        $components = $config['components'] ?? array();
        
        switch ($layout_structure) {
            case 'single_column':
                return $this->build_single_column_layout($components, $config);
            case 'two_column':
                return $this->build_two_column_layout($components, $config);
            case 'three_column':
                return $this->build_three_column_layout($components, $config);
            case 'tabbed':
                return $this->build_tabbed_layout($components, $config);
            default:
                return $this->build_flexible_layout($components, $config);
        }
    }
    
    /**
     * Build single column layout
     */
    private function build_single_column_layout($components, $config) {
        $sections = array();
        
        if (isset($components['header'])) {
            $sections[] = $this->build_section(array(
                'section_type' => 'header',
                'content' => $components['header']
            ));
        }
        
        if (isset($components['controls'])) {
            $sections[] = $this->build_section(array(
                'section_type' => 'controls',
                'content' => $components['controls']
            ));
        }
        
        if (isset($components['form'])) {
            $sections[] = $this->build_section(array(
                'section_type' => 'form',
                'content' => $components['form']
            ));
        }
        
        if (isset($components['results'])) {
            $sections[] = $this->build_section(array(
                'section_type' => 'results',
                'content' => $components['results']
            ));
        }
        
        if (isset($components['charts'])) {
            $sections[] = $this->build_section(array(
                'section_type' => 'charts',
                'content' => $components['charts']
            ));
        }
        
        return $this->build_container(array(
            'container_type' => 'responsive',
            'content' => implode("\n", $sections),
            'attributes' => array('class' => 'qcc-single-column-layout')
        ));
    }
    
    /**
     * Build two column layout
     */
    private function build_two_column_layout($components, $config) {
        $left_column = array();
        $right_column = array();
        
        // Header spans full width
        $header_section = '';
        if (isset($components['header'])) {
            $header_section = $this->build_section(array(
                'section_type' => 'header',
                'content' => $components['header']
            ));
        }
        
        // Controls in left column
        if (isset($components['controls'])) {
            $left_column[] = $this->build_section(array(
                'section_type' => 'controls',
                'content' => $components['controls']
            ));
        }
        
        // Form in left column
        if (isset($components['form'])) {
            $left_column[] = $this->build_section(array(
                'section_type' => 'form',
                'content' => $components['form']
            ));
        }
        
        // Results in right column
        if (isset($components['results'])) {
            $right_column[] = $this->build_section(array(
                'section_type' => 'results',
                'content' => $components['results']
            ));
        }
        
        // Charts in right column
        if (isset($components['charts'])) {
            $right_column[] = $this->build_section(array(
                'section_type' => 'charts',
                'content' => $components['charts']
            ));
        }
        
        $grid_content = array();
        
        if (!empty($header_section)) {
            $grid_content[] = sprintf(
                '<div class="qcc-grid-item qcc-full-width">%s</div>',
                $header_section
            );
        }
        
        $grid_content[] = sprintf(
            '<div class="qcc-grid-item qcc-left-column">%s</div>',
            implode("\n", $left_column)
        );
        
        $grid_content[] = sprintf(
            '<div class="qcc-grid-item qcc-right-column">%s</div>',
            implode("\n", $right_column)
        );
        
        return $this->build_grid(array(
            'grid_type' => 'responsive',
            'columns' => 2,
            'gap' => '2rem',
            'items' => $grid_content,
            'attributes' => array('class' => 'qcc-two-column-layout')
        ));
    }
    
    /**
     * Build three column layout
     */
    private function build_three_column_layout($components, $config) {
        $columns = array(
            'left' => array(),
            'center' => array(),
            'right' => array()
        );
        
        // Distribute components across columns
        if (isset($components['controls'])) {
            $columns['left'][] = $components['controls'];
        }
        
        if (isset($components['form'])) {
            $columns['center'][] = $components['form'];
        }
        
        if (isset($components['results'])) {
            $columns['right'][] = $components['results'];
        }
        
        if (isset($components['charts'])) {
            $columns['center'][] = $components['charts'];
        }
        
        $grid_items = array();
        foreach ($columns as $column_name => $column_content) {
            if (!empty($column_content)) {
                $grid_items[] = sprintf(
                    '<div class="qcc-grid-item qcc-%s-column">%s</div>',
                    esc_attr($column_name),
                    implode("\n", $column_content)
                );
            }
        }
        
        return $this->build_grid(array(
            'grid_type' => 'responsive',
            'columns' => 3,
            'gap' => '1.5rem',
            'items' => $grid_items,
            'attributes' => array('class' => 'qcc-three-column-layout')
        ));
    }
    
    /**
     * Build tabbed layout
     */
    private function build_tabbed_layout($components, $config) {
        $tabs = array();
        $tab_contents = array();
        
        $tab_configs = array(
            'input' => array('label' => 'input_tab', 'icon' => 'edit', 'components' => array('form', 'controls')),
            'results' => array('label' => 'results_tab', 'icon' => 'bar-chart', 'components' => array('results')),
            'charts' => array('label' => 'charts_tab', 'icon' => 'pie-chart', 'components' => array('charts'))
        );
        
        foreach ($tab_configs as $tab_id => $tab_config) {
            $tab_content = array();
            foreach ($tab_config['components'] as $component_key) {
                if (isset($components[$component_key])) {
                    $tab_content[] = $components[$component_key];
                }
            }
            
            if (!empty($tab_content)) {
                $tabs[] = sprintf(
                    '<button type="button" class="qcc-tab-button" data-tab="%s">
                        %s %s
                    </button>',
                    esc_attr($tab_id),
                    $this->get_icon_svg($tab_config['icon']),
                    esc_html($this->translator->get($tab_config['label']))
                );
                
                $tab_contents[] = sprintf(
                    '<div class="qcc-tab-content" id="qcc-tab-%s">%s</div>',
                    esc_attr($tab_id),
                    implode("\n", $tab_content)
                );
            }
        }
        
        $header_content = '';
        if (isset($components['header'])) {
            $header_content = $components['header'];
        }
        
        return sprintf(
            '<div class="qcc-tabbed-layout">
                %s
                <div class="qcc-tabs-header">%s</div>
                <div class="qcc-tabs-body">%s</div>
            </div>',
            $header_content,
            implode("\n", $tabs),
            implode("\n", $tab_contents)
        );
    }
    
    /**
     * Build flexible layout
     */
    private function build_flexible_layout($components, $config) {
        $layout_order = $config['layout_order'] ?? array('header', 'controls', 'form', 'results', 'charts');
        $sections = array();
        
        foreach ($layout_order as $component_key) {
            if (isset($components[$component_key])) {
                $sections[] = $this->build_section(array(
                    'section_type' => $component_key,
                    'content' => $components[$component_key]
                ));
            }
        }
        
        return $this->build_container(array(
            'container_type' => 'responsive',
            'content' => implode("\n", $sections),
            'attributes' => array('class' => 'qcc-flexible-layout')
        ));
    }
    
    /**
     * Build grid item
     */
    private function build_grid_item($item) {
        if (is_string($item)) {
            return sprintf('<div class="qcc-grid-item">%s</div>', $item);
        }
        
        $content = $item['content'] ?? '';
        $span = $item['span'] ?? 1;
        $classes = $item['classes'] ?? '';
        
        return sprintf(
            '<div class="qcc-grid-item qcc-span-%d %s">%s</div>',
            intval($span),
            esc_attr($classes),
            $content
        );
    }
    
    /**
     * Get container classes
     */
    private function get_container_classes($container_type, $config) {
        $classes = array('qcc-container');
        
        switch ($container_type) {
            case 'fluid':
                $classes[] = 'qcc-container-fluid';
                break;
            case 'fixed':
                $classes[] = 'qcc-container-fixed';
                break;
            case 'responsive':
                $classes[] = 'qcc-container-responsive';
                break;
        }
        
        if (isset($config['additional_classes'])) {
            $classes = array_merge($classes, (array) $config['additional_classes']);
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get grid classes
     */
    private function get_grid_classes($grid_type, $columns, $config) {
        $classes = array('qcc-grid');
        
        switch ($grid_type) {
            case 'auto':
                $classes[] = 'qcc-grid-auto';
                break;
            case 'responsive':
                $classes[] = 'qcc-grid-responsive';
                break;
            case 'fixed':
                $classes[] = 'qcc-grid-fixed';
                break;
        }
        
        $classes[] = 'qcc-grid-' . $columns . '-col';
        
        return implode(' ', $classes);
    }
    
    /**
     * Get grid styles
     */
    private function get_grid_styles($columns, $gap, $config) {
        $styles = array();
        
        if ($config['grid_type'] === 'auto') {
            $styles[] = 'display: grid';
            $styles[] = 'grid-template-columns: repeat(auto-fit, minmax(250px, 1fr))';
        } else {
            $styles[] = 'display: grid';
            $styles[] = sprintf('grid-template-columns: repeat(%d, 1fr)', $columns);
        }
        
        $styles[] = 'gap: ' . esc_attr($gap);
        
        return implode('; ', $styles);
    }
    
    /**
     * Get section classes
     */
    private function get_section_classes($section_type, $config) {
        $classes = array('qcc-section', 'qcc-section-' . $section_type);
        
        if (isset($config['additional_classes'])) {
            $classes = array_merge($classes, (array) $config['additional_classes']);
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get responsive classes
     */
    private function get_responsive_classes($breakpoint_configs) {
        $classes = array('qcc-responsive');
        
        foreach ($breakpoint_configs as $breakpoint => $config) {
            if (isset($config['hidden']) && $config['hidden']) {
                $classes[] = 'qcc-hidden-' . $breakpoint;
            }
            if (isset($config['visible']) && $config['visible']) {
                $classes[] = 'qcc-visible-' . $breakpoint;
            }
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * Get responsive styles
     */
    private function get_responsive_styles($breakpoint_configs) {
        // Inline styles for critical responsive behavior
        $styles = array();
        
        foreach ($breakpoint_configs as $breakpoint => $config) {
            if (isset($config['width'])) {
                $styles[] = sprintf('width: %s', esc_attr($config['width']));
            }
        }
        
        return implode('; ', $styles);
    }
    
    /**
     * Build attributes string
     */
    private function build_attributes($attributes) {
        $attr_string = '';
        
        foreach ($attributes as $name => $value) {
            if ($name === 'class') {
                continue; // Handle classes separately
            }
            $attr_string .= sprintf(' %s="%s"', esc_attr($name), esc_attr($value));
        }
        
        return $attr_string;
    }
    
    /**
     * Initialize layout configurations
     */
    private function init_layout_configs() {
        $this->breakpoints = array(
            'xs' => '0px',
            'sm' => '576px',
            'md' => '768px',
            'lg' => '992px',
            'xl' => '1200px',
            'xxl' => '1400px'
        );
        
        $this->grid_system = array(
            'columns' => 12,
            'gutter' => '1rem',
            'container_max_widths' => array(
                'sm' => '540px',
                'md' => '720px',
                'lg' => '960px',
                'xl' => '1140px',
                'xxl' => '1320px'
            )
        );
        
        $this->layout_configs = array(
            'default' => 'two_column',
            'mobile' => 'single_column',
            'tablet' => 'two_column',
            'desktop' => 'two_column'
        );
    }
    
    /**
     * Get SVG icon
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'edit' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
            'bar-chart' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
            'pie-chart' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>'
        );
        
        return $icons[$icon_name] ?? '';
    }
    
    /**
     * Get required dependencies
     */
    public function get_dependencies() {
        return array('translator', 'template_manager');
    }
    
    /**
     * Get required assets
     */
    public function get_required_assets() {
        return array(
            'css' => array('qcc-layout-builder.css', 'qcc-grid-system.css'),
            'js' => array('qcc-responsive.js'),
            'dependencies' => array()
        );
    }
}
php

  QCC Layout Coordinator
  
  Coordinates layout decisions, responsive behavior, and component
  positioning based on device type, content size, and user preferences.
 
  @package QualityCostCalculator
  @subpackage RenderingOrchestration
  @since 3.0.0
 

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Layout_Coordinator {
    
    private $layout_rules = array();
    private $breakpoints = array();
    private $device_detector;
    private $layout_cache = array();
    
    public function __construct() {
        $this-init_layout_rules();
        $this-init_breakpoints();
        $this-init_device_detection();
    }
    
    
      Coordinate layout for given components and context
     
    public function coordinate_layout($components, $context = array()) {
        $layout_config = $this-determine_layout_config($components, $context);
        $layout_strategy = $this-select_layout_strategy($layout_config);
        
        return $this-apply_layout_strategy($components, $layout_strategy, $context);
    }
    
    
      Determine optimal layout configuration
     
    private function determine_layout_config($components, $context) {
        $device_info = $this-detect_device_characteristics();
        $content_analysis = $this-analyze_content_requirements($components);
        $user_preferences = $this-get_user_preferences($context);
        
        return array(
            'device' = $device_info,
            'content' = $content_analysis,
            'preferences' = $user_preferences,
            'constraints' = $this-get_layout_constraints($context)
        );
    }
    
    
      Select appropriate layout strategy
     
    private function select_layout_strategy($layout_config) {
        $device = $layout_config['device'];
        $content = $layout_config['content'];
        $preferences = $layout_config['preferences'];
        
         Mobile-first approach
        if ($device['is_mobile']) {
            return $this-get_mobile_layout_strategy($content, $preferences);
        }
        
         Tablet considerations
        if ($device['is_tablet']) {
            return $this-get_tablet_layout_strategy($content, $preferences);
        }
        
         Desktop layout strategies
        return $this-get_desktop_layout_strategy($content, $preferences);
    }
    
    
      Apply selected layout strategy
     
    private function apply_layout_strategy($components, $strategy, $context) {
        switch ($strategy['type']) {
            case 'single_column'
                return $this-apply_single_column_layout($components, $strategy, $context);
            
            case 'two_column'
                return $this-apply_two_column_layout($components, $strategy, $context);
            
            case 'three_column'
                return $this-apply_three_column_layout($components, $strategy, $context);
            
            case 'tabbed'
                return $this-apply_tabbed_layout($components, $strategy, $context);
            
            case 'accordion'
                return $this-apply_accordion_layout($components, $strategy, $context);
            
            case 'grid'
                return $this-apply_grid_layout($components, $strategy, $context);
            
            default
                return $this-apply_flexible_layout($components, $strategy, $context);
        }
    }
    
    
      Get mobile layout strategy
     
    private function get_mobile_layout_strategy($content, $preferences) {
         Prefer single column or tabbed on mobile
        if ($content['component_count']  4) {
            return array(
                'type' = 'tabbed',
                'orientation' = 'vertical',
                'collapse_sections' = true,
                'priority_order' = array('form', 'results', 'charts', 'controls')
            );
        }
        
        return array(
            'type' = 'single_column',
            'stack_order' = array('controls', 'form', 'results', 'charts'),
            'collapse_sections' = true
        );
    }
    
    
      Get tablet layout strategy
     
    private function get_tablet_layout_strategy($content, $preferences) {
         Prefer two-column layout for tablets
        if ($content['has_complex_forms']) {
            return array(
                'type' = 'two_column',
                'left_column' = array('controls', 'form'),
                'right_column' = array('results', 'charts'),
                'breakpoint' = 'tablet'
            );
        }
        
        return array(
            'type' = 'single_column',
            'stack_order' = array('controls', 'form', 'results', 'charts'),
            'responsive_breakpoint' = 'tablet'
        );
    }
    
    
      Get desktop layout strategy
     
    private function get_desktop_layout_strategy($content, $preferences) {
        $preferred_layout = $preferences['layout']  'two_column';
        
         Override based on content complexity
        if ($content['component_count']  6) {
            $preferred_layout = 'three_column';
        }
        
        switch ($preferred_layout) {
            case 'three_column'
                return array(
                    'type' = 'three_column',
                    'left_column' = array('controls'),
                    'center_column' = array('form', 'charts'),
                    'right_column' = array('results'),
                    'column_ratios' = array('1fr', '2fr', '1.5fr')
                );
            
            case 'grid'
                return array(
                    'type' = 'grid',
                    'grid_template' = 'auto-fit',
                    'min_column_width' = '300px',
                    'gap' = '2rem'
                );
            
            default
                return array(
                    'type' = 'two_column',
                    'left_column' = array('controls', 'form'),
                    'right_column' = array('results', 'charts'),
                    'column_ratio' = '1fr 1fr'
                );
        }
    }
    
    
      Apply single column layout
     
    private function apply_single_column_layout($components, $strategy, $context) {
        $layout_html = array();
        $order = $strategy['stack_order']  array('controls', 'form', 'results', 'charts');
        
        foreach ($order as $component_key) {
            if (isset($components[$component_key])) {
                $section_html = $this-wrap_component_in_section(
                    $components[$component_key],
                    $component_key,
                    $strategy
                );
                $layout_html[] = $section_html;
            }
        }
        
        return $this-wrap_in_container($layout_html, 'single-column', $strategy);
    }
    
    
      Apply two column layout
     
    private function apply_two_column_layout($components, $strategy, $context) {
        $left_components = array();
        $right_components = array();
        
        $left_order = $strategy['left_column']  array('controls', 'form');
        $right_order = $strategy['right_column']  array('results', 'charts');
        
         Build left column
        foreach ($left_order as $component_key) {
            if (isset($components[$component_key])) {
                $left_components[] = $this-wrap_component_in_section(
                    $components[$component_key],
                    $component_key,
                    $strategy
                );
            }
        }
        
         Build right column
        foreach ($right_order as $component_key) {
            if (isset($components[$component_key])) {
                $right_components[] = $this-wrap_component_in_section(
                    $components[$component_key],
                    $component_key,
                    $strategy
                );
            }
        }
        
        $column_ratio = $strategy['column_ratio']  '1fr 1fr';
        
        $layout_html = sprintf(
            'div class=qcc-two-column-grid style=display grid; grid-template-columns %s; gap 2rem;
                div class=qcc-left-column%sdiv
                div class=qcc-right-column%sdiv
            div',
            esc_attr($column_ratio),
            implode(n, $left_components),
            implode(n, $right_components)
        );
        
        return $this-wrap_in_container(array($layout_html), 'two-column', $strategy);
    }
    
    
      Apply three column layout
     
    private function apply_three_column_layout($components, $strategy, $context) {
        $columns = array('left' = array(), 'center' = array(), 'right' = array());
        
        $column_assignments = array(
            'left' = $strategy['left_column']  array('controls'),
            'center' = $strategy['center_column']  array('form'),
            'right' = $strategy['right_column']  array('results', 'charts')
        );
        
        foreach ($column_assignments as $column_name = $component_keys) {
            foreach ($component_keys as $component_key) {
                if (isset($components[$component_key])) {
                    $columns[$column_name][] = $this-wrap_component_in_section(
                        $components[$component_key],
                        $component_key,
                        $strategy
                    );
                }
            }
        }
        
        $column_ratios = $strategy['column_ratios']  array('1fr', '2fr', '1.5fr');
        $grid_template = implode(' ', $column_ratios);
        
        $layout_html = sprintf(
            'div class=qcc-three-column-grid style=display grid; grid-template-columns %s; gap 1.5rem;
                div class=qcc-left-column%sdiv
                div class=qcc-center-column%sdiv
                div class=qcc-right-column%sdiv
            div',
            esc_attr($grid_template),
            implode(n, $columns['left']),
            implode(n, $columns['center']),
            implode(n, $columns['right'])
        );
        
        return $this-wrap_in_container(array($layout_html), 'three-column', $strategy);
    }
    
    
      Apply tabbed layout
     
    private function apply_tabbed_layout($components, $strategy, $context) {
        $tabs = array();
        $tab_contents = array();
        
        $tab_config = array(
            'input' = array('label' = 'Input', 'icon' = 'edit-3', 'components' = array('controls', 'form')),
            'results' = array('label' = 'Results', 'icon' = 'bar-chart-2', 'components' = array('results')),
            'charts' = array('label' = 'Charts', 'icon' = 'pie-chart', 'components' = array('charts'))
        );
        
        $active_tab = '';
        
        foreach ($tab_config as $tab_id = $tab_info) {
            $tab_content = array();
            $has_content = false;
            
            foreach ($tab_info['components'] as $component_key) {
                if (isset($components[$component_key])) {
                    $tab_content[] = $components[$component_key];
                    $has_content = true;
                }
            }
            
            if ($has_content) {
                if (empty($active_tab)) {
                    $active_tab = $tab_id;
                }
                
                $active_class = ($tab_id === $active_tab)  ' qcc-tab-active'  '';
                
                $tabs[] = sprintf(
                    'button type=button class=qcc-tab-button%s data-tab=%s
                        %s span%sspan
                    button',
                    $active_class,
                    esc_attr($tab_id),
                    $this-get_icon_svg($tab_info['icon']),
                    esc_html($tab_info['label'])
                );
                
                $tab_contents[] = sprintf(
                    'div class=qcc-tab-content%s id=qcc-tab-%s%sdiv',
                    $active_class,
                    esc_attr($tab_id),
                    implode(n, $tab_content)
                );
            }
        }
        
        $layout_html = sprintf(
            'div class=qcc-tabbed-layout
                div class=qcc-tab-navigation%sdiv
                div class=qcc-tab-body%sdiv
            div',
            implode(n, $tabs),
            implode(n, $tab_contents)
        );
        
        return $this-wrap_in_container(array($layout_html), 'tabbed', $strategy);
    }
    
    
      Apply accordion layout
     
    private function apply_accordion_layout($components, $strategy, $context) {
        $accordion_sections = array();
        
        $section_config = array(
            'controls' = array('title' = 'Settings', 'icon' = 'settings', 'expanded' = false),
            'form' = array('title' = 'Input Values', 'icon' = 'edit-3', 'expanded' = true),
            'results' = array('title' = 'Results', 'icon' = 'bar-chart-2', 'expanded' = true),
            'charts' = array('title' = 'Charts', 'icon' = 'pie-chart', 'expanded' = false)
        );
        
        foreach ($section_config as $component_key = $section_info) {
            if (isset($components[$component_key])) {
                $expanded_class = $section_info['expanded']  ' qcc-accordion-expanded'  '';
                
                $accordion_sections[] = sprintf(
                    'div class=qcc-accordion-section%s
                        button type=button class=qcc-accordion-header data-section=%s
                            %s span%sspan %s
                        button
                        div class=qcc-accordion-content%sdiv
                    div',
                    $expanded_class,
                    esc_attr($component_key),
                    $this-get_icon_svg($section_info['icon']),
                    esc_html($section_info['title']),
                    $this-get_icon_svg('chevron-down'),
                    $components[$component_key]
                );
            }
        }
        
        $layout_html = sprintf(
            'div class=qcc-accordion-layout%sdiv',
            implode(n, $accordion_sections)
        );
        
        return $this-wrap_in_container(array($layout_html), 'accordion', $strategy);
    }
    
    
      Apply grid layout
     
    private function apply_grid_layout($components, $strategy, $context) {
        $grid_items = array();
        
        foreach ($components as $component_key = $component_html) {
            $grid_items[] = sprintf(
                'div class=qcc-grid-item qcc-grid-%s%sdiv',
                esc_attr($component_key),
                $component_html
            );
        }
        
        $min_width = $strategy['min_column_width']  '300px';
        $gap = $strategy['gap']  '2rem';
        
        $layout_html = sprintf(
            'div class=qcc-grid-layout style=display grid; grid-template-columns repeat(auto-fit, minmax(%s, 1fr)); gap %s;%sdiv',
            esc_attr($min_width),
            esc_attr($gap),
            implode(n, $grid_items)
        );
        
        return $this-wrap_in_container(array($layout_html), 'grid', $strategy);
    }
    
    
      Apply flexible layout
     
    private function apply_flexible_layout($components, $strategy, $context) {
        $layout_html = array();
        
        foreach ($components as $component_key = $component_html) {
            $layout_html[] = $this-wrap_component_in_section($component_html, $component_key, $strategy);
        }
        
        return $this-wrap_in_container($layout_html, 'flexible', $strategy);
    }
    
    
      Wrap component in section
     
    private function wrap_component_in_section($component_html, $component_key, $strategy) {
        $collapse_class = ($strategy['collapse_sections']  false)  ' qcc-collapsible'  '';
        
        return sprintf(
            'section class=qcc-section qcc-section-%s%s%ssection',
            esc_attr($component_key),
            $collapse_class,
            $component_html
        );
    }
    
    
      Wrap layout in container
     
    private function wrap_in_container($layout_parts, $layout_type, $strategy) {
        $responsive_class = $this-get_responsive_classes($strategy);
        
        return sprintf(
            'div class=qcc-layout-container qcc-layout-%s%s%sdiv',
            esc_attr($layout_type),
            $responsive_class,
            implode(n, $layout_parts)
        );
    }
    
    
      Get responsive CSS classes
     
    private function get_responsive_classes($strategy) {
        $classes = array();
        
        if (isset($strategy['breakpoint'])) {
            $classes[] = 'qcc-responsive-' . $strategy['breakpoint'];
        }
        
        if (isset($strategy['responsive_breakpoint'])) {
            $classes[] = 'qcc-responsive-' . $strategy['responsive_breakpoint'];
        }
        
        return empty($classes)  ''  ' ' . implode(' ', $classes);
    }
    
    
      Detect device characteristics
     
    private function detect_device_characteristics() {
         Simple user agent detection (in production, use more sophisticated detection)
        $user_agent = $_SERVER['HTTP_USER_AGENT']  '';
        
        return array(
            'is_mobile' = wp_is_mobile(),
            'is_tablet' = $this-is_tablet($user_agent),
            'screen_size' = $this-estimate_screen_size($user_agent),
            'touch_device' = $this-is_touch_device($user_agent)
        );
    }
    
    
      Check if device is tablet
     
    private function is_tablet($user_agent) {
        return preg_match('(tabletipadplaybooksilk)(android(!.mobile))i', $user_agent);
    }
    
    
      Estimate screen size category
     
    private function estimate_screen_size($user_agent) {
        if (wp_is_mobile()) {
            return 'small';
        }
        
        if ($this-is_tablet($user_agent)) {
            return 'medium';
        }
        
        return 'large';
    }
    
    
      Check if touch device
     
    private function is_touch_device($user_agent) {
        return wp_is_mobile()  $this-is_tablet($user_agent);
    }
    
    
      Analyze content requirements
     
    private function analyze_content_requirements($components) {
        return array(
            'component_count' = count($components),
            'has_complex_forms' = isset($components['form']),
            'has_charts' = isset($components['charts']),
            'has_results' = isset($components['results']),
            'estimated_height' = $this-estimate_content_height($components)
        );
    }
    
    
      Estimate content height
     
    private function estimate_content_height($components) {
        $height_estimates = array(
            'controls' = 150,
            'form' = 400,
            'results' = 300,
            'charts' = 350
        );
        
        $total_height = 0;
        foreach ($components as $key = $component) {
            $total_height += $height_estimates[$key]  200;
        }
        
        return $total_height;
    }
    
    
      Get user preferences
     
    private function get_user_preferences($context) {
        return array(
            'layout' = $context['layout']  'auto',
            'compact_mode' = $context['compact']  false,
            'accessibility_mode' = $context['accessibility']  false
        );
    }
    
    
      Get layout constraints
     
    private function get_layout_constraints($context) {
        return array(
            'max_width' = $context['max_width']  '1200px',
            'min_width' = $context['min_width']  '320px',
            'container_padding' = $context['padding']  '1rem'
        );
    }
    
    
      Initialize layout rules
     
    private function init_layout_rules() {
        $this-layout_rules = array(
            'mobile' = array(
                'max_columns' = 1,
                'prefer_vertical_stack' = true,
                'use_collapsible_sections' = true
            ),
            'tablet' = array(
                'max_columns' = 2,
                'prefer_two_column' = true,
                'allow_tabbed_interface' = true
            ),
            'desktop' = array(
                'max_columns' = 3,
                'allow_complex_layouts' = true,
                'prefer_horizontal_flow' = true
            )
        );
    }
    
    
      Initialize breakpoints
     
    private function init_breakpoints() {
        $this-breakpoints = array(
            'mobile' = array('min' = 0, 'max' = 767),
            'tablet' = array('min' = 768, 'max' = 1023),
            'desktop' = array('min' = 1024, 'max' = 9999)
        );
    }
    
    
      Initialize device detection
     
    private function init_device_detection() {
         Placeholder for more sophisticated device detection
        $this-device_detector = null;
    }
    
    
      Get SVG icon
     
    private function get_icon_svg($icon_name) {
        $icons = array(
            'edit-3' = 'svg width=16 height=16 viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=2path d=M12 20h9pathpath d=M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5zpathsvg',
            'bar-chart-2' = 'svg width=16 height=16 viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=2line x1=18 y1=20 x2=18 y2=10lineline x1=12 y1=20 x2=12 y2=4lineline x1=6 y1=20 x2=6 y2=14linesvg',
            'pie-chart' = 'svg width=16 height=16 viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=2path d=M21.21 15.89A10 10 0 1 1 8 2.83pathpath d=M22 12A10 10 0 0 0 12 2v10zpathsvg',
            'settings' = 'svg width=16 height=16 viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=2circle cx=12 cy=12 r=3circlepath d=M12 1v6m0 6v6m11-7h-6m-6 0H1m17-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM7 21a4 4 0 1 1-8 0 4 4 0 0 1 8 0zpathsvg',
            'chevron-down' = 'svg width=16 height=16 viewBox=0 0 24 24 fill=none stroke=currentColor stroke-width=2polyline points=6,9 12,15 18,9polylinesvg'
        );
        
        return $icons[$icon_name]  '';
    }
}
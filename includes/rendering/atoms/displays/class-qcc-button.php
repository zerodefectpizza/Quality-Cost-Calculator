<?php
/**
 * QCC Button Atom
 * 
 * Atomic component for rendering various types of buttons with
 * icons, states, and accessibility features.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms\Displays
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Button extends QCC_Base_Atom {
    
    protected $template_name = 'atoms/button.php';
    protected $css_base_class = 'qcc-button';
    protected $required_fields = array('label');
    
    protected $default_attributes = array(
        'type' => 'button',
        'class' => 'qcc-button'
    );
    
    /**
     * Render button with all features
     */
    public function render($data = array(), $attributes = array()) {
        // Validate required data
        $validation = $this->validate_data($data);
        if (is_wp_error($validation)) {
            return $this->render_error($validation);
        }
        
        // Prepare button configuration
        $button_config = $this->prepare_button_config($data, $attributes);
        
        // Generate button HTML
        return $this->generate_button_html($button_config);
    }
    
    /**
     * Prepare button configuration
     */
    private function prepare_button_config($data, $attributes) {
        return array(
            'type' => $data['type'] ?? 'button',
            'label' => $data['label'],
            'icon' => $data['icon'] ?? null,
            'icon_position' => $data['icon_position'] ?? 'left',
            'variant' => $data['variant'] ?? 'default',
            'size' => $data['size'] ?? 'medium',
            'state' => $data['state'] ?? 'normal',
            'action' => $data['action'] ?? null,
            'url' => $data['url'] ?? null,
            'target' => $data['target'] ?? '_self',
            'loading' => $data['loading'] ?? false,
            'disabled' => $data['disabled'] ?? false,
            'attributes' => array_merge($this->get_default_attributes(), $attributes),
            'css_classes' => $this->build_css_classes($data),
            'accessibility' => $this->get_accessibility_attributes($data)
        );
    }
    
    /**
     * Generate button HTML
     */
    private function generate_button_html($config) {
        $tag = $this->get_button_tag($config);
        $attributes = $this->build_attributes_string($config);
        $content = $this->build_button_content($config);
        
        return sprintf('<%s%s>%s</%s>', $tag, $attributes, $content, $tag);
    }
    
    /**
     * Get appropriate HTML tag
     */
    private function get_button_tag($config) {
        return (!empty($config['url'])) ? 'a' : 'button';
    }
    
    /**
     * Build attributes string
     */
    private function build_attributes_string($config) {
        $attributes = array();
        
        // Core attributes
        $attributes['class'] = $config['css_classes'];
        
        if ($config['type'] === 'button' && empty($config['url'])) {
            $attributes['type'] = $config['type'];
        }
        
        // URL handling
        if (!empty($config['url'])) {
            $attributes['href'] = esc_url($config['url']);
            if ($config['target'] !== '_self') {
                $attributes['target'] = esc_attr($config['target']);
            }
        }
        
        // Action handling
        if (!empty($config['action'])) {
            $attributes['data-action'] = esc_attr($config['action']);
        }
        
        // State attributes
        if ($config['disabled']) {
            $attributes['disabled'] = 'disabled';
            $attributes['aria-disabled'] = 'true';
        }
        
        if ($config['loading']) {
            $attributes['data-loading'] = 'true';
            $attributes['aria-busy'] = 'true';
        }
        
        // Accessibility attributes
        foreach ($config['accessibility'] as $attr => $value) {
            $attributes[$attr] = esc_attr($value);
        }
        
        // Custom attributes
        foreach ($config['attributes'] as $attr => $value) {
            if ($attr !== 'class') { // Class handled separately
                $attributes[$attr] = esc_attr($value);
            }
        }
        
        // Build attribute string
        $attr_string = '';
        foreach ($attributes as $name => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($name), $value);
        }
        
        return $attr_string;
    }
    
    /**
     * Build button content
     */
    private function build_button_content($config) {
        $content_parts = array();
        
        // Loading indicator
        if ($config['loading']) {
            $content_parts[] = '<span class="qcc-button-spinner" aria-hidden="true"></span>';
        }
        
        // Icon before label
        if ($config['icon'] && $config['icon_position'] === 'left') {
            $content_parts[] = $this->render_icon($config['icon'], 'left');
        }
        
        // Label
        $content_parts[] = sprintf(
            '<span class="qcc-button-label">%s</span>',
            esc_html($config['label'])
        );
        
        // Icon after label
        if ($config['icon'] && $config['icon_position'] === 'right') {
            $content_parts[] = $this->render_icon($config['icon'], 'right');
        }
        
        return implode('', $content_parts);
    }
    
    /**
     * Render icon
     */
    private function render_icon($icon, $position) {
        $icon_svg = $this->get_icon_svg($icon);
        
        if (empty($icon_svg)) {
            return '';
        }
        
        return sprintf(
            '<span class="qcc-button-icon qcc-button-icon-%s" aria-hidden="true">%s</span>',
            esc_attr($position),
            $icon_svg
        );
    }
    
    /**
     * Build CSS classes
     */
    private function build_css_classes($data) {
        $classes = array($this->css_base_class);
        
        // Variant classes
        $variant = $data['variant'] ?? 'default';
        $classes[] = $this->css_base_class . '--' . $variant;
        
        // Size classes
        $size = $data['size'] ?? 'medium';
        $classes[] = $this->css_base_class . '--' . $size;
        
        // State classes
        $state = $data['state'] ?? 'normal';
        if ($state !== 'normal') {
            $classes[] = $this->css_base_class . '--' . $state;
        }
        
        // Feature classes
        if ($data['loading'] ?? false) {
            $classes[] = $this->css_base_class . '--loading';
        }
        
        if ($data['disabled'] ?? false) {
            $classes[] = $this->css_base_class . '--disabled';
        }
        
        if (!empty($data['icon'])) {
            $classes[] = $this->css_base_class . '--with-icon';
            $classes[] = $this->css_base_class . '--icon-' . ($data['icon_position'] ?? 'left');
        }
        
        // Action-specific classes
        if (!empty($data['action'])) {
            $classes[] = $this->css_base_class . '--action-' . sanitize_html_class($data['action']);
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
     * Get accessibility attributes
     */
    private function get_accessibility_attributes($data) {
        $attributes = array();
        
        // ARIA label
        if (!empty($data['aria_label'])) {
            $attributes['aria-label'] = $data['aria_label'];
        }
        
        // ARIA described by
        if (!empty($data['aria_describedby'])) {
            $attributes['aria-describedby'] = $data['aria_describedby'];
        }
        
        // Role
        if (!empty($data['role'])) {
            $attributes['role'] = $data['role'];
        }
        
        // ARIA expanded (for toggles)
        if (isset($data['aria_expanded'])) {
            $attributes['aria-expanded'] = $data['aria_expanded'] ? 'true' : 'false';
        }
        
        // ARIA pressed (for toggle buttons)
        if (isset($data['aria_pressed'])) {
            $attributes['aria-pressed'] = $data['aria_pressed'] ? 'true' : 'false';
        }
        
        return $attributes;
    }
    
    /**
     * Get icon SVG
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            // Common actions
            'save' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17,21 17,13 7,13 7,21"></polyline><polyline points="7,3 7,8 15,8"></polyline></svg>',
            'download' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7,10 12,15 17,10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
            'upload' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17,8 12,3 7,8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
            'export' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16,6 12,2 8,6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>',
            'print' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 6,2 18,2 18,9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
            
            // Navigation
            'chevron-left' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15,18 9,12 15,6"></polyline></svg>',
            'chevron-right' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9,18 15,12 9,6"></polyline></svg>',
            'chevron-up' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18,15 12,9 6,15"></polyline></svg>',
            'chevron-down' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6,9 12,15 18,9"></polyline></svg>',
            
            // Actions
            'edit' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
            'delete' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
            'copy' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>',
            
            // Status
            'check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"></polyline></svg>',
            'x' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
            'plus' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
            'minus' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
            
            // Utility
            'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m11-7h-6m-6 0H1m17-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM7 21a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"></path></svg>',
            'refresh' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,4 23,10 17,10"></polyline><polyline points="1,20 1,14 7,14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>',
            'info' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            'help' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
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
            'css' => array('qcc-button.css'),
            'js' => array('qcc-button.js'),
            'dependencies' => array()
        );
    }
}
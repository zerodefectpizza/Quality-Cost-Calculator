<?php
/**
 * QCC Status Display Atom
 * 
 * Atomic component for displaying validation status, messages,
 * and system feedback with appropriate styling.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Atoms\Displays
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Status_Display extends QCC_Base_Atom {
    
    protected $template_name = 'atoms/status-display.php';
    protected $css_base_class = 'qcc-status-display';
    protected $required_fields = array('status');
    
    protected $default_attributes = array(
        'class' => 'qcc-status-display',
        'role' => 'status'
    );
    
    /**
     * Render status display
     */
    public function render($data = array(), $attributes = array()) {
        $validation = $this->validate_data($data);
        if (is_wp_error($validation)) {
            return $this->render_error($validation);
        }
        
        $status_config = $this->prepare_status_config($data, $attributes);
        return $this->generate_status_html($status_config);
    }
    
    /**
     * Prepare status configuration
     */
    private function prepare_status_config($data, $attributes) {
        return array(
            'status' => $data['status'], // 'success', 'error', 'warning', 'info', 'loading'
            'message' => $data['message'] ?? '',
            'messages' => $data['messages'] ?? array(),
            'title' => $data['title'] ?? '',
            'icon' => $data['icon'] ?? $this->get_status_icon($data['status']),
            'dismissible' => $data['dismissible'] ?? false,
            'progress' => $data['progress'] ?? null,
            'progress_label' => $data['progress_label'] ?? '',
            'actions' => $data['actions'] ?? array(),
            'compact' => $data['compact'] ?? false,
            'animate' => $data['animate'] ?? true,
            'auto_hide' => $data['auto_hide'] ?? null,
            'css_classes' => $this->build_css_classes($data),
            'attributes' => array_merge($this->get_default_attributes(), $attributes)
        );
    }
    
    /**
     * Generate status HTML
     */
    private function generate_status_html($config) {
        $status_parts = array();
        
        // Status header
        $status_parts[] = $this->render_status_header($config);
        
        // Status content
        if (!empty($config['message']) || !empty($config['messages'])) {
            $status_parts[] = $this->render_status_content($config);
        }
        
        // Progress bar
        if ($config['progress'] !== null) {
            $status_parts[] = $this->render_progress_bar($config);
        }
        
        // Actions
        if (!empty($config['actions'])) {
            $status_parts[] = $this->render_status_actions($config);
        }
        
        // Dismiss button
        if ($config['dismissible']) {
            $status_parts[] = $this->render_dismiss_button();
        }
        
        $container_attributes = $this->build_container_attributes($config);
        
        return sprintf(
            '<div %s>%s</div>',
            $container_attributes,
            implode("\n", $status_parts)
        );
    }
    
    /**
     * Render status header
     */
    private function render_status_header($config) {
        $header_parts = array();
        
        // Icon
        if ($config['icon']) {
            $header_parts[] = sprintf(
                '<span class="qcc-status-icon qcc-status-icon--%s">%s</span>',
                esc_attr($config['status']),
                $this->get_icon_svg($config['icon'])
            );
        }
        
        // Title
        if (!empty($config['title'])) {
            $title_tag = $config['compact'] ? 'span' : 'h4';
            $header_parts[] = sprintf(
                '<%s class="qcc-status-title">%s</%s>',
                $title_tag,
                esc_html($config['title']),
                $title_tag
            );
        }
        
        return sprintf(
            '<div class="qcc-status-header">%s</div>',
            implode("\n", $header_parts)
        );
    }
    
    /**
     * Render status content
     */
    private function render_status_content($config) {
        $content_parts = array();
        
        // Single message
        if (!empty($config['message'])) {
            $content_parts[] = sprintf(
                '<p class="qcc-status-message">%s</p>',
                esc_html($config['message'])
            );
        }
        
        // Multiple messages
        if (!empty($config['messages'])) {
            $message_items = array();
            foreach ($config['messages'] as $message) {
                $message_items[] = sprintf('<li>%s</li>', esc_html($message));
            }
            
            $content_parts[] = sprintf(
                '<ul class="qcc-status-messages">%s</ul>',
                implode("\n", $message_items)
            );
        }
        
        return sprintf(
            '<div class="qcc-status-content">%s</div>',
            implode("\n", $content_parts)
        );
    }
    
    /**
     * Render progress bar
     */
    private function render_progress_bar($config) {
        $progress_value = max(0, min(100, intval($config['progress'])));
        
        $progress_html = sprintf(
            '<div class="qcc-progress-bar" role="progressbar" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100">
                <div class="qcc-progress-fill" style="width: %d%%"></div>
            </div>',
            $progress_value,
            $progress_value
        );
        
        $progress_label = '';
        if (!empty($config['progress_label'])) {
            $progress_label = sprintf(
                '<span class="qcc-progress-label">%s</span>',
                esc_html($config['progress_label'])
            );
        }
        
        return sprintf(
            '<div class="qcc-status-progress">
                %s
                %s
            </div>',
            $progress_html,
            $progress_label
        );
    }
    
    /**
     * Render status actions
     */
    private function render_status_actions($config) {
        $action_buttons = array();
        
        foreach ($config['actions'] as $action) {
            $button_type = $action['type'] ?? 'button';
            $button_class = 'qcc-status-action';
            
            if (!empty($action['primary'])) {
                $button_class .= ' qcc-status-action--primary';
            }
            
            $action_buttons[] = sprintf(
                '<button type="%s" class="%s" data-action="%s">%s</button>',
                esc_attr($button_type),
                esc_attr($button_class),
                esc_attr($action['action'] ?? ''),
                esc_html($action['label'] ?? '')
            );
        }
        
        return sprintf(
            '<div class="qcc-status-actions">%s</div>',
            implode("\n", $action_buttons)
        );
    }
    
    /**
     * Render dismiss button
     */
    private function render_dismiss_button() {
        return sprintf(
            '<button type="button" class="qcc-status-dismiss" aria-label="Dismiss">
                %s
            </button>',
            $this->get_icon_svg('x')
        );
    }
    
    /**
     * Build container attributes
     */
    private function build_container_attributes($config) {
        $attributes = array(
            'class' => $config['css_classes']
        );
        
        // ARIA attributes
        $attributes['role'] = 'status';
        
        if ($config['status'] === 'error') {
            $attributes['aria-live'] = 'assertive';
        } else {
            $attributes['aria-live'] = 'polite';
        }
        
        // Auto-hide data attribute
        if ($config['auto_hide']) {
            $attributes['data-auto-hide'] = esc_attr($config['auto_hide']);
        }
        
        // Animation attribute
        if ($config['animate']) {
            $attributes['data-animate'] = 'true';
        }
        
        // Custom attributes
        foreach ($config['attributes'] as $name => $value) {
            if ($name !== 'class' && $name !== 'role') {
                $attributes[$name] = esc_attr($value);
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
     * Build CSS classes
     */
    private function build_css_classes($data) {
        $classes = array($this->css_base_class);
        
        // Status type class
        $classes[] = $this->css_base_class . '--' . sanitize_html_class($data['status']);
        
        // Compact mode
        if ($data['compact'] ?? false) {
            $classes[] = $this->css_base_class . '--compact';
        }
        
        // Dismissible
        if ($data['dismissible'] ?? false) {
            $classes[] = $this->css_base_class . '--dismissible';
        }
        
        // Has progress
        if (isset($data['progress'])) {
            $classes[] = $this->css_base_class . '--with-progress';
        }
        
        // Has actions
        if (!empty($data['actions'])) {
            $classes[] = $this->css_base_class . '--with-actions';
        }
        
        // Animation
        if ($data['animate'] ?? true) {
            $classes[] = $this->css_base_class . '--animated';
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
     * Get default icon for status
     */
    private function get_status_icon($status) {
        $icons = array(
            'success' => 'check-circle',
            'error' => 'x-circle',
            'warning' => 'alert-triangle',
            'info' => 'info',
            'loading' => 'loader'
        );
        
        return $icons[$status] ?? 'info';
    }
    
    /**
     * Get icon SVG
     */
    private function get_icon_svg($icon_name) {
        $icons = array(
            'check-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>',
            'x-circle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            'alert-triangle' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            'info' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            'loader' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>',
            'x' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'
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
            'css' => array('qcc-status-display.css'),
            'js' => array('qcc-status.js'),
            'dependencies' => array()
        );
    }
}
<?php
/**
 * QCC Template Router
 * 
 * Handles template location, loading, caching and override management
 * for all QCC rendering components with fallback mechanisms.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Orchestration
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Template_Router {
    
    private $template_paths = array();
    private $template_cache = array();
    private $fallback_templates = array();
    private $cache_enabled = true;
    private $cache_duration = 3600; // 1 hour
    
    public function __construct() {
        $this->init_template_paths();
        $this->init_fallback_templates();
        $this->setup_hooks();
    }
    
    /**
     * Locate and return template path
     */
    public function locate_template($template_name, $template_type = 'component') {
        // Check cache first
        $cache_key = $this->get_cache_key($template_name, $template_type);
        if ($this->cache_enabled && isset($this->template_cache[$cache_key])) {
            return $this->template_cache[$cache_key];
        }
        
        // Search in priority order
        $template_path = $this->search_template_paths($template_name, $template_type);
        
        // Cache the result
        if ($this->cache_enabled) {
            $this->template_cache[$cache_key] = $template_path;
        }
        
        return $template_path;
    }
    
    /**
     * Render template with data
     */
    public function render_template($template_name, $data = array(), $template_type = 'component') {
        $template_path = $this->locate_template($template_name, $template_type);
        
        if (!$template_path) {
            return $this->render_fallback_template($template_name, $data, $template_type);
        }
        
        return $this->load_and_render_template($template_path, $data);
    }
    
    /**
     * Check if template exists
     */
    public function template_exists($template_name, $template_type = 'component') {
        return $this->locate_template($template_name, $template_type) !== false;
    }
    
    /**
     * Get available templates for a specific type
     */
    public function get_available_templates($template_type = 'component') {
        $templates = array();
        
        foreach ($this->template_paths as $priority => $path_config) {
            $full_path = $this->get_typed_template_path($path_config['path'], $template_type);
            
            if (is_dir($full_path)) {
                $found_templates = $this->scan_template_directory($full_path);
                $templates = array_merge($templates, $found_templates);
            }
        }
        
        return array_unique($templates);
    }
    
    /**
     * Register custom template path
     */
    public function register_template_path($path, $priority = 50, $name = '') {
        $this->template_paths[$priority] = array(
            'path' => trailingslashit($path),
            'name' => $name ?: basename($path),
            'registered_at' => time()
        );
        
        // Re-sort by priority
        ksort($this->template_paths);
        
        // Clear cache since paths changed
        $this->clear_template_cache();
    }
    
    /**
     * Unregister template path
     */
    public function unregister_template_path($priority) {
        unset($this->template_paths[$priority]);
        $this->clear_template_cache();
    }
    
    /**
     * Clear template cache
     */
    public function clear_template_cache() {
        $this->template_cache = array();
    }
    
    /**
     * Get template cache statistics
     */
    public function get_cache_stats() {
        return array(
            'cached_templates' => count($this->template_cache),
            'cache_enabled' => $this->cache_enabled,
            'cache_duration' => $this->cache_duration,
            'memory_usage' => strlen(serialize($this->template_cache))
        );
    }
    
    /**
     * Search template across all registered paths
     */
    private function search_template_paths($template_name, $template_type) {
        foreach ($this->template_paths as $priority => $path_config) {
            $template_path = $this->find_template_in_path($template_name, $template_type, $path_config['path']);
            
            if ($template_path) {
                return $template_path;
            }
        }
        
        return false;
    }
    
    /**
     * Find template in specific path
     */
    private function find_template_in_path($template_name, $template_type, $base_path) {
        $possible_paths = $this->get_possible_template_paths($template_name, $template_type, $base_path);
        
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_readable($path)) {
                return $path;
            }
        }
        
        return false;
    }
    
    /**
     * Get all possible template paths for a template
     */
    private function get_possible_template_paths($template_name, $template_type, $base_path) {
        $template_name = $this->normalize_template_name($template_name);
        $typed_path = $this->get_typed_template_path($base_path, $template_type);
        
        return array(
            // Type-specific directory
            $typed_path . $template_name . '.php',
            $typed_path . $template_name . '.html',
            
            // Root template directory
            $base_path . $template_name . '.php',
            $base_path . $template_name . '.html',
            
            // With template type prefix
            $base_path . $template_type . '-' . $template_name . '.php',
            $base_path . $template_type . '-' . $template_name . '.html',
            
            // Subdirectory with same name as template type
            $base_path . $template_type . '/' . $template_name . '.php',
            $base_path . $template_type . '/' . $template_name . '.html'
        );
    }
    
    /**
     * Get typed template path (atoms/, molecules/, etc.)
     */
    private function get_typed_template_path($base_path, $template_type) {
        $type_map = array(
            'atom' => 'atoms/',
            'molecule' => 'molecules/',
            'component' => 'components/',
            'section' => 'sections/',
            'layout' => 'layouts/',
            'page' => 'pages/'
        );
        
        $type_subdir = $type_map[$template_type] ?? $template_type . '/';
        
        return trailingslashit($base_path) . $type_subdir;
    }
    
    /**
     * Normalize template name
     */
    private function normalize_template_name($template_name) {
        // Remove file extension if provided
        $template_name = preg_replace('/\.(php|html)$/', '', $template_name);
        
        // Convert class names to template names
        $template_name = str_replace(array('QCC_', '_'), array('', '-'), $template_name);
        $template_name = strtolower($template_name);
        
        return $template_name;
    }
    
    /**
     * Load and render template file
     */
    private function load_and_render_template($template_path, $data) {
        // Extract data variables
        if (is_array($data) && !empty($data)) {
            extract($data, EXTR_SKIP);
        }
        
        // Start output buffering
        ob_start();
        
        try {
            // Include template file
            include $template_path;
            
            // Get the output
            $output = ob_get_clean();
            
            // Apply filters
            $output = apply_filters('qcc_template_output', $output, $template_path, $data);
            
            return $output;
            
        } catch (Exception $e) {
            // Clean output buffer on error
            ob_end_clean();
            
            // Log error
            error_log('QCC Template Error: ' . $e->getMessage() . ' in ' . $template_path);
            
            // Return fallback
            return $this->render_error_template($e, $template_path, $data);
        }
    }
    
    /**
     * Render fallback template
     */
    private function render_fallback_template($template_name, $data, $template_type) {
        // Check if we have a fallback template
        $fallback_key = $template_type . '/' . $template_name;
        
        if (isset($this->fallback_templates[$fallback_key])) {
            return $this->generate_html_from_fallback($this->fallback_templates[$fallback_key], $data);
        }
        
        // Generic fallback based on template type
        if (isset($this->fallback_templates[$template_type])) {
            return $this->generate_html_from_fallback($this->fallback_templates[$template_type], $data);
        }
        
        // Last resort fallback
        return $this->render_emergency_fallback($template_name, $data, $template_type);
    }
    
    /**
     * Generate HTML from fallback configuration
     */
    private function generate_html_from_fallback($fallback_config, $data) {
        if (is_callable($fallback_config)) {
            return call_user_func($fallback_config, $data);
        }
        
        if (is_string($fallback_config)) {
            return $fallback_config;
        }
        
        if (is_array($fallback_config) && isset($fallback_config['template'])) {
            return $this->process_fallback_template($fallback_config['template'], $data);
        }
        
        return '';
    }
    
    /**
     * Process fallback template with placeholders
     */
    private function process_fallback_template($template, $data) {
        // Simple placeholder replacement
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', esc_html($value), $template);
            }
        }
        
        // Remove any remaining placeholders
        $template = preg_replace('/\{\{[^}]+\}\}/', '', $template);
        
        return $template;
    }
    
    /**
     * Render emergency fallback
     */
    private function render_emergency_fallback($template_name, $data, $template_type) {
        $error_message = sprintf(
            'Template not found: %s (type: %s)',
            esc_html($template_name),
            esc_html($template_type)
        );
        
        if (WP_DEBUG) {
            return sprintf(
                '<div class="qcc-template-error" style="border: 2px solid red; padding: 10px; margin: 10px; background: #ffe6e6;">
                    <strong>QCC Template Error:</strong> %s
                    <br><small>Available paths: %s</small>
                </div>',
                $error_message,
                esc_html(implode(', ', array_column($this->template_paths, 'path')))
            );
        }
        
        return sprintf('<div class="qcc-component-placeholder" data-template="%s"></div>', esc_attr($template_name));
    }
    
    /**
     * Render error template
     */
    private function render_error_template($exception, $template_path, $data) {
        if (WP_DEBUG) {
            return sprintf(
                '<div class="qcc-template-exception" style="border: 2px solid orange; padding: 10px; margin: 10px; background: #fff3cd;">
                    <strong>QCC Template Exception:</strong> %s
                    <br><small>File: %s</small>
                </div>',
                esc_html($exception->getMessage()),
                esc_html($template_path)
            );
        }
        
        return '<div class="qcc-component-error"></div>';
    }
    
    /**
     * Scan template directory for available templates
     */
    private function scan_template_directory($directory) {
        $templates = array();
        
        if (!is_dir($directory)) {
            return $templates;
        }
        
        $files = glob($directory . '*.{php,html}', GLOB_BRACE);
        
        foreach ($files as $file) {
            $template_name = basename($file, '.php');
            $template_name = basename($template_name, '.html');
            $templates[] = $template_name;
        }
        
        return $templates;
    }
    
    /**
     * Get cache key for template
     */
    private function get_cache_key($template_name, $template_type) {
        return md5($template_name . '_' . $template_type . '_' . serialize(array_keys($this->template_paths)));
    }
    
    /**
     * Initialize template paths in priority order
     */
    private function init_template_paths() {
        // Priority 10: Child theme override
        if (is_child_theme()) {
            $this->template_paths[10] = array(
                'path' => get_stylesheet_directory() . '/qcc-templates/',
                'name' => 'Child Theme Override'
            );
        }
        
        // Priority 20: Parent theme override
        $this->template_paths[20] = array(
            'path' => get_template_directory() . '/qcc-templates/',
            'name' => 'Theme Override'
        );
        
        // Priority 30: Custom uploads directory
        $upload_dir = wp_upload_dir();
        $this->template_paths[30] = array(
            'path' => $upload_dir['basedir'] . '/qcc-templates/',
            'name' => 'Custom Templates'
        );
        
        // Priority 90: Plugin templates directory
        $this->template_paths[90] = array(
            'path' => QCC_PLUGIN_PATH . 'templates/',
            'name' => 'Plugin Default'
        );
        
        // Allow modification via filter
        $this->template_paths = apply_filters('qcc_template_paths', $this->template_paths);
        
        // Sort by priority
        ksort($this->template_paths);
    }
    
    /**
     * Initialize fallback templates
     */
    private function init_fallback_templates() {
        $this->fallback_templates = array(
            // Atom fallbacks
            'atom/percentage-input' => '<input type="number" name="{{name}}" value="{{value}}" min="0" max="100" step="0.01" class="qcc-percentage-input">',
            'atom/currency-input' => '<input type="number" name="{{name}}" value="{{value}}" step="0.01" class="qcc-currency-input">',
            'atom/select-input' => '<select name="{{name}}" class="qcc-select-input"><option value="{{value}}">{{label}}</option></select>',
            'atom/result-card' => '<div class="qcc-result-card"><h3>{{title}}</h3><div class="qcc-value">{{value}}</div></div>',
            'atom/button' => '<button type="{{type}}" class="qcc-button">{{label}}</button>',
            
            // Component fallbacks
            'component' => '<div class="qcc-component-fallback">{{content}}</div>',
            
            // Section fallbacks
            'section' => '<section class="qcc-section"><h2>{{title}}</h2>{{content}}</section>',
            
            // Layout fallbacks
            'layout' => '<div class="qcc-layout">{{content}}</div>'
        );
        
        // Allow modification via filter
        $this->fallback_templates = apply_filters('qcc_fallback_templates', $this->fallback_templates);
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Clear cache when theme changes
        add_action('switch_theme', array($this, 'clear_template_cache'));
        
        // Clear cache when plugins are activated/deactivated
        add_action('activated_plugin', array($this, 'clear_template_cache'));
        add_action('deactivated_plugin', array($this, 'clear_template_cache'));
        
        // Add template hierarchy filter
        add_filter('qcc_template_hierarchy', array($this, 'filter_template_hierarchy'), 10, 3);
    }
    
    /**
     * Filter template hierarchy
     */
    public function filter_template_hierarchy($hierarchy, $template_name, $template_type) {
        // Allow themes and plugins to modify template search hierarchy
        return $hierarchy;
    }
    
    /**
     * Enable/disable template caching
     */
    public function set_cache_enabled($enabled) {
        $this->cache_enabled = (bool) $enabled;
        
        if (!$enabled) {
            $this->clear_template_cache();
        }
    }
    
    /**
     * Set cache duration
     */
    public function set_cache_duration($duration) {
        $this->cache_duration = (int) $duration;
    }
    
    /**
     * Get template paths
     */
    public function get_template_paths() {
        return $this->template_paths;
    }
    
    /**
     * Get debug information
     */
    public function get_debug_info() {
        return array(
            'template_paths' => $this->template_paths,
            'cached_templates' => array_keys($this->template_cache),
            'fallback_templates' => array_keys($this->fallback_templates),
            'cache_stats' => $this->get_cache_stats()
        );
    }
}
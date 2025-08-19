<?php
/**
 * QCC Template Service
 * 
 * Manages template operations for the Quality Cost Calculator plugin.
 * Part of the modular service layer architecture.
 * 
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Template Service Class
 * 
 * Handles template loading, caching, compilation, and theme integration
 * with support for multiple template engines and override systems.
 */
class QCC_Template_Service {
    
    /**
     * Template cache
     * 
     * @var array
     */
    private $template_cache = array();
    
    /**
     * Template paths in priority order
     * 
     * @var array
     */
    private $template_paths = array();
    
    /**
     * Compiled templates cache
     * 
     * @var array
     */
    private $compiled_cache = array();
    
    /**
     * Template variables
     * 
     * @var array
     */
    private $template_vars = array();
    
    /**
     * Template hooks
     * 
     * @var array
     */
    private $template_hooks = array();
    
    /**
     * Template filters
     * 
     * @var array
     */
    private $template_filters = array();
    
    /**
     * Debug mode
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Template statistics
     * 
     * @var array
     */
    private $stats = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_template_paths();
        $this->init_template_hooks();
        $this->init_template_filters();
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->init_stats();
    }
    
    /**
     * Initialize template search paths
     */
    private function init_template_paths() {
        $plugin_path = plugin_dir_path(__FILE__);
        
        $this->template_paths = array(
            // Child theme override
            get_stylesheet_directory() . '/qcc-templates/',
            
            // Parent theme override
            get_template_directory() . '/qcc-templates/',
            
            // Plugin custom templates
            $plugin_path . '../templates/custom/',
            
            // Plugin default templates
            $plugin_path . '../templates/',
            
            // Plugin fallback templates
            $plugin_path . '../templates/fallback/'
        );
        
        // Allow filtering of template paths
        $this->template_paths = apply_filters('qcc_template_paths', $this->template_paths);
    }
    
    /**
     * Initialize template hooks
     */
    private function init_template_hooks() {
        $this->template_hooks = array(
            'before_template' => array(),
            'after_template' => array(),
            'template_not_found' => array(),
            'before_compile' => array(),
            'after_compile' => array()
        );
    }
    
    /**
     * Initialize template filters
     */
    private function init_template_filters() {
        $this->template_filters = array(
            'escape' => 'esc_html',
            'url' => 'esc_url',
            'attr' => 'esc_attr',
            'stripslashes' => 'stripslashes',
            'wpautop' => 'wpautop',
            'date' => array($this, 'format_date'),
            'currency' => array($this, 'format_currency'),
            'number' => array($this, 'format_number')
        );
    }
    
    /**
     * Initialize statistics
     */
    private function init_stats() {
        $this->stats = array(
            'templates_loaded' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'compilation_time' => 0,
            'render_time' => 0
        );
    }
    
    /**
     * Render template
     * 
     * @param string $template Template name
     * @param array $data Template data
     * @param bool $return Whether to return or echo
     * @return string|void Rendered template
     */
    public function render($template, $data = array(), $return = false) {
        $start_time = microtime(true);
        
        try {
            // Execute before_template hooks
            $this->execute_hooks('before_template', $template, $data);
            
            // Merge with global template variables
            $data = array_merge($this->template_vars, $data);
            
            // Get template content
            $content = $this->get_template_content($template, $data);
            
            // Process template content
            $rendered = $this->process_template_content($content, $data);
            
            // Execute after_template hooks
            $this->execute_hooks('after_template', $template, $rendered);
            
            // Update statistics
            $this->stats['render_time'] += microtime(true) - $start_time;
            $this->stats['templates_loaded']++;
            
            if ($return) {
                return $rendered;
            } else {
                echo $rendered;
            }
            
        } catch (Exception $e) {
            return $this->handle_template_error($e, $template, $data, $return);
        }
    }
    
    /**
     * Get template content
     * 
     * @param string $template Template name
     * @param array $data Template data
     * @return string Template content
     */
    private function get_template_content($template, $data = array()) {
        // Check cache first
        $cache_key = $this->get_cache_key($template, $data);
        
        if (isset($this->template_cache[$cache_key])) {
            $this->stats['cache_hits']++;
            return $this->template_cache[$cache_key];
        }
        
        $this->stats['cache_misses']++;
        
        // Find template file
        $template_file = $this->locate_template($template);
        
        if (!$template_file) {
            throw new Exception("Template not found: {$template}");
        }
        
        // Load template content
        $content = $this->load_template_file($template_file, $data);
        
        // Cache the content
        $this->template_cache[$cache_key] = $content;
        
        return $content;
    }
    
    /**
     * Locate template file
     * 
     * @param string $template Template name
     * @return string|false Template file path or false if not found
     */
    private function locate_template($template) {
        // Normalize template name
        $template = $this->normalize_template_name($template);
        
        // Search in all template paths
        foreach ($this->template_paths as $path) {
            $file_path = $path . $template;
            
            if (file_exists($file_path) && is_readable($file_path)) {
                return $file_path;
            }
            
            // Try with .php extension if not present
            if (!str_ends_with($template, '.php')) {
                $php_file = $file_path . '.php';
                if (file_exists($php_file) && is_readable($php_file)) {
                    return $php_file;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Load template file
     * 
     * @param string $file Template file path
     * @param array $data Template data
     * @return string Template content
     */
    private function load_template_file($file, $data = array()) {
        // Extract variables to template scope
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        // Include template file
        include $file;
        
        // Get content and clean buffer
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Process template content
     * 
     * @param string $content Template content
     * @param array $data Template data
     * @return string Processed content
     */
    private function process_template_content($content, $data) {
        // Process template variables
        $content = $this->process_template_variables($content, $data);
        
        // Process template filters
        $content = $this->process_template_filters($content);
        
        // Process conditional blocks
        $content = $this->process_conditional_blocks($content, $data);
        
        // Process loops
        $content = $this->process_loops($content, $data);
        
        return $content;
    }
    
    /**
     * Process template variables
     * 
     * @param string $content Template content
     * @param array $data Template data
     * @return string Processed content
     */
    private function process_template_variables($content, $data) {
        // Simple variable replacement {{variable}}
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($data) {
            $var_name = trim($matches[1]);
            
            // Support dot notation
            $value = $this->get_nested_value($data, $var_name);
            
            return $value !== null ? $value : '';
        }, $content);
    }
    
    /**
     * Process template filters
     * 
     * @param string $content Template content
     * @return string Processed content
     */
    private function process_template_filters($content) {
        // Process filter syntax {{variable|filter}}
        return preg_replace_callback('/\{\{([^|]+)\|([^}]+)\}\}/', function($matches) {
            $variable = trim($matches[1]);
            $filter = trim($matches[2]);
            
            if (isset($this->template_filters[$filter])) {
                $filter_func = $this->template_filters[$filter];
                if (is_callable($filter_func)) {
                    return call_user_func($filter_func, $variable);
                }
            }
            
            return $variable;
        }, $content);
    }
    
    /**
     * Process conditional blocks
     * 
     * @param string $content Template content
     * @param array $data Template data
     * @return string Processed content
     */
    private function process_conditional_blocks($content, $data) {
        // Process {% if condition %} blocks
        return preg_replace_callback('/\{%\s*if\s+([^%]+)\s*%\}(.*?)\{%\s*endif\s*%\}/s', 
            function($matches) use ($data) {
                $condition = trim($matches[1]);
                $block_content = $matches[2];
                
                if ($this->evaluate_condition($condition, $data)) {
                    return $block_content;
                }
                
                return '';
            }, $content);
    }
    
    /**
     * Process loops
     * 
     * @param string $content Template content
     * @param array $data Template data
     * @return string Processed content
     */
    private function process_loops($content, $data) {
        // Process {% for item in items %} blocks
        return preg_replace_callback('/\{%\s*for\s+(\w+)\s+in\s+(\w+)\s*%\}(.*?)\{%\s*endfor\s*%\}/s',
            function($matches) use ($data) {
                $item_var = $matches[1];
                $array_var = $matches[2];
                $loop_content = $matches[3];
                
                if (!isset($data[$array_var]) || !is_array($data[$array_var])) {
                    return '';
                }
                
                $output = '';
                foreach ($data[$array_var] as $item) {
                    $loop_data = array_merge($data, array($item_var => $item));
                    $output .= $this->process_template_content($loop_content, $loop_data);
                }
                
                return $output;
            }, $content);
    }
    
    /**
     * Set global template variable
     * 
     * @param string $key Variable key
     * @param mixed $value Variable value
     */
    public function set_global($key, $value) {
        $this->template_vars[$key] = $value;
    }
    
    /**
     * Get global template variable
     * 
     * @param string $key Variable key
     * @param mixed $default Default value
     * @return mixed Variable value
     */
    public function get_global($key, $default = null) {
        return isset($this->template_vars[$key]) ? $this->template_vars[$key] : $default;
    }
    
    /**
     * Register template hook
     * 
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority
     */
    public function add_hook($hook, $callback, $priority = 10) {
        if (!isset($this->template_hooks[$hook])) {
            $this->template_hooks[$hook] = array();
        }
        
        $this->template_hooks[$hook][] = array(
            'callback' => $callback,
            'priority' => $priority
        );
        
        // Sort by priority
        usort($this->template_hooks[$hook], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Register template filter
     * 
     * @param string $name Filter name
     * @param callable $callback Filter callback
     */
    public function add_filter($name, $callback) {
        $this->template_filters[$name] = $callback;
    }
    
    /**
     * Check if template exists
     * 
     * @param string $template Template name
     * @return bool Template exists
     */
    public function template_exists($template) {
        return $this->locate_template($template) !== false;
    }
    
    /**
     * Get template list
     * 
     * @param string $directory Directory to scan
     * @return array Template list
     */
    public function get_template_list($directory = '') {
        $templates = array();
        
        foreach ($this->template_paths as $path) {
            $scan_path = $path . $directory;
            
            if (is_dir($scan_path)) {
                $files = glob($scan_path . '*.php');
                foreach ($files as $file) {
                    $template_name = basename($file, '.php');
                    if (!in_array($template_name, $templates)) {
                        $templates[] = $template_name;
                    }
                }
            }
        }
        
        return $templates;
    }
    
    /**
     * Clear template cache
     * 
     * @param string|null $template Specific template or all
     */
    public function clear_cache($template = null) {
        if ($template) {
            $cache_keys = array_keys($this->template_cache);
            foreach ($cache_keys as $key) {
                if (strpos($key, $template) === 0) {
                    unset($this->template_cache[$key]);
                }
            }
        } else {
            $this->template_cache = array();
        }
    }
    
    /**
     * Get template statistics
     * 
     * @return array Statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Helper methods
     */
    private function normalize_template_name($template) {
        // Remove leading slashes
        $template = ltrim($template, '/');
        
        // Convert backslashes to forward slashes
        $template = str_replace('\\', '/', $template);
        
        return $template;
    }
    
    private function get_cache_key($template, $data) {
        return md5($template . serialize($data));
    }
    
    private function get_nested_value($data, $key) {
        $keys = explode('.', $key);
        $value = $data;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    private function evaluate_condition($condition, $data) {
        // Simple condition evaluation (extend as needed)
        if (strpos($condition, '==') !== false) {
            list($left, $right) = explode('==', $condition, 2);
            $left = trim($left);
            $right = trim($right, " '\"");
            
            $left_value = $this->get_nested_value($data, $left);
            return $left_value == $right;
        }
        
        if (strpos($condition, '!=') !== false) {
            list($left, $right) = explode('!=', $condition, 2);
            $left = trim($left);
            $right = trim($right, " '\"");
            
            $left_value = $this->get_nested_value($data, $left);
            return $left_value != $right;
        }
        
        // Check if variable exists and is truthy
        return !empty($this->get_nested_value($data, $condition));
    }
    
    private function execute_hooks($hook, ...$args) {
        if (isset($this->template_hooks[$hook])) {
            foreach ($this->template_hooks[$hook] as $hook_data) {
                call_user_func_array($hook_data['callback'], $args);
            }
        }
    }
    
    private function handle_template_error($error, $template, $data, $return) {
        $error_message = "Template Error: {$error->getMessage()}";
        
        if ($this->debug_mode) {
            $error_message .= "\nTemplate: {$template}";
            $error_message .= "\nData: " . print_r($data, true);
        }
        
        // Execute error hooks
        $this->execute_hooks('template_not_found', $template, $error);
        
        // Log error
        error_log($error_message);
        
        // Return fallback content
        $fallback = $this->get_error_fallback($template);
        
        if ($return) {
            return $fallback;
        } else {
            echo $fallback;
        }
    }
    
    private function get_error_fallback($template) {
        return sprintf(
            '<div class="qcc-template-error">%s</div>',
            esc_html__('Template not found: ' . $template, 'quality-cost-calculator')
        );
    }
    
    /**
     * Template filter callbacks
     */
    public function format_date($value, $format = 'Y-m-d') {
        return date($format, strtotime($value));
    }
    
    public function format_currency($value, $currency = 'USD') {
        return number_format($value, 2) . ' ' . $currency;
    }
    
    public function format_number($value, $decimals = 2) {
        return number_format($value, $decimals);
    }
}
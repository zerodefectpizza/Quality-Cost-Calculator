<?php
/**
 * Template Router for Quality Cost Calculator
 * 
 * Handles template routing, loading, and fallback mechanisms for the QCC plugin.
 * Part of the modular architecture refactoring - Phase 3: Rendering Layer.
 * 
 * @package QualityCostCalculator
 * @subpackage Presentation
 * @since 1.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Template Router Class
 * 
 * Manages template loading with fallback mechanisms, caching, and theme override support.
 * Implements intelligent template resolution for the atomic design architecture.
 * 
 * Features:
 * - Multi-level template fallback system
 * - Theme override support
 * - Template caching for performance
 * - Data injection and sanitization
 * - Emergency fallback generation
 * - Template validation and debugging
 */
class QCC_Template_Router {
    
    /**
     * Template search paths in priority order
     * 
     * @var array
     */
    private $template_paths = array();
    
    /**
     * Template cache for performance optimization
     * 
     * @var array
     */
    private $template_cache = array();
    
    /**
     * Default template extensions to search for
     * 
     * @var array
     */
    private $template_extensions = array('.php', '.html');
    
    /**
     * Translation service instance
     * 
     * @var QCC_Translation_Service
     */
    private $translator;
    
    /**
     * Configuration service instance
     * 
     * @var QCC_Configuration
     */
    private $config;
    
    /**
     * Debug mode flag
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Template loading statistics for debugging
     * 
     * @var array
     */
    private $load_stats = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->setup_template_paths();
        $this->load_dependencies();
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Initialize load statistics
        $this->load_stats = array(
            'cache_hits' => 0,
            'cache_misses' => 0,
            'fallbacks_used' => 0,
            'emergency_fallbacks' => 0
        );
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        try {
            $this->translator = QCC_Service_Container::get('translator');
            $this->config = QCC_Service_Container::get('configuration');
        } catch (Exception $e) {
            // Graceful degradation if services not available
            $this->translator = null;
            $this->config = null;
            
            if ($this->debug_mode) {
                error_log('QCC Template Router: Failed to load dependencies - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Setup template search paths in priority order
     * 
     * Priority:
     * 1. Child theme override (/themes/child/qcc-templates/)
     * 2. Parent theme override (/themes/parent/qcc-templates/)
     * 3. Custom upload directory (/uploads/qcc-templates/)
     * 4. Plugin templates directory (/plugins/qcc/templates/)
     * 5. Plugin fallback templates (/plugins/qcc/templates/fallback/)
     */
    private function setup_template_paths() {
        $upload_dir = wp_upload_dir();
        
        $this->template_paths = array(
            'child_theme' => array(
                'path' => get_stylesheet_directory() . '/qcc-templates/',
                'url' => get_stylesheet_directory_uri() . '/qcc-templates/',
                'priority' => 1,
                'description' => 'Child Theme Override'
            ),
            'parent_theme' => array(
                'path' => get_template_directory() . '/qcc-templates/',
                'url' => get_template_directory_uri() . '/qcc-templates/',
                'priority' => 2,
                'description' => 'Parent Theme Override'
            ),
            'custom_upload' => array(
                'path' => $upload_dir['basedir'] . '/qcc-templates/',
                'url' => $upload_dir['baseurl'] . '/qcc-templates/',
                'priority' => 3,
                'description' => 'Custom Upload Directory'
            ),
            'plugin_templates' => array(
                'path' => QCC_PLUGIN_PATH . 'templates/',
                'url' => QCC_PLUGIN_URL . 'templates/',
                'priority' => 4,
                'description' => 'Plugin Templates'
            ),
            'plugin_fallback' => array(
                'path' => QCC_PLUGIN_PATH . 'templates/fallback/',
                'url' => QCC_PLUGIN_URL . 'templates/fallback/',
                'priority' => 5,
                'description' => 'Plugin Fallback Templates'
            )
        );
        
        // Allow filtering of template paths
        $this->template_paths = apply_filters('qcc_template_paths', $this->template_paths);
        
        // Sort by priority
        uasort($this->template_paths, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }
    
    /**
     * Locate template file with fallback mechanism
     * 
     * @param string $template_name Template name (e.g., 'calculator/main-layout')
     * @param array $context Additional context for template selection
     * @return string|false Template file path or false if not found
     */
    public function locate_template($template_name, $context = array()) {
        // Generate cache key
        $cache_key = $this->generate_cache_key($template_name, $context);
        
        // Check cache first
        if (isset($this->template_cache[$cache_key])) {
            $this->load_stats['cache_hits']++;
            return $this->template_cache[$cache_key];
        }
        
        $this->load_stats['cache_misses']++;
        
        // Clean template name
        $template_name = $this->sanitize_template_name($template_name);
        
        // Search through template paths
        foreach ($this->template_paths as $path_key => $path_info) {
            $template_file = $this->search_template_in_path($template_name, $path_info['path'], $context);
            
            if ($template_file) {
                // Cache the result
                $this->template_cache[$cache_key] = $template_file;
                
                if ($this->debug_mode) {
                    error_log("QCC Template Router: Found template '{$template_name}' in {$path_info['description']}");
                }
                
                return $template_file;
            }
        }
        
        // Template not found, try fallback
        $fallback_template = $this->get_fallback_template($template_name, $context);
        
        if ($fallback_template) {
            $this->load_stats['fallbacks_used']++;
            $this->template_cache[$cache_key] = $fallback_template;
            return $fallback_template;
        }
        
        // Ultimate fallback - generate emergency template
        $emergency_template = $this->generate_emergency_template($template_name, $context);
        $this->load_stats['emergency_fallbacks']++;
        
        if ($this->debug_mode) {
            error_log("QCC Template Router: Using emergency fallback for template '{$template_name}'");
        }
        
        return $emergency_template;
    }
    
    /**
     * Search for template in specific path
     * 
     * @param string $template_name Template name
     * @param string $base_path Base path to search in
     * @param array $context Template context
     * @return string|false Template file path or false
     */
    private function search_template_in_path($template_name, $base_path, $context = array()) {
        // Try different variations of the template name
        $template_variations = $this->get_template_variations($template_name, $context);
        
        foreach ($template_variations as $variation) {
            foreach ($this->template_extensions as $extension) {
                $full_path = $base_path . $variation . $extension;
                
                if (file_exists($full_path) && is_readable($full_path)) {
                    return $full_path;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get template name variations based on context
     * 
     * @param string $template_name Base template name
     * @param array $context Template context
     * @return array Array of template variations to try
     */
    private function get_template_variations($template_name, $context) {
        $variations = array($template_name);
        
        // Add language-specific variations
        if ($this->translator) {
            $current_language = $this->translator->get_current_language();
            if ($current_language !== 'en') {
                $variations[] = $template_name . '-' . $current_language;
            }
        }
        
        // Add theme-specific variations
        $theme_slug = get_template();
        $variations[] = $template_name . '-' . $theme_slug;
        
        // Add device-specific variations
        if (wp_is_mobile()) {
            $variations[] = $template_name . '-mobile';
        }
        
        // Add context-specific variations
        if (isset($context['variant'])) {
            $variations[] = $template_name . '-' . $context['variant'];
        }
        
        // Sort by specificity (most specific first)
        return array_reverse($variations);
    }
    
    /**
     * Get fallback template for a given template name
     * 
     * @param string $template_name Template name
     * @param array $context Template context
     * @return string|false Fallback template path or false
     */
    private function get_fallback_template($template_name, $context) {
        // Define fallback mappings
        $fallback_mappings = array(
            'calculator/main-layout' => 'calculator/basic-layout',
            'calculator/advanced-layout' => 'calculator/main-layout',
            'forms/cogq-form' => 'forms/basic-form',
            'forms/copq-form' => 'forms/basic-form',
            'forms/opportunity-form' => 'forms/basic-form',
            'results/advanced-results' => 'results/basic-results',
            'charts/advanced-chart' => 'charts/basic-chart',
        );
        
        // Check if we have a specific fallback mapping
        if (isset($fallback_mappings[$template_name])) {
            $fallback_name = $fallback_mappings[$template_name];
            return $this->locate_template($fallback_name, $context);
        }
        
        // Try generic fallbacks based on template structure
        $template_parts = explode('/', $template_name);
        
        if (count($template_parts) > 1) {
            // Try generic template in the same category
            $category = $template_parts[0];
            $generic_template = $category . '/generic';
            
            foreach ($this->template_paths as $path_info) {
                $generic_path = $this->search_template_in_path($generic_template, $path_info['path'], $context);
                if ($generic_path) {
                    return $generic_path;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Generate emergency fallback template
     * 
     * @param string $template_name Template name
     * @param array $context Template context
     * @return string Emergency template content
     */
    private function generate_emergency_template($template_name, $context) {
        // Create a temporary file for the emergency template
        $temp_file = wp_tempnam('qcc-emergency-template-');
        
        $emergency_content = $this->get_emergency_template_content($template_name, $context);
        
        file_put_contents($temp_file, $emergency_content);
        
        return $temp_file;
    }
    
    /**
     * Get emergency template content
     * 
     * @param string $template_name Template name
     * @param array $context Template context
     * @return string Emergency template content
     */
    private function get_emergency_template_content($template_name, $context) {
        $error_message = $this->translator ? 
            $this->translator->get('template_not_found', 'Template not found') : 
            'Template not found';
            
        return sprintf(
            '<?php
/**
 * Emergency fallback template for: %s
 * Generated automatically by QCC Template Router
 */
?>
<div class="qcc-emergency-template" data-template="%s">
    <div class="qcc-error-notice">
        <p><strong>%s:</strong> %s</p>
        <p><small>%s</small></p>
    </div>
    <?php
    // Basic data output for debugging
    if (isset($data) && is_array($data)) {
        echo "<pre class=\"qcc-debug-data\">";
        echo esc_html(print_r($data, true));
        echo "</pre>";
    }
    ?>
</div>',
            esc_html($template_name),
            esc_attr($template_name),
            esc_html($error_message),
            esc_html($template_name),
            esc_html(sprintf(
                'Please create the template file or contact the site administrator. Debug mode: %s',
                $this->debug_mode ? 'enabled' : 'disabled'
            ))
        );
    }
    
    /**
     * Render template with data injection
     * 
     * @param string $template_name Template name
     * @param array $data Data to inject into template
     * @param array $context Template context
     * @return string Rendered template content
     */
    public function render_template($template_name, $data = array(), $context = array()) {
        $template_file = $this->locate_template($template_name, $context);
        
        if (!$template_file) {
            return $this->render_error_template($template_name, 'Template not found');
        }
        
        // Sanitize data before injection
        $data = $this->sanitize_template_data($data);
        
        // Add global template variables
        $data = array_merge($data, $this->get_global_template_vars($context));
        
        // Start output buffering
        ob_start();
        
        try {
            // Extract data variables
            extract($data, EXTR_SKIP);
            
            // Include the template file
            include $template_file;
            
            $output = ob_get_contents();
            
        } catch (Exception $e) {
            $output = $this->render_error_template($template_name, $e->getMessage());
            
            if ($this->debug_mode) {
                error_log('QCC Template Router: Template rendering error - ' . $e->getMessage());
            }
        } finally {
            ob_end_clean();
        }
        
        // Clean up temporary emergency templates
        if (strpos($template_file, wp_tempnam('', '')) !== false) {
            unlink($template_file);
        }
        
        return $output;
    }
    
    /**
     * Get global template variables
     * 
     * @param array $context Template context
     * @return array Global variables for all templates
     */
    private function get_global_template_vars($context) {
        return array(
            'translator' => $this->translator,
            'config' => $this->config,
            'context' => $context,
            'debug_mode' => $this->debug_mode,
            'plugin_url' => QCC_PLUGIN_URL,
            'plugin_path' => QCC_PLUGIN_PATH,
            'template_router' => $this
        );
    }
    
    /**
     * Sanitize template data
     * 
     * @param array $data Raw template data
     * @return array Sanitized template data
     */
    private function sanitize_template_data($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            // Sanitize key
            $clean_key = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            
            if (empty($clean_key)) {
                continue;
            }
            
            // Sanitize value based on type
            if (is_string($value)) {
                $sanitized[$clean_key] = sanitize_text_field($value);
            } elseif (is_array($value)) {
                $sanitized[$clean_key] = $this->sanitize_template_data($value);
            } elseif (is_numeric($value)) {
                $sanitized[$clean_key] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$clean_key] = $value;
            } else {
                // Skip unknown types for security
                continue;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Render error template
     * 
     * @param string $template_name Template name that failed
     * @param string $error_message Error message
     * @return string Error template content
     */
    private function render_error_template($template_name, $error_message) {
        $error_title = $this->translator ? 
            $this->translator->get('template_error', 'Template Error') : 
            'Template Error';
            
        return sprintf(
            '<div class="qcc-template-error" data-template="%s">
                <div class="qcc-error-message">
                    <h4>%s</h4>
                    <p>%s: <code>%s</code></p>
                    <p>%s</p>
                </div>
            </div>',
            esc_attr($template_name),
            esc_html($error_title),
            esc_html('Template'),
            esc_html($template_name),
            esc_html($error_message)
        );
    }
    
    /**
     * Sanitize template name
     * 
     * @param string $template_name Raw template name
     * @return string Sanitized template name
     */
    private function sanitize_template_name($template_name) {
        // Remove any potential path traversal attempts
        $template_name = str_replace(array('..', '\\'), '', $template_name);
        
        // Ensure forward slashes only
        $template_name = str_replace('\\', '/', $template_name);
        
        // Remove leading/trailing slashes
        $template_name = trim($template_name, '/');
        
        return $template_name;
    }
    
    /**
     * Generate cache key for template
     * 
     * @param string $template_name Template name
     * @param array $context Template context
     * @return string Cache key
     */
    private function generate_cache_key($template_name, $context) {
        $key_parts = array(
            $template_name,
            $this->translator ? $this->translator->get_current_language() : 'en',
            get_template(),
            wp_is_mobile() ? 'mobile' : 'desktop'
        );
        
        if (!empty($context)) {
            $key_parts[] = md5(serialize($context));
        }
        
        return 'qcc_template_' . md5(implode('_', $key_parts));
    }
    
    /**
     * Clear template cache
     * 
     * @param string|null $template_name Specific template to clear, or null for all
     */
    public function clear_cache($template_name = null) {
        if ($template_name) {
            // Clear specific template cache entries
            $pattern = 'qcc_template_' . md5($template_name);
            foreach ($this->template_cache as $key => $value) {
                if (strpos($key, $pattern) !== false) {
                    unset($this->template_cache[$key]);
                }
            }
        } else {
            // Clear all template cache
            $this->template_cache = array();
        }
    }
    
    /**
     * Get template loading statistics
     * 
     * @return array Loading statistics
     */
    public function get_load_stats() {
        return $this->load_stats;
    }
    
    /**
     * Get available templates in all search paths
     * 
     * @param string|null $category Filter by category (e.g., 'calculator', 'forms')
     * @return array Available templates
     */
    public function get_available_templates($category = null) {
        $templates = array();
        
        foreach ($this->template_paths as $path_key => $path_info) {
            if (!is_dir($path_info['path'])) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path_info['path'])
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), array('php', 'html'))) {
                    $relative_path = str_replace($path_info['path'], '', $file->getPathname());
                    $template_name = str_replace('.' . $file->getExtension(), '', $relative_path);
                    $template_name = trim($template_name, '/\\');
                    
                    if ($category && strpos($template_name, $category . '/') !== 0) {
                        continue;
                    }
                    
                    $templates[$template_name] = array(
                        'name' => $template_name,
                        'path' => $file->getPathname(),
                        'source' => $path_info['description'],
                        'priority' => $path_info['priority']
                    );
                }
            }
        }
        
        // Sort by template name
        ksort($templates);
        
        return $templates;
    }
    
    /**
     * Validate template file
     * 
     * @param string $template_file Template file path
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_template($template_file) {
        if (!file_exists($template_file)) {
            return new WP_Error('template_not_found', 'Template file does not exist');
        }
        
        if (!is_readable($template_file)) {
            return new WP_Error('template_not_readable', 'Template file is not readable');
        }
        
        // Basic PHP syntax check
        $content = file_get_contents($template_file);
        
        // Check for PHP opening tag
        if (strpos($content, '<?php') === false && strpos($content, '<?=') === false) {
            // HTML-only template is valid
            return true;
        }
        
        // Check for potential security issues
        $dangerous_functions = array('eval', 'exec', 'system', 'shell_exec', 'passthru');
        foreach ($dangerous_functions as $function) {
            if (strpos($content, $function . '(') !== false) {
                return new WP_Error('template_security_risk', 'Template contains potentially dangerous functions');
            }
        }
        
        return true;
    }
    
    /**
     * Get debug information
     * 
     * @return array Debug information
     */
    public function get_debug_info() {
        return array(
            'template_paths' => $this->template_paths,
            'cache_size' => count($this->template_cache),
            'load_stats' => $this->load_stats,
            'debug_mode' => $this->debug_mode,
            'cache_keys' => array_keys($this->template_cache)
        );
    }
}
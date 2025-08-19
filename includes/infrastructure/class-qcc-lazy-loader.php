<?php
/**
 * QCC Lazy Loader
 * 
 * Manages lazy loading of QCC components and services to optimize performance
 * and reduce memory usage by loading components only when needed.
 * 
 * @package    QCC
 * @subpackage Infrastructure
 * @since      3.0.0
 * @author     Quality Cost Calculator Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Lazy Loader Class
 * 
 * @since 3.0.0
 */
class QCC_Lazy_Loader {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Lazy_Loader
     */
    private static $instance = null;
    
    /**
     * Loaded components registry
     * 
     * @since 3.0.0
     * @var array
     */
    private $loaded_components = array();
    
    /**
     * Component load callbacks
     * 
     * @since 3.0.0
     * @var array
     */
    private $load_callbacks = array();
    
    /**
     * Loading priorities
     * 
     * @since 3.0.0
     * @var array
     */
    private $load_priorities = array();
    
    /**
     * Load statistics
     * 
     * @since 3.0.0
     * @var array
     */
    private $load_stats = array(
        'total_loads' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'load_times' => array(),
        'memory_usage' => array()
    );
    
    /**
     * Debug mode
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Preload components list
     * 
     * @since 3.0.0
     * @var array
     */
    private $preload_components = array();
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->init_preload_components();
        $this->register_hooks();
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Lazy_Loader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize preload components
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_preload_components() {
        $this->preload_components = apply_filters('qcc_preload_components', array(
            'QCC_Cache_Service',
            'QCC_Settings_Service'
        ));
    }
    
    /**
     * Register WordPress hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_hooks() {
        add_action('init', array($this, 'preload_critical_components'), 5);
        add_action('wp_footer', array($this, 'output_debug_info'), 999);
        add_action('admin_footer', array($this, 'output_debug_info'), 999);
    }
    
    /**
     * Load component with lazy loading
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @param array  $args Optional arguments
     * @return object|null Component instance or null on failure
     */
    public function load_component($component_name, $args = array()) {
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        try {
            // Check if already loaded
            if (isset($this->loaded_components[$component_name])) {
                $this->load_stats['cache_hits']++;
                
                if ($this->debug_mode) {
                    error_log("QCC Lazy Loader: Cache hit for {$component_name}");
                }
                
                return $this->loaded_components[$component_name];
            }
            
            $this->load_stats['cache_misses']++;
            
            // Try custom load callback first
            if (isset($this->load_callbacks[$component_name])) {
                $component = call_user_func($this->load_callbacks[$component_name], $args);
                if ($component) {
                    $this->loaded_components[$component_name] = $component;
                    $this->record_load_stats($component_name, $start_time, $start_memory);
                    return $component;
                }
            }
            
            // Standard class loading
            $class_name = $this->resolve_class_name($component_name);
            
            if (!class_exists($class_name)) {
                if ($this->debug_mode) {
                    error_log("QCC Lazy Loader: Class {$class_name} not found for component {$component_name}");
                }
                return null;
            }
            
            // Create instance
            $component = $this->create_component_instance($class_name, $args);
            
            if ($component) {
                $this->loaded_components[$component_name] = $component;
                $this->record_load_stats($component_name, $start_time, $start_memory);
                
                // Trigger loaded action
                do_action('qcc_component_loaded', $component_name, $component);
                
                if ($this->debug_mode) {
                    error_log("QCC Lazy Loader: Successfully loaded {$component_name}");
                }
            }
            
            return $component;
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Lazy Loader: Error loading {$component_name} - " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Resolve class name from component name
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return string Class name
     */
    private function resolve_class_name($component_name) {
        // If already a class name, return as is
        if (class_exists($component_name)) {
            return $component_name;
        }
        
        // Convert component name to class name
        if (strpos($component_name, 'QCC_') === 0) {
            return $component_name;
        }
        
        // Convert snake_case or kebab-case to CamelCase
        $class_name = 'QCC_' . str_replace(array('-', '_'), '_', $component_name);
        $class_name = implode('_', array_map('ucfirst', explode('_', $class_name)));
        
        return $class_name;
    }
    
    /**
     * Create component instance
     * 
     * @since 3.0.0
     * @param string $class_name Class name
     * @param array  $args Arguments
     * @return object|null Component instance
     */
    private function create_component_instance($class_name, $args = array()) {
        try {
            // Check if singleton pattern
            if (method_exists($class_name, 'get_instance')) {
                return call_user_func(array($class_name, 'get_instance'), $args);
            }
            
            // Standard instantiation
            if (empty($args)) {
                return new $class_name();
            } else {
                $reflection = new ReflectionClass($class_name);
                return $reflection->newInstanceArgs($args);
            }
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Lazy Loader: Failed to instantiate {$class_name} - " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Record loading statistics
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @param float  $start_time Start time
     * @param int    $start_memory Start memory
     * @return void
     */
    private function record_load_stats($component_name, $start_time, $start_memory) {
        $load_time = microtime(true) - $start_time;
        $memory_used = memory_get_usage() - $start_memory;
        
        $this->load_stats['total_loads']++;
        $this->load_stats['load_times'][$component_name] = $load_time;
        $this->load_stats['memory_usage'][$component_name] = $memory_used;
    }
    
    /**
     * Register custom load callback
     * 
     * @since 3.0.0
     * @param string   $component_name Component name
     * @param callable $callback Load callback
     * @param int      $priority Priority (higher loads first)
     * @return void
     */
    public function register_load_callback($component_name, $callback, $priority = 10) {
        if (!is_callable($callback)) {
            if ($this->debug_mode) {
                error_log("QCC Lazy Loader: Invalid callback for {$component_name}");
            }
            return;
        }
        
        $this->load_callbacks[$component_name] = $callback;
        $this->load_priorities[$component_name] = $priority;
    }
    
    /**
     * Unregister load callback
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return void
     */
    public function unregister_load_callback($component_name) {
        unset($this->load_callbacks[$component_name]);
        unset($this->load_priorities[$component_name]);
    }
    
    /**
     * Check if component is loaded
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return bool True if loaded
     */
    public function is_loaded($component_name) {
        return isset($this->loaded_components[$component_name]);
    }
    
    /**
     * Get loaded component
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return object|null Component instance or null
     */
    public function get_loaded_component($component_name) {
        return $this->loaded_components[$component_name] ?? null;
    }
    
    /**
     * Unload component
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return bool True if unloaded
     */
    public function unload_component($component_name) {
        if (isset($this->loaded_components[$component_name])) {
            $component = $this->loaded_components[$component_name];
            
            // Call cleanup method if exists
            if (method_exists($component, 'cleanup')) {
                $component->cleanup();
            }
            
            unset($this->loaded_components[$component_name]);
            
            do_action('qcc_component_unloaded', $component_name, $component);
            
            if ($this->debug_mode) {
                error_log("QCC Lazy Loader: Unloaded {$component_name}");
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Preload critical components
     * 
     * @since 3.0.0
     * @return void
     */
    public function preload_critical_components() {
        foreach ($this->preload_components as $component) {
            $this->load_component($component);
        }
    }
    
    /**
     * Get loading statistics
     * 
     * @since 3.0.0
     * @return array Loading statistics
     */
    public function get_load_statistics() {
        $stats = $this->load_stats;
        $stats['loaded_components'] = array_keys($this->loaded_components);
        $stats['registered_callbacks'] = array_keys($this->load_callbacks);
        $stats['cache_hit_ratio'] = $stats['total_loads'] > 0 ? 
            ($stats['cache_hits'] / $stats['total_loads']) * 100 : 0;
        
        return $stats;
    }
    
    /**
     * Clear all loaded components
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear_all_components() {
        foreach (array_keys($this->loaded_components) as $component_name) {
            $this->unload_component($component_name);
        }
        
        $this->loaded_components = array();
        
        if ($this->debug_mode) {
            error_log("QCC Lazy Loader: Cleared all components");
        }
    }
    
    /**
     * Reset loading statistics
     * 
     * @since 3.0.0
     * @return void
     */
    public function reset_statistics() {
        $this->load_stats = array(
            'total_loads' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'load_times' => array(),
            'memory_usage' => array()
        );
    }
    
    /**
     * Add component to preload list
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return void
     */
    public function add_preload_component($component_name) {
        if (!in_array($component_name, $this->preload_components)) {
            $this->preload_components[] = $component_name;
        }
    }
    
    /**
     * Remove component from preload list
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return void
     */
    public function remove_preload_component($component_name) {
        $key = array_search($component_name, $this->preload_components);
        if ($key !== false) {
            unset($this->preload_components[$key]);
            $this->preload_components = array_values($this->preload_components);
        }
    }
    
    /**
     * Output debug information
     * 
     * @since 3.0.0
     * @return void
     */
    public function output_debug_info() {
        if (!$this->debug_mode || !current_user_can('manage_options')) {
            return;
        }
        
        $stats = $this->get_load_statistics();
        
        echo "<!-- QCC Lazy Loader Debug Info\n";
        echo "Loaded Components: " . count($stats['loaded_components']) . "\n";
        echo "Total Loads: " . $stats['total_loads'] . "\n";
        echo "Cache Hit Ratio: " . round($stats['cache_hit_ratio'], 2) . "%\n";
        echo "Components: " . implode(', ', $stats['loaded_components']) . "\n";
        echo "-->\n";
    }
    
    /**
     * Get component with lazy loading (magic method)
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return object|null Component instance
     */
    public function __get($component_name) {
        return $this->load_component($component_name);
    }
    
    /**
     * Check if component exists (magic method)
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @return bool True if exists
     */
    public function __isset($component_name) {
        return $this->is_loaded($component_name) || 
               class_exists($this->resolve_class_name($component_name));
    }
}
<?php
/**
 * QCC Instance Cache
 * 
 * Manages caching of class instances to optimize performance and memory usage.
 * Provides intelligent caching strategies with automatic cleanup and monitoring.
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
 * QCC Instance Cache Class
 * 
 * @since 3.0.0
 */
class QCC_Instance_Cache {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Instance_Cache
     */
    private static $instance = null;
    
    /**
     * Cached instances
     * 
     * @since 3.0.0
     * @var array
     */
    private $cache = array();
    
    /**
     * Instance metadata
     * 
     * @since 3.0.0
     * @var array
     */
    private $metadata = array();
    
    /**
     * Cache configuration
     * 
     * @since 3.0.0
     * @var array
     */
    private $config = array();
    
    /**
     * Access statistics
     * 
     * @since 3.0.0
     * @var array
     */
    private $stats = array(
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
        'cleanups' => 0,
        'memory_saved' => 0
    );
    
    /**
     * Debug mode
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Cleanup hooks registered
     * 
     * @since 3.0.0
     * @var array
     */
    private $cleanup_hooks = array();
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->init_config();
        $this->register_hooks();
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Instance_Cache
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize cache configuration
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_config() {
        $defaults = array(
            'max_instances' => 100,
            'ttl' => 3600, // 1 hour
            'auto_cleanup' => true,
            'memory_limit' => 50 * 1024 * 1024, // 50MB
            'singleton_cache' => true,
            'weak_references' => class_exists('WeakReference')
        );
        
        $this->config = apply_filters('qcc_instance_cache_config', $defaults);
    }
    
    /**
     * Register WordPress hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_hooks() {
        // Cleanup on shutdown
        add_action('shutdown', array($this, 'cleanup_expired'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_cleanup'));
        }
        
        // Memory pressure cleanup
        if ($this->config['auto_cleanup']) {
            add_action('qcc_memory_pressure', array($this, 'emergency_cleanup'));
        }
    }
    
    /**
     * Get cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @param mixed  $default Default value if not found
     * @return mixed Cached instance or default
     */
    public function get($key, $default = null) {
        if (!$this->has($key)) {
            $this->stats['misses']++;
            return $default;
        }
        
        $cache_entry = $this->cache[$key];
        
        // Check for weak reference
        if ($this->config['weak_references'] && isset($cache_entry['weak_ref'])) {
            $instance = $cache_entry['weak_ref']->get();
            if ($instance === null) {
                $this->delete($key);
                $this->stats['misses']++;
                return $default;
            }
            $this->update_access_time($key);
            $this->stats['hits']++;
            return $instance;
        }
        
        // Regular reference
        if (isset($cache_entry['instance'])) {
            $this->update_access_time($key);
            $this->stats['hits']++;
            return $cache_entry['instance'];
        }
        
        $this->stats['misses']++;
        return $default;
    }
    
    /**
     * Set cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @param mixed  $instance Instance to cache
     * @param array  $options Cache options
     * @return bool True on success
     */
    public function set($key, $instance, $options = array()) {
        // Validate input
        if (empty($key) || !is_object($instance)) {
            return false;
        }
        
        // Check memory limits
        if (!$this->check_memory_limits()) {
            if ($this->debug_mode) {
                error_log("QCC Instance Cache: Memory limit reached, triggering cleanup");
            }
            $this->emergency_cleanup();
        }
        
        // Check instance limits
        if (count($this->cache) >= $this->config['max_instances']) {
            $this->cleanup_least_used();
        }
        
        $options = wp_parse_args($options, array(
            'ttl' => $this->config['ttl'],
            'singleton' => false,
            'weak_ref' => $this->config['weak_references']
        ));
        
        $cache_entry = array(
            'created' => time(),
            'accessed' => time(),
            'access_count' => 1,
            'ttl' => $options['ttl'],
            'class' => get_class($instance),
            'memory_size' => $this->estimate_memory_usage($instance)
        );
        
        // Use weak reference if enabled and supported
        if ($options['weak_ref'] && $this->config['weak_references']) {
            $cache_entry['weak_ref'] = WeakReference::create($instance);
        } else {
            $cache_entry['instance'] = $instance;
        }
        
        $this->cache[$key] = $cache_entry;
        $this->metadata[$key] = $options;
        $this->stats['sets']++;
        
        if ($this->debug_mode) {
            error_log("QCC Instance Cache: Cached instance '{$key}' of class " . get_class($instance));
        }
        
        return true;
    }
    
    /**
     * Check if key exists in cache
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @return bool True if exists and valid
     */
    public function has($key) {
        if (!isset($this->cache[$key])) {
            return false;
        }
        
        $cache_entry = $this->cache[$key];
        
        // Check TTL
        if ($this->is_expired($cache_entry)) {
            $this->delete($key);
            return false;
        }
        
        // Check weak reference validity
        if ($this->config['weak_references'] && isset($cache_entry['weak_ref'])) {
            if ($cache_entry['weak_ref']->get() === null) {
                $this->delete($key);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Delete cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @return bool True if deleted
     */
    public function delete($key) {
        if (!isset($this->cache[$key])) {
            return false;
        }
        
        $cache_entry = $this->cache[$key];
        
        // Call cleanup method if exists
        if (isset($cache_entry['instance']) && 
            method_exists($cache_entry['instance'], 'cache_cleanup')) {
            $cache_entry['instance']->cache_cleanup();
        }
        
        unset($this->cache[$key]);
        unset($this->metadata[$key]);
        $this->stats['deletes']++;
        
        if ($this->debug_mode) {
            error_log("QCC Instance Cache: Deleted cached instance '{$key}'");
        }
        
        return true;
    }
    
    /**
     * Clear entire cache
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear() {
        foreach (array_keys($this->cache) as $key) {
            $this->delete($key);
        }
        
        $this->cache = array();
        $this->metadata = array();
        $this->stats['cleanups']++;
        
        if ($this->debug_mode) {
            error_log("QCC Instance Cache: Cleared entire cache");
        }
    }
    
    /**
     * Get or create cached instance
     * 
     * @since 3.0.0
     * @param string   $key Cache key
     * @param callable $factory Factory function to create instance
     * @param array    $options Cache options
     * @return mixed Cached or newly created instance
     */
    public function get_or_set($key, $factory, $options = array()) {
        $instance = $this->get($key);
        
        if ($instance !== null) {
            return $instance;
        }
        
        if (!is_callable($factory)) {
            return null;
        }
        
        try {
            $instance = call_user_func($factory);
            
            if (is_object($instance)) {
                $this->set($key, $instance, $options);
                return $instance;
            }
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Instance Cache: Factory failed for '{$key}' - " . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Update access time for cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @return void
     */
    private function update_access_time($key) {
        if (isset($this->cache[$key])) {
            $this->cache[$key]['accessed'] = time();
            $this->cache[$key]['access_count']++;
        }
    }
    
    /**
     * Check if cache entry is expired
     * 
     * @since 3.0.0
     * @param array $cache_entry Cache entry
     * @return bool True if expired
     */
    private function is_expired($cache_entry) {
        if (!isset($cache_entry['ttl']) || $cache_entry['ttl'] <= 0) {
            return false; // No expiration
        }
        
        return (time() - $cache_entry['created']) > $cache_entry['ttl'];
    }
    
    /**
     * Cleanup expired entries
     * 
     * @since 3.0.0
     * @return int Number of cleaned up entries
     */
    public function cleanup_expired() {
        $cleaned = 0;
        
        foreach (array_keys($this->cache) as $key) {
            if (!$this->has($key)) { // This will delete expired entries
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            $this->stats['cleanups']++;
            
            if ($this->debug_mode) {
                error_log("QCC Instance Cache: Cleaned up {$cleaned} expired entries");
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Cleanup least recently used entries
     * 
     * @since 3.0.0
     * @param int $count Number of entries to clean
     * @return int Number of cleaned up entries
     */
    public function cleanup_least_used($count = null) {
        if ($count === null) {
            $count = max(1, (int) (count($this->cache) * 0.1)); // Clean 10%
        }
        
        // Sort by access time (oldest first)
        uasort($this->cache, function($a, $b) {
            return $a['accessed'] - $b['accessed'];
        });
        
        $cleaned = 0;
        $keys = array_keys($this->cache);
        
        for ($i = 0; $i < min($count, count($keys)); $i++) {
            $this->delete($keys[$i]);
            $cleaned++;
        }
        
        if ($cleaned > 0) {
            $this->stats['cleanups']++;
            
            if ($this->debug_mode) {
                error_log("QCC Instance Cache: Cleaned up {$cleaned} least used entries");
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Emergency cleanup on memory pressure
     * 
     * @since 3.0.0
     * @return void
     */
    public function emergency_cleanup() {
        $initial_count = count($this->cache);
        
        // Clean up 50% of cache aggressively
        $cleanup_count = (int) ($initial_count * 0.5);
        $this->cleanup_least_used($cleanup_count);
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $final_count = count($this->cache);
        $cleaned = $initial_count - $final_count;
        
        if ($this->debug_mode) {
            error_log("QCC Instance Cache: Emergency cleanup removed {$cleaned} entries");
        }
    }
    
    /**
     * Check memory limits
     * 
     * @since 3.0.0
     * @return bool True if within limits
     */
    private function check_memory_limits() {
        $current_memory = memory_get_usage();
        return $current_memory < $this->config['memory_limit'];
    }
    
    /**
     * Estimate memory usage of an object
     * 
     * @since 3.0.0
     * @param object $instance Object instance
     * @return int Estimated memory usage in bytes
     */
    private function estimate_memory_usage($instance) {
        if (function_exists('memory_get_usage')) {
            $before = memory_get_usage();
            $temp = serialize($instance);
            $after = memory_get_usage();
            unset($temp);
            return max(0, $after - $before);
        }
        
        // Fallback estimation
        return strlen(serialize($instance));
    }
    
    /**
     * Get cache statistics
     * 
     * @since 3.0.0
     * @return array Cache statistics
     */
    public function get_statistics() {
        $total_requests = $this->stats['hits'] + $this->stats['misses'];
        $hit_ratio = $total_requests > 0 ? ($this->stats['hits'] / $total_requests) * 100 : 0;
        
        $memory_usage = 0;
        foreach ($this->cache as $entry) {
            $memory_usage += $entry['memory_size'] ?? 0;
        }
        
        return array_merge($this->stats, array(
            'total_requests' => $total_requests,
            'hit_ratio' => round($hit_ratio, 2),
            'cached_instances' => count($this->cache),
            'memory_usage' => $memory_usage,
            'average_access_count' => $this->get_average_access_count(),
            'config' => $this->config
        ));
    }
    
    /**
     * Get average access count
     * 
     * @since 3.0.0
     * @return float Average access count
     */
    private function get_average_access_count() {
        if (empty($this->cache)) {
            return 0;
        }
        
        $total_accesses = 0;
        foreach ($this->cache as $entry) {
            $total_accesses += $entry['access_count'] ?? 0;
        }
        
        return round($total_accesses / count($this->cache), 2);
    }
    
    /**
     * Get cache entries by class
     * 
     * @since 3.0.0
     * @param string $class_name Class name to filter by
     * @return array Matching cache entries
     */
    public function get_by_class($class_name) {
        $matches = array();
        
        foreach ($this->cache as $key => $entry) {
            if (isset($entry['class']) && $entry['class'] === $class_name) {
                $instance = $this->get($key);
                if ($instance !== null) {
                    $matches[$key] = $instance;
                }
            }
        }
        
        return $matches;
    }
    
    /**
     * Delete cache entries by class
     * 
     * @since 3.0.0
     * @param string $class_name Class name to delete
     * @return int Number of deleted entries
     */
    public function delete_by_class($class_name) {
        $deleted = 0;
        
        foreach ($this->cache as $key => $entry) {
            if (isset($entry['class']) && $entry['class'] === $class_name) {
                if ($this->delete($key)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Set cache configuration
     * 
     * @since 3.0.0
     * @param array $config Configuration array
     * @return void
     */
    public function set_config($config) {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Get cache configuration
     * 
     * @since 3.0.0
     * @return array Configuration array
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Admin cleanup routine
     * 
     * @since 3.0.0
     * @return void
     */
    public function admin_cleanup() {
        // More aggressive cleanup in admin
        if (count($this->cache) > ($this->config['max_instances'] * 0.8)) {
            $this->cleanup_least_used();
        }
    }
    
    /**
     * Register cleanup hook
     * 
     * @since 3.0.0
     * @param string   $hook_name Hook name
     * @param callable $callback Cleanup callback
     * @return void
     */
    public function register_cleanup_hook($hook_name, $callback) {
        if (!isset($this->cleanup_hooks[$hook_name])) {
            $this->cleanup_hooks[$hook_name] = array();
        }
        
        $this->cleanup_hooks[$hook_name][] = $callback;
        add_action($hook_name, array($this, 'execute_cleanup_hooks'));
    }
    
    /**
     * Execute cleanup hooks
     * 
     * @since 3.0.0
     * @return void
     */
    public function execute_cleanup_hooks() {
        $current_hook = current_filter();
        
        if (isset($this->cleanup_hooks[$current_hook])) {
            foreach ($this->cleanup_hooks[$current_hook] as $callback) {
                if (is_callable($callback)) {
                    call_user_func($callback, $this);
                }
            }
        }
    }
    
    /**
     * Export cache data for debugging
     * 
     * @since 3.0.0
     * @return array Cache data
     */
    public function export_debug_data() {
        if (!$this->debug_mode) {
            return array('debug_disabled' => true);
        }
        
        $debug_data = array(
            'statistics' => $this->get_statistics(),
            'cache_keys' => array_keys($this->cache),
            'cache_details' => array()
        );
        
        foreach ($this->cache as $key => $entry) {
            $debug_data['cache_details'][$key] = array(
                'class' => $entry['class'] ?? 'unknown',
                'created' => date('Y-m-d H:i:s', $entry['created']),
                'accessed' => date('Y-m-d H:i:s', $entry['accessed']),
                'access_count' => $entry['access_count'] ?? 0,
                'memory_size' => $entry['memory_size'] ?? 0,
                'is_weak_ref' => isset($entry['weak_ref']),
                'ttl' => $entry['ttl'] ?? 0
            );
        }
        
        return $debug_data;
    }
    
    /**
     * Magic method to get cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @return mixed Cached instance or null
     */
    public function __get($key) {
        return $this->get($key);
    }
    
    /**
     * Magic method to set cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @param mixed  $instance Instance to cache
     * @return void
     */
    public function __set($key, $instance) {
        $this->set($key, $instance);
    }
    
    /**
     * Magic method to check if key exists
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function __isset($key) {
        return $this->has($key);
    }
    
    /**
     * Magic method to delete cached instance
     * 
     * @since 3.0.0
     * @param string $key Cache key
     * @return void
     */
    public function __unset($key) {
        $this->delete($key);
    }
}
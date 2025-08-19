<?php
/**
 * QCC Cache Service - Intelligent Caching System
 *
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_Cache_Service {
    
    private $bootstrap;
    private $cache_prefix = 'qcc_';
    private $default_expiration = 3600; // 1 hour
    private $cache_groups = array();
    private $hit_count = 0;
    private $miss_count = 0;
    
    public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ?: QCC_Bootstrap::get_instance();
        $this->init_cache_groups();
    }
    
    /**
     * Initialize cache groups
     */
    private function init_cache_groups() {
        $this->cache_groups = array(
            'calculations' => array('expiration' => 1800, 'prefix' => 'calc_'),
            'templates' => array('expiration' => 7200, 'prefix' => 'tpl_'),
            'translations' => array('expiration' => 86400, 'prefix' => 'lang_'),
            'assets' => array('expiration' => 3600, 'prefix' => 'asset_'),
            'settings' => array('expiration' => 3600, 'prefix' => 'set_'),
            'system' => array('expiration' => 300, 'prefix' => 'sys_')
        );
    }
    
    /**
     * Get cached value
     */
    public function get($key, $group = 'default') {
        $cache_key = $this->build_cache_key($key, $group);
        $value = get_transient($cache_key);
        
        if ($value !== false) {
            $this->hit_count++;
            return $value;
        }
        
        $this->miss_count++;
        return false;
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $group = 'default', $expiration = null) {
        $cache_key = $this->build_cache_key($key, $group);
        $expiration = $expiration ?: $this->get_group_expiration($group);
        
        return set_transient($cache_key, $value, $expiration);
    }
    
    /**
     * Delete cached value
     */
    public function delete($key, $group = 'default') {
        $cache_key = $this->build_cache_key($key, $group);
        return delete_transient($cache_key);
    }
    
    /**
     * Clear cache group
     */
    public function clear_group($group) {
        global $wpdb;
        
        $group_prefix = $this->get_group_prefix($group);
        $cache_prefix = '_transient_' . $this->cache_prefix . $group_prefix;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $cache_prefix . '%',
                '_transient_timeout_' . $this->cache_prefix . $group_prefix . '%'
            )
        );
    }
    
    /**
     * Clear all cache
     */
    public function clear_all() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%',
                '_transient_timeout_' . $this->cache_prefix . '%'
            )
        );
        
        $this->hit_count = 0;
        $this->miss_count = 0;
    }
    
    /**
     * Cache with callback
     */
    public function remember($key, $callback, $group = 'default', $expiration = null) {
        $value = $this->get($key, $group);
        
        if ($value === false) {
            $value = call_user_func($callback);
            $this->set($key, $value, $group, $expiration);
        }
        
        return $value;
    }
    
    /**
     * Cache calculation results
     */
    public function cache_calculation($input, $result) {
        $key = md5(serialize($input));
        return $this->set($key, $result, 'calculations', 1800);
    }
    
    /**
     * Get cached calculation
     */
    public function get_cached_calculation($input) {
        $key = md5(serialize($input));
        return $this->get($key, 'calculations');
    }
    
    /**
     * Cache template output
     */
    public function cache_template($template_name, $content, $context = array()) {
        $key = $template_name . '_' . md5(serialize($context));
        return $this->set($key, $content, 'templates', 7200);
    }
    
    /**
     * Get cached template
     */
    public function get_cached_template($template_name, $context = array()) {
        $key = $template_name . '_' . md5(serialize($context));
        return $this->get($key, 'templates');
    }
    
    /**
     * Cache translations
     */
    public function cache_translations($language, $translations) {
        return $this->set($language, $translations, 'translations', 86400);
    }
    
    /**
     * Get cached translations
     */
    public function get_cached_translations($language) {
        return $this->get($language, 'translations');
    }
    
    /**
     * Cache asset data
     */
    public function cache_asset($asset_type, $data) {
        return $this->set($asset_type, $data, 'assets', 3600);
    }
    
    /**
     * Get cached asset
     */
    public function get_cached_asset($asset_type) {
        return $this->get($asset_type, 'assets');
    }
    
    /**
     * Build cache key
     */
    private function build_cache_key($key, $group) {
        $group_prefix = $this->get_group_prefix($group);
        return $this->cache_prefix . $group_prefix . $key;
    }
    
    /**
     * Get group prefix
     */
    private function get_group_prefix($group) {
        return isset($this->cache_groups[$group]) 
            ? $this->cache_groups[$group]['prefix'] 
            : 'def_';
    }
    
    /**
     * Get group expiration
     */
    private function get_group_expiration($group) {
        return isset($this->cache_groups[$group]) 
            ? $this->cache_groups[$group]['expiration'] 
            : $this->default_expiration;
    }
    
    /**
     * Cleanup expired cache
     */
    public function cleanup_expired() {
        global $wpdb;
        
        $current_time = time();
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
                '_transient_timeout_' . $this->cache_prefix . '%',
                $current_time
            )
        );
    }
    
    /**
     * Get cache statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $total_cached = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        
        $hit_rate = ($this->hit_count + $this->miss_count) > 0 
            ? ($this->hit_count / ($this->hit_count + $this->miss_count)) * 100 
            : 0;
        
        return array(
            'total_cached_items' => intval($total_cached),
            'cache_hits' => $this->hit_count,
            'cache_misses' => $this->miss_count,
            'hit_rate' => round($hit_rate, 2),
            'groups' => array_keys($this->cache_groups)
        );
    }
    
    /**
     * Warm up cache
     */
    public function warmup() {
        // Cache default translations
        if (function_exists('qcc_get_option')) {
            $language = qcc_get_option('default_language', 'en');
            $this->cache_translations($language, array());
        }
        
        // Cache system info
        $this->set('system_info', array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => QCC_PLUGIN_VERSION
        ), 'system', 3600);
    }
    
    /**
     * Is caching enabled
     */
    public function is_enabled() {
        if ($this->bootstrap) {
            return $this->bootstrap->get_feature_flag('enable_advanced_caching', true);
        }
        return true;
    }
    
    /**
     * Flush cache on plugin update
     */
    public function flush_on_update() {
        $cached_version = $this->get('plugin_version', 'system');
        
        if ($cached_version !== QCC_PLUGIN_VERSION) {
            $this->clear_all();
            $this->set('plugin_version', QCC_PLUGIN_VERSION, 'system');
        }
    }
}
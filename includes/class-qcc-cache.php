<?php
/**
 * Cache functionality for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cache Class
 */
class QCC_Cache {
    
    /**
     * Cache prefix
     */
    const CACHE_PREFIX = 'qcc_cache_';
    
    /**
     * Default cache expiration (1 hour)
     */
    const DEFAULT_EXPIRATION = 3600;
    
    /**
     * Set cache
     */
    public static function set($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = self::DEFAULT_EXPIRATION;
        }
        
        $cache_key = self::CACHE_PREFIX . $key;
        $cache_data = array(
            'data' => $data,
            'timestamp' => time(),
            'expiration' => $expiration
        );
        
        return set_transient($cache_key, $cache_data, $expiration);
    }
    
    /**
     * Get cache
     */
    public static function get($key) {
        $cache_key = self::CACHE_PREFIX . $key;
        $cache_data = get_transient($cache_key);
        
        if ($cache_data === false) {
            return false;
        }
        
        // Check if cache has expired (additional check)
        if (isset($cache_data['timestamp'], $cache_data['expiration'])) {
            $expired_time = $cache_data['timestamp'] + $cache_data['expiration'];
            if (time() > $expired_time) {
                self::delete($key);
                return false;
            }
        }
        
        return isset($cache_data['data']) ? $cache_data['data'] : false;
    }
    
    /**
     * Delete cache
     */
    public static function delete($key) {
        $cache_key = self::CACHE_PREFIX . $key;
        return delete_transient($cache_key);
    }
    
    /**
     * Clear all plugin cache
     */
    public static function clear_all() {
        global $wpdb;
        
        $cache_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $deleted = 0;
        foreach ($cache_keys as $cache_key) {
            $key = str_replace('_transient_' . self::CACHE_PREFIX, '', $cache_key);
            if (self::delete($key)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get cache statistics
     */
    public static function get_stats() {
        global $wpdb;
        
        $cache_keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $stats = array(
            'total_entries' => 0,
            'total_size' => 0,
            'expired_entries' => 0,
            'entries' => array()
        );
        
        foreach ($cache_keys as $cache_key) {
            $key = str_replace('_transient_' . self::CACHE_PREFIX, '', $cache_key->option_name);
            $data = maybe_unserialize($cache_key->option_value);
            $size = strlen(serialize($data));
            
            $stats['total_entries']++;
            $stats['total_size'] += $size;
            
            $is_expired = false;
            if (isset($data['timestamp'], $data['expiration'])) {
                $expired_time = $data['timestamp'] + $data['expiration'];
                if (time() > $expired_time) {
                    $stats['expired_entries']++;
                    $is_expired = true;
                }
            }
            
            $stats['entries'][] = array(
                'key' => $key,
                'size' => $size,
                'created' => isset($data['timestamp']) ? date('Y-m-d H:i:s', $data['timestamp']) : 'Unknown',
                'expires' => isset($data['timestamp'], $data['expiration']) ? 
                    date('Y-m-d H:i:s', $data['timestamp'] + $data['expiration']) : 'Never',
                'expired' => $is_expired
            );
        }
        
        return $stats;
    }
    
    /**
     * Cache calculation results
     */
    public static function cache_calculation($input_hash, $results) {
        $key = 'calculation_' . $input_hash;
        return self::set($key, $results, 1800); // 30 minutes
    }
    
    /**
     * Get cached calculation results
     */
    public static function get_cached_calculation($input_hash) {
        $key = 'calculation_' . $input_hash;
        return self::get($key);
    }
    
    /**
     * Generate hash for calculation inputs
     */
    public static function generate_input_hash($inputs) {
        ksort($inputs); // Sort to ensure consistent hash
        return md5(serialize($inputs));
    }
    
    /**
     * Cache translation data
     */
    public static function cache_translations($language, $translations) {
        $key = 'translations_' . $language;
        return self::set($key, $translations, 86400); // 24 hours
    }
    
    /**
     * Get cached translations
     */
    public static function get_cached_translations($language) {
        $key = 'translations_' . $language;
        return self::get($key);
    }
    
    /**
     * Cache system info
     */
    public static function cache_system_info($info) {
        return self::set('system_info', $info, 300); // 5 minutes
    }
    
    /**
     * Get cached system info
     */
    public static function get_cached_system_info() {
        return self::get('system_info');
    }
    
    /**
     * Cleanup expired cache entries
     */
    public static function cleanup_expired() {
        global $wpdb;
        
        $cache_keys = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $cleaned = 0;
        foreach ($cache_keys as $cache_key) {
            $data = maybe_unserialize($cache_key->option_value);
            
            if (isset($data['timestamp'], $data['expiration'])) {
                $expired_time = $data['timestamp'] + $data['expiration'];
                if (time() > $expired_time) {
                    $key = str_replace('_transient_' . self::CACHE_PREFIX, '', $cache_key->option_name);
                    if (self::delete($key)) {
                        $cleaned++;
                    }
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Schedule automatic cache cleanup
     */
    public static function schedule_cleanup() {
        if (!wp_next_scheduled('qcc_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'qcc_cache_cleanup');
        }
    }
    
    /**
     * Unschedule automatic cache cleanup
     */
    public static function unschedule_cleanup() {
        $timestamp = wp_next_scheduled('qcc_cache_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'qcc_cache_cleanup');
        }
    }
}

// Hook for automatic cache cleanup
add_action('qcc_cache_cleanup', array('QCC_Cache', 'cleanup_expired'));
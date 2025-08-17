<?php
/**
 * Plugin configuration and constants
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Configuration Class
 */
class QCC_Config {
    
    /**
     * Plugin version
     */
    const VERSION = '1.1.0';
    
    /**
     * Minimum PHP version
     */
    const MIN_PHP_VERSION = '7.4';
    
    /**
     * Minimum WordPress version
     */
    const MIN_WP_VERSION = '5.0';
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'quality-cost-calculator';
    
    /**
     * Plugin option prefix
     */
    const OPTION_PREFIX = 'qcc_';
    
    /**
     * Plugin capability requirement
     */
    const CAPABILITY = 'manage_options';
    
    /**
     * AJAX nonce action
     */
    const NONCE_ACTION = 'qcc_nonce';
    
    /**
     * Default values
     */
    const DEFAULTS = array(
        'revenue' => 140,
        'quality_percentage' => 6,
        'prevention' => 10,
        'appraisal' => 20,
        'internal_defect' => 30,
        'external_defect' => 40,
        'lost_sales' => 5,
        'customer_churn' => 2,
        'market_share_loss' => 1,
        'productivity_loss' => 3
    );
    
    /**
     * Supported languages
     */
    const LANGUAGES = array(
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'zh' => '中文'
    );
    
    /**
     * Supported currencies
     */
    const CURRENCIES = array(
        '€' => 'Euro',
        '$' => 'US-Dollar',
        '¥' => 'Renminbi'
    );
    
    /**
     * Supported units
     */
    const UNITS = array(
        '1000000' => 'Millions',
        '1000000000' => 'Billions'
    );
    
    /**
     * Chart.js CDN URL
     */
    const CHARTJS_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
    
    /**
     * Chart.js version
     */
    const CHARTJS_VERSION = '3.9.1';
    
    /**
     * Plugin database version
     */
    const DB_VERSION = '1.0';
    
    /**
     * Cache expiration time (in seconds)
     */
    const CACHE_EXPIRATION = 3600; // 1 hour
    
    /**
     * Maximum memory limit for calculations
     */
    const MAX_MEMORY_LIMIT = '128M';
    
    /**
     * Debug mode
     */
    const DEBUG_MODE = WP_DEBUG;
    
    /**
     * Get plugin URL
     */
    public static function get_plugin_url() {
        return QCC_PLUGIN_URL;
    }
    
    /**
     * Get plugin path
     */
    public static function get_plugin_path() {
        return QCC_PLUGIN_PATH;
    }
    
    /**
     * Get asset URL
     */
    public static function get_asset_url($asset) {
        return self::get_plugin_url() . 'assets/' . $asset;
    }
    
    /**
     * Get template path
     */
    public static function get_template_path($template) {
        return self::get_plugin_path() . 'templates/' . $template;
    }
    
    /**
     * Get include path
     */
    public static function get_include_path($file) {
        return self::get_plugin_path() . 'includes/' . $file;
    }
    
    /**
     * Check if debug mode is enabled
     */
    public static function is_debug() {
        return self::DEBUG_MODE && WP_DEBUG_LOG;
    }
    
    /**
     * Get option with prefix
     */
    public static function get_option($option, $default = '') {
        return get_option(self::OPTION_PREFIX . $option, $default);
    }
    
    /**
     * Set option with prefix
     */
    public static function set_option($option, $value) {
        return update_option(self::OPTION_PREFIX . $option, $value);
    }
    
    /**
     * Delete option with prefix
     */
    public static function delete_option($option) {
        return delete_option(self::OPTION_PREFIX . $option);
    }
    
    /**
     * Get all plugin options
     */
    public static function get_all_options() {
        global $wpdb;
        
        $options = array();
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                self::OPTION_PREFIX . '%'
            )
        );
        
        foreach ($results as $result) {
            $key = str_replace(self::OPTION_PREFIX, '', $result->option_name);
            $options[$key] = maybe_unserialize($result->option_value);
        }
        
        return $options;
    }
    
    /**
     * Check system requirements
     */
    public static function check_requirements() {
        $errors = array();
        
        // Check PHP version
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<')) {
            $errors[] = sprintf(
                'PHP version %s or higher is required. Current version: %s',
                self::MIN_PHP_VERSION,
                PHP_VERSION
            );
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), self::MIN_WP_VERSION, '<')) {
            $errors[] = sprintf(
                'WordPress version %s or higher is required. Current version: %s',
                self::MIN_WP_VERSION,
                get_bloginfo('version')
            );
        }
        
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit && $memory_limit !== '-1') {
            $memory_bytes = self::convert_to_bytes($memory_limit);
            $required_bytes = self::convert_to_bytes(self::MAX_MEMORY_LIMIT);
            
            if ($memory_bytes < $required_bytes) {
                $errors[] = sprintf(
                    'Memory limit of %s or higher is recommended. Current limit: %s',
                    self::MAX_MEMORY_LIMIT,
                    $memory_limit
                );
            }
        }
        
        return $errors;
    }
    
    /**
     * Convert memory value to bytes
     */
    private static function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Get plugin info for debugging
     */
    public static function get_debug_info() {
        return array(
            'plugin_version' => self::VERSION,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => size_format(memory_get_usage(true)),
            'memory_peak' => size_format(memory_get_peak_usage(true)),
            'active_theme' => wp_get_theme()->get('Name'),
            'plugin_options' => self::get_all_options(),
            'system_requirements' => self::check_requirements()
        );
    }
}
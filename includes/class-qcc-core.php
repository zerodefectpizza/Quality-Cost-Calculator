<?php
/**
 * Core functionality for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core Class
 */
class QCC_Core {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            
            // Enqueue Chart.js
            wp_enqueue_script(
                'chart-js', 
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', 
                array(), 
                '3.9.1', 
                true
            );
            
            // Enqueue plugin JavaScript
            wp_enqueue_script(
                'quality-cost-calculator-js', 
                QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js', 
                array('jquery', 'chart-js'), 
                QCC_PLUGIN_VERSION, 
                true
            );
            
            // Enqueue plugin CSS
            wp_enqueue_style(
                'quality-cost-calculator-css', 
                QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css', 
                array(), 
                QCC_PLUGIN_VERSION
            );
            
            // Localize script for AJAX
            wp_localize_script('quality-cost-calculator-js', 'qcc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('qcc_nonce'),
                'default_language' => get_option('qcc_default_language', 'en'),
                'plugin_url' => QCC_PLUGIN_URL
            ));
        }
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return QCC_PLUGIN_VERSION;
    }
    
    /**
     * Get plugin URL
     */
    public function get_plugin_url() {
        return QCC_PLUGIN_URL;
    }
    
    /**
     * Get plugin path
     */
    public function get_plugin_path() {
        return QCC_PLUGIN_PATH;
    }
    
    /**
     * Check system requirements
     */
    public function check_requirements() {
        $requirements = array(
            'php' => version_compare(PHP_VERSION, '7.4', '>='),
            'wordpress' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'jquery' => wp_script_is('jquery', 'registered')
        );
        
        return $requirements;
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'language' => get_option('qcc_default_language', 'en'),
            'currency' => get_option('qcc_default_currency', '€'),
            'unit' => get_option('qcc_default_unit', '1000000')
        );
    }
    
    /**
     * Log message
     */
    public function log($message, $level = 'info') {
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log("QCC [{$level}]: {$message}");
        }
    }
}
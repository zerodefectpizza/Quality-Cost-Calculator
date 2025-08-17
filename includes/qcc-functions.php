<?php
/**
 * Helper functions for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin instance - CONFLICT PROTECTED VERSION
 */
if (!function_exists('qcc')) {
    function qcc() {
        if (class_exists('QualityCostCalculator')) {
            return QualityCostCalculator::get_instance();
        }
        return false;
    }
}

/**
 * Get plugin option
 */
if (!function_exists('qcc_get_option')) {
    function qcc_get_option($option_name, $default = '') {
        return get_option('qcc_' . $option_name, $default);
    }
}

/**
 * Set plugin option
 */
if (!function_exists('qcc_set_option')) {
    function qcc_set_option($option_name, $value) {
        return update_option('qcc_' . $option_name, $value);
    }
}

/**
 * Delete plugin option
 */
if (!function_exists('qcc_delete_option')) {
    function qcc_delete_option($option_name) {
        return delete_option('qcc_' . $option_name);
    }
}

/**
 * Log message for debugging
 */
if (!function_exists('qcc_log')) {
    function qcc_log($message, $level = 'info') {
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log($message, strtoupper($level));
        } elseif (WP_DEBUG && WP_DEBUG_LOG) {
            error_log("QCC [{$level}]: {$message}");
        }
    }
}

/**
 * Check if current page has calculator shortcode
 */
if (!function_exists('qcc_has_shortcode')) {
    function qcc_has_shortcode() {
        global $post;
        return is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'quality_cost_calculator');
    }
}

/**
 * Get default calculator values
 */
if (!function_exists('qcc_get_default_values')) {
    function qcc_get_default_values() {
        return array(
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
    }
}

/**
 * Validate percentage values
 */
if (!function_exists('qcc_validate_percentages')) {
    function qcc_validate_percentages($prevention, $appraisal, $internal, $external) {
        $total = $prevention + $appraisal + $internal + $external;
        return abs($total - 100) < 0.01;
    }
}

/**
 * Calculate quality costs
 */
if (!function_exists('qcc_calculate_quality_costs')) {
    function qcc_calculate_quality_costs($revenue, $quality_percentage, $prevention, $appraisal, $internal, $external) {
        $total_quality_cost = ($revenue * $quality_percentage) / 100;
        
        return array(
            'total_quality_cost' => $total_quality_cost,
            'prevention_cost' => ($total_quality_cost * $prevention) / 100,
            'appraisal_cost' => ($total_quality_cost * $appraisal) / 100,
            'internal_defect_cost' => ($total_quality_cost * $internal) / 100,
            'external_defect_cost' => ($total_quality_cost * $external) / 100
        );
    }
}

/**
 * Calculate COGQ and COPQ
 */
if (!function_exists('qcc_calculate_cogq_copq')) {
    function qcc_calculate_cogq_copq($costs) {
        $cogq = $costs['prevention_cost'] + $costs['appraisal_cost'];
        $copq = $costs['internal_defect_cost'] + $costs['external_defect_cost'];
        
        return array(
            'cogq' => $cogq,
            'copq' => $copq,
            'cogq_percentage' => ($cogq / $costs['total_quality_cost']) * 100,
            'copq_percentage' => ($copq / $costs['total_quality_cost']) * 100
        );
    }
}

/**
 * Calculate opportunity costs
 */
if (!function_exists('qcc_calculate_opportunity_costs')) {
    function qcc_calculate_opportunity_costs($revenue, $lost_sales, $customer_churn, $market_share, $productivity) {
        return array(
            'lost_sales_cost' => ($revenue * $lost_sales) / 100,
            'customer_churn_cost' => ($revenue * $customer_churn) / 100,
            'market_share_cost' => ($revenue * $market_share) / 100,
            'productivity_cost' => ($revenue * $productivity) / 100
        );
    }
}

/**
 * Format currency amount
 */
if (!function_exists('qcc_format_currency')) {
    function qcc_format_currency($amount, $currency = 'EUR', $language = 'en', $unit = 1000000) {
        $formatted_amount = number_format($amount / $unit, 2);
        
        // Language-specific formatting
        switch ($language) {
            case 'de':
                $formatted_amount = number_format($amount / $unit, 2, ',', '.');
                return $formatted_amount . ' ' . $currency;
            case 'fr':
                $formatted_amount = number_format($amount / $unit, 2, ',', ' ');
                return $formatted_amount . ' ' . $currency;
            case 'zh':
                return $currency . ' ' . number_format($amount / $unit, 2);
            default:
                return $currency . ' ' . number_format($amount / $unit, 2);
        }
    }
}

/**
 * Get unit name in different languages
 */
if (!function_exists('qcc_get_unit_name')) {
    function qcc_get_unit_name($unit, $language = 'en') {
        $units = array(
            'en' => array(
                '1000000' => 'Millions',
                '1000000000' => 'Billions'
            ),
            'de' => array(
                '1000000' => 'Millionen',
                '1000000000' => 'Milliarden'
            ),
            'fr' => array(
                '1000000' => 'Millions',
                '1000000000' => 'Milliards'
            ),
            'zh' => array(
                '1000000' => '百万',
                '1000000000' => '十亿'
            )
        );
        
        return isset($units[$language][$unit]) ? $units[$language][$unit] : $units['en'][$unit];
    }
}

/**
 * Sanitize calculator input
 */
if (!function_exists('qcc_sanitize_input')) {
    function qcc_sanitize_input($input, $type = 'number') {
        switch ($type) {
            case 'number':
                return floatval($input);
            case 'percentage':
                $value = floatval($input);
                return max(0, min(100, $value)); // Clamp between 0-100
            case 'language':
                $allowed = array('en', 'de', 'fr', 'zh');
                return in_array($input, $allowed) ? $input : 'en';
            case 'currency':
                $allowed = array('EUR', 'USD', 'CNY');
                return in_array($input, $allowed) ? $input : 'EUR';
            case 'unit':
                $allowed = array('1000000', '1000000000');
                return in_array($input, $allowed) ? $input : '1000000';
            default:
                return sanitize_text_field($input);
        }
    }
}

/**
 * Get system requirements status
 */
if (!function_exists('qcc_check_system_requirements')) {
    function qcc_check_system_requirements() {
        return array(
            'php_version' => array(
                'required' => '7.4',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4', '>=')
            ),
            'wordpress_version' => array(
                'required' => '5.0',
                'current' => get_bloginfo('version'),
                'status' => version_compare(get_bloginfo('version'), '5.0', '>=')
            ),
            'memory_limit' => array(
                'required' => '64M',
                'current' => ini_get('memory_limit'),
                'status' => qcc_check_memory_limit()
            ),
            'jquery' => array(
                'required' => 'Available',
                'current' => wp_script_is('jquery', 'registered') ? 'Available' : 'Not Available',
                'status' => wp_script_is('jquery', 'registered')
            )
        );
    }
}

/**
 * Check memory limit
 */
if (!function_exists('qcc_check_memory_limit')) {
    function qcc_check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit === '-1') return true; // Unlimited
        
        $memory_limit_bytes = qcc_convert_to_bytes($memory_limit);
        $required_bytes = qcc_convert_to_bytes('64M');
        
        return $memory_limit_bytes >= $required_bytes;
    }
}

/**
 * Convert memory value to bytes
 */
if (!function_exists('qcc_convert_to_bytes')) {
    function qcc_convert_to_bytes($value) {
        $value = trim($value);
        if ($value === '-1') return -1; // Unlimited
        
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
}

/**
 * Generate nonce for AJAX requests
 */
if (!function_exists('qcc_get_ajax_nonce')) {
    function qcc_get_ajax_nonce() {
        return wp_create_nonce(defined('QCC_NONCE_ACTION') ? QCC_NONCE_ACTION : 'qcc_nonce');
    }
}

/**
 * Verify nonce for AJAX requests
 */
if (!function_exists('qcc_verify_ajax_nonce')) {
    function qcc_verify_ajax_nonce($nonce) {
        return wp_verify_nonce($nonce, defined('QCC_NONCE_ACTION') ? QCC_NONCE_ACTION : 'qcc_nonce');
    }
}

/**
 * Remove directory recursively
 */
if (!function_exists('qcc_remove_directory_recursive')) {
    function qcc_remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? qcc_remove_directory_recursive($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}

/**
 * Clean up plugin data on uninstall
 */
if (!function_exists('qcc_cleanup_plugin_data')) {
    function qcc_cleanup_plugin_data() {
        if (function_exists('qcc_log')) {
            qcc_log('Starting plugin data cleanup', 'cleanup');
        }
        
        // Remove options
        $options = array(
            'qcc_default_language',
            'qcc_default_currency',
            'qcc_default_unit',
            'qcc_version',
            'qcc_db_version',
            'qcc_activation_time'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove transients
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_qcc_%',
                '_transient_timeout_qcc_%'
            )
        );
        
        // Remove assets directory
        $upload_dir = wp_upload_dir();
        $assets_dirs = array(
            $upload_dir['basedir'] . '/quality-cost-calculator-cache',
            $upload_dir['basedir'] . '/quality-cost-calculator-exports',
            $upload_dir['basedir'] . '/quality-cost-calculator-temp'
        );
        
        foreach ($assets_dirs as $dir) {
            if (file_exists($dir)) {
                qcc_remove_directory_recursive($dir);
            }
        }
        
        if (function_exists('qcc_log')) {
            qcc_log('Plugin data cleanup completed', 'cleanup');
        }
    }
}

/**
 * Get plugin health status
 */
if (!function_exists('qcc_get_health_status')) {
    function qcc_get_health_status() {
        $status = array(
            'overall' => 'good',
            'issues' => array(),
            'details' => array()
        );
        
        // Check if plugin is properly loaded
        if (!function_exists('qcc') || !qcc()) {
            $status['overall'] = 'critical';
            $status['issues'][] = 'Plugin not properly initialized';
        }
        
        // Check system requirements
        $requirements = qcc_check_system_requirements();
        foreach ($requirements as $req => $data) {
            if (!$data['status']) {
                $status['overall'] = 'recommended';
                $status['issues'][] = "{$req}: {$data['current']} (required: {$data['required']})";
            }
        }
        
        // Check shortcode registration
        if (!shortcode_exists('quality_cost_calculator')) {
            $status['overall'] = 'warning';
            $status['issues'][] = 'Shortcode not registered';
        }
        
        $status['details'] = array(
            'plugin_version' => defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : 'Unknown',
            'memory_usage' => size_format(memory_get_usage(true)),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled',
            'shortcode_exists' => shortcode_exists('quality_cost_calculator') ? 'Yes' : 'No'
        );
        
        return $status;
    }
}

/**
 * Emergency function to reset plugin settings
 */
if (!function_exists('qcc_emergency_reset')) {
    function qcc_emergency_reset() {
        if (!current_user_can('administrator')) {
            return false;
        }
        
        if (function_exists('qcc_log')) {
            qcc_log('Emergency reset initiated', 'emergency');
        }
        
        // Reset to default options
        $defaults = array(
            'default_language' => 'en',
            'default_currency' => 'EUR',
            'default_unit' => '1000000',
            'enable_caching' => true,
            'debug_mode' => false
        );
        
        foreach ($defaults as $option => $value) {
            qcc_set_option($option, $value);
        }
        
        // Clear all transients
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_qcc_%',
                '_transient_timeout_qcc_%'
            )
        );
        
        if (function_exists('qcc_log')) {
            qcc_log('Emergency reset completed', 'emergency');
        }
        
        return true;
    }
}

// =============================================================================
// EMERGENCY RESET CAPABILITY - SHORTENED
// =============================================================================

// Add emergency reset capability via URL parameter (admin only)
if (is_admin() && isset($_GET['qcc_emergency_reset']) && current_user_can('administrator')) {
    add_action('admin_init', function() {
        if ($_GET['qcc_emergency_reset'] === 'confirm' && wp_verify_nonce($_GET['_wpnonce'], 'qcc_emergency_reset')) {
            if (qcc_emergency_reset()) {
                wp_redirect(admin_url('plugins.php?qcc_reset=success'));
                exit;
            }
        }
    });
}

// Show reset success message
if (is_admin() && isset($_GET['qcc_reset']) && $_GET['qcc_reset'] === 'success') {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Quality Cost Calculator:</strong> Emergency reset completed successfully.</p>';
        echo '</div>';
    });
}

// Log that functions file has been loaded
if (function_exists('qcc_log')) {
    qcc_log('QCC Functions file loaded successfully - Shortcode conflicts resolved', 'functions');
}

// NOTE: SHORTCODE FUNCTIONALITY REMOVED FROM THIS FILE
// Shortcode is now handled exclusively by class-qcc-shortcode.php to avoid conflicts
<?php
/**
 * Helper functions for Quality Cost Calculator - ERWEITERTE VERSION
 * 
 * Integriert bestehende Funktionen mit den aus quality-cost-calculator.php ausgelagerten
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// BESTEHENDE FUNKTIONEN (von der ursprünglichen qcc-functions.php)
// =============================================================================

/**
 * Get plugin instance - CONFLICT PROTECTED VERSION
 */
if (!function_exists('qcc')) {
    function qcc() {
        if (class_exists('QualityCostCalculator')) {
            return QualityCostCalculator::get_instance();
        }
        // Bootstrap integration
        if (class_exists('QCC_Bootstrap')) {
            return QCC_Bootstrap::get_instance();
        }
        return false;
    }
}

/**
 * Get plugin option - ERWEITERT für Bootstrap-Kompatibilität
 */
if (!function_exists('qcc_get_option')) {
    function qcc_get_option($option_name, $default = '') {
        // Support both formats: qcc_get_option('language') and qcc_get_option('default_language')
        $option_map = array(
            'language' => 'qcc_default_language',
            'currency' => 'qcc_default_currency',
            'unit' => 'qcc_default_unit'
        );
        
        $full_option_name = isset($option_map[$option_name]) 
            ? $option_map[$option_name] 
            : 'qcc_' . $option_name;
            
        return get_option($full_option_name, $default);
    }
}

/**
 * Set plugin option - ERWEITERT für Bootstrap-Kompatibilität
 */
if (!function_exists('qcc_set_option')) {
    function qcc_set_option($option_name, $value) {
        // Support both formats
        $option_map = array(
            'language' => 'qcc_default_language',
            'currency' => 'qcc_default_currency',
            'unit' => 'qcc_default_unit'
        );
        
        $full_option_name = isset($option_map[$option_name]) 
            ? $option_map[$option_name] 
            : 'qcc_' . $option_name;
            
        return update_option($full_option_name, $value);
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
 * Log message for debugging - ERWEITERT mit mehr Levels
 */
if (!function_exists('qcc_log')) {
    function qcc_log($message, $level = 'info', $context = array()) {
        // Only log if debug mode is enabled
        if (!defined('QCC_DEBUG') || !QCC_DEBUG) {
            return;
        }
        
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log($message, strtoupper($level));
        } elseif (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $timestamp = date('Y-m-d H:i:s');
            $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
            error_log("QCC [{$timestamp}] [{$level}]: {$message}{$context_str}");
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
 * Calculate quality costs - ERWEITERT mit Enhanced Validation
 */
if (!function_exists('qcc_calculate_quality_costs')) {
    function qcc_calculate_quality_costs($revenue, $quality_percentage, $prevention, $appraisal, $internal, $external) {
        // Enhanced validation
        if ($revenue <= 0) {
            return new WP_Error('invalid_revenue', 'Revenue must be positive');
        }
        
        if (!qcc_validate_percentages($prevention, $appraisal, $internal, $external)) {
            return new WP_Error('invalid_percentages', 'Percentages must add up to 100%');
        }
        
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
 * Calculate COGQ and COPQ - ERWEITERT mit Ratios
 */
if (!function_exists('qcc_calculate_cogq_copq')) {
    function qcc_calculate_cogq_copq($costs) {
        if (is_wp_error($costs)) {
            return $costs;
        }
        
        $cogq = $costs['prevention_cost'] + $costs['appraisal_cost'];
        $copq = $costs['internal_defect_cost'] + $costs['external_defect_cost'];
        $total = $costs['total_quality_cost'];
        
        return array(
            'cogq' => $cogq,
            'copq' => $copq,
            'cogq_percentage' => $total > 0 ? ($cogq / $total) * 100 : 0,
            'copq_percentage' => $total > 0 ? ($copq / $total) * 100 : 0,
            'cogq_ratio' => $copq > 0 ? $cogq / $copq : 0,
            'quality_index' => $total > 0 ? ($cogq / $total) : 0
        );
    }
}

/**
 * Calculate opportunity costs - ERWEITERT mit Total
 */
if (!function_exists('qcc_calculate_opportunity_costs')) {
    function qcc_calculate_opportunity_costs($revenue, $lost_sales, $customer_churn, $market_share, $productivity) {
        return array(
            'lost_sales_cost' => ($revenue * $lost_sales) / 100,
            'customer_churn_cost' => ($revenue * $customer_churn) / 100,
            'market_share_cost' => ($revenue * $market_share) / 100,
            'productivity_cost' => ($revenue * $productivity) / 100,
            'total_opportunity_cost' => ($revenue * ($lost_sales + $customer_churn + $market_share + $productivity)) / 100
        );
    }
}

// =============================================================================
// NEUE FUNKTIONEN (aus quality-cost-calculator.php Bootstrap ausgelagert)
// =============================================================================

/**
 * Format currency value with unit (aus Bootstrap ausgelagert)
 */
if (!function_exists('qcc_format_currency_with_unit')) {
    function qcc_format_currency_with_unit($value, $currency = 'EUR', $unit = '1000000') {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'CNY' => '¥'
        );
        
        $symbol = $symbols[$currency] ?? $currency;
        $unit_label = $unit == '1000000' ? 'M' : 'B';
        $formatted = number_format($value / intval($unit), 2, '.', ',');
        
        return $formatted . ' ' . $symbol . ' ' . $unit_label;
    }
}

/**
 * Format currency amount - ERWEITERT
 */
if (!function_exists('qcc_format_currency')) {
    function qcc_format_currency($amount, $currency = 'EUR', $language = 'en', $unit = 1000000) {
        // Support both string and numeric unit values
        $unit_value = is_string($unit) ? intval($unit) : $unit;
        $formatted_amount = number_format($amount / $unit_value, 2);
        
        // Get currency symbol
        $symbol = qcc_get_currency_symbol($currency);
        
        // Language-specific formatting
        switch ($language) {
            case 'de':
                $formatted_amount = number_format($amount / $unit_value, 2, ',', '.');
                return $formatted_amount . ' ' . $symbol;
            case 'fr':
                $formatted_amount = number_format($amount / $unit_value, 2, ',', ' ');
                return $formatted_amount . ' ' . $symbol;
            case 'zh':
                return $symbol . ' ' . number_format($amount / $unit_value, 2);
            default:
                return $symbol . ' ' . number_format($amount / $unit_value, 2);
        }
    }
}

/**
 * Get currency symbol - NEU
 */
if (!function_exists('qcc_get_currency_symbol')) {
    function qcc_get_currency_symbol($currency) {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'CNY' => '¥',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'Fr',
            'CAD' => 'C$'
        );
        
        return $symbols[$currency] ?? $currency;
    }
}

/**
 * Get unit name in different languages - ERWEITERT
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
 * Get unit label - NEU (aus Bootstrap)
 */
if (!function_exists('qcc_get_unit_label')) {
    function qcc_get_unit_label($unit, $language = 'en') {
        $labels = array(
            'en' => array('1000000' => 'M', '1000000000' => 'B'),
            'de' => array('1000000' => 'Mio', '1000000000' => 'Mrd'),
            'fr' => array('1000000' => 'M', '1000000000' => 'Md'),
            'zh' => array('1000000' => '百万', '1000000000' => '十亿')
        );
        
        return $labels[$language][$unit] ?? ($unit == '1000000' ? 'M' : 'B');
    }
}

/**
 * Sanitize calculator input - ERWEITERT
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
                $allowed = array('en', 'de', 'fr', 'es', 'zh');
                return in_array($input, $allowed) ? $input : 'en';
            case 'currency':
                $allowed = array('EUR', 'USD', 'CNY', 'GBP', 'JPY');
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
 * Sanitize all calculator inputs - NEU (aus Bootstrap)
 */
if (!function_exists('qcc_sanitize_calculator_input')) {
    function qcc_sanitize_calculator_input($input) {
        return array(
            'revenue' => max(0, floatval($input['revenue'] ?? 140)),
            'quality_percentage' => max(0, min(100, floatval($input['quality_percentage'] ?? 6))),
            'prevention' => max(0, min(100, floatval($input['prevention'] ?? 10))),
            'appraisal' => max(0, min(100, floatval($input['appraisal'] ?? 20))),
            'internal_defect' => max(0, min(100, floatval($input['internal_defect'] ?? 30))),
            'external_defect' => max(0, min(100, floatval($input['external_defect'] ?? 40))),
            'language' => qcc_sanitize_input($input['language'] ?? 'en', 'language'),
            'currency' => qcc_sanitize_input($input['currency'] ?? 'EUR', 'currency'),
            'unit' => qcc_sanitize_input($input['unit'] ?? '1000000', 'unit')
        );
    }
}

/**
 * Sanitize language code - NEU
 */
if (!function_exists('qcc_sanitize_language')) {
    function qcc_sanitize_language($language) {
        $allowed = array('en', 'de', 'fr', 'es', 'zh');
        return in_array($language, $allowed) ? $language : 'en';
    }
}

/**
 * Sanitize currency code - NEU
 */
if (!function_exists('qcc_sanitize_currency')) {
    function qcc_sanitize_currency($currency) {
        $allowed = array('EUR', 'USD', 'CNY', 'GBP', 'JPY');
        return in_array($currency, $allowed) ? $currency : 'EUR';
    }
}

/**
 * Sanitize unit value - NEU
 */
if (!function_exists('qcc_sanitize_unit')) {
    function qcc_sanitize_unit($unit) {
        $allowed = array('1000000', '1000000000');
        return in_array($unit, $allowed) ? $unit : '1000000';
    }
}

// =============================================================================
// SYSTEM & DEBUG FUNKTIONEN (erweitert)
// =============================================================================

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
 * Check required classes - NEU (aus Bootstrap)
 */
if (!function_exists('qcc_check_required_classes')) {
    function qcc_check_required_classes() {
        $required_classes = array(
            'QCC_Bootstrap',
            'QCC_Shortcode',
            'QCC_Calculator',
            'QCC_Validator',
            'QCC_Admin'
        );
        
        $status = array();
        foreach ($required_classes as $class) {
            $status[$class] = class_exists($class) ? 'Loaded' : 'Missing';
        }
        
        return $status;
    }
}

/**
 * Check file permissions - NEU (aus Bootstrap)
 */
if (!function_exists('qcc_check_file_permissions')) {
    function qcc_check_file_permissions() {
        $directories = array(
            'includes' => QCC_PLUGIN_PATH . 'includes',
            'templates' => QCC_PLUGIN_PATH . 'templates',
            'assets' => QCC_PLUGIN_PATH . 'assets'
        );
        
        $status = array();
        foreach ($directories as $name => $path) {
            $status[$name] = array(
                'exists' => is_dir($path),
                'readable' => is_readable($path),
                'writable' => is_writable($path)
            );
        }
        
        return $status;
    }
}

/**
 * Get plugin status information - ERWEITERT
 */
if (!function_exists('qcc_get_plugin_status')) {
    function qcc_get_plugin_status() {
        $status = array(
            'plugin_version' => defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : 'Unknown',
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_usage' => size_format(memory_get_usage(true)),
            'debug_mode' => defined('QCC_DEBUG') && QCC_DEBUG ? 'Enabled' : 'Disabled',
            'shortcode_exists' => shortcode_exists('quality_cost_calculator') ? 'Yes' : 'No',
            'required_classes' => qcc_check_required_classes(),
            'file_permissions' => qcc_check_file_permissions(),
            'bootstrap_loaded' => class_exists('QCC_Bootstrap') ? 'Yes' : 'No'
        );
        
        return $status;
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
 * Clear all plugin cache - NEU (aus Bootstrap)
 */
if (!function_exists('qcc_clear_all_cache')) {
    function qcc_clear_all_cache() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_qcc_%' 
             OR option_name LIKE '_transient_timeout_qcc_%'"
        );
        
        qcc_log('All cache cleared', 'cache');
    }
}

/**
 * Clean up plugin data on uninstall - ERWEITERT
 */
if (!function_exists('qcc_cleanup_plugin_data')) {
    function qcc_cleanup_plugin_data() {
        if (function_exists('qcc_log')) {
            qcc_log('Starting plugin data cleanup', 'cleanup');
        }
        
        // Remove options (erweiterte Liste)
        $options = array(
            'qcc_default_language',
            'qcc_default_currency',
            'qcc_default_unit',
            'qcc_version',
            'qcc_db_version',
            'qcc_activation_time',
            'qcc_feature_flags',
            'qcc_activated'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove transients
        qcc_clear_all_cache();
        
        // Remove assets directory (erweiterte Liste)
        $upload_dir = wp_upload_dir();
        $assets_dirs = array(
            $upload_dir['basedir'] . '/quality-cost-calculator-cache',
            $upload_dir['basedir'] . '/quality-cost-calculator-exports',
            $upload_dir['basedir'] . '/quality-cost-calculator-temp',
            $upload_dir['basedir'] . '/qcc-cache',
            $upload_dir['basedir'] . '/qcc-exports',
            $upload_dir['basedir'] . '/qcc-temp'
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
 * Get plugin health status - ERWEITERT
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
        
        // Check Bootstrap status
        if (!class_exists('QCC_Bootstrap')) {
            $status['overall'] = 'warning';
            $status['issues'][] = 'Bootstrap class not loaded';
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
            'debug_mode' => defined('QCC_DEBUG') && QCC_DEBUG ? 'Enabled' : 'Disabled',
            'shortcode_exists' => shortcode_exists('quality_cost_calculator') ? 'Yes' : 'No',
            'bootstrap_loaded' => class_exists('QCC_Bootstrap') ? 'Yes' : 'No'
        );
        
        return $status;
    }
}

/**
 * Emergency function to reset plugin settings - ERWEITERT
 */
if (!function_exists('qcc_emergency_reset')) {
    function qcc_emergency_reset() {
        if (!current_user_can('administrator')) {
            return false;
        }
        
        if (function_exists('qcc_log')) {
            qcc_log('Emergency reset initiated', 'emergency');
        }
        
        // Reset to default options (erweiterte Liste)
        $defaults = array(
            'default_language' => 'en',
            'default_currency' => 'EUR',
            'default_unit' => '1000000',
            'enable_caching' => true,
            'debug_mode' => false,
            'feature_flags' => array(
                'use_new_shortcode_architecture' => false,
                'use_service_container' => true,
                'enable_performance_monitoring' => false,
                'use_modular_rendering' => false,
                'enable_advanced_caching' => true,
                'use_new_calculation_engine' => false,
                'enable_auto_setup' => true
            )
        );
        
        foreach ($defaults as $option => $value) {
            qcc_set_option($option, $value);
        }
        
        // Clear all transients
        qcc_clear_all_cache();
        
        if (function_exists('qcc_log')) {
            qcc_log('Emergency reset completed', 'emergency');
        }
        
        return true;
    }
}

// =============================================================================
// EMERGENCY RESET CAPABILITY - BESTEHEND (unverändert)
// =============================================================================

// Add emergency reset capability via URL parameter (admin only)
if (is_admin() && isset($_GET['qcc_emergency_reset']) && current_user_can('administrator')) {
    add_action('admin_init', function() {
        if ($_GET['qcc_emergency_reset'] === 'confirm' && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'qcc_emergency_reset')) {
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

// Log that functions file has been loaded - ERWEITERT
if (function_exists('qcc_log')) {
    qcc_log('QCC Functions file loaded successfully - Enhanced with Bootstrap integration', 'functions');
}

// NOTE: SHORTCODE FUNCTIONALITY REMOVED FROM THIS FILE
// Shortcode is now handled exclusively by class-qcc-shortcode.php to avoid conflicts
<?php
/**
 * Plugin Name:       Quality Cost Calculator
 * Plugin URI:        https://github.com/zerodefectpizza/Quality-Cost-Calculator
 * Description:       Professional Quality Cost Calculator for COGQ, COPQ and ROI analysis. Calculate Cost of Good Quality (prevention, appraisal) and Cost of Poor Quality (internal/external defects) with multi-language support, real-time validation, and export functionality.
 * Version:           2.0.0
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * Author:            Martin Schneider
 * Author URI:        https://zerodefectpizza.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quality-cost-calculator
 * Domain Path:       /languages
 * Network:           false
 * Update URI:        false
 * 
 * @package QualityCostCalculator
 * @version 2.0.0
 * @author Martin Schneider
 * @copyright 2024 Martin Schneider
 * @license GPL-2.0-or-later
 * @link https://github.com/zerodefectpizza/Quality-Cost-Calculator
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// WordPress and PHP version check
if (!function_exists('add_action')) {
    exit('WordPress environment required.');
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    exit('PHP 7.4 or higher required.');
}

// =============================================================================
// PLUGIN CONSTANTS
// =============================================================================

define('QCC_PLUGIN_VERSION', '2.0.0');
define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('QCC_PLUGIN_FILE', __FILE__);
define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');
define('QCC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// =============================================================================
// BOOTSTRAP SYSTEM
// =============================================================================

/**
 * Load the autoloader and bootstrap system
 */
require_once QCC_PLUGIN_PATH . 'includes/qcc-autoloader.php';

/**
 * Initialize the plugin bootstrap
 */
function qcc_init_plugin() {
    try {
        // Initialize autoloader
        QCC_Autoloader::init();
        
        // Load bootstrap class
        if (class_exists('QCC_Bootstrap')) {
            QCC_Bootstrap::initialize();
        } else {
            // Fallback to legacy system
            qcc_init_legacy_system();
        }
        
    } catch (Exception $e) {
        if (QCC_DEBUG) {
            error_log('QCC Bootstrap Error: ' . $e->getMessage());
        }
        // Fallback to basic functionality
        qcc_emergency_fallback();
    }
}

/**
 * Legacy system fallback
 */
function qcc_init_legacy_system() {
    // Load essential functions
    require_once QCC_PLUGIN_PATH . 'includes/qcc-functions.php';
    
    // Load core classes if they exist
    $core_classes = array(
        'includes/class-qcc-shortcode.php',
        'includes/class-qcc-admin.php'
    );
    
    foreach ($core_classes as $class_file) {
        $file_path = QCC_PLUGIN_PATH . $class_file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    // Initialize shortcode
    if (class_exists('QCC_Shortcode')) {
        new QCC_Shortcode();
    }
    
    // Initialize admin
    if (is_admin() && class_exists('QCC_Admin')) {
        $admin = new QCC_Admin();
        $admin->init();
    }
}

/**
 * Emergency fallback system
 */
function qcc_emergency_fallback() {
    // Register basic shortcode
    add_shortcode('quality_cost_calculator', function($atts) {
        return '<div class="qcc-error">Quality Cost Calculator: System initialization failed. Please check plugin files.</div>';
    });
    
    // Admin notice
    if (is_admin()) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Quality Cost Calculator:</strong> Plugin initialization failed. Please check file permissions and PHP version.</p></div>';
        });
    }
}

// =============================================================================
// WORDPRESS HOOKS
// =============================================================================

// Plugin activation
register_activation_hook(__FILE__, function() {
    if (class_exists('QCC_Bootstrap')) {
        QCC_Bootstrap::activate();
    }
});

// Plugin deactivation
register_deactivation_hook(__FILE__, function() {
    if (class_exists('QCC_Bootstrap')) {
        QCC_Bootstrap::deactivate();
    }
});

// Plugin uninstall handler required by WordPress since the callback is
// serialized when registered. Anonymous functions (closures) cannot be
// serialized and would cause a fatal error. Using a named function ensures
// proper cleanup during plugin uninstallation.
function qcc_plugin_uninstall() {
    if (class_exists('QCC_Bootstrap')) {
        QCC_Bootstrap::uninstall();
    }
}
register_uninstall_hook(__FILE__, 'qcc_plugin_uninstall');

// Initialize plugin on plugins_loaded
add_action('plugins_loaded', 'qcc_init_plugin', 10);

// Plugin action links
add_filter('plugin_action_links_' . QCC_PLUGIN_BASENAME, function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=quality-cost-calculator') . '">' . 
                     __('Settings', QCC_TEXT_DOMAIN) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Plugin meta links
add_filter('plugin_row_meta', function($links, $file) {
    if ($file === QCC_PLUGIN_BASENAME) {
        $meta_links = array(
            '<a href="' . admin_url('admin.php?page=quality-cost-calculator') . '">' . 
            __('Documentation', QCC_TEXT_DOMAIN) . '</a>',
            '<a href="https://github.com/zerodefectpizza/Quality-Cost-Calculator" target="_blank">' . 
            __('GitHub', QCC_TEXT_DOMAIN) . '</a>'
        );
        $links = array_merge($links, $meta_links);
    }
    return $links;
}, 10, 2);

// Plugin loaded successfully
if (QCC_DEBUG) {
    error_log('QCC: Main plugin file loaded successfully - Version ' . QCC_PLUGIN_VERSION);
}
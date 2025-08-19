<?php
/**
 * Plugin Name:       Quality Cost Calculator
 * Plugin URI:        https://github.com/zerodefectpizza/Quality-Cost-Calculator
 * Description:       Professional Quality Cost Calculator for COGQ, COPQ and ROI analysis. Calculate Cost of Good Quality (prevention, appraisal) and Cost of Poor Quality (internal/external defects) with multi-language support, real-time validation, and export functionality.
 * Version:           2.0.2
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
 * @version 2.0.2
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

define('QCC_PLUGIN_VERSION', '2.0.2');
define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('QCC_PLUGIN_FILE', __FILE__);
define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');
define('QCC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// =============================================================================
// BOOTSTRAP SYSTEM - FIXED
// =============================================================================

/**
 * Load the autoloader safely
 */
function qcc_load_autoloader() {
    $autoloader_path = QCC_PLUGIN_PATH . 'includes/qcc-autoloader.php';
    
    if (file_exists($autoloader_path)) {
        require_once $autoloader_path;
        
        if (class_exists('QCC_Autoloader')) {
            QCC_Autoloader::register();
            return true;
        }
    }
    
    return false;
}

/**
 * Initialize the plugin bootstrap - FIXED METHOD CALLS
 */
function qcc_init_plugin() {
    try {
        // Load autoloader first
        if (!qcc_load_autoloader()) {
            throw new Exception('Autoloader could not be loaded');
        }
        
        // Try to initialize bootstrap - FIXED: Use proper public methods
        if (class_exists('QCC_Bootstrap')) {
            // Check for public static method first
            if (method_exists('QCC_Bootstrap', 'get_instance')) {
                $bootstrap = QCC_Bootstrap::get_instance();
                if (method_exists($bootstrap, 'initialize')) {
                    $bootstrap->initialize();
                } elseif (method_exists($bootstrap, 'init')) {
                    $bootstrap->init();
                }
            } elseif (method_exists('QCC_Bootstrap', 'initialize')) {
                QCC_Bootstrap::initialize();
            } else {
                // Fallback to creating instance
                new QCC_Bootstrap();
            }
        } else {
            // Fallback to legacy system
            qcc_init_legacy_system();
        }
        
        if (QCC_DEBUG) {
            error_log('QCC: Plugin initialized successfully');
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
    if (QCC_DEBUG) {
        error_log('QCC: Initializing legacy system');
    }
    
    // Load essential functions
    $functions_path = QCC_PLUGIN_PATH . 'includes/qcc-functions.php';
    if (file_exists($functions_path)) {
        require_once $functions_path;
    }
    
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
        if (method_exists($admin, 'init')) {
            $admin->init();
        }
    }
}

/**
 * Emergency fallback system - BULLETPROOF
 */
function qcc_emergency_fallback() {
    if (QCC_DEBUG) {
        error_log('QCC: Activating emergency fallback');
    }
    
    // Register basic shortcode
    add_shortcode('quality_cost_calculator', 'qcc_emergency_shortcode');
    
    // Admin notice for developers
    if (is_admin() && QCC_DEBUG) {
        add_action('admin_notices', 'qcc_emergency_admin_notice');
    }
}

/**
 * Emergency shortcode - SELF-CONTAINED
 */
function qcc_emergency_shortcode($atts) {
    // Sanitize attributes
    $atts = shortcode_atts(array(
        'language' => 'en',
        'currency' => 'EUR',
        'unit' => '1000000'
    ), $atts, 'quality_cost_calculator');
    
    // Generate unique ID
    $calc_id = 'qcc-calc-' . uniqid();
    
    // Return inline calculator
    return '
    <div id="' . esc_attr($calc_id) . '" class="qcc-emergency-calculator">
        <style>
            .qcc-emergency-calculator {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-family: Arial, sans-serif;
                background: #f9f9f9;
            }
            .qcc-emergency-calculator h3 {
                color: #333;
                margin-top: 0;
            }
            .qcc-form-group {
                margin-bottom: 15px;
            }
            .qcc-form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #555;
            }
            .qcc-form-group input {
                width: 100%;
                padding: 8px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
            }
            .qcc-button {
                background: #0073aa;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }
            .qcc-button:hover {
                background: #005a87;
            }
            .qcc-results {
                margin-top: 20px;
                padding: 15px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                display: none;
            }
            .qcc-result-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .qcc-error {
                color: red;
                font-weight: bold;
                margin-top: 10px;
                display: none;
            }
        </style>
        
        <h3>Quality Cost Calculator</h3>
        <p>Professional COGQ/COPQ Analysis Tool</p>
        
        <form>
            <div class="qcc-form-group">
                <label>Revenue (' . esc_html($atts['currency']) . '):</label>
                <input type="number" id="revenue-' . esc_attr($calc_id) . '" value="1000000" min="0" step="1000">
            </div>
            
            <div class="qcc-form-group">
                <label>Quality Cost Percentage (% of Revenue):</label>
                <input type="number" id="quality-percentage-' . esc_attr($calc_id) . '" value="6" min="0" max="100" step="0.1">
            </div>
            
            <div class="qcc-form-group">
                <label>Prevention Costs (%):</label>
                <input type="number" id="prevention-' . esc_attr($calc_id) . '" value="10" min="0" max="100" step="0.1">
            </div>
            
            <div class="qcc-form-group">
                <label>Appraisal Costs (%):</label>
                <input type="number" id="appraisal-' . esc_attr($calc_id) . '" value="20" min="0" max="100" step="0.1">
            </div>
            
            <div class="qcc-form-group">
                <label>Internal Defect Costs (%):</label>
                <input type="number" id="internal-defect-' . esc_attr($calc_id) . '" value="30" min="0" max="100" step="0.1">
            </div>
            
            <div class="qcc-form-group">
                <label>External Defect Costs (%):</label>
                <input type="number" id="external-defect-' . esc_attr($calc_id) . '" value="40" min="0" max="100" step="0.1">
            </div>
            
            <button type="button" class="qcc-button" onclick="qccCalc' . esc_js($calc_id) . '()">
                Calculate Quality Costs
            </button>
            
            <div id="error-' . esc_attr($calc_id) . '" class="qcc-error"></div>
        </form>
        
        <div id="results-' . esc_attr($calc_id) . '" class="qcc-results">
            <h4>Quality Cost Analysis Results:</h4>
            <div class="qcc-result-item">
                <span><strong>Total Quality Cost:</strong></span>
                <span id="total-quality-cost-' . esc_attr($calc_id) . '">-</span>
            </div>
            <div class="qcc-result-item">
                <span><strong>Cost of Good Quality (COGQ):</strong></span>
                <span id="cogq-' . esc_attr($calc_id) . '">-</span>
            </div>
            <div class="qcc-result-item">
                <span><strong>Cost of Poor Quality (COPQ):</strong></span>
                <span id="copq-' . esc_attr($calc_id) . '">-</span>
            </div>
            <div class="qcc-result-item">
                <span>Prevention Cost:</span>
                <span id="prevention-cost-' . esc_attr($calc_id) . '">-</span>
            </div>
            <div class="qcc-result-item">
                <span>Appraisal Cost:</span>
                <span id="appraisal-cost-' . esc_attr($calc_id) . '">-</span>
            </div>
            <div class="qcc-result-item">
                <span>Internal Defect Cost:</span>
                <span id="internal-defect-cost-' . esc_attr($calc_id) . '">-</span>
            </div>
            <div class="qcc-result-item">
                <span>External Defect Cost:</span>
                <span id="external-defect-cost-' . esc_attr($calc_id) . '">-</span>
            </div>
        </div>
    </div>
    
    <script>
    function qccCalc' . esc_js($calc_id) . '() {
        try {
            // Get values
            var revenue = parseFloat(document.getElementById("revenue-' . esc_js($calc_id) . '").value) || 0;
            var qualityPercentage = parseFloat(document.getElementById("quality-percentage-' . esc_js($calc_id) . '").value) || 0;
            var prevention = parseFloat(document.getElementById("prevention-' . esc_js($calc_id) . '").value) || 0;
            var appraisal = parseFloat(document.getElementById("appraisal-' . esc_js($calc_id) . '").value) || 0;
            var internalDefect = parseFloat(document.getElementById("internal-defect-' . esc_js($calc_id) . '").value) || 0;
            var externalDefect = parseFloat(document.getElementById("external-defect-' . esc_js($calc_id) . '").value) || 0;
            
            // Validate percentages
            var total = prevention + appraisal + internalDefect + externalDefect;
            var errorEl = document.getElementById("error-' . esc_js($calc_id) . '");
            
            if (Math.abs(total - 100) > 0.01) {
                errorEl.innerHTML = "Error: Percentages must sum to 100% (currently: " + total.toFixed(1) + "%)";
                errorEl.style.display = "block";
                return;
            } else {
                errorEl.style.display = "none";
            }
            
            // Calculate
            var unit = ' . (int)$atts['unit'] . ';
            var revenueInUnit = revenue * unit;
            var totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
            
            var preventionCost = (totalQualityCost * prevention) / 100;
            var appraisalCost = (totalQualityCost * appraisal) / 100;
            var internalDefectCost = (totalQualityCost * internalDefect) / 100;
            var externalDefectCost = (totalQualityCost * externalDefect) / 100;
            
            var cogq = preventionCost + appraisalCost;
            var copq = internalDefectCost + externalDefectCost;
            
            // Format currency
            function formatCurrency(value) {
                var scaled = value / unit;
                return scaled.toFixed(2) + " ' . esc_js($atts['currency']) . ' " + (unit == 1000000 ? "M" : "B");
            }
            
            // Display results
            document.getElementById("total-quality-cost-' . esc_js($calc_id) . '").textContent = formatCurrency(totalQualityCost);
            document.getElementById("cogq-' . esc_js($calc_id) . '").textContent = formatCurrency(cogq);
            document.getElementById("copq-' . esc_js($calc_id) . '").textContent = formatCurrency(copq);
            document.getElementById("prevention-cost-' . esc_js($calc_id) . '").textContent = formatCurrency(preventionCost);
            document.getElementById("appraisal-cost-' . esc_js($calc_id) . '").textContent = formatCurrency(appraisalCost);
            document.getElementById("internal-defect-cost-' . esc_js($calc_id) . '").textContent = formatCurrency(internalDefectCost);
            document.getElementById("external-defect-cost-' . esc_js($calc_id) . '").textContent = formatCurrency(externalDefectCost);
            
            document.getElementById("results-' . esc_js($calc_id) . '").style.display = "block";
            
        } catch (error) {
            document.getElementById("error-' . esc_js($calc_id) . '").innerHTML = "Calculation error: " + error.message;
            document.getElementById("error-' . esc_js($calc_id) . '").style.display = "block";
        }
    }
    </script>';
}

/**
 * Emergency admin notice
 */
function qcc_emergency_admin_notice() {
    echo '<div class="notice notice-warning"><p><strong>QCC:</strong> Running in emergency fallback mode. Some advanced features may be limited.</p></div>';
}

// =============================================================================
// ACTIVATION/DEACTIVATION HOOKS - SAFE
// =============================================================================

/**
 * Plugin activation handler - SAFE
 */
function qcc_activate_plugin() {
    try {
        update_option('qcc_activated', true);
        update_option('qcc_activation_time', current_time('mysql'));
        update_option('qcc_version', QCC_PLUGIN_VERSION);
        
        // Try to run bootstrap activation if available
        if (class_exists('QCC_Bootstrap')) {
            if (method_exists('QCC_Bootstrap', 'activate')) {
                QCC_Bootstrap::activate();
            }
        }
        
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC: Plugin activated successfully');
        }
        
    } catch (Exception $e) {
        if (QCC_DEBUG) {
            error_log('QCC: Activation error - ' . $e->getMessage());
        }
        // Don't fail activation, just log the error
    }
}

/**
 * Plugin deactivation handler - SAFE
 */
function qcc_deactivate_plugin() {
    try {
        // Try to run bootstrap deactivation if available
        if (class_exists('QCC_Bootstrap')) {
            if (method_exists('QCC_Bootstrap', 'deactivate')) {
                QCC_Bootstrap::deactivate();
            }
        }
        
        delete_transient('qcc_system_check');
        flush_rewrite_rules();
        
        if (QCC_DEBUG) {
            error_log('QCC: Plugin deactivated successfully');
        }
        
    } catch (Exception $e) {
        if (QCC_DEBUG) {
            error_log('QCC: Deactivation error - ' . $e->getMessage());
        }
    }
}

/**
 * Plugin uninstall handler - SAFE
 */
function qcc_uninstall_plugin() {
    try {
        // Try to run bootstrap uninstall if available
        if (class_exists('QCC_Bootstrap')) {
            if (method_exists('QCC_Bootstrap', 'uninstall')) {
                QCC_Bootstrap::uninstall();
            }
        }
        
        // Remove options
        $options_to_remove = array(
            'qcc_activated',
            'qcc_activation_time', 
            'qcc_version',
            'qcc_default_language',
            'qcc_default_currency',
            'qcc_default_unit'
        );
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
        }
        
        // Remove transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_qcc_%'");
        
        if (QCC_DEBUG) {
            error_log('QCC: Plugin uninstalled successfully');
        }
        
    } catch (Exception $e) {
        if (QCC_DEBUG) {
            error_log('QCC: Uninstall error - ' . $e->getMessage());
        }
    }
}

// =============================================================================
// WORDPRESS HOOKS REGISTRATION
// =============================================================================

register_activation_hook(__FILE__, 'qcc_activate_plugin');
register_deactivation_hook(__FILE__, 'qcc_deactivate_plugin');
register_uninstall_hook(__FILE__, 'qcc_uninstall_plugin');

// Initialize plugin on plugins_loaded
add_action('plugins_loaded', 'qcc_init_plugin', 10);

// Plugin action links
add_filter('plugin_action_links_' . QCC_PLUGIN_BASENAME, 'qcc_add_plugin_action_links');

/**
 * Add plugin action links
 */
function qcc_add_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=quality-cost-calculator') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Plugin meta links
add_filter('plugin_row_meta', 'qcc_add_plugin_meta_links', 10, 2);

/**
 * Add plugin meta links
 */
function qcc_add_plugin_meta_links($links, $file) {
    if ($file === QCC_PLUGIN_BASENAME) {
        $meta_links = array(
            '<a href="https://github.com/zerodefectpizza/Quality-Cost-Calculator" target="_blank">GitHub</a>'
        );
        $links = array_merge($links, $meta_links);
    }
    return $links;
}

// Plugin loaded successfully
if (QCC_DEBUG) {
    error_log('QCC: Main plugin file loaded successfully - Version ' . QCC_PLUGIN_VERSION);
}
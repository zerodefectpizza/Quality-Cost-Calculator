<?php
/**
 * Plugin Name: Quality Cost Calculator
 * Plugin URI: https://github.com/your-repo/quality-cost-calculator
 * Description: Professional COGQ/COPQ calculator with modern modular architecture and multi-language support
 * Version: 2.0.0
 * Author: Your Team
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quality-cost-calculator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent multiple inclusions
if (defined('QCC_PLUGIN_LOADED')) {
    return;
}

/**
 * Plugin Constants
 */
define('QCC_PLUGIN_LOADED', true);
define('QCC_PLUGIN_VERSION', '2.0.0');
define('QCC_PLUGIN_FILE', __FILE__);
define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('QCC_PLUGIN_DIR', dirname(QCC_PLUGIN_BASENAME));
define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');

/**
 * System Requirements Check
 * 
 * @return bool True if requirements met, false otherwise
 */
function qcc_check_system_requirements() {
    $requirements_met = true;
    $errors = array();
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = sprintf(
            __('Quality Cost Calculator requires PHP 7.4 or higher. Current version: %s', 'quality-cost-calculator'),
            PHP_VERSION
        );
        $requirements_met = false;
    }
    
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        $errors[] = sprintf(
            __('Quality Cost Calculator requires WordPress 5.0 or higher. Current version: %s', 'quality-cost-calculator'),
            get_bloginfo('version')
        );
        $requirements_met = false;
    }
    
    // Check required PHP extensions
    $required_extensions = array('json', 'mbstring');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                __('Quality Cost Calculator requires PHP %s extension.', 'quality-cost-calculator'),
                $extension
            );
            $requirements_met = false;
        }
    }
    
    // Display errors if requirements not met
    if (!$requirements_met) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . __('Quality Cost Calculator Error:', 'quality-cost-calculator') . '</strong><br>';
            echo implode('<br>', $errors);
            echo '</p></div>';
        });
        
        // Deactivate plugin if activated
        add_action('admin_init', function() {
            if (is_plugin_active(plugin_basename(__FILE__))) {
                deactivate_plugins(plugin_basename(__FILE__));
                if (isset($_GET['activate'])) {
                    unset($_GET['activate']);
                }
            }
        });
    }
    
    return $requirements_met;
}

/**
 * Load Plugin Architecture
 */
function qcc_load_plugin_architecture() {
    // Load core configuration
    $config_file = QCC_PLUGIN_PATH . 'includes/qcc-config.php';
    if (file_exists($config_file)) {
        require_once $config_file;
    } else {
        error_log('QCC: Critical file missing - qcc-config.php');
        return false;
    }
    
    // Load autoloader
    $autoloader_file = QCC_PLUGIN_PATH . 'includes/qcc-autoloader.php';
    if (file_exists($autoloader_file)) {
        require_once $autoloader_file;
        
        // Initialize autoloader
        if (class_exists('QCC_Autoloader')) {
            QCC_Autoloader::register();
        }
    } else {
        error_log('QCC: Critical file missing - qcc-autoloader.php');
        return false;
    }
    
    // Load bootstrap class
    $bootstrap_file = QCC_PLUGIN_PATH . 'includes/core/class-qcc-bootstrap.php';
    if (file_exists($bootstrap_file)) {
        require_once $bootstrap_file;
    } else {
        // Fallback to old architecture if new bootstrap doesn't exist
        $legacy_file = QCC_PLUGIN_PATH . 'includes/legacy/class-qcc-legacy-bootstrap.php';
        if (file_exists($legacy_file)) {
            require_once $legacy_file;
        } else {
            error_log('QCC: No bootstrap class found - neither new nor legacy');
            return false;
        }
    }
    
    return true;
}

/**
 * Initialize Plugin
 */
function qcc_initialize_plugin() {
    // Check system requirements first
    if (!qcc_check_system_requirements()) {
        return false;
    }
    
    // Load plugin architecture
    if (!qcc_load_plugin_architecture()) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo __('Quality Cost Calculator failed to load core files. Please check file permissions.', 'quality-cost-calculator');
            echo '</p></div>';
        });
        return false;
    }
    
    // Initialize bootstrap
    try {
        if (class_exists('QCC_Bootstrap')) {
            QCC_Bootstrap::initialize();
        } elseif (class_exists('QCC_Legacy_Bootstrap')) {
            // Fallback to legacy bootstrap
            QCC_Legacy_Bootstrap::initialize();
        } else {
            throw new Exception('No bootstrap class available');
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('QCC: Bootstrap initialization failed - ' . $e->getMessage());
        
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(
                __('Quality Cost Calculator initialization failed: %s', 'quality-cost-calculator'),
                $e->getMessage()
            );
            echo '</p></div>';
        });
        
        return false;
    }
}

/**
 * Plugin Activation Handler
 */
function qcc_activate_plugin() {
    // Log activation
    error_log('QCC: Plugin activation started');
    
    try {
        // Check requirements during activation
        if (!qcc_check_system_requirements()) {
            throw new Exception('System requirements not met');
        }
        
        // Load necessary classes for activation
        if (!qcc_load_plugin_architecture()) {
            throw new Exception('Failed to load plugin architecture');
        }
        
        // Run activation procedures
        if (class_exists('QCC_Bootstrap')) {
            QCC_Bootstrap::activate();
        } elseif (class_exists('QCC_Legacy_Bootstrap')) {
            QCC_Legacy_Bootstrap::activate();
        }
        
        // Set activation flag
        update_option('qcc_activated', true);
        update_option('qcc_activation_time', current_time('timestamp'));
        update_option('qcc_version', QCC_PLUGIN_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('QCC: Plugin activation completed successfully');
        
    } catch (Exception $e) {
        error_log('QCC: Plugin activation failed - ' . $e->getMessage());
        
        // Deactivate plugin on activation failure
        deactivate_plugins(plugin_basename(__FILE__));
        
        wp_die(
            sprintf(
                __('Quality Cost Calculator activation failed: %s', 'quality-cost-calculator'),
                $e->getMessage()
            ),
            __('Plugin Activation Error', 'quality-cost-calculator'),
            array('back_link' => true)
        );
    }
}

/**
 * Plugin Deactivation Handler
 */
function qcc_deactivate_plugin() {
    error_log('QCC: Plugin deactivation started');
    
    try {
        // Load necessary classes for deactivation
        qcc_load_plugin_architecture();
        
        // Run deactivation procedures
        if (class_exists('QCC_Bootstrap')) {
            QCC_Bootstrap::deactivate();
        } elseif (class_exists('QCC_Legacy_Bootstrap')) {
            QCC_Legacy_Bootstrap::deactivate();
        }
        
        // Clean up temporary data
        delete_transient('qcc_system_check');
        delete_transient('qcc_performance_data');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        error_log('QCC: Plugin deactivation completed successfully');
        
    } catch (Exception $e) {
        error_log('QCC: Plugin deactivation failed - ' . $e->getMessage());
    }
}

/**
 * Plugin Uninstall Handler
 */
function qcc_uninstall_plugin() {
    error_log('QCC: Plugin uninstall started');
    
    try {
        // Load necessary classes for uninstall
        qcc_load_plugin_architecture();
        
        // Run uninstall procedures
        if (class_exists('QCC_Bootstrap')) {
            QCC_Bootstrap::uninstall();
        } elseif (class_exists('QCC_Legacy_Bootstrap')) {
            QCC_Legacy_Bootstrap::uninstall();
        }
        
        // Remove all plugin options
        $options_to_remove = array(
            'qcc_activated',
            'qcc_activation_time', 
            'qcc_version',
            'qcc_default_language',
            'qcc_default_currency',
            'qcc_default_unit',
            'qcc_settings'
        );
        
        foreach ($options_to_remove as $option) {
            delete_option($option);
        }
        
        // Remove all transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_qcc_%' 
            OR option_name LIKE '_transient_timeout_qcc_%'"
        );
        
        error_log('QCC: Plugin uninstall completed successfully');
        
    } catch (Exception $e) {
        error_log('QCC: Plugin uninstall failed - ' . $e->getMessage());
    }
}

/**
 * Global Plugin Instance Getter
 * 
 * @return mixed Plugin instance or false if not available
 */
function qcc() {
    if (class_exists('QCC_Bootstrap')) {
        return QCC_Bootstrap::get_instance();
    } elseif (class_exists('QCC_Legacy_Bootstrap')) {
        return QCC_Legacy_Bootstrap::get_instance();
    }
    
    return false;
}

/**
 * Emergency Recovery Function
 * Can be called via URL parameter for debugging
 */
function qcc_emergency_recovery() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['qcc_recovery']) && $_GET['qcc_recovery'] === 'reset') {
        if (wp_verify_nonce($_GET['_wpnonce'], 'qcc_recovery')) {
            // Reset to safe defaults
            delete_option('qcc_use_new_architecture');
            delete_transient('qcc_system_check');
            
            wp_redirect(admin_url('plugins.php?qcc_recovery=success'));
            exit;
        }
    }
    
    if (isset($_GET['qcc_recovery']) && $_GET['qcc_recovery'] === 'success') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Quality Cost Calculator has been reset to safe defaults.', 'quality-cost-calculator') . '</p>';
            echo '</div>';
        });
    }
}

// Hook registration
register_activation_hook(__FILE__, 'qcc_activate_plugin');
register_deactivation_hook(__FILE__, 'qcc_deactivate_plugin');
register_uninstall_hook(__FILE__, 'qcc_uninstall_plugin');

// Emergency recovery system
add_action('admin_init', 'qcc_emergency_recovery');

// Initialize plugin on plugins_loaded
add_action('plugins_loaded', 'qcc_initialize_plugin', 10);

// Add plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=quality-cost-calculator') . '">' . 
                     __('Settings', 'quality-cost-calculator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Add plugin meta links
add_filter('plugin_row_meta', function($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://github.com/your-repo/quality-cost-calculator/wiki">' . 
                   __('Documentation', 'quality-cost-calculator') . '</a>';
        $links[] = '<a href="https://github.com/your-repo/quality-cost-calculator/issues">' . 
                   __('Support', 'quality-cost-calculator') . '</a>';
    }
    return $links;
}, 10, 2);

// Plugin loaded successfully
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('QCC: Main plugin file loaded successfully - Version ' . QCC_PLUGIN_VERSION);
}
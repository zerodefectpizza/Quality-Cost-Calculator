<?php
/**
 * Admin functionality for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class QCC_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_head', array($this, 'admin_styles'));
        add_filter('plugin_action_links_' . QCC_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Quality Cost Calculator Settings',
            'Quality Cost Calculator',
            'manage_options',
            'quality-cost-calculator',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function admin_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['qcc_settings_nonce'], 'qcc_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $current_settings = $this->get_current_settings();
        
        // Include admin template
        include QCC_PLUGIN_PATH . 'templates/admin-page.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $default_language = sanitize_text_field($_POST['default_language']);
        $default_currency = sanitize_text_field($_POST['default_currency']);
        $default_unit = sanitize_text_field($_POST['default_unit']);
        
        update_option('qcc_default_language', $default_language);
        update_option('qcc_default_currency', $default_currency);
        update_option('qcc_default_unit', $default_unit);
    }
    
    /**
     * Get current settings
     */
    private function get_current_settings() {
        return array(
            'language' => get_option('qcc_default_language', 'en'),
            'currency' => get_option('qcc_default_currency', '€'),
            'unit' => get_option('qcc_default_unit', '1000000')
        );
    }
    
    /**
     * Add custom CSS for admin pages
     */
    public function admin_styles() {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'quality-cost-calculator') !== false) {
            ?>
            <style type="text/css">
            .qcc-admin-header {
                background: linear-gradient(135deg, #449775, #141C14);
                color: white;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
            }
            .qcc-admin-header h1 {
                color: white;
                margin: 0;
                font-size: 2rem;
            }
            .qcc-admin-header p {
                margin: 5px 0 0 0;
                opacity: 0.9;
            }
            .qcc-status-good {
                color: #46b450;
                font-weight: bold;
            }
            .qcc-status-bad {
                color: #dc3232;
                font-weight: bold;
            }
            .card {
                margin-bottom: 20px;
            }
            .card h2 {
                border-bottom: 1px solid #ccd0d4;
                padding-bottom: 10px;
            }
            .card h3 {
                color: #449775;
                margin-top: 20px;
            }
            .card h4 {
                color: #141C14;
                margin-top: 15px;
            }
            .card h5 {
                color: #353535;
                margin-top: 10px;
            }
            .card code {
                background: #f1f1f1;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: Consolas, Monaco, monospace;
            }
            .card ul {
                margin-left: 20px;
            }
            .card li {
                margin-bottom: 5px;
            }
            </style>
            <?php
        }
    }
    
    /**
     * Add plugin action links
     */
    public function plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=quality-cost-calculator') . '">Settings</a>';
        $docs_link = '<a href="' . admin_url('options-general.php?page=quality-cost-calculator') . '#documentation">Documentation</a>';
        
        array_unshift($links, $settings_link);
        array_unshift($links, $docs_link);
        
        return $links;
    }
    
    /**
     * Add plugin meta links
     */
    public function plugin_row_meta($links, $file) {
        if (QCC_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="' . admin_url('options-general.php?page=quality-cost-calculator') . '">Documentation</a>',
                'support' => '<a href="#" target="_blank">Support</a>',
                'github' => '<a href="#" target="_blank">GitHub</a>'
            );
            
            return array_merge($links, $row_meta);
        }
        
        return $links;
    }
    
    /**
     * Get system info for debugging
     */
    public function get_system_info() {
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => QCC_PLUGIN_VERSION,
            'current_language' => get_option('qcc_default_language', 'en'),
            'active_theme' => wp_get_theme()->get('Name'),
            'wp_locale' => get_locale()
        );
    }
}
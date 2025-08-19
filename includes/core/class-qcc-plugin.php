<?php
/**
 * Haupt-Plugin-Klasse - Entry Point
 */
class QCC_Plugin {
    private static $instance = null;
    private $version = '2.0.0';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }
    
    private function define_constants() {
        if (!defined('QCC_VERSION')) {
            define('QCC_VERSION', $this->version);
        }
        if (!defined('QCC_PLUGIN_PATH')) {
            define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
        }
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        QCC_Bootstrap::initialize();
    }
}
// includes/legacy/class-qcc-legacy-bootstrap.php
<?php
class QCC_Legacy_Bootstrap {
    public static function initialize() {
        // Lädt bestehende Implementierung
        if (!class_exists('QCC_Shortcode_Legacy')) {
            require_once QCC_PLUGIN_PATH . 'includes/legacy/class-qcc-shortcode-legacy.php';
        }
        
        // Initialisiert Legacy-System
        if (class_exists('QCC_Core')) {
            return QCC_Core::get_instance();
        }
        
        return true;
    }
    
    public static function activate() {
        // Legacy activation
    }
    
    public static function deactivate() {
        // Legacy deactivation  
    }
    
    public static function uninstall() {
        // Legacy uninstall
    }
    
    public static function get_instance() {
        return self::initialize();
    }
}
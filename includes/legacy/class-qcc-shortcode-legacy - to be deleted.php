// includes/legacy/class-qcc-legacy-bootstrap.php
<?php
class QCC_Legacy_Bootstrap {
    public static function initialize() {
        // Lädt die bestehende/alte Implementierung
        require_once QCC_PLUGIN_PATH . 'includes/legacy/class-qcc-shortcode-legacy.php';
        // Initialisiert die alte Shortcode-Klasse
        return true;
    }
    
    public static function activate() { /* Legacy activation */ }
    public static function deactivate() { /* Legacy deactivation */ }
    public static function uninstall() { /* Legacy uninstall */ }
}
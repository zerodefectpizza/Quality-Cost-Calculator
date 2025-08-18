<?php
/**
 * QCC Configurable Interface
 * 
 * Interface für konfigurierbare Components und Services.
 * Ermöglicht einheitliche Konfiguration aller System-Komponenten.
 * 
 * @package QualityCostCalculator
 * @subpackage Infrastructure/Interfaces
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Configurable Interface
 * 
 * Definiert Contract für konfigurierbare Components im QCC System.
 * Ermöglicht einheitliche Konfiguration, Validation und Schema-Management.
 * 
 * Implementiert von:
 * - Calculation Services (Algorithmus-Parameter)
 * - Rendering Components (Display-Optionen)
 * - Translation Services (Language-Settings)
 * - Asset Managers (Build-Konfiguration)
 */
interface QCC_Configurable {
    
    /**
     * Setzt Konfiguration für Component/Service
     * 
     * @param array $config Configuration array
     * @return bool True wenn Konfiguration erfolgreich gesetzt
     * @throws QCC_Configuration_Exception Bei ungültiger Konfiguration
     */
    public function set_config($config);
    
    /**
     * Gibt aktuelle Konfiguration zurück
     * 
     * @param string|null $key Specific config key oder null für alle
     * @return mixed Configuration value oder full config array
     */
    public function get_config($key = null);
    
    /**
     * Gibt Default-Konfiguration zurück
     * 
     * @return array Default configuration values
     */
    public function get_default_config();
    
    /**
     * Validiert Konfiguration
     * 
     * @param array $config Configuration to validate
     * @return bool|WP_Error True wenn valid, WP_Error bei Fehlern
     */
    public function validate_config($config);
    
    /**
     * Gibt Konfiguration-Schema zurück
     * 
     * @return array {
     *     @type array $field_name {
     *         @type string $type Field type (string, number, boolean, array)
     *         @type mixed $default Default value
     *         @type bool $required Is required
     *         @type string $description Field description
     *         @type array $options Valid options (für enums)
     *         @type array $validation Validation rules
     *     }
     * }
     */
    public function get_config_schema();
    
    /**
     * Exportiert Konfiguration für Backup/Migration
     * 
     * @param array $options Export options
     * @return array Exportable configuration
     */
    public function export_config($options = array());
    
    /**
     * Importiert Konfiguration von Backup/Migration
     * 
     * @param array $config Configuration to import
     * @param array $options Import options
     * @return bool True wenn erfolgreich importiert
     */
    public function import_config($config, $options = array());
    
    /**
     * Mergt neue Konfiguration mit bestehender
     * 
     * @param array $new_config New configuration values
     * @param bool $deep_merge Deep merge für nested arrays
     * @return bool True wenn erfolgreich gemergt
     */
    public function merge_config($new_config, $deep_merge = true);
    
    /**
     * Resettet Konfiguration auf Defaults
     * 
     * @param array $keys Specific keys to reset (empty = reset all)
     * @return bool True wenn erfolgreich resetted
     */
    public function reset_config($keys = array());
    
    /**
     * Gibt verfügbare Konfiguration-Presets zurück
     * 
     * @return array {
     *     @type array $preset_name {
     *         @type string $name Display name
     *         @type string $description Preset description
     *         @type array $config Preset configuration
     *     }
     * }
     */
    public function get_config_presets();
}
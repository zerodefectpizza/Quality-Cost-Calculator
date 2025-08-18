<?php
/**
 * QCC Calculable Interface
 * 
 * Interface für alle Calculation-Services (COGQ, COPQ, ROI, etc.).
 * Definiert einheitliche API für Berechnungs-Komponenten.
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
 * QCC Calculable Interface
 * 
 * Definiert Contract für alle Calculation-Services im QCC System.
 * Ermöglicht einheitliche Berechnungs-API für verschiedene Quality Cost Kategorien.
 * 
 * Implementiert von:
 * - COGQ Calculator (Cost of Good Quality)
 * - COPQ Calculator (Cost of Poor Quality)
 * - ROI Calculator (Return on Investment)
 * - Opportunity Calculator (Opportunity Cost Analysis)
 */
interface QCC_Calculable {
    
    /**
     * Führt Berechnung basierend auf Input-Daten durch
     * 
     * @param array $input_data Calculation input data
     * @param array $options Calculation options/settings
     * @return array {
     *     @type mixed $result Calculation result
     *     @type array $breakdown Detailed calculation breakdown
     *     @type array $metadata Calculation metadata (timestamp, version, etc.)
     * }
     * @throws QCC_Calculation_Exception Bei Berechnungs-Fehlern
     */
    public function calculate($input_data, $options = array());
    
    /**
     * Validiert Input-Daten für Berechnung
     * 
     * @param array $input_data Input data to validate
     * @return bool|WP_Error True wenn valid, WP_Error bei Validierungs-Fehlern
     */
    public function validate_input($input_data);
    
    /**
     * Gibt benötigte Input-Felder zurück
     * 
     * @return array {
     *     @type array $required Required field names
     *     @type array $optional Optional field names with defaults
     *     @type array $field_types Field type definitions (string, number, percentage)
     * }
     */
    public function get_required_fields();
    
    /**
     * Formatiert Calculation-Result für Display
     * 
     * @param array $result Raw calculation result
     * @param string $format Format type ('display', 'export', 'api', 'chart')
     * @param array $options Formatting options (currency, locale, precision)
     * @return array Formatted result
     */
    public function format_result($result, $format = 'display', $options = array());
    
    /**
     * Gibt Calculation-Metadaten zurück
     * 
     * @return array {
     *     @type string $name Calculator name
     *     @type string $version Calculator version
     *     @type array $formulas Used formulas and algorithms
     *     @type array $assumptions Calculation assumptions
     * }
     */
    public function get_calculation_metadata();
    
    /**
     * Exportiert Calculation-Logic für Debugging
     * 
     * @param array $input_data Input data
     * @return array Detailed calculation steps
     */
    public function debug_calculation($input_data);
    
    /**
     * Gibt unterstützte Währungen zurück
     * 
     * @return array Supported currency codes
     */
    public function get_supported_currencies();
    
    /**
     * Gibt Default-Werte für Calculator zurück
     * 
     * @return array Default input values
     */
    public function get_default_values();
    
    /**
     * Berechnet Trend-Analyse basierend auf historischen Daten
     * 
     * @param array $historical_data Array von Calculation-Results
     * @param array $options Trend analysis options
     * @return array Trend analysis result
     */
    public function calculate_trend($historical_data, $options = array());
}
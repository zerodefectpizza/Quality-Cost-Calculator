<?php
/**
 * QCC Validatable Interface
 * 
 * Interface für alle Validation-Services.
 * Definiert einheitliche Validierungs-API für Input, Business Rules, etc.
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
 * QCC Validation Result Class
 * 
 * Standardisierte Klasse für Validierungs-Ergebnisse.
 */
class QCC_Validation_Result {
    private $is_valid = true;
    private $errors = array();
    private $warnings = array();
    private $sanitized_data = null;
    
    public function __construct($is_valid = true, $errors = array(), $warnings = array(), $sanitized_data = null) {
        $this->is_valid = $is_valid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->sanitized_data = $sanitized_data;
    }
    
    public function is_valid() {
        return $this->is_valid && empty($this->errors);
    }
    
    public function has_warnings() {
        return !empty($this->warnings);
    }
    
    public function get_errors() {
        return $this->errors;
    }
    
    public function get_warnings() {
        return $this->warnings;
    }
    
    public function get_sanitized_data() {
        return $this->sanitized_data;
    }
    
    public function add_error($field, $message, $code = '') {
        $this->errors[] = array('field' => $field, 'message' => $message, 'code' => $code);
        $this->is_valid = false;
    }
    
    public function add_warning($field, $message, $code = '') {
        $this->warnings[] = array('field' => $field, 'message' => $message, 'code' => $code);
    }
}

/**
 * QCC Validatable Interface
 * 
 * Definiert Contract für alle Validation-Services im QCC System.
 * Ermöglicht einheitliche Validierung von Inputs, Business Rules und Datenintegrität.
 * 
 * Implementiert von:
 * - Input Validator (Formulareingaben)
 * - Business Validator (Geschäftsregeln)
 * - Integrity Validator (Datenintegrität)
 * - Calculation Validator (Berechnungseingaben)
 */
interface QCC_Validatable {
    
    /**
     * Validiert Daten basierend auf definierten Regeln
     * 
     * @param mixed $data Data to validate
     * @param array $rules Validation rules to apply
     * @param array $context Validation context
     * @return QCC_Validation_Result Validation result object
     */
    public function validate($data, $rules = array(), $context = array());
    
    /**
     * Gibt verfügbare Validierungs-Regeln zurück
     * 
     * @return array {
     *     @type array $rule_name {
     *         @type string $description Rule description
     *         @type array $parameters Required parameters
     *         @type string $error_message Default error message
     *     }
     * }
     */
    public function get_available_rules();
    
    /**
     * Registriert custom Validierungs-Regel
     * 
     * @param string $rule_name Rule name
     * @param callable $validator Validator function
     * @param array $options Rule options
     * @return bool True wenn erfolgreich registriert
     */
    public function register_rule($rule_name, $validator, $options = array());
    
    /**
     * Sanitisiert Daten nach erfolgreicher Validierung
     * 
     * @param mixed $data Data to sanitize
     * @param array $sanitization_rules Sanitization rules
     * @return mixed Sanitized data
     */
    public function sanitize($data, $sanitization_rules = array());
    
    /**
     * Gibt Default-Fehlermeldungen zurück
     * 
     * @param string $language Language code
     * @return array Default error messages by rule
     */
    public function get_default_error_messages($language = 'en');
    
    /**
     * Validiert spezifische Datentypen
     * 
     * @param mixed $value Value to validate
     * @param string $type Data type (percentage, currency, number, email, etc.)
     * @param array $constraints Type-specific constraints
     * @return bool|WP_Error True wenn valid, WP_Error bei Fehlern
     */
    public function validate_type($value, $type, $constraints = array());
    
    /**
     * Validiert Business-spezifische Regeln
     * 
     * @param array $data Complete data set
     * @param array $business_rules Business rule definitions
     * @return QCC_Validation_Result Business validation result
     */
    public function validate_business_rules($data, $business_rules = array());
    
    /**
     * Cross-Field-Validation (z.B. Summe = 100%)
     * 
     * @param array $data Multi-field data
     * @param array $cross_rules Cross-validation rules
     * @return QCC_Validation_Result Cross-validation result
     */
    public function validate_cross_fields($data, $cross_rules = array());
}
<?php
/**
 * QCC Atom Interface
 * 
 * Interface für Atomic Components - die kleinsten wiederverwendbaren UI-Bausteine
 * im Quality Cost Calculator Plugin. Atoms sind einzelne HTML-Elemente wie
 * Input-Felder, Buttons, Labels etc.
 * 
 * @package QualityCostCalculator
 * @subpackage Interfaces
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface QCC_Atom
 * 
 * Erweitert QCC_Renderable um atom-spezifische Funktionalitäten.
 * Atoms sind die kleinsten UI-Components und haben keine Child-Components.
 * 
 * Typische Atoms:
 * - Input-Felder (percentage, currency, text)
 * - Buttons (submit, export, print)
 * - Labels und Icons
 * - Status-Indicators
 * 
 * @since 3.0.0
 * @extends QCC_Renderable
 */
interface QCC_Atom extends QCC_Renderable {
    
    /**
     * Gibt Standard-HTML-Attribute für das Atom zurück
     * 
     * Definiert Default-Attribute die immer auf das HTML-Element
     * angewendet werden, es sei denn sie werden überschrieben.
     * 
     * @since 3.0.0
     * 
     * @return array Associative array von HTML-Attributen
     * 
     * @example
     * return array(
     *     'type' => 'number',
     *     'min' => '0',
     *     'max' => '100',
     *     'step' => '0.01',
     *     'class' => 'qcc-percentage-input'
     * );
     */
    public function get_default_attributes();
    
    /**
     * Gibt erforderliche Datenfelder zurück
     * 
     * Definiert welche Datenfelder für das Atom mindestens
     * vorhanden sein müssen damit render() funktioniert.
     * 
     * @since 3.0.0
     * 
     * @return array Array von required field names
     * 
     * @example
     * return array('id', 'name', 'value', 'label');
     */
    public function get_required_fields();
    
    /**
     * Gibt optionale Datenfelder zurück
     * 
     * Definiert zusätzliche Datenfelder die das Atom unterstützt
     * aber nicht zwingend benötigt.
     * 
     * @since 3.0.0
     * 
     * @return array Array von optional field names mit Defaults
     * 
     * @example
     * return array(
     *     'placeholder' => '',
     *     'description' => '',
     *     'required' => false,
     *     'disabled' => false
     * );
     */
    public function get_optional_fields();
    
    /**
     * Gibt die Basis-CSS-Klasse für das Atom zurück
     * 
     * Hauptklasse die immer auf das Atom angewendet wird.
     * Weitere Klassen werden je nach Status hinzugefügt.
     * 
     * @since 3.0.0
     * 
     * @return string Base CSS class name
     * 
     * @example
     * return 'qcc-percentage-input';
     */
    public function get_base_css_class();
    
    /**
     * Gibt Status-abhängige CSS-Klassen zurück
     * 
     * Generiert zusätzliche CSS-Klassen basierend auf
     * dem aktuellen Zustand des Atoms.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-Daten für Status-Erkennung
     * @return array Array von status-spezifischen CSS-Klassen
     * 
     * @example
     * // Für error state:
     * return array('qcc-error', 'qcc-invalid');
     * // Für required field:
     * return array('qcc-required');
     */
    public function get_status_css_classes($data);
    
    /**
     * Validiert einzelne Input-Werte
     * 
     * Atom-spezifische Validierung für einzelne Werte.
     * Unterscheidet sich von validate_data() welches das gesamte
     * Daten-Array validiert.
     * 
     * @since 3.0.0
     * 
     * @param mixed $value Einzelwert zum validieren
     * @param string $field_name Name des Feldes (für context)
     * @return true|WP_Error True bei gültigem Wert, WP_Error bei Fehlern
     * 
     * @example
     * $result = $atom->validate_value('25.5', 'percentage');
     * if (is_wp_error($result)) {
     *     echo $result->get_error_message();
     * }
     */
    public function validate_value($value, $field_name);
    
    /**
     * Formatiert Werte für Display
     * 
     * Konvertiert interne Werte in benutzerfreundliche
     * Darstellung (z.B. Zahlen formatieren, Währung hinzufügen).
     * 
     * @since 3.0.0
     * 
     * @param mixed $value Raw value zum formatieren
     * @param string $field_name Field context für Formatierung
     * @param array $options Formatting options
     * @return string Formatted display value
     * 
     * @example
     * echo $atom->format_value(1250000, 'currency', array('symbol' => '€'));
     * // Returns: "€1,250,000"
     */
    public function format_value($value, $field_name, $options = array());
    
    /**
     * Parsiert Input-Werte aus Formularen
     * 
     * Konvertiert User-Input (immer Strings) in die korrekte
     * interne Datentypen (numbers, booleans, etc.).
     * 
     * @since 3.0.0
     * 
     * @param string $input User input string
     * @param string $field_name Field context für Parsing
     * @return mixed Parsed value in correct data type
     * 
     * @example
     * $parsed = $atom->parse_input('25.5%', 'percentage');
     * // Returns: 25.5 (float)
     */
    public function parse_input($input, $field_name);
    
    /**
     * Gibt Input-Typ zurück
     * 
     * Definiert welche Art von HTML-Input das Atom erstellt.
     * Wird für Validierung und JavaScript-Handling verwendet.
     * 
     * @since 3.0.0
     * 
     * @return string Input type ('text', 'number', 'select', 'checkbox', etc.)
     * 
     * @example
     * return 'number'; // für <input type="number">
     */
    public function get_input_type();
    
    /**
     * Prüft ob Atom interactive ist
     * 
     * Unterscheidet zwischen Input-Atoms (interactive) und
     * Display-Atoms (read-only wie Labels, Icons).
     * 
     * @since 3.0.0
     * 
     * @return bool True wenn Atom User-Input akzeptiert
     * 
     * @example
     * return true;  // für Input-Felder
     * return false; // für Labels, Icons
     */
    public function is_interactive();
    
    /**
     * Gibt JavaScript-Events zurück
     * 
     * Definiert welche JavaScript-Events das Atom triggern kann.
     * Wird für Frontend-Interaktivity verwendet.
     * 
     * @since 3.0.0
     * 
     * @return array Array von Event-Namen und Callbacks
     * 
     * @example
     * return array(
     *     'change' => 'qcc_percentage_changed',
     *     'blur' => 'qcc_validate_percentage',
     *     'focus' => 'qcc_highlight_field'
     * );
     */
    public function get_javascript_events();
    
    /**
     * Gibt Accessibility-Attribute zurück
     * 
     * ARIA-Attribute und andere Accessibility-Features
     * für Screen-Reader und Keyboard-Navigation.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-Daten für Context
     * @return array ARIA und Accessibility-Attribute
     * 
     * @example
     * return array(
     *     'aria-label' => 'Prevention cost percentage',
     *     'aria-required' => 'true',
     *     'aria-describedby' => 'prevention-help-text'
     * );
     */
    public function get_accessibility_attributes($data);
    
    /**
     * Erstellt Help-Text für das Atom
     * 
     * Benutzerfreundliche Beschreibung was das Atom macht
     * und wie es verwendet werden soll.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-Daten für Context
     * @return string HTML help text
     * 
     * @example
     * return '<small class="qcc-help">Enter percentage between 0-100</small>';
     */
    public function get_help_text($data);
    
    /**
     * Generiert Validation-Regeln für Frontend
     * 
     * JavaScript-kompatible Validierungsregeln die im
     * Frontend für Live-Validation verwendet werden.
     * 
     * @since 3.0.0
     * 
     * @return array Validation rules array
     * 
     * @example
     * return array(
     *     'required' => true,
     *     'type' => 'number',
     *     'min' => 0,
     *     'max' => 100,
     *     'step' => 0.01
     * );
     */
    public function get_frontend_validation_rules();
    
    /**
     * Generiert einmalige ID für das Atom
     * 
     * Erstellt unique HTML-ID für das Atom-Element.
     * Wird für Label-Associations und JavaScript verwendet.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-Daten für ID-Generierung
     * @return string Unique HTML ID
     * 
     * @example
     * return 'qcc-prevention-input-' . uniqid();
     */
    public function generate_unique_id($data);
}
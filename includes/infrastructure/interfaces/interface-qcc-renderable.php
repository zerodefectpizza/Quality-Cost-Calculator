<?php
/**
 * QCC Renderable Interface
 * 
 * Base interface für alle Rendering-Komponenten im Quality Cost Calculator Plugin.
 * Definiert die grundlegenden Contracts für HTML-Generierung, Validation und Asset-Management.
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
 * Interface QCC_Renderable
 * 
 * Basis-Contract für alle Rendering-Komponenten (Atoms, Molecules, Builders).
 * Stellt sicher, dass alle UI-Components einheitliche APIs haben.
 * 
 * @since 3.0.0
 */
interface QCC_Renderable {
    
    /**
     * Rendert die Komponente als HTML-String
     * 
     * Hauptmethode für HTML-Generierung. Muss von allen implementierenden
     * Klassen überschrieben werden.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-spezifische Daten (values, labels, etc.)
     * @param array $attributes HTML-Attribute für das Component-Element
     * @return string Generated HTML string
     * 
     * @throws QCC_Rendering_Exception Bei Rendering-Fehlern
     * 
     * @example
     * $html = $component->render(
     *     array('value' => 25, 'label' => 'Prevention Cost'),
     *     array('class' => 'custom-class', 'id' => 'prevention-input')
     * );
     */
    public function render($data = array(), $attributes = array());
    
    /**
     * Validiert Input-Daten vor dem Rendering
     * 
     * Prüft ob die übergebenen Daten für das Component gültig sind.
     * Sollte vor jedem render() Aufruf verwendet werden.
     * 
     * @since 3.0.0
     * 
     * @param array $data Zu validierende Component-Daten
     * @return true|WP_Error True bei gültigen Daten, WP_Error bei Fehlern
     * 
     * @example
     * $validation = $component->validate_data(array('value' => 'invalid'));
     * if (is_wp_error($validation)) {
     *     error_log($validation->get_error_message());
     * }
     */
    public function validate_data($data);
    
    /**
     * Gibt benötigte Frontend-Assets zurück
     * 
     * Definiert CSS- und JavaScript-Dateien die für das Component
     * geladen werden müssen.
     * 
     * @since 3.0.0
     * 
     * @return array Asset-Definition mit CSS/JS/Dependencies
     * 
     * @example
     * return array(
     *     'css' => array('percentage-input.css'),
     *     'js' => array('percentage-validation.js'),
     *     'dependencies' => array('jquery', 'qcc-core')
     * );
     */
    public function get_required_assets();
    
    /**
     * Erstellt einmaligen Cache-Key für Component + Daten
     * 
     * Ermöglicht Caching von gerenderten HTML-Fragmenten basierend
     * auf Component-Typ und Daten-Inhalt.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-Daten für Cache-Key-Generierung
     * @return string Unique cache key string
     * 
     * @example
     * $cache_key = $component->get_cache_key(array('value' => 25));
     * // Returns: "qcc_percentage_input_abc123_en_US"
     */
    public function get_cache_key($data);
    
    /**
     * Gibt Component-Typ zurück
     * 
     * Identifiziert den Component-Typ für Registry und Debugging.
     * Wird automatisch von Base-Classes implementiert.
     * 
     * @since 3.0.0
     * 
     * @return string Component type ('atom', 'molecule', 'builder')
     * 
     * @example
     * echo $component->get_component_type(); // 'atom'
     */
    public function get_component_type();
    
    /**
     * Gibt Component-Name zurück
     * 
     * Eindeutige Identifikation des Components für Registry.
     * Wird automatisch aus Klassennamen generiert.
     * 
     * @since 3.0.0
     * 
     * @return string Component name (kebab-case)
     * 
     * @example
     * echo $component->get_component_name(); // 'percentage-input'
     */
    public function get_component_name();
    
    /**
     * Prüft ob Component gecacht werden kann
     * 
     * Manche Components (z.B. mit dynamic content) sollten nicht
     * gecacht werden.
     * 
     * @since 3.0.0
     * 
     * @return bool True wenn caching erlaubt ist
     */
    public function is_cacheable();
    
    /**
     * Gibt Template-Name zurück
     * 
     * Pfad zur Template-Datei für HTML-Struktur.
     * Ermöglicht Theme-Overrides.
     * 
     * @since 3.0.0
     * 
     * @return string Template filename oder Pfad
     * 
     * @example
     * return 'atoms/percentage-input.php';
     */
    public function get_template_name();
    
    /**
     * Bereitet Daten für Template-Rendering vor
     * 
     * Konvertiert Component-Daten in Template-kompatibles Format.
     * Wird intern von render() verwendet.
     * 
     * @since 3.0.0
     * 
     * @param array $data Raw component data
     * @param array $attributes HTML attributes
     * @return array Prepared template data
     * 
     * @access protected (sollte nur intern verwendet werden)
     */
    public function prepare_template_data($data, $attributes);
    
    /**
     * Sanitisiert Input-Daten
     * 
     * Säubert und validiert Input-Werte für sicheres HTML-Rendering.
     * 
     * @since 3.0.0
     * 
     * @param mixed $value Input-Wert zum sanitisieren
     * @return mixed Sanitized value
     * 
     * @example
     * $safe_value = $component->sanitize_input('<script>alert("xss")</script>');
     * // Returns: 'alert("xss")'
     */
    public function sanitize_input($value);
    
    /**
     * Generiert CSS-Klassen für Component
     * 
     * Erstellt context-aware CSS-Klassen basierend auf Component-Zustand
     * und übergebenen Daten.
     * 
     * @since 3.0.0
     * 
     * @param array $data Component-Daten für CSS-Klassen-Generierung
     * @return string Space-separated CSS classes
     * 
     * @example
     * echo $component->get_css_classes(array('error' => true));
     * // Returns: "qcc-percentage-input qcc-error qcc-required"
     */
    public function get_css_classes($data);
    
    /**
     * Behandelt Rendering-Fehler
     * 
     * Erstellt Error-HTML für Fälle wo normales Rendering fehlschlägt.
     * Verhindert dass Fehler die ganze Seite brechen.
     * 
     * @since 3.0.0
     * 
     * @param WP_Error $error Error object with details
     * @return string Error HTML output
     * 
     * @example
     * return $component->handle_error(new WP_Error('invalid_data', 'Value must be numeric'));
     * // Returns: '<div class="qcc-error">Value must be numeric</div>'
     */
    public function handle_error($error);
}
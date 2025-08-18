<?php
/**
 * QCC Renderable Interface
 * 
 * Basis-Interface für alle Rendering-Komponenten (Atoms, Molecules, Organisms).
 * Definiert grundlegende Methoden für HTML-Generierung und Asset-Management.
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
 * QCC Renderable Interface
 * 
 * Definiert Contract für alle Rendering-Komponenten im QCC System.
 * Ermöglicht einheitliche HTML-Generierung, Asset-Management und Template-Support.
 * 
 * Implementiert von:
 * - Atomic Components (Input-Fields, Buttons, Cards)
 * - Molecule Components (Input-Groups, Result-Sections)
 * - Organism Components (Forms, Charts, Layouts)
 */
interface QCC_Renderable {
    
    /**
     * Rendert die Komponente als HTML
     * 
     * @param array $data Component-spezifische Daten
     * @param array $attributes HTML-Attribute (class, id, data-*, etc.)
     * @return string Generated HTML
     * @throws QCC_Rendering_Exception Bei Rendering-Fehlern
     */
    public function render($data = array(), $attributes = array());
    
    /**
     * Validiert Input-Daten vor dem Rendering
     * 
     * @param array $data Zu validierende Daten
     * @return bool|WP_Error True wenn valid, WP_Error bei Fehlern
     */
    public function validate_data($data);
    
    /**
     * Gibt benötigte Assets zurück (CSS, JS, Dependencies)
     * 
     * @return array {
     *     @type array $css Array von CSS-Dateien/URLs
     *     @type array $js Array von JavaScript-Dateien/URLs  
     *     @type array $dependencies Array von WordPress-Dependencies
     * }
     */
    public function get_required_assets();
    
    /**
     * Erstellt Cache-Key für Component-Caching
     * 
     * @param array $data Component-Daten
     * @return string Unique Cache Key
     */
    public function get_cache_key($data);
    
    /**
     * Gibt Template-Name für die Komponente zurück
     * 
     * @return string Template filename (ohne Extension)
     */
    public function get_template_name();
    
    /**
     * Gibt Standard-Attribute für die Komponente zurück
     * 
     * @return array Default HTML attributes
     */
    public function get_default_attributes();
    
    /**
     * Sanitisiert und bereitet Daten für Template-Injection vor
     * 
     * @param array $data Raw input data
     * @return array Sanitized template data
     */
    public function prepare_template_data($data);
    
    /**
     * Gibt CSS-Klassen für Component zurück
     * 
     * @param array $data Component-Daten für dynamische Klassen
     * @return string Space-separated CSS classes
     */
    public function get_css_classes($data = array());
}
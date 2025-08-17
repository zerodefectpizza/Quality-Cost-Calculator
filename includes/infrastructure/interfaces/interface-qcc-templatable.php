<?php
/**
 * QCC Templatable Interface
 * 
 * Interface für Template-System Support in QCC Components.
 * Ermöglicht Theme-Overrides und Template-basierte HTML-Generierung.
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
 * Interface QCC_Templatable
 * 
 * Erweitert Components um Template-System-Funktionalitäten.
 * Kann von allen Component-Typen implementiert werden.
 * 
 * @since 3.0.0
 */
interface QCC_Templatable {
    
    /**
     * Gibt Template-Pfad zurück
     * 
     * @since 3.0.0
     * @return string Template file path
     */
    public function get_template_path();
    
    /**
     * Gibt Template-Variablen zurück
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array Template variables
     */
    public function get_template_vars($data);
    
    /**
     * Prüft Template-Existenz
     * 
     * @since 3.0.0
     * @return bool True wenn Template existiert
     */
    public function template_exists();
    
    /**
     * Gibt Template-Fallback zurück
     * 
     * @since 3.0.0
     * @return string Fallback template path
     */
    public function get_fallback_template();
    
    /**
     * Rendert via Template
     * 
     * @since 3.0.0
     * @param array $data Template data
     * @return string Rendered HTML
     */
    public function render_template($data);
    
    /**
     * Gibt Theme-Override-Pfad zurück
     * 
     * @since 3.0.0
     * @return string Theme override path
     */
    public function get_theme_override_path();
    
    /**
     * Prüft Theme-Override-Existenz
     * 
     * @since 3.0.0
     * @return bool True wenn Theme-Override existiert
     */
    public function has_theme_override();
    
    /**
     * Gibt Template-Hooks zurück
     * 
     * @since 3.0.0
     * @return array Template hook definitions
     */
    public function get_template_hooks();
    
    /**
     * Registriert Template-Hooks
     * 
     * @since 3.0.0
     * @return void
     */
    public function register_template_hooks();
    
    /**
     * Gibt Template-Context zurück
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @return array Template context
     */
    public function get_template_context($data);
    
    /**
     * Prüft Template-Cache
     * 
     * @since 3.0.0
     * @param string $cache_key Cache key
     * @return bool True wenn cacheable
     */
    public function is_template_cacheable($cache_key);
    
    /**
     * Gibt Template-Cache-Key zurück
     * 
     * @since 3.0.0
     * @param array $data Template data
     * @return string Cache key
     */
    public function get_template_cache_key($data);
}
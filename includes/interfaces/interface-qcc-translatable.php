<?php
/**
 * QCC Translatable Interface
 * 
 * Interface für Components mit Internationalisierung-Support.
 * Ermöglicht einheitliche Multi-Language-Funktionalität.
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
 * QCC Translatable Interface
 * 
 * Definiert Contract für Components mit I18n-Support im QCC System.
 * Ermöglicht einheitliche Internationalisierung für alle UI-Komponenten.
 * 
 * Implementiert von:
 * - Rendering Components (UI-Texte)
 * - Validation Services (Fehlermeldungen)
 * - Calculation Services (Result-Labels)
 * - Error Handlers (Benutzer-Nachrichten)
 */
interface QCC_Translatable {
    
    /**
     * Gibt übersetzbaren Text zurück
     * 
     * @param string $key Translation key
     * @param string $default Default text wenn Translation nicht verfügbar
     * @param array $context Translation context (plurals, variables, etc.)
     * @return string Translated text
     */
    public function translate($key, $default = '', $context = array());
    
    /**
     * Gibt alle Translation-Keys für Component zurück
     * 
     * @return array Translation keys used by this component
     */
    public function get_translation_keys();
    
    /**
     * Setzt aktuelle Sprache für Component
     * 
     * @param string $language Language code (en, de, fr, es, zh)
     * @return bool True wenn Sprache erfolgreich gesetzt
     */
    public function set_language($language);
    
    /**
     * Gibt aktuelle Sprache zurück
     * 
     * @return string Current language code
     */
    public function get_current_language();
    
    /**
     * Gibt unterstützte Sprachen zurück
     * 
     * @return array {
     *     @type string $language_code {
     *         @type string $name Language name (English)
     *         @type string $native_name Native language name (Deutsch)
     *         @type bool $rtl Is right-to-left language
     *         @type float $completion Translation completion percentage
     *     }
     * }
     */
    public function get_supported_languages();
    
    /**
     * Registriert Translation-Strings für Component
     * 
     * @param array $translations Translation array
     * @param string $language Language code
     * @return bool True wenn erfolgreich registriert
     */
    public function register_translations($translations, $language);
    
    /**
     * Formatiert Text basierend auf Sprach-spezifischen Regeln
     * 
     * @param mixed $value Value to format (number, date, currency, etc.)
     * @param string $type Format type (number, currency, date, percentage)
     * @param array $options Formatting options
     * @return string Formatted text
     */
    public function format_localized($value, $type, $options = array());
    
    /**
     * Übersetzt Text mit Variablen-Ersetzung
     * 
     * @param string $key Translation key
     * @param array $variables Variables for placeholder replacement
     * @param string $default Default text
     * @return string Translated text with variables
     */
    public function translate_with_variables($key, $variables = array(), $default = '');
    
    /**
     * Übersetzt Plural-Formen
     * 
     * @param string $single_key Singular translation key
     * @param string $plural_key Plural translation key
     * @param int $count Count for plural determination
     * @param array $variables Variables for replacement
     * @return string Appropriate plural form
     */
    public function translate_plural($single_key, $plural_key, $count, $variables = array());
    
    /**
     * Gibt Text-Direction für aktuelle Sprache zurück
     * 
     * @return string 'ltr' oder 'rtl'
     */
    public function get_text_direction();
    
    /**
     * Exportiert alle Translations für Backup/Migration
     * 
     * @param string|null $language Specific language oder null für alle
     * @return array Translation export data
     */
    public function export_translations($language = null);
}
<?php
/**
 * Translator Class for Quality Cost Calculator - SYNTAX CORRECTED
 * 
 * Handles all internationalization and localization for the QCC plugin.
 * Uses separate translation files for better maintainability and performance.
 * 
 * @package QualityCostCalculator
 * @since 1.1.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Translator Class - Loads translations from separate files
 */
class QCC_Translator {
    
    /**
     * Current language code
     * @var string
     */
    private $current_language = 'en';
    
    /**
     * Loaded translations cache
     * @var array
     */
    private $translations = array();
    
    /**
     * Supported languages with their information
     * @var array
     */
    private $supported_languages = array(
        'en' => array(
            'name' => 'English',
            'class' => 'QCC_English_Translations',
            'native' => 'English',
            'locale' => 'en_US'
        ),
        'de' => array(
            'name' => 'German',
            'class' => 'QCC_German_Translations',
            'native' => 'Deutsch',
            'locale' => 'de_DE'
        ),
        'fr' => array(
            'name' => 'French',
            'class' => 'QCC_French_Translations',
            'native' => 'Français',
            'locale' => 'fr_FR'
        ),
        'es' => array(
            'name' => 'Spanish',
            'class' => 'QCC_Spanish_Translations',
            'native' => 'Español',
            'locale' => 'es_ES'
        ),
        'zh' => array(
            'name' => 'Chinese',
            'class' => 'QCC_Chinese_Translations',
            'native' => '中文',
            'locale' => 'zh_CN'
        )
    );
    
    /**
     * Language-specific formatting rules
     * @var array
     */
    private $formatting_rules = array(
        'en' => array('decimal' => '.', 'thousands' => ','),
        'de' => array('decimal' => ',', 'thousands' => '.'),
        'fr' => array('decimal' => ',', 'thousands' => ' '),
        'es' => array('decimal' => ',', 'thousands' => '.'),
        'zh' => array('decimal' => '.', 'thousands' => ',')
    );
    
    /**
     * Debug mode flag
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     * 
     * @param string $language Initial language
     */
    public function __construct($language = null) {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        
        // Set current language
        if ($language && $this->is_supported($language)) {
            $this->current_language = $language;
        } else {
            $this->current_language = $this->get_default_language();
        }
        
        if ($this->debug_mode) {
            error_log("QCC_Translator initialized with language: {$this->current_language}");
        }
    }
    
    /**
     * Get translation for a specific key
     * 
     * @param string $key Translation key
     * @param string $fallback Fallback text if translation not found
     * @param array $replacements Optional placeholder replacements
     * @return string Translated text
     */
    public function get($key, $fallback = null, $replacements = array()) {
        // Load translations for current language if not already loaded
        if (!isset($this->translations[$this->current_language])) {
            $this->load_language_translations($this->current_language);
        }
        
        // Get the translation
        $translation = $this->translations[$this->current_language][$key] ?? 
                      $this->get_fallback_translation($key) ?? 
                      $fallback ?? 
                      $this->format_fallback_key($key);
        
        // Handle placeholder replacements
        if (!empty($replacements)) {
            foreach ($replacements as $placeholder => $value) {
                $translation = str_replace('{' . $placeholder . '}', $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Get translation with language override
     * 
     * @param string $key Translation key
     * @param string $language Language code
     * @param string $fallback Fallback text
     * @return string Translated text
     */
    public function get_for_language($key, $language, $fallback = null) {
        if (!$this->is_supported($language)) {
            return $fallback ?? $key;
        }
        
        // Load translations for specified language if not already loaded
        if (!isset($this->translations[$language])) {
            $this->load_language_translations($language);
        }
        
        return $this->translations[$language][$key] ?? $fallback ?? $key;
    }
    
    /**
     * Set current language
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    public function set_language($language) {
        if (!$this->is_supported($language)) {
            if ($this->debug_mode) {
                error_log("QCC Translator: Unsupported language '{$language}'");
            }
            return false;
        }
        
        $this->current_language = $language;
        
        // Preload translations for performance
        if (!isset($this->translations[$language])) {
            $this->load_language_translations($language);
        }
        
        if ($this->debug_mode) {
            error_log("QCC Translator: Language changed to '{$language}'");
        }
        
        return true;
    }
    
    /**
     * Get current language
     * 
     * @return string Current language code
     */
    public function get_current_language() {
        return $this->current_language;
    }
    
    /**
     * Get supported languages
     * 
     * @return array Supported languages
     */
    public function get_supported_languages() {
        return array_keys($this->supported_languages);
    }
    
    /**
     * Get language information
     * 
     * @param string $language Language code
     * @return array|null Language information
     */
    public function get_language_info($language) {
        return $this->supported_languages[$language] ?? null;
    }
    
    /**
     * Check if language is supported
     * 
     * @param string $language Language code
     * @return bool True if supported
     */
    public function is_supported($language) {
        return isset($this->supported_languages[$language]);
    }
    
    /**
     * Format number according to language rules
     * 
     * @param float $number Number to format
     * @param int $decimals Number of decimal places
     * @param string $language Optional language override
     * @return string Formatted number
     */
    public function format_number($number, $decimals = 2, $language = null) {
        $lang = $language ?: $this->current_language;
        $rules = $this->formatting_rules[$lang] ?? $this->formatting_rules['en'];
        
        return number_format($number, $decimals, $rules['decimal'], $rules['thousands']);
    }
    
    /**
     * Format currency according to language rules
     * 
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @param string $language Optional language override
     * @return string Formatted currency
     */
    public function format_currency($amount, $currency = 'EUR', $language = null) {
        $lang = $language ?: $this->current_language;
        
        // Currency symbols
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥'
        );
        
        $symbol = $symbols[$currency] ?? $currency;
        $formatted_amount = $this->format_number($amount, 2, $lang);
        
        // Language-specific currency formatting
        switch ($lang) {
            case 'de':
            case 'fr':
                return $formatted_amount . ' ' . $symbol;
            case 'en':
            default:
                return $symbol . ' ' . $formatted_amount;
        }
    }
    
    /**
     * Format percentage according to language rules
     * 
     * @param float $value Value to format as percentage
     * @param int $decimals Number of decimal places
     * @param string $language Optional language override
     * @return string Formatted percentage
     */
    public function format_percentage($value, $decimals = 1, $language = null) {
        $formatted = $this->format_number($value, $decimals, $language);
        return $formatted . '%';
    }
    
    /**
     * Get default language from WordPress settings
     * 
     * @return string Default language code
     */
    private function get_default_language() {
        $wp_locale = get_locale();
        
        // Map WordPress locales to our language codes
        $locale_mapping = array(
            'de_DE' => 'de',
            'de_DE_formal' => 'de',
            'fr_FR' => 'fr',
            'es_ES' => 'es',
            'zh_CN' => 'zh',
            'zh_TW' => 'zh'
        );
        
        return $locale_mapping[$wp_locale] ?? 'en';
    }
    
    /**
     * Load translations for a specific language
     * 
     * @param string $language Language code
     */
    private function load_language_translations($language) {
        if (!$this->is_supported($language)) {
            $this->translations[$language] = array();
            return;
        }
        
        $language_info = $this->supported_languages[$language];
        $class_name = $language_info['class'];
        
        // Try to load the translation class
        if (class_exists($class_name)) {
            try {
                $translation_instance = new $class_name();
                if (method_exists($translation_instance, 'get_translations')) {
                    $translations = $translation_instance->get_translations();
                    $this->translations[$language] = is_array($translations) ? $translations : array();
                    
                    if ($this->debug_mode) {
                        error_log("QCC Translator: Loaded " . count($this->translations[$language]) . " translations for language '{$language}'");
                    }
                } else {
                    $this->translations[$language] = array();
                }
            } catch (Exception $e) {
                if ($this->debug_mode) {
                    error_log("QCC Translator: Error loading translations for '{$language}': " . $e->getMessage());
                }
                $this->translations[$language] = array();
            }
        } else {
            if ($this->debug_mode) {
                error_log("QCC Translator: Language class '{$class_name}' not found");
            }
            $this->translations[$language] = array();
        }
    }
    
    /**
     * Get fallback translation from English
     * 
     * @param string $key Translation key
     * @return string|null Fallback translation
     */
    private function get_fallback_translation($key) {
        // If current language is not English, try to get English translation
        if ($this->current_language !== 'en') {
            if (!isset($this->translations['en'])) {
                $this->load_language_translations('en');
            }
            
            return $this->translations['en'][$key] ?? null;
        }
        
        return null;
    }
    
    /**
     * Format fallback key for display
     * 
     * @param string $key Translation key
     * @return string Formatted key
     */
    private function format_fallback_key($key) {
        // Convert key to readable format
        $formatted = str_replace(array('_', '.'), ' ', $key);
        $formatted = ucwords($formatted);
        
        if ($this->debug_mode) {
            $formatted = "[{$key}] {$formatted}";
        }
        
        return $formatted;
    }
    
    /**
     * Get available language options for forms
     * 
     * @return array Language options
     */
    public function get_language_options() {
        $options = array();
        
        foreach ($this->supported_languages as $code => $info) {
            $options[$code] = $info['native'] . ' (' . $info['name'] . ')';
        }
        
        return $options;
    }
    
    /**
     * Get translation statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $stats = array(
            'current_language' => $this->current_language,
            'supported_languages' => count($this->supported_languages),
            'loaded_languages' => count($this->translations),
            'translations_per_language' => array()
        );
        
        foreach ($this->translations as $lang => $translations) {
            $stats['translations_per_language'][$lang] = count($translations);
        }
        
        return $stats;
    }
    
    /**
     * Clear translation cache
     */
    public function clear_cache() {
        $this->translations = array();
        
        if ($this->debug_mode) {
            error_log("QCC Translator: Translation cache cleared");
        }
    }
    
    /**
     * Preload all translations for performance
     */
    public function preload_all_translations() {
        foreach ($this->supported_languages as $language => $info) {
            if (!isset($this->translations[$language])) {
                $this->load_language_translations($language);
            }
        }
        
        if ($this->debug_mode) {
            error_log("QCC Translator: All translations preloaded");
        }
    }
}
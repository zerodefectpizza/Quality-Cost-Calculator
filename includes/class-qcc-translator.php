<?php
/**
 * Translator Class for Quality Cost Calculator - REVISED VERSION
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
     * Supported languages with their file paths
     * @var array
     */
    private $supported_languages = array(
        'en' => array(
            'name' => 'English',
            'file' => 'qcc-translations-en.php',
            'native' => 'English'
        ),
        'de' => array(
            'name' => 'Deutsch',
            'file' => 'qcc-translations-de.php',
            'native' => 'Deutsch'
        ),
        'fr' => array(
            'name' => 'Français',
            'file' => 'qcc-translations-fr.php',
            'native' => 'Français'
        ),
        'es' => array(
            'name' => 'Español',
            'file' => 'qcc-translations-es.php',
            'native' => 'Español'
        ),
        'zh' => array(
            'name' => '中文',
            'file' => 'qcc-translations-zh.php',
            'native' => '中文'
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
     * Translation file directory path
     * @var string
     */
    private $translations_dir = '';
    
    /**
     * Constructor
     */
    public function __construct($language = null) {
        // Set translations directory
        $this->translations_dir = $this->get_translations_directory();
        
        // Set current language
        if ($language && $this->is_supported($language)) {
            $this->current_language = $language;
        } else {
            $this->current_language = $this->get_default_language();
        }
        
        // Debug logging
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log("QCC_Translator initialized with language: {$this->current_language}", 'TRANSLATOR');
            QCC_Debug_Logger::log("Translations directory: {$this->translations_dir}", 'TRANSLATOR');
        }
    }
    
    /**
     * Get translation for a specific key
     * 
     * @param string $key Translation key
     * @param string $language Optional language override
     * @param array $replacements Optional placeholder replacements
     * @return string Translated text
     */
    public function get($key, $language = null, $replacements = array()) {
        $lang = $language ?: $this->current_language;
        
        // Load translations for this language if not already loaded
        if (!isset($this->translations[$lang])) {
            $this->load_language_translations($lang);
        }
        
        // Get the translation
        $translation = $this->translations[$lang][$key] ?? 
                      $this->get_fallback_translation($key, $lang) ?? 
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
     * Alias for get() method for backward compatibility
     */
    public function t($key, $language = null, $replacements = array()) {
        return $this->get($key, $language, $replacements);
    }
    
    /**
     * Set current language
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    public function set_language($language) {
        if ($this->is_supported($language)) {
            $this->current_language = $language;
            
            // Preload this language's translations
            if (!isset($this->translations[$language])) {
                $this->load_language_translations($language);
            }
            
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log("Language changed to: {$language}", 'TRANSLATOR');
            }
            
            return true;
        }
        
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log_error("Unsupported language requested: {$language}");
        }
        
        return false;
    }
    
    /**
     * Get current language
     * 
     * @return string Current language code
     */
    public function get_language() {
        return $this->current_language;
    }
    
    /**
     * Check if language is supported
     * 
     * @param string $language Language code
     * @return bool
     */
    public function is_supported($language) {
        return array_key_exists($language, $this->supported_languages);
    }
    
    /**
     * Get all supported languages
     * 
     * @return array Array of language codes and names
     */
    public function get_supported_languages() {
        $languages = array();
        foreach ($this->supported_languages as $code => $data) {
            $languages[$code] = $data['name'];
        }
        return $languages;
    }
    
    /**
     * Get detailed language information
     * 
     * @return array Detailed language information
     */
    public function get_language_details() {
        return $this->supported_languages;
    }
    
    /**
     * Get all translations for JavaScript (loads all languages)
     * 
     * @return array All translations formatted for JSON
     */
    public function get_all_translations() {
        $all_translations = array();
        
        foreach (array_keys($this->supported_languages) as $lang_code) {
            if (!isset($this->translations[$lang_code])) {
                $this->load_language_translations($lang_code);
            }
            $all_translations[$lang_code] = $this->translations[$lang_code];
        }
        
        return $all_translations;
    }
    
    /**
     * Get translations for specific language
     * 
     * @param string $language Language code
     * @return array Language-specific translations
     */
    public function get_language_translations($language) {
        if (!isset($this->translations[$language])) {
            $this->load_language_translations($language);
        }
        
        return $this->translations[$language] ?? array();
    }
    
    /**
     * Format number according to language conventions
     * 
     * @param float $number Number to format
     * @param int $decimals Number of decimal places
     * @param string $language Language code
     * @return string Formatted number
     */
    public function format_number($number, $decimals = 2, $language = null) {
        $lang = $language ?: $this->current_language;
        $rules = $this->formatting_rules[$lang] ?? $this->formatting_rules['en'];
        
        return number_format($number, $decimals, $rules['decimal'], $rules['thousands']);
    }
    
    /**
     * Format currency according to language and currency type
     * 
     * @param float $amount Amount to format
     * @param string $currency Currency code (EUR, USD, CNY)
     * @param int $unit Unit multiplier (1000000 for millions)
     * @param string $language Language code
     * @return string Formatted currency string
     */
    public function format_currency($amount, $currency = 'EUR', $unit = 1000000, $language = null) {
        $lang = $language ?: $this->current_language;
        $formatted_amount = $this->format_number($amount / $unit, 2, $lang);
        
        $symbols = array(
            'EUR' => '€',
            'USD' => ',
            'CNY' => '¥'
        );
        
        $symbol = $symbols[$currency] ?? '€';
        
        // German/European style: amount + symbol
        if ($lang === 'de' || $lang === 'fr') {
            return $formatted_amount . ' ' . $symbol;
        }
        
        // American/Chinese style: symbol + amount  
        return $symbol . ' ' . $formatted_amount;
    }
    
    /**
     * Get unit name in specific language
     * 
     * @param string $unit Unit value (1000000 or 1000000000)
     * @param string $language Language code
     * @return string Unit name
     */
    public function get_unit_name($unit, $language = null) {
        $lang = $language ?: $this->current_language;
        
        $unit_key = $unit === '1000000000' ? 'billions' : 'millions';
        return $this->get($unit_key, $lang);
    }
    
    /**
     * Get language status for admin display
     * 
     * @return array Language status information
     */
    public function get_language_status() {
        $status = array();
        
        foreach ($this->supported_languages as $code => $data) {
            $file_path = $this->get_translation_file_path($code);
            $file_exists = file_exists($file_path);
            $translations_count = 0;
            
            if ($file_exists) {
                $translations = $this->load_translation_file($code);
                $translations_count = is_array($translations) ? count($translations) : 0;
            }
            
            $status[] = array(
                'code' => $code,
                'name' => $data['name'],
                'native_name' => $data['native'],
                'file' => $data['file'],
                'file_exists' => $file_exists,
                'file_path' => $file_path,
                'translations_count' => $translations_count,
                'status' => $file_exists && $translations_count > 0 ? 'complete' : 'missing',
                'status_text' => $this->get_status_text($code, $file_exists, $translations_count),
                'completion' => $file_exists ? min(100, ($translations_count / 100) * 100) : 0
            );
        }
        
        return $status;
    }
    
    /**
     * Reload translations for specific language (useful for development)
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    public function reload_language($language) {
        if (!$this->is_supported($language)) {
            return false;
        }
        
        // Remove from cache
        unset($this->translations[$language]);
        
        // Reload
        $this->load_language_translations($language);
        
        if (class_exists('QCC_Debug_Logger')) {
            $count = count($this->translations[$language] ?? array());
            QCC_Debug_Logger::log("Reloaded {$count} translations for language: {$language}", 'TRANSLATOR');
        }
        
        return isset($this->translations[$language]);
    }
    
    /**
     * Get translation statistics
     * 
     * @return array Translation statistics
     */
    public function get_translation_stats() {
        $stats = array(
            'total_languages' => count($this->supported_languages),
            'loaded_languages' => count($this->translations),
            'total_translations' => 0,
            'language_details' => array()
        );
        
        foreach ($this->supported_languages as $code => $data) {
            $translations = $this->get_language_translations($code);
            $count = count($translations);
            $stats['total_translations'] += $count;
            
            $stats['language_details'][$code] = array(
                'name' => $data['name'],
                'translation_count' => $count,
                'file_size' => file_exists($this->get_translation_file_path($code)) ? 
                             filesize($this->get_translation_file_path($code)) : 0,
                'last_modified' => file_exists($this->get_translation_file_path($code)) ? 
                                 filemtime($this->get_translation_file_path($code)) : 0
            );
        }
        
        return $stats;
    }
    
    // =============================================================================
    // PRIVATE METHODS
    // =============================================================================
    
    /**
     * Get translations directory path
     * 
     * @return string Directory path
     */
    private function get_translations_directory() {
        // Try different possible locations
        $possible_paths = array(
            defined('QCC_PLUGIN_PATH') ? QCC_PLUGIN_PATH . 'includes/translations/' : '',
            dirname(__FILE__) . '/translations/',
            plugin_dir_path(__FILE__) . 'translations/',
        );
        
        foreach ($possible_paths as $path) {
            if (!empty($path) && is_dir($path)) {
                return trailingslashit($path);
            }
        }
        
        // Fallback to same directory as this file
        return dirname(__FILE__) . '/';
    }
    
    /**
     * Get translation file path for specific language
     * 
     * @param string $language Language code
     * @return string File path
     */
    private function get_translation_file_path($language) {
        if (!isset($this->supported_languages[$language])) {
            return '';
        }
        
        return $this->translations_dir . $this->supported_languages[$language]['file'];
    }
    
    /**
     * Load translations for specific language from file
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    private function load_language_translations($language) {
        if (!$this->is_supported($language)) {
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log_error("Attempt to load unsupported language: {$language}");
            }
            return false;
        }
        
        $translations = $this->load_translation_file($language);
        
        if (is_array($translations)) {
            $this->translations[$language] = $translations;
            
            if (class_exists('QCC_Debug_Logger')) {
                $count = count($translations);
                QCC_Debug_Logger::log("Loaded {$count} translations for language: {$language}", 'TRANSLATOR');
            }
            
            return true;
        }
        
        // Fallback: set empty array to prevent repeated loading attempts
        $this->translations[$language] = array();
        
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log_error("Failed to load translations for language: {$language}");
        }
        
        return false;
    }
    
    /**
     * Load translation file and return translations array
     * 
     * @param string $language Language code
     * @return array|false Translation array or false on failure
     */
    private function load_translation_file($language) {
        $file_path = $this->get_translation_file_path($language);
        
        if (!file_exists($file_path)) {
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log_error("Translation file not found: {$file_path}");
            }
            return false;
        }
        
        try {
            // Include file and capture returned array
            $translations = include $file_path;
            
            if (!is_array($translations)) {
                if (class_exists('QCC_Debug_Logger')) {
                    QCC_Debug_Logger::log_error("Translation file did not return an array: {$file_path}");
                }
                return false;
            }
            
            // Apply filters to allow customization
            if (has_filter('qcc_translations_' . $language)) {
                $translations = apply_filters('qcc_translations_' . $language, $translations);
            }
            
            return $translations;
            
        } catch (Exception $e) {
            if (class_exists('QCC_Debug_Logger')) {
                QCC_Debug_Logger::log_error("Error loading translation file {$file_path}: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get fallback translation (try English, then formatting)
     * 
     * @param string $key Translation key
     * @param string $requested_language Originally requested language
     * @return string|null Fallback translation or null
     */
    private function get_fallback_translation($key, $requested_language) {
        // If not English, try English as fallback
        if ($requested_language !== 'en') {
            if (!isset($this->translations['en'])) {
                $this->load_language_translations('en');
            }
            
            if (isset($this->translations['en'][$key])) {
                if (class_exists('QCC_Debug_Logger')) {
                    QCC_Debug_Logger::log("Using English fallback for key '{$key}' in language '{$requested_language}'", 'TRANSLATOR');
                }
                return $this->translations['en'][$key];
            }
        }
        
        return null;
    }
    
    /**
     * Get default language from WordPress settings
     * 
     * @return string Default language code
     */
    private function get_default_language() {
        // Try QCC-specific setting first
        if (function_exists('qcc_get_option')) {
            $default = qcc_get_option('default_language');
            if ($default && $this->is_supported($default)) {
                return $default;
            }
        }
        
        // Try WordPress locale
        $wp_locale = get_locale();
        $wp_lang = substr($wp_locale, 0, 2);
        
        if ($this->is_supported($wp_lang)) {
            return $wp_lang;
        }
        
        // Default fallback
        return 'en';
    }
    
    /**
     * Format fallback key when translation is missing
     * 
     * @param string $key Translation key
     * @return string Formatted fallback text
     */
    private function format_fallback_key($key) {
        $formatted = ucfirst(str_replace(array('-', '_'), ' ', $key));
        
        if (class_exists('QCC_Debug_Logger')) {
            QCC_Debug_Logger::log_warning("Using formatted fallback for missing translation key: {$key} -> {$formatted}");
        }
        
        return $formatted;
    }
    
    /**
     * Get status text for language
     * 
     * @param string $language_code Language code
     * @param bool $file_exists Whether translation file exists
     * @param int $translations_count Number of translations
     * @return string Status text
     */
    private function get_status_text($language_code, $file_exists, $translations_count) {
        if (!$file_exists) {
            return '✗ Missing File';
        }
        
        if ($translations_count === 0) {
            return '⚠ Empty File';
        }
        
        if ($translations_count < 50) {
            return '⚠ Incomplete';
        }
        
        $status_texts = array(
            'en' => '✓ Complete',
            'de' => '✓ Vollständig',
            'fr' => '✓ Complet',
            'es' => '✓ Completo',
            'zh' => '✓ 完整'
        );
        
        return $status_texts[$language_code] ?? '✓ Complete';
    }
}

// =============================================================================
// GLOBAL HELPER FUNCTIONS FOR BACKWARD COMPATIBILITY
// =============================================================================

/**
 * Global translation function - creates translator instance if needed
 * 
 * @param string $key Translation key
 * @param string $language Optional language override
 * @param array $replacements Optional placeholder replacements
 * @return string Translated text
 */
if (!function_exists('qcc_translate')) {
    function qcc_translate($key, $language = null, $replacements = array()) {
        static $translator = null;
        
        if ($translator === null) {
            $translator = new QCC_Translator($language);
        }
        
        // Update language if different
        if ($language && $translator->get_language() !== $language) {
            $translator->set_language($language);
        }
        
        return $translator->get($key, $language, $replacements);
    }
}

/**
 * Get QCC translator instance
 * 
 * @param string $language Optional language override
 * @return QCC_Translator
 */
if (!function_exists('qcc_get_translator')) {
    function qcc_get_translator($language = null) {
        static $translator = null;
        
        if ($translator === null || ($language && $translator->get_language() !== $language)) {
            $translator = new QCC_Translator($language);
        }
        
        return $translator;
    }
}

/**
 * Format number for QCC display
 * 
 * @param float $number Number to format
 * @param int $decimals Number of decimal places
 * @param string $language Language code
 * @return string Formatted number
 */
if (!function_exists('qcc_format_number')) {
    function qcc_format_number($number, $decimals = 2, $language = null) {
        $translator = qcc_get_translator($language);
        return $translator->format_number($number, $decimals, $language);
    }
}

/**
 * Format currency for QCC display
 * 
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @param int $unit Unit multiplier
 * @param string $language Language code
 * @return string Formatted currency
 */
if (!function_exists('qcc_format_currency')) {
    function qcc_format_currency($amount, $currency = 'EUR', $unit = 1000000, $language = null) {
        $translator = qcc_get_translator($language);
        return $translator->format_currency($amount, $currency, $unit, $language);
    }
}

/**
 * Check if translation file exists for language
 * 
 * @param string $language Language code
 * @return bool File exists status
 */
if (!function_exists('qcc_translation_file_exists')) {
    function qcc_translation_file_exists($language) {
        $translator = qcc_get_translator();
        $status = $translator->get_language_status();
        
        foreach ($status as $lang_status) {
            if ($lang_status['code'] === $language) {
                return $lang_status['file_exists'];
            }
        }
        
        return false;
    }
}

/**
 * Get translation file path for language
 * 
 * @param string $language Language code  
 * @return string File path
 */
if (!function_exists('qcc_get_translation_file_path')) {
    function qcc_get_translation_file_path($language) {
        $translator = qcc_get_translator();
        return $translator->get_translation_file_path($language);
    }
}

if (class_exists('QCC_Debug_Logger')) {
    QCC_Debug_Logger::log('QCC_Translator class loaded with separate translation files support', 'TRANSLATOR');
}

?>
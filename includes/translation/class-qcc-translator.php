<?php
/**
 * QCC Translator - Main Translation Service
 * 
 * Central translation management for the Quality Cost Calculator plugin.
 * Handles multi-language support with dynamic loading and caching.
 * 
 * @package QualityCostCalculator
 * @subpackage Translation
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Translator Class
 * 
 * Manages all translations with dynamic language loading,
 * fallback mechanisms, and caching for optimal performance.
 */
class QCC_Translator {
    
    /**
     * Current language code
     * 
     * @var string
     */
    private $current_language = 'en';
    
    /**
     * Loaded translations cache
     * 
     * @var array
     */
    private $translations = array();
    
    /**
     * Language instances
     * 
     * @var array
     */
    private $language_instances = array();
    
    /**
     * Supported languages configuration
     * 
     * @var array
     */
    private $supported_languages = array(
        'en' => array(
            'name' => 'English',
            'native_name' => 'English',
            'class' => 'QCC_English_Translations',
            'flag' => '🇺🇸',
            'rtl' => false,
            'completion' => 100
        ),
        'de' => array(
            'name' => 'German',
            'native_name' => 'Deutsch',
            'class' => 'QCC_German_Translations',
            'flag' => '🇩🇪',
            'rtl' => false,
            'completion' => 100
        ),
        'fr' => array(
            'name' => 'French',
            'native_name' => 'Français',
            'class' => 'QCC_French_Translations',
            'flag' => '🇫🇷',
            'rtl' => false,
            'completion' => 100
        ),
        'es' => array(
            'name' => 'Spanish',
            'native_name' => 'Español',
            'class' => 'QCC_Spanish_Translations',
            'flag' => '🇪🇸',
            'rtl' => false,
            'completion' => 100
        ),
        'zh' => array(
            'name' => 'Chinese',
            'native_name' => '中文',
            'class' => 'QCC_Chinese_Translations',
            'flag' => '🇨🇳',
            'rtl' => false,
            'completion' => 90
        )
    );
    
    /**
     * Language-specific formatting rules
     * 
     * @var array
     */
    private $formatting_rules = array(
        'en' => array(
            'decimal' => '.',
            'thousands' => ',',
            'currency_position' => 'before',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s'
        ),
        'de' => array(
            'decimal' => ',',
            'thousands' => '.',
            'currency_position' => 'after',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i'
        ),
        'fr' => array(
            'decimal' => ',',
            'thousands' => ' ',
            'currency_position' => 'after',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ),
        'es' => array(
            'decimal' => ',',
            'thousands' => '.',
            'currency_position' => 'after',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ),
        'zh' => array(
            'decimal' => '.',
            'thousands' => ',',
            'currency_position' => 'before',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s'
        )
    );
    
    /**
     * Translation cache duration
     * 
     * @var int
     */
    private $cache_duration = 3600; // 1 hour
    
    /**
     * Debug mode flag
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     * 
     * @param string|null $language Initial language
     */
    public function __construct($language = null) {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Set initial language
        $this->set_language($language ?: $this->detect_language());
        
        // Initialize hooks
        $this->init_hooks();
        
        if ($this->debug_mode) {
            error_log("QCC Translator initialized with language: {$this->current_language}");
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_filter('qcc_translate', array($this, 'translate'), 10, 3);
        add_filter('qcc_format_localized', array($this, 'format_localized'), 10, 3);
    }
    
    /**
     * Load WordPress textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'quality-cost-calculator',
            false,
            plugin_basename(dirname(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Set current language
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    public function set_language($language) {
        if (!$this->is_supported_language($language)) {
            if ($this->debug_mode) {
                error_log("QCC Translator: Unsupported language '{$language}', falling back to English");
            }
            $language = 'en';
        }
        
        $this->current_language = $language;
        
        // Load translations for this language
        $this->load_language_translations($language);
        
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
     * Translate a text string
     * 
     * @param string $key Translation key
     * @param string $default Default text if translation not found
     * @param array $variables Variables for placeholder replacement
     * @return string Translated text
     */
    public function translate($key, $default = '', $variables = array()) {
        // Get translation from cache or load it
        $translation = $this->get_translation($key, $this->current_language);
        
        // Fallback to default or key if no translation found
        if (!$translation) {
            $translation = $default ?: $this->format_fallback_key($key);
        }
        
        // Replace variables if provided
        if (!empty($variables)) {
            $translation = $this->replace_variables($translation, $variables);
        }
        
        return $translation;
    }
    
    /**
     * Get translation for specific key and language
     * 
     * @param string $key Translation key
     * @param string $language Language code
     * @return string|null Translation or null if not found
     */
    public function get_translation($key, $language = null) {
        $lang = $language ?: $this->current_language;
        
        // Ensure translations are loaded for this language
        if (!isset($this->translations[$lang])) {
            $this->load_language_translations($lang);
        }
        
        // Return translation if exists
        if (isset($this->translations[$lang][$key])) {
            return $this->translations[$lang][$key];
        }
        
        // Try fallback to English if not current language
        if ($lang !== 'en') {
            if (!isset($this->translations['en'])) {
                $this->load_language_translations('en');
            }
            
            if (isset($this->translations['en'][$key])) {
                if ($this->debug_mode) {
                    error_log("QCC Translator: Using English fallback for key '{$key}' in language '{$lang}'");
                }
                return $this->translations['en'][$key];
            }
        }
        
        return null;
    }
    
    /**
     * Translate with pluralization
     * 
     * @param string $singular_key Singular form key
     * @param string $plural_key Plural form key
     * @param int $count Count for determining form
     * @param array $variables Variables for replacement
     * @return string Translated text
     */
    public function translate_plural($singular_key, $plural_key, $count, $variables = array()) {
        $key = $this->determine_plural_form($count) ? $plural_key : $singular_key;
        
        // Add count to variables
        $variables['count'] = $count;
        
        return $this->translate($key, '', $variables);
    }
    
    /**
     * Format value according to language rules
     * 
     * @param mixed $value Value to format
     * @param string $type Format type (number, currency, date, percentage)
     * @param array $options Additional formatting options
     * @return string Formatted value
     */
    public function format_localized($value, $type, $options = array()) {
        $rules = $this->get_formatting_rules($this->current_language);
        
        switch ($type) {
            case 'number':
                return $this->format_number($value, $rules, $options);
                
            case 'currency':
                return $this->format_currency($value, $rules, $options);
                
            case 'percentage':
                return $this->format_percentage($value, $rules, $options);
                
            case 'date':
                return $this->format_date($value, $rules, $options);
                
            case 'time':
                return $this->format_time($value, $rules, $options);
                
            default:
                return $value;
        }
    }
    
    /**
     * Get all supported languages
     * 
     * @return array Supported languages with metadata
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
    
    /**
     * Check if language is supported
     * 
     * @param string $language Language code
     * @return bool Is supported
     */
    public function is_supported_language($language) {
        return isset($this->supported_languages[$language]);
    }
    
    /**
     * Get language metadata
     * 
     * @param string $language Language code
     * @return array|null Language metadata
     */
    public function get_language_info($language) {
        return $this->supported_languages[$language] ?? null;
    }
    
    /**
     * Get text direction for current language
     * 
     * @return string 'ltr' or 'rtl'
     */
    public function get_text_direction() {
        $language_info = $this->get_language_info($this->current_language);
        return $language_info['rtl'] ? 'rtl' : 'ltr';
    }
    
    /**
     * Export translations for backup or migration
     * 
     * @param string|null $language Specific language or null for all
     * @return array Translation data
     */
    public function export_translations($language = null) {
        if ($language) {
            return array(
                $language => $this->get_all_translations($language)
            );
        }
        
        $export = array();
        foreach (array_keys($this->supported_languages) as $lang) {
            $export[$lang] = $this->get_all_translations($lang);
        }
        
        return $export;
    }
    
    /**
     * Import translations from backup
     * 
     * @param array $translations Translation data
     * @return bool Success status
     */
    public function import_translations($translations) {
        try {
            foreach ($translations as $language => $lang_translations) {
                if ($this->is_supported_language($language)) {
                    $this->translations[$language] = $lang_translations;
                    
                    // Cache the translations
                    $this->cache_translations($language, $lang_translations);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Translator: Import failed - " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get translation statistics
     * 
     * @return array Translation statistics
     */
    public function get_translation_stats() {
        $stats = array(
            'current_language' => $this->current_language,
            'loaded_languages' => array_keys($this->translations),
            'language_completion' => array()
        );
        
        foreach ($this->supported_languages as $lang => $info) {
            $stats['language_completion'][$lang] = $info['completion'];
        }
        
        return $stats;
    }
    
    /**
     * Private helper methods
     */
    
    /**
     * Detect user language
     * 
     * @return string Detected language code
     */
    private function detect_language() {
        // Check user preference
        if (is_user_logged_in()) {
            $user_lang = get_user_meta(get_current_user_id(), 'qcc_language', true);
            if ($user_lang && $this->is_supported_language($user_lang)) {
                return $user_lang;
            }
        }
        
        // Check site language
        $site_locale = get_locale();
        $site_lang = substr($site_locale, 0, 2);
        
        if ($this->is_supported_language($site_lang)) {
            return $site_lang;
        }
        
        // Check browser language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browser_langs as $browser_lang) {
                $lang = substr(trim($browser_lang), 0, 2);
                if ($this->is_supported_language($lang)) {
                    return $lang;
                }
            }
        }
        
        // Default to English
        return 'en';
    }
    
    /**
     * Load translations for specific language
     * 
     * @param string $language Language code
     */
    private function load_language_translations($language) {
        if (isset($this->translations[$language])) {
            return; // Already loaded
        }
        
        // Try to load from cache first
        $cached_translations = $this->get_cached_translations($language);
        if ($cached_translations !== false) {
            $this->translations[$language] = $cached_translations;
            return;
        }
        
        // Load from language class
        $language_info = $this->get_language_info($language);
        if (!$language_info) {
            return;
        }
        
        $class_name = $language_info['class'];
        
        // Load the language class if not already loaded
        if (!class_exists($class_name)) {
            $file_path = $this->get_language_file_path($language);
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Instantiate language class and get translations
        if (class_exists($class_name)) {
            if (!isset($this->language_instances[$language])) {
                $this->language_instances[$language] = new $class_name();
            }
            
            $translations = $this->language_instances[$language]->get_translations();
            $this->translations[$language] = $translations;
            
            // Cache the translations
            $this->cache_translations($language, $translations);
            
            if ($this->debug_mode) {
                error_log("QCC Translator: Loaded " . count($translations) . " translations for language '{$language}'");
            }
        } else {
            if ($this->debug_mode) {
                error_log("QCC Translator: Language class '{$class_name}' not found");
            }
            
            // Set empty array to prevent repeated loading attempts
            $this->translations[$language] = array();
        }
    }
    
    /**
     * Get file path for language class
     * 
     * @param string $language Language code
     * @return string File path
     */
    private function get_language_file_path($language) {
        $language_info = $this->get_language_info($language);
        if (!$language_info) {
            return '';
        }
        
        $class_name = strtolower(str_replace('QCC_', '', $language_info['class']));
        $filename = 'class-qcc-' . str_replace('_', '-', $class_name) . '.php';
        
        return plugin_dir_path(__FILE__) . 'languages/' . $filename;
    }
    
    /**
     * Get all translations for a language
     * 
     * @param string $language Language code
     * @return array All translations
     */
    private function get_all_translations($language) {
        if (!isset($this->translations[$language])) {
            $this->load_language_translations($language);
        }
        
        return $this->translations[$language] ?? array();
    }
    
    /**
     * Replace variables in translation string
     * 
     * @param string $text Text with placeholders
     * @param array $variables Variables to replace
     * @return string Text with replaced variables
     */
    private function replace_variables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        
        return $text;
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
     * Determine plural form based on count
     * 
     * @param int $count Count value
     * @return bool True if plural form should be used
     */
    private function determine_plural_form($count) {
        // Simple English pluralization rule
        // Can be extended for other languages with different rules
        return $count !== 1;
    }
    
    /**
     * Get formatting rules for language
     * 
     * @param string $language Language code
     * @return array Formatting rules
     */
    private function get_formatting_rules($language) {
        return $this->formatting_rules[$language] ?? $this->formatting_rules['en'];
    }
    
    /**
     * Format number according to language rules
     * 
     * @param float $value Number value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted number
     */
    private function format_number($value, $rules, $options = array()) {
        $decimals = $options['decimals'] ?? 2;
        
        return number_format(
            $value,
            $decimals,
            $rules['decimal'],
            $rules['thousands']
        );
    }
    
    /**
     * Format currency according to language rules
     * 
     * @param float $value Currency value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted currency
     */
    private function format_currency($value, $rules, $options = array()) {
        $currency = $options['currency'] ?? 'USD';
        $symbol = $this->get_currency_symbol($currency);
        $formatted_number = $this->format_number($value, $rules, $options);
        
        if ($rules['currency_position'] === 'before') {
            return $symbol . $formatted_number;
        } else {
            return $formatted_number . ' ' . $symbol;
        }
    }
    
    /**
     * Format percentage according to language rules
     * 
     * @param float $value Percentage value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted percentage
     */
    private function format_percentage($value, $rules, $options = array()) {
        $formatted_number = $this->format_number($value, $rules, $options);
        return $formatted_number . '%';
    }
    
    /**
     * Format date according to language rules
     * 
     * @param mixed $value Date value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted date
     */
    private function format_date($value, $rules, $options = array()) {
        $format = $options['format'] ?? $rules['date_format'];
        
        if (is_string($value)) {
            $value = strtotime($value);
        }
        
        return date($format, $value);
    }
    
    /**
     * Format time according to language rules
     * 
     * @param mixed $value Time value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted time
     */
    private function format_time($value, $rules, $options = array()) {
        $format = $options['format'] ?? $rules['time_format'];
        
        if (is_string($value)) {
            $value = strtotime($value);
        }
        
        return date($format, $value);
    }
    
    /**
     * Get currency symbol
     * 
     * @param string $currency Currency code
     * @return string Currency symbol
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr'
        );
        
        return $symbols[$currency] ?? $currency;
    }
    
    /**
     * Cache translations
     * 
     * @param string $language Language code
     * @param array $translations Translations to cache
     */
    private function cache_translations($language, $translations) {
        $cache_key = "qcc_translations_{$language}";
        set_transient($cache_key, $translations, $this->cache_duration);
    }
    
    /**
     * Get cached translations
     * 
     * @param string $language Language code
     * @return array|false Cached translations or false
     */
    private function get_cached_translations($language) {
        $cache_key = "qcc_translations_{$language}";
        return get_transient($cache_key);
    }
    
    /**
     * Clear translation cache
     * 
     * @param string|null $language Specific language or null for all
     */
    public function clear_cache($language = null) {
        if ($language) {
            delete_transient("qcc_translations_{$language}");
        } else {
            foreach (array_keys($this->supported_languages) as $lang) {
                delete_transient("qcc_translations_{$lang}");
            }
        }
    }
}<?php
/**
 * QCC Translator - Main Translation Service
 * 
 * Central translation management for the Quality Cost Calculator plugin.
 * Handles multi-language support with dynamic loading and caching.
 * 
 * @package QualityCostCalculator
 * @subpackage Translation
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Translator Class
 * 
 * Manages all translations with dynamic language loading,
 * fallback mechanisms, and caching for optimal performance.
 */
class QCC_Translator {
    
    /**
     * Current language code
     * 
     * @var string
     */
    private $current_language = 'en';
    
    /**
     * Loaded translations cache
     * 
     * @var array
     */
    private $translations = array();
    
    /**
     * Language instances
     * 
     * @var array
     */
    private $language_instances = array();
    
    /**
     * Supported languages configuration
     * 
     * @var array
     */
    private $supported_languages = array(
        'en' => array(
            'name' => 'English',
            'native_name' => 'English',
            'class' => 'QCC_English_Translations',
            'flag' => '🇺🇸',
            'rtl' => false,
            'completion' => 100
        ),
        'de' => array(
            'name' => 'German',
            'native_name' => 'Deutsch',
            'class' => 'QCC_German_Translations',
            'flag' => '🇩🇪',
            'rtl' => false,
            'completion' => 100
        ),
        'fr' => array(
            'name' => 'French',
            'native_name' => 'Français',
            'class' => 'QCC_French_Translations',
            'flag' => '🇫🇷',
            'rtl' => false,
            'completion' => 100
        ),
        'es' => array(
            'name' => 'Spanish',
            'native_name' => 'Español',
            'class' => 'QCC_Spanish_Translations',
            'flag' => '🇪🇸',
            'rtl' => false,
            'completion' => 100
        ),
        'zh' => array(
            'name' => 'Chinese',
            'native_name' => '中文',
            'class' => 'QCC_Chinese_Translations',
            'flag' => '🇨🇳',
            'rtl' => false,
            'completion' => 90
        )
    );
    
    /**
     * Language-specific formatting rules
     * 
     * @var array
     */
    private $formatting_rules = array(
        'en' => array(
            'decimal' => '.',
            'thousands' => ',',
            'currency_position' => 'before',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s'
        ),
        'de' => array(
            'decimal' => ',',
            'thousands' => '.',
            'currency_position' => 'after',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i'
        ),
        'fr' => array(
            'decimal' => ',',
            'thousands' => ' ',
            'currency_position' => 'after',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ),
        'es' => array(
            'decimal' => ',',
            'thousands' => '.',
            'currency_position' => 'after',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        ),
        'zh' => array(
            'decimal' => '.',
            'thousands' => ',',
            'currency_position' => 'before',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s'
        )
    );
    
    /**
     * Translation cache duration
     * 
     * @var int
     */
    private $cache_duration = 3600; // 1 hour
    
    /**
     * Debug mode flag
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     * 
     * @param string|null $language Initial language
     */
    public function __construct($language = null) {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Set initial language
        $this->set_language($language ?: $this->detect_language());
        
        // Initialize hooks
        $this->init_hooks();
        
        if ($this->debug_mode) {
            error_log("QCC Translator initialized with language: {$this->current_language}");
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_filter('qcc_translate', array($this, 'translate'), 10, 3);
        add_filter('qcc_format_localized', array($this, 'format_localized'), 10, 3);
    }
    
    /**
     * Load WordPress textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'quality-cost-calculator',
            false,
            plugin_basename(dirname(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Set current language
     * 
     * @param string $language Language code
     * @return bool Success status
     */
    public function set_language($language) {
        if (!$this->is_supported_language($language)) {
            if ($this->debug_mode) {
                error_log("QCC Translator: Unsupported language '{$language}', falling back to English");
            }
            $language = 'en';
        }
        
        $this->current_language = $language;
        
        // Load translations for this language
        $this->load_language_translations($language);
        
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
     * Translate a text string
     * 
     * @param string $key Translation key
     * @param string $default Default text if translation not found
     * @param array $variables Variables for placeholder replacement
     * @return string Translated text
     */
    public function translate($key, $default = '', $variables = array()) {
        // Get translation from cache or load it
        $translation = $this->get_translation($key, $this->current_language);
        
        // Fallback to default or key if no translation found
        if (!$translation) {
            $translation = $default ?: $this->format_fallback_key($key);
        }
        
        // Replace variables if provided
        if (!empty($variables)) {
            $translation = $this->replace_variables($translation, $variables);
        }
        
        return $translation;
    }
    
    /**
     * Get translation for specific key and language
     * 
     * @param string $key Translation key
     * @param string $language Language code
     * @return string|null Translation or null if not found
     */
    public function get_translation($key, $language = null) {
        $lang = $language ?: $this->current_language;
        
        // Ensure translations are loaded for this language
        if (!isset($this->translations[$lang])) {
            $this->load_language_translations($lang);
        }
        
        // Return translation if exists
        if (isset($this->translations[$lang][$key])) {
            return $this->translations[$lang][$key];
        }
        
        // Try fallback to English if not current language
        if ($lang !== 'en') {
            if (!isset($this->translations['en'])) {
                $this->load_language_translations('en');
            }
            
            if (isset($this->translations['en'][$key])) {
                if ($this->debug_mode) {
                    error_log("QCC Translator: Using English fallback for key '{$key}' in language '{$lang}'");
                }
                return $this->translations['en'][$key];
            }
        }
        
        return null;
    }
    
    /**
     * Translate with pluralization
     * 
     * @param string $singular_key Singular form key
     * @param string $plural_key Plural form key
     * @param int $count Count for determining form
     * @param array $variables Variables for replacement
     * @return string Translated text
     */
    public function translate_plural($singular_key, $plural_key, $count, $variables = array()) {
        $key = $this->determine_plural_form($count) ? $plural_key : $singular_key;
        
        // Add count to variables
        $variables['count'] = $count;
        
        return $this->translate($key, '', $variables);
    }
    
    /**
     * Format value according to language rules
     * 
     * @param mixed $value Value to format
     * @param string $type Format type (number, currency, date, percentage)
     * @param array $options Additional formatting options
     * @return string Formatted value
     */
    public function format_localized($value, $type, $options = array()) {
        $rules = $this->get_formatting_rules($this->current_language);
        
        switch ($type) {
            case 'number':
                return $this->format_number($value, $rules, $options);
                
            case 'currency':
                return $this->format_currency($value, $rules, $options);
                
            case 'percentage':
                return $this->format_percentage($value, $rules, $options);
                
            case 'date':
                return $this->format_date($value, $rules, $options);
                
            case 'time':
                return $this->format_time($value, $rules, $options);
                
            default:
                return $value;
        }
    }
    
    /**
     * Get all supported languages
     * 
     * @return array Supported languages with metadata
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
    
    /**
     * Check if language is supported
     * 
     * @param string $language Language code
     * @return bool Is supported
     */
    public function is_supported_language($language) {
        return isset($this->supported_languages[$language]);
    }
    
    /**
     * Get language metadata
     * 
     * @param string $language Language code
     * @return array|null Language metadata
     */
    public function get_language_info($language) {
        return $this->supported_languages[$language] ?? null;
    }
    
    /**
     * Get text direction for current language
     * 
     * @return string 'ltr' or 'rtl'
     */
    public function get_text_direction() {
        $language_info = $this->get_language_info($this->current_language);
        return $language_info['rtl'] ? 'rtl' : 'ltr';
    }
    
    /**
     * Export translations for backup or migration
     * 
     * @param string|null $language Specific language or null for all
     * @return array Translation data
     */
    public function export_translations($language = null) {
        if ($language) {
            return array(
                $language => $this->get_all_translations($language)
            );
        }
        
        $export = array();
        foreach (array_keys($this->supported_languages) as $lang) {
            $export[$lang] = $this->get_all_translations($lang);
        }
        
        return $export;
    }
    
    /**
     * Import translations from backup
     * 
     * @param array $translations Translation data
     * @return bool Success status
     */
    public function import_translations($translations) {
        try {
            foreach ($translations as $language => $lang_translations) {
                if ($this->is_supported_language($language)) {
                    $this->translations[$language] = $lang_translations;
                    
                    // Cache the translations
                    $this->cache_translations($language, $lang_translations);
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Translator: Import failed - " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get translation statistics
     * 
     * @return array Translation statistics
     */
    public function get_translation_stats() {
        $stats = array(
            'current_language' => $this->current_language,
            'loaded_languages' => array_keys($this->translations),
            'language_completion' => array()
        );
        
        foreach ($this->supported_languages as $lang => $info) {
            $stats['language_completion'][$lang] = $info['completion'];
        }
        
        return $stats;
    }
    
    /**
     * Private helper methods
     */
    
    /**
     * Detect user language
     * 
     * @return string Detected language code
     */
    private function detect_language() {
        // Check user preference
        if (is_user_logged_in()) {
            $user_lang = get_user_meta(get_current_user_id(), 'qcc_language', true);
            if ($user_lang && $this->is_supported_language($user_lang)) {
                return $user_lang;
            }
        }
        
        // Check site language
        $site_locale = get_locale();
        $site_lang = substr($site_locale, 0, 2);
        
        if ($this->is_supported_language($site_lang)) {
            return $site_lang;
        }
        
        // Check browser language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browser_langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browser_langs as $browser_lang) {
                $lang = substr(trim($browser_lang), 0, 2);
                if ($this->is_supported_language($lang)) {
                    return $lang;
                }
            }
        }
        
        // Default to English
        return 'en';
    }
    
    /**
     * Load translations for specific language
     * 
     * @param string $language Language code
     */
    private function load_language_translations($language) {
        if (isset($this->translations[$language])) {
            return; // Already loaded
        }
        
        // Try to load from cache first
        $cached_translations = $this->get_cached_translations($language);
        if ($cached_translations !== false) {
            $this->translations[$language] = $cached_translations;
            return;
        }
        
        // Load from language class
        $language_info = $this->get_language_info($language);
        if (!$language_info) {
            return;
        }
        
        $class_name = $language_info['class'];
        
        // Load the language class if not already loaded
        if (!class_exists($class_name)) {
            $file_path = $this->get_language_file_path($language);
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Instantiate language class and get translations
        if (class_exists($class_name)) {
            if (!isset($this->language_instances[$language])) {
                $this->language_instances[$language] = new $class_name();
            }
            
            $translations = $this->language_instances[$language]->get_translations();
            $this->translations[$language] = $translations;
            
            // Cache the translations
            $this->cache_translations($language, $translations);
            
            if ($this->debug_mode) {
                error_log("QCC Translator: Loaded " . count($translations) . " translations for language '{$language}'");
            }
        } else {
            if ($this->debug_mode) {
                error_log("QCC Translator: Language class '{$class_name}' not found");
            }
            
            // Set empty array to prevent repeated loading attempts
            $this->translations[$language] = array();
        }
    }
    
    /**
     * Get file path for language class
     * 
     * @param string $language Language code
     * @return string File path
     */
    private function get_language_file_path($language) {
        $language_info = $this->get_language_info($language);
        if (!$language_info) {
            return '';
        }
        
        $class_name = strtolower(str_replace('QCC_', '', $language_info['class']));
        $filename = 'class-qcc-' . str_replace('_', '-', $class_name) . '.php';
        
        return plugin_dir_path(__FILE__) . 'languages/' . $filename;
    }
    
    /**
     * Get all translations for a language
     * 
     * @param string $language Language code
     * @return array All translations
     */
    private function get_all_translations($language) {
        if (!isset($this->translations[$language])) {
            $this->load_language_translations($language);
        }
        
        return $this->translations[$language] ?? array();
    }
    
    /**
     * Replace variables in translation string
     * 
     * @param string $text Text with placeholders
     * @param array $variables Variables to replace
     * @return string Text with replaced variables
     */
    private function replace_variables($text, $variables) {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        
        return $text;
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
     * Determine plural form based on count
     * 
     * @param int $count Count value
     * @return bool True if plural form should be used
     */
    private function determine_plural_form($count) {
        // Simple English pluralization rule
        // Can be extended for other languages with different rules
        return $count !== 1;
    }
    
    /**
     * Get formatting rules for language
     * 
     * @param string $language Language code
     * @return array Formatting rules
     */
    private function get_formatting_rules($language) {
        return $this->formatting_rules[$language] ?? $this->formatting_rules['en'];
    }
    
    /**
     * Format number according to language rules
     * 
     * @param float $value Number value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted number
     */
    private function format_number($value, $rules, $options = array()) {
        $decimals = $options['decimals'] ?? 2;
        
        return number_format(
            $value,
            $decimals,
            $rules['decimal'],
            $rules['thousands']
        );
    }
    
    /**
     * Format currency according to language rules
     * 
     * @param float $value Currency value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted currency
     */
    private function format_currency($value, $rules, $options = array()) {
        $currency = $options['currency'] ?? 'USD';
        $symbol = $this->get_currency_symbol($currency);
        $formatted_number = $this->format_number($value, $rules, $options);
        
        if ($rules['currency_position'] === 'before') {
            return $symbol . $formatted_number;
        } else {
            return $formatted_number . ' ' . $symbol;
        }
    }
    
    /**
     * Format percentage according to language rules
     * 
     * @param float $value Percentage value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted percentage
     */
    private function format_percentage($value, $rules, $options = array()) {
        $formatted_number = $this->format_number($value, $rules, $options);
        return $formatted_number . '%';
    }
    
    /**
     * Format date according to language rules
     * 
     * @param mixed $value Date value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted date
     */
    private function format_date($value, $rules, $options = array()) {
        $format = $options['format'] ?? $rules['date_format'];
        
        if (is_string($value)) {
            $value = strtotime($value);
        }
        
        return date($format, $value);
    }
    
    /**
     * Format time according to language rules
     * 
     * @param mixed $value Time value
     * @param array $rules Formatting rules
     * @param array $options Additional options
     * @return string Formatted time
     */
    private function format_time($value, $rules, $options = array()) {
        $format = $options['format'] ?? $rules['time_format'];
        
        if (is_string($value)) {
            $value = strtotime($value);
        }
        
        return date($format, $value);
    }
    
    /**
     * Get currency symbol
     * 
     * @param string $currency Currency code
     * @return string Currency symbol
     */
    private function get_currency_symbol($currency) {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr'
        );
        
        return $symbols[$currency] ?? $currency;
    }
    
    /**
     * Cache translations
     * 
     * @param string $language Language code
     * @param array $translations Translations to cache
     */
    private function cache_translations($language, $translations) {
        $cache_key = "qcc_translations_{$language}";
        set_transient($cache_key, $translations, $this->cache_duration);
    }
    
    /**
     * Get cached translations
     * 
     * @param string $language Language code
     * @return array|false Cached translations or false
     */
    private function get_cached_translations($language) {
        $cache_key = "qcc_translations_{$language}";
        return get_transient($cache_key);
    }
    
    /**
     * Clear translation cache
     * 
     * @param string|null $language Specific language or null for all
     */
    public function clear_cache($language = null) {
        if ($language) {
            delete_transient("qcc_translations_{$language}");
        } else {
            foreach (array_keys($this->supported_languages) as $lang) {
                delete_transient("qcc_translations_{$lang}");
            }
        }
    }
}
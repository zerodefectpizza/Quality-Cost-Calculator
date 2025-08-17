<?php
/**
 * Internationalization functionality for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * i18n Class
 */
class QCC_I18n {
    
    /**
     * Supported languages
     */
    private $supported_languages = array(
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'zh' => '中文'
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        // Constructor can be empty for now
    }
    
    /**
     * Set default language based on WordPress locale
     */
    public function set_default_language() {
        if (!get_option('qcc_default_language')) {
            $wp_locale = get_locale();
            $default_language = $this->detect_language_from_locale($wp_locale);
            update_option('qcc_default_language', $default_language);
        }
    }
    
    /**
     * Detect language from WordPress locale
     */
    private function detect_language_from_locale($locale) {
        if (strpos($locale, 'de') === 0) {
            return 'de';
        } elseif (strpos($locale, 'fr') === 0) {
            return 'fr';
        } elseif (strpos($locale, 'zh') === 0) {
            return 'zh';
        }
        
        return 'en'; // Default to English
    }
    
    /**
     * Get supported languages
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }
    
    /**
     * Check if language is supported
     */
    public function is_language_supported($language) {
        return array_key_exists($language, $this->supported_languages);
    }
    
    /**
     * Get language name
     */
    public function get_language_name($language_code) {
        return isset($this->supported_languages[$language_code]) 
            ? $this->supported_languages[$language_code] 
            : 'Unknown';
    }
    
    /**
     * Get current language
     */
    public function get_current_language() {
        return get_option('qcc_default_language', 'en');
    }
    
    /**
     * Set current language
     */
    public function set_current_language($language) {
        if ($this->is_language_supported($language)) {
            update_option('qcc_default_language', $language);
            return true;
        }
        return false;
    }
    
    /**
     * Get language status for admin display
     */
    public function get_language_status() {
        $status = array();
        
        foreach ($this->supported_languages as $code => $name) {
            $status[] = array(
                'code' => $code,
                'name' => $name,
                'native_name' => $name,
                'status' => 'complete',
                'status_text' => $this->get_status_text($code)
            );
        }
        
        return $status;
    }
    
    /**
     * Get status text for language
     */
    private function get_status_text($language_code) {
        $status_texts = array(
            'en' => '✓ Complete',
            'de' => '✓ Vollständig',
            'fr' => '✓ Complet',
            'zh' => '✓ 完整'
        );
        
        return isset($status_texts[$language_code]) ? $status_texts[$language_code] : '✓ Complete';
    }
    
    /**
     * Get RTL languages
     */
    public function get_rtl_languages() {
        return array(); // None of our current languages are RTL
    }
    
    /**
     * Check if language is RTL
     */
    public function is_rtl_language($language_code) {
        return in_array($language_code, $this->get_rtl_languages());
    }
    
    /**
     * Get language direction
     */
    public function get_language_direction($language_code) {
        return $this->is_rtl_language($language_code) ? 'rtl' : 'ltr';
    }
    
    /**
     * Format number based on language
     */
    public function format_number($number, $decimals = 2, $language_code = null) {
        if (!$language_code) {
            $language_code = $this->get_current_language();
        }
        
        switch ($language_code) {
            case 'de':
                return number_format($number, $decimals, ',', '.');
            case 'fr':
                return number_format($number, $decimals, ',', ' ');
            case 'zh':
                return number_format($number, $decimals, '.', ',');
            default:
                return number_format($number, $decimals, '.', ',');
        }
    }
    
    /**
     * Get currency symbol position for language
     */
    public function get_currency_position($language_code = null) {
        if (!$language_code) {
            $language_code = $this->get_current_language();
        }
        
        $positions = array(
            'en' => 'before', // $100
            'de' => 'after',  // 100€
            'fr' => 'after',  // 100€
            'zh' => 'before'  // ¥100
        );
        
        return isset($positions[$language_code]) ? $positions[$language_code] : 'before';
    }
    
    /**
     * Format currency based on language
     */
    public function format_currency($amount, $currency_symbol, $language_code = null) {
        if (!$language_code) {
            $language_code = $this->get_current_language();
        }
        
        $formatted_amount = $this->format_number($amount, 2, $language_code);
        $position = $this->get_currency_position($language_code);
        
        if ($position === 'before') {
            return $currency_symbol . $formatted_amount;
        } else {
            return $formatted_amount . $currency_symbol;
        }
    }
}
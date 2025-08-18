<?php
/**
 * QCC CSS Generator - Dynamic CSS Generation
 *
 * @package QualityCostCalculator
 * @subpackage Assets
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_CSS_Generator {
    
    private $bootstrap;
    private $container;
    private $css_cache = array();
    
    public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ?: QCC_Bootstrap::get_instance();
        $this->container = $this->bootstrap ? $this->bootstrap->get_container() : null;
    }
    
    /**
     * Generate dynamic CSS based on settings
     */
    public function generate_dynamic_css($config = array()) {
        $cache_key = md5(serialize($config));
        
        if (isset($this->css_cache[$cache_key])) {
            return $this->css_cache[$cache_key];
        }
        
        $css = '';
        
        // Base CSS
        $css .= $this->get_base_css($config);
        
        // Theme-specific CSS
        $css .= $this->get_theme_css($config);
        
        // Language-specific CSS
        $css .= $this->get_language_css($config);
        
        // Responsive CSS
        $css .= $this->get_responsive_css($config);
        
        // Custom colors
        $css .= $this->get_color_css($config);
        
        $this->css_cache[$cache_key] = $css;
        return $css;
    }
    
    /**
     * Get base CSS
     */
    private function get_base_css($config) {
        return '
        .qcc-calculator {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .qcc-input-group {
            margin-bottom: 1rem;
        }
        
        .qcc-input-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .qcc-input-group input,
        .qcc-input-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .qcc-input-group input:focus,
        .qcc-input-group select:focus {
            outline: none;
            border-color: #449775;
            box-shadow: 0 0 0 2px rgba(68, 151, 117, 0.2);
        }
        
        .qcc-results {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        
        .qcc-error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 0.75rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        ';
    }
    
    /**
     * Get theme-specific CSS
     */
    private function get_theme_css($config) {
        $theme = get_template();
        $css = '';
        
        switch ($theme) {
            case 'astra':
                $css .= '.qcc-calculator { margin: 2rem 0; }';
                break;
            case 'oceanwp':
                $css .= '.qcc-calculator { clear: both; overflow: hidden; }';
                break;
            case 'generatepress':
                $css .= '.qcc-calculator * { box-sizing: border-box; }';
                break;
        }
        
        return $css;
    }
    
    /**
     * Get language-specific CSS
     */
    private function get_language_css($config) {
        $language = $config['language'] ?? 'en';
        $css = '';
        
        // RTL languages
        if (in_array($language, array('ar', 'he', 'ur'))) {
            $css .= '
            .qcc-calculator {
                direction: rtl;
                text-align: right;
            }
            
            .qcc-input-group input,
            .qcc-input-group select {
                text-align: right;
            }
            ';
        }
        
        // Font adjustments for specific languages
        if ($language === 'zh') {
            $css .= '
            .qcc-calculator {
                font-family: "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            }
            ';
        }
        
        return $css;
    }
    
    /**
     * Get responsive CSS
     */
    private function get_responsive_css($config) {
        return '
        @media (max-width: 768px) {
            .qcc-calculator {
                padding: 1rem;
            }
            
            .qcc-controls {
                grid-template-columns: 1fr !important;
            }
            
            .qcc-input-section {
                padding: 1rem;
            }
        }
        
        @media (min-width: 1200px) {
            .qcc-calculator {
                max-width: 1200px;
                margin: 0 auto;
            }
        }
        ';
    }
    
    /**
     * Get color-based CSS
     */
    private function get_color_css($config) {
        $primary_color = $config['primary_color'] ?? '#449775';
        $secondary_color = $config['secondary_color'] ?? '#2c5f47';
        
        return "
        .qcc-calculator .qcc-button-primary {
            background-color: {$primary_color};
            border-color: {$primary_color};
        }
        
        .qcc-calculator .qcc-button-primary:hover {
            background-color: {$secondary_color};
            border-color: {$secondary_color};
        }
        
        .qcc-calculator .qcc-header {
            background: linear-gradient(135deg, {$primary_color}, {$secondary_color});
        }
        ";
    }
    
    /**
     * Generate print CSS
     */
    public function generate_print_css() {
        return '
        @media print {
            .qcc-calculator .qcc-controls,
            .qcc-calculator .qcc-buttons {
                display: none !important;
            }
            
            .qcc-calculator {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
            
            .qcc-calculator .qcc-results {
                page-break-inside: avoid;
            }
        }
        ';
    }
    
    /**
     * Generate minified CSS
     */
    public function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        
        return trim($css);
    }
    
    /**
     * Clear CSS cache
     */
    public function clear_cache() {
        $this->css_cache = array();
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        return array(
            'cached_items' => count($this->css_cache),
            'memory_usage' => memory_get_usage(true)
        );
    }
}
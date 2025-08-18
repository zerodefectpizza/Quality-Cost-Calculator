<?php
/**
 * QCC Asset Manager - Professional Asset Management
 * 
 * Handles CSS, JavaScript, and other assets for the Quality Cost Calculator
 * Integrates with Bootstrap architecture and Service Container
 *
 * @package QualityCostCalculator
 * @subpackage Assets
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset Manager Class for Quality Cost Calculator
 * 
 * Manages frontend and admin assets with intelligent loading,
 * caching, and optimization features
 */
class QCC_Asset_Manager {
    
    /**
     * Bootstrap instance
     * 
     * @var QCC_Bootstrap|null
     */
    private $bootstrap;
    
    /**
     * Service container
     * 
     * @var QCC_Service_Container|null
     */
    private $container;
    
    /**
     * Asset cache
     * 
     * @var array
     */
    private $asset_cache = array();
    
    /**
     * Asset configuration
     * 
     * @var array
     */
    private $config = array();
    
    /**
     * Performance metrics
     * 
     * @var array
     */
    private $metrics = array(
        'assets_loaded' => 0,
        'cache_hits' => 0,
        'load_time' => 0
    );
    
    /**
     * Constructor
     * 
     * @param QCC_Bootstrap $bootstrap Bootstrap instance
     */
    public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ?: QCC_Bootstrap::get_instance();
        $this->container = $this->bootstrap ? $this->bootstrap->get_container() : null;
        
        $this->init_config();
        $this->init_hooks();
        
        if (QCC_DEBUG) {
            error_log('QCC Asset Manager initialized');
        }
    }
    
    /**
     * Initialize asset configuration
     */
    private function init_config() {
        $this->config = array(
            'version' => QCC_PLUGIN_VERSION,
            'cache_busting' => $this->get_feature_flag('enable_cache_busting', true),
            'minification' => $this->get_feature_flag('enable_minification', false),
            'cdn_support' => $this->get_feature_flag('enable_cdn', false),
            'inline_critical' => $this->get_feature_flag('inline_critical_css', false),
            'lazy_loading' => $this->get_feature_flag('lazy_load_assets', true),
            'conditional_loading' => $this->get_feature_flag('conditional_loading', true)
        );
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'), 10);
        add_action('wp_head', array($this, 'add_frontend_meta'), 5);
        add_action('wp_footer', array($this, 'add_footer_scripts'), 25);
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'), 10);
        add_action('admin_head', array($this, 'add_admin_meta'), 5);
        
        // Asset optimization
        add_action('wp_print_styles', array($this, 'optimize_css_delivery'), 99);
        add_action('wp_print_scripts', array($this, 'optimize_js_delivery'), 99);
        
        // Cache management
        add_action('qcc_clear_asset_cache', array($this, 'clear_asset_cache'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        $start_time = microtime(true);
        
        try {
            // Core CSS
            $this->enqueue_frontend_css();
            
            // Core JavaScript
            $this->enqueue_frontend_js();
            
            // Chart.js for visualizations
            $this->enqueue_chart_library();
            
            // Language-specific assets
            $this->enqueue_language_assets();
            
            // Theme compatibility
            $this->enqueue_theme_compatibility();
            
            $this->metrics['load_time'] = microtime(true) - $start_time;
            $this->metrics['assets_loaded']++;
            
            if (QCC_DEBUG) {
                error_log('QCC Frontend assets loaded in ' . round($this->metrics['load_time'] * 1000, 2) . 'ms');
            }
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Asset Manager Error: ' . $e->getMessage());
            }
            
            // Fallback to basic assets
            $this->enqueue_fallback_assets();
        }
    }
    
    /**
     * Enqueue frontend CSS
     */
    private function enqueue_frontend_css() {
        $css_files = $this->get_css_files('frontend');
        
        foreach ($css_files as $handle => $file_info) {
            $url = $this->get_asset_url($file_info['file']);
            $version = $this->get_asset_version($file_info['file']);
            
            wp_enqueue_style(
                $handle,
                $url,
                $file_info['deps'] ?? array(),
                $version,
                $file_info['media'] ?? 'all'
            );
            
            // Add inline CSS if needed
            if (isset($file_info['inline'])) {
                wp_add_inline_style($handle, $file_info['inline']);
            }
        }
        
        // Critical CSS inline
        if ($this->config['inline_critical']) {
            $this->add_critical_css();
        }
    }
    
    /**
     * Enqueue frontend JavaScript
     */
    private function enqueue_frontend_js() {
        $js_files = $this->get_js_files('frontend');
        
        foreach ($js_files as $handle => $file_info) {
            $url = $this->get_asset_url($file_info['file']);
            $version = $this->get_asset_version($file_info['file']);
            
            wp_enqueue_script(
                $handle,
                $url,
                $file_info['deps'] ?? array('jquery'),
                $version,
                $file_info['in_footer'] ?? true
            );
            
            // Localize script with data
            if (isset($file_info['localize'])) {
                wp_localize_script(
                    $handle,
                    $file_info['localize']['name'],
                    $file_info['localize']['data']
                );
            }
        }
        
        // Main calculator localization
        $this->localize_calculator_script();
    }
    
    /**
     * Enqueue Chart.js library
     */
    private function enqueue_chart_library() {
        if (!$this->get_feature_flag('enable_charts', true)) {
            return;
        }
        
        // Use CDN or local version
        if ($this->config['cdn_support']) {
            wp_enqueue_script(
                'chart-js',
                'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                array(),
                '3.9.1',
                true
            );
        } else {
            // Check if local Chart.js exists
            $local_chart = QCC_PLUGIN_URL . 'assets/js/vendor/chart.min.js';
            if (file_exists(QCC_PLUGIN_PATH . 'assets/js/vendor/chart.min.js')) {
                wp_enqueue_script(
                    'chart-js',
                    $local_chart,
                    array(),
                    $this->config['version'],
                    true
                );
            }
        }
    }
    
    /**
     * Enqueue language-specific assets
     */
    private function enqueue_language_assets() {
        $language = $this->get_current_language();
        
        // Language-specific CSS
        $language_css = QCC_PLUGIN_PATH . "assets/css/languages/{$language}.css";
        if (file_exists($language_css)) {
            wp_enqueue_style(
                'qcc-language-' . $language,
                QCC_PLUGIN_URL . "assets/css/languages/{$language}.css",
                array('qcc-frontend'),
                $this->config['version']
            );
        }
        
        // RTL support for Arabic, Hebrew, etc.
        if (in_array($language, array('ar', 'he', 'ur'))) {
            wp_enqueue_style(
                'qcc-rtl',
                QCC_PLUGIN_URL . 'assets/css/qcc-rtl.css',
                array('qcc-frontend'),
                $this->config['version']
            );
        }
    }
    
    /**
     * Enqueue theme compatibility assets
     */
    private function enqueue_theme_compatibility() {
        $theme = get_template();
        $theme_css = QCC_PLUGIN_PATH . "assets/css/themes/{$theme}.css";
        
        if (file_exists($theme_css)) {
            wp_enqueue_style(
                'qcc-theme-' . $theme,
                QCC_PLUGIN_URL . "assets/css/themes/{$theme}.css",
                array('qcc-frontend'),
                $this->config['version']
            );
        }
        
        // Popular theme compatibility
        $popular_themes = array(
            'astra', 'oceanwp', 'generatepress', 'storefront', 
            'twentytwentythree', 'kadence', 'neve'
        );
        
        if (in_array($theme, $popular_themes)) {
            $compat_css = $this->generate_theme_compatibility_css($theme);
            if ($compat_css) {
                wp_add_inline_style('qcc-frontend', $compat_css);
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!$this->should_load_admin_assets($hook)) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'qcc-admin',
            QCC_PLUGIN_URL . 'assets/css/qcc-admin.css',
            array('wp-admin'),
            $this->config['version']
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'qcc-admin',
            QCC_PLUGIN_URL . 'assets/js/qcc-admin.js',
            array('jquery', 'wp-util'),
            $this->config['version'],
            true
        );
        
        // Localize admin script
        wp_localize_script('qcc-admin', 'qccAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_admin_nonce'),
            'strings' => $this->get_admin_strings(),
            'features' => $this->get_admin_feature_flags()
        ));
        
        // Color picker for admin
        if (strpos($hook, 'quality-cost-calculator') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }
    
    /**
     * Add frontend meta information
     */
    public function add_frontend_meta() {
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        echo '<meta name="qcc-version" content="' . esc_attr($this->config['version']) . '">' . "\n";
        echo '<meta name="qcc-language" content="' . esc_attr($this->get_current_language()) . '">' . "\n";
        
        // Preload critical resources
        $this->add_resource_hints();
        
        // Critical CSS
        if ($this->config['inline_critical']) {
            $this->output_critical_css();
        }
    }
    
    /**
     * Add admin meta information
     */
    public function add_admin_meta() {
        if (!$this->should_load_admin_assets(get_current_screen()->id ?? '')) {
            return;
        }
        
        echo '<meta name="qcc-admin-version" content="' . esc_attr($this->config['version']) . '">' . "\n";
    }
    
    /**
     * Add footer scripts
     */
    public function add_footer_scripts() {
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        // Analytics and tracking
        if ($this->get_feature_flag('enable_analytics', false)) {
            $this->add_analytics_script();
        }
        
        // Performance metrics
        if (QCC_DEBUG) {
            $this->add_performance_metrics();
        }
    }
    
    /**
     * Get CSS files configuration
     */
    private function get_css_files($context = 'frontend') {
        $files = array();
        
        if ($context === 'frontend') {
            $files = array(
                'qcc-frontend' => array(
                    'file' => 'assets/css/qcc-frontend.css',
                    'deps' => array(),
                    'media' => 'all'
                ),
                'qcc-calculator' => array(
                    'file' => 'assets/quality-cost-calculator.css',
                    'deps' => array('qcc-frontend'),
                    'media' => 'all'
                )
            );
            
            // Add responsive CSS
            if ($this->get_feature_flag('enable_responsive', true)) {
                $files['qcc-responsive'] = array(
                    'file' => 'assets/css/qcc-responsive.css',
                    'deps' => array('qcc-frontend'),
                    'media' => 'screen'
                );
            }
            
            // Add print styles
            $files['qcc-print'] = array(
                'file' => 'assets/css/qcc-print.css',
                'deps' => array('qcc-frontend'),
                'media' => 'print'
            );
        }
        
        return apply_filters('qcc_css_files', $files, $context);
    }
    
    /**
     * Get JavaScript files configuration
     */
    private function get_js_files($context = 'frontend') {
        $files = array();
        
        if ($context === 'frontend') {
            $files = array(
                'qcc-frontend' => array(
                    'file' => 'assets/quality-cost-calculator.js',
                    'deps' => array('jquery'),
                    'in_footer' => true,
                    'localize' => array(
                        'name' => 'qccAjax',
                        'data' => $this->get_localization_data()
                    )
                )
            );
            
            // Add Chart.js integration
            if ($this->get_feature_flag('enable_charts', true)) {
                $files['qcc-charts'] = array(
                    'file' => 'assets/js/qcc-charts.js',
                    'deps' => array('qcc-frontend', 'chart-js'),
                    'in_footer' => true
                );
            }
            
            // Add export functionality
            if ($this->get_feature_flag('enable_export', true)) {
                $files['qcc-export'] = array(
                    'file' => 'assets/js/qcc-export.js',
                    'deps' => array('qcc-frontend'),
                    'in_footer' => true
                );
            }
        }
        
        return apply_filters('qcc_js_files', $files, $context);
    }
    
    /**
     * Localize calculator script with comprehensive data
     */
    private function localize_calculator_script() {
        wp_localize_script('qcc-frontend', 'qccData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_nonce'),
            'version' => $this->config['version'],
            'debug' => QCC_DEBUG,
            'language' => $this->get_current_language(),
            'currency' => $this->get_default_currency(),
            'unit' => $this->get_default_unit(),
            'features' => $this->get_frontend_feature_flags(),
            'translations' => $this->get_frontend_translations(),
            'defaults' => $this->get_default_values(),
            'validation' => $this->get_validation_rules(),
            'formatting' => $this->get_formatting_options()
        ));
    }
    
    /**
     * Get localization data
     */
    private function get_localization_data() {
        return array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qcc_nonce'),
            'version' => $this->config['version'],
            'debug' => QCC_DEBUG,
            'strings' => array(
                'loading' => __('Loading...', 'quality-cost-calculator'),
                'error' => __('An error occurred', 'quality-cost-calculator'),
                'success' => __('Success', 'quality-cost-calculator'),
                'calculating' => __('Calculating...', 'quality-cost-calculator'),
                'exportSuccess' => __('Export completed successfully', 'quality-cost-calculator'),
                'resetConfirm' => __('Reset all values to defaults?', 'quality-cost-calculator')
            )
        );
    }
    
    /**
     * Should load frontend assets
     */
    private function should_load_frontend_assets() {
        if (!$this->config['conditional_loading']) {
            return true;
        }
        
        global $post;
        
        // Load on pages with shortcode
        if ($post && has_shortcode($post->post_content, 'quality_cost_calculator')) {
            return true;
        }
        
        // Load on specific pages
        if (is_page() && in_array(get_the_ID(), $this->get_calculator_page_ids())) {
            return true;
        }
        
        // Load if forced by filter
        return apply_filters('qcc_force_load_assets', false);
    }
    
    /**
     * Should load admin assets
     */
    private function should_load_admin_assets($hook) {
        $qcc_pages = array(
            'settings_page_quality-cost-calculator',
            'toplevel_page_quality-cost-calculator',
            'qcc_page_qcc-settings'
        );
        
        return in_array($hook, $qcc_pages) || 
               strpos($hook, 'quality-cost-calculator') !== false;
    }
    
    /**
     * Get asset URL with cache busting
     */
    private function get_asset_url($file_path) {
        $url = QCC_PLUGIN_URL . $file_path;
        
        // Add cache busting
        if ($this->config['cache_busting']) {
            $file_full_path = QCC_PLUGIN_PATH . $file_path;
            if (file_exists($file_full_path)) {
                $mtime = filemtime($file_full_path);
                $url = add_query_arg('t', $mtime, $url);
            }
        }
        
        return $url;
    }
    
    /**
     * Get asset version
     */
    private function get_asset_version($file_path) {
        if ($this->config['cache_busting']) {
            $file_full_path = QCC_PLUGIN_PATH . $file_path;
            if (file_exists($file_full_path)) {
                return filemtime($file_full_path);
            }
        }
        
        return $this->config['version'];
    }
    
    /**
     * Add critical CSS inline
     */
    private function add_critical_css() {
        $critical_css = $this->get_critical_css();
        if ($critical_css) {
            wp_add_inline_style('qcc-frontend', $critical_css);
        }
    }
    
    /**
     * Get critical CSS
     */
    private function get_critical_css() {
        $cache_key = 'qcc_critical_css_' . get_template();
        $critical_css = get_transient($cache_key);
        
        if ($critical_css === false) {
            $critical_css = $this->generate_critical_css();
            set_transient($cache_key, $critical_css, HOUR_IN_SECONDS);
        }
        
        return $critical_css;
    }
    
    /**
     * Generate critical CSS
     */
    private function generate_critical_css() {
        // Basic critical CSS for above-the-fold content
        return '.qcc-calculator { opacity: 1; } .qcc-loading { display: none; }';
    }
    
    /**
     * Add resource hints for performance
     */
    private function add_resource_hints() {
        // Preload critical assets
        echo '<link rel="preload" href="' . esc_url(QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css') . '" as="style">' . "\n";
        echo '<link rel="preload" href="' . esc_url(QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js') . '" as="script">' . "\n";
        
        // DNS prefetch for CDN
        if ($this->config['cdn_support']) {
            echo '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">' . "\n";
        }
    }
    
    /**
     * Generate theme compatibility CSS
     */
    private function generate_theme_compatibility_css($theme) {
        $css = '';
        
        switch ($theme) {
            case 'astra':
                $css = '.qcc-calculator { margin: 20px 0; }';
                break;
            case 'oceanwp':
                $css = '.qcc-calculator { clear: both; }';
                break;
            case 'generatepress':
                $css = '.qcc-calculator .qcc-input { box-sizing: border-box; }';
                break;
        }
        
        return apply_filters('qcc_theme_compatibility_css', $css, $theme);
    }
    
    /**
     * Get current language
     */
    private function get_current_language() {
        if (function_exists('qcc_get_option')) {
            return qcc_get_option('default_language', 'en');
        }
        return get_option('qcc_default_language', 'en');
    }
    
    /**
     * Get default currency
     */
    private function get_default_currency() {
        if (function_exists('qcc_get_option')) {
            return qcc_get_option('default_currency', 'EUR');
        }
        return get_option('qcc_default_currency', 'EUR');
    }
    
    /**
     * Get default unit
     */
    private function get_default_unit() {
        if (function_exists('qcc_get_option')) {
            return qcc_get_option('default_unit', '1000000');
        }
        return get_option('qcc_default_unit', '1000000');
    }
    
    /**
     * Get feature flag value
     */
    private function get_feature_flag($flag_name, $default = false) {
        if ($this->bootstrap) {
            return $this->bootstrap->get_feature_flag($flag_name);
        }
        return $default;
    }
    
    /**
     * Get default values
     */
    private function get_default_values() {
        if (function_exists('qcc_get_default_values')) {
            return qcc_get_default_values();
        }
        
        return array(
            'revenue' => 140,
            'quality_percentage' => 6,
            'prevention' => 10,
            'appraisal' => 20,
            'internal_defect' => 30,
            'external_defect' => 40
        );
    }
    
    /**
     * Get frontend feature flags
     */
    private function get_frontend_feature_flags() {
        return array(
            'enableCharts' => $this->get_feature_flag('enable_charts', true),
            'enableExport' => $this->get_feature_flag('enable_export', true),
            'enableValidation' => $this->get_feature_flag('enable_validation', true),
            'enableAnimations' => $this->get_feature_flag('enable_animations', true)
        );
    }
    
    /**
     * Get admin feature flags
     */
    private function get_admin_feature_flags() {
        return array(
            'debugMode' => QCC_DEBUG,
            'enablePreview' => $this->get_feature_flag('enable_admin_preview', true),
            'enableBulkActions' => $this->get_feature_flag('enable_bulk_actions', false)
        );
    }
    
    /**
     * Get frontend translations
     */
    private function get_frontend_translations() {
        // Use existing shortcode translations if available
        if (class_exists('QCC_Shortcode')) {
            $shortcode = new QCC_Shortcode();
            if (method_exists($shortcode, 'get_all_translations')) {
                return $shortcode->get_all_translations();
            }
        }
        
        // Fallback basic translations
        return array(
            'en' => array(
                'calculate' => 'Calculate',
                'reset' => 'Reset',
                'export' => 'Export'
            )
        );
    }
    
    /**
     * Get admin strings
     */
    private function get_admin_strings() {
        return array(
            'save' => __('Save Changes', 'quality-cost-calculator'),
            'saving' => __('Saving...', 'quality-cost-calculator'),
            'saved' => __('Settings saved successfully', 'quality-cost-calculator'),
            'error' => __('An error occurred while saving', 'quality-cost-calculator'),
            'confirm' => __('Are you sure?', 'quality-cost-calculator'),
            'preview' => __('Preview', 'quality-cost-calculator')
        );
    }
    
    /**
     * Get validation rules
     */
    private function get_validation_rules() {
        return array(
            'revenue' => array('min' => 0, 'required' => true),
            'quality_percentage' => array('min' => 0, 'max' => 100, 'required' => true),
            'percentages_sum' => array('exact' => 100, 'tolerance' => 0.01)
        );
    }
    
    /**
     * Get formatting options
     */
    private function get_formatting_options() {
        return array(
            'decimal_places' => 2,
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'currency_position' => 'before',
            'percentage_symbol' => '%'
        );
    }
    
    /**
     * Get calculator page IDs
     */
    private function get_calculator_page_ids() {
        $page_ids = get_option('qcc_calculator_pages', array());
        return is_array($page_ids) ? $page_ids : array();
    }
    
    /**
     * Enqueue fallback assets
     */
    private function enqueue_fallback_assets() {
        // Minimal CSS
        wp_enqueue_style(
            'qcc-fallback',
            QCC_PLUGIN_URL . 'assets/quality-cost-calculator.css',
            array(),
            $this->config['version']
        );
        
        // Minimal JS
        wp_enqueue_script(
            'qcc-fallback',
            QCC_PLUGIN_URL . 'assets/quality-cost-calculator.js',
            array('jquery'),
            $this->config['version'],
            true
        );
    }
    
    /**
     * Output critical CSS inline
     */
    private function output_critical_css() {
        $critical_css = $this->get_critical_css();
        if ($critical_css) {
            echo '<style id="qcc-critical-css">' . $critical_css . '</style>' . "\n";
        }
    }
    
    /**
     * Add analytics script
     */
    private function add_analytics_script() {
        // Placeholder for analytics integration
        if (QCC_DEBUG) {
            echo '<script>console.log("QCC Analytics placeholder");</script>' . "\n";
        }
    }
    
    /**
     * Add performance metrics script
     */
    private function add_performance_metrics() {
        $metrics = wp_json_encode($this->metrics);
        echo '<script>if(window.console) console.log("QCC Performance:", ' . $metrics . ');</script>' . "\n";
    }
    
    /**
     * Optimize CSS delivery
     */
    public function optimize_css_delivery() {
        // Placeholder for CSS optimization
    }
    
    /**
     * Optimize JS delivery
     */
    public function optimize_js_delivery() {
        // Placeholder for JS optimization
    }
    
    /**
     * Clear asset cache
     */
    public function clear_asset_cache() {
        delete_transient('qcc_critical_css_' . get_template());
        $this->asset_cache = array();
        
        if (QCC_DEBUG) {
            error_log('QCC Asset cache cleared');
        }
    }
    
    /**
     * Get asset statistics
     */
    public function get_statistics() {
        return array_merge($this->metrics, array(
            'cache_size' => count($this->asset_cache),
            'config' => $this->config
        ));
    }
    
    /**
     * Initialize method for service container
     */
    public function init() {
        // Already initialized in constructor
        return $this;
    }
}
<?php
/**
 * QCC Configuration Service - Zentrale Konfigurationsverwaltung
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration Service for Quality Cost Calculator
 * 
 * Manages all plugin configuration with WordPress integration
 */
class QCC_Configuration {
    
    /**
     * Configuration cache
     * 
     * @var array
     */
    private $config = array();
    
    /**
     * Default configuration values
     * 
     * @var array
     */
    private $defaults = array(
        // Core Settings
        'version' => '2.0.0',
        'debug_mode' => false,
        'enable_logging' => false,
        
        // Performance Settings
        'enable_lazy_loading' => true,
        'cache_translations' => true,
        'cache_calculations' => true,
        'enable_compression' => true,
        
        // Feature Flags
        'new_calculation_engine' => false,
        'new_html_renderer' => false,
        'new_translation_system' => false,
        'performance_monitoring' => true,
        
        // Localization
        'default_language' => 'en',
        'supported_languages' => array('en', 'de', 'fr', 'es', 'zh'),
        'fallback_language' => 'en',
        
        // Currency & Units
        'default_currency' => 'EUR',
        'supported_currencies' => array('EUR', 'USD', 'CNY'),
        'default_unit' => '1000000000', // Billions
        'supported_units' => array('1000000', '1000000000'),
        
        // Calculator Defaults
        'default_revenue' => 140,
        'default_quality_percentage' => 6,
        'default_prevention' => 10,
        'default_appraisal' => 20,
        'default_internal_defect' => 30,
        'default_external_defect' => 40,
        
        // UI Settings
        'enable_charts' => true,
        'chart_library' => 'chartjs',
        'theme' => 'default',
        'responsive_design' => true,
        
        // Export Settings
        'enable_pdf_export' => true,
        'enable_csv_export' => true,
        'enable_json_export' => false,
        
        // Security
        'enable_nonce_verification' => true,
        'enable_capability_check' => true,
        'allowed_user_roles' => array('administrator', 'editor'),
        
        // API Settings
        'api_enabled' => false,
        'api_version' => 'v1',
        'api_rate_limit' => 100
    );
    
    /**
     * Configuration option name in WordPress
     * 
     * @var string
     */
    private $option_name = 'qcc_configuration';
    
    /**
     * Whether configuration is loaded
     * 
     * @var bool
     */
    private $loaded = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_configuration();
    }
    
    /**
     * Load configuration from WordPress options
     * 
     * @return void
     */
    private function load_configuration() {
        if ($this->loaded) {
            return;
        }
        
        // Load from WordPress options
        $stored_config = get_option($this->option_name, array());
        
        // Merge with defaults
        $this->config = array_merge($this->defaults, $stored_config);
        
        // Apply environment overrides
        $this->apply_environment_overrides();
        
        // Validate configuration
        $this->validate_configuration();
        
        $this->loaded = true;
    }
    
    /**
     * Apply environment-specific overrides
     * 
     * @return void
     */
    private function apply_environment_overrides() {
        // WordPress Debug Mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->config['debug_mode'] = true;
            $this->config['enable_logging'] = true;
            $this->config['performance_monitoring'] = true;
        }
        
        // WordPress Environment
        if (defined('WP_ENVIRONMENT_TYPE')) {
            switch (WP_ENVIRONMENT_TYPE) {
                case 'development':
                    $this->config['debug_mode'] = true;
                    $this->config['enable_logging'] = true;
                    break;
                case 'staging':
                    $this->config['enable_logging'] = true;
                    break;
                case 'production':
                    $this->config['debug_mode'] = false;
                    $this->config['enable_logging'] = false;
                    break;
            }
        }
        
        // Multisite considerations
        if (is_multisite()) {
            $this->config['api_enabled'] = false; // Disable API on multisite by default
        }
    }
    
    /**
     * Validate configuration values
     * 
     * @return void
     */
    private function validate_configuration() {
        // Validate languages
        if (!in_array($this->config['default_language'], $this->config['supported_languages'])) {
            $this->config['default_language'] = 'en';
        }
        
        // Validate currencies
        if (!in_array($this->config['default_currency'], $this->config['supported_currencies'])) {
            $this->config['default_currency'] = 'EUR';
        }
        
        // Validate units
        if (!in_array($this->config['default_unit'], $this->config['supported_units'])) {
            $this->config['default_unit'] = '1000000000';
        }
        
        // Validate percentages sum to 100
        $percentage_sum = $this->config['default_prevention'] + 
                         $this->config['default_appraisal'] + 
                         $this->config['default_internal_defect'] + 
                         $this->config['default_external_defect'];
        
        if ($percentage_sum !== 100) {
            // Reset to defaults if invalid
            $this->config['default_prevention'] = 10;
            $this->config['default_appraisal'] = 20;
            $this->config['default_internal_defect'] = 30;
            $this->config['default_external_defect'] = 40;
        }
    }
    
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key (supports dot notation)
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public function get($key, $default = null) {
        // Support dot notation for nested access
        if (strpos($key, '.') !== false) {
            return $this->get_nested($key, $default);
        }
        
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Get nested configuration value using dot notation
     * 
     * @param string $key Dot notation key (e.g., 'cache.translations')
     * @param mixed $default Default value
     * @return mixed
     */
    private function get_nested($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param bool $persist Whether to save to database
     * @return void
     */
    public function set($key, $value, $persist = true) {
        $this->config[$key] = $value;
        
        if ($persist) {
            $this->save_configuration();
        }
    }
    
    /**
     * Get all configuration
     * 
     * @return array Complete configuration array
     */
    public function all() {
        return $this->config;
    }
    
    /**
     * Check if configuration key exists
     * 
     * @param string $key Configuration key
     * @return bool
     */
    public function has($key) {
        if (strpos($key, '.') !== false) {
            return $this->get_nested($key) !== null;
        }
        
        return isset($this->config[$key]);
    }
    
    /**
     * Save configuration to WordPress options
     * 
     * @return bool Success status
     */
    public function save_configuration() {
        // Only save non-default values to keep database lean
        $values_to_save = array();
        
        foreach ($this->config as $key => $value) {
            if (!isset($this->defaults[$key]) || $this->defaults[$key] !== $value) {
                $values_to_save[$key] = $value;
            }
        }
        
        return update_option($this->option_name, $values_to_save);
    }
    
    /**
     * Reset configuration to defaults
     * 
     * @return bool Success status
     */
    public function reset() {
        $this->config = $this->defaults;
        return delete_option($this->option_name);
    }
    
    /**
     * Get default configuration
     * 
     * @return array Default configuration
     */
    public function get_defaults() {
        return $this->defaults;
    }
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool
     */
    public function is_debug_mode() {
        return (bool) $this->get('debug_mode', false);
    }
    
    /**
     * Check if feature is enabled
     * 
     * @param string $feature Feature name
     * @return bool
     */
    public function is_feature_enabled($feature) {
        return (bool) $this->get($feature, false);
    }
    
    /**
     * Get environment information
     * 
     * @return array Environment data
     */
    public function get_environment() {
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => $this->get('version'),
            'environment_type' => defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'unknown',
            'debug_mode' => $this->is_debug_mode(),
            'multisite' => is_multisite(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );
    }
    
    /**
     * Get configuration schema for validation
     * 
     * @return array Configuration schema
     */
    public function get_schema() {
        return array(
            'version' => array('type' => 'string', 'required' => true),
            'debug_mode' => array('type' => 'boolean', 'default' => false),
            'default_language' => array('type' => 'string', 'enum' => $this->defaults['supported_languages']),
            'default_currency' => array('type' => 'string', 'enum' => $this->defaults['supported_currencies']),
            'default_unit' => array('type' => 'string', 'enum' => $this->defaults['supported_units']),
            'default_revenue' => array('type' => 'number', 'min' => 0),
            'default_quality_percentage' => array('type' => 'number', 'min' => 0, 'max' => 100),
            'default_prevention' => array('type' => 'number', 'min' => 0, 'max' => 100),
            'default_appraisal' => array('type' => 'number', 'min' => 0, 'max' => 100),
            'default_internal_defect' => array('type' => 'number', 'min' => 0, 'max' => 100),
            'default_external_defect' => array('type' => 'number', 'min' => 0, 'max' => 100)
        );
    }
    
    /**
     * Validate configuration against schema
     * 
     * @param array $config Configuration to validate
     * @return array Validation errors (empty if valid)
     */
    public function validate($config = null) {
        if ($config === null) {
            $config = $this->config;
        }
        
        $errors = array();
        $schema = $this->get_schema();
        
        foreach ($schema as $key => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($config[$key])) {
                $errors[] = "Required field '{$key}' is missing";
                continue;
            }
            
            if (!isset($config[$key])) {
                continue;
            }
            
            $value = $config[$key];
            
            // Type validation
            if (isset($rules['type'])) {
                $valid_type = false;
                switch ($rules['type']) {
                    case 'string':
                        $valid_type = is_string($value);
                        break;
                    case 'number':
                        $valid_type = is_numeric($value);
                        break;
                    case 'boolean':
                        $valid_type = is_bool($value);
                        break;
                    case 'array':
                        $valid_type = is_array($value);
                        break;
                }
                
                if (!$valid_type) {
                    $errors[] = "Field '{$key}' must be of type {$rules['type']}";
                    continue;
                }
            }
            
            // Enum validation
            if (isset($rules['enum']) && !in_array($value, $rules['enum'])) {
                $errors[] = "Field '{$key}' must be one of: " . implode(', ', $rules['enum']);
            }
            
            // Range validation
            if (isset($rules['min']) && $value < $rules['min']) {
                $errors[] = "Field '{$key}' must be at least {$rules['min']}";
            }
            
            if (isset($rules['max']) && $value > $rules['max']) {
                $errors[] = "Field '{$key}' must be at most {$rules['max']}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Export configuration for backup
     * 
     * @return array Exportable configuration
     */
    public function export() {
        return array(
            'version' => $this->get('version'),
            'exported_at' => current_time('c'),
            'configuration' => $this->config
        );
    }
    
    /**
     * Import configuration from backup
     * 
     * @param array $data Exported configuration data
     * @return bool Success status
     */
    public function import($data) {
        if (!isset($data['configuration']) || !is_array($data['configuration'])) {
            return false;
        }
        
        // Validate imported configuration
        $errors = $this->validate($data['configuration']);
        if (!empty($errors)) {
            return false;
        }
        
        $this->config = array_merge($this->defaults, $data['configuration']);
        return $this->save_configuration();
    }
}
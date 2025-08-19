<?php
/**
 * QCC Settings Service
 * 
 * Manages all settings operations for the Quality Cost Calculator plugin.
 * Part of the modular service layer architecture.
 * 
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Settings Service Class
 * 
 * Centralizes settings management with validation, caching,
 * environment support, backup/restore, and health monitoring.
 */
class QCC_Settings_Service {
    
    /**
     * Settings cache
     * 
     * @var array
     */
    private $settings_cache = array();
    
    /**
     * Default settings
     * 
     * @var array
     */
    private $default_settings = array();
    
    /**
     * Settings validators
     * 
     * @var array
     */
    private $validators = array();
    
    /**
     * Settings sanitizers
     * 
     * @var array
     */
    private $sanitizers = array();
    
    /**
     * Settings groups
     * 
     * @var array
     */
    private $settings_groups = array();
    
    /**
     * Change tracking
     * 
     * @var array
     */
    private $change_log = array();
    
    /**
     * Settings option name
     * 
     * @var string
     */
    private $option_name = 'qcc_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_default_settings();
        $this->init_validators();
        $this->init_sanitizers();
        $this->init_settings_groups();
        $this->load_settings_cache();
        $this->schedule_cleanup();
    }
    
    /**
     * Initialize default settings
     */
    private function init_default_settings() {
        $this->default_settings = array(
            // General settings
            'general' => array(
                'plugin_version' => QCC_VERSION ?? '2.0.0',
                'enable_cache' => true,
                'cache_duration' => 3600,
                'enable_debug' => false,
                'auto_update' => true,
                'language' => 'en',
                'timezone' => 'UTC'
            ),
            
            // Calculation settings
            'calculation' => array(
                'default_currency' => 'USD',
                'decimal_places' => 2,
                'calculation_method' => 'standard',
                'enable_real_time' => true,
                'validation_level' => 'strict',
                'rounding_mode' => 'round',
                'tax_rate' => 0.0,
                'enable_tax_calculation' => false
            ),
            
            // Display settings
            'display' => array(
                'theme' => 'default',
                'color_scheme' => 'light',
                'show_tooltips' => true,
                'compact_mode' => false,
                'responsive_design' => true,
                'animation_enabled' => true,
                'font_size' => 'medium',
                'layout_mode' => 'standard'
            ),
            
            // Performance settings
            'performance' => array(
                'enable_minification' => true,
                'combine_assets' => true,
                'lazy_load' => true,
                'preload_templates' => false,
                'enable_compression' => true,
                'max_cache_size' => 100,
                'cache_cleanup_interval' => 24
            ),
            
            // Security settings
            'security' => array(
                'enable_nonce_check' => true,
                'capability_required' => 'edit_posts',
                'sanitize_input' => true,
                'escape_output' => true,
                'enable_rate_limiting' => false,
                'max_requests_per_minute' => 60,
                'enable_csrf_protection' => true
            ),
            
            // Integration settings
            'integration' => array(
                'enable_rest_api' => true,
                'enable_shortcodes' => true,
                'enable_widgets' => true,
                'enable_gutenberg' => true,
                'enable_elementor' => false,
                'enable_beaver_builder' => false,
                'enable_divi' => false
            ),
            
            // Notification settings
            'notifications' => array(
                'enable_email_notifications' => false,
                'admin_email' => '',
                'notification_frequency' => 'daily',
                'enable_error_notifications' => true,
                'enable_update_notifications' => true
            ),
            
            // Export/Import settings
            'export_import' => array(
                'default_export_format' => 'json',
                'include_metadata' => true,
                'compress_exports' => false,
                'auto_backup_before_import' => true,
                'validate_imports' => true
            )
        );
    }
    
    /**
     * Initialize validators
     */
    private function init_validators() {
        $this->validators = array(
            'email' => 'is_email',
            'url' => array($this, 'validate_url'),
            'numeric' => 'is_numeric',
            'boolean' => array($this, 'validate_boolean'),
            'currency' => array($this, 'validate_currency'),
            'color' => array($this, 'validate_color'),
            'capability' => array($this, 'validate_capability'),
            'timezone' => array($this, 'validate_timezone'),
            'language' => array($this, 'validate_language'),
            'positive_number' => array($this, 'validate_positive_number'),
            'percentage' => array($this, 'validate_percentage')
        );
    }
    
    /**
     * Initialize sanitizers
     */
    private function init_sanitizers() {
        $this->sanitizers = array(
            'text' => 'sanitize_text_field',
            'textarea' => 'sanitize_textarea_field',
            'email' => 'sanitize_email',
            'url' => 'esc_url_raw',
            'key' => 'sanitize_key',
            'html' => 'wp_kses_post',
            'number' => array($this, 'sanitize_number'),
            'boolean' => array($this, 'sanitize_boolean'),
            'currency' => array($this, 'sanitize_currency'),
            'percentage' => array($this, 'sanitize_percentage')
        );
    }
    
    /**
     * Initialize settings groups
     */
    private function init_settings_groups() {
        $this->settings_groups = array(
            'general' => array(
                'title' => __('General Settings', 'quality-cost-calculator'),
                'description' => __('Basic plugin configuration', 'quality-cost-calculator'),
                'priority' => 10,
                'icon' => 'dashicons-admin-generic'
            ),
            'calculation' => array(
                'title' => __('Calculation Settings', 'quality-cost-calculator'),
                'description' => __('Configure calculation behavior', 'quality-cost-calculator'),
                'priority' => 20,
                'icon' => 'dashicons-calculator'
            ),
            'display' => array(
                'title' => __('Display Settings', 'quality-cost-calculator'),
                'description' => __('Customize appearance and layout', 'quality-cost-calculator'),
                'priority' => 30,
                'icon' => 'dashicons-admin-appearance'
            ),
            'performance' => array(
                'title' => __('Performance Settings', 'quality-cost-calculator'),
                'description' => __('Optimize plugin performance', 'quality-cost-calculator'),
                'priority' => 40,
                'icon' => 'dashicons-performance'
            ),
            'security' => array(
                'title' => __('Security Settings', 'quality-cost-calculator'),
                'description' => __('Configure security options', 'quality-cost-calculator'),
                'priority' => 50,
                'icon' => 'dashicons-shield'
            ),
            'integration' => array(
                'title' => __('Integration Settings', 'quality-cost-calculator'),
                'description' => __('Enable/disable integrations', 'quality-cost-calculator'),
                'priority' => 60,
                'icon' => 'dashicons-admin-plugins'
            ),
            'notifications' => array(
                'title' => __('Notification Settings', 'quality-cost-calculator'),
                'description' => __('Configure notification preferences', 'quality-cost-calculator'),
                'priority' => 70,
                'icon' => 'dashicons-email-alt'
            ),
            'export_import' => array(
                'title' => __('Export/Import Settings', 'quality-cost-calculator'),
                'description' => __('Data export and import options', 'quality-cost-calculator'),
                'priority' => 80,
                'icon' => 'dashicons-migrate'
            )
        );
    }
    
    /**
     * Get setting value
     * 
     * @param string $key Setting key (supports dot notation)
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public function get($key, $default = null) {
        // Check cache first
        if (isset($this->settings_cache[$key])) {
            return $this->settings_cache[$key];
        }
        
        // Parse dot notation
        $keys = explode('.', $key);
        $value = $this->get_all_settings();
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Try default settings
                $value = $this->get_default_value($key, $default);
                break;
            }
        }
        
        // Cache the result
        $this->settings_cache[$key] = $value;
        
        return $value;
    }
    
    /**
     * Set setting value
     * 
     * @param string $key Setting key (supports dot notation)
     * @param mixed $value Setting value
     * @param bool $validate Whether to validate the value
     * @return bool Success status
     */
    public function set($key, $value, $validate = true) {
        try {
            // Validate if requested
            if ($validate && !$this->validate_setting($key, $value)) {
                return false;
            }
            
            // Sanitize value
            $value = $this->sanitize_setting($key, $value);
            
            // Get current settings
            $settings = $this->get_all_settings();
            
            // Parse dot notation and set value
            $keys = explode('.', $key);
            $current = &$settings;
            
            for ($i = 0; $i < count($keys) - 1; $i++) {
                if (!isset($current[$keys[$i]]) || !is_array($current[$keys[$i]])) {
                    $current[$keys[$i]] = array();
                }
                $current = &$current[$keys[$i]];
            }
            
            $final_key = end($keys);
            $old_value = isset($current[$final_key]) ? $current[$final_key] : null;
            $current[$final_key] = $value;
            
            // Save settings
            $success = update_option($this->option_name, $settings);
            
            if ($success) {
                // Update cache
                $this->settings_cache[$key] = $value;
                
                // Log change
                $this->log_setting_change($key, $old_value, $value);
                
                // Trigger action
                do_action('qcc_setting_updated', $key, $value, $old_value);
            }
            
            return $success;
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('QCC Settings Error: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Delete setting
     * 
     * @param string $key Setting key
     * @return bool Success status
     */
    public function delete($key) {
        $settings = $this->get_all_settings();
        $keys = explode('.', $key);
        $current = &$settings;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!isset($current[$keys[$i]])) {
                return false; // Path doesn't exist
            }
            $current = &$current[$keys[$i]];
        }
        
        $final_key = end($keys);
        if (!isset($current[$final_key])) {
            return false; // Key doesn't exist
        }
        
        $old_value = $current[$final_key];
        unset($current[$final_key]);
        
        $success = update_option($this->option_name, $settings);
        
        if ($success) {
            // Clear from cache
            unset($this->settings_cache[$key]);
            
            // Log change
            $this->log_setting_change($key, $old_value, null);
            
            // Trigger action
            do_action('qcc_setting_deleted', $key, $old_value);
        }
        
        return $success;
    }
    
    /**
     * Get all settings
     * 
     * @return array All settings
     */
    public function get_all_settings() {
        $settings = get_option($this->option_name, array());
        
        // Merge with defaults
        return array_replace_recursive($this->default_settings, $settings);
    }
    
    /**
     * Update multiple settings
     * 
     * @param array $settings Settings array
     * @param bool $merge Whether to merge with existing settings
     * @return bool Success status
     */
    public function update_multiple($settings, $merge = true) {
        if ($merge) {
            $current_settings = $this->get_all_settings();
            $settings = array_replace_recursive($current_settings, $settings);
        }
        
        // Validate all settings
        foreach ($settings as $group => $group_settings) {
            if (is_array($group_settings)) {
                foreach ($group_settings as $key => $value) {
                    $full_key = $group . '.' . $key;
                    if (!$this->validate_setting($full_key, $value)) {
                        return false;
                    }
                }
            }
        }
        
        $success = update_option($this->option_name, $settings);
        
        if ($success) {
            // Clear cache
            $this->settings_cache = array();
            
            // Trigger action
            do_action('qcc_settings_updated', $settings);
        }
        
        return $success;
    }
    
    /**
     * Reset settings to defaults
     * 
     * @param string|null $group Optional group to reset
     * @return bool Success status
     */
    public function reset_to_defaults($group = null) {
        if ($group) {
            if (!isset($this->default_settings[$group])) {
                return false;
            }
            
            $current_settings = $this->get_all_settings();
            $current_settings[$group] = $this->default_settings[$group];
            $success = update_option($this->option_name, $current_settings);
        } else {
            $success = update_option($this->option_name, $this->default_settings);
        }
        
        if ($success) {
            // Clear cache
            $this->settings_cache = array();
            
            // Trigger action
            do_action('qcc_settings_reset', $group);
        }
        
        return $success;
    }
    
    /**
     * Export settings
     * 
     * @param array $groups Optional specific groups to export
     * @return array Exported settings
     */
    public function export_settings($groups = null) {
        $settings = $this->get_all_settings();
        
        if ($groups) {
            $filtered_settings = array();
            foreach ($groups as $group) {
                if (isset($settings[$group])) {
                    $filtered_settings[$group] = $settings[$group];
                }
            }
            $settings = $filtered_settings;
        }
        
        return array(
            'version' => QCC_VERSION ?? '2.0.0',
            'exported_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'export_hash' => md5(serialize($settings)),
            'settings' => $settings
        );
    }
    
    /**
     * Import settings
     * 
     * @param array $import_data Import data
     * @param bool $merge Whether to merge with existing
     * @return array Import results
     */
    public function import_settings($import_data, $merge = true) {
        $results = array(
            'success' => false,
            'imported_count' => 0,
            'skipped_count' => 0,
            'errors' => array()
        );
        
        if (!isset($import_data['settings'])) {
            $results['errors'][] = 'Invalid import data format';
            return $results;
        }
        
        $import_settings = $import_data['settings'];
        
        // Validate import data
        if (!$this->validate_import_data($import_settings)) {
            $results['errors'][] = 'Import data validation failed';
            return $results;
        }
        
        // Create backup before import
        if ($this->get('export_import.auto_backup_before_import', true)) {
            $this->backup_settings('pre_import_' . date('Y_m_d_H_i_s'));
        }
        
        // Count settings for reporting
        $flat_import = $this->flatten_array($import_settings);
        $total_settings = count($flat_import);
        
        try {
            $success = $this->update_multiple($import_settings, $merge);
            
            if ($success) {
                $results['success'] = true;
                $results['imported_count'] = $total_settings;
                
                // Log import
                $this->log_setting_change('*', 'imported', $import_data['exported_at'] ?? 'unknown');
            } else {
                $results['errors'][] = 'Failed to save imported settings';
            }
            
        } catch (Exception $e) {
            $results['errors'][] = 'Import failed: ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Get settings by environment
     */
    public function get_environment_setting($key, $environment = null) {
        if (!$environment) {
            $environment = $this->get_current_environment();
        }
        
        $env_key = "env.{$environment}.{$key}";
        $env_value = $this->get($env_key);
        
        // Fallback to global setting if environment-specific not found
        return $env_value !== null ? $env_value : $this->get($key);
    }
    
    /**
     * Set environment-specific setting
     */
    public function set_environment_setting($key, $value, $environment = null) {
        if (!$environment) {
            $environment = $this->get_current_environment();
        }
        
        $env_key = "env.{$environment}.{$key}";
        return $this->set($env_key, $value);
    }
    
    /**
     * Backup current settings
     */
    public function backup_settings($backup_name = null) {
        if (!$backup_name) {
            $backup_name = 'auto_backup_' . date('Y_m_d_H_i_s');
        }
        
        $settings = $this->get_all_settings();
        $backup_data = array(
            'name' => $backup_name,
            'created_at' => current_time('mysql'),
            'version' => QCC_VERSION ?? '2.0.0',
            'wp_version' => get_bloginfo('version'),
            'user_id' => get_current_user_id(),
            'settings_count' => count($this->flatten_array($settings)),
            'settings' => $settings
        );
        
        $backups = get_option('qcc_settings_backups', array());
        $backups[$backup_name] = $backup_data;
        
        // Keep only last 10 backups
        if (count($backups) > 10) {
            $backups = array_slice($backups, -10, null, true);
        }
        
        return update_option('qcc_settings_backups', $backups);
    }
    
    /**
     * Restore settings from backup
     */
    public function restore_settings($backup_name) {
        $backups = get_option('qcc_settings_backups', array());
        
        if (!isset($backups[$backup_name])) {
            return false;
        }
        
        $backup_data = $backups[$backup_name];
        
        if (!isset($backup_data['settings'])) {
            return false;
        }
        
        $success = update_option($this->option_name, $backup_data['settings']);
        
        if ($success) {
            // Clear cache
            $this->settings_cache = array();
            
            // Log restore
            $this->log_setting_change('*', 'restored_from_backup', $backup_name);
            
            // Trigger action
            do_action('qcc_settings_restored', $backup_name, $backup_data);
        }
        
        return $success;
    }
    
    /**
     * Get settings groups
     */
    public function get_settings_groups() {
        return $this->settings_groups;
    }
    
    /**
     * Get settings schema
     */
    public function get_settings_schema() {
        $schema = array();
        
        foreach ($this->default_settings as $group => $settings) {
            $schema[$group] = array();
            foreach ($settings as $key => $default_value) {
                $schema[$group][$key] = array(
                    'type' => $this->detect_setting_type($default_value),
                    'default' => $default_value,
                    'validation' => $this->get_setting_validation($group . '.' . $key),
                    'sanitization' => $this->get_setting_sanitization($group . '.' . $key),
                    'description' => $this->get_setting_description($group . '.' . $key)
                );
            }
        }
        
        return $schema;
    }
    
    /**
     * Get change log
     */
    public function get_change_log($limit = 50) {
        $log = get_option('qcc_settings_changelog', array());
        return array_slice($log, -$limit);
    }
    
    /**
     * Get backups list
     */
    public function get_backups_list() {
        $backups = get_option('qcc_settings_backups', array());
        
        $list = array();
        foreach ($backups as $name => $data) {
            $list[] = array(
                'name' => $name,
                'created_at' => $data['created_at'],
                'version' => $data['version'] ?? 'unknown',
                'wp_version' => $data['wp_version'] ?? 'unknown',
                'user_id' => $data['user_id'] ?? 0,
                'settings_count' => $data['settings_count'] ?? 0,
                'size' => strlen(serialize($data['settings']))
            );
        }
        
        // Sort by creation date (newest first)
        usort($list, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $list;
    }
    
    /**
     * Delete backup
     */
    public function delete_backup($backup_name) {
        $backups = get_option('qcc_settings_backups', array());
        
        if (!isset($backups[$backup_name])) {
            return false;
        }
        
        unset($backups[$backup_name]);
        return update_option('qcc_settings_backups', $backups);
    }
    
    /**
     * Get settings health check
     */
    public function get_health_check() {
        $health = array(
            'status' => 'healthy',
            'score' => 100,
            'issues' => array(),
            'recommendations' => array(),
            'stats' => array()
        );
        
        // Check settings structure
        $settings = $this->get_all_settings();
        $validation = $this->validate_settings_structure($settings);
        
        if (!$validation['valid']) {
            $health['status'] = 'error';
            $health['score'] -= 30;
            $health['issues'] = array_merge($health['issues'], $validation['errors']);
        }
        
        if (!empty($validation['warnings'])) {
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
            $health['score'] -= 10;
            $health['issues'] = array_merge($health['issues'], $validation['warnings']);
        }
        
        // Check cache performance
        if (count($this->settings_cache) > 100) {
            $health['score'] -= 5;
            $health['recommendations'][] = 'Settings cache is large. Consider clearing it.';
        }
        
        // Check backup status
        $backups = $this->get_backups_list();
        if (empty($backups)) {
            $health['score'] -= 15;
            $health['recommendations'][] = 'No settings backups found. Consider creating one.';
        } elseif (strtotime($backups[0]['created_at']) < strtotime('-7 days')) {
            $health['score'] -= 10;
            $health['recommendations'][] = 'Latest backup is older than 7 days.';
        }
        
        // Check for deprecated settings
        $deprecated_settings = $this->find_deprecated_settings($settings);
        if (!empty($deprecated_settings)) {
            $health['score'] -= 5;
            $health['recommendations'][] = 'Found deprecated settings: ' . implode(', ', $deprecated_settings);
        }
        
        // Statistics
        $health['stats'] = array(
            'total_settings' => count($this->flatten_array($settings)),
            'cache_size' => count($this->settings_cache),
            'changelog_entries' => count($this->get_change_log(1000)),
            'backups_count' => count($backups),
            'memory_usage' => memory_get_usage(true),
            'last_backup' => !empty($backups) ? $backups[0]['created_at'] : 'never'
        );
        
        // Determine final status based on score
        if ($health['score'] >= 90) {
            $health['status'] = 'excellent';
        } elseif ($health['score'] >= 70) {
            $health['status'] = 'good';
        } elseif ($health['score'] >= 50) {
            $health['status'] = 'warning';
        } else {
            $health['status'] = 'critical';
        }
        
        return $health;
    }
    
    /**
     * Private helper methods
     */
    
    private function get_default_value($key, $fallback = null) {
        $keys = explode('.', $key);
        $value = $this->default_settings;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $fallback;
            }
        }
        
        return $value;
    }
    
    private function load_settings_cache() {
        // Pre-load frequently accessed settings
        $frequent_settings = array(
            'general.enable_cache',
            'calculation.default_currency',
            'display.theme',
            'performance.enable_minification',
            'security.enable_nonce_check'
        );
        
        foreach ($frequent_settings as $setting) {
            $this->get($setting);
        }
    }
    
    private function log_setting_change($key, $old_value, $new_value) {
        $this->change_log[] = array(
            'key' => $key,
            'old_value' => $old_value,
            'new_value' => $new_value,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        
        // Keep only last 100 changes
        if (count($this->change_log) > 100) {
            $this->change_log = array_slice($this->change_log, -100);
        }
        
        // Store in database for persistence
        update_option('qcc_settings_changelog', $this->change_log);
    }
    
    private function validate_setting($key, $value) {
        $validation_rules = $this->get_setting_validation($key);
        
        if (empty($validation_rules)) {
            return true;
        }
        
        foreach ($validation_rules as $rule) {
            if (isset($this->validators[$rule])) {
                $validator = $this->validators[$rule];
                if (is_callable($validator)) {
                    if (!call_user_func($validator, $value)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    private function sanitize_setting($key, $value) {
        $sanitization_type = $this->get_setting_sanitization($key);
        
        if ($sanitization_type && isset($this->sanitizers[$sanitization_type])) {
            $sanitizer = $this->sanitizers[$sanitization_type];
            if (is_callable($sanitizer)) {
                return call_user_func($sanitizer, $value);
            }
        }
        
        return $value;
    }
    
    private function get_current_environment() {
        if (defined('WP_ENV')) {
            return WP_ENV;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'development';
        }
        
        return 'production';
    }
    
    private function validate_import_data($data) {
        if (!is_array($data)) {
            return false;
        }
        
        // Basic structure validation
        foreach ($data as $group => $settings) {
            if (!is_string($group) || !is_array($settings)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function validate_settings_structure($settings) {
        $results = array(
            'valid' => true,
            'errors' => array(),
            'warnings' => array()
        );
        
        // Check against schema
        $schema = $this->get_settings_schema();
        
        foreach ($settings as $group => $group_settings) {
            if (!isset($schema[$group])) {
                $results['warnings'][] = "Unknown settings group: {$group}";
                continue;
            }
            
            if (!is_array($group_settings)) {
                $results['errors'][] = "Settings group {$group} must be an array";
                $results['valid'] = false;
                continue;
            }
            
            foreach ($group_settings as $key => $value) {
                $full_key = "{$group}.{$key}";
                
                if (!isset($schema[$group][$key])) {
                    $results['warnings'][] = "Unknown setting: {$full_key}";
                    continue;
                }
                
                // Type validation
                $expected_type = $schema[$group][$key]['type'];
                $actual_type = $this->detect_setting_type($value);
                
                if ($expected_type !== $actual_type) {
                    $results['errors'][] = "Setting {$full_key} has wrong type. Expected: {$expected_type}, got: {$actual_type}";
                    $results['valid'] = false;
                }
                
                // Custom validation
                if (!$this->validate_setting($full_key, $value)) {
                    $results['errors'][] = "Setting {$full_key} failed validation";
                    $results['valid'] = false;
                }
            }
        }
        
        return $results;
    }
    
    private function flatten_array($array, $prefix = '') {
        $result = array();
        
        foreach ($array as $key => $value) {
            $new_key = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flatten_array($value, $new_key));
            } else {
                $result[$new_key] = $value;
            }
        }
        
        return $result;
    }
    
    private function detect_setting_type($value) {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'number';
        if (is_array($value)) return 'array';
        return 'string';
    }
    
    private function get_setting_validation($key) {
        $validation_map = array(
            'general.enable_cache' => array('boolean'),
            'general.cache_duration' => array('positive_number'),
            'general.language' => array('language'),
            'general.timezone' => array('timezone'),
            'calculation.default_currency' => array('currency'),
            'calculation.decimal_places' => array('positive_number'),
            'calculation.tax_rate' => array('percentage'),
            'display.color_scheme' => array('text'),
            'display.font_size' => array('text'),
            'performance.max_cache_size' => array('positive_number'),
            'performance.cache_cleanup_interval' => array('positive_number'),
            'security.capability_required' => array('capability'),
            'security.max_requests_per_minute' => array('positive_number'),
            'notifications.admin_email' => array('email'),
            'notifications.notification_frequency' => array('text')
        );
        
        return isset($validation_map[$key]) ? $validation_map[$key] : array();
    }
    
    private function get_setting_sanitization($key) {
        $sanitization_map = array(
            'general.enable_cache' => 'boolean',
            'general.cache_duration' => 'number',
            'general.language' => 'text',
            'general.timezone' => 'text',
            'calculation.default_currency' => 'currency',
            'calculation.decimal_places' => 'number',
            'calculation.tax_rate' => 'percentage',
            'display.theme' => 'text',
            'display.color_scheme' => 'text',
            'performance.max_cache_size' => 'number',
            'security.capability_required' => 'text',
            'notifications.admin_email' => 'email',
            'export_import.default_export_format' => 'text'
        );
        
        return isset($sanitization_map[$key]) ? $sanitization_map[$key] : 'text';
    }
    
    private function get_setting_description($key) {
        $descriptions = array(
            'general.enable_cache' => __('Enable caching for better performance', 'quality-cost-calculator'),
            'general.cache_duration' => __('Cache duration in seconds', 'quality-cost-calculator'),
            'general.language' => __('Default language for the plugin', 'quality-cost-calculator'),
            'calculation.default_currency' => __('Default currency for calculations', 'quality-cost-calculator'),
            'calculation.decimal_places' => __('Number of decimal places to display', 'quality-cost-calculator'),
            'display.theme' => __('Visual theme for the calculator', 'quality-cost-calculator'),
            'performance.enable_minification' => __('Minify CSS and JavaScript files', 'quality-cost-calculator'),
            'security.enable_nonce_check' => __('Enable nonce verification for security', 'quality-cost-calculator'),
            'integration.enable_rest_api' => __('Enable REST API endpoints', 'quality-cost-calculator')
        );
        
        return isset($descriptions[$key]) ? $descriptions[$key] : '';
    }
    
    private function find_deprecated_settings($settings) {
        $deprecated = array();
        $deprecated_keys = array(
            'old_setting_key',
            'legacy_option',
            'deprecated_feature'
        );
        
        $flat_settings = $this->flatten_array($settings);
        
        foreach ($flat_settings as $key => $value) {
            foreach ($deprecated_keys as $deprecated_key) {
                if (strpos($key, $deprecated_key) !== false) {
                    $deprecated[] = $key;
                }
            }
        }
        
        return $deprecated;
    }
    
    private function schedule_cleanup() {
        if (!wp_next_scheduled('qcc_settings_cleanup')) {
            wp_schedule_event(time(), 'daily', 'qcc_settings_cleanup');
        }
        
        // Hook cleanup function
        add_action('qcc_settings_cleanup', array($this, 'cleanup_old_data'));
    }
    
    public function cleanup_old_data() {
        // Clean old change logs (keep last 30 days)
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        $changelog = $this->get_change_log(1000);
        
        $filtered_changelog = array_filter($changelog, function($entry) use ($cutoff_date) {
            return $entry['timestamp'] >= $cutoff_date;
        });
        
        update_option('qcc_settings_changelog', $filtered_changelog);
        
        // Clean old backups (keep last 5)
        $backups = get_option('qcc_settings_backups', array());
        if (count($backups) > 5) {
            // Sort by creation date
            uasort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            // Keep only last 5
            $backups = array_slice($backups, 0, 5, true);
            update_option('qcc_settings_backups', $backups);
        }
        
        // Clean settings cache if too large
        if (count($this->settings_cache) > 50) {
            $this->settings_cache = array();
        }
    }
    
    /**
     * Custom validators
     */
    public function validate_url($value) {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    public function validate_boolean($value) {
        return is_bool($value) || in_array($value, array('0', '1', 'true', 'false'), true);
    }
    
    public function validate_currency($value) {
        $valid_currencies = array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NOK', 'DKK');
        return in_array(strtoupper($value), $valid_currencies);
    }
    
    public function validate_color($value) {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value) || 
               in_array($value, array('red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink', 'brown', 'black', 'white', 'gray'));
    }
    
    public function validate_capability($value) {
        $valid_capabilities = array(
            'read', 'edit_posts', 'publish_posts', 'edit_others_posts', 'edit_published_posts',
            'delete_posts', 'delete_others_posts', 'delete_published_posts', 'manage_categories',
            'manage_options', 'moderate_comments', 'activate_plugins', 'edit_plugins',
            'edit_users', 'edit_themes', 'install_plugins', 'update_plugins', 'delete_plugins'
        );
        return in_array($value, $valid_capabilities);
    }
    
    public function validate_timezone($value) {
        return in_array($value, timezone_identifiers_list());
    }
    
    public function validate_language($value) {
        $valid_languages = array('en', 'de', 'fr', 'es', 'it', 'pt', 'nl', 'sv', 'da', 'no', 'fi', 'pl', 'ru', 'zh', 'ja', 'ko');
        return in_array($value, $valid_languages);
    }
    
    public function validate_positive_number($value) {
        return is_numeric($value) && $value >= 0;
    }
    
    public function validate_percentage($value) {
        return is_numeric($value) && $value >= 0 && $value <= 100;
    }
    
    /**
     * Custom sanitizers
     */
    public function sanitize_number($value) {
        return is_numeric($value) ? floatval($value) : 0;
    }
    
    public function sanitize_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }
        return in_array($value, array('1', 'true', true), true);
    }
    
    public function sanitize_currency($value) {
        return strtoupper(sanitize_text_field($value));
    }
    
    public function sanitize_percentage($value) {
        $percentage = floatval($value);
        return max(0, min(100, $percentage));
    }
    
    /**
     * Get settings diff between two versions
     */
    public function get_settings_diff($old_settings, $new_settings) {
        $diff = array(
            'added' => array(),
            'removed' => array(),
            'modified' => array(),
            'unchanged' => array()
        );
        
        // Flatten both arrays for comparison
        $old_flat = $this->flatten_array($old_settings);
        $new_flat = $this->flatten_array($new_settings);
        
        // Find added and modified
        foreach ($new_flat as $key => $value) {
            if (!isset($old_flat[$key])) {
                $diff['added'][$key] = $value;
            } elseif ($old_flat[$key] !== $value) {
                $diff['modified'][$key] = array(
                    'old' => $old_flat[$key],
                    'new' => $value
                );
            } else {
                $diff['unchanged'][$key] = $value;
            }
        }
        
        // Find removed
        foreach ($old_flat as $key => $value) {
            if (!isset($new_flat[$key])) {
                $diff['removed'][$key] = $value;
            }
        }
        
        return $diff;
    }
    
    /**
     * Get comprehensive settings report
     */
    public function get_settings_report() {
        $settings = $this->get_all_settings();
        $health = $this->get_health_check();
        $backups = $this->get_backups_list();
        $changelog = $this->get_change_log(20);
        
        return array(
            'overview' => array(
                'total_settings' => count($this->flatten_array($settings)),
                'settings_groups' => count($this->settings_groups),
                'health_score' => $health['score'],
                'health_status' => $health['status'],
                'last_modified' => !empty($changelog) ? $changelog[0]['timestamp'] : 'never',
                'cache_size' => count($this->settings_cache),
                'memory_usage' => memory_get_usage(true)
            ),
            'health_check' => $health,
            'recent_changes' => array_slice($changelog, 0, 10),
            'backups_info' => array(
                'total_backups' => count($backups),
                'latest_backup' => !empty($backups) ? $backups[0] : null,
                'total_backup_size' => array_sum(array_column($backups, 'size'))
            ),
            'settings_by_group' => $this->get_settings_by_group_summary(),
            'environment_info' => array(
                'current_environment' => $this->get_current_environment(),
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'plugin_version' => QCC_VERSION ?? '2.0.0'
            )
        );
    }
    
    private function get_settings_by_group_summary() {
        $settings = $this->get_all_settings();
        $summary = array();
        
        foreach ($settings as $group => $group_settings) {
            $summary[$group] = array(
                'count' => count($group_settings),
                'title' => $this->settings_groups[$group]['title'] ?? $group,
                'modified_from_default' => $this->count_modified_settings($group, $group_settings)
            );
        }
        
        return $summary;
    }
    
    private function count_modified_settings($group, $settings) {
        $count = 0;
        $defaults = $this->default_settings[$group] ?? array();
        
        foreach ($settings as $key => $value) {
            if (!isset($defaults[$key]) || $defaults[$key] !== $value) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Clear all caches
     */
    public function clear_all_caches() {
        $this->settings_cache = array();
        wp_cache_delete('qcc_settings', 'options');
        
        do_action('qcc_settings_cache_cleared');
        
        return true;
    }
    
    /**
     * Get setting history for a specific key
     */
    public function get_setting_history($key, $limit = 10) {
        $changelog = $this->get_change_log(1000);
        
        $history = array_filter($changelog, function($entry) use ($key) {
            return $entry['key'] === $key || $entry['key'] === '*';
        });
        
        return array_slice($history, -$limit);
    }
    
    /**
     * Validate plugin compatibility
     */
    public function validate_plugin_compatibility() {
        $compatibility = array(
            'wp_version' => version_compare(get_bloginfo('version'), '5.0', '>='),
            'php_version' => version_compare(PHP_VERSION, '7.4', '>='),
            'required_functions' => array(
                'wp_json_encode' => function_exists('wp_json_encode'),
                'wp_cache_get' => function_exists('wp_cache_get'),
                'current_user_can' => function_exists('current_user_can')
            ),
            'required_extensions' => array(
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'curl' => extension_loaded('curl')
            )
        );
        
        return $compatibility;
    }
}
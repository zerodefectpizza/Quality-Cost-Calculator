<?php
/**
 * QCC Feature Flags
 * 
 * Manages feature flags for enabling/disabling functionality dynamically.
 * Supports conditional features, A/B testing, gradual rollouts, and user-based flags.
 * 
 * @package    QCC
 * @subpackage Infrastructure
 * @since      3.0.0
 * @author     Quality Cost Calculator Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Feature Flags Class
 * 
 * @since 3.0.0
 */
class QCC_Feature_Flags {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Feature_Flags
     */
    private static $instance = null;
    
    /**
     * Feature flags storage
     * 
     * @since 3.0.0
     * @var array
     */
    private $flags = array();
    
    /**
     * Flag definitions and metadata
     * 
     * @since 3.0.0
     * @var array
     */
    private $definitions = array();
    
    /**
     * Flag evaluation cache
     * 
     * @since 3.0.0
     * @var array
     */
    private $evaluation_cache = array();
    
    /**
     * User context for flag evaluation
     * 
     * @since 3.0.0
     * @var array
     */
    private $user_context = array();
    
    /**
     * Flag usage statistics
     * 
     * @since 3.0.0
     * @var array
     */
    private $usage_stats = array();
    
    /**
     * Debug mode
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Flag evaluation listeners
     * 
     * @since 3.0.0
     * @var array
     */
    private $listeners = array();
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->load_flags();
        $this->register_default_flags();
        $this->init_user_context();
        $this->register_hooks();
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Feature_Flags
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load flags from database
     * 
     * @since 3.0.0
     * @return void
     */
    private function load_flags() {
        $this->flags = get_option('qcc_feature_flags', array());
        
        // Load flag definitions
        $this->definitions = get_option('qcc_feature_flag_definitions', array());
        
        // Load usage statistics
        $this->usage_stats = get_option('qcc_feature_flag_stats', array());
    }
    
    /**
     * Save flags to database
     * 
     * @since 3.0.0
     * @return bool True on success
     */
    private function save_flags() {
        $result1 = update_option('qcc_feature_flags', $this->flags);
        $result2 = update_option('qcc_feature_flag_definitions', $this->definitions);
        $result3 = update_option('qcc_feature_flag_stats', $this->usage_stats);
        
        // Clear evaluation cache
        $this->evaluation_cache = array();
        
        return $result1 && $result2 && $result3;
    }
    
    /**
     * Register default feature flags
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_default_flags() {
        $default_flags = array(
            'use_new_shortcode_architecture' => array(
                'name' => 'New Shortcode Architecture',
                'description' => 'Enable the new modular shortcode system',
                'default_value' => false,
                'type' => 'boolean',
                'category' => 'core',
                'stability' => 'experimental'
            ),
            'use_service_container' => array(
                'name' => 'Service Container',
                'description' => 'Enable dependency injection container',
                'default_value' => true,
                'type' => 'boolean',
                'category' => 'core',
                'stability' => 'stable'
            ),
            'enable_performance_monitoring' => array(
                'name' => 'Performance Monitoring',
                'description' => 'Enable detailed performance tracking',
                'default_value' => defined('WP_DEBUG') && WP_DEBUG,
                'type' => 'boolean',
                'category' => 'monitoring',
                'stability' => 'stable'
            ),
            'use_modular_rendering' => array(
                'name' => 'Modular Rendering',
                'description' => 'Enable component-based rendering system',
                'default_value' => false,
                'type' => 'boolean',
                'category' => 'rendering',
                'stability' => 'beta'
            ),
            'enable_advanced_caching' => array(
                'name' => 'Advanced Caching',
                'description' => 'Enable intelligent caching system',
                'default_value' => true,
                'type' => 'boolean',
                'category' => 'performance',
                'stability' => 'stable'
            ),
            'use_new_calculation_engine' => array(
                'name' => 'New Calculation Engine',
                'description' => 'Enable improved calculation algorithms',
                'default_value' => false,
                'type' => 'boolean',
                'category' => 'business',
                'stability' => 'experimental'
            ),
            'enable_multi_language' => array(
                'name' => 'Multi-Language Support',
                'description' => 'Enable translation system',
                'default_value' => false,
                'type' => 'boolean',
                'category' => 'i18n',
                'stability' => 'beta'
            ),
            'debug_mode' => array(
                'name' => 'Debug Mode',
                'description' => 'Enable debug output and logging',
                'default_value' => defined('QCC_DEBUG') && QCC_DEBUG,
                'type' => 'boolean',
                'category' => 'debug',
                'stability' => 'stable'
            ),
            'api_rate_limit' => array(
                'name' => 'API Rate Limit',
                'description' => 'Maximum API requests per minute',
                'default_value' => 60,
                'type' => 'integer',
                'category' => 'api',
                'stability' => 'stable',
                'min' => 1,
                'max' => 1000
            ),
            'cache_ttl' => array(
                'name' => 'Cache TTL',
                'description' => 'Cache time-to-live in seconds',
                'default_value' => 3600,
                'type' => 'integer',
                'category' => 'performance',
                'stability' => 'stable',
                'min' => 60,
                'max' => 86400
            )
        );
        
        foreach ($default_flags as $flag_name => $definition) {
            $this->register_flag($flag_name, $definition);
        }
    }
    
    /**
     * Initialize user context
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_user_context() {
        $this->user_context = array(
            'user_id' => get_current_user_id(),
            'user_role' => $this->get_user_role(),
            'is_admin' => current_user_can('manage_options'),
            'site_url' => get_site_url(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : '1.0.0',
            'environment' => $this->detect_environment(),
            'timestamp' => time()
        );
    }
    
    /**
     * Register WordPress hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_hooks() {
        add_action('wp_loaded', array($this, 'refresh_user_context'));
        add_action('user_register', array($this, 'refresh_user_context'));
        add_action('wp_login', array($this, 'refresh_user_context'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('wp_ajax_qcc_toggle_feature_flag', array($this, 'ajax_toggle_flag'));
        }
    }
    
    /**
     * Register a feature flag
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param array  $definition Flag definition
     * @return bool True on success
     */
    public function register_flag($flag_name, $definition = array()) {
        $default_definition = array(
            'name' => ucwords(str_replace('_', ' ', $flag_name)),
            'description' => '',
            'default_value' => false,
            'type' => 'boolean',
            'category' => 'general',
            'stability' => 'experimental',
            'conditions' => array(),
            'rollout_percentage' => 100,
            'user_targeting' => array()
        );
        
        $this->definitions[$flag_name] = array_merge($default_definition, $definition);
        
        // Set default value if not already set
        if (!isset($this->flags[$flag_name])) {
            $this->flags[$flag_name] = $definition['default_value'] ?? false;
        }
        
        // Initialize usage stats
        if (!isset($this->usage_stats[$flag_name])) {
            $this->usage_stats[$flag_name] = array(
                'evaluations' => 0,
                'true_count' => 0,
                'false_count' => 0,
                'last_accessed' => time()
            );
        }
        
        if ($this->debug_mode) {
            error_log("QCC Feature Flags: Registered flag '{$flag_name}'");
        }
        
        return true;
    }
    
    /**
     * Check if feature flag is enabled
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $default_value Default value if flag not found
     * @param array  $context Additional context for evaluation
     * @return mixed Flag value
     */
    public function is_enabled($flag_name, $default_value = false, $context = array()) {
        // Check cache first
        $cache_key = $this->get_cache_key($flag_name, $context);
        if (isset($this->evaluation_cache[$cache_key])) {
            return $this->evaluation_cache[$cache_key];
        }
        
        // Evaluate flag
        $result = $this->evaluate_flag($flag_name, $default_value, $context);
        
        // Cache result
        $this->evaluation_cache[$cache_key] = $result;
        
        // Update statistics
        $this->update_usage_stats($flag_name, $result);
        
        // Trigger listeners
        $this->trigger_listeners($flag_name, $result, $context);
        
        return $result;
    }
    
    /**
     * Evaluate feature flag with conditions
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $default_value Default value
     * @param array  $context Evaluation context
     * @return mixed Flag value
     */
    private function evaluate_flag($flag_name, $default_value, $context) {
        // Check if flag exists
        if (!isset($this->flags[$flag_name])) {
            return $default_value;
        }
        
        $definition = $this->definitions[$flag_name] ?? array();
        $base_value = $this->flags[$flag_name];
        
        // Merge context
        $full_context = array_merge($this->user_context, $context);
        
        // Check rollout percentage
        if (isset($definition['rollout_percentage']) && $definition['rollout_percentage'] < 100) {
            if (!$this->check_rollout_percentage($flag_name, $definition['rollout_percentage'], $full_context)) {
                return false;
            }
        }
        
        // Check user targeting
        if (isset($definition['user_targeting']) && !empty($definition['user_targeting'])) {
            if (!$this->check_user_targeting($definition['user_targeting'], $full_context)) {
                return false;
            }
        }
        
        // Check conditions
        if (isset($definition['conditions']) && !empty($definition['conditions'])) {
            if (!$this->check_conditions($definition['conditions'], $full_context)) {
                return false;
            }
        }
        
        return $base_value;
    }
    
    /**
     * Check rollout percentage
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param int    $percentage Rollout percentage
     * @param array  $context Evaluation context
     * @return bool True if user is in rollout
     */
    private function check_rollout_percentage($flag_name, $percentage, $context) {
        $user_id = $context['user_id'] ?? 0;
        $hash = md5($flag_name . '_' . $user_id . '_' . get_site_url());
        $hash_int = hexdec(substr($hash, 0, 8));
        $user_percentage = ($hash_int % 100) + 1;
        
        return $user_percentage <= $percentage;
    }
    
    /**
     * Check user targeting conditions
     * 
     * @since 3.0.0
     * @param array $targeting User targeting rules
     * @param array $context Evaluation context
     * @return bool True if user matches targeting
     */
    private function check_user_targeting($targeting, $context) {
        foreach ($targeting as $rule_type => $rule_value) {
            switch ($rule_type) {
                case 'user_ids':
                    if (!in_array($context['user_id'], (array) $rule_value)) {
                        return false;
                    }
                    break;
                    
                case 'user_roles':
                    if (!in_array($context['user_role'], (array) $rule_value)) {
                        return false;
                    }
                    break;
                    
                case 'admin_only':
                    if ($rule_value && !$context['is_admin']) {
                        return false;
                    }
                    break;
                    
                case 'exclude_user_ids':
                    if (in_array($context['user_id'], (array) $rule_value)) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }
    
    /**
     * Check general conditions
     * 
     * @since 3.0.0
     * @param array $conditions Conditions to check
     * @param array $context Evaluation context
     * @return bool True if all conditions met
     */
    private function check_conditions($conditions, $context) {
        foreach ($conditions as $condition) {
            if (!$this->evaluate_condition($condition, $context)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Evaluate a single condition
     * 
     * @since 3.0.0
     * @param array $condition Condition definition
     * @param array $context Evaluation context
     * @return bool True if condition met
     */
    private function evaluate_condition($condition, $context) {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? '';
        
        $context_value = $context[$field] ?? null;
        
        switch ($operator) {
            case '==':
                return $context_value == $value;
            case '!=':
                return $context_value != $value;
            case '>':
                return $context_value > $value;
            case '<':
                return $context_value < $value;
            case '>=':
                return $context_value >= $value;
            case '<=':
                return $context_value <= $value;
            case 'in':
                return in_array($context_value, (array) $value);
            case 'not_in':
                return !in_array($context_value, (array) $value);
            case 'contains':
                return strpos($context_value, $value) !== false;
            case 'starts_with':
                return strpos($context_value, $value) === 0;
            case 'ends_with':
                return substr($context_value, -strlen($value)) === $value;
            default:
                return false;
        }
    }
    
    /**
     * Set feature flag value
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $value Flag value
     * @return bool True on success
     */
    public function set_flag($flag_name, $value) {
        $definition = $this->definitions[$flag_name] ?? array();
        
        // Validate value type
        if (isset($definition['type'])) {
            $value = $this->validate_flag_value($value, $definition);
        }
        
        $old_value = $this->flags[$flag_name] ?? null;
        $this->flags[$flag_name] = $value;
        
        // Clear cache
        $this->clear_evaluation_cache($flag_name);
        
        // Save to database
        $success = $this->save_flags();
        
        if ($success) {
            do_action('qcc_feature_flag_changed', $flag_name, $value, $old_value);
            
            if ($this->debug_mode) {
                error_log("QCC Feature Flags: Set flag '{$flag_name}' to " . var_export($value, true));
            }
        }
        
        return $success;
    }
    
    /**
     * Validate flag value according to type
     * 
     * @since 3.0.0
     * @param mixed $value Value to validate
     * @param array $definition Flag definition
     * @return mixed Validated value
     */
    private function validate_flag_value($value, $definition) {
        $type = $definition['type'] ?? 'boolean';
        
        switch ($type) {
            case 'boolean':
                return (bool) $value;
                
            case 'integer':
                $int_value = (int) $value;
                if (isset($definition['min'])) {
                    $int_value = max($int_value, $definition['min']);
                }
                if (isset($definition['max'])) {
                    $int_value = min($int_value, $definition['max']);
                }
                return $int_value;
                
            case 'float':
                $float_value = (float) $value;
                if (isset($definition['min'])) {
                    $float_value = max($float_value, $definition['min']);
                }
                if (isset($definition['max'])) {
                    $float_value = min($float_value, $definition['max']);
                }
                return $float_value;
                
            case 'string':
                return (string) $value;
                
            case 'array':
                return is_array($value) ? $value : array($value);
                
            default:
                return $value;
        }
    }
    
    /**
     * Get feature flag value
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $default_value Default value
     * @return mixed Flag value
     */
    public function get_flag($flag_name, $default_value = null) {
        return $this->flags[$flag_name] ?? $default_value;
    }
    
    /**
     * Toggle boolean feature flag
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @return bool New flag value
     */
    public function toggle_flag($flag_name) {
        $current_value = $this->get_flag($flag_name, false);
        $new_value = !$current_value;
        $this->set_flag($flag_name, $new_value);
        return $new_value;
    }
    
    /**
     * Delete feature flag
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @return bool True on success
     */
    public function delete_flag($flag_name) {
        if (!isset($this->flags[$flag_name])) {
            return false;
        }
        
        unset($this->flags[$flag_name]);
        unset($this->definitions[$flag_name]);
        unset($this->usage_stats[$flag_name]);
        
        $this->clear_evaluation_cache($flag_name);
        
        return $this->save_flags();
    }
    
    /**
     * Get all feature flags
     * 
     * @since 3.0.0
     * @param string $category Optional category filter
     * @return array All flags or filtered by category
     */
    public function get_all_flags($category = null) {
        if ($category === null) {
            return $this->flags;
        }
        
        $filtered_flags = array();
        foreach ($this->flags as $flag_name => $value) {
            $definition = $this->definitions[$flag_name] ?? array();
            if (($definition['category'] ?? 'general') === $category) {
                $filtered_flags[$flag_name] = $value;
            }
        }
        
        return $filtered_flags;
    }
    
    /**
     * Get flag definition
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @return array|null Flag definition or null
     */
    public function get_flag_definition($flag_name) {
        return $this->definitions[$flag_name] ?? null;
    }
    
    /**
     * Get all flag definitions
     * 
     * @since 3.0.0
     * @param string $category Optional category filter
     * @return array All definitions or filtered by category
     */
    public function get_all_definitions($category = null) {
        if ($category === null) {
            return $this->definitions;
        }
        
        $filtered_definitions = array();
        foreach ($this->definitions as $flag_name => $definition) {
            if (($definition['category'] ?? 'general') === $category) {
                $filtered_definitions[$flag_name] = $definition;
            }
        }
        
        return $filtered_definitions;
    }
    
    /**
     * Get flag usage statistics
     * 
     * @since 3.0.0
     * @param string $flag_name Optional specific flag
     * @return array Usage statistics
     */
    public function get_usage_statistics($flag_name = null) {
        if ($flag_name !== null) {
            return $this->usage_stats[$flag_name] ?? null;
        }
        
        return $this->usage_stats;
    }
    
    /**
     * Update usage statistics
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $result Evaluation result
     * @return void
     */
    private function update_usage_stats($flag_name, $result) {
        if (!isset($this->usage_stats[$flag_name])) {
            $this->usage_stats[$flag_name] = array(
                'evaluations' => 0,
                'true_count' => 0,
                'false_count' => 0,
                'last_accessed' => time()
            );
        }
        
        $this->usage_stats[$flag_name]['evaluations']++;
        $this->usage_stats[$flag_name]['last_accessed'] = time();
        
        if ($result) {
            $this->usage_stats[$flag_name]['true_count']++;
        } else {
            $this->usage_stats[$flag_name]['false_count']++;
        }
    }
    
    /**
     * Clear evaluation cache
     * 
     * @since 3.0.0
     * @param string $flag_name Optional specific flag
     * @return void
     */
    private function clear_evaluation_cache($flag_name = null) {
        if ($flag_name === null) {
            $this->evaluation_cache = array();
        } else {
            foreach (array_keys($this->evaluation_cache) as $cache_key) {
                if (strpos($cache_key, $flag_name . '_') === 0) {
                    unset($this->evaluation_cache[$cache_key]);
                }
            }
        }
    }
    
    /**
     * Get cache key for flag evaluation
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param array  $context Evaluation context
     * @return string Cache key
     */
    private function get_cache_key($flag_name, $context) {
        $context_hash = md5(serialize(array_merge($this->user_context, $context)));
        return $flag_name . '_' . $context_hash;
    }
    
    /**
     * Add evaluation listener
     * 
     * @since 3.0.0
     * @param string   $flag_name Flag name (or '*' for all)
     * @param callable $callback Listener callback
     * @return void
     */
    public function add_listener($flag_name, $callback) {
        if (!is_callable($callback)) {
            return;
        }
        
        if (!isset($this->listeners[$flag_name])) {
            $this->listeners[$flag_name] = array();
        }
        
        $this->listeners[$flag_name][] = $callback;
    }
    
    /**
     * Remove evaluation listener
     * 
     * @since 3.0.0
     * @param string   $flag_name Flag name
     * @param callable $callback Listener callback
     * @return void
     */
    public function remove_listener($flag_name, $callback) {
        if (!isset($this->listeners[$flag_name])) {
            return;
        }
        
        $key = array_search($callback, $this->listeners[$flag_name], true);
        if ($key !== false) {
            unset($this->listeners[$flag_name][$key]);
        }
    }
    
    /**
     * Trigger evaluation listeners
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $result Evaluation result
     * @param array  $context Evaluation context
     * @return void
     */
    private function trigger_listeners($flag_name, $result, $context) {
        // Trigger specific flag listeners
        if (isset($this->listeners[$flag_name])) {
            foreach ($this->listeners[$flag_name] as $callback) {
                call_user_func($callback, $flag_name, $result, $context);
            }
        }
        
        // Trigger global listeners
        if (isset($this->listeners['*'])) {
            foreach ($this->listeners['*'] as $callback) {
                call_user_func($callback, $flag_name, $result, $context);
            }
        }
    }
    
    /**
     * Refresh user context
     * 
     * @since 3.0.0
     * @return void
     */
    public function refresh_user_context() {
        $this->init_user_context();
        $this->evaluation_cache = array(); // Clear cache when context changes
    }
    
    /**
     * Get user role
     * 
     * @since 3.0.0
     * @return string User role
     */
    private function get_user_role() {
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'guest';
    }
    
    /**
     * Detect environment
     * 
     * @since 3.0.0
     * @return string Environment type
     */
    private function detect_environment() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return 'development';
        }
        
        if (defined('STAGING_ENVIRONMENT') && STAGING_ENVIRONMENT) {
            return 'staging';
        }
        
        return 'production';
    }
    
    /**
     * Export flags for external systems
     * 
     * @since 3.0.0
     * @param array $options Export options
     * @return array Exported flag data
     */
    public function export_flags($options = array()) {
        $defaults = array(
            'include_definitions' => true,
            'include_stats' => false,
            'category_filter' => null,
            'stability_filter' => null
        );
        
        $options = array_merge($defaults, $options);
        
        $export_data = array(
            'flags' => $this->flags,
            'timestamp' => time(),
            'version' => defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : '1.0.0'
        );
        
        if ($options['include_definitions']) {
            $export_data['definitions'] = $this->definitions;
        }
        
        if ($options['include_stats']) {
            $export_data['statistics'] = $this->usage_stats;
        }
        
        // Apply filters
        if ($options['category_filter'] || $options['stability_filter']) {
            $filtered_flags = array();
            $filtered_definitions = array();
            
            foreach ($this->flags as $flag_name => $value) {
                $definition = $this->definitions[$flag_name] ?? array();
                
                // Category filter
                if ($options['category_filter'] && 
                    ($definition['category'] ?? 'general') !== $options['category_filter']) {
                    continue;
                }
                
                // Stability filter
                if ($options['stability_filter'] && 
                    ($definition['stability'] ?? 'experimental') !== $options['stability_filter']) {
                    continue;
                }
                
                $filtered_flags[$flag_name] = $value;
                if ($options['include_definitions']) {
                    $filtered_definitions[$flag_name] = $definition;
                }
            }
            
            $export_data['flags'] = $filtered_flags;
            if ($options['include_definitions']) {
                $export_data['definitions'] = $filtered_definitions;
            }
        }
        
        return $export_data;
    }
    
    /**
     * Import flags from external data
     * 
     * @since 3.0.0
     * @param array $import_data Import data
     * @param array $options Import options
     * @return bool True on success
     */
    public function import_flags($import_data, $options = array()) {
        $defaults = array(
            'overwrite_existing' => false,
            'import_definitions' => true,
            'validate_data' => true
        );
        
        $options = array_merge($defaults, $options);
        
        if (!is_array($import_data) || !isset($import_data['flags'])) {
            return false;
        }
        
        $imported_count = 0;
        
        foreach ($import_data['flags'] as $flag_name => $value) {
            // Skip existing flags if not overwriting
            if (!$options['overwrite_existing'] && isset($this->flags[$flag_name])) {
                continue;
            }
            
            // Import definition if available
            if ($options['import_definitions'] && 
                isset($import_data['definitions'][$flag_name])) {
                $this->definitions[$flag_name] = $import_data['definitions'][$flag_name];
            }
            
            // Validate and set flag
            if ($options['validate_data']) {
                $definition = $this->definitions[$flag_name] ?? array();
                $value = $this->validate_flag_value($value, $definition);
            }
            
            $this->flags[$flag_name] = $value;
            $imported_count++;
        }
        
        if ($imported_count > 0) {
            $this->save_flags();
            
            if ($this->debug_mode) {
                error_log("QCC Feature Flags: Imported {$imported_count} flags");
            }
        }
        
        return $imported_count > 0;
    }
    
    /**
     * Add admin menu for feature flags
     * 
     * @since 3.0.0
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'qcc-admin',
            'Feature Flags',
            'Feature Flags',
            'manage_options',
            'qcc-feature-flags',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Render admin page
     * 
     * @since 3.0.0
     * @return void
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        // Handle form submission
        if (isset($_POST['qcc_feature_flags_nonce']) && 
            wp_verify_nonce($_POST['qcc_feature_flags_nonce'], 'qcc_feature_flags')) {
            $this->handle_admin_form_submission();
        }
        
        $this->render_admin_page();
    }
    
    /**
     * Handle admin form submission
     * 
     * @since 3.0.0
     * @return void
     */
    private function handle_admin_form_submission() {
        foreach ($this->definitions as $flag_name => $definition) {
            if (isset($_POST['flags'][$flag_name])) {
                $new_value = $_POST['flags'][$flag_name];
                
                // Convert checkbox values
                if ($definition['type'] === 'boolean') {
                    $new_value = $new_value === '1';
                }
                
                $this->set_flag($flag_name, $new_value);
            }
        }
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Feature flags updated successfully!</p></div>';
        });
    }
    
    /**
     * Render admin page HTML
     * 
     * @since 3.0.0
     * @return void
     */
    private function render_admin_page() {
        $categories = array();
        foreach ($this->definitions as $definition) {
            $category = $definition['category'] ?? 'general';
            if (!in_array($category, $categories)) {
                $categories[] = $category;
            }
        }
        
        ?>
        <div class="wrap">
            <h1>QCC Feature Flags</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('qcc_feature_flags', 'qcc_feature_flags_nonce'); ?>
                
                <?php foreach ($categories as $category): ?>
                    <h2><?php echo esc_html(ucfirst($category)); ?></h2>
                    <table class="form-table">
                        <?php foreach ($this->definitions as $flag_name => $definition): ?>
                            <?php if (($definition['category'] ?? 'general') !== $category) continue; ?>
                            
                            <tr>
                                <th scope="row">
                                    <label for="flag_<?php echo esc_attr($flag_name); ?>">
                                        <?php echo esc_html($definition['name'] ?? $flag_name); ?>
                                    </label>
                                </th>
                                <td>
                                    <?php $this->render_flag_input($flag_name, $definition); ?>
                                    
                                    <?php if (!empty($definition['description'])): ?>
                                        <p class="description">
                                            <?php echo esc_html($definition['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p class="description">
                                        <strong>Stability:</strong> <?php echo esc_html($definition['stability'] ?? 'experimental'); ?>
                                        <?php if (isset($this->usage_stats[$flag_name])): ?>
                                            | <strong>Evaluations:</strong> <?php echo esc_html($this->usage_stats[$flag_name]['evaluations']); ?>
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
                
                <?php submit_button('Update Feature Flags'); ?>
            </form>
            
            <?php if ($this->debug_mode): ?>
                <h2>Debug Information</h2>
                <textarea readonly style="width: 100%; height: 200px;">
<?php echo esc_textarea(json_encode($this->export_flags(array('include_stats' => true)), JSON_PRETTY_PRINT)); ?>
                </textarea>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render flag input field
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param array  $definition Flag definition
     * @return void
     */
    private function render_flag_input($flag_name, $definition) {
        $current_value = $this->get_flag($flag_name, $definition['default_value'] ?? false);
        $type = $definition['type'] ?? 'boolean';
        
        switch ($type) {
            case 'boolean':
                ?>
                <label>
                    <input type="checkbox" 
                           name="flags[<?php echo esc_attr($flag_name); ?>]" 
                           value="1" 
                           <?php checked($current_value); ?> />
                    Enabled
                </label>
                <?php
                break;
                
            case 'integer':
                ?>
                <input type="number" 
                       name="flags[<?php echo esc_attr($flag_name); ?>]" 
                       value="<?php echo esc_attr($current_value); ?>"
                       <?php if (isset($definition['min'])): ?>min="<?php echo esc_attr($definition['min']); ?>"<?php endif; ?>
                       <?php if (isset($definition['max'])): ?>max="<?php echo esc_attr($definition['max']); ?>"<?php endif; ?> />
                <?php
                break;
                
            case 'string':
                ?>
                <input type="text" 
                       name="flags[<?php echo esc_attr($flag_name); ?>]" 
                       value="<?php echo esc_attr($current_value); ?>" 
                       class="regular-text" />
                <?php
                break;
                
            default:
                ?>
                <textarea name="flags[<?php echo esc_attr($flag_name); ?>]" 
                          rows="3" cols="50"><?php echo esc_textarea(is_array($current_value) ? json_encode($current_value) : $current_value); ?></textarea>
                <?php
                break;
        }
    }
    
    /**
     * AJAX handler for toggling flags
     * 
     * @since 3.0.0
     * @return void
     */
    public function ajax_toggle_flag() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $flag_name = sanitize_text_field($_POST['flag_name'] ?? '');
        if (empty($flag_name)) {
            wp_die('Invalid flag name');
        }
        
        $new_value = $this->toggle_flag($flag_name);
        
        wp_send_json_success(array(
            'flag_name' => $flag_name,
            'new_value' => $new_value
        ));
    }
    
    /**
     * Magic method to check flag
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @return bool Flag value
     */
    public function __get($flag_name) {
        return $this->is_enabled($flag_name);
    }
    
    /**
     * Magic method to set flag
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @param mixed  $value Flag value
     * @return void
     */
    public function __set($flag_name, $value) {
        $this->set_flag($flag_name, $value);
    }
    
    /**
     * Magic method to check if flag exists
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @return bool True if exists
     */
    public function __isset($flag_name) {
        return isset($this->flags[$flag_name]);
    }
    
    /**
     * Magic method to unset flag
     * 
     * @since 3.0.0
     * @param string $flag_name Flag name
     * @return void
     */
    public function __unset($flag_name) {
        $this->delete_flag($flag_name);
    }
}
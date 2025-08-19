<?php
/**
 * QCC AJAX Handler
 * 
 * Handles AJAX requests for the Quality Cost Calculator plugin.
 * Provides secure AJAX endpoints with proper nonce verification.
 * 
 * @package QualityCostCalculator
 * @subpackage API
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC AJAX Handler Class
 * 
 * Manages all AJAX endpoints with proper security,
 * validation, and response handling.
 */
class QCC_AJAX_Handler {
    
    /**
     * Main API instance
     * 
     * @var QCC_API
     */
    private $api;
    
    /**
     * Settings service
     * 
     * @var QCC_Settings_Service
     */
    private $settings;
    
    /**
     * Registered AJAX actions
     * 
     * @var array
     */
    private $ajax_actions = array();
    
    /**
     * Nonce actions
     * 
     * @var array
     */
    private $nonce_actions = array();
    
    /**
     * Constructor
     * 
     * @param QCC_API $api Main API instance
     */
    public function __construct($api) {
        $this->api = $api;
        $this->load_dependencies();
        $this->setup_ajax_actions();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        try {
            $container = QCC_Service_Container::get_instance();
            $this->settings = $container->get('settings');
        } catch (Exception $e) {
            $this->settings = new QCC_Settings_Service();
        }
    }
    
    /**
     * Setup AJAX actions configuration
     */
    private function setup_ajax_actions() {
        $this->ajax_actions = array(
            'qcc_calculate' => array(
                'callback' => array($this, 'handle_calculate'),
                'nopriv' => true, // Allow non-logged users
                'nonce' => 'qcc_calculate_nonce'
            ),
            'qcc_validate_input' => array(
                'callback' => array($this, 'handle_validate'),
                'nopriv' => true,
                'nonce' => 'qcc_validate_nonce'
            ),
            'qcc_export_data' => array(
                'callback' => array($this, 'handle_export'),
                'nopriv' => false, // Require login
                'nonce' => 'qcc_export_nonce'
            ),
            'qcc_import_data' => array(
                'callback' => array($this, 'handle_import'),
                'nopriv' => false,
                'nonce' => 'qcc_import_nonce',
                'capability' => 'manage_options'
            ),
            'qcc_save_settings' => array(
                'callback' => array($this, 'handle_save_settings'),
                'nopriv' => false,
                'nonce' => 'qcc_settings_nonce',
                'capability' => 'manage_options'
            ),
            'qcc_get_templates' => array(
                'callback' => array($this, 'handle_get_templates'),
                'nopriv' => true,
                'nonce' => 'qcc_templates_nonce'
            ),
            'qcc_health_check' => array(
                'callback' => array($this, 'handle_health_check'),
                'nopriv' => false,
                'nonce' => 'qcc_health_nonce',
                'capability' => 'manage_options'
            ),
            'qcc_clear_cache' => array(
                'callback' => array($this, 'handle_clear_cache'),
                'nopriv' => false,
                'nonce' => 'qcc_cache_nonce',
                'capability' => 'manage_options'
            ),
            'qcc_get_statistics' => array(
                'callback' => array($this, 'handle_get_statistics'),
                'nopriv' => false,
                'nonce' => 'qcc_stats_nonce',
                'capability' => 'view_qcc_stats'
            )
        );
        
        // Setup nonce actions
        foreach ($this->ajax_actions as $action => $config) {
            if (isset($config['nonce'])) {
                $this->nonce_actions[$action] = $config['nonce'];
            }
        }
    }
    
    /**
     * Initialize AJAX handler
     */
    public function init() {
        // Register AJAX actions
        foreach ($this->ajax_actions as $action => $config) {
            // Register for logged-in users
            add_action('wp_ajax_' . $action, $config['callback']);
            
            // Register for non-logged users if allowed
            if (!empty($config['nopriv'])) {
                add_action('wp_ajax_nopriv_' . $action, $config['callback']);
            }
        }
        
        // Enqueue AJAX script data
        add_action('wp_enqueue_scripts', array($this, 'localize_ajax_data'));
        add_action('admin_enqueue_scripts', array($this, 'localize_ajax_data'));
    }
    
    /**
     * Localize AJAX data for frontend
     */
    public function localize_ajax_data() {
        $ajax_data = array(
            'url' => admin_url('admin-ajax.php'),
            'nonces' => array()
        );
        
        // Generate nonces for each action
        foreach ($this->nonce_actions as $action => $nonce_action) {
            $ajax_data['nonces'][$action] = wp_create_nonce($nonce_action);
        }
        
        // Add additional configuration
        $ajax_data['config'] = array(
            'timeout' => $this->settings->get('performance.ajax_timeout', 30000),
            'retry_attempts' => $this->settings->get('performance.ajax_retry_attempts', 3),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        );
        
        wp_localize_script('qcc-frontend', 'qcc_ajax', $ajax_data);
    }
    
    /**
     * Handle calculation AJAX request
     */
    public function handle_calculate() {
        try {
            // Verify nonce
            $this->verify_nonce('qcc_calculate');
            
            // Check rate limits
            if (!$this->check_rate_limits('calculate')) {
                wp_send_json_error(array(
                    'message' => __('Too many requests. Please try again later.', 'quality-cost-calculator'),
                    'code' => 'rate_limit_exceeded'
                ), 429);
            }
            
            // Get and validate input
            $input_data = $this->get_post_data(array(
                'prevention_costs' => 0,
                'appraisal_costs' => 0,
                'internal_defect_costs' => 0,
                'external_defect_costs' => 0,
                'currency' => 'USD',
                'unit' => '1'
            ));
            
            // Validate required fields
            $validation_errors = $this->validate_calculation_input($input_data);
            if (!empty($validation_errors)) {
                wp_send_json_error(array(
                    'message' => __('Validation failed', 'quality-cost-calculator'),
                    'errors' => $validation_errors
                ), 400);
            }
            
            // Load calculator
            if (!class_exists('QCC_Calculator')) {
                require_once plugin_dir_path(__FILE__) . '../class-qcc-calculator.php';
            }
            
            $calculator = new QCC_Calculator();
            
            // Perform calculation
            $start_time = microtime(true);
            $results = $calculator->calculate($input_data);
            $calculation_time = microtime(true) - $start_time;
            
            // Format response
            $response_data = array(
                'success' => true,
                'data' => $results,
                'meta' => array(
                    'calculation_time' => round($calculation_time * 1000, 2), // milliseconds
                    'currency' => $input_data['currency'],
                    'unit' => $input_data['unit'],
                    'timestamp' => current_time('mysql')
                )
            );
            
            // Log successful calculation
            $this->log_ajax_activity('calculate', 'success', $input_data);
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            $this->handle_ajax_error('calculate', $e);
        }
    }
    
    /**
     * Handle input validation AJAX request
     */
    public function handle_validate() {
        try {
            $this->verify_nonce('qcc_validate_input');
            
            $input_data = $this->get_post_data();
            $validation_type = sanitize_text_field($_POST['validation_type'] ?? 'calculation');
            
            // Load validator
            if (!class_exists('QCC_Validator')) {
                require_once plugin_dir_path(__FILE__) . '../class-qcc-validator.php';
            }
            
            $validator = new QCC_Validator();
            
            // Perform validation based on type
            switch ($validation_type) {
                case 'calculation':
                    $validation_result = $this->validate_calculation_input($input_data);
                    break;
                    
                case 'settings':
                    $validation_result = $this->validate_settings_input($input_data);
                    break;
                    
                default:
                    $validation_result = $validator->validate($input_data);
            }
            
            $is_valid = empty($validation_result) || (!is_wp_error($validation_result) && $validation_result === true);
            
            wp_send_json_success(array(
                'valid' => $is_valid,
                'errors' => $is_valid ? array() : $validation_result,
                'validation_type' => $validation_type
            ));
            
        } catch (Exception $e) {
            $this->handle_ajax_error('validate', $e);
        }
    }
    
    /**
     * Handle data export AJAX request
     */
    public function handle_export() {
        try {
            $this->verify_nonce('qcc_export_data');
            $this->check_capability('edit_posts');
            
            $export_data = $this->get_post_data();
            $format = sanitize_text_field($_POST['format'] ?? 'json');
            $data_type = sanitize_text_field($_POST['data_type'] ?? 'calculation_data');
            
            // Validate format
            if (!in_array($format, array('json', 'csv', 'xml'))) {
                wp_send_json_error(array(
                    'message' => __('Invalid export format', 'quality-cost-calculator')
                ), 400);
            }
            
            // Generate export
            $exported_data = $this->generate_export($export_data, $format, $data_type);
            
            wp_send_json_success(array(
                'data' => $exported_data,
                'format' => $format,
                'filename' => $this->generate_export_filename($format, $data_type),
                'size' => strlen($exported_data)
            ));
            
        } catch (Exception $e) {
            $this->handle_ajax_error('export', $e);
        }
    }
    
    /**
     * Handle data import AJAX request
     */
    public function handle_import() {
        try {
            $this->verify_nonce('qcc_import_data');
            $this->check_capability('manage_options');
            
            // Handle file upload
            if (!empty($_FILES['import_file'])) {
                $uploaded_file = $_FILES['import_file'];
                
                // Validate file
                $validation = $this->validate_import_file($uploaded_file);
                if (!$validation['valid']) {
                    wp_send_json_error(array(
                        'message' => __('Invalid import file', 'quality-cost-calculator'),
                        'errors' => $validation['errors']
                    ), 400);
                }
                
                // Process import
                $import_service = new QCC_Import_Service();
                $import_result = $import_service->import_from_file(
                    $uploaded_file['tmp_name'],
                    array(
                        'data_type' => sanitize_text_field($_POST['data_type'] ?? 'calculation_data'),
                        'import_type' => sanitize_text_field($_POST['import_type'] ?? 'merge')
                    )
                );
                
                if (!$import_result['success']) {
                    wp_send_json_error(array(
                        'message' => $import_result['error'] ?? __('Import failed', 'quality-cost-calculator'),
                        'errors' => $import_result['errors'] ?? array()
                    ), 400);
                }
                
                wp_send_json_success($import_result);
                
            } else {
                wp_send_json_error(array(
                    'message' => __('No file uploaded', 'quality-cost-calculator')
                ), 400);
            }
            
        } catch (Exception $e) {
            $this->handle_ajax_error('import', $e);
        }
    }
    
    /**
     * Handle save settings AJAX request
     */
    public function handle_save_settings() {
        try {
            $this->verify_nonce('qcc_save_settings');
            $this->check_capability('manage_options');
            
            $settings_data = $this->get_post_data();
            $settings_group = sanitize_text_field($_POST['settings_group'] ?? '');
            
            // Validate settings
            $validation = $this->settings->validate_settings_structure($settings_data);
            if (!$validation['valid']) {
                wp_send_json_error(array(
                    'message' => __('Settings validation failed', 'quality-cost-calculator'),
                    'errors' => $validation['errors']
                ), 400);
            }
            
            // Create backup before saving
            $backup_name = 'ajax_save_' . date('Y_m_d_H_i_s');
            $this->settings->backup_settings($backup_name);
            
            // Save settings
            if ($settings_group) {
                $success = $this->settings->set($settings_group, $settings_data);
            } else {
                $success = $this->settings->update_multiple($settings_data, true);
            }
            
            if (!$success) {
                wp_send_json_error(array(
                    'message' => __('Failed to save settings', 'quality-cost-calculator')
                ), 500);
            }
            
            wp_send_json_success(array(
                'message' => __('Settings saved successfully', 'quality-cost-calculator'),
                'backup_created' => $backup_name,
                'settings_count' => count($this->flatten_array($settings_data))
            ));
            
        } catch (Exception $e) {
            $this->handle_ajax_error('save_settings', $e);
        }
    }
    
    /**
     * Handle get templates AJAX request
     */
    public function handle_get_templates() {
        try {
            $this->verify_nonce('qcc_get_templates');
            
            $template_type = sanitize_text_field($_POST['template_type'] ?? '');
            $include_content = !empty($_POST['include_content']);
            
            $template_service = new QCC_Template_Service();
            $templates = $template_service->get_template_list($template_type);
            
            $template_data = array();
            foreach ($templates as $template) {
                $template_info = array(
                    'name' => $template,
                    'exists' => $template_service->template_exists($template),
                    'type' => $this->get_template_type($template)
                );
                
                if ($include_content && $template_info['exists']) {
                    $template_info['content'] = $template_service->render($template, array(), true);
                }
                
                $template_data[] = $template_info;
            }
            
            wp_send_json_success(array(
                'templates' => $template_data,
                'total' => count($template_data),
                'template_type' => $template_type
            ));
            
        } catch (Exception $e) {
            $this->handle_ajax_error('get_templates', $e);
        }
    }
    
    /**
     * Handle health check AJAX request
     */
    public function handle_health_check() {
        try {
            $this->verify_nonce('qcc_health_check');
            $this->check_capability('manage_options');
            
            $health_data = array(
                'status' => 'healthy',
                'timestamp' => current_time('mysql'),
                'checks' => array(
                    'php_version' => $this->check_php_version(),
                    'wp_version' => $this->check_wp_version(),
                    'memory_usage' => $this->check_memory_usage(),
                    'database' => $this->check_database_connection(),
                    'file_permissions' => $this->check_file_permissions(),
                    'plugin_files' => $this->check_plugin_files(),
                    'settings_health' => $this->settings->get_health_check()
                )
            );
            
            // Determine overall status
            $issues = 0;
            foreach ($health_data['checks'] as $check) {
                if (is_array($check) && isset($check['status']) && $check['status'] !== 'pass') {
                    $issues++;
                }
            }
            
            if ($issues > 0) {
                $health_data['status'] = $issues > 2 ? 'critical' : 'warning';
            }
            
            wp_send_json_success($health_data);
            
        } catch (Exception $e) {
            $this->handle_ajax_error('health_check', $e);
        }
    }
    
    /**
     * Handle clear cache AJAX request
     */
    public function handle_clear_cache() {
        try {
            $this->verify_nonce('qcc_clear_cache');
            $this->check_capability('manage_options');
            
            $cache_type = sanitize_text_field($_POST['cache_type'] ?? 'all');
            
            $cleared = array();
            
            switch ($cache_type) {
                case 'settings':
                    $this->settings->clear_all_caches();
                    $cleared[] = 'settings';
                    break;
                    
                case 'templates':
                    $template_service = new QCC_Template_Service();
                    $template_service->clear_cache();
                    $cleared[] = 'templates';
                    break;
                    
                case 'wp_cache':
                    wp_cache_flush();
                    $cleared[] = 'wp_cache';
                    break;
                    
                case 'all':
                default:
                    $this->settings->clear_all_caches();
                    $template_service = new QCC_Template_Service();
                    $template_service->clear_cache();
                    wp_cache_flush();
                    $cleared = array('settings', 'templates', 'wp_cache');
                    break;
            }
            
            // Clear opcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $cleared[] = 'opcache';
            }
            
            wp_send_json_success(array(
                'message' => __('Cache cleared successfully', 'quality-cost-calculator'),
                'cleared' => $cleared,
                'timestamp' => current_time('mysql')
            ));
            
        } catch (Exception $e) {
            $this->handle_ajax_error('clear_cache', $e);
        }
    }
    
    /**
     * Handle get statistics AJAX request
     */
    public function handle_get_statistics() {
        try {
            $this->verify_nonce('qcc_get_statistics');
            $this->check_capability('view_qcc_stats');
            
            $timeframe = sanitize_text_field($_POST['timeframe'] ?? '24h');
            $stat_type = sanitize_text_field($_POST['stat_type'] ?? 'all');
            
            $stats = array(
                'timeframe' => $timeframe,
                'generated_at' => current_time('mysql')
            );
            
            // Add different statistics based on type
            switch ($stat_type) {
                case 'calculations':
                    $stats['calculations'] = $this->get_calculation_stats($timeframe);
                    break;
                    
                case 'performance':
                    $stats['performance'] = $this->get_performance_stats($timeframe);
                    break;
                    
                case 'usage':
                    $stats['usage'] = $this->get_usage_stats($timeframe);
                    break;
                    
                case 'all':
                default:
                    $stats['calculations'] = $this->get_calculation_stats($timeframe);
                    $stats['performance'] = $this->get_performance_stats($timeframe);
                    $stats['usage'] = $this->get_usage_stats($timeframe);
                    break;
            }
            
            wp_send_json_success($stats);
            
        } catch (Exception $e) {
            $this->handle_ajax_error('get_statistics', $e);
        }
    }
    
    /**
     * Private helper methods
     */
    
    private function verify_nonce($action) {
        $nonce_action = $this->nonce_actions[$action] ?? $action;
        $nonce_value = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? '';
        
        if (!wp_verify_nonce($nonce_value, $nonce_action)) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'quality-cost-calculator'),
                'code' => 'invalid_nonce'
            ), 403);
        }
    }
    
    private function check_capability($capability) {
        if (!current_user_can($capability)) {
            wp_send_json_error(array(
                'message' => __('Insufficient permissions', 'quality-cost-calculator'),
                'code' => 'insufficient_permissions'
            ), 403);
        }
    }
    
    private function check_rate_limits($action) {
        if (!$this->settings->get('security.enable_rate_limiting', false)) {
            return true;
        }
        
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = $user_id ? "user_{$user_id}" : "ip_{$ip_address}";
        
        $transient_key = "qcc_ajax_rate_limit_{$action}_{$key}";
        $current_count = get_transient($transient_key);
        $max_requests = $this->settings->get('security.max_ajax_requests_per_minute', 30);
        
        if ($current_count === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return true;
        }
        
        if ($current_count >= $max_requests) {
            return false;
        }
        
        set_transient($transient_key, $current_count + 1, MINUTE_IN_SECONDS);
        return true;
    }
    
    private function get_post_data($defaults = array()) {
        $data = array();
        
        foreach ($_POST as $key => $value) {
            if ($key === 'action' || $key === 'nonce' || $key === '_wpnonce') {
                continue;
            }
            
            $clean_key = sanitize_key($key);
            
            if (is_array($value)) {
                $data[$clean_key] = $this->sanitize_array($value);
            } else {
                $data[$clean_key] = sanitize_text_field($value);
            }
        }
        
        return array_merge($defaults, $data);
    }
    
    private function sanitize_array($array) {
        $sanitized = array();
        
        foreach ($array as $key => $value) {
            $clean_key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$clean_key] = $this->sanitize_array($value);
            } elseif (is_numeric($value)) {
                $sanitized[$clean_key] = is_float($value) ? floatval($value) : intval($value);
            } else {
                $sanitized[$clean_key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    private function validate_calculation_input($data) {
        $errors = array();
        
        // Check required fields
        $required_fields = array('prevention_costs', 'appraisal_costs', 'internal_defect_costs', 'external_defect_costs');
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || !is_numeric($data[$field])) {
                $errors[$field] = sprintf(__('%s must be a valid number', 'quality-cost-calculator'), $field);
            } elseif ($data[$field] < 0) {
                $errors[$field] = sprintf(__('%s cannot be negative', 'quality-cost-calculator'), $field);
            }
        }
        
        // Validate currency
        if (isset($data['currency'])) {
            $valid_currencies = array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD');
            if (!in_array($data['currency'], $valid_currencies)) {
                $errors['currency'] = __('Invalid currency code', 'quality-cost-calculator');
            }
        }
        
        // Validate unit
        if (isset($data['unit'])) {
            $valid_units = array('1', '1000', '1000000');
            if (!in_array($data['unit'], $valid_units)) {
                $errors['unit'] = __('Invalid unit value', 'quality-cost-calculator');
            }
        }
        
        return $errors;
    }
    
    private function validate_settings_input($data) {
        return $this->settings->validate_settings_structure($data);
    }
    
    private function validate_import_file($file) {
        $validation = array(
            'valid' => true,
            'errors' => array()
        );
        
        // Check file upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $validation['valid'] = false;
            $validation['errors'][] = __('File upload error', 'quality-cost-calculator');
            return $validation;
        }
        
        // Check file size
        $max_size = $this->settings->get('import.max_file_size', 5 * 1024 * 1024); // 5MB default
        if ($file['size'] > $max_size) {
            $validation['valid'] = false;
            $validation['errors'][] = __('File too large', 'quality-cost-calculator');
        }
        
        // Check file type
        $allowed_types = array('application/json', 'text/csv', 'application/xml', 'text/xml');
        if (!in_array($file['type'], $allowed_types)) {
            $validation['valid'] = false;
            $validation['errors'][] = __('Invalid file type', 'quality-cost-calculator');
        }
        
        return $validation;
    }
    
    private function generate_export($data, $format, $type) {
        switch ($format) {
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT);
                
            case 'csv':
                return $this->convert_to_csv($data);
                
            case 'xml':
                return $this->convert_to_xml($data);
                
            default:
                throw new Exception('Unsupported export format: ' . $format);
        }
    }
    
    private function convert_to_csv($data) {
        $output = fopen('php://temp', 'r+');
        
        if (is_array($data) && !empty($data)) {
            $first_row = reset($data);
            if (is_array($first_row)) {
                fputcsv($output, array_keys($first_row));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            } else {
                fputcsv($output, array('Key', 'Value'));
                foreach ($data as $key => $value) {
                    fputcsv($output, array($key, $value));
                }
            }
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    private function convert_to_xml($data) {
        $xml = new SimpleXMLElement('<qcc_export/>');
        $this->array_to_xml($data, $xml);
        return $xml->asXML();
    }
    
    private function array_to_xml($data, $xml) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild(is_numeric($key) ? 'item' : $key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml->addChild(is_numeric($key) ? 'item' : $key, htmlspecialchars($value));
            }
        }
    }
    
    private function generate_export_filename($format, $type) {
        $timestamp = date('Y-m-d_H-i-s');
        return "qcc_export_{$type}_{$timestamp}.{$format}";
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
    
    private function handle_ajax_error($action, $exception) {
        $error_message = $exception->getMessage();
        
        // Log the error
        $this->log_ajax_activity($action, 'error', array(
            'message' => $error_message,
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ));
        
        wp_send_json_error(array(
            'message' => sprintf(__('Error in %s: %s', 'quality-cost-calculator'), $action, $error_message),
            'code' => 'ajax_error'
        ), 500);
    }
    
    private function log_ajax_activity($action, $status, $data = array()) {
        if (!$this->settings->get('general.enable_activity_logging', false)) {
            return;
        }
        
        $activity_log = get_option('qcc_ajax_activity_log', array());
        
        $activity_log[] = array(
            'timestamp' => current_time('mysql'),
            'action' => $action,
            'status' => $status,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data
        );
        
        // Keep only last 1000 entries
        if (count($activity_log) > 1000) {
            $activity_log = array_slice($activity_log, -1000);
        }
        
        update_option('qcc_ajax_activity_log', $activity_log);
    }
    
    private function get_template_type($template_name) {
        if (strpos($template_name, 'form') !== false) {
            return 'form';
        } elseif (strpos($template_name, 'result') !== false) {
            return 'result';
        } elseif (strpos($template_name, 'admin') !== false) {
            return 'admin';
        } else {
            return 'general';
        }
    }
    
    // Health check methods
    private function check_php_version() {
        $required_version = '7.4';
        $current_version = PHP_VERSION;
        
        return array(
            'status' => version_compare($current_version, $required_version, '>=') ? 'pass' : 'fail',
            'message' => "PHP {$current_version} (required: {$required_version}+)",
            'current' => $current_version,
            'required' => $required_version
        );
    }
    
    private function check_wp_version() {
        $required_version = '5.0';
        $current_version = get_bloginfo('version');
        
        return array(
            'status' => version_compare($current_version, $required_version, '>=') ? 'pass' : 'fail',
            'message' => "WordPress {$current_version} (required: {$required_version}+)",
            'current' => $current_version,
            'required' => $required_version
        );
    }
    
    private function check_memory_usage() {
        $memory_usage = memory_get_usage(true);
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $usage_percentage = ($memory_usage / $memory_limit) * 100;
        
        $status = 'pass';
        if ($usage_percentage > 90) {
            $status = 'fail';
        } elseif ($usage_percentage > 75) {
            $status = 'warning';
        }
        
        return array(
            'status' => $status,
            'message' => sprintf('Memory usage: %s of %s (%.1f%%)', 
                size_format($memory_usage), 
                size_format($memory_limit), 
                $usage_percentage
            ),
            'usage' => $memory_usage,
            'limit' => $memory_limit,
            'percentage' => $usage_percentage
        );
    }
    
    private function check_database_connection() {
        global $wpdb;
        
        $test_query = $wpdb->get_var('SELECT 1');
        
        return array(
            'status' => $test_query === '1' ? 'pass' : 'fail',
            'message' => $test_query === '1' ? 'Database connection OK' : 'Database connection failed',
            'version' => $wpdb->get_var('SELECT VERSION()')
        );
    }
    
    private function check_file_permissions() {
        $plugin_dir = plugin_dir_path(__FILE__);
        $upload_dir = wp_upload_dir();
        
        $checks = array(
            'plugin_readable' => is_readable($plugin_dir),
            'uploads_writable' => is_writable($upload_dir['basedir'])
        );
        
        $all_good = array_reduce($checks, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        return array(
            'status' => $all_good ? 'pass' : 'fail',
            'message' => $all_good ? 'File permissions OK' : 'Some file permission issues detected',
            'details' => $checks
        );
    }
    
    private function check_plugin_files() {
        $required_files = array(
            'class-qcc-calculator.php',
            'class-qcc-validator.php',
            'class-qcc-shortcode.php'
        );
        
        $missing_files = array();
        $plugin_dir = plugin_dir_path(__FILE__);
        
        foreach ($required_files as $file) {
            if (!file_exists($plugin_dir . $file)) {
                $missing_files[] = $file;
            }
        }
        
        return array(
            'status' => empty($missing_files) ? 'pass' : 'fail',
            'message' => empty($missing_files) ? 'All plugin files present' : 'Missing plugin files detected',
            'missing_files' => $missing_files
        );
    }
    
    // Statistics methods
    private function get_calculation_stats($timeframe) {
        $activity_log = get_option('qcc_ajax_activity_log', array());
        $cutoff_time = $this->get_cutoff_time($timeframe);
        
        $calculations = array_filter($activity_log, function($entry) use ($cutoff_time) {
            return $entry['action'] === 'calculate' && 
                   strtotime($entry['timestamp']) >= $cutoff_time;
        });
        
        return array(
            'total_calculations' => count($calculations),
            'successful_calculations' => count(array_filter($calculations, function($entry) {
                return $entry['status'] === 'success';
            })),
            'failed_calculations' => count(array_filter($calculations, function($entry) {
                return $entry['status'] === 'error';
            }))
        );
    }
    
    private function get_performance_stats($timeframe) {
        return array(
            'average_response_time' => 0, // Would need to implement response time tracking
            'cache_hit_rate' => 0, // Would need to implement cache statistics
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    private function get_usage_stats($timeframe) {
        $activity_log = get_option('qcc_ajax_activity_log', array());
        $cutoff_time = $this->get_cutoff_time($timeframe);
        
        $recent_activity = array_filter($activity_log, function($entry) use ($cutoff_time) {
            return strtotime($entry['timestamp']) >= $cutoff_time;
        });
        
        $unique_users = array_unique(array_column($recent_activity, 'user_id'));
        $unique_ips = array_unique(array_column($recent_activity, 'ip_address'));
        
        return array(
            'total_requests' => count($recent_activity),
            'unique_users' => count($unique_users),
            'unique_visitors' => count($unique_ips),
            'most_used_actions' => $this->get_action_frequency($recent_activity)
        );
    }
    
    private function get_cutoff_time($timeframe) {
        switch ($timeframe) {
            case '1h':
                return strtotime('-1 hour');
            case '24h':
                return strtotime('-24 hours');
            case '7d':
                return strtotime('-7 days');
            case '30d':
                return strtotime('-30 days');
            default:
                return strtotime('-24 hours');
        }
    }
    
    private function get_action_frequency($activity) {
        $actions = array_column($activity, 'action');
        return array_count_values($actions);
    }
}
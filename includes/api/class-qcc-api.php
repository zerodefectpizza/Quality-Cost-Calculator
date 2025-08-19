<?php
/**
 * QCC API Main Class
 * 
 * Central API management for the Quality Cost Calculator plugin.
 * Coordinates REST API and AJAX endpoints with unified error handling.
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
 * QCC API Main Class
 * 
 * Manages REST API and AJAX endpoints with unified authentication,
 * validation, and error handling.
 */
class QCC_API {
    
    /**
     * REST Controller instance
     * 
     * @var QCC_REST_Controller
     */
    private $rest_controller;
    
    /**
     * AJAX Handler instance
     * 
     * @var QCC_AJAX_Handler
     */
    private $ajax_handler;
    
    /**
     * Settings service
     * 
     * @var QCC_Settings_Service
     */
    private $settings;
    
    /**
     * API version
     * 
     * @var string
     */
    private $api_version = 'v1';
    
    /**
     * API namespace
     * 
     * @var string
     */
    private $api_namespace = 'qcc';
    
    /**
     * Rate limiting data
     * 
     * @var array
     */
    private $rate_limits = array();
    
    /**
     * API endpoints registry
     * 
     * @var array
     */
    private $registered_endpoints = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        try {
            $container = QCC_Service_Container::get_instance();
            $this->settings = $container->get('settings');
        } catch (Exception $e) {
            // Fallback if container not available
            $this->settings = new QCC_Settings_Service();
        }
    }
    
    /**
     * Initialize API components
     */
    private function init_components() {
        $this->rest_controller = new QCC_REST_Controller($this);
        $this->ajax_handler = new QCC_AJAX_Handler($this);
        
        // Initialize rate limiting
        $this->init_rate_limiting();
    }
    
    /**
     * Initialize API system
     */
    public function init() {
        // Check if API is enabled
        if (!$this->is_api_enabled()) {
            return;
        }
        
        // Initialize components
        $this->rest_controller->init();
        $this->ajax_handler->init();
        
        // Register hooks
        $this->register_hooks();
        
        // Log API initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('QCC API: System initialized');
        }
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // REST API hooks
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_filter('rest_pre_dispatch', array($this, 'check_rate_limits'), 10, 3);
        
        // AJAX hooks are handled by AJAX_Handler
        
        // Authentication hooks
        add_filter('rest_authentication_errors', array($this, 'authenticate_request'));
        
        // CORS headers
        add_action('rest_api_init', array($this, 'add_cors_headers'));
        
        // Error logging
        add_action('qcc_api_error', array($this, 'log_api_error'), 10, 3);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $namespace = $this->get_api_namespace();
        
        // Main calculation endpoint
        register_rest_route($namespace, '/calculate', array(
            'methods' => 'POST',
            'callback' => array($this->rest_controller, 'calculate'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_calculate_args_schema()
        ));
        
        // Settings endpoints
        register_rest_route($namespace, '/settings', array(
            'methods' => 'GET',
            'callback' => array($this->rest_controller, 'get_settings'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        register_rest_route($namespace, '/settings', array(
            'methods' => 'POST',
            'callback' => array($this->rest_controller, 'update_settings'),
            'permission_callback' => array($this, 'check_admin_permissions'),
            'args' => $this->get_settings_args_schema()
        ));
        
        // Export endpoints
        register_rest_route($namespace, '/export/(?P<format>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array($this->rest_controller, 'export_data'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'format' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('json', 'csv', 'xml', 'pdf')
                )
            )
        ));
        
        // Import endpoints
        register_rest_route($namespace, '/import', array(
            'methods' => 'POST',
            'callback' => array($this->rest_controller, 'import_data'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
        
        // Health check endpoint
        register_rest_route($namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this->rest_controller, 'health_check'),
            'permission_callback' => '__return_true'
        ));
        
        // Templates endpoint
        register_rest_route($namespace, '/templates', array(
            'methods' => 'GET',
            'callback' => array($this->rest_controller, 'get_templates'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        // Validation endpoint
        register_rest_route($namespace, '/validate', array(
            'methods' => 'POST',
            'callback' => array($this->rest_controller, 'validate_input'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => $this->get_validate_args_schema()
        ));
        
        // Register custom endpoints from settings
        $this->register_custom_endpoints();
        
        // Store registered endpoints for documentation
        $this->registered_endpoints = array(
            'calculate', 'settings', 'export', 'import', 'health', 'templates', 'validate'
        );
    }
    
    /**
     * Get API namespace
     * 
     * @return string API namespace with version
     */
    public function get_api_namespace() {
        return $this->api_namespace . '/' . $this->api_version;
    }
    
    /**
     * Check API permissions
     * 
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error Permission check result
     */
    public function check_permissions($request) {
        // Check if API is enabled
        if (!$this->is_api_enabled()) {
            return new WP_Error(
                'api_disabled',
                __('API access is disabled', 'quality-cost-calculator'),
                array('status' => 503)
            );
        }
        
        // Check rate limits
        if (!$this->check_user_rate_limit($request)) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Rate limit exceeded', 'quality-cost-calculator'),
                array('status' => 429)
            );
        }
        
        // Check capability requirement
        $required_capability = $this->settings->get('security.capability_required', 'read');
        
        if (!current_user_can($required_capability)) {
            return new WP_Error(
                'insufficient_permissions',
                __('Insufficient permissions', 'quality-cost-calculator'),
                array('status' => 403)
            );
        }
        
        return true;
    }
    
    /**
     * Check admin permissions
     * 
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error Permission check result
     */
    public function check_admin_permissions($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'admin_required',
                __('Administrator privileges required', 'quality-cost-calculator'),
                array('status' => 403)
            );
        }
        
        return $this->check_permissions($request);
    }
    
    /**
     * Authenticate API request
     * 
     * @param mixed $result Previous authentication result
     * @return mixed Authentication result
     */
    public function authenticate_request($result) {
        // Skip if already authenticated
        if (!empty($result)) {
            return $result;
        }
        
        // Check for API key authentication
        $api_key = $this->get_api_key_from_request();
        if ($api_key && $this->validate_api_key($api_key)) {
            return true;
        }
        
        // Check for JWT authentication if enabled
        if ($this->settings->get('security.enable_jwt_auth', false)) {
            return $this->authenticate_jwt();
        }
        
        return $result;
    }
    
    /**
     * Add CORS headers
     */
    public function add_cors_headers() {
        if ($this->settings->get('integration.enable_cors', false)) {
            $allowed_origins = $this->settings->get('integration.cors_origins', array());
            
            if (!empty($allowed_origins)) {
                $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
                if (in_array($origin, $allowed_origins) || in_array('*', $allowed_origins)) {
                    header('Access-Control-Allow-Origin: ' . $origin);
                    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                    header('Access-Control-Allow-Headers: Content-Type, Authorization');
                    header('Access-Control-Allow-Credentials: true');
                }
            }
        }
    }
    
    /**
     * Check rate limits
     * 
     * @param mixed $result Previous result
     * @param WP_REST_Server $server REST server instance
     * @param WP_REST_Request $request Request object
     * @return mixed Result
     */
    public function check_rate_limits($result, $server, $request) {
        // Skip rate limiting for admin users
        if (current_user_can('manage_options')) {
            return $result;
        }
        
        // Check if rate limiting is enabled
        if (!$this->settings->get('security.enable_rate_limiting', false)) {
            return $result;
        }
        
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $key = $user_id ? "user_{$user_id}" : "ip_{$ip_address}";
        
        if (!$this->check_user_rate_limit($request, $key)) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Too many requests. Please try again later.', 'quality-cost-calculator'),
                array('status' => 429)
            );
        }
        
        return $result;
    }
    
    /**
     * Log API errors
     * 
     * @param string $endpoint Endpoint name
     * @param string $error Error message
     * @param array $context Error context
     */
    public function log_api_error($endpoint, $error, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = "QCC API Error [{$endpoint}]: {$error}";
            if (!empty($context)) {
                $log_message .= ' | Context: ' . json_encode($context);
            }
            error_log($log_message);
        }
        
        // Store in database for admin review
        $this->store_error_log($endpoint, $error, $context);
    }
    
    /**
     * Get REST controller
     * 
     * @return QCC_REST_Controller
     */
    public function get_rest_controller() {
        return $this->rest_controller;
    }
    
    /**
     * Get AJAX handler
     * 
     * @return QCC_AJAX_Handler
     */
    public function get_ajax_handler() {
        return $this->ajax_handler;
    }
    
    /**
     * Get API documentation
     * 
     * @return array API documentation
     */
    public function get_api_documentation() {
        return array(
            'version' => $this->api_version,
            'namespace' => $this->get_api_namespace(),
            'base_url' => rest_url($this->get_api_namespace()),
            'endpoints' => $this->get_endpoint_documentation(),
            'authentication' => $this->get_authentication_documentation(),
            'rate_limits' => $this->get_rate_limit_documentation(),
            'error_codes' => $this->get_error_codes_documentation()
        );
    }
    
    /**
     * Private helper methods
     */
    
    private function is_api_enabled() {
        return $this->settings->get('integration.enable_rest_api', true);
    }
    
    private function init_rate_limiting() {
        $this->rate_limits = array(
            'requests_per_minute' => $this->settings->get('security.max_requests_per_minute', 60),
            'requests_per_hour' => $this->settings->get('security.max_requests_per_hour', 1000),
            'requests_per_day' => $this->settings->get('security.max_requests_per_day', 10000)
        );
    }
    
    private function check_user_rate_limit($request, $key = null) {
        if (!$key) {
            $user_id = get_current_user_id();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $key = $user_id ? "user_{$user_id}" : "ip_{$ip_address}";
        }
        
        $transient_key = "qcc_rate_limit_{$key}";
        $current_count = get_transient($transient_key);
        
        if ($current_count === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return true;
        }
        
        if ($current_count >= $this->rate_limits['requests_per_minute']) {
            return false;
        }
        
        set_transient($transient_key, $current_count + 1, MINUTE_IN_SECONDS);
        return true;
    }
    
    private function get_api_key_from_request() {
        // Check header
        $headers = getallheaders();
        if (isset($headers['X-API-Key'])) {
            return $headers['X-API-Key'];
        }
        
        // Check query parameter
        return $_GET['api_key'] ?? null;
    }
    
    private function validate_api_key($api_key) {
        $valid_keys = $this->settings->get('security.api_keys', array());
        return in_array($api_key, $valid_keys);
    }
    
    private function authenticate_jwt() {
        // JWT authentication implementation
        // This would require a JWT library
        return false;
    }
    
    private function register_custom_endpoints() {
        $custom_endpoints = $this->settings->get('integration.custom_endpoints', array());
        
        foreach ($custom_endpoints as $endpoint) {
            if (isset($endpoint['route']) && isset($endpoint['callback'])) {
                register_rest_route(
                    $this->get_api_namespace(),
                    $endpoint['route'],
                    array(
                        'methods' => $endpoint['methods'] ?? 'GET',
                        'callback' => $endpoint['callback'],
                        'permission_callback' => array($this, 'check_permissions')
                    )
                );
            }
        }
    }
    
    private function store_error_log($endpoint, $error, $context) {
        $error_logs = get_option('qcc_api_error_logs', array());
        
        $error_logs[] = array(
            'timestamp' => current_time('mysql'),
            'endpoint' => $endpoint,
            'error' => $error,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        );
        
        // Keep only last 100 errors
        if (count($error_logs) > 100) {
            $error_logs = array_slice($error_logs, -100);
        }
        
        update_option('qcc_api_error_logs', $error_logs);
    }
    
    private function get_calculate_args_schema() {
        return array(
            'prevention_costs' => array(
                'required' => true,
                'type' => 'number',
                'minimum' => 0,
                'description' => 'Prevention costs amount'
            ),
            'appraisal_costs' => array(
                'required' => true,
                'type' => 'number',
                'minimum' => 0,
                'description' => 'Appraisal costs amount'
            ),
            'internal_defect_costs' => array(
                'required' => true,
                'type' => 'number',
                'minimum' => 0,
                'description' => 'Internal defect costs amount'
            ),
            'external_defect_costs' => array(
                'required' => true,
                'type' => 'number',
                'minimum' => 0,
                'description' => 'External defect costs amount'
            ),
            'currency' => array(
                'type' => 'string',
                'default' => 'USD',
                'enum' => array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD')
            ),
            'unit' => array(
                'type' => 'string',
                'default' => '1',
                'enum' => array('1', '1000', '1000000')
            )
        );
    }
    
    private function get_settings_args_schema() {
        return array(
            'settings' => array(
                'required' => true,
                'type' => 'object',
                'description' => 'Settings object to update'
            )
        );
    }
    
    private function get_validate_args_schema() {
        return array(
            'data' => array(
                'required' => true,
                'type' => 'object',
                'description' => 'Data to validate'
            ),
            'validation_rules' => array(
                'type' => 'array',
                'description' => 'Custom validation rules'
            )
        );
    }
    
    private function get_endpoint_documentation() {
        return array(
            'calculate' => array(
                'path' => '/calculate',
                'method' => 'POST',
                'description' => 'Calculate quality costs',
                'parameters' => $this->get_calculate_args_schema()
            ),
            'settings' => array(
                'path' => '/settings',
                'methods' => array('GET', 'POST'),
                'description' => 'Manage plugin settings'
            ),
            'export' => array(
                'path' => '/export/{format}',
                'method' => 'POST',
                'description' => 'Export calculation data'
            ),
            'health' => array(
                'path' => '/health',
                'method' => 'GET',
                'description' => 'API health check'
            )
        );
    }
    
    private function get_authentication_documentation() {
        return array(
            'methods' => array('API Key', 'WordPress Cookie', 'JWT (if enabled)'),
            'api_key' => array(
                'header' => 'X-API-Key: your-api-key',
                'parameter' => '?api_key=your-api-key'
            )
        );
    }
    
    private function get_rate_limit_documentation() {
        return array(
            'requests_per_minute' => $this->rate_limits['requests_per_minute'],
            'requests_per_hour' => $this->rate_limits['requests_per_hour'],
            'requests_per_day' => $this->rate_limits['requests_per_day'],
            'headers' => array(
                'X-RateLimit-Limit',
                'X-RateLimit-Remaining',
                'X-RateLimit-Reset'
            )
        );
    }
    
    private function get_error_codes_documentation() {
        return array(
            '400' => 'Bad Request - Invalid input parameters',
            '401' => 'Unauthorized - Authentication required',
            '403' => 'Forbidden - Insufficient permissions',
            '404' => 'Not Found - Endpoint not found',
            '429' => 'Too Many Requests - Rate limit exceeded',
            '500' => 'Internal Server Error - Server error occurred',
            '503' => 'Service Unavailable - API disabled'
        );
    }
}
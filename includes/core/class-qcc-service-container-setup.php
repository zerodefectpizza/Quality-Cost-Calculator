<?php
/**
 * QCC Service Container Setup - Syntax-korrigierte Version
 * 
 * Initializes and configures the service container with all
 * rendering components and shortcode services.
 *
 * @package QualityCostCalculator
 * @subpackage Core
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Service_Container_Setup {
    
    /**
     * @var bool Initialization flag
     */
    private static $initialized = false;
    
    /**
     * @var QCC_Service_Container Container instance
     */
    private static $container;
    
    /**
     * @var array Service registration statistics
     */
    private static $registration_stats = array();
    
    /**
     * @var array Failed service registrations
     */
    private static $failed_registrations = array();
    
    /**
     * Initialize the service container with all components
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        $start_time = microtime(true);
        
        try {
            // Get or create container instance
            self::$container = self::get_or_create_container();
            
            // Register services in order of dependency
            self::register_core_services();           // Foundation services
            self::register_shortcode_services();      // NEW: Shortcode architecture
            self::register_rendering_components();    // Rendering system
            self::register_integration_services();    // Integration layer
            
            // Verify critical dependencies
            self::verify_dependencies();
            
            // Mark as initialized
            self::$initialized = true;
            
            // Record performance stats
            $execution_time = microtime(true) - $start_time;
            self::$registration_stats['total_time'] = $execution_time;
            self::$registration_stats['services_count'] = self::get_service_count();
            
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log(sprintf('QCC: Service Container initialized with %d services (%.3fms)', 
                    self::$registration_stats['services_count'], 
                    $execution_time * 1000
                ));
            }
            
        } catch (Exception $e) {
            error_log('QCC Service Container Setup Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get or create service container instance
     */
    private static function get_or_create_container() {
        if (class_exists('QCC_Service_Container')) {
            // QCC_Service_Container has no get_instance() method - create new instance
            return new QCC_Service_Container();
        }
        
        // Fallback: Create basic container
        return new QCC_Basic_Service_Container();
    }
    
    /**
     * Register core foundation services
     */
    private static function register_core_services() {
        // Translation Service (highest priority)
        self::register_service('translator', function() {
            if (class_exists('QCC_Translator')) {
                return new QCC_Translator();
            }
            return new QCC_Basic_Translator();
        }, 'Core translation service');
        
        // Configuration Service
        self::register_service('configuration', function() {
            if (class_exists('QCC_Configuration')) {
                return new QCC_Configuration();
            }
            return new QCC_Basic_Configuration();
        }, 'Configuration management');
        
        // Feature Flags Service
        self::register_service('feature_flags', function() {
            if (class_exists('QCC_Feature_Flags')) {
                return QCC_Feature_Flags::get_instance();
            }
            return new QCC_Basic_Feature_Flags();
        }, 'Feature flag management');
        
        // Cache Manager (if available)
        self::register_service('cache_manager', function() {
            if (class_exists('QCC_Cache_Manager')) {
                return new QCC_Cache_Manager();
            }
            return new QCC_Basic_Cache_Manager();
        }, 'Caching service');
    }
    
    /**
     * Register new shortcode services architecture
     */
    private static function register_shortcode_services() {
        // Fallback Renderer (no dependencies - most reliable)
        self::register_service('fallback_renderer', function() {
            $translator = self::$container->get('translator');
            return new QCC_Fallback_Renderer($translator);
        }, 'Fallback shortcode renderer');
        
        // Modern Shortcode Renderer (depends on advanced services)
        self::register_service('shortcode_renderer', function() {
            return new QCC_Shortcode_Renderer(self::$container);
        }, 'Modern shortcode renderer');
        
        // Main Shortcode Handler (coordinates other renderers)
        self::register_service('shortcode_handler', function() {
            return new QCC_Shortcode();
        }, 'Main shortcode coordinator');
        
        // Shortcode Integration Bridge
        self::register_service('shortcode_integration', function() {
            if (class_exists('QCC_Shortcode_Integration')) {
                return new QCC_Shortcode_Integration();
            }
            return new QCC_Basic_Shortcode_Integration();
        }, 'Shortcode integration bridge');
    }
    
    /**
     * Register rendering system components
     */
    private static function register_rendering_components() {
        // Template Router
        self::register_service('template_router', function() {
            if (class_exists('QCC_Template_Router')) {
                return new QCC_Template_Router();
            }
            return new QCC_Basic_Template_Router();
        }, 'Template routing system');
        
        // Layout Builder
        self::register_service('layout_builder', function() {
            if (class_exists('QCC_Layout_Builder')) {
                $component_registry = self::$container->get('component_registry');
                $template_router = self::$container->get('template_router');
                return new QCC_Layout_Builder($component_registry, $template_router);
            }
            return new QCC_Basic_Layout_Builder();
        }, 'Dynamic layout construction');
        
        // Component Registry
        self::register_service('component_registry', function() {
            if (class_exists('QCC_Component_Registry')) {
                return QCC_Component_Registry::get_instance();
            }
            return new QCC_Basic_Component_Registry();
        }, 'Component registration and management');
        
        // Asset Manager
        self::register_service('asset_manager', function() {
            if (class_exists('QCC_Asset_Manager')) {
                return new QCC_Asset_Manager();
            }
            return new QCC_Basic_Asset_Manager();
        }, 'Asset management and optimization');
    }
    
    /**
     * Register integration and compatibility services
     */
    private static function register_integration_services() {
        // Main Integration Bridge
        self::register_service('main_integration', function() {
            if (class_exists('QCC_Main_Integration')) {
                return QCC_Main_Integration::get_instance();
            }
            return new QCC_Basic_Integration();
        }, 'Main integration coordinator');
        
        // Backward Compatibility Handler
        self::register_service('backward_compatibility', function() {
            if (class_exists('QCC_Backward_Compatibility')) {
                return new QCC_Backward_Compatibility();
            }
            return new QCC_Basic_Compatibility();
        }, 'Legacy system compatibility');
        
        // Performance Monitor
        self::register_service('performance_monitor', function() {
            if (class_exists('QCC_Performance_Monitor')) {
                return new QCC_Performance_Monitor();
            }
            return new QCC_Basic_Performance_Monitor();
        }, 'Performance monitoring and optimization');
    }
    
    /**
     * Register individual service with error handling
     */
    private static function register_service($name, $factory, $description = '') {
        try {
            self::$container->register($name, $factory);
            
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log("QCC: Registered service '{$name}' - {$description}");
            }
            
        } catch (Exception $e) {
            self::$failed_registrations[] = array(
                'service' => $name,
                'description' => $description,
                'error' => $e->getMessage(),
                'time' => time()
            );
            
            error_log("QCC: Failed to register service '{$name}': " . $e->getMessage());
            
            // Don't throw - continue with other services
        }
    }
    
    /**
     * Verify critical dependencies are satisfied
     */
    private static function verify_dependencies() {
        $critical_services = array(
            'translator' => 'Translation service is required',
            'fallback_renderer' => 'Fallback renderer must be available',
            'shortcode_handler' => 'Main shortcode handler is required'
        );
        
        $missing_services = array();
        
        foreach ($critical_services as $service => $description) {
            try {
                $instance = self::$container->get($service);
                if (!$instance) {
                    $missing_services[] = $service . ': ' . $description;
                }
            } catch (Exception $e) {
                $missing_services[] = $service . ': ' . $e->getMessage();
            }
        }
        
        if (!empty($missing_services)) {
            $error_message = 'Critical service dependencies not satisfied: ' . implode(', ', $missing_services);
            error_log('QCC Service Container: ' . $error_message);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                throw new Exception($error_message);
            }
        }
    }
    
    /**
     * Warmup critical services for performance
     */
    public static function warmup() {
        if (!self::$initialized) {
            self::init();
        }
        
        $warmup_services = array(
            'translator',
            'fallback_renderer',
            'shortcode_handler',
            'configuration'
        );
        
        $warmed_up = 0;
        foreach ($warmup_services as $service) {
            try {
                $instance = self::$container->get($service);
                if ($instance) {
                    $warmed_up++;
                }
            } catch (Exception $e) {
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log("QCC: Failed to warmup service '{$service}': " . $e->getMessage());
                }
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Warmed up {$warmed_up}/" . count($warmup_services) . " critical services");
        }
        
        return $warmed_up;
    }
    
    /**
     * Get service container instance
     */
    public static function get_container() {
        if (!self::$initialized) {
            self::init();
        }
        
        return self::$container;
    }
    
    /**
     * Check if container is initialized
     */
    public static function is_initialized() {
        return self::$initialized;
    }
    
    /**
     * Get shortcode services status
     */
    public static function get_shortcode_services_status() {
        if (!self::$initialized) {
            return array('error' => 'Container not initialized');
        }
        
        $shortcode_services = array(
            'shortcode_handler' => 'Main Shortcode Coordinator',
            'shortcode_renderer' => 'Modern Shortcode Renderer', 
            'fallback_renderer' => 'Fallback Shortcode Renderer',
            'shortcode_integration' => 'Integration Bridge'
        );
        
        $status = array();
        
        foreach ($shortcode_services as $service => $description) {
            try {
                $instance = self::$container->get($service);
                $status[$service] = array(
                    'description' => $description,
                    'available' => $instance !== null,
                    'class' => $instance ? get_class($instance) : null,
                    'status' => 'ok'
                );
            } catch (Exception $e) {
                $status[$service] = array(
                    'description' => $description,
                    'available' => false,
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $status;
    }
    
    /**
     * Get service count from container
     */
    private static function get_service_count() {
        if (!self::$container) {
            return 0;
        }
        
        // Try different methods to get service count
        if (method_exists(self::$container, 'get_service_count')) {
            return self::$container->get_service_count();
        }
        
        if (method_exists(self::$container, 'count')) {
            return self::$container->count();
        }
        
        // Fallback: estimate from registration attempts
        return count(self::$registration_stats) - count(self::$failed_registrations);
    }
    
    /**
     * Get initialization statistics
     */
    public static function get_status() {
        if (!self::$initialized) {
            return array(
                'initialized' => false,
                'message' => 'Service container not initialized'
            );
        }
        
        return array(
            'initialized' => true,
            'total_services' => self::get_service_count(),
            'failed_registrations' => count(self::$failed_registrations),
            'registration_stats' => self::$registration_stats,
            'shortcode_services' => self::get_shortcode_services_status(),
            'container_class' => get_class(self::$container)
        );
    }
    
    /**
     * Get failed registration details
     */
    public static function get_failed_registrations() {
        return self::$failed_registrations;
    }
    
    /**
     * Health check for service container
     */
    public static function health_check() {
        $health = array(
            'status' => 'healthy',
            'issues' => array(),
            'recommendations' => array()
        );
        
        // Check initialization
        if (!self::$initialized) {
            $health['status'] = 'critical';
            $health['issues'][] = 'Service container not initialized';
            return $health;
        }
        
        // Check critical services
        $shortcode_status = self::get_shortcode_services_status();
        foreach ($shortcode_status as $service => $info) {
            if (!$info['available']) {
                $health['status'] = ($health['status'] === 'critical') ? 'critical' : 'warning';
                $health['issues'][] = "Shortcode service '{$service}' not available";
            }
        }
        
        // Check for failed registrations
        if (count(self::$failed_registrations) > 0) {
            $health['status'] = ($health['status'] === 'critical') ? 'critical' : 'warning';
            $health['issues'][] = count(self::$failed_registrations) . ' service registrations failed';
            $health['recommendations'][] = 'Check error logs for service registration failures';
        }
        
        // Performance recommendations
        if (isset(self::$registration_stats['total_time']) && self::$registration_stats['total_time'] > 0.1) {
            $health['recommendations'][] = 'Service container initialization is slow (' . 
                round(self::$registration_stats['total_time'] * 1000, 2) . 'ms)';
        }
        
        return $health;
    }
}

/**
 * Basic Service Container Fallback
 * 
 * Simple fallback implementation when main container is not available
 */
class QCC_Basic_Service_Container {
    
    private $services = array();
    private $instances = array();
    
    public function register($name, $factory) {
        $this->services[$name] = $factory;
    }
    
    public function get($name) {
        if (!isset($this->services[$name])) {
            return null;
        }
        
        if (!isset($this->instances[$name])) {
            $factory = $this->services[$name];
            $this->instances[$name] = is_callable($factory) ? call_user_func($factory) : null;
        }
        
        return $this->instances[$name];
    }
    
    public function has($name) {
        return isset($this->services[$name]);
    }
    
    public function count() {
        return count($this->services);
    }
    
    public function get_service_count() {
        return $this->count();
    }
}

/**
 * Basic Translator Fallback
 */
class QCC_Basic_Translator {
    
    public function get($key, $fallback = null) {
        return $fallback ?: $key;
    }
    
    public function get_supported_languages() {
        return array('en', 'de');
    }
}

/**
 * Basic Configuration Fallback
 */
class QCC_Basic_Configuration {
    
    public function get($key, $default = null) {
        return get_option('qcc_' . $key, $default);
    }
    
    public function set($key, $value) {
        return update_option('qcc_' . $key, $value);
    }
}

/**
 * Basic Feature Flags Fallback
 */
class QCC_Basic_Feature_Flags {
    
    public function is_enabled($flag) {
        return get_option('qcc_feature_' . $flag, false);
    }
    
    public function enable($flag) {
        return update_option('qcc_feature_' . $flag, true);
    }
    
    public function disable($flag) {
        return update_option('qcc_feature_' . $flag, false);
    }
}

/**
 * Basic Cache Manager Fallback
 */
class QCC_Basic_Cache_Manager {
    
    public function get($key) {
        return wp_cache_get($key, 'qcc');
    }
    
    public function set($key, $value, $expiration = 3600) {
        return wp_cache_set($key, $value, 'qcc', $expiration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, 'qcc');
    }
}

/**
 * Basic Asset Manager Fallback
 */
class QCC_Basic_Asset_Manager {
    
    public function enqueue_calculator_assets($config) {
        // Basic asset enqueueing
        wp_enqueue_script('jquery');
    }
    
    public function get_calculator_css($config) {
        return '/* Basic CSS fallback */';
    }
    
    public function get_calculator_js($config) {
        return '/* Basic JS fallback */';
    }
}

/**
 * Basic Template Router Fallback
 */
class QCC_Basic_Template_Router {
    
    public function route_calculator_template($config, $content) {
        return '<div class="qcc-basic-template">Calculator content would go here</div>';
    }
}

/**
 * Basic Layout Builder Fallback
 */
class QCC_Basic_Layout_Builder {
    
    public function build_complete_layout($config, $content) {
        return '<div class="qcc-basic-layout">Basic layout fallback</div>';
    }
}

/**
 * Basic Component Registry Fallback
 */
class QCC_Basic_Component_Registry {
    
    public function get($name) {
        return null;
    }
    
    public function has($name) {
        return false;
    }
    
    public function register($name, $component) {
        // Basic registration
    }
}

/**
 * Basic Shortcode Integration Fallback
 */
class QCC_Basic_Shortcode_Integration {
    
    public function render($config) {
        return '<div class="qcc-basic-shortcode">Basic shortcode fallback</div>';
    }
}

/**
 * Basic Integration Fallback
 */
class QCC_Basic_Integration {
    
    public function init() {
        // Basic initialization
    }
}

/**
 * Basic Compatibility Fallback
 */
class QCC_Basic_Compatibility {
    
    public function ensure_compatibility() {
        // Basic compatibility
    }
}

/**
 * Basic Performance Monitor Fallback
 */
class QCC_Basic_Performance_Monitor {
    
    public function track($metric, $value) {
        // Basic tracking
    }
    
    public function get_stats() {
        return array();
    }
}
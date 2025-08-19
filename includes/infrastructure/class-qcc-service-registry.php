<?php
/**
 * QCC Service Registry
 * 
 * Central registry for managing services and their dependencies.
 * Provides service discovery, registration, and lifecycle management.
 * 
 * @package QualityCostCalculator
 * @subpackage Infrastructure
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Service Registry Class
 * 
 * Manages service registration, discovery, and dependency resolution
 * for the entire QCC plugin ecosystem.
 */
class QCC_Service_Registry {
    
    /**
     * Registry instance
     * 
     * @var QCC_Service_Registry
     */
    private static $instance = null;
    
    /**
     * Registered services
     * 
     * @var array
     */
    private $services = array();
    
    /**
     * Service instances
     * 
     * @var array
     */
    private $instances = array();
    
    /**
     * Service dependencies
     * 
     * @var array
     */
    private $dependencies = array();
    
    /**
     * Service metadata
     * 
     * @var array
     */
    private $metadata = array();
    
    /**
     * Service aliases
     * 
     * @var array
     */
    private $aliases = array();
    
    /**
     * Service status tracking
     * 
     * @var array
     */
    private $service_status = array();
    
    /**
     * Loading statistics
     * 
     * @var array
     */
    private $load_stats = array();
    
    /**
     * Debug mode flag
     * 
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Initialization state
     * 
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->init_load_stats();
        $this->register_core_services();
    }
    
    /**
     * Get registry instance
     * 
     * @return QCC_Service_Registry
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize the registry
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        // Register WordPress hooks
        add_action('qcc_services_init', array($this, 'initialize_services'));
        add_action('qcc_services_shutdown', array($this, 'shutdown_services'));
        
        // Register built-in services
        $this->register_builtin_services();
        
        // Allow plugins to register services
        do_action('qcc_register_services', $this);
        
        $this->initialized = true;
        
        if ($this->debug_mode) {
            error_log('QCC Service Registry: Initialized with ' . count($this->services) . ' services');
        }
    }
    
    /**
     * Register a service
     * 
     * @param string $name Service name
     * @param string|callable $class_or_factory Class name or factory function
     * @param array $options Service options
     * @return bool Success status
     */
    public function register($name, $class_or_factory, $options = array()) {
        $options = array_merge(array(
            'singleton' => true,
            'dependencies' => array(),
            'priority' => 10,
            'description' => '',
            'version' => '1.0.0',
            'author' => 'QCC Core',
            'lazy' => true,
            'interfaces' => array(),
            'tags' => array()
        ), $options);
        
        // Validate service name
        if (!$this->is_valid_service_name($name)) {
            if ($this->debug_mode) {
                error_log("QCC Service Registry: Invalid service name '{$name}'");
            }
            return false;
        }
        
        // Check if service already exists
        if (isset($this->services[$name])) {
            if ($this->debug_mode) {
                error_log("QCC Service Registry: Service '{$name}' already registered");
            }
            return false;
        }
        
        // Register the service
        $this->services[$name] = array(
            'class_or_factory' => $class_or_factory,
            'options' => $options,
            'registered_at' => microtime(true)
        );
        
        // Store dependencies
        if (!empty($options['dependencies'])) {
            $this->dependencies[$name] = $options['dependencies'];
        }
        
        // Store metadata
        $this->metadata[$name] = array(
            'description' => $options['description'],
            'version' => $options['version'],
            'author' => $options['author'],
            'interfaces' => $options['interfaces'],
            'tags' => $options['tags'],
            'priority' => $options['priority'],
            'singleton' => $options['singleton'],
            'lazy' => $options['lazy']
        );
        
        // Initialize status
        $this->service_status[$name] = 'registered';
        
        if ($this->debug_mode) {
            error_log("QCC Service Registry: Registered service '{$name}'");
        }
        
        return true;
    }
    
    /**
     * Get a service instance
     * 
     * @param string $name Service name
     * @return mixed Service instance or null
     */
    public function get($name) {
        $start_time = microtime(true);
        
        try {
            // Resolve alias if needed
            $name = $this->resolve_alias($name);
            
            // Check if service is registered
            if (!isset($this->services[$name])) {
                if ($this->debug_mode) {
                    error_log("QCC Service Registry: Service '{$name}' not found");
                }
                return null;
            }
            
            // Return existing instance for singletons
            if ($this->is_singleton($name) && isset($this->instances[$name])) {
                $this->update_load_stats($name, $start_time, true);
                return $this->instances[$name];
            }
            
            // Create new instance
            $instance = $this->create_instance($name);
            
            // Store instance if singleton
            if ($this->is_singleton($name) && $instance !== null) {
                $this->instances[$name] = $instance;
                $this->service_status[$name] = 'loaded';
            }
            
            $this->update_load_stats($name, $start_time, false);
            
            return $instance;
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Service Registry: Error loading service '{$name}' - " . $e->getMessage());
            }
            
            $this->service_status[$name] = 'error';
            $this->update_load_stats($name, $start_time, false, $e->getMessage());
            
            return null;
        }
    }
    
    /**
     * Check if service exists
     * 
     * @param string $name Service name
     * @return bool Service exists
     */
    public function has($name) {
        $name = $this->resolve_alias($name);
        return isset($this->services[$name]);
    }
    
    /**
     * Remove a service
     * 
     * @param string $name Service name
     * @return bool Success status
     */
    public function remove($name) {
        $name = $this->resolve_alias($name);
        
        if (!isset($this->services[$name])) {
            return false;
        }
        
        // Remove from all registries
        unset($this->services[$name]);
        unset($this->instances[$name]);
        unset($this->dependencies[$name]);
        unset($this->metadata[$name]);
        unset($this->service_status[$name]);
        
        // Remove aliases pointing to this service
        foreach ($this->aliases as $alias => $target) {
            if ($target === $name) {
                unset($this->aliases[$alias]);
            }
        }
        
        if ($this->debug_mode) {
            error_log("QCC Service Registry: Removed service '{$name}'");
        }
        
        return true;
    }
    
    /**
     * Register service alias
     * 
     * @param string $alias Alias name
     * @param string $target Target service name
     * @return bool Success status
     */
    public function alias($alias, $target) {
        if (!$this->is_valid_service_name($alias)) {
            return false;
        }
        
        $this->aliases[$alias] = $target;
        
        if ($this->debug_mode) {
            error_log("QCC Service Registry: Registered alias '{$alias}' -> '{$target}'");
        }
        
        return true;
    }
    
    /**
     * Get all registered services
     * 
     * @return array Service list
     */
    public function get_services() {
        return array_keys($this->services);
    }
    
    /**
     * Get service metadata
     * 
     * @param string $name Service name
     * @return array|null Service metadata
     */
    public function get_metadata($name) {
        $name = $this->resolve_alias($name);
        return $this->metadata[$name] ?? null;
    }
    
    /**
     * Get services by tag
     * 
     * @param string $tag Tag name
     * @return array Services with the tag
     */
    public function get_services_by_tag($tag) {
        $services = array();
        
        foreach ($this->metadata as $name => $meta) {
            if (in_array($tag, $meta['tags'])) {
                $services[] = $name;
            }
        }
        
        return $services;
    }
    
    /**
     * Get services implementing interface
     * 
     * @param string $interface Interface name
     * @return array Services implementing the interface
     */
    public function get_services_by_interface($interface) {
        $services = array();
        
        foreach ($this->metadata as $name => $meta) {
            if (in_array($interface, $meta['interfaces'])) {
                $services[] = $name;
            }
        }
        
        return $services;
    }
    
    /**
     * Initialize all registered services
     */
    public function initialize_services() {
        $services_by_priority = $this->get_services_by_priority();
        
        foreach ($services_by_priority as $name) {
            if ($this->metadata[$name]['lazy'] === false) {
                $this->get($name);
            }
        }
        
        if ($this->debug_mode) {
            error_log('QCC Service Registry: Initialized ' . count($services_by_priority) . ' services');
        }
    }
    
    /**
     * Shutdown all services
     */
    public function shutdown_services() {
        foreach ($this->instances as $name => $instance) {
            if (method_exists($instance, 'shutdown')) {
                try {
                    $instance->shutdown();
                } catch (Exception $e) {
                    if ($this->debug_mode) {
                        error_log("QCC Service Registry: Error shutting down service '{$name}' - " . $e->getMessage());
                    }
                }
            }
        }
        
        // Clear instances
        $this->instances = array();
        
        if ($this->debug_mode) {
            error_log('QCC Service Registry: All services shut down');
        }
    }
    
    /**
     * Get registry statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        return array(
            'total_services' => count($this->services),
            'loaded_services' => count($this->instances),
            'aliases' => count($this->aliases),
            'dependencies' => count($this->dependencies),
            'memory_usage' => memory_get_usage(true),
            'load_stats' => $this->load_stats,
            'service_status' => $this->service_status
        );
    }
    
    /**
     * Get detailed service information
     * 
     * @return array Detailed service info
     */
    public function get_service_info() {
        $info = array();
        
        foreach ($this->services as $name => $service) {
            $info[$name] = array(
                'metadata' => $this->metadata[$name],
                'status' => $this->service_status[$name],
                'is_loaded' => isset($this->instances[$name]),
                'dependencies' => $this->dependencies[$name] ?? array(),
                'dependents' => $this->get_dependents($name),
                'aliases' => $this->get_aliases_for_service($name)
            );
        }
        
        return $info;
    }
    
    /**
     * Validate service dependencies
     * 
     * @return array Validation results
     */
    public function validate_dependencies() {
        $issues = array();
        
        foreach ($this->dependencies as $service => $deps) {
            foreach ($deps as $dependency) {
                if (!$this->has($dependency)) {
                    $issues[] = "Service '{$service}' depends on missing service '{$dependency}'";
                }
            }
            
            // Check for circular dependencies
            if ($this->has_circular_dependency($service)) {
                $issues[] = "Service '{$service}' has circular dependencies";
            }
        }
        
        return $issues;
    }
    
    /**
     * Clear all service instances (for testing)
     */
    public function clear_instances() {
        $this->instances = array();
        $this->load_stats = array();
        
        foreach ($this->service_status as $name => $status) {
            if ($status === 'loaded') {
                $this->service_status[$name] = 'registered';
            }
        }
    }
    
    /**
     * Private helper methods
     */
    
    private function register_core_services() {
        // Core services that are always available
        $core_services = array(
            'settings' => array(
                'class' => 'QCC_Settings_Service',
                'description' => 'Settings management service',
                'priority' => 5,
                'lazy' => false
            ),
            'cache' => array(
                'class' => 'QCC_Cache_Service',
                'description' => 'Caching service',
                'priority' => 5,
                'lazy' => false
            ),
            'translator' => array(
                'class' => 'QCC_Translator',
                'description' => 'Translation service',
                'priority' => 5,
                'lazy' => false
            )
        );
        
        foreach ($core_services as $name => $config) {
            if (class_exists($config['class'])) {
                $this->register($name, $config['class'], $config);
            }
        }
    }
    
    private function register_builtin_services() {
        // Additional built-in services
        $builtin_services = array(
            'template_service' => 'QCC_Template_Service',
            'import_service' => 'QCC_Import_Service',
            'calculation_engine' => 'QCC_Calculation_Engine',
            'validator' => 'QCC_Validator',
            'asset_manager' => 'QCC_Asset_Manager'
        );
        
        foreach ($builtin_services as $name => $class) {
            if (class_exists($class)) {
                $this->register($name, $class, array(
                    'description' => "Built-in {$name} service",
                    'priority' => 10,
                    'lazy' => true
                ));
            }
        }
    }
    
    private function create_instance($name) {
        $service = $this->services[$name];
        $class_or_factory = $service['class_or_factory'];
        
        // Resolve dependencies first
        $dependencies = $this->resolve_dependencies($name);
        
        // Create instance
        if (is_callable($class_or_factory)) {
            // Factory function
            return call_user_func($class_or_factory, $dependencies, $this);
        } elseif (is_string($class_or_factory) && class_exists($class_or_factory)) {
            // Class instantiation
            if (empty($dependencies)) {
                return new $class_or_factory();
            } else {
                // Use reflection for dependency injection
                $reflection = new ReflectionClass($class_or_factory);
                return $reflection->newInstanceArgs($dependencies);
            }
        }
        
        throw new Exception("Cannot create instance for service '{$name}'");
    }
    
    private function resolve_dependencies($name) {
        $dependencies = array();
        
        if (isset($this->dependencies[$name])) {
            foreach ($this->dependencies[$name] as $dependency) {
                $dep_instance = $this->get($dependency);
                if ($dep_instance !== null) {
                    $dependencies[] = $dep_instance;
                } else {
                    throw new Exception("Cannot resolve dependency '{$dependency}' for service '{$name}'");
                }
            }
        }
        
        return $dependencies;
    }
    
    private function resolve_alias($name) {
        return $this->aliases[$name] ?? $name;
    }
    
    private function is_singleton($name) {
        return $this->metadata[$name]['singleton'] ?? true;
    }
    
    private function is_valid_service_name($name) {
        return is_string($name) && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name);
    }
    
    private function get_services_by_priority() {
        $services_with_priority = array();
        
        foreach ($this->metadata as $name => $meta) {
            $services_with_priority[$name] = $meta['priority'];
        }
        
        asort($services_with_priority);
        
        return array_keys($services_with_priority);
    }
    
    private function get_dependents($service_name) {
        $dependents = array();
        
        foreach ($this->dependencies as $service => $deps) {
            if (in_array($service_name, $deps)) {
                $dependents[] = $service;
            }
        }
        
        return $dependents;
    }
    
    private function get_aliases_for_service($service_name) {
        $aliases = array();
        
        foreach ($this->aliases as $alias => $target) {
            if ($target === $service_name) {
                $aliases[] = $alias;
            }
        }
        
        return $aliases;
    }
    
    private function has_circular_dependency($service, $visited = array()) {
        if (in_array($service, $visited)) {
            return true;
        }
        
        $visited[] = $service;
        
        if (isset($this->dependencies[$service])) {
            foreach ($this->dependencies[$service] as $dependency) {
                if ($this->has_circular_dependency($dependency, $visited)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function init_load_stats() {
        $this->load_stats = array(
            'total_loads' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'total_load_time' => 0,
            'average_load_time' => 0,
            'errors' => 0
        );
    }
    
    private function update_load_stats($service_name, $start_time, $cache_hit, $error = null) {
        $load_time = microtime(true) - $start_time;
        
        $this->load_stats['total_loads']++;
        $this->load_stats['total_load_time'] += $load_time;
        $this->load_stats['average_load_time'] = $this->load_stats['total_load_time'] / $this->load_stats['total_loads'];
        
        if ($cache_hit) {
            $this->load_stats['cache_hits']++;
        } else {
            $this->load_stats['cache_misses']++;
        }
        
        if ($error) {
            $this->load_stats['errors']++;
        }
        
        if ($this->debug_mode) {
            $status = $cache_hit ? 'cache hit' : 'cache miss';
            if ($error) {
                $status = 'error: ' . $error;
            }
            error_log("QCC Service Registry: Loaded '{$service_name}' in " . round($load_time * 1000, 2) . "ms ({$status})");
        }
    }
}
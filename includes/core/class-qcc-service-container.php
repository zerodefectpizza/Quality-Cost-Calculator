<?php
/**
 * QCC Service Container - Dependency Injection System
 * 
 * SPEICHERN ALS: wp-content/plugins/quality-cost-calculator/includes/core/class-qcc-service-container.php
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Container for Dependency Injection
 */
class QCC_Service_Container {
    
    /**
     * Registered services
     * @var array
     */
    private $services = array();
    
    /**
     * Service instances
     * @var array
     */
    private $instances = array();
    
    /**
     * Singleton services
     * @var array
     */
    private $singletons = array();
    
    /**
     * Register a service
     * 
     * @param string $name Service name
     * @param callable $factory Factory function
     * @param bool $singleton Whether to create singleton
     */
    public function register($name, $factory, $singleton = true) {
        $this->services[$name] = $factory;
        
        if ($singleton) {
            $this->singletons[] = $name;
        }
        
        if (QCC_DEBUG) {
            error_log("QCC Container: Registered service '{$name}'");
        }
    }
    
    /**
     * Get a service instance
     * 
     * @param string $name Service name
     * @return mixed Service instance
     * @throws Exception If service not found
     */
    public function get($name) {
        // Return existing singleton instance
        if (in_array($name, $this->singletons) && isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        // Check if service is registered
        if (!isset($this->services[$name])) {
            if (QCC_DEBUG) {
                error_log("QCC Container: Service '{$name}' not found");
            }
            return null;
        }
        
        // Create new instance
        try {
            $factory = $this->services[$name];
            $instance = call_user_func($factory);
            
            // Store singleton instance
            if (in_array($name, $this->singletons)) {
                $this->instances[$name] = $instance;
            }
            
            if (QCC_DEBUG) {
                error_log("QCC Container: Created instance for service '{$name}'");
            }
            
            return $instance;
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log("QCC Container: Failed to create service '{$name}': " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Check if service exists
     * 
     * @param string $name Service name
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
    }
    
    /**
     * Remove a service
     * 
     * @param string $name Service name
     */
    public function remove($name) {
        unset($this->services[$name]);
        unset($this->instances[$name]);
        
        $key = array_search($name, $this->singletons);
        if ($key !== false) {
            unset($this->singletons[$key]);
        }
    }
    
    /**
     * Get all registered service names
     * 
     * @return array
     */
    public function get_service_names() {
        return array_keys($this->services);
    }
    
    /**
     * Clear all services
     */
    public function clear() {
        $this->services = array();
        $this->instances = array();
        $this->singletons = array();
    }
    
    /**
     * Get container statistics
     * 
     * @return array
     */
    public function get_stats() {
        return array(
            'registered_services' => count($this->services),
            'singleton_services' => count($this->singletons),
            'instantiated_services' => count($this->instances),
            'service_names' => array_keys($this->services)
        );
    }
}
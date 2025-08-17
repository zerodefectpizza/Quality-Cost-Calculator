<?php
/**
 * QCC Service Container - Dependency Injection System
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Container for Quality Cost Calculator
 * 
 * Implements dependency injection pattern for better testability and modularity
 */
class QCC_Service_Container {
    
    /**
     * Registered services
     * 
     * @var array
     */
    private $services = array();
    
    /**
     * Service instances (singletons)
     * 
     * @var array
     */
    private $instances = array();
    
    /**
     * Service aliases
     * 
     * @var array
     */
    private $aliases = array();
    
    /**
     * Singleton factories
     * 
     * @var array
     */
    private $singletons = array();
    
    /**
     * Service resolution stack (for circular dependency detection)
     * 
     * @var array
     */
    private $resolution_stack = array();
    
    /**
     * Register a service
     * 
     * @param string $name Service name
     * @param callable $factory Factory function
     * @param bool $singleton Whether service should be singleton
     * @return self
     */
    public function register($name, $factory, $singleton = true) {
        if (!is_callable($factory)) {
            throw new InvalidArgumentException("Factory for service '{$name}' must be callable");
        }
        
        $this->services[$name] = $factory;
        
        if ($singleton) {
            $this->singletons[$name] = true;
        }
        
        return $this;
    }
    
    /**
     * Register a service alias
     * 
     * @param string $alias Alias name
     * @param string $service Original service name
     * @return self
     */
    public function alias($alias, $service) {
        $this->aliases[$alias] = $service;
        return $this;
    }
    
    /**
     * Register a singleton service
     * 
     * @param string $name Service name
     * @param callable $factory Factory function
     * @return self
     */
    public function singleton($name, $factory) {
        return $this->register($name, $factory, true);
    }
    
    /**
     * Register a transient service (new instance each time)
     * 
     * @param string $name Service name
     * @param callable $factory Factory function
     * @return self
     */
    public function transient($name, $factory) {
        return $this->register($name, $factory, false);
    }
    
    /**
     * Register an existing instance
     * 
     * @param string $name Service name
     * @param mixed $instance Service instance
     * @return self
     */
    public function instance($name, $instance) {
        $this->instances[$name] = $instance;
        $this->singletons[$name] = true;
        return $this;
    }
    
    /**
     * Get a service
     * 
     * @param string $name Service name
     * @return mixed Service instance
     * @throws Exception If service not found or circular dependency detected
     */
    public function get($name) {
        // Resolve alias
        $original_name = $name;
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        
        // Check for circular dependency
        if (in_array($name, $this->resolution_stack)) {
            throw new Exception("Circular dependency detected: " . implode(' -> ', $this->resolution_stack) . " -> {$name}");
        }
        
        // Return existing instance if singleton
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        // Check if service is registered
        if (!isset($this->services[$name])) {
            throw new Exception("Service '{$original_name}' not found in container");
        }
        
        // Add to resolution stack
        $this->resolution_stack[] = $name;
        
        try {
            // Create instance
            $factory = $this->services[$name];
            $instance = call_user_func($factory, $this);
            
            // Store instance if singleton
            if (isset($this->singletons[$name])) {
                $this->instances[$name] = $instance;
            }
            
            // Remove from resolution stack
            array_pop($this->resolution_stack);
            
            return $instance;
            
        } catch (Exception $e) {
            // Remove from resolution stack on error
            array_pop($this->resolution_stack);
            throw new Exception("Failed to resolve service '{$original_name}': " . $e->getMessage());
        }
    }
    
    /**
     * Check if service is registered
     * 
     * @param string $name Service name
     * @return bool
     */
    public function has($name) {
        // Check aliases
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        
        return isset($this->services[$name]) || isset($this->instances[$name]);
    }
    
    /**
     * Remove a service
     * 
     * @param string $name Service name
     * @return self
     */
    public function remove($name) {
        // Check aliases
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        
        unset($this->services[$name]);
        unset($this->instances[$name]);
        unset($this->singletons[$name]);
        
        // Remove aliases pointing to this service
        foreach ($this->aliases as $alias => $service) {
            if ($service === $name) {
                unset($this->aliases[$alias]);
            }
        }
        
        return $this;
    }
    
    /**
     * Clear all services
     * 
     * @return self
     */
    public function clear() {
        $this->services = array();
        $this->instances = array();
        $this->aliases = array();
        $this->singletons = array();
        $this->resolution_stack = array();
        
        return $this;
    }
    
    /**
     * Get all registered service names
     * 
     * @return array
     */
    public function get_registered_services() {
        return array_merge(array_keys($this->services), array_keys($this->instances));
    }
    
    /**
     * Get all service aliases
     * 
     * @return array
     */
    public function get_aliases() {
        return $this->aliases;
    }
    
    /**
     * Get container statistics
     * 
     * @return array
     */
    public function get_stats() {
        return array(
            'total_services' => count($this->services) + count($this->instances),
            'registered_factories' => count($this->services),
            'registered_instances' => count($this->instances),
            'singletons' => count($this->singletons),
            'aliases' => count($this->aliases),
            'instantiated_services' => array_keys($this->instances),
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    /**
     * Call a method on a service with dependency injection
     * 
     * @param string $service_name Service name
     * @param string $method Method name
     * @param array $parameters Additional parameters
     * @return mixed Method return value
     */
    public function call($service_name, $method, $parameters = array()) {
        $service = $this->get($service_name);
        
        if (!method_exists($service, $method)) {
            throw new Exception("Method '{$method}' not found on service '{$service_name}'");
        }
        
        return call_user_func_array(array($service, $method), $parameters);
    }
    
    /**
     * Create an instance of a class with dependency injection
     * 
     * @param string $class_name Class name
     * @param array $parameters Additional constructor parameters
     * @return object Class instance
     */
    public function make($class_name, $parameters = array()) {
        if (!class_exists($class_name)) {
            throw new Exception("Class '{$class_name}' not found");
        }
        
        $reflection = new ReflectionClass($class_name);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            return new $class_name();
        }
        
        $dependencies = $this->resolve_dependencies($constructor, $parameters);
        
        return $reflection->newInstanceArgs($dependencies);
    }
    
    /**
     * Resolve method dependencies
     * 
     * @param ReflectionMethod $method Method reflection
     * @param array $parameters Additional parameters
     * @return array Resolved dependencies
     */
    private function resolve_dependencies(ReflectionMethod $method, $parameters = array()) {
        $dependencies = array();
        $method_parameters = $method->getParameters();
        
        foreach ($method_parameters as $index => $parameter) {
            // Use provided parameter if available
            if (isset($parameters[$index])) {
                $dependencies[] = $parameters[$index];
                continue;
            }
            
            // Try to resolve from type hint
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                $type_name = $type->getName();
                
                // Convert class name to service name
                $service_name = $this->class_to_service_name($type_name);
                
                if ($this->has($service_name)) {
                    $dependencies[] = $this->get($service_name);
                    continue;
                }
                
                // Try to create instance automatically
                try {
                    $dependencies[] = $this->make($type_name);
                    continue;
                } catch (Exception $e) {
                    // Fall through to default value or error
                }
            }
            
            // Use default value if available
            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }
            
            // Parameter cannot be resolved
            throw new Exception("Cannot resolve parameter '{$parameter->getName()}' for method '{$method->getName()}'");
        }
        
        return $dependencies;
    }
    
    /**
     * Convert class name to service name
     * 
     * @param string $class_name Class name
     * @return string Service name
     */
    private function class_to_service_name($class_name) {
        // Remove namespace
        $class_name = basename(str_replace('\\', '/', $class_name));
        
        // Convert PascalCase to snake_case
        $service_name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class_name));
        
        // Remove common prefixes
        $service_name = preg_replace('/^qcc_/', '', $service_name);
        
        return $service_name;
    }
    
    /**
     * Register default QCC services
     * 
     * @return self
     */
    public function register_default_services() {
        // Configuration service
        $this->singleton('config', function() {
            return new QCC_Configuration();
        });
        
        // Translation service
        $this->singleton('translator', function($container) {
            $config = $container->get('config');
            return new QCC_Translation_Service($config);
        });
        
        // Validation service
        $this->singleton('validator', function($container) {
            return new QCC_Validation_Engine();
        });
        
        // Cache service
        $this->singleton('cache', function() {
            return new QCC_Cache_Service();
        });
        
        // Performance monitor
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->singleton('performance_monitor', function() {
                return new QCC_Performance_Monitor();
            });
        }
        
        // Register aliases
        $this->alias('configuration', 'config');
        $this->alias('translation', 'translator');
        $this->alias('validation', 'validator');
        
        return $this;
    }
    
    /**
     * Extend a service (decorator pattern)
     * 
     * @param string $name Service name
     * @param callable $extender Extender function
     * @return self
     */
    public function extend($name, $extender) {
        if (!isset($this->services[$name]) && !isset($this->instances[$name])) {
            throw new Exception("Cannot extend non-existent service '{$name}'");
        }
        
        $original_factory = $this->services[$name] ?? function() use ($name) {
            return $this->instances[$name];
        };
        
        $this->services[$name] = function($container) use ($original_factory, $extender) {
            $original_service = call_user_func($original_factory, $container);
            return call_user_func($extender, $original_service, $container);
        };
        
        // Remove cached instance if exists
        unset($this->instances[$name]);
        
        return $this;
    }
    
    /**
     * Tag services for bulk operations
     * 
     * @param array $services Service names
     * @param string $tag Tag name
     * @return self
     */
    public function tag($services, $tag) {
        if (!isset($this->tags)) {
            $this->tags = array();
        }
        
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = array();
        }
        
        $this->tags[$tag] = array_merge($this->tags[$tag], (array) $services);
        
        return $this;
    }
    
    /**
     * Get services by tag
     * 
     * @param string $tag Tag name
     * @return array Service instances
     */
    public function tagged($tag) {
        if (!isset($this->tags[$tag])) {
            return array();
        }
        
        $services = array();
        foreach ($this->tags[$tag] as $service_name) {
            if ($this->has($service_name)) {
                $services[$service_name] = $this->get($service_name);
            }
        }
        
        return $services;
    }
    
    /**
     * Debug information about container state
     * 
     * @return array Debug information
     */
    public function debug() {
        return array(
            'services' => array_keys($this->services),
            'instances' => array_keys($this->instances),
            'singletons' => array_keys($this->singletons),
            'aliases' => $this->aliases,
            'resolution_stack' => $this->resolution_stack,
            'tags' => isset($this->tags) ? $this->tags : array(),
            'memory_usage' => memory_get_usage(true),
            'stats' => $this->get_stats()
        );
    }
}
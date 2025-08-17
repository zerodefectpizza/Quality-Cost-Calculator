<?php
/**
 * QCC Component Registry
 * 
 * Manages registration, instantiation and dependency injection for all
 * rendering components including atoms, molecules, builders and services.
 *
 * @package QualityCostCalculator
 * @subpackage Rendering\Orchestration
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Component_Registry {
    
    private static $instance = null;
    
    private $atoms = array();
    private $molecules = array();
    private $builders = array();
    private $services = array();
    
    private $instances = array();
    private $dependency_map = array();
    private $loading_stack = array();
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->register_core_components();
    }
    
    /**
     * Register an atom component
     */
    public function register_atom($name, $class_name, $dependencies = array()) {
        $this->atoms[$name] = array(
            'class' => $class_name,
            'dependencies' => $dependencies,
            'type' => 'atom',
            'registered_at' => time()
        );
        
        $this->dependency_map[$name] = $dependencies;
        
        return $this;
    }
    
    /**
     * Register a molecule component
     */
    public function register_molecule($name, $class_name, $dependencies = array()) {
        $this->molecules[$name] = array(
            'class' => $class_name,
            'dependencies' => $dependencies,
            'type' => 'molecule',
            'registered_at' => time()
        );
        
        $this->dependency_map[$name] = $dependencies;
        
        return $this;
    }
    
    /**
     * Register a builder component
     */
    public function register_builder($name, $class_name, $dependencies = array()) {
        $this->builders[$name] = array(
            'class' => $class_name,
            'dependencies' => $dependencies,
            'type' => 'builder',
            'registered_at' => time()
        );
        
        $this->dependency_map[$name] = $dependencies;
        
        return $this;
    }
    
    /**
     * Register a service
     */
    public function register_service($name, $class_name, $dependencies = array()) {
        $this->services[$name] = array(
            'class' => $class_name,
            'dependencies' => $dependencies,
            'type' => 'service',
            'registered_at' => time()
        );
        
        $this->dependency_map[$name] = $dependencies;
        
        return $this;
    }
    
    /**
     * Get component instance with lazy loading and dependency injection
     */
    public function get($name, $force_new = false) {
        // Check for circular dependencies
        if (in_array($name, $this->loading_stack)) {
            throw new Exception("Circular dependency detected: " . implode(' -> ', $this->loading_stack) . ' -> ' . $name);
        }
        
        // Return cached instance if available
        if (!$force_new && isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        // Find component definition
        $definition = $this->find_component_definition($name);
        if (!$definition) {
            throw new Exception("Component '{$name}' not found in registry");
        }
        
        // Add to loading stack for circular dependency detection
        $this->loading_stack[] = $name;
        
        try {
            // Load dependencies first
            $dependencies = $this->load_dependencies($definition['dependencies']);
            
            // Create instance
            $instance = $this->create_instance($definition, $dependencies);
            
            // Cache instance
            if (!$force_new) {
                $this->instances[$name] = $instance;
            }
            
            // Remove from loading stack
            array_pop($this->loading_stack);
            
            return $instance;
            
        } catch (Exception $e) {
            // Remove from loading stack on error
            array_pop($this->loading_stack);
            throw $e;
        }
    }
    
    /**
     * Get multiple components at once
     */
    public function get_multiple($names) {
        $components = array();
        
        foreach ($names as $name) {
            try {
                $components[$name] = $this->get($name);
            } catch (Exception $e) {
                error_log("Failed to load component '{$name}': " . $e->getMessage());
                $components[$name] = null;
            }
        }
        
        return $components;
    }
    
    /**
     * Check if component is registered
     */
    public function has($name) {
        return $this->find_component_definition($name) !== null;
    }
    
    /**
     * Get all registered components of a specific type
     */
    public function get_all_atoms() {
        return array_keys($this->atoms);
    }
    
    public function get_all_molecules() {
        return array_keys($this->molecules);
    }
    
    public function get_all_builders() {
        return array_keys($this->builders);
    }
    
    public function get_all_services() {
        return array_keys($this->services);
    }
    
    /**
     * Get component information
     */
    public function get_component_info($name) {
        $definition = $this->find_component_definition($name);
        if (!$definition) {
            return null;
        }
        
        return array(
            'name' => $name,
            'class' => $definition['class'],
            'type' => $definition['type'],
            'dependencies' => $definition['dependencies'],
            'registered_at' => $definition['registered_at'],
            'is_instantiated' => isset($this->instances[$name]),
            'dependency_tree' => $this->get_dependency_tree($name)
        );
    }
    
    /**
     * Clear all cached instances
     */
    public function clear_instances() {
        $this->instances = array();
    }
    
    /**
     * Clear specific component instance
     */
    public function clear_instance($name) {
        unset($this->instances[$name]);
    }
    
    /**
     * Find component definition across all registries
     */
    private function find_component_definition($name) {
        if (isset($this->atoms[$name])) {
            return $this->atoms[$name];
        }
        
        if (isset($this->molecules[$name])) {
            return $this->molecules[$name];
        }
        
        if (isset($this->builders[$name])) {
            return $this->builders[$name];
        }
        
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }
        
        return null;
    }
    
    /**
     * Load all dependencies for a component
     */
    private function load_dependencies($dependencies) {
        $loaded_dependencies = array();
        
        foreach ($dependencies as $dependency_name) {
            $loaded_dependencies[$dependency_name] = $this->get($dependency_name);
        }
        
        return $loaded_dependencies;
    }
    
    /**
     * Create component instance with dependency injection
     */
    private function create_instance($definition, $dependencies) {
        $class_name = $definition['class'];
        
        // Check if class exists
        if (!class_exists($class_name)) {
            throw new Exception("Class '{$class_name}' does not exist");
        }
        
        // Create instance
        $instance = new $class_name();
        
        // Inject dependencies if the instance supports it
        if (method_exists($instance, 'set_dependencies')) {
            $instance->set_dependencies($dependencies);
        }
        
        // Alternative dependency injection through constructor
        if (method_exists($instance, 'inject_dependencies')) {
            $instance->inject_dependencies($dependencies);
        }
        
        // Initialize if method exists
        if (method_exists($instance, 'init')) {
            $instance->init();
        }
        
        return $instance;
    }
    
    /**
     * Get dependency tree for a component
     */
    private function get_dependency_tree($name, $visited = array()) {
        if (in_array($name, $visited)) {
            return array('circular' => true);
        }
        
        $visited[] = $name;
        $dependencies = $this->dependency_map[$name] ?? array();
        $tree = array();
        
        foreach ($dependencies as $dependency) {
            $tree[$dependency] = $this->get_dependency_tree($dependency, $visited);
        }
        
        return $tree;
    }
    
    /**
     * Register core components
     */
    private function register_core_components() {
        // Register Builders
        $this->register_builder('form_builder', 'QCC_Form_Builder', array('translator', 'template_manager'));
        $this->register_builder('display_builder', 'QCC_Display_Builder', array('translator', 'template_manager', 'asset_manager'));
        $this->register_builder('control_builder', 'QCC_Control_Builder', array('translator', 'template_manager', 'asset_manager'));
        $this->register_builder('layout_builder', 'QCC_Layout_Builder', array('translator', 'template_manager'));
        
        // Register Atoms - Input Components
        $this->register_atom('percentage_input', 'QCC_Percentage_Input', array('translator'));
        $this->register_atom('currency_input', 'QCC_Currency_Input', array('translator'));
        $this->register_atom('select_input', 'QCC_Select_Input', array('translator'));
        $this->register_atom('text_input', 'QCC_Text_Input', array('translator'));
        
        // Register Atoms - Display Components
        $this->register_atom('result_card', 'QCC_Result_Card', array('translator'));
        $this->register_atom('chart_container', 'QCC_Chart_Container', array('asset_manager'));
        $this->register_atom('status_display', 'QCC_Status_Display', array('translator'));
        $this->register_atom('button', 'QCC_Button', array('translator'));
        
        // Register Molecules
        $this->register_molecule('input_group', 'QCC_Input_Group', array('translator', 'percentage_input', 'currency_input', 'select_input'));
        $this->register_molecule('result_section', 'QCC_Result_Section', array('translator', 'result_card'));
        $this->register_molecule('control_panel', 'QCC_Control_Panel', array('translator', 'button'));
        $this->register_molecule('chart_section', 'QCC_Chart_Section', array('translator', 'chart_container'));
        
        // Register Factories
        $this->register_service('input_factory', 'QCC_Input_Factory', array('percentage_input', 'currency_input', 'select_input', 'text_input'));
        $this->register_service('display_factory', 'QCC_Display_Factory', array('result_card', 'chart_container', 'status_display', 'button'));
    }
    
    /**
     * Validate component registration
     */
    public function validate_component($name) {
        $definition = $this->find_component_definition($name);
        if (!$definition) {
            return array('valid' => false, 'error' => 'Component not found');
        }
        
        // Check if class exists
        if (!class_exists($definition['class'])) {
            return array('valid' => false, 'error' => 'Class does not exist: ' . $definition['class']);
        }
        
        // Check dependencies
        foreach ($definition['dependencies'] as $dependency) {
            if (!$this->has($dependency)) {
                return array('valid' => false, 'error' => 'Missing dependency: ' . $dependency);
            }
        }
        
        // Check for circular dependencies
        try {
            $this->get_dependency_tree($name);
        } catch (Exception $e) {
            return array('valid' => false, 'error' => 'Circular dependency detected');
        }
        
        return array('valid' => true);
    }
    
    /**
     * Validate all registered components
     */
    public function validate_all_components() {
        $results = array();
        $all_components = array_merge(
            array_keys($this->atoms),
            array_keys($this->molecules),
            array_keys($this->builders),
            array_keys($this->services)
        );
        
        foreach ($all_components as $component) {
            $results[$component] = $this->validate_component($component);
        }
        
        return $results;
    }
    
    /**
     * Get registry statistics
     */
    public function get_statistics() {
        return array(
            'atoms' => count($this->atoms),
            'molecules' => count($this->molecules),
            'builders' => count($this->builders),
            'services' => count($this->services),
            'total_registered' => count($this->atoms) + count($this->molecules) + count($this->builders) + count($this->services),
            'instantiated' => count($this->instances),
            'memory_usage' => $this->get_memory_usage()
        );
    }
    
    /**
     * Get memory usage of cached instances
     */
    private function get_memory_usage() {
        $memory = 0;
        foreach ($this->instances as $instance) {
            $memory += strlen(serialize($instance));
        }
        return $memory;
    }
    
    /**
     * Debug method to get full registry state
     */
    public function debug_dump() {
        return array(
            'atoms' => $this->atoms,
            'molecules' => $this->molecules,
            'builders' => $this->builders,
            'services' => $this->services,
            'instances' => array_keys($this->instances),
            'dependency_map' => $this->dependency_map,
            'statistics' => $this->get_statistics()
        );
    }
    
    /**
     * Register multiple components from configuration array
     */
    public function register_from_config($config) {
        foreach ($config as $type => $components) {
            foreach ($components as $name => $component_config) {
                $class = $component_config['class'];
                $dependencies = $component_config['dependencies'] ?? array();
                
                switch ($type) {
                    case 'atoms':
                        $this->register_atom($name, $class, $dependencies);
                        break;
                    case 'molecules':
                        $this->register_molecule($name, $class, $dependencies);
                        break;
                    case 'builders':
                        $this->register_builder($name, $class, $dependencies);
                        break;
                    case 'services':
                        $this->register_service($name, $class, $dependencies);
                        break;
                }
            }
        }
    }
    
    /**
     * Unregister a component
     */
    public function unregister($name) {
        unset($this->atoms[$name]);
        unset($this->molecules[$name]);
        unset($this->builders[$name]);
        unset($this->services[$name]);
        unset($this->instances[$name]);
        unset($this->dependency_map[$name]);
    }
    
    /**
     * Check if component can be safely unregistered
     */
    public function can_unregister($name) {
        // Check if any other components depend on this one
        foreach ($this->dependency_map as $component => $dependencies) {
            if (in_array($name, $dependencies)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get components that depend on a specific component
     */
    public function get_dependents($name) {
        $dependents = array();
        
        foreach ($this->dependency_map as $component => $dependencies) {
            if (in_array($name, $dependencies)) {
                $dependents[] = $component;
            }
        }
        
        return $dependents;
    }
}
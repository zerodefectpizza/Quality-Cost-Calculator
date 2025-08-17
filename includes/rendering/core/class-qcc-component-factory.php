<?php
/**
 * QCC Component Factory
 * 
 * Factory für Component-Erstellung mit Dependency Injection.
 * Erstellt und konfiguriert Components basierend auf Registry-Definitionen.
 * 
 * @package QualityCostCalculator
 * @subpackage Core
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QCC_Component_Factory
 * 
 * @since 3.0.0
 */
class QCC_Component_Factory {
    
    /**
     * Component-Registry
     * 
     * @since 3.0.0
     * @var QCC_Component_Registry
     */
    private $registry;
    
    /**
     * Service-Container
     * 
     * @since 3.0.0
     * @var QCC_Service_Container
     */
    private $service_container;
    
    /**
     * Factory-Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    private $config = array(
        'auto_inject_dependencies' => true,
        'validate_interfaces' => true,
        'cache_instances' => true,
        'strict_mode' => false
    );
    
    /**
     * Created Components Counter
     * 
     * @since 3.0.0
     * @var int
     */
    private $created_count = 0;
    
    /**
     * Factory-Cache
     * 
     * @since 3.0.0
     * @var array
     */
    private $factory_cache = array();
    
    /**
     * Creation Hooks
     * 
     * @since 3.0.0
     * @var array
     */
    private $creation_hooks = array();
    
    /**
     * Debug-Modus
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     * @param QCC_Component_Registry $registry Component registry
     * @param QCC_Service_Container $service_container Service container
     */
    public function __construct($registry = null, $service_container = null) {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->registry = $registry ?: QCC_Component_Registry::get_instance();
        $this->service_container = $service_container ?: QCC_Service_Container::get_instance();
        $this->init_creation_hooks();
    }
    
    /**
     * Creation-Hooks initialisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_creation_hooks() {
        $this->creation_hooks = array(
            'before_create' => array(),
            'after_create' => array(),
            'configure' => array(),
            'validate' => array()
        );
    }
    
    /**
     * Component erstellen
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param array $config Component configuration
     * @param string $type Component type ('auto', 'atom', 'molecule', 'builder')
     * @return object|null Created component instance
     */
    public function create($name, $config = array(), $type = 'auto') {
        // Cache-Check
        if ($this->config['cache_instances']) {
            $cache_key = $this->get_cache_key($name, $config, $type);
            if (isset($this->factory_cache[$cache_key])) {
                return $this->factory_cache[$cache_key];
            }
        }
        
        // Before create hook
        $this->run_creation_hook('before_create', $name, $config, $type);
        
        try {
            // Component-Info aus Registry
            $component_info = $this->registry->get_component_info($name);
            if (!$component_info) {
                throw new Exception("Component '$name' not found in registry");
            }
            
            // Klasse laden und validieren
            $class_name = $component_info['class'];
            $this->ensure_class_loaded($class_name, $component_info);
            
            // Interface-Validierung
            if ($this->config['validate_interfaces']) {
                $this->validate_component_interfaces($class_name, $component_info['type']);
            }
            
            // Dependencies auflösen
            $dependencies = $this->resolve_dependencies($component_info['dependencies']);
            
            // Instanz erstellen
            $instance = $this->create_instance($class_name, $dependencies, $config);
            
            // Component konfigurieren
            $this->configure_component($instance, $config, $component_info);
            
            // Validierung der erstellten Instanz
            $this->validate_created_component($instance, $component_info);
            
            // After create hook
            $this->run_creation_hook('after_create', $name, $instance, $config);
            
            // Cache speichern
            if ($this->config['cache_instances']) {
                $this->factory_cache[$cache_key] = $instance;
            }
            
            $this->created_count++;
            
            do_action('qcc_component_created', $name, $instance, $config);
            
            return $instance;
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Factory: Error creating component '$name': " . $e->getMessage());
            }
            
            if ($this->config['strict_mode']) {
                throw $e;
            }
            
            return null;
        }
    }
    
    /**
     * Cache-Key generieren
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param array $config Configuration
     * @param string $type Component type
     * @return string Cache key
     */
    private function get_cache_key($name, $config, $type) {
        return sprintf(
            'qcc_factory_%s_%s_%s',
            $name,
            $type,
            md5(serialize($config))
        );
    }
    
    /**
     * Klasse laden sicherstellen
     * 
     * @since 3.0.0
     * @param string $class_name Class name
     * @param array $component_info Component info
     * @return void
     * @throws Exception
     */
    private function ensure_class_loaded($class_name, $component_info) {
        if (class_exists($class_name)) {
            return;
        }
        
        if (isset($component_info['file'])) {
            $file_path = QCC_PLUGIN_PATH . 'includes/rendering/' . $component_info['file'];
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                throw new Exception("Component file not found: {$component_info['file']}");
            }
        }
        
        if (!class_exists($class_name)) {
            throw new Exception("Class '$class_name' not found after loading file");
        }
    }
    
    /**
     * Component-Interfaces validieren
     * 
     * @since 3.0.0
     * @param string $class_name Class name
     * @param string $component_type Component type
     * @return void
     * @throws Exception
     */
    private function validate_component_interfaces($class_name, $component_type) {
        $reflection = new ReflectionClass($class_name);
        
        // Basis-Interface prüfen
        if (!$reflection->implementsInterface('QCC_Renderable')) {
            throw new Exception("Class '$class_name' must implement QCC_Renderable interface");
        }
        
        // Typ-spezifische Interfaces prüfen
        switch ($component_type) {
            case 'atom':
                if (!$reflection->implementsInterface('QCC_Atom')) {
                    throw new Exception("Atom class '$class_name' must implement QCC_Atom interface");
                }
                break;
                
            case 'molecule':
                if (!$reflection->implementsInterface('QCC_Molecule')) {
                    throw new Exception("Molecule class '$class_name' must implement QCC_Molecule interface");
                }
                break;
                
            case 'builder':
                if (!$reflection->implementsInterface('QCC_Builder')) {
                    throw new Exception("Builder class '$class_name' must implement QCC_Builder interface");
                }
                break;
        }
    }
    
    /**
     * Dependencies auflösen
     * 
     * @since 3.0.0
     * @param array $dependencies Dependency definition
     * @return array Resolved dependencies
     */
    private function resolve_dependencies($dependencies) {
        $resolved = array();
        
        if (empty($dependencies)) {
            return $resolved;
        }
        
        // Service-Dependencies
        if (isset($dependencies['services'])) {
            $resolved['services'] = array();
            foreach ($dependencies['services'] as $service_name) {
                try {
                    $resolved['services'][$service_name] = $this->service_container->get($service_name);
                } catch (Exception $e) {
                    if ($this->config['strict_mode']) {
                        throw new Exception("Required service '$service_name' not available");
                    }
                    if ($this->debug_mode) {
                        error_log("QCC Factory: Service dependency '$service_name' not available");
                    }
                }
            }
        }
        
        // Component-Dependencies
        if (isset($dependencies['atoms'])) {
            $resolved['atoms'] = array();
            foreach ($dependencies['atoms'] as $atom_name) {
                $atom = $this->registry->get($atom_name, 'atom');
                if ($atom) {
                    $resolved['atoms'][$atom_name] = $atom;
                } elseif ($this->config['strict_mode']) {
                    throw new Exception("Required atom '$atom_name' not available");
                }
            }
        }
        
        if (isset($dependencies['molecules'])) {
            $resolved['molecules'] = array();
            foreach ($dependencies['molecules'] as $molecule_name) {
                $molecule = $this->registry->get($molecule_name, 'molecule');
                if ($molecule) {
                    $resolved['molecules'][$molecule_name] = $molecule;
                } elseif ($this->config['strict_mode']) {
                    throw new Exception("Required molecule '$molecule_name' not available");
                }
            }
        }
        
        return $resolved;
    }
    
    /**
     * Component-Instanz erstellen
     * 
     * @since 3.0.0
     * @param string $class_name Class name
     * @param array $dependencies Resolved dependencies
     * @param array $config Component configuration
     * @return object Component instance
     */
    private function create_instance($class_name, $dependencies, $config) {
        // Constructor-Parameter ermitteln
        $reflection = new ReflectionClass($class_name);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
            // Kein Constructor - einfache Instanziierung
            return new $class_name();
        }
        
        // Constructor-Parameter aufbauen
        $params = $this->build_constructor_params($constructor, $dependencies, $config);
        
        // Instanz mit Parametern erstellen
        return $reflection->newInstanceArgs($params);
    }
    
    /**
     * Constructor-Parameter aufbauen
     * 
     * @since 3.0.0
     * @param ReflectionMethod $constructor Constructor method
     * @param array $dependencies Resolved dependencies
     * @param array $config Configuration
     * @return array Constructor parameters
     */
    private function build_constructor_params($constructor, $dependencies, $config) {
        $params = array();
        $parameters = $constructor->getParameters();
        
        foreach ($parameters as $param) {
            $param_name = $param->getName();
            $param_class = $param->getClass();
            
            // Service-Injection
            if ($param_class && isset($dependencies['services'])) {
                foreach ($dependencies['services'] as $service) {
                    if ($service instanceof $param_class->getName()) {
                        $params[] = $service;
                        continue 2;
                    }
                }
            }
            
            // Config-Parameter
            if (isset($config[$param_name])) {
                $params[] = $config[$param_name];
                continue;
            }
            
            // Default-Wert verwenden
            if ($param->isDefaultValueAvailable()) {
                $params[] = $param->getDefaultValue();
            } elseif ($param->allowsNull()) {
                $params[] = null;
            } else {
                throw new Exception("Cannot resolve constructor parameter '$param_name' for class");
            }
        }
        
        return $params;
    }
    
    /**
     * Component konfigurieren
     * 
     * @since 3.0.0
     * @param object $instance Component instance
     * @param array $config Configuration
     * @param array $component_info Component info
     * @return void
     */
    private function configure_component($instance, $config, $component_info) {
        // Auto-Dependency-Injection
        if ($this->config['auto_inject_dependencies']) {
            if (method_exists($instance, 'set_service_container')) {
                $instance->set_service_container($this->service_container);
            }
            
            if (method_exists($instance, 'set_registry')) {
                $instance->set_registry($this->registry);
            }
        }
        
        // Konfiguration anwenden
        if (method_exists($instance, 'configure')) {
            $instance->configure($config);
        }
        
        // Config-Properties setzen
        foreach ($config as $key => $value) {
            $setter = 'set_' . $key;
            if (method_exists($instance, $setter)) {
                $instance->$setter($value);
            }
        }
        
        // Configure hook
        $this->run_creation_hook('configure', $instance, $config, $component_info);
    }
    
    /**
     * Erstellte Component validieren
     * 
     * @since 3.0.0
     * @param object $instance Component instance
     * @param array $component_info Component info
     * @return void
     * @throws Exception
     */
    private function validate_created_component($instance, $component_info) {
        // Validate hook
        $validation = $this->run_creation_hook('validate', $instance, $component_info);
        
        if (is_wp_error($validation)) {
            throw new Exception("Component validation failed: " . $validation->get_error_message());
        }
        
        // Basic method validation
        if (!method_exists($instance, 'render')) {
            throw new Exception("Component must have render() method");
        }
        
        // Type-specific validation
        switch ($component_info['type']) {
            case 'atom':
                if (!method_exists($instance, 'get_input_type')) {
                    throw new Exception("Atom must have get_input_type() method");
                }
                break;
                
            case 'molecule':
                if (!method_exists($instance, 'get_child_components')) {
                    throw new Exception("Molecule must have get_child_components() method");
                }
                break;
                
            case 'builder':
                if (!method_exists($instance, 'build')) {
                    throw new Exception("Builder must have build() method");
                }
                break;
        }
    }
    
    /**
     * Creation-Hook ausführen
     * 
     * @since 3.0.0
     * @param string $hook_name Hook name
     * @param mixed ...$args Hook arguments
     * @return mixed Hook result
     */
    private function run_creation_hook($hook_name, ...$args) {
        if (!isset($this->creation_hooks[$hook_name])) {
            return null;
        }
        
        $result = null;
        foreach ($this->creation_hooks[$hook_name] as $callback) {
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, $args);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Creation-Hook registrieren
     * 
     * @since 3.0.0
     * @param string $hook_name Hook name
     * @param callable $callback Callback function
     * @return void
     */
    public function add_creation_hook($hook_name, $callback) {
        if (!isset($this->creation_hooks[$hook_name])) {
            $this->creation_hooks[$hook_name] = array();
        }
        
        $this->creation_hooks[$hook_name][] = $callback;
    }
    
    /**
     * Bulk-Components erstellen
     * 
     * @since 3.0.0
     * @param array $component_specs Component specifications
     * @return array Created components
     */
    public function create_bulk($component_specs) {
        $created = array();
        
        foreach ($component_specs as $name => $spec) {
            $config = $spec['config'] ?? array();
            $type = $spec['type'] ?? 'auto';
            
            $component = $this->create($name, $config, $type);
            if ($component) {
                $created[$name] = $component;
            }
        }
        
        return $created;
    }
    
    /**
     * Factory-Konfiguration setzen
     * 
     * @since 3.0.0
     * @param array $config Factory configuration
     * @return void
     */
    public function set_config($config) {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Factory-Konfiguration abrufen
     * 
     * @since 3.0.0
     * @return array Factory configuration
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Factory-Cache leeren
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear_cache() {
        $this->factory_cache = array();
        do_action('qcc_factory_cache_cleared');
    }
    
    /**
     * Factory-Statistiken
     * 
     * @since 3.0.0
     * @return array Statistics
     */
    public function get_statistics() {
        return array(
            'components_created' => $this->created_count,
            'cached_instances' => count($this->factory_cache),
            'registered_hooks' => array_map('count', $this->creation_hooks),
            'memory_usage' => memory_get_usage(),
            'config' => $this->config
        );
    }
    
    /**
     * Factory validieren
     * 
     * @since 3.0.0
     * @return array Validation results
     */
    public function validate_factory() {
        $issues = array();
        
        // Registry-Verfügbarkeit
        if (!$this->registry) {
            $issues[] = 'Component registry not available';
        }
        
        // Service-Container-Verfügbarkeit
        if (!$this->service_container) {
            $issues[] = 'Service container not available';
        }
        
        // Cache-Integrität
        foreach ($this->factory_cache as $key => $instance) {
            if (!is_object($instance)) {
                $issues[] = "Invalid cached instance for key: $key";
            }
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues,
            'checked_at' => current_time('timestamp')
        );
    }
    
    /**
     * Debug-Informationen
     * 
     * @since 3.0.0
     * @return array Debug information
     */
    public function get_debug_info() {
        if (!$this->debug_mode) {
            return array('debug_mode' => false);
        }
        
        return array(
            'debug_mode' => true,
            'statistics' => $this->get_statistics(),
            'cached_components' => array_keys($this->factory_cache),
            'creation_hooks' => array_keys($this->creation_hooks),
            'config' => $this->config,
            'validation' => $this->validate_factory()
        );
    }
}
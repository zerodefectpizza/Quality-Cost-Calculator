<?php
/**
 * QCC Component Registry
 * 
 * Central registry für alle Rendering-Components.
 * Verwaltet Registration, Lazy Loading und Dependency Injection.
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
 * Class QCC_Component_Registry
 * 
 * @since 3.0.0
 */
class QCC_Component_Registry {
    
    /**
     * Singleton-Instanz
     * 
     * @since 3.0.0
     * @var QCC_Component_Registry
     */
    private static $instance = null;
    
    /**
     * Registrierte Atoms
     * 
     * @since 3.0.0
     * @var array
     */
    private $atoms = array();
    
    /**
     * Registrierte Molecules
     * 
     * @since 3.0.0
     * @var array
     */
    private $molecules = array();
    
    /**
     * Registrierte Builders
     * 
     * @since 3.0.0
     * @var array
     */
    private $builders = array();
    
    /**
     * Component-Instanzen (Cache)
     * 
     * @since 3.0.0
     * @var array
     */
    private $instances = array();
    
    /**
     * Service-Container
     * 
     * @since 3.0.0
     * @var QCC_Service_Container
     */
    private $service_container;
    
    /**
     * Loaded Components Counter
     * 
     * @since 3.0.0
     * @var int
     */
    private $loaded_count = 0;
    
    /**
     * Component-Aliases
     * 
     * @since 3.0.0
     * @var array
     */
    private $aliases = array();
    
    /**
     * Auto-Loading aktiviert
     * 
     * @since 3.0.0
     * @var bool
     */
    private $auto_loading = true;
    
    /**
     * Debug-Modus
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor (private für Singleton)
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->init_service_container();
        $this->register_default_components();
    }
    
    /**
     * Singleton-Instanz abrufen
     * 
     * @since 3.0.0
     * @return QCC_Component_Registry
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Service-Container initialisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_service_container() {
        try {
            $this->service_container = QCC_Service_Container::get_instance();
        } catch (Exception $e) {
            $this->service_container = null;
            if ($this->debug_mode) {
                error_log('QCC Component Registry: Service Container not available');
            }
        }
    }
    
    /**
     * Default-Components registrieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_default_components() {
        // Atoms
        $this->register_atom('percentage-input', 'QCC_Percentage_Input', array(
            'file' => 'atoms/inputs/class-qcc-percentage-input.php'
        ));
        
        $this->register_atom('currency-input', 'QCC_Currency_Input', array(
            'file' => 'atoms/inputs/class-qcc-currency-input.php'
        ));
        
        $this->register_atom('select-input', 'QCC_Select_Input', array(
            'file' => 'atoms/inputs/class-qcc-select-input.php'
        ));
        
        $this->register_atom('result-card', 'QCC_Result_Card', array(
            'file' => 'atoms/displays/class-qcc-result-card.php'
        ));
        
        // Molecules
        $this->register_molecule('input-group', 'QCC_Input_Group', array(
            'file' => 'molecules/class-qcc-input-group.php',
            'dependencies' => array('atoms' => array('percentage-input'))
        ));
        
        // Builders
        $this->register_builder('form-builder', 'QCC_Form_Builder', array(
            'file' => 'builders/class-qcc-form-builder.php',
            'dependencies' => array('molecules' => array('input-group'))
        ));
    }
    
    /**
     * Atom registrieren
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param string $class_name Class name
     * @param array $options Additional options
     * @return bool Success
     */
    public function register_atom($name, $class_name, $options = array()) {
        if ($this->is_registered($name)) {
            if ($this->debug_mode) {
                error_log("QCC Registry: Atom '$name' already registered");
            }
            return false;
        }
        
        $this->atoms[$name] = array(
            'class' => $class_name,
            'type' => 'atom',
            'file' => $options['file'] ?? null,
            'dependencies' => $options['dependencies'] ?? array(),
            'priority' => $options['priority'] ?? 10,
            'lazy_load' => $options['lazy_load'] ?? true,
            'registered_at' => current_time('timestamp')
        );
        
        // Alias registrieren falls vorhanden
        if (isset($options['alias'])) {
            $this->register_alias($options['alias'], $name);
        }
        
        do_action('qcc_atom_registered', $name, $class_name, $options);
        
        return true;
    }
    
    /**
     * Molecule registrieren
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param string $class_name Class name
     * @param array $options Additional options
     * @return bool Success
     */
    public function register_molecule($name, $class_name, $options = array()) {
        if ($this->is_registered($name)) {
            if ($this->debug_mode) {
                error_log("QCC Registry: Molecule '$name' already registered");
            }
            return false;
        }
        
        $this->molecules[$name] = array(
            'class' => $class_name,
            'type' => 'molecule',
            'file' => $options['file'] ?? null,
            'dependencies' => $options['dependencies'] ?? array(),
            'priority' => $options['priority'] ?? 20,
            'lazy_load' => $options['lazy_load'] ?? true,
            'registered_at' => current_time('timestamp')
        );
        
        if (isset($options['alias'])) {
            $this->register_alias($options['alias'], $name);
        }
        
        do_action('qcc_molecule_registered', $name, $class_name, $options);
        
        return true;
    }
    
    /**
     * Builder registrieren
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param string $class_name Class name
     * @param array $options Additional options
     * @return bool Success
     */
    public function register_builder($name, $class_name, $options = array()) {
        if ($this->is_registered($name)) {
            if ($this->debug_mode) {
                error_log("QCC Registry: Builder '$name' already registered");
            }
            return false;
        }
        
        $this->builders[$name] = array(
            'class' => $class_name,
            'type' => 'builder',
            'file' => $options['file'] ?? null,
            'dependencies' => $options['dependencies'] ?? array(),
            'priority' => $options['priority'] ?? 30,
            'lazy_load' => $options['lazy_load'] ?? true,
            'registered_at' => current_time('timestamp')
        );
        
        if (isset($options['alias'])) {
            $this->register_alias($options['alias'], $name);
        }
        
        do_action('qcc_builder_registered', $name, $class_name, $options);
        
        return true;
    }
    
    /**
     * Component-Instanz abrufen (mit Lazy Loading)
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param string $type Component type ('auto', 'atom', 'molecule', 'builder')
     * @return object|null Component instance
     */
    public function get($name, $type = 'auto') {
        // Alias auflösen
        $resolved_name = $this->resolve_alias($name);
        $cache_key = $type . '_' . $resolved_name;
        
        // Bereits instanziiert?
        if (isset($this->instances[$cache_key])) {
            return $this->instances[$cache_key];
        }
        
        // Component-Definition finden
        $definition = $this->find_component_definition($resolved_name, $type);
        if (!$definition) {
            if ($this->debug_mode) {
                error_log("QCC Registry: Component '$resolved_name' not found");
            }
            return null;
        }
        
        // Dependencies laden
        $this->load_dependencies($definition['dependencies']);
        
        // Klasse laden falls File definiert
        if (isset($definition['file']) && !class_exists($definition['class'])) {
            $file_path = QCC_PLUGIN_PATH . 'includes/rendering/' . $definition['file'];
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                if ($this->debug_mode) {
                    error_log("QCC Registry: File not found: $file_path");
                }
                return null;
            }
        }
        
        // Instanz erstellen
        try {
            $class_name = $definition['class'];
            if (!class_exists($class_name)) {
                if ($this->debug_mode) {
                    error_log("QCC Registry: Class '$class_name' not found");
                }
                return null;
            }
            
            $instance = new $class_name();
            
            // Dependencies injizieren falls unterstützt
            if (method_exists($instance, 'set_dependencies')) {
                $instance->set_dependencies($this->service_container);
            }
            
            // Cache für weitere Verwendung
            $this->instances[$cache_key] = $instance;
            $this->loaded_count++;
            
            do_action('qcc_component_loaded', $resolved_name, $instance, $definition);
            
            return $instance;
            
        } catch (Exception $e) {
            if ($this->debug_mode) {
                error_log("QCC Registry: Error creating instance of '$class_name': " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Component-Definition finden
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @param string $type Component type
     * @return array|null Component definition
     */
    private function find_component_definition($name, $type) {
        if ($type === 'atom' && isset($this->atoms[$name])) {
            return $this->atoms[$name];
        }
        
        if ($type === 'molecule' && isset($this->molecules[$name])) {
            return $this->molecules[$name];
        }
        
        if ($type === 'builder' && isset($this->builders[$name])) {
            return $this->builders[$name];
        }
        
        // Auto-Detection bei type='auto'
        if ($type === 'auto') {
            if (isset($this->atoms[$name])) {
                return $this->atoms[$name];
            }
            if (isset($this->molecules[$name])) {
                return $this->molecules[$name];
            }
            if (isset($this->builders[$name])) {
                return $this->builders[$name];
            }
        }
        
        return null;
    }
    
    /**
     * Dependencies laden
     * 
     * @since 3.0.0
     * @param array $dependencies Dependency definition
     * @return void
     */
    private function load_dependencies($dependencies) {
        if (empty($dependencies)) {
            return;
        }
        
        // Atom-Dependencies
        if (isset($dependencies['atoms'])) {
            foreach ($dependencies['atoms'] as $atom_name) {
                $this->get($atom_name, 'atom');
            }
        }
        
        // Molecule-Dependencies
        if (isset($dependencies['molecules'])) {
            foreach ($dependencies['molecules'] as $molecule_name) {
                $this->get($molecule_name, 'molecule');
            }
        }
        
        // Service-Dependencies
        if (isset($dependencies['services']) && $this->service_container) {
            foreach ($dependencies['services'] as $service_name) {
                try {
                    $this->service_container->get($service_name);
                } catch (Exception $e) {
                    if ($this->debug_mode) {
                        error_log("QCC Registry: Service dependency '$service_name' not available");
                    }
                }
            }
        }
    }
    
    /**
     * Alle Atoms abrufen
     * 
     * @since 3.0.0
     * @return array Atom names
     */
    public function get_all_atoms() {
        return array_keys($this->atoms);
    }
    
    /**
     * Alle Molecules abrufen
     * 
     * @since 3.0.0
     * @return array Molecule names
     */
    public function get_all_molecules() {
        return array_keys($this->molecules);
    }
    
    /**
     * Alle Builders abrufen
     * 
     * @since 3.0.0
     * @return array Builder names
     */
    public function get_all_builders() {
        return array_keys($this->builders);
    }
    
    /**
     * Component-Registration prüfen
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @return bool True wenn registriert
     */
    public function is_registered($name) {
        $resolved_name = $this->resolve_alias($name);
        return isset($this->atoms[$resolved_name]) || 
               isset($this->molecules[$resolved_name]) || 
               isset($this->builders[$resolved_name]);
    }
    
    /**
     * Component-Information abrufen
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @return array|null Component info
     */
    public function get_component_info($name) {
        $resolved_name = $this->resolve_alias($name);
        
        if (isset($this->atoms[$resolved_name])) {
            return $this->atoms[$resolved_name];
        }
        
        if (isset($this->molecules[$resolved_name])) {
            return $this->molecules[$resolved_name];
        }
        
        if (isset($this->builders[$resolved_name])) {
            return $this->builders[$resolved_name];
        }
        
        return null;
    }
    
    /**
     * Component deregistrieren
     * 
     * @since 3.0.0
     * @param string $name Component name
     * @return bool Success
     */
    public function unregister($name) {
        $resolved_name = $this->resolve_alias($name);
        $found = false;
        
        if (isset($this->atoms[$resolved_name])) {
            unset($this->atoms[$resolved_name]);
            $found = true;
        }
        
        if (isset($this->molecules[$resolved_name])) {
            unset($this->molecules[$resolved_name]);
            $found = true;
        }
        
        if (isset($this->builders[$resolved_name])) {
            unset($this->builders[$resolved_name]);
            $found = true;
        }
        
        // Instanz-Cache löschen
        foreach ($this->instances as $cache_key => $instance) {
            if (strpos($cache_key, '_' . $resolved_name) !== false) {
                unset($this->instances[$cache_key]);
            }
        }
        
        if ($found) {
            do_action('qcc_component_unregistered', $resolved_name);
        }
        
        return $found;
    }
    
    /**
     * Alias registrieren
     * 
     * @since 3.0.0
     * @param string $alias Alias name
     * @param string $real_name Real component name
     * @return void
     */
    public function register_alias($alias, $real_name) {
        $this->aliases[$alias] = $real_name;
    }
    
    /**
     * Alias auflösen
     * 
     * @since 3.0.0
     * @param string $name Possible alias
     * @return string Real name
     */
    private function resolve_alias($name) {
        return $this->aliases[$name] ?? $name;
    }
    
    /**
     * Registry-Statistiken
     * 
     * @since 3.0.0
     * @return array Statistics
     */
    public function get_statistics() {
        return array(
            'atoms_registered' => count($this->atoms),
            'molecules_registered' => count($this->molecules),
            'builders_registered' => count($this->builders),
            'total_registered' => count($this->atoms) + count($this->molecules) + count($this->builders),
            'instances_loaded' => $this->loaded_count,
            'instances_cached' => count($this->instances),
            'aliases_registered' => count($this->aliases),
            'memory_usage' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage()
        );
    }
    
    /**
     * Registry-Cache leeren
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear_cache() {
        $this->instances = array();
        $this->loaded_count = 0;
        
        do_action('qcc_registry_cache_cleared');
    }
    
    /**
     * Auto-Loading togglen
     * 
     * @since 3.0.0
     * @param bool $enabled Auto-loading enabled
     * @return void
     */
    public function set_auto_loading($enabled) {
        $this->auto_loading = (bool) $enabled;
    }
    
    /**
     * Debug-Info ausgeben
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
            'registered_atoms' => array_keys($this->atoms),
            'registered_molecules' => array_keys($this->molecules),
            'registered_builders' => array_keys($this->builders),
            'loaded_instances' => array_keys($this->instances),
            'aliases' => $this->aliases,
            'auto_loading' => $this->auto_loading
        );
    }
    
    /**
     * Registry-Status validieren
     * 
     * @since 3.0.0
     * @return array Validation results
     */
    public function validate_registry() {
        $issues = array();
        
        // Prüfe auf fehlende Klassen
        foreach (array_merge($this->atoms, $this->molecules, $this->builders) as $name => $definition) {
            if (!class_exists($definition['class'])) {
                $issues[] = "Class '{$definition['class']}' for component '$name' not found";
            }
        }
        
        // Prüfe auf zirkuläre Dependencies
        foreach ($this->molecules as $name => $definition) {
            if ($this->has_circular_dependencies($name, $definition['dependencies'])) {
                $issues[] = "Circular dependency detected for molecule '$name'";
            }
        }
        
        return array(
            'valid' => empty($issues),
            'issues' => $issues,
            'checked_at' => current_time('timestamp')
        );
    }
    
    /**
     * Zirkuläre Dependencies prüfen
     * 
     * @since 3.0.0
     * @param string $component_name Component name
     * @param array $dependencies Dependencies to check
     * @param array $visited Already visited components
     * @return bool True wenn zirkulär
     */
    private function has_circular_dependencies($component_name, $dependencies, $visited = array()) {
        if (in_array($component_name, $visited)) {
            return true;
        }
        
        $visited[] = $component_name;
        
        foreach ($dependencies as $type => $deps) {
            foreach ($deps as $dep_name) {
                $dep_info = $this->get_component_info($dep_name);
                if ($dep_info && isset($dep_info['dependencies'])) {
                    if ($this->has_circular_dependencies($dep_name, $dep_info['dependencies'], $visited)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
}
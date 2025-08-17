<?php
/**
 * QCC Base Molecule Abstract Class
 * 
 * Abstract base class für alle Molecule-Components.
 * Erweitert QCC_Base_Component um molecule-spezifische Funktionalitäten.
 * 
 * @package QualityCostCalculator
 * @subpackage Base
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Class QCC_Base_Molecule
 * 
 * @since 3.0.0
 * @abstract
 */
abstract class QCC_Base_Molecule extends QCC_Base_Component implements QCC_Molecule {
    
    /**
     * Child-Components Definition
     * 
     * @since 3.0.0
     * @var array
     */
    protected $child_components = array();
    
    /**
     * Child-Component-Instanzen (Lazy Loading)
     * 
     * @since 3.0.0
     * @var array
     */
    protected $child_instances = array();
    
    /**
     * Layout-Template
     * 
     * @since 3.0.0
     * @var string
     */
    protected $layout_template = '';
    
    /**
     * Layout-Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    protected $layout_config = array(
        'orientation' => 'vertical',
        'spacing' => 'medium',
        'alignment' => 'left',
        'wrap_children' => true
    );
    
    /**
     * Molecule-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $molecule_type = 'form';
    
    /**
     * Event-Propagation-Rules
     * 
     * @since 3.0.0
     * @var array
     */
    protected $event_propagation_rules = array();
    
    /**
     * Ob Molecule kollabierbar ist
     * 
     * @since 3.0.0
     * @var bool
     */
    protected $is_collapsible = false;
    
    /**
     * Component-Registry für Child-Loading
     * 
     * @since 3.0.0
     * @var QCC_Component_Registry
     */
    protected $component_registry;
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_molecule_properties();
        $this->load_component_registry();
    }
    
    /**
     * Initialisiert Molecule-Eigenschaften
     * 
     * @since 3.0.0
     * @return void
     */
    protected function init_molecule_properties() {
        if (empty($this->layout_template)) {
            $this->layout_template = 'molecules/' . $this->component_name . '-layout.php';
        }
        
        // Child-Components validieren
        $this->validate_child_definitions();
    }
    
    /**
     * Lädt Component-Registry
     * 
     * @since 3.0.0
     * @return void
     */
    protected function load_component_registry() {
        try {
            $this->component_registry = $this->service_container->get('component_registry');
        } catch (Exception $e) {
            $this->component_registry = null;
        }
    }
    
    /**
     * Validiert Child-Component-Definitionen
     * 
     * @since 3.0.0
     * @return void
     */
    protected function validate_child_definitions() {
        foreach ($this->child_components as $name => $definition) {
            if (!isset($definition['type']) || !isset($definition['class'])) {
                wp_die(sprintf(
                    __('Invalid child component definition for "%s" in molecule "%s"', 'quality-cost-calculator'),
                    $name,
                    $this->component_name
                ));
            }
        }
    }
    
    /**
     * Molecule-Rendering mit Child-Assembly
     * 
     * @since 3.0.0
     * @param array $data Component data
     * @param array $attributes HTML attributes
     * @return string Rendered HTML
     */
    public function render($data = array(), $attributes = array()) {
        // Struktur-Validierung
        $structure_validation = $this->validate_structure();
        if (is_wp_error($structure_validation)) {
            return $this->handle_error($structure_validation);
        }
        
        // Data-Validierung
        $data_validation = $this->validate_data($data);
        if (is_wp_error($data_validation)) {
            return $this->handle_error($data_validation);
        }
        
        // Cache-Check
        if ($this->is_cacheable) {
            $cache_key = $this->get_cache_key($data);
            $cached = $this->cache_manager ? $this->cache_manager->get($cache_key) : false;
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Before render hook
        do_action('qcc_before_molecule_render', $this, $data);
        
        // Child-Data mapping
        $child_data = $this->map_data_to_children($data);
        
        // Children rendern
        $rendered_children = $this->render_children($child_data);
        
        // Children zu Molecule assemblieren
        $html = $this->assemble_components($rendered_children, $data);
        
        // Cache speichern
        if ($this->is_cacheable && $this->cache_manager) {
            $this->cache_manager->set($cache_key, $html, $this->cache_duration);
        }
        
        // After render hook
        do_action('qcc_after_molecule_render', $this, $html, $data);
        
        return $html;
    }
    
    /**
     * Child-Components getter
     * 
     * @since 3.0.0
     * @return array Child component definitions
     */
    public function get_child_components() {
        return $this->child_components;
    }
    
    /**
     * Data-Mapping zu Children (überschreibbar)
     * 
     * @since 3.0.0
     * @param array $data Input data
     * @return array Mapped data für children
     */
    public function map_data_to_children($data) {
        $mapped = array();
        
        foreach ($this->child_components as $child_name => $definition) {
            $mapped[$child_name] = $this->map_data_to_child($data, $child_name, $definition);
        }
        
        return apply_filters('qcc_molecule_map_child_data', $mapped, $data, $this);
    }
    
    /**
     * Data-Mapping zu einzelnem Child (überschreibbar)
     * 
     * @since 3.0.0
     * @param array $data Original data
     * @param string $child_name Child name
     * @param array $child_definition Child definition
     * @return array Child-specific data
     */
    protected function map_data_to_child($data, $child_name, $child_definition) {
        // Standard-Mapping: versuche matching field zu finden
        if (isset($data[$child_name])) {
            return $data[$child_name];
        }
        
        // Fallback: leere Daten mit ID
        return array(
            'id' => $data['id'] ?? $this->generate_unique_id($data) . '-' . $child_name,
            'name' => $child_name
        );
    }
    
    /**
     * Rendert alle Child-Components
     * 
     * @since 3.0.0
     * @param array $child_data Mapped child data
     * @return array Rendered child HTML
     */
    protected function render_children($child_data) {
        $rendered = array();
        
        foreach ($this->child_components as $child_name => $definition) {
            try {
                $child_instance = $this->get_child($child_name);
                if ($child_instance) {
                    $data = $child_data[$child_name] ?? array();
                    $rendered[$child_name] = $child_instance->render($data);
                } else {
                    $rendered[$child_name] = $this->handle_child_error(
                        $child_name,
                        new WP_Error('child_not_found', 'Child component not found')
                    );
                }
            } catch (Exception $e) {
                $rendered[$child_name] = $this->handle_child_error(
                    $child_name,
                    new WP_Error('child_render_error', $e->getMessage())
                );
            }
        }
        
        return $rendered;
    }
    
    /**
     * Assembliert Child-HTML zu Molecule
     * 
     * @since 3.0.0
     * @param array $rendered_children Rendered child HTML
     * @param array $data Original molecule data
     * @return string Assembled HTML
     */
    public function assemble_components($rendered_children, $data) {
        // Template-basierte Assembly
        if ($this->template_exists()) {
            return $this->render_template_assembly($rendered_children, $data);
        }
        
        // Fallback-Assembly
        return $this->render_fallback_assembly($rendered_children, $data);
    }
    
    /**
     * Template-basierte Assembly
     * 
     * @since 3.0.0
     * @param array $rendered_children Rendered children
     * @param array $data Original data
     * @return string Template HTML
     */
    protected function render_template_assembly($rendered_children, $data) {
        $template_vars = array(
            'children' => $rendered_children,
            'data' => $data,
            'molecule' => $this,
            'container_attributes' => $this->get_container_attributes($data),
            'css_classes' => $this->get_molecule_css_classes($data),
            'layout_config' => $this->get_layout_config($data)
        );
        
        return $this->template_manager->render($this->get_layout_template(), $template_vars);
    }
    
    /**
     * Fallback-Assembly ohne Template
     * 
     * @since 3.0.0
     * @param array $rendered_children Rendered children
     * @param array $data Original data
     * @return string Fallback HTML
     */
    protected function render_fallback_assembly($rendered_children, $data) {
        $container_attrs = $this->get_container_attributes($data);
        $attr_string = '';
        
        foreach ($container_attrs as $key => $value) {
            $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
        }
        
        $html = sprintf('<div%s>', $attr_string);
        
        foreach ($rendered_children as $child_name => $child_html) {
            if ($this->layout_config['wrap_children']) {
                $html .= sprintf(
                    '<div class="qcc-molecule-child qcc-child-%s">%s</div>',
                    esc_attr($child_name),
                    $child_html
                );
            } else {
                $html .= $child_html;
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Layout-Template getter
     * 
     * @since 3.0.0
     * @return string Layout template
     */
    public function get_layout_template() {
        return $this->layout_template;
    }
    
    /**
     * Layout-Konfiguration getter
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array Layout config
     */
    public function get_layout_config($data) {
        return apply_filters('qcc_molecule_layout_config', $this->layout_config, $data, $this);
    }
    
    /**
     * Struktur-Validierung
     * 
     * @since 3.0.0
     * @return true|WP_Error
     */
    public function validate_structure() {
        if (empty($this->child_components)) {
            return new WP_Error(
                'no_child_components',
                __('Molecule must have at least one child component', 'quality-cost-calculator')
            );
        }
        
        foreach ($this->child_components as $name => $definition) {
            if (!class_exists($definition['class'])) {
                return new WP_Error(
                    'child_class_not_found',
                    sprintf(__('Child component class "%s" not found', 'quality-cost-calculator'), $definition['class'])
                );
            }
        }
        
        return true;
    }
    
    /**
     * Child-Component-Instanz getter (Lazy Loading)
     * 
     * @since 3.0.0
     * @param string $child_name Child name
     * @return QCC_Atom|null
     */
    public function get_child($child_name) {
        if (isset($this->child_instances[$child_name])) {
            return $this->child_instances[$child_name];
        }
        
        if (!isset($this->child_components[$child_name])) {
            return null;
        }
        
        $definition = $this->child_components[$child_name];
        
        try {
            if ($this->component_registry) {
                $instance = $this->component_registry->get($child_name, $definition['type']);
            } else {
                $class_name = $definition['class'];
                $instance = new $class_name();
            }
            
            $this->child_instances[$child_name] = $instance;
            return $instance;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Child-Existenz-Prüfung
     * 
     * @since 3.0.0
     * @param string $child_name Child name
     * @return bool
     */
    public function has_child($child_name) {
        return isset($this->child_components[$child_name]);
    }
    
    /**
     * Child-Dependencies sammeln
     * 
     * @since 3.0.0
     * @return array Consolidated dependencies
     */
    public function get_child_dependencies() {
        $dependencies = array(
            'css' => array(),
            'js' => array(),
            'dependencies' => array()
        );
        
        foreach ($this->child_components as $child_name => $definition) {
            $child = $this->get_child($child_name);
            if ($child && method_exists($child, 'get_required_assets')) {
                $child_assets = $child->get_required_assets();
                
                $dependencies['css'] = array_merge($dependencies['css'], $child_assets['css'] ?? array());
                $dependencies['js'] = array_merge($dependencies['js'], $child_assets['js'] ?? array());
                $dependencies['dependencies'] = array_merge($dependencies['dependencies'], $child_assets['dependencies'] ?? array());
            }
        }
        
        // Duplikate entfernen
        $dependencies['css'] = array_unique($dependencies['css']);
        $dependencies['js'] = array_unique($dependencies['js']);
        $dependencies['dependencies'] = array_unique($dependencies['dependencies']);
        
        return $dependencies;
    }
    
    /**
     * Molecule-CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array CSS classes
     */
    public function get_molecule_css_classes($data) {
        $classes = array(
            'qcc-molecule',
            'qcc-molecule--' . $this->molecule_type,
            'qcc-molecule--' . $this->layout_config['orientation'],
            'qcc-molecule--spacing-' . $this->layout_config['spacing']
        );
        
        if ($this->is_collapsible) {
            $classes[] = 'qcc-molecule--collapsible';
            
            if ($this->get_default_expanded_state($data)) {
                $classes[] = 'qcc-molecule--expanded';
            } else {
                $classes[] = 'qcc-molecule--collapsed';
            }
        }
        
        return $classes;
    }
    
    /**
     * Event-Propagation-Rules getter
     * 
     * @since 3.0.0
     * @return array Event rules
     */
    public function get_event_propagation_rules() {
        return $this->event_propagation_rules;
    }
    
    /**
     * Container-Attribute
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array Container attributes
     */
    public function get_container_attributes($data) {
        $attributes = array(
            'class' => implode(' ', array_merge(
                explode(' ', $this->get_css_classes($data)),
                $this->get_molecule_css_classes($data)
            )),
            'data-molecule' => $this->component_name,
            'data-molecule-type' => $this->molecule_type
        );
        
        if ($this->is_collapsible) {
            $attributes['data-collapsible'] = 'true';
            $attributes['data-expanded'] = $this->get_default_expanded_state($data) ? 'true' : 'false';
        }
        
        return $attributes;
    }
    
    /**
     * Child-Error-Handling
     * 
     * @since 3.0.0
     * @param string $child_name Child name
     * @param WP_Error $error Error details
     * @return string Fallback HTML
     */
    public function handle_child_error($child_name, $error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return sprintf(
                '<div class="qcc-child-error" data-child="%s">%s: %s</div>',
                esc_attr($child_name),
                esc_html($error->get_error_code()),
                esc_html($error->get_error_message())
            );
        }
        
        return ''; // Skip fehlerhafte Children in Production
    }
    
    /**
     * Molecule-Typ getter
     * 
     * @since 3.0.0
     * @return string Molecule type
     */
    public function get_molecule_type() {
        return $this->molecule_type;
    }
    
    /**
     * Collapsible-Status getter
     * 
     * @since 3.0.0
     * @return bool
     */
    public function is_collapsible() {
        return $this->is_collapsible;
    }
    
    /**
     * Default-Expanded-State (überschreibbar)
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return bool
     */
    public function get_default_expanded_state($data) {
        // Expand wenn Fehler vorhanden
        return !empty($data['errors']) || !empty($data['expand']);
    }
    
    /**
     * Required fields für Molecules
     * 
     * @since 3.0.0
     * @return array Required fields
     */
    protected function get_required_fields() {
        return array('id');
    }
    
    /**
     * Assets mit Child-Dependencies
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        $child_assets = $this->get_child_dependencies();
        
        $molecule_assets = array(
            'css' => array($this->component_name . '.css', 'molecules.css'),
            'js' => array($this->component_name . '.js'),
            'dependencies' => array('qcc-molecules')
        );
        
        return array_merge_recursive($base_assets, $child_assets, $molecule_assets);
    }
}
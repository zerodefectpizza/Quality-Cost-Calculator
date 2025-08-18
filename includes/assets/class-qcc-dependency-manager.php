<?php
/**
 * QCC Dependency Manager - Asset Dependencies Management
 *
 * @package QualityCostCalculator
 * @subpackage Assets
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_Dependency_Manager {
    
    private $bootstrap;
    private $dependencies = array();
    private $loaded_dependencies = array();
    private $dependency_tree = array();
    
    public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ?: QCC_Bootstrap::get_instance();
        $this->init_dependencies();
    }
    
    /**
     * Initialize dependency mappings
     */
    private function init_dependencies() {
        $this->dependencies = array(
            'qcc-core' => array(
                'css' => array(),
                'js' => array('jquery'),
                'external' => false,
                'priority' => 1
            ),
            'qcc-calculator' => array(
                'css' => array('qcc-core'),
                'js' => array('qcc-core'),
                'external' => false,
                'priority' => 5
            ),
            'qcc-charts' => array(
                'css' => array('qcc-calculator'),
                'js' => array('qcc-calculator', 'chart-js'),
                'external' => false,
                'priority' => 10
            ),
            'chart-js' => array(
                'css' => array(),
                'js' => array(),
                'external' => true,
                'url' => 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
                'priority' => 3
            ),
            'qcc-export' => array(
                'css' => array('qcc-calculator'),
                'js' => array('qcc-calculator'),
                'external' => false,
                'priority' => 8
            ),
            'qcc-admin' => array(
                'css' => array(),
                'js' => array('jquery', 'wp-util'),
                'external' => false,
                'priority' => 5
            )
        );
    }
    
    /**
     * Register a new dependency
     */
    public function register_dependency($handle, $config) {
        $this->dependencies[$handle] = array_merge(array(
            'css' => array(),
            'js' => array(),
            'external' => false,
            'priority' => 10,
            'condition' => null
        ), $config);
        
        $this->build_dependency_tree();
    }
    
    /**
     * Get dependencies for a handle
     */
    public function get_dependencies($handle, $type = 'js') {
        if (!isset($this->dependencies[$handle])) {
            return array();
        }
        
        $deps = $this->dependencies[$handle][$type] ?? array();
        $resolved = array();
        
        foreach ($deps as $dep) {
            $resolved = array_merge($resolved, $this->resolve_dependency($dep, $type));
        }
        
        return array_unique($resolved);
    }
    
    /**
     * Resolve dependency recursively
     */
    private function resolve_dependency($handle, $type) {
        $resolved = array();
        
        if (isset($this->dependencies[$handle])) {
            $dep_deps = $this->dependencies[$handle][$type] ?? array();
            
            foreach ($dep_deps as $sub_dep) {
                $resolved = array_merge($resolved, $this->resolve_dependency($sub_dep, $type));
            }
        }
        
        $resolved[] = $handle;
        return $resolved;
    }
    
    /**
     * Get load order for dependencies
     */
    public function get_load_order($handles) {
        $ordered = array();
        $priorities = array();
        
        foreach ($handles as $handle) {
            if (isset($this->dependencies[$handle])) {
                $priority = $this->dependencies[$handle]['priority'] ?? 10;
                $priorities[$handle] = $priority;
            }
        }
        
        asort($priorities);
        return array_keys($priorities);
    }
    
    /**
     * Check if dependency should be loaded
     */
    public function should_load_dependency($handle) {
        if (!isset($this->dependencies[$handle])) {
            return false;
        }
        
        $config = $this->dependencies[$handle];
        
        // Check condition
        if (isset($config['condition']) && is_callable($config['condition'])) {
            return call_user_func($config['condition']);
        }
        
        // Check feature flags
        if (isset($config['feature_flag'])) {
            return $this->get_feature_flag($config['feature_flag'], true);
        }
        
        return true;
    }
    
    /**
     * Get external dependency URL
     */
    public function get_external_url($handle) {
        if (!isset($this->dependencies[$handle])) {
            return false;
        }
        
        $config = $this->dependencies[$handle];
        
        if (!($config['external'] ?? false)) {
            return false;
        }
        
        return $config['url'] ?? false;
    }
    
    /**
     * Build dependency tree
     */
    private function build_dependency_tree() {
        $this->dependency_tree = array();
        
        foreach ($this->dependencies as $handle => $config) {
            $this->dependency_tree[$handle] = array(
                'js_deps' => $this->get_dependencies($handle, 'js'),
                'css_deps' => $this->get_dependencies($handle, 'css'),
                'priority' => $config['priority'] ?? 10
            );
        }
    }
    
    /**
     * Validate dependency tree for circular dependencies
     */
    public function validate_dependencies() {
        $errors = array();
        
        foreach ($this->dependencies as $handle => $config) {
            if ($this->has_circular_dependency($handle)) {
                $errors[] = "Circular dependency detected for: {$handle}";
            }
        }
        
        return $errors;
    }
    
    /**
     * Check for circular dependencies
     */
    private function has_circular_dependency($handle, $visited = array()) {
        if (in_array($handle, $visited)) {
            return true;
        }
        
        if (!isset($this->dependencies[$handle])) {
            return false;
        }
        
        $visited[] = $handle;
        $deps = array_merge(
            $this->dependencies[$handle]['js'] ?? array(),
            $this->dependencies[$handle]['css'] ?? array()
        );
        
        foreach ($deps as $dep) {
            if ($this->has_circular_dependency($dep, $visited)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get dependency graph
     */
    public function get_dependency_graph() {
        $graph = array();
        
        foreach ($this->dependencies as $handle => $config) {
            $graph[$handle] = array(
                'depends_on' => array_merge(
                    $config['js'] ?? array(),
                    $config['css'] ?? array()
                ),
                'priority' => $config['priority'] ?? 10,
                'external' => $config['external'] ?? false
            );
        }
        
        return $graph;
    }
    
    /**
     * Mark dependency as loaded
     */
    public function mark_loaded($handle) {
        if (!in_array($handle, $this->loaded_dependencies)) {
            $this->loaded_dependencies[] = $handle;
        }
    }
    
    /**
     * Check if dependency is loaded
     */
    public function is_loaded($handle) {
        return in_array($handle, $this->loaded_dependencies);
    }
    
    /**
     * Get unloaded dependencies for a handle
     */
    public function get_unloaded_dependencies($handle) {
        $all_deps = array_merge(
            $this->get_dependencies($handle, 'js'),
            $this->get_dependencies($handle, 'css')
        );
        
        return array_diff($all_deps, $this->loaded_dependencies);
    }
    
    /**
     * Reset loaded dependencies
     */
    public function reset_loaded() {
        $this->loaded_dependencies = array();
    }
    
    /**
     * Get conditional dependencies
     */
    public function get_conditional_dependencies() {
        $conditional = array();
        
        foreach ($this->dependencies as $handle => $config) {
            if (isset($config['condition']) || isset($config['feature_flag'])) {
                $conditional[$handle] = array(
                    'should_load' => $this->should_load_dependency($handle),
                    'reason' => isset($config['condition']) ? 'condition' : 'feature_flag'
                );
            }
        }
        
        return $conditional;
    }
    
    /**
     * Get feature flag value
     */
    private function get_feature_flag($flag_name, $default = false) {
        if ($this->bootstrap) {
            return $this->bootstrap->get_feature_flag($flag_name);
        }
        return $default;
    }
    
    /**
     * Get statistics
     */
    public function get_statistics() {
        return array(
            'total_dependencies' => count($this->dependencies),
            'loaded_dependencies' => count($this->loaded_dependencies),
            'external_dependencies' => count(array_filter($this->dependencies, function($dep) {
                return $dep['external'] ?? false;
            })),
            'validation_errors' => $this->validate_dependencies()
        );
    }
    
    /**
     * Debug dependency tree
     */
    public function debug_tree() {
        if (!QCC_DEBUG) {
            return;
        }
        
        error_log('QCC Dependency Tree: ' . print_r($this->dependency_tree, true));
        error_log('QCC Loaded Dependencies: ' . implode(', ', $this->loaded_dependencies));
    }
}
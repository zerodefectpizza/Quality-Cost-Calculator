<?php
/**
 * Lazy Loading Manager
 */
class QCC_Lazy_Loader {
    private $loaded_components = array();
    
    public function load_component($component_name) {
        if (!isset($this->loaded_components[$component_name])) {
            $class_name = 'QCC_' . ucwords($component_name, '_');
            
            if (class_exists($class_name)) {
                $this->loaded_components[$component_name] = new $class_name();
            } else {
                throw new Exception("Component {$component_name} not found");
            }
        }
        
        return $this->loaded_components[$component_name];
    }
    
    public function is_loaded($component_name) {
        return isset($this->loaded_components[$component_name]);
    }
}
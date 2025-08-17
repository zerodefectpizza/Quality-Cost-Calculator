<?php
/**
 * Feature Toggle System
 */
class QCC_Feature_Flags {
    private $flags = array(
        'new_calculation_engine' => false,
        'new_html_renderer' => false,
        'new_translation_system' => false,
        'performance_monitoring' => true,
        'debug_logging' => false
    );
    
    public function is_enabled($feature) {
        return $this->flags[$feature] ?? false;
    }
    
    public function enable($feature) {
        $this->flags[$feature] = true;
        update_option('qcc_feature_flags', $this->flags);
    }
    
    public function disable($feature) {
        $this->flags[$feature] = false;
        update_option('qcc_feature_flags', $this->flags);
    }
}
<?php
/**
 * Plugin autoloader for Quality Cost Calculator - ERWEITERT FÜR NEUE ARCHITEKTUR
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Autoloader Class - Erweitert für modulare Architektur
 */
class QCC_Autoloader {
    
    /**
     * Class map for autoloading - VOLLSTÄNDIG ERWEITERT
     */
    private static $class_map = array(
        // =============================================================================
        // LEGACY CLASSES (bestehend)
        // =============================================================================
        'QCC_Core' => 'class-qcc-core.php',
        'QCC_Admin' => 'class-qcc-admin.php', 
        'QCC_Shortcode' => 'class-qcc-shortcode.php',
        'QCC_Ajax' => 'class-qcc-ajax.php',
        'QCC_I18n' => 'class-qcc-i18n.php',
        'QCC_Config' => 'qcc-config.php',
        'QCC_Cache' => 'class-qcc-cache.php',
        'QCC_Validator' => 'class-qcc-validator.php',
        
        // =============================================================================
        // NEUE CORE ARCHITECTURE (Phase 1)
        // =============================================================================
        'QCC_Bootstrap' => 'core/class-qcc-bootstrap.php',
        'QCC_Service_Container' => 'core/class-qcc-service-container.php',
        'QCC_Configuration' => 'core/class-qcc-configuration.php',
        'QCC_Plugin' => 'core/class-qcc-plugin.php',
        
        // =============================================================================
        // LEGACY FALLBACK SYSTEM 
        // =============================================================================
        'QCC_Legacy_Bootstrap' => 'legacy/class-qcc-legacy-bootstrap.php',
        'QCC_Shortcode_Legacy' => 'legacy/class-qcc-shortcode-legacy.php',
        'QCC_Legacy_Calculator' => 'legacy/class-qcc-legacy-calculator.php',
        
        // =============================================================================
        // PRESENTATION LAYER (Phase 2)
        // =============================================================================
        'QCC_Shortcode_Controller' => 'presentation/class-qcc-shortcode-controller.php',
        'QCC_Template_Router' => 'presentation/class-qcc-template-router.php',
        'QCC_Response_Builder' => 'presentation/class-qcc-response-builder.php',
        'QCC_Error_Handler' => 'presentation/class-qcc-error-handler.php',
        
        // =============================================================================
        // RENDERING LAYER (Phase 3)
        // =============================================================================
        
        // Orchestration
        'QCC_HTML_Orchestrator' => 'rendering/orchestration/class-qcc-html-orchestrator.php',
        'QCC_HTML_Renderer' => 'rendering/orchestration/class-qcc-html-renderer.php',
        
        // Builders
        'QCC_Form_Builder' => 'rendering/builders/class-qcc-form-builder.php',
        'QCC_Display_Builder' => 'rendering/builders/class-qcc-display-builder.php',
        'QCC_Control_Builder' => 'rendering/builders/class-qcc-control-builder.php',
        'QCC_Layout_Builder' => 'rendering/builders/class-qcc-layout-builder.php',
        'QCC_Section_Builder' => 'rendering/builders/class-qcc-section-builder.php',
        'QCC_UI_Builder' => 'rendering/builders/class-qcc-ui-builder.php',
        'QCC_Result_Builder' => 'rendering/builders/class-qcc-result-builder.php',
        
        // Atomic Components
        'QCC_Input_Factory' => 'rendering/atoms/class-qcc-input-factory.php',
        'QCC_Display_Factory' => 'rendering/atoms/class-qcc-display-factory.php',
        'QCC_Layout_Factory' => 'rendering/atoms/class-qcc-layout-factory.php',
        'QCC_Interaction_Factory' => 'rendering/atoms/class-qcc-interaction-factory.php',
        
        // Input Atoms
        'QCC_Percentage_Input' => 'rendering/atoms/inputs/class-qcc-percentage-input.php',
        'QCC_Currency_Input' => 'rendering/atoms/inputs/class-qcc-currency-input.php',
        'QCC_Select_Input' => 'rendering/atoms/inputs/class-qcc-select-input.php',
        'QCC_Number_Input' => 'rendering/atoms/inputs/class-qcc-number-input.php',
        
        // Display Atoms  
        'QCC_Result_Card' => 'rendering/atoms/displays/class-qcc-result-card.php',
        'QCC_Chart_Container' => 'rendering/atoms/displays/class-qcc-chart-container.php',
        'QCC_Status_Display' => 'rendering/atoms/displays/class-qcc-status-display.php',
        'QCC_Progress_Bar' => 'rendering/atoms/displays/class-qcc-progress-bar.php',
        
        // =============================================================================
        // BUSINESS LOGIC LAYER (Phase 4)
        // =============================================================================
        
        // Calculations
        'QCC_Calculation_Engine' => 'business/calculations/class-qcc-calculation-engine.php',
        'QCC_COGQ_Calculator' => 'business/calculations/class-qcc-cogq-calculator.php',
        'QCC_COPQ_Calculator' => 'business/calculations/class-qcc-copq-calculator.php',
        'QCC_Opportunity_Calculator' => 'business/calculations/class-qcc-opportunity-calculator.php',
        'QCC_ROI_Calculator' => 'business/calculations/class-qcc-roi-calculator.php',
        'QCC_Cost_Calculator' => 'business/calculations/class-qcc-cost-calculator.php',
        'QCC_Percentage_Calculator' => 'business/calculations/class-qcc-percentage-calculator.php',
        
        // Validation
        'QCC_Validation_Engine' => 'business/validation/class-qcc-validation-engine.php',
        'QCC_Input_Validator' => 'business/validation/class-qcc-input-validator.php',
        'QCC_Business_Validator' => 'business/validation/class-qcc-business-validator.php',
        'QCC_Integrity_Validator' => 'business/validation/class-qcc-integrity-validator.php',
        'QCC_Percentage_Validator' => 'business/validation/class-qcc-percentage-validator.php',
        'QCC_Currency_Validator' => 'business/validation/class-qcc-currency-validator.php',
        
        // =============================================================================
        // TRANSLATION LAYER (Phase 5)
        // =============================================================================
        'QCC_Translation_Service' => 'translation/class-qcc-translation-service.php',
        'QCC_Language_Detector' => 'translation/class-qcc-language-detector.php',
        'QCC_Translation_Cache' => 'translation/class-qcc-translation-cache.php',
        'QCC_Fallback_Handler' => 'translation/class-qcc-fallback-handler.php',
        'QCC_Translator' => 'translation/class-qcc-translator.php',
        
        // Language Specific Classes
        'QCC_English_Translations' => 'translation/languages/class-qcc-english-translations.php',
        'QCC_German_Translations' => 'translation/languages/class-qcc-german-translations.php',
        'QCC_French_Translations' => 'translation/languages/class-qcc-french-translations.php',
        'QCC_Spanish_Translations' => 'translation/languages/class-qcc-spanish-translations.php',
        'QCC_Chinese_Translations' => 'translation/languages/class-qcc-chinese-translations.php',
        
        // =============================================================================
        // ASSET MANAGEMENT (Phase 6)
        // =============================================================================
        'QCC_Asset_Manager' => 'assets/class-qcc-asset-manager.php',
        'QCC_JavaScript_Generator' => 'assets/class-qcc-javascript-generator.php',
        'QCC_CSS_Generator' => 'assets/class-qcc-css-generator.php',
        'QCC_Dependency_Manager' => 'assets/class-qcc-dependency-manager.php',
        'QCC_Theme_Manager' => 'assets/class-qcc-theme-manager.php',
        
        // =============================================================================
        // SERVICES LAYER
        // =============================================================================
        'QCC_Cache_Service' => 'services/class-qcc-cache-service.php',
        'QCC_Export_Service' => 'services/class-qcc-export-service.php',
        'QCC_Import_Service' => 'services/class-qcc-import-service.php',
        'QCC_Template_Service' => 'services/class-qcc-template-service.php',
        'QCC_Settings_Service' => 'services/class-qcc-settings-service.php',
        
        // =============================================================================
        // INFRASTRUCTURE LAYER
        // =============================================================================
        'QCC_Service_Registry' => 'infrastructure/class-qcc-service-registry.php',
        'QCC_Lazy_Loader' => 'infrastructure/class-qcc-lazy-loader.php',
        'QCC_Instance_Cache' => 'infrastructure/class-qcc-instance-cache.php',
        'QCC_Feature_Flags' => 'infrastructure/class-qcc-feature-flags.php',
        'QCC_Event_Manager' => 'infrastructure/class-qcc-event-manager.php',
        
        // =============================================================================
        // MONITORING & DEBUG
        // =============================================================================
        'QCC_Performance_Monitor' => 'monitoring/class-qcc-performance-monitor.php',
        'QCC_Error_Tracker' => 'monitoring/class-qcc-error-tracker.php',
        'QCC_Debug_Logger' => 'monitoring/class-qcc-debug-logger.php',
        'QCC_Memory_Monitor' => 'monitoring/class-qcc-memory-monitor.php',
        'QCC_System_Monitor' => 'monitoring/class-qcc-system-monitor.php',
        
        // =============================================================================
        // ADMIN INTERFACE
        // =============================================================================
        'QCC_Admin_Controller' => 'admin/class-qcc-admin-controller.php',
        'QCC_Admin_Page' => 'admin/class-qcc-admin-page.php',
        'QCC_Settings_Page' => 'admin/class-qcc-settings-page.php',
        'QCC_Status_Page' => 'admin/class-qcc-status-page.php',
        'QCC_Debug_Page' => 'admin/class-qcc-debug-page.php',
        
        // =============================================================================
        // INTERFACES (Design Contracts)
        // =============================================================================
        'QCC_Renderable_Interface' => 'interfaces/interface-qcc-renderable.php',
        'QCC_Configurable_Interface' => 'interfaces/interface-qcc-configurable.php',
        'QCC_Translatable_Interface' => 'interfaces/interface-qcc-translatable.php',
        'QCC_Calculable_Interface' => 'interfaces/interface-qcc-calculable.php',
        'QCC_Validatable_Interface' => 'interfaces/interface-qcc-validatable.php',
        'QCC_Cacheable_Interface' => 'interfaces/interface-qcc-cacheable.php'
    );
    
    /**
     * Service aliases for easier access
     */
    private static $aliases = array(
        'container' => 'QCC_Service_Container',
        'config' => 'QCC_Configuration',
        'translator' => 'QCC_Translation_Service',
        'calculator' => 'QCC_Calculation_Engine',
        'validator' => 'QCC_Validation_Engine',
        'renderer' => 'QCC_HTML_Orchestrator',
        'monitor' => 'QCC_Performance_Monitor'
    );
    
    /**
     * Registered autoloader flag
     */
    private static $registered = false;
    
    /**
     * Initialize and register autoloader
     */
    public static function register() {
        if (self::$registered) {
            return;
        }
        
        spl_autoload_register(array(__CLASS__, 'autoload'));
        self::$registered = true;
        
        // Log autoloader initialization
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log('QCC: Autoloader registered with ' . count(self::$class_map) . ' classes');
        }
    }
    
    /**
     * Unregister autoloader
     */
    public static function unregister() {
        if (self::$registered) {
            spl_autoload_unregister(array(__CLASS__, 'autoload'));
            self::$registered = false;
        }
    }
    
    /**
     * Autoload classes
     */
    public static function autoload($class_name) {
        // Check if class is in our namespace
        if (strpos($class_name, 'QCC_') !== 0) {
            return;
        }
        
        // Check for alias
        if (isset(self::$aliases[$class_name])) {
            $class_name = self::$aliases[$class_name];
        }
        
        // Check if we have a mapping for this class
        if (!isset(self::$class_map[$class_name])) {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log("QCC: No autoload mapping found for class: {$class_name}");
            }
            return;
        }
        
        // Build file path
        $file = QCC_PLUGIN_PATH . 'includes/' . self::$class_map[$class_name];
        
        // Load file if it exists
        if (file_exists($file)) {
            require_once $file;
            
            // Verify class was loaded
            if (class_exists($class_name)) {
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log("QCC: Successfully autoloaded class {$class_name} from {$file}");
                }
            } else {
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log("QCC: File loaded but class {$class_name} not found in {$file}");
                }
            }
        } else {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log("QCC: Failed to autoload class {$class_name} - file not found: {$file}");
            }
        }
    }
    
    /**
     * Load class manually (for explicit loading)
     */
    public static function load_class($class_name) {
        if (!class_exists($class_name)) {
            self::autoload($class_name);
        }
        return class_exists($class_name);
    }
    
    /**
     * Get all mapped classes
     */
    public static function get_class_map() {
        return self::$class_map;
    }
    
    /**
     * Get aliases
     */
    public static function get_aliases() {
        return self::$aliases;
    }
    
    /**
     * Add class to map dynamically
     */
    public static function add_class($class_name, $file_name) {
        self::$class_map[$class_name] = $file_name;
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Added class mapping: {$class_name} => {$file_name}");
        }
    }
    
    /**
     * Add alias
     */
    public static function add_alias($alias, $class_name) {
        self::$aliases[$alias] = $class_name;
    }
    
    /**
     * Remove class from map
     */
    public static function remove_class($class_name) {
        if (isset(self::$class_map[$class_name])) {
            unset(self::$class_map[$class_name]);
            
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log("QCC: Removed class mapping: {$class_name}");
            }
        }
    }
    
    /**
     * Check if all mapped classes exist
     */
    public static function validate_class_map() {
        $missing_files = array();
        $found_files = array();
        
        foreach (self::$class_map as $class => $file) {
            $file_path = QCC_PLUGIN_PATH . 'includes/' . $file;
            if (!file_exists($file_path)) {
                $missing_files[] = array(
                    'class' => $class,
                    'file' => $file,
                    'path' => $file_path
                );
            } else {
                $found_files[] = array(
                    'class' => $class,
                    'file' => $file,
                    'path' => $file_path
                );
            }
        }
        
        // Log results
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Class map validation: " . count($found_files) . " found, " . count($missing_files) . " missing");
            
            if (!empty($missing_files)) {
                foreach ($missing_files as $missing) {
                    error_log("QCC: Missing class file: {$missing['class']} => {$missing['file']}");
                }
            }
        }
        
        return $missing_files;
    }
    
    /**
     * Load core classes (minimal set for bootstrap)
     */
    public static function load_core_classes() {
        $core_classes = array(
            'QCC_Bootstrap',
            'QCC_Service_Container', 
            'QCC_Configuration'
        );
        
        $loaded = 0;
        foreach ($core_classes as $class) {
            if (self::load_class($class)) {
                $loaded++;
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Core classes loading: {$loaded}/" . count($core_classes) . " loaded");
        }
        
        return $loaded === count($core_classes);
    }
    
    /**
     * Load legacy classes (fallback system)
     */
    public static function load_legacy_classes() {
        $legacy_classes = array(
            'QCC_Legacy_Bootstrap',
            'QCC_Shortcode_Legacy'
        );
        
        $loaded = 0;
        foreach ($legacy_classes as $class) {
            if (self::load_class($class)) {
                $loaded++;
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Legacy classes loading: {$loaded}/" . count($legacy_classes) . " loaded");
        }
        
        return $loaded === count($legacy_classes);
    }
    
    /**
     * Load admin classes (only in admin)
     */
    public static function load_admin_classes() {
        if (!is_admin()) {
            return false;
        }
        
        $admin_classes = array(
            'QCC_Admin_Controller',
            'QCC_Admin_Page',
            'QCC_Settings_Page'
        );
        
        $loaded = 0;
        foreach ($admin_classes as $class) {
            if (self::load_class($class)) {
                $loaded++;
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Admin classes loading: {$loaded}/" . count($admin_classes) . " loaded");
        }
        
        return $loaded === count($admin_classes);
    }
    
    /**
     * Load frontend classes (only in frontend)
     */
    public static function load_frontend_classes() {
        if (is_admin() && !wp_doing_ajax()) {
            return false;
        }
        
        $frontend_classes = array(
            'QCC_Shortcode_Controller',
            'QCC_HTML_Orchestrator',
            'QCC_Translation_Service'
        );
        
        $loaded = 0;
        foreach ($frontend_classes as $class) {
            if (self::load_class($class)) {
                $loaded++;
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Frontend classes loading: {$loaded}/" . count($frontend_classes) . " loaded");
        }
        
        return $loaded === count($frontend_classes);
    }
    
    /**
     * Load classes by category/layer
     */
    public static function load_layer_classes($layer) {
        $layer_classes = array();
        
        switch ($layer) {
            case 'rendering':
                $layer_classes = array(
                    'QCC_HTML_Orchestrator',
                    'QCC_Form_Builder',
                    'QCC_Display_Builder',
                    'QCC_Input_Factory',
                    'QCC_Display_Factory'
                );
                break;
                
            case 'business':
                $layer_classes = array(
                    'QCC_Calculation_Engine',
                    'QCC_COGQ_Calculator',
                    'QCC_COPQ_Calculator',
                    'QCC_Validation_Engine'
                );
                break;
                
            case 'translation':
                $layer_classes = array(
                    'QCC_Translation_Service',
                    'QCC_Language_Detector',
                    'QCC_Translation_Cache'
                );
                break;
                
            case 'monitoring':
                $layer_classes = array(
                    'QCC_Performance_Monitor',
                    'QCC_Error_Tracker',
                    'QCC_Debug_Logger'
                );
                break;
        }
        
        $loaded = 0;
        foreach ($layer_classes as $class) {
            if (self::load_class($class)) {
                $loaded++;
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: {$layer} layer classes loading: {$loaded}/" . count($layer_classes) . " loaded");
        }
        
        return $loaded === count($layer_classes);
    }
    
    /**
     * Get autoloader statistics
     */
    public static function get_stats() {
        $stats = array(
            'total_classes' => count(self::$class_map),
            'total_aliases' => count(self::$aliases),
            'loaded_classes' => 0,
            'missing_files' => 0,
            'class_details' => array(),
            'memory_usage' => memory_get_usage(true),
            'registered' => self::$registered
        );
        
        foreach (self::$class_map as $class => $file) {
            $file_path = QCC_PLUGIN_PATH . 'includes/' . $file;
            $exists = file_exists($file_path);
            $loaded = class_exists($class);
            
            if (!$exists) {
                $stats['missing_files']++;
            }
            
            if ($loaded) {
                $stats['loaded_classes']++;
            }
            
            $stats['class_details'][$class] = array(
                'file' => $file,
                'file_exists' => $exists,
                'class_loaded' => $loaded,
                'file_path' => $file_path
            );
        }
        
        return $stats;
    }
    
    /**
     * Debug output for troubleshooting
     */
    public static function debug_output() {
        if (!defined('QCC_DEBUG') || !QCC_DEBUG) {
            return;
        }
        
        echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px; border: 1px solid #ccc; font-family: monospace;'>";
        echo "<h3>🔧 QCC Autoloader Debug Information</h3>";
        
        $stats = self::get_stats();
        echo "<p><strong>📊 Statistics:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Total Classes:</strong> {$stats['total_classes']}</li>";
        echo "<li><strong>Total Aliases:</strong> {$stats['total_aliases']}</li>";
        echo "<li><strong>Loaded Classes:</strong> {$stats['loaded_classes']}</li>";
        echo "<li><strong>Missing Files:</strong> {$stats['missing_files']}</li>";
        echo "<li><strong>Autoloader Registered:</strong> " . ($stats['registered'] ? 'Yes' : 'No') . "</li>";
        echo "<li><strong>Memory Usage:</strong> " . size_format($stats['memory_usage']) . "</li>";
        echo "</ul>";
        
        if (!empty(self::$aliases)) {
            echo "<p><strong>🔗 Aliases:</strong></p>";
            echo "<ul>";
            foreach (self::$aliases as $alias => $class) {
                echo "<li><code>{$alias}</code> → <code>{$class}</code></li>";
            }
            echo "</ul>";
        }
        
        echo "<p><strong>📁 Class Details:</strong></p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; font-size: 12px;'>";
        echo "<tr style='background: #ddd;'><th>Class</th><th>File</th><th>Exists</th><th>Loaded</th><th>Layer</th></tr>";
        
        foreach ($stats['class_details'] as $class => $details) {
            $file_status = $details['file_exists'] ? '✅' : '❌';
            $class_status = $details['class_loaded'] ? '✅' : '❌';
            $row_style = (!$details['file_exists'] || !$details['class_loaded']) ? 'background: #ffe6e6;' : '';
            
            // Determine layer
            $layer = 'Legacy';
            if (strpos($details['file'], 'core/') === 0) $layer = 'Core';
            elseif (strpos($details['file'], 'presentation/') === 0) $layer = 'Presentation';
            elseif (strpos($details['file'], 'rendering/') === 0) $layer = 'Rendering';
            elseif (strpos($details['file'], 'business/') === 0) $layer = 'Business';
            elseif (strpos($details['file'], 'translation/') === 0) $layer = 'Translation';
            elseif (strpos($details['file'], 'assets/') === 0) $layer = 'Assets';
            elseif (strpos($details['file'], 'services/') === 0) $layer = 'Services';
            elseif (strpos($details['file'], 'infrastructure/') === 0) $layer = 'Infrastructure';
            elseif (strpos($details['file'], 'monitoring/') === 0) $layer = 'Monitoring';
            elseif (strpos($details['file'], 'admin/') === 0) $layer = 'Admin';
            elseif (strpos($details['file'], 'interfaces/') === 0) $layer = 'Interface';
            elseif (strpos($details['file'], 'legacy/') === 0) $layer = 'Legacy';
            
            echo "<tr style='{$row_style}'>";
            echo "<td><code>{$class}</code></td>";
            echo "<td>{$details['file']}</td>";
            echo "<td>{$file_status}</td>";
            echo "<td>{$class_status}</td>";
            echo "<td><strong>{$layer}</strong></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    }
    
    /**
     * Get classes by layer
     */
    public static function get_classes_by_layer($layer) {
        $classes = array();
        
        foreach (self::$class_map as $class => $file) {
            if (strpos($file, $layer . '/') === 0) {
                $classes[] = $class;
            }
        }
        
        return $classes;
    }
    
    /**
     * Health check - verify critical classes can be loaded
     */
    public static function health_check() {
        $critical_classes = array(
            'QCC_Bootstrap',
            'QCC_Service_Container'
        );
        
        $health = array(
            'status' => 'healthy',
            'issues' => array(),
            'critical_classes' => array()
        );
        
        foreach ($critical_classes as $class) {
            if (!isset(self::$class_map[$class])) {
                $health['status'] = 'critical';
                $health['issues'][] = "Critical class {$class} not mapped";
                continue;
            }
            
            $file_path = QCC_PLUGIN_PATH . 'includes/' . self::$class_map[$class];
            if (!file_exists($file_path)) {
                $health['status'] = 'critical';
                $health['issues'][] = "Critical class file missing: {$file_path}";
                continue;
            }
            
            $health['critical_classes'][$class] = 'ok';
        }
        
        return $health;
    }
}

// Register autoloader immediately when file is loaded
QCC_Autoloader::register();
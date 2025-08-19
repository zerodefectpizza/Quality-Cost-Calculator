<?php
/**
 * QCC Autoloader - Erweitert für neue Architektur
 *
 * ERSETZEN: wp-content/plugins/quality-cost-calculator/includes/qcc-autoloader.php
 *
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Autoloader Class - Neue Architektur
 */
class QCC_Autoloader {
    
    /**
     * Autoloader registered flag
     * @var bool
     */
    private static $registered = false;
    
    /**
     * Class map for autoloading
     */
    private static $class_map = array(
        // =============================================================================
        // NEUE CORE ARCHITECTURE (Priorität)
        // =============================================================================
        'QCC_Bootstrap' => 'core/class-qcc-bootstrap.php',
        'QCC_Service_Container' => 'core/class-qcc-service-container.php',
        'QCC_Configuration' => 'core/class-qcc-configuration.php',
        'QCC_Plugin' => 'core/class-qcc-plugin.php',
        
        // =============================================================================
        // BESTEHENDE LEGACY CLASSES (funktionsfähig)
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
        // BUSINESS LOGIC (behalten)
        // =============================================================================
        'QCC_Calculation_Engine' => 'business/calculations/class-qcc-calculation-engine.php',
        'QCC_COPQ_Calculator' => 'business/calculations/class-qcc-copq-calculator.php',
        
        // =============================================================================
        // LEGACY FALLBACK SYSTEM
        // =============================================================================
        'QCC_Legacy_Bootstrap' => 'legacy/class-qcc-legacy-bootstrap.php',
        'QCC_Shortcode_Legacy' => 'legacy/class-qcc-shortcode-legacy.php',
        'QCC_Legacy_Calculator' => 'legacy/class-qcc-legacy-calculator.php',
        
        // =============================================================================
        // PRESENTATION LAYER
        // =============================================================================
        'QCC_Shortcode_Controller' => 'presentation/class-qcc-shortcode-controller.php',
        'QCC_Template_Router' => 'presentation/class-qcc-template-router.php',
        'QCC_Response_Builder' => 'presentation/class-qcc-response-builder.php',
        
        // =============================================================================
        // SERVICES LAYER (bei Bedarf)
        // =============================================================================
        'QCC_Cache_Service' => 'services/class-qcc-cache-service.php',
        'QCC_Export_Service' => 'services/class-qcc-export-service.php',
        'QCC_Import_Service' => 'services/class-qcc-import-service.php',
        'QCC_Settings_Service' => 'services/class-qcc-settings-service.php',
        
        // =============================================================================
        // ASSET MANAGEMENT
        // =============================================================================
        'QCC_Asset_Manager' => 'assets/class-qcc-asset-manager.php',
        'QCC_JavaScript_Generator' => 'assets/class-qcc-javascript-generator.php',
        'QCC_CSS_Generator' => 'assets/class-qcc-css-generator.php',
        
        // =============================================================================
        // INTERFACES
        // =============================================================================
        'QCC_Renderable' => 'interfaces/interface-qcc-renderable.php',
        'QCC_Calculable' => 'interfaces/interface-qcc-calculable.php',
        'QCC_Cacheable' => 'interfaces/interface-qcc-cacheable.php',
    );
    
    /**
     * Loaded classes tracking
     */
    private static $loaded_classes = array();
    
    /**
     * Failed loads tracking
     */
    private static $failed_loads = array();
    
    /**
     * Initialize autoloader
     */
    public static function init() {
        if (self::$registered) {
            return true;
        }
        
        if (!function_exists('spl_autoload_register')) {
            if (QCC_DEBUG) {
                error_log('QCC: spl_autoload_register not available');
            }
            return false;
        }
        
        // Register autoloader
        spl_autoload_register(array(__CLASS__, 'autoload'));
        self::$registered = true;
        
        if (QCC_DEBUG) {
            error_log('QCC: Autoloader initialized with ' . count(self::$class_map) . ' classes');
        }
        
        return true;
    }
    
    /**
     * Autoload classes
     */
    public static function autoload($class_name) {
        // Only load QCC classes
        if (strpos($class_name, 'QCC_') !== 0) {
            return false;
        }
        
        // Check if already loaded
        if (in_array($class_name, self::$loaded_classes)) {
            return true;
        }
        
        // Check if in class map
        if (!isset(self::$class_map[$class_name])) {
            self::$failed_loads[] = array(
                'class' => $class_name,
                'reason' => 'not_in_map',
                'time' => time()
            );
            
            if (QCC_DEBUG) {
                error_log("QCC: Class {$class_name} not found in autoloader map");
            }
            
            return false;
        }
        
        // Build file path
        $relative_path = self::$class_map[$class_name];
        $file_path = QCC_PLUGIN_PATH . 'includes/' . $relative_path;
        
        // Check if file exists
        if (!file_exists($file_path)) {
            self::$failed_loads[] = array(
                'class' => $class_name,
                'file' => $file_path,
                'reason' => 'file_not_found',
                'time' => time()
            );
            
            if (QCC_DEBUG) {
                error_log("QCC: Failed to load {$class_name} - file not found: {$file_path}");
            }
            
            return false;
        }
        
        // Load the file
        try {
            require_once $file_path;
            
            // Verify class was loaded
            if (class_exists($class_name) || interface_exists($class_name)) {
                self::$loaded_classes[] = $class_name;
                
                if (QCC_DEBUG) {
                    error_log("QCC: Successfully loaded {$class_name}");
                }
                
                return true;
            } else {
                self::$failed_loads[] = array(
                    'class' => $class_name,
                    'file' => $file_path,
                    'reason' => 'class_not_defined_after_include',
                    'time' => time()
                );
                
                if (QCC_DEBUG) {
                    error_log("QCC: File loaded but class {$class_name} not defined");
                }
                
                return false;
            }
            
        } catch (Exception $e) {
            self::$failed_loads[] = array(
                'class' => $class_name,
                'file' => $file_path,
                'reason' => 'exception: ' . $e->getMessage(),
                'time' => time()
            );
            
            if (QCC_DEBUG) {
                error_log("QCC: Exception loading {$class_name}: " . $e->getMessage());
            }
            
            return false;
        }
    }
    
    /**
     * Load core classes for bootstrap
     */
    public static function load_core_classes() {
        $core_classes = array(
            'QCC_Bootstrap',
            'QCC_Service_Container'
        );
        
        $loaded = 0;
        foreach ($core_classes as $class) {
            if (self::autoload($class)) {
                $loaded++;
            }
        }
        
        if (QCC_DEBUG) {
            error_log("QCC: Core classes loading: {$loaded}/" . count($core_classes) . " loaded");
        }
        
        return $loaded === count($core_classes);
    }
    
    /**
     * Get loaded classes
     */
    public static function get_loaded_classes() {
        return self::$loaded_classes;
    }
    
    /**
     * Get failed loads
     */
    public static function get_failed_loads() {
        return self::$failed_loads;
    }
    
    /**
     * Get class map
     */
    public static function get_class_map() {
        return self::$class_map;
    }
    
    /**
     * Validate class map (check if files exist)
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
        
        return array(
            'missing' => $missing_files,
            'found' => $found_files,
            'stats' => array(
                'total' => count(self::$class_map),
                'found' => count($found_files),
                'missing' => count($missing_files),
                'percentage' => count(self::$class_map) > 0 ? round((count($found_files) / count(self::$class_map)) * 100, 2) : 0
            )
        );
    }
    
    /**
     * Get autoloader statistics
     */
    public static function get_stats() {
        return array(
            'registered_classes' => count(self::$class_map),
            'loaded_classes' => count(self::$loaded_classes),
            'failed_loads' => count(self::$failed_loads),
            'load_percentage' => count(self::$class_map) > 0 ? 
                round((count(self::$loaded_classes) / count(self::$class_map)) * 100, 2) : 0,
            'recent_failures' => array_slice(self::$failed_loads, -5), // Last 5 failures
            'loaded_list' => self::$loaded_classes,
            'autoloader_registered' => self::$registered
        );
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
    
    /**
     * Debug output for admin
     */
    public static function debug_output() {
        if (!QCC_DEBUG || !current_user_can('manage_options')) {
            return;
        }
        
        $stats = self::get_stats();
        $health = self::health_check();
        
        echo '<div style="background: #f0f0f0; padding: 15px; margin: 10px; border: 1px solid #ccc; font-family: monospace;">';
        echo '<h3>🔧 QCC Autoloader Debug Information</h3>';
        
        echo '<p><strong>📊 Statistics:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Total Classes:</strong> ' . $stats['registered_classes'] . '</li>';
        echo '<li><strong>Loaded Classes:</strong> ' . $stats['loaded_classes'] . '</li>';
        echo '<li><strong>Failed Loads:</strong> ' . $stats['failed_loads'] . '</li>';
        echo '<li><strong>Load Percentage:</strong> ' . $stats['load_percentage'] . '%</li>';
        echo '<li><strong>Autoloader Registered:</strong> ' . ($stats['autoloader_registered'] ? 'Yes' : 'No') . '</li>';
        echo '</ul>';
        
        echo '<p><strong>🏥 Health Status:</strong> ' . strtoupper($health['status']) . '</p>';
        if (!empty($health['issues'])) {
            echo '<p><strong>Issues:</strong></p>';
            echo '<ul>';
            foreach ($health['issues'] as $issue) {
                echo '<li style="color: red;">' . esc_html($issue) . '</li>';
            }
            echo '</ul>';
        }
        
        if (!empty($stats['loaded_list'])) {
            echo '<p><strong>✅ Loaded Classes:</strong></p>';
            echo '<ul>';
            foreach ($stats['loaded_list'] as $class) {
                echo '<li style="color: green;">' . esc_html($class) . '</li>';
            }
            echo '</ul>';
        }
        
        if (!empty($stats['recent_failures'])) {
            echo '<p><strong>❌ Recent Failures:</strong></p>';
            echo '<ul>';
            foreach ($stats['recent_failures'] as $failure) {
                echo '<li style="color: red;">' . esc_html($failure['class']) . ' - ' . esc_html($failure['reason']) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
}
<?php
/**
 * QCC Autoloader - Erweitert für neue Shortcode Services Architektur
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
 * QCC Autoloader Class - Neue Architektur mit Shortcode Services
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
        'QCC_Service_Container_Setup' => 'core/class-qcc-service-container-setup.php',
        'QCC_Configuration' => 'core/class-qcc-configuration.php',
        'QCC_Plugin' => 'core/class-qcc-plugin.php',
        
        // =============================================================================
        // SHORTCODE SERVICES (Neu - Entschlackte Architektur)
        // =============================================================================
        'QCC_Shortcode' => 'class-qcc-shortcode.php',                           // Ersetzt bestehende
        'QCC_Shortcode_Renderer' => 'services/class-qcc-shortcode-renderer.php', // NEU
        'QCC_Fallback_Renderer' => 'services/class-qcc-fallback-renderer.php',  // NEU
        
        // =============================================================================
        // BESTEHENDE LEGACY CLASSES (funktionsfähig)
        // =============================================================================
        'QCC_Core' => 'class-qcc-core.php',
        'QCC_Admin' => 'class-qcc-admin.php', 
        'QCC_Ajax' => 'class-qcc-ajax.php',
        'QCC_I18n' => 'class-qcc-i18n.php',
        'QCC_Config' => 'qcc-config.php',
        'QCC_Cache' => 'class-qcc-cache.php',
        'QCC_Validator' => 'class-qcc-validator.php',
        'QCC_Translator' => 'class-qcc-translator.php',
        
        // =============================================================================
        // BUSINESS LOGIC (behalten)
        // =============================================================================
        'QCC_Calculation_Engine' => 'business/calculations/class-qcc-calculation-engine.php',
        'QCC_COPQ_Calculator' => 'business/calculations/class-qcc-copq-calculator.php',
        
        // =============================================================================
        // TEMPLATE & RENDERING SYSTEM
        // =============================================================================
        'QCC_Template_Router' => 'presentation/class-qcc-template-router.php',
        'QCC_Template_Manager' => 'rendering/core/class-qcc-template-manager.php',
        'QCC_Layout_Builder' => 'rendering/builders/class-qcc-layout-builder.php',
        'QCC_Component_Registry' => 'rendering/orchestration/class-qcc-component-registry.php',
        'QCC_Component_Factory' => 'rendering/core/class-qcc-component-factory.php',
        
        // =============================================================================
        // PRESENTATION LAYER
        // =============================================================================
        'QCC_Shortcode_Controller' => 'presentation/class-qcc-shortcode-controller.php',
        'QCC_Response_Builder' => 'presentation/class-qcc-response-builder.php',
        'QCC_Shortcode_Integration' => 'integration/class-qcc-shortcode-integration.php',
        
        // =============================================================================
        // SERVICES LAYER (erweitert)
        // =============================================================================
        'QCC_Cache_Service' => 'services/class-qcc-cache-service.php',
        'QCC_Export_Service' => 'services/class-qcc-export-service.php',
        'QCC_Import_Service' => 'services/class-qcc-import-service.php',
        'QCC_Settings_Service' => 'services/class-qcc-settings-service.php',
        'QCC_Translation_Service' => 'services/class-qcc-translation-service.php',
        'QCC_Asset_Service' => 'services/class-qcc-asset-service.php',
        
        // =============================================================================
        // ASSET MANAGEMENT
        // =============================================================================
        'QCC_Asset_Manager' => 'assets/class-qcc-asset-manager.php',
        'QCC_JavaScript_Generator' => 'assets/class-qcc-javascript-generator.php',
        'QCC_CSS_Generator' => 'assets/class-qcc-css-generator.php',
        'QCC_Dependency_Manager' => 'assets/class-qcc-dependency-manager.php',
        
        // =============================================================================
        // TRANSLATION SYSTEM
        // =============================================================================
        'QCC_Translator' => 'translation/class-qcc-translator.php',
        'QCC_English_Translations' => 'translation/languages/class-qcc-english-translations.php',
        'QCC_German_Translations' => 'translation/languages/class-qcc-german-translations.php',
        'QCC_French_Translations' => 'translation/languages/class-qcc-french-translations.php',
        'QCC_Spanish_Translations' => 'translation/languages/class-qcc-spanish-translations.php',
        'QCC_Chinese_Translations' => 'translation/languages/class-qcc-chinese-translations.php',
        
        // =============================================================================
        // INFRASTRUCTURE & FEATURE FLAGS
        // =============================================================================
        'QCC_Feature_Flags' => 'infrastructure/class-qcc-feature-flags.php',
        'QCC_Service_Registry' => 'infrastructure/class-qcc-service-registry.php',
        'QCC_Lazy_Loader' => 'infrastructure/class-qcc-lazy-loader.php',
        'QCC_Instance_Cache' => 'infrastructure/class-qcc-instance-cache.php',
        'QCC_Event_Manager' => 'infrastructure/class-qcc-event-manager.php',
        
        // =============================================================================
        // LEGACY FALLBACK SYSTEM
        // =============================================================================
        'QCC_Legacy_Bootstrap' => 'legacy/class-qcc-legacy-bootstrap.php',
        'QCC_Shortcode_Legacy' => 'legacy/class-qcc-shortcode-legacy.php',
        'QCC_Legacy_Calculator' => 'legacy/class-qcc-legacy-calculator.php',
        'QCC_Backward_Compatibility' => 'legacy/class-qcc-backward-compatibility.php',
        
        // =============================================================================
        // INTERFACES
        // =============================================================================
        'QCC_Renderable' => 'interfaces/interface-qcc-renderable.php',
        'QCC_Calculable' => 'interfaces/interface-qcc-calculable.php',
        'QCC_Cacheable' => 'interfaces/interface-qcc-cacheable.php',
        'QCC_Translatable' => 'interfaces/interface-qcc-translatable.php',
        'QCC_Service' => 'infrastructure/interfaces/interface-qcc-service.php',
        'QCC_Molecule' => 'infrastructure/interfaces/interface-qcc-molecule.php',
        
        // =============================================================================
        // MONITORING & DEBUG
        // =============================================================================
        'QCC_Performance_Monitor' => 'monitoring/class-qcc-performance-monitor.php',
        'QCC_Error_Tracker' => 'monitoring/class-qcc-error-tracker.php',
        'QCC_Debug_Logger' => 'monitoring/class-qcc-debug-logger.php',
    );
    
    /**
     * Service priority loading order
     */
    private static $service_priority = array(
        'critical' => array(
            'QCC_Bootstrap',
            'QCC_Service_Container',
            'QCC_Service_Container_Setup'
        ),
        'core' => array(
            'QCC_Configuration',
            'QCC_Feature_Flags',
            'QCC_Translator'
        ),
        'shortcode' => array(
            'QCC_Shortcode',
            'QCC_Shortcode_Renderer',
            'QCC_Fallback_Renderer'
        ),
        'rendering' => array(
            'QCC_Template_Router',
            'QCC_Layout_Builder', 
            'QCC_Component_Registry'
        ),
        'services' => array(
            'QCC_Asset_Manager',
            'QCC_Cache_Service',
            'QCC_Translation_Service'
        )
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
     * Loading performance stats
     */
    private static $loading_stats = array();
    
    /**
     * Initialize autoloader
     */
    public static function init() {
        if (self::$registered) {
            return true;
        }
        
        if (!function_exists('spl_autoload_register')) {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC: spl_autoload_register not available');
            }
            return false;
        }
        
        // Register autoloader
        spl_autoload_register(array(__CLASS__, 'autoload'));
        self::$registered = true;
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log('QCC: Autoloader initialized with ' . count(self::$class_map) . ' classes');
        }
        
        return true;
    }
    
    /**
     * Autoload classes with performance tracking
     */
    public static function autoload($class_name) {
        $start_time = microtime(true);
        
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
            self::record_failed_load($class_name, 'not_in_map');
            return false;
        }
        
        // Build file path
        $relative_path = self::$class_map[$class_name];
        $file_path = QCC_PLUGIN_PATH . 'includes/' . $relative_path;
        
        // Check if file exists
        if (!file_exists($file_path)) {
            self::record_failed_load($class_name, 'file_not_found', $file_path);
            return false;
        }
        
        // Load the file
        try {
            require_once $file_path;
            
            // Verify class was loaded
            if (class_exists($class_name) || interface_exists($class_name)) {
                self::$loaded_classes[] = $class_name;
                
                // Record loading stats
                $execution_time = microtime(true) - $start_time;
                self::$loading_stats[$class_name] = array(
                    'file' => $relative_path,
                    'execution_time' => $execution_time,
                    'memory_before' => memory_get_usage(true),
                    'memory_after' => memory_get_usage(true),
                    'loaded_at' => time()
                );
                
                if (defined('QCC_DEBUG') && QCC_DEBUG) {
                    error_log(sprintf('QCC: Successfully loaded %s (%.3fms)', $class_name, $execution_time * 1000));
                }
                
                return true;
            } else {
                self::record_failed_load($class_name, 'class_not_defined_after_include', $file_path);
                return false;
            }
            
        } catch (Exception $e) {
            self::record_failed_load($class_name, 'exception: ' . $e->getMessage(), $file_path);
            return false;
        }
    }
    
    /**
     * Record failed load attempt
     */
    private static function record_failed_load($class_name, $reason, $file_path = null) {
        $failure = array(
            'class' => $class_name,
            'reason' => $reason,
            'time' => time(),
            'memory_usage' => memory_get_usage(true)
        );
        
        if ($file_path) {
            $failure['file'] = $file_path;
        }
        
        self::$failed_loads[] = $failure;
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            $message = "QCC: Failed to load {$class_name} - {$reason}";
            if ($file_path) {
                $message .= " (File: {$file_path})";
            }
            error_log($message);
        }
    }
    
    /**
     * Load classes by priority group
     */
    public static function load_priority_group($group) {
        if (!isset(self::$service_priority[$group])) {
            return false;
        }
        
        $loaded = 0;
        $total = count(self::$service_priority[$group]);
        
        foreach (self::$service_priority[$group] as $class) {
            if (self::autoload($class)) {
                $loaded++;
            }
        }
        
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log("QCC: Priority group '{$group}' loading: {$loaded}/{$total} loaded");
        }
        
        return array(
            'group' => $group,
            'loaded' => $loaded,
            'total' => $total,
            'success_rate' => $total > 0 ? round(($loaded / $total) * 100, 2) : 0
        );
    }
    
    /**
     * Load core classes for bootstrap
     */
    public static function load_core_classes() {
        return self::load_priority_group('critical');
    }
    
    /**
     * Load shortcode services
     */
    public static function load_shortcode_services() {
        return self::load_priority_group('shortcode');
    }
    
    /**
     * Warmup essential services
     */
    public static function warmup_services() {
        $warmup_groups = array('critical', 'core', 'shortcode');
        $results = array();
        
        foreach ($warmup_groups as $group) {
            $results[$group] = self::load_priority_group($group);
        }
        
        return $results;
    }
    
    /**
     * Check if class is available for loading
     */
    public static function is_class_available($class_name) {
        if (!isset(self::$class_map[$class_name])) {
            return false;
        }
        
        $file_path = QCC_PLUGIN_PATH . 'includes/' . self::$class_map[$class_name];
        return file_exists($file_path);
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
     * Get loading statistics
     */
    public static function get_loading_stats() {
        return self::$loading_stats;
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
        $validation_start = microtime(true);
        
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
                    'path' => $file_path,
                    'size' => filesize($file_path),
                    'modified' => filemtime($file_path)
                );
            }
        }
        
        $validation_time = microtime(true) - $validation_start;
        
        return array(
            'missing' => $missing_files,
            'found' => $found_files,
            'stats' => array(
                'total' => count(self::$class_map),
                'found' => count($found_files),
                'missing' => count($missing_files),
                'percentage' => count(self::$class_map) > 0 ? round((count($found_files) / count(self::$class_map)) * 100, 2) : 0,
                'validation_time' => $validation_time
            )
        );
    }
    
    /**
     * Get autoloader statistics
     */
    public static function get_stats() {
        $total_loading_time = 0;
        $slowest_class = null;
        $fastest_class = null;
        
        foreach (self::$loading_stats as $class => $stats) {
            $total_loading_time += $stats['execution_time'];
            
            if (!$slowest_class || $stats['execution_time'] > self::$loading_stats[$slowest_class]['execution_time']) {
                $slowest_class = $class;
            }
            
            if (!$fastest_class || $stats['execution_time'] < self::$loading_stats[$fastest_class]['execution_time']) {
                $fastest_class = $class;
            }
        }
        
        return array(
            'registered_classes' => count(self::$class_map),
            'loaded_classes' => count(self::$loaded_classes),
            'failed_loads' => count(self::$failed_loads),
            'load_percentage' => count(self::$class_map) > 0 ? 
                round((count(self::$loaded_classes) / count(self::$class_map)) * 100, 2) : 0,
            'performance' => array(
                'total_loading_time' => $total_loading_time,
                'average_loading_time' => count(self::$loading_stats) > 0 ? $total_loading_time / count(self::$loading_stats) : 0,
                'slowest_class' => $slowest_class,
                'fastest_class' => $fastest_class
            ),
            'recent_failures' => array_slice(self::$failed_loads, -5),
            'loaded_list' => self::$loaded_classes,
            'autoloader_registered' => self::$registered,
            'service_priorities' => self::$service_priority
        );
    }
    
    /**
     * Health check - verify critical classes can be loaded
     */
    public static function health_check() {
        $critical_classes = self::$service_priority['critical'];
        $shortcode_classes = self::$service_priority['shortcode'];
        
        $health = array(
            'status' => 'healthy',
            'issues' => array(),
            'critical_classes' => array(),
            'shortcode_services' => array(),
            'recommendations' => array()
        );
        
        // Check critical classes
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
        
        // Check shortcode services
        foreach ($shortcode_classes as $class) {
            if (!isset(self::$class_map[$class])) {
                $health['status'] = ($health['status'] === 'critical') ? 'critical' : 'warning';
                $health['issues'][] = "Shortcode service {$class} not mapped";
                continue;
            }
            
            $file_path = QCC_PLUGIN_PATH . 'includes/' . self::$class_map[$class];
            if (!file_exists($file_path)) {
                $health['status'] = ($health['status'] === 'critical') ? 'critical' : 'warning';
                $health['issues'][] = "Shortcode service file missing: {$file_path}";
                continue;
            }
            
            $health['shortcode_services'][$class] = 'ok';
        }
        
        // Performance recommendations
        if (count(self::$failed_loads) > 0) {
            $health['recommendations'][] = "Consider investigating " . count(self::$failed_loads) . " failed class loads";
        }
        
        if (count(self::$loading_stats) > 0) {
            $avg_time = array_sum(array_column(self::$loading_stats, 'execution_time')) / count(self::$loading_stats);
            if ($avg_time > 0.01) { // 10ms threshold
                $health['recommendations'][] = "Class loading performance could be optimized (avg: " . round($avg_time * 1000, 2) . "ms)";
            }
        }
        
        return $health;
    }
    
    /**
     * Get shortcode services status
     */
    public static function get_shortcode_services_status() {
        $services = array(
            'QCC_Shortcode' => 'Main Shortcode Handler',
            'QCC_Shortcode_Renderer' => 'Modern Rendering Service',
            'QCC_Fallback_Renderer' => 'Fallback Rendering Service'
        );
        
        $status = array();
        
        foreach ($services as $class => $description) {
            $status[$class] = array(
                'description' => $description,
                'mapped' => isset(self::$class_map[$class]),
                'file_exists' => false,
                'loaded' => in_array($class, self::$loaded_classes),
                'load_time' => null
            );
            
            if ($status[$class]['mapped']) {
                $file_path = QCC_PLUGIN_PATH . 'includes/' . self::$class_map[$class];
                $status[$class]['file_exists'] = file_exists($file_path);
                $status[$class]['file_path'] = $file_path;
            }
            
            if (isset(self::$loading_stats[$class])) {
                $status[$class]['load_time'] = self::$loading_stats[$class]['execution_time'];
            }
        }
        
        return $status;
    }
    
    /**
     * Debug output for admin
     */
    public static function debug_output() {
        if (!defined('QCC_DEBUG') || !QCC_DEBUG || !current_user_can('manage_options')) {
            return;
        }
        
        $stats = self::get_stats();
        $health = self::health_check();
        $shortcode_status = self::get_shortcode_services_status();
        
        echo '<div style="background: #f0f0f0; padding: 15px; margin: 10px; border: 1px solid #ccc; font-family: monospace;">';
        echo '<h3>🔧 QCC Autoloader Debug Information</h3>';
        
        echo '<p><strong>📊 Statistics:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Total Classes:</strong> ' . $stats['registered_classes'] . '</li>';
        echo '<li><strong>Loaded Classes:</strong> ' . $stats['loaded_classes'] . '</li>';
        echo '<li><strong>Failed Loads:</strong> ' . $stats['failed_loads'] . '</li>';
        echo '<li><strong>Load Percentage:</strong> ' . $stats['load_percentage'] . '%</li>';
        echo '<li><strong>Autoloader Registered:</strong> ' . ($stats['autoloader_registered'] ? 'Yes' : 'No') . '</li>';
        echo '<li><strong>Total Loading Time:</strong> ' . round($stats['performance']['total_loading_time'] * 1000, 2) . 'ms</li>';
        echo '</ul>';
        
        echo '<p><strong>🚀 Shortcode Services Status:</strong></p>';
        echo '<table style="width: 100%; border-collapse: collapse; margin: 10px 0;">';
        echo '<tr style="background: #ddd;"><th style="border: 1px solid #999; padding: 5px;">Service</th><th style="border: 1px solid #999; padding: 5px;">Status</th><th style="border: 1px solid #999; padding: 5px;">Load Time</th></tr>';
        foreach ($shortcode_status as $class => $info) {
            $status_color = $info['loaded'] ? 'green' : ($info['file_exists'] ? 'orange' : 'red');
            $status_text = $info['loaded'] ? '✅ Loaded' : ($info['file_exists'] ? '⏳ Available' : '❌ Missing');
            $load_time = $info['load_time'] ? round($info['load_time'] * 1000, 2) . 'ms' : '--';
            
            echo '<tr>';
            echo '<td style="border: 1px solid #999; padding: 5px;">' . esc_html($class) . '<br><small>' . esc_html($info['description']) . '</small></td>';
            echo '<td style="border: 1px solid #999; padding: 5px; color: ' . $status_color . ';">' . $status_text . '</td>';
            echo '<td style="border: 1px solid #999; padding: 5px;">' . $load_time . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        echo '<p><strong>🏥 Health Status:</strong> ' . strtoupper($health['status']) . '</p>';
        if (!empty($health['issues'])) {
            echo '<p><strong>Issues:</strong></p>';
            echo '<ul>';
            foreach ($health['issues'] as $issue) {
                echo '<li style="color: red;">' . esc_html($issue) . '</li>';
            }
            echo '</ul>';
        }
        
        if (!empty($health['recommendations'])) {
            echo '<p><strong>💡 Recommendations:</strong></p>';
            echo '<ul>';
            foreach ($health['recommendations'] as $recommendation) {
                echo '<li style="color: blue;">' . esc_html($recommendation) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
}
<?php
/**
 * QCC Service Container Setup
 * 
 * Initializes and configures the service container with all
 * rendering components and their dependencies.
 *
 * @package QualityCostCalculator
 * @subpackage Integration
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class QCC_Service_Container_Setup {
    
    private static $initialized = false;
    private static $container;
    
    /**
     * Initialize the service container with all components
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        try {
            self::$container = QCC_Service_Container::get_instance();
            self::register_core_services();
            self::register_rendering_components();
            self::register_integration_services();
            self::verify_dependencies();
            
            self::$initialized = true;
            
        } catch (Exception $e) {
            error_log('QCC Service Container Setup Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Register core services
     */
    private static function register_core_services() {
        // Translation Service
        self::$container->register('translator', function() {
            return new QCC_Translation_Service();
        });
        
        // Template Router
        self::$container->register('template_router', function() {
            return new QCC_Template_Router();
        });
        
        // Asset Manager (if available)
        if (class_exists('QCC_Asset_Manager')) {
            self::$container->register('asset_manager', function() {
                return new QCC_Asset_Manager();
            });
        } else {
            // Fallback asset manager
            self::$container->register('asset_manager', function() {
                return new QCC_Asset_Manager_Fallback();
            });
        }
        
        // Cache Manager (if available)
        if (class_exists('QCC_Cache_Manager')) {
            self::$container->register('cache_manager', function() {
                return new QCC_Cache_Manager();
            });
        }
        
        // Configuration Service
        self::$container->register('configuration', function() {
            return new QCC_Configuration();
        });
    }
    
    /**
     * Register rendering components
     */
    private static function register_rendering_components() {
        // Component Registry
        self::$container->register('component_registry', function() {
            return QCC_Component_Registry::get_instance();
        });
        
        // Layout Coordinator
        self::$container->register('layout_coordinator', function() {
            return new QCC_Layout_Coordinator();
        });
        
        // HTML Orchestrator
        self::$container->register('html_orchestrator', function() {
            return new QCC_HTML_Orchestrator();
        });
        
        // Builders
        self::$container->register('form_builder', function() {
            return new QCC_Form_Builder();
        });
        
        self::$container->register('display_builder', function() {
            return new QCC_Display_Builder();
        });
        
        self::$container->register('control_builder', function() {
            return new QCC_Control_Builder();
        });
        
        self::$container->register('layout_builder', function() {
            return new QCC_Layout_Builder();
        });
        
        // Factories
        self::$container->register('input_factory', function() {
            return new QCC_Input_Factory();
        });
        
        self::$container->register('display_factory', function() {
            return new QCC_Display_Factory();
        });
        
        // Atomic Components
        self::register_atomic_components();
        
        // Molecule Components
        self::register_molecule_components();
    }
    
    /**
     * Register atomic components
     */
    private static function register_atomic_components() {
        $component_registry = self::$container->get('component_registry');
        
        // Input Atoms
        $component_registry->register_atom('percentage_input', 'QCC_Percentage_Input', array('translator'));
        $component_registry->register_atom('currency_input', 'QCC_Currency_Input', array('translator'));
        $component_registry->register_atom('select_input', 'QCC_Select_Input', array('translator'));
        $component_registry->register_atom('text_input', 'QCC_Text_Input', array('translator'));
        
        // Display Atoms
        $component_registry->register_atom('result_card', 'QCC_Result_Card', array('translator'));
        $component_registry->register_atom('chart_container', 'QCC_Chart_Container', array('asset_manager'));
        $component_registry->register_atom('status_display', 'QCC_Status_Display', array('translator'));
        $component_registry->register_atom('button', 'QCC_Button', array('translator'));
    }
    
    /**
     * Register molecule components
     */
    private static function register_molecule_components() {
        $component_registry = self::$container->get('component_registry');
        
        // Molecule Components
        $component_registry->register_molecule('chart_section', 'QCC_Chart_Section', array('display_factory', 'translator'));
        $component_registry->register_molecule('control_panel', 'QCC_Control_Panel', array('display_factory', 'control_builder', 'translator'));
        
        // Additional molecules that might exist
        if (class_exists('QCC_Input_Group')) {
            $component_registry->register_molecule('input_group', 'QCC_Input_Group', array('input_factory', 'translator'));
        }
        
        if (class_exists('QCC_Result_Section')) {
            $component_registry->register_molecule('result_section', 'QCC_Result_Section', array('display_factory', 'translator'));
        }
    }
    
    /**
     * Register integration services
     */
    private static function register_integration_services() {
        // Shortcode Integration Bridge
        self::$container->register('shortcode_integration', function() {
            return new QCC_Shortcode_Integration();
        });
        
        // Backward Compatibility Handler
        if (class_exists('QCC_Backward_Compatibility')) {
            self::$container->register('backward_compatibility', function() {
                return new QCC_Backward_Compatibility();
            });
        }
        
        // Performance Monitor (if available)
        if (class_exists('QCC_Performance_Monitor')) {
            self::$container->register('performance_monitor', function() {
                return new QCC_Performance_Monitor();
            });
        }
    }
    
    /**
     * Verify all dependencies are satisfied
     */
    private static function verify_dependencies() {
        $component_registry = self::$container->get('component_registry');
        $validation_results = $component_registry->validate_all_components();
        
        $failed_components = array();
        foreach ($validation_results as $component => $result) {
            if (!$result['valid']) {
                $failed_components[] = $component . ': ' . $result['error'];
            }
        }
        
        if (!empty($failed_components)) {
            $error_message = 'Component validation failed: ' . implode(', ', $failed_components);
            error_log('QCC Service Container: ' . $error_message);
            
            if (WP_DEBUG) {
                throw new Exception($error_message);
            }
        }
    }
    
    /**
     * Get service container instance
     */
    public static function get_container() {
        if (!self::$initialized) {
            self::init();
        }
        
        return self::$container;
    }
    
    /**
     * Check if container is initialized
     */
    public static function is_initialized() {
        return self::$initialized;
    }
    
    /**
     * Get initialization status and statistics
     */
    public static function get_status() {
        if (!self::$initialized) {
            return array(
                'initialized' => false,
                'message' => 'Service container not initialized'
            );
        }
        
        $component_registry = self::$container->get('component_registry');
        $stats = $component_registry->get_statistics();
        
        return array(
            'initialized' => true,
            'total_services' => count(self::get_all_service_names()),
            'component_stats' => $stats,
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    /**
     * Get all registered service names
     */
    private static function get_all_service_names() {
        $services = array(
            // Core services
            'translator', 'template_router', 'asset_manager', 'configuration',
            
            // Rendering components
            'component_registry', 'layout_coordinator', 'html_orchestrator',
            
            // Builders
            'form_builder', 'display_builder', 'control_builder', 'layout_builder',
            
            // Factories
            'input_factory', 'display_factory',
            
            // Integration
            'shortcode_integration'
        );
        
        // Add optional services if they exist
        $optional_services = array('cache_manager', 'backward_compatibility', 'performance_monitor');
        foreach ($optional_services as $service) {
            try {
                if (self::$container && self::$container->has($service)) {
                    $services[] = $service;
                }
            } catch (Exception $e) {
                // Service not available
            }
        }
        
        return $services;
    }
    
    /**
     * Reset container (for testing)
     */
    public static function reset() {
        self::$initialized = false;
        self::$container = null;
    }
    
    /**
     * Warmup container by pre-loading critical services
     */
    public static function warmup() {
        if (!self::$initialized) {
            self::init();
        }
        
        // Pre-load critical services to improve performance
        $critical_services = array(
            'translator',
            'component_registry', 
            'html_orchestrator'
        );
        
        foreach ($critical_services as $service) {
            try {
                self::$container->get($service);
            } catch (Exception $e) {
                error_log("QCC Warmup failed for service: {$service} - " . $e->getMessage());
            }
        }
    }
    
    /**
     * Debug method to list all available services
     */
    public static function debug_services() {
        if (!WP_DEBUG || !self::$initialized) {
            return;
        }
        
        echo '<pre>QCC Service Container Debug:' . PHP_EOL;
        echo 'Initialized: ' . (self::$initialized ? 'Yes' : 'No') . PHP_EOL;
        echo 'Available Services: ' . PHP_EOL;
        
        foreach (self::get_all_service_names() as $service) {
            try {
                $instance = self::$container->get($service);
                $status = $instance ? 'Available' : 'Failed';
                $type = $instance ? get_class($instance) : 'Unknown';
                echo "  - {$service}: {$status} ({$type})" . PHP_EOL;
            } catch (Exception $e) {
                echo "  - {$service}: Error - " . $e->getMessage() . PHP_EOL;
            }
        }
        
        if (method_exists(self::$container, 'get_statistics')) {
            $stats = self::$container->get_statistics();
            echo 'Container Statistics: ' . print_r($stats, true) . PHP_EOL;
        }
        
        echo '</pre>';
    }
}

/**
 * Fallback Asset Manager for when the main one isn't available
 */
class QCC_Asset_Manager_Fallback {
    
    public function generate_inline_css($css_files, $config) {
        return '<style>/* CSS fallback - implement basic styles */</style>';
    }
    
    public function generate_css_links($css_files, $config) {
        return '<!-- CSS links fallback -->';
    }
    
    public function generate_inline_js($js_files, $js_config, $config) {
        return '<script>/* JS fallback */</script>';
    }
    
    public function generate_js_scripts($js_files, $js_config, $config) {
        return '<!-- JS scripts fallback -->';
    }
}
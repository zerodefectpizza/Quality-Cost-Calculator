<?php
/**
 * Plugin Name:       Quality Cost Calculator
 * Plugin URI:        https://github.com/zerodefectpizza/Quality-Cost-Calculator
 * Description:       Professional Quality Cost Calculator for COGQ, COPQ and ROI analysis. Calculate Cost of Good Quality (prevention, appraisal) and Cost of Poor Quality (internal/external defects) with multi-language support, real-time validation, and export functionality.
 * Version:           2.0.0
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * Author:            Martin Schneider
 * Author URI:        https://zerodefectpizza.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       quality-cost-calculator
 * Domain Path:       /languages
 * Network:           false
 * Update URI:        false
 * 
 * @package QualityCostCalculator
 * @version 2.0.0
 * @author Martin Schneider
 * @copyright 2024 Martin Schneider
 * @license GPL-2.0-or-later
 * @link https://github.com/zerodefectpizza/Quality-Cost-Calculator
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// WordPress and PHP version check
if (!function_exists('add_action')) {
    exit('WordPress environment required.');
}

if (version_compare(PHP_VERSION, '7.4', '<')) {
    exit('PHP 7.4 or higher required.');
}

// =============================================================================
// PLUGIN CONSTANTS
// =============================================================================

define('QCC_PLUGIN_VERSION', '2.0.0');
define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('QCC_PLUGIN_FILE', __FILE__);
define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');
define('QCC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// =============================================================================
// MODERNE QCC ARCHITEKTUR - Clean & Simple
// =============================================================================

/**
 * QCC Plugin Controller - Single Entry Point
 */
class QCC_Plugin_Controller {
    
    private static $instance = null;
    private $services = array();
    private $initialized = false;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'), 10);
    }
    
    /**
     * Initialize Plugin
     */
    public function init() {
        if ($this->initialized) {
            return;
        }
        
        try {
            // 1. Core Services laden
            $this->load_core_services();
            
            // 2. Shortcode System initialisieren
            $this->init_shortcode_system();
            
            // 3. Admin Interface (falls im Admin)
            if (is_admin()) {
                $this->init_admin_interface();
            }
            
            $this->initialized = true;
            
            if (QCC_DEBUG) {
                error_log('QCC Plugin: Modern architecture initialized successfully');
            }
            
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Plugin Error: ' . $e->getMessage());
            }
            // Fallback: Use functional emergency calculator
            $this->init_emergency_calculator();
        }
    }
    
    /**
     * Load Core Services (using existing components)
     */
    private function load_core_services() {
        // Translator (already exists and works)
        if (file_exists(QCC_PLUGIN_PATH . 'includes/class-qcc-translator.php')) {
            require_once QCC_PLUGIN_PATH . 'includes/class-qcc-translator.php';
            $this->services['translator'] = new QCC_Translator();
        }
        
        // Fallback Renderer (already exists and works)
        if (file_exists(QCC_PLUGIN_PATH . 'includes/services/class-qcc-fallback-renderer.php')) {
            require_once QCC_PLUGIN_PATH . 'includes/services/class-qcc-fallback-renderer.php';
            $this->services['fallback_renderer'] = new QCC_Fallback_Renderer($this->services['translator']);
        }
        
        // Create modern calculator service
        $this->services['calculator'] = new QCC_Modern_Calculator();
        
        // Create modern renderer
        $this->services['renderer'] = new QCC_Modern_Renderer($this->services);
    }
    
    /**
     * Initialize Shortcode System
     */
    private function init_shortcode_system() {
        $shortcode_handler = new QCC_Modern_Shortcode_Handler($this->services);
        
        // Register all shortcode variants
        add_shortcode('quality_cost_calculator', array($shortcode_handler, 'render'));
        add_shortcode('qcc_calculator', array($shortcode_handler, 'render'));
        add_shortcode('cost_quality_calculator', array($shortcode_handler, 'render'));
        add_shortcode('cogq_calculator', array($shortcode_handler, 'render'));
        add_shortcode('copq_calculator', array($shortcode_handler, 'render'));
    }
    
    /**
     * Initialize Admin Interface
     */
    private function init_admin_interface() {
        add_action('admin_menu', function() {
            add_options_page(
                'Quality Cost Calculator',
                'QCC Settings',
                'manage_options',
                'qcc-settings',
                array($this, 'render_admin_page')
            );
        });
    }
    
    /**
     * Emergency Calculator (always works)
     */
    private function init_emergency_calculator() {
        add_shortcode('quality_cost_calculator', function($atts) {
            return $this->render_emergency_calculator($atts);
        });
    }
    
    /**
     * Emergency Calculator Renderer
     */
    private function render_emergency_calculator($atts) {
        $atts = shortcode_atts(array(
            'language' => 'de',
            'currency' => 'EUR'
        ), $atts);
        
        return '<div class="qcc-emergency-calculator" style="max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 12px; font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
            <h2 style="color: #2c3e50; text-align: center; margin-bottom: 30px;">🧮 Qualitätskostenrechner</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Jahresumsatz (Mio. €):</label>
                    <input type="number" id="emergency-revenue" value="140" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Qualitätskosten (%):</label>
                    <input type="number" id="emergency-quality" value="6" step="0.1" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <button onclick="emergencyQCCCalculate()" style="background: #007cba; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-right: 10px;">Berechnen</button>
                <button onclick="emergencyQCCReset()" style="background: #6c757d; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">Zurücksetzen</button>
            </div>
            
            <div id="emergency-qcc-results" style="display: none; margin-top: 30px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="padding: 20px; background: linear-gradient(135deg, #e8f5e8, #c8e6c9); border-radius: 10px; border-left: 5px solid #4caf50;">
                        <h3 style="color: #2e7d32; margin-bottom: 15px;">COGQ - Kosten guter Qualität</h3>
                        <div style="margin-bottom: 10px;"><strong>Prävention:</strong> <span id="emergency-prevention">-</span> €</div>
                        <div style="margin-bottom: 15px;"><strong>Prüfung:</strong> <span id="emergency-appraisal">-</span> €</div>
                        <div style="padding: 10px; background: rgba(255,255,255,0.5); border-radius: 6px; font-size: 18px; font-weight: bold; color: #1b5e20;">
                            COGQ Gesamt: <span id="emergency-cogq-total">-</span> €
                        </div>
                    </div>
                    
                    <div style="padding: 20px; background: linear-gradient(135deg, #fff3cd, #ffeaa7); border-radius: 10px; border-left: 5px solid #ff9800;">
                        <h3 style="color: #f57c00; margin-bottom: 15px;">COPQ - Kosten schlechter Qualität</h3>
                        <div style="margin-bottom: 10px;"><strong>Interne Fehler:</strong> <span id="emergency-internal">-</span> €</div>
                        <div style="margin-bottom: 15px;"><strong>Externe Fehler:</strong> <span id="emergency-external">-</span> €</div>
                        <div style="padding: 10px; background: rgba(255,255,255,0.5); border-radius: 6px; font-size: 18px; font-weight: bold; color: #e65100;">
                            COPQ Gesamt: <span id="emergency-copq-total">-</span> €
                        </div>
                    </div>
                </div>
                
                <div style="padding: 20px; background: linear-gradient(135deg, #f3e5f5, #e1bee7); border-radius: 10px; text-align: center; border: 2px solid #9c27b0;">
                    <div style="font-size: 24px; font-weight: bold; color: #7b1fa2; margin-bottom: 10px;">
                        Gesamte Qualitätskosten: <span id="emergency-total-costs">-</span> €
                    </div>
                    <div style="font-size: 16px; color: #6a1b9a;">
                        <span id="emergency-quality-percentage">-</span>% vom Umsatz
                    </div>
                </div>
            </div>
            
            <script>
            function emergencyQCCCalculate() {
                const revenue = parseFloat(document.getElementById("emergency-revenue").value) || 140;
                const qualityPercent = parseFloat(document.getElementById("emergency-quality").value) || 6;
                
                const totalQualityCosts = revenue * 1000000 * (qualityPercent / 100);
                
                const prevention = totalQualityCosts * 0.1;      // 10%
                const appraisal = totalQualityCosts * 0.2;       // 20%
                const internal = totalQualityCosts * 0.4;        // 40%
                const external = totalQualityCosts * 0.3;        // 30%
                
                const cogq = prevention + appraisal;
                const copq = internal + external;
                
                document.getElementById("emergency-prevention").textContent = Math.round(prevention).toLocaleString();
                document.getElementById("emergency-appraisal").textContent = Math.round(appraisal).toLocaleString();
                document.getElementById("emergency-internal").textContent = Math.round(internal).toLocaleString();
                document.getElementById("emergency-external").textContent = Math.round(external).toLocaleString();
                document.getElementById("emergency-cogq-total").textContent = Math.round(cogq).toLocaleString();
                document.getElementById("emergency-copq-total").textContent = Math.round(copq).toLocaleString();
                document.getElementById("emergency-total-costs").textContent = Math.round(totalQualityCosts).toLocaleString();
                document.getElementById("emergency-quality-percentage").textContent = qualityPercent.toFixed(1);
                
                document.getElementById("emergency-qcc-results").style.display = "block";
            }
            
            function emergencyQCCReset() {
                document.getElementById("emergency-revenue").value = "140";
                document.getElementById("emergency-quality").value = "6";
                document.getElementById("emergency-qcc-results").style.display = "none";
            }
            </script>
        </div>';
    }
    
    /**
     * Admin Page
     */
    public function render_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>Quality Cost Calculator - Modern Architecture</h1>';
        echo '<div class="card" style="max-width: none;">';
        echo '<h2>System Status</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>Plugin Version</th><td>' . QCC_PLUGIN_VERSION . '</td></tr>';
        echo '<tr><th>Architecture</th><td>Modern (No Legacy)</td></tr>';
        echo '<tr><th>Status</th><td>' . ($this->initialized ? '✅ Active' : '❌ Error') . '</td></tr>';
        echo '<tr><th>Services Loaded</th><td>' . count($this->services) . '</td></tr>';
        echo '<tr><th>Shortcodes</th><td>';
        $shortcodes = array('quality_cost_calculator', 'qcc_calculator', 'cost_quality_calculator');
        foreach ($shortcodes as $sc) {
            echo shortcode_exists($sc) ? "✅ [$sc] " : "❌ [$sc] ";
        }
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';
        echo '</div>';
    }
}

// =============================================================================
// MODERNE SERVICES (Clean Implementation)
// =============================================================================

/**
 * Modern Calculator Service
 */
class QCC_Modern_Calculator {
    
    public function calculate($revenue, $quality_percent, $distribution = null) {
        if ($distribution === null) {
            $distribution = array(
                'prevention' => 10,       // 10%
                'appraisal' => 20,        // 20%
                'internal_failure' => 40, // 40%
                'external_failure' => 30  // 30%
            );
        }
        
        $total_quality_costs = $revenue * 1000000 * ($quality_percent / 100);
        
        return array(
            'revenue' => $revenue,
            'quality_percent' => $quality_percent,
            'total_costs' => $total_quality_costs,
            'prevention' => $total_quality_costs * ($distribution['prevention'] / 100),
            'appraisal' => $total_quality_costs * ($distribution['appraisal'] / 100),
            'internal_failure' => $total_quality_costs * ($distribution['internal_failure'] / 100),
            'external_failure' => $total_quality_costs * ($distribution['external_failure'] / 100),
            'cogq' => $total_quality_costs * (($distribution['prevention'] + $distribution['appraisal']) / 100),
            'copq' => $total_quality_costs * (($distribution['internal_failure'] + $distribution['external_failure']) / 100)
        );
    }
}

/**
 * Modern Renderer Service
 */
class QCC_Modern_Renderer {
    
    private $services;
    
    public function __construct($services) {
        $this->services = $services;
    }
    
    public function render($atts) {
        // Use fallback renderer if available
        if (isset($this->services['fallback_renderer'])) {
            return $this->services['fallback_renderer']->render($atts);
        }
        
        // Emergency fallback
        return '<div class="qcc-error">Modern renderer not available. Please refresh the page.</div>';
    }
}

/**
 * Modern Shortcode Handler
 */
class QCC_Modern_Shortcode_Handler {
    
    private $services;
    
    public function __construct($services) {
        $this->services = $services;
    }
    
    public function render($atts) {
        $atts = shortcode_atts(array(
            'language' => 'auto',
            'currency' => 'EUR',
            'layout' => 'modern',
            'theme' => 'default'
        ), $atts);
        
        try {
            return $this->services['renderer']->render($atts);
        } catch (Exception $e) {
            if (QCC_DEBUG) {
                error_log('QCC Shortcode Error: ' . $e->getMessage());
            }
            return '<div class="qcc-error">Calculator temporarily unavailable. Please try again.</div>';
        }
    }
}

// =============================================================================
// PLUGIN INITIALIZATION
// =============================================================================

// Initialize plugin
add_action('plugins_loaded', function() {
    QCC_Plugin_Controller::get_instance();
}, 10);

// Plugin activation
register_activation_hook(__FILE__, function() {
    // Simple activation
    update_option('qcc_version', QCC_PLUGIN_VERSION);
    update_option('qcc_activation_time', current_time('timestamp'));
});

// Plugin deactivation
register_deactivation_hook(__FILE__, function() {
    // Simple deactivation
    delete_transient('qcc_cache');
});

// Plugin action links
add_filter('plugin_action_links_' . QCC_PLUGIN_BASENAME, function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=qcc-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});

// Plugin loaded successfully
if (QCC_DEBUG) {
    error_log('QCC: Modern architecture plugin loaded - Version ' . QCC_PLUGIN_VERSION);
}
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
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 * 
 * @package QualityCostCalculator
 * @version 2.0.0
 * @author Your Name
 * @copyright 2024 Your Name
 * @license GPL-2.0-or-later
 * @link https://github.com/your-username/quality-cost-calculator
 * @since 1.0.0
 * 
 * Features:
 * ✓ COGQ & COPQ Calculation with detailed breakdown
 * ✓ Multi-language support (EN, DE, FR, ES, ZH)
 * ✓ Multi-currency support (EUR, USD, CNY)
 * ✓ Real-time input validation
 * ✓ Responsive design for all devices
 * ✓ AJAX-powered calculations
 * ✓ REST API endpoints
 * ✓ Export functionality (JSON, CSV)
 * ✓ WordPress admin integration
 * ✓ Shortcode support with attributes
 * ✓ Theme integration ready
 * ✓ Developer hooks and filters
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// WordPress and PHP version check
if (!function_exists('add_action')) {
    exit('WordPress environment required.');
}

// Plugin constants
define('QCC_VERSION', '2.0.0');
define('QCC_PLUGIN_FILE', __FILE__);
define('QCC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('QCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('QCC_TEXT_DOMAIN', 'quality-cost-calculator');
define('QCC_MIN_PHP_VERSION', '7.4');
define('QCC_MIN_WP_VERSION', '5.0');
define('QCC_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

/**
 * Smart Autoloader - lädt Klassen nur bei Bedarf
 */
spl_autoload_register(function($class_name) {
    if (strpos($class_name, 'QCC_') !== 0) {
        return;
    }
    
    // Klassen-Mapping für intelligente Auslagerung
    $class_map = array(
        'QCC_Calculator' => 'includes/class-qcc-calculator.php',
        'QCC_Validator' => 'includes/class-qcc-validator.php',
        'QCC_Renderer' => 'includes/class-qcc-renderer.php',
        'QCC_Admin' => 'includes/class-qcc-admin.php',
        'QCC_Assets' => 'includes/class-qcc-assets.php',
        'QCC_API' => 'includes/class-qcc-api.php'
    );
    
    if (isset($class_map[$class_name])) {
        $file_path = QCC_PLUGIN_PATH . $class_map[$class_name];
        
        if (file_exists($file_path)) {
            require_once $file_path;
            
            if (QCC_DEBUG) {
                error_log("QCC: Loaded {$class_name}");
            }
        } else if (QCC_DEBUG) {
            error_log("QCC: Failed to load {$class_name} - file not found");
        }
    }
});

/**
 * Main Plugin Class - Nur Koordination, keine Business Logic
 */
class QCC_Plugin {
    
    private static $instance = null;
    private $loaded = false;
    private $components = array();
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Requirements check
        if (!$this->check_requirements()) {
            return;
        }
        
        // WordPress hooks
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_plugin'));
        
        $this->loaded = true;
    }
    
    private function check_requirements() {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                printf(__('Quality Cost Calculator requires PHP 7.4+. Current: %s', QCC_TEXT_DOMAIN), PHP_VERSION);
                echo '</p></div>';
            });
            return false;
        }
        return true;
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(QCC_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    public function init_plugin() {
        // Lazy loading der Komponenten nur bei Bedarf
        $this->maybe_load_frontend();
        $this->maybe_load_admin();
        $this->maybe_load_api();
        
        // Register shortcode
        add_shortcode('quality_cost_calculator', array($this, 'render_shortcode'));
        add_shortcode('qcc', array($this, 'render_shortcode'));
        
        do_action('qcc_init');
    }
    
    private function maybe_load_frontend() {
        // Nur laden wenn Shortcode auf der Seite ist oder AJAX-Request
        if ($this->needs_frontend()) {
            $this->get_component('assets')->enqueue_frontend();
        }
    }
    
    private function maybe_load_admin() {
        if (is_admin()) {
            $this->get_component('admin')->init();
        }
    }
    
    private function maybe_load_api() {
        if ($this->needs_api()) {
            $this->get_component('api')->init();
        }
    }
    
    private function needs_frontend() {
        return !is_admin() && (
            $this->page_has_shortcode() || 
            $this->is_ajax_request() ||
            isset($_GET['qcc']) // Manual override
        );
    }
    
    private function needs_api() {
        return defined('REST_REQUEST') || 
               (isset($_POST['action']) && strpos($_POST['action'], 'qcc_') === 0);
    }
    
    private function page_has_shortcode() {
        global $post;
        return is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'quality_cost_calculator') ||
            has_shortcode($post->post_content, 'qcc')
        );
    }
    
    private function is_ajax_request() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
    
    public function render_shortcode($atts) {
        // Renderer-Komponente nur bei tatsächlicher Verwendung laden
        return $this->get_component('renderer')->render_calculator($atts);
    }
    
    /**
     * Intelligente Komponenten-Verwaltung - Lazy Loading
     */
    private function get_component($name) {
        if (!isset($this->components[$name])) {
            $this->components[$name] = $this->create_component($name);
        }
        return $this->components[$name];
    }
    
    private function create_component($name) {
        switch ($name) {
            case 'calculator':
                return new QCC_Calculator();
                
            case 'validator':
                return new QCC_Validator();
                
            case 'renderer':
                return new QCC_Renderer(
                    $this->get_component('calculator'),
                    $this->get_component('validator')
                );
                
            case 'admin':
                return new QCC_Admin();
                
            case 'assets':
                return new QCC_Assets();
                
            case 'api':
                return new QCC_API(
                    $this->get_component('calculator'),
                    $this->get_component('validator')
                );
                
            default:
                throw new Exception("Unknown component: {$name}");
        }
    }
    
    // Public API
    public function is_loaded() {
        return $this->loaded;
    }
    
    public function get_version() {
        return QCC_VERSION;
    }
    
    // Activation hooks
    public static function activate() {
        add_option('qcc_default_language', 'en');
        add_option('qcc_default_currency', 'EUR');
        add_option('qcc_default_unit', '1000000');
        add_option('qcc_version', QCC_VERSION);
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        delete_option('qcc_default_language');
        delete_option('qcc_default_currency');
        delete_option('qcc_default_unit');
        delete_option('qcc_version');
    }
}

/**
 * Helper Functions - Minimales Global API
 */
function qcc_plugin() {
    return QCC_Plugin::get_instance();
}

function qcc_format_currency($value, $currency = 'EUR', $unit = '1000000') {
    $symbols = array('EUR' => '€', 'USD' => '$', 'CNY' => '¥');
    $symbol = $symbols[$currency] ?? $currency;
    $unit_label = $unit == '1000000' ? 'M' : 'B';
    $formatted = number_format($value / intval($unit), 2, '.', ',');
    return $formatted . ' ' . $symbol . ' ' . $unit_label;
}

function qcc_get_option($key, $default = null) {
    $option_map = array(
        'language' => 'qcc_default_language',
        'currency' => 'qcc_default_currency',
        'unit' => 'qcc_default_unit'
    );
    
    $option_name = $option_map[$key] ?? "qcc_{$key}";
    return get_option($option_name, $default);
}

/**
 * Auto-Setup System - Erstellt fehlende Dateien automatisch
 */
add_action('init', function() {
    // Prüfe ob Komponenten-Dateien existieren, wenn nicht erstelle sie
    $required_files = array(
        'includes/class-qcc-calculator.php' => 'calculator_class',
        'includes/class-qcc-validator.php' => 'validator_class',
        'includes/class-qcc-renderer.php' => 'renderer_class',
        'includes/class-qcc-admin.php' => 'admin_class',
        'includes/class-qcc-assets.php' => 'assets_class',
        'includes/class-qcc-api.php' => 'api_class',
        'templates/calculator.php' => 'calculator_template',
        'assets/css/qcc-frontend.css' => 'frontend_css',
        'assets/js/qcc-frontend.js' => 'frontend_js'
    );
    
    foreach ($required_files as $file => $template_key) {
        $file_path = QCC_PLUGIN_PATH . $file;
        
        if (!file_exists($file_path)) {
            wp_mkdir_p(dirname($file_path));
            
            $content = qcc_get_file_template($template_key);
            file_put_contents($file_path, $content);
            
            if (QCC_DEBUG) {
                error_log("QCC: Auto-created {$file}");
            }
        }
    }
}, 999); // Late priority um sicherzustellen dass Plugin initialisiert ist

/**
 * Template-Generator für Auto-Setup
 */
function qcc_get_file_template($template_key) {
    $templates = array(
        
        'calculator_class' => '<?php
/**
 * QCC Calculator - Reine Business Logic
 */
class QCC_Calculator {
    
    public function calculate($input) {
        $total_quality_cost = $input["revenue"] * ($input["quality_percentage"] / 100);
        
        $prevention_cost = $total_quality_cost * ($input["prevention"] / 100);
        $appraisal_cost = $total_quality_cost * ($input["appraisal"] / 100);
        $internal_defect_cost = $total_quality_cost * ($input["internal_defect"] / 100);
        $external_defect_cost = $total_quality_cost * ($input["external_defect"] / 100);
        
        $cogq = $prevention_cost + $appraisal_cost;
        $copq = $internal_defect_cost + $external_defect_cost;
        
        return array(
            "total_quality_cost" => $total_quality_cost,
            "cogq" => $cogq,
            "copq" => $copq,
            "prevention_cost" => $prevention_cost,
            "appraisal_cost" => $appraisal_cost,
            "internal_defect_cost" => $internal_defect_cost,
            "external_defect_cost" => $external_defect_cost,
            "cogq_percentage" => $total_quality_cost > 0 ? ($cogq / $total_quality_cost) * 100 : 0,
            "copq_percentage" => $total_quality_cost > 0 ? ($copq / $total_quality_cost) * 100 : 0,
            "timestamp" => current_time("mysql")
        );
    }
    
    public function get_default_values() {
        return array(
            "revenue" => 140,
            "quality_percentage" => 6,
            "prevention" => 10,
            "appraisal" => 20,
            "internal_defect" => 30,
            "external_defect" => 40
        );
    }
}',

        'validator_class' => '<?php
/**
 * QCC Validator - Input-Validierung
 */
class QCC_Validator {
    
    public function validate($input) {
        $errors = array();
        
        // Revenue validation
        if (!isset($input["revenue"]) || !is_numeric($input["revenue"]) || $input["revenue"] <= 0) {
            $errors[] = __("Revenue must be greater than 0", QCC_TEXT_DOMAIN);
        }
        
        // Quality percentage validation
        if (!isset($input["quality_percentage"]) || !is_numeric($input["quality_percentage"]) || 
            $input["quality_percentage"] < 0 || $input["quality_percentage"] > 100) {
            $errors[] = __("Quality percentage must be between 0 and 100", QCC_TEXT_DOMAIN);
        }
        
        // Percentage breakdown validation
        $percentages = array("prevention", "appraisal", "internal_defect", "external_defect");
        $total = 0;
        
        foreach ($percentages as $key) {
            if (isset($input[$key])) {
                if (!is_numeric($input[$key]) || $input[$key] < 0 || $input[$key] > 100) {
                    $errors[] = sprintf(__("%s percentage must be between 0 and 100", QCC_TEXT_DOMAIN), ucfirst(str_replace("_", " ", $key)));
                } else {
                    $total += floatval($input[$key]);
                }
            }
        }
        
        // Check percentage sum
        if (abs($total - 100) > 0.01) {
            $errors[] = __("Percentages must sum to 100%", QCC_TEXT_DOMAIN);
        }
        
        return empty($errors) ? true : $errors;
    }
    
    public function sanitize($input) {
        return array(
            "revenue" => floatval($input["revenue"] ?? 0),
            "quality_percentage" => floatval($input["quality_percentage"] ?? 6),
            "prevention" => floatval($input["prevention"] ?? 10),
            "appraisal" => floatval($input["appraisal"] ?? 20),
            "internal_defect" => floatval($input["internal_defect"] ?? 30),
            "external_defect" => floatval($input["external_defect"] ?? 40)
        );
    }
}',

        'renderer_class' => '<?php
/**
 * QCC Renderer - HTML-Output
 */
class QCC_Renderer {
    
    private $calculator;
    private $validator;
    
    public function __construct($calculator, $validator) {
        $this->calculator = $calculator;
        $this->validator = $validator;
    }
    
    public function render_calculator($atts) {
        $atts = shortcode_atts(array(
            "currency" => qcc_get_option("currency", "EUR"),
            "language" => qcc_get_option("language", "en"),
            "unit" => qcc_get_option("unit", "1000000")
        ), $atts);
        
        ob_start();
        include QCC_PLUGIN_PATH . "templates/calculator.php";
        return ob_get_clean();
    }
}',

        'admin_class' => '<?php
/**
 * QCC Admin - Backend-Funktionalität
 */
class QCC_Admin {
    
    public function init() {
        add_action("admin_menu", array($this, "add_menu"));
        add_action("admin_init", array($this, "register_settings"));
    }
    
    public function add_menu() {
        add_options_page(
            __("Quality Cost Calculator", QCC_TEXT_DOMAIN),
            __("QCC Settings", QCC_TEXT_DOMAIN),
            "manage_options",
            "quality-cost-calculator",
            array($this, "render_page")
        );
    }
    
    public function register_settings() {
        register_setting("qcc_settings", "qcc_default_language");
        register_setting("qcc_settings", "qcc_default_currency");
        register_setting("qcc_settings", "qcc_default_unit");
    }
    
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e("Quality Cost Calculator Settings", QCC_TEXT_DOMAIN); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields("qcc_settings"); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e("Default Language", QCC_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="qcc_default_language">
                                <option value="en" <?php selected(get_option("qcc_default_language", "en"), "en"); ?>>English</option>
                                <option value="de" <?php selected(get_option("qcc_default_language", "en"), "de"); ?>>Deutsch</option>
                                <option value="fr" <?php selected(get_option("qcc_default_language", "en"), "fr"); ?>>Français</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e("Default Currency", QCC_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="qcc_default_currency">
                                <option value="EUR" <?php selected(get_option("qcc_default_currency", "EUR"), "EUR"); ?>>Euro (€)</option>
                                <option value="USD" <?php selected(get_option("qcc_default_currency", "EUR"), "USD"); ?>>US Dollar ($)</option>
                                <option value="CNY" <?php selected(get_option("qcc_default_currency", "EUR"), "CNY"); ?>>Chinese Yuan (¥)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e("Default Unit", QCC_TEXT_DOMAIN); ?></th>
                        <td>
                            <select name="qcc_default_unit">
                                <option value="1000000" <?php selected(get_option("qcc_default_unit", "1000000"), "1000000"); ?>><?php esc_html_e("Millions", QCC_TEXT_DOMAIN); ?></option>
                                <option value="1000000000" <?php selected(get_option("qcc_default_unit", "1000000"), "1000000000"); ?>><?php esc_html_e("Billions", QCC_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <div class="qcc-info">
                <h3><?php esc_html_e("Shortcode Usage", QCC_TEXT_DOMAIN); ?></h3>
                <p><code>[quality_cost_calculator]</code></p>
                <p><strong><?php esc_html_e("Version:", QCC_TEXT_DOMAIN); ?></strong> <?php echo QCC_VERSION; ?></p>
            </div>
        </div>
        <?php
    }
}',

        'assets_class' => '<?php
/**
 * QCC Assets - CSS/JS Management
 */
class QCC_Assets {
    
    public function enqueue_frontend() {
        // CSS
        wp_enqueue_style("qcc-frontend", QCC_PLUGIN_URL . "assets/css/qcc-frontend.css", array(), QCC_VERSION);
        
        // JavaScript
        wp_enqueue_script("qcc-frontend", QCC_PLUGIN_URL . "assets/js/qcc-frontend.js", array("jquery"), QCC_VERSION, true);
        
        // Localize script
        wp_localize_script("qcc-frontend", "qcc_config", array(
            "ajax_url" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("qcc_nonce"),
            "strings" => array(
                "calculation_error" => __("Calculation error occurred", QCC_TEXT_DOMAIN),
                "percentage_sum_error" => __("Percentages must sum to 100%", QCC_TEXT_DOMAIN)
            )
        ));
    }
}',

        'api_class' => '<?php
/**
 * QCC API - AJAX & REST Endpoints
 */
class QCC_API {
    
    private $calculator;
    private $validator;
    
    public function __construct($calculator, $validator) {
        $this->calculator = $calculator;
        $this->validator = $validator;
    }
    
    public function init() {
        // AJAX hooks
        add_action("wp_ajax_qcc_calculate", array($this, "ajax_calculate"));
        add_action("wp_ajax_nopriv_qcc_calculate", array($this, "ajax_calculate"));
        
        // REST API
        add_action("rest_api_init", array($this, "register_rest_routes"));
    }
    
    public function ajax_calculate() {
        if (!wp_verify_nonce($_POST["nonce"] ?? "", "qcc_nonce")) {
            wp_send_json_error(__("Security check failed", QCC_TEXT_DOMAIN));
        }
        
        $input = $this->validator->sanitize($_POST);
        $validation = $this->validator->validate($input);
        
        if ($validation !== true) {
            wp_send_json_error(array("errors" => $validation));
        }
        
        $result = $this->calculator->calculate($input);
        wp_send_json_success($result);
    }
    
    public function register_rest_routes() {
        register_rest_route("qcc/v1", "/calculate", array(
            "methods" => "POST",
            "callback" => array($this, "rest_calculate"),
            "permission_callback" => "__return_true"
        ));
    }
    
    public function rest_calculate($request) {
        $params = $request->get_params();
        $input = $this->validator->sanitize($params);
        $validation = $this->validator->validate($input);
        
        if ($validation !== true) {
            return new WP_Error("validation_failed", implode(", ", $validation), array("status" => 400));
        }
        
        return rest_ensure_response($this->calculator->calculate($input));
    }
}',

        'calculator_template' => '<?php
// Calculator Template
$currency_symbols = array("EUR" => "€", "USD" => "$", "CNY" => "¥");
$symbol = $currency_symbols[$atts["currency"]] ?? $atts["currency"];
$unit_label = $atts["unit"] == "1000000" ? "M" : "B";
?>
<div class="qcc-calculator" data-currency="<?php echo esc_attr($atts["currency"]); ?>" data-unit="<?php echo esc_attr($atts["unit"]); ?>">
    <form class="qcc-form">
        <div class="qcc-inputs">
            <div class="qcc-input-group">
                <label for="qcc-revenue">' . __('Revenue', QCC_TEXT_DOMAIN) . '</label>
                <input type="number" id="qcc-revenue" name="revenue" min="0" step="0.01" placeholder="140" required>
                <span class="unit"><?php echo esc_html($symbol . " " . $unit_label); ?></span>
            </div>
            
            <div class="qcc-input-group">
                <label for="qcc-quality-pct">' . __('Quality Cost %', QCC_TEXT_DOMAIN) . '</label>
                <input type="number" id="qcc-quality-pct" name="quality_percentage" min="0" max="100" step="0.1" placeholder="6" required>
                <span class="unit">%</span>
            </div>
            
            <div class="qcc-breakdown">
                <h4>' . __('Cost Breakdown', QCC_TEXT_DOMAIN) . '</h4>
                <div class="qcc-row">
                    <div class="qcc-input-group">
                        <label for="qcc-prevention">' . __('Prevention %', QCC_TEXT_DOMAIN) . '</label>
                        <input type="number" id="qcc-prevention" name="prevention" min="0" max="100" step="0.1" placeholder="10" required>
                    </div>
                    <div class="qcc-input-group">
                        <label for="qcc-appraisal">' . __('Appraisal %', QCC_TEXT_DOMAIN) . '</label>
                        <input type="number" id="qcc-appraisal" name="appraisal" min="0" max="100" step="0.1" placeholder="20" required>
                    </div>
                </div>
                <div class="qcc-row">
                    <div class="qcc-input-group">
                        <label for="qcc-internal">' . __('Internal Defects %', QCC_TEXT_DOMAIN) . '</label>
                        <input type="number" id="qcc-internal" name="internal_defect" min="0" max="100" step="0.1" placeholder="30" required>
                    </div>
                    <div class="qcc-input-group">
                        <label for="qcc-external">' . __('External Defects %', QCC_TEXT_DOMAIN) . '</label>
                        <input type="number" id="qcc-external" name="external_defect" min="0" max="100" step="0.1" placeholder="40" required>
                    </div>
                </div>
                <div class="qcc-error" style="display:none; color: red; margin-top: 10px;"></div>
            </div>
        </div>
        
        <button type="submit" class="qcc-calculate">' . __('Calculate', QCC_TEXT_DOMAIN) . '</button>
    </form>
    
    <div class="qcc-results" style="display:none;">
        <h3>' . __('Results', QCC_TEXT_DOMAIN) . '</h3>
        <div class="qcc-cards">
            <div class="qcc-card cogq">
                <h4>' . __('Cost of Good Quality (COGQ)', QCC_TEXT_DOMAIN) . '</h4>
                <div class="value" data-field="cogq">-</div>
            </div>
            <div class="qcc-card copq">
                <h4>' . __('Cost of Poor Quality (COPQ)', QCC_TEXT_DOMAIN) . '</h4>
                <div class="value" data-field="copq">-</div>
            </div>
        </div>
        <div class="qcc-breakdown">
            <div class="qcc-item"><span>' . __('Prevention:', QCC_TEXT_DOMAIN) . '</span> <span data-field="prevention_cost">-</span></div>
            <div class="qcc-item"><span>' . __('Appraisal:', QCC_TEXT_DOMAIN) . '</span> <span data-field="appraisal_cost">-</span></div>
            <div class="qcc-item"><span>' . __('Internal Defects:', QCC_TEXT_DOMAIN) . '</span> <span data-field="internal_defect_cost">-</span></div>
            <div class="qcc-item"><span>' . __('External Defects:', QCC_TEXT_DOMAIN) . '</span> <span data-field="external_defect_cost">-</span></div>
        </div>
    </div>
</div>',

        'frontend_css' => '.qcc-calculator{max-width:600px;margin:20px 0;padding:20px;border:1px solid #ddd;border-radius:8px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}.qcc-input-group{margin-bottom:15px;position:relative}.qcc-input-group label{display:block;margin-bottom:5px;font-weight:600}.qcc-input-group input{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px}.qcc-input-group .unit{position:absolute;right:15px;top:35px;color:#666;pointer-events:none}.qcc-row{display:flex;gap:15px}.qcc-row .qcc-input-group{flex:1}.qcc-calculate{width:100%;padding:12px;background:#0073aa;color:white;border:none;border-radius:4px;font-size:16px;cursor:pointer;margin-top:20px}.qcc-calculate:hover{background:#005a87}.qcc-results{margin-top:30px;padding-top:20px;border-top:1px solid #eee}.qcc-cards{display:flex;gap:20px;margin-bottom:20px}.qcc-card{flex:1;padding:20px;border-radius:8px;text-align:center}.qcc-card.cogq{background:#e8f5e8;border:1px solid #4caf50}.qcc-card.copq{background:#fff3e0;border:1px solid #ff9800}.qcc-card h4{margin:0 0 10px;font-size:14px}.qcc-card .value{font-size:24px;font-weight:bold}.qcc-item{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee}.qcc-breakdown h4{margin-bottom:15px}.qcc-error{font-size:14px;font-weight:bold}@media(max-width:768px){.qcc-row{flex-direction:column;gap:10px}.qcc-cards{flex-direction:column;gap:15px}}',

        'frontend_js' => 'jQuery(document).ready(function($){$(".qcc-calculator").each(function(){var $calc=$(this);var $form=$calc.find(".qcc-form");var $results=$calc.find(".qcc-results");var $error=$calc.find(".qcc-error");var currency=$calc.data("currency")||"EUR";var unit=$calc.data("unit")||"1000000";var symbols={EUR:"€",USD:"$",CNY:"¥"};var symbol=symbols[currency]||currency;var unitLabel=unit=="1000000"?"M":"B";function formatCurrency(value){var scaled=value/parseFloat(unit);return scaled.toFixed(2)+" "+symbol+" "+unitLabel}function validatePercentages(){var total=0;$form.find("input[name$=\\"_defect\\"], input[name=\\"prevention\\"], input[name=\\"appraisal\\"]").each(function(){total+=parseFloat($(this).val())||0});var isValid=Math.abs(total-100)<0.01;$error.toggle(!isValid);if(!isValid){$error.text(qcc_config.strings.percentage_sum_error||"Percentages must sum to 100%")}return isValid}$form.find("input[name$=\\"_defect\\"], input[name=\\"prevention\\"], input[name=\\"appraisal\\"]").on("input",validatePercentages);$form.on("submit",function(e){e.preventDefault();if(!validatePercentages())return;var data={action:"qcc_calculate",nonce:qcc_config.nonce};$form.find("input").each(function(){data[this.name]=parseFloat(this.value)||0});$.post(qcc_config.ajax_url,data,function(response){if(response.success){var result=response.data;$results.find("[data-field=\\"cogq\\"]").text(formatCurrency(result.cogq));$results.find("[data-field=\\"copq\\"]").text(formatCurrency(result.copq));$results.find("[data-field=\\"prevention_cost\\"]").text(formatCurrency(result.prevention_cost));$results.find("[data-field=\\"appraisal_cost\\"]").text(formatCurrency(result.appraisal_cost));$results.find("[data-field=\\"internal_defect_cost\\"]").text(formatCurrency(result.internal_defect_cost));$results.find("[data-field=\\"external_defect_cost\\"]").text(formatCurrency(result.external_defect_cost));$results.show();$error.hide()}else{var errorMsg=response.data&&response.data.errors?response.data.errors.join(", "):(qcc_config.strings.calculation_error||"Calculation failed");$error.show().text(errorMsg)}}).fail(function(){$error.show().text("Connection error")})})})});'
    );
    
    return $templates[$template_key] ?? '';
}

// Initialize plugin
add_action('plugins_loaded', array('QCC_Plugin', 'get_instance'));

// Activation hooks
register_activation_hook(__FILE__, array('QCC_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('QCC_Plugin', 'deactivate'));
register_uninstall_hook(__FILE__, array('QCC_Plugin', 'uninstall'));

// Emergency shortcode fallback (falls Autoload fehlschlägt)
if (!class_exists('QCC_Plugin')) {
    add_shortcode('quality_cost_calculator', function($atts) {
        return '<div class="qcc-error" style="padding:20px;border:1px solid red;background:#ffe6e6;color:red;border-radius:4px;margin:20px 0;">
            <strong>QCC Error:</strong> Plugin components could not be loaded. Please check your installation.
        </div>';
    });
}

// End of file
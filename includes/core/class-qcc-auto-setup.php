<?php
/**
 * QCC Auto-Setup Class - Ausgelagerte Datei-Generierung
 * 
 * Datei: includes/core/class-qcc-auto-setup.php
 * 
 * Übernimmt das komplette Auto-Setup System aus der Hauptdatei
 * 
 * @package QualityCostCalculator
 * @subpackage Core
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Auto-Setup Class
 * 
 * Automatische Erstellung fehlender Plugin-Dateien und Strukturen
 */
class QCC_Auto_Setup {
    
    /**
     * Instance tracker
     * 
     * @var bool
     */
    private static $initialized = false;
    
    /**
     * Required files map
     * 
     * @var array
     */
    private $required_files = array();
    
    /**
     * File templates
     * 
     * @var array
     */
    private $templates = array();
    
    /**
     * Created files log
     * 
     * @var array
     */
    private $created_files = array();
    
    /**
     * Initialize auto-setup system
     */
    public function init() {
        if (self::$initialized) {
            return;
        }
        
        $this->setup_required_files();
        $this->setup_templates();
        $this->register_hooks();
        
        self::$initialized = true;
        
        if (QCC_DEBUG) {
            error_log('QCC Auto-Setup: System initialized');
        }
    }
    
    /**
     * Setup required files mapping
     */
    private function setup_required_files() {
        $this->required_files = array(
            // Core classes
            'includes/class-qcc-calculator.php' => 'calculator_class',
            'includes/class-qcc-validator.php' => 'validator_class',
            'includes/class-qcc-renderer.php' => 'renderer_class',
            
            // Admin classes
            'includes/class-qcc-admin.php' => 'admin_class',
            
            // Service classes
            'includes/class-qcc-assets.php' => 'assets_class',
            'includes/class-qcc-api.php' => 'api_class',
            
            // Templates
            'templates/calculator.php' => 'calculator_template',
            
            // Frontend assets
            'assets/css/qcc-frontend.css' => 'frontend_css',
            'assets/js/qcc-frontend.js' => 'frontend_js',
            
            // Configuration files
            'includes/qcc-config.php' => 'config_file'
        );
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // Late initialization to ensure plugin is fully loaded
        add_action('init', array($this, 'check_and_create_files'), 999);
        
        // Admin notice for created files
        add_action('admin_notices', array($this, 'show_creation_notices'));
    }
    
    /**
     * Check and create missing files
     */
    public function check_and_create_files() {
        foreach ($this->required_files as $file => $template_key) {
            $this->create_if_missing($file, $template_key);
        }
        
        // Log results
        if (!empty($this->created_files) && QCC_DEBUG) {
            error_log('QCC Auto-Setup: Created ' . count($this->created_files) . ' missing files');
        }
    }
    
    /**
     * Create file if missing
     * 
     * @param string $file Relative file path
     * @param string $template_key Template identifier
     */
    private function create_if_missing($file, $template_key) {
        $file_path = QCC_PLUGIN_PATH . $file;
        
        if (!file_exists($file_path)) {
            // Create directory if needed
            $dir = dirname($file_path);
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
            
            // Generate content
            $content = $this->get_template_content($template_key, $file);
            
            // Write file
            if (file_put_contents($file_path, $content)) {
                $this->created_files[] = $file;
                
                if (QCC_DEBUG) {
                    error_log("QCC Auto-Setup: Created {$file}");
                }
            } else {
                if (QCC_DEBUG) {
                    error_log("QCC Auto-Setup: Failed to create {$file}");
                }
            }
        }
    }
    
    /**
     * Get template content for file
     * 
     * @param string $template_key Template identifier
     * @param string $file_path File path for context
     * @return string Generated content
     */
    private function get_template_content($template_key, $file_path) {
        $this->setup_templates();
        
        if (isset($this->templates[$template_key])) {
            return $this->templates[$template_key];
        }
        
        // Fallback based on file extension
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'php':
                return $this->get_php_template($file_path);
            case 'css':
                return $this->get_css_template($file_path);
            case 'js':
                return $this->get_js_template($file_path);
            default:
                return $this->get_generic_template($file_path);
        }
    }
    
    /**
     * Setup all templates
     */
    private function setup_templates() {
        if (!empty($this->templates)) {
            return;
        }
        
        $this->templates = array(
            'calculator_class' => $this->get_calculator_class_template(),
            'validator_class' => $this->get_validator_class_template(),
            'renderer_class' => $this->get_renderer_class_template(),
            'admin_class' => $this->get_admin_class_template(),
            'assets_class' => $this->get_assets_class_template(),
            'api_class' => $this->get_api_class_template(),
            'calculator_template' => $this->get_calculator_template(),
            'frontend_css' => $this->get_frontend_css_template(),
            'frontend_js' => $this->get_frontend_js_template(),
            'config_file' => $this->get_config_template()
        );
    }
    
    /**
     * Calculator class template
     */
    private function get_calculator_class_template() {
        return '<?php
/**
 * QCC Calculator - Auto-generated Business Logic
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class QCC_Calculator {
    
    /**
     * Calculate quality costs
     * 
     * @param array $input Input parameters
     * @return array Calculation results
     */
    public function calculate($input) {
        // Sanitize inputs
        $revenue = floatval($input["revenue"] ?? 0);
        $quality_percentage = floatval($input["quality_percentage"] ?? 6);
        $prevention = floatval($input["prevention"] ?? 10);
        $appraisal = floatval($input["appraisal"] ?? 20);
        $internal_defect = floatval($input["internal_defect"] ?? 30);
        $external_defect = floatval($input["external_defect"] ?? 40);
        
        // Calculate total quality cost
        $total_quality_cost = ($revenue * $quality_percentage) / 100;
        
        // Calculate individual costs
        $prevention_cost = ($total_quality_cost * $prevention) / 100;
        $appraisal_cost = ($total_quality_cost * $appraisal) / 100;
        $internal_defect_cost = ($total_quality_cost * $internal_defect) / 100;
        $external_defect_cost = ($total_quality_cost * $external_defect) / 100;
        
        // Calculate COGQ and COPQ
        $cogq = $prevention_cost + $appraisal_cost;
        $copq = $internal_defect_cost + $external_defect_cost;
        
        return array(
            "revenue" => $revenue,
            "quality_percentage" => $quality_percentage,
            "total_quality_cost" => $total_quality_cost,
            "cogq" => $cogq,
            "copq" => $copq,
            "prevention_cost" => $prevention_cost,
            "appraisal_cost" => $appraisal_cost,
            "internal_defect_cost" => $internal_defect_cost,
            "external_defect_cost" => $external_defect_cost,
            "cogq_percentage" => $total_quality_cost > 0 ? ($cogq / $total_quality_cost) * 100 : 0,
            "copq_percentage" => $total_quality_cost > 0 ? ($copq / $total_quality_cost) * 100 : 0,
            "revenue_percentage" => $revenue > 0 ? ($total_quality_cost / $revenue) * 100 : 0
        );
    }
    
    /**
     * Validate input percentages
     * 
     * @param array $input Input parameters
     * @return bool|array True if valid, error array if invalid
     */
    public function validate_percentages($input) {
        $prevention = floatval($input["prevention"] ?? 0);
        $appraisal = floatval($input["appraisal"] ?? 0);
        $internal_defect = floatval($input["internal_defect"] ?? 0);
        $external_defect = floatval($input["external_defect"] ?? 0);
        
        $total = $prevention + $appraisal + $internal_defect + $external_defect;
        
        if (abs($total - 100) > 0.01) {
            return array(
                "valid" => false,
                "error" => "Percentages must add up to 100%",
                "current_total" => $total
            );
        }
        
        return array("valid" => true);
    }
}';
    }
    
    /**
     * Validator class template
     */
    private function get_validator_class_template() {
        return '<?php
/**
 * QCC Validator - Auto-generated Validation Logic
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class QCC_Validator {
    
    /**
     * Validate calculator input
     * 
     * @param array $input Input data
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate($input) {
        $errors = array();
        
        // Validate revenue
        if (!isset($input["revenue"]) || !is_numeric($input["revenue"]) || $input["revenue"] <= 0) {
            $errors[] = "Revenue must be a positive number";
        }
        
        // Validate quality percentage
        if (!isset($input["quality_percentage"]) || !is_numeric($input["quality_percentage"]) || 
            $input["quality_percentage"] < 0 || $input["quality_percentage"] > 100) {
            $errors[] = "Quality percentage must be between 0 and 100";
        }
        
        // Validate cost percentages
        $percentages = array("prevention", "appraisal", "internal_defect", "external_defect");
        foreach ($percentages as $key) {
            if (!isset($input[$key]) || !is_numeric($input[$key]) || 
                $input[$key] < 0 || $input[$key] > 100) {
                $errors[] = ucfirst(str_replace("_", " ", $key)) . " must be between 0 and 100";
            }
        }
        
        // Validate percentage total
        if (count($errors) == 0) {
            $total = $input["prevention"] + $input["appraisal"] + 
                     $input["internal_defect"] + $input["external_defect"];
            if (abs($total - 100) > 0.01) {
                $errors[] = "Cost percentages must add up to 100%";
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error("validation_failed", implode(", ", $errors));
        }
        
        return true;
    }
    
    /**
     * Sanitize input data
     * 
     * @param array $input Raw input
     * @return array Sanitized input
     */
    public function sanitize($input) {
        return array(
            "revenue" => floatval($input["revenue"] ?? 0),
            "quality_percentage" => floatval($input["quality_percentage"] ?? 6),
            "prevention" => floatval($input["prevention"] ?? 10),
            "appraisal" => floatval($input["appraisal"] ?? 20),
            "internal_defect" => floatval($input["internal_defect"] ?? 30),
            "external_defect" => floatval($input["external_defect"] ?? 40),
            "language" => sanitize_text_field($input["language"] ?? "en"),
            "currency" => sanitize_text_field($input["currency"] ?? "EUR"),
            "unit" => sanitize_text_field($input["unit"] ?? "1000000")
        );
    }
}';
    }
    
    /**
     * Renderer class template
     */
    private function get_renderer_class_template() {
        return '<?php
/**
 * QCC Renderer - Auto-generated HTML Output
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class QCC_Renderer {
    
    private $calculator;
    private $validator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->calculator = new QCC_Calculator();
        $this->validator = new QCC_Validator();
    }
    
    /**
     * Render calculator HTML
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_calculator($atts = array()) {
        $atts = shortcode_atts(array(
            "currency" => get_option("qcc_default_currency", "EUR"),
            "language" => get_option("qcc_default_language", "en"),
            "unit" => get_option("qcc_default_unit", "1000000"),
            "theme" => "default"
        ), $atts);
        
        // Load template
        $template_path = QCC_PLUGIN_PATH . "templates/calculator.php";
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        // Fallback HTML
        return $this->get_fallback_html($atts);
    }
    
    /**
     * Get fallback HTML if template missing
     * 
     * @param array $atts Attributes
     * @return string HTML
     */
    private function get_fallback_html($atts) {
        return \'<div class="qcc-calculator-fallback">
            <h3>Quality Cost Calculator</h3>
            <p>Template files are being generated. Please refresh the page.</p>
            <style>
                .qcc-calculator-fallback {
                    padding: 20px;
                    border: 2px dashed #ccc;
                    text-align: center;
                    margin: 20px 0;
                }
            </style>
        </div>\';
    }
}';
    }
    
    /**
     * Admin class template
     */
    private function get_admin_class_template() {
        return '<?php
/**
 * QCC Admin - Auto-generated Admin Interface
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class QCC_Admin {
    
    /**
     * Initialize admin hooks
     */
    public function init() {
        add_action("admin_menu", array($this, "add_admin_menu"));
        add_action("admin_init", array($this, "register_settings"));
        add_action("admin_enqueue_scripts", array($this, "enqueue_admin_scripts"));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __("Quality Cost Calculator", "quality-cost-calculator"),
            __("QCC Settings", "quality-cost-calculator"),
            "manage_options",
            "quality-cost-calculator",
            array($this, "render_admin_page")
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting("qcc_settings", "qcc_default_language");
        register_setting("qcc_settings", "qcc_default_currency");
        register_setting("qcc_settings", "qcc_default_unit");
        register_setting("qcc_settings", "qcc_cache_enabled");
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, "quality-cost-calculator") !== false) {
            wp_enqueue_style("qcc-admin", QCC_PLUGIN_URL . "assets/admin.css", array(), QCC_PLUGIN_VERSION);
        }
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e("Quality Cost Calculator Settings", "quality-cost-calculator"); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields("qcc_settings"); ?>
                <?php do_settings_sections("qcc_settings"); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e("Default Language", "quality-cost-calculator"); ?></th>
                        <td>
                            <select name="qcc_default_language">
                                <option value="en" <?php selected(get_option("qcc_default_language"), "en"); ?>>English</option>
                                <option value="de" <?php selected(get_option("qcc_default_language"), "de"); ?>>Deutsch</option>
                                <option value="fr" <?php selected(get_option("qcc_default_language"), "fr"); ?>>Français</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e("Default Currency", "quality-cost-calculator"); ?></th>
                        <td>
                            <select name="qcc_default_currency">
                                <option value="EUR" <?php selected(get_option("qcc_default_currency"), "EUR"); ?>>Euro (€)</option>
                                <option value="USD" <?php selected(get_option("qcc_default_currency"), "USD"); ?>>US Dollar ($)</option>
                                <option value="CNY" <?php selected(get_option("qcc_default_currency"), "CNY"); ?>>Chinese Yuan (¥)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}';
    }
    
    /**
     * Assets class template
     */
    private function get_assets_class_template() {
        return '<?php
/**
 * QCC Assets - Auto-generated Asset Management
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class QCC_Assets {
    
    /**
     * Initialize asset hooks
     */
    public function init() {
        add_action("wp_enqueue_scripts", array($this, "enqueue_frontend_assets"));
        add_action("admin_enqueue_scripts", array($this, "enqueue_admin_assets"));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        global $post;
        
        // Only load on pages with shortcode
        if ($post && has_shortcode($post->post_content, "quality_cost_calculator")) {
            wp_enqueue_style(
                "qcc-frontend",
                QCC_PLUGIN_URL . "assets/css/qcc-frontend.css",
                array(),
                QCC_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                "qcc-frontend",
                QCC_PLUGIN_URL . "assets/js/qcc-frontend.js",
                array("jquery"),
                QCC_PLUGIN_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script("qcc-frontend", "qcc_ajax", array(
                "ajax_url" => admin_url("admin-ajax.php"),
                "nonce" => wp_create_nonce("qcc_nonce")
            ));
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, "quality-cost-calculator") !== false) {
            wp_enqueue_style(
                "qcc-admin",
                QCC_PLUGIN_URL . "assets/css/qcc-admin.css",
                array(),
                QCC_PLUGIN_VERSION
            );
        }
    }
}';
    }
    
    /**
     * API class template
     */
    private function get_api_class_template() {
        return '<?php
/**
 * QCC API - Auto-generated REST API
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class QCC_API {
    
    /**
     * Initialize API hooks
     */
    public function init() {
        add_action("rest_api_init", array($this, "register_routes"));
        add_action("wp_ajax_qcc_calculate", array($this, "ajax_calculate"));
        add_action("wp_ajax_nopriv_qcc_calculate", array($this, "ajax_calculate"));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route("qcc/v1", "/calculate", array(
            "methods" => "POST",
            "callback" => array($this, "rest_calculate"),
            "permission_callback" => "__return_true"
        ));
    }
    
    /**
     * REST API calculate endpoint
     */
    public function rest_calculate($request) {
        $calculator = new QCC_Calculator();
        $validator = new QCC_Validator();
        
        $input = $request->get_json_params();
        
        // Validate input
        $validation = $validator->validate($input);
        if (is_wp_error($validation)) {
            return new WP_Error("validation_failed", $validation->get_error_message(), array("status" => 400));
        }
        
        // Sanitize input
        $input = $validator->sanitize($input);
        
        // Calculate
        $results = $calculator->calculate($input);
        
        return rest_ensure_response($results);
    }
    
    /**
     * AJAX calculate handler
     */
    public function ajax_calculate() {
        check_ajax_referer("qcc_nonce", "nonce");
        
        $calculator = new QCC_Calculator();
        $validator = new QCC_Validator();
        
        $input = $_POST;
        
        // Validate and sanitize
        $validation = $validator->validate($input);
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        $input = $validator->sanitize($input);
        $results = $calculator->calculate($input);
        
        wp_send_json_success($results);
    }
}';
    }
    
    /**
     * Calculator template
     */
    private function get_calculator_template() {
        return '<?php
/**
 * QCC Calculator Template - Auto-generated
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

// Get current values
$language = $atts["language"] ?? "en";
$currency = $atts["currency"] ?? "EUR";
$unit = $atts["unit"] ?? "1000000";
?>

<div class="qcc-calculator" data-language="<?php echo esc_attr($language); ?>" data-currency="<?php echo esc_attr($currency); ?>">
    <div class="qcc-header">
        <h3><?php esc_html_e("Quality Cost Calculator", "quality-cost-calculator"); ?></h3>
        <p><?php esc_html_e("Calculate Cost of Good Quality (COGQ) and Cost of Poor Quality (COPQ)", "quality-cost-calculator"); ?></p>
    </div>
    
    <form class="qcc-form" id="qcc-calculator-form">
        <div class="qcc-input-section">
            <h4><?php esc_html_e("Input Parameters", "quality-cost-calculator"); ?></h4>
            
            <div class="qcc-field">
                <label for="qcc-revenue"><?php esc_html_e("Revenue", "quality-cost-calculator"); ?></label>
                <input type="number" id="qcc-revenue" name="revenue" value="140" step="0.01" min="0" required>
                <span class="qcc-unit"><?php echo esc_html($currency); ?> <?php echo $unit == "1000000" ? "M" : "B"; ?></span>
            </div>
            
            <div class="qcc-field">
                <label for="qcc-quality-percentage"><?php esc_html_e("Quality Cost Basis (% of Revenue)", "quality-cost-calculator"); ?></label>
                <input type="number" id="qcc-quality-percentage" name="quality_percentage" value="6" step="0.1" min="0" max="100" required>
                <span class="qcc-unit">%</span>
            </div>
            
            <h5><?php esc_html_e("Cost Distribution (Must total 100%)", "quality-cost-calculator"); ?></h5>
            
            <div class="qcc-percentage-group">
                <div class="qcc-field">
                    <label for="qcc-prevention"><?php esc_html_e("Prevention Costs (%)", "quality-cost-calculator"); ?></label>
                    <input type="number" id="qcc-prevention" name="prevention" value="10" step="1" min="0" max="100" required>
                </div>
                
                <div class="qcc-field">
                    <label for="qcc-appraisal"><?php esc_html_e("Appraisal Costs (%)", "quality-cost-calculator"); ?></label>
                    <input type="number" id="qcc-appraisal" name="appraisal" value="20" step="1" min="0" max="100" required>
                </div>
                
                <div class="qcc-field">
                    <label for="qcc-internal-defect"><?php esc_html_e("Internal Defect Costs (%)", "quality-cost-calculator"); ?></label>
                    <input type="number" id="qcc-internal-defect" name="internal_defect" value="30" step="1" min="0" max="100" required>
                </div>
                
                <div class="qcc-field">
                    <label for="qcc-external-defect"><?php esc_html_e("External Defect Costs (%)", "quality-cost-calculator"); ?></label>
                    <input type="number" id="qcc-external-defect" name="external_defect" value="40" step="1" min="0" max="100" required>
                </div>
            </div>
            
            <div id="qcc-percentage-error" class="qcc-error" style="display: none;">
                <?php esc_html_e("Values do not add up to 100%", "quality-cost-calculator"); ?>
            </div>
        </div>
        
        <div class="qcc-results-section">
            <h4><?php esc_html_e("Calculated Results", "quality-cost-calculator"); ?></h4>
            
            <div class="qcc-results-grid">
                <div class="qcc-result-item">
                    <label><?php esc_html_e("Total Quality Cost", "quality-cost-calculator"); ?></label>
                    <span id="qcc-total-quality-cost">0.00</span>
                </div>
                
                <div class="qcc-result-item cogq">
                    <label><?php esc_html_e("COGQ (Prevention + Appraisal)", "quality-cost-calculator"); ?></label>
                    <span id="qcc-cogq">0.00</span>
                </div>
                
                <div class="qcc-result-item copq">
                    <label><?php esc_html_e("COPQ (Internal + External Defects)", "quality-cost-calculator"); ?></label>
                    <span id="qcc-copq">0.00</span>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.qcc-calculator {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
}

.qcc-header {
    text-align: center;
    margin-bottom: 30px;
}

.qcc-field {
    margin-bottom: 15px;
}

.qcc-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.qcc-field input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.qcc-percentage-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.qcc-results-grid {
    display: grid;
    gap: 15px;
}

.qcc-result-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.qcc-error {
    color: #d63638;
    font-weight: 600;
    padding: 10px;
    background: #fff2f2;
    border: 1px solid #d63638;
    border-radius: 4px;
}
</style>';
    }
    
    /**
     * Frontend CSS template
     */
    private function get_frontend_css_template() {
        return \'/* QCC Frontend Styles - Auto-generated */
.qcc-calculator {
    font-family: Arial, sans-serif;
    line-height: 1.6;
}

.qcc-calculator * {
    box-sizing: border-box;
}

.qcc-header {
    border-bottom: 2px solid #0073aa;
    padding-bottom: 20px;
}

.qcc-input-section,
.qcc-results-section {
    margin: 30px 0;
}

.qcc-field input:focus {
    outline: none;
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
}

.qcc-percentage-group .qcc-field input.error {
    border-color: #d63638;
}

@media (max-width: 768px) {
    .qcc-percentage-group {
        grid-template-columns: 1fr;
    }
}
\';
    }
    
    /**
     * Frontend JS template
     */
    private function get_frontend_js_template() {
        return \'// QCC Frontend Script - Auto-generated
(function($) {
    "use strict";
    
    $(document).ready(function() {
        var calculator = {
            init: function() {
                this.bindEvents();
                this.calculate();
            },
            
            bindEvents: function() {
                $(".qcc-calculator input").on("input", this.calculate.bind(this));
            },
            
            calculate: function() {
                var revenue = parseFloat($("#qcc-revenue").val()) || 0;
                var qualityPercentage = parseFloat($("#qcc-quality-percentage").val()) || 0;
                var prevention = parseFloat($("#qcc-prevention").val()) || 0;
                var appraisal = parseFloat($("#qcc-appraisal").val()) || 0;
                var internalDefect = parseFloat($("#qcc-internal-defect").val()) || 0;
                var externalDefect = parseFloat($("#qcc-external-defect").val()) || 0;
                
                // Validate percentages
                var total = prevention + appraisal + internalDefect + externalDefect;
                if (Math.abs(total - 100) > 0.01) {
                    $("#qcc-percentage-error").show();
                    $(".qcc-percentage-group input").addClass("error");
                } else {
                    $("#qcc-percentage-error").hide();
                    $(".qcc-percentage-group input").removeClass("error");
                }
                
                // Calculate costs
                var totalQualityCost = (revenue * qualityPercentage) / 100;
                var cogq = (totalQualityCost * (prevention + appraisal)) / 100;
                var copq = (totalQualityCost * (internalDefect + externalDefect)) / 100;
                
                // Update display
                $("#qcc-total-quality-cost").text(totalQualityCost.toFixed(2));
                $("#qcc-cogq").text(cogq.toFixed(2));
                $("#qcc-copq").text(copq.toFixed(2));
            }
        };
        
        calculator.init();
    });
})(jQuery);
\';
    }
    
    /**
     * Config file template
     */
    private function get_config_template() {
        return '<?php
/**
 * QCC Configuration - Auto-generated
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

return array(
    "plugin_name" => "Quality Cost Calculator",
    "plugin_version" => QCC_PLUGIN_VERSION,
    "text_domain" => QCC_TEXT_DOMAIN,
    
    "default_settings" => array(
        "language" => "en",
        "currency" => "EUR",
        "unit" => "1000000",
        "cache_enabled" => true
    ),
    
    "supported_languages" => array(
        "en" => "English",
        "de" => "Deutsch",
        "fr" => "Français",
        "es" => "Español",
        "zh" => "中文"
    ),
    
    "supported_currencies" => array(
        "EUR" => array("symbol" => "€", "name" => "Euro"),
        "USD" => array("symbol" => "$", "name" => "US Dollar"),
        "CNY" => array("symbol" => "¥", "name" => "Chinese Yuan")
    ),
    
    "calculation_defaults" => array(
        "revenue" => 140,
        "quality_percentage" => 6,
        "prevention" => 10,
        "appraisal" => 20,
        "internal_defect" => 30,
        "external_defect" => 40
    )
);';
    }
    
    /**
     * Generic PHP template
     */
    private function get_php_template($file_path) {
        $class_name = $this->extract_class_name($file_path);
        return "<?php
/**
 * Auto-generated PHP file: {$file_path}
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class {$class_name} {
    
    public function __construct() {
        // Auto-generated constructor
    }
    
    public function init() {
        // Auto-generated initialization
    }
}";
    }
    
    /**
     * Generic CSS template
     */
    private function get_css_template($file_path) {
        return "/* Auto-generated CSS file: {$file_path} */

.qcc-auto-generated {
    /* Auto-generated styles */
    font-family: Arial, sans-serif;
}";
    }
    
    /**
     * Generic JS template
     */
    private function get_js_template($file_path) {
        return "// Auto-generated JS file: {$file_path}

(function() {
    'use strict';
    
    // Auto-generated JavaScript
    console.log('QCC: Auto-generated script loaded');
})();";
    }
    
    /**
     * Generic template
     */
    private function get_generic_template($file_path) {
        return "# Auto-generated file: {$file_path}

This file was automatically generated by QCC Auto-Setup system.
Generated on: " . date('Y-m-d H:i:s');
    }
    
    /**
     * Extract class name from file path
     */
    private function extract_class_name($file_path) {
        $filename = basename($file_path, '.php');
        
        // Convert class-qcc-example.php to QCC_Example
        if (strpos($filename, 'class-qcc-') === 0) {
            $name = substr($filename, 10); // Remove 'class-qcc-'
            $parts = explode('-', $name);
            $class_parts = array_map('ucfirst', $parts);
            return 'QCC_' . implode('_', $class_parts);
        }
        
        // Convert filename to class name
        $parts = explode('-', $filename);
        $class_parts = array_map('ucfirst', $parts);
        return 'QCC_' . implode('_', $class_parts);
    }
    
    /**
     * Show admin notices for created files
     */
    public function show_creation_notices() {
        if (!empty($this->created_files) && current_user_can('administrator')) {
            $count = count($this->created_files);
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Quality Cost Calculator:</strong> Auto-Setup created ' . $count . ' missing files.</p>';
            echo '<details><summary>Show created files</summary><ul>';
            foreach ($this->created_files as $file) {
                echo '<li><code>' . esc_html($file) . '</code></li>';
            }
            echo '</ul></details>';
            echo '</div>';
        }
    }
    
    /**
     * Get created files log
     * 
     * @return array List of created files
     */
    public function get_created_files() {
        return $this->created_files;
    }
    
    /**
     * Force recreation of specific file
     * 
     * @param string $file File path
     * @param string $template_key Template key
     * @return bool Success status
     */
    public function force_recreate_file($file, $template_key) {
        $file_path = QCC_PLUGIN_PATH . $file;
        
        // Remove existing file
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Create new file
        $this->create_if_missing($file, $template_key);
        
        return file_exists($file_path);
    }
    
    /**
     * Get system status for auto-setup
     * 
     * @return array Status information
     */
    public function get_system_status() {
        $status = array(
            'initialized' => self::$initialized,
            'required_files_total' => count($this->required_files),
            'created_files_count' => count($this->created_files),
            'missing_files' => array(),
            'writable_directories' => array()
        );
        
        // Check missing files
        foreach ($this->required_files as $file => $template_key) {
            $file_path = QCC_PLUGIN_PATH . $file;
            if (!file_exists($file_path)) {
                $status['missing_files'][] = $file;
            }
        }
        
        // Check writable directories
        $directories = array('includes', 'templates', 'assets', 'assets/css', 'assets/js');
        foreach ($directories as $dir) {
            $dir_path = QCC_PLUGIN_PATH . $dir;
            $status['writable_directories'][$dir] = is_writable($dir_path);
        }
        
        return $status;
    }
    
    /**
     * Emergency file creation for critical files
     * 
     * @return bool Success status
     */
    public function emergency_create_critical_files() {
        $critical_files = array(
            'includes/qcc-functions.php',
            'includes/class-qcc-shortcode.php',
            'templates/calculator.php'
        );
        
        $created = 0;
        
        foreach ($critical_files as $file) {
            if (isset($this->required_files[$file])) {
                $template_key = $this->required_files[$file];
                $this->create_if_missing($file, $template_key);
                
                if (file_exists(QCC_PLUGIN_PATH . $file)) {
                    $created++;
                }
            }
        }
        
        if (QCC_DEBUG) {
            error_log("QCC Auto-Setup: Emergency created {$created} critical files");
        }
        
        return $created > 0;
    }
    
    /**
     * Validate file integrity
     * 
     * @param string $file File path
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_file_integrity($file) {
        $file_path = QCC_PLUGIN_PATH . $file;
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_missing', 'File does not exist: ' . $file);
        }
        
        if (!is_readable($file_path)) {
            return new WP_Error('file_unreadable', 'File is not readable: ' . $file);
        }
        
        $content = file_get_contents($file_path);
        
        // Check for PHP syntax errors
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $syntax_check = php_check_syntax($file_path);
            if (!$syntax_check) {
                return new WP_Error('php_syntax_error', 'PHP syntax error in: ' . $file);
            }
        }
        
        // Check minimum content length
        if (strlen($content) < 10) {
            return new WP_Error('file_too_small', 'File appears to be empty or corrupted: ' . $file);
        }
        
        return true;
    }
    
    /**
     * Cleanup auto-generated files (for uninstall)
     * 
     * @return int Number of files removed
     */
    public function cleanup_auto_generated_files() {
        $removed = 0;
        
        foreach ($this->required_files as $file => $template_key) {
            $file_path = QCC_PLUGIN_PATH . $file;
            
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Only remove files that contain auto-generated marker
                if (strpos($content, 'Auto-generated') !== false) {
                    if (unlink($file_path)) {
                        $removed++;
                        
                        if (QCC_DEBUG) {
                            error_log("QCC Auto-Setup: Removed auto-generated file: {$file}");
                        }
                    }
                }
            }
        }
        
        return $removed;
    }
    
    /**
     * Get debug information
     * 
     * @return array Debug data
     */
    public function get_debug_info() {
        return array(
            'class' => __CLASS__,
            'initialized' => self::$initialized,
            'required_files_count' => count($this->required_files),
            'templates_loaded' => count($this->templates),
            'created_files' => $this->created_files,
            'system_status' => $this->get_system_status(),
            'memory_usage' => memory_get_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        );
    }
}
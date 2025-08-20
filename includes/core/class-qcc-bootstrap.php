<?php
/**
 * QCC Bootstrap - Bereinigt ohne doppelte Klassen
 * 
 * ERSETZEN: wp-content/plugins/quality-cost-calculator/includes/core/class-qcc-bootstrap.php
 * 
 * @package QualityCostCalculator
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_Bootstrap 
{
    private static $instance = null;
    private $container = null;
    private $initialized = false;
    
    public static function get_instance() 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function initialize() 
    {
        $instance = self::get_instance();
        return $instance->init();
    }
    
    private function init() 
    {
        if ($this->initialized) {
            return true;
        }
        
        try {
            // Note: Service Container wird von quality-cost-calculator.php initialisiert
            // Hier nur noch Hooks und Shortcodes registrieren
            $this->setup_hooks();
            $this->register_shortcodes();
            
            $this->initialized = true;
            
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC Bootstrap: Initialisierung erfolgreich');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('QCC Bootstrap Error: ' . $e->getMessage());
            return $this->emergency_fallback();
        }
    }
    
    private function setup_hooks() 
    {
        if (!is_admin()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        }
        
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_menu', array($this, 'register_admin_menu'));
        }
        
        add_action('wp_ajax_qcc_calculate', array($this, 'handle_ajax_calculation'));
        add_action('wp_ajax_nopriv_qcc_calculate', array($this, 'handle_ajax_calculation'));
    }
    
    private function register_shortcodes() 
    {
        // Die Shortcodes werden vom Service Container Setup über QCC_Shortcode registriert
        // Hier nur als Fallback falls Service Container nicht verfügbar ist
        
        if (!shortcode_exists('quality_cost_calculator')) {
            add_shortcode('quality_cost_calculator', array($this, 'render_shortcode_fallback'));
            add_shortcode('qcc_calculator', array($this, 'render_shortcode_fallback'));
            add_shortcode('cost_quality_calculator', array($this, 'render_shortcode_fallback'));
            
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC Bootstrap: Fallback shortcodes registered');
            }
        }
    }
    
    public function render_shortcode_fallback($atts = array(), $content = '') 
    {
        // Versuche Service Container zu nutzen
        try {
            if (class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized()) {
                $container = QCC_Service_Container_Setup::get_container();
                $shortcode_handler = $container->get('shortcode_handler');
                
                if ($shortcode_handler && method_exists($shortcode_handler, 'handle_shortcode')) {
                    return $shortcode_handler->handle_shortcode($atts, $content);
                }
            }
        } catch (Exception $e) {
            if (defined('QCC_DEBUG') && QCC_DEBUG) {
                error_log('QCC Bootstrap Shortcode Fallback Error: ' . $e->getMessage());
            }
        }
        
        // Letzter Fallback: Basic Calculator
        return $this->render_basic_calculator($atts);
    }
    
    /**
     * Render basic calculator as ultimate fallback
     */
    private function render_basic_calculator($atts) 
    {
        $atts = shortcode_atts(array(
            'currency' => 'EUR',
            'language' => 'en'
        ), $atts);
        
        $calculator_id = 'qcc-calculator-' . uniqid();
        
        ob_start();
        ?>
        <div class="qcc-calculator-basic" id="<?php echo esc_attr($calculator_id); ?>">
            <style>
            .qcc-calculator-basic {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .qcc-calculator-basic h3 {
                margin-top: 0;
                color: #333;
                text-align: center;
            }
            .qcc-input-group {
                margin-bottom: 15px;
            }
            .qcc-input-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: #555;
            }
            .qcc-input-group input {
                width: 100%;
                padding: 10px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 16px;
            }
            .qcc-section {
                margin: 20px 0;
                padding: 15px;
                border: 1px solid #eee;
                border-radius: 6px;
                background: #f9f9f9;
            }
            .qcc-section h4 {
                margin-top: 0;
                color: #444;
            }
            .qcc-cogq-section {
                border-left: 4px solid #28a745;
            }
            .qcc-copq-section {
                border-left: 4px solid #dc3545;
            }
            .qcc-actions {
                text-align: center;
                margin: 20px 0;
            }
            .qcc-btn {
                padding: 12px 24px;
                margin: 0 10px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.3s;
            }
            .qcc-btn-primary {
                background: #007bff;
                color: white;
            }
            .qcc-btn-primary:hover {
                background: #0056b3;
            }
            .qcc-btn-secondary {
                background: #6c757d;
                color: white;
            }
            .qcc-btn-secondary:hover {
                background: #545b62;
            }
            .qcc-results {
                margin-top: 20px;
                padding: 20px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 6px;
                display: none;
            }
            .qcc-result-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .qcc-result-total {
                display: flex;
                justify-content: space-between;
                padding: 12px 0;
                font-weight: bold;
                font-size: 18px;
                border-top: 2px solid #333;
                margin-top: 10px;
            }
            </style>
            
            <h3>Quality Cost Calculator</h3>
            <p style="text-align: center; color: #666;">Professional COGQ/COPQ Analysis</p>
            
            <div class="qcc-section">
                <h4>Basic Parameters</h4>
                <div class="qcc-input-group">
                    <label for="production-volume">Production Volume:</label>
                    <input type="number" id="production-volume" min="0" step="1" placeholder="10000">
                </div>
                <div class="qcc-input-group">
                    <label for="unit-price">Unit Price (<?php echo esc_html($atts['currency']); ?>):</label>
                    <input type="number" id="unit-price" min="0" step="0.01" placeholder="25.00">
                </div>
            </div>
            
            <div class="qcc-section qcc-cogq-section">
                <h4 style="color: #28a745;">Cost of Good Quality (COGQ)</h4>
                <div class="qcc-input-group">
                    <label for="prevention-cost">Prevention Cost (<?php echo esc_html($atts['currency']); ?>):</label>
                    <input type="number" id="prevention-cost" min="0" step="0.01" placeholder="5000">
                </div>
                <div class="qcc-input-group">
                    <label for="appraisal-cost">Appraisal Cost (<?php echo esc_html($atts['currency']); ?>):</label>
                    <input type="number" id="appraisal-cost" min="0" step="0.01" placeholder="3000">
                </div>
            </div>
            
            <div class="qcc-section qcc-copq-section">
                <h4 style="color: #dc3545;">Cost of Poor Quality (COPQ)</h4>
                <div class="qcc-input-group">
                    <label for="internal-failure">Internal Failure Cost (<?php echo esc_html($atts['currency']); ?>):</label>
                    <input type="number" id="internal-failure" min="0" step="0.01" placeholder="2000">
                </div>
                <div class="qcc-input-group">
                    <label for="external-failure">External Failure Cost (<?php echo esc_html($atts['currency']); ?>):</label>
                    <input type="number" id="external-failure" min="0" step="0.01" placeholder="1500">
                </div>
            </div>
            
            <div class="qcc-actions">
                <button type="button" class="qcc-btn qcc-btn-primary" onclick="qccCalculate('<?php echo esc_js($calculator_id); ?>')">Calculate</button>
                <button type="button" class="qcc-btn qcc-btn-secondary" onclick="qccReset('<?php echo esc_js($calculator_id); ?>')">Reset</button>
            </div>
            
            <div class="qcc-results" id="<?php echo esc_attr($calculator_id); ?>-results">
                <h4>Results</h4>
                <div class="qcc-result-item">
                    <span>Total COGQ:</span>
                    <span id="result-cogq">--</span>
                </div>
                <div class="qcc-result-item">
                    <span>Total COPQ:</span>
                    <span id="result-copq">--</span>
                </div>
                <div class="qcc-result-total">
                    <span>Total Quality Cost:</span>
                    <span id="result-total">--</span>
                </div>
                <div class="qcc-result-item">
                    <span>COGQ Percentage:</span>
                    <span id="result-cogq-percent">--</span>
                </div>
                <div class="qcc-result-item">
                    <span>COPQ Percentage:</span>
                    <span id="result-copq-percent">--</span>
                </div>
            </div>
        </div>
        
        <script>
        function qccCalculate(calculatorId) {
            try {
                // Get input values
                const container = document.getElementById(calculatorId);
                const volume = parseFloat(container.querySelector('#production-volume').value) || 0;
                const unitPrice = parseFloat(container.querySelector('#unit-price').value) || 0;
                const prevention = parseFloat(container.querySelector('#prevention-cost').value) || 0;
                const appraisal = parseFloat(container.querySelector('#appraisal-cost').value) || 0;
                const internalFailure = parseFloat(container.querySelector('#internal-failure').value) || 0;
                const externalFailure = parseFloat(container.querySelector('#external-failure').value) || 0;
                
                // Validate inputs
                if (volume <= 0 || unitPrice <= 0) {
                    alert('Please enter valid production volume and unit price.');
                    return;
                }
                
                // Calculate
                const totalCOGQ = prevention + appraisal;
                const totalCOPQ = internalFailure + externalFailure;
                const totalQualityCost = totalCOGQ + totalCOPQ;
                const cogqPercent = totalQualityCost > 0 ? (totalCOGQ / totalQualityCost * 100) : 0;
                const copqPercent = totalQualityCost > 0 ? (totalCOPQ / totalQualityCost * 100) : 0;
                
                // Format currency
                const formatCurrency = (value) => '<?php echo esc_js($atts['currency']); ?> ' + value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const formatPercent = (value) => value.toFixed(1) + '%';
                
                // Update results
                container.querySelector('#result-cogq').textContent = formatCurrency(totalCOGQ);
                container.querySelector('#result-copq').textContent = formatCurrency(totalCOPQ);
                container.querySelector('#result-total').textContent = formatCurrency(totalQualityCost);
                container.querySelector('#result-cogq-percent').textContent = formatPercent(cogqPercent);
                container.querySelector('#result-copq-percent').textContent = formatPercent(copqPercent);
                
                // Show results
                document.getElementById(calculatorId + '-results').style.display = 'block';
                
            } catch (error) {
                console.error('Calculation error:', error);
                alert('Calculation error. Please check your input.');
            }
        }
        
        function qccReset(calculatorId) {
            const container = document.getElementById(calculatorId);
            const inputs = container.querySelectorAll('input[type="number"]');
            inputs.forEach(input => input.value = '');
            document.getElementById(calculatorId + '-results').style.display = 'none';
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function handle_ajax_calculation() 
    {
        try {
            if (!wp_verify_nonce($_POST['nonce'], 'qcc_nonce')) {
                wp_die('Security check failed');
            }
            
            // Try to use service container calculator
            if (class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized()) {
                $container = QCC_Service_Container_Setup::get_container();
                $calculator = $container->get('calculation_engine');
                
                if ($calculator && method_exists($calculator, 'calculate')) {
                    $result = $calculator->calculate($_POST);
                    wp_send_json_success($result);
                    return;
                }
            }
            
            // Fallback calculation
            $this->handle_basic_calculation($_POST);
            
        } catch (Exception $e) {
            wp_send_json_error('Calculation failed: ' . $e->getMessage());
        }
    }
    
    private function handle_basic_calculation($data) 
    {
        $volume = floatval($data['production_volume'] ?? 0);
        $unit_price = floatval($data['unit_price'] ?? 0);
        $prevention = floatval($data['prevention_cost'] ?? 0);
        $appraisal = floatval($data['appraisal_cost'] ?? 0);
        $internal_failure = floatval($data['internal_failure'] ?? 0);
        $external_failure = floatval($data['external_failure'] ?? 0);
        
        $total_cogq = $prevention + $appraisal;
        $total_copq = $internal_failure + $external_failure;
        $total_quality_cost = $total_cogq + $total_copq;
        
        $result = array(
            'total_cogq' => $total_cogq,
            'total_copq' => $total_copq,
            'total_quality_cost' => $total_quality_cost,
            'cogq_percentage' => $total_quality_cost > 0 ? ($total_cogq / $total_quality_cost * 100) : 0,
            'copq_percentage' => $total_quality_cost > 0 ? ($total_copq / $total_quality_cost * 100) : 0
        );
        
        wp_send_json_success($result);
    }
    
    public function enqueue_frontend_assets() 
    {
        if ($this->should_load_frontend_assets()) {
            wp_enqueue_script('jquery');
        }
    }
    
    public function enqueue_admin_assets() 
    {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'quality-cost-calculator') !== false) {
            wp_enqueue_script('jquery');
        }
    }
    
    public function register_admin_menu() 
    {
        add_menu_page(
            'Quality Cost Calculator',
            'QCC Settings',
            'manage_options',
            'quality-cost-calculator',
            array($this, 'render_admin_page'),
            'dashicons-calculator',
            30
        );
    }
    
    public function render_admin_page() 
    {
        echo '<div class="wrap">';
        echo '<h1>Quality Cost Calculator Settings</h1>';
        echo '<p>Settings interface would go here.</p>';
        
        // Show system status if debug mode
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            $this->render_system_status();
        }
        
        echo '</div>';
    }
    
    private function render_system_status() 
    {
        echo '<h2>System Status</h2>';
        
        $bootstrap_status = $this->initialized ? '✅ Active' : '❌ Failed';
        $service_container_status = class_exists('QCC_Service_Container_Setup') && QCC_Service_Container_Setup::is_initialized() ? '✅ Active' : '❌ Not Available';
        
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Component</th><th>Status</th><th>Details</th></tr></thead>';
        echo '<tbody>';
        echo '<tr><td>Bootstrap System</td><td>' . $bootstrap_status . '</td><td>Main plugin initialization</td></tr>';
        echo '<tr><td>Service Container</td><td>' . $service_container_status . '</td><td>Modern service architecture</td></tr>';
        echo '<tr><td>Plugin Version</td><td>✅ ' . (defined('QCC_PLUGIN_VERSION') ? QCC_PLUGIN_VERSION : '2.0.0') . '</td><td>Current version</td></tr>';
        echo '</tbody>';
        echo '</table>';
    }
    
    private function should_load_frontend_assets() 
    {
        global $post;
        
        if (is_a($post, 'WP_Post')) {
            return has_shortcode($post->post_content, 'quality_cost_calculator') ||
                   has_shortcode($post->post_content, 'qcc_calculator') ||
                   has_shortcode($post->post_content, 'cost_quality_calculator');
        }
        
        return false;
    }
    
    private function emergency_fallback() 
    {
        add_shortcode('quality_cost_calculator', array($this, 'render_emergency_shortcode'));
        add_shortcode('qcc_calculator', array($this, 'render_emergency_shortcode'));
        add_shortcode('cost_quality_calculator', array($this, 'render_emergency_shortcode'));
        
        return false;
    }
    
    public function render_emergency_shortcode($atts) 
    {
        return '<div class="qcc-emergency" style="padding: 20px; border: 1px solid #ff6b6b; background: #ffe6e6; color: #d63031; border-radius: 5px;">
            <strong>Quality Cost Calculator:</strong> Bootstrap initialization failed.
            <br><small>Running in emergency mode. Please check system configuration.</small>
        </div>';
    }
    
    // Plugin lifecycle methods
    public static function activate() 
    {
        // Activation tasks
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin activated');
        }
    }
    
    public static function deactivate() 
    {
        // Deactivation tasks
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin deactivated');
        }
    }
    
    public static function uninstall() 
    {
        // Uninstall cleanup
        if (defined('QCC_DEBUG') && QCC_DEBUG) {
            error_log('QCC Bootstrap: Plugin uninstalled');
        }
    }
}
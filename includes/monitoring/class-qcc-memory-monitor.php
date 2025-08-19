<?php
/**
 * QCC Memory Monitor - Simplified Version
 * 
 * Simple memory usage tracking and monitoring for QCC plugin operations.
 * Tracks memory consumption, peak usage, and potential memory issues.
 * 
 * @package    QCC
 * @subpackage Monitoring
 * @since      3.0.0
 * @author     Quality Cost Calculator Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Memory Monitor Class - Simplified
 * 
 * @since 3.0.0
 */
class QCC_Memory_Monitor {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Memory_Monitor
     */
    private static $instance = null;
    
    /**
     * Memory snapshots
     * 
     * @since 3.0.0
     * @var array
     */
    private $snapshots = array();
    
    /**
     * Configuration
     * 
     * @since 3.0.0
     * @var array
     */
    private $config = array();
    
    /**
     * Memory statistics
     * 
     * @since 3.0.0
     * @var array
     */
    private $stats = array();
    
    /**
     * Debug mode
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Monitoring enabled
     * 
     * @since 3.0.0
     * @var bool
     */
    private $monitoring_enabled = false;
    
    /**
     * Request start memory
     * 
     * @since 3.0.0
     * @var int
     */
    private $request_start_memory = 0;
    
    /**
     * Memory thresholds
     * 
     * @since 3.0.0
     * @var array
     */
    private $thresholds = array();
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->request_start_memory = memory_get_usage();
        
        $this->init_config();
        $this->init_thresholds();
        $this->init_stats();
        $this->register_hooks();
        $this->take_snapshot('request_start');
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Memory_Monitor
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize configuration
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_config() {
        $this->config = array(
            'enabled' => $this->debug_mode || (defined('QCC_MEMORY_MONITORING') && QCC_MEMORY_MONITORING),
            'max_snapshots' => 50,
            'alert_on_threshold' => true,
            'log_to_file' => false,
            'log_file' => WP_CONTENT_DIR . '/qcc-memory.log',
            'track_real_memory' => true
        );
        
        $this->monitoring_enabled = $this->config['enabled'];
    }
    
    /**
     * Initialize memory thresholds
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_thresholds() {
        $php_memory_limit = $this->parse_memory_limit(ini_get('memory_limit'));
        
        $this->thresholds = array(
            'warning' => $php_memory_limit * 0.7,  // 70% of memory limit
            'critical' => $php_memory_limit * 0.85, // 85% of memory limit
            'growth_warning' => 10 * 1024 * 1024,   // 10MB growth in single operation
            'growth_critical' => 25 * 1024 * 1024   // 25MB growth in single operation
        );
    }
    
    /**
     * Initialize statistics
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_stats() {
        $this->stats = array(
            'total_snapshots' => 0,
            'max_memory_usage' => 0,
            'max_peak_memory' => 0,
            'largest_growth' => 0,
            'memory_warnings' => 0,
            'memory_criticals' => 0,
            'php_memory_limit' => $this->parse_memory_limit(ini_get('memory_limit')),
            'start_memory' => $this->request_start_memory
        );
    }
    
    /**
     * Register WordPress hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function register_hooks() {
        if (!$this->monitoring_enabled) {
            return;
        }
        
        // Core WordPress hooks
        add_action('init', array($this, 'snapshot_init'));
        add_action('wp_footer', array($this, 'snapshot_footer'));
        add_action('admin_footer', array($this, 'snapshot_footer'));
        add_action('shutdown', array($this, 'finalize_monitoring'));
        
        // QCC specific hooks
        add_action('qcc_calculation_start', array($this, 'snapshot_calculation_start'));
        add_action('qcc_calculation_end', array($this, 'snapshot_calculation_end'));
        add_action('qcc_before_render', array($this, 'snapshot_before_render'));
        add_action('qcc_after_render', array($this, 'snapshot_after_render'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        
        // Show memory bar in debug mode
        if ($this->debug_mode) {
            add_action('wp_footer', array($this, 'show_memory_bar'), 999);
            add_action('admin_footer', array($this, 'show_memory_bar'), 999);
        }
    }
    
    /**
     * Take memory snapshot
     * 
     * @since 3.0.0
     * @param string $label Snapshot label
     * @param array  $context Additional context
     * @return array Snapshot data
     */
    public function take_snapshot($label, $context = array()) {
        if (!$this->monitoring_enabled) {
            return array();
        }
        
        $current_memory = memory_get_usage();
        $peak_memory = memory_get_peak_usage();
        $real_memory = $this->config['track_real_memory'] ? memory_get_usage(true) : 0;
        $real_peak = $this->config['track_real_memory'] ? memory_get_peak_usage(true) : 0;
        
        $snapshot = array(
            'label' => $label,
            'timestamp' => microtime(true),
            'memory_usage' => $current_memory,
            'peak_memory' => $peak_memory,
            'real_memory' => $real_memory,
            'real_peak' => $real_peak,
            'memory_growth' => 0,
            'context' => $context
        );
        
        // Calculate memory growth from previous snapshot
        if (!empty($this->snapshots)) {
            $last_snapshot = end($this->snapshots);
            $snapshot['memory_growth'] = $current_memory - $last_snapshot['memory_usage'];
        } else {
            $snapshot['memory_growth'] = $current_memory - $this->request_start_memory;
        }
        
        // Add to snapshots
        $this->snapshots[] = $snapshot;
        
        // Limit snapshots
        if (count($this->snapshots) > $this->config['max_snapshots']) {
            array_shift($this->snapshots);
        }
        
        // Update statistics
        $this->update_stats($snapshot);
        
        // Check thresholds
        if ($this->config['alert_on_threshold']) {
            $this->check_thresholds($snapshot);
        }
        
        // Log to file
        if ($this->config['log_to_file']) {
            $this->log_to_file($snapshot);
        }
        
        // Debug output
        if ($this->debug_mode) {
            error_log(sprintf(
                "QCC Memory Snapshot [%s]: %s (Growth: %s)",
                $label,
                size_format($current_memory),
                $snapshot['memory_growth'] >= 0 ? '+' . size_format($snapshot['memory_growth']) : size_format($snapshot['memory_growth'])
            ));
        }
        
        return $snapshot;
    }
    
    /**
     * Update statistics
     * 
     * @since 3.0.0
     * @param array $snapshot Snapshot data
     * @return void
     */
    private function update_stats($snapshot) {
        $this->stats['total_snapshots']++;
        
        if ($snapshot['memory_usage'] > $this->stats['max_memory_usage']) {
            $this->stats['max_memory_usage'] = $snapshot['memory_usage'];
        }
        
        if ($snapshot['peak_memory'] > $this->stats['max_peak_memory']) {
            $this->stats['max_peak_memory'] = $snapshot['peak_memory'];
        }
        
        if (abs($snapshot['memory_growth']) > $this->stats['largest_growth']) {
            $this->stats['largest_growth'] = abs($snapshot['memory_growth']);
        }
    }
    
    /**
     * Check memory thresholds
     * 
     * @since 3.0.0
     * @param array $snapshot Snapshot data
     * @return void
     */
    private function check_thresholds($snapshot) {
        $memory = $snapshot['memory_usage'];
        $growth = abs($snapshot['memory_growth']);
        
        // Check memory usage thresholds
        if ($memory > $this->thresholds['critical']) {
            $this->stats['memory_criticals']++;
            $this->trigger_alert('critical', 'Memory usage critical', $snapshot);
        } elseif ($memory > $this->thresholds['warning']) {
            $this->stats['memory_warnings']++;
            $this->trigger_alert('warning', 'Memory usage high', $snapshot);
        }
        
        // Check memory growth thresholds
        if ($growth > $this->thresholds['growth_critical']) {
            $this->trigger_alert('critical', 'Excessive memory growth', $snapshot);
        } elseif ($growth > $this->thresholds['growth_warning']) {
            $this->trigger_alert('warning', 'High memory growth', $snapshot);
        }
    }
    
    /**
     * Trigger memory alert
     * 
     * @since 3.0.0
     * @param string $level Alert level
     * @param string $message Alert message
     * @param array  $snapshot Snapshot data
     * @return void
     */
    private function trigger_alert($level, $message, $snapshot) {
        $alert_data = array(
            'level' => $level,
            'message' => $message,
            'memory_usage' => $snapshot['memory_usage'],
            'memory_growth' => $snapshot['memory_growth'],
            'memory_limit' => $this->stats['php_memory_limit'],
            'label' => $snapshot['label'],
            'timestamp' => $snapshot['timestamp']
        );
        
        do_action('qcc_memory_alert', $alert_data);
        
        if ($this->debug_mode) {
            error_log(sprintf(
                "QCC Memory Alert [%s]: %s - %s (Growth: %s)",
                strtoupper($level),
                $message,
                size_format($snapshot['memory_usage']),
                size_format($snapshot['memory_growth'])
            ));
        }
    }
    
    /**
     * Log to file
     * 
     * @since 3.0.0
     * @param array $snapshot Snapshot data
     * @return bool True on success
     */
    private function log_to_file($snapshot) {
        $log_entry = sprintf(
            "[%s] %s: %s (Peak: %s, Growth: %s)\n",
            date('Y-m-d H:i:s', $snapshot['timestamp']),
            $snapshot['label'],
            size_format($snapshot['memory_usage']),
            size_format($snapshot['peak_memory']),
            $snapshot['memory_growth'] >= 0 ? '+' . size_format($snapshot['memory_growth']) : size_format($snapshot['memory_growth'])
        );
        
        return file_put_contents($this->config['log_file'], $log_entry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @since 3.0.0
     * @param string $limit Memory limit string
     * @return int Memory limit in bytes
     */
    private function parse_memory_limit($limit) {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }
        
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $number = (int) $limit;
        
        switch ($last) {
            case 'g':
                $number *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $number *= 1024 * 1024;
                break;
            case 'k':
                $number *= 1024;
                break;
        }
        
        return $number;
    }
    
    /**
     * Get memory usage percentage
     * 
     * @since 3.0.0
     * @param int $memory_usage Memory usage in bytes
     * @return float Percentage
     */
    public function get_memory_percentage($memory_usage = null) {
        if ($memory_usage === null) {
            $memory_usage = memory_get_usage();
        }
        
        if ($this->stats['php_memory_limit'] === PHP_INT_MAX) {
            return 0; // Unlimited memory
        }
        
        return round(($memory_usage / $this->stats['php_memory_limit']) * 100, 1);
    }
    
    /**
     * Predefined snapshot methods
     */
    public function snapshot_init() {
        $this->take_snapshot('wp_init');
    }
    
    public function snapshot_footer() {
        $this->take_snapshot('footer');
    }
    
    public function snapshot_calculation_start() {
        $this->take_snapshot('calculation_start');
    }
    
    public function snapshot_calculation_end() {
        $this->take_snapshot('calculation_end');
    }
    
    public function snapshot_before_render() {
        $this->take_snapshot('before_render');
    }
    
    public function snapshot_after_render() {
        $this->take_snapshot('after_render');
    }
    
    /**
     * Show memory bar in debug mode
     * 
     * @since 3.0.0
     * @return void
     */
    public function show_memory_bar() {
        if (!$this->debug_mode || !current_user_can('manage_options')) {
            return;
        }
        
        $current_memory = memory_get_usage();
        $peak_memory = memory_get_peak_usage();
        $percentage = $this->get_memory_percentage($current_memory);
        $peak_percentage = $this->get_memory_percentage($peak_memory);
        
        $color = '#10b981'; // Green
        if ($percentage > 70) {
            $color = '#f59e0b'; // Orange
        }
        if ($percentage > 85) {
            $color = '#ef4444'; // Red
        }
        
        ?>
        <div id="qcc-memory-bar" style="
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: #1f2937; 
            color: #fff; 
            padding: 8px 15px; 
            font-size: 12px; 
            z-index: 99998;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
        ">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>
                    💾 Memory: 
                    <strong style="color: <?php echo $color; ?>">
                        <?php echo size_format($current_memory); ?>
                    </strong>
                    (<?php echo $percentage; ?>%) | 
                    Peak: <strong><?php echo size_format($peak_memory); ?></strong>
                    (<?php echo $peak_percentage; ?>%) | 
                    Snapshots: <?php echo count($this->snapshots); ?>
                </span>
                <button onclick="this.parentElement.parentElement.style.display='none'" 
                        style="background:none;border:none;color:#fff;cursor:pointer;">✕</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Finalize monitoring
     * 
     * @since 3.0.0
     * @return void
     */
    public function finalize_monitoring() {
        if (!$this->monitoring_enabled) {
            return;
        }
        
        $this->take_snapshot('request_end');
        
        if ($this->debug_mode) {
            $total_growth = memory_get_usage() - $this->request_start_memory;
            error_log(sprintf(
                "QCC Memory Monitor Summary - Total Growth: %s, Peak: %s, Snapshots: %d",
                size_format($total_growth),
                size_format(memory_get_peak_usage()),
                count($this->snapshots)
            ));
        }
    }
    
    /**
     * Get all snapshots
     * 
     * @since 3.0.0
     * @return array Snapshots
     */
    public function get_snapshots() {
        return $this->snapshots;
    }
    
    /**
     * Get statistics
     * 
     * @since 3.0.0
     * @return array Statistics
     */
    public function get_statistics() {
        return array_merge($this->stats, array(
            'current_memory' => memory_get_usage(),
            'current_peak' => memory_get_peak_usage(),
            'current_percentage' => $this->get_memory_percentage(),
            'total_growth' => memory_get_usage() - $this->request_start_memory,
            'snapshots_count' => count($this->snapshots)
        ));
    }
    
    /**
     * Clear all snapshots
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear_snapshots() {
        $this->snapshots = array();
        $this->init_stats();
    }
    
    /**
     * Add admin menu
     * 
     * @since 3.0.0
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'qcc-admin',
            'Memory Monitor',
            'Memory',
            'manage_options',
            'qcc-memory',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Render admin page
     * 
     * @since 3.0.0
     * @return void
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $stats = $this->get_statistics();
        $snapshots = array_slice($this->get_snapshots(), -20); // Last 20 snapshots
        
        ?>
        <div class="wrap">
            <h1>💾 QCC Memory Monitor</h1>
            
            <!-- Status -->
            <div class="notice notice-info">
                <p>
                    <strong>Status:</strong> <?php echo $this->monitoring_enabled ? '✅ Enabled' : '❌ Disabled'; ?> | 
                    <strong>Current Usage:</strong> <?php echo size_format($stats['current_memory']); ?> 
                    (<?php echo $stats['current_percentage']; ?>%) | 
                    <strong>Peak:</strong> <?php echo size_format($stats['current_peak']); ?>
                </p>
            </div>
            
            <!-- Memory Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div class="card" style="padding: 15px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; border-radius: 8px;">
                    <h4 style="margin: 0 0 5px 0;">Current Memory</h4>
                    <span style="font-size: 20px; font-weight: bold;"><?php echo size_format($stats['current_memory']); ?></span>
                    <br><small><?php echo $stats['current_percentage']; ?>% of limit</small>
                </div>
                
                <div class="card" style="padding: 15px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 8px;">
                    <h4 style="margin: 0 0 5px 0;">Peak Memory</h4>
                    <span style="font-size: 20px; font-weight: bold;"><?php echo size_format($stats['current_peak']); ?></span>
                </div>
                
                <div class="card" style="padding: 15px; background: linear-gradient(135deg, #10b981 0%, #047857 100%); color: white; border-radius: 8px;">
                    <h4 style="margin: 0 0 5px 0;">Total Growth</h4>
                    <span style="font-size: 20px; font-weight: bold;"><?php echo size_format($stats['total_growth']); ?></span>
                </div>
                
                <div class="card" style="padding: 15px; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; border-radius: 8px;">
                    <h4 style="margin: 0 0 5px 0;">Memory Limit</h4>
                    <span style="font-size: 20px; font-weight: bold;">
                        <?php echo $stats['php_memory_limit'] === PHP_INT_MAX ? 'Unlimited' : size_format($stats['php_memory_limit']); ?>
                    </span>
                </div>
            </div>
            
            <!-- Alerts Summary -->
            <?php if ($stats['memory_warnings'] > 0 || $stats['memory_criticals'] > 0): ?>
            <div class="notice notice-warning">
                <p>
                    <strong>⚠️ Memory Alerts:</strong> 
                    <?php echo $stats['memory_warnings']; ?> warnings, 
                    <?php echo $stats['memory_criticals']; ?> critical alerts this session.
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Recent Snapshots -->
            <h3>Recent Memory Snapshots</h3>
            <?php if (empty($snapshots)): ?>
                <p>No memory snapshots recorded yet.</p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Memory Usage</th>
                            <th>Peak Memory</th>
                            <th>Memory Growth</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($snapshots as $snapshot): ?>
                            <tr>
                                <td><code><?php echo esc_html($snapshot['label']); ?></code></td>
                                <td>
                                    <?php echo size_format($snapshot['memory_usage']); ?>
                                    <small style="color: #666;">
                                        (<?php echo $this->get_memory_percentage($snapshot['memory_usage']); ?>%)
                                    </small>
                                </td>
                                <td><?php echo size_format($snapshot['peak_memory']); ?></td>
                                <td style="color: <?php echo $snapshot['memory_growth'] >= 0 ? '#10b981' : '#ef4444'; ?>">
                                    <?php 
                                    echo $snapshot['memory_growth'] >= 0 ? '+' : '';
                                    echo size_format($snapshot['memory_growth']); 
                                    ?>
                                </td>
                                <td><?php echo date('H:i:s', $snapshot['timestamp']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Configuration -->
            <h3>Configuration</h3>
            <table class="form-table">
                <tr>
                    <th>Memory Monitoring</th>
                    <td><?php echo $this->monitoring_enabled ? '✅ Enabled' : '❌ Disabled'; ?></td>
                </tr>
                <tr>
                    <th>PHP Memory Limit</th>
                    <td>
                        <?php echo $stats['php_memory_limit'] === PHP_INT_MAX ? 'Unlimited' : size_format($stats['php_memory_limit']); ?>
                        <small>(<?php echo ini_get('memory_limit'); ?>)</small>
                    </td>
                </tr>
                <tr>
                    <th>Warning Threshold</th>
                    <td><?php echo size_format($this->thresholds['warning']); ?> (70%)</td>
                </tr>
                <tr>
                    <th>Critical Threshold</th>
                    <td><?php echo size_format($this->thresholds['critical']); ?> (85%)</td>
                </tr>
                <tr>
                    <th>Max Snapshots</th>
                    <td><?php echo $this->config['max_snapshots']; ?></td>
                </tr>
            </table>
            
            <h4>Enable Memory Monitoring</h4>
            <p>To enable memory monitoring, add this to your <code>wp-config.php</code>:</p>
            <pre style="background: #f1f1f1; padding: 10px; border-radius: 5px;"><code>define('QCC_MEMORY_MONITORING', true);
define('QCC_DEBUG', true); // For memory bar display</code></pre>
        </div>
        <?php
    }
    
    /**
     * Enable monitoring
     * 
     * @since 3.0.0
     * @return void
     */
    public function enable() {
        $this->monitoring_enabled = true;
    }
    
    /**
     * Disable monitoring
     * 
     * @since 3.0.0
     * @return void
     */
    public function disable() {
        $this->monitoring_enabled = false;
    }
    
    /**
     * Check if monitoring is enabled
     * 
     * @since 3.0.0
     * @return bool
     */
    public function is_enabled() {
        return $this->monitoring_enabled;
    }
    
    /**
     * Get current memory usage
     * 
     * @since 3.0.0
     * @return array Memory info
     */
    public function get_current_memory() {
        return array(
            'usage' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
            'real_usage' => memory_get_usage(true),
            'real_peak' => memory_get_peak_usage(true),
            'percentage' => $this->get_memory_percentage(),
            'limit' => $this->stats['php_memory_limit']
        );
    }
}
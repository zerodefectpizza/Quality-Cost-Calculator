<?php
/**
 * QCC Performance Monitor - Minimal Version
 * 
 * Simple performance monitoring for basic execution time and memory tracking.
 * Lightweight implementation focused on essential metrics only.
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
 * QCC Performance Monitor Class - Minimal Version
 * 
 * @since 3.0.0
 */
class QCC_Performance_Monitor {
    
    /**
     * Instance of this class
     * 
     * @since 3.0.0
     * @var QCC_Performance_Monitor
     */
    private static $instance = null;
    
    /**
     * Active timers
     * 
     * @since 3.0.0
     * @var array
     */
    private $timers = array();
    
    /**
     * Performance data
     * 
     * @since 3.0.0
     * @var array
     */
    private $data = array();
    
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
    private $enabled = false;
    
    /**
     * Request start time
     * 
     * @since 3.0.0
     * @var float
     */
    private $start_time = 0;
    
    /**
     * Request start memory
     * 
     * @since 3.0.0
     * @var int
     */
    private $start_memory = 0;
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    private function __construct() {
        $this->debug_mode = defined('QCC_DEBUG') && QCC_DEBUG;
        $this->enabled = $this->debug_mode || (defined('QCC_PERFORMANCE_MONITORING') && QCC_PERFORMANCE_MONITORING);
        $this->start_time = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        $this->start_memory = memory_get_usage();
        
        if ($this->enabled) {
            $this->init_hooks();
        }
    }
    
    /**
     * Get singleton instance
     * 
     * @since 3.0.0
     * @return QCC_Performance_Monitor
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize hooks
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_hooks() {
        add_action('init', array($this, 'start_page_timer'), -999);
        add_action('wp_footer', array($this, 'end_page_timer'), 999);
        add_action('admin_footer', array($this, 'end_page_timer'), 999);
        add_action('shutdown', array($this, 'output_debug_info'), 999);
        
        // QCC specific hooks
        add_action('qcc_calculation_start', array($this, 'start_calculation_timer'));
        add_action('qcc_calculation_end', array($this, 'end_calculation_timer'));
        
        // Admin page
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Start a timer
     * 
     * @since 3.0.0
     * @param string $name Timer name
     * @return void
     */
    public function start_timer($name) {
        if (!$this->enabled) {
            return;
        }
        
        $this->timers[$name] = array(
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage()
        );
    }
    
    /**
     * End a timer
     * 
     * @since 3.0.0
     * @param string $name Timer name
     * @return array|null Timer data
     */
    public function end_timer($name) {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return null;
        }
        
        $timer = $this->timers[$name];
        $end_time = microtime(true);
        $end_memory = memory_get_usage();
        
        $data = array(
            'name' => $name,
            'execution_time' => $end_time - $timer['start_time'],
            'memory_used' => $end_memory - $timer['start_memory'],
            'timestamp' => time()
        );
        
        $this->data[] = $data;
        unset($this->timers[$name]);
        
        if ($this->debug_mode) {
            error_log(sprintf(
                "QCC Performance: %s completed in %.3fs using %s",
                $name,
                $data['execution_time'],
                size_format($data['memory_used'])
            ));
        }
        
        return $data;
    }
    
    /**
     * Start page timer
     * 
     * @since 3.0.0
     * @return void
     */
    public function start_page_timer() {
        $this->start_timer('page_load');
    }
    
    /**
     * End page timer
     * 
     * @since 3.0.0
     * @return void
     */
    public function end_page_timer() {
        $this->end_timer('page_load');
        
        if ($this->debug_mode && current_user_can('manage_options')) {
            $this->show_performance_bar();
        }
    }
    
    /**
     * Start calculation timer
     * 
     * @since 3.0.0
     * @return void
     */
    public function start_calculation_timer() {
        $this->start_timer('calculation');
    }
    
    /**
     * End calculation timer
     * 
     * @since 3.0.0
     * @return void
     */
    public function end_calculation_timer() {
        $this->end_timer('calculation');
    }
    
    /**
     * Get summary statistics
     * 
     * @since 3.0.0
     * @return array Statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $page_time = 0;
        $calc_time = 0;
        
        foreach ($this->data as $entry) {
            if ($entry['name'] === 'page_load') {
                $page_time = $entry['execution_time'];
            } elseif ($entry['name'] === 'calculation') {
                $calc_time = $entry['execution_time'];
            }
        }
        
        return array(
            'page_load_time' => $page_time ?: (microtime(true) - $this->start_time),
            'calculation_time' => $calc_time,
            'memory_usage' => memory_get_usage(),
            'memory_peak' => memory_get_peak_usage(),
            'memory_delta' => memory_get_usage() - $this->start_memory,
            'db_queries' => $wpdb->num_queries ?? 0,
            'total_timers' => count($this->data),
            'enabled' => $this->enabled
        );
    }
    
    /**
     * Show performance bar
     * 
     * @since 3.0.0
     * @return void
     */
    private function show_performance_bar() {
        $stats = $this->get_stats();
        
        ?>
        <div id="qcc-perf-bar" style="
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: #1e40af; 
            color: #fff; 
            padding: 8px 15px; 
            font-size: 12px; 
            z-index: 99999;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
        ">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>
                    🚀 QCC Performance: 
                    <?php echo number_format($stats['page_load_time'], 3); ?>s | 
                    <?php echo size_format($stats['memory_usage']); ?> | 
                    <?php echo $stats['db_queries']; ?> queries
                    <?php if ($stats['calculation_time'] > 0): ?>
                        | Calc: <?php echo number_format($stats['calculation_time'], 3); ?>s
                    <?php endif; ?>
                </span>
                <button onclick="this.parentElement.parentElement.style.display='none'" 
                        style="background:none;border:none;color:#fff;cursor:pointer;">✕</button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Output debug information
     * 
     * @since 3.0.0
     * @return void
     */
    public function output_debug_info() {
        if (!$this->debug_mode) {
            return;
        }
        
        $stats = $this->get_stats();
        
        error_log(sprintf(
            "QCC Performance Summary - Page: %.3fs, Memory: %s (Δ%s), Peak: %s, Queries: %d",
            $stats['page_load_time'],
            size_format($stats['memory_usage']),
            size_format($stats['memory_delta']),
            size_format($stats['memory_peak']),
            $stats['db_queries']
        ));
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
            'Performance',
            'Performance',
            'manage_options',
            'qcc-performance',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page
     * 
     * @since 3.0.0
     * @return void
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $stats = $this->get_stats();
        
        ?>
        <div class="wrap">
            <h1>QCC Performance Monitor</h1>
            
            <div class="notice notice-info">
                <p><strong>Status:</strong> 
                    <?php echo $this->enabled ? '✅ Monitoring Enabled' : '❌ Monitoring Disabled'; ?>
                </p>
            </div>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Page Load Time</th>
                    <td><?php echo number_format($stats['page_load_time'], 3); ?> seconds</td>
                </tr>
                <tr>
                    <th scope="row">Memory Usage</th>
                    <td><?php echo size_format($stats['memory_usage']); ?></td>
                </tr>
                <tr>
                    <th scope="row">Peak Memory</th>
                    <td><?php echo size_format($stats['memory_peak']); ?></td>
                </tr>
                <tr>
                    <th scope="row">Memory Delta</th>
                    <td><?php echo size_format($stats['memory_delta']); ?></td>
                </tr>
                <tr>
                    <th scope="row">Database Queries</th>
                    <td><?php echo $stats['db_queries']; ?></td>
                </tr>
                <?php if ($stats['calculation_time'] > 0): ?>
                <tr>
                    <th scope="row">Last Calculation Time</th>
                    <td><?php echo number_format($stats['calculation_time'], 3); ?> seconds</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row">Total Timers</th>
                    <td><?php echo $stats['total_timers']; ?></td>
                </tr>
            </table>
            
            <?php if (!empty($this->data)): ?>
                <h2>Recent Performance Data</h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Timer</th>
                            <th>Execution Time</th>
                            <th>Memory Used</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($this->data, -10) as $entry): ?>
                            <tr>
                                <td><?php echo esc_html($entry['name']); ?></td>
                                <td><?php echo number_format($entry['execution_time'], 3); ?>s</td>
                                <td><?php echo size_format($entry['memory_used']); ?></td>
                                <td><?php echo date('H:i:s', $entry['timestamp']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <h3>Configuration</h3>
            <p>To enable performance monitoring, add this to your wp-config.php:</p>
            <code>define('QCC_PERFORMANCE_MONITORING', true);</code>
            
            <p>For debug mode with detailed logging:</p>
            <code>define('QCC_DEBUG', true);</code>
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
        $this->enabled = true;
    }
    
    /**
     * Disable monitoring
     * 
     * @since 3.0.0
     * @return void
     */
    public function disable() {
        $this->enabled = false;
    }
    
    /**
     * Check if monitoring is enabled
     * 
     * @since 3.0.0
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }
    
    /**
     * Get all performance data
     * 
     * @since 3.0.0
     * @return array
     */
    public function get_data() {
        return $this->data;
    }
    
    /**
     * Clear all data
     * 
     * @since 3.0.0
     * @return void
     */
    public function clear_data() {
        $this->data = array();
        $this->timers = array();
    }
    
    /**
     * Stop tracking (for shutdown)
     * 
     * @since 3.0.0
     * @return void
     */
    public static function stop_tracking() {
        $instance = self::get_instance();
        $instance->output_debug_info();
    }
}
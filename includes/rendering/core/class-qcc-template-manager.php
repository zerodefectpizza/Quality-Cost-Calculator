<?php
/**
 * QCC Template Manager
 * 
 * Template-System für QCC Components mit Theme-Override-Support,
 * Caching und Template-Hierarchie.
 * 
 * @package QualityCostCalculator
 * @subpackage Core
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QCC_Template_Manager
 * 
 * @since 3.0.0
 */
class QCC_Template_Manager {
    
    /**
     * Template-Pfade (Prioritäts-Reihenfolge)
     * 
     * @since 3.0.0
     * @var array
     */
    private $template_paths = array();
    
    /**
     * Template-Cache
     * 
     * @since 3.0.0
     * @var array
     */
    private $template_cache = array();
    
    /**
     * Compiled Template Cache
     * 
     * @since 3.0.0
     * @var array
     */
    private $compiled_cache = array();
    
    /**
     * Template-Variablen
     * 
     * @since 3.0.0
     * @var array
     */
    private $global_vars = array();
    
    /**
     * Template-Hooks
     * 
     * @since 3.0.0
     * @var array
     */
    private $template_hooks = array();
    
    /**
     * Cache-Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    private $cache_config = array(
        'enabled' => true,
        'duration' => 3600,
        'path_cache' => true,
        'compiled_cache' => true
    );
    
    /**
     * Template-Engine-Config
     * 
     * @since 3.0.0
     * @var array
     */
    private $engine_config = array(
        'auto_escape' => true,
        'strict_variables' => false,
        'debug_mode' => false,
        'template_extension' => '.php'
    );
    
    /**
     * Render-Statistiken
     * 
     * @since 3.0.0
     * @var array
     */
    private $stats = array(
        'renders' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'template_loads' => 0
    );
    
    /**
     * Debug-Modus
     * 
     * @since 3.0.0
     * @var bool
     */
    private $debug_mode = false;
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->engine_config['debug_mode'] = $this->debug_mode;
        
        $this->init_template_paths();
        $this->init_global_vars();
        $this->init_template_hooks();
    }
    
    /**
     * Template-Pfade initialisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_template_paths() {
        // Prioritäts-Reihenfolge: Theme > Child-Theme > Plugin
        $this->template_paths = array(
            'child_theme' => get_stylesheet_directory() . '/qcc-templates/',
            'theme' => get_template_directory() . '/qcc-templates/',
            'plugin_custom' => WP_CONTENT_DIR . '/qcc-templates/',
            'plugin_default' => QCC_PLUGIN_PATH . 'templates/'
        );
        
        // Filter für Custom-Pfade
        $this->template_paths = apply_filters('qcc_template_paths', $this->template_paths);
    }
    
    /**
     * Globale Template-Variablen initialisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_global_vars() {
        $this->global_vars = array(
            'plugin_url' => QCC_PLUGIN_URL,
            'plugin_path' => QCC_PLUGIN_PATH,
            'assets_url' => QCC_PLUGIN_URL . 'assets/',
            'locale' => get_locale(),
            'is_admin' => is_admin(),
            'current_user_id' => get_current_user_id(),
            'site_url' => site_url(),
            'debug_mode' => $this->debug_mode
        );
    }
    
    /**
     * Template-Hooks initialisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_template_hooks() {
        $this->template_hooks = array(
            'before_render' => array(),
            'after_render' => array(),
            'before_load' => array(),
            'after_load' => array(),
            'template_not_found' => array()
        );
    }
    
    /**
     * Template rendern
     * 
     * @since 3.0.0
     * @param string $template_name Template name/path
     * @param array $vars Template variables
     * @param bool $echo Echo output or return
     * @return string|void Rendered template
     */
    public function render($template_name, $vars = array(), $echo = false) {
        $this->stats['renders']++;
        
        // Before render hook
        $this->run_template_hook('before_render', $template_name, $vars);
        
        // Cache-Check
        if ($this->cache_config['enabled']) {
            $cache_key = $this->get_cache_key($template_name, $vars);
            $cached = $this->get_cached_template($cache_key);
            if ($cached !== false) {
                $this->stats['cache_hits']++;
                if ($echo) {
                    echo $cached;
                    return;
                }
                return $cached;
            }
            $this->stats['cache_misses']++;
        }
        
        // Template-Pfad ermitteln
        $template_path = $this->locate_template($template_name);
        if (!$template_path) {
            $error_output = $this->handle_template_not_found($template_name, $vars);
            if ($echo) {
                echo $error_output;
                return;
            }
            return $error_output;
        }
        
        // Template-Variablen vorbereiten
        $template_vars = $this->prepare_template_vars($vars);
        
        // Template rendern
        $output = $this->render_template_file($template_path, $template_vars);
        
        // Output nachbearbeiten
        $output = $this->process_template_output($output, $template_name, $vars);
        
        // Cache speichern
        if ($this->cache_config['enabled'] && isset($cache_key)) {
            $this->cache_template($cache_key, $output);
        }
        
        // After render hook
        $output = $this->run_template_hook('after_render', $template_name, $output, $vars);
        
        if ($echo) {
            echo $output;
            return;
        }
        
        return $output;
    }
    
    /**
     * Template-Pfad lokalisieren
     * 
     * @since 3.0.0
     * @param string $template_name Template name
     * @return string|false Template path or false
     */
    public function locate_template($template_name) {
        // Cache-Check für Pfade
        if ($this->cache_config['path_cache'] && isset($this->template_cache[$template_name])) {
            return $this->template_cache[$template_name];
        }
        
        // Before load hook
        $this->run_template_hook('before_load', $template_name);
        
        // Template-Name normalisieren
        $template_name = $this->normalize_template_name($template_name);
        
        // In allen Pfaden suchen
        foreach ($this->template_paths as $path_type => $base_path) {
            $full_path = $base_path . $template_name;
            
            if (file_exists($full_path) && is_readable($full_path)) {
                // Pfad cachen
                if ($this->cache_config['path_cache']) {
                    $this->template_cache[$template_name] = $full_path;
                }
                
                $this->stats['template_loads']++;
                
                // After load hook
                $this->run_template_hook('after_load', $template_name, $full_path, $path_type);
                
                return $full_path;
            }
        }
        
        return false;
    }
    
    /**
     * Template-Name normalisieren
     * 
     * @since 3.0.0
     * @param string $template_name Raw template name
     * @return string Normalized name
     */
    private function normalize_template_name($template_name) {
        // .php Extension hinzufügen falls fehlt
        if (!str_ends_with($template_name, $this->engine_config['template_extension'])) {
            $template_name .= $this->engine_config['template_extension'];
        }
        
        // Pfad-Traversal verhindern
        $template_name = str_replace('..', '', $template_name);
        $template_name = ltrim($template_name, '/');
        
        return $template_name;
    }
    
    /**
     * Template-Variablen vorbereiten
     * 
     * @since 3.0.0
     * @param array $vars User variables
     * @return array Prepared variables
     */
    private function prepare_template_vars($vars) {
        // Globale Variablen mergen
        $template_vars = array_merge($this->global_vars, $vars);
        
        // Auto-Escape aktivieren
        if ($this->engine_config['auto_escape']) {
            $template_vars = $this->escape_template_vars($template_vars);
        }
        
        // Template-Helper hinzufügen
        $template_vars['template_helper'] = $this;
        $template_vars['wp'] = $GLOBALS['wp'];
        
        return apply_filters('qcc_template_vars', $template_vars);
    }
    
    /**
     * Template-Variablen escapen
     * 
     * @since 3.0.0
     * @param array $vars Variables to escape
     * @return array Escaped variables
     */
    private function escape_template_vars($vars) {
        $escaped = array();
        
        foreach ($vars as $key => $value) {
            if (is_string($value)) {
                $escaped[$key] = esc_html($value);
            } elseif (is_array($value)) {
                $escaped[$key] = $this->escape_template_vars($value);
            } else {
                $escaped[$key] = $value;
            }
        }
        
        return $escaped;
    }
    
    /**
     * Template-Datei rendern
     * 
     * @since 3.0.0
     * @param string $template_path Template file path
     * @param array $template_vars Template variables
     * @return string Rendered output
     */
    private function render_template_file($template_path, $template_vars) {
        // Variablen extrahieren
        extract($template_vars, EXTR_SKIP);
        
        // Output buffering starten
        ob_start();
        
        try {
            // Template einbinden
            include $template_path;
            $output = ob_get_clean();
            
        } catch (Exception $e) {
            ob_end_clean();
            
            if ($this->debug_mode) {
                $output = sprintf(
                    '<div class="qcc-template-error">Template Error: %s</div>',
                    esc_html($e->getMessage())
                );
            } else {
                $output = '<!-- Template render error -->';
            }
        }
        
        return $output;
    }
    
    /**
     * Template-Output nachbearbeiten
     * 
     * @since 3.0.0
     * @param string $output Raw output
     * @param string $template_name Template name
     * @param array $vars Template variables
     * @return string Processed output
     */
    private function process_template_output($output, $template_name, $vars) {
        // Whitespace cleanup
        if (!$this->debug_mode) {
            $output = $this->cleanup_whitespace($output);
        }
        
        // Template-spezifische Filter
        $filter_name = 'qcc_template_output_' . str_replace(array('/', '.'), '_', $template_name);
        $output = apply_filters($filter_name, $output, $vars);
        
        return $output;
    }
    
    /**
     * Whitespace bereinigen
     * 
     * @since 3.0.0
     * @param string $output HTML output
     * @return string Cleaned output
     */
    private function cleanup_whitespace($output) {
        // Mehrfache Leerzeichen entfernen
        $output = preg_replace('/\s+/', ' ', $output);
        
        // Leere Zeilen entfernen
        $output = preg_replace('/^\s*$/m', '', $output);
        
        // Trim
        return trim($output);
    }
    
    /**
     * Template-Not-Found-Handler
     * 
     * @since 3.0.0
     * @param string $template_name Template name
     * @param array $vars Template variables
     * @return string Error output
     */
    private function handle_template_not_found($template_name, $vars) {
        // Template not found hook
        $fallback = $this->run_template_hook('template_not_found', $template_name, $vars);
        
        if ($fallback) {
            return $fallback;
        }
        
        if ($this->debug_mode) {
            return sprintf(
                '<div class="qcc-template-missing">Template not found: %s</div>',
                esc_html($template_name)
            );
        }
        
        return '<!-- Template not found -->';
    }
    
    /**
     * Template-Existenz prüfen
     * 
     * @since 3.0.0
     * @param string $template_name Template name
     * @return bool True wenn existiert
     */
    public function template_exists($template_name) {
        return $this->locate_template($template_name) !== false;
    }
    
    /**
     * Cache-Key generieren
     * 
     * @since 3.0.0
     * @param string $template_name Template name
     * @param array $vars Template variables
     * @return string Cache key
     */
    private function get_cache_key($template_name, $vars) {
        return sprintf(
            'qcc_template_%s_%s_%s',
            md5($template_name),
            md5(serialize($vars)),
            get_locale()
        );
    }
    
    /**
     * Template aus Cache abrufen
     * 
     * @since 3.0.0
     * @param string $cache_key Cache key
     * @return string|false Cached template or false
     */
    private function get_cached_template($cache_key) {
        // Memory-Cache prüfen
        if (isset($this->compiled_cache[$cache_key])) {
            return $this->compiled_cache[$cache_key];
        }
        
        // WordPress-Cache prüfen
        $cached = wp_cache_get($cache_key, 'qcc_templates');
        if ($cached !== false) {
            $this->compiled_cache[$cache_key] = $cached;
            return $cached;
        }
        
        return false;
    }
    
    /**
     * Template in Cache speichern
     * 
     * @since 3.0.0
     * @param string $cache_key Cache key
     * @param string $output Template output
     * @return void
     */
    private function cache_template($cache_key, $output) {
        // Memory-Cache
        $this->compiled_cache[$cache_key] = $output;
        
        // WordPress-Cache
        wp_cache_set($cache_key, $output, 'qcc_templates', $this->cache_config['duration']);
    }
    
    /**
     * Template-Hook ausführen
     * 
     * @since 3.0.0
     * @param string $hook_name Hook name
     * @param mixed ...$args Hook arguments
     * @return mixed Hook result
     */
    private function run_template_hook($hook_name, ...$args) {
        if (!isset($this->template_hooks[$hook_name])) {
            return null;
        }
        
        $result = null;
        foreach ($this->template_hooks[$hook_name] as $callback) {
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, $args);
            }
        }
        
        return $result;
    }
    
    /**
     * Template-Hook hinzufügen
     * 
     * @since 3.0.0
     * @param string $hook_name Hook name
     * @param callable $callback Callback function
     * @return void
     */
    public function add_template_hook($hook_name, $callback) {
        if (!isset($this->template_hooks[$hook_name])) {
            $this->template_hooks[$hook_name] = array();
        }
        
        $this->template_hooks[$hook_name][] = $callback;
    }
    
    /**
     * Globale Variable hinzufügen
     * 
     * @since 3.0.0
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function add_global_var($name, $value) {
        $this->global_vars[$name] = $value;
    }
    
    /**
     * Template-Pfad hinzufügen
     * 
     * @since 3.0.0
     * @param string $name Path name
     * @param string $path Path
     * @param int $priority Priority (lower = higher priority)
     * @return void
     */
    public function add_template_path($name, $path, $priority = 10) {
        $this->template_paths = array_slice($this->template_paths, 0, $priority, true) +
                               array($name => trailingslashit($path)) +
                               array_slice($this->template_paths, $priority, null, true);
    }
    
    /**
     * Cache leeren
     * 
     * @since 3.0.0
     * @param string $type Cache type ('all', 'compiled', 'paths')
     * @return void
     */
    public function clear_cache($type = 'all') {
        switch ($type) {
            case 'compiled':
                $this->compiled_cache = array();
                wp_cache_flush_group('qcc_templates');
                break;
                
            case 'paths':
                $this->template_cache = array();
                break;
                
            case 'all':
            default:
                $this->compiled_cache = array();
                $this->template_cache = array();
                wp_cache_flush_group('qcc_templates');
                break;
        }
        
        do_action('qcc_template_cache_cleared', $type);
    }
    
    /**
     * Cache-Konfiguration setzen
     * 
     * @since 3.0.0
     * @param array $config Cache configuration
     * @return void
     */
    public function set_cache_config($config) {
        $this->cache_config = array_merge($this->cache_config, $config);
    }
    
    /**
     * Engine-Konfiguration setzen
     * 
     * @since 3.0.0
     * @param array $config Engine configuration
     * @return void
     */
    public function set_engine_config($config) {
        $this->engine_config = array_merge($this->engine_config, $config);
    }
    
    /**
     * Template-Statistiken abrufen
     * 
     * @since 3.0.0
     * @return array Statistics
     */
    public function get_statistics() {
        $this->stats['cache_hit_ratio'] = $this->stats['renders'] > 0 
            ? round(($this->stats['cache_hits'] / $this->stats['renders']) * 100, 2)
            : 0;
            
        $this->stats['memory_usage'] = memory_get_usage();
        $this->stats['cached_templates'] = count($this->compiled_cache);
        $this->stats['cached_paths'] = count($this->template_cache);
        
        return $this->stats;
    }
    
    /**
     * Template-Pfade abrufen
     * 
     * @since 3.0.0
     * @return array Template paths
     */
    public function get_template_paths() {
        return $this->template_paths;
    }
    
    /**
     * Verfügbare Templates scannen
     * 
     * @since 3.0.0
     * @param string $directory Subdirectory to scan
     * @return array Available templates
     */
    public function scan_templates($directory = '') {
        $templates = array();
        
        foreach ($this->template_paths as $path_type => $base_path) {
            $scan_path = $base_path . $directory;
            
            if (is_dir($scan_path)) {
                $files = glob($scan_path . '*' . $this->engine_config['template_extension']);
                
                foreach ($files as $file) {
                    $template_name = str_replace($base_path, '', $file);
                    $templates[$template_name] = array(
                        'path' => $file,
                        'type' => $path_type,
                        'size' => filesize($file),
                        'modified' => filemtime($file)
                    );
                }
            }
        }
        
        return $templates;
    }
    
    /**
     * Debug-Informationen
     * 
     * @since 3.0.0
     * @return array Debug information
     */
    public function get_debug_info() {
        if (!$this->debug_mode) {
            return array('debug_mode' => false);
        }
        
        return array(
            'debug_mode' => true,
            'statistics' => $this->get_statistics(),
            'template_paths' => $this->template_paths,
            'cache_config' => $this->cache_config,
            'engine_config' => $this->engine_config,
            'global_vars' => array_keys($this->global_vars),
            'registered_hooks' => array_map('count', $this->template_hooks),
            'available_templates' => count($this->scan_templates())
        );
    }
}
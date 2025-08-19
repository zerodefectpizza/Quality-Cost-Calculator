<?php
/**
 * QCC Import Service
 * 
 * Handles all data import operations for the Quality Cost Calculator plugin.
 * Part of the modular service layer architecture.
 * 
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Import Service Class
 * 
 * Manages import operations with support for multiple formats, validation,
 * and error handling with rollback capabilities.
 */
class QCC_Import_Service {
    
    /**
     * Supported import formats
     * 
     * @var array
     */
    private $supported_formats = array('json', 'csv', 'xml');
    
    /**
     * Import validators
     * 
     * @var array
     */
    private $validators = array();
    
    /**
     * Import transformers
     * 
     * @var array
     */
    private $transformers = array();
    
    /**
     * Import results
     * 
     * @var array
     */
    private $import_results = array();
    
    /**
     * Error tracking
     * 
     * @var array
     */
    private $errors = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_validators();
        $this->init_transformers();
    }
    
    /**
     * Initialize validators for different data types
     */
    private function init_validators() {
        $this->validators = array(
            'calculation_data' => array($this, 'validate_calculation_data'),
            'settings' => array($this, 'validate_settings_data'),
            'templates' => array($this, 'validate_template_data'),
            'translations' => array($this, 'validate_translation_data')
        );
    }
    
    /**
     * Initialize data transformers
     */
    private function init_transformers() {
        $this->transformers = array(
            'legacy_format' => array($this, 'transform_legacy_format'),
            'v1_to_v2' => array($this, 'transform_v1_to_v2'),
            'normalize_keys' => array($this, 'normalize_data_keys')
        );
    }
    
    /**
     * Import data from file
     * 
     * @param string $file_path Path to import file
     * @param array $options Import options
     * @return array Import results
     */
    public function import_from_file($file_path, $options = array()) {
        $this->reset_import_state();
        
        try {
            // Validate file
            if (!$this->validate_import_file($file_path)) {
                throw new Exception('Invalid import file');
            }
            
            // Detect format
            $format = $this->detect_file_format($file_path);
            if (!in_array($format, $this->supported_formats)) {
                throw new Exception('Unsupported file format: ' . $format);
            }
            
            // Read and parse data
            $raw_data = $this->read_file_data($file_path, $format);
            
            // Validate data structure
            if (!$this->validate_import_data($raw_data, $options)) {
                throw new Exception('Invalid data structure');
            }
            
            // Transform data if needed
            $processed_data = $this->transform_import_data($raw_data, $options);
            
            // Import with transaction support
            $import_result = $this->execute_import($processed_data, $options);
            
            // Log success
            $this->log_import_success($file_path, $import_result);
            
            return $import_result;
            
        } catch (Exception $e) {
            $this->handle_import_error($e, $file_path);
            return array(
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->errors
            );
        }
    }
    
    /**
     * Import data from URL
     * 
     * @param string $url URL to import from
     * @param array $options Import options
     * @return array Import results
     */
    public function import_from_url($url, $options = array()) {
        $this->reset_import_state();
        
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL provided');
            }
            
            // Download data
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'QCC-Import-Service/2.0'
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('Failed to fetch data: ' . $response->get_error_message());
            }
            
            $data = wp_remote_retrieve_body($response);
            
            // Process as string data
            return $this->import_from_string($data, $options);
            
        } catch (Exception $e) {
            $this->handle_import_error($e, $url);
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Import data from string
     * 
     * @param string $data Data string
     * @param array $options Import options
     * @return array Import results
     */
    public function import_from_string($data, $options = array()) {
        $this->reset_import_state();
        
        try {
            // Detect format from data
            $format = $this->detect_data_format($data);
            
            // Parse data
            $parsed_data = $this->parse_data_string($data, $format);
            
            // Validate and transform
            if (!$this->validate_import_data($parsed_data, $options)) {
                throw new Exception('Invalid data structure');
            }
            
            $processed_data = $this->transform_import_data($parsed_data, $options);
            
            // Execute import
            return $this->execute_import($processed_data, $options);
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Validate import file
     */
    private function validate_import_file($file_path) {
        if (!file_exists($file_path)) {
            $this->add_error('File does not exist: ' . $file_path);
            return false;
        }
        
        if (!is_readable($file_path)) {
            $this->add_error('File is not readable: ' . $file_path);
            return false;
        }
        
        $file_size = filesize($file_path);
        $max_size = $this->get_max_import_size();
        
        if ($file_size > $max_size) {
            $this->add_error('File too large: ' . $file_size . ' bytes (max: ' . $max_size . ')');
            return false;
        }
        
        return true;
    }
    
    /**
     * Detect file format
     */
    private function detect_file_format($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'json':
                return 'json';
            case 'csv':
                return 'csv';
            case 'xml':
                return 'xml';
            default:
                // Try to detect from content
                return $this->detect_format_from_content($file_path);
        }
    }
    
    /**
     * Read file data based on format
     */
    private function read_file_data($file_path, $format) {
        switch ($format) {
            case 'json':
                return json_decode(file_get_contents($file_path), true);
                
            case 'csv':
                return $this->read_csv_file($file_path);
                
            case 'xml':
                return $this->read_xml_file($file_path);
                
            default:
                throw new Exception('Unsupported format: ' . $format);
        }
    }
    
    /**
     * Read CSV file
     */
    private function read_csv_file($file_path) {
        $data = array();
        $headers = null;
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                if ($headers === null) {
                    $headers = $row;
                } else {
                    $data[] = array_combine($headers, $row);
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Read XML file
     */
    private function read_xml_file($file_path) {
        $xml = simplexml_load_file($file_path);
        return json_decode(json_encode($xml), true);
    }
    
    /**
     * Validate import data
     */
    private function validate_import_data($data, $options) {
        $data_type = $options['data_type'] ?? 'calculation_data';
        
        if (!isset($this->validators[$data_type])) {
            return true; // No specific validator
        }
        
        return call_user_func($this->validators[$data_type], $data, $options);
    }
    
    /**
     * Validate calculation data
     */
    private function validate_calculation_data($data, $options) {
        $required_fields = array('cost_data', 'quality_metrics');
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                $this->add_error('Missing required field: ' . $field);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate settings data
     */
    private function validate_settings_data($data, $options) {
        if (!is_array($data)) {
            $this->add_error('Settings data must be an array');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate template data
     */
    private function validate_template_data($data, $options) {
        $required_fields = array('name', 'content');
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                $this->add_error('Missing template field: ' . $field);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate translation data
     */
    private function validate_translation_data($data, $options) {
        if (!isset($data['language']) || !isset($data['translations'])) {
            $this->add_error('Invalid translation data structure');
            return false;
        }
        
        return true;
    }
    
    /**
     * Transform import data
     */
    private function transform_import_data($data, $options) {
        $transformers = $options['transformers'] ?? array();
        
        foreach ($transformers as $transformer) {
            if (isset($this->transformers[$transformer])) {
                $data = call_user_func($this->transformers[$transformer], $data, $options);
            }
        }
        
        return $data;
    }
    
    /**
     * Execute the actual import
     */
    private function execute_import($data, $options) {
        $import_type = $options['import_type'] ?? 'replace';
        $data_type = $options['data_type'] ?? 'calculation_data';
        
        switch ($data_type) {
            case 'calculation_data':
                return $this->import_calculation_data($data, $import_type);
                
            case 'settings':
                return $this->import_settings_data($data, $import_type);
                
            case 'templates':
                return $this->import_template_data($data, $import_type);
                
            case 'translations':
                return $this->import_translation_data($data, $import_type);
                
            default:
                throw new Exception('Unknown data type: ' . $data_type);
        }
    }
    
    /**
     * Import calculation data
     */
    private function import_calculation_data($data, $import_type) {
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($data as $item) {
            try {
                if ($this->process_calculation_item($item, $import_type)) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (Exception $e) {
                $errors++;
                $this->add_error('Error importing item: ' . $e->getMessage());
            }
        }
        
        return array(
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($data)
        );
    }
    
    /**
     * Import settings data
     */
    private function import_settings_data($data, $import_type) {
        if ($import_type === 'replace') {
            // Replace all settings
            update_option('qcc_settings', $data);
        } else {
            // Merge with existing settings
            $existing = get_option('qcc_settings', array());
            $merged = array_merge($existing, $data);
            update_option('qcc_settings', $merged);
        }
        
        return array(
            'success' => true,
            'imported' => count($data),
            'type' => 'settings'
        );
    }
    
    /**
     * Process individual calculation item
     */
    private function process_calculation_item($item, $import_type) {
        // Validate item structure
        if (!isset($item['id'])) {
            $item['id'] = uniqid('qcc_');
        }
        
        // Check if item exists
        $existing = $this->get_calculation_item($item['id']);
        
        if ($existing && $import_type === 'skip_existing') {
            return false;
        }
        
        // Save item
        return $this->save_calculation_item($item);
    }
    
    /**
     * Get calculation item by ID
     */
    private function get_calculation_item($id) {
        $items = get_option('qcc_calculation_items', array());
        return isset($items[$id]) ? $items[$id] : null;
    }
    
    /**
     * Save calculation item
     */
    private function save_calculation_item($item) {
        $items = get_option('qcc_calculation_items', array());
        $items[$item['id']] = $item;
        return update_option('qcc_calculation_items', $items);
    }
    
    /**
     * Reset import state
     */
    private function reset_import_state() {
        $this->errors = array();
        $this->import_results = array();
    }
    
    /**
     * Add error to error tracking
     */
    private function add_error($message) {
        $this->errors[] = array(
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Get maximum import file size
     */
    private function get_max_import_size() {
        return apply_filters('qcc_max_import_size', 10 * 1024 * 1024); // 10MB default
    }
    
    /**
     * Handle import error
     */
    private function handle_import_error($error, $source) {
        $this->add_error('Import failed from ' . $source . ': ' . $error->getMessage());
        
        // Log to WordPress error log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('QCC Import Error: ' . $error->getMessage());
        }
        
        // Trigger action for external error handling
        do_action('qcc_import_error', $error, $source);
    }
    
    /**
     * Log import success
     */
    private function log_import_success($source, $result) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('QCC Import Success: ' . $source . ' - ' . json_encode($result));
        }
        
        do_action('qcc_import_success', $source, $result);
    }
    
    /**
     * Get import statistics
     */
    public function get_import_stats() {
        return array(
            'total_imports' => get_option('qcc_total_imports', 0),
            'successful_imports' => get_option('qcc_successful_imports', 0),
            'failed_imports' => get_option('qcc_failed_imports', 0),
            'last_import' => get_option('qcc_last_import_date', null)
        );
    }
}
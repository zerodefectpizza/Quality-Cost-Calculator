<?php
/**
 * QCC Response Builder
 * Baut HTTP-Responses für den Quality Cost Calculator
 * 
 * @package QualityCostCalculator
 * @subpackage Presentation
 * @since 2.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class QCC_Response_Builder {
    
    /**
     * Response types
     */
    const TYPE_HTML = 'html';
    const TYPE_JSON = 'json';
    const TYPE_XML = 'xml';
    const TYPE_CSV = 'csv';
    const TYPE_PDF = 'pdf';
    
    /**
     * HTTP status codes
     */
    const STATUS_OK = 200;
    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_INTERNAL_ERROR = 500;
    const STATUS_SERVICE_UNAVAILABLE = 503;
    
    /**
     * Default response configuration
     * @var array
     */
    private $default_config = array(
        'type' => self::TYPE_HTML,
        'status' => self::STATUS_OK,
        'headers' => array(),
        'cache_duration' => 0,
        'compress' => true
    );
    
    /**
     * Response data
     * @var array
     */
    private $response_data = array();
    
    /**
     * Error handler
     * @var QCC_Error_Handler
     */
    private $error_handler;
    
    /**
     * Template router
     * @var QCC_Template_Router
     */
    private $template_router;
    
    /**
     * Translator
     * @var QCC_Translator
     */
    private $translator;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = new QCC_Error_Handler();
        $this->template_router = new QCC_Template_Router();
        $this->translator = $this->get_translator();
    }
    
    /**
     * Build calculator response
     * 
     * @param array $calculation_data Calculation results
     * @param array $config Response configuration
     * @return array Response array
     */
    public function build_calculator_response($calculation_data, $config = array()) {
        $config = array_merge($this->default_config, $config);
        
        try {
            switch ($config['type']) {
                case self::TYPE_JSON:
                    return $this->build_json_response($calculation_data, $config);
                    
                case self::TYPE_XML:
                    return $this->build_xml_response($calculation_data, $config);
                    
                case self::TYPE_CSV:
                    return $this->build_csv_response($calculation_data, $config);
                    
                case self::TYPE_PDF:
                    return $this->build_pdf_response($calculation_data, $config);
                    
                case self::TYPE_HTML:
                default:
                    return $this->build_html_response($calculation_data, $config);
            }
            
        } catch (Exception $e) {
            $this->error_handler->handle_calculation_error($e);
            return $this->build_error_response($e, $config);
        }
    }
    
    /**
     * Build HTML response
     * 
     * @param array $data Calculation data
     * @param array $config Configuration
     * @return array
     */
    private function build_html_response($data, $config) {
        $template_data = $this->prepare_template_data($data);
        
        // Determine template based on request context
        $template_name = $config['template'] ?? $this->determine_template($data);
        
        try {
            $html_content = $this->template_router->render_template($template_name, $template_data);
            
        } catch (Exception $e) {
            $this->error_handler->handle_template_error($template_name, $e);
            
            // Fallback to emergency template
            $emergency_data = array_merge($template_data, array(
                'error_message' => $e->getMessage(),
                'original_template' => $template_name,
                'show_debug' => defined('WP_DEBUG') && WP_DEBUG
            ));
            
            $html_content = $this->template_router->render_emergency_fallback($emergency_data);
        }
        
        return array(
            'type' => self::TYPE_HTML,
            'status' => $this->error_handler->has_errors() ? self::STATUS_INTERNAL_ERROR : self::STATUS_OK,
            'headers' => $this->build_html_headers($config),
            'content' => $html_content,
            'data' => $template_data
        );
    }
    
    /**
     * Build JSON response
     * 
     * @param array $data Calculation data
     * @param array $config Configuration
     * @return array
     */
    private function build_json_response($data, $config) {
        $response_data = array(
            'success' => !$this->error_handler->has_errors(),
            'data' => $this->prepare_json_data($data),
            'meta' => $this->build_response_meta(),
            'timestamp' => current_time('c')
        );
        
        // Add errors if any
        if ($this->error_handler->has_errors()) {
            $response_data['errors'] = $this->error_handler->get_errors_for_display('array');
        }
        
        $json_content = wp_json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return array(
            'type' => self::TYPE_JSON,
            'status' => $response_data['success'] ? self::STATUS_OK : self::STATUS_BAD_REQUEST,
            'headers' => $this->build_json_headers($config),
            'content' => $json_content,
            'data' => $response_data
        );
    }
    
    /**
     * Build XML response
     * 
     * @param array $data Calculation data
     * @param array $config Configuration
     * @return array
     */
    private function build_xml_response($data, $config) {
        $xml_data = array(
            'calculator_results' => array(
                'success' => !$this->error_handler->has_errors(),
                'timestamp' => current_time('c'),
                'data' => $this->prepare_xml_data($data),
                'meta' => $this->build_response_meta()
            )
        );
        
        if ($this->error_handler->has_errors()) {
            $xml_data['calculator_results']['errors'] = $this->error_handler->get_errors_for_display('array');
        }
        
        $xml_content = $this->array_to_xml($xml_data);
        
        return array(
            'type' => self::TYPE_XML,
            'status' => $xml_data['calculator_results']['success'] ? self::STATUS_OK : self::STATUS_BAD_REQUEST,
            'headers' => $this->build_xml_headers($config),
            'content' => $xml_content,
            'data' => $xml_data
        );
    }
    
    /**
     * Build CSV response
     * 
     * @param array $data Calculation data
     * @param array $config Configuration
     * @return array
     */
    private function build_csv_response($data, $config) {
        $csv_data = $this->prepare_csv_data($data);
        $csv_content = $this->array_to_csv($csv_data);
        
        return array(
            'type' => self::TYPE_CSV,
            'status' => self::STATUS_OK,
            'headers' => $this->build_csv_headers($config),
            'content' => $csv_content,
            'data' => $csv_data
        );
    }
    
    /**
     * Build PDF response
     * 
     * @param array $data Calculation data
     * @param array $config Configuration
     * @return array
     */
    private function build_pdf_response($data, $config) {
        // Note: PDF generation would require a library like TCPDF or DOMPDF
        // For now, we'll return HTML that can be printed to PDF
        
        $template_data = $this->prepare_template_data($data);
        $template_data['pdf_mode'] = true;
        
        $html_content = $this->template_router->render_template('pdf-export', $template_data);
        
        return array(
            'type' => self::TYPE_PDF,
            'status' => self::STATUS_OK,
            'headers' => $this->build_pdf_headers($config),
            'content' => $html_content,
            'data' => $template_data
        );
    }
    
    /**
     * Build error response
     * 
     * @param Exception $exception Exception that occurred
     * @param array $config Configuration
     * @return array
     */
    private function build_error_response($exception, $config) {
        $error_data = array(
            'success' => false,
            'error' => array(
                'message' => $exception->getMessage(),
                'type' => get_class($exception),
                'code' => $exception->getCode()
            ),
            'timestamp' => current_time('c')
        );
        
        // Add debug information if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $error_data['debug'] = array(
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            );
        }
        
        switch ($config['type']) {
            case self::TYPE_JSON:
                $content = wp_json_encode($error_data, JSON_PRETTY_PRINT);
                $headers = $this->build_json_headers($config);
                break;
                
            case self::TYPE_XML:
                $content = $this->array_to_xml(array('error_response' => $error_data));
                $headers = $this->build_xml_headers($config);
                break;
                
            case self::TYPE_HTML:
            default:
                $error_template_data = array(
                    'error_message' => $exception->getMessage(),
                    'error_type' => get_class($exception),
                    'show_debug' => defined('WP_DEBUG') && WP_DEBUG,
                    'debug_info' => $error_data['debug'] ?? array()
                );
                
                $content = $this->template_router->render_emergency_fallback($error_template_data);
                $headers = $this->build_html_headers($config);
                break;
        }
        
        return array(
            'type' => $config['type'],
            'status' => self::STATUS_INTERNAL_ERROR,
            'headers' => $headers,
            'content' => $content,
            'data' => $error_data
        );
    }
    
    /**
     * Prepare template data
     * 
     * @param array $calculation_data Raw calculation data
     * @return array Prepared template data
     */
    private function prepare_template_data($calculation_data) {
        return array(
            'calculator_data' => $calculation_data,
            'header' => $this->build_header_data(),
            'controls' => $this->build_controls_data(),
            'input_section' => $this->build_input_section_data($calculation_data),
            'results_section' => $this->build_results_section_data($calculation_data),
            'chart_section' => $this->build_chart_section_data($calculation_data),
            'actions' => $this->build_actions_data(),
            'footer' => $this->build_footer_data(),
            'errors' => $this->error_handler->get_errors_for_display('array'),
            'meta' => $this->build_response_meta()
        );
    }
    
    /**
     * Prepare JSON data
     * 
     * @param array $data Calculation data
     * @return array
     */
    private function prepare_json_data($data) {
        return array(
            'inputs' => $data['inputs'] ?? array(),
            'calculations' => $data['calculations'] ?? array(),
            'cogq' => $data['cogq'] ?? array(),
            'copq' => $data['copq'] ?? array(),
            'totals' => $data['totals'] ?? array(),
            'charts' => $data['charts'] ?? array(),
            'formatted' => $this->format_values_for_display($data)
        );
    }
    
    /**
     * Prepare XML data
     * 
     * @param array $data Calculation data
     * @return array
     */
    private function prepare_xml_data($data) {
        // XML requires slightly different structure
        $xml_data = $this->prepare_json_data($data);
        
        // Convert arrays to XML-friendly format
        return $this->convert_arrays_for_xml($xml_data);
    }
    
    /**
     * Prepare CSV data
     * 
     * @param array $data Calculation data
     * @return array
     */
    private function prepare_csv_data($data) {
        $csv_rows = array();
        
        // Header row
        $csv_rows[] = array(
            'Category',
            'Item',
            'Value',
            'Percentage',
            'Currency',
            'Unit'
        );
        
        // Input values
        if (isset($data['inputs'])) {
            foreach ($data['inputs'] as $key => $value) {
                $csv_rows[] = array(
                    'Input',
                    $this->translator->get($key, $key),
                    $value,
                    '',
                    $data['currency'] ?? 'EUR',
                    $data['unit'] ?? 'Millions'
                );
            }
        }
        
        // COGQ values
        if (isset($data['cogq'])) {
            foreach ($data['cogq'] as $key => $value) {
                $csv_rows[] = array(
                    'COGQ',
                    $this->translator->get($key, $key),
                    $value['amount'] ?? $value,
                    $value['percentage'] ?? '',
                    $data['currency'] ?? 'EUR',
                    $data['unit'] ?? 'Millions'
                );
            }
        }
        
        // COPQ values
        if (isset($data['copq'])) {
            foreach ($data['copq'] as $key => $value) {
                $csv_rows[] = array(
                    'COPQ',
                    $this->translator->get($key, $key),
                    $value['amount'] ?? $value,
                    $value['percentage'] ?? '',
                    $data['currency'] ?? 'EUR',
                    $data['unit'] ?? 'Millions'
                );
            }
        }
        
        return $csv_rows;
    }
    
    /**
     * Build header data
     * 
     * @return array
     */
    private function build_header_data() {
        return array(
            'title' => $this->translator->get('calculator_title', 'Quality Cost Calculator'),
            'subtitle' => $this->translator->get('calculator_subtitle', 'Calculate and analyze your Cost of Quality metrics'),
            'theme' => 'default'
        );
    }
    
    /**
     * Build controls data
     * 
     * @return array
     */
    private function build_controls_data() {
        return array(
            'language_options' => array(
                'en' => 'English',
                'de' => 'Deutsch',
                'fr' => 'Français',
                'es' => 'Español',
                'zh' => '中文'
            ),
            'currency_options' => array(
                'EUR' => 'Euro (€)',
                'USD' => 'US Dollar ($)',
                'CNY' => 'Renminbi (¥)'
            ),
            'unit_options' => array(
                '1000000' => $this->translator->get('millions', 'Millions'),
                '1000000000' => $this->translator->get('billions', 'Billions')
            )
        );
    }
    
    /**
     * Build input section data
     * 
     * @param array $calculation_data Calculation data
     * @return string
     */
    private function build_input_section_data($calculation_data) {
        $input_data = array(
            'fields' => $this->get_input_field_definitions(),
            'values' => $calculation_data['inputs'] ?? array(),
            'validation' => $calculation_data['validation'] ?? array()
        );
        
        return $this->template_router->render_template('form-section', $input_data);
    }
    
    /**
     * Build results section data
     * 
     * @param array $calculation_data Calculation data
     * @return string
     */
    private function build_results_section_data($calculation_data) {
        $results_data = array(
            'cogq' => $calculation_data['cogq'] ?? array(),
            'copq' => $calculation_data['copq'] ?? array(),
            'totals' => $calculation_data['totals'] ?? array(),
            'formatted' => $this->format_values_for_display($calculation_data)
        );
        
        return $this->template_router->render_template('results-section', $results_data);
    }
    
    /**
     * Build chart section data
     * 
     * @param array $calculation_data Calculation data
     * @return string
     */
    private function build_chart_section_data($calculation_data) {
        $chart_data = array(
            'chart_data' => $this->prepare_chart_data($calculation_data),
            'chart_config' => $this->get_chart_configuration(),
            'show_charts' => !empty($calculation_data['calculations'])
        );
        
        return $this->template_router->render_template('chart-section', $chart_data);
    }
    
    /**
     * Build actions data
     * 
     * @return array
     */
    private function build_actions_data() {
        return array(
            'buttons' => array(
                array(
                    'id' => 'calculate-btn',
                    'label' => $this->translator->get('calculate', 'Calculate'),
                    'action' => 'calculate',
                    'type' => 'primary',
                    'icon' => '📊'
                ),
                array(
                    'id' => 'reset-btn',
                    'label' => $this->translator->get('reset', 'Reset'),
                    'action' => 'reset',
                    'type' => 'secondary',
                    'icon' => '🔄'
                ),
                array(
                    'id' => 'export-btn',
                    'label' => $this->translator->get('export', 'Export'),
                    'action' => 'export',
                    'type' => 'export',
                    'icon' => '📄'
                )
            )
        );
    }
    
    /**
     * Build footer data
     * 
     * @return array
     */
    private function build_footer_data() {
        return array(
            'version' => '2.0',
            'copyright' => '© ' . date('Y') . ' Quality Cost Calculator',
            'links' => array(
                array(
                    'url' => '#',
                    'label' => $this->translator->get('documentation', 'Documentation')
                ),
                array(
                    'url' => '#',
                    'label' => $this->translator->get('support', 'Support')
                )
            )
        );
    }
    
    /**
     * Build response meta
     * 
     * @return array
     */
    private function build_response_meta() {
        return array(
            'version' => '2.0',
            'timestamp' => current_time('c'),
            'request_id' => uniqid('qcc_'),
            'language' => $this->translator->get_current_language(),
            'processing_time' => $this->get_processing_time(),
            'memory_usage' => $this->get_memory_usage()
        );
    }
    
    /**
     * Determine template based on data
     * 
     * @param array $data Calculation data
     * @return string Template name
     */
    private function determine_template($data) {
        // Logic to determine which template to use
        if ($this->error_handler->has_errors('critical')) {
            return 'emergency-fallback';
        }
        
        if (!empty($data['calculations'])) {
            return 'main-container';
        }
        
        return 'main-container'; // Default template
    }
    
    /**
     * Build HTML headers
     * 
     * @param array $config Configuration
     * @return array
     */
    private function build_html_headers($config) {
        $headers = array(
            'Content-Type' => 'text/html; charset=utf-8'
        );
        
        if ($config['cache_duration'] > 0) {
            $headers['Cache-Control'] = 'public, max-age=' . $config['cache_duration'];
            $headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $config['cache_duration']) . ' GMT';
        } else {
            $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            $headers['Pragma'] = 'no-cache';
            $headers['Expires'] = '0';
        }
        
        return array_merge($headers, $config['headers']);
    }
    
    /**
     * Build JSON headers
     * 
     * @param array $config Configuration
     * @return array
     */
    private function build_json_headers($config) {
        return array_merge(array(
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff'
        ), $config['headers']);
    }
    
    /**
     * Build XML headers
     * 
     * @param array $config Configuration
     * @return array
     */
    private function build_xml_headers($config) {
        return array_merge(array(
            'Content-Type' => 'application/xml; charset=utf-8'
        ), $config['headers']);
    }
    
    /**
     * Build CSV headers
     * 
     * @param array $config Configuration
     * @return array
     */
    private function build_csv_headers($config) {
        $filename = 'quality-cost-calculation-' . date('Y-m-d-H-i-s') . '.csv';
        
        return array_merge(array(
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ), $config['headers']);
    }
    
    /**
     * Build PDF headers
     * 
     * @param array $config Configuration
     * @return array
     */
    private function build_pdf_headers($config) {
        return array_merge(array(
            'Content-Type' => 'text/html; charset=utf-8',
            'X-PDF-Mode' => 'true'
        ), $config['headers']);
    }
    
    /**
     * Convert array to XML
     * 
     * @param array $data Array data
     * @return string XML string
     */
    private function array_to_xml($data, $xml = null, $root = 'root') {
        if ($xml === null) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $root . '></' . $root . '>');
        }
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->array_to_xml($value, $child, $key);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }
    
    /**
     * Convert array to CSV
     * 
     * @param array $data Array data
     * @return string CSV string
     */
    private function array_to_csv($data) {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    /**
     * Format values for display
     * 
     * @param array $data Calculation data
     * @return array Formatted values
     */
    private function format_values_for_display($data) {
        $formatted = array();
        $currency = $data['currency'] ?? 'EUR';
        $unit = $data['unit'] ?? 'Millions';
        
        if (isset($data['calculations'])) {
            foreach ($data['calculations'] as $key => $value) {
                $formatted[$key] = $this->format_currency_value($value, $currency, $unit);
            }
        }
        
        return $formatted;
    }
    
    /**
     * Format currency value
     * 
     * @param float $value Value to format
     * @param string $currency Currency code
     * @param string $unit Unit (Millions/Billions)
     * @return string Formatted value
     */
    private function format_currency_value($value, $currency, $unit) {
        $currency_symbols = array(
            'EUR' => '€',
            'USD' => ',
            'CNY' => '¥'
        );
        
        $symbol = $currency_symbols[$currency] ?? $currency;
        $formatted_value = number_format($value, 2, '.', ',');
        $unit_short = $unit === 'Billions' ? 'Mrd.' : 'Mio.';
        
        return $symbol . ' ' . $formatted_value . ' ' . $unit_short;
    }
    
    /**
     * Get input field definitions
     * 
     * @return array
     */
    private function get_input_field_definitions() {
        return array(
            'revenue' => array(
                'type' => 'number',
                'label' => $this->translator->get('revenue_label', 'Revenue'),
                'required' => true,
                'min' => 0,
                'step' => 0.01
            ),
            'quality_percentage' => array(
                'type' => 'number',
                'label' => $this->translator->get('quality_percentage_label', 'Quality Cost % of Revenue'),
                'required' => true,
                'min' => 0,
                'max' => 100,
                'step' => 0.01
            ),
            'prevention_costs' => array(
                'type' => 'number',
                'label' => $this->translator->get('prevention_costs_label', 'Prevention Costs (%)'),
                'required' => true,
                'min' => 0,
                'max' => 100,
                'step' => 0.01
            ),
            'appraisal_costs' => array(
                'type' => 'number',
                'label' => $this->translator->get('appraisal_costs_label', 'Appraisal Costs (%)'),
                'required' => true,
                'min' => 0,
                'max' => 100,
                'step' => 0.01
            ),
            'internal_defect_costs' => array(
                'type' => 'number',
                'label' => $this->translator->get('internal_defect_costs_label', 'Internal Defect Costs (%)'),
                'required' => true,
                'min' => 0,
                'max' => 100,
                'step' => 0.01
            ),
            'external_defect_costs' => array(
                'type' => 'number',
                'label' => $this->translator->get('external_defect_costs_label', 'External Defect Costs (%)'),
                'required' => true,
                'min' => 0,
                'max' => 100,
                'step' => 0.01
            )
        );
    }
    
    /**
     * Prepare chart data
     * 
     * @param array $calculation_data Calculation data
     * @return array
     */
    private function prepare_chart_data($calculation_data) {
        if (empty($calculation_data['calculations'])) {
            return array();
        }
        
        return array(
            'labels' => array(
                $this->translator->get('prevention_costs', 'Prevention Costs'),
                $this->translator->get('appraisal_costs', 'Appraisal Costs'),
                $this->translator->get('internal_defect_costs', 'Internal Defect Costs'),
                $this->translator->get('external_defect_costs', 'External Defect Costs')
            ),
            'data' => array(
                $calculation_data['cogq']['prevention'] ?? 0,
                $calculation_data['cogq']['appraisal'] ?? 0,
                $calculation_data['copq']['internal'] ?? 0,
                $calculation_data['copq']['external'] ?? 0
            ),
            'colors' => array('#449775', '#6bc27f', '#dc3545', '#ff6b6b')
        );
    }
    
    /**
     * Get chart configuration
     * 
     * @return array
     */
    private function get_chart_configuration() {
        return array(
            'type' => 'doughnut',
            'responsive' => true,
            'animation' => array(
                'animateRotate' => true,
                'animateScale' => true
            ),
            'legend' => array(
                'position' => 'bottom'
            )
        );
    }
    
    /**
     * Convert arrays for XML compatibility
     * 
     * @param array $data Data to convert
     * @return array XML-compatible data
     */
    private function convert_arrays_for_xml($data) {
        $converted = array();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric(key($value))) {
                    // Numeric array - convert to named elements
                    $converted[$key] = array();
                    foreach ($value as $index => $item) {
                        $converted[$key]['item_' . $index] = $item;
                    }
                } else {
                    // Associative array - recurse
                    $converted[$key] = $this->convert_arrays_for_xml($value);
                }
            } else {
                $converted[$key] = $value;
            }
        }
        
        return $converted;
    }
    
    /**
     * Get processing time
     * 
     * @return float Processing time in seconds
     */
    private function get_processing_time() {
        if (defined('QCC_START_TIME')) {
            return microtime(true) - QCC_START_TIME;
        }
        return 0;
    }
    
    /**
     * Get memory usage
     * 
     * @return string Memory usage
     */
    private function get_memory_usage() {
        return size_format(memory_get_usage(true));
    }
    
    /**
     * Get translator instance
     * 
     * @return QCC_Translator
     */
    private function get_translator() {
        if (class_exists('QCC_Translator')) {
            return new QCC_Translator();
        }
        
        // Fallback translator
        return new class {
            public function get($key, $default = '') {
                return $default;
            }
            
            public function get_current_language() {
                return 'en';
            }
        };
    }
    
    /**
     * Send response to browser
     * 
     * @param array $response Response array
     */
    public function send_response($response) {
        // Set HTTP status code
        http_response_code($response['status']);
        
        // Set headers
        foreach ($response['headers'] as $name => $value) {
            header($name . ': ' . $value);
        }
        
        // Output content
        echo $response['content'];
        
        // For AJAX requests, exit to prevent additional output
        if (wp_doing_ajax()) {
            wp_die();
        }
    }
}
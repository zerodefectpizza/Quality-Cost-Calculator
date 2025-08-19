<?php
/**
 * QCC Export Service - Data Export Functionality
 *
 * @package QualityCostCalculator
 * @subpackage Services
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_Export_Service {
    
    private $bootstrap;
    private $supported_formats = array('csv', 'json', 'xml', 'pdf');
    private $export_stats = array();
    
    public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ?: QCC_Bootstrap::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_qcc_export', array($this, 'handle_ajax_export'));
        add_action('wp_ajax_nopriv_qcc_export', array($this, 'handle_ajax_export'));
    }
    
    /**
     * Export calculation data
     */
    public function export_calculation($data, $format = 'csv', $options = array()) {
        if (!in_array($format, $this->supported_formats)) {
            return new WP_Error('invalid_format', 'Unsupported export format');
        }
        
        $method = 'export_to_' . $format;
        
        if (!method_exists($this, $method)) {
            return new WP_Error('method_not_found', 'Export method not implemented');
        }
        
        $this->export_stats['format'] = $format;
        $this->export_stats['timestamp'] = current_time('mysql');
        
        return $this->$method($data, $options);
    }
    
    /**
     * Export to CSV
     */
    private function export_to_csv($data, $options = array()) {
        $language = $options['language'] ?? 'en';
        $currency = $options['currency'] ?? 'EUR';
        $filename = $options['filename'] ?? 'quality_cost_calculation_' . date('Y-m-d') . '.csv';
        
        $csv_data = array();
        
        // Headers
        $csv_data[] = array($this->translate('parameter', $language), $this->translate('value', $language));
        
        // Input parameters
        $csv_data[] = array($this->translate('revenue', $language), $data['input']['revenue'] ?? 0);
        $csv_data[] = array($this->translate('quality_percentage', $language), ($data['input']['quality_percentage'] ?? 0) . '%');
        $csv_data[] = array($this->translate('prevention_percentage', $language), ($data['input']['prevention'] ?? 0) . '%');
        $csv_data[] = array($this->translate('appraisal_percentage', $language), ($data['input']['appraisal'] ?? 0) . '%');
        $csv_data[] = array($this->translate('internal_defect_percentage', $language), ($data['input']['internal_defect'] ?? 0) . '%');
        $csv_data[] = array($this->translate('external_defect_percentage', $language), ($data['input']['external_defect'] ?? 0) . '%');
        
        // Empty row
        $csv_data[] = array('', '');
        
        // Results header
        $csv_data[] = array($this->translate('calculated_results', $language), '');
        
        // Calculated values
        $csv_data[] = array($this->translate('total_quality_cost', $language), $this->format_currency($data['total_quality_cost'] ?? 0, $currency));
        $csv_data[] = array($this->translate('prevention_cost', $language), $this->format_currency($data['prevention_cost'] ?? 0, $currency));
        $csv_data[] = array($this->translate('appraisal_cost', $language), $this->format_currency($data['appraisal_cost'] ?? 0, $currency));
        $csv_data[] = array($this->translate('internal_defect_cost', $language), $this->format_currency($data['internal_defect_cost'] ?? 0, $currency));
        $csv_data[] = array($this->translate('external_defect_cost', $language), $this->format_currency($data['external_defect_cost'] ?? 0, $currency));
        $csv_data[] = array($this->translate('total_cogq', $language), $this->format_currency($data['cogq'] ?? 0, $currency));
        $csv_data[] = array($this->translate('total_copq', $language), $this->format_currency($data['copq'] ?? 0, $currency));
        
        // Convert to CSV string
        $csv_string = '';
        foreach ($csv_data as $row) {
            $csv_string .= implode(',', array_map(array($this, 'escape_csv_field'), $row)) . "\n";
        }
        
        return array(
            'content' => $csv_string,
            'filename' => $filename,
            'mime_type' => 'text/csv',
            'size' => strlen($csv_string)
        );
    }
    
    /**
     * Export to JSON
     */
    private function export_to_json($data, $options = array()) {
        $filename = $options['filename'] ?? 'quality_cost_calculation_' . date('Y-m-d') . '.json';
        
        $export_data = array(
            'meta' => array(
                'version' => QCC_PLUGIN_VERSION,
                'exported_at' => current_time('c'),
                'language' => $options['language'] ?? 'en',
                'currency' => $options['currency'] ?? 'EUR'
            ),
            'calculation' => $data
        );
        
        $json_string = wp_json_encode($export_data, JSON_PRETTY_PRINT);
        
        return array(
            'content' => $json_string,
            'filename' => $filename,
            'mime_type' => 'application/json',
            'size' => strlen($json_string)
        );
    }
    
    /**
     * Export to XML
     */
    private function export_to_xml($data, $options = array()) {
        $filename = $options['filename'] ?? 'quality_cost_calculation_' . date('Y-m-d') . '.xml';
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><quality_cost_calculation></quality_cost_calculation>');
        
        // Meta information
        $meta = $xml->addChild('meta');
        $meta->addChild('version', QCC_PLUGIN_VERSION);
        $meta->addChild('exported_at', current_time('c'));
        $meta->addChild('language', $options['language'] ?? 'en');
        $meta->addChild('currency', $options['currency'] ?? 'EUR');
        
        // Input data
        $input = $xml->addChild('input');
        foreach ($data['input'] ?? array() as $key => $value) {
            $input->addChild($key, htmlspecialchars($value));
        }
        
        // Results
        $results = $xml->addChild('results');
        $calculations = array(
            'total_quality_cost', 'prevention_cost', 'appraisal_cost',
            'internal_defect_cost', 'external_defect_cost', 'cogq', 'copq'
        );
        
        foreach ($calculations as $calc) {
            if (isset($data[$calc])) {
                $results->addChild($calc, $data[$calc]);
            }
        }
        
        $xml_string = $xml->asXML();
        
        return array(
            'content' => $xml_string,
            'filename' => $filename,
            'mime_type' => 'application/xml',
            'size' => strlen($xml_string)
        );
    }
    
    /**
     * Export to PDF (basic implementation)
     */
    private function export_to_pdf($data, $options = array()) {
        // This would require a PDF library like TCPDF or similar
        // For now, return HTML that can be converted to PDF
        
        $filename = $options['filename'] ?? 'quality_cost_calculation_' . date('Y-m-d') . '.html';
        $language = $options['language'] ?? 'en';
        $currency = $options['currency'] ?? 'EUR';
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $this->translate('quality_cost_report', $language) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .section { margin-bottom: 20px; }
                .section h3 { color: #449775; border-bottom: 2px solid #449775; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; }
                .highlight { background-color: #e8f5e8; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . $this->translate('quality_cost_report', $language) . '</h1>
                <p>' . $this->translate('generated_on', $language) . ': ' . current_time('Y-m-d H:i:s') . '</p>
            </div>
            
            <div class="section">
                <h3>' . $this->translate('input_parameters', $language) . '</h3>
                <table>
                    <tr><td>' . $this->translate('revenue', $language) . '</td><td>' . $this->format_currency($data['input']['revenue'] ?? 0, $currency) . '</td></tr>
                    <tr><td>' . $this->translate('quality_percentage', $language) . '</td><td>' . ($data['input']['quality_percentage'] ?? 0) . '%</td></tr>
                    <tr><td>' . $this->translate('prevention_percentage', $language) . '</td><td>' . ($data['input']['prevention'] ?? 0) . '%</td></tr>
                    <tr><td>' . $this->translate('appraisal_percentage', $language) . '</td><td>' . ($data['input']['appraisal'] ?? 0) . '%</td></tr>
                    <tr><td>' . $this->translate('internal_defect_percentage', $language) . '</td><td>' . ($data['input']['internal_defect'] ?? 0) . '%</td></tr>
                    <tr><td>' . $this->translate('external_defect_percentage', $language) . '</td><td>' . ($data['input']['external_defect'] ?? 0) . '%</td></tr>
                </table>
            </div>
            
            <div class="section">
                <h3>' . $this->translate('calculated_results', $language) . '</h3>
                <table>
                    <tr class="highlight"><td>' . $this->translate('total_quality_cost', $language) . '</td><td>' . $this->format_currency($data['total_quality_cost'] ?? 0, $currency) . '</td></tr>
                    <tr><td>' . $this->translate('prevention_cost', $language) . '</td><td>' . $this->format_currency($data['prevention_cost'] ?? 0, $currency) . '</td></tr>
                    <tr><td>' . $this->translate('appraisal_cost', $language) . '</td><td>' . $this->format_currency($data['appraisal_cost'] ?? 0, $currency) . '</td></tr>
                    <tr><td>' . $this->translate('internal_defect_cost', $language) . '</td><td>' . $this->format_currency($data['internal_defect_cost'] ?? 0, $currency) . '</td></tr>
                    <tr><td>' . $this->translate('external_defect_cost', $language) . '</td><td>' . $this->format_currency($data['external_defect_cost'] ?? 0, $currency) . '</td></tr>
                    <tr class="highlight"><td>' . $this->translate('total_cogq', $language) . '</td><td>' . $this->format_currency($data['cogq'] ?? 0, $currency) . '</td></tr>
                    <tr class="highlight"><td>' . $this->translate('total_copq', $language) . '</td><td>' . $this->format_currency($data['copq'] ?? 0, $currency) . '</td></tr>
                </table>
            </div>
        </body>
        </html>';
        
        return array(
            'content' => $html,
            'filename' => $filename,
            'mime_type' => 'text/html',
            'size' => strlen($html)
        );
    }
    
    /**
     * Handle AJAX export request
     */
    public function handle_ajax_export() {
        check_ajax_referer('qcc_nonce', 'nonce');
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $data = $_POST['data'] ?? array();
        $options = $_POST['options'] ?? array();
        
        $result = $this->export_calculation($data, $format, $options);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Set headers for download
        $this->set_download_headers($result);
        
        echo $result['content'];
        wp_die();
    }
    
    /**
     * Set download headers
     */
    private function set_download_headers($export_result) {
        header('Content-Type: ' . $export_result['mime_type']);
        header('Content-Disposition: attachment; filename="' . $export_result['filename'] . '"');
        header('Content-Length: ' . $export_result['size']);
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    /**
     * Escape CSV field
     */
    private function escape_csv_field($field) {
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
    
    /**
     * Format currency value
     */
    private function format_currency($amount, $currency = 'EUR') {
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'CNY' => '¥'
        );
        
        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . ' ' . number_format($amount, 2);
    }
    
    /**
     * Translate text
     */
    private function translate($key, $language = 'en') {
        // Use existing translation system if available
        if (class_exists('QCC_Shortcode')) {
            $shortcode = new QCC_Shortcode();
            if (method_exists($shortcode, 'get_translation')) {
                return $shortcode->get_translation($language, $key);
            }
        }
        
        // Fallback translations
        $translations = array(
            'en' => array(
                'parameter' => 'Parameter',
                'value' => 'Value',
                'revenue' => 'Revenue',
                'quality_percentage' => 'Quality Percentage',
                'prevention_percentage' => 'Prevention Percentage',
                'appraisal_percentage' => 'Appraisal Percentage',
                'internal_defect_percentage' => 'Internal Defect Percentage',
                'external_defect_percentage' => 'External Defect Percentage',
                'calculated_results' => 'Calculated Results',
                'total_quality_cost' => 'Total Quality Cost',
                'prevention_cost' => 'Prevention Cost',
                'appraisal_cost' => 'Appraisal Cost',
                'internal_defect_cost' => 'Internal Defect Cost',
                'external_defect_cost' => 'External Defect Cost',
                'total_cogq' => 'Total COGQ',
                'total_copq' => 'Total COPQ',
                'quality_cost_report' => 'Quality Cost Report',
                'generated_on' => 'Generated on',
                'input_parameters' => 'Input Parameters'
            )
        );
        
        return $translations[$language][$key] ?? $key;
    }
    
    /**
     * Get export statistics
     */
    public function get_statistics() {
        return $this->export_stats;
    }
    
    /**
     * Get supported formats
     */
    public function get_supported_formats() {
        return $this->supported_formats;
    }
}
<?php
/**
 * AJAX functionality for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Class
 */
class QCC_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_qcc_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_nopriv_qcc_export_csv', array($this, 'export_csv'));
        add_action('wp_ajax_qcc_get_status', array($this, 'get_status'));
        add_action('wp_ajax_nopriv_qcc_get_status', array($this, 'get_status'));
    }
    
    /**
     * Export CSV
     */
    public function export_csv() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'qcc_nonce')) {
            wp_die('Security check failed');
        }
        
        $data = json_decode(stripslashes($_POST['data']), true);
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';
        
        // Get language-specific headers
        $headers = $this->get_csv_headers($language);
        
        // Build CSV data
        $csv_data = array(
            array($headers['parameter'], $headers['value']),
            array($headers['revenue'], $data['revenue']),
            array($headers['quality_basis'], $data['qualityPercentage'] . '%'),
            array($headers['total_quality'], $data['totalQualityCost']),
            array($headers['prevention'], $data['preventionCost']),
            array($headers['appraisal'], $data['appraisalCost']),
            array($headers['internal'], $data['internalDefectCost']),
            array($headers['external'], $data['externalDefectCost']),
            array($headers['total_cogq'], $data['totalCOGQ']),
            array($headers['total_copq'], $data['totalCOPQ'])
        );
        
        // Generate filename
        $filename = 'quality_costs_cogq_copq_' . $language . '_' . date('Y-m-d') . '.csv';
        
        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write CSV data
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        wp_die();
    }
    
    /**
     * Get plugin status
     */
    public function get_status() {
        check_ajax_referer('qcc_nonce', 'nonce');
        
        $status = array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => QCC_PLUGIN_VERSION,
            'current_language' => get_option('qcc_default_language', 'en'),
            'jquery_loaded' => wp_script_is('jquery', 'done'),
            'chartjs_loaded' => wp_script_is('chart-js', 'done'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * Get CSV headers for different languages
     */
    private function get_csv_headers($language) {
        $headers = array(
            'en' => array(
                'parameter' => 'Parameter',
                'value' => 'Value',
                'revenue' => 'Revenue',
                'quality_basis' => 'Quality Cost Basis (%)',
                'total_quality' => 'Total Quality Cost',
                'prevention' => 'Prevention Cost (COGQ)',
                'appraisal' => 'Appraisal Cost (COGQ)',
                'internal' => 'Internal Defect Cost (COPQ)',
                'external' => 'External Defect Cost (COPQ)',
                'total_cogq' => 'Total COGQ',
                'total_copq' => 'Total COPQ'
            ),
            'de' => array(
                'parameter' => 'Parameter',
                'value' => 'Wert',
                'revenue' => 'Umsatz',
                'quality_basis' => 'Qualitätskostenbasis (%)',
                'total_quality' => 'Gesamte Qualitätskosten',
                'prevention' => 'Präventionskosten (COGQ)',
                'appraisal' => 'Bewertungskosten (COGQ)',
                'internal' => 'Interne Fehlerkosten (COPQ)',
                'external' => 'Externe Fehlerkosten (COPQ)',
                'total_cogq' => 'Gesamt COGQ',
                'total_copq' => 'Gesamt COPQ'
            ),
            'fr' => array(
                'parameter' => 'Paramètre',
                'value' => 'Valeur',
                'revenue' => 'Chiffre d\'Affaires',
                'quality_basis' => 'Base de Coût Qualité (%)',
                'total_quality' => 'Coût Total de Qualité',
                'prevention' => 'Coûts de Prévention (COGQ)',
                'appraisal' => 'Coûts d\'Évaluation (COGQ)',
                'internal' => 'Coûts de Défauts Internes (COPQ)',
                'external' => 'Coûts de Défauts Externes (COPQ)',
                'total_cogq' => 'Total COGQ',
                'total_copq' => 'Total COPQ'
            ),
            'zh' => array(
                'parameter' => '参数',
                'value' => '值',
                'revenue' => '收入',
                'quality_basis' => '质量成本基础 (%)',
                'total_quality' => '总质量成本',
                'prevention' => '预防成本 (COGQ)',
                'appraisal' => '评估成本 (COGQ)',
                'internal' => '内部缺陷成本 (COPQ)',
                'external' => '外部缺陷成本 (COPQ)',
                'total_cogq' => '总计 COGQ',
                'total_copq' => '总计 COPQ'
            )
        );
        
        return isset($headers[$language]) ? $headers[$language] : $headers['en'];
    }
    
    /**
     * Validate CSV data
     */
    private function validate_csv_data($data) {
        $required_fields = array(
            'revenue',
            'qualityPercentage',
            'totalQualityCost',
            'preventionCost',
            'appraisalCost',
            'internalDefectCost',
            'externalDefectCost',
            'totalCOGQ',
            'totalCOPQ'
        );
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        return true;
    }
}
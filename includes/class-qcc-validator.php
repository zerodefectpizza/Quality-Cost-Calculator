<?php
/**
 * Validation functionality for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validator Class
 */
class QCC_Validator {
    
    /**
     * Validation errors
     */
    private static $errors = array();
    
    /**
     * Validate AJAX request
     */
    public static function validate_ajax_request($action, $nonce, $data = array()) {
        // Verify nonce
        if (!wp_verify_nonce($nonce, QCC_Config::NONCE_ACTION)) {
            return array(
                'valid' => false,
                'error' => __('Security check failed', 'quality-cost-calculator')
            );
        }
        
        // Validate action
        $allowed_actions = array(
            'qcc_export_csv',
            'qcc_get_status',
            'qcc_calculate',
            'qcc_save_settings'
        );
        
        if (!in_array($action, $allowed_actions)) {
            return array(
                'valid' => false,
                'error' => __('Invalid action', 'quality-cost-calculator')
            );
        }
        
        // Validate data if provided
        if (!empty($data) && !self::validate_input($data)) {
            return array(
                'valid' => false,
                'error' => __('Invalid input data', 'quality-cost-calculator'),
                'errors' => self::get_errors()
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Validate file upload
     */
    public static function validate_file_upload($file, $allowed_types = array()) {
        if (empty($allowed_types)) {
            $allowed_types = array('csv', 'json', 'xml');
        }
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return array(
                'valid' => false,
                'error' => __('No file uploaded', 'quality-cost-calculator')
            );
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'valid' => false,
                'error' => self::get_upload_error_message($file['error'])
            );
        }
        
        // Check file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            return array(
                'valid' => false,
                'error' => __('File size exceeds maximum allowed size of 5MB', 'quality-cost-calculator')
            );
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    __('Invalid file type. Allowed types: %s', 'quality-cost-calculator'),
                    implode(', ', $allowed_types)
                )
            );
        }
        
        // Check MIME type for additional security
        $allowed_mimes = array(
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xml' => 'application/xml'
        );
        
        if (isset($allowed_mimes[$file_extension])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if ($mime_type !== $allowed_mimes[$file_extension]) {
                return array(
                    'valid' => false,
                    'error' => __('File content does not match file extension', 'quality-cost-calculator')
                );
            }
        }
        
        return array('valid' => true);
    }
    
    /**
     * Get upload error message
     */
    private static function get_upload_error_message($error_code) {
        $error_messages = array(
            UPLOAD_ERR_INI_SIZE => __('File exceeds upload_max_filesize directive', 'quality-cost-calculator'),
            UPLOAD_ERR_FORM_SIZE => __('File exceeds MAX_FILE_SIZE directive', 'quality-cost-calculator'),
            UPLOAD_ERR_PARTIAL => __('File was only partially uploaded', 'quality-cost-calculator'),
            UPLOAD_ERR_NO_FILE => __('No file was uploaded', 'quality-cost-calculator'),
            UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder', 'quality-cost-calculator'),
            UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk', 'quality-cost-calculator'),
            UPLOAD_ERR_EXTENSION => __('File upload stopped by extension', 'quality-cost-calculator')
        );
        
        return isset($error_messages[$error_code]) 
            ? $error_messages[$error_code] 
            : __('Unknown upload error', 'quality-cost-calculator');
    }
    
    /**
     * Validate calculation bounds
     */
    public static function validate_calculation_bounds($results) {
        $errors = array();
        
        // Check for extremely large values that might cause overflow
        $max_value = PHP_FLOAT_MAX / 1000; // Safety margin
        
        foreach ($results as $key => $value) {
            if (is_numeric($value) && $value > $max_value) {
                $errors[] = sprintf(
                    __('Calculated value for %s exceeds maximum allowed value', 'quality-cost-calculator'),
                    $key
                );
            }
            
            // Check for negative values where they shouldn't occur
            if (is_numeric($value) && $value < 0 && 
                !in_array($key, array('profit', 'net_benefit'))) {
                $errors[] = sprintf(
                    __('Calculated value for %s cannot be negative', 'quality-cost-calculator'),
                    $key
                );
            }
        }
        
        return empty($errors) ? array('valid' => true) : array(
            'valid' => false,
            'errors' => $errors
        );
    }
    
    /**
     * Validate JSON data
     */
    public static function validate_json($json_string) {
        if (empty($json_string)) {
            return array(
                'valid' => false,
                'error' => __('Empty JSON data', 'quality-cost-calculator')
            );
        }
        
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'valid' => false,
                'error' => sprintf(
                    __('Invalid JSON: %s', 'quality-cost-calculator'),
                    json_last_error_msg()
                )
            );
        }
        
        return array(
            'valid' => true,
            'data' => $data
        );
    }
    
    /**
     * Validate email address
     */
    public static function validate_email($email) {
        return is_email($email);
    }
    
    /**
     * Validate URL
     */
    public static function validate_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate date
     */
    public static function validate_date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate color hex code
     */
    public static function validate_color($color) {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color);
    }
    
    /**
     * Validate calculation frequency
     */
    public static function validate_calculation_frequency($frequency) {
        $allowed_frequencies = array('daily', 'weekly', 'monthly', 'quarterly', 'yearly');
        return in_array($frequency, $allowed_frequencies);
    }
    
    /**
     * Get validation rules for different input types
     */
    public static function get_validation_rules() {
        return array(
            'revenue' => array(
                'type' => 'number',
                'min' => 0,
                'max' => 999999999999,
                'required' => true,
                'message' => __('Revenue must be a positive number', 'quality-cost-calculator')
            ),
            'quality_percentage' => array(
                'type' => 'percentage',
                'min' => 0,
                'max' => 100,
                'required' => true,
                'message' => __('Quality cost basis must be between 0 and 100%', 'quality-cost-calculator')
            ),
            'prevention' => array(
                'type' => 'percentage',
                'min' => 0,
                'max' => 100,
                'required' => true,
                'message' => __('Prevention costs must be between 0 and 100%', 'quality-cost-calculator')
            ),
            'appraisal' => array(
                'type' => 'percentage',
                'min' => 0,
                'max' => 100,
                'required' => true,
                'message' => __('Appraisal costs must be between 0 and 100%', 'quality-cost-calculator')
            ),
            'internal_defect' => array(
                'type' => 'percentage',
                'min' => 0,
                'max' => 100,
                'required' => true,
                'message' => __('Internal defect costs must be between 0 and 100%', 'quality-cost-calculator')
            ),
            'external_defect' => array(
                'type' => 'percentage',
                'min' => 0,
                'max' => 100,
                'required' => true,
                'message' => __('External defect costs must be between 0 and 100%', 'quality-cost-calculator')
            ),
            'language' => array(
                'type' => 'select',
                'options' => array_keys(QCC_Config::LANGUAGES),
                'required' => false,
                'message' => __('Invalid language selection', 'quality-cost-calculator')
            ),
            'currency' => array(
                'type' => 'select',
                'options' => array_keys(QCC_Config::CURRENCIES),
                'required' => false,
                'message' => __('Invalid currency selection', 'quality-cost-calculator')
            ),
            'unit' => array(
                'type' => 'select',
                'options' => array_keys(QCC_Config::UNITS),
                'required' => false,
                'message' => __('Invalid unit selection', 'quality-cost-calculator')
            )
        );
    }

    /**
     * Validate input data
     */
    public static function validate_input($data) {
        self::$errors = array();
        
        // Validate revenue
        if (!self::validate_revenue($data['revenue'])) {
            self::$errors['revenue'] = __('Revenue must be a positive number', 'quality-cost-calculator');
        }
        
        // Validate quality percentage
        if (!self::validate_percentage($data['quality_percentage'], 'quality_percentage')) {
            self::$errors['quality_percentage'] = __('Quality cost basis must be between 0 and 100', 'quality-cost-calculator');
        }
        
        // Validate cost distribution percentages
        $cost_percentages = array(
            'prevention' => $data['prevention'],
            'appraisal' => $data['appraisal'],
            'internal_defect' => $data['internal_defect'],
            'external_defect' => $data['external_defect']
        );
        
        if (!self::validate_cost_distribution($cost_percentages)) {
            self::$errors['cost_distribution'] = __('Cost distribution percentages must add up to 100%', 'quality-cost-calculator');
        }
        
        // Validate opportunity cost percentages if provided
        if (isset($data['lost_sales']) || isset($data['customer_churn']) || 
            isset($data['market_share_loss']) || isset($data['productivity_loss'])) {
            
            $opportunity_percentages = array(
                'lost_sales' => isset($data['lost_sales']) ? $data['lost_sales'] : 0,
                'customer_churn' => isset($data['customer_churn']) ? $data['customer_churn'] : 0,
                'market_share_loss' => isset($data['market_share_loss']) ? $data['market_share_loss'] : 0,
                'productivity_loss' => isset($data['productivity_loss']) ? $data['productivity_loss'] : 0
            );
            
            foreach ($opportunity_percentages as $key => $value) {
                if (!self::validate_percentage($value, $key)) {
                    self::$errors[$key] = sprintf(
                        __('%s must be between 0 and 100', 'quality-cost-calculator'),
                        ucfirst(str_replace('_', ' ', $key))
                    );
                }
            }
        }
        
        // Validate language
        if (isset($data['language']) && !self::validate_language($data['language'])) {
            self::$errors['language'] = __('Invalid language selection', 'quality-cost-calculator');
        }
        
        // Validate currency
        if (isset($data['currency']) && !self::validate_currency($data['currency'])) {
            self::$errors['currency'] = __('Invalid currency selection', 'quality-cost-calculator');
        }
        
        // Validate unit
        if (isset($data['unit']) && !self::validate_unit($data['unit'])) {
            self::$errors['unit'] = __('Invalid unit selection', 'quality-cost-calculator');
        }
        
        return empty(self::$errors);
    }
    
    /**
     * Validate revenue
     */
    public static function validate_revenue($revenue) {
        if (!is_numeric($revenue)) {
            return false;
        }
        
        $revenue = floatval($revenue);
        return $revenue >= 0 && $revenue <= 999999999999; // Max 999 billion
    }
    
    /**
     * Validate percentage
     */
    public static function validate_percentage($percentage, $field = '') {
        if (!is_numeric($percentage)) {
            return false;
        }
        
        $percentage = floatval($percentage);
        return $percentage >= 0 && $percentage <= 100;
    }
    
    /**
     * Validate cost distribution percentages
     */
    public static function validate_cost_distribution($percentages) {
        $total = 0;
        
        foreach ($percentages as $percentage) {
            if (!self::validate_percentage($percentage)) {
                return false;
            }
            $total += floatval($percentage);
        }
        
        // Allow small floating point differences
        return abs($total - 100) < 0.01;
    }
    
    /**
     * Validate language
     */
    public static function validate_language($language) {
        return array_key_exists($language, QCC_Config::LANGUAGES);
    }
    
    /**
     * Validate currency
     */
    public static function validate_currency($currency) {
        return array_key_exists($currency, QCC_Config::CURRENCIES);
    }
    
    /**
     * Validate unit
     */
    public static function validate_unit($unit) {
        return array_key_exists($unit, QCC_Config::UNITS);
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize_input($data) {
        $sanitized = array();
        
        // Sanitize numeric fields
        $numeric_fields = array(
            'revenue', 'quality_percentage', 'prevention', 'appraisal',
            'internal_defect', 'external_defect', 'lost_sales', 'customer_churn',
            'market_share_loss', 'productivity_loss'
        );
        
        foreach ($numeric_fields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = self::sanitize_number($data[$field]);
            }
        }
        
        // Sanitize text fields
        if (isset($data['language'])) {
            $sanitized['language'] = self::sanitize_language($data['language']);
        }
        
        if (isset($data['currency'])) {
            $sanitized['currency'] = self::sanitize_currency($data['currency']);
        }
        
        if (isset($data['unit'])) {
            $sanitized['unit'] = self::sanitize_unit($data['unit']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize number
     */
    public static function sanitize_number($number) {
        return floatval($number);
    }
    
    /**
     * Sanitize percentage
     */
    public static function sanitize_percentage($percentage) {
        $percentage = floatval($percentage);
        return max(0, min(100, $percentage)); // Clamp between 0-100
    }
    
    /**
     * Sanitize language
     */
    public static function sanitize_language($language) {
        return self::validate_language($language) ? $language : 'en';
    }
    
    /**
     * Sanitize currency
     */
    public static function sanitize_currency($currency) {
        return self::validate_currency($currency) ? $currency : '€';
    }
    
    /**
     * Sanitize unit
     */
    public static function sanitize_unit($unit) {
        return self::validate_unit($unit) ? $unit : '1000000';
    }
    
    /**
     * Get validation errors
     */
    public static function get_errors() {
        return self::$errors;
    }
    
    /**
     * Check if there are validation errors
     */
    public static function has_errors() {
        return !empty(self::$errors);
    }
    
    /**
     * Get error for specific field
     */
    public static function get_error($field) {
        return isset(self::$errors[$field]) ? self::$errors[$field] : '';
    }
    
    /**
     * Clear all errors
     */
    public static function clear_errors() {
        self::$errors = array();
    }
    
    /**
     * Add custom error
     */
    public static function add_error($field, $message) {
        self::$errors[$field] = $message;
    }
}


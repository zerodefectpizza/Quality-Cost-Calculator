<?php
/**
 * QCC JavaScript Generator - Dynamic JS Generation
 *
 * @package QualityCostCalculator
 * @subpackage Assets
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class QCC_JavaScript_Generator {
    
    private $bootstrap;
    private $container;
    private $js_cache = array();
    private $config = array();
    
    public function __construct($bootstrap = null) {
        $this->bootstrap = $bootstrap ?: QCC_Bootstrap::get_instance();
        $this->container = $this->bootstrap ? $this->bootstrap->get_container() : null;
        $this->init_config();
    }
    
    /**
     * Initialize configuration
     */
    private function init_config() {
        $this->config = array(
            'namespace' => 'QCC',
            'version' => QCC_PLUGIN_VERSION,
            'debug' => QCC_DEBUG,
            'enable_analytics' => $this->get_feature_flag('enable_analytics', false),
            'enable_validation' => $this->get_feature_flag('enable_validation', true),
            'enable_charts' => $this->get_feature_flag('enable_charts', true)
        );
    }
    
    /**
     * Generate calculator JavaScript
     */
    public function generate_calculator_js($config = array()) {
        $cache_key = md5(serialize($config));
        
        if (isset($this->js_cache[$cache_key])) {
            return $this->js_cache[$cache_key];
        }
        
        $js = '';
        
        // Core namespace
        $js .= $this->get_namespace_js();
        
        // Configuration
        $js .= $this->get_config_js($config);
        
        // Core calculator logic
        $js .= $this->get_calculator_js();
        
        // Validation logic
        if ($this->config['enable_validation']) {
            $js .= $this->get_validation_js();
        }
        
        // Chart logic
        if ($this->config['enable_charts']) {
            $js .= $this->get_charts_js();
        }
        
        // Export functionality
        $js .= $this->get_export_js();
        
        // Event handlers
        $js .= $this->get_events_js();
        
        // Initialization
        $js .= $this->get_init_js();
        
        $this->js_cache[$cache_key] = $js;
        return $js;
    }
    
    /**
     * Get namespace JavaScript
     */
    private function get_namespace_js() {
        $namespace = $this->config['namespace'];
        
        return "
        // Quality Cost Calculator - Version {$this->config['version']}
        window.{$namespace} = window.{$namespace} || {};
        
        {$namespace}.version = '{$this->config['version']}';
        {$namespace}.debug = " . ($this->config['debug'] ? 'true' : 'false') . ";
        {$namespace}.initialized = false;
        {$namespace}.instances = {};
        ";
    }
    
    /**
     * Get configuration JavaScript
     */
    private function get_config_js($config) {
        $default_config = array(
            'currency' => 'EUR',
            'unit' => '1000000',
            'language' => 'en',
            'validation' => true,
            'charts' => true,
            'export' => true
        );
        
        $merged_config = array_merge($default_config, $config);
        $json_config = wp_json_encode($merged_config);
        
        return "
        {$this->config['namespace']}.config = {$json_config};
        
        {$this->config['namespace']}.updateConfig = function(newConfig) {
            this.config = Object.assign(this.config, newConfig);
            this.log('Config updated:', this.config);
        };
        ";
    }
    
    /**
     * Get calculator core JavaScript
     */
    private function get_calculator_js() {
        return "
        {$this->config['namespace']}.Calculator = {
            
            calculate: function(input) {
                try {
                    var revenue = parseFloat(input.revenue) || 0;
                    var qualityPercentage = parseFloat(input.qualityPercentage) || 0;
                    var prevention = parseFloat(input.prevention) || 0;
                    var appraisal = parseFloat(input.appraisal) || 0;
                    var internalDefect = parseFloat(input.internalDefect) || 0;
                    var externalDefect = parseFloat(input.externalDefect) || 0;
                    
                    var unit = parseFloat({$this->config['namespace']}.config.unit) || 1000000;
                    var revenueInUnit = revenue * unit;
                    
                    var totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
                    
                    var preventionCost = (totalQualityCost * prevention) / 100;
                    var appraisalCost = (totalQualityCost * appraisal) / 100;
                    var internalDefectCost = (totalQualityCost * internalDefect) / 100;
                    var externalDefectCost = (totalQualityCost * externalDefect) / 100;
                    
                    var cogq = preventionCost + appraisalCost;
                    var copq = internalDefectCost + externalDefectCost;
                    
                    var result = {
                        input: input,
                        totalQualityCost: totalQualityCost,
                        preventionCost: preventionCost,
                        appraisalCost: appraisalCost,
                        internalDefectCost: internalDefectCost,
                        externalDefectCost: externalDefectCost,
                        cogq: cogq,
                        copq: copq,
                        cogqPercentage: totalQualityCost > 0 ? (cogq / totalQualityCost) * 100 : 0,
                        copqPercentage: totalQualityCost > 0 ? (copq / totalQualityCost) * 100 : 0,
                        revenuePercentage: revenueInUnit > 0 ? (totalQualityCost / revenueInUnit) * 100 : 0
                    };
                    
                    {$this->config['namespace']}.trigger('calculated', result);
                    return result;
                    
                } catch (error) {
                    {$this->config['namespace']}.log('Calculation error:', error);
                    throw error;
                }
            },
            
            formatCurrency: function(value, currency, unit) {
                currency = currency || {$this->config['namespace']}.config.currency;
                unit = unit || {$this->config['namespace']}.config.unit;
                
                var symbols = {
                    'EUR': '€',
                    'USD': '$',
                    'CNY': '¥'
                };
                
                var symbol = symbols[currency] || currency;
                var formattedValue = (value / parseFloat(unit)).toFixed(2);
                
                return symbol + ' ' + formattedValue;
            }
        };
        ";
    }
    
    /**
     * Get validation JavaScript
     */
    private function get_validation_js() {
        return "
        {$this->config['namespace']}.Validator = {
            
            validatePercentages: function(prevention, appraisal, internal, external) {
                var total = prevention + appraisal + internal + external;
                var isValid = Math.abs(total - 100) < 0.01;
                
                return {
                    valid: isValid,
                    total: total,
                    error: isValid ? null : 'Percentages must add up to 100%'
                };
            },
            
            validateInput: function(input) {
                var errors = [];
                
                if (!input.revenue || input.revenue <= 0) {
                    errors.push('Revenue must be positive');
                }
                
                if (input.qualityPercentage < 0 || input.qualityPercentage > 100) {
                    errors.push('Quality percentage must be between 0 and 100');
                }
                
                var percentageValidation = this.validatePercentages(
                    input.prevention || 0,
                    input.appraisal || 0,
                    input.internalDefect || 0,
                    input.externalDefect || 0
                );
                
                if (!percentageValidation.valid) {
                    errors.push(percentageValidation.error);
                }
                
                return {
                    valid: errors.length === 0,
                    errors: errors
                };
            },
            
            showValidationErrors: function(errors, container) {
                var errorHtml = errors.map(function(error) {
                    return '<div class=\"qcc-error\">' + error + '</div>';
                }).join('');
                
                if (container) {
                    container.innerHTML = errorHtml;
                }
            }
        };
        ";
    }
    
    /**
     * Get charts JavaScript
     */
    private function get_charts_js() {
        return "
        {$this->config['namespace']}.Charts = {
            
            chartInstance: null,
            
            createChart: function(canvas, data) {
                if (!window.Chart) {
                    {$this->config['namespace']}.log('Chart.js not available');
                    return null;
                }
                
                if (this.chartInstance) {
                    this.chartInstance.destroy();
                }
                
                var ctx = canvas.getContext('2d');
                
                this.chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Prevention', 'Appraisal', 'Internal Defects', 'External Defects'],
                        datasets: [{
                            label: 'Quality Costs',
                            data: [
                                data.preventionCost,
                                data.appraisalCost,
                                data.internalDefectCost,
                                data.externalDefectCost
                            ],
                            backgroundColor: [
                                '#28a745',
                                '#17a2b8',
                                '#ffc107',
                                '#dc3545'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
                
                return this.chartInstance;
            },
            
            updateChart: function(data) {
                if (this.chartInstance) {
                    this.chartInstance.data.datasets[0].data = [
                        data.preventionCost,
                        data.appraisalCost,
                        data.internalDefectCost,
                        data.externalDefectCost
                    ];
                    this.chartInstance.update();
                }
            }
        };
        ";
    }
    
    /**
     * Get export JavaScript
     */
    private function get_export_js() {
        return "
        {$this->config['namespace']}.Export = {
            
            exportToCSV: function(data, filename) {
                filename = filename || 'quality_cost_calculation.csv';
                
                var csv = 'Parameter,Value\\n';
                csv += 'Revenue,' + data.input.revenue + '\\n';
                csv += 'Quality Percentage,' + data.input.qualityPercentage + '%\\n';
                csv += 'Prevention Cost,' + data.preventionCost + '\\n';
                csv += 'Appraisal Cost,' + data.appraisalCost + '\\n';
                csv += 'Internal Defect Cost,' + data.internalDefectCost + '\\n';
                csv += 'External Defect Cost,' + data.externalDefectCost + '\\n';
                csv += 'Total COGQ,' + data.cogq + '\\n';
                csv += 'Total COPQ,' + data.copq + '\\n';
                csv += 'Total Quality Cost,' + data.totalQualityCost + '\\n';
                
                var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                var link = document.createElement('a');
                
                if (link.download !== undefined) {
                    var url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    {$this->config['namespace']}.trigger('exported', { type: 'csv', filename: filename });
                }
            },
            
            exportToJSON: function(data, filename) {
                filename = filename || 'quality_cost_calculation.json';
                
                var jsonData = JSON.stringify(data, null, 2);
                var blob = new Blob([jsonData], { type: 'application/json;charset=utf-8;' });
                var link = document.createElement('a');
                
                if (link.download !== undefined) {
                    var url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    {$this->config['namespace']}.trigger('exported', { type: 'json', filename: filename });
                }
            }
        };
        ";
    }
    
    /**
     * Get events JavaScript
     */
    private function get_events_js() {
        return "
        {$this->config['namespace']}.Events = {
            
            listeners: {},
            
            on: function(event, callback) {
                if (!this.listeners[event]) {
                    this.listeners[event] = [];
                }
                this.listeners[event].push(callback);
            },
            
            off: function(event, callback) {
                if (this.listeners[event]) {
                    var index = this.listeners[event].indexOf(callback);
                    if (index > -1) {
                        this.listeners[event].splice(index, 1);
                    }
                }
            },
            
            trigger: function(event, data) {
                if (this.listeners[event]) {
                    this.listeners[event].forEach(function(callback) {
                        try {
                            callback(data);
                        } catch (error) {
                            {$this->config['namespace']}.log('Event callback error:', error);
                        }
                    });
                }
            }
        };
        
        // Bind events to main namespace
        {$this->config['namespace']}.on = {$this->config['namespace']}.Events.on.bind({$this->config['namespace']}.Events);
        {$this->config['namespace']}.off = {$this->config['namespace']}.Events.off.bind({$this->config['namespace']}.Events);
        {$this->config['namespace']}.trigger = {$this->config['namespace']}.Events.trigger.bind({$this->config['namespace']}.Events);
        ";
    }
    
    /**
     * Get initialization JavaScript
     */
    private function get_init_js() {
        return "
        {$this->config['namespace']}.init = function(containerId, options) {
            if (this.initialized) {
                this.log('Already initialized');
                return;
            }
            
            options = options || {};
            this.updateConfig(options);
            
            var container = document.getElementById(containerId);
            if (!container) {
                this.log('Container not found:', containerId);
                return;
            }
            
            this.container = container;
            this.bindEvents();
            this.initialized = true;
            
            this.log('Initialized successfully');
            this.trigger('initialized', { container: container, config: this.config });
        };
        
        {$this->config['namespace']}.bindEvents = function() {
            var self = this;
            
            // Form input events
            var inputs = this.container.querySelectorAll('input, select');
            inputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    self.handleInputChange();
                });
                
                input.addEventListener('change', function() {
                    self.handleInputChange();
                });
            });
            
            // Button events
            var calculateBtn = this.container.querySelector('.qcc-calculate-btn');
            if (calculateBtn) {
                calculateBtn.addEventListener('click', function() {
                    self.handleCalculate();
                });
            }
            
            var resetBtn = this.container.querySelector('.qcc-reset-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    self.handleReset();
                });
            }
            
            var exportBtn = this.container.querySelector('.qcc-export-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    self.handleExport();
                });
            }
        };
        
        {$this->config['namespace']}.handleInputChange = function() {
            if (this.config.autoCalculate !== false) {
                this.handleCalculate();
            }
        };
        
        {$this->config['namespace']}.handleCalculate = function() {
            try {
                var input = this.getFormData();
                
                if (this.config.validation) {
                    var validation = this.Validator.validateInput(input);
                    if (!validation.valid) {
                        this.showErrors(validation.errors);
                        return;
                    }
                }
                
                var result = this.Calculator.calculate(input);
                this.displayResults(result);
                
                if (this.config.charts && window.Chart) {
                    var canvas = this.container.querySelector('.qcc-chart');
                    if (canvas) {
                        this.Charts.createChart(canvas, result);
                    }
                }
                
            } catch (error) {
                this.log('Calculate error:', error);
                this.showErrors(['Calculation failed: ' + error.message]);
            }
        };
        
        {$this->config['namespace']}.getFormData = function() {
            var data = {};
            var inputs = this.container.querySelectorAll('input, select');
            
            inputs.forEach(function(input) {
                var name = input.name || input.id.replace('qcc-', '');
                var value = input.type === 'number' ? parseFloat(input.value) : input.value;
                data[name] = value;
            });
            
            return data;
        };
        
        {$this->config['namespace']}.displayResults = function(result) {
            var elements = {
                'total-quality-cost': result.totalQualityCost,
                'prevention-cost': result.preventionCost,
                'appraisal-cost': result.appraisalCost,
                'internal-defect-cost': result.internalDefectCost,
                'external-defect-cost': result.externalDefectCost,
                'total-cogq': result.cogq,
                'total-copq': result.copq
            };
            
            for (var id in elements) {
                var element = this.container.querySelector('#qcc-' + id);
                if (element) {
                    var formatted = this.Calculator.formatCurrency(elements[id]);
                    element.textContent = formatted;
                }
            }
            
            // Update percentages
            var cogqPercent = this.container.querySelector('#qcc-cogq-percentage');
            if (cogqPercent) {
                cogqPercent.textContent = result.cogqPercentage.toFixed(1) + '%';
            }
            
            var copqPercent = this.container.querySelector('#qcc-copq-percentage');
            if (copqPercent) {
                copqPercent.textContent = result.copqPercentage.toFixed(1) + '%';
            }
        };
        
        {$this->config['namespace']}.showErrors = function(errors) {
            var errorContainer = this.container.querySelector('.qcc-errors');
            if (errorContainer) {
                this.Validator.showValidationErrors(errors, errorContainer);
            }
        };
        
        {$this->config['namespace']}.handleReset = function() {
            if (confirm('Reset all values to defaults?')) {
                this.resetForm();
                this.trigger('reset');
            }
        };
        
        {$this->config['namespace']}.resetForm = function() {
            var inputs = this.container.querySelectorAll('input');
            inputs.forEach(function(input) {
                if (input.dataset.default) {
                    input.value = input.dataset.default;
                }
            });
            
            this.handleCalculate();
        };
        
        {$this->config['namespace']}.handleExport = function() {
            var result = this.lastResult || this.Calculator.calculate(this.getFormData());
            this.Export.exportToCSV(result);
        };
        
        {$this->config['namespace']}.log = function() {
            if (this.debug && window.console) {
                console.log.apply(console, ['QCC:'].concat(Array.prototype.slice.call(arguments)));
            }
        };
        
        // Auto-initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            var calculators = document.querySelectorAll('.qcc-calculator');
            calculators.forEach(function(calculator, index) {
                var instanceName = 'instance_' + index;
                {$this->config['namespace']}.instances[instanceName] = Object.create({$this->config['namespace']});
                {$this->config['namespace']}.instances[instanceName].init(calculator.id);
            });
        });
        ";
    }
    
    /**
     * Generate admin JavaScript
     */
    public function generate_admin_js($config = array()) {
        return "
        // QCC Admin JavaScript
        jQuery(document).ready(function($) {
            
            var QCCAdmin = {
                
                init: function() {
                    this.bindEvents();
                    this.initColorPickers();
                },
                
                bindEvents: function() {
                    $('.qcc-admin-form').on('submit', this.handleSave.bind(this));
                    $('.qcc-preview-btn').on('click', this.handlePreview.bind(this));
                    $('.qcc-reset-btn').on('click', this.handleReset.bind(this));
                },
                
                initColorPickers: function() {
                    $('.qcc-color-picker').wpColorPicker();
                },
                
                handleSave: function(e) {
                    e.preventDefault();
                    
                    var form = $(e.target);
                    var data = form.serialize();
                    
                    this.showSpinner();
                    
                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            this.showMessage('Settings saved successfully', 'success');
                        } else {
                            this.showMessage('Save failed: ' + response.data, 'error');
                        }
                        this.hideSpinner();
                    }.bind(this));
                },
                
                handlePreview: function() {
                    var previewWindow = window.open('', 'qcc-preview', 'width=800,height=600');
                    previewWindow.document.write('<h1>Calculator Preview</h1><p>Loading...</p>');
                },
                
                showMessage: function(message, type) {
                    var notice = $('<div class=\"notice notice-' + type + ' is-dismissible\"><p>' + message + '</p></div>');
                    $('.wrap h1').after(notice);
                    
                    setTimeout(function() {
                        notice.fadeOut();
                    }, 3000);
                },
                
                showSpinner: function() {
                    $('.qcc-spinner').show();
                },
                
                hideSpinner: function() {
                    $('.qcc-spinner').hide();
                }
            };
            
            QCCAdmin.init();
        });
        ";
    }
    
    /**
     * Minify JavaScript
     */
    public function minify_js($js) {
        // Basic minification
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove comments
        $js = preg_replace('/\/\/.*/', '', $js); // Remove single line comments
        $js = preg_replace('/\s+/', ' ', $js); // Reduce whitespace
        $js = str_replace(array(' {', '{ ', ' }', '} ', ' ;', '; '), array('{', '{', '}', '}', ';', ';'), $js);
        
        return trim($js);
    }
    
    /**
     * Get feature flag value
     */
    private function get_feature_flag($flag_name, $default = false) {
        if ($this->bootstrap) {
            return $this->bootstrap->get_feature_flag($flag_name);
        }
        return $default;
    }
    
    /**
     * Clear JavaScript cache
     */
    public function clear_cache() {
        $this->js_cache = array();
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        return array(
            'cached_items' => count($this->js_cache),
            'memory_usage' => memory_get_usage(true)
        );
    }
}
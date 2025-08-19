<?php
/**
 * QCC Chinese Translations
 * 
 * Chinese language translations for the Quality Cost Calculator plugin.
 * Complete Chinese localization with proper formatting rules.
 * 
 * @package QualityCostCalculator
 * @subpackage Translation/Languages
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Chinese Translations Class
 * 
 * Provides all Chinese translations for the QCC plugin.
 * Includes Chinese-specific formatting and cultural adaptations.
 */
class QCC_Chinese_Translations {
    
    /**
     * Get all Chinese translations
     * 
     * @return array Complete translation array
     */
    public function get_translations() {
        return array(
            // General UI Elements
            'plugin_name' => '质量成本计算器',
            'plugin_description' => '计算优质成本（COGQ）和劣质成本（COPQ）',
            'calculate' => '计算',
            'reset' => '重置',
            'clear' => '清除',
            'submit' => '提交',
            'cancel' => '取消',
            'save' => '保存',
            'delete' => '删除',
            'edit' => '编辑',
            'update' => '更新',
            'close' => '关闭',
            'back' => '返回',
            'next' => '下一步',
            'previous' => '上一步',
            'loading' => '加载中...',
            'please_wait' => '请稍候...',
            'error' => '错误',
            'warning' => '警告',
            'success' => '成功',
            'info' => '信息',
            
            // Form Labels
            'prevention_costs' => '预防成本',
            'appraisal_costs' => '评估成本',
            'internal_defect_costs' => '内部缺陷成本',
            'external_defect_costs' => '外部缺陷成本',
            'currency' => '货币',
            'unit' => '单位',
            'language' => '语言',
            
            // Form Placeholders
            'enter_prevention_costs' => '输入预防成本',
            'enter_appraisal_costs' => '输入评估成本',
            'enter_internal_defects' => '输入内部缺陷成本',
            'enter_external_defects' => '输入外部缺陷成本',
            'select_currency' => '选择货币',
            'select_unit' => '选择单位',
            'select_language' => '选择语言',
            
            // Form Help Text
            'prevention_costs_help' => '投资于预防缺陷的成本（培训、规划、设计评审）',
            'appraisal_costs_help' => '质量评估成本（检查、测试、审核）',
            'internal_defects_help' => '交付前发现的缺陷成本（返工、报废、停机时间）',
            'external_defects_help' => '交付后发现的缺陷成本（保修、退货、投诉）',
            
            // Results Labels
            'calculation_results' => '计算结果',
            'cost_of_good_quality' => '优质成本（COGQ）',
            'cost_of_poor_quality' => '劣质成本（COPQ）',
            'total_quality_costs' => '总质量成本',
            'cogq_percentage' => 'COGQ百分比',
            'copq_percentage' => 'COPQ百分比',
            'prevention_percentage' => '预防百分比',
            'appraisal_percentage' => '评估百分比',
            'internal_defects_percentage' => '内部缺陷百分比',
            'external_defects_percentage' => '外部缺陷百分比',
            
            // Chart Labels
            'quality_cost_breakdown' => '质量成本分解',
            'cogq_vs_copq' => 'COGQ vs COPQ',
            'cost_categories' => '成本类别',
            'prevention' => '预防',
            'appraisal' => '评估',
            'internal_defects' => '内部缺陷',
            'external_defects' => '外部缺陷',
            
            // Validation Messages
            'field_required' => '此字段为必填项',
            'invalid_number' => '请输入有效数字',
            'number_too_small' => '数字必须大于或等于{min}',
            'number_too_large' => '数字必须小于或等于{max}',
            'invalid_currency' => '请选择有效货币',
            'invalid_unit' => '请选择有效单位',
            'calculation_error' => '计算过程中发生错误',
            'validation_failed' => '验证失败',
            'all_costs_zero' => '至少一个成本必须大于零',
            
            // Error Messages
            'general_error' => '发生意外错误',
            'network_error' => '网络错误，请检查连接',
            'server_error' => '服务器错误，请稍后重试',
            'timeout_error' => '请求超时，请重试',
            'permission_denied' => '权限被拒绝',
            'not_found' => '资源未找到',
            'invalid_request' => '无效请求',
            'maintenance_mode' => '系统目前正在维护中',
            
            // Success Messages
            'calculation_complete' => '计算成功完成',
            'data_saved' => '数据保存成功',
            'settings_updated' => '设置更新成功',
            'export_complete' => '导出成功完成',
            'import_complete' => '导入成功完成',
            
            // Export/Import
            'export' => '导出',
            'import' => '导入',
            'export_data' => '导出数据',
            'import_data' => '导入数据',
            'export_format' => '导出格式',
            'select_file' => '选择文件',
            'download' => '下载',
            'upload' => '上传',
            'file_size_limit' => '最大文件大小：{size}',
            'supported_formats' => '支持的格式：{formats}',
            
            // Units
            'units' => '单位',
            'unit_1' => '个',
            'unit_1000' => '千',
            'unit_1000000' => '百万',
            
            // Currencies
            'usd' => '美元',
            'eur' => '欧元',
            'gbp' => '英镑',
            'jpy' => '日元',
            'cad' => '加元',
            'aud' => '澳元',
            'chf' => '瑞士法郎',
            'cny' => '人民币',
            'sek' => '瑞典克朗',
            'nok' => '挪威克朗',
            'dkk' => '丹麦克朗',
            
            // Languages
            'english' => '英语',
            'german' => '德语',
            'french' => '法语',
            'spanish' => '西班牙语',
            'chinese' => '中文',
            
            // Admin Interface
            'settings' => '设置',
            'general_settings' => '常规设置',
            'calculation_settings' => '计算设置',
            'display_settings' => '显示设置',
            'advanced_settings' => '高级设置',
            'plugin_settings' => '插件设置',
            'save_settings' => '保存设置',
            'reset_settings' => '重置设置',
            'backup_settings' => '备份设置',
            'restore_settings' => '恢复设置',
            
            // Dashboard
            'dashboard' => '仪表板',
            'overview' => '概览',
            'statistics' => '统计',
            'recent_calculations' => '最近计算',
            'usage_statistics' => '使用统计',
            'system_status' => '系统状态',
            'performance_metrics' => '性能指标',
            
            // Help & Documentation
            'help' => '帮助',
            'documentation' => '文档',
            'user_guide' => '用户指南',
            'faq' => '常见问题',
            'support' => '支持',
            'contact' => '联系',
            'about' => '关于',
            'version' => '版本',
            
            // Quality Terms & Definitions
            'cogq_definition' => '优质成本：预防和评估活动的投资',
            'copq_definition' => '劣质成本：内部和外部故障的成本',
            'prevention_definition' => '预防缺陷发生的活动',
            'appraisal_definition' => '评估和检查质量的活动',
            'internal_failure_definition' => '交付给客户前发现的缺陷成本',
            'external_failure_definition' => '交付给客户后发现的缺陷成本',
            
            // Calculation Types
            'basic_calculation' => '基本计算',
            'advanced_calculation' => '高级计算',
            'detailed_analysis' => '详细分析',
            'trend_analysis' => '趋势分析',
            'comparison_analysis' => '比较分析',
            'roi_analysis' => 'ROI分析',
            
            // Time Periods
            'daily' => '每日',
            'weekly' => '每周',
            'monthly' => '每月',
            'quarterly' => '每季度',
            'yearly' => '每年',
            'custom_period' => '自定义周期',
            
            // Status Messages
            'status_active' => '活跃',
            'status_inactive' => '非活跃',
            'status_pending' => '待处理',
            'status_completed' => '已完成',
            'status_failed' => '失败',
            'status_processing' => '处理中',
            'status_cancelled' => '已取消',
            
            // Actions
            'view' => '查看',
            'print' => '打印',
            'share' => '分享',
            'copy' => '复制',
            'paste' => '粘贴',
            'duplicate' => '复制',
            'archive' => '归档',
            'restore' => '恢复',
            'refresh' => '刷新',
            'reload' => '重新加载',
            
            // Navigation
            'home' => '首页',
            'calculator' => '计算器',
            'reports' => '报告',
            'tools' => '工具',
            'preferences' => '偏好设置',
            'account' => '账户',
            'logout' => '登出',
            'login' => '登录',
            
            // Formatting
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'datetime_format' => 'Y-m-d H:i:s',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            
            // Tooltips
            'tooltip_prevention' => '包括培训、流程设计和质量规划的成本',
            'tooltip_appraisal' => '包括测试、检查和质量审核的成本',
            'tooltip_internal' => '包括返工、废料和生产延误的成本',
            'tooltip_external' => '包括保修、退货和客户投诉的成本',
            'tooltip_currency' => '选择所有货币值的货币',
            'tooltip_unit' => '选择显示大数字的单位比例',
            
            // Notifications
            'notification_saved' => '您的更改已保存',
            'notification_deleted' => '项目已删除',
            'notification_exported' => '数据导出成功',
            'notification_imported' => '数据导入成功',
            'notification_error' => '处理您的请求时发生错误',
            'notification_warning' => '请检查您的输入数据',
            'notification_info' => '有其他信息可用',
            
            // Placeholders for Dynamic Content
            'no_data_available' => '无可用数据',
            'no_results_found' => '未找到结果',
            'no_calculations_yet' => '尚未进行任何计算',
            'empty_state_message' => '开始输入您的质量成本数据',
            'loading_calculations' => '加载计算中...',
            'processing_request' => '处理您的请求中...',
            
            // Accessibility
            'accessibility_calculate_button' => '计算质量成本',
            'accessibility_reset_button' => '重置所有表单字段',
            'accessibility_export_button' => '导出计算结果',
            'accessibility_help_button' => '打开帮助文档',
            'accessibility_close_button' => '关闭对话框',
            'accessibility_menu_button' => '打开导航菜单',
            
            // API Messages
            'api_success' => 'API请求成功完成',
            'api_error' => 'API请求失败',
            'api_timeout' => 'API请求超时',
            'api_unauthorized' => 'API访问未授权',
            'api_rate_limit' => 'API速率限制超出',
            'api_maintenance' => 'API正在维护中',
            
            // Comparison & Analysis
            'compare' => '比较',
            'comparison' => '比较',
            'benchmark' => '基准',
            'target' => '目标',
            'actual' => '实际',
            'variance' => '差异',
            'improvement' => '改进',
            'deterioration' => '恶化',
            'trend_up' => '上升趋势',
            'trend_down' => '下降趋势',
            'trend_stable' => '稳定',
            
            // Reports
            'generate_report' => '生成报告',
            'report_title' => '质量成本报告',
            'report_period' => '报告期间',
            'report_summary' => '执行摘要',
            'report_details' => '详细分析',
            'report_recommendations' => '建议',
            'report_conclusion' => '结论',
            
            // Quality Metrics
            'quality_ratio' => '质量比率',
            'efficiency_score' => '效率评分',
            'performance_index' => '性能指数',
            'cost_effectiveness' => '成本效益',
            'return_on_investment' => '投资回报',
            'cost_per_unit' => '单位成本',
            'defect_rate' => '缺陷率',
            'yield_rate' => '产出率',
            
            // Plurals (Chinese doesn't have typical plural forms)
            'calculation_singular' => '计算',
            'calculation_plural' => '计算',
            'result_singular' => '结果',
            'result_plural' => '结果',
            'error_singular' => '错误',
            'error_plural' => '错误',
            'warning_singular' => '警告',
            'warning_plural' => '警告',
            'item_singular' => '项目',
            'item_plural' => '项目',
            'record_singular' => '记录',
            'record_plural' => '记录',
            
            // Time Expressions
            'seconds_ago' => '{count}秒前',
            'minutes_ago' => '{count}分钟前',
            'hours_ago' => '{count}小时前',
            'days_ago' => '{count}天前',
            'weeks_ago' => '{count}周前',
            'months_ago' => '{count}个月前',
            'years_ago' => '{count}年前',
            'just_now' => '刚刚',
            
            // System Messages
            'system_healthy' => '系统运行正常',
            'system_warning' => '检测到系统警告',
            'system_error' => '检测到系统错误',
            'system_maintenance' => '系统维护进行中',
            'system_offline' => '系统当前离线',
            'system_updating' => '系统正在更新',
            
            // Advanced Features
            'advanced_mode' => '高级模式',
            'expert_mode' => '专家模式',
            'professional_mode' => '专业模式',
            'custom_formula' => '自定义公式',
            'calculation_method' => '计算方法',
            'precision_level' => '精度级别',
            'rounding_method' => '舍入方法',
            
            // Integration
            'integration' => '集成',
            'api_access' => 'API访问',
            'webhook' => 'Webhook',
            'automation' => '自动化',
            'sync' => '同步',
            'connection' => '连接',
            'authentication' => '身份验证',
            'authorization' => '授权',
            
            // Business Terms (Chinese-specific)
            'six_sigma' => '六西格玛',
            'lean_manufacturing' => '精益制造',
            'total_quality_management' => '全面质量管理',
            'continuous_improvement' => '持续改进',
            'quality_assurance' => '质量保证',
            'quality_control' => '质量控制',
            'defect_prevention' => '缺陷预防',
            'process_improvement' => '流程改进',
            'customer_satisfaction' => '客户满意度',
            'operational_excellence' => '卓越运营',
            
            // Chinese-specific Quality Terms
            'kaizen' => '改善',
            'poka_yoke' => '防错',
            'gemba' => '现场',
            'hoshin_kanri' => '方针管理',
            'jidoka' => '自働化',
            'just_in_time' => '准时制',
            'zero_defects' => '零缺陷',
            'first_pass_yield' => '一次通过率',
            'right_first_time' => '一次做对',
            'voice_of_customer' => '客户之声',
            
            // Manufacturing Terms
            'production_line' => '生产线',
            'assembly_line' => '装配线',
            'work_station' => '工作站',
            'batch_size' => '批量大小',
            'cycle_time' => '周期时间',
            'lead_time' => '交付周期',
            'throughput' => '吞吐量',
            'capacity_utilization' => '产能利用率',
            'overall_equipment_effectiveness' => '设备综合效率',
            'mean_time_between_failures' => '平均故障间隔时间',
            
            // Quality Metrics (Extended)
            'customer_complaints' => '客户投诉',
            'warranty_claims' => '保修索赔',
            'return_rate' => '退货率',
            'scrap_rate' => '报废率',
            'rework_rate' => '返工率',
            'inspection_rate' => '检验率',
            'audit_score' => '审核评分',
            'supplier_quality' => '供应商质量',
            'incoming_quality' => '进料质量',
            'outgoing_quality' => '出货质量',
            
            // Cost Categories (Detailed)
            'direct_labor_cost' => '直接人工成本',
            'indirect_labor_cost' => '间接人工成本',
            'material_cost' => '材料成本',
            'overhead_cost' => '间接成本',
            'equipment_cost' => '设备成本',
            'training_cost' => '培训成本',
            'documentation_cost' => '文档成本',
            'testing_cost' => '测试成本',
            'inspection_cost' => '检验成本',
            'calibration_cost' => '校准成本',
            
            // Footer
            'powered_by' => '由Quality Cost Calculator提供支持',
            'copyright' => '版权所有 © {year} 保留所有权利',
            'privacy_policy' => '隐私政策',
            'terms_of_service' => '服务条款',
            'contact_us' => '联系我们',
            
            // Cultural Adaptations
            'respectful_greeting' => '您好',
            'polite_request' => '请',
            'thank_you' => '谢谢',
            'you_are_welcome' => '不客气',
            'excuse_me' => '不好意思',
            'sorry' => '对不起',
            'please_wait_patiently' => '请耐心等待',
            'thank_you_for_using' => '感谢您使用',
            
            // Units of Measurement (Chinese)
            'rmb' => '人民币',
            'wan' => '万',
            'yi' => '亿',
            'yuan' => '元',
            'jiao' => '角',
            'fen' => '分',
            
            // Time Units (Chinese)
            'year' => '年',
            'month' => '月',
            'week' => '周',
            'day' => '日',
            'hour' => '小时',
            'minute' => '分钟',
            'second' => '秒',
            
            // Directions
            'up' => '上',
            'down' => '下',
            'left' => '左',
            'right' => '右',
            'center' => '中心',
            'top' => '顶部',
            'bottom' => '底部',
            
            // Colors (for charts and UI)
            'red' => '红色',
            'blue' => '蓝色',
            'green' => '绿色',
            'yellow' => '黄色',
            'orange' => '橙色',
            'purple' => '紫色',
            'pink' => '粉色',
            'brown' => '棕色',
            'black' => '黑色',
            'white' => '白色',
            'gray' => '灰色'
        );
    }
    
    /**
     * Get language metadata
     * 
     * @return array Language information
     */
    public function get_language_info() {
        return array(
            'code' => 'zh',
            'name' => 'Chinese',
            'native_name' => '中文',
            'flag' => '🇨🇳',
            'rtl' => false,
            'completion' => 90,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'currency_position' => 'before',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'cultural_notes' => array(
                'formal_address' => true,
                'respect_hierarchy' => true,
                'number_preferences' => array(
                    'avoid_4' => true, // 4 is considered unlucky
                    'prefer_8' => true, // 8 is considered lucky
                    'use_traditional_units' => array('万', '亿')
                )
            )
        );
    }
}
<?php
/**
 * QCC Spanish Translations
 * 
 * Spanish language translations for the Quality Cost Calculator plugin.
 * Complete Spanish localization with proper formatting rules.
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
 * QCC Spanish Translations Class
 * 
 * Provides all Spanish translations for the QCC plugin.
 * Includes Spanish-specific formatting and pluralization rules.
 */
class QCC_Spanish_Translations {
    
    /**
     * Get all Spanish translations
     * 
     * @return array Complete translation array
     */
    public function get_translations() {
        return array(
            // General UI Elements
            'plugin_name' => 'Calculadora de Costos de Calidad',
            'plugin_description' => 'Calcular el Costo de la Buena Calidad (COGQ) y el Costo de la Mala Calidad (COPQ)',
            'calculate' => 'Calcular',
            'reset' => 'Restablecer',
            'clear' => 'Limpiar',
            'submit' => 'Enviar',
            'cancel' => 'Cancelar',
            'save' => 'Guardar',
            'delete' => 'Eliminar',
            'edit' => 'Editar',
            'update' => 'Actualizar',
            'close' => 'Cerrar',
            'back' => 'Atrás',
            'next' => 'Siguiente',
            'previous' => 'Anterior',
            'loading' => 'Cargando...',
            'please_wait' => 'Por favor espere...',
            'error' => 'Error',
            'warning' => 'Advertencia',
            'success' => 'Éxito',
            'info' => 'Información',
            
            // Form Labels
            'prevention_costs' => 'Costos de Prevención',
            'appraisal_costs' => 'Costos de Evaluación',
            'internal_defect_costs' => 'Costos de Defectos Internos',
            'external_defect_costs' => 'Costos de Defectos Externos',
            'currency' => 'Moneda',
            'unit' => 'Unidad',
            'language' => 'Idioma',
            
            // Form Placeholders
            'enter_prevention_costs' => 'Ingrese los costos de prevención',
            'enter_appraisal_costs' => 'Ingrese los costos de evaluación',
            'enter_internal_defects' => 'Ingrese los costos de defectos internos',
            'enter_external_defects' => 'Ingrese los costos de defectos externos',
            'select_currency' => 'Seleccione la moneda',
            'select_unit' => 'Seleccione la unidad',
            'select_language' => 'Seleccione el idioma',
            
            // Form Help Text
            'prevention_costs_help' => 'Costos invertidos en prevenir defectos (capacitación, planificación, revisión de diseño)',
            'appraisal_costs_help' => 'Costos de evaluación de calidad (inspección, pruebas, auditoría)',
            'internal_defects_help' => 'Costos de defectos encontrados antes de la entrega (retrabajo, desperdicio, tiempo de inactividad)',
            'external_defects_help' => 'Costos de defectos encontrados después de la entrega (garantía, devoluciones, quejas)',
            
            // Results Labels
            'calculation_results' => 'Resultados del Cálculo',
            'cost_of_good_quality' => 'Costo de la Buena Calidad (COGQ)',
            'cost_of_poor_quality' => 'Costo de la Mala Calidad (COPQ)',
            'total_quality_costs' => 'Costos Totales de Calidad',
            'cogq_percentage' => 'Porcentaje COGQ',
            'copq_percentage' => 'Porcentaje COPQ',
            'prevention_percentage' => 'Porcentaje de Prevención',
            'appraisal_percentage' => 'Porcentaje de Evaluación',
            'internal_defects_percentage' => 'Porcentaje de Defectos Internos',
            'external_defects_percentage' => 'Porcentaje de Defectos Externos',
            
            // Chart Labels
            'quality_cost_breakdown' => 'Desglose de Costos de Calidad',
            'cogq_vs_copq' => 'COGQ vs COPQ',
            'cost_categories' => 'Categorías de Costos',
            'prevention' => 'Prevención',
            'appraisal' => 'Evaluación',
            'internal_defects' => 'Defectos Internos',
            'external_defects' => 'Defectos Externos',
            
            // Validation Messages
            'field_required' => 'Este campo es obligatorio',
            'invalid_number' => 'Por favor ingrese un número válido',
            'number_too_small' => 'El número debe ser mayor o igual a {min}',
            'number_too_large' => 'El número debe ser menor o igual a {max}',
            'invalid_currency' => 'Por favor seleccione una moneda válida',
            'invalid_unit' => 'Por favor seleccione una unidad válida',
            'calculation_error' => 'Ocurrió un error durante el cálculo',
            'validation_failed' => 'La validación falló',
            'all_costs_zero' => 'Al menos un costo debe ser mayor que cero',
            
            // Error Messages
            'general_error' => 'Ocurrió un error inesperado',
            'network_error' => 'Error de red. Por favor verifique su conexión.',
            'server_error' => 'Error del servidor. Por favor intente más tarde.',
            'timeout_error' => 'Tiempo de espera agotado. Por favor intente de nuevo.',
            'permission_denied' => 'Permiso denegado',
            'not_found' => 'Recurso no encontrado',
            'invalid_request' => 'Solicitud inválida',
            'maintenance_mode' => 'El sistema está actualmente en mantenimiento',
            
            // Success Messages
            'calculation_complete' => 'Cálculo completado exitosamente',
            'data_saved' => 'Datos guardados exitosamente',
            'settings_updated' => 'Configuraciones actualizadas exitosamente',
            'export_complete' => 'Exportación completada exitosamente',
            'import_complete' => 'Importación completada exitosamente',
            
            // Export/Import
            'export' => 'Exportar',
            'import' => 'Importar',
            'export_data' => 'Exportar datos',
            'import_data' => 'Importar datos',
            'export_format' => 'Formato de exportación',
            'select_file' => 'Seleccionar archivo',
            'download' => 'Descargar',
            'upload' => 'Subir',
            'file_size_limit' => 'Tamaño máximo de archivo: {size}',
            'supported_formats' => 'Formatos soportados: {formats}',
            
            // Units
            'units' => 'Unidades',
            'unit_1' => 'Unidades',
            'unit_1000' => 'Miles',
            'unit_1000000' => 'Millones',
            
            // Currencies
            'usd' => 'Dólar estadounidense',
            'eur' => 'Euro',
            'gbp' => 'Libra esterlina',
            'jpy' => 'Yen japonés',
            'cad' => 'Dólar canadiense',
            'aud' => 'Dólar australiano',
            'chf' => 'Franco suizo',
            'cny' => 'Yuan chino',
            'sek' => 'Corona sueca',
            'nok' => 'Corona noruega',
            'dkk' => 'Corona danesa',
            
            // Languages
            'english' => 'Inglés',
            'german' => 'Alemán',
            'french' => 'Francés',
            'spanish' => 'Español',
            'chinese' => 'Chino',
            
            // Admin Interface
            'settings' => 'Configuraciones',
            'general_settings' => 'Configuraciones generales',
            'calculation_settings' => 'Configuraciones de cálculo',
            'display_settings' => 'Configuraciones de visualización',
            'advanced_settings' => 'Configuraciones avanzadas',
            'plugin_settings' => 'Configuraciones del plugin',
            'save_settings' => 'Guardar configuraciones',
            'reset_settings' => 'Restablecer configuraciones',
            'backup_settings' => 'Respaldar configuraciones',
            'restore_settings' => 'Restaurar configuraciones',
            
            // Dashboard
            'dashboard' => 'Panel de control',
            'overview' => 'Vista general',
            'statistics' => 'Estadísticas',
            'recent_calculations' => 'Cálculos recientes',
            'usage_statistics' => 'Estadísticas de uso',
            'system_status' => 'Estado del sistema',
            'performance_metrics' => 'Métricas de rendimiento',
            
            // Help & Documentation
            'help' => 'Ayuda',
            'documentation' => 'Documentación',
            'user_guide' => 'Guía del usuario',
            'faq' => 'Preguntas frecuentes',
            'support' => 'Soporte',
            'contact' => 'Contacto',
            'about' => 'Acerca de',
            'version' => 'Versión',
            
            // Quality Terms & Definitions
            'cogq_definition' => 'Costo de la Buena Calidad: Inversión en actividades de prevención y evaluación',
            'copq_definition' => 'Costo de la Mala Calidad: Costo de fallas internas y externas',
            'prevention_definition' => 'Actividades para prevenir la ocurrencia de defectos',
            'appraisal_definition' => 'Actividades para evaluar e inspeccionar la calidad',
            'internal_failure_definition' => 'Costos de defectos encontrados antes de la entrega al cliente',
            'external_failure_definition' => 'Costos de defectos encontrados después de la entrega al cliente',
            
            // Calculation Types
            'basic_calculation' => 'Cálculo básico',
            'advanced_calculation' => 'Cálculo avanzado',
            'detailed_analysis' => 'Análisis detallado',
            'trend_analysis' => 'Análisis de tendencias',
            'comparison_analysis' => 'Análisis comparativo',
            'roi_analysis' => 'Análisis ROI',
            
            // Time Periods
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
            'custom_period' => 'Período personalizado',
            
            // Status Messages
            'status_active' => 'Activo',
            'status_inactive' => 'Inactivo',
            'status_pending' => 'Pendiente',
            'status_completed' => 'Completado',
            'status_failed' => 'Fallido',
            'status_processing' => 'Procesando',
            'status_cancelled' => 'Cancelado',
            
            // Actions
            'view' => 'Ver',
            'print' => 'Imprimir',
            'share' => 'Compartir',
            'copy' => 'Copiar',
            'paste' => 'Pegar',
            'duplicate' => 'Duplicar',
            'archive' => 'Archivar',
            'restore' => 'Restaurar',
            'refresh' => 'Actualizar',
            'reload' => 'Recargar',
            
            // Navigation
            'home' => 'Inicio',
            'calculator' => 'Calculadora',
            'reports' => 'Reportes',
            'tools' => 'Herramientas',
            'preferences' => 'Preferencias',
            'account' => 'Cuenta',
            'logout' => 'Cerrar sesión',
            'login' => 'Iniciar sesión',
            
            // Formatting
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd/m/Y H:i',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            
            // Tooltips
            'tooltip_prevention' => 'Incluir costos de capacitación, diseño de procesos y planificación de calidad',
            'tooltip_appraisal' => 'Incluir costos de pruebas, inspección y auditorías de calidad',
            'tooltip_internal' => 'Incluir costos de retrabajo, desperdicio y retrasos de producción',
            'tooltip_external' => 'Incluir costos de garantías, devoluciones y quejas de clientes',
            'tooltip_currency' => 'Seleccionar la moneda para todos los valores monetarios',
            'tooltip_unit' => 'Seleccionar la escala de unidad para mostrar números grandes',
            
            // Notifications
            'notification_saved' => 'Sus cambios han sido guardados',
            'notification_deleted' => 'El elemento ha sido eliminado',
            'notification_exported' => 'Los datos han sido exportados exitosamente',
            'notification_imported' => 'Los datos han sido importados exitosamente',
            'notification_error' => 'Ocurrió un error al procesar su solicitud',
            'notification_warning' => 'Por favor revise sus datos de entrada',
            'notification_info' => 'Información adicional está disponible',
            
            // Placeholders for Dynamic Content
            'no_data_available' => 'No hay datos disponibles',
            'no_results_found' => 'No se encontraron resultados',
            'no_calculations_yet' => 'Aún no se han realizado cálculos',
            'empty_state_message' => 'Comience ingresando sus datos de costos de calidad',
            'loading_calculations' => 'Cargando cálculos...',
            'processing_request' => 'Procesando su solicitud...',
            
            // Accessibility
            'accessibility_calculate_button' => 'Calcular costos de calidad',
            'accessibility_reset_button' => 'Restablecer todos los campos del formulario',
            'accessibility_export_button' => 'Exportar resultados del cálculo',
            'accessibility_help_button' => 'Abrir documentación de ayuda',
            'accessibility_close_button' => 'Cerrar diálogo',
            'accessibility_menu_button' => 'Abrir menú de navegación',
            
            // API Messages
            'api_success' => 'Solicitud API completada exitosamente',
            'api_error' => 'Falló la solicitud API',
            'api_timeout' => 'Tiempo de espera de solicitud API agotado',
            'api_unauthorized' => 'Acceso API no autorizado',
            'api_rate_limit' => 'Límite de velocidad API excedido',
            'api_maintenance' => 'API en mantenimiento',
            
            // Comparison & Analysis
            'compare' => 'Comparar',
            'comparison' => 'Comparación',
            'benchmark' => 'Referencia',
            'target' => 'Objetivo',
            'actual' => 'Real',
            'variance' => 'Varianza',
            'improvement' => 'Mejora',
            'deterioration' => 'Deterioro',
            'trend_up' => 'Tendencia al alza',
            'trend_down' => 'Tendencia a la baja',
            'trend_stable' => 'Estable',
            
            // Reports
            'generate_report' => 'Generar reporte',
            'report_title' => 'Reporte de Costos de Calidad',
            'report_period' => 'Período del reporte',
            'report_summary' => 'Resumen ejecutivo',
            'report_details' => 'Análisis detallado',
            'report_recommendations' => 'Recomendaciones',
            'report_conclusion' => 'Conclusión',
            
            // Quality Metrics
            'quality_ratio' => 'Ratio de calidad',
            'efficiency_score' => 'Puntuación de eficiencia',
            'performance_index' => 'Índice de rendimiento',
            'cost_effectiveness' => 'Efectividad de costos',
            'return_on_investment' => 'Retorno de inversión',
            'cost_per_unit' => 'Costo por unidad',
            'defect_rate' => 'Tasa de defectos',
            'yield_rate' => 'Tasa de rendimiento',
            
            // Plurals
            'calculation_singular' => 'cálculo',
            'calculation_plural' => 'cálculos',
            'result_singular' => 'resultado',
            'result_plural' => 'resultados',
            'error_singular' => 'error',
            'error_plural' => 'errores',
            'warning_singular' => 'advertencia',
            'warning_plural' => 'advertencias',
            'item_singular' => 'elemento',
            'item_plural' => 'elementos',
            'record_singular' => 'registro',
            'record_plural' => 'registros',
            
            // Time Expressions
            'seconds_ago' => 'hace {count} segundos',
            'minutes_ago' => 'hace {count} minutos',
            'hours_ago' => 'hace {count} horas',
            'days_ago' => 'hace {count} días',
            'weeks_ago' => 'hace {count} semanas',
            'months_ago' => 'hace {count} meses',
            'years_ago' => 'hace {count} años',
            'just_now' => 'Ahora mismo',
            
            // System Messages
            'system_healthy' => 'El sistema está funcionando normalmente',
            'system_warning' => 'Advertencia del sistema detectada',
            'system_error' => 'Error del sistema detectado',
            'system_maintenance' => 'Mantenimiento del sistema en progreso',
            'system_offline' => 'El sistema está actualmente fuera de línea',
            'system_updating' => 'El sistema se está actualizando',
            
            // Advanced Features
            'advanced_mode' => 'Modo avanzado',
            'expert_mode' => 'Modo experto',
            'professional_mode' => 'Modo profesional',
            'custom_formula' => 'Fórmula personalizada',
            'calculation_method' => 'Método de cálculo',
            'precision_level' => 'Nivel de precisión',
            'rounding_method' => 'Método de redondeo',
            
            // Integration
            'integration' => 'Integración',
            'api_access' => 'Acceso API',
            'webhook' => 'Webhook',
            'automation' => 'Automatización',
            'sync' => 'Sincronización',
            'connection' => 'Conexión',
            'authentication' => 'Autenticación',
            'authorization' => 'Autorización',
            
            // Footer
            'powered_by' => 'Impulsado por Quality Cost Calculator',
            'copyright' => 'Copyright © {year} Todos los derechos reservados',
            'privacy_policy' => 'Política de privacidad',
            'terms_of_service' => 'Términos de servicio',
            'contact_us' => 'Contáctanos'
        );
    }
    
    /**
     * Get language metadata
     * 
     * @return array Language information
     */
    public function get_language_info() {
        return array(
            'code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'flag' => '🇪🇸',
            'rtl' => false,
            'completion' => 100,
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'currency_position' => 'after',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        );
    }
}
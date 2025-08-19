<?php
/**
 * QCC German Translations
 * 
 * German language translations for the Quality Cost Calculator plugin.
 * Complete German localization with proper formatting rules.
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
 * QCC German Translations Class
 * 
 * Provides all German translations for the QCC plugin.
 * Includes German-specific formatting and pluralization rules.
 */
class QCC_German_Translations {
    
    /**
     * Get all German translations
     * 
     * @return array Complete translation array
     */
    public function get_translations() {
        return array(
            // General UI Elements
            'plugin_name' => 'Qualitätskostenrechner',
            'plugin_description' => 'Berechnung der Kosten guter Qualität (COGQ) und der Kosten schlechter Qualität (COPQ)',
            'calculate' => 'Berechnen',
            'reset' => 'Zurücksetzen',
            'clear' => 'Löschen',
            'submit' => 'Senden',
            'cancel' => 'Abbrechen',
            'save' => 'Speichern',
            'delete' => 'Löschen',
            'edit' => 'Bearbeiten',
            'update' => 'Aktualisieren',
            'close' => 'Schließen',
            'back' => 'Zurück',
            'next' => 'Weiter',
            'previous' => 'Vorherige',
            'loading' => 'Wird geladen...',
            'please_wait' => 'Bitte warten...',
            'error' => 'Fehler',
            'warning' => 'Warnung',
            'success' => 'Erfolg',
            'info' => 'Information',
            
            // Form Labels
            'prevention_costs' => 'Präventionskosten',
            'appraisal_costs' => 'Prüfkosten',
            'internal_defect_costs' => 'Interne Fehlerkosten',
            'external_defect_costs' => 'Externe Fehlerkosten',
            'currency' => 'Währung',
            'unit' => 'Einheit',
            'language' => 'Sprache',
            
            // Form Placeholders
            'enter_prevention_costs' => 'Präventionskosten eingeben',
            'enter_appraisal_costs' => 'Prüfkosten eingeben',
            'enter_internal_defects' => 'Interne Fehlerkosten eingeben',
            'enter_external_defects' => 'Externe Fehlerkosten eingeben',
            'select_currency' => 'Währung auswählen',
            'select_unit' => 'Einheit auswählen',
            'select_language' => 'Sprache auswählen',
            
            // Form Help Text
            'prevention_costs_help' => 'Kosten zur Fehlervermeidung (Schulungen, Planung, Design-Review)',
            'appraisal_costs_help' => 'Kosten zur Qualitätsbewertung (Prüfung, Tests, Audits)',
            'internal_defects_help' => 'Kosten für Fehler vor Auslieferung (Nacharbeit, Ausschuss, Stillstand)',
            'external_defects_help' => 'Kosten für Fehler nach Auslieferung (Garantie, Rücksendungen, Beschwerden)',
            
            // Results Labels
            'calculation_results' => 'Berechnungsergebnisse',
            'cost_of_good_quality' => 'Kosten guter Qualität (COGQ)',
            'cost_of_poor_quality' => 'Kosten schlechter Qualität (COPQ)',
            'total_quality_costs' => 'Gesamte Qualitätskosten',
            'cogq_percentage' => 'COGQ Prozentsatz',
            'copq_percentage' => 'COPQ Prozentsatz',
            'prevention_percentage' => 'Präventionsanteil',
            'appraisal_percentage' => 'Prüfkostenanteil',
            'internal_defects_percentage' => 'Interne Fehlerkosten Anteil',
            'external_defects_percentage' => 'Externe Fehlerkosten Anteil',
            
            // Chart Labels
            'quality_cost_breakdown' => 'Qualitätskostenverteilung',
            'cogq_vs_copq' => 'COGQ vs COPQ',
            'cost_categories' => 'Kostenkategorien',
            'prevention' => 'Prävention',
            'appraisal' => 'Prüfung',
            'internal_defects' => 'Interne Fehler',
            'external_defects' => 'Externe Fehler',
            
            // Validation Messages
            'field_required' => 'Dieses Feld ist erforderlich',
            'invalid_number' => 'Bitte geben Sie eine gültige Zahl ein',
            'number_too_small' => 'Die Zahl muss größer oder gleich {min} sein',
            'number_too_large' => 'Die Zahl muss kleiner oder gleich {max} sein',
            'invalid_currency' => 'Bitte wählen Sie eine gültige Währung',
            'invalid_unit' => 'Bitte wählen Sie eine gültige Einheit',
            'calculation_error' => 'Ein Fehler ist bei der Berechnung aufgetreten',
            'validation_failed' => 'Validierung fehlgeschlagen',
            'all_costs_zero' => 'Mindestens ein Kostenwert muss größer als null sein',
            
            // Error Messages
            'general_error' => 'Ein unerwarteter Fehler ist aufgetreten',
            'network_error' => 'Netzwerkfehler. Bitte überprüfen Sie Ihre Verbindung.',
            'server_error' => 'Serverfehler. Bitte versuchen Sie es später erneut.',
            'timeout_error' => 'Zeitüberschreitung. Bitte versuchen Sie es erneut.',
            'permission_denied' => 'Zugriff verweigert',
            'not_found' => 'Ressource nicht gefunden',
            'invalid_request' => 'Ungültige Anfrage',
            'maintenance_mode' => 'Das System befindet sich derzeit in Wartung',
            
            // Success Messages
            'calculation_complete' => 'Berechnung erfolgreich abgeschlossen',
            'data_saved' => 'Daten erfolgreich gespeichert',
            'settings_updated' => 'Einstellungen erfolgreich aktualisiert',
            'export_complete' => 'Export erfolgreich abgeschlossen',
            'import_complete' => 'Import erfolgreich abgeschlossen',
            
            // Export/Import
            'export' => 'Exportieren',
            'import' => 'Importieren',
            'export_data' => 'Daten exportieren',
            'import_data' => 'Daten importieren',
            'export_format' => 'Exportformat',
            'select_file' => 'Datei auswählen',
            'download' => 'Herunterladen',
            'upload' => 'Hochladen',
            'file_size_limit' => 'Maximale Dateigröße: {size}',
            'supported_formats' => 'Unterstützte Formate: {formats}',
            
            // Units
            'units' => 'Einheiten',
            'unit_1' => 'Einer',
            'unit_1000' => 'Tausender',
            'unit_1000000' => 'Millionen',
            
            // Currencies
            'usd' => 'US-Dollar',
            'eur' => 'Euro',
            'gbp' => 'Britisches Pfund',
            'jpy' => 'Japanischer Yen',
            'cad' => 'Kanadischer Dollar',
            'aud' => 'Australischer Dollar',
            'chf' => 'Schweizer Franken',
            'cny' => 'Chinesischer Yuan',
            'sek' => 'Schwedische Krone',
            'nok' => 'Norwegische Krone',
            'dkk' => 'Dänische Krone',
            
            // Languages
            'english' => 'Englisch',
            'german' => 'Deutsch',
            'french' => 'Französisch',
            'spanish' => 'Spanisch',
            'chinese' => 'Chinesisch',
            
            // Admin Interface
            'settings' => 'Einstellungen',
            'general_settings' => 'Allgemeine Einstellungen',
            'calculation_settings' => 'Berechnungseinstellungen',
            'display_settings' => 'Anzeigeeinstellungen',
            'advanced_settings' => 'Erweiterte Einstellungen',
            'plugin_settings' => 'Plugin-Einstellungen',
            'save_settings' => 'Einstellungen speichern',
            'reset_settings' => 'Einstellungen zurücksetzen',
            'backup_settings' => 'Einstellungen sichern',
            'restore_settings' => 'Einstellungen wiederherstellen',
            
            // Dashboard
            'dashboard' => 'Dashboard',
            'overview' => 'Übersicht',
            'statistics' => 'Statistiken',
            'recent_calculations' => 'Aktuelle Berechnungen',
            'usage_statistics' => 'Nutzungsstatistiken',
            'system_status' => 'Systemstatus',
            'performance_metrics' => 'Leistungskennzahlen',
            
            // Help & Documentation
            'help' => 'Hilfe',
            'documentation' => 'Dokumentation',
            'user_guide' => 'Benutzerhandbuch',
            'faq' => 'Häufig gestellte Fragen',
            'support' => 'Support',
            'contact' => 'Kontakt',
            'about' => 'Über',
            'version' => 'Version',
            
            // Quality Terms & Definitions
            'cogq_definition' => 'Kosten guter Qualität: Investition in Präventions- und Prüfaktivitäten',
            'copq_definition' => 'Kosten schlechter Qualität: Kosten interner und externer Fehler',
            'prevention_definition' => 'Aktivitäten zur Fehlervermeidung',
            'appraisal_definition' => 'Aktivitäten zur Qualitätsbewertung und -prüfung',
            'internal_failure_definition' => 'Kosten für Fehler vor Lieferung an den Kunden',
            'external_failure_definition' => 'Kosten für Fehler nach Lieferung an den Kunden',
            
            // Calculation Types
            'basic_calculation' => 'Grundberechnung',
            'advanced_calculation' => 'Erweiterte Berechnung',
            'detailed_analysis' => 'Detaillierte Analyse',
            'trend_analysis' => 'Trendanalyse',
            'comparison_analysis' => 'Vergleichsanalyse',
            'roi_analysis' => 'ROI-Analyse',
            
            // Time Periods
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich',
            'monthly' => 'Monatlich',
            'quarterly' => 'Vierteljährlich',
            'yearly' => 'Jährlich',
            'custom_period' => 'Benutzerdefinierter Zeitraum',
            
            // Status Messages
            'status_active' => 'Aktiv',
            'status_inactive' => 'Inaktiv',
            'status_pending' => 'Ausstehend',
            'status_completed' => 'Abgeschlossen',
            'status_failed' => 'Fehlgeschlagen',
            'status_processing' => 'Wird bearbeitet',
            'status_cancelled' => 'Abgebrochen',
            
            // Actions
            'view' => 'Anzeigen',
            'print' => 'Drucken',
            'share' => 'Teilen',
            'copy' => 'Kopieren',
            'paste' => 'Einfügen',
            'duplicate' => 'Duplizieren',
            'archive' => 'Archivieren',
            'restore' => 'Wiederherstellen',
            'refresh' => 'Aktualisieren',
            'reload' => 'Neu laden',
            
            // Navigation
            'home' => 'Startseite',
            'calculator' => 'Rechner',
            'reports' => 'Berichte',
            'tools' => 'Werkzeuge',
            'preferences' => 'Einstellungen',
            'account' => 'Konto',
            'logout' => 'Abmelden',
            'login' => 'Anmelden',
            
            // Formatting
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd.m.Y H:i',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            
            // Tooltips
            'tooltip_prevention' => 'Kosten für Schulungen, Prozessdesign und Qualitätsplanung einbeziehen',
            'tooltip_appraisal' => 'Kosten für Tests, Prüfungen und Qualitätsaudits einbeziehen',
            'tooltip_internal' => 'Kosten für Nacharbeit, Ausschuss und Produktionsverzögerungen einbeziehen',
            'tooltip_external' => 'Kosten für Garantien, Rücksendungen und Kundenbeschwerden einbeziehen',
            'tooltip_currency' => 'Währung für alle Geldbeträge auswählen',
            'tooltip_unit' => 'Einheitenmaßstab für die Anzeige großer Zahlen auswählen',
            
            // Notifications
            'notification_saved' => 'Ihre Änderungen wurden gespeichert',
            'notification_deleted' => 'Element wurde gelöscht',
            'notification_exported' => 'Daten wurden erfolgreich exportiert',
            'notification_imported' => 'Daten wurden erfolgreich importiert',
            'notification_error' => 'Ein Fehler ist bei der Verarbeitung Ihrer Anfrage aufgetreten',
            'notification_warning' => 'Bitte überprüfen Sie Ihre Eingabedaten',
            'notification_info' => 'Zusätzliche Informationen sind verfügbar',
            
            // Placeholders for Dynamic Content
            'no_data_available' => 'Keine Daten verfügbar',
            'no_results_found' => 'Keine Ergebnisse gefunden',
            'no_calculations_yet' => 'Noch keine Berechnungen durchgeführt',
            'empty_state_message' => 'Beginnen Sie mit der Eingabe Ihrer Qualitätskostendaten',
            'loading_calculations' => 'Lade Berechnungen...',
            'processing_request' => 'Verarbeite Ihre Anfrage...',
            
            // Accessibility
            'accessibility_calculate_button' => 'Qualitätskosten berechnen',
            'accessibility_reset_button' => 'Alle Formularfelder zurücksetzen',
            'accessibility_export_button' => 'Berechnungsergebnisse exportieren',
            'accessibility_help_button' => 'Hilfedokumentation öffnen',
            'accessibility_close_button' => 'Dialog schließen',
            'accessibility_menu_button' => 'Navigationsmenü öffnen',
            
            // API Messages
            'api_success' => 'API-Anfrage erfolgreich abgeschlossen',
            'api_error' => 'API-Anfrage fehlgeschlagen',
            'api_timeout' => 'API-Anfrage zeitüberschritten',
            'api_unauthorized' => 'API-Zugriff nicht autorisiert',
            'api_rate_limit' => 'API-Ratenlimit überschritten',
            'api_maintenance' => 'API ist in Wartung',
            
            // Comparison & Analysis
            'compare' => 'Vergleichen',
            'comparison' => 'Vergleich',
            'benchmark' => 'Benchmark',
            'target' => 'Ziel',
            'actual' => 'Ist-Wert',
            'variance' => 'Abweichung',
            'improvement' => 'Verbesserung',
            'deterioration' => 'Verschlechterung',
            'trend_up' => 'Steigender Trend',
            'trend_down' => 'Fallender Trend',
            'trend_stable' => 'Stabil',
            
            // Reports
            'generate_report' => 'Bericht erstellen',
            'report_title' => 'Qualitätskostenbericht',
            'report_period' => 'Berichtszeitraum',
            'report_summary' => 'Zusammenfassung',
            'report_details' => 'Detaillierte Analyse',
            'report_recommendations' => 'Empfehlungen',
            'report_conclusion' => 'Fazit',
            
            // Quality Metrics
            'quality_ratio' => 'Qualitätsverhältnis',
            'efficiency_score' => 'Effizienz-Score',
            'performance_index' => 'Leistungsindex',
            'cost_effectiveness' => 'Kosteneffizienz',
            'return_on_investment' => 'Kapitalrendite',
            'cost_per_unit' => 'Kosten pro Einheit',
            'defect_rate' => 'Fehlerrate',
            'yield_rate' => 'Ausbeute',
            
            // Plurals
            'calculation_singular' => 'Berechnung',
            'calculation_plural' => 'Berechnungen',
            'result_singular' => 'Ergebnis',
            'result_plural' => 'Ergebnisse',
            'error_singular' => 'Fehler',
            'error_plural' => 'Fehler',
            'warning_singular' => 'Warnung',
            'warning_plural' => 'Warnungen',
            'item_singular' => 'Element',
            'item_plural' => 'Elemente',
            'record_singular' => 'Datensatz',
            'record_plural' => 'Datensätze',
            
            // Time Expressions
            'seconds_ago' => 'vor {count} Sekunden',
            'minutes_ago' => 'vor {count} Minuten',
            'hours_ago' => 'vor {count} Stunden',
            'days_ago' => 'vor {count} Tagen',
            'weeks_ago' => 'vor {count} Wochen',
            'months_ago' => 'vor {count} Monaten',
            'years_ago' => 'vor {count} Jahren',
            'just_now' => 'Gerade eben',
            
            // System Messages
            'system_healthy' => 'System funktioniert normal',
            'system_warning' => 'Systemwarnung erkannt',
            'system_error' => 'Systemfehler erkannt',
            'system_maintenance' => 'Systemwartung läuft',
            'system_offline' => 'System ist derzeit offline',
            'system_updating' => 'System wird aktualisiert',
            
            // Advanced Features
            'advanced_mode' => 'Erweiterter Modus',
            'expert_mode' => 'Expertenmodus',
            'professional_mode' => 'Professioneller Modus',
            'custom_formula' => 'Benutzerdefinierte Formel',
            'calculation_method' => 'Berechnungsmethode',
            'precision_level' => 'Genauigkeitsstufe',
            'rounding_method' => 'Rundungsmethode',
            
            // Integration
            'integration' => 'Integration',
            'api_access' => 'API-Zugriff',
            'webhook' => 'Webhook',
            'automation' => 'Automatisierung',
            'sync' => 'Synchronisation',
            'connection' => 'Verbindung',
            'authentication' => 'Authentifizierung',
            'authorization' => 'Autorisierung',
            
            // Footer
            'powered_by' => 'Unterstützt von Quality Cost Calculator',
            'copyright' => 'Copyright © {year} Alle Rechte vorbehalten',
            'privacy_policy' => 'Datenschutzerklärung',
            'terms_of_service' => 'Nutzungsbedingungen',
            'contact_us' => 'Kontaktieren Sie uns'
        );
    }
    
    /**
     * Get language metadata
     * 
     * @return array Language information
     */
    public function get_language_info() {
        return array(
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'flag' => '🇩🇪',
            'rtl' => false,
            'completion' => 100,
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'currency_position' => 'after',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i'
        );
    }
}
<?php
/**
 * QCC French Translations
 * 
 * French language translations for the Quality Cost Calculator plugin.
 * Complete French localization with proper formatting rules.
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
 * QCC French Translations Class
 * 
 * Provides all French translations for the QCC plugin.
 * Includes French-specific formatting and pluralization rules.
 */
class QCC_French_Translations {
    
    /**
     * Get all French translations
     * 
     * @return array Complete translation array
     */
    public function get_translations() {
        return array(
            // General UI Elements
            'plugin_name' => 'Calculateur de Coûts de Qualité',
            'plugin_description' => 'Calculer le Coût de la Bonne Qualité (COGQ) et le Coût de la Mauvaise Qualité (COPQ)',
            'calculate' => 'Calculer',
            'reset' => 'Réinitialiser',
            'clear' => 'Effacer',
            'submit' => 'Soumettre',
            'cancel' => 'Annuler',
            'save' => 'Enregistrer',
            'delete' => 'Supprimer',
            'edit' => 'Modifier',
            'update' => 'Mettre à jour',
            'close' => 'Fermer',
            'back' => 'Retour',
            'next' => 'Suivant',
            'previous' => 'Précédent',
            'loading' => 'Chargement...',
            'please_wait' => 'Veuillez patienter...',
            'error' => 'Erreur',
            'warning' => 'Avertissement',
            'success' => 'Succès',
            'info' => 'Information',
            
            // Form Labels
            'prevention_costs' => 'Coûts de Prévention',
            'appraisal_costs' => 'Coûts d\'Évaluation',
            'internal_defect_costs' => 'Coûts de Défauts Internes',
            'external_defect_costs' => 'Coûts de Défauts Externes',
            'currency' => 'Devise',
            'unit' => 'Unité',
            'language' => 'Langue',
            
            // Form Placeholders
            'enter_prevention_costs' => 'Saisir les coûts de prévention',
            'enter_appraisal_costs' => 'Saisir les coûts d\'évaluation',
            'enter_internal_defects' => 'Saisir les coûts de défauts internes',
            'enter_external_defects' => 'Saisir les coûts de défauts externes',
            'select_currency' => 'Sélectionner la devise',
            'select_unit' => 'Sélectionner l\'unité',
            'select_language' => 'Sélectionner la langue',
            
            // Form Help Text
            'prevention_costs_help' => 'Coûts investis dans la prévention des défauts (formation, planification, revue de conception)',
            'appraisal_costs_help' => 'Coûts d\'évaluation de la qualité (inspection, tests, audit)',
            'internal_defects_help' => 'Coûts des défauts trouvés avant livraison (retravail, rebut, temps d\'arrêt)',
            'external_defects_help' => 'Coûts des défauts trouvés après livraison (garantie, retours, plaintes)',
            
            // Results Labels
            'calculation_results' => 'Résultats du Calcul',
            'cost_of_good_quality' => 'Coût de la Bonne Qualité (COGQ)',
            'cost_of_poor_quality' => 'Coût de la Mauvaise Qualité (COPQ)',
            'total_quality_costs' => 'Coûts Totaux de Qualité',
            'cogq_percentage' => 'Pourcentage COGQ',
            'copq_percentage' => 'Pourcentage COPQ',
            'prevention_percentage' => 'Pourcentage de Prévention',
            'appraisal_percentage' => 'Pourcentage d\'Évaluation',
            'internal_defects_percentage' => 'Pourcentage de Défauts Internes',
            'external_defects_percentage' => 'Pourcentage de Défauts Externes',
            
            // Chart Labels
            'quality_cost_breakdown' => 'Répartition des Coûts de Qualité',
            'cogq_vs_copq' => 'COGQ vs COPQ',
            'cost_categories' => 'Catégories de Coûts',
            'prevention' => 'Prévention',
            'appraisal' => 'Évaluation',
            'internal_defects' => 'Défauts Internes',
            'external_defects' => 'Défauts Externes',
            
            // Validation Messages
            'field_required' => 'Ce champ est obligatoire',
            'invalid_number' => 'Veuillez saisir un nombre valide',
            'number_too_small' => 'Le nombre doit être supérieur ou égal à {min}',
            'number_too_large' => 'Le nombre doit être inférieur ou égal à {max}',
            'invalid_currency' => 'Veuillez sélectionner une devise valide',
            'invalid_unit' => 'Veuillez sélectionner une unité valide',
            'calculation_error' => 'Une erreur s\'est produite lors du calcul',
            'validation_failed' => 'La validation a échoué',
            'all_costs_zero' => 'Au moins un coût doit être supérieur à zéro',
            
            // Error Messages
            'general_error' => 'Une erreur inattendue s\'est produite',
            'network_error' => 'Erreur réseau. Veuillez vérifier votre connexion.',
            'server_error' => 'Erreur serveur. Veuillez réessayer plus tard.',
            'timeout_error' => 'Délai d\'attente dépassé. Veuillez réessayer.',
            'permission_denied' => 'Permission refusée',
            'not_found' => 'Ressource non trouvée',
            'invalid_request' => 'Requête invalide',
            'maintenance_mode' => 'Le système est actuellement en maintenance',
            
            // Success Messages
            'calculation_complete' => 'Calcul terminé avec succès',
            'data_saved' => 'Données enregistrées avec succès',
            'settings_updated' => 'Paramètres mis à jour avec succès',
            'export_complete' => 'Export terminé avec succès',
            'import_complete' => 'Import terminé avec succès',
            
            // Export/Import
            'export' => 'Exporter',
            'import' => 'Importer',
            'export_data' => 'Exporter les données',
            'import_data' => 'Importer les données',
            'export_format' => 'Format d\'export',
            'select_file' => 'Sélectionner un fichier',
            'download' => 'Télécharger',
            'upload' => 'Téléverser',
            'file_size_limit' => 'Taille maximale du fichier : {size}',
            'supported_formats' => 'Formats supportés : {formats}',
            
            // Units
            'units' => 'Unités',
            'unit_1' => 'Unités',
            'unit_1000' => 'Milliers',
            'unit_1000000' => 'Millions',
            
            // Currencies
            'usd' => 'Dollar américain',
            'eur' => 'Euro',
            'gbp' => 'Livre sterling',
            'jpy' => 'Yen japonais',
            'cad' => 'Dollar canadien',
            'aud' => 'Dollar australien',
            'chf' => 'Franc suisse',
            'cny' => 'Yuan chinois',
            'sek' => 'Couronne suédoise',
            'nok' => 'Couronne norvégienne',
            'dkk' => 'Couronne danoise',
            
            // Languages
            'english' => 'Anglais',
            'german' => 'Allemand',
            'french' => 'Français',
            'spanish' => 'Espagnol',
            'chinese' => 'Chinois',
            
            // Admin Interface
            'settings' => 'Paramètres',
            'general_settings' => 'Paramètres généraux',
            'calculation_settings' => 'Paramètres de calcul',
            'display_settings' => 'Paramètres d\'affichage',
            'advanced_settings' => 'Paramètres avancés',
            'plugin_settings' => 'Paramètres du plugin',
            'save_settings' => 'Enregistrer les paramètres',
            'reset_settings' => 'Réinitialiser les paramètres',
            'backup_settings' => 'Sauvegarder les paramètres',
            'restore_settings' => 'Restaurer les paramètres',
            
            // Dashboard
            'dashboard' => 'Tableau de bord',
            'overview' => 'Vue d\'ensemble',
            'statistics' => 'Statistiques',
            'recent_calculations' => 'Calculs récents',
            'usage_statistics' => 'Statistiques d\'utilisation',
            'system_status' => 'État du système',
            'performance_metrics' => 'Métriques de performance',
            
            // Help & Documentation
            'help' => 'Aide',
            'documentation' => 'Documentation',
            'user_guide' => 'Guide utilisateur',
            'faq' => 'Questions fréquemment posées',
            'support' => 'Support',
            'contact' => 'Contact',
            'about' => 'À propos',
            'version' => 'Version',
            
            // Quality Terms & Definitions
            'cogq_definition' => 'Coût de la Bonne Qualité : Investissement dans les activités de prévention et d\'évaluation',
            'copq_definition' => 'Coût de la Mauvaise Qualité : Coût des défaillances internes et externes',
            'prevention_definition' => 'Activités pour prévenir l\'occurrence de défauts',
            'appraisal_definition' => 'Activités pour évaluer et inspecter la qualité',
            'internal_failure_definition' => 'Coûts des défauts trouvés avant livraison au client',
            'external_failure_definition' => 'Coûts des défauts trouvés après livraison au client',
            
            // Calculation Types
            'basic_calculation' => 'Calcul de base',
            'advanced_calculation' => 'Calcul avancé',
            'detailed_analysis' => 'Analyse détaillée',
            'trend_analysis' => 'Analyse de tendance',
            'comparison_analysis' => 'Analyse comparative',
            'roi_analysis' => 'Analyse ROI',
            
            // Time Periods
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            'quarterly' => 'Trimestriel',
            'yearly' => 'Annuel',
            'custom_period' => 'Période personnalisée',
            
            // Status Messages
            'status_active' => 'Actif',
            'status_inactive' => 'Inactif',
            'status_pending' => 'En attente',
            'status_completed' => 'Terminé',
            'status_failed' => 'Échoué',
            'status_processing' => 'En cours de traitement',
            'status_cancelled' => 'Annulé',
            
            // Actions
            'view' => 'Voir',
            'print' => 'Imprimer',
            'share' => 'Partager',
            'copy' => 'Copier',
            'paste' => 'Coller',
            'duplicate' => 'Dupliquer',
            'archive' => 'Archiver',
            'restore' => 'Restaurer',
            'refresh' => 'Actualiser',
            'reload' => 'Recharger',
            
            // Navigation
            'home' => 'Accueil',
            'calculator' => 'Calculateur',
            'reports' => 'Rapports',
            'tools' => 'Outils',
            'preferences' => 'Préférences',
            'account' => 'Compte',
            'logout' => 'Déconnexion',
            'login' => 'Connexion',
            
            // Formatting
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'datetime_format' => 'd/m/Y H:i',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            
            // Tooltips
            'tooltip_prevention' => 'Inclure les coûts de formation, conception de processus et planification qualité',
            'tooltip_appraisal' => 'Inclure les coûts de tests, inspection et audits qualité',
            'tooltip_internal' => 'Inclure les coûts de retravail, rebut et retards de production',
            'tooltip_external' => 'Inclure les coûts de garanties, retours et plaintes clients',
            'tooltip_currency' => 'Sélectionner la devise pour toutes les valeurs monétaires',
            'tooltip_unit' => 'Sélectionner l\'échelle d\'unité pour afficher les grands nombres',
            
            // Notifications
            'notification_saved' => 'Vos modifications ont été enregistrées',
            'notification_deleted' => 'L\'élément a été supprimé',
            'notification_exported' => 'Les données ont été exportées avec succès',
            'notification_imported' => 'Les données ont été importées avec succès',
            'notification_error' => 'Une erreur s\'est produite lors du traitement de votre demande',
            'notification_warning' => 'Veuillez vérifier vos données d\'entrée',
            'notification_info' => 'Des informations supplémentaires sont disponibles',
            
            // Placeholders for Dynamic Content
            'no_data_available' => 'Aucune donnée disponible',
            'no_results_found' => 'Aucun résultat trouvé',
            'no_calculations_yet' => 'Aucun calcul n\'a encore été effectué',
            'empty_state_message' => 'Commencez en saisissant vos données de coûts de qualité',
            'loading_calculations' => 'Chargement des calculs...',
            'processing_request' => 'Traitement de votre demande...',
            
            // Accessibility
            'accessibility_calculate_button' => 'Calculer les coûts de qualité',
            'accessibility_reset_button' => 'Réinitialiser tous les champs du formulaire',
            'accessibility_export_button' => 'Exporter les résultats de calcul',
            'accessibility_help_button' => 'Ouvrir la documentation d\'aide',
            'accessibility_close_button' => 'Fermer la boîte de dialogue',
            'accessibility_menu_button' => 'Ouvrir le menu de navigation',
            
            // API Messages
            'api_success' => 'Requête API terminée avec succès',
            'api_error' => 'Échec de la requête API',
            'api_timeout' => 'Délai d\'attente de la requête API dépassé',
            'api_unauthorized' => 'Accès API non autorisé',
            'api_rate_limit' => 'Limite de taux API dépassée',
            'api_maintenance' => 'API en maintenance',
            
            // Comparison & Analysis
            'compare' => 'Comparer',
            'comparison' => 'Comparaison',
            'benchmark' => 'Référence',
            'target' => 'Cible',
            'actual' => 'Réel',
            'variance' => 'Écart',
            'improvement' => 'Amélioration',
            'deterioration' => 'Détérioration',
            'trend_up' => 'Tendance à la hausse',
            'trend_down' => 'Tendance à la baisse',
            'trend_stable' => 'Stable',
            
            // Reports
            'generate_report' => 'Générer un rapport',
            'report_title' => 'Rapport de Coûts de Qualité',
            'report_period' => 'Période du rapport',
            'report_summary' => 'Résumé exécutif',
            'report_details' => 'Analyse détaillée',
            'report_recommendations' => 'Recommandations',
            'report_conclusion' => 'Conclusion',
            
            // Quality Metrics
            'quality_ratio' => 'Ratio de qualité',
            'efficiency_score' => 'Score d\'efficacité',
            'performance_index' => 'Indice de performance',
            'cost_effectiveness' => 'Rapport coût-efficacité',
            'return_on_investment' => 'Retour sur investissement',
            'cost_per_unit' => 'Coût par unité',
            'defect_rate' => 'Taux de défaut',
            'yield_rate' => 'Taux de rendement',
            
            // Plurals
            'calculation_singular' => 'calcul',
            'calculation_plural' => 'calculs',
            'result_singular' => 'résultat',
            'result_plural' => 'résultats',
            'error_singular' => 'erreur',
            'error_plural' => 'erreurs',
            'warning_singular' => 'avertissement',
            'warning_plural' => 'avertissements',
            'item_singular' => 'élément',
            'item_plural' => 'éléments',
            'record_singular' => 'enregistrement',
            'record_plural' => 'enregistrements',
            
            // Time Expressions
            'seconds_ago' => 'il y a {count} secondes',
            'minutes_ago' => 'il y a {count} minutes',
            'hours_ago' => 'il y a {count} heures',
            'days_ago' => 'il y a {count} jours',
            'weeks_ago' => 'il y a {count} semaines',
            'months_ago' => 'il y a {count} mois',
            'years_ago' => 'il y a {count} ans',
            'just_now' => 'À l\'instant',
            
            // System Messages
            'system_healthy' => 'Le système fonctionne normalement',
            'system_warning' => 'Avertissement système détecté',
            'system_error' => 'Erreur système détectée',
            'system_maintenance' => 'Maintenance système en cours',
            'system_offline' => 'Le système est actuellement hors ligne',
            'system_updating' => 'Le système est en cours de mise à jour',
            
            // Advanced Features
            'advanced_mode' => 'Mode avancé',
            'expert_mode' => 'Mode expert',
            'professional_mode' => 'Mode professionnel',
            'custom_formula' => 'Formule personnalisée',
            'calculation_method' => 'Méthode de calcul',
            'precision_level' => 'Niveau de précision',
            'rounding_method' => 'Méthode d\'arrondi',
            
            // Integration
            'integration' => 'Intégration',
            'api_access' => 'Accès API',
            'webhook' => 'Webhook',
            'automation' => 'Automatisation',
            'sync' => 'Synchronisation',
            'connection' => 'Connexion',
            'authentication' => 'Authentification',
            'authorization' => 'Autorisation',
            
            // Footer
            'powered_by' => 'Propulsé par Quality Cost Calculator',
            'copyright' => 'Copyright © {year} Tous droits réservés',
            'privacy_policy' => 'Politique de confidentialité',
            'terms_of_service' => 'Conditions de service',
            'contact_us' => 'Contactez-nous'
        );
    }
    
    /**
     * Get language metadata
     * 
     * @return array Language information
     */
    public function get_language_info() {
        return array(
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'flag' => '🇫🇷',
            'rtl' => false,
            'completion' => 100,
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'currency_position' => 'after',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i'
        );
    }
}
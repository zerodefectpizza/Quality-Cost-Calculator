<?php
/**
 * QCC Result Card Group Molecule
 * 
 * Molecule-Component für Gruppen von Result-Cards.
 * Zeigt mehrere Berechnungsergebnisse in strukturierter Grid-Anordnung.
 * 
 * @package QualityCostCalculator
 * @subpackage Molecules
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class QCC_Result_Card_Group
 * 
 * @since 3.0.0
 */
class QCC_Result_Card_Group extends QCC_Base_Molecule {
    
    /**
     * Child-Components Definition (dynamisch)
     * 
     * @since 3.0.0
     * @var array
     */
    protected $child_components = array();
    
    /**
     * Layout-Konfiguration
     * 
     * @since 3.0.0
     * @var array
     */
    protected $layout_config = array(
        'orientation' => 'grid',
        'spacing' => 'medium',
        'alignment' => 'stretch',
        'wrap_children' => true,
        'columns' => 'auto',
        'equal_height' => true
    );
    
    /**
     * Molecule-Typ
     * 
     * @since 3.0.0
     * @var string
     */
    protected $molecule_type = 'display';
    
    /**
     * Event-Propagation-Rules
     * 
     * @since 3.0.0
     * @var array
     */
    protected $event_propagation_rules = array(
        'card_group.update' => array('*.update'),
        'card_group.highlight' => array('*.highlight'),
        'card_group.reset' => array('*.reset')
    );
    
    /**
     * Vordefinierte Card-Group-Typen
     * 
     * @since 3.0.0
     * @var array
     */
    protected $card_group_types = array(
        'cogq' => array(
            'title' => 'Cost of Good Quality (COGQ)',
            'cards' => array('prevention', 'appraisal', 'cogq_total'),
            'color_scheme' => 'success',
            'columns' => 3
        ),
        'copq' => array(
            'title' => 'Cost of Poor Quality (COPQ)',
            'cards' => array('internal_defect', 'external_defect', 'copq_total'),
            'color_scheme' => 'warning',
            'columns' => 3
        ),
        'opportunity' => array(
            'title' => 'Opportunity Costs',
            'cards' => array('lost_sales', 'customer_churn', 'market_share_loss', 'opportunity_total'),
            'color_scheme' => 'danger',
            'columns' => 2
        ),
        'summary' => array(
            'title' => 'Quality Cost Summary',
            'cards' => array('cogq_total', 'copq_total', 'opportunity_total', 'grand_total'),
            'color_scheme' => 'primary',
            'columns' => 2
        )
    );
    
    /**
     * Result-Card-Definitionen
     * 
     * @since 3.0.0
     * @var array
     */
    protected $card_definitions = array(
        'prevention' => array(
            'title' => 'Prevention Costs',
            'description' => 'Costs to prevent quality issues',
            'icon' => 'shield-check',
            'color' => 'success',
            'format' => 'currency'
        ),
        'appraisal' => array(
            'title' => 'Appraisal Costs',
            'description' => 'Costs to detect quality issues',
            'icon' => 'search',
            'color' => 'info',
            'format' => 'currency'
        ),
        'internal_defect' => array(
            'title' => 'Internal Defect Costs',
            'description' => 'Costs of defects found internally',
            'icon' => 'wrench',
            'color' => 'warning',
            'format' => 'currency'
        ),
        'external_defect' => array(
            'title' => 'External Defect Costs',
            'description' => 'Costs of defects found by customers',
            'icon' => 'exclamation-triangle',
            'color' => 'danger',
            'format' => 'currency'
        ),
        'lost_sales' => array(
            'title' => 'Lost Sales',
            'description' => 'Revenue lost due to quality issues',
            'icon' => 'trending-down',
            'color' => 'danger',
            'format' => 'currency'
        ),
        'customer_churn' => array(
            'title' => 'Customer Churn',
            'description' => 'Customers lost due to quality issues',
            'icon' => 'user-x',
            'color' => 'danger',
            'format' => 'currency'
        ),
        'market_share_loss' => array(
            'title' => 'Market Share Loss',
            'description' => 'Market position lost due to quality',
            'icon' => 'pie-chart',
            'color' => 'danger',
            'format' => 'currency'
        ),
        'cogq_total' => array(
            'title' => 'COGQ Total',
            'description' => 'Total Cost of Good Quality',
            'icon' => 'check-circle',
            'color' => 'success',
            'format' => 'currency',
            'is_total' => true
        ),
        'copq_total' => array(
            'title' => 'COPQ Total',
            'description' => 'Total Cost of Poor Quality',
            'icon' => 'x-circle',
            'color' => 'warning',
            'format' => 'currency',
            'is_total' => true
        ),
        'opportunity_total' => array(
            'title' => 'Opportunity Total',
            'description' => 'Total Opportunity Costs',
            'icon' => 'trending-down',
            'color' => 'danger',
            'format' => 'currency',
            'is_total' => true
        ),
        'grand_total' => array(
            'title' => 'Grand Total',
            'description' => 'Total Quality Costs',
            'icon' => 'calculator',
            'color' => 'primary',
            'format' => 'currency',
            'is_total' => true,
            'is_grand_total' => true
        )
    );
    
    /**
     * Constructor
     * 
     * @since 3.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->init_result_card_group_specific();
    }
    
    /**
     * Result-Card-Group-spezifische Initialisierung
     * 
     * @since 3.0.0
     * @return void
     */
    private function init_result_card_group_specific() {
        // Template-Pfad setzen
        $this->layout_template = 'molecules/result-card-group.php';
        
        // Lokalisierte Card-Definitionen
        $this->localize_card_definitions();
    }
    
    /**
     * Card-Definitionen lokalisieren
     * 
     * @since 3.0.0
     * @return void
     */
    private function localize_card_definitions() {
        foreach ($this->card_definitions as $key => &$definition) {
            $definition['title'] = __($definition['title'], 'quality-cost-calculator');
            $definition['description'] = __($definition['description'], 'quality-cost-calculator');
        }
        
        foreach ($this->card_group_types as $key => &$group) {
            $group['title'] = __($group['title'], 'quality-cost-calculator');
        }
    }
    
    /**
     * Child-Components dynamisch generieren basierend auf Cards
     * 
     * @since 3.0.0
     * @return array Child component definitions
     */
    public function get_child_components() {
        // Bereits generiert?
        if (!empty($this->child_components)) {
            return $this->child_components;
        }
        
        // Fallback für leere Initialisierung
        return array();
    }
    
    /**
     * Child-Components für spezifische Cards setup
     * 
     * @since 3.0.0
     * @param array $cards Array von Card-Namen
     * @return void
     */
    public function setup_cards($cards) {
        $this->child_components = array();
        
        foreach ($cards as $card_name) {
            $this->child_components[$card_name] = array(
                'type' => 'atom',
                'class' => 'QCC_Result_Card',
                'required' => true
            );
        }
    }
    
    /**
     * Card-Group-Typ setup
     * 
     * @since 3.0.0
     * @param string $group_type Group type name
     * @param array $data Additional data
     * @return void
     */
    public function setup_group_type($group_type, $data = array()) {
        if (!isset($this->card_group_types[$group_type])) {
            return;
        }
        
        $group_config = $this->card_group_types[$group_type];
        
        // Cards setup
        $this->setup_cards($group_config['cards']);
        
        // Layout-Config anpassen
        $this->layout_config['columns'] = $group_config['columns'];
        
        // Color-Scheme setzen
        if (isset($group_config['color_scheme'])) {
            $this->layout_config['color_scheme'] = $group_config['color_scheme'];
        }
    }
    
    /**
     * Data-Mapping zu einzelnem Child
     * 
     * @since 3.0.0
     * @param array $data Original data
     * @param string $child_name Child name
     * @param array $child_definition Child definition
     * @return array Child-specific data
     */
    protected function map_data_to_child($data, $child_name, $child_definition) {
        $base_id = $data['id'] ?? $this->generate_unique_id($data);
        $card_def = $this->card_definitions[$child_name] ?? array();
        $values = $data['values'] ?? array();
        $currency = $data['currency'] ?? 'EUR';
        $unit = $data['unit'] ?? 1000000;
        
        return array(
            'id' => $base_id . '-' . $child_name,
            'name' => $child_name,
            'title' => $card_def['title'] ?? ucfirst(str_replace('_', ' ', $child_name)),
            'description' => $card_def['description'] ?? '',
            'value' => $values[$child_name] ?? 0,
            'currency' => $currency,
            'unit' => $unit,
            'format' => $card_def['format'] ?? 'currency',
            'icon' => $card_def['icon'] ?? 'circle',
            'color' => $card_def['color'] ?? 'primary',
            'is_total' => $card_def['is_total'] ?? false,
            'is_grand_total' => $card_def['is_grand_total'] ?? false,
            'show_change' => $data['show_change'] ?? false,
            'previous_value' => ($data['previous_values'] ?? array())[$child_name] ?? null,
            'class' => 'qcc-result-card-group-card'
        );
    }
    
    /**
     * Card-Group-Werte validieren
     * 
     * @since 3.0.0
     * @param array $data Card-Group data
     * @return true|WP_Error
     */
    public function validate_card_group($data) {
        // Basis-Validierung
        $base_validation = $this->validate_data($data);
        if (is_wp_error($base_validation)) {
            return $base_validation;
        }
        
        // Values müssen Array sein
        if (isset($data['values']) && !is_array($data['values'])) {
            return new WP_Error(
                'card_group_values_not_array',
                __('Card group values must be an array', 'quality-cost-calculator')
            );
        }
        
        // Alle Werte müssen numerisch sein
        $values = $data['values'] ?? array();
        foreach ($values as $card_name => $value) {
            if (!is_numeric($value)) {
                return new WP_Error(
                    'card_group_value_not_numeric',
                    sprintf(__('Value for card "%s" must be numeric', 'quality-cost-calculator'), $card_name)
                );
            }
        }
        
        return true;
    }
    
    /**
     * Totals automatisch berechnen
     * 
     * @since 3.0.0
     * @param array $values Raw values
     * @param string $group_type Group type
     * @return array Values mit berechneten Totals
     */
    public function calculate_totals($values, $group_type = null) {
        $calculated = $values;
        
        // COGQ Total
        if (isset($values['prevention']) && isset($values['appraisal'])) {
            $calculated['cogq_total'] = $values['prevention'] + $values['appraisal'];
        }
        
        // COPQ Total
        if (isset($values['internal_defect']) && isset($values['external_defect'])) {
            $calculated['copq_total'] = $values['internal_defect'] + $values['external_defect'];
        }
        
        // Opportunity Total
        $opportunity_components = array('lost_sales', 'customer_churn', 'market_share_loss');
        $opportunity_total = 0;
        $has_opportunity_components = false;
        
        foreach ($opportunity_components as $component) {
            if (isset($values[$component])) {
                $opportunity_total += $values[$component];
                $has_opportunity_components = true;
            }
        }
        
        if ($has_opportunity_components) {
            $calculated['opportunity_total'] = $opportunity_total;
        }
        
        // Grand Total
        $grand_total_components = array('cogq_total', 'copq_total', 'opportunity_total');
        $grand_total = 0;
        $has_grand_components = false;
        
        foreach ($grand_total_components as $component) {
            if (isset($calculated[$component])) {
                $grand_total += $calculated[$component];
                $has_grand_components = true;
            }
        }
        
        if ($has_grand_components) {
            $calculated['grand_total'] = $grand_total;
        }
        
        return $calculated;
    }
    
    /**
     * Value-Changes berechnen
     * 
     * @since 3.0.0
     * @param array $current_values Current values
     * @param array $previous_values Previous values
     * @return array Change data
     */
    public function calculate_changes($current_values, $previous_values) {
        $changes = array();
        
        foreach ($current_values as $card_name => $current_value) {
            $previous_value = $previous_values[$card_name] ?? 0;
            
            $changes[$card_name] = array(
                'absolute' => $current_value - $previous_value,
                'percentage' => $previous_value != 0 ? (($current_value - $previous_value) / $previous_value) * 100 : 0,
                'direction' => $current_value > $previous_value ? 'up' : ($current_value < $previous_value ? 'down' : 'same')
            );
        }
        
        return $changes;
    }
    
    /**
     * Result-Card-Group-spezifische CSS-Klassen
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array CSS classes
     */
    public function get_molecule_css_classes($data) {
        $classes = parent::get_molecule_css_classes($data);
        
        // Grid-Layout-Klassen
        $columns = $this->layout_config['columns'];
        if (is_numeric($columns)) {
            $classes[] = 'qcc-result-card-group--cols-' . $columns;
        } else {
            $classes[] = 'qcc-result-card-group--cols-auto';
        }
        
        // Color-Scheme
        if (isset($this->layout_config['color_scheme'])) {
            $classes[] = 'qcc-result-card-group--' . $this->layout_config['color_scheme'];
        }
        
        // Features
        if ($data['show_change'] ?? false) {
            $classes[] = 'qcc-result-card-group--with-changes';
        }
        
        if ($this->layout_config['equal_height']) {
            $classes[] = 'qcc-result-card-group--equal-height';
        }
        
        // Group-Type
        if (!empty($data['group_type'])) {
            $classes[] = 'qcc-result-card-group--' . $data['group_type'];
        }
        
        return $classes;
    }
    
    /**
     * Container-Attribute für Result-Card-Group
     * 
     * @since 3.0.0
     * @param array $data Molecule data
     * @return array Container attributes
     */
    public function get_container_attributes($data) {
        $base_attributes = parent::get_container_attributes($data);
        
        $card_group_attributes = array(
            'data-card-count' => count($this->child_components),
            'data-columns' => $this->layout_config['columns'],
            'data-group-type' => $data['group_type'] ?? 'custom'
        );
        
        if (!empty($data['values'])) {
            $card_group_attributes['data-has-values'] = 'true';
        }
        
        return array_merge($base_attributes, $card_group_attributes);
    }
    
    /**
     * Template-Daten für Result-Card-Group vorbereiten
     * 
     * @since 3.0.0
     * @param array $data Raw data
     * @param array $attributes HTML attributes
     * @return array Template data
     */
    public function prepare_template_data($data, $attributes) {
        $base_data = parent::prepare_template_data($data, $attributes);
        
        $group_type = $data['group_type'] ?? 'custom';
        $values = $data['values'] ?? array();
        $previous_values = $data['previous_values'] ?? array();
        
        // Totals berechnen
        $calculated_values = $this->calculate_totals($values, $group_type);
        
        // Changes berechnen falls gewünscht
        $changes = array();
        if ($data['show_change'] ?? false && !empty($previous_values)) {
            $changes = $this->calculate_changes($calculated_values, $previous_values);
        }
        
        $card_group_data = array(
            'group_type' => $group_type,
            'group_config' => $this->card_group_types[$group_type] ?? array(),
            'card_definitions' => $this->card_definitions,
            'values' => $calculated_values,
            'previous_values' => $previous_values,
            'changes' => $changes,
            'currency' => $data['currency'] ?? 'EUR',
            'unit' => $data['unit'] ?? 1000000,
            'show_change' => $data['show_change'] ?? false,
            'show_icons' => $data['show_icons'] ?? true,
            'show_descriptions' => $data['show_descriptions'] ?? true,
            'grid_columns' => $this->layout_config['columns'],
            'equal_height' => $this->layout_config['equal_height'],
            'card_count' => count($this->child_components),
            'has_totals' => $this->has_total_cards($calculated_values),
            'is_result_card_group' => true
        );
        
        return array_merge($base_data, $card_group_data);
    }
    
    /**
     * Prüfen ob Total-Cards vorhanden sind
     * 
     * @since 3.0.0
     * @param array $values Values array
     * @return bool True wenn Total-Cards vorhanden
     */
    private function has_total_cards($values) {
        $total_cards = array('cogq_total', 'copq_total', 'opportunity_total', 'grand_total');
        
        foreach ($total_cards as $total_card) {
            if (isset($values[$total_card])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Required fields für Result-Card-Group
     * 
     * @since 3.0.0
     * @return array Required fields
     */
    protected function get_required_fields() {
        return array('id');
    }
    
    /**
     * Assets für Result-Card-Group
     * 
     * @since 3.0.0
     * @return array Asset requirements
     */
    public function get_required_assets() {
        $base_assets = parent::get_required_assets();
        
        $card_group_assets = array(
            'css' => array('result-card-group.css', 'result-card-grid.css'),
            'js' => array('result-card-group.js', 'result-card-animations.js'),
            'dependencies' => array('qcc-result-card', 'qcc-grid-layout')
        );
        
        return array_merge_recursive($base_assets, $card_group_assets);
    }
    
    /**
     * Factory-Methode für vordefinierte Card-Groups
     * 
     * @since 3.0.0
     * @param string $group_type Group type name
     * @param array $data Additional data
     * @return QCC_Result_Card_Group Configured card group
     */
    public static function create_for_type($group_type, $data = array()) {
        $instance = new self();
        $instance->setup_group_type($group_type, $data);
        
        $data['group_type'] = $group_type;
        
        return $instance;
    }
    
    /**
     * Factory-Methode für Custom-Card-Groups
     * 
     * @since 3.0.0
     * @param array $cards Array von Card-Namen
     * @param array $data Additional data
     * @return QCC_Result_Card_Group Configured card group
     */
    public static function create_custom($cards, $data = array()) {
        $instance = new self();
        $instance->setup_cards($cards);
        
        $data['group_type'] = 'custom';
        
        return $instance;
    }
}
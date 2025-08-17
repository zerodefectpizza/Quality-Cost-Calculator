<?php
/**
 * QCC Molecule Interface
 * 
 * Interface für Molecule Components - Kombinationen von Atoms die zusammen
 * eine funktionale UI-Einheit bilden. Molecules sind wiederverwendbare
 * Component-Gruppen im Quality Cost Calculator Plugin.
 * 
 * @package QualityCostCalculator
 * @subpackage Interfaces
 * @since 3.0.0
 * @author QCC Development Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface QCC_Molecule
 * 
 * Erweitert QCC_Renderable um molecule-spezifische Funktionalitäten.
 * Molecules bestehen aus mehreren Atoms und haben eine definierte
 * interne Struktur und Layout.
 * 
 * Typische Molecules:
 * - Input-Group (Label + Input + Validation + Help)
 * - Result-Card-Group (mehrere Result-Cards zusammen)
 * - Control-Panel (mehrere Buttons + Selects)
 * - Chart-Section (Chart + Legend + Controls)
 * - Form-Section (Title + mehrere Input-Groups)
 * 
 * @since 3.0.0
 * @extends QCC_Renderable
 */
interface QCC_Molecule extends QCC_Renderable {
    
    /**
     * Gibt Child-Components (Atoms) zurück
     * 
     * Definiert welche Atoms dieses Molecule enthält.
     * Wird für Dependency-Management und Auto-Loading verwendet.
     * 
     * @since 3.0.0
     * 
     * @return array Array von Child-Component-Definitionen
     * 
     * @example
     * return array(
     *     'label' => array('type' => 'atom', 'class' => 'QCC_Label'),
     *     'input' => array('type' => 'atom', 'class' => 'QCC_Percentage_Input'),
     *     'validation' => array('type' => 'atom', 'class' => 'QCC_Validation_Message'),
     *     'help' => array('type' => 'atom', 'class' => 'QCC_Help_Text')
     * );
     */
    public function get_child_components();
    
    /**
     * Mappt Input-Daten auf Child-Components
     * 
     * Verteilt die Molecule-Input-Daten auf die einzelnen
     * Child-Atoms entsprechend deren Anforderungen.
     * 
     * @since 3.0.0
     * 
     * @param array $data Input-Daten für das gesamte Molecule
     * @return array Mapped data für jedes Child-Component
     * 
     * @example
     * // Input: array('id' => 'prevention', 'value' => 25, 'label' => 'Prevention Cost')
     * // Output:
     * return array(
     *     'label' => array('text' => 'Prevention Cost', 'for' => 'prevention'),
     *     'input' => array('id' => 'prevention', 'value' => 25),
     *     'validation' => array('field' => 'prevention', 'rules' => array(...)),
     *     'help' => array('text' => 'Enter percentage between 0-100')
     * );
     */
    public function map_data_to_children($data);
    
    /**
     * Assembliert Child-Component HTML zu Molecule
     * 
     * Nimmt die gerenderten HTML-Strings der Child-Components
     * und kombiniert sie zum finalen Molecule-HTML.
     * 
     * @since 3.0.0
     * 
     * @param array $rendered_children Gerenderte HTML-Strings der Children
     * @param array $data Original molecule data für Context
     * @return string Assembled molecule HTML
     * 
     * @example
     * $children = array(
     *     'label' => '<label for="prevention">Prevention Cost</label>',
     *     'input' => '<input type="number" id="prevention" value="25">',
     *     'validation' => '<div class="qcc-validation"></div>',
     *     'help' => '<small class="qcc-help">Enter percentage</small>'
     * );
     * return $this->assemble_components($children, $data);
     */
    public function assemble_components($rendered_children, $data);
    
    /**
     * Gibt Layout-Template für Molecule zurück
     * 
     * Template-Datei die definiert wie die Child-Components
     * angeordnet und strukturiert werden.
     * 
     * @since 3.0.0
     * 
     * @return string Layout template filename
     * 
     * @example
     * return 'molecules/input-group-layout.php';
     */
    public function get_layout_template();
    
    /**
     * Gibt Layout-Konfiguration zurück
     * 
     * Definiert wie Child-Components im Layout angeordnet werden.
     * Ermöglicht verschiedene Layout-Varianten des gleichen Molecules.
     * 
     * @since 3.0.0
     * 
     * @param array $data Molecule data für layout context
     * @return array Layout configuration
     * 
     * @example
     * return array(
     *     'orientation' => 'vertical',    // oder 'horizontal'
     *     'spacing' => 'medium',          // 'small', 'medium', 'large'
     *     'alignment' => 'left',          // 'left', 'center', 'right'
     *     'wrap_children' => true,        // jedes Child in wrapper
     *     'grid_columns' => 2             // für grid-based layouts
     * );
     */
    public function get_layout_config($data);
    
    /**
     * Validiert Molecule-Struktur
     * 
     * Prüft ob alle erforderlichen Child-Components vorhanden
     * und korrekt konfiguriert sind.
     * 
     * @since 3.0.0
     * 
     * @return true|WP_Error True wenn Struktur valid, WP_Error bei Problemen
     * 
     * @example
     * $validation = $molecule->validate_structure();
     * if (is_wp_error($validation)) {
     *     error_log('Molecule structure invalid: ' . $validation->get_error_message());
     * }
     */
    public function validate_structure();
    
    /**
     * Gibt Child-Component-Instanz zurück
     * 
     * Lazy-Loading Zugriff auf Child-Components.
     * Erstellt Instanzen nur wenn benötigt.
     * 
     * @since 3.0.0
     * 
     * @param string $child_name Name des Child-Components
     * @return QCC_Atom|null Child component instance or null
     * 
     * @example
     * $input_atom = $molecule->get_child('input');
     * if ($input_atom) {
     *     echo $input_atom->render($input_data);
     * }
     */
    public function get_child($child_name);
    
    /**
     * Prüft ob Child-Component existiert
     * 
     * Validiert ob ein bestimmtes Child-Component
     * in diesem Molecule definiert ist.
     * 
     * @since 3.0.0
     * 
     * @param string $child_name Name des Child-Components
     * @return bool True wenn Child existiert
     * 
     * @example
     * if ($molecule->has_child('validation')) {
     *     // Validation component verfügbar
     * }
     */
    public function has_child($child_name);
    
    /**
     * Gibt Child-Component-Dependencies zurück
     * 
     * Sammelt alle Asset-Dependencies der Child-Components
     * für optimales Asset-Loading.
     * 
     * @since 3.0.0
     * 
     * @return array Consolidated asset dependencies
     * 
     * @example
     * return array(
     *     'css' => array('input-group.css', 'validation.css'),
     *     'js' => array('input-validation.js', 'help-tooltips.js'),
     *     'dependencies' => array('jquery', 'qcc-core')
     * );
     */
    public function get_child_dependencies();
    
    /**
     * Gibt Molecule-spezifische CSS-Klassen zurück
     * 
     * CSS-Klassen die nur auf Molecule-Level gelten,
     * zusätzlich zu den Child-Component-Klassen.
     * 
     * @since 3.0.0
     * 
     * @param array $data Molecule data für CSS-Context
     * @return array Molecule-specific CSS classes
     * 
     * @example
     * return array(
     *     'qcc-input-group',
     *     'qcc-input-group--vertical',
     *     'qcc-input-group--with-validation'
     * );
     */
    public function get_molecule_css_classes($data);
    
    /**
     * Propagiert Events zwischen Child-Components
     * 
     * Definiert wie Events von einem Child-Component
     * an andere Children weitergegeben werden.
     * 
     * @since 3.0.0
     * 
     * @return array Event propagation rules
     * 
     * @example
     * return array(
     *     'input.change' => array('validation.validate', 'help.update'),
     *     'input.focus' => array('help.show'),
     *     'input.blur' => array('help.hide', 'validation.display')
     * );
     */
    public function get_event_propagation_rules();
    
    /**
     * Gibt Container-Attribute zurück
     * 
     * HTML-Attribute für das Molecule-Container-Element
     * das alle Child-Components umschließt.
     * 
     * @since 3.0.0
     * 
     * @param array $data Molecule data für Attribute-Generierung
     * @return array Container HTML attributes
     * 
     * @example
     * return array(
     *     'class' => 'qcc-input-group qcc-input-group--vertical',
     *     'data-molecule' => 'input-group',
     *     'data-field' => $data['field_name'],
     *     'role' => 'group',
     *     'aria-labelledby' => $data['label_id']
     * );
     */
    public function get_container_attributes($data);
    
    /**
     * Behandelt Child-Component-Fehler
     * 
     * Strategie für den Fall dass einzelne Child-Components
     * nicht gerendert werden können.
     * 
     * @since 3.0.0
     * 
     * @param string $child_name Name des fehlerhaften Child-Components
     * @param WP_Error $error Error details
     * @return string Fallback HTML für das fehlerhafte Child
     * 
     * @example
     * if ($child_name === 'input') {
     *     return '<div class="qcc-error">Input component failed</div>';
     * }
     * return ''; // Skip non-critical children
     */
    public function handle_child_error($child_name, $error);
    
    /**
     * Gibt Molecule-Typ zurück
     * 
     * Klassifikation des Molecules für Registry und
     * verschiedene Behandlungsstrategien.
     * 
     * @since 3.0.0
     * 
     * @return string Molecule type ('form', 'display', 'control', 'layout')
     * 
     * @example
     * return 'form'; // für Input-Groups
     * return 'display'; // für Result-Card-Groups
     * return 'control'; // für Button-Panels
     */
    public function get_molecule_type();
    
    /**
     * Prüft ob Molecule kollabierbar ist
     * 
     * Bestimmt ob das Molecule ein Collapse/Expand-Feature
     * haben soll (z.B. für komplexe Form-Sections).
     * 
     * @since 3.0.0
     * 
     * @return bool True wenn collapsible
     * 
     * @example
     * return true; // für große Form-Sections
     * return false; // für einfache Input-Groups
     */
    public function is_collapsible();
    
    /**
     * Gibt Default-Collapse-State zurück
     * 
     * Definiert ob kollabierbare Molecules standardmäßig
     * expanded oder collapsed sind.
     * 
     * @since 3.0.0
     * 
     * @param array $data Molecule data für Context
     * @return bool True wenn initially expanded
     * 
     * @example
     * // Expand wenn Validation-Fehler vorhanden
     * return !empty($data['errors']);
     */
    public function get_default_expanded_state($data);
}
<?php
/**
 * Admin page template for Quality Cost Calculator
 *
 * @package QualityCostCalculator
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$system_info = qcc()->admin->get_system_info();
$requirements = qcc_check_system_requirements();
?>

<div class="wrap">
    <div class="card">
        <h2>Verwendung / Usage</h2>
        <p>Um den Quality Cost Calculator auf einer Seite oder in einem Beitrag anzuzeigen, verwenden Sie den folgenden Shortcode:</p>
        <p>To display the Quality Cost Calculator on any page or post, use the following shortcode:</p>
        <code>[quality_cost_calculator]</code>
        
        <h3>Shortcode Parameters / Shortcode-Parameter</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Parameter</th>
                    <th>Default</th>
                    <th>Options</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>language</code></td>
                    <td><?php echo esc_html($current_settings['language']); ?></td>
                    <td>en, de, fr, zh</td>
                    <td>Default language / Standardsprache</td>
                </tr>
                <tr>
                    <td><code>theme</code></td>
                    <td>default</td>
                    <td>default</td>
                    <td>Color theme / Farbthema</td>
                </tr>
            </tbody>
        </table>
        
        <h4>Examples / Beispiele:</h4>
        <ul>
            <li><code>[quality_cost_calculator]</code> - Uses default settings / Verwendet Standardeinstellungen</li>
            <li><code>[quality_cost_calculator language="de"]</code> - German interface / Deutsche Benutzeroberfläche</li>
            <li><code>[quality_cost_calculator language="fr"]</code> - French interface / Französische Benutzeroberfläche</li>
            <li><code>[quality_cost_calculator language="zh"]</code> - Chinese interface / Chinesische Benutzeroberfläche</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>System Requirements / Systemanforderungen</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Component / Komponente</th>
                    <th>Required / Erforderlich</th>
                    <th>Current / Aktuell</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requirements as $component => $data): ?>
                <tr>
                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $component))); ?></td>
                    <td><?php echo esc_html($data['required']); ?></td>
                    <td><?php echo esc_html($data['current']); ?></td>
                    <td>
                        <?php if ($data['status']): ?>
                            <span class="qcc-status-good">✓</span>
                        <?php else: ?>
                            <span class="qcc-status-bad">✗</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2>Supported Languages / Unterstützte Sprachen</h2>
        <?php $i18n = qcc()->i18n; ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Language / Sprache</th>
                    <th>Code</th>
                    <th>Native Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($i18n->get_language_status() as $lang): ?>
                <tr>
                    <td><?php echo esc_html($lang['name']); ?></td>
                    <td><?php echo esc_html($lang['code']); ?></td>
                    <td><?php echo esc_html($lang['native_name']); ?></td>
                    <td><span class="qcc-status-good"><?php echo esc_html($lang['status_text']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2>Features / Funktionen</h2>
        <div class="qcc-feature-grid">
            <div class="qcc-feature-card">
                <h4>🌍 Multi-language Support</h4>
                <p>Complete translations for German, English, French, and Chinese</p>
                <p>Vollständige Übersetzungen für Deutsch, Englisch, Französisch und Chinesisch</p>
            </div>
            
            <div class="qcc-feature-card">
                <h4>💱 Multiple Currencies</h4>
                <p>Support for Euro, US Dollar, and Chinese Renminbi</p>
                <p>Unterstützung für Euro, US-Dollar und Chinesischen Renminbi</p>
            </div>
            
            <div class="qcc-feature-card">
                <h4>📊 COGQ/COPQ Analysis</h4>
                <p>Cost of Good Quality vs Cost of Poor Quality breakdown</p>
                <p>Aufschlüsselung Kosten guter vs. schlechter Qualität</p>
            </div>
            
            <div class="qcc-feature-card">
                <h4>⚡ Real-time Calculations</h4>
                <p>Automatic recalculation with input validation</p>
                <p>Automatische Neuberechnung mit Eingabevalidierung</p>
            </div>
            
            <div class="qcc-feature-card">
                <h4>📈 Interactive Charts</h4>
                <p>Bar and pie charts with Chart.js integration</p>
                <p>Balken- und Kreisdiagramme mit Chart.js-Integration</p>
            </div>
            
            <div class="qcc-feature-card">
                <h4>📄 Export Functions</h4>
                <p>CSV and PDF export with multilingual headers</p>
                <p>CSV- und PDF-Export mit mehrsprachigen Überschriften</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h2>Debug Information / Debug-Informationen</h2>
        <table class="wp-list-table widefat fixed striped">
            <?php foreach ($system_info as $key => $value): ?>
            <tr>
                <th><?php echo esc_html(ucfirst(str_replace('_', ' ', $key))); ?></th>
                <td><?php echo esc_html($value); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h4>Quick Test / Schnelltest:</h4>
        <p>To test if the plugin is working correctly, create a new page with:</p>
        <p>Um zu testen, ob das Plugin korrekt funktioniert, erstellen Sie eine neue Seite mit:</p>
        <code>[quality_cost_calculator language="<?php echo esc_attr($current_settings['language']); ?>"]</code>
    </div>
    
    <div class="card">
        <h2>Troubleshooting / Fehlerbehebung</h2>
        
        <h4>Common Issues / Häufige Probleme:</h4>
        
        <h5>1. Plugin not loading / Plugin lädt nicht</h5>
        <ul>
            <li>Check WordPress and PHP version requirements / WordPress- und PHP-Versionsanforderungen prüfen</li>
            <li>Ensure plugin is properly activated / Sicherstellen, dass Plugin aktiviert ist</li>
            <li>Check for JavaScript errors in browser console / JavaScript-Fehler in Browser-Konsole prüfen</li>
        </ul>
        
        <h5>2. Charts not displaying / Diagramme werden nicht angezeigt</h5>
        <ul>
            <li>Verify Chart.js CDN is accessible / Chart.js CDN-Erreichbarkeit prüfen</li>
            <li>Check for theme conflicts / Theme-Konflikte überprüfen</li>
            <li>Test with different browsers / Mit verschiedenen Browsern testen</li>
        </ul>
        
        <h5>3. Language switching not working / Sprachumschaltung funktioniert nicht</h5>
        <ul>
            <li>Clear browser cache / Browser-Cache leeren</li>
            <li>Check JavaScript console for errors / JavaScript-Konsole auf Fehler prüfen</li>
            <li>Verify translations are loaded / Übersetzungen laden überprüfen</li>
        </ul>
        
        <h5>4. Export not working / Export funktioniert nicht</h5>
        <ul>
            <li>Check server permissions / Server-Berechtigungen prüfen</li>
            <li>Verify AJAX nonces / AJAX-Nonces überprüfen</li>
            <li>Test with different browsers / Mit verschiedenen Browsern testen</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Support & Documentation</h2>
        
        <h4>Resources / Ressourcen:</h4>
        <ul>
            <li><strong>Plugin Settings:</strong> WordPress Admin → Settings → Quality Cost Calculator</li>
            <li><strong>Plugin-Einstellungen:</strong> WordPress Admin → Einstellungen → Quality Cost Calculator</li>
            <li><strong>Version:</strong> <?php echo esc_html(QCC_PLUGIN_VERSION); ?></li>
            <li><strong>GitHub Repository:</strong> [Your Repository URL]</li>
        </ul>
        
        <h4>Version History / Versionshistorie:</h4>
        <ul>
            <li><strong>v1.1.0:</strong> Modular structure, German translations, enhanced COGQ/COPQ / Modulare Struktur, deutsche Übersetzungen, verbesserte COGQ/COPQ</li>
            <li><strong>v1.0.2:</strong> Improved opportunity cost calculations / Verbesserte Opportunitätskostenberechnungen</li>
            <li><strong>v1.0.1:</strong> Bug fixes and performance improvements / Fehlerbehebungen und Performance-Verbesserungen</li>
            <li><strong>v1.0.0:</strong> Initial release with multilingual support / Erste Version mit mehrsprachiger Unterstützung</li>
        </ul>
    </div>
</div>

<style>
.qcc-feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.qcc-feature-card {
    background: #f9f9f9;
    padding: 15px;
    border-left: 4px solid #449775;
    border-radius: 4px;
}

.qcc-feature-card h4 {
    margin: 0 0 10px 0;
    color: #449775;
    font-size: 1.1em;
}

.qcc-feature-card p {
    margin: 5px 0;
    font-size: 0.9em;
    line-height: 1.4;
}
</style> class="qcc-admin-header">
        <h1>Quality Cost Calculator Settings</h1>
        <p>Qualitätskostenrechner Einstellungen</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('qcc_settings', 'qcc_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">Default Language / Standardsprache</th>
                <td>
                    <select name="default_language">
                        <option value="en" <?php selected($current_settings['language'], 'en'); ?>>English</option>
                        <option value="de" <?php selected($current_settings['language'], 'de'); ?>>Deutsch</option>
                        <option value="fr" <?php selected($current_settings['language'], 'fr'); ?>>Français</option>
                        <option value="zh" <?php selected($current_settings['language'], 'zh'); ?>>中文</option>
                    </select>
                    <p class="description">Default language for new calculator instances</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Default Currency / Standardwährung</th>
                <td>
                    <select name="default_currency">
                        <option value="€" <?php selected($current_settings['currency'], '€'); ?>>Euro (€)</option>
                        <option value="$" <?php selected($current_settings['currency'], '$'); ?>>US-Dollar ($)</option>
                        <option value="¥" <?php selected($current_settings['currency'], '¥'); ?>>Renminbi (¥)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">Default Unit / Standardeinheit</th>
                <td>
                    <select name="default_unit">
                        <option value="1000000" <?php selected($current_settings['unit'], '1000000'); ?>>Millions / Millionen</option>
                        <option value="1000000000" <?php selected($current_settings['unit'], '1000000000'); ?>>Billions / Milliarden</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div
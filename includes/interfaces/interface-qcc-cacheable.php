<?php
/**
 * QCC Cacheable Interface
 * 
 * Interface für Components mit Caching-Funktionalität.
 * Ermöglicht Performance-Optimierung durch intelligentes Caching.
 * 
 * @package QualityCostCalculator
 * @subpackage Infrastructure/Interfaces
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QCC Cacheable Interface
 * 
 * Definiert Contract für Components mit Caching-Support.
 * Ermöglicht intelligente Performance-Optimierung durch Cache-Management.
 * 
 * Implementiert von:
 * - Calculation Services (für teure Berechnungen)
 * - Template Components (für HTML-Fragment-Caching)
 * - Translation Services (für Übersetzungs-Caching)
 * - Asset Generators (für CSS/JS-Caching)
 */
interface QCC_Cacheable {
    
    /**
     * Generiert Cache-Key basierend auf Component-State
     * 
     * @param array $context Caching-Context (data, user, language, etc.)
     * @return string Unique cache key
     */
    public function generate_cache_key($context = array());
    
    /**
     * Gibt Cache-Lebensdauer in Sekunden zurück
     * 
     * @return int Cache TTL in seconds (0 = no caching, -1 = never expires)
     */
    public function get_cache_ttl();
    
    /**
     * Bestimmt ob Component cacheable ist
     * 
     * @param array $context Current context (user-specific, time-sensitive, etc.)
     * @return bool True wenn cacheable
     */
    public function is_cacheable($context = array());
    
    /**
     * Cache-Invalidierung bei Datenänderungen
     * 
     * @param array $context Invalidation context
     * @return bool True wenn erfolgreich invalidiert
     */
    public function invalidate_cache($context = array());
    
    /**
     * Cache-Dependencies definieren
     * 
     * @return array Array von Cache-Tags für Dependency-Tracking
     */
    public function get_cache_dependencies();
    
    /**
     * Gibt Cache-Gruppe zurück für organisiertes Cache-Management
     * 
     * @return string Cache group name
     */
    public function get_cache_group();
    
    /**
     * Warmup-Funktionalität für kritische Caches
     * 
     * @param array $context Warmup context
     * @return bool True wenn Warmup erfolgreich
     */
    public function warmup_cache($context = array());
    
    /**
     * Cache-Statistiken für Monitoring
     * 
     * @return array {
     *     @type int $hits Cache hits
     *     @type int $misses Cache misses
     *     @type float $hit_ratio Hit ratio percentage
     *     @type int $size Cache size in bytes
     * }
     */
    public function get_cache_stats();
}
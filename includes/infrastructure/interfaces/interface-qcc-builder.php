<?php
/**
 * QCC Builder Interface
 * 
 * Interface for Builder Components (Complex UI Sections) that define
 * contracts for build processes, sub-sections, dependencies and hooks.
 *
 * @package QualityCostCalculator
 * @subpackage Infrastructure\Interfaces
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

interface QCC_Builder extends QCC_Renderable {
    
    /**
     * Build process - main method to construct complex UI sections
     * 
     * @param array $config Build configuration including type, components, layout
     * @return string Complete section HTML
     */
    public function build($config);
    
    /**
     * Define sub-sections that this builder can create
     * 
     * @return array Array of sub-section definitions with their capabilities
     */
    public function get_sub_sections();
    
    /**
     * Get event hooks that this builder provides
     * 
     * @return array Array of hook definitions for extensibility
     */
    public function get_hooks();
    
    /**
     * Get required dependencies for this builder
     * 
     * @return array Required components/services that must be available
     */
    public function get_dependencies();
    
    /**
     * Validate build configuration before processing
     * 
     * @param array $config Configuration to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_build_config($config);
    
    /**
     * Get supported build types for this builder
     * 
     * @return array Array of supported build type strings
     */
    public function get_supported_build_types();
    
    /**
     * Pre-build hook - called before main build process
     * 
     * @param array $config Build configuration
     * @return array Modified configuration
     */
    public function pre_build($config);
    
    /**
     * Post-build hook - called after main build process
     * 
     * @param string $html Generated HTML
     * @param array $config Build configuration
     * @return string Modified HTML
     */
    public function post_build($html, $config);
}
<?php

declare(strict_types=1);

namespace WordpressStarter\PluginConfigurators;

/**
 * Abstract base class for plugin configurators
 *
 * Provides common functionality for automatic plugin configuration.
 * Each plugin configurator extends this class to define its specific
 * configuration logic.
 *
 * Configuration is applied:
 * 1. When the plugin is activated (via activated_plugin hook)
 * 2. On admin_init as fallback (idempotent check)
 *
 * User changes are respected - configuration is only applied once
 * unless explicitly reset.
 */
abstract class AbstractPluginConfigurator
{
    /**
     * Get the plugin slug for identification
     *
     * Should match the plugin's directory name (e.g., 'wp-optimize')
     */
    abstract public static function getPluginSlug(): string;

    /**
     * Check if the plugin is active
     *
     * Usually checks for a class or constant that the plugin defines
     */
    abstract public static function isPluginActive(): bool;

    /**
     * Check if configuration has been applied
     *
     * Returns true if the plugin is not active (not applicable)
     * or if configuration has already been applied.
     */
    public static function isConfigured(): bool
    {
        if (!static::isPluginActive()) {
            return true; // Not applicable
        }

        return static::isMarkedConfigured();
    }

    /**
     * Apply the default configuration
     *
     * Should be idempotent - safe to call multiple times.
     * Must call markConfigured() at the end on success.
     */
    abstract public static function configure(): void;

    /**
     * Get a human-readable summary of the configuration
     *
     * Used in admin notices after configuration is applied.
     */
    abstract public static function getConfigurationSummary(): string;

    /**
     * Mark configuration as complete
     *
     * Stores a flag in wp_options to prevent re-configuration.
     */
    protected static function markConfigured(): void
    {
        update_option('wp_starter_configured_' . static::getPluginSlug(), true);
    }

    /**
     * Check if marked as configured via option
     */
    protected static function isMarkedConfigured(): bool
    {
        return (bool) get_option('wp_starter_configured_' . static::getPluginSlug(), false);
    }

    /**
     * Reset configuration flag to allow re-configuration
     *
     * Useful for testing or when user wants to reapply defaults.
     */
    public static function resetConfiguration(): void
    {
        delete_option('wp_starter_configured_' . static::getPluginSlug());
    }
}

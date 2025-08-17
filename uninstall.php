<?php

/**
 * Uninstall script for SecToolbox
 *
 * @package SecToolbox
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
class SecToolbox_Uninstaller
{
    /**
     * Run uninstall process
     */
    public static function uninstall(): void
    {
        // Remove plugin options
        self::remove_options();

        // Remove capabilities
        self::remove_capabilities();

        // Clear any cached data
        self::clear_cache();

        // Remove scheduled events
        self::clear_scheduled_events();
    }

    /**
     * Remove plugin options
     */
    private static function remove_options(): void
    {
        $options = [
            'sectoolbox_version',
            'sectoolbox_settings',
            'sectoolbox_cache_timestamp',
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        // Remove transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sectoolbox_%' OR option_name LIKE '_transient_timeout_sectoolbox_%'"
        );
    }

    /**
     * Remove capabilities
     */
    private static function remove_capabilities(): void
    {
        $roles = ['administrator', 'editor'];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->remove_cap('manage_sectoolbox');
            }
        }
    }

    /**
     * Clear cache
     */
    private static function clear_cache(): void
    {
        wp_cache_flush();

        // Clear object cache if available
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('sectoolbox');
        }
    }

    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events(): void
    {
        wp_clear_scheduled_hook('sectoolbox_cleanup');
        wp_clear_scheduled_hook('sectoolbox_update_check');
    }
}

// Run uninstall
SecToolbox_Uninstaller::uninstall();

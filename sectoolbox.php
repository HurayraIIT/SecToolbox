<?php

/**
 * Plugin Name: SecToolbox
 * Plugin URI: https://github.com/abuhurayra-codes/sectoolbox
 * Description: Advanced security analysis toolkit for WordPress - inspect REST API routes, permissions, and more.
 * Version: 2.0.0
 * Author: Abu Hurayra
 * Author URI: https://github.com/abuhurayra-codes
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sectoolbox
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SECTOOLBOX_VERSION', '2.0.0');
define('SECTOOLBOX_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SECTOOLBOX_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SECTOOLBOX_PLUGIN_FILE', __FILE__);
define('SECTOOLBOX_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class loader
 */
final class SecToolbox_Loader
{
    /**
     * Single instance of the plugin
     *
     * @var SecToolbox_Loader
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return SecToolbox_Loader
     */
    public static function instance(): SecToolbox_Loader
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies(): void
    {
        require_once SECTOOLBOX_PLUGIN_DIR . 'includes/class-sectoolbox.php';
        require_once SECTOOLBOX_PLUGIN_DIR . 'includes/class-route-analyzer.php';
        require_once SECTOOLBOX_PLUGIN_DIR . 'includes/class-admin-page.php';
        require_once SECTOOLBOX_PLUGIN_DIR . 'includes/class-ajax-handler.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        if (!$this->check_requirements()) {
            return;
        }

        SecToolbox::instance();
    }

    /**
     * Check plugin requirements
     *
     * @return bool
     */
    private function check_requirements(): bool
    {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(
                    esc_html__('SecToolbox requires PHP 8.0 or higher. You are running %s.', 'sectoolbox'),
                    PHP_VERSION
                );
                echo '</p></div>';
            });
            return false;
        }

        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>';
                echo esc_html__('SecToolbox requires WordPress 5.8 or higher.', 'sectoolbox');
                echo '</p></div>';
            });
            return false;
        }

        return true;
    }

    /**
     * Plugin activation
     */
    public function activate(): void
    {
        // Set default options
        add_option('sectoolbox_version', SECTOOLBOX_VERSION);

        // Create capabilities if needed
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_sectoolbox');
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void
    {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('sectoolbox_cleanup');
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserializing
     */
    public function __wakeup() {}
}

// Initialize plugin
SecToolbox_Loader::instance();

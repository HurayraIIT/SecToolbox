<?php

/**
 * Main SecToolbox class
 *
 * @package SecToolbox
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main SecToolbox class
 */
class SecToolbox
{
    /**
     * Single instance
     *
     * @var SecToolbox
     */
    private static $instance = null;

    /**
     * Route analyzer instance
     *
     * @var SecToolbox_Route_Analyzer
     */
    private $route_analyzer;

    /**
     * Admin page instance
     *
     * @var SecToolbox_Admin_Page
     */
    private $admin_page;

    /**
     * AJAX handler instance
     *
     * @var SecToolbox_Ajax_Handler
     */
    private $ajax_handler;

    /**
     * Get instance
     *
     * @return SecToolbox
     */
    public static function instance(): SecToolbox
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
        $this->init_components();
        $this->init_hooks();
    }

    private bool $rest_ready = false;

    public function mark_rest_ready()
    {
        $this->rest_ready = true;
    }

    /**
     * Initialize components
     */
    private function init_components(): void
    {
        $this->route_analyzer = new SecToolbox_Route_Analyzer();
        $this->admin_page = new SecToolbox_Admin_Page($this->route_analyzer);
        $this->ajax_handler = new SecToolbox_Ajax_Handler($this->route_analyzer);
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('rest_api_init', [$this, 'mark_rest_ready'], 9999);
        if (is_admin()) {
            add_action('admin_init', [$this, 'check_version']);
        }
    }

    /**
     * Load text domain
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'sectoolbox',
            false,
            dirname(SECTOOLBOX_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Check version and run upgrade routines
     */
    public function check_version(): void
    {
        $current_version = get_option('sectoolbox_version', '1.0.0');

        if (version_compare($current_version, SECTOOLBOX_VERSION, '<')) {
            $this->upgrade_routine($current_version);
            update_option('sectoolbox_version', SECTOOLBOX_VERSION);
        }
    }

    /**
     * Run upgrade routines
     *
     * @param string $from_version
     */
    private function upgrade_routine(string $from_version): void
    {
        // Future upgrade routines
        if (version_compare($from_version, '2.0.0', '<')) {
            // Upgrade to 2.0.0
            $this->upgrade_to_2_0_0();
        }
    }

    /**
     * Upgrade to version 2.0.0
     */
    private function upgrade_to_2_0_0(): void
    {
        // Migration logic for 2.0.0
        delete_option('sectoolbox_old_option'); // Remove old options if any
    }

    /**
     * Get route analyzer
     *
     * @return SecToolbox_Route_Analyzer
     */
    public function get_route_analyzer(): SecToolbox_Route_Analyzer
    {
        return $this->route_analyzer;
    }

    /**
     * Get admin page
     *
     * @return SecToolbox_Admin_Page
     */
    public function get_admin_page(): SecToolbox_Admin_Page
    {
        return $this->admin_page;
    }

    /**
     * Get AJAX handler
     *
     * @return SecToolbox_Ajax_Handler
     */
    public function get_ajax_handler(): SecToolbox_Ajax_Handler
    {
        return $this->ajax_handler;
    }
}

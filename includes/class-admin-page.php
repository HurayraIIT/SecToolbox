<?php

/**
 * Admin Page class for SecToolbox
 *
 * @package SecToolbox
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Page handler
 */
class SecToolbox_Admin_Page
{
    /**
     * Route analyzer instance
     *
     * @var SecToolbox_Route_Analyzer
     */
    private SecToolbox_Route_Analyzer $route_analyzer;

    /**
     * Page hook suffix
     *
     * @var string|false
     */
    private $page_hook;

    /**
     * Constructor
     *
     * @param SecToolbox_Route_Analyzer $route_analyzer
     */
    public function __construct(SecToolbox_Route_Analyzer $route_analyzer)
    {
        $this->route_analyzer = $route_analyzer;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void
    {
        $this->page_hook = add_menu_page(
            __('SecToolbox', 'sectoolbox'),
            __('SecToolbox', 'sectoolbox'),
            'manage_sectoolbox',
            'sectoolbox',
            [$this, 'render_admin_page'],
            'dashicons-shield-alt',
            30
        );

        // Add submenu items for future features
        add_submenu_page(
            'sectoolbox',
            __('REST API Routes', 'sectoolbox'),
            __('REST API Routes', 'sectoolbox'),
            'manage_sectoolbox',
            'sectoolbox',
            [$this, 'render_admin_page']
        );

        // Placeholder for future features
        add_submenu_page(
            'sectoolbox',
            __('Shortcode Analysis', 'sectoolbox'),
            __('Shortcode Analysis', 'sectoolbox'),
            'manage_sectoolbox',
            'sectoolbox-shortcodes',
            [$this, 'render_shortcode_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix
     */
    public function enqueue_admin_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== $this->page_hook) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'sectoolbox-admin',
            SECTOOLBOX_PLUGIN_URL . 'admin/css/admin.css',
            ['wp-admin', 'dashicons'],
            SECTOOLBOX_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'sectoolbox-admin',
            SECTOOLBOX_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery', 'wp-api-fetch'],
            SECTOOLBOX_VERSION,
            true
        );

        // Localize script
        wp_localize_script('sectoolbox-admin', 'sectoolboxAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sectoolbox_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'sectoolbox'),
                'error' => __('An error occurred', 'sectoolbox'),
                'no_plugins' => __('No plugins with REST routes found', 'sectoolbox'),
                'no_routes' => __('No routes found for selected plugins', 'sectoolbox'),
                'select_plugins' => __('Please select at least one plugin', 'sectoolbox'),
                'analyzing' => __('Analyzing routes...', 'sectoolbox'),
            ]
        ]);
    }

    /**
     * Render main admin page
     */
    public function render_admin_page(): void
    {
        if (!current_user_can('manage_sectoolbox')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'sectoolbox'));
        }

?>
        <div class="wrap sectoolbox-admin">
            <h1 class="wp-heading-inline">
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>

            <hr class="wp-header-end">

            <div class="sectoolbox-header">
                <p class="description">
                    <?php esc_html_e('Analyze REST API routes and their permission structures from installed plugins to identify potential security issues.', 'sectoolbox'); ?>
                </p>
            </div>

            <div class="sectoolbox-main">
                <div class="sectoolbox-controls">
                    <div class="postbox">
                        <h2 class="handle">
                            <span><?php esc_html_e('Plugin Selection', 'sectoolbox'); ?></span>
                        </h2>
                        <div class="inside">
                            <div class="sectoolbox-form">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <?php esc_html_e('Select Plugins to Analyze:', 'sectoolbox'); ?>
                                        </th>
                                        <td>
                                            <div id="plugin-checkboxes" class="sectoolbox-plugin-list">
                                                <p class="loading-text"><?php esc_html_e('Loading plugins...', 'sectoolbox'); ?></p>
                                            </div>
                                            <p class="description">
                                                <?php esc_html_e('Select the plugins you want to analyze. Only plugins with REST API routes are shown.', 'sectoolbox'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <button id="inspect-routes-btn" class="button button-primary" disabled>
                                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                        <span class="button-text"><?php esc_html_e('Analyze Selected Routes', 'sectoolbox'); ?></span>
                                    </button>
                                    <span id="loading-spinner" class="spinner"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                                <div id="results-container" class="sectoolbox-results" style="display: none;">
                    <div class="postbox">
                        <h2 class="handle">
                            <span><?php esc_html_e('REST API Routes', 'sectoolbox'); ?></span>
                        </h2>
                        <div class="inside">
                            <div id="route-list" class="sectoolbox-route-list"></div>
                        </div>
                    </div>
                </div>
    <?php
    }

    /**
     * Render shortcode analysis page (placeholder for future feature)
     */
    public function render_shortcode_page(): void
    {
        if (!current_user_can('manage_sectoolbox')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'sectoolbox'));
        }

    ?>
        <div class="wrap sectoolbox-admin">
            <h1><?php esc_html_e('Shortcode Analysis', 'sectoolbox'); ?></h1>
            <div class="notice notice-info">
                <p><?php esc_html_e('Shortcode analysis feature is coming soon in a future release.', 'sectoolbox'); ?></p>
            </div>
        </div>
<?php
    }
}

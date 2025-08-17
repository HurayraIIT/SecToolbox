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
                        <h2 class="hndle">
                            <span><?php esc_html_e('Plugin Selection', 'sectoolbox'); ?></span>
                        </h2>
                        <div class="inside">
                            <div class="sectoolbox-form">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="plugin-select">
                                                <?php esc_html_e('Select Plugins to Analyze:', 'sectoolbox'); ?>
                                            </label>
                                        </th>
                                        <td>
                                            <select id="plugin-select" multiple size="8" class="regular-text">
                                                <option value=""><?php esc_html_e('Loading plugins...', 'sectoolbox'); ?></option>
                                            </select>
                                            <p class="description">
                                                <?php esc_html_e('Hold Ctrl/Cmd to select multiple plugins. Only plugins with REST API routes are shown.', 'sectoolbox'); ?>
                                            </p>
                                        </td>
                                    </tr>
                                </table>

                                <p class="submit">
                                    <button id="inspect-routes-btn" class="button button-primary" disabled>
                                        <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                        <?php esc_html_e('Analyze Selected Routes', 'sectoolbox'); ?>
                                    </button>
                                    <span id="loading-spinner" class="spinner"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="results-container" class="sectoolbox-results" style="display: none;">
                    <div class="sectoolbox-filters postbox">
                        <h2 class="hndle">
                            <span><?php esc_html_e('Filters & Legend', 'sectoolbox'); ?></span>
                        </h2>
                        <div class="inside">
                            <div class="sectoolbox-filter-controls">
                                <div class="filter-group">
                                    <label for="route-filter"><?php esc_html_e('Filter by route:', 'sectoolbox'); ?></label>
                                    <input type="text" id="route-filter" class="regular-text" placeholder="<?php esc_attr_e('e.g. /users, /posts', 'sectoolbox'); ?>">
                                </div>

                                <div class="filter-group">
                                    <label for="method-filter"><?php esc_html_e('HTTP Method:', 'sectoolbox'); ?></label>
                                    <select id="method-filter" class="regular-text">
                                        <option value=""><?php esc_html_e('All Methods', 'sectoolbox'); ?></option>
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="PATCH">PATCH</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="access-filter"><?php esc_html_e('Access Level:', 'sectoolbox'); ?></label>
                                    <select id="access-filter" class="regular-text">
                                        <option value=""><?php esc_html_e('All Access Levels', 'sectoolbox'); ?></option>
                                        <option value="public"><?php esc_html_e('Public Access', 'sectoolbox'); ?></option>
                                        <option value="subscriber"><?php esc_html_e('Subscriber+', 'sectoolbox'); ?></option>
                                        <option value="contributor"><?php esc_html_e('Contributor+', 'sectoolbox'); ?></option>
                                        <option value="author"><?php esc_html_e('Author+', 'sectoolbox'); ?></option>
                                        <option value="editor"><?php esc_html_e('Editor+', 'sectoolbox'); ?></option>
                                        <option value="admin"><?php esc_html_e('Admin Only', 'sectoolbox'); ?></option>
                                        <option value="custom"><?php esc_html_e('Custom/Unknown', 'sectoolbox'); ?></option>
                                    </select>
                                </div>

                                <div class="filter-group">
                                    <label for="risk-filter"><?php esc_html_e('Risk Level:', 'sectoolbox'); ?></label>
                                    <select id="risk-filter" class="regular-text">
                                        <option value=""><?php esc_html_e('All Risk Levels', 'sectoolbox'); ?></option>
                                        <option value="high"><?php esc_html_e('High Risk', 'sectoolbox'); ?></option>
                                        <option value="medium"><?php esc_html_e('Medium Risk', 'sectoolbox'); ?></option>
                                        <option value="low"><?php esc_html_e('Low Risk', 'sectoolbox'); ?></option>
                                    </select>
                                </div>

                                <div class="filter-actions">
                                    <button id="clear-filters" class="button">
                                        <?php esc_html_e('Clear Filters', 'sectoolbox'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="sectoolbox-legend">
                                <h3><?php esc_html_e('Security Level Legend', 'sectoolbox'); ?></h3>
                                <div class="legend-items">
                                    <div class="legend-item risk-high">
                                        <span class="legend-color"></span>
                                        <?php esc_html_e('High Risk', 'sectoolbox'); ?>
                                        <span class="legend-desc"><?php esc_html_e('Public write access', 'sectoolbox'); ?></span>
                                    </div>
                                    <div class="legend-item risk-medium">
                                        <span class="legend-color"></span>
                                        <?php esc_html_e('Medium Risk', 'sectoolbox'); ?>
                                        <span class="legend-desc"><?php esc_html_e('Public read or protected write', 'sectoolbox'); ?></span>
                                    </div>
                                    <div class="legend-item risk-low">
                                        <span class="legend-color"></span>
                                        <?php esc_html_e('Low Risk', 'sectoolbox'); ?>
                                        <span class="legend-desc"><?php esc_html_e('Admin only or read-only', 'sectoolbox'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sectoolbox-routes-analysis postbox">
                        <h2 class="hndle">
                            <span><?php esc_html_e('Routes Analysis Results', 'sectoolbox'); ?></span>
                            <span id="results-count" class="results-count"></span>
                        </h2>
                        <div class="inside">
                            <div id="routes-table-container"></div>
                        </div>
                    </div>
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

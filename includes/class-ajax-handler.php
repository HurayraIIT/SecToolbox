<?php

/**
 * AJAX Handler class for SecToolbox
 *
 * @package SecToolbox
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Handler
 */
class SecToolbox_Ajax_Handler
{
    /**
     * Route analyzer instance
     *
     * @var SecToolbox_Route_Analyzer
     */
    private SecToolbox_Route_Analyzer $route_analyzer;

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
        add_action('wp_ajax_sectoolbox_get_plugins', [$this, 'ajax_get_plugins']);
        add_action('wp_ajax_sectoolbox_inspect_routes', [$this, 'ajax_inspect_routes']);
    }

    /**
     * AJAX handler for getting plugins
     */
    public function ajax_get_plugins(): void
    {
        try {
            $this->verify_ajax_request();

            $plugins = $this->route_analyzer->get_plugins_with_rest_routes();

            wp_send_json_success([
                'plugins' => $plugins,
                'total' => count($plugins)
            ]);
        } catch (Exception $e) {
            error_log('SecToolbox: Error in ajax_get_plugins - ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Failed to load plugins. Please try again.', 'sectoolbox')
            ]);
        }
    }

    /**
     * AJAX handler for inspecting routes
     */
    public function ajax_inspect_routes(): void
    {
        try {
            $this->verify_ajax_request();

            $selected_plugins = $this->get_selected_plugins();

            if (empty($selected_plugins)) {
                wp_send_json_error([
                    'message' => __('No plugins selected for analysis.', 'sectoolbox')
                ]);
                return;
            }

            $routes = $this->route_analyzer->analyze_plugin_routes($selected_plugins);

            $stats = $this->calculate_stats($routes);

            wp_send_json_success([
                'routes' => $routes,
                'stats' => $stats,
                'total' => count($routes)
            ]);
        } catch (Exception $e) {
            error_log('SecToolbox: Error in ajax_inspect_routes - ' . $e->getMessage());
            wp_send_json_error([
                'message' => __('Failed to analyze routes. Please try again.', 'sectoolbox')
            ]);
        }
    }

    /**
     * Verify AJAX request
     *
     * @throws Exception
     */
    private function verify_ajax_request(): void
    {
        if (!check_ajax_referer('sectoolbox_nonce', 'nonce', false)) {
            throw new Exception('Invalid nonce');
        }

        if (!current_user_can('manage_sectoolbox')) {
            throw new Exception('Insufficient permissions');
        }
    }

    /**
     * Get selected plugins from request
     *
     * @return array
     */
    private function get_selected_plugins(): array
    {
        $plugins = $_POST['plugins'] ?? [];

        if (!is_array($plugins)) {
            return [];
        }

        return array_filter(array_map('sanitize_text_field', $plugins));
    }

    /**
     * Calculate statistics for routes
     *
     * @param array $routes
     * @return array
     */
    private function calculate_stats(array $routes): array
    {
        $stats = [
            'total' => count($routes),
            'by_access_level' => [],
            'by_risk_level' => [],
            'by_plugin' => [],
            'public_write_routes' => 0,
            'admin_only_routes' => 0
        ];

        foreach ($routes as $route) {
            // Count by access level
            $access_level = $route['access_level'];
            $stats['by_access_level'][$access_level] = ($stats['by_access_level'][$access_level] ?? 0) + 1;

            // Count by risk level
            $risk_level = $route['risk_level'];
            $stats['by_risk_level'][$risk_level] = ($stats['by_risk_level'][$risk_level] ?? 0) + 1;

            // Count by plugin
            $plugin_name = $route['plugin_name'];
            $stats['by_plugin'][$plugin_name] = ($stats['by_plugin'][$plugin_name] ?? 0) + 1;

            // Special counts
            if ($access_level === 'public' && !empty(array_intersect($route['methods'], ['POST', 'PUT', 'PATCH', 'DELETE']))) {
                $stats['public_write_routes']++;
            }

            if ($access_level === 'administrator') {
                $stats['admin_only_routes']++;
            }
        }

        return $stats;
    }
}

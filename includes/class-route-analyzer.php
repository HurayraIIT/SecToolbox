<?php

/**
 * Route Analyzer class for SecToolbox
 *
 * @package SecToolbox
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API Route Analyzer
 */
class SecToolbox_Route_Analyzer
{
    /**
     * Admin capabilities
     *
     * @var array
     */
    private array $admin_capabilities = [
        'manage_options',
        'install_plugins',
        'activate_plugins',
        'edit_plugins',
        'delete_plugins',
        'update_plugins',
        'manage_categories',
        'manage_links',
        'upload_files',
        'import',
        'unfiltered_html',
        'edit_themes',
        'install_themes',
        'update_themes',
        'delete_themes',
        'edit_users',
        'list_users',
        'remove_users',
        'add_users',
        'create_users',
        'delete_users',
        'promote_users'
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        /**
         * Filter admin capabilities
         *
         * @param array $capabilities Admin capabilities
         */
        $this->admin_capabilities = apply_filters('sectoolbox_admin_capabilities', $this->admin_capabilities);
    }

    /**
     * Get plugins with REST routes
     *
     * @return array
     */
    public function get_plugins_with_rest_routes(): array
    {
        global $wp_rest_server;

        if (empty($wp_rest_server)) {
            $wp_rest_server = rest_get_server();
        }

        $routes = $wp_rest_server->get_routes();
        $plugin_namespaces = [];

        foreach ($routes as $route => $handlers) {
            // Skip core WordPress routes
            if (preg_match('#^/(wp/v2|oembed)#', $route)) {
                continue;
            }

            // Extract namespace
            if (preg_match('#^/([^/]+)#', $route, $matches)) {
                $namespace = $matches[1];

                // Skip core namespaces
                if (in_array($namespace, ['wp', 'oembed'])) {
                    continue;
                }

                if (!isset($plugin_namespaces[$namespace])) {
                    $plugin_name = $this->guess_plugin_name($namespace, $handlers[0] ?? []);
                    $plugin_namespaces[$namespace] = [
                        'namespace' => $namespace,
                        'name' => $plugin_name,
                        'route_count' => 0
                    ];
                }
                $plugin_namespaces[$namespace]['route_count']++;
            }
        }

        // Sort by plugin name
        uasort($plugin_namespaces, fn($a, $b) => strcmp($a['name'], $b['name']));

        /**
         * Filter detected plugins
         *
         * @param array $plugin_namespaces Detected plugins
         */
        return apply_filters('sectoolbox_detected_plugins', array_values($plugin_namespaces));
    }

    /**
     * Analyze plugin routes
     *
     * @param array $selected_plugins
     * @return array
     */
    public function analyze_plugin_routes(array $selected_plugins): array
    {
        global $wp_rest_server;

        if (empty($wp_rest_server)) {
            $wp_rest_server = rest_get_server();
        }

        $routes = $wp_rest_server->get_routes();
        $analyzed_routes = [];

        foreach ($routes as $route => $handlers) {
            // Skip core routes
            if (preg_match('#^/(wp/v2|oembed)#', $route)) {
                continue;
            }

            // Extract namespace
            $namespace = '';
            if (preg_match('#^/([^/]+)#', $route, $matches)) {
                $namespace = $matches[1];
            }

            // Skip if not in selected plugins
            if (!in_array($namespace, $selected_plugins)) {
                continue;
            }

            foreach ($handlers as $handler) {
                $route_analysis = $this->analyze_single_route($route, $handler, $namespace);
                if ($route_analysis) {
                    $analyzed_routes[] = $route_analysis;
                }
            }
        }

        // Sort by plugin name, then route
        usort($analyzed_routes, function ($a, $b) {
            $plugin_cmp = strcmp($a['plugin_name'], $b['plugin_name']);
            return $plugin_cmp !== 0 ? $plugin_cmp : strcmp($a['route'], $b['route']);
        });

        return $analyzed_routes;
    }

    /**
     * Analyze single route
     *
     * @param string $route
     * @param array $handler
     * @param string $namespace
     * @return array|null
     */
    private function analyze_single_route(string $route, array $handler, string $namespace): ?array
    {
        try {
            $methods = isset($handler['methods']) ? array_keys(array_filter($handler['methods'])) : [];
            $permission_callback = $handler['permission_callback'] ?? null;

            $permission_analysis = $this->analyze_permission_callback($permission_callback);

            return [
                'route' => $route,
                'namespace' => $namespace,
                'plugin_name' => $this->format_plugin_name($namespace),
                'methods' => $methods,
                'access_level' => $permission_analysis['access_level'],
                'capabilities' => $permission_analysis['capabilities'],
                'custom_roles' => $permission_analysis['custom_roles'],
                'permission_callback_info' => $permission_analysis['callback_info'],
                'risk_level' => $this->calculate_risk_level($permission_analysis, $methods)
            ];
        } catch (Exception $e) {
            error_log('SecToolbox: Error analyzing route ' . $route . ' - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate risk level
     *
     * @param array $permission_analysis
     * @param array $methods
     * @return string
     */
    private function calculate_risk_level(array $permission_analysis, array $methods): string
    {
        $access_level = $permission_analysis['access_level'];

        // High risk: Public write operations
        if ($access_level === 'public' && !empty(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']))) {
            return 'high';
        }

        // Medium risk: Public read or protected write
        if (
            $access_level === 'public' ||
            ($access_level !== 'admin' && !empty(array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE'])))
        ) {
            return 'medium';
        }

        // Low risk: Admin only or read-only
        return 'low';
    }

    /**
     * Analyze permission callback
     *
     * @param mixed $permission_callback
     * @return array
     */
    private function analyze_permission_callback($permission_callback): array
    {
        $result = [
            'access_level' => 'unknown',
            'capabilities' => [],
            'custom_roles' => [],
            'callback_info' => ''
        ];

        if ($permission_callback === null || $permission_callback === '__return_true') {
            $result['access_level'] = 'public';
            $result['callback_info'] = $permission_callback === null ?
                __('No permission callback', 'sectoolbox') :
                __('Public access (__return_true)', 'sectoolbox');
            return $result;
        }

        if (is_string($permission_callback)) {
            $result['callback_info'] = sprintf(__('Function: %s', 'sectoolbox'), $permission_callback);

            if (strpos($permission_callback, 'current_user_can') !== false) {
                $result['access_level'] = 'custom';
            }

            return $result;
        }

        if (is_array($permission_callback) || is_callable($permission_callback)) {
            $callback_name = $this->format_callback($permission_callback);
            $result['callback_info'] = $callback_name;

            $capabilities = $this->extract_capabilities_from_callback($permission_callback);

            if (!empty($capabilities)) {
                $result['capabilities'] = $capabilities;
                $result['access_level'] = $this->determine_access_level($capabilities);

                $custom_roles = $this->find_custom_roles_for_capabilities($capabilities);
                if (!empty($custom_roles)) {
                    $result['custom_roles'] = $custom_roles;
                }
            } else {
                $result['access_level'] = 'custom';
            }

            return $result;
        }

        $result['callback_info'] = __('Complex callback (Closure)', 'sectoolbox');
        $result['access_level'] = 'custom';
        return $result;
    }

    /**
     * Format callback for display
     *
     * @param mixed $callback
     * @return string
     */
    private function format_callback($callback): string
    {
        if (is_array($callback)) {
            if (is_object($callback[0])) {
                return get_class($callback[0]) . '::' . $callback[1];
            }
            return implode('::', $callback);
        }

        if (is_string($callback)) {
            return $callback;
        }

        return __('Closure/Anonymous function', 'sectoolbox');
    }

    /**
     * Extract capabilities from callback
     *
     * @param callable $callback
     * @return array
     */
    private function extract_capabilities_from_callback($callback): array
    {
        $capabilities = [];

        if (!is_callable($callback)) {
            return $capabilities;
        }

        try {
            if (is_array($callback)) {
                $reflection = new ReflectionMethod($callback[0], $callback[1]);
            } else {
                $reflection = new ReflectionFunction($callback);
            }

            $filename = $reflection->getFileName();
            $start_line = $reflection->getStartLine() - 1;
            $end_line = $reflection->getEndLine();
            $length = $end_line - $start_line;

            if ($filename && file_exists($filename)) {
                $source = file($filename);
                if ($source !== false && $length > 0) {
                    $function_source = implode("", array_slice($source, $start_line, $length));

                    // Extract capabilities from current_user_can calls
                    if (preg_match_all('/current_user_can\s*\(\s*[\'"]([^\'"]+)[\'"]/', $function_source, $matches)) {
                        $capabilities = array_merge($capabilities, $matches[1]);
                    }

                    // Look for user_can calls
                    if (preg_match_all('/user_can\s*\([^,]+,\s*[\'"]([^\'"]+)[\'"]/', $function_source, $matches)) {
                        $capabilities = array_merge($capabilities, $matches[1]);
                    }
                }
            }
        } catch (Exception $e) {
            error_log('SecToolbox: Error extracting capabilities - ' . $e->getMessage());
        }

        return array_unique($capabilities);
    }

    /**
     * Determine access level from capabilities
     *
     * @param array $capabilities
     * @return string
     */
    private function determine_access_level(array $capabilities): string
    {
        if (empty($capabilities)) {
            return 'unknown';
        }

        // Check for admin-only capabilities
        $admin_caps_found = array_intersect($capabilities, $this->admin_capabilities);
        if (!empty($admin_caps_found)) {
            return 'admin';
        }

        // Define role hierarchy with capabilities
        $role_hierarchy = [
            'subscriber' => ['read'],
            'contributor' => ['read', 'edit_posts', 'delete_posts'],
            'author' => ['read', 'edit_posts', 'delete_posts', 'publish_posts', 'upload_files'],
            'editor' => [
                'read',
                'edit_posts',
                'delete_posts',
                'publish_posts',
                'upload_files',
                'edit_others_posts',
                'delete_others_posts',
                'edit_published_posts',
                'delete_published_posts',
                'edit_pages',
                'delete_pages',
                'publish_pages',
                'edit_others_pages',
                'delete_others_pages',
                'edit_published_pages',
                'delete_published_pages',
                'moderate_comments'
            ]
        ];

        $min_role = 'admin';

        foreach ($capabilities as $cap) {
            foreach ($role_hierarchy as $role => $role_caps) {
                if (in_array($cap, $role_caps)) {
                    if ($this->compare_role_hierarchy($role, $min_role) < 0) {
                        $min_role = $role;
                    }
                }
            }
        }

        return $min_role;
    }

    /**
     * Compare role hierarchy
     *
     * @param string $role1
     * @param string $role2
     * @return int
     */
    private function compare_role_hierarchy(string $role1, string $role2): int
    {
        $hierarchy = ['subscriber' => 0, 'contributor' => 1, 'author' => 2, 'editor' => 3, 'admin' => 4];
        $level1 = $hierarchy[$role1] ?? 4;
        $level2 = $hierarchy[$role2] ?? 4;
        return $level1 - $level2;
    }

    /**
     * Find custom roles for capabilities
     *
     * @param array $capabilities
     * @return array
     */
    private function find_custom_roles_for_capabilities(array $capabilities): array
    {
        $custom_roles = [];
        $all_roles = wp_roles()->roles;
        $standard_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

        foreach ($capabilities as $cap) {
            foreach ($all_roles as $role_name => $role_data) {
                if (in_array($role_name, $standard_roles)) {
                    continue;
                }

                if (isset($role_data['capabilities'][$cap]) && $role_data['capabilities'][$cap]) {
                    $custom_roles[] = $role_name;
                }
            }
        }

        return array_unique($custom_roles);
    }

    /**
     * Guess plugin name from namespace
     *
     * @param string $namespace
     * @param array $handler
     * @return string
     */
    private function guess_plugin_name(string $namespace, array $handler): string
    {
        // Try to get plugin name from callback
        $callback = $handler['callback'] ?? null;

        if (is_array($callback) && is_object($callback[0])) {
            $class_name = get_class($callback[0]);

            if (preg_match('/^(.+?)(_|\\\\)/', $class_name, $matches)) {
                return $this->format_plugin_name($matches[1]);
            }
        }

        // Known namespace mappings
        $known_namespaces = [
            'wc' => 'WooCommerce',
            'wc-admin' => 'WooCommerce Admin',
            'yoast' => 'Yoast SEO',
            'elementor' => 'Elementor',
            'buddypress' => 'BuddyPress',
            'bbpress' => 'bbPress',
            'learndash' => 'LearnDash',
            'tribe' => 'The Events Calendar',
            'gravityforms' => 'Gravity Forms',
            'contact-form-7' => 'Contact Form 7',
            'jetpack' => 'Jetpack',
            'woocommerce' => 'WooCommerce'
        ];

        return $known_namespaces[$namespace] ?? $this->format_plugin_name($namespace);
    }

    /**
     * Format plugin name
     *
     * @param string $name
     * @return string
     */
    private function format_plugin_name(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', $name);
        return ucwords($name);
    }
}

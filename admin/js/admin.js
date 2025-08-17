/**
 * SecToolbox Admin JavaScript
 */
(function ($) {
  "use strict";

  // Global variables
  let allRoutes = [];

  // Initialize when document is ready
  $(document).ready(function () {
    initializeSecToolbox();
  });

  /**
   * Initialize the SecToolbox interface
   */
  function initializeSecToolbox() {
    bindEvents();
    loadPlugins();
  }

  /**
   * Bind event handlers
   */
  function bindEvents() {
    $(document).on("change", ".plugin-checkbox", handlePluginSelection);
    $("#inspect-routes-btn").on("click", inspectRoutes);
    $(document).on("keydown", handleKeyboardShortcuts);
  }

  /**
   * Handle plugin selection
   */
  function handlePluginSelection() {
    const selectedCount = $(".plugin-checkbox:checked").length;
    const hasSelection = selectedCount > 0;

    $("#inspect-routes-btn").prop("disabled", !hasSelection);

    if (hasSelection) {
      $("#inspect-routes-btn").removeClass("button-secondary").addClass("button-primary");
    } else {
      $("#inspect-routes-btn").removeClass("button-primary").addClass("button-secondary");
    }
  }

  /**
   * Load plugins with REST routes
   */
  function loadPlugins() {
    const $container = $("#plugin-checkboxes");

    // Show loading state
    $container.html('<p class="loading-text">' + sectoolboxAjax.strings.loading + "</p>");

    $.ajax({
      url: sectoolboxAjax.ajaxurl,
      type: "POST",
      data: {
        action: "sectoolbox_get_plugins",
        nonce: sectoolboxAjax.nonce,
      },
      timeout: 10000,
      success: function (response) {
        if (response.success && response.data.plugins) {
          populatePluginCheckboxes(response.data.plugins);
        } else {
          showError(response.data?.message || sectoolboxAjax.strings.error);
        }
      },
      error: function (xhr, status, error) {
        showError(`${sectoolboxAjax.strings.error}: ${error}`);
        $container.html(`<p class="error">${sectoolboxAjax.strings.no_plugins}</p>`);
      },
    });
  }

  /**
   * Populate plugin checkboxes
   */
  function populatePluginCheckboxes(plugins) {
    const $container = $("#plugin-checkboxes");
    $container.empty();

    if (!plugins.length) {
      $container.append(`<p class="no-plugins">${sectoolboxAjax.strings.no_plugins}</p>`);
      return;
    }

    // Sort plugins alphabetically
    plugins.sort((a, b) => (a.name || a.namespace).localeCompare(b.name || b.namespace));

    plugins.forEach(function (plugin) {
      const pluginName = plugin.namespace || plugin.name;
      const routeCount = plugin.route_count ? ` (${plugin.route_count} routes)` : "";
      const displayName = pluginName + routeCount;

      const checkbox = $('<div class="plugin-checkbox-wrapper"></div>').append(
        $("<label></label>").append(
          $("<input>").attr({
            type: "checkbox",
            class: "plugin-checkbox",
            value: plugin.namespace,
            "data-name": pluginName,
          }),
          $("<span></span>").text(" " + displayName)
        )
      );

      $container.append(checkbox);
    });
  }

  /**
   * Inspect selected routes
   */
  function inspectRoutes() {
    const selectedPlugins = $(".plugin-checkbox:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    if (!selectedPlugins.length) {
      showError(sectoolboxAjax.strings.select_plugins);
      return;
    }

    // Update UI state
    setLoadingState(true);
    $("#results-container").hide();

    $.ajax({
      url: sectoolboxAjax.ajaxurl,
      type: "POST",
      data: {
        action: "sectoolbox_inspect_routes",
        plugins: selectedPlugins,
        nonce: sectoolboxAjax.nonce,
      },
      timeout: 30000,
      success: function (response) {
        if (response.success && response.data.routes) {
          allRoutes = response.data.routes;
          // No more risk-based sort, just return as-is
          displayResults(allRoutes, response.data.stats);
          $("#results-container").slideDown();

          // Scroll to results
          $("html, body").animate(
            {
              scrollTop: $("#results-container").offset().top - 50,
            },
            500
          );
        } else {
          showError(response.data?.message || sectoolboxAjax.strings.error);
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          showError("Analysis timed out. Try selecting fewer plugins.");
        } else {
          showError(`${sectoolboxAjax.strings.error}: ${error}`);
        }
      },
      complete: function () {
        setLoadingState(false);
      },
    });
  }

  /**
   * Display analysis results
   */
  function displayResults(routes, stats) {
    const container = $("#route-list");

    if (!routes.length) {
      container.html(`
                <div class="no-results">
                    <div class="dashicons dashicons-search"></div>
                    <p>${sectoolboxAjax.strings.no_routes}</p>
                </div>
            `);
      updateResultsCount(0);
      return;
    }

    // Build table HTML
    let html = `
            <table class="sectoolbox-routes-table wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-plugin">Plugin</th>
                        <th scope="col" class="column-route">Route</th>
                        <th scope="col" class="column-methods">Methods</th>
                        <th scope="col" class="column-access">Access Level</th>
                        <th scope="col" class="column-details">Details</th>
                        <th scope="col" class="column-callback">Permission Callback</th>
                    </tr>
                </thead>
                <tbody>
        `;

    routes.forEach(function (route) {
      html += buildRouteRow(route);
    });

    html += "</tbody></table>";
    container.html(html);

    updateResultsCount(routes.length);
  }

  /**
   * Build individual route row HTML
   */
  function buildRouteRow(route) {
    const accessClass = `access-${route.access_level}`;

    return `
            <tr class="route-row"
                data-route="${escapeHtml(route.route)}"
                data-methods="${route.methods.join(",")}"
                data-access="${route.access_level}"
                data-plugin="${escapeHtml(route.namespace)}">

                <td class="column-plugin">
                    <strong class="plugin-name">${escapeHtml(route.plugin_name)}</strong>
                </td>

                <td class="column-route">
                    <code class="route-path">${escapeHtml(route.route)}</code>
                </td>

                <td class="column-methods">
                    ${formatMethods(route.methods)}
                </td>

                <td class="column-access">
                    <span class="access-level ${accessClass}">
                        ${formatAccessLevel(route.access_level)}
                    </span>
                </td>

                <td class="column-details">
                    ${formatPermissionDetails(route)}
                </td>

                <td class="column-callback">
                    ${
                      route.permission_callback_info
                        ? `<code>${escapeHtml(route.permission_callback_info)}</code>`
                        : "<em>None</em>"
                    }
                </td>
            </tr>
        `;
  }

  /**
   * Format HTTP methods with badges
   */
  function formatMethods(methods) {
    return methods
      .map(function (method) {
        return `<span class="method-badge method-${method}">${method}</span>`;
      })
      .join("");
  }

  /**
   * Format access level
   */
  function formatAccessLevel(level) {
    const labels = {
      administrator: "Administrator",
      editor: "Editor+",
      author: "Author+",
      contributor: "Contributor+",
      subscriber: "Subscriber+",
      public: "Public",
      custom: "Custom",
      unknown: "Unknown",
    };
    return labels[level] || level;
  }

  /**
   * Format permission details
   */
  function formatPermissionDetails(route) {
    let html = "";

    if (route.capabilities && route.capabilities.length > 0) {
      const caps = route.capabilities.slice(0, 2); // Show first 2 capabilities
      const remaining = route.capabilities.length - caps.length;

      html += `<div class="permission-summary">
                <strong>Caps:</strong> ${caps.join(", ")}
                ${remaining > 0 ? ` <em>(+${remaining} more)</em>` : ""}
            </div>`;
    }

    if (route.custom_roles && route.custom_roles.length > 0) {
      html += `<div class="role-summary">
                <strong>Roles:</strong> ${route.custom_roles.join(", ")}
            </div>`;
    }

    return html || "<em>No specific requirements</em>";
  }

  /**
   * Update results count display
   */
  function updateResultsCount(count) {
    const $counter = $("#results-count");
    const text = count === 0 ? "No results" : `${count} routes`;
    $counter.text(text);
  }

  /**
   * Set loading state
   */
  function setLoadingState(loading) {
    const $button = $("#inspect-routes-btn");
    const $spinner = $("#loading-spinner");

    if (loading) {
      $button.prop("disabled", true).find(".button-text").text(sectoolboxAjax.strings.analyzing);
      $spinner.addClass("is-active");
    } else {
      $button.prop("disabled", false).find(".button-text").text("Analyze Selected Routes");
      $spinner.removeClass("is-active");

      // Re-check plugin selection
      handlePluginSelection();
    }
  }

  /**
   * Show error message
   */
  function showError(message) {
    const errorHtml = `
            <div class="notice notice-error is-dismissible sectoolbox-error">
                <p>
                    <span class="dashicons dashicons-warning"></span>
                    <strong>SecToolbox Error:</strong> ${escapeHtml(message)}
                </p>
            </div>
        `;

    $(".sectoolbox-error").remove();
    $(".sectoolbox-main").prepend(errorHtml);

    $(".sectoolbox-error .notice-dismiss").on("click", function () {
      $(this).closest(".sectoolbox-error").fadeOut();
    });

    setTimeout(function () {
      $(".sectoolbox-error").fadeOut();
    }, 8000);
  }

  /**
   * Handle keyboard shortcuts
   */
  function handleKeyboardShortcuts(e) {
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
      e.preventDefault();
      if (!$("#inspect-routes-btn").prop("disabled")) {
        inspectRoutes();
      }
    }
    if (e.keyCode === 27 && $("#results-container").is(":visible")) {
      clearAllFilters();
    }
  }

  /**
   * Escape HTML for safe output
   */
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text || "";
    return div.innerHTML;
  }

  // Export functions for testing (if needed)
  window.SecToolbox = {
    loadPlugins,
    inspectRoutes,
  };
})(jQuery);

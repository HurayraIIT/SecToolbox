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
    $("#plugin-select").on("change", handlePluginSelection);
    $("#inspect-routes-btn").on("click", inspectRoutes);
    $(document).on("keydown", handleKeyboardShortcuts);
  }

  /**
   * Handle plugin selection
   */
  function handlePluginSelection() {
    const selected = $("#plugin-select").val();
    const hasSelection = selected && selected.length > 0;

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
    const $select = $("#plugin-select");

    // Show loading state
    $select.html('<option value="">' + sectoolboxAjax.strings.loading + "</option>");

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
          populatePluginSelect(response.data.plugins);
        } else {
          showError(response.data?.message || sectoolboxAjax.strings.error);
        }
      },
      error: function (xhr, status, error) {
        showError(`${sectoolboxAjax.strings.error}: ${error}`);
        $select.html(`<option value="">${sectoolboxAjax.strings.no_plugins}</option>`);
      },
    });
  }

  /**
   * Populate plugin selection dropdown
   */
  function populatePluginSelect(plugins) {
    const $select = $("#plugin-select");

    $select.empty();

    if (!plugins.length) {
      $select.append(`<option value="">${sectoolboxAjax.strings.no_plugins}</option>`);
      return;
    }

    plugins.forEach(function (plugin) {
      const pluginName = plugin.namespace || plugin.name;  // Fallback to name if namespace is missing
      const routeCount = plugin.route_count ? ` (${plugin.route_count} routes)` : "";
      const displayName = pluginName + routeCount;

      $select.append(
        $("<option>")
          .val(plugin.namespace)
          .text(displayName)
          .attr("title", `Namespace: ${plugin.namespace}`)
      );
    });
  }

  /**
   * Group plugins by first letter
   */
  function groupPluginsByLetter(plugins) {
    return plugins.reduce(function (acc, plugin) {
      const letter = plugin.name.charAt(0).toUpperCase();
      if (!acc[letter]) {
        acc[letter] = [];
      }
      acc[letter].push(plugin);
      return acc;
    }, {});
  }

  /**
   * Inspect selected routes
   */
  function inspectRoutes() {
    const selectedPlugins = $("#plugin-select").val();

    if (!selectedPlugins || !selectedPlugins.length) {
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
                        <th scope="col" class="column-risk">Risk</th>
                        <th scope="col" class="column-details">Details</th>
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
    
    // Add click handlers for expandable details
    bindRouteRowEvents();
  }

  /**
   * Build individual route row HTML
   */
  function buildRouteRow(route) {
    const riskClass = `risk-${route.risk_level}`;
    const accessClass = `access-${route.access_level}`;

    return `
            <tr class="route-row ${riskClass}" 
                data-route="${escapeHtml(route.route)}"
                data-methods="${route.methods.join(",")}"
                data-access="${route.access_level}"
                data-risk="${route.risk_level}"
                data-plugin="${escapeHtml(route.namespace)}">
                
                <td class="column-plugin">
                    <strong class="plugin-name">${escapeHtml(route.plugin_name)}</strong>
                    <div class="plugin-namespace">${escapeHtml(route.namespace)}</div>
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
                
                <td class="column-risk">
                    <span class="risk-indicator ${riskClass}">
                        ${formatRiskLevel(route.risk_level)}
                    </span>
                </td>
                
                <td class="column-details">
                    ${formatPermissionDetails(route)}
                    <button type="button" class="button-link toggle-details" 
                            aria-expanded="false" aria-label="Toggle details">
                        <span class="screen-reader-text">Toggle details</span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="route-details" style="display: none;">
                        ${buildDetailedInfo(route)}
                    </div>
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
      admin: "Admin Only",
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
   * Format risk level
   */
  function formatRiskLevel(level) {
    const labels = {
      high: "High Risk",
      medium: "Medium Risk",
      low: "Low Risk",
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

    if (route.permission_callback_info) {
      const callback =
        route.permission_callback_info.length > 30
          ? route.permission_callback_info.substring(0, 30) + "..."
          : route.permission_callback_info;
      html += `<div class="callback-summary"><strong>Callback:</strong> ${escapeHtml(callback)}</div>`;
    }

    return html || "<em>No specific requirements</em>";
  }

  /**
   * Build detailed information panel
   */
  function buildDetailedInfo(route) {
    let html = '<div class="detailed-info">';

    // Full callback information
    if (route.permission_callback_info) {
      html += `
                <div class="detail-section">
                    <h4>Permission Callback</h4>
                    <code>${escapeHtml(route.permission_callback_info)}</code>
                </div>
            `;
    }

    // All capabilities
    if (route.capabilities && route.capabilities.length > 0) {
      html += `
                <div class="detail-section">
                    <h4>Required Capabilities</h4>
                    <ul class="capability-list">
            `;
      route.capabilities.forEach(function (cap) {
        html += `<li><code>${escapeHtml(cap)}</code></li>`;
      });
      html += "</ul></div>";
    }

    // Custom roles
    if (route.custom_roles && route.custom_roles.length > 0) {
      html += `
                <div class="detail-section">
                    <h4>Custom Roles with Access</h4>
                    <ul class="role-list">
            `;
      route.custom_roles.forEach(function (role) {
        html += `<li><code>${escapeHtml(role)}</code></li>`;
      });
      html += "</ul></div>";
    }

    return html;
  }

  /**
   * Bind route row events
   */
  function bindRouteRowEvents() {
    $(".toggle-details")
      .off("click")
      .on("click", function () {
        const $button = $(this);
        const $details = $button.siblings(".route-details");
        const isExpanded = $button.attr("aria-expanded") === "true";

        if (isExpanded) {
          $details.slideUp();
          $button
            .attr("aria-expanded", "false")
            .find(".dashicons")
            .removeClass("dashicons-arrow-up-alt2")
            .addClass("dashicons-arrow-down-alt2");
        } else {
          $details.slideDown();
          $button
            .attr("aria-expanded", "true")
            .find(".dashicons")
            .removeClass("dashicons-arrow-down-alt2")
            .addClass("dashicons-arrow-up-alt2");
        }
      });

    // Double-click to expand/collapse
    $(".route-row")
      .off("dblclick")
      .on("dblclick", function () {
        $(this).find(".toggle-details").click();
      });
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

    // Remove existing errors
    $(".sectoolbox-error").remove();

    // Add new error
    $(".sectoolbox-main").prepend(errorHtml);

    // Make dismissible
    $(".sectoolbox-error .notice-dismiss").on("click", function () {
      $(this).closest(".sectoolbox-error").fadeOut();
    });

    // Auto-dismiss after 8 seconds
    setTimeout(function () {
      $(".sectoolbox-error").fadeOut();
    }, 8000);
  }

  /**
   * Handle keyboard shortcuts
   */
  function handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + Enter to analyze
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
      e.preventDefault();
      if (!$("#inspect-routes-btn").prop("disabled")) {
        inspectRoutes();
      }
    }

    // Escape to clear filters
    if (e.keyCode === 27 && $("#results-container").is(":visible")) {
      clearAllFilters();
    }
  }

  /**
   * Debounce function for performance
   */
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = function () {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  /**
   * Escape HTML for safe output
   */
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // Export functions for testing (if needed)
  window.SecToolbox = {
    loadPlugins,
    inspectRoutes
  };
})(jQuery);
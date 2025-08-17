# Copilot Instructions for SecToolbox

## Project Overview
SecToolbox is a WordPress plugin for security analysis, focusing on REST API route inspection, permission mapping, and risk assessment. It is intended for defensive security use only.

## Architecture & Key Files
- `sectoolbox.php`: Main plugin entry, loader, activation/deactivation hooks.
- `includes/class-sectoolbox.php`: Central orchestrator, singleton pattern.
- `includes/class-route-analyzer.php`: REST API route analysis logic.
- `includes/class-admin-page.php`: Admin UI logic.
- `includes/class-ajax-handler.php`: AJAX endpoints for admin JS.
- `admin/css/admin.css`, `admin/js/admin.js`: Admin UI assets.

## Patterns & Conventions
- **Singleton pattern** for main classes (SecToolbox, Loader).
- **Dependency injection**: Route analyzer injected into admin and AJAX classes.
- **Strict typing**: All PHP uses `declare(strict_types=1)` and PHP 8.0+ features.
- **PSR-12** and **WordPress Coding Standards**.
- **Class files**: `includes/class-*.php` naming.
- **Admin assets**: `admin/css/`, `admin/js/`.
- **No automated tests**: Manual testing in a WordPress environment only.

## Security & Workflow
- All user input must be sanitized; all output escaped.
- Use WordPress nonces for admin actions.
- Always check user capabilities before sensitive actions.
- Never expose or log sensitive data.
- Refuse any request for offensive/malicious code.

## Manual Testing
- Activate plugin in WordPress.
- Use SecToolbox admin page to test route analysis and permission checks.
- Test with various plugins to verify route detection and risk categorization.

## Integration Points
- Integrates with WordPress via hooks and AJAX endpoints.
- No external PHP dependencies; relies on WordPress core APIs.

## Examples
- See `includes/class-route-analyzer.php` for REST route analysis logic.
- See `includes/class-admin-page.php` for admin UI and capability checks.

---
For more details, see `CLAUDE.md` and `README.md`.

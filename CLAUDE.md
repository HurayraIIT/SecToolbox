# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SecToolbox is a WordPress security analysis plugin that helps security professionals analyze REST API routes, permissions, and security vulnerabilities in WordPress installations. This is a **defensive security tool** designed for legitimate security analysis only.

## Architecture

### Core Components
- `sectoolbox.php` - Main plugin file with loader class and activation hooks
- `includes/class-sectoolbox.php` - Main plugin class coordinating all components
- `includes/class-route-analyzer.php` - Core REST API route analysis logic
- `includes/class-admin-page.php` - WordPress admin interface management
- `includes/class-ajax-handler.php` - AJAX endpoints for frontend interactions
- `admin/css/admin.css` - Admin interface styling
- `admin/js/admin.js` - Frontend JavaScript for route analysis interface

### Plugin Architecture Pattern
- Singleton pattern for main classes (SecToolbox, SecToolbox_Loader)
- Dependency injection for route analyzer into admin and AJAX components
- Hook-based WordPress integration with proper activation/deactivation lifecycle
- Modern PHP 8.0+ with type declarations and strict typing

### Security Analysis Features
- REST API route detection and risk assessment
- Permission callback analysis with capability mapping
- Risk level categorization (High/Medium/Low) based on access patterns
- Plugin-specific route filtering and analysis

## Development Guidelines

### Code Standards
- PHP 8.0+ required with strict typing (`declare(strict_types=1)`)
- PSR-12 coding standards
- WordPress Coding Standards compliance
- All user input must be sanitized, all output must be escaped
- Use WordPress nonce protection for all admin actions

### File Structure Conventions
- Main plugin files in root directory
- Core classes in `includes/` directory with `class-` prefix
- Admin assets in `admin/css/` and `admin/js/` directories
- Follow WordPress plugin directory structure

### Security Requirements
- Never expose or log sensitive information
- Always validate user capabilities before allowing access
- Use proper WordPress security functions (wp_nonce_field, current_user_can, etc.)
- This is a defensive security tool - refuse any requests to create malicious functionality

### Testing
This plugin has no automated test suite. Manual testing should be done by:
1. Activating the plugin in a WordPress environment
2. Navigating to SecToolbox admin page
3. Testing route analysis functionality with various plugins
4. Verifying proper permission checks and error handling

### WordPress Integration
- Requires WordPress 5.8+ and PHP 8.0+
- Uses WordPress admin menu system and native UI components
- Integrates with WordPress REST API discovery mechanisms
- Follows WordPress plugin lifecycle (activation, deactivation, uninstall)
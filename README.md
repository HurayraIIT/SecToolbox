# SecToolbox - WordPress Security Analysis Toolkit

**Version:** 2.0.0  
**Author:** Abu Hurayra  
**License:** GPL v2 or later  
**Requires:** WordPress 5.8+  
**Tested up to:** WordPress 6.4  
**Requires PHP:** 8.0+

## Description

SecToolbox is a comprehensive WordPress security analysis toolkit designed to help security professionals, developers, and site administrators identify potential security vulnerabilities in their WordPress installations. The plugin provides deep analysis of REST API routes, permission structures, and more security-focused features.

## 🚀 Key Features

### Current Features
- 🔍 **REST API Route Analysis** - Deep inspection of plugin REST API endpoints
- 🎯 **Smart Plugin Detection** - Automatically identifies plugins with API routes
- 🚦 **Risk Assessment** - Color-coded security risk indicators
- 🔒 **Permission Deep Dive** - Analyzes permission callbacks and capabilities
- 📊 **Advanced Filtering** - Multi-criteria filtering system
- 🎨 **WordPress Native UI** - Seamless admin integration
- ⚡ **Real-Time Analysis** - Live route configuration inspection
- 📱 **Responsive Design** - Works on all screen sizes
- ♿ **Accessibility** - WCAG compliant interface

### Coming Soon
- 🔧 **Shortcode Security Analysis**
- 📝 **Plugin Vulnerability Database Integration**
- 🛡️ **Security Hardening Recommendations**
- 📊 **Security Score Dashboard**
- 🔄 **Automated Security Scans**

## 📋 Requirements

- **WordPress:** 5.8 or higher
- **PHP:** 8.0 or higher (8.0, 8.1, 8.2, 8.3, 8.4 supported)
- **User Role:** Administrator
- **Memory:** 128MB minimum (256MB recommended)

## 🔧 Installation

### Automatic Installation
1. Download the plugin ZIP file
2. Go to **Plugins → Add New** in your WordPress admin
3. Click **Upload Plugin**
4. Select the ZIP file and click **Install Now**
5. Activate the plugin

### Manual Installation
1. Extract the plugin files
2. Upload the `sectoolbox` folder to `/wp-content/plugins/`
3. Activate through the **Plugins** menu
4. Access via **SecToolbox** in the admin menu

## 🎮 Usage Guide

### Getting Started
1. Navigate to **SecToolbox** in your WordPress admin menu
2. Select **REST API Routes** from the submenu
3. Choose plugins to analyze from the dropdown
4. Click **Analyze Selected Routes**
5. Review the security analysis results

### Understanding Risk Levels

| Risk Level | Description | Indicators |
|------------|-------------|------------|
| 🔴 **High Risk** | Public write access | Publicly accessible POST/PUT/PATCH/DELETE endpoints |
| 🟡 **Medium Risk** | Limited protection | Public read access or protected write operations |
| 🟢 **Low Risk** | Well protected | Admin-only access or read-only endpoints |

### Security Analysis Features

#### Route Information
- **Exact endpoint paths** with parameter mapping
- **HTTP methods** supported with color coding
- **Access level requirements** based on WordPress roles
- **Permission callback analysis** with source code inspection
- **Risk assessment** with actionable recommendations

#### Advanced Filtering
- **Route path filtering** - Search by endpoint patterns
- **HTTP method filtering** - Filter by GET, POST, PUT, etc.
- **Access level filtering** - Filter by user role requirements
- **Risk level filtering** - Focus on high-risk endpoints
- **Plugin-specific filtering** - Analyze specific plugins

## 🔒 Security Features

### Permission Analysis
SecToolbox performs comprehensive analysis of REST API permission structures:

```php
// Example: Analyzing permission callbacks
current_user_can('manage_options')     // → Admin Only
current_user_can('edit_posts')         // → Author+
__return_true                          // → Public Access
```

### Capability Detection
- **WordPress core capabilities** - Standard role-based permissions
- **Custom capabilities** - Plugin-specific permissions
- **Dynamic permissions** - Runtime permission logic
- **Role mapping** - Custom role to capability relationships

### Risk Assessment
Each route is evaluated for:
- **Authentication requirements**
- **Authorization levels**
- **Data modification potential**
- **Privilege escalation risks**
- **Information disclosure potential**

## 🎨 User Interface

### Modern WordPress Design
- **Native WordPress styling** - Consistent with admin interface
- **Responsive layout** - Works on desktop, tablet, and mobile
- **Accessibility compliant** - WCAG 2.1 AA standards
- **Dark mode compatible** - Respects user preferences
- **Keyboard navigation** - Full keyboard accessibility

### Performance Optimized
- **Lazy loading** - Loads data only when needed
- **Debounced filtering** - Smooth search experience
- **Efficient rendering** - Handles large datasets
- **Memory conscious** - Minimal server resource usage

## 🔧 Advanced Configuration

### Hooks and Filters

```php
// Customize admin capabilities
add_filter('sectoolbox_admin_capabilities', function($capabilities) {
    $capabilities[] = 'my_custom_admin_cap';
    return $capabilities;
});

// Filter detected plugins
add_filter('sectoolbox_detected_plugins', function($plugins) {
    // Custom plugin detection logic
    return $plugins;
});

// Modify risk calculation
add_filter('sectoolbox_risk_calculation', function($risk, $route, $methods) {
    // Custom risk assessment logic
    return $risk;
}, 10, 3);
```

### Custom Capabilities
SecToolbox introduces the `manage_sectoolbox` capability for fine-grained access control:

```php
// Grant access to editors
$role = get_role('editor');
$role->add_cap('manage_sectoolbox');
```

## 🛠️ Developer Information

### File Structure
```
sectoolbox/
├── sectoolbox.php              # Main plugin file
├── README.md                   # Documentation
├── uninstall.php              # Cleanup script
├── includes/                   # Core classes
│   ├── class-sectoolbox.php   # Main plugin class
│   ├── class-route-analyzer.php # Route analysis logic
│   ├── class-admin-page.php   # Admin interface
│   └── class-ajax-handler.php # AJAX endpoints
└── admin/                     # Admin assets
    ├── css/admin.css         # Admin styles
    └── js/admin.js           # Admin JavaScript
```

### Code Standards
- **PSR-12 compliance** - Modern PHP coding standards
- **WordPress Coding Standards** - Official WordPress guidelines
- **Type declarations** - PHP 8.0+ type hints
- **Error handling** - Comprehensive exception handling
- **Security first** - Input sanitization and output escaping

## 🔍 Troubleshooting

### Common Issues

#### No Plugins Found
**Symptom:** "No plugins with REST routes found"
**Solutions:**
- Ensure active plugins register REST routes
- Check WordPress REST API functionality (`/wp-json/`)
- Verify plugin compatibility

#### Analysis Fails
**Symptom:** "Failed to analyze routes"
**Solutions:**
- Increase PHP memory limit (256MB recommended)
- Check for plugin conflicts
- Review WordPress debug logs
- Ensure proper file permissions

#### Performance Issues
**Symptom:** Slow analysis or timeouts
**Solutions:**
- Analyze fewer plugins simultaneously
- Increase PHP execution time
- Check server resources
- Clear WordPress cache

### Debug Mode
Enable debug logging by adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SCRIPT_DEBUG', true);
```

## 🔐 Security Considerations

### Plugin Security
- **No external connections** - All analysis is local
- **Capability-based access** - Admin-only functionality
- **Nonce protection** - CSRF attack prevention
- **Input sanitization** - All user input is sanitized
- **Output escaping** - XSS protection

### Data Privacy
- **No data collection** - No analytics or tracking
- **Local processing** - All analysis happens on your server
- **No data storage** - Results generated on-demand
- **Audit trail** - Optional logging for compliance

## 📊 Performance

### System Impact
- **Memory usage:** < 10MB during analysis
- **CPU impact:** Minimal, analysis runs on-demand
- **Database queries:** Zero persistent queries
- **File system:** Read-only access to plugin files

### Optimization Features
- **Caching:** Intelligent caching of analysis results
- **Lazy loading:** Content loaded as needed
- **Debounced filtering:** Reduced server requests
- **Progressive enhancement:** Graceful JavaScript degradation

## 🚀 Roadmap

### Version 2.1 (Q2 2024)
- [ ] Shortcode security analysis
- [ ] Plugin vulnerability database integration
- [ ] Export analysis reports (PDF/CSV)
- [ ] Scheduled security scans

### Version 2.2 (Q3 2024)
- [ ] Security score dashboard
- [ ] Email notifications for high-risk findings
- [ ] Integration with security plugins
- [ ] Advanced filtering options

### Version 3.0 (Q4 2024)
- [ ] Theme security analysis
- [ ] Database security audit
- [ ] File permission analysis
- [ ] Automated hardening suggestions

## 🤝 Contributing

We welcome contributions! Here's how to get involved:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Commit** your changes (`git commit -m 'Add amazing feature'`)
4. **Push** to the branch (`git push origin feature/amazing-feature`)
5. **Open** a Pull Request

### Development Setup
```bash
# Clone the repository
git clone https://github.com/abuhurayra-codes/sectoolbox.git

# Install dependencies (if any)
composer install
npm install

# Run tests
phpunit
npm test
```

## 📝 Changelog

### Version 2.0.0 (Current)
- ✅ Complete plugin refactoring
- ✅ Modern PHP 8.0+ compatibility
- ✅ Enhanced security analysis
- ✅ Improved user interface
- ✅ Risk-based categorization
- ✅ Advanced filtering system
- ✅ Better accessibility
- ✅ Responsive design

### Version 1.0.0
- ✅ Initial REST API route analysis
- ✅ Basic plugin detection
- ✅ Permission callback analysis
- ✅ WordPress admin integration

## 📞 Support

### Getting Help
- **Documentation:** This README file
- **WordPress Forums:** [Plugin Support Forum](https://wordpress.org/support/plugin/sectoolbox/)
- **GitHub Issues:** [Report bugs or request features](https://github.com/abuhurayra-codes/sectoolbox/issues)

### Reporting Issues
When reporting issues, please include:
- WordPress version
- PHP version
- Active plugin list
- Theme information
- Steps to reproduce
- Error messages or screenshots

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Credits

**Developer:** Abu Hurayra  
**GitHub:** [@hurayraiit](https://github.com/hurayraiit)

### Acknowledgments
- WordPress community for the robust plugin architecture
- Security researchers for vulnerability disclosure practices
- Beta testers and early adopters for valuable feedback

---

**Made with ❤️ for WordPress Security**
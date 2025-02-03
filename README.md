# Debug log admin viewer

A WordPress plugin that provides debugging utilities and a powerful debug log admin viewer.

## Description

Debug log admin viewer is a WordPress plugin designed to make debugging easier by providing a user-friendly interface to manage WordPress debug settings and view debug logs. It allows you to modify debug-related constants in wp-config.php directly from the WordPress admin panel and provides an advanced log viewer with filtering and search capabilities.

## Features

- Enable/disable WordPress debug mode (WP_DEBUG)
- Toggle debug logging (WP_DEBUG_LOG)
- Control debug display (WP_DEBUG_DISPLAY)
- Advanced debug log admin viewer with filtering and search
- Copy log entries to clipboard
- Clear log file with one click
- Automatic backup of wp-config.php before making changes
- Backup rotation (keeps last 5 backups)

## Installation

1. Upload the `debug-log-admin-viewer` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Debug Log Admin Viewer to configure debug settings and view logs

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Security

The plugin includes several security measures:
- Automatic backup creation before any wp-config.php modifications
- Backup files are protected with .htaccess rules
- Proper file permissions management
- Nonce verification for all actions
- Capability checks for administrative functions

## Usage

1. Navigate to Settings > Debug Log Admin Viewer in the WordPress admin panel
2. Toggle the debug settings as needed
3. View debug logs directly in the admin interface when WP_DEBUG_LOG is enabled
4. Use filters and search to find specific log entries
5. Copy log entries to clipboard with one click
6. Use the "Clear Log File" button to reset the debug.log file

## Changelog

### 1.0.0
- Initial release
- WordPress debug settings management
- Advanced debug log admin viewer with filtering and search
- Automatic wp-config.php backup system

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Aida Martinez. Contact: aida@thewebkitchen.co.uk

## Support

For support, please [create an issue](https://github.com/aidamartinez/debug-log-admin-viewer/issues) on GitHub.
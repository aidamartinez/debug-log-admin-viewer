# TWK Utils

A WordPress plugin that provides debugging utilities and configuration management.

## Description

TWK Utils is a WordPress plugin designed to make debugging easier by providing a user-friendly interface to manage WordPress debug settings. It allows you to modify debug-related constants in wp-config.php directly from the WordPress admin panel.

## Features

- Enable/disable WordPress debug mode (WP_DEBUG)
- Toggle debug logging (WP_DEBUG_LOG)
- Control debug display (WP_DEBUG_DISPLAY)
- View and clear debug logs directly from the admin interface
- Automatic backup of wp-config.php before making changes
- Backup rotation (keeps last 5 backups)

## Installation

1. Upload the `twk-utils` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > TWK Utils to configure debug settings

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

1. Navigate to Settings > TWK Utils in the WordPress admin panel
2. Toggle the debug settings as needed
3. View debug logs directly in the admin interface when WP_DEBUG_LOG is enabled
4. Use the "Clear Log File" button to reset the debug.log file

## Changelog

### 1.0.0
- Initial release
- WordPress debug settings management
- Debug log viewer
- Automatic wp-config.php backup system

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by TWK Media.

## Support

For support, please [create an issue](https://github.com/aidamartinez/twk-debugger/issues) on GitHub. 
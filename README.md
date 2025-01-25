# TWK Debugger

A WordPress plugin that provides an easy-to-use interface for managing WordPress debug settings and site visibility notifications.

## Features

### Debug Settings Management
- Enable/Disable WP_DEBUG
- Enable/Disable WP_DEBUG_LOG
- Enable/Disable WP_DEBUG_DISPLAY
- View and clear debug.log file directly from the admin panel
- Automatic backup of wp-config.php before making changes

### Site Visibility Notifications
- Option to enable notifications when search engines are discouraged from indexing the site
- Visual indicator in the admin bar when search engines are blocked

## Installation

1. Download the plugin
2. Upload to your WordPress site
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > TWK Debugger to configure

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Write permissions for wp-config.php file

## Usage

### Debug Settings
1. Navigate to Settings > TWK Debugger
2. Select the DEBUG tab
3. Toggle the desired debug options
4. Save changes
5. View debug log (if WP_DEBUG_LOG is enabled)

### Search Engine Visibility Notification
1. Navigate to Settings > TWK Debugger
2. Select the Miscellaneous tab
3. Enable "Search Engine Visibility Notification"
4. When search engines are discouraged from indexing your site (Settings > Reading), a notification will appear in the admin bar

## Security

The plugin includes several security features:
- Automatic backup creation before modifying wp-config.php
- Secure storage of backup files
- Proper file permissions checks
- WordPress nonce verification
- Capability checks for administrative functions

## Support

For support, please contact: aida@thewebkitchen.co.uk

## License

This project is licensed under the GPL v2 or later

## Credits

Developed by TWK Media
Website: thewebkitchen.co.uk

Based on the WordPress Plugin Boilerplate by Devin Vinson 
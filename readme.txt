=== Debug log admin viewer ===
Contributors: twkmedia
Tags: debug, log, viewer
Requires at least: 5.0
Tested up to: 6.7.2
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Debug log admin viewer is a WordPress plugin designed to make debugging easier by providing a user-friendly interface to manage WordPress debug settings and view debug logs. It allows you to modify debug-related constants in wp-config.php directly from the WordPress admin panel and provides an advanced log viewer with filtering and search capabilities.

== Short Description ==
A WordPress plugin that provides debugging utilities and a powerful debug log admin viewer.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/debug-log-admin-viewer` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

== Security ==
The plugin includes several security measures:
- Automatic backup creation before any wp-config.php modifications.
- Backup files are protected with .htaccess rules.
- Proper file permissions management.
- Nonce verification for all actions.
- Capability checks for administrative functions.

== Usage ==
1. Navigate to Settings > Debug Log Admin Viewer in the WordPress admin panel.
2. Toggle the debug settings as needed.
3. View debug logs directly in the admin interface when WP_DEBUG_LOG is enabled.
4. Use filters and search to find specific log entries.
5. Copy log entries to clipboard with one click.
6. Use the "Clear Log File" button to reset the debug.log file.


== Screenshots ==

1. The admin panel.
2. The log viewer.


== Changelog ==

= 1.0.0 =
- Initial release.
- WordPress debug settings management.
- Advanced debug log admin viewer with filtering and search.
- Automatic wp-config.php backup system.

== Frequently Asked Questions ==
Q: How do I access the debug log viewer?
A: Navigate to Settings > Debug Log Admin Viewer in the WordPress admin panel.


== Support ==
For support, please [create an issue](https://github.com/aidamartinez/debug-log-admin-viewer/issues) on GitHub.

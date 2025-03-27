=== Debug log admin viewer ===
Contributors: twkmedia
Tags: debug, log, viewer
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin providing a user-friendly interface to manage debug settings and view debug logs with advanced filtering capabilities.

== ⚠️ Important Disclaimer ==

This plugin modifies your wp-config.php file directly. While the plugin automatically creates backups before any modifications (keeping the 5 most recent backups), it is strongly recommended to:

1. Create a manual backup of your wp-config.php file before installing and using this plugin
2. Note the location of automatic backups (shown in the plugin settings page)
3. Test the plugin in a staging environment first

The plugin may not work if your wp-config.php has unusual formatting or custom modifications. In case of any issues, you can restore your wp-config.php from the automatic backups located in wp-content/uploads/debug-log-admin-viewer/.

== Description ==
Debug log admin viewer is a WordPress plugin designed to make debugging easier by providing a user-friendly interface to manage WordPress debug settings and view debug logs. It allows you to modify debug-related constants in wp-config.php directly from the WordPress admin panel and provides an advanced log viewer with filtering and search capabilities.

Developed by [TWK Media](https://www.thewebkitchen.co.uk/).

== Short Description ==
A WordPress plugin providing a user-friendly interface to manage debug settings and view debug logs with advanced filtering capabilities.

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


== Frequently Asked Questions ==

Q: Where are the backup files stored?

A: Backup files are automatically stored in the `wp-content/uploads/debug-log-admin-viewer/` directory. The plugin keeps the 5 most recent backups and protects them with proper file permissions and .htaccess rules. You can find the exact path in the plugin settings page, where you can also copy it with a single click.

Q: How do I access the debug log viewer?

A: Navigate to Settings > Debug Log Admin Viewer in the WordPress admin panel.

Q: Who is behind this plugin?

A: This plugin is developed and maintained by [TWK Media](https://www.thewebkitchen.co.uk/).


== Support ==
For support, please [create an issue](https://github.com/aidamartinez/debug-log-admin-viewer/issues) on GitHub.

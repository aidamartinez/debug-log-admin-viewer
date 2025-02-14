<?php
/**
 * Debug log admin viewer
 *
 * @link              https://github.com/aidamartinez/debug-log-admin-viewer
 * @since             1.0.0
 * @package           Debug_Log_Admin_Viewer
 *
 * @wordpress-plugin
 * Plugin Name:       Debug log admin viewer
 * Plugin URI:        https://github.com/aidamartinez/debug-log-admin-viewer
 * Description:       A powerful debug log admin viewer with filtering and search capabilities, plus debug settings management.
 * Version:           1.0.0
 * Author:            TWK Media
 * Author URI:        https://www.thewebkitchen.co.uk/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       debug-log-admin-viewer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'DEBUG_LOG_ADMIN_VIEWER_VERSION', '1.0.0' );
define( 'DEBUG_LOG_ADMIN_VIEWER_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The core plugin class
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-debug-log-admin-viewer.php';

/**
 * Begins execution of the plugin.
 */
function run_debug_log_admin_viewer() {
	$plugin = new Debug_Log_Admin_Viewer();
	$plugin->run();
}
run_debug_log_admin_viewer();
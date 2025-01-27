<?php
/**
 * The plugin bootstrap file
 *
 * @link              thewebkitchen.co.uk
 * @since             1.0.0
 * @package           Twk_Utils
 *
 * @wordpress-plugin
 * Plugin Name:       TWK Utils
 * Plugin URI:        thewebkitchen.co.uk
 * Description:       A utils plugin for WP. A debugger in your CMS.
 * Version:           1.0.0
 * Author:            TWK Media
 * Author URI:        thewebkitchen.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       twk-utils
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('TWK_UTILS_VERSION', '1.0.0');
define('TWK_UTILS_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . 'includes/class-twk-utils.php';

/**
 * Begins execution of the plugin.
 */
function run_twk_utils() {
    $plugin = new Twk_Utils();
    $plugin->run();
}
run_twk_utils(); 
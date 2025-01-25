<?php
/**
 * The plugin bootstrap file
 *
 * @link              thewebkitchen.co.uk
 * @since             1.0.0
 * @package           Twk_Debugger
 *
 * @wordpress-plugin
 * Plugin Name:       TWK Debugger
 * Plugin URI:        thewebkitchen.co.uk
 * Description:       A debugger in your CMS
 * Version:           1.0.0
 * Author:            TWK Media
 * Author URI:        thewebkitchen.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       twk-debugger
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('TWK_DEBUGGER_VERSION', '1.0.0');
define('TWK_DEBUGGER_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * The core plugin class
 */
require plugin_dir_path(__FILE__) . 'includes/class-twk-debugger.php';

/**
 * Begins execution of the plugin.
 */
function run_twk_debugger() {
    $plugin = new Twk_Debugger();
    $plugin->run();
}
run_twk_debugger(); 
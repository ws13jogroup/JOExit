<?php
/**
 * Plugin Name: JOb Exit Plugin
 * Plugin URI: https://example.com/jo-exit-plugin
 * Description: A Tinder-style app for employees with swipe functionality
 * Version:           1.1.2
 * Author:            Antigravity
 * Author URI:       https://example.com
 * License:           GPL2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       job-exit-plugin
 * Domain Path:        /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at 1.0.4 and use SemVer - https://semver.org
 */
define('JO_EXIT_PLUGIN_VERSION', '1.1.2');
define('JO_EXIT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('JO_EXIT_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin Update Checker Integration
 */
require_once JO_EXIT_PLUGIN_PATH . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/ws13jogroup/JOExit/',
    __FILE__,
    'job-exit-plugin'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

// Includi un Access Token se la repository è privata (lascia stringa vuota se pubblica)
// $myUpdateChecker->setAuthentication('');

/**
 * The code that runs during plugin activation.
 */
function activate_jo_exit_plugin()
{
    require_once JO_EXIT_PLUGIN_PATH . 'includes/class-jo-exit-activator.php';
    Jo_Exit_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_jo_exit_plugin()
{
    require_once JO_EXIT_PLUGIN_PATH . 'includes/class-jo-exit-deactivator.php';
    Jo_Exit_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_jo_exit_plugin');
register_deactivation_hook(__FILE__, 'deactivate_jo_exit_plugin');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require JO_EXIT_PLUGIN_PATH . 'includes/class-jo-exit.php';

/**
 * Begins execution of the plugin.
 */
function run_jo_exit_plugin()
{
    $plugin = new Jo_Exit();
    $plugin->run();
}
run_jo_exit_plugin();

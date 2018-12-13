<?php

/**
 * The plugin bootstrap file
 *
 * @link              stellarcowboy.com
 * @since             1.0.0
 * @package           WooCommerce_Api_Email
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce API to Email Plugin
 * Plugin URI:        stellarcowboy.com
 * Description:       Allows customization of WooCommerce confirmation emails and purchase success page based on existence of a particular product ID and an API response
 * Version:           1.0.0
 * Author:            Kenneth White
 * Author URI:        stellarcowboy.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-api-email
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-api-email.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_WooCommerce_Api_Email() {

	$plugin = new WooCommerce_Api_Email();
	$plugin->run();

}
run_WooCommerce_Api_Email();

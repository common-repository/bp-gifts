<?php
/**
 * Plugin Name: SuitePlugins - Gifts for BuddyPress
 * Plugin URI:  http://suiteplugins.com
 * Description: Enable users to share gifts with other users in BuddyPress
 * Author:      SuitePlugins
 * Author URI:  http://suiteplugins.com
 * Version:     1.0.0
 * Text Domain: bp-gifts
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once(plugin_dir_path( __FILE__ ) . 'bp-gifts-loader.php');
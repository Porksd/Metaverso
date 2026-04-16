<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://pixiefy.com/plugins/wp-preloader-unlimited/
 * @since      1.0.0
 *
 * @package    WP_Preloader_unlimited
 * @subpackage WP_Preloader_unlimited/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WP_Preloader_unlimited
 * @subpackage WP_Preloader_unlimited/includes
 * @author     Your Name <email@example.com>
 */
class WP_Preloader_unlimited_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_option( 'wppu_display' );
	}

}

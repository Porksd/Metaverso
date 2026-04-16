<?php
/**
 * Plugin Data Module
 * This class is responsible for plugin data.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes;

use app\wisdmlabs\edwiserBridgePro\admin as admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Eb_Pro_Get_Plugin_Data' ) ) {
	/**
	 * Edwiser Bridge Pro class
	 */
	class Eb_Pro_Get_Plugin_Data {
		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    3.0.0
		 * @access   protected
		 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    3.0.0
		 * @access   protected
		 * @var      string    $version    The current version of the plugin.
		 */
		protected $version;

		/**
		 * Plugin license data.
		 *
		 * @since    3.0.0
		 * @access   protected
		 * @var      array    $license_data    The array containing plugin license data.
		 */
		protected static $license_data;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 * @param    string $loader The loader that's responsible for maintaining and registering all hooks that power the plugin.
		 */
		public function __construct( $loader ) {
			global $eb_pro_plugin_data;
			$this->plugin_name = $eb_pro_plugin_data['plugin_slug'];
			$this->version     = $eb_pro_plugin_data['plugin_version'];
		}

		/**
		 * Function to get license info from DB.
		 *
		 * @param array $plugin_data Array of the plugin information.
		 * @param bool  $cache cache value info.
		 */
		public static function get_data_from_db( $plugin_data, $cache = true ) {

			if ( null !== self::$license_data && true === $cache ) {
				return self::$license_data;
			}

			$plugin_name = $plugin_data['plugin_name'];
			$plugin_slug = $plugin_data['plugin_slug'];
			$store_url   = $plugin_data['store_url'];

			$license_transient = get_transient( 'wdm_' . $plugin_slug . '_license_trans' );

			if ( ! $license_transient ) {
				$license_key = trim( get_option( 'edd_' . $plugin_slug . '_license_key' ) );

				if ( $license_key ) {
					$api_params = array(
						'edd_action'      => 'check_license',
						'license'         => $license_key,
						'item_name'       => rawurlencode( $plugin_name ),
						'current_version' => $plugin_data['plugin_version'],
					);

					$response = wp_remote_get(
						add_query_arg( $api_params, $store_url ),
						array(
							'timeout'   => 15,
							'sslverify' => true,
							'blocking'  => true,
						)
					);

					if ( is_wp_error( $response ) ) {
						return false;
					}

					$license_data = json_decode( wp_remote_retrieve_body( $response ) );

					$valid_resp_code = array( '200', '301' );

					$curr_resp_code = wp_remote_retrieve_response_code( $response );

					if ( null === $license_data || ! in_array( $curr_resp_code, $valid_resp_code, true ) ) {
						// if server does not respond, read current license information.
						$license_status = get_option( 'edd_' . $plugin_slug . '_license_status', '' );
						if ( empty( $license_data ) ) {
							set_transient( 'wdm_' . $plugin_slug . '_license_trans', 'server_did_not_respond', 60 * 60 * 24 );
						}
					} else {
						include_once plugin_dir_path( __FILE__ ) . 'class-edwiser-custom-field-add-plugin-data-in-db.php';
						$license_status = Edwiser_Custom_Field_Add_Plugin_Data_In_Db::update_status( $license_data, $plugin_slug );
					}

					$active_site = self::get_site_list( $plugin_slug );

					self::set_response_data( $license_status, $active_site, $plugin_slug, true );

					return self::$license_data;
				}
			} else {
				$license_status = get_option( 'edd_' . $plugin_slug . '_license_status' );
				$active_site    = self::get_site_list( $plugin_slug );

				self::set_response_data( $license_status, $active_site, $plugin_slug );
				return self::$license_data;
			}
		}

		/**
		 * This function is used to get list of sites where license key is already acvtivated.
		 *
		 * @param string $plugin_slug current plugin's slug.
		 */
		public static function get_site_list( $plugin_slug ) {
			$sites    = get_option( 'eb_' . $plugin_slug . '_license_key_sites' );
			$max      = get_option( 'eb_' . $plugin_slug . '_license_max_site' );
			$cur_site = get_site_url();
			$cur_site = preg_replace( '#^https?://#', '', $cur_site );

			$site_count  = 0;
			$active_site = '';

			if ( ! empty( $sites ) || '' !== $sites ) {
				foreach ( $sites as $key ) {
					foreach ( $key as $value ) {
						$value = rtrim( $value, '/' );

						if ( 0 !== strcasecmp( $value, $cur_site ) ) {
							$active_site .= '<li>' . $value . '</li>';
							++$site_count;
						}
					}
				}
			}

			if ( $site_count >= $max ) {
				return $active_site;
			} else {
				return '';
			}
		}


		/**
		 * Function to add the responce data into the DB.
		 *
		 * @param string $license_status License status.
		 * @param array  $active_site List of the active site.
		 * @param string $plugin_slug  Plugin slug.
		 * @param bool   $set_trans Should set transient or not.
		 */
		public static function set_response_data( $license_status, $active_site, $plugin_slug, $set_trans = false ) {

			if ( 'valid' === $license_status ) {
				self::$license_data = 'available';
			} elseif ( 'expired' === $license_status && ( ! empty( $active_site ) || '' !== $active_site ) ) {
				self::$license_data = 'unavailable';
			} elseif ( 'expired' === $license_status ) {
				self::$license_data = 'available';
			} else {
				self::$license_data = 'unavailable';
			}

			if ( $set_trans ) {
				switch ( $license_status ) {
					case 'invalid':
					case 'no_activations_left':
						$time = 0; // Do not repeat.
						break;
					case 'failed':
						$time = 86400; // Repeat everyday.
						break;
					case 'expired':
						$time = 86400 * 2; // Repeat every 2 days.
						break;
					case 'disabled':
						$time = 86400 * 4; // Repeat every 4 days.
						break;
					case 'valid':
						$time = 86400 * 7; // Repeat every 7 days.
						break;
					default:
						$time = 86400 * 7; // Fallback. Repeat every 7 days.
						break;
				}
				set_transient( 'wdm_' . $plugin_slug . '_license_trans', $license_status, $time );
			}
		}
	}
}

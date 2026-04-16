<?php
/**
 * Fired during plugin activation
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Edwiser Bridge Pro Activator class
 */
class Eb_Pro_Activator {

	/**
	 * Network wide tells if the plugin was activated for the entire network or just for single site.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      boolean $network_wide
	 */
	private static $network_wide = false;
	/**
	 * Plugin activation function.
	 */
	public static function activate( $network_wide = false ) {

		self::$network_wide = $network_wide;

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				// Get all blog ids.
				$allSites = wp_get_sites();
                foreach ($allSites as $blog) {
					switch_to_blog( $blog['blog_id'] );
					self::activate_eb_pro();
				}
				restore_current_blog();
			} else {
				self::activate_eb_pro();
			}
		} else {
			self::activate_eb_pro();
		}
	}

	/**
	 * Activate Edwiser Bridge Pro.
	 */
	private static function activate_eb_pro() {
		// eb pro migration.
		self::eb_pro_migration();

		// create database tables.
		self::create_eb_pro_db_tables();

		// create pages.
		self::create_eb_pro_pages();

		// add default email template options.
		self::add_default_email_tmpl_options();

		self::sso_data_migration();

		// woo int.
		self::create_moodle_db_tables();

		self::custom_fields_data_migration();

		// modify table schema.
		self::modify_table_schema();

		// add nre roles
		add_role(
			'non_editing_teacher',
			'Non editing Teacher',
			array(
				'read'    => true,
				'level_0' => true,
			)
		);
	}

	/**
	 * Edwiser bridge pro migration (for users who are migrating from multiple pro plugin to single pro plugin).
	 */
	private static function eb_pro_migration() {
		$is_migrated = get_option( 'eb_pro_legacy_plugin_migrated' );

		if ( function_exists( '\app\wisdmlabs\edwiserBridge\eb_is_legacy_pro' ) ) {
			$is_legacy_pro = \app\wisdmlabs\edwiserBridge\eb_is_legacy_pro( true );
		} else {
			$is_legacy_pro = false;
		}
		if ( ! $is_migrated && $is_legacy_pro ) {
			// deactivate legacy plugins.
			$modules_data = get_option( 'eb_pro_modules_data' );
			if ( ! is_array( $modules_data ) ) {
				$modules_data = array();
			}
			$extensions = array(
				'woo_integration' => 'woocommerce-integration/bridge-woocommerce.php',
				'selective_sync'  => 'selective-synchronization/selective-synchronization.php',
				'sso'             => 'edwiser-bridge-sso/sso.php',
				'bulk_purchase'   => 'edwiser-multiple-users-course-purchase/edwiser-multiple-users-course-purchase.php',
				'custom_fields'   => 'edwiser-custom-fields/edwiser-custom-fields.php',
			);

			foreach ( $extensions as $key => $plugin_path ) {
				if ( is_plugin_active( $plugin_path ) ) {
					deactivate_plugins( $plugin_path );
					$modules_data[ $key ] = 'active';
				} else {
					$modules_data[ $key ] = 'deactive';
				}
			}

			update_option( 'eb_pro_modules_data', $modules_data );
			update_option( 'eb_pro_legacy_plugin_migrated', 'yes' );
		}

		$modules_data = get_option( 'eb_pro_modules_data' );
		if ( ! is_array( $modules_data ) || empty( $modules_data ) ) {
			$modules_data = array(
				'selective_sync'  => 'active',
				'sso'             => 'active',
				'woo_integration' => 'active',
				'bulk_purchase'   => 'active',
				'custom_fields'   => 'active',
			);
			update_option( 'eb_pro_modules_data', $modules_data );
		}
	}
	/**
	 * Create required DB tables.
	 */
	private static function create_eb_pro_db_tables() {
		global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// SSO table.
        $tbl_gp_oauth      = $wpdb->prefix . 'gp_oauth_users';

        $stmt_gp_oauth = "CREATE TABLE $tbl_gp_oauth (
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
            `oauth_provider` VARCHAR(255) NOT NULL ,
            `oauth_uid` VARCHAR(255) NOT NULL ,
            `first_name` VARCHAR(100) NOT NULL ,
            `last_name` VARCHAR(100) NOT NULL ,
            `email` VARCHAR(255) NOT NULL ,
            `gender` VARCHAR(10),
            `locale` VARCHAR(10),
            `picture` VARCHAR(255),
            `link` VARCHAR(255),
            `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            `modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ,
            `wp_user_id` BIGINT(20),
            PRIMARY KEY (`id`)
        ) $charset_collate;";

        dbDelta($stmt_gp_oauth);

		// Moodle enrollment table
		$woo_moo_course_tbl_old = $wpdb->prefix . 'woo_moodle_course';
		$table_present_result_old = $wpdb->get_var( "SHOW TABLES LIKE '{$woo_moo_course_tbl_old}'" ); // @codingStandardsIgnoreLine
		$woo_moo_course_tbl = $wpdb->prefix . 'eb_moodle_course_products';
		$table_present_result = $wpdb->get_var( "SHOW TABLES LIKE '{$woo_moo_course_tbl}'" ); // @codingStandardsIgnoreLine

		if ( ( null === $table_present_result && null === $table_present_result_old ) || ( $table_present_result !== $woo_moo_course_tbl && $table_present_result_old !== $woo_moo_course_tbl_old ) ) {
			$woo_moo_course_table = "CREATE TABLE IF NOT EXISTS $woo_moo_course_tbl (
				meta_id        bigint(20) AUTO_INCREMENT,
				product_id bigint(20),
				moodle_post_id bigint(20),
				moodle_course_id bigint(20),
				PRIMARY KEY id (meta_id)
			) $charset_collate;";

			dbDelta( $woo_moo_course_table );

			$query = 'SELECT `post_id`,`meta_value`
							FROM  `' . $wpdb->prefix . "postmeta` 
							WHERE  `meta_key` LIKE  'product_options'";

			$result = $wpdb->get_results( $query ); // @codingStandardsIgnoreLine

			if ( ! empty( $result ) ) {
				foreach ( $result as $single_result ) {
					$product_options = unserialize( $single_result->meta_value ); // @codingStandardsIgnoreLine

					if ( wooInt\check_value_set( $product_options, 'moodle_post_course_id' ) && wooInt\check_value_set( $product_options, 'moodle_course_id' ) ) {
						foreach ( $product_options['moodle_post_course_id'] as $key => $value ) {
							$moo_course_id_list = explode( ',', $product_options['moodle_course_id'] );

							$wpdb->insert( // @codingStandardsIgnoreLine
								$woo_moo_course_tbl,
								array(
									'product_id'       => $single_result->post_id,
									'moodle_post_id'   => $value,
									'moodle_course_id' => $moo_course_id_list[ $key ],
								)
							);
						}
					}
				}
			}
		}

		// Cohort info table
		global $wpdb;
		$tbl_cohort_info  = $wpdb->prefix . 'bp_cohort_info';
		$stmt_cohort_info = "CREATE TABLE IF NOT EXISTS $tbl_cohort_info("
				. 'ID INT( 11 ) NOT NULL AUTO_INCREMENT ,'
				. 'NAME VARCHAR( 300 ) NOT NULL ,'
				. 'COHORT_NAME VARCHAR( 300 ) NOT NULL ,'
				. 'MDL_COHORT_ID INT( 20 ) ,'
				. 'PRODUCTS VARCHAR( 300 ) ,'
				. 'COURSES VARCHAR( 300 ) NOT NULL ,'
				. 'COHORT_MANAGER INT( 10 ) NOT NULL ,'
				. 'INCOMP_ORD VARCHAR( 300 ) DEFAULT NULL ,'
				. "SYNC TINYINT( 4 ) NOT NULL DEFAULT  '0',"
				. 'idnumber varchar(500),'
				. 'PRIMARY KEY ( ID )'
				. ")$charset_collate;";

		dbDelta( $stmt_cohort_info );
	}

	/**
	 * Create required pages.
	 */
	private static function create_eb_pro_pages() {
		// bulk purchase page.
		$enroll_page_id = post_exists( 'Enroll Students', '[bridge_woo_enroll_users]' );
		if ( ! $enroll_page_id ) {
			$user_ID        = get_current_user_id();
			$blogtime       = current_time( 'mysql' );
			$my_page        = array(
				'post_title'   => 'Enroll Students',
				'post_content' => '[bridge_woo_enroll_users]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => $user_ID,
				'post_date'    => $blogtime,
			);
			$enroll_page_id = wp_insert_post( $my_page );
		}
		update_option( 'wdm_enroll_students', $enroll_page_id );
		$eb_general                             = get_option( 'eb_general' );
		$eb_general['mucp_group_enrol_page_id'] = $enroll_page_id;
		update_option( 'eb_general', $eb_general );
	}
	/**
	 * Add default email template options.
	 */
	private static function add_default_email_tmpl_options() {
		// add default email template options.
		require_once plugin_dir_path( __FILE__ ) . 'woo-int/class-bridge-woo-email-template-manager.php';
		$eb_tmpl_manag       = new wooInt\Bridge_Woo_Email_Template_Manager();
		$bp_purchase_content = $eb_tmpl_manag->get_woo_int_default_notification( 'eb_emailtmpl_woocommerce_moodle_course_notifn', true );

		if ( false === get_option( 'eb_emailtmpl_woocommerce_moodle_course_notifn' ) ) {
			update_option( 'eb_emailtmpl_woocommerce_moodle_course_notifn', $bp_purchase_content );
			update_option( 'eb_emailtmpl_woocommerce_moodle_course_notifn_notify_allow', 'ON' );
		}

		require_once plugin_dir_path( __FILE__ ) . 'bulk-purchase/class-eb-bp-email-template-manager.php';
		$eb_tmpl_manag        = new bulkPurchase\Eb_Bp_Email_Template_Manager();
		$bp_purchase_content  = $eb_tmpl_manag->get_bulk_purchase_default_notification( 'eb_emailtmpl_bulk_prod_purchase_notifn' );
		$cohort_enrol_cont    = $eb_tmpl_manag->get_bulk_purchase_cohort_enroll_notification( 'eb_emailtmpl_student_enroll_in_cohort_notifn' );
		$cohort_unenrol_cont  = $eb_tmpl_manag->get_bulk_purchase_cohort_unenroll_notification( 'eb_emailtmpl_student_unenroll_in_cohort_notifn' );
		$cohort_delete_cont   = $eb_tmpl_manag->bp_get_group_deletion_content( 'eb_emailtmpl_cohort_deletion' );
		$bulk_qty_refund_cont = $eb_tmpl_manag->bp_get_group_refund_content( 'eb_emailtmpl_bulk_refund' );
		$bp_new_group_cont    = $eb_tmpl_manag->bp_get_group_creation_content( 'eb_emailtmpl_new_group_creation' );

		update_option( 'eb_emailtmpl_bulk_prod_purchase_notifn', $bp_purchase_content );
		update_option( 'eb_emailtmpl_student_enroll_in_cohort_notifn', $cohort_enrol_cont );
		update_option( 'eb_emailtmpl_student_unenroll_in_cohort_notifn', $cohort_unenrol_cont );
		update_option( 'eb_emailtmpl_cohort_deletion', $cohort_delete_cont );
		update_option( 'eb_emailtmpl_bulk_refund', $bulk_qty_refund_cont );
		update_option( 'eb_emailtmpl_new_group_creation', $bp_new_group_cont );

		update_option( 'eb_emailtmpl_bulk_prod_purchase_notifn_notify_allow', 'ON' );
		update_option( 'eb_emailtmpl_student_enroll_in_cohort_notifn_notify_allow', 'ON' );
		update_option( 'eb_emailtmpl_student_unenroll_in_cohort_notifn_notify_allow', 'ON' );
		update_option( 'eb_emailtmpl_cohort_deletion_notify_allow', 'ON' );
		update_option( 'eb_emailtmpl_bulk_refund_notify_allow', 'ON' );
		update_option( 'eb_emailtmpl_new_group_creation_notify_allow', 'ON' );
	}

	/**
	 * SSO data migration.
	 */
	private static function sso_data_migration() {
		$sso_settings = get_option( 'eb_sso_settings_general' );
		if ( isset( $sso_settings['eb_sso_fb_enable'] ) && 'yes' === $sso_settings['eb_sso_fb_enable'] ) {
			$sso_settings['eb_sso_fb_enable'] = 'both';
		}
		if ( isset( $sso_settings['eb_sso_gp_enable'] ) && 'yes' === $sso_settings['eb_sso_gp_enable'] ) {
			$sso_settings['eb_sso_gp_enable'] = 'both';
		}
		update_option( 'eb_sso_settings_general', $sso_settings );
	}

	/**
	 * Create required DB tables
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public static function create_moodle_db_tables() {
		global $wpdb;

		
	}

	/**
	 * Custom Fields data migration
	 *
	 * @since    1.0.0
	 * @access public
	 */
	public static function custom_fields_data_migration() {
		$migrated = get_option( 'edwiser_custom_fields_migration' );
		if ( ! $migrated ) {
			$table_data = get_option( 'eb_wi_custom_fields', array() );
			if ( ! empty( $table_data ) ) {
				$new_table_data = array();
				foreach ( $table_data as $key => $value ) {
					// in case is plugin is reactivated.
					if ( isset( $value['eb-user-accnt'] ) ) {
						$value['edwiser-user-accnt'] = $value['eb-user-accnt'];
					}

					// create new data.
					$new_table_data[ $key ] = array(
						'type'           => isset( $value['type'] ) ? $value['type'] : 'text',
						'label'          => isset( $value['label'] ) ? $value['label'] : '',
						'placeholder'    => isset( $value['placeholder'] ) ? $value['placeholder'] : '',
						'default-val'    => isset( $value['default-val'] ) ? $value['default-val'] : '',
						'class'          => isset( $value['class'] ) ? $value['class'] : '',
						'enabled'        => isset( $value['enabled'] ) ? $value['enabled'] : 0,
						'required'       => isset( $value['required'] ) ? $value['required'] : 0,
						'sync-on-moodle' => isset( $value['sync-on-moodle'] ) ? $value['sync-on-moodle'] : 0,
						'checkout'       => isset( $value['checkout'] ) ? $value['checkout'] : 1, // for all custom fields set checkout to 1.
						'woo-reg'        => isset( $value['woo-reg'] ) ? $value['woo-reg'] : 0,
						'woo-my-accnt'   => isset( $value['woo-my-accnt'] ) ? $value['woo-my-accnt'] : 0,
						'eb-reg'         => isset( $value['eb-reg'] ) ? $value['eb-reg'] : 0,
						'eb-user-accnt'  => isset( $value['edwiser-user-accnt'] ) ? $value['edwiser-user-accnt'] : 0,
					);

					if ( isset( $value['options'] ) ) {
						$new_table_data[ $key ]['options'] = $value['options'];
					}
				}
				update_option( 'edwiser_custom_fields', $new_table_data );
			}
			update_option( 'edwiser_custom_fields_migration', true );
		}
	}

	/**
	 * Modify table structure
	 */
	public static function modify_table_schema() {
		global $wpdb;

		// bp_cohort_info table.
		$tbl_cohort = $wpdb->prefix . 'bp_cohort_info';
		if ( $wpdb->query( "SHOW TABLES LIKE '" . $tbl_cohort . "'" ) === 1 ) { // @codingStandardsIgnoreLine.
			$wpdb->query( "ALTER TABLE `$tbl_cohort` MODIFY COLUMN COHORT_NAME varchar(300);" ); // @codingStandardsIgnoreLine.
		}

		$new_columns = array(
			'idnumber' => 'varchar(500)',
			'NAME'     => 'varchar(300)',
			'COURSES'  => 'text',
		);
		foreach ( $new_columns as $col_name => $col_type ) {
			$exists = $wpdb->query( $wpdb->prepare( "SHOW COLUMNS FROM {$tbl_cohort} LIKE '%s';", $col_name ) ); // @codingStandardsIgnoreLine.
			/**
			* Checkes the column exist or not if not exist then add the column into the databse.
			*/
			if ( ! $exists ) {
				$wpdb->query( "ALTER TABLE {$tbl_cohort} ADD {$col_name} {$col_type};" ); // @codingStandardsIgnoreLine.
			} else {
				$wpdb->query( "ALTER TABLE {$tbl_cohort} MODIFY {$col_name} {$col_type};" ); // @codingStandardsIgnoreLine.
			}
		}

		// moodle_enrollment table
		$usr_enrol_tbl = $wpdb->prefix . 'moodle_enrollment';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$usr_enrol_tbl}'" ) === $usr_enrol_tbl ) { // @codingStandardsIgnoreLine.
			$columns = array(
				'enrolled_by'   => 'varchar(10)',
				'product_id'    => 'int(11)',
				'mdl_cohort_id' => 'int(20)',
				'role'          => 'varchar(20)',
			);

			foreach ( $columns as $col_name => $col_type ) {
				$exists = $wpdb->query( $wpdb->prepare( "SHOW COLUMNS FROM {$usr_enrol_tbl} LIKE '%s';", $col_name ) ); // @codingStandardsIgnoreLine.

				/**
				 * Checkes the column exist or not if not exist then add the column into the databse.
				 */
				if ( ! $exists ) {
					$wpdb->query( "ALTER TABLE {$usr_enrol_tbl} ADD {$col_name} {$col_type};" ); // @codingStandardsIgnoreLine.
				}
			}
		}

		// check if woo_moodle_course table exists then change the table name to eb_moodle_course_products.
		$woo_moo_course_tbl = $wpdb->prefix . 'woo_moodle_course';
		$woo_moo_course_tbl_new = $wpdb->prefix . 'eb_moodle_course_products';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$woo_moo_course_tbl}'" ) === $woo_moo_course_tbl && $wpdb->get_var( "SHOW TABLES LIKE '{$woo_moo_course_tbl_new}'" ) !== $woo_moo_course_tbl_new ) { // @codingStandardsIgnoreLine.
			$wpdb->query( "RENAME TABLE {$woo_moo_course_tbl} TO {$wpdb->prefix}eb_moodle_course_products;" ); // @codingStandardsIgnoreLine.
		}
	}

	/**
	 * Update process
	 */
	public static function update() {
		$elementor_templates_created = get_option( 'eb_elementor_templates_created' );

		// check if elementor pro is installed and templates are not created.
		if ( is_plugin_active( 'elementor-pro/elementor-pro.php' ) && ! $elementor_templates_created ) {
			// create elementor templates.
			self::create_elementor_templates();
		}
	}

	/**
	 * Create Elementor templates
	 */
	public static function create_elementor_templates() {

		// create elementor template for shop page.
		self::create_elementor_shop_page_template();

		// create elementor template for single product page.
		self::create_elementor_product_page_template();
	}

	public static function create_elementor_shop_page_template(){
		// create elementor template for shop page and make it draft for now.
		$eb_shop_page = get_option( 'eb_pro_elementor_shop_page_template_id' );
		if ( ! $eb_shop_page ) {
			$post_args = array(
				'post_title'   => 'Edwiser Bridge Shop Page',
				'post_status'  => 'draft',
				'post_type'    => 'elementor_library',
			);
	
			$post_id = wp_insert_post( $post_args );
		} else {
			$post_id = $eb_shop_page;
		}
		
		$elementor_data = array(
			array(
				'id'       => '48363dc',
				'elType'   => 'container',
				'settings' => array(),
				'elements' => array(
					array(
						'id'         => '40387ac',
						'elType'     => 'widget',
						'settings'   => array(
							'title'          => 'All courses',
							'order'          => 'asc',
							'per_page'       => 8,
							'default_layout' => 'grid',
						),
						'elements'   => array(),
						'widgetType' => 'eb-pro-shop-page-widget',
					),
				),
				'isInner'  => false,
			),
		);

		$elementor_data = wp_json_encode( $elementor_data );
		update_post_meta( $post_id, '_elementor_data', $elementor_data );

		// background color
		$page_settings = array(
			'background_background' => 'classic',
			'background_color'      => '#F7F9FD',
		);
		update_post_meta( $post_id, '_elementor_page_settings', $page_settings );

		// set page display condition for shop page
		$display_condition = array(
			'include/product_archive/shop_page',
		);

		update_post_meta( $post_id, '_elementor_template_type', 'product-archive' );
		update_post_meta( $post_id, '_elementor_conditions', $display_condition );

		// save the template id in option.
		update_option( 'eb_pro_elementor_shop_page_template_id', $post_id );

		return $post_id;
	}

	public static function create_elementor_product_page_template(){
		$eb_product_page = get_option( 'eb_pro_elementor_single_product_page_template_id' );

		if ( ! $eb_product_page ) {
			// create elementor template for single product page and make it draft for now.
			$post_args = array(
				'post_title'   => 'Edwiser Bridge Single Product Page',
				'post_status'  => 'draft',
				'post_type'    => 'elementor_library',
			);

			$post_id = wp_insert_post( $post_args );
		} else {
			$post_id = $eb_product_page;
		}
		
		// get any woo product
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 1,
        );
        $products = get_posts( $args );
        if( ! empty( $products ) ) {
			$product_id = $products[0]->ID;
		} else {
			$product_id = 0;
		}

		$elementor_data = array(
			array(
				'id'       => 'f05411c',
				'elType'   => 'container',
				'settings' => array(),
				'elements' => array(
					array(
						'id'         => 'ed57592',
						'elType'     => 'widget',
						'settings'   => array(
							'product_id' => $product_id,
						),
						'elements'   => array(),
						'widgetType' => 'eb-pro-product-page-widget',
					),
					array(
						'id'         => 'eca12b3',
						'elType'     => 'widget',
						'settings'   => array(
							'title'    => 'Related Courses',
							'per_page' => 4,
						),
						'elements'   => array(),
						'widgetType' => 'eb-pro-related-product-widget',
					),
				),
				'isInner'  => false,
			),
		);

		$elementor_data = wp_json_encode( $elementor_data );

		update_post_meta( $post_id, '_elementor_data', $elementor_data );

		// background color
		$page_settings = array(
			'background_background' => 'classic',
			'background_color'      => '#F7F9FD',
		);
		update_post_meta( $post_id, '_elementor_page_settings', $page_settings );

		// set page display condition for single product page
		$display_condition = array(
			'include/product',
		);

		update_post_meta( $post_id, '_elementor_template_type', 'product' );
		update_post_meta( $post_id, '_elementor_conditions', $display_condition );

		// save the template id in option.
		update_option( 'eb_pro_elementor_single_product_page_template_id', $post_id );

		return $post_id;
	}
}

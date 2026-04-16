<?php
/**
 * Woo Integration Module
 * This class is responsible for Woo Integration module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for Woo Integration module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\wooInt;

use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

/**
 * Bridge WooCommerce Product Manager class
 */
class Bridge_Woocommerce_Product_Manager {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Edwiser Bridge instance
	 *
	 * @var object
	 */
	private $edwiser_bridge;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

		$this->edwiser_bridge = new EdwiserBridge();
	}

	/**
	 * This function adds new tab for woocommerce Product
	 *
	 * @since 1.0.4
	 * @access public
	 * @param array $product_data_tabs The current Product tabs settings.
	 */
	public function bridge_woo_add_tab( $product_data_tabs ) {

		$bridge_woo_tab    = array(
			'bridge_woo_simple' => array(
				'label'  => __( 'WooCommerce Integration', 'bridge_woocommerce' ),
				'target' => 'bridge_woo_product_data',
				'class'  => array( 'show_if_simple', 'hide_if_grouped' ),
			),
		);
		$product_data_tabs = array_merge( $product_data_tabs, $bridge_woo_tab );

		return $product_data_tabs;
	}

	/**
	 * Bridge WooCommerce add data panel.
	 */
	public function bridge_woo_add_data_panel() {

		global $post;

		// Enqueue script.
		wp_enqueue_script( 'admin_product_js' );
		?>
		<div id="bridge_woo_product_data" class="panel woocommerce_options_panel">
			<div class="options_bridge_woo pricing show_if_simple show_if_external">
				<?php self::bridge_woo_show_meta( 0, $post->ID, 'product' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * This function adds new tab for woocommerce Product
	 *
	 * @since 1.0.4
	 * @access public
	 * @param int $loop    The current variation Index.
	 * @param int $variation_data    variation details.
	 * @param int $variation    variation post details.
	 */
	public function bridge_woo_add_product_meta_variation( $loop, $variation_data, $variation ) {

		if ( empty( $variation_data ) ) {
			$variation_data = '';
		}
		wp_enqueue_script( 'admin_product_js' );

		if ( isset( $variation->ID ) && ! empty( $variation->ID ) ) {
			?>
			<div class="bridge_woo_variation_wrapper">
				<?php self::bridge_woo_show_meta( $loop, $variation->ID, $variation->post_type ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Show meta fields for product.
	 *
	 * @param int    $index      The current variation Index.
	 * @param int    $product_id The current product ID.
	 * @param string $post_type  The current post type.
	 */
	private function bridge_woo_show_meta( $index, $product_id, $post_type = '' ) {

		// Check for existing Product option.
		$product_option = get_post_meta( $product_id, 'product_options', true );

		$moo_post_course_id = array();

		if ( check_value_set( $product_option, 'moodle_post_course_id' ) && is_array( $product_option['moodle_post_course_id'] ) ) {
			$moo_post_course_id = $product_option['moodle_post_course_id'];
		}

		// Get existing available course options.

		$fields = $this->populate_metabox_fields( 'product' );

		if ( ! empty( $fields ) && isset( $fields['moodle_post_course_id']['options'] ) && is_array( $fields['moodle_post_course_id']['options'] ) ) {
			if ( 'product_variation' === $post_type ) {
				$name = 'bridge_woo_variation_option[' . $index . '][]';
			} else {
				$name = 'product_options[moodle_post_course_id][]';
			}
			?>

			<p class="form-field">
				<label for="courses_ids">
					<?php esc_html_e( 'Courses', 'edwiser-bridge-pro' ); ?>
				</label>
				<select name="<?php echo esc_html( $name ); ?>" class="moodle_post_course_id" multiple="multiple" class="woo-moodle-post-course-id">
					<?php
					foreach ( $fields['moodle_post_course_id']['options'] as $key => $value ) {
						?>
						<option value="<?php echo esc_html( $key ); ?>" <?php echo esc_html( in_array( $key, $moo_post_course_id ) ? 'selected=selected' : '' ); // @codingStandardsIgnoreLine ?> >
							<?php echo esc_html( $value ); ?>
						</option>
						<?php
					}
					?>
				</select>
				<img class="help_tip" data-tip='<?php esc_html_e( 'Associate product with courses.', 'edwiser-bridge-pro' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" />
			</p>
			<?php
			do_action( 'wdm_display_fields', $product_id, $post_type, $index );
		}
	}

	/**
	 * Array esc attr.
	 *
	 * @param array $item1 The current item.
	 * @param int   $key   The current key.
	 */
	public function bridge_woo_array_esc_attr( &$item1, $key ) {
		unset( $key );
		$item1 = esc_attr( $item1 );
	}

	/**
	 * Save variation meta.
	 *
	 * @param int    $variation_id The current variation ID.
	 * @param string $key          The current key.
	 */
	public function bridge_woo_save_variation_meta( $variation_id, $key ) {

		$moodle_post_ids = array();
		if ( isset( $_POST['bridge_woo_variation_option'][ $key ] ) ) { // @codingStandardsIgnoreLine
			if ( is_array( $_POST['bridge_woo_variation_option'][ $key ] ) ) { // @codingStandardsIgnoreLine
				array_walk( $_POST['bridge_woo_variation_option'][ $key ], array( $this, 'bridge_woo_array_esc_attr' ) ); // @codingStandardsIgnoreLine
				$moodle_post_ids['moodle_post_course_id'] = $_POST['bridge_woo_variation_option'][ $key ]; // @codingStandardsIgnoreLine
			} else {
				$moodle_post_ids['moodle_post_course_id'] = esc_attr( $_POST['bridge_woo_variation_option'][ $key ] ); // @codingStandardsIgnoreLine
			}
		} else {
			// No data to save.
			$moodle_post_ids['moodle_post_course_id'] = '-1';
		}

		if ( isset( $_POST['eb_apply_subscription_expiry'][ $key ] ) && 'yes' === $_POST['eb_apply_subscription_expiry'][ $key ]  ) { // @codingStandardsIgnoreLine
			$moodle_post_ids['eb_apply_subscription_expiry'] = 'yes';
		} else {
			$moodle_post_ids['eb_apply_subscription_expiry'] = 'no';
		}
		self::bridge_woo_save_meta( $moodle_post_ids, $variation_id, 'product_variation' );
	}

	/**
	 * Register meta boxes for Product
	 *
	 * @return void
	 * @since         1.0.0
	 */
	public function register_meta_boxes() {

		// Register metabox for Product post type.

		add_meta_box(
			'bridge_woo_product_options',
			__( 'Product Options', 'edwiser-bridge-pro' ),
			array( $this, 'post_options_callback' ),
			'product',
			'advanced',
			'default',
			array( 'post_type' => 'product' )
		);

		// Enqueue script.
		wp_enqueue_script( 'admin_product_js' );
	}

	/**
	 * Callback for metabox fields
	 *
	 * @since         1.0.0
	 * @param object $post current $post object.
	 * @param array  $args arguments supplied to the callback function.
	 */
	public function post_options_callback( $post, $args ) {

		if ( empty( $post ) ) {
			$post = '';
		}
		// get fields for a specific post type.
		$fields = $this->populate_metabox_fields( $args['args']['post_type'] );

		$plugin_post_types = new EB_Post_Types( $this->plugin_name, $this->version );

		echo esc_html( "<div id='{$args['args']['post_type']}_options' class='post-options'>" );

		// render fields using our render_metabox_fields( ) function.
		foreach ( $fields as $key => $values ) {
			$field_args = array(
				'field_id'  => $key,
				'field'     => $values,
				'post_type' => $args['args']['post_type'],
			);
			$plugin_post_types->render_metabox_fields( $field_args );
		}
		echo esc_html( '</div>' );
	}

	/**
	 * Method to populate metabox fields for Product post types
	 *
	 * @since     1.0.0
	 * @param string $post_type returns array of fields for specific post type.
	 */
	private function populate_metabox_fields( $post_type ) {

		global $wpdb;

		$course_list = array();

		$query = "SELECT `ID`,`post_title`, `post_status` FROM  `" . $wpdb->prefix . "posts` WHERE  `post_type` LIKE  'eb_course' AND (`post_status` LIKE 'publish' OR `post_status` LIKE 'draft' )"; // @codingStandardsIgnoreLine

		$result = $wpdb->get_results( $query, OBJECT_K ); // @codingStandardsIgnoreLine

		if ( ! empty( $result ) ) {
			foreach ( $result as $post_id => $single_result ) {
				$draft = '';
				if ( 'draft' === $single_result->post_status ) {
					$draft = '( draft  )';
				}
				$course_list[ $post_id ] = $single_result->post_title . $draft;
			}
		}

		$args_array = array(
			'product' => array(
				'moodle_post_course_id' => array(
					'label'       => __( 'Courses', 'edwiser-bridge-pro' ),
					'description' => __( 'Associate product with courses', 'edwiser-bridge-pro' ),
					'type'        => 'select_multi',
					'options'     => $course_list,
				),
			),
		);

		$args_array = apply_filters( 'ed_woo_post_options', $args_array );

		if ( ! empty( $post_type ) ) {
			if ( isset( $args_array[ $post_type ] ) ) {
				return $args_array[ $post_type ];
			} else {
				return $args_array;
			}
		}
	}
	/**
	 * This function handle meta save when Product is saved/updated.
	 * This adds Product, Course log record in table and also update corresponding courses closed url
	 *
	 * @param integer $post_id post id.
	 * @access public
	 * @since 1.0.0
	 */
	public function handle_post_options_save( $post_id ) {
		global $pagenow;

		$post_type = get_post_type( $post_id );

		if ( self::is_valid_to_save_meta() && in_array( $post_type, array( 'product' ) ) && 'admin-ajax.php' !== $pagenow ) { // @codingStandardsIgnoreLine

			// Options to update will be stored here.
			$update_post_options = array();

			$fields = $this->populate_metabox_fields( $post_type );

			// Using other function eo check the isset.
			$post_options = check_value_set( $_POST, $post_type . '_options' ); // @codingStandardsIgnoreLine

			if ( ! empty( $post_options ) ) {
				foreach ( $fields as $key => $values ) {
					$option_name  = $key;
					$option_value = $this->wdm_isset_null( $post_options[ $key ] );

					// format the values.
					switch ( sanitize_title( $values['type'] ) ) {
						case 'checkbox':
							$option_value = is_null( $option_value ) ? 'no' : 'yes';
							break;
						case 'textarea':
							$option_value = wp_kses_post( trim( $option_value ) );
							break;
						case 'text':
						case 'text_secret':
						case 'number':
						case 'select':
						case 'password':
						case 'radio':
							$option_value = wpClean( $option_value );
							break;
						case 'select_multi':
						case 'checkbox_multi':
							$option_value = array_filter( array_map( 'wpClean', (array) $option_value ) );
							break;
						default:
							break;
					}

					if ( ! is_null( $option_value ) ) {
						$update_post_options[ $option_name ] = $option_value;
					}
				}
			}//if ends - $_POST not empty

			$product = wc_get_product( $post_id );
			if ( class_exists( 'WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
				if ( isset( $_POST['eb_apply_subscription_expiry'] ) && 'yes' === $_POST['eb_apply_subscription_expiry'] ) {
					$update_post_options['eb_apply_subscription_expiry'] = 'yes';
				} else {
					$update_post_options['eb_apply_subscription_expiry'] = 'no';
				}
			}
			self::bridge_woo_save_meta( $update_post_options, $post_id, 'product' );

			return true;
		}
	}
	// function ends - handle_post_options_save.

	/**
	 * Check if the request is valid to save meta.
	 */
	public static function is_valid_to_save_meta() {
		if ( empty( $_POST ) ) { // @codingStandardsIgnoreLine
			return false;
		}

		if ( isset( $_POST['action'] ) && 'handle_product_synchronization' === $_POST['action'] ) { // @codingStandardsIgnoreLine
			// The request is coming from product synchronization functionality
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return true;
	}

	/**
	 * This function used to check isset( ), to reduce NPath complexity.
	 *
	 * @param var $var variable to check.
	 * @return boolean true/false
	 * @since   1.0.2
	 */
	public function wdm_isset_null( $var ) {

		if ( isset( $var ) ) {
			return $var;
		} else {
			return null;
		}
	}

	/**
	 * This function handle meta save when Product is saved/updated.
	 *
	 * @param array   $update_post_options array of post options.
	 * @param integer $product_id product id.
	 * @param string  $post_type post type.
	 */
	public function bridge_woo_save_meta( $update_post_options, $product_id, $post_type ) {
		global $wpdb;

		$course_id_list = array();

		// Retrieve selected Moodle post course details -- check course ID on Moodle & update.

		if ( is_array( $update_post_options ) ) {
			if ( isset( $update_post_options['moodle_post_course_id'] ) && is_array( $update_post_options['moodle_post_course_id'] ) ) {
				foreach ( $update_post_options['moodle_post_course_id'] as $key => $moo_post_course_id ) {
					if ( -1 === intval( $moo_post_course_id ) ) {
						array_splice( $update_post_options['moodle_post_course_id'], $key, 1 );
						continue;
					}

					$moodle_course_id = get_post_meta( $moo_post_course_id, 'moodle_course_id', true );

					array_push( $course_id_list, $moodle_course_id );

					// Add Course link entry in table.

					$insert_query = 'INSERT INTO `' . $wpdb->prefix . "eb_moodle_course_products`(`product_id`, `moodle_post_id`, `moodle_course_id` ) SELECT * FROM (SELECT '{$product_id}' as `product_id`, '{$moo_post_course_id}' as `moodle_post_id`, '{$moodle_course_id}' as `moodle_course_id` ) AS tmp
					WHERE NOT EXISTS ( SELECT `product_id`,`moodle_post_id` FROM `" . $wpdb->prefix . "eb_moodle_course_products` WHERE `product_id` = '{$product_id}' AND `moodle_post_id` = '{$moo_post_course_id}'
					) LIMIT 1;";

					$wpdb->get_results($insert_query ); // @codingStandardsIgnoreLine
				}//foreach ends - loop through selected courses

				/**
				 * Get Previous Course list
				 * Find difference between current & Previous selection
				 * If current post permalink is set for any previously selected course,
				 * it needs to be reset
				 */

				$moo_course_id_list = get_post_meta( $product_id, 'product_options', true );

				if ( check_value_set( $moo_course_id_list, 'moodle_post_course_id' ) && is_array( $moo_course_id_list['moodle_post_course_id'] ) ) {
					$course_list_diff = array_diff( $moo_course_id_list['moodle_post_course_id'], $update_post_options['moodle_post_course_id'] );
					if ( is_array( $course_list_diff ) ) {
						if ( 'product_variation' === $post_type ) {
							// Delete course link entry from Table.

							$course_tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
							$where           = array(
								'product_id'     => $product_id,
								'moodle_post_id' => implode( ',', $course_list_diff ),
							);

							$wpdb->delete( $course_tbl_name, $where ); // @codingStandardsIgnoreLine
						} else {
							$product_permalink = get_permalink( $product_id );

							foreach ( $course_list_diff as $single_course_diff ) {
								$course_options = get_post_meta( $single_course_diff, 'eb_course_options', true );

								if ( ! empty( $course_options ) && isset( $course_options['course_closed_url'] ) ) {
									if ( 0 === strcmp( $course_options['course_closed_url'], $product_permalink ) ) {
										/**
										 * Course contain, current Product link.
										 * It needs to be reset
										 */

										$course_options['course_closed_url'] = '';

										update_post_meta( $single_course_diff, 'eb_course_options', $course_options );
									}
								}

								// Delete course link entry from Table.

								$course_tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
								$where           = array(
									'product_id'     => $product_id,
									'moodle_post_id' => $single_course_diff,
								);

								$wpdb->delete( $course_tbl_name, $where ); // @codingStandardsIgnoreLine
							}
						}
					}//course_list_diff is array
				}

				// Update Post meta details.
				if ( '-1' != $update_post_options['moodle_post_course_id'] ) { // @codingStandardsIgnoreLine
					$update_post_options['moodle_course_id'] = implode( ',', $course_id_list );
				} else {
					$update_post_options = '';
				}

				update_post_meta( $product_id, 'product_options', $update_post_options );
				update_post_meta( $product_id, 'is_product_a_moodle_course', 'yes' );
			} else {
				// if ends - Moodle post courses are selected.
				$product_options = array(
					'moodle_post_course_id' => array(),
					'moodle_course_id'      => '',
				);

				update_post_meta( $product_id, 'product_options', $product_options );
				update_post_meta( $product_id, 'is_product_a_moodle_course', 'no' );
			}
		} else {
			// if ends - Update post options is array.
			// $product_options = array(
			// 	'moodle_post_course_id' => array(),
			// 	'moodle_course_id'      => '',
			// );

			// update_post_meta( $product_id, 'product_options', $product_options );
			// update_post_meta( $product_id, 'is_product_a_moodle_course', 'no' );
		}
	}

	/**
	 * This function performs operation, if any Product or Course is deleted
	 * for removing linking between course & Product
	 * rather than leaving it hanging.
	 *
	 * @param integer $post_id post id.
	 * @access public
	 * @since 1.0.0
	 */
	public function handle_post_options_delete( $post_id ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );

		$post_permalink = get_permalink( $post_id );

		if ( 'product' === $post_type ) {
			$query                = "SELECT * FROM `{$wpdb->prefix}eb_moodle_course_products` WHERE `product_id` = " . $post_id;
			$linked_course_result = $wpdb->get_results( $query ); // @codingStandardsIgnoreLine

			if ( ! empty( $linked_course_result ) ) {
				$this->edwiser_bridge->logger()->add( 'product', 'Perform operation on Product delete  ' . $post_id );  // add Product log.

				foreach ( $linked_course_result as $single_course_result ) {
					$moodle_post_id = $single_course_result->moodle_post_id;

					$course_options = get_post_meta( $moodle_post_id, 'eb_course_options', true );

					if ( ! empty( $course_options ) ) {
						if ( 0 === strcmp( rtrim( $course_options['course_closed_url'], '/' ), rtrim( $post_permalink, '/' ) ) ) {
							// Update Course closed url to empty.

							$course_options['course_closed_url'] = '';
							update_post_meta( $moodle_post_id, 'eb_course_options', $course_options );

							$this->edwiser_bridge->logger()->add( 'product', 'Course ID ' . $moodle_post_id . ' closed url is reset.' );  // add Product log.
						}//if ends - Product url matches
					}
				}// foreach ends -- loop through associated courses.
				// Delete associated Products entry.

				$course_tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
				$where           = array( 'product_id' => $post_id );
				$wpdb->delete( $course_tbl_name, $where ); // @codingStandardsIgnoreLine
			}//if ends - post type is Product
		} elseif ( 'eb_course' === $post_type ) {
			$query          = "SELECT * FROM `{$wpdb->prefix}eb_moodle_course_products` WHERE `moodle_post_id` = " . $post_id;
			$product_result = $wpdb->get_results( $query ); // @codingStandardsIgnoreLine

			$cur_moodle_course_id = get_post_meta( $post_id, 'moodle_course_id', true );

			if ( ! empty( $product_result ) ) {
				$this->edwiser_bridge->logger()->add( 'course', 'Perform operation on Course delete  ' . $post_id );  // add Course log.

				foreach ( $product_result as $single_product ) {
					$find_key   = false;
					$product_id = $this->wdm_isset_null( $single_product->product_id );

					if ( ! empty( $product_id ) ) {
						// Get Product meta.
						$product_options = get_post_meta( $product_id, 'product_options', true );

						if ( ! empty( $product_options ) && is_array( $product_options ) ) {
							$moodle_post_id_list = $this->wdm_isset_null( $product_options['moodle_post_course_id'] );

							if ( ! empty( $moodle_post_id_list ) && is_array( $moodle_post_id_list ) ) {
								$find_key = array_search( $post_id, $moodle_post_id_list ); // @codingStandardsIgnoreLine

								if ( is_numeric( $find_key ) ) {
									unset( $moodle_post_id_list[ $find_key ] );
								}

								$product_options['moodle_post_course_id'] = $moodle_post_id_list;
							}

							$course_id_list = isset( $product_options['moodle_course_id'] ) ? $product_options['moodle_course_id'] : '';
							$find_key       = false;

							if ( ! empty( $course_id_list ) ) {
								$course_id_list = explode( ',', $course_id_list );

								$find_key = array_search( $cur_moodle_course_id, $course_id_list ); // @codingStandardsIgnoreLine

								if ( is_numeric( $find_key ) ) {
									unset( $course_id_list[ $find_key ] );
								}

								$product_options['moodle_course_id'] = implode( ',', $course_id_list );
							}
						}

						update_post_meta( $product_id, 'product_options', $product_options );

						$this->edwiser_bridge->logger()->add( 'course', 'Product association is removed for Product ID ' . $product_id );  // add Course log.

						if ( empty( $product_options['moodle_post_course_id'] ) ) {
							wp_delete_post( $product_id );
							$this->edwiser_bridge->logger()->add( 'course', 'Product deleted, Product ID ' . $product_id );  // add Course log.
						}
					}
				}// foreach ends -- loop through associated products.
				// Delete associated Courses entry.

				$course_tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
				$where           = array( 'moodle_post_id' => $post_id );
				$wpdb->delete( $course_tbl_name, $where ); // @codingStandardsIgnoreLine
			}
		}//if ends - post type is eb_course
	}
}


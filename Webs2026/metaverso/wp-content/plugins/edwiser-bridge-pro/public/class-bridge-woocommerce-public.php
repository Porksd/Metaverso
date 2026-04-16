<?php
/**
 * Woo Int Public Module
 * This class is responsible for SSO module.
 * This is used to define internationalization, admin-specific hooks, and public-facing site hooks for SSO module.
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\pb;

use app\wisdmlabs\edwiserBridgePro\includes as includes;

/**
 * The public-facing functionality of Woo Int.
 */
class Bridge_Woocommerce_Public {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var string The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'eb-pro-woo-int-public-css', EB_PRO_PLUGIN_URL . 'public/assets/css/bridge-woocommerce-public.css', array(), $this->version, 'all' );

		wp_enqueue_style( 'edwiser-bridge-pro-elementor', EB_PRO_PLUGIN_URL . 'public/assets/css/edwiser-bridge-pro-elementor.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// Registering scripts.
		wp_register_script( 'bridge_woo_variation_courses', EB_PRO_PLUGIN_URL . 'public/assets/js/bridge-woocommerce-variation-courses.js', array( 'jquery' ), $this->version ); // @codingStandardsIgnoreLine
		wp_enqueue_script(
			'eb-pro-woo-int-public-js',
			EB_PRO_PLUGIN_URL . 'public/assets/js/bridge-woocommerce-public.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_enqueue_script( 'edwiser-bridge-pro-elementor-js', EB_PRO_PLUGIN_URL . 'public/assets/js/edwiser-bridge-pro-elementor.js', array( 'jquery' ), $this->version, false );

		$setting = get_option( 'eb_general', array() );
		if ( isset( $setting['eb_my_courses_page_id'] ) ) {
			$url = get_permalink( $setting['eb_my_courses_page_id'] );
			if ( $url ) {
				wp_localize_script(
					'eb-pro-woo-int-public-js',
					'wiPublic',
					array(
						'myCoursesUrl' => $url,
						'cancel'       => __( 'Cancel', 'edwiser-bridge-pro' ),
						'resume'       => __( 'Resume', 'edwiser-bridge-pro' ),
					)
				);
			}
		}
	}

	/**
	 * This function is used to add associated courses shortcode on - woocommerce_single_product_summary hook
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function display_product_related_courses() {
		global $product;
		$setting_woo_integration = get_option( 'eb_woo_int_settings', array() );
		if ( isset( $setting_woo_integration['wi_enable_asso_courses'] ) && 'yes' === $setting_woo_integration['wi_enable_asso_courses'] ) {
			if ( ( $product->is_type( 'simple' ) || $product->is_type( 'subscription' ) ) && shortcode_exists( 'bridge_woo_display_associated_courses' ) ) {
				$product_id = get_the_ID();
				echo esc_html( do_shortcode( '[bridge_woo_display_associated_courses product_id=' . $product_id . ']' ) );
			} elseif ( $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' ) ) {
				$available_variations = $product->get_available_variations();

				$variation_settings = array();

				if ( ! empty( $available_variations ) ) {
					foreach ( $available_variations as $single_variation ) {
						$return          = '';
						$variation_id    = $single_variation['variation_id'];
						$product_options = get_post_meta( $variation_id, 'product_options', true );

						if ( ! empty( $product_options ) ) {
							if ( isset( $product_options['moodle_post_course_id'] ) && is_array( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
								$return = ' <ul class="bridge-woo-available-courses">';
								foreach ( $product_options['moodle_post_course_id'] as $single_course_id ) {
									if ( 'publish' === get_post_status( $single_course_id ) ) {
										ob_start();
										?>
										<li>
											<a href="<?php echo esc_url( get_permalink( $single_course_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $single_course_id ) ); ?></a>
										</li>
										<?php
										$return .= ob_get_clean();
									}
								}
								$return .= '</ul>';
							}
						}

							$variation_settings[ $variation_id ] = apply_filters( 'bridge_woo_single_variation_html', $return, $variation_id );
					}//foreach ends

					wp_enqueue_script( 'bridge_woo_variation_courses' );
					wp_localize_script( 'bridge_woo_variation_courses', 'bridge_woo_courses', $variation_settings );

					ob_start();

					?>
						<div class="bridge-woo-courses" style="display:none;">
							<h4><?php esc_attr_e( 'Available courses', 'edwiser-bridge-pro' ); ?></h4>
						</div>
					<?php

					$content = ob_get_clean();

					echo apply_filters( 'bridge_woo_variation_associated_courses', $content ); // @codingStandardsIgnoreLine
				}
			}
		}
	}

	/**
	 * Display associated courses on grouped product page
	 *
	 * @param object $product product object.
	 */
	public function grouped_product_display_associated_courses( $product ) {
		$product_options = get_post_meta( $product->get_id(), 'product_options', true );

		if ( isset( $product_options['moodle_post_course_id'] ) && is_array( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
			ob_start();
			?>
			<td>
				<div class="wi-asso-courses-wrapper">
			<h7><?php esc_attr_e( 'Courses', 'edwiser-bridge-pro' ); ?></h7>
			<ul class="bridge-woo-available-courses">
				<?php
					includes\wooInt\wi_get_associated_courses( $product_options['moodle_post_course_id'] );
				?>
			</ul>
		</div>
			</td>
			<?php
			echo ob_get_clean(); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * This function is used to send associated courses list in WooCommerce Emails
	 *
	 * @param object $order order object.
	 * @param bool   $sent_to_admin sent to admin.
	 * @param bool   $plain_text plain text.
	 */
	public function send_associated_courses_in_email( $order, $sent_to_admin, $plain_text ) {
		if ( empty( $sent_to_admin ) ) {
			$sent_to_admin = '';
		}
		if ( empty( $plain_text ) ) {
			$plain_text = '';
		}

		$allowed_order_status = apply_filters( 'bridge_woo_email_allowed_order_status', array( 'wc-processing', 'wc-completed', 'wc-on-hold' ) );

		if ( in_array( $order->get_status(), $allowed_order_status, true ) ) {
			// Including required files.
			include_once EB_PRO_PLUGIN_PATH . 'includes/class-bridge-woo-functions.php';

			require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

			require_once WP_PLUGIN_DIR . '/edwiser-bridge/public/class-eb-template-loader.php';

			$edwiser_bridge = new \app\wisdmlabs\edwiserBridge\EdwiserBridge();

			$plugin_tpl_loader = new \app\wisdmlabs\edwiserBridge\Eb_Template_Loader( $edwiser_bridge->getPluginName(), $edwiser_bridge->getVersion() );

			ob_start();

			$plugin_tpl_loader->wpGetTemplate(
				'emails/associated-courses-order-email.php',
				array(
					'order' => $order,
				),
				'',
				EB_PRO_PLUGIN_PATH . 'public/templates/'
			);
			$email_content = ob_get_clean();

			echo $email_content; // @codingStandardsIgnoreLine
		}
	}

	/**
	 * This function is used to set Enable registration on the "Checkout" page and Disable guest checkout - woocommerce_after_checkout_billing_form hook
	 *
	 * @param object $checkout checkout object.
	 */
	public function configure_woocommerce_checkout( $checkout ) {
		// Unnecessary var.
		unset( $checkout );

		if ( ! \WC_Checkout::instance()->enable_signup || \WC_Checkout::instance()->enable_guest_checkout ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				unset( $cart_item_key );
				$_product = $values['data'];

				$product_id = ( isset( $_product->variation_id ) ? $_product->variation_id : $_product->id );

				$product_options = get_post_meta( $product_id, 'product_options', true );

				if ( ! empty( $product_options ) && isset( $product_options['moodle_post_course_id'] ) && ! empty( $product_options['moodle_post_course_id'] ) ) {
						// Add condition to make it work on checkout which have courses in the cart.
						\WC_Checkout::instance()->enable_signup         = true;
						\WC_Checkout::instance()->enable_guest_checkout = false;
						break;
				}
			}
		}
	}

	/**
	 * This function is used to add courses to user account page.
	 *
	 * @param array $user_orders user orders.
	 */
	public function add_woocomerce_orders_to_user_account_page( $user_orders ) {
		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}
		global $post;
		$content = $post->post_content;
		if ( ! has_shortcode( $content, 'eb_user_account' ) ) {
			return;
		}

		$wc_order_statuses = wc_get_order_statuses();

		$customer_orders = get_posts(
			array(
				'numberposts' => -1,
				'meta_key'    => '_customer_user', // @codingStandardsIgnoreLine
				'meta_value'  => get_current_user_id(), // @codingStandardsIgnoreLine
				'post_type'   => wc_get_order_types(),
				'post_status' => is_array( $wc_order_statuses ) ? array_keys( $wc_order_statuses ) : array(),
			)
		);

		$woo_orders  = $this->get_users_woocommerce_orders( $customer_orders );
		$user_orders = array_merge( $user_orders, $woo_orders );

		return $user_orders;
	}

	/**
	 * Get WooCommerce Orders.
	 *
	 * @param array $customer_orders Customer Orders.
	 */
	public function get_users_woocommerce_orders( $customer_orders ) {
		$course_associated_orders = array();
		foreach ( $customer_orders as $key => $order_object ) {
			$formatted_order_data = array();
			// if Order Belongs to subscription order shop_subscription do not include.
			if ( 'shop_subscription' === $order_object->post_type ) {
				$formatted_order_data = array();
				continue;
			}

			$formatted_order_data['order_id']    = $order_object->ID;
			$formatted_order_data['eb_order_id'] = $order_object->ID;

			// Get WC order Object.
			$order                               = wc_get_order( $order_object->ID );
			$formatted_order_data['status']      = $order->get_status();
			$formatted_order_data['amount_paid'] = $order->get_formatted_order_total();

			// Get WC order Object data.
			$order_data                            = $order->get_data();
			$formatted_order_data['billing_email'] = $order_data['billing']['email'];
			$formatted_order_data['currency']      = $order_data['currency'];
			$formatted_order_data['date']          = $order_data['date_created']->date( 'Y-m-d' );

			// $product_item is WC_Order_Item_Product Object.
			$ordered_items                        = $order->get_items();
			$formatted_order_data['ordered_item'] = array();
			foreach ( $ordered_items as $key => $product_item ) {
				// Get variation id.

				$product_id = $product_item->get_product_id();

				$variation_id = $product_item->get_variation_id();
				if ( 0 !== $variation_id ) {
					$product_id = $variation_id;
				}

				$product_options = get_post_meta( $product_id, 'product_options', true );

				// if the product is not associated with any moodle course.
				if ( false === $product_options || empty( $product_options ) ) {
					continue;
				} elseif ( ! empty( $product_options['moodle_post_course_id'] ) ) {
					// array merge for courses from different product.
					$formatted_order_data['ordered_item'] = array_merge( $formatted_order_data['ordered_item'], $product_options['moodle_post_course_id'] );
				}
			}
			$course_associated_orders[] = $formatted_order_data;
			$formatted_order_data       = array();
		}
		return $course_associated_orders;
	}

	/**
	 * Product page after add to cart.
	 */
	public function product_page_after_add_to_cart() {
		global $product;
		if ( 'simple' === $product->get_type() ) {
			$args = array( 'product' => $product );
			echo self::get_buy_now_button( $args ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Shop page after add to cart.
	 */
	public function shop_page_after_add_to_cart() {
		global $product;
		if ( 'simple' === $product->get_type() ) {
			$args = array( 'product' => $product );
			echo '<br />' . self::get_buy_now_button( $args ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Get buy now button.
	 *
	 * @param array $args arguments.
	 */
	public static function get_buy_now_button( $args ) {
		$eb_general = get_option( 'eb_woo_int_settings', array() );
		$buy_now_enabled = isset( $eb_general['wi_enable_buynow'] ) && 'yes' === $eb_general['wi_enable_buynow'] ? true : false;
		if ( ! $buy_now_enabled ) {
			return '';
		}
		$args = wp_parse_args(
			$args,
			array(
				'product' => null,
				'class'   => 'button',
			)
		);

		extract( $args ); // @codingStandardsIgnoreLine

		$eb_general = get_option( 'eb_woo_int_settings', array() );
		if ( isset( $eb_general['wi_buy_now_text'] ) && ! empty( $eb_general['wi_buy_now_text'] ) ) {
			$buy_now_text = $eb_general['wi_buy_now_text'];
		} else {
			$buy_now_text = __( 'Buy Now', 'edwiser-bridge-pro' );
		}

		$html = '';

		if ( null === $product || ! $product->is_purchasable() ) {
			return '';
		}

		$link   = self::get_product_add_to_cart_link( $product, 1 );
		$_id    = 'wi_buy_now_' . $product->get_id();
		$_class = 'wi_btn_buy_now button wi_buy_now_' . $product->get_type() . ' ' . $class;
		if ( is_product() ) {
			$_class .= ' wi_product';
		}
		$_attrs = 'data-product_type="' . $product->get_type() . '" data-product_id="' . $product->get_id() . '"';
		$html  .= '<a href="' . $link . '" id="' . $_id . '" ' . $_attrs . '  class="' . $_class . '">';
		$html  .= $buy_now_text;
		$html  .= '</a>';
		return $html;
	}

	/**
	 * Get product add to cart link.
	 *
	 * @param object $product product.
	 * @param int    $qty quantity.
	 */
	public static function get_product_add_to_cart_link( $product, $qty = 1 ) {
		if ( 'simple' === $product->get_type() ) {
			$link = $product->add_to_cart_url();
			$link = add_query_arg( 'quantity', $qty, $link );
			$link = add_query_arg( 'wi_buy_now', true, $link );
			return $link;
		}
	}

	/**
	 * Redirect to one click checkout page if buy now button is clicked.
	 *
	 * @param string $url url.
	 */
	public function buy_now_redirect( $url ) {
		if ( isset( $_REQUEST['wi_buy_now'] ) && 1 === (int)$_REQUEST['wi_buy_now'] ) { // @codingStandardsIgnoreLine
			$eb_general = get_option( 'eb_woo_int_settings', array() );
			if ( isset( $eb_general['wi_scc_page_id'] ) ) {
				$scc_url = get_permalink( $eb_general['wi_scc_page_id'] );
				if ( $scc_url ) {
					$url = $scc_url;
				}
			}
		}

		return $url;
	}

	/**
	 * On update cart by default page redirect to the default checkout page.
	 * If page is one click checkout page then function redirects to the edwiser bridge selected one click checkout page.
	 *
	 * @since  2.16
	 * @param  string $default_cart_url default cart url.
	 * @return string
	 */
	public function eb_get_one_click_checkout_url( $default_cart_url ) {
		global $post;

		if ( isset( $post ) && is_object( $post ) && isset( $post->post_content ) && isset( $post->post_content ) && strpos( $post->post_content, '[bridge_woo_single_cart_checkout]' ) !== false ) {
			$eb_general = get_option( 'eb_woo_int_settings', array() );
			if ( isset( $eb_general['wi_scc_page_id'] ) ) {
				$scc_page_id      = (int) $eb_general['wi_scc_page_id'];
				$default_cart_url = get_page_link( $scc_page_id );
			}
		}

		return $default_cart_url;
	}


	/**
	 * Woocommmerce loads checkout js and css only on the woocommerce checkout page.
	 * Below is the function to load woocommerce js and css on our one click checkout page.
	 *
	 * @since  2.16
	 * @param  string $is_scc Boolean Provider Slug/Type.
	 * @return string
	 */
	public function is_single_cart_checkout( $is_scc ) {
		global $post;

		$eb_general = get_option( 'eb_woo_int_settings', array() );
		if ( isset( $eb_general['wi_scc_page_id'] ) ) {
			$scc_page_id = (int) $eb_general['wi_scc_page_id'];
			if ( is_page( $scc_page_id ) ) {
				$is_scc = true;
			}
		}

		if ( isset( $post ) && is_object( $post ) && isset( $post->post_content ) && strpos( $post->post_content, '[woocommerce_cart]' ) !== false ) {
			$is_scc = false;
		}

		return $is_scc;
	}

	/**
	 * Add input checkbox and other input fields for purchase for someone else.
	 *
	 * @param object $checkout checkout object.
	 * @since  2.2.1
	 */
	public function wi_add_purchase_for_someone_else_input_fields( $checkout ) {

		// get products from cart.
		$cart_items = WC()->cart->get_cart();
		// get cart meta data.

		$products = array();
		foreach ( $cart_items as $cart_item ) {
			if ( isset( $cart_item['wdm_edwiser_self_enroll'] ) && 'on' == $cart_item['wdm_edwiser_self_enroll'] ) { // @codingStandardsIgnoreLine
				return;
			}
			$products[] = $cart_item['data'];
		}

		foreach ( $products as $product ) {
			$product_id      = $product->get_id();
			$product_options = get_post_meta( $product_id, 'product_options', true );
			$group_purchase  = 'off';
			$list_of_courses = array();
			if ( includes\wooInt\check_value_set( $product_options, 'moodle_post_course_id' ) ) {
				$line_item_course_ids = $product_options['moodle_post_course_id'];

				if ( ! empty( $list_of_courses ) ) {
					$list_of_courses = array_unique( array_merge( $list_of_courses, $line_item_course_ids ), SORT_REGULAR );
				} else {
					$list_of_courses = $line_item_course_ids;
				}
			} else {
				return;
			}
		}
		if ( empty( $list_of_courses ) ) {
			return;
		}

		// add checkbox for purchase for someone else.
		?>
		<h3 id="purchase-for-someone-else">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input id="purchase-for-someone-else-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="purchase_for_someone_else" value="1" style="border: 1px solid rgb(0, 0, 0);">
				<span><?php esc_html_e( 'Purchase this product for someone else?', 'edwiser-bridge-pro' ); ?></span>
			</label>
		</h3>
		<p class="form-row form-row-wide eb-purchase-for-someone-else" id="order_comments_field">
			<label for="order_comments" class=""><?php esc_html_e( 'First Name', 'edwiser-bridge-pro' ); ?><abbr class="required" title="required">*</abbr></label>
			<input type="text" class="input-text" name="recipient_first_name" id="recipient_first_name" placeholder="" value="" />
		</p>
		<p class="form-row form-row-wide eb-purchase-for-someone-else" id="order_comments_field">
			<label for="order_comments" class=""><?php esc_html_e( 'Last Name', 'edwiser-bridge-pro' ); ?><abbr class="required" title="required">*</abbr></label>
			<input type="text" class="input-text" name="recipient_last_name" id="recipient_last_name" placeholder="" value="" />
		</p>
		<p class="form-row form-row-wide eb-purchase-for-someone-else" id="order_comments_field">
			<label for="order_comments" class=""><?php esc_html_e( 'Email', 'edwiser-bridge-pro' ); ?><abbr class="required" title="required">*</abbr></label>
			<input type="text" class="input-text" name="recipient_email" id="recipient_email" placeholder="" value="" />
		</p>
		<?php
	}

	/**
	 * Validate input fields for purchase for someone else.
	 *
	 * @since  2.2.1
	 */
	public function wi_validate_purchase_for_someone_else_input_fields() {
		$eb_general                        = get_option( 'eb_woo_int_settings' );
		$purchase_for_someone_else_enabled = isset( $eb_general['wi_enable_purchase_for_someone_else'] ) && 'yes' === $eb_general['wi_enable_purchase_for_someone_else'] ? true : false;
		$is_for_someone_else = isset( $_POST['purchase_for_someone_else'] ) && '1' === $_POST['purchase_for_someone_else'] ? true : false; // @codingStandardsIgnoreLine
		if ( $purchase_for_someone_else_enabled && $is_for_someone_else ) {
			if ( ( isset( $_POST['recipient_first_name'] ) && empty( $_POST['recipient_first_name'] ) ) || ! isset( $_POST['recipient_first_name'] ) ) { // @codingStandardsIgnoreLine
				wc_add_notice( '<b>' . esc_html__( 'First Name', 'edwiser-bridge-pro' ) . '</b>' . esc_html__( ' is a required field.', 'edwiser-bridge-pro' ), 'error' );
			}
			if ( ( isset( $_POST['recipient_last_name'] ) && empty( $_POST['recipient_last_name'] ) ) || ! isset( $_POST['recipient_last_name'] ) ) { // @codingStandardsIgnoreLine
				wc_add_notice( '<b>' . esc_html__( 'Last Name', 'edwiser-bridge-pro' ) . '</b>' . esc_html__( ' is a required field.', 'edwiser-bridge-pro' ), 'error' );
			}
			if ( ( isset( $_POST['recipient_email'] ) && empty( $_POST['recipient_email'] ) ) || ! isset( $_POST['recipient_email'] ) ) { // @codingStandardsIgnoreLine
				wc_add_notice( '<b>' . esc_html__( 'Email', 'edwiser-bridge-pro' ) . '</b>' . esc_html__( ' is a required field.', 'edwiser-bridge-pro' ), 'error' );
			}
		}
	}

	/**
	 * Add Expiry control option to subscription product.
	 *
	 * @since  2.2.1
	 */
	public function wi_add_expiry_options_to_subscription_product() {
		// add checkbox for subscription expiry.
		global $post;
		$product_options = get_post_meta( $post->ID, 'product_options', true );
		if ( isset( $product_options['eb_apply_subscription_expiry'] ) ) {
			$eb_apply_subscription_expiry = $product_options['eb_apply_subscription_expiry'];
		} else {
			$eb_apply_subscription_expiry = 'no';
		}
		woocommerce_wp_checkbox(
			array(
				'id'          => 'eb_apply_subscription_expiry',
				'label'       => __( 'Subscription Expiry Date Access Control', 'edwiser-bridge-pro' ),
				'description' => __( 'This setting allows you to specify whether the subscription expiration date should take precedence over the course expiration date.', 'edwiser-bridge-pro' ),
				'value'       => $eb_apply_subscription_expiry,
			)
		);
	}

	/**
	 * Add Expiry control option to variable subscription product.
	 */
	public function wi_add_expiry_options_to_variable_subscription_product( $loop, $variation_data, $variation ) {
		if ( ! $variation ) {
			return;
		}
		$product_options = get_post_meta( $variation->ID, 'product_options', true );
		if ( isset( $product_options['eb_apply_subscription_expiry'] ) ) {
			$eb_apply_subscription_expiry = $product_options['eb_apply_subscription_expiry'];
		} else {
			$eb_apply_subscription_expiry = 'no';
		}
		?>
		<p class="form-field form-field-wide">
			<label>
				<?php esc_html_e( 'Subscription Expiry Date Access Control', 'edwiser-bridge-pro' ); ?>
				<?php echo wcs_help_tip( _x( 'This setting allows you to specify whether the subscription expiration date should take precedence over the course expiration date.', 'edwiser-bridge-pro' ) ); ?>
			</label>

			<input type="checkbox" class="checkbox" name="eb_apply_subscription_expiry[<?php echo esc_attr( $loop ); ?>]" value="yes" <?php checked( $eb_apply_subscription_expiry, 'yes' ); ?> />
		<?php
	}
}


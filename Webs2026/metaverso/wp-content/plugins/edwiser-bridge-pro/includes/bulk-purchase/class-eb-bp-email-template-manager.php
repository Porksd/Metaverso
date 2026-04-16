<?php
/**
 * Handles email tmaplate realted functionalitys.
 *
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  www.wisdmlabs.com
 * @since 1.0.0
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 *
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 */
if ( ! class_exists( 'Eb_Bp_Email_Template_Manager' ) ) {

	/**
	 * Class handles the email template managment functionality.
	 */
	class Eb_Bp_Email_Template_Manager {


		/**
		 * Provides the functionality to handle the tempalte restore event
		 * genrated by the Edwiser bridge plugin for the template.
		 * Calles for the eb_reset_email_tmpl_content filter
		 *
		 * @param array $args contains the tmpl_name(Key) and boolean value to restore the template or not.
		 */
		public function handle_template_restore( $args ) {
			$tmpl_key = $args['tmpl_name'];
			switch ( $tmpl_key ) {
				case 'eb_emailtmpl_bulk_prod_purchase_notifn':
					$value = $this->get_bulk_purchase_default_notification( 'eb_emailtmpl_bulk_prod_purchase_notifn', true );
					break;
				case 'eb_emailtmpl_student_enroll_in_cohort_notifn':
					$value = $this->get_bulk_purchase_cohort_enroll_notification( 'eb_emailtmpl_student_enroll_in_cohort_notifn', true );
					break;
				case 'eb_emailtmpl_student_unenroll_in_cohort_notifn':
					$value = $this->get_bulk_purchase_cohort_unenroll_notification( 'eb_emailtmpl_student_unenroll_in_cohort_notifn', true );
					break;
				case 'eb_emailtmpl_cohort_deletion':
					$value = $this->bp_get_group_deletion_content( 'eb_emailtmpl_cohort_deletion', true );
					break;
				case 'eb_emailtmpl_bulk_refund':
					$value = $this->bp_get_group_refund_content( 'eb_emailtmpl_bulk_refund', true );
					break;
				case 'eb_emailtmpl_new_group_creation':
					$value = $this->bp_get_group_creation_content( 'eb_emailtmpl_new_group_creation', true );
					break;
				default:
					return $args;
			}
			$status = update_option( $tmpl_key, $value );
			if ( $status ) {
				$args['is_restored'] = true;
			}
			return $args;
		}

		/**
		 * Prepares the bulk product enrollment email notification template content
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 */
		public function get_bulk_purchase_default_notification( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}
			$data = array(
				'subject' => __( 'Enroll student in bulk', 'edwiser-bridge-pro' ),
				'content' => $this->get_bulk_prod_purchase_default_content(),
			);
			return $data;
		}

		/**
		 * Prepares the user cohort enrollment email notification template content.
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 */
		public function get_bulk_purchase_cohort_enroll_notification( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}
			$data = array(
				'subject' => __( 'You have been enrolled in course', 'edwiser-bridge-pro' ),
				'content' => $this->get_enrol_students_in_cohort_default_content(),
			);
			return $data;
		}

		/**
		 * Prepares the user cohort unenrollment email notification template content.
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 */
		public function get_bulk_purchase_cohort_unenroll_notification( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}

			$data = array(
				'subject' => __( 'You have been unenrolled from group', 'edwiser-bridge-pro' ),
				'content' => $this->get_unnrol_students_incohort_default_content(),
			);
			return $data;
		}

		/**
		 * Prepares the cohort deletion email content.
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 */
		public function bp_get_group_deletion_content( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}

			$data = array(
				'subject' => __( 'Group Deleted', 'edwiser-bridge-pro' ),
				'content' => $this->get_cohort_deletion_default_content(),
			);
			return $data;
		}


		/**
		 * Prepares the cohort deletion email content.
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 */
		public function bp_get_group_refund_content( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}

			$data = array(
				'subject' => __( 'Order Refund', 'edwiser-bridge-pro' ),
				'content' => $this->get_refund_default_content(),
			);
			return $data;
		}

		/**
		 * Prepares new group created .
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 */
		public function bp_get_group_creation_content( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}

			$data = array(
				'subject' => __( 'New Group Created', 'edwiser-bridge-pro' ),
				'content' => $this->get_new_group_creation_default_content(),
			);
			return $data;
		}

		/**
		 * Add email templates in the EB email list.
		 *
		 * @since 1.1.0
		 *
		 * @param array $list list of the email temaplates.
		 */
		public function eb_templates_list( $list ) {
			$list['eb_emailtmpl_bulk_prod_purchase_notifn']         = __( 'Bulk product purchase(courses)', 'edwiser-bridge-pro' );
			$list['eb_emailtmpl_student_enroll_in_cohort_notifn']   = __( 'User enrolled in group', 'edwiser-bridge-pro' );
			$list['eb_emailtmpl_student_unenroll_in_cohort_notifn'] = __( 'User unenrolled from group', 'edwiser-bridge-pro' );
			$list['eb_emailtmpl_cohort_deletion']                   = __( 'Group Deleted', 'edwiser-bridge-pro' );
			$list['eb_emailtmpl_bulk_refund']                       = __( 'Group Quantity Refund', 'edwiser-bridge-pro' );
			$list['eb_emailtmpl_new_group_creation']                = __( 'New Group Created', 'edwiser-bridge-pro' );
			return $list;
		}

		/**
		 * Add email template constants.
		 *
		 * @since 1.1.0
		 *
		 * @param array $constants array of the email templ constants.
		 */
		public function eb_templates_constants( $constants ) {
			$constants['Bulk product purchase(courses)']['{BULK_ENROL_PAGE_URL}']      = __( 'Enroll Students Page URL', 'edwiser-bridge-pro' );
			$constants['Bulk product purchase(courses)']['{BULK_PRODUCT_LIST}']        = __( 'Name of the products purchased in bulk', 'edwiser-bridge-pro' );
			$constants['Bulk product purchase(courses)']['{BULK_PRODUCTS_COURSES}']    = __( 'List of the courses associated with the product', 'edwiser-bridge-pro' );
			$constants['User enrolled in cohort']['{COHORT_NAME}']                     = __( 'Enrolled courses list', 'edwiser-bridge-pro' );
			$constants['User enrolled in cohort']['{GROUP_NAME}']                      = __( 'Group Name', 'edwiser-bridge-pro' );
			$constants['User enrolled in cohort']['{BULK_ENROLLED_COURSES}']           = __( 'Enrolled courses list', 'edwiser-bridge-pro' );
			$constants['User enrolled in cohort']['{COHORT_MANAGER_DISP_NAME}']        = __( 'Group manager display name', 'edwiser-bridge-pro' );
			$constants['User unenrolled from cohort']['{COHORT_NAME}']                 = __( 'Group name', 'edwiser-bridge-pro' );
			$constants['User unenrolled from cohort']['{GROUP_NAME}']                  = __( 'Group Name', 'edwiser-bridge-pro' );
			$constants['User unenrolled from cohort']['{COHORT_CURRENT_USER_COURSES}'] = __( 'Group unenrolled courses list for users', 'edwiser-bridge-pro' );
			$constants['Group Delete']['{GROUP_NAME}']                                 = __( 'Group Name', 'edwiser-bridge-pro' );
			$constants['Group Quantity Refund']['{BULK_REFUND_TYPE}']                  = __( 'If the order is partial or full refunded', 'edwiser-bridge-pro' );
			$constants['Group Quantity Refund']['{REFUNDED_BULK_QUANTITY}']            = __( 'Refunded Quantity', 'edwiser-bridge-pro' );
			$constants['New Group Created']['{BULK_ENROL_PAGE_URL}']                   = __( 'Enroll Students Page URL', 'edwiser-bridge-pro' );
			$constants['New Group Created']['{NEW_GROUP_PRODUCT_LIST}']                = __( 'Name of the products purchased in bulk', 'edwiser-bridge-pro' );
			$constants['New Group Created']['{NEW_GROUP_PRODUCTS_COURSES}']            = __( 'List of the courses associated with the product', 'edwiser-bridge-pro' );
			$constants['New Group Created']['{NEW_GROUP_NAME}']                        = __( 'Name of the newly created group', 'edwiser-bridge-pro' );
			return $constants;
		}

		/**
		 * Callback for the eb_emailtmpl_content_before filter.
		 *
		 * @param array $data array of the default arguments provided by the send email action
		 * and unparsed content.
		 */
		public function email_template_parser( $data ) {
			$args = $data['args'];
			if ( empty( $args ) || count( $args ) <= 0 ) {
				$args = array(
					'product_id'        => '1',
					'mdl_cohort_id'     => '1',
					'order_id'          => 231,
					'cohort_manager_id' => 1,
				);
			}
			$tmpl_content = $data['content'];
			$tmpl_const   = $this->get_tmpl_constant( $args );
			foreach ( $tmpl_const as $const => $val ) {
				if ( empty( $val ) ) {
					$val = '';
				}
				$tmpl_content = str_replace( $const, $val, $tmpl_content );
			}
			return array(
				'args'    => $args,
				'content' => $tmpl_content,
			);
		}

		/**
		 * Provides the functionality to get the values for the email temaplte constants.
		 *
		 * @param array $args array of the default values for the constants to
		 * prepare the email template content.
		 */
		private function get_tmpl_constant( $args ) {
			$constants['{BULK_ENROL_PAGE_URL}']         = $this->get_bulk_enroll_page_url();
			$constants['{BULK_PRODUCT_LIST}']           = $this->get_product_list( $args );
			$constants['{BULK_PRODUCTS_COURSES}']       = $this->get_products_courses( $args );
			$constants['{BULK_ENROLLED_COURSES}']       = $this->get_cohort_courses( $args );
			$constants['{COHORT_MANAGER_DISP_NAME}']    = $this->get_manager_name( $args );
			$constants['{COHORT_NAME}']                 = $this->get_cohort_name( $args );
			$constants['{COHORT_CURRENT_USER_COURSES}'] = $this->get_cohort_unenrolled_courses( $args );
			$constants['{GROUP_NAME}']                  = $this->get_group_name( $args );
			$constants['{BULK_REFUND_TYPE}']            = $this->get_bulk_refund_type( $args );
			$constants['{REFUNDED_BULK_QUANTITY}']      = $this->get_refunded_qty( $args );
			$constants['{ORDER_ID}']                    = $this->get_order_id( $args );
			$constants['{NEW_GROUP_PRODUCT_LIST}']      = $this->get_new_product_list( $args );
			$constants['{NEW_GROUP_PRODUCTS_COURSES}']  = $this->get_new_products_courses( $args );
			$constants['{NEW_GROUP_NAME}']              = isset( $args['group_name'] ) ? $args['group_name'] : '';
			return $constants;
		}

		/**
		 * Function returns the refund quantity.
		 *
		 * @param array $args array of the email tmpl args.
		 */
		private function get_order_id( $args ) {
			if ( isset( $args['order_id'] ) ) {
				return $args['order_id'];
			}
			return '';
		}

		/**
		 * Function returns the refund quantity.
		 *
		 * @param array $args array of the email tmpl args.
		 */
		private function get_refunded_qty( $args ) {
			if ( isset( $args['refunded_bulk_quantity'] ) ) {
				return $args['refunded_bulk_quantity'];
			}
			return '';
		}

		/**
		 * Function returns the refund type.
		 *
		 * @param array $args array of the email tmpl args.
		 */
		private function get_bulk_refund_type( $args ) {
			if ( isset( $args['bulk_refund_type'] ) ) {
				return $args['bulk_refund_type'];
			}
			return '';
		}

		/**
		 * Function returns the grooup name.
		 *
		 * @param array $args array of the email tmpl args.
		 */
		private function get_group_name( $args ) {
			if ( ! isset( $args['group_name'] ) ) {
				if ( isset( $args['mdl_cohort_id'] ) ) {
					global $wpdb;
					$cohort_id       = $args['mdl_cohort_id'];
					$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
					$name     = $wpdb->get_var( $wpdb->prepare( "SELECT NAME FROM {$tbl_cohort_info} WHERE MDL_COHORT_ID = %s", $cohort_id ) ); // @codingStandardsIgnoreLine
					return $name;
				}
			} elseif ( isset( $args['group_name'] ) && ! empty( $args['group_name'] ) ) {
				return $args['group_name'];
			}
			return '';
		}

		/**
		 * Provides the functionality to get the enrolluser page url.
		 */
		private function get_bulk_enroll_page_url() {
			$general_settings    = get_option( 'eb_general' );
			$bulk_enroll_page_id = isset( $general_settings['mucp_group_enrol_page_id'] ) ? $general_settings['mucp_group_enrol_page_id'] : '';
			return "<a href='" . get_permalink( $bulk_enroll_page_id ) . "'>" . __( 'Enroll Students', 'edwiser-bridge-pro' ) . '</a>';
		}

		/**
		 * Provides the functionality to get the product name by using product id.
		 *
		 * @param array $args default arguments for the send email notification.
		 */
		private function get_product_list( $args ) {
			ob_start();
			?>
			<style>
				.wdm-emial-tbl-body{
					font-family: arial, sans-serif;
					border-collapse: collapse;
					width: 100%;
				}
				.wdm-emial-tbl-body thead{
					background: #b28300;
					color: white;
				}
				.wdm-emial-tbl-body th,
				.wdm-emial-tbl-body td{
					border: 1px solid #000000;
					text-align: left;
					padding: 8px;
					color:black;
				}
				.wdm-emial-tbl-body tbody{
					background: white;
				}
			</style>
			<table border="0" cellspacing="0" class="wdm-emial-tbl-body" style="font-family: arial, sans-serif;border-collapse: collapse;width: 100%;">
				<thead style="background: white;">
					<tr>
						<th style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_html_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_html_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody style="background: white;">
					<?php

					if ( isset( $args['order_id'] ) && '12235' !== $args['order_id'] ) {
						if ( 'shop_order' === OrderUtil::get_order_type( $args['order_id'] ) ) {
							$order = new \WC_Order( $args['order_id'] );
							$items = $order->get_items();
							?>
							<?php
							foreach ( $items as $item_id => $product ) {
								if ( ! $this->check_was_group_purchase( $product, $item_id ) ) {
									continue;
								}
								if ( $product['qty'] > 0 ) {
									?>
									<tr>
										<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php echo esc_html( get_the_title( $product['product_id'] ) ); ?></td>
										<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php echo esc_html( $product['qty'] ); ?></td>
									</tr>
									<?php
								}
							}
						}
					} else {
						?>
						<tr>
							<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_attr_e( 'Test Product', 'edwiser-bridge-pro' ); ?></td>
							<td style="border: 1px solid #000000;text-align: left;padding: 8px;">5</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<?php
			return ob_get_clean();
		}

		/**
		 * Provides the functionality to get the product name by using product id for new;y created group.
		 *
		 * @param array $args default arguments for the send email notification.
		 */
		private function get_new_product_list( $args ) {
			ob_start();
			?>
			<style>
				.wdm-emial-tbl-body{
					font-family: arial, sans-serif;
					border-collapse: collapse;
					width: 100%;
				}
				.wdm-emial-tbl-body thead{
					background: #b28300;
					color: white;
				}
				.wdm-emial-tbl-body th,
				.wdm-emial-tbl-body td{
					border: 1px solid #000000;
					text-align: left;
					padding: 8px;
					color:black;
				}
				.wdm-emial-tbl-body tbody{
					background: white;
				}
			</style>
			<table border="0" cellspacing="0" class="wdm-emial-tbl-body" style="font-family: arial, sans-serif;border-collapse: collapse;width: 100%;">
				<thead style="background: white;">
					<tr>
						<th style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_html_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_html_e( 'Quantity', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody style="background: white;">
					<?php

					if ( isset( $args['products'] ) ) {
						$products = $args['products'];
						foreach ( $products as $product => $quantity ) {
							?>
							<tr>
								<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php echo esc_html( get_the_title( $product ) ); ?></td>
								<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php echo esc_html( $quantity ); ?></td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_attr_e( 'Test Product', 'edwiser-bridge-pro' ); ?></td>
							<td style="border: 1px solid #000000;text-align: left;padding: 8px;">5</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<?php
			return ob_get_clean();
		}

		/**
		 * Provides the functionality to get the product associated courses by
		 * using product id.
		 *
		 * @param array $args default arguments for the send email notification.
		 */
		private function get_products_courses( $args ) {
			if ( ! isset( $args['order_id'] ) || '12235' === $args['order_id'] ) {
				return '';
			}
			$order_id = $args['order_id'];
			
			if ( 'shop_order' === OrderUtil::get_order_type( $args['order_id'] ) ) {
				$order    = new \WC_Order( $order_id );
				$products = $order->get_items();
				$data     = '<div>';

				foreach ( $products as $item_id => $product ) {
					$prod_id  = $product['product_id'];
					$_product = wc_get_product( $prod_id );

					if ( ! $this->check_was_group_purchase( $product, $item_id ) ) {
						continue;
					}
					if ( $_product && $_product->is_type( 'variable' ) && isset( $product['variation_id'] ) ) {
						$prod_id = $product['variation_id'];
					}

					$prod_name = $product['name'];
					$courses   = get_post_meta( $prod_id, 'product_options', true );
					$data     .= "<div><p><strong>$prod_name</strong></p><ol>";
					foreach ( $courses['moodle_post_course_id'] as $course_id ) {
						$data .= '<li><a href=' . get_permalink( $course_id ) . '>' . get_the_title( $course_id ) . '</a></li>';
					}
					$data .= '</ol></div>';
				}
				$data .= '</div>';
				return $data;
			}
		}

		/**
		 * Provides the functionality to get the product associated courses by
		 * using product id.
		 *
		 * @param array $args default arguments for the send email notification.
		 */
		private function get_new_products_courses( $args ) {

			if ( isset( $args['courses'] ) ) {
				$data  = '<div>';
				$data .= '<div><ol>';
				foreach ( $args['courses'] as $course_id ) {
					$data .= '<li><a href=' . get_permalink( $course_id ) . '>' . get_the_title( $course_id ) . '</a></li>';
				}
				$data .= '</ol></div>';
				$data .= '</div>';
				return $data;
			}
		}

		/**
		 * Function checks is the group purchase order or not.
		 *
		 * @param object $product object of the woocomerce product.
		 * @param int    $item_id id of the cart item.
		 */
		private function check_was_group_purchase( $product, $item_id ) {
			$is_gp     = true;
			$prod_name = $product['name'];
			$prod_id   = $product['product_id'];

			$_product = wc_get_product( $prod_id );
			if ( $_product && $_product->is_type( 'variable' ) && isset( $product['variation_id'] ) ) {
				// The line item is a variable product, so consider its variation.
				$prod_id = $product['variation_id'];
			}

			$courses = get_post_meta( $prod_id, 'product_options', true );
			if ( ! isset( $courses['moodle_post_course_id'] ) || count( $courses['moodle_post_course_id'] ) < 1 ) {
				$is_gp = false;
			}

			if ( ! isset( $courses['moodle_course_group_purchase'] ) || 'on' !== $courses['moodle_course_group_purchase'] ) {
				$is_gp = false;
			}

			$order_meta = wc_get_order_item_meta( $item_id, 'Group Enrollment' );

			if ( 'no' === $order_meta ) {
				$is_gp = false;
			}
			return $is_gp;
		}

		/**
		 * Provides the functionality to get the chohort associated courses by
		 * using cohort id.
		 *
		 * @param array $args default arguments for the send email notification.
		 */
		private function get_cohort_courses( $args ) {
			if ( ! isset( $args['mdl_cohort_id'] ) ) {
				return '';
			}
			$cohort_id = $args['mdl_cohort_id'];
			global $wpdb;
			$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
			$results         = $wpdb->get_row( $wpdb->prepare( "select COURSES from {$tbl_cohort_info} where MDL_COHORT_ID=%s", $cohort_id ) ); // @codingStandardsIgnoreLine
			// v1.1.1.
			$results = maybe_unserialize( $results->COURSES ); // @codingStandardsIgnoreLine.
			$out_put = '<div><ul>';
			foreach ( $results as $course_id ) {
				$out_put .= '<li>' . get_the_title( $course_id ) . '</li>';
			}
			$out_put .= '</ul></div>';
			return $out_put;
		}

		/**
		 * Provides the functionality to get the chohort manager display name
		 * using cohort manager id.
		 *
		 * @param type $args default arguments for the send email notification.
		 */
		private function get_manager_name( $args ) {
			if ( ! isset( $args['cohort_manager_id'] ) ) {
				return '';
			}
			$manager_id = $args['cohort_manager_id'];
			$manager    = get_userdata( $manager_id );
			return $manager->display_name;
		}

		/**
		 * Provides the functionality to get the chohort name.
		 *
		 * @param type $args default arguments for the send email notification.
		 */
		private function get_cohort_name( $args ) {
			if ( isset( $args['mdl_cohort_id'] ) ) {
				global $wpdb;
				$cohort_id       = $args['mdl_cohort_id'];
				$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
				$cohort_name     = $wpdb->get_var( $wpdb->prepare( "SELECT COHORT_NAME FROM {$tbl_cohort_info} WHERE MDL_COHORT_ID = %s", $cohort_id ) ); // @codingStandardsIgnoreLine
				$manager_name    = get_userdata( $args['cohort_manager_id'] );
				return str_replace( $manager_name->user_login . '_', '', $cohort_name );
			}
			return 'cohort';
		}

		/**
		 * Provides the functionality to get the chohort associated courses by
		 * using cohort id
		 *
		 * @param type $args default arguments for the send email notification.
		 */
		private function get_cohort_unenrolled_courses( $args ) {
			if ( ! isset( $args['mdl_cohort_id'] ) ) {
				return '';
			}
			$cohort_id = $args['mdl_cohort_id'];
			global $wpdb;
			$tbl_cohort_info = $wpdb->prefix . 'bp_cohort_info';
			$results         = $wpdb->get_row( $wpdb->prepare( "select COURSES AS courses from {$tbl_cohort_info} where MDL_COHORT_ID=%s", $cohort_id ) ); // @codingStandardsIgnoreLine
			// v1.1.1.
			$results = maybe_unserialize( $results->courses ); // @codingStandardsIgnoreLine.
			$out_put = '<div><ol>';
			foreach ( $results as $course_id ) {
				$out_put .= '<li>' . get_the_title( $course_id ) . '</li>';
			}
			$out_put .= '</ol></div>';
			return $out_put;
		}


		/**
		 * Function returns the defualt template for the refund email notification .
		 */
		public function get_refund_default_content() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; padding: 70px 70px 70px 70px; margin: auto; height: auto;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 1px 2px 0px 1px #d0d0d0; border-radius: 6px !important; background-color: #dfdfdf; margin: auto;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #465c94; border-radius: 6px 6px 0px 0px; border-bottom: 0; font-family: Arial;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_attr_e( 'Your order {ORDER_ID} has been {BULK_REFUND_TYPE} refunded.', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Hello {FIRST_NAME} {LAST_NAME},', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'This is to inform you that, The quantity {REFUNDED_BULK_QUANTITY} of group {GROUP_NAME} has been refunded successfully, by {SITE_NAME}.', 'edwiser-bridge-pro' ); ?>
								</div>
								<div></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Order Details:', 'edwiser-bridge-pro' ); ?>
								</div>
								<div></div>
								<div style="font-family: Arial;">
									<table style="border-collapse: collapse;">
										<tbody>
											<tr style="border: 1px solid #465b94; padding: 5px;">
												<td style="border: 1px solid #465b94; padding: 5px;">
													<?php esc_attr_e( 'Order Item', 'edwiser-bridge-pro' ); ?>
												</td>
												<td style="border: 1px solid #465b94; padding: 10px;">
													{ORDER_ID}
												</td>
											</tr>
											<tr style="border: 1px solid #465b94; padding: 5px;">
												<td style="border: 1px solid #465b94; padding: 5px;">
													<?php esc_attr_e( 'Current Refunded Quantity', 'edwiser-bridge-pro' ); ?>
												</td>
												<td style="border: 1px solid #465b94; padding: 10px;">
													{REFUNDED_BULK_QUANTITY}
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
							</td>
						</tr>
						<tr>
							<td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Function returns the defualt template for the cohort deletetion email notification .
		 */
		public function get_cohort_deletion_default_content() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #465c94; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_attr_e( 'Group Deleted', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Hi {FIRST_NAME}', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
										printf(
											/* translators: 1: Group name, 2: Site Name. */
											esc_attr__( 'Your Group %1$s is deleted from %2$s.', 'edwiser-bridge-pro' ),
											'<strong>{GROUP_NAME}</strong>',
											'<strong>{SITE_URL}</strong>'
										);
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>

							</td>
						</tr>
						<tr>
							<td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * Function returns the defualt template for the bulk purachse email notification .
		 */
		private function get_bulk_prod_purchase_default_content() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #1f397d; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_attr_e( 'Start enrolling students in courses', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Hi {FIRST_NAME},', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Thank you for purchasing bulk products.', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{BULK_PRODUCT_LIST}';
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Associated courses :', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{BULK_PRODUCTS_COURSES}';
									?>
								</div>

								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									/*
									* translators: Page url.
									*/
									printf( esc_attr__( 'You can enroll students in the purchased products from %s.', 'edwiser-bridge-pro' ), '<span style="color: #0000ff;">{BULK_ENROL_PAGE_URL}</span>' );
									?>
								</div>
							</td>
						</tr>
						<tr>
							<td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			$content = ob_get_clean();

			return apply_filters( 'mucp_bulk_prod_purchase_content', $content );
		}

		/**
		 * Function returns the defualt template for the bulk purachse email notification .
		 */
		private function get_new_group_creation_default_content() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #1f397d; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_attr_e( 'Start enrolling students in courses', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Hi {FIRST_NAME},', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									/*
									* translators: Page url.
									*/
									printf( esc_attr__( 'We\'ve created a new group purchase group called "%s".', 'edwiser-bridge-pro' ), '<span>{NEW_GROUP_NAME}</span>' );
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'The products associated with this group are :', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{NEW_GROUP_PRODUCT_LIST}';
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'The courses associated with this group are :', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{NEW_GROUP_PRODUCTS_COURSES}';
									?>
								</div>

								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									/*
									* translators: Page url.
									*/
									printf( esc_attr__( 'To enroll students, please use this link %s.', 'edwiser-bridge-pro' ), '<span style="color: #0000ff;">{BULK_ENROL_PAGE_URL}</span>' );
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'If you have any questions or concerns about this group purchase group or the enrollment process, feel free to reach out to us.', 'edwiser-bridge-pro' ); ?>
								</div>
							</td>
						</tr>
						<tr>
							<td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			$content = ob_get_clean();

			return apply_filters( 'mucp_group_creation_content', $content );
		}

		/**
		 * Function returns the defualt template for the cohort enrollment email notification .
		 */
		private function get_enrol_students_in_cohort_default_content() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #1f397d; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_attr_e( 'You have been successfully enrolled in {GROUP_NAME}', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Hi {FIRST_NAME},', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'You have been enrolled by {COHORT_MANAGER_DISP_NAME} to courses', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{BULK_ENROLLED_COURSES}';
									?>
								</div>

								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									/*
									* translators: course page Url.
									*/
									printf( esc_attr__( 'You can access your courses from %s.', 'edwiser-bridge-pro' ), '<span style="color: #0000ff;">{MY_COURSES_PAGE_LINK}</span>' );
									?>
								</div>
							</td>
						</tr>
						<tr>
							<td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			$content = ob_get_clean();
			return apply_filters( 'mucp_student_enroll_in_cohort_content', $content );
		}

		/**
		 * Function returns the defualt template for the user unenrollemnt from cohort email notification .
		 */
		private function get_unnrol_students_incohort_default_content() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #1f397d; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_attr_e( 'You have been unenrolled from {GROUP_NAME}', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'Hi {FIRST_NAME},', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php esc_attr_e( 'You have been unenrolled by {COHORT_MANAGER_DISP_NAME} from courses', 'edwiser-bridge-pro' ); ?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{COHORT_CURRENT_USER_COURSES}';
									?>
								</div>
							</td>
						</tr>
						<tr>
							<td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			$content = ob_get_clean();
			return apply_filters( 'mucp_student_unenroll_in_cohort_content', $content );
		}
	}
}

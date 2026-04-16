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

if ( ! class_exists( 'Bridge_Woo_Email_Template_Manager' ) ) {

	/**
	 * Woo int template manager class
	 */
	class Bridge_Woo_Email_Template_Manager {

		/**
		 * Parse the email template content
		 *
		 * @param array $args contains the tmpl_name( Key ) and boolean value to restore the template or not.
		 */
		public function wdm_parse_email_template( $args ) {
			return $this->handle_template_restore( $args );
		}

		/**
		 * Provides the functionality to handle the tempalte restore event
		 * genrated by the Edwiser bridge plugin for the template.
		 * Calles for the eb_reset_email_tmpl_content filter
		 *
		 * @param array $args contains the tmpl_name( Key ) and boolean value to restore the template or not.
		 * @return array of the tmpl_name( Key ) and is_restored( boolean on sucessfull restored true, false othrewise. )
		 */
		public function handle_template_restore( $args ) {
			$tmpl_key = $args['tmpl_name'];
			switch ( $tmpl_key ) {
				case 'eb_emailtmpl_woocommerce_moodle_course_notifn':
					$value = $this->get_woo_int_default_notification( 'eb_emailtmpl_woocommerce_moodle_course_notifn', true );
					break;
				default:
					return $args;
			}
			$status = update_option( $tmpl_key, $value );
			if ( $status ) {
				$args['is_restored'] = true;
				return $args;
			} else {
				return $args;
			}
		}

		/**
		 * Prepares the course enrollment email notification template content
		 *
		 * @param string  $tmpl_id template key.
		 * @param boolean $restore true to restore the templates default contend by default false.
		 * @return array array of template subject and content.
		 */
		public function get_woo_int_default_notification( $tmpl_id, $restore = false ) {
			$data = get_option( $tmpl_id );
			if ( $data && ! $restore ) {
				return $data;
			}
			$data = array(
				'subject' => __( 'Moodle Course Enrollment.', 'edwiser-bridge-pro' ),
				'content' => $this->get_woo_int_mail_default_body(),
			);
			return $data;
		}

		/**
		 * Prepares the woocommerce moodle product purchase email body.
		 *
		 * @return html woocommerce moodle product purchase email template
		 */
		private function get_woo_int_mail_default_body() {
			ob_start();
			?>
			<div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
				<table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba( 0,0,0,0.025 ) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td style="background-color: #1f397d; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
								<h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
									<?php esc_html_e( 'Course Enrollment.', 'edwiser-bridge-pro' ); ?>
								</h1>
							</td>
						</tr>
						<tr>
							<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									printf(
										esc_html__( 'Hi %s', 'edwiser-bridge-pro' ), // @codingStandardsIgnoreLine
										'{FIRST_NAME}'
									);
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left; margin-top:3%; margin-bottom:3%;">
									<?php
									esc_html_e( 'Thank you for your order. You have been successfully enrolled in the following courses.', 'edwiser-bridge-pro' );
									?>
								</div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
								<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
									<?php
									echo '{PRODUCT_LIST}';
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

			return apply_filters( 'eb_woo_int_course_enroll_email_content', $content );
		}
		/**
		 * Add email templates in the EB email list.
		 *
		 * @param array $list array of the email templates.
		 * @since 1.1.0
		 */
		public function eb_templates_list( $list ) {
			$list['eb_emailtmpl_woocommerce_moodle_course_notifn'] = __( 'Woocommerce Moodle Course Enrollment', 'edwiser-bridge-pro' );
			return $list;
		}

		/**
		 * Add email template constants.
		 *
		 * @param array $constants array of the email template constants.
		 * @since 1.1.0
		 */
		public function eb_templates_constants( $constants ) {
			$constants['Woocommerce Moodle Course Enrollment']['{PRODUCT_LIST}'] = __( 'Products List', 'edwiser-bridge-pro' );
			return $constants;
		}

		/**
		 * Callback for the eb_emailtmpl_content_before filter
		 *
		 * @param array $data array of the default arguments provided by the send email action and unparsed content.
		 * @return array returns the array of the default arguments and parsed content
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
		 * Provides the functionality to get the values for the email temaplte constants
		 *
		 * @param array $args array of the default values for the constants to prepare the email template content.
		 *
		 * @return array returns the array of the email temaplte constants with
		 * associated values for the constants
		 */
		private function get_tmpl_constant( $args ) {
			$constants['{PRODUCT_LIST}'] = $this->get_product_list( $args );
			return $constants;
		}
		/**
		 * Provides the functionality to get the product name by using product id
		 *
		 * @param array $args default arguments for the send email notification.
		 * @return string returns the product id
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
					background: #1f397d;
					color: white;
				}
				.wdm-emial-tbl-body th,
				.wdm-emial-tbl-body td{
					border: 1px solid #000000;
					text-align: left;
					padding: 8px;
				}
				.wdm-emial-tbl-body tbody{
					background: white;
				}
			</style>
			<table border="0" cellspacing="0" class="wdm-emial-tbl-body" style="font-family: arial, sans-serif;border-collapse: collapse;width: 100%;border: 1px solid gray;">
				<thead style="background: #1f397d;color: white;">
					<tr>
						<th style="text-align: left;padding: 8px;"><?php esc_html_e( 'Product Name', 'edwiser-bridge-pro' ); ?></th>
						<th style="text-align: left;padding: 8px;"><?php esc_html_e( 'Associated Courses', 'edwiser-bridge-pro' ); ?></th>
					</tr>
				</thead>
				<tbody style="background: white;">
					<?php
					if ( isset( $args['order_id'] ) && '12235' !== $args['order_id'] ) {
						$order = new \WC_Order( $args['order_id'] );
						$items = $order->get_items();

						foreach ( $items as $item_id => $prop ) {

							$prod_id  = $prop->get_product_id();
							$_product = wc_get_product( $prod_id );

							if ( $_product && $_product->is_type( 'variable' ) && isset( $prop['variation_id'] ) ) {
								$prod_id = $prop['variation_id'];
							}
							$courses = get_post_meta( $prod_id, 'product_options', true );
							?>
							<tr>
								<td style="text-align: left;padding: 8px;vertical-align: top;"><?php echo esc_html( get_the_title( $prod_id ) ); ?></td>
								<td style="text-align: left;padding: 8px;">
									<ul type="disc">
									<?php
									if ( isset( $courses['moodle_post_course_id'] ) ) {

										foreach ( $courses['moodle_post_course_id'] as $course_id ) {
											?>
											<li><?php echo esc_html( get_the_title( $course_id ) ); ?></li>
											<?php
										}
									}
									?>
									</ul>
								</td>
							</tr>
							<?php
						}
						?>
						<?php
					} else {
						?>
						<tr>
							<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_html_e( 'Test Product', 'edwiser-bridge-pro' ); ?></td>
							<td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php esc_html_e( 'Test Course', 'edwiser-bridge-pro' ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
			<?php
			return ob_get_clean();
		}
	}
}

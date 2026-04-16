<?php
/**
 * SSO Module
 *
 * @link       http://edwiser.org
 * @since      3.0.0
 * @package    Edwiser Bridge Pro
 */

namespace app\wisdmlabs\edwiserBridgePro\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Sso_Settings_Redirection' ) ) {

	/**
	 * SSO Settings.
	 */
	class Sso_Settings_Redirection {

		/**
		 * Function provides the functionality to send the
		 */
		public function get_user_redirection_settings() {
			global $current_tab;
			$settings = get_option( 'eb_sso_settings_redirection' );
			$this->get_default_red_settings( $settings );
			$class = 'ebsso-hide';
			if ( isset( $settings['ebsso_role_base_redirect'] ) && 'on' === $settings['ebsso_role_base_redirect'] ) {
				$class = '';
			}
			?>

			<div id="ebsso-role-redirect-setting-block" class="<?php echo esc_attr( $class ); ?>">
				<?php
				$this->role_settings( $settings );
				?>
			</div>
			<?php
		}

		/**
		 * Function defines the default column name and provides the filter to add or modify colum rows.
		 */
		private function get_role_based_settings_col_names() {
			$heders = array( __( 'User Roles', 'edwiser-bridge-pro' ), __( 'Redirect', 'edwiser-bridge-pro' ), __( 'Manage', 'edwiser-bridge-pro' ) );
			return apply_filters( 'eb_sso_settings_role_redirect_table_headers', $heders );
		}

		/**
		 * Function provides the functionality to display the default redirection settings.
		 *
		 * @param array $settings settings array.
		 */
		private function get_default_red_settings( $settings ) {
			$def_redi_url    = isset( $settings['ebsso_login_redirect_url'] ) ? $settings['ebsso_login_redirect_url'] : '';
			$role_base_redir = '';
			if ( isset( $settings['ebsso_role_base_redirect'] ) && 'on' === $settings['ebsso_role_base_redirect'] ) {
				$role_base_redir = 'checked';
			}
			$default_login = isset( $settings['ebsso_login_page'] ) ? $settings['ebsso_login_page'] : '';
			$dropdown_args = array(
				'name'             => 'ebsso_login_page',
				'id'               => 'ebsso_login_page',
				'sort_column'      => 'menu_order',
				'sort_order'       => 'ASC',
				'show_option_none' => __( 'Select a page', 'edwiser-bridge-pro' ),
				'class'            => '',
				'echo'             => false,
				'selected'         => absint( $default_login ),
			);
			?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ebsso_login_redirect_url"><?php esc_html_e( 'Common Login Redirect URL', 'edwiser-bridge-pro' ); ?></label>
						</th>
						<td class="forminp forminp-url">
							<input name="ebsso_login_redirect_url" id="ebsso_login_redirect_url" type="url" value='<?php echo esc_url( $def_redi_url ); ?>' placeholder="e.g. http://mymoodle.com/my/">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ebsso_login_page"><?php esc_html_e( 'Default Login Page', 'edwiser-bridge-pro' ); ?></label>
						</th>
						<td class="forminp forminp-url">
							<?php
								echo wp_kses( str_replace( ' id=', " data-placeholder='" . __( 'Select a page', 'edwiser-bridge-pro' ) . "' id=", wp_dropdown_pages( $dropdown_args ) ), \app\wisdmlabs\edwiserBridge\wdm_eb_get_allowed_html_tags() );
							?>
							<span class="description"><br/><?php esc_html_e( 'Default Login Page for Login with WordPress button on moodle. Default page is ', 'edwiser-bridge-pro' ); ?><a href="<?php echo esc_url( site_url( '/user-account' ) ); ?>"><?php esc_html_e( 'User Account', 'edwiser-bridge-pro' ); ?></a></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<?php esc_html_e( 'Enable user role based redirect', 'edwiser-bridge-pro' ); ?>
						</th>
						<td class="forminp forminp-checkbox">
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'Enable user role based redirect', 'edwiser-bridge-pro' ); ?></span>
								</legend>
								<label for="ebsso_role_base_redirect">
									<input name="ebsso_role_base_redirect" id="ebsso_role_base_redirect" type="checkbox" value="on" <?php echo esc_attr( $role_base_redir ); ?>>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
			<?php
		}

		/**
		 * This will display the role based redirection settings.
		 *
		 * @param array $settings contains the old settings for the SSO plugin redirection.
		 */
		private function role_settings( $settings ) {
			$exisiting_rules    = $this->get_existing_redir_rules( $settings );
			$roles              = $this->get_user_roles( $exisiting_rules );
			$roles_to_add_rules = $roles;
			?>
			<h3 class="ebsso-role-redirect-settings">
				<?php esc_html_e( 'User Role Based Redirection Settings', 'edwiser-bridge-pro' ); ?>
			</h3>
			<?php
			$this->create_role_redi_rule( $roles_to_add_rules, 'top' );
			$this->show_set_role_redirections( $exisiting_rules );
			$this->create_role_redi_rule( $roles_to_add_rules, 'bottom' );
		}

		/**
		 * Function filters the roles and associated urls stored in the plugin settings.
		 *
		 * @param array $settings contains the old settings for the SSO plugin redirection.
		 * @return returns the array of the old rules.
		 */
		private function get_existing_redir_rules( $settings ) {

			$redirect_data = array();
			if ( is_array( $settings ) ) {
				foreach ( $settings as $key_id => $value ) {
					if ( strpos( $key_id, 'ebsso_login_redirect_url_' ) !== false ) {
						$key                   = str_replace( 'ebsso_login_redirect_url_', '', $key_id );
						$redirect_data[ $key ] = $value;
					}
				}
			}
			return $redirect_data;
		}

		/**
		 * Function provides the functionality to get the all the roles
		 * available on the system and remove the previously added roles from the output array.
		 *
		 * @global Object $wp_roles This is the global variable defined for the user roles by WP.
		 * @param array $exisiting_rules The array of the roles previously added in setting.
		 * @return array array of the role names where roles are not associated with the url in the sso settings.
		 */
		private function get_user_roles( $exisiting_rules ) {

			global $wp_roles;
			$roles = $wp_roles->get_names();
			if ( ! is_array( $exisiting_rules ) ) {
				return $roles;
			}
			foreach ( $exisiting_rules as $key => $value ) {
				if ( isset( $roles[ $key ] ) ) {
					unset( $roles[ $key ] );
				}
				unset( $value );
			}
			return $roles;
		}

		/**
		 * Function provides the functionality to generate the view to add the new user role
		 *  and redirection rule for it.
		 *
		 * @param array  $add_rules_for_role This is the set of the user roles to add new rules.
		 * @param String $pos This is the position of the view where it will be displayed like top/bottum.
		 * This will be used for the identifying the element of the view.
		 */
		private function create_role_redi_rule( $add_rules_for_role, $pos ) {
			$url_in_id = 'ebsso_selected_login_redirect_url_' . $pos;
			$btn_id    = 'ebsso_add_role_setting_' . $pos;
			?>
			<div class="ebsso-setting-red-rule">
				<?php $this->role_selector( $add_rules_for_role, $pos ); ?>
				<label class="ebsso-setting-filed-lbl"><?php esc_html_e( 'URL', 'edwiser-bridge-pro' ); ?></label>
				<input name="<?php echo esc_attr( $url_in_id ); ?>" id="<?php echo esc_attr( $url_in_id ); ?>" type="url" class="ebsso-role-redi-new-setting-url" placeholder="e.g. http://mymoodle.com/my/">
				<input type="button" id="<?php echo esc_attr( $btn_id ); ?>" name="<?php echo esc_attr( $btn_id ); ?>" class="ebsso-add-new-redirect-rule" value="<?php echo esc_html_e( 'Add', 'edwiser-bridge-pro' ); ?>">
				<span class="ebsso-error" style="color:red"></span>
			</div>
			<?php
		}

		/**
		 * Create the select box for the given roles.
		 *
		 * @param array  $add_rules_for_role Roles to add in the select options.
		 * @param String $pos Position of the select box.
		 */
		private function role_selector( $add_rules_for_role, $pos ) {
			?>
			<label class="ebsso-setting-filed-lbl"><?php esc_html_e( 'Select user role', 'edwiser-bridge-pro' ); ?></label>
			<select name="ebsso-role-<?php echo esc_attr( $pos ); ?>" class="ebsso-role-redi-new-setting-role" id="ebsso-role-<?php echo esc_attr( $pos ); ?>">
				<option value=""><?php esc_html_e( 'Select Role', 'edwiser-bridge-pro' ); ?></option>
				<?php
				foreach ( $add_rules_for_role as $key => $value ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $value ); ?>
					</option>
					<?php
				}
				?>
			</select>
			<?php
		}

		/**
		 * Provides the functionality to display the user roles table.
		 *
		 * @param array $rules_to_display array of the roles where URL are set.
		 */
		private function show_set_role_redirections( $rules_to_display ) {
			$header = $this->get_role_based_settings_col_names();
			?>
			<table id='ebsso-tbl-role-redirect-rule' class='ebsso-role-redirect-settings wp-list-table widefat fixed striped posts'>
				<thead>
					<?php $this->get_table_heading_row( $header ); ?>
				</thead>
				<tbody class='role-table-row'>
					<?php $this->create_table_body( $rules_to_display ); ?>
				</tbody>
				<tfoot>
					<?php $this->get_table_heading_row( $header ); ?>
				</tfoot>
			</table>
			<?php
		}

		/**
		 * Creates the table header
		 *
		 * @param array $heders This is the array of the roles table column.
		 */
		private function get_table_heading_row( $heders = array() ) {
			?>
			<tr>
				<?php
				foreach ( $heders as $lable ) {
					$this->get_table_herader_tag( $lable );
				}
				?>
			</tr>
			<?php
		}

		/**
		 * Creates the table heading tag.
		 *
		 * @param String $lable Table column heading text.
		 */
		private function get_table_herader_tag( $lable ) {
			?>
			<th scope="col" class="manage-column column-title column-primary">
				<span><?php echo $lable ; // @codingStandardsIgnoreLine?></span>
			</th>
			<?php
		}

		/**
		 * Function provides the functionality to display the roles table body.
		 *
		 * @param array $roles_redir_rules  Array of the rules.
		 */
		private function create_table_body( $roles_redir_rules ) {
			global $wp_roles;
			$all_roles = $wp_roles->get_names();

			foreach ( $roles_redir_rules as $role_id => $role ) {
				$role_disp_name = $all_roles[ $role_id ]
				?>
				<tr id="<?php echo esc_html( 'ebsso_login_redirect_row_' . $role_id ); ?>">
					<?php $this->create_table_row( $role_disp_name, $role_id, $role ); ?>
				</tr>
				<?php
			}
		}

		/**
		 * Creates the table row cells elements.
		 *
		 * @param String $role_disp_name role name to display.
		 * @param String $role_id user role id.
		 * @param String $redirect_url redirect URL for the role.
		 */
		private function create_table_row( $role_disp_name, $role_id, $redirect_url ) {
			$field_id = 'ebsso_login_redirect_url_' . $role_id;
			do_action( 'ebsso_settings_at_redir_row_start', $role_id );
			?>
			<td class="ebsso-setting-filed-lbl">
				<?php echo esc_html( $role_disp_name ); ?>
			</td>
			<td>
				<input type="url" name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_url( $redirect_url ); ?>"/>
			</td>
			<td>
				<input type="button" data-name='<?php echo esc_attr( $role_disp_name ); ?>' data-text="<?php echo esc_attr( $role_id ); ?>" class="ebsso-edit-manage-redirect-rule" name="<?php echo esc_attr( $field_id ) . '-btn'; ?>" id="<?php echo esc_attr( $field_id ) . '-btn'; ?>" value="<?php esc_html_e( 'Delete', 'edwiser-bridge-pro' ); ?>" class="eb-sso-btn-dele-redire-setting"/>
				<?php do_action( 'ebsso_settings_tbl_redir_more_row_action', $role_id ); ?>
			</td>
			<?php
			do_action( 'ebsso_settings_at_redir_row_end', $role_id );
		}
	}
}

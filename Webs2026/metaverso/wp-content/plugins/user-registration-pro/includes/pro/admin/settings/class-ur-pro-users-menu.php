<?php
/**
 * UserRegistration Pro Users Menu class.
 *
 * @package  UserRegistration/Admin
 * @author   WPEverest
 *
 * @since 4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'User_Registration_Pro_Users_Menu' ) ) {
	/**
	 * User_Registration_Pro_Users_Menu class.
	 */
	class User_Registration_Pro_Users_Menu {
		/**
		 * Errors attribute.
		 *
		 * @var [array]
		 */
		private $errors;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_users_menu_tab' ), 60 );
			add_filter( 'manage_user-registration_page_user-registration-users_columns', array( $this, 'get_column_headers' ) );
			add_filter( 'user_registration_users_table_column_headers', array( $this, 'add_form_fields_columns' ), 10, 1 );

			add_action( 'load-user-registration_page_user-registration-users', array( $this, 'add_screen_options' ) );
			add_filter( 'set_screen_option_user_registration_page_user_registration_users_per_page', array( $this, 'save_users_per_page_screen_option' ), 10, 3 );

			add_filter( 'bulk_actions-user-registration_page_user-registration-users', array( $this, 'manage_bulk_action_items' ) );
			add_action( 'admin_init', array( $this, 'handle_actions' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

			add_action( 'admin_notices', array( $this, 'handle_redirect_notices' ) );
		}

		/**
		 * Add admin scripts and styles.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function admin_scripts() {

			wp_enqueue_script(
				'user-registration-pro-users',
				plugins_url( '/assets/js/pro/admin/user-registration-pro-users-script.js', UR_PLUGIN_FILE ),
				array( 'jquery', 'sweetalert2' ),
				UR_VERSION,
				true
			);

			wp_enqueue_style( 'sweetalert2' );

			wp_enqueue_style( 'user-registration-pro-admin-style' );
			wp_enqueue_style( 'user-registration-pro-frontend-style' );

			wp_localize_script(
				'user-registration-pro-users',
				'urUsersl10n',
				array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'change_column_nonce' => wp_create_nonce( 'ur-users-column-change' ),
					'delete_prompt'       => array(
						'icon'                   => plugins_url( 'assets/images/users/delete-user-red.svg', UR_PLUGIN_FILE ),
						'title'                  => __( 'Delete User', 'user-registration' ),
						'confirm_message_single' => __( 'Are you sure you want to delete this user?', 'user-registration' ),
						'confirm_message_bulk'   => __( 'Are you sure you want to delete these users?', 'user-registration' ),
						'warning_message'        => __( 'All the user data and files will be permanently deleted.', 'user-registration' ),
						'delete_label'           => __( 'Delete', 'user-registration' ),
						'cancel_label'           => __( 'Cancel', 'user-registration' ),
					),
				)
			);
		}

		/**
		 * Add Users submenu to User Registration Menus.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function add_users_menu_tab() {
			add_submenu_page(
				'user-registration',
				__( 'User Registration Users', 'user-registration' ),
				__( 'Users', 'user-registration' ),
				'manage_user_registration',
				'user-registration-users',
				array(
					$this,
					'render_users_page',
				)
			);
		}

		/**
		 * Render the contents of Users page.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function render_users_page() {

			if ( ! current_user_can( 'list_users' ) ) {
				wp_die(
					'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to list users.' ) . '</p>',
					403
				);
			}

			if ( isset( $_GET['view_user'] ) && isset( $_GET['user_id'] ) ) {
				$this->render_single_user_details();
				return;
			}

			add_screen_option( 'per_page' );

			include_once UR_ABSPATH . 'includes/pro/admin/settings/class-ur-pro-users-list-table.php';

			$list_table = new User_Registration_Pro_Users_List_Table();

			$list_table->prepare_items();
			?>
			<div class="ur-admin-page-topnav" id="ur-users-page-topnav">
				<div class="ur-page-title__wrapper">
					<span class="ur-back-button" title="<?php esc_attr_e( 'Back to Previous Page', 'user-registration' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 25">
							<path stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 18.5-6-6 6-6"/>
						</svg>
					</span>
					<h1 class="ur-page-title">
						<?php esc_html_e( 'User Listing', 'user-registration' ); ?>
					</h1>
				</div>
				<div class="ur-page-actions">
					<button id="ur-users-page-settings-button" class="ur-button-primary" title="<?php esc_html_e( 'Screen Options', 'user-registration' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<g clip-path="url(#a)">
							<path fill="#fff" fill-rule="evenodd" d="M11.293 2.293A1 1 0 0 1 13 3v.094a2.65 2.65 0 0 0 1.601 2.423 2.65 2.65 0 0 0 2.918-.532l.008-.008.06-.06a1 1 0 0 1 1.416 0 .999.999 0 0 1 0 1.415l-.06.06-.008.009a2.65 2.65 0 0 0-.607 2.729c.012.09.037.18.073.264A2.65 2.65 0 0 0 20.826 11H21a1 1 0 0 1 0 2h-.094a2.65 2.65 0 0 0-2.423 1.601 2.65 2.65 0 0 0 .532 2.918l.008.008.06.06a1 1 0 0 1 0 1.416 1 1 0 0 1-1.415 0l-.06-.06-.009-.008a2.651 2.651 0 0 0-2.918-.532 2.65 2.65 0 0 0-1.601 2.423V21a1 1 0 0 1-2 0v-.113a2.65 2.65 0 0 0-1.705-2.415 2.651 2.651 0 0 0-2.894.543l-.008.008-.06.06a.999.999 0 0 1-1.416 0 1 1 0 0 1 0-1.415l.06-.06.008-.009a2.65 2.65 0 0 0 .532-2.918 2.65 2.65 0 0 0-2.423-1.601H3a1 1 0 0 1 0-2h.113a2.65 2.65 0 0 0 2.414-1.705 2.65 2.65 0 0 0-.542-2.894l-.008-.008-.06-.06a1 1 0 0 1 0-1.416 1 1 0 0 1 1.415 0l.06.06.009.008a2.65 2.65 0 0 0 2.729.607 1 1 0 0 0 .264-.073A2.65 2.65 0 0 0 11 3.174V3a1 1 0 0 1 .293-.707ZM12 0a3 3 0 0 0-3 3v.167a.65.65 0 0 1-.285.534 1 1 0 0 0-.199.064.65.65 0 0 1-.714-.127l-.054-.055a3 3 0 1 0-4.245 4.244l.055.055a.65.65 0 0 1 .127.714l-.024.059a.65.65 0 0 1-.585.425H3a3 3 0 1 0 0 6h.167a.65.65 0 0 1 .594.394l.004.01a.65.65 0 0 1-.127.714l-.055.055a3 3 0 0 0 3.27 4.895 3 3 0 0 0 .974-.65v-.001l.055-.055a.65.65 0 0 1 .714-.127l.059.023a.651.651 0 0 1 .425.586V21a3 3 0 1 0 6 0v-.168a.65.65 0 0 1 .394-.593l.01-.004a.65.65 0 0 1 .714.127l.055.055a2.999 2.999 0 0 0 4.244 0l-.707-.707.707.707a2.999 2.999 0 0 0 0-4.244l-.055-.055a.65.65 0 0 1-.127-.714l.004-.01a.65.65 0 0 1 .594-.394H21a3 3 0 0 0 0-6h-.168a.65.65 0 0 1-.533-.285 1.006 1.006 0 0 0-.064-.199.65.65 0 0 1 .127-.714l.055-.054a2.999 2.999 0 0 0-.973-4.896 3 3 0 0 0-3.271.651l-.055.055a.65.65 0 0 1-.714.127l-.01-.004A.65.65 0 0 1 15 3.087V3a3 3 0 0 0-3-3Zm-2 12a2 2 0 1 1 4 0 2 2 0 0 1-4 0Zm2-4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" clip-rule="evenodd"/>
						</g>
						<defs>
							<clipPath id="a">
							<path fill="#fff" d="M0 0h24v24H0z"/>
							</clipPath>
						</defs>
					</svg>
					</button>
					<a href="<?php echo esc_url_raw( admin_url( 'admin.php?page=user-registration-settings&tab=import_export' ) ); ?>"
					title="<?php esc_html_e( 'Export Users', 'user-registration' ); ?>" target="_blank">
						<button class="button ur-button-primary">Export</button>
					</a>
				</div>
			</div>
			<span class="wp-header-end"></span>
			<div id="user-registration-pro-users-page">
				<div id="user-registration-pro-filters-row">
					<?php
					$list_table->display_filters();
					?>
					<form method="get" id="user-registration-users-search-form">
						<input type="hidden" name="page" value="user-registration-users" />

						<?php
						$list_table->display_search_box();

						if ( ! empty( $_REQUEST['role'] ) ) {
							?>
						<input type="hidden" name="role" value="<?php echo esc_attr( $_REQUEST['role'] ); ?>" />
						<?php } ?>
					</form>
				</div>
				<hr>
			<form method="get" id="user-registration-users-action-form">
				<input type="hidden" name="page" value="user-registration-users" />

				<?php if ( ! empty( $_REQUEST['role'] ) ) { ?>
				<input type="hidden" name="role" value="<?php echo esc_attr( $_REQUEST['role'] ); ?>" />
				<?php } ?>

				<?php $list_table->display(); ?>
			</form>
			<?php
			if ( isset( $_GET['form_filter'] ) ) {
				$form_id = (int) sanitize_text_field( wp_unslash( $_GET['form_filter'] ) );

				printf(
					"<input type='hidden' id='user-registration-users-form-id' value='%d'>",
					esc_attr( $form_id )
				);
			}
			?>
			<div class="clear"></div>
			</div>
			<?php
		}

		/**
		 * Render user single page content.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function render_single_user_details() {

			$user_id = sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) );
			$user    = get_userdata( $user_id );

			if ( ! $user ) {
				$redirect = admin_url( 'admin.php?page=user-registration-users' );
				wp_safe_redirect( $redirect );
				exit;
			}

			$user_extra_fields        = ur_get_user_extra_fields( $user_id );
			$user_data                = (array) $user->data;
			$user_data['first_name']  = get_user_meta( $user_id, 'first_name', true );
			$user_data['last_name']   = get_user_meta( $user_id, 'last_name', true );
			$user_data['description'] = get_user_meta( $user_id, 'description', true );
			$user_data['nickname']    = get_user_meta( $user_id, 'nickname', true );
			$user_data                = array_merge( $user_data, $user_extra_fields );

			$form_id               = ur_get_form_id_by_userid( $user_id );
			$form_field_data_array = user_registration_pro_profile_details_form_fields( $form_id );
			$user_data_to_show     = user_registration_pro_profile_details_form_field_datas( $form_id, $user_data, $form_field_data_array );
			$show_profile_picture  = get_option( 'user_registration_disable_profile_picture', true );
			?>

			<div class="ur-admin-page-topnav" id="ur-users-page-topnav">
				<div class="ur-page-title__wrapper">
					<h1 class="ur-page-title">
						<?php esc_html_e( 'User Details', 'user-registration' ); ?>
					</h1>
				</div>
			</div>
			<span class="wp-header-end"></span>

			<div id="user-registration-pro-single-user-view">
				<div id="user-registration-user-sidebar">
					<?php $this->render_user_profile( $user_id ); ?>
					<?php $this->render_user_actions( $user_id ); //phpcs:ignore ?>
					<?php $this->render_user_extra_details( $user_id ); ?>
					<?php
						/**
						 * Add more sections to the sidebar of user view page.

						@param int $user_id User Id.
						 */
						do_action( 'user_registration_user_view_sidebar', $user_id );
					?>
				</div>
				<?php
				if ( isset( $_GET['tab'] ) && 'user-actions' === $_GET['tab'] ) {
					?>
					<div id="user-registration-user-actions" class="user-registration-user-body">
						<?php $this->render_user_settings_section( $user_id ); ?>
					</div>
					<?php
				} else {
					$this->render_user_form_fields( $user_id );
				}
				?>
			</div>

			<?php
		}

		/**
		 * Display user profile image and username.
		 *
		 * @param [int] $user_id User Id.
		 * @return void
		 */
		public function render_user_profile( $user_id ) {
			$user   = get_userdata( $user_id );
			$avatar = get_avatar( $user_id, 900 );
			?>
			<div class="sidebar-box">
				<div class="user-profile">
					<div class="user-avatar">
						<?php echo $avatar; ?>
					</div>
					<p class="user-login">@<?php echo esc_html( $user->user_login ); ?> </p>
				</div>
			</div>
			<?php
		}

		/**
		 * Returns the html for the user actions sidebar.
		 *
		 * @param [int] $user_id User Id.
		 *
		 * @since 4.1
		 *
		 * @return string
		 */
		public function render_user_actions( $user_id ) {
			$actions = array();

			$user = get_userdata( $user_id );

			if ( current_user_can( 'edit_user', $user_id ) ) {
				// 1. Edit User
				$edit_link       = admin_url( 'user-edit.php?user_id=' . $user_id );
				$actions['edit'] = sprintf(
					'<a href="%s" target="_blank">%s <p>%s</p></a>',
					esc_url( $edit_link ),
					'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#000" fill-rule="evenodd" d="M19.207 3.207a1.121 1.121 0 0 1 1.586 1.586l-9.304 9.304-2.115.529.529-2.114 9.304-9.305ZM20 .88c-.828 0-1.622.329-2.207.914l-9.5 9.5a1 1 0 0 0-.263.465l-1 4a1 1 0 0 0 1.213 1.212l4-1a1 1 0 0 0 .464-.263l9.5-9.5A3.121 3.121 0 0 0 20 .88ZM4 3a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-7a1 1 0 1 0-2 0v7a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h7a1 1 0 1 0 0-2H4Z" clip-rule="evenodd"/>
					</svg>',
					__( 'Edit User', 'user-registration' ),
				);

				// 2. Approve/Deny User
				$user_manager = new UR_Admin_User_Manager( $user );
				$status       = $user_manager->get_user_status();

				if ( ! empty( $status ) ) {
					$user_status = esc_html( UR_Admin_User_Manager::get_status_label( $status['user_status'] ) );
				}

				$approve_link = add_query_arg(
					array(
						'action'   => 'approve',
						'user_id'  => $user_id,
						'_wpnonce' => wp_create_nonce( 'bulk-users' ),
					),
					admin_url( 'admin.php?page=user-registration-users&view_user' ),
				);

				$deny_link = add_query_arg(
					array(
						'action'   => 'deny',
						'user_id'  => $user_id,
						'_wpnonce' => wp_create_nonce( 'bulk-users' ),
					),
					admin_url( 'admin.php?page=user-registration-users&view_user' ),
				);

				if ( 'Pending' === $user_status || 'Denied' === $user_status ) {

					$actions['approve'] = sprintf(
						'<a href="%s">%s <p>%s</p></a>',
						$approve_link,
						'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path fill="#000" fill-rule="evenodd" d="M8.5 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6Zm-5 3a5 5 0 1 1 10 0 5 5 0 0 1-10 0Zm-2.036 8.464A5 5 0 0 1 5 14h7a5 5 0 0 1 5 5v2a1 1 0 1 1-2 0v-2a3 3 0 0 0-3-3H5a3 3 0 0 0-3 3v2a1 1 0 1 1-2 0v-2a5 5 0 0 1 1.464-3.536Zm22.243-5.757a1 1 0 0 0-1.414-1.414L19 11.586l-1.293-1.293a1 1 0 1 0-1.414 1.414l2 2a1 1 0 0 0 1.414 0l4-4Z" clip-rule="evenodd"/>
						</svg>',
						__( 'Approve User', 'user-registration' ),
					);
				}

				if ( 'Pending' === $user_status || 'Approved' === $user_status ) {

					$actions['deny'] = sprintf(
						'<a href="%s">%s <p>%s</p></a>',
						$deny_link,
						'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path fill="#000" fill-rule="evenodd" d="M6 7a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-5a5 5 0 1 0 0 10A5 5 0 0 0 9 2ZM6 14a5 5 0 0 0-5 5v2a1 1 0 1 0 2 0v-2a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v2a1 1 0 1 0 2 0v-2a5 5 0 0 0-5-5H6Zm10.293-6.707a1 1 0 0 1 1.414 0L19.5 9.086l1.793-1.793a1 1 0 1 1 1.414 1.414L20.914 10.5l1.793 1.793a1 1 0 0 1-1.414 1.414L19.5 11.914l-1.793 1.793a1 1 0 0 1-1.414-1.414l1.793-1.793-1.793-1.793a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
						</svg>',
						__( 'Deny User', 'user-registration' )
					);
				}

				// 3. Send Password Reset
				$password_reset_link = add_query_arg(
					array(
						'action'   => 'resetpassword',
						'user_id'  => $user_id,
						'_wpnonce' => wp_create_nonce( 'bulk-users' ),
					),
					admin_url( 'admin.php?page=user-registration-users&view_user' ),
				);

				$actions['request_password_reset'] = sprintf(
					'<a href="%s" target="_blank">%s <p>%s</p></a>',
					$password_reset_link,
					'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#000" fill-rule="evenodd" d="M12 2h-.004a10.75 10.75 0 0 0-7.431 3.021l-.012.012L4 5.586V3a1 1 0 1 0-2 0v5a.997.997 0 0 0 1 1h5a1 1 0 0 0 0-2H5.414l.547-.547A8.75 8.75 0 0 1 12.001 4 8 8 0 1 1 4 12a1 1 0 1 0-2 0A10 10 0 1 0 12 2Z" clip-rule="evenodd"/>
					</svg>',
					__( 'Send Password Reset Email', 'user-registration' )
				);

				// 4. Delete User
				$delete_link = add_query_arg(
					array(
						'action'   => 'delete',
						'user_id'  => $user_id,
						'_wpnonce' => wp_create_nonce( 'bulk-users' ),
					),
					admin_url( 'admin.php?page=user-registration-users&view_user' ),
				);

				$wp_delete_url = add_query_arg(
					array(
						'user'     => $user_id,
						'_wpnonce' => wp_create_nonce( 'bulk_users' ),
					),
					admin_url( 'users.php?action=delete' )
				);

				$actions['delete'] = sprintf(
					'<a href="%s" target="_blank" data-wp-delete-url="%s">%s <p>%s</p></a>',
					$delete_link,
					esc_url_raw( $wp_delete_url ),
					'<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<path fill="#000" fill-rule="evenodd" d="M9.293 3.293A1 1 0 0 1 10 3h4a1 1 0 0 1 1 1v1H9V4a1 1 0 0 1 .293-.707ZM7 5V4a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1h4a1 1 0 1 1 0 2h-1v13a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7H3a1 1 0 0 1 0-2h4Zm1 2h10v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7h2Zm2 3a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm5 7v-6a1 1 0 1 0-2 0v6a1 1 0 1 0 2 0Z" clip-rule="evenodd"/>
					</svg>',
					__( 'Delete User', 'user-registration' )
				);
			}

			$actions = apply_filters( 'user_registration_pro_user_actions', $actions, $user_id );

			if ( ! empty( $actions ) ) {
				?>
				<div class="sidebar-box" id="user-registration-user-view-user-actions">
					<h2 class="box-title">User Actions</h2>
					<ul>
						<?php
						foreach ( $actions as $key => $action_link ) {
							echo '<li id="user-registration-user-action-' . $key . '">' . $action_link . '</li>';
						}
						?>
					</ul>
				</div>
				<?php
			}
		}

		/**
		 * Render extra information of the user.
		 *
		 * @param [int] $user_id User Id.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		private function render_user_extra_details( $user_id ) {

			$user = get_userdata( $user_id );

			$user_manager = new UR_Admin_User_Manager( $user );

			$status = $user_manager->get_user_status();

			if ( ! empty( $status ) ) {
				$status = esc_html( UR_Admin_User_Manager::get_status_label( $status['user_status'] ) );
			}

			$form_id    = ur_get_form_id_by_userid( $user_id );
			$form_title = get_the_title( $form_id );

			$extra_details = array(
				'user_id'         => array(
					'title' => __( 'User Id', 'user-registration' ),
					'value' => $user_id,
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path fill="#000" fill-rule="evenodd" d="M21.707 1.293a1 1 0 0 1 0 1.414L20.414 4l2.293 2.293a1 1 0 0 1 0 1.414l-3.5 3.5a1 1 0 0 1-1.414 0L15.5 8.914l-2.751 2.751a6.5 6.5 0 1 1-1.414-1.414l3.457-3.457v-.001l.002-.001 3.497-3.497.002-.002.002-.002 1.998-1.998a1 1 0 0 1 1.414 0ZM19 5.414 16.914 7.5 18.5 9.086 20.586 7 19 5.414ZM7.5 11a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Z" clip-rule="evenodd"/>
								</svg>',
				),
				'user_status'     => array(
					'title' => __( 'User Status', 'user-registration' ),
					'value' => $status,
					'class' => 'user-registration-user-status-' . strtolower( $status ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path fill="#000" fill-rule="evenodd" d="M4 3a1 1 0 0 0-2 0v18a1 1 0 0 0 1 1h18a1 1 0 1 0 0-2H4V3Zm15.707 5.293a1 1 0 0 0-1.414 0L14 12.586l-3.293-3.293a1 1 0 0 0-1.414 0l-3 3a1 1 0 1 0 1.414 1.414L10 11.414l3.293 3.293a1 1 0 0 0 1.414 0l5-5a1 1 0 0 0 0-1.414Z" clip-rule="evenodd"/>
								</svg>',

				),
				'user_role'       => array(
					'title' => __( 'User Role', 'user-registration' ),
					'value' => esc_html( ucfirst( implode( ' ', $user->roles ) ) ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path fill="#000" fill-rule="evenodd" d="M9 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM4 7a5 5 0 1 1 10 0A5 5 0 0 1 4 7Zm12.969 6.286a1.999 1.999 0 0 0-.883 2.295 1.003 1.003 0 0 1 .2.45 2 2 0 0 0 2.295.883 1.002 1.002 0 0 1 .45-.2 2 2 0 0 0 .883-2.295 1.002 1.002 0 0 1-.2-.45 1.999 1.999 0 0 0-2.294-.883 1 1 0 0 1-.451.2Zm4.186-.745a4.022 4.022 0 0 0-.846-.808l.04-.117a1 1 0 0 0-1.898-.632l-.013.04a4.028 4.028 0 0 0-1.061.024l-.048-.12a1 1 0 0 0-1.857.743l.07.174c-.31.24-.582.526-.808.846l-.118-.04a1 1 0 0 0-.632 1.898l.04.013a4.03 4.03 0 0 0 .024 1.062l-.12.047a1 1 0 1 0 .743 1.857l.174-.069c.24.308.525.58.845.807l-.04.118a1 1 0 0 0 1.898.632l.014-.04a4.07 4.07 0 0 0 1.062-.024l.048.12a1 1 0 0 0 1.857-.743l-.07-.174c.309-.24.58-.526.807-.845l.118.039a1 1 0 0 0 .632-1.898l-.04-.013a4.04 4.04 0 0 0-.024-1.062l.12-.048a1 1 0 0 0-.743-1.857l-.174.07ZM6 14a5 5 0 0 0-5 5v2a1 1 0 1 0 2 0v-2a3 3 0 0 1 3-3h4a1 1 0 1 0 0-2H6Z" clip-rule="evenodd"/>
								</svg>',
				),
				'registered_form' => array(
					'title' => __( 'Form', 'user-registration' ),
					'value' => $form_title,
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path fill="#000" fill-rule="evenodd" d="M3.879 1.879A3 3 0 0 1 6 1h8.5a1 1 0 0 1 .707.293l5.5 5.5A1 1 0 0 1 21 7.5V20a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V4a3 3 0 0 1 .879-2.121ZM6 3h7v5a1 1 0 0 0 1 1h5v11a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm9 4h3.086L15 3.914V7Zm-7 5a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Zm-1 5a1 1 0 0 1 1-1h8a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1Zm1-9a1 1 0 0 0 0 2h2a1 1 0 1 0 0-2H8Z" clip-rule="evenodd"/>
								</svg>',
				),
				'registered_on'   => array(
					'title' => __( 'Date', 'user-registration' ),
					'value' => $user->user_registered,
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
									<path fill="#000" fill-rule="evenodd" d="M17 2a1 1 0 1 0-2 0v1H9V2a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3h-2V2Zm3 7V6a1 1 0 0 0-1-1h-2v1a1 1 0 1 1-2 0V5H9v1a1 1 0 0 1-2 0V5H5a1 1 0 0 0-1 1v3h16ZM4 11h16v9a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9Zm3 3a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1Zm5-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12Zm3 1a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H16a1 1 0 0 1-1-1Zm-7 3a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H8Zm3 1a1 1 0 0 1 1-1h.01a1 1 0 1 1 0 2H12a1 1 0 0 1-1-1Zm5-1a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H16Z" clip-rule="evenodd"/>
								</svg>',
				),
			);

			/**
			 * Add details to show in extra details section.
			 *
			 * @since 4.1
			 */
			$extra_details = apply_filters( 'user_registration_single_user_view_extra_details', $extra_details, $user );

			if ( ! empty( $extra_details ) ) :
				?>
				<div class="sidebar-box" id="user-registration-user-view-extra-details">
					<h2 class="box-title"><?php esc_html_e( 'Extra Details', 'user-registration' ); ?></h2>
					<ul>
					<?php
					foreach ( $extra_details as $id => $data ) {
						printf(
							'<li id="%s">%s<p><span>%s:&nbsp;</span><span class="%s">%s</span></p></li>',
							esc_attr( 'user-registration-user-extra-detail-' . $id ),
							isset( $data['icon'] ) ? $data['icon'] : '',
							esc_html( $data['title'] ),
							isset( $data['class'] ) ? esc_attr( $data['class'] ) : '',
							esc_html( $data['value'] )
						);
					}
					?>
					</ul>
				</div>
				<?php
			endif;
		}

		/**
		 * Render user form fields and their values.
		 *
		 * @param [int] $user_id User Id.
		 *
		 * @return void
		 */
		private function render_user_form_fields( $user_id ) {
			$user            = get_userdata( $user_id );
			$form_id         = ur_get_form_id_by_userid( $user_id );
			$form_data_array = ( $form_id ) ? UR()->form->get_form( $form_id, array( 'content_only' => true ) ) : array();

			?>
			<div class="user-registration-user-body">
				<div class="user-registration-user-form-details">
					<?php
					foreach ( $form_data_array as $data ) {
						foreach ( $data as $grid_key => $grid_data ) {
							foreach ( $grid_data as $grid_data_key => $single_item ) {
								if ( ! isset( $single_item->general_setting->field_name ) ) {
									continue;
								}

								$field_name = $single_item->general_setting->field_name;
								$field_key  = $single_item->field_key;

								/**
								 * Return fields to skip display in User view page.
								 *
								 * @since 4.1
								 */
								$skip_fields = apply_filters(
									'user_registration_single_user_view_skip_form_fields',
									array(
										'user_confirm_email',
										'user_pass',
										'user_confirm_password',
										'html',
										'section_title',
										'billing_address_title',
										'shipping_address_title',
										'profile_picture',
										'captcha',
										'multiple_choice',
										'single_item',
										'quantity_field',
										'stripe_gateway',
										'total_field',
									),
								);

								if ( in_array( $field_key, $skip_fields, true ) ) {
									continue;
								}

								echo '<div class="single-field">';
								echo '<h3 class="single-field__label">' . esc_html( $single_item->general_setting->label ) . '</h3>';

								$value = '';

								if ( in_array( $field_key, array( 'user_login', 'user_email', 'display_name', 'user_url' ), true ) ) {
									$value = $user->$field_key;
								} elseif ( 'multi_select2' === $field_key ) {
									$values = get_user_meta( $user->ID, 'user_registration_' . $field_name, true );

									if ( ! empty( $values ) ) {
										$value = implode( ',', $values );
									}
								} elseif ( 'country' === $field_key ) {
									$value         = get_user_meta( $user->ID, 'user_registration_' . $field_name, true );
									$country_class = ur_load_form_field_class( $field_key );
									$countries     = $country_class::get_instance()->get_country();
									$value         = isset( $countries[ $value ] ) ? $countries[ $value ] : $value;
								} else {
									$value = get_user_meta( $user->ID, 'user_registration_' . $field_name, true );

									// For Woocommerce fields.
									$value = empty( $value ) ? get_user_meta( $user->ID, $field_name, true ) : $value;
								}

								$checkbox_fields = array(
									'checkbox',
									'privacy_policy',
									'mailerlite',
									'separate_shipping',
								);

								// Mark checkbox fields as Checked/Unchecked.
								if ( in_array( $field_key, $checkbox_fields, true ) ) {
									$value = is_array( $value ) ? implode( ', ', $value ) : esc_attr( $value );
								}

								/**
								 * Modify value for the single field.
								 *
								 * @since 4.1
								 */
								$value = apply_filters( 'user_registration_single_user_view_field_value', $value, $field_name, $field_key );

								$non_text_fields = apply_filters(
									'user_registration_single_user_view_non_text_fields',
									array(
										'file',
									)
								);

								if ( is_string( $value ) && ! in_array( $field_key, $non_text_fields, true ) ) {
									if ( 60 > strlen( $value ) ) {
										printf(
											'<input type="text" value="%s" disabled>',
											esc_attr( $value )
										);
									} else {
										printf(
											'<textarea rows="6" disabled>%s</textarea>',
											esc_attr( $value )
										);
									}
								} else {
									do_action( 'user_registration_single_user_view_output_' . $field_key . '_field', $user_id, $single_item );
								}
								echo '</div>';
							}
						}
					}
					?>
				</div>
				<?php do_action( 'user_registration_single_user_details_content', $user_id, $form_id ); ?>
			</div>
			<?php
		}

		/**
		 * Returns the list of column headers for Users list table.
		 *
		 * @since 4.1
		 *
		 * @return array
		 */
		public function get_column_headers() {
			$column_headers = apply_filters(
				'user_registration_users_table_column_headers',
				array(
					'cb'              => '<input type="checkbox" />',
					'username'        => __( 'Username', 'user-registration' ),
					'fullname'        => __( 'Name', 'user-registration' ),
					'email'           => __( 'Email', 'user-registration' ),
					'role'            => __( 'Role', 'user-registration' ),
					'user_status'     => __( 'User Status', 'user-registration' ),
					'user_source'     => __( 'Source', 'user-registration' ),
					'user_registered' => __( 'Registered On', 'user-registration' ),
				)
			);

			$column_headers['actions'] = __( 'Actions', 'user-registration' );
			return $column_headers;
		}


		/**
		 * Add form specific columns to the screen column options.
		 *
		 * @param [array] $columns Columns array.
		 * @return array
		 */
		public function add_form_fields_columns( $columns ) {

			// Return early if no specific form is selected.
			if ( ! isset( $_GET['form_filter'] ) ) {
				return $columns;
			}

			$form_id = (int) sanitize_text_field( $_GET['form_filter'] ); //phpcs:ignore WordPress.Security.NonceVerification

			if ( $form_id ) {
				$form_data_array = ( $form_id ) ? UR()->form->get_form( $form_id, array( 'content_only' => true ) ) : array();

				foreach ( $form_data_array as $data ) {
					foreach ( $data as $grid_key => $grid_data ) {
						foreach ( $grid_data as $grid_data_key => $single_item ) {

							$field_label = $single_item->general_setting->label;
							$field_name  = $single_item->general_setting->field_name;

							$skip_fields = array(
								'user_login',
								'user_email',
								'user_confirm_email',
								'user_pass',
								'user_confirm_password',
							);

							if ( in_array( $field_name, $skip_fields ) ) {
								continue;
							}

							if ( ! empty( $field_name ) && ! empty( $field_label ) ) {
								$columns[ $field_name ] = $field_label;
							}
						}
					}
				}
			}

			return $columns;
		}

		/**
		 * Add screen options for Users table.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function add_screen_options() {
			add_screen_option(
				'per_page',
				array(
					'label'   => 'Number of users per page',
					'default' => 10,
				)
			);
		}

		/**
		 * Updates the value of screen option for 'per_page' option.
		 *
		 * @param [string] $screen_option Default Screen option.
		 * @param [string] $option Option name.
		 * @param [string] $value User provided option value.
		 *
		 * @since 4.1
		 *
		 * @return string
		 */
		public function save_users_per_page_screen_option( $screen_option, $option, $value ) {

			if ( ! empty( $value ) && is_numeric( $value ) ) {
				$screen_option = intval( $value );
			}

			return $screen_option;
		}

		/**
		 * Add or remove bulk items from the dropdown.
		 *
		 * @param [array] $bulk_array Array of bulk actions.
		 *
		 * @since 4.1
		 *
		 * @return array
		 */
		public function manage_bulk_action_items( $bulk_array ) {
			$new_actions = array(
				'approve'     => __( 'Approve', 'user-registration' ),
				'deny'        => __( 'Deny', 'user-registration' ),
				'update_role' => __( 'Change role', 'user-registration' ),
			);

			$bulk_array = array_merge( $new_actions, $bulk_array );

			return $bulk_array;
		}

		/**
		 * Bulk actions and single user action handler.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function handle_actions() {
			global $wpdb;

			if ( ! ( ( isset( $_GET['page'] ) && 'user-registration-users' === $_GET['page'] ) ) ) {
				return;
			}

			if ( ! isset( $_REQUEST['action'] ) ) {
				return;
			}

			if ( empty( $_REQUEST['users'] ) && empty( $_REQUEST['user_id'] ) ) {
				return;
			}

			check_admin_referer( 'bulk-users' );

			if ( current_user_can( 'edit_users' ) ) {

				$action  = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
				$userids = array();

				if ( ! empty( $_REQUEST['users'] ) ) {
					$userids = array_map( 'intval', (array) $_REQUEST['users'] );
				} elseif ( ! empty( $_REQUEST['user_id'] ) ) {
					$userids = array( (int) $_REQUEST['user_id'] );
				}

				switch ( $action ) {
					case 'delete':
						if ( ! current_user_can( 'delete_users' ) ) {
							$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to delete users.', 'user-registration' ) );
							break;
						}

						$userids = array_diff( $userids, array( get_current_user_id() ) );

						/**
						 * Check whether the user to be deleted has additional content in the site.
						 *
						 * @since 4.1
						 */
						$users_have_content = (bool) apply_filters( 'user_registration_users_have_additional_content', false, $userids );

						if ( $userids && ! $users_have_content ) {
							if ( $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_author IN( " . implode( ',', $userids ) . ' ) LIMIT 1' ) ) {
								$users_have_content = true;
							} elseif ( $wpdb->get_var( "SELECT link_id FROM {$wpdb->links} WHERE link_owner IN( " . implode( ',', $userids ) . ' ) LIMIT 1' ) ) {
								$users_have_content = true;
							}
						}

						if ( $users_have_content ) {
							$redirect_url = add_query_arg(
								array(
									'_wpnonce' => wp_create_nonce( 'bulk-users' ),
									'users'    => $userids,
									'action'   => 'delete',
									'action2'  => 'delete',
								),
								admin_url( 'users.php?s' )
							);

							wp_safe_redirect( esc_url_raw( $redirect_url ) );
							exit;

						}

						$delete_count = 0;

						foreach ( $userids as $id ) {
							if ( ! current_user_can( 'delete_user', $id ) ) {
								$user = get_userdata( $id );

								$this->errors[] = new WP_Error( 'edit_users', __( "Sorry, you are not allowed to delete the user $user->user_login.", 'user-registration' ) );
								continue;
							}

							wp_delete_user( $id );

							++$delete_count;
						}

						if ( $delete_count ) {
							add_action(
								'admin_notices',
								function () use ( $delete_count ) {
									printf(
										"<div class='updated notice ur-users-notice is-dismissible'><p>%s deleted successfully.</p></div>",
										1 < $delete_count ? $delete_count . ' users' : 'User'
									);
								}
							);

							if ( isset( $_GET['view_user'] ) ) {
								$redirect = admin_url( 'admin.php?page=user-registration-users' );
								$redirect = add_query_arg( 'ur_user_deleted', 1, $redirect );

								wp_safe_redirect( $redirect );
								exit;
							}
						}

						break;

					case 'resetpassword':
						$reset_count = 0;

						foreach ( $userids as $id ) {
							if ( ! current_user_can( 'edit_user', $id ) ) {
								$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to edit this user.', 'user-registration' ) );
							}

							// Send the password reset link.
							$user = get_userdata( $id );
							if ( retrieve_password( $user->user_login ) ) {
								++$reset_count;
							}
						}

						if ( $reset_count ) {
							add_action(
								'admin_notices',
								function () use ( $reset_count ) {
									printf(
										"<div class='updated notice ur-users-notice is-dismissible'><p>Reset password email sent%s successfully.</p></div>",
										1 < $reset_count ? esc_html( " to {$reset_count} users" ) : '',
									);
								}
							);
						}

						break;

					case 'update_role':
						if ( ! current_user_can( 'promote_users' ) ) {
							$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to update user roles.', 'user-registration' ) );
							break;
						}

						$editable_roles = get_editable_roles();
						$role           = $_REQUEST['new_role'];

						if ( ! $role || empty( $editable_roles[ $role ] ) ) {
							$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to give users that role.', 'user-registration' ) );
							break;
						}

						$role_change_count = 0;

						foreach ( $userids as $id ) {
							$id = (int) $id;

							$user = get_userdata( $id );

							if ( ! current_user_can( 'promote_user', $id ) ) {
								$this->errors[] = new WP_Error( 'edit_users', "Sorry, you are not allowed to change role for user {$user->user_login}." );
							}

							// If the user doesn't already belong to the blog, bail.
							if ( is_multisite() && ! is_user_member_of_blog( $id ) ) {
								wp_die(
									'<h1>' . __( 'Something went wrong.' ) . '</h1>' .
									'<p>' . __( 'One of the selected users is not a member of this site.' ) . '</p>',
									403
								);
							}

							$user->set_role( $role );
							++$role_change_count;
						}

						if ( $role_change_count ) {
							add_action(
								'admin_notices',
								function () use ( $role_change_count ) {
									echo "<div class='updated notice ur-users-notice is-dismissible'><p>Roles updated for {$role_change_count} users successfully.</p></div>";
								}
							);
						}

						break;

					case 'approve':
						if ( ! current_user_can( 'promote_users' ) ) {
							$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to approve the users.', 'user-registration' ) );
							break;
						}

						$approval_count = 0;

						foreach ( $userids as $user_id ) {
							try {
								$user_manager = new UR_Admin_User_Manager( $user_id );
								$form_id      = ur_get_form_id_by_userid( $user_id );
								$login_option = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_login_options', get_option( 'user_registration_general_setting_login_options', 'default' ) );

								$user_manager->approve();

								if ( 'email_confirmation' === $login_option || 'admin_approval_after_email_confirmation' === $login_option ) {
									update_user_meta( $user_id, 'ur_confirm_email', '1' );
									delete_user_meta( $user_id, 'ur_confirm_email_token' );
									if ( 'admin_approval_after_email_confirmation' === $login_option ) {
										update_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', 'true' );
									}
								}

								++$approval_count;
							} catch ( Exception $e ) {
								$this->errors[] = new WP_Error( 'edit_users', $e->getMessage() );
							}
						}

						if ( $approval_count ) {
							add_action(
								'admin_notices',
								function () use ( $approval_count ) {
									printf(
										"<div class='updated notice ur-users-notice is-dismissible'><p>%s approved successfully.</p></div>",
										1 < $approval_count ? $approval_count . ' users' : 'User'
									);
								}
							);
						}
						break;

					case 'deny':
						if ( ! current_user_can( 'promote_users' ) ) {
							$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to deny the users.', 'user-registration' ) );
							break;
						}

						$denial_count = 0;

						foreach ( $userids as $user_id ) {
							try {
								$user_manager = new UR_Admin_User_Manager( $user_id );
								$form_id      = ur_get_form_id_by_userid( $user_id );
								$login_option = ur_get_single_post_meta( $form_id, 'user_registration_form_setting_login_options', get_option( 'user_registration_general_setting_login_options', 'default' ) );

								$user_manager->deny();

								if ( 'email_confirmation' === $login_option || 'admin_approval_after_email_confirmation' === $login_option ) {
									update_user_meta( $user_id, 'ur_confirm_email', '0' );
									delete_user_meta( $user_id, 'ur_confirm_email_token' );
									if ( 'admin_approval_after_email_confirmation' === $login_option ) {
										update_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', 'denied' );
									}
								}

								++$denial_count;
							} catch ( Exception $e ) {
								$this->errors[] = new WP_Error( 'edit_users', $e->getMessage() );
							}
						}

						if ( $denial_count ) {
							add_action(
								'admin_notices',
								function () use ( $denial_count ) {
									printf(
										"<div class='updated notice ur-users-notice is-dismissible'><p>%s denied successfully.</p></div>",
										( 1 < $denial_count ) ? $denial_count . ' users' : 'User'
									);
								}
							);
						}
						break;

					default:
						do_action( 'user_registration_users_do_bulk_' . $action, $userids );
						break;
				}
			} else {
				$this->errors[] = new WP_Error( 'edit_users', __( 'Sorry, you are not allowed to edit users.', 'user-registration' ) );
			}

			if ( ! empty( $this->errors ) ) {
				foreach ( $this->errors as $error ) {
					add_action(
						'admin_notices',
						function () use ( $error ) {
							echo esc_html( '<div class="notice ur-users-notice notice-error"><p>' . $error->get_error_message() . '</p></div>' );
						}
					);
				}
			}
		}

		/**
		 * Display Notices for actions that require redirections.
		 *
		 * @return void
		 */
		public function handle_redirect_notices() {

			if ( isset( $_GET['ur_user_deleted'] ) ) {

				printf(
					'<div class="notice ur-users-notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html__( 'User deleted successfully.', 'user-registration' ),
				);

			}
		}
	}
}

return new User_Registration_Pro_Users_Menu();

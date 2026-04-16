<?php
/**
 * UserRegistration Pro Users ListTable class.
 *
 * @package  UserRegistration/Admin
 * @author   WPEverest
 *
 * @since 4.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'User_Registration_Pro_Users_ListTable' ) ) {

	_get_list_table( 'WP_Users_List_Table' );

	/**
	 * User_Registration_Pro_Users_List_Table class.
	 */
	class User_Registration_Pro_Users_List_Table extends WP_Users_List_Table {


		function __construct() {
			parent::__construct();

			add_filter( 'ur_pro_manage_users_custom_column', array( $this, 'output_custom_column_data' ), 10, 3 );
		}

		/**
		 * Prepare the users list for display.
		 *
		 * @since 4.1
		 *
		 * @global string $role
		 * @global string $usersearch
		 */
		public function prepare_items() {
			global $role, $usersearch;

			$usersearch = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

			$role = isset( $_REQUEST['role'] ) ? $_REQUEST['role'] : '';

			$users_per_page = $this->get_items_per_page( 'user_registration_page_user_registration_users_per_page' );

			$paged = $this->get_pagenum();

			$args = array(
				'number' => $users_per_page,
				'offset' => ( $paged - 1 ) * $users_per_page,
				'search' => $usersearch,
				'fields' => 'all_with_meta',
			);

			$args['role'] = 'all' === $role ? '' : $role;

			if ( isset( $_REQUEST['form_filter'] ) ) {
				$form_filter = sanitize_text_field( wp_unslash( $_REQUEST['form_filter'] ) );

				if ( ! empty( $form_filter ) && in_array( $form_filter, array_keys( $this->get_all_registration_forms() ) ) ) {
					$args['meta_query'] = array(
						array(
							'key'     => 'ur_form_id',
							'value'   => (string) $form_filter,
							'compare' => '=',
						),
					);
				}
			}

			if ( isset( $_REQUEST['user_status'] ) ) {
				$status_filter = sanitize_text_field( wp_unslash( $_REQUEST['user_status'] ) );

				if ( ! empty( $status_filter ) && in_array( $status_filter, array( 'approved', 'pending', 'denied' ) ) ){
					$status_code = UR_Admin_User_Manager::get_status_code( $status_filter );

					$status_filter_args = array(
						array(
							'key'     => 'ur_user_status',
							'value'   => (string) $status_code,
							'compare' => '=',
						),
					);

					if ( 1 == $status_code ) {
						$status_filter_args['relation'] = 'OR';
						$status_filter_args[] = array(
							'key'     => 'ur_user_status',
							'compare' => 'NOT EXISTS',
						);
					}

					$args['meta_query'][] = $status_filter_args;
				}
			}

			// Date Range Filter.

			$start_date = gmdate( 'Y-m-d', strtotime( '-5 years' ) );
			$end_date   = gmdate( 'Y-m-d' );

			if ( isset( $_REQUEST['date_range'] ) && ! empty( $_REQUEST['date_range'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				$date_range = sanitize_text_field( wp_unslash( $_REQUEST['date_range'] ) );

				switch ( $date_range ) {
					case 'day':
						$start_date = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
						break;

					case 'week':
						$start_date = gmdate( 'Y-m-d', strtotime( '-1 week' ) );
						break;

					case 'month':
						$start_date = gmdate( 'Y-m-d', strtotime( '-1 month' ) );
						break;

					case 'year':
						break;

					case 'custom':
						if ( isset( $_REQUEST['start_date'] ) && ! empty( $_REQUEST['start_date'] ) && strtotime( $_REQUEST['start_date'] ) ) {
							$start_date = sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) );
						}

						if ( isset( $_REQUEST['end_date'] ) && ! empty( $_REQUEST['end_date'] ) && strtotime( $_REQUEST['end_date'] ) ) {
							$end_date = sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) );
						}
						break;
				}
			}

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$args['date_query'] = array(
					array(
						'after'     => $start_date,
						'before'    => $end_date,
						'inclusive' => true,
					),
				);
			}

			// Date Range Filter End.

			if ( isset( $args['meta_query'] ) && 1 < count( $args['meta_query'] ) ) {
				$args['meta_query']['relation'] = 'AND';
			}

			if ( '' !== $args['search'] ) {
				$args['search'] = '*' . $args['search'] . '*';
			}

			if ( $this->is_site_users ) {
				$args['blog_id'] = $this->site_id;
			}

			if ( isset( $_REQUEST['orderby'] ) ) {
				$args['orderby'] = $_REQUEST['orderby'];
			}

			if ( isset( $_REQUEST['order'] ) ) {
				$args['order'] = $_REQUEST['order'];
			}

			/**
			 * Filters the query arguments used to retrieve users for the current users list table.
			 *
			 * @since 4.1
			 *
			 * @param array $args Arguments passed to WP_User_Query to retrieve items for the current
			 *                    users list table.
			 */
			$args = apply_filters( 'ur_pro_users_list_table_query_args', $args );

			// Query the user IDs for this page.
			$wp_user_search = new WP_User_Query( $args );

			$this->items = $wp_user_search->get_results();

			$this->set_pagination_args(
				array(
					'total_items' => $wp_user_search->get_total(),
					'per_page'    => $users_per_page,
				)
			);
		}

		/**
		 * Output the controls to allow user roles to be changed in bulk.
		 *
		 * @since 4.1
		 *
		 * @param string $which Whether this is being invoked above ("top")
		 *                      or below the table ("bottom").
		 */
		protected function extra_tablenav( $which ) {
			$id        = 'bottom' === $which ? 'new_role2' : 'new_role';
			$button_id = 'bottom' === $which ? 'changeit2' : 'changeit';
			?>
		<div class="alignleft actions">
			<?php if ( current_user_can( 'promote_users' ) && $this->has_items() ) : ?>
			<label class="screen-reader-text" for="<?php echo $id; ?>">
				<?php
				/* translators: Hidden accessibility text. */
				_e( 'Change role to&hellip;' );
				?>
			</label>
			<select name="<?php echo $id; ?>" id="<?php echo $id; ?>">
				<option value=""><?php _e( 'Change role to&hellip;' ); ?></option>
				<?php wp_dropdown_roles(); ?>
				<option value="none"><?php _e( '&mdash; No role for this site &mdash;' ); ?></option>
			</select>
				<?php
				submit_button( __( 'Change' ), '', $button_id, false );
			endif;

			/**
			 * Fires just before the closing div containing the bulk role-change controls
			 * in the Users list table.
			 *
			 * @since 4.1
			 *
			 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
			 */
			do_action( 'ur_pro_restrict_manage_users', $which );
			?>
			</div>
			<?php
			/**
			 * Fires immediately following the closing "actions" div in the tablenav for the users
			 * list table.
			 *
			 * @since 4.1
			 *
			 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
			 */
			do_action( 'ur_pro_manage_users_extra_tablenav', $which );
		}

		/**
		 * Generate HTML for a single row on the users.php admin panel.
		 *
		 * @since 4.1
		 *
		 * @param WP_User $user_object The current user object.
		 * @param string  $style       Deprecated. Not used.
		 * @param string  $role        Deprecated. Not used.
		 * @param int     $numposts    Optional. Post count to display for this user. Defaults
		 *                             to zero, as in, a new user has made zero posts.
		 * @return string Output for a single row.
		 */
		public function single_row( $user_object, $style = '', $role = '', $numposts = 0 ) {
			if ( ! ( $user_object instanceof WP_User ) ) {
				$user_object = get_userdata( (int) $user_object );
			}
			$user_object->filter = 'display';
			$email               = $user_object->user_email;

			$user_manager = new UR_Admin_User_Manager( $user_object );

			if ( $this->is_site_users ) {
				$url = "site-users.php?id={$this->site_id}&amp;";
			} else {
				$url = 'users.php?';
			}

			$user_roles = $this->get_role_list( $user_object );

			// Set up the hover actions for this user.
			$actions     = array();
			$checkbox    = '';
			$super_admin = '';

			if ( is_multisite() && current_user_can( 'manage_network_users' ) ) {
				if ( in_array( $user_object->user_login, get_super_admins(), true ) ) {
					$super_admin = ' &mdash; ' . __( 'Super Admin' );
				}
			}

			// Check if the user for this row is editable.
			if ( current_user_can( 'list_users' ) ) {
				// Set up the user editing link.
				$edit_link = esc_url(
					add_query_arg(
						'wp_http_referer',
						urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
						get_edit_user_link( $user_object->ID )
					)
				);

				// Add a link to the user's author archive, if not empty.
				$actions['view'] = sprintf(
					'<a href="%s" target="_blank" aria-label="%s">%s</a>',
					esc_url( admin_url( 'admin.php?page=user-registration-users&view_user&user_id=' . $user_object->ID ) ),
					/* translators: %s: Author's display name. */
					esc_attr( sprintf( __( 'View details for %s' ), $user_object->display_name ) ),
					__( 'View' )
				);

				if ( current_user_can( 'edit_user', $user_object->ID ) ) {
					$actions['edit'] = '<a href="' . $edit_link . '" target="_blank">' . __( 'Edit' ) . '</a>';
				}

				/**
				 * Filters the action links displayed under each user in the Users list table.
				 *
				 * @since 4.1
				 *
				 * @param string[] $actions     An array of action links to be displayed.
				 *                              Default 'Edit', 'Delete' for single site, and
				 *                              'Edit', 'Remove' for Multisite.
				 * @param WP_User  $user_object WP_User object for the currently listed user.
				 */
				$actions = apply_filters( 'ur_pro_user_row_actions', $actions, $user_object );

				// Role classes.
				$role_classes = esc_attr( implode( ' ', array_keys( $user_roles ) ) );

				// Set up the checkbox (because the user is editable, otherwise it's empty).
				$checkbox = sprintf(
					'<label class="screen-reader-text" for="user_%1$s">%2$s</label>' .
					'<input type="checkbox" name="users[]" id="user_%1$s" class="%3$s" value="%1$s" />',
					$user_object->ID,
					/* translators: Hidden accessibility text. %s: User login. */
					sprintf( __( 'Select %s' ), $user_object->user_login ),
					$role_classes
				);

			}

			$avatar = get_avatar( $user_object->ID, 32 );

			// Comma-separated list of user roles.
			$roles_list = implode( ', ', $user_roles );

			$row = "<tr id='user-$user_object->ID'>";

			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

			foreach ( $columns as $column_name => $column_display_name ) {
				$classes = "$column_name column-$column_name";
				if ( $primary === $column_name ) {
					$classes .= ' has-row-actions column-primary';
				}
				if ( 'posts' === $column_name ) {
					$classes .= ' num'; // Special case for that column.
				}

				if ( in_array( $column_name, $hidden, true ) ) {
					$classes .= ' hidden';
				}

				$data = 'data-colname="' . esc_attr( wp_strip_all_tags( $column_display_name ) ) . '"';

				$attributes = "class='$classes' $data";

				if ( 'cb' === $column_name ) {
					$row .= "<th scope='row' class='check-column'>$checkbox</th>";
				} else {
					$row .= "<td $attributes>";
					switch ( $column_name ) {
						case 'username':
							$row .= "$avatar $user_object->user_login";
							break;
						case 'fullname':
							if ( $user_object->first_name && $user_object->last_name ) {
								$row .= sprintf(
									/* translators: 1: User's first name, 2: Last name. */
									_x( '%1$s %2$s', 'Display name based on first name and last name' ),
									$user_object->first_name,
									$user_object->last_name
								);
							} elseif ( $user_object->first_name ) {
								$row .= $user_object->first_name;
							} elseif ( $user_object->last_name ) {
								$row .= $user_object->last_name;
							} else {
								$row .= sprintf(
									'<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
									/* translators: Hidden accessibility text. */
									_x( 'Unknown', 'name' )
								);
							}
							break;
						case 'email':
							$row .= "<a href='" . esc_url( "mailto:$email" ) . "'>$email</a>";
							break;
						case 'role':
							$row .= esc_html( $roles_list );
							break;
						case 'user_status':
							$status = $user_manager->get_user_status();

							if ( ! empty( $status ) ) {
								$user_status  = $status['user_status'];
								$status_label = UR_Admin_User_Manager::get_status_label( $user_status );

								$row .= sprintf(
									'<span class="%s">%s</span>',
									esc_attr( 'user-status-' . str_replace( ' ', '', strtolower( $status_label ) ) ),
									esc_html( $status_label )
								);
							}

							break;
						case 'user_source':
							$row .= ur_get_user_registered_source( $user_object->ID );
							break;
						case 'user_registered':
							$row .= $user_object->user_registered;
							break;
						case 'actions':
							$row .= $this->row_actions( $actions, true );
							break;
						default:
							/**
							 * Filters the display output of custom columns in the Users list table.
							 *
							 * @since 4.1
							 *
							 * @param string $output      Custom column output. Default empty.
							 * @param string $column_name Column name.
							 * @param int    $user_id     ID of the currently-listed user.
							 */
							$row .= apply_filters( 'ur_pro_manage_users_custom_column', '', $column_name, $user_object->ID );
					}
					$row .= '</td>';
				}
			}
			$row .= '</tr>';

			return $row;
		}

		/**
		 * Render the filter options for users table.
		 *
		 * @since 4.1
		 *
		 * @return void
		 */
		public function display_filters() {
			?>
			<form action='' method='GET'>
				<input type="hidden" name="page" value="user-registration-users">
				<?php wp_nonce_field( 'user-registration-pro-filter-users' ); ?>
				<ul class="subsubsub" id="user-registration-pro-users-filters">
					<li>
						<div>
							<p>Form</p>
							<select name="form_filter" id="user_registration_pro_users_form_filter">
								<?php
								foreach ( $this->get_all_registration_forms() as $id => $form ) {
									$selected = isset( $_REQUEST['form_filter'] ) && $id == $_REQUEST['form_filter'] ? 'selected=selected' : '';
									?>
									<option value='<?php echo esc_attr( $id ); ?>' <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $form ); ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</li>

					<li>
						<div>
							<p>Status</p>
							<select name="user_status" id="user_registration_pro_users_user_status_filter">
								<?php
								$user_status = isset( $_GET['user_status'] ) ? sanitize_text_field( $_GET['user_status'] ) : '';
								?>
								<option value="all">All</option>
								<option value="approved" <?php echo 'approved' === $user_status ? 'selected=selected' : ''; ?>>Approved</option>
								<option value="pending" <?php echo 'pending' === $user_status ? 'selected=selected' : ''; ?>>Pending</option>
								<option value="denied" <?php echo 'denied' === $user_status ? 'selected=selected' : ''; ?>>Denied</option>
							</select>
						</div>
					</li>

					<li>
						<div>
							<p>Role</p>
							<select name="role" id="user_registration_pro_users_role_filter">
								<?php
								foreach ( $this->get_role_filters() as $role_key => $role_label ) {
									$selected = isset( $_REQUEST['role'] ) && $role_key === $_REQUEST['role'] ? 'selected=selected' : '';
									?>
										<option value='<?php echo esc_attr( $role_key );  ?>' <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $role_label ); ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</li>

					<li>
						<div>
							<p>Date Range</p>
							<select name="date_range" id="user_registration_pro_users_date_range_filter">
								<?php
								$selected_date_range_filter = isset( $_GET['date_range'] ) ? sanitize_text_field( $_GET['date_range'] ) : '';

								$date_range_filters = array(
									'day'    => __( 'Day', 'user-registration' ),
									'week'   => __( 'Week', 'user-registration' ),
									'month'  => __( 'Month', 'user-registration' ),
									'year'   => __( 'Year', 'user-registration' ),
									'custom' => __( 'Custom Range', 'user-registration' ),
								);

								$default_range = 'year';

								foreach ( $date_range_filters as $key => $label ) {

									$selected = '';
									if ( $key === $selected_date_range_filter ) {
										$selected = 'selected=selected';
									} elseif ( '' === $selected_date_range_filter && $key === $default_range ) {
										$selected = 'selected=selected';
									}

									echo sprintf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $key ),
										esc_attr( $selected ),
										esc_html( $label )
									);
								}
								?>
							</select>
						</div>
					</li>

					<li class="user-registration-users-filter-btns">
						<button type="submit" name="ur_users_filter" id="user-registration-users-filter-btn" class="button ur-button-primary">
							<?php esc_html_e( 'Filter', 'user-registration' ); ?>
						</button>

						<button type="reset"  id="user-registration-users-filter-reset-btn" class="" title="<?php _e( 'Reset', 'user-registration' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
								<path fill="#000" fill-rule="evenodd" d="M12 2h-.004a10.75 10.75 0 0 0-7.431 3.021l-.012.012L4 5.586V3a1 1 0 1 0-2 0v5a.997.997 0 0 0 1 1h5a1 1 0 0 0 0-2H5.414l.547-.547A8.75 8.75 0 0 1 12.001 4 8 8 0 1 1 4 12a1 1 0 1 0-2 0A10 10 0 1 0 12 2Z" clip-rule="evenodd"/>
							</svg>
						</button>
					</li>
				</ul>

				<?php
				$hide_advanced_filters = 'display:none';
				if ( isset( $_REQUEST['date_range'] ) && 'custom' === sanitize_text_field( $_REQUEST['date_range'] ) ) {
					$hide_advanced_filters = '';
				}
				?>
				<br>
				<div id="user-registration-users-advanced-filters" style="<?php echo esc_attr( $hide_advanced_filters ); ?>">
					<ul>
						<li>
							<div>
								<p>Custom Date Range</p>
								<?php
									$start_date = isset( $_REQUEST['start_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) ) : '';
									$end_date = isset( $_REQUEST['end_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) ) : '';
								?>
								<input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>" id="user_registration_pro_start_date_filter" title="Start date" />
								-
								<input type="date" name="end_date"  value="<?php echo esc_attr( $end_date ); ?>" id="user_registration_pro_end_date_filter" title="End date" />
							</div>
						</li>
					</ul>
				</div>
			</form>

			<?php
		}

		/**
		 * Displays the search box.
		 *
		 * @since 4.1
		 *
		 */
		public function display_search_box() {
			if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
				return;
			}

			$input_id = 'user-registration-users-search-input';

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
			}
			if ( ! empty( $_REQUEST['order'] ) ) {
				echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
			}
			if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
				echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
			}
			if ( ! empty( $_REQUEST['detached'] ) ) {
				echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
			}
			?>
			<p class="search-box">
				<div>
					<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="Search User..." />
					<button type="submit" id="search-submit">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<path fill="#000" fill-rule="evenodd" d="M4 11a7 7 0 1 1 12.042 4.856 1.012 1.012 0 0 0-.186.186A7 7 0 0 1 4 11Zm12.618 7.032a9 9 0 1 1 1.414-1.414l3.675 3.675a1 1 0 0 1-1.414 1.414l-3.675-3.675Z" clip-rule="evenodd"/>
						</svg>
					</button>
				</div>
			</p>
			<?php
		}

		/**
		 * Returns a list of all registration forms.
		 *
		 * @since 4.1
		 *
		 * @return array
		 */
		protected function get_all_registration_forms() {
			$forms = array(
				'0' => __( 'All Forms', 'user-registration' ),
			);

			$forms = array_replace( $forms, ur_get_all_user_registration_form() );

			return $forms;
		}

		/**
		 * Returns a list of all available roles.
		 *
		 * @since 4.1
		 *
		 * @return array
		 */
		protected function get_role_filters() {
			$roles = wp_roles()->role_names;

			$roles = array_merge(
				array(
					'all' => __( 'All Roles', 'user-registration' ),
				),
				$roles
			);

			return $roles;
		}

		/**
		 * Returns an array of translated user role names for a given user object.
		 *
		 * @since 4.1
		 *
		 * @param WP_User $user_object The WP_User object.
		 * @return string[] An array of user role names keyed by role.
		 */
		protected function get_role_list( $user_object ) {
			$wp_roles = wp_roles();

			$role_list = array();

			foreach ( $user_object->roles as $role ) {
				if ( isset( $wp_roles->role_names[ $role ] ) ) {
					$role_list[ $role ] = translate_user_role( $wp_roles->role_names[ $role ] );
				}
			}

			if ( empty( $role_list ) ) {
				$role_list['none'] = _x( 'None', 'no user roles' );
			}

			/**
			 * Filters the returned array of translated role names for a user.
			 *
			 * @since 4.1
			 *
			 * @param string[] $role_list   An array of translated user role names keyed by role.
			 * @param WP_User  $user_object A WP_User object.
			 */
			return apply_filters( 'ur_pro_get_role_list', $role_list, $user_object );
		}

		/**
		 * Returns a list of sortable columns.
		 *
		 * @since 4.1
		 *
		 * @return array
		 */
		protected function get_sortable_columns() {
			return apply_filters(
				'user_registration_pro_users_table_sortable_columns',
				array(
					'username'        => 'login',
					'email'           => 'email',
					'user_registered' => 'user_registered',
				)
			);
		}

		/**
		 * Displays the table.
		 *
		 * @since 4.1
		 */
		public function display() {
			$singular = $this->_args['singular'];

			$this->display_tablenav( 'top' );

			$this->screen->render_screen_reader_content( 'heading_list' );
			?>
			<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
				<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
				</thead>

				<tbody id="the-list"
					<?php
					if ( $singular ) {
						echo " data-wp-lists='list:$singular'";
					}
					?>
					>
					<?php $this->display_rows_or_placeholder(); ?>
				</tbody>
			</table>
			<?php
			$this->display_tablenav( 'bottom' );
		}

		/**
		 * Generates the table navigation above or below the table
		 *
		 * @since 4.1
		 *
		 * @param string $which
		 */
		protected function display_tablenav( $which ) {
			if ( 'top' === $which ) {
				wp_nonce_field( 'bulk-' . $this->_args['plural'] );
			}
			?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() && 'top' === $which ) : ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
				<?php
				$this->extra_tablenav( $which );
				$this->pagination( $which );
			endif;


			if ( 'bottom' === $which ) : ?>
				<div class="alignleft">
					<?php $this->footer_text(); ?>
				</div>
			<?php
				$this->pagination( $which );
			endif;
			?>
				<br class="clear" />
			</div>
			<?php
		}

		/**
		 * Output footer text.
		 *
		 * @return void
		 */
		protected function footer_text() {
			$total_items    = $this->_pagination_args['total_items'];
			$current        = $this->get_pagenum();
			$users_per_page = $this->_pagination_args['per_page'];

			echo esc_html(
				sprintf(
					'Showing results %d-%d of %d users',
					( ( $current - 1 ) * $users_per_page ) + 1,
					min( ( $current ) * $users_per_page, $total_items ),
					$total_items
				)
			);
		}

		public function output_custom_column_data( $output, $column_name, $user_id ) {
			$meta_key = 'user_registration_' . $column_name;

			$meta_value = get_user_meta( $user_id, $meta_key, true );

			if ( empty( $meta_value ) ) {
				$meta_value = get_user_meta( $user_id, $column_name, true );
			}

			$output .= is_array( $meta_value ) ? implode( ', ', $meta_value ) : $meta_value;

			return $output;
		}
	}
}

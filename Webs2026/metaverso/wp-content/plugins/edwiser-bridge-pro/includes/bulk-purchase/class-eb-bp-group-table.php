<?php
/**
 * This class is responsible to shopw manage enrollment table.
 *
 * @link       https://edwiser.org
 * @since      2.3.8
 * @package    BulkPurchase
 */

namespace app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( '\app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\Eb_Bp_Group_Table' ) ) {

	/**
	 * Custom list table.
	 */
	class Eb_Bp_Group_Table extends \WP_List_Table {

		/**
		 * Bp group columns.
		 *
		 * @since    1.0.0
		 *
		 * @var string bp_group_columns.
		 */
		protected $bp_group_columns;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Set parent defaults.
			parent::__construct(
				array(
					'singular' => 'group',
					'plural'   => 'groups',
					'ajax'     => true,
				)
			);

			// Columns.
			$this->bp_group_columns = apply_filters(
				'eb_bp_group_columns',
				array(
					'cb'              => '<input type="checkbox" />',
					'group_name'      => esc_html__( 'Group Name', 'edwiser-bridge-pro' ),
					'user'            => esc_html__( 'Group Manager', 'edwiser-bridge-pro' ),
					'enrolled'        => esc_html__( 'Enrolled Users', 'edwiser-bridge-pro' ),
					'available_seats' => esc_html__( 'Available Seats', 'edwiser-bridge-pro' ),
					'courses'         => esc_html__( 'Courses', 'edwiser-bridge-pro' ),
				)
			);
		}


		/**
		 * Get filter query.
		 *
		 * @param text $filter filter.
		 * @param text $search_text text.
		 * @param text $from from.
		 * @param text $to to.
		 * @param text $order order.
		 * @param text $per_page per_page.
		 * @param text $offset offset.
		 * @param text $stmt stmt.
		 */
		private function eb_get_filter_query( $filter, $search_text, $from, $to, $order, $per_page, $offset, $stmt ) {
			global $wpdb;
			$column     = '';
			$where      = '';
			$post_table = '';
			$user_table = '';
			$date       = empty( $from ) ? '' : "time> '" . $from . "'" . ( empty( $to ) ? '' : " AND time< '" . $to . "'" );

			// There are 2 filters which need join query.
			// 1. Group name.
			// 2. User name.
			if ( 'group_name' === $filter ) {
				$order = $order;
				$where = '1';
				$stmt  = $wpdb->prepare( "SELECT b.* FROM {$wpdb->prefix}bp_cohort_info b WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d", $per_page, $offset ); // @codingStandardsIgnoreLine.

				if ( ! empty( $search_text ) ) {
					$stmt = $wpdb->prepare( "SELECT b.* FROM {$wpdb->prefix}bp_cohort_info b, WHERE b.NAME like %s ORDER BY {$order} LIMIT %d OFFSET %d", '%' . $search_text . '%', $per_page, $offset ); // @codingStandardsIgnoreLine.
				}
			} elseif ( 'user' === $filter ) {
				$column     = ' u.user_login ';
				$order      = $column . $order;
				$where      = 'b.COHORT_MANAGER=u.ID';
				$user_table = ', ' . $wpdb->users . ' u';
				$stmt  = $wpdb->prepare( "SELECT b.*, {$column} FROM {$wpdb->prefix}bp_cohort_info b  {$post_table} {$user_table}  WHERE {$where} ORDER BY {$order} LIMIT %d OFFSET %d", $per_page, $offset ); // @codingStandardsIgnoreLine.

				if ( ! empty( $search_text ) ) {
					$stmt = $wpdb->prepare( "SELECT b.*, u.user_login FROM {$wpdb->prefix}bp_cohort_info b LEFT JOIN {$wpdb->users} u ON u.ID=b.COHORT_MANAGER WHERE b.NAME like %s AND u.ID=b.COHORT_MANAGER  ORDER BY u.user_login", '%' . $search_text . '%' );
				}
			}

			if ( ! empty( $from ) || ! empty( $to ) ) {
				$stmt = $wpdb->prepare( "SELECT b.*, {$column} FROM {$wpdb->prefix}bp_cohort_info b {$post_table} {$user_table}  WHERE {$where} AND {$date} ORDER BY {$order} LIMIT %d OFFSET %d", $per_page, $offset ); // @codingStandardsIgnoreLine.
			}

			if ( ( ! empty( $from ) || ! empty( $to ) ) && ! empty( $search_text ) ) {
				$stmt = $wpdb->prepare( "SELECT b.*, {$column} FROM wp_bp_cohort_info b LEFT JOIN wp_users u ON b.COHORT_MANAGER=u.ID WHERE b.NAME like %s AND time>= %s  AND time<= %s ORDER BY {$order} LIMIT %d OFFSET %d", '%' . $search_text . '%', $from, $to, $per_page, $offset ); // @codingStandardsIgnoreLine.
			}

			return $stmt;
		}


		/**
		 * Get table.
		 *
		 * @param text $post_data post_data.
		 * @param text $search_text text.
		 * @param text $current_page current_page.
		 */
		public function bp_get_groups_table( $post_data, $search_text, $current_page ) {
			global $wpdb;
			$per_page    = 20;
			$search_text = isset( $_REQUEST['ebbp_grp_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ebbp_grp_search'] ) ) : ''; // WPCS: CSRF ok, input var ok. // @codingStandardsIgnoreLine
			$from        = isset( $_REQUEST['enrollment_from_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['enrollment_from_date'] ) ) : ''; // WPCS: CSRF ok, input var ok. // @codingStandardsIgnoreLine
			$to          = isset( $_REQUEST['enrollment_to_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['enrollment_to_date'] ) ) : ''; // WPCS: CSRF ok, input var ok. // @codingStandardsIgnoreLine
			// If no sort, default to title.
			$order_by = ! empty( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'id'; // WPCS: CSRF ok, input var ok. // @codingStandardsIgnoreLine.
			// If no order, default to asc.
			$order = ! empty( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc'; // WPCS: CSRF ok, input var ok. // @codingStandardsIgnoreLine.
			$date  = empty( $from ) ? '' : "time> '" . $from . "'" . ( empty( $to ) ? '' : " AND time< '" . $to . "'" );

			if ( 'group_name' === $order_by ) {
				$order_by = 'NAME';
			}

			$order_query = $order_by . ' ' . strtoupper( $order );

			// Determine sort order.
			$tbl_records = array();
			$offset      = ( $current_page - 1 ) * $per_page;
			$stmt        = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_cohort_info ORDER BY {$order_query} LIMIT %d OFFSET %d", $per_page, $offset ); // @codingStandardsIgnoreLine.

			if ( ! empty( $search_text ) ) {
				$stmt = $wpdb->prepare( "SELECT b.* FROM {$wpdb->prefix}bp_cohort_info b WHERE b.NAME like %s ORDER BY {$order_query} LIMIT %d OFFSET %d", '%' . $search_text . '%', $per_page, $offset ); // @codingStandardsIgnoreLine.
			}

			if ( ! empty( $from ) || ! empty( $to ) ) {
				$stmt = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bp_cohort_info  WHERE {$date} ORDER BY {$order_query} LIMIT %d OFFSET %d", $per_page, $offset ); // @codingStandardsIgnoreLine.
			}

			if ( ( ! empty( $from ) || ! empty( $to ) ) && ! empty( $search_text ) ) {
				$stmt = $wpdb->prepare( "SELECT b.* FROM {$wpdb->prefix}bp_cohort_info b WHERE b.NAME like %s AND {$date} ORDER BY {$order_query} LIMIT %d OFFSET %d", '%' . $search_text . '%', $per_page, $offset ); // @codingStandardsIgnoreLine.
			}

			// Need to check above if conditions again because of prepare statements as direct concatenating is prohibited.
			// Also Not creating one common query with all tables as it will take time for all other searches.
			if ( ! empty( $order_by ) && 'id' !== $order_by && 'time' !== $order_by ) {
				$stmt = $this->eb_get_filter_query( $order_by, $search_text, $from, $to, $order, $per_page, $offset, $stmt );
			}

			$results = $wpdb->get_results( $stmt ); // @codingStandardsIgnoreLine

			foreach ( $results as $result ) {
				$profile_url       = $this->get_user_profile_url( $result->COHORT_MANAGER ); // @codingStandardsIgnoreLine.
				$row               = array();
				$row['user_id']    = $result->COHORT_MANAGER; // @codingStandardsIgnoreLine.
				$row['user']       = $profile_url;
				$row['group_name'] = $result->NAME; // @codingStandardsIgnoreLine.
				$row['manage']     = true;
				$row['ID']         = $result->ID;
				$row['rId']        = $result->ID;
                $row['cohort_id']  = $result->MDL_COHORT_ID; // @codingStandardsIgnoreLine.
				$row['products']   = $result->PRODUCTS; // @codingStandardsIgnoreLine.
				$row['courses']    = $result->COURSES; // @codingStandardsIgnoreLine.

				$tbl_records[] = apply_filters( 'eb_bp_manage_grpup_each_row', $row, $result, $search_text );
			}

			$table_data    = apply_filters( 'eb_bp_manage_group_table_data', $tbl_records );
			$total_records = $this->eb_get_enrollment_total_record( $search_text, $from, $to ); // WPCS: CSRF ok, input var ok. // @codingStandardsIgnoreLine
			return array(
				'total_records' => $total_records,
				'data'          => $table_data,
			);
		}

		/**
		 * Returns the user profile link.
		 *
		 * @param string $search_text search_text.
		 * @param string $from from date.
		 * @param string $to to date.
		 * @return type
		 */
		public function eb_get_enrollment_total_record( $search_text, $from, $to ) {
			global $wpdb;
			$stmt = "SELECT * FROM {$wpdb->prefix}bp_cohort_info";

			if ( ! empty( $search_text ) ) {
				$stmt = $wpdb->prepare( "SELECT b.* FROM {$wpdb->prefix}bp_cohort_info b WHERE b.NAME like %s", '%' . $search_text . '%' );
			}

			$total_result_stmt = $wpdb->get_results( $stmt ); // @codingStandardsIgnoreLine
			return count( $total_result_stmt );
		}

		/**
		 * Returns the user profile link.
		 *
		 * @param type $user_id user_id.
		 * @return type
		 */
		private function get_user_profile_url( $user_id ) {
			$user_name = '';
			$user_info = get_userdata( $user_id );
			if ( $user_info ) {
				$edit_link = get_edit_user_link( $user_id );
				$user_name = '<a href="' . esc_url( $edit_link ) . '">' . $user_info->user_login . '</a>';
			}
			return $user_name;
		}

		/**
		 * Get columns.
		 */
		public function get_columns() {
			return $this->bp_group_columns;
		}

		/**
		 * Get sortable columns
		 */
		protected function get_sortable_columns() {
			$sortable_columns = array(
				'group_name' => array( 'group_name', false ),
				'user'       => array( 'user', false ),
			);
			return $sortable_columns;
		}

		/**
		 * Get default column value.
		 *
		 * Recommended. This method is called when the parent class can't find a method
		 * specifically build for a given column. Generally, it's recommended to include
		 * one method for each column you want to render, keeping your package class
		 * neat and organized. For example, if the class needs to process a column
		 * named 'title', it would first see if a method named $this->column_title()
		 * exists - if it does, that method will be used. If it doesn't, this one will
		 * be used. Generally, you should try to use custom column methods as much as
		 * possible.
		 *
		 * Since we have defined a column_title() method later on, this method doesn't
		 * need to concern itself with any column with a name of 'title'. Instead, it
		 * needs to handle everything else.
		 *
		 * For more detailed insight into how columns are handled, take a look at
		 * WP_List_Table::single_row_columns()
		 *
		 * @param object $item        A singular item (one full row's worth of data).
		 * @param string $column_name The name/slug of the column to be processed.
		 * @return string Text or HTML to be placed inside the column <td>.
		 */
		protected function column_default( $item, $column_name ) {
			// from 1.3.5.
			return $item[ $column_name ];
		}

		/**
		 * Add row actioins for group name column.
		 *
		 * @param array $item item.
		 */
		public function column_group_name( $item ) {
			$url       = add_query_arg(
				array(
					'mdl_cohort_id'    => $item['cohort_id'],
					'eb-bp-edit-group' => wp_create_nonce( 'eb-bp-edit-group' ),
				)
			);
			$id_text   = __( 'ID ', 'edwiser-bridge-pro' );
			$edit_text = __( 'Edit', 'edwiser-bridge-pro' );
			$actions   = array(
				// show id.
				'id'   => sprintf( '%s: %s', $id_text, $item['ID'] ),
				'edit' => sprintf( '<a href="%s">%s</a>', $url, $edit_text ),
			);
			return sprintf( '%1$s %2$s', $item['group_name'], $this->row_actions( $actions ) );
		}

		/**
		 * Get value for checkbox column.
		 *
		 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
		 * is given special treatment when columns are processed. It ALWAYS needs to
		 * have it's own method.
		 *
		 * @param object $item A singular item (one full row's worth of data).
		 * @return string Text to be placed inside the column <td>.
		 */
		protected function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				$this->_args['singular'],
				$item['ID']
			);
		}

		/**
		 * Get value for enrolled column.
		 *
		 * @param object $item A singular item (one full row's worth of data).
		 */
		protected function column_enrolled( $item ) {
			global $wpdb;
			$tbl_name       = $wpdb->prefix . 'moodle_enrollment';
			$enrolled_users = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT `user_id` FROM `{$tbl_name}` WHERE `mdl_cohort_id` = '%d'", $item['cohort_id'] ) ); // @codingStandardsIgnoreLine

			$seats = count( $enrolled_users );
			return $seats;
		}

		/**
		 * Get value for available seats column.
		 *
		 * @param object $item A singular item (one full row's worth of data).
		 */
		protected function column_available_seats( $item ) {
			$products        = maybe_unserialize( $item['products'] );
			$available_seats = ( is_array( $products ) ) ? min( $products ) : 0;
			return $available_seats;
		}

		/**
		 * Get value for courses column.
		 *
		 * @param object $item A singular item (one full row's worth of data).
		 */
		protected function column_courses( $item ) {
			$courses     = maybe_unserialize( $item['courses'] );
			$course_list = '';
			if ( is_array( $courses ) ) {
				foreach ( $courses as $course_id ) {
					$course = get_post( $course_id );
					if ( $course ) {
						$course_list .= '<a href="' . get_edit_post_link( $course_id ) . '">' . $course->post_title . '</a>, ';
					}
				}
			}
			return rtrim( $course_list, ', ' );
		}

		/**
		 * Add new group button on top of the table.
		 *
		 * @param string $which which.
		 * @since 2.3.8
		 */
		public function extra_tablenav( $which ) {
			if ( 'top' === $which ) {
				$url = add_query_arg(
					array(
						'eb-bp-add-group' => wp_create_nonce( 'eb-bp-add-group' ),
					)
				);
				?>
				<div class="alignleft actions">
					<a href="<?php echo esc_url( $url ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Group', 'edwiser-bridge-pro' ); ?></a>
				</div>
				<?php
			}
		}

		/**
		 * Get an associative array ( option_name => option_title ) with the list
		 * of bulk actions available on this table.
		 *
		 * Optional. If you need to include bulk actions in your list table, this is
		 * the place to define them. Bulk actions are an associative array in the format
		 * 'slug'=>'Visible Title'
		 *
		 * If this method returns an empty value, no bulk action will be rendered. If
		 * you specify any bulk actions, the bulk actions box will be rendered with
		 * the table automatically on display().
		 *
		 * Also note that list tables are not automatically wrapped in <form> elements,
		 * so you will need to create those manually in order for bulk actions to function.
		 *
		 * @return array An associative array containing all the bulk actions.
		 */
		protected function get_bulk_actions() {
			$actions = array(
				'delete' => esc_html_x( 'Delete', 'Delete the selected Groups', 'edwiser-bridge-pro' ),
			);
			return $actions;
		}

		/**
		 * Handle bulk actions.
		 *
		 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
		 * For this example package, we will handle it in the class to keep things
		 * clean and organized.
		 *
		 * @param text $post_data post_data.
		 * @see $this->prepare_items()
		 */
		protected function process_bulk_action( $post_data ) {
			// Detect when a bulk action is being triggered.
			if ( 'delete' === $this->current_action() ) {
				if ( isset( $post_data['group'] ) && is_array( $post_data['group'] ) && count( $post_data['group'] ) ) {
					$this->delete_groups( wp_unslash( $post_data['group'] ) );
				} else {
					echo '<div class="notice notice-error is-dismissible">';
					echo '<p>' . esc_html__( 'No records selected to delete groups, Please select the records to delete', 'edwiser-bridge-pro' ) . '</p>';
					echo '</div>';
				}
			}
		}

		/**
		 * Delete groups.
		 *
		 * @param array $group_ids group ids.
		 */
		private function delete_groups( $group_ids ) {
			global $wpdb;
			$tbl_name  = $wpdb->prefix . 'bp_cohort_info';
			$group_ids = array_map( 'intval', $group_ids );
			$group_ids = implode( ',', $group_ids );

			// get mdl cohort ids from table.
			$mdl_cohort_ids = $wpdb->get_col( "SELECT MDL_COHORT_ID FROM `{$tbl_name}` WHERE `ID` IN ({$group_ids})" ); // @codingStandardsIgnoreLine

			// delete mdl cohort.
			$conhort_manage = new Eb_Bp_Manage_Cohort();
			$conhort_manage->delete_cohort( $mdl_cohort_ids );

			$wpdb->query( "DELETE FROM `{$tbl_name}` WHERE `id` IN ({$group_ids})" ); // @codingStandardsIgnoreLine
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p>' . esc_html__( 'Selected groups deleted successfully', 'edwiser-bridge-pro' ) . '</p>';
			echo '</div>';
		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * REQUIRED! This is where you prepare your data for display. This method will
		 * usually be used to query the database, sort and filter the data, and generally
		 * get it ready to be displayed. At a minimum, we should set $this->items and
		 * $this->set_pagination_args(), although the following properties and methods
		 * are frequently interacted with here.
		 *
		 * @global wpdb $wpdb
		 * @uses $this->_column_headers
		 * @uses $this->items
		 * @uses $this->get_columns()
		 * @uses $this->get_sortable_columns()
		 * @uses $this->get_pagenum()
		 * @uses $this->set_pagination_args()
		 */
		public function prepare_items() {
			/*
			 * First, lets decide how many records per page to show
			 */
			$per_page = 20;

			$options = array();

			/*
			 * REQUIRED. Now we need to define our column headers. This includes a complete
			 * array of columns to be displayed (slugs & titles), a list of columns
			 * to keep hidden, and a list of columns that are sortable. Each of these
			 * can be defined in another method (as we've done here) before being
			 * used to build the value for our _column_headers property.
			 */
			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();

			/*
			 * REQUIRED. Finally, we build an array to be used by the class for column
			 * headers. The $this->_column_headers property takes an array which contains
			 * three other arrays. One for all columns, one for hidden columns, and one
			 * for sortable columns.
			 */
			$this->_column_headers = array( $columns, $hidden, $sortable );

			/*
			 * REQUIRED for pagination. Let's figure out what page the user is currently
			 * looking at. We'll need this later, so you should always include it in
			 * your own package classes.
			 */
			$current_page = $this->get_pagenum();

			/**
			 * Optional. You can handle your bulk actions however you see fit. In this
			 * case, we'll handle them within our package just to keep things clean.
			 */

			if ( isset( $_REQUEST['eb-bp-manage-groups'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['eb-bp-manage-groups'] ) ), 'eb-bp-manage-groups' ) ) {
				return;
			}
			$this->process_bulk_action( $_POST );

			$search_text = isset( $_REQUEST['ebbp_grp_search'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ebbp_grp_search'] ) ) : '';

			$options['ebbp_grp_search'] = $search_text;

			$table_data = $this->bp_get_groups_table( $_REQUEST, $search_text, $current_page );
			$data       = $table_data['data'];

			/*
			 * REQUIRED for pagination. Let's check how many items are in our data array.
			 * In real-world use, this would be the total number of items in your database,
			 * without filtering. We'll need this later, so you should always include it
			 * in your own package classes.
			 */
			$total_items = $table_data['total_records'];

			/*
			 * REQUIRED. Now we can add our *sorted* data to the items property, where
			 * it can be used by the rest of the class.
			 */
			$this->items = $data;

			/**
			 * REQUIRED. We also have to register our pagination options & calculations.
			 */
			$this->set_pagination_args(
				array(
					'total_items' => $total_items, // WE have to calculate the total number of items.
					'per_page'    => $per_page, // WE have to determine how many items to show on a page.
					'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
				)
			);
			// Update the current URI with the new options.
			$_SERVER['REQUEST_URI'] = add_query_arg( $options, $_SERVER['REQUEST_URI'] ); // @codingStandardsIgnoreLine
		}
	}
}

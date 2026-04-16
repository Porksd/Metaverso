<?php

if (!defined('ABSPATH')) {
    exit;
}


use app\wisdmlabs\edwiserBridgePro\includes as includes;
use function app\wisdmlabs\edwiserBridgePro\includes\bulkPurchase\eb_bp_get_wp_user_reg_role;

class EdwiserBridgeBlocksPro_GroupManagement_API
{
    // API namespace
    private const API_NAMESPACE = 'eb/api/v1';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'eb_register_group_management_routes'));
    }

    /**
     * Register API routes.
     */
    public function eb_register_group_management_routes()
    {
        register_rest_route(self::API_NAMESPACE, '/group-management/groups', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_groups'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/(?P<id>\d+)', array(
            'methods'  => WP_REST_Server::READABLE,
            'callback' => array($this, 'eb_get_group_by_id'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/update-name', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_update_cohort_name'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/delete', array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => array($this, 'eb_delete_cohort'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/course-progress', array(
            'methods'  => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_get_cohort_course_progress'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/delete-user', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_delete_enrolled_user'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/delete-users', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_delete_multiple_enrolled_users'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/users/update', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_update_user'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(self::API_NAMESPACE, '/group-management/groups/enroll-user', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'eb_enroll_user_in_cohort'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(
            self::API_NAMESPACE,
            '/group-management/groups/enrollment-details',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'eb_get_group_enrollment_details'),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function eb_get_groups($request)
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_REST_Response(array(
                'auth_required' => true,
                'message' => __('You must be logged in to access groups!', 'edwiser-bridge-pro'),
                'sign_in_url' => esc_url(wp_login_url(get_permalink())),
            ), 200);
        }

        global $wpdb;
        $user = wp_get_current_user();
        $tbl_name = $wpdb->prefix . 'bp_cohort_info';

        // Get cohort information for current user
        $result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, NAME, COHORT_NAME, MDL_COHORT_ID, PRODUCTS FROM {$tbl_name} WHERE COHORT_MANAGER = %d AND SYNC='1'",
                $user->ID
            ),
            ARRAY_A
        );

        if (empty($result)) {
            return new WP_REST_Response(array(
                'groups' => array(),
                'total_groups' => 0
            ), 200);
        }

        $groups = array();

        foreach ($result as $row) {
            $products_qty = maybe_unserialize($row['PRODUCTS']);
            $products_qty = array_values($products_qty);
            $available_seats = min($products_qty);

            $group_name = !empty($row['NAME'])
                ? $row['NAME']
                : str_replace($user->user_login . '_', '', $row['COHORT_NAME']);

            $groups[] = array(
                'name' => $group_name,
                'cohort_name' => $row['COHORT_NAME'],
                'mdl_cohort_id' => $row['MDL_COHORT_ID'],
                'available_seats' => $available_seats,
                'products' => $products_qty,
                'display_text' => $group_name . ' (' . $available_seats . ')'
            );
        }

        return new WP_REST_Response(array(
            'groups' => $groups,
            'total_groups' => count($groups)
        ), 200);
    }

    public function eb_get_cohort_course_progress($request)
    {
        global $wpdb;

        $params = $request->get_json_params();

        $group_id = $params['group_id'];
        $user_id = $params['user_id'];
        $nonce = $params['nonce'];

        if (!wp_verify_nonce(sanitize_text_field($nonce), 'wdm_eb_gp_mng_nonce')) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('Unable to fetch course progress, due to security reasons.', 'edwiser-bridge-pro')),
                403
            );
        }

        // Validate the group ID and user ID parameters
        if (empty($group_id) || !is_numeric($group_id) || empty($user_id) || !is_numeric($user_id)) {
            return new WP_REST_Response(array(
                'message' => __('Invalid request parameters', 'edwiser-bridge-pro')
            ), 400);
        }

        $table_name     = $wpdb->prefix . 'bp_cohort_info';
        $cohort_courses = maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT COURSES FROM {$table_name} WHERE MDL_COHORT_ID = %d;", $group_id)));

        if (empty($cohort_courses)) {
            return new WP_REST_Response(array(
                'courses' => array(),
                'message' => __('No courses found for this group.', 'edwiser-bridge-pro')
            ), 200);
        }

        $progress_checker = new \app\wisdmlabs\edwiserBridgePro\pb\Eb_Bp_Enroll_Students_Course_Progress();
        $user_course_progress = $progress_checker->get_course_progress($user_id);

        $courses_with_progress = array();
        foreach ($cohort_courses as $course_id) {
            $progress = isset($user_course_progress[$course_id]) ? $user_course_progress[$course_id] : 0;

            $courses_with_progress[] = array(
                'course_id' => $course_id,
                'course_title' => get_the_title($course_id),
                'progress' => ceil(floatval($progress)),
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'group_id' => $group_id,
            'user_id' => $user_id,
            'courses_progress' => $courses_with_progress,
        ), 200);
    }

    public function eb_get_group_by_id($request)
    {
        $group_id = $request->get_param('id');

        // Validate the group ID parameter
        if (empty($group_id) || !is_numeric($group_id)) {
            return new WP_REST_Response(array(
                'message' => __('Invalid request parameters', 'edwiser-bridge-pro')
            ), 400);
        }

        global $wpdb;
        $tbl_name = $wpdb->prefix . 'bp_cohort_info';

        // Get group information
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT PRODUCTS, NAME, COHORT_NAME FROM {$tbl_name} WHERE MDL_COHORT_ID = %d;",
                $group_id
            ),
            ARRAY_A
        );

        if (empty($result)) {
            return new WP_REST_Response(array(
                'message' => __('Group not found', 'edwiser-bridge-pro')
            ), 404);
        }

        $products = maybe_unserialize($result['PRODUCTS']);
        $group_details = array(
            'name' => $result['NAME'],
            'cohort_name' => $result['COHORT_NAME'],
            'cohort_id' => $group_id,
            'products' => array()
        );

        // Process each product and get associated courses
        foreach (array_keys($products) as $product_id) {
            $tbl_name_courses = $wpdb->prefix . 'eb_moodle_course_products';

            // Get courses for this product
            $courses = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT `moodle_post_id` FROM `{$tbl_name_courses}` WHERE `product_id` = %d",
                    $product_id
                )
            );

            $product_name = get_the_title($product_id);
            $product_courses = array();

            // Get course details
            foreach ($courses as $course_id) {
                $course_info = get_post($course_id);
                if ($course_info) {
                    $product_courses[] = array(
                        'course_id' => $course_id,
                        'course_title' => $course_info->post_title,
                        'course_slug' => $course_info->post_name
                    );
                }
            }

            $group_details['products'][] = array(
                'product_id' => $product_id,
                'product_name' => $product_name,
                'quantity' => isset($products[$product_id]) ? $products[$product_id] : 0,
                'courses' => $product_courses,
                'total_courses' => count($product_courses)
            );
        }

        // Get enrolled users information
        $enrollment_tbl = $wpdb->prefix . 'moodle_enrollment';
        $enrolled_users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT `user_id` FROM `{$enrollment_tbl}` WHERE `mdl_cohort_id` = '%d'",
                $group_id
            )
        );

        // Calculate available seats
        $available_seats = min($products);
        if (null === $available_seats) {
            $available_seats = 0;
        }

        // Process enrolled users data
        $enrolled_users_data = array();
        if (!empty($enrolled_users)) {
            foreach ($enrolled_users as $user) {
                $user_data = get_userdata($user->user_id);
                if ($user_data) {
                    $enrolled_users_data[] = array(
                        'user_id' => $user->user_id,
                        'first_name' => $user_data->first_name,
                        'last_name' => $user_data->last_name,
                        'full_name' => $user_data->first_name . ' ' . $user_data->last_name,
                        'email' => $user_data->user_email,
                        'display_name' => $user_data->display_name,
                    );
                }
            }
        }

        return new WP_REST_Response(array(
            'group_details' => $group_details,
            'enrollment_details' => array(
                'available_seats' => $available_seats,
                'enrolled_users_count' => count($enrolled_users),
                'remaining_seats' => max(0, $available_seats - count($enrolled_users)),
                'enrolled_users' => $enrolled_users_data
            )
        ), 200);
    }

    public function eb_update_cohort_name($request)
    {
        global $wpdb;

        $params = $request->get_json_params();

        // Verify nonce
        if (!isset($params['nonce']) || !wp_verify_nonce(sanitize_text_field($params['nonce']), 'wdm_eb_gp_mng_nonce')) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('Unable to update group name, due to security reasons.', 'edwiser-bridge-pro'),),
                403
            );
        }

        // Validate required parameters
        if (empty($params['cohort_id']) || empty($params['cohort_name'])) {
            return new WP_REST_Response(
                array('success' => false, 'message' =>  __('Unable to update group name.', 'edwiser-bridge-pro'),),
                400
            );
        }

        $cohort_id = sanitize_text_field($params['cohort_id']);
        $cohort_name = sanitize_text_field($params['cohort_name']);
        $tblcohort_info = $wpdb->prefix . 'bp_cohort_info';

        // Update cohort name
        $result = $wpdb->update(
            $tblcohort_info,
            array('NAME' => $cohort_name),
            array('MDL_COHORT_ID' => $cohort_id),
            array('%s'),
            array('%s')
        );

        if ($result !== false) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Group name successfully updated.', 'edwiser-bridge-pro')
            ), 200);
        } else {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Unable to update group name.', 'edwiser-bridge-pro'),
                ),
                500
            );
        }
    }

    public function eb_delete_cohort($request)
    {
        $params = $request->get_json_params();

        if (!isset($params['nonce']) || !wp_verify_nonce(sanitize_text_field($params['nonce']), 'wdm_eb_gp_mng_nonce')) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('Security check failed try reloading page.', 'edwiser-bridge-pro'),),
                403
            );
        }

        // Validate required parameters
        if (!isset($params['cohort_id']) || empty($params['cohort_id'])) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' =>  __('Cohort ID is required.', 'edwiser-bridge-pro'),
                ),
                400
            );
        }

        $cohort_id = sanitize_text_field($params['cohort_id']);

        $cohort_user_manager = new includes\bulkPurchase\Eb_Bp_Cohort_Manage_User();
        $user_delete_result = $cohort_user_manager->delete_all_users_from_cohort($cohort_id);

        // Delete cohort from WordPress and Moodle
        $cohort_manager = new includes\bulkPurchase\Eb_Bp_Manage_Cohort();
        $cohort_delete_result = $cohort_manager->delete_cohort(array($cohort_id));

        $response_data = array(
            'success' => true,
            'message' => __('Cohort deleted successfully', 'edwiser-bridge-pro'),
            'cohort_id' => $cohort_id
        );

        // Add redirect URL if requested
        if (isset($params['redirect']) && $params['redirect']) {
            $response_data['redirect'] = admin_url('edit.php?post_type=eb_course&page=eb-manage-groups');
        }

        return new WP_REST_Response($response_data, 200);
    }

    public function eb_delete_enrolled_user($request)
    {
        $params = $request->get_json_params();

        if (!isset($params['nonce']) || !wp_verify_nonce(sanitize_text_field($params['nonce']), 'wdm_ebbp_enroll_nonce')) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('Unable to unenroll user, due to security reasons.', 'edwiser-bridge-pro')),
                403
            );
        }

        if (!isset($params['user_id']) || empty($params['user_id']) || !isset($params['cohort_id']) || empty($params['cohort_id'])) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('User ID and Group ID are required.', 'edwiser-bridge-pro')),
                400
            );
        }

        $user_id = intval($params['user_id']);
        $cohort_id = intval($params['cohort_id']);

        $cohort_details = includes\bulkPurchase\get_cohort_details($cohort_id);
        if (isset($cohort_details['courses'])) {
            $courses = $cohort_details['courses'];
            update_user_meta($user_id, 'eb_pending_enrollment', $courses);
        }

        $current_user_id = get_current_user_id();
        $cohort_manager = new includes\bulkPurchase\Eb_Bp_Cohort_Manage_User();
        $result = $cohort_manager->delete_user_from_cohort($user_id, $cohort_id, $current_user_id);

        $user_info = get_userdata($user_id);

        if (isset($result['status']) && $result['status']) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => sprintf(__('User with the email "%s" has been unenrolled successfully.', 'edwiser-bridge-pro'), $user_info->user_email),
            ), 200);
        } else {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => sprintf(__('Unable to unenroll user with the email "%s".', 'edwiser-bridge-pro'), $user_info->user_email),
                ),
                500
            );
        }
    }

    /**
     * Delete multiple users from a cohort
     * 
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response Response object
     */
    public function eb_delete_multiple_enrolled_users($request)
    {
        $params = $request->get_json_params();

        // Verify nonce
        if (!isset($params['nonce']) || !wp_verify_nonce(sanitize_text_field($params['nonce']), 'wdm_eb_gp_mng_nonce')) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Security check failed.', 'edwiser-bridge-pro')
                ),
                403
            );
        }

        // Validate required parameters
        if (
            !isset($params['user_ids']) || empty($params['user_ids']) ||
            !isset($params['cohort_id']) || empty($params['cohort_id']) ||
            !isset($params['total']) || !isset($params['processed_users'])
        ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __('Missing required parameters.', 'edwiser-bridge-pro')
                ),
                400
            );
        }

        $user_ids = array_map('intval', $params['user_ids']);
        $cohort_id = intval($params['cohort_id']);
        $total = intval($params['total']);
        $processed_users = intval($params['processed_users']);
        $current_user_id = get_current_user_id();
        $cohort_manager = new includes\bulkPurchase\Eb_Bp_Cohort_Manage_User();

        $success_users = array();
        $failed_users = array();
        $enrolled_user_ids = array();
        $total_seats_freed = 0;
        $processed_count = 0;

        // Get cohort details for pending enrollment
        $cohort_details = includes\bulkPurchase\get_cohort_details($cohort_id);
        $courses = isset($cohort_details['courses']) ? $cohort_details['courses'] : array();

        foreach ($user_ids as $user_id) {
            $processed_count++;

            // Update pending enrollment before deletion
            if (!empty($courses)) {
                update_user_meta($user_id, 'eb_pending_enrollment', $courses);
            }

            // Delete user from cohort
            $result = $cohort_manager->delete_user_from_cohort($user_id, $cohort_id, $current_user_id);
            $user_info = get_userdata($user_id);

            if (isset($result['status']) && $result['status']) {
                $total_seats_freed += isset($result['qty']) ? intval($result['qty']) : 0;
                array_push($enrolled_user_ids, $user_id);
                $success_users[] = array(
                    'user_id' => $user_id,
                    'email' => $user_info->user_email,
                    'name' => $user_info->display_name,
                );
            } else {
                $failed_users[] = array(
                    'user_id' => $user_id,
                    'email' => $user_info->user_email,
                    'name' => $user_info->display_name,
                );
            }
        }

        // Create instance of enrollment manager
        if (($processed_users + $processed_count) == $total) {
            // Final batch - get complete data and return full response
            $this->eb_update_batch_removal_data(
                $cohort_id,
                $failed_users,
                $success_users,
                $enrolled_user_ids
            );

            $saved_response = $this->eb_get_and_clear_batch_removal_data($cohort_id);

            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'total_processed' => $total,
                    'processed_users' => $processed_users + $processed_count,
                    'seats_freed' => $total_seats_freed,
                    'is_final_batch' => true,
                    'failed_removals' => !empty($saved_response['failed_removals']) ? $saved_response['failed_removals'] : array(),
                    'successful_removals' => !empty($saved_response['successful_removals']) ? $saved_response['successful_removals'] : array(),
                    'removed_user_ids' => !empty($saved_response['removed_user_ids']) ? $saved_response['removed_user_ids'] : array(),
                )
            ), 200);
        } else {
            // Intermediate batch - store data and return partial response
            $this->eb_update_batch_removal_data(
                $cohort_id,
                $failed_users,
                $success_users,
                $enrolled_user_ids
            );

            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'total_processed' => $total,
                    'processed_users' => $processed_users + $processed_count,
                    'seats_freed' => $total_seats_freed,
                    'is_final_batch' => false
                )
            ), 200);
        }
    }

    public function eb_update_user($request)
    {
        $params = $request->get_json_params();

        // Nonce verification
        if (!isset($params['nonce']) || !wp_verify_nonce(sanitize_text_field($params['nonce']), 'wdm_eb_gp_mng_nonce')) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('Security check failed.', 'edwiser-bridge-pro')),
                403
            );
        }

        // Parameter validation
        $user_id =  intval($params['user_id']);
        $first_name =  sanitize_text_field($params['first_name']);
        $last_name = sanitize_text_field($params['last_name']);
        $email = sanitize_email($params['email']);

        if ((isset($user_id) && empty($user_id)) || (isset($first_name) && empty($first_name)) || (isset($last_name) && empty($last_name)) || (isset($email) && !is_email($email))) {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('User data is inappropriate.', 'edwiser-bridge-pro')),
                400
            );
        }

        // Update WP user
        $update_user_result = wp_update_user(array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_email' => $email,
        ));

        if (is_wp_error($update_user_result)) {
            return new WP_REST_Response(
                array('success' => false, 'message' => $update_user_result->get_error_message()),
                500
            );
        }

        // Update Moodle user
        $moodle_user_id = get_user_meta($user_id, 'moodle_user_id', true);

        $moodle_user_data = array(
            'id'        => $moodle_user_id,
            'firstname' => $first_name,
            'lastname'  => $last_name,
            'email'     => $email,
        );

        $moodle_user_updated = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->userManager()->createMoodleUser($moodle_user_data, 1);

        if (isset($moodle_user_updated['user_updated']) && 1 === $moodle_user_updated['user_updated']) {
            return new WP_REST_Response(
                array('success' => true, 'message' => __('User data has been updated successfully.', 'edwiser-bridge-pro')),
                200
            );
        } else {
            return new WP_REST_Response(
                array('success' => false, 'message' => __('Failed to update user on Moodle.', 'edwiser-bridge-pro')),
                500
            );
        }
    }

    public function eb_enroll_user_in_cohort($request)
    {
        // Extract and sanitize parameters
        $params = $request->get_json_params();
        $nonce = isset($params['nonce']) ? sanitize_text_field($params['nonce']) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'wdm_ebbp_enroll_nonce')) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Security check failed, try reloading the page.', 'edwiser-bridge-pro'),
            ], 403);
        }

        $cohort_id = isset($params['cohort_id']) ? sanitize_text_field($params['cohort_id']) : '';
        $first_names = isset($params['firstname']) ? array_map('sanitize_text_field', (array)$params['firstname']) : [];
        $last_names = isset($params['lastname']) ? array_map('sanitize_text_field', (array)$params['lastname']) : [];
        $emails = isset($params['email']) ? array_map('sanitize_text_field', (array)$params['email']) : [];
        $total = isset($params['total']) ? intval($params['total']) : null;
        $processed_users = isset($params['processed_users']) ? intval($params['processed_users']) : null;

        // Validate required data
        if (empty($cohort_id) || empty($first_names) || empty($last_names) || empty($emails)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Invalid user data.', 'edwiser-bridge-pro'),
            ], 400);
        }

        // Get cohort details
        $user_manager = new includes\bulkPurchase\Eb_Bp_User_Manager();
        $cohort_details = $user_manager->get_cohort_details($cohort_id);
        $remaining_seats = $cohort_details['quantity'];
        $mdl_cohort_id = $cohort_details['mdl_cohort_id'];
        $products = $cohort_details['products'];
        $courses = $cohort_details['courses'];
        $current_user_id = get_current_user_id();
        $user_role = 'Student';
        $wp_user_role = eb_bp_get_wp_user_reg_role();
        $is_bulk = $total > 1;

        // Check available seats
        if (count($emails) > $remaining_seats) {
            return new WP_REST_Response([
                'success' => false,
                'message' => __('Available seats are less than requested quantity.', 'edwiser-bridge-pro'),
            ], 400);
        }

        $enroll_errors = [];
        $enroll_success = [];
        $already_enrolled = [];
        $users_for_moodle = [];
        $processed_count = 0;

        foreach ($emails as $i => $email) {
            $first_name = $first_names[$i] ?? '';
            $last_name = $last_names[$i] ?? '';
            if (empty($first_name) || empty($last_name) || empty($email)) {
                continue;
            }
            $processed_count++;
            $user = get_user_by('email', $email);
            $password = wp_generate_password();
            $user_id = 0;
            if (email_exists($email)) {
                $user = get_user_by('email', $email);
                $user_id = $user->ID;
                $user_login = $user->user_login;
                // Already enrolled?
                if ($this->is_user_already_enrolled($courses, $user_id)) {
                    $already_enrolled[] = $user->user_email;
                    continue;
                }
            } else {
                $user_login = sanitize_user(current(explode('@', $email)), true);
                $append = 1;
                $original_login = $user_login;
                while (username_exists($user_login)) {
                    $user_login = $original_login . $append;
                    $append++;
                }
                $wp_user_data = apply_filters(
                    'eb_bp_cohort_new_user_data',
                    [
                        'user_login' => $user_login,
                        'first_name' => $first_name,
                        'last_name'  => $last_name,
                        'user_pass'  => $password,
                        'user_email' => $email,
                        'role'       => $wp_user_role,
                    ]
                );
                $user_id = wp_insert_user($wp_user_data);
                $args = [
                    'user_email' => $email,
                    'username'   => $user_login,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'password'   => $password,
                ];
                do_action('eb_created_user_bulk_enroll', $args);
                if (is_wp_error($user_id)) {
                    continue;
                }
            }
            $users_for_moodle[] = [
                'firstname' => $first_name,
                'lastname'  => $last_name,
                'password'  => $password,
                'username'  => strtolower($user_login),
                'email'     => $email,
            ];
            $user_manager->eb_update_pending_course_enrollment($user_id, $products);
        }

        // Moodle enrollment
        if (!empty($users_for_moodle)) {
            $conn_helper = includes\bulkPurchase\Eb_Bp_Manage_Cohort::get_connection_helper();
            $moodle_function = 'auth_edwiserbridge_manage_user_cohort_enrollment';
            $moodle_response = $conn_helper->connect_moodle_with_args_helper(
                $moodle_function,
                [
                    'cohort_id' => $mdl_cohort_id,
                    'users'     => $users_for_moodle,
                ]
            );
            if (isset($moodle_response['response_data']->users)) {
                foreach ($moodle_response['response_data']->users as $moodle_user) {
                    if ((isset($moodle_user->creation_error) && $moodle_user->creation_error) || (isset($moodle_user->enrolled) && !$moodle_user->enrolled)) {
                        $enroll_errors[] = $moodle_user->email;
                    } else {
                        $user = get_user_by('email', $moodle_user->email);
                        if (!get_user_meta($user->ID, 'moodle_user_id', true)) {
                            update_user_meta($user->ID, 'moodle_user_id', $moodle_user->user_id);
                            $args = [
                                'user_email' => $moodle_user->email,
                                'username'   => $moodle_user->username,
                                'first_name' => $user->first_name,
                                'last_name'  => $user->last_name,
                                'password'   => $moodle_user->password,
                            ];
                            do_action('eb_linked_to_existing_wordpress_user', $args);
                        }
                        $this->enroll_user($mdl_cohort_id, $user->ID, $current_user_id, $user_role, $products);
                        $user_manager->update_wordpres_user_role($user->ID, $user->user_email);
                        $email_args = [
                            'user_email'        => $user->user_email,
                            'username'          => $user->user_login,
                            'last_name'         => $user->last_name,
                            'first_name'        => $user->first_name,
                            'mdl_cohort_id'     => $mdl_cohort_id,
                            'cohort_manager_id' => $current_user_id,
                        ];
                        do_action('eb_bp_new_user_to_cohort', $email_args);
                        $remaining_seats--;
                        $user_manager->update_bp_cohort_info_table_on_enrollment($mdl_cohort_id, $remaining_seats);
                        $enroll_success[] = $moodle_user->email;
                    }
                }
            }
        }

        // Prepare cohort name for response
        $current_user = wp_get_current_user();
        $cohort_details['name'] = str_replace($current_user->user_login . '_', '', $cohort_details['name']);
        $cohort_details['name'] .= ' (' . $remaining_seats . ') ';

        // CSV bulk response handling
        if ($is_bulk && $total !== null && $processed_users !== null) {
            if (($processed_users + $processed_count) == $total) {
                $user_manager->set_csv_users_response_data($current_user->ID, $mdl_cohort_id, $enroll_errors, $already_enrolled, $enroll_success);
                $csv_response = $user_manager->get_csv_users_response_data($current_user->ID, $mdl_cohort_id, $enroll_errors, $already_enrolled, $enroll_success);
                $enroll_errors = $csv_response['enrollment_err'];
                $already_enrolled = $csv_response['already_enrolled_user'];
                $enroll_success = $csv_response['enrollment_suc'];
            } else {
                $user_manager->set_csv_users_response_data($current_user->ID, $mdl_cohort_id, $enroll_errors, $already_enrolled, $enroll_success);
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'cohort' => html_entity_decode($cohort_details['name']),
            'enrollment_result' => array(
                'enroll_errors' => $enroll_errors,
                'already_enrolled' => $already_enrolled,
                'enroll_success' => $enroll_success,
                'is_bulk' => $is_bulk,
            ),
            'processed_users' => $processed_count,
        ], 200);
    }

    /**
     * Check if the user is already enrolled in all the given courses.
     * @param array $course_ids
     * @param int $user_id
     * @return bool
     */
    private function is_user_already_enrolled($course_ids, $user_id)
    {
        global $wpdb;
        $mdl_enroll = $wpdb->prefix . 'moodle_enrollment';
        $result = $wpdb->get_col($wpdb->prepare("SELECT course_id FROM {$mdl_enroll} WHERE user_id=%d", $user_id));
        if (array_intersect($course_ids, $result) === $course_ids) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Enroll a user in all courses associated with the cohort's products.
     * @param int $mdl_cohort_id
     * @param int $user_id
     * @param int $curr_user_id
     * @param string $user_role
     * @param array $prod_ids
     * @return bool
     */
    private function enroll_user($mdl_cohort_id, $user_id, $curr_user_id, $user_role, $prod_ids)
    {
        global $wpdb;
        $status = false;
        $prod_ids = maybe_unserialize($prod_ids);
        $course_post_ids = $this->get_product_courses(array_keys($prod_ids));
        foreach ($course_post_ids as $course_id => $prod_id) {
            $status = $wpdb->insert(
                $wpdb->prefix . 'moodle_enrollment',
                array(
                    'user_id'       => $user_id,
                    'course_id'     => $course_id,
                    'role_id'       => '5',
                    'time'          => date('Y-m-d H:i:s'),
                    'enrolled_by'   => $curr_user_id,
                    'product_id'    => $prod_id,
                    'mdl_cohort_id' => $mdl_cohort_id,
                    'role'          => $user_role,
                ),
                array(
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                )
            );
        }
        return $status;
    }

    /**
     * Get all course IDs associated with the given product IDs.
     * @param array $product_ids
     * @return array course_id => product_id
     */
    private function get_product_courses($product_ids)
    {
        global $wpdb;
        $tbl_name = $wpdb->prefix . 'eb_moodle_course_products';
        $eb_course_ids = array();
        $stmt = "SELECT DISTINCT `product_id`,`moodle_post_id` FROM `{$tbl_name}` WHERE `product_id` in ('" . implode("','", $product_ids) . "');";
        $result = $wpdb->get_results($stmt, ARRAY_A);
        foreach ($result as $rec) {
            $eb_course_ids[$rec['moodle_post_id']] = $rec['product_id'];
        }
        return $eb_course_ids;
    }

    /**
     * Get only enrollment details and group name for a group
     */
    public function eb_get_group_enrollment_details($request)
    {
        $group_id = $request->get_param('id');

        // Validate the group ID parameter
        if (empty($group_id) || !is_numeric($group_id)) {
            return new WP_REST_Response(array(
                'message' => __('Invalid request parameters', 'edwiser-bridge-pro')
            ), 400);
        }

        global $wpdb;
        $tbl_name = $wpdb->prefix . 'bp_cohort_info';

        // Get group name and products
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT PRODUCTS, NAME FROM {$tbl_name} WHERE MDL_COHORT_ID = %d;",
                $group_id
            ),
            ARRAY_A
        );

        if (empty($result)) {
            return new WP_REST_Response(array(
                'message' => __('Group not found', 'edwiser-bridge-pro')
            ), 404);
        }

        $products = maybe_unserialize($result['PRODUCTS']);
        $group_name = $result['NAME'];

        // Get enrolled users information
        $enrollment_tbl = $wpdb->prefix . 'moodle_enrollment';
        $enrolled_users = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT `user_id` FROM `{$enrollment_tbl}` WHERE `mdl_cohort_id` = '%d'",
                $group_id
            )
        );

        // Calculate available seats
        $available_seats = min($products);
        if (null === $available_seats) {
            $available_seats = 0;
        }

        // Process enrolled users data
        $enrolled_users_data = array();
        if (!empty($enrolled_users)) {
            foreach ($enrolled_users as $user) {
                $user_data = get_userdata($user->user_id);
                if ($user_data) {
                    $enrolled_users_data[] = array(
                        'user_id' => $user->user_id,
                        'first_name' => $user_data->first_name,
                        'last_name' => $user_data->last_name,
                        'full_name' => $user_data->first_name . ' ' . $user_data->last_name,
                        'email' => $user_data->user_email,
                        'display_name' => $user_data->display_name,
                    );
                }
            }
        }

        return new WP_REST_Response(array(
            'group_name' => $group_name . ' (' . $available_seats . ')',
            'enrollment_details' => array(
                'available_seats' => $available_seats,
                'enrolled_users_count' => count($enrolled_users),
                'remaining_seats' => max(0, $available_seats - count($enrolled_users)),
                'enrolled_users' => $enrolled_users_data
            )
        ), 200);
    }

    /**
     * Updates and stores the state of a batch user removal process.
     *
     * This function safely handles merging new data with existing data from previous batches.
     *
     * @param int   $cohort_id        The ID of the cohort being processed.
     * @param array $failed_users     An array of users who failed to be removed in the current batch.
     * @param array $success_users    An array of users who were successfully removed in the current batch.
     * @param array $enrolled_user_ids An array of user IDs that were successfully removed.
     */
    private function eb_update_batch_removal_data($cohort_id, $failed_users, $success_users, $enrolled_user_ids)
    {
        $option_key = 'eb_batch_removed_users_' . $cohort_id;
        $existing_data = get_option($option_key, array());

        $existing_failed = isset($existing_data['failed_removals']) ? $existing_data['failed_removals'] : array();
        $existing_success = isset($existing_data['successful_removals']) ? $existing_data['successful_removals'] : array();
        $existing_enrolled_ids = isset($existing_data['removed_user_ids']) ? $existing_data['removed_user_ids'] : array();

        $updated_data = array(
            'failed_removals'     => array_merge($existing_failed, $failed_users),
            'successful_removals' => array_merge($existing_success, $success_users),
            'removed_user_ids'  => array_merge($existing_enrolled_ids, $enrolled_user_ids),
        );

        update_option($option_key, $updated_data);
    }

    /**
     * Retrieves and then deletes the stored data for a batch removal process.
     *
     * @param int $cohort_id The ID of the cohort.
     * @return array The stored data.
     */
    private function eb_get_and_clear_batch_removal_data($cohort_id)
    {
        $option_key = 'eb_batch_removed_users_' . $cohort_id;
        $data = get_option($option_key, array());
        delete_option($option_key);
        return $data;
    }
}

new EdwiserBridgeBlocksPro_GroupManagement_API();

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.10.0
 * @since form-maker    1.15.31
 */
class Zoho_Flow_Form_Maker extends Zoho_Flow_Service{
    
    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "form_entry_submitted",
    );
    
    /**
     * List forms
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int     $limit         Number of results. Default: 200.
     * @type string  $order_by      List order by the field. Default: id.
     * @type string  $order         List order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response    WP_REST_Response array with form details
     */
    public function list_forms( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'title'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ($request['order_by'] && (in_array($request['order_by'], $order_by_allowed))) ? $request['order_by'] : 'id';
        $order = ($request['order'] && (in_array($request['order'], $order_allowed))) ? $request['order'] : 'DESC';
        $limit = ($request['limit']) ? $request['limit'] : '200';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id,title,type,published FROM {$wpdb->prefix}formmaker ORDER BY $order_by $order LIMIT %d",
                $limit
            ), 'ARRAY_A'
                );
        return rest_ensure_response( $results );
    }
    
    /**
     * List form fields
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int  form_id   Form ID to retrive the fields for.
     *
     * @return WP_REST_Response|WP_Error    WP_REST_Response array with form field details | WP_Error object with error details.
     */
    public function list_form_fields( $request ){
        $form_id = $request['form_id'];
        $form = $this->is_valid_form( $form_id );
        if( $form ){
            $fields = explode('*:*new_field*:*', $form['form_fields']);
            $fields = array_slice($fields, 0, count($fields) - 1);
            $fields_array = array();
            foreach ( $fields as $field ) {
                $field_temp = array();
                $temp = explode('*:*id*:*', $field);
                $field_temp['id'] = $temp[0];
                $temp = explode('*:*type*:*', $temp[1]);
                $field_temp['type'] = $temp[0];
                $temp = explode('*:*w_field_label*:*', $temp[1]);
                $field_temp['label'] = $temp[0];
                $field_temp['param'] = $temp[0];
                array_push($fields_array, $field_temp);
            }
            return rest_ensure_response( $fields_array );
        }
        return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 404 ) );
    }
    
    private function is_valid_form( $form_id ){
        if( isset( $form_id ) && is_numeric( $form_id )){
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}formmaker WHERE `id` = %d",
                    $form_id
                ), 'ARRAY_A'
                    );
            if( !empty( $result ) ){
                return $result;
            }
        }
        return false;
    }
    
    private function get_submission( $form_id, $submission_id ){
        if( isset( $form_id ) && is_numeric( $form_id ) && isset( $submission_id  ) && is_numeric( $submission_id  ) ){
            global $wpdb;
            $result = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT element_label,element_value,ip,user_id_wd FROM {$wpdb->prefix}formmaker_submits WHERE `form_id` = %d AND `group_id` = %d",
                    $form_id,
                    $submission_id,
                ), 'ARRAY_A'
                    );
            if( !empty( $result ) ){
                return $result;
            }
        }
        return false;
    }
    
    /**
     * Creates a webhook entry
     * The events available in $supported_events array only accepted
     *
     * @param WP_REST_Request   $request  WP_REST_Request object.
     * @return WP_REST_Response|WP_Error    WP_REST_Response array with Webhook ID | WP_Error object with error details.
     */
    public function create_webhook( $request ){
        $entry = json_decode( $request->get_body() );
        if( ( !isset( $entry->form_id ) ) || !$this->is_valid_form( $entry->form_id ) ){
            return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 400 ) );
        }
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
                'form_id' => $entry->form_id
            );
            $post_name = "Form Maker ";
            $post_id = $this->create_webhook_post( $post_name, $args );
            if( is_wp_error( $post_id ) ){
                $errors = $post_id->get_error_messages();
                return new WP_Error( 'rest_bad_request', $errors, array( 'status' => 400 ) );
            }
            return rest_ensure_response(
                array(
                    'webhook_id' => $post_id
                ) );
        }
        else{
            return new WP_Error( 'rest_bad_request', 'Data validation failed', array( 'status' => 400 ) );
        }
    }
    
    /**
     * Deletes a webhook entry
     * Webhook ID returned from webhook create event should be used. Use minimum user scope.
     *
     * @param WP_REST_Request   $request    WP_REST_Request object.
     * @return WP_REST_Response|WP_Error    WP_REST_Response array with success message | WP_Error object with error details.
     */
    public function delete_webhook( $request ){
        $webhook_id = $request->get_url_params()['webhook_id'];
        if( is_numeric( $webhook_id ) ){
            $webhook_post = $this->get_webhook_post( $webhook_id );
            if( !empty( $webhook_post[0]->ID ) ){
                $delete_webhook = $this->delete_webhook_post( $webhook_id );
                if( is_wp_error( $delete_webhook ) ){
                    $errors = $delete_webhook->get_error_messages();
                    return new WP_Error( 'rest_bad_request', $errors, array( 'status' => 400 ) );
                }
                else{
                    return rest_ensure_response( array( 'message' => 'Success' ) );
                }
            }
            else{
                return new WP_Error( 'rest_bad_request', 'Invalid webhook ID', array( 'status' => 400 ) );
            }
        }
        else{
            return new WP_Error( 'rest_bad_request', 'Invalid webhook ID', array( 'status' => 400 ) );
        }
    }
    
    /**
     * Fires after entry is processed.
     *    
     * @param   array   $entry_params  Form entry details.
     */
    public function payload_form_entry_submitted( $entry_params ){
        $form_id = $entry_params['form_id'];
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $entry_details = $entry_params['custom_fields'];
            $entry_details['submission_data'] = $this->get_submission( $form_id, $entry_details['subid'] );
            $event_data = array(
                'event' => 'form_entry_submitted',
                'data' => $entry_details
            );
            foreach( $webhooks as $webhook ){
                $event_data['id'] = $webhook->ID;
                $url = $webhook->url;
                zoho_flow_execute_webhook( $url, $event_data, array() );
            }
        }
    }
    
    /**
     * Default API
     * Get user and system info.
     *
     * @return array|WP_Error System and logged in user details | WP_Error object with error details.
     */
    public function get_system_info(){
        $system_info = parent::get_system_info();
        if( ! function_exists( 'get_plugin_data' ) ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_dir = ABSPATH . 'wp-content/plugins/form-maker/form-maker.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['form_maker'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
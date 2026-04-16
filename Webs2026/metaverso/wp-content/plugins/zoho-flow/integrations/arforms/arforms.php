<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.11.0
 * @since arforms       1.7.0
 */
class Zoho_Flow_ARForms extends Zoho_Flow_Service{
    
    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "form_entry_submitted",
        "pro_form_entry_submitted"
    );
    
    /**
     * List forms
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $limit          Number of results. Default: 200.
     * @type    string  $order_by       List order by the field. Default: id.
     * @type    string  $order          List order Values: ASC|DESC. Default: DESC.
     * @type    string  $form_type      Type of the form.Default: ALL. Allowed Values PRO | LITE
     *
     * @return WP_REST_Response    WP_REST_Response array with form details
     */
    public function list_forms( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'form_key',
            'name',
            'created_date'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( isset( $request['order_by'] ) && ( in_array($request['order_by'], $order_by_allowed ) ) ) ? $request['order_by'] : 'id';
        $order = ( isset( $request['order'] ) && ( in_array( $request['order'], $order_allowed ) ) ) ? $request['order'] : 'DESC';
        $limit = isset( $request['limit'] ) ? $request['limit'] : '200';
        $query = "SELECT id, form_key, name, description, status, created_date, arf_is_lite_form, arf_lite_form_id FROM {$wpdb->prefix}arf_forms  WHERE is_template = 0";
        if ( isset( $request['form_type'] ) ) {
            if ( 'PRO' === $request['form_type'] ) {
                $query .= $wpdb->prepare(" AND arf_is_lite_form = 0");
            }
            elseif ( 'LITE' === $request['form_type'] ) {
                $query .= $wpdb->prepare(" AND arf_is_lite_form = 1");
            }
        }
        $query .= $wpdb->prepare(
            " ORDER BY $order_by $order LIMIT %d",
            $limit
            );
        $results = $wpdb->get_results( $query, 'ARRAY_A' );
        
        return rest_ensure_response( $results );
    }
    
    /**
     * List form fields
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int    $form_id   ID of the Form.
     *
     * @return WP_REST_Response    WP_REST_Response Array of field details
     */
    public function list_form_fields( $request ){
        $form_id = $request->get_url_params()['form_id'];
        if( isset( $form_id) && $this->is_valid_form( $form_id ) ){
            global $wpdb;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}arf_fields WHERE form_id = %d ORDER BY id ASC",
                    $form_id
                ), 'ARRAY_A'
                    );
            foreach ( $results as $index => $field ){
                $results[ $index ]['field_options'] = json_decode( maybe_unserialize( $field['field_options'], true ) );
                $results[ $index ]['options'] = json_decode( maybe_unserialize( $field['options'], true ) );
            }
            return rest_ensure_response( $results );
        }
        return new WP_Error( 'rest_bad_request', 'Form does not exist!', array( 'status' => 404 ) );
    }
    
    /**
     * Fetch form entry details
     *
     * @param int $entry_id  ID of the entry.
     * @return array | bool  entry details ifexists | false for others.
     */
    private function fetch_form_entry( $entry_id ){
        if( isset( $entry_id ) && is_numeric( $entry_id )){
            global $wpdb;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}arf_entries WHERE id = %d LIMIT 1",
                    $entry_id
                ), 'ARRAY_A'
               );
            if( !empty( $results ) ){
                $entry_details = $results[0];
                $entry_details[ 'description' ] = maybe_unserialize( $entry_details[ 'description' ] );
                $values = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, entry_value, field_id FROM {$wpdb->prefix}arf_entry_values WHERE entry_id = %d LIMIT 1000",
                        $entry_id
                    ), 'ARRAY_A'
                        );
                foreach ( $values as $value ){
                    $entry_details[ 'fields' ][] = array(
                        'id' => $value['id'],
                        'field_id' => $value['field_id'],
                        'entry_value' => maybe_unserialize( $value['entry_value'], true )
                    );
                }
                return $entry_details;
            }
        }
        return false;
    }
    
    /**
     * Check whether the form ID is valid or not.
     *
     * @param int $form_id  ID of the form.
     * @return bool  true if form exists | false for others.
     */
    private function is_valid_form( $form_id ){
        if( isset( $form_id ) && is_numeric( $form_id )){
            global $wpdb;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}arf_forms WHERE id = %d AND is_template = 0 LIMIT 1",
                    $form_id
                ), 'ARRAY_A'
                    );
            if( !empty( $results ) ){
                return true;
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
            $post_name = "ARForms ";
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
     * @param   array       $params         Form property details.
     * @param   array       $errors         Error details.
     * @param   stdClass    $form           Form object.
     * @param   array       $entry_values   Form entry details
     */
    public function payload_form_entry_submitted( $params, $errors, $form, $entry_values ){
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form->id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $event_data = array(
                'event' => 'form_entry_submitted',
                'data' => $entry_values
            );
            foreach( $webhooks as $webhook ){
                $event_data['id'] = $webhook->ID;
                $url = $webhook->url;
                zoho_flow_execute_webhook( $url, $event_data, array() );
            }
        }
    }
    
    /**
     * Fires after entry is processed.
     *
     * @param   int       $entry_id        Form entry ID.
     * @param   int       $form_id         Form ID.
     */
    public function payload_pro_form_entry_submitted( $entry_id, $form_id ){
        $args = array(
            'event' => 'pro_form_entry_submitted',
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $form_entry  = $this->fetch_form_entry( $entry_id );
            if( $form_entry ){
                $event_data = array(
                    'event' => 'pro_form_entry_submitted',
                    'data' => $form_entry
                );
                foreach( $webhooks as $webhook ){
                    $event_data['id'] = $webhook->ID;
                    $url = $webhook->url;
                    zoho_flow_execute_webhook( $url, $event_data, array() );
                }
            }
        }
    }
    
    /**
     * default API
     * Get user and system info.
     *
     * @return array|WP_Error System and logged in user details | WP_Error object with error details.
     */
    public function get_system_info(){
        $system_info = parent::get_system_info();
        if( ! function_exists( 'get_plugin_data' ) ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_dir = ABSPATH . 'wp-content/plugins/arforms-form-builder/arforms-form-builder.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['arforms'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
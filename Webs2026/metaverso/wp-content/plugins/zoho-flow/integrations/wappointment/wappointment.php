<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.11.0
 * @since wappointment  2.6.7
 */
class Zoho_Flow_Wappointment extends Zoho_Flow_Service{
    
    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "appointment_confirmed",
        "appointment_rescheduled",
        "appointment_canceled"
    );
    
    /**
     * List services
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int     $limit         Number of results. Default: 200.
     * @type string  $order_by      List order by the field. Default: updated_at.
     * @type string  $order         List order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response    WP_REST_Response array with service details
     */
    public function list_services( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'sorting',
            'name',
            'created_at',
            'updated_at'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ($request['order_by'] && (in_array($request['order_by'], $order_by_allowed))) ? $request['order_by'] : 'updated_at';
        $order = ($request['order'] && (in_array($request['order'], $order_allowed))) ? $request['order'] : 'DESC';
        $limit = ($request['limit']) ? $request['limit'] : '200';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}wappo_services ORDER BY $order_by $order LIMIT %d",
                $limit
            ), 'ARRAY_A'
                );
        foreach ($results as $index => $row ){
            $results[$index]['options'] = json_decode( $results[$index]['options'], true );
        }
        return rest_ensure_response( $results );
    }
    
    /**
     * Fetch service details
     * @param WP_REST_Request $request WP_REST_Request object.
     * @return WP_REST_Response    WP_REST_Response array with service details
     */
    public function fetch_service( $request ){
        $service_id = $request->get_url_params()['service_id'];
        $service = $this->is_valid_service( $service_id );
        if( $service ){
            return rest_ensure_response( $service );
        }
        return new WP_Error( 'rest_bad_request', "Service does not exist!", array( 'status' => 404 ) );
    }
    
    private function is_valid_service( $service_id ){
        if( isset( $service_id ) && is_numeric( $service_id )){
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wappo_services WHERE `id` = %d",
                    $service_id
                ), 'ARRAY_A'
                    );
            if( !empty( $result ) ){
                $result['options'] = json_decode( $result['options'], true );
                return $result;
            }
        }
        return false;
    }
    
    private function is_valid_client( $client_id ){
        if( isset( $client_id ) && is_numeric( $client_id )){
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wappo_clients WHERE `id` = %d",
                    $client_id
                ), 'ARRAY_A'
                    );
            if( !empty( $result ) ){
                $result['options'] = json_decode( $result['options'], true );
                return $result;
            }
        }
        return false;
    }
    
    private function is_valid_staff( $staff_id ){
        if( isset( $staff_id ) && is_numeric( $staff_id )){
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wappo_calendars WHERE `id` = %d",
                    $staff_id
                ), 'ARRAY_A'
                    );
            if( !empty( $result ) ){
                $result['options'] = json_decode( $result['options'], true );
                return $result;
            }
        }
        return false;
    }
    
    private function is_valid_location( $location_id ){
        if( isset( $location_id ) && is_numeric( $location_id )){
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}wappo_locations WHERE `id` = %d",
                    $location_id
                ), 'ARRAY_A'
                    );
            if( !empty( $result ) ){
                $result['options'] = json_decode( $result['options'], true );
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
        if( ( !isset( $entry->service_id ) ) || !$this->is_valid_service( $entry->service_id ) ){
            return new WP_Error( 'rest_bad_request', "Service does not exist!", array( 'status' => 400 ) );
        }
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
                'service_id' => $entry->service_id
            );
            $post_name = "Wappointment ";
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
     * Fires after the appointment is confirmed.
     *
     * @param   object       $event        Event class object
     */
    public function payload_appointment_confirmed( $event ){
        $args = array(
            'event' => 'appointment_confirmed',
            'service_id' => $event->getAppointment()['service_id']
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $appointment  = $event->getAppointment()->toArraySpecial();
            $appointment['service'] = $this->is_valid_service( $appointment['service_id'] );
            $appointment['client'] = $this->is_valid_client( $appointment['client_id'] );
            $appointment['staff'] = $this->is_valid_staff( $appointment['staff_id'] );
            $appointment['location'] = $this->is_valid_location( $appointment['location_id'] );
            $event_data = array(
                'event' => 'appointment_confirmed',
                'data' => $appointment
            );
            foreach( $webhooks as $webhook ){
                $event_data['id'] = $webhook->ID;
                $url = $webhook->url;
                zoho_flow_execute_webhook( $url, $event_data, array() );
            }
        }
    }
    
    /**
     * Fires after the appointment is rescheduled.
     *
     * @param   object       $event        Event class object
     */
    public function payload_appointment_rescheduled( $event ){
        $args = array(
            'event' => 'appointment_rescheduled',
            'service_id' => $event->getAppointment()['service_id']
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $appointment  = $event->getAppointment()->toArraySpecial();
            $appointment['service'] = $this->is_valid_service( $appointment['service_id'] );
            $appointment['client'] = $this->is_valid_client( $appointment['client_id'] );
            $appointment['staff'] = $this->is_valid_staff( $appointment['staff_id'] );
            $appointment['location'] = $this->is_valid_location( $appointment['location_id'] );
            $event_data = array(
                'event' => 'appointment_rescheduled',
                'data' => $appointment
            );
            foreach( $webhooks as $webhook ){
                $event_data['id'] = $webhook->ID;
                $url = $webhook->url;
                zoho_flow_execute_webhook( $url, $event_data, array() );
            }
        }
    }
    
    /**
     * Fires after the appointment is canceled.
     *
     * @param   object       $event        Event class object
     */
    public function payload_appointment_canceled( $event ){
        $args = array(
            'event' => 'appointment_canceled',
            'service_id' => $event->getAppointment()['service_id']
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $appointment  = $event->getAppointment()->toArraySpecial();
            $appointment['service'] = $this->is_valid_service( $appointment['service_id'] );
            $appointment['client'] = $this->is_valid_client( $appointment['client_id'] );
            $appointment['staff'] = $this->is_valid_staff( $appointment['staff_id'] );
            $appointment['location'] = $this->is_valid_location( $appointment['location_id'] );
            $event_data = array(
                'event' => 'appointment_canceled',
                'data' => $appointment
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/wappointment/index.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['wappointment'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow          2.11.0
 * @since restrict-content  3.2.12
 */
class Zoho_Flow_Restrict_Content extends Zoho_Flow_Service{
    
    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "membership_added",
        "membership_updated",
        "membership_activated",
        "membership_expired",
        "membership_cancelled",
        "membership_renewed",
        "payment_added"
    );
    
    /**
     * List membership levels
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return WP_REST_Response    WP_REST_Response Array of membership levels
     */
    public function list_membership_levels( $request ){
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}restrict_content_pro ORDER BY id DESC LIMIT 500"
            ), 'ARRAY_A'
                );
        return rest_ensure_response( $results );
    }
    
    /**
     * Fetch membership details
     *
     * @param int $membership_id  ID of the membership.
     * @return array|bool  Membership details if exists | false for others.
     */
    private function fetch_membership_details( $membership_id ){
        if( isset( $membership_id ) && is_numeric( $membership_id ) ){
            $membership = rcp_get_membership( $membership_id );
            if( $membership ){
                global $wpdb;
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}rcp_memberships WHERE id = %d LIMIT 1",
                        $membership_id
                    ), 'ARRAY_A'
                        );
                if( !empty( $results ) ){
                    $membership_details = $results[0];
                    if( $membership->get_customer_id() ){
                        $membership_details['customer'] = $this->fetch_customer_details( $membership->get_customer_id() );
                    }
                    if( $membership->get_object_id() ){
                        $membership_details['membership_level'] = $this->fetch_level_details( $membership->get_object_id() );
                    }
                    return $membership_details;
                }
            }
            
        }
        return false;
    }
    
    /**
     * Fetch customer details
     *
     * @param int $customer_id  ID of the customer.
     * @return array|bool  Customer details if exists | false for others.
     */
    private function fetch_customer_details( $customer_id ){
        if( isset( $customer_id ) && is_numeric( $customer_id )){
            global $wpdb;
            $customer = rcp_get_customer( $customer_id );
            if( $customer ){
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}rcp_customers WHERE id = %d LIMIT 1",
                        $customer_id
                    ), 'ARRAY_A'
                        );
                if( !empty( $results ) ){
                    $customer_details = $results[0];
                    $user = new WP_User( $customer_details[ 'user_id' ] );
                    $customer_details[ 'user' ] = $user->data;
                    unset(
                        $customer_details[ 'user' ]->user_pass,
                        $customer_details[ 'user' ]->user_activation_key
                        );
                    return $customer_details;
                }
            }
            
        }
        return false;
    }
    
    /**
     * Fetch membership level details
     *
     * @param int $level_id  ID of the level.
     * @return array|bool  Level details if exists | false for others.
     */
    private function fetch_level_details( $level_id ){
        if( isset( $level_id ) && is_numeric( $level_id )){
            global $wpdb;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}restrict_content_pro WHERE id = %d LIMIT 1",
                    $level_id
                ), 'ARRAY_A'
                    );
            if( !empty( $results ) ){
                return $results[0];
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
        
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
            );
            if( ( ( 'membership_added' === $entry->event ) || ( 'payment_added' === $entry->event ) ) && isset( $entry->level_id ) ){
                if( rcp_get_membership_level( $entry->level_id ) ){
                    $args['level_id'] = $entry->level_id;
                }
                else{
                    return new WP_Error( 'rest_bad_request', "Membership level does not exist!", array( 'status' => 400 ) );
                }
            }
            $post_name = "Restrict Content ";
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
     * Fires after membership is updated.
     *
     *
     * @param RCP_Membership $membership    Membership object.
     */
    public function payload_membership_updated( $membership ){
        $args = array(
            'event' => 'membership_updated'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $membership_details = $this->fetch_membership_details( $membership->get_id() );
            if( $membership_details ){
                $event_data = array(
                    'event' => 'membership_updated',
                    'data' => $membership_details
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
     * Fires after membership is activated.
     *
     * @param string    $old_status         Old status.
     * @param int       $membership_id      ID of the membership.
     */
    public function payload_membership_activated( $old_status, $membership_id ){
        $args = array(
            'event' => 'membership_activated'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $membership_details = $this->fetch_membership_details( $membership_id );
            if( $membership_details ){
                $membership_details['old_status'] = $old_status;
                $event_data = array(
                    'event' => 'membership_activated',
                    'data' => $membership_details
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
     * Fires after membership is expired.
     *
     * @param string    $old_status         Old status.
     * @param int       $membership_id      ID of the membership.
     */
    public function payload_membership_expired( $old_status, $membership_id ){
        $args = array(
            'event' => 'membership_expired'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $membership_details = $this->fetch_membership_details( $membership_id );
            if( $membership_details ){
                $membership_details['old_status'] = $old_status;
                $event_data = array(
                    'event' => 'membership_expired',
                    'data' => $membership_details
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
     * Fires after membership is cancelled.
     *
     * @param string    $old_status         Old status.
     * @param int       $membership_id      ID of the membership.
     */
    public function payload_membership_cancelled( $old_status, $membership_id ){
        $args = array(
            'event' => 'membership_cancelled'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $membership_details = $this->fetch_membership_details( $membership_id );
            if( $membership_details ){
                $membership_details['old_status'] = $old_status;
                $event_data = array(
                    'event' => 'membership_cancelled',
                    'data' => $membership_details
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
     * Fires after membership is renewed.
     *
     *
     * @param string         $expiration    New expiration date to be set.
	 * @param int            $membership_id ID of the membership.
	 * @param RCP_Membership $membership    Membership object.
     */
    public function payload_membership_renewed( $expiration, $membership_id, $membership ){
        $args = array(
            'event' => 'membership_renewed'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $membership_details = $this->fetch_membership_details( $membership_id );
            if( $membership_details ){
                $event_data = array(
                    'event' => 'membership_renewed',
                    'data' => $membership_details
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
     * Fires after membership is added.
     *
     * @param int   $membership_id          ID of the membership that was just added.
     * @param array $membership_data        Membership data.
     */
    public function payload_membership_added( $membership_id, $membership_data ){
        $args = array(
            'event' => 'membership_added'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $membership_details = $this->fetch_membership_details( $membership_id );
            if( $membership_details ){
                $event_data = array(
                    'event' => 'membership_added',
                    'data' => $membership_details
                );
                foreach( $webhooks as $webhook ){
                    $event_data['id'] = $webhook->ID;
                    $level_id = get_post_meta( $webhook->ID, 'level_id', true );
                    if( empty( $level_id ) || ( $level_id == $membership_data['object_id'] ) ){
                        $url = $webhook->url;
                        zoho_flow_execute_webhook( $url, $event_data, array() );
                    }
                }
            }
        }
    }
    
    /**
     * Runs only when a payment is updated to "complete".
     *
     * @param int   $payment_id ID of the payment that was just updated.
     * @param array $args       Array of payment information that was just updated.
     * @param float $amount     Amount the payment was for.
     */
    public function payload_payment_added( $payment_id, $payment, $amount ){
        $args = array(
            'event' => 'payment_added'
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $payment['customer'] = $this->fetch_customer_details( $payment['customer_id'] );
            $event_data = array(
                'event' => 'payment_added',
                'data' => $payment
            );
            foreach( $webhooks as $webhook ){
                $event_data['id'] = $webhook->ID;
                $level_id = get_post_meta( $webhook->ID, 'level_id', true );
                if( empty( $level_id ) || ( $level_id == $payment['object_id'] ) ){
                    $url = $webhook->url;
                    zoho_flow_execute_webhook( $url, $event_data, array() );
                }
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/restrict-content/restrictcontent.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['restrict_content'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
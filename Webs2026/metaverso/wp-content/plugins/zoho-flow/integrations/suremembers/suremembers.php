<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use SureMembers\Inc\Access;
use SureMembers\Inc\Access_Groups;

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.13.0
 * @since suremembers   1.10.5
 */
class Zoho_Flow_SureMembers extends Zoho_Flow_Service{

    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "user_added_to_group",
        "user_removed_from_group"
    );

    /**
     * List groups
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return WP_REST_Response|WP_Error    WP_REST_Response array of group details | WP_Error object with error details.
     */
    public function list_groups( $request ){
        $active_groups = Access_Groups::get_active();
        $groups = array();
        foreach ( $active_groups as $group_id => $group_name ) {
			$groups[] = array(
				'id' => $group_id,
				'name'  => $group_name,
			);
		}
        return rest_ensure_response( $groups );
    }

    public function add_user_to_group( $request ){
        $user_id = $request->get_url_params()['user_id'];
        $access_group_id = $request->get_url_params()['access_group_id'];

        if( !isset( $user_id ) || !get_userdata( $user_id ) )
            return new WP_Error( 'rest_bad_request', "User does not exist!", array( 'status' => 400 ) );

        if( !isset( $access_group_id ) || !Access_Groups::is_active_access_group( $access_group_id ) )
            return new WP_Error( 'rest_bad_request', "Access group does not exist!", array( 'status' => 400 ) );

        if( Access_Groups::check_plan_active( $user_id, $access_group_id ) )
            return new WP_Error( 'rest_bad_request', "The user is already in the access group.", array( 'status' => 400 ) );

        Access::grant( $user_id, $access_group_id );

        return rest_ensure_response( $this->get_user_and_group_details( $user_id ) );

    }

    public function remove_user_to_group( $request ){
        $user_id = $request->get_url_params()['user_id'];
        $access_group_id = $request->get_url_params()['access_group_id'];

        if( !isset( $user_id ) || !get_userdata( $user_id ) )
            return new WP_Error( 'rest_bad_request', "User does not exist!", array( 'status' => 400 ) );

        if( !isset( $access_group_id ) || !Access_Groups::is_active_access_group( $access_group_id ) )
            return new WP_Error( 'rest_bad_request', "Access group does not exist!", array( 'status' => 400 ) );

        if( !Access_Groups::check_plan_active( $user_id, $access_group_id ) )
            return new WP_Error( 'rest_bad_request', "The user is not in the access group.", array( 'status' => 400 ) );

        Access::revoke( $user_id, $access_group_id );

        return rest_ensure_response( $this->get_user_and_group_details( $user_id ) );

    }

    private function get_user_and_group_details( $user_id ){
        $user_details = get_user( $user_id );
        if( $user_details ){
            $user_data = json_decode( json_encode( $user_details->data ), true);
            unset( $user_data['user_pass'] );
            $user_data['access_group_ids'] = get_user_meta( $user_id, 'suremembers_user_access_group', true );
            if( $user_data['access_group_ids'] && is_array( $user_data['access_group_ids'] ) ){
                $utc_tz = new DateTimeZone("UTC");
                foreach( $user_data['access_group_ids'] as $access_group_id ){
                    $access_group_details = get_user_meta( $user_id, "suremembers_user_access_group_$access_group_id", true );
                    $access_group_details['access_group_id'] = $access_group_id;
                    $post_data = get_post( $access_group_id );
    
                    if( $post_data )
                        $access_group_details['access_group_name'] = $post_data->post_title;
    
                    if( !empty( $access_group_details['created'] ) )
                        $access_group_details['created'] = wp_date( 'Y-m-d H:i:s', $access_group_details['created'], $utc_tz );

                    if( !empty( $access_group_details['modified'] ) )
                        $access_group_details['modified'] = wp_date( 'Y-m-d H:i:s', $access_group_details['modified'], $utc_tz );
    
                    $user_data['access_group_details'][] = $access_group_details;
                }
            }
            else{
                $user_data['access_group_ids'] = [];
            }
            return $user_data;
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
        if( ( !isset( $entry->access_group_id ) ) || !Access_Groups::is_active_access_group( $entry->access_group_id ) ){
            return new WP_Error( 'rest_bad_request', "Access group does not exist!", array( 'status' => 400 ) );
        }
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
                'access_group_id' => $entry->access_group_id
            );
            $post_name = "SureMembers ";
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
     * Fires after user is added to access group.
     * 
     * @param int       $user_id          WP User ID of the user.
     * @param arrray    $access_group_ids Array of SureMember's access group IDs.
     */
    public function payload_user_added_to_group( $user_id, $access_group_ids ){
        $user_data = $this->get_user_and_group_details( $user_id );
        foreach( $access_group_ids as $access_group_id ){
            $args = array(
                'event' => 'user_added_to_group',
                'access_group_id' => $access_group_id
            );
            $webhooks = $this->get_webhook_posts( $args );
            if( !empty( $webhooks ) ){
                $event_data = array(
                    'event' => 'user_added_to_group',
                    'data' => $user_data
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
     * Fires after user is removed from access group.
     * 
     * @param int       $user_id          WP User ID of the user.
     * @param arrray    $access_group_ids Array of SureMember's access group IDs.
     */
    public function payload_user_removed_from_group( $user_id, $access_group_ids ){
        $user_data = $this->get_user_and_group_details( $user_id );
        foreach( $access_group_ids as $access_group_id ){
            $args = array(
                'event' => 'user_removed_from_group',
                'access_group_id' => $access_group_id
            );
            $webhooks = $this->get_webhook_posts( $args );
            if( !empty( $webhooks ) ){
                $event_data = array(
                    'event' => 'user_removed_from_group',
                    'data' => $user_data
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/suremembers/suremembers.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['suremembers'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.13.0
 * @since divi          4.27.4
 */
class Zoho_Flow_Divi extends Zoho_Flow_Service{

    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "form_entry_submitted"
    );

    /**
     * List forms
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $limit          Number of results. Default: 200.
     * @type    string  $order_by       List order by the field. Default: post_modified.
     * @type    string  $order          List order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response    WP_REST_Response array with form details
     */
    public function list_forms( $request ){
        $order_by_allowed = array(
            'ID',
            'post_date',
            'post_date_gmt',
            'post_modified',
            'post_modified_gmt'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( isset( $request['order_by'] ) && ( in_array($request['order_by'], $order_by_allowed ) ) ) ? $request['order_by'] : 'post_modified';
        $order = ( isset( $request['order'] ) && ( in_array( $request['order'], $order_allowed ) ) ) ? $request['order'] : 'DESC';
        $limit = isset( $request['limit'] ) ? $request['limit'] : '200';
        $args = array(
            'post_type' => 'any',
            'numberposts' => $limit,
            'orderby'        => $order_by,
            //'s'              => 'et_pb_contact_form',
            'order'          => $order,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_et_dynamic_cached_shortcodes',
                    'value'   => 'et_pb_contact_form',
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => '_et_dynamic_cached_shortcodes',
                    'value'   => 'et_pb_contact_field',
                    'compare' => 'LIKE',
                ),
            ),
        );
        $posts = get_posts( $args );
        $return_array = array();
        foreach( $posts as $post ){
            $forms = $this->extract_et_pb_contact_forms( $post->post_content, $post->ID, $post->post_title );
            if( !empty( $forms ) ){
                $return_array = array_merge( $return_array, $forms );
            }
        }
        return rest_ensure_response( $return_array );
    }

    private function extract_et_pb_contact_forms( $content, $post_id, $post_name ) {
        $forms = [];
        
        // Match all instances of et_pb_contact_form with _unique_id and title (if available)
        preg_match_all('/\[et_pb_contact_form[^]]*_unique_id="([^"]+)"(?:[^]]*title="([^"]+)")?/', $content, $matches, PREG_SET_ORDER);
    
        foreach ($matches as $match) {
            $forms[] = [
                'form_id' => $match[1], 
                'title' => $match[2] ?? 'No title', // Default to empty string if title is not present
                'post_id' => $post_id,
                'post_name' => $post_name
            ];
        }
        
        return $forms;
    }


     /**
     * List form fields
     * 
     * @param WP_REST_Request $request WP_REST_Request object.
     * 
     * Request path param  Mandatory.
     * @type    int     $post_id    ID of the post.
     * @type    int     $form_id    Form block ID.
     * 
     * @return WP_REST_Response    WP_REST_Response array with form field details
     */
    public function list_form_fields( $request ){
        $post_id = $request->get_url_params()['post_id'];
        $form_id = $request->get_url_params()['form_id'];
        $post = get_post( $post_id, 'ARRAY_A' );
        if( $post ){
            $fields = $this->extract_et_pb_contact_fields( $post['post_content'], $form_id );
            if( $fields ){
                return rest_ensure_response( $fields );
            }
        }
        return new WP_Error( 'rest_bad_request', 'Invalid Form', array( 'status' => 400 ) );
    }

    private function extract_et_pb_contact_fields($content, $unique_id) {
        $fields = [];
    
        // Match the specific et_pb_contact_form block with the given unique_id
        $form_pattern = '/\[et_pb_contact_form[^]]*_unique_id="' . preg_quote($unique_id, '/') . '"[^]]*](.*?)\[\/et_pb_contact_form]/s';
        
        if (preg_match($form_pattern, $content, $form_match)) {
            $form_inner_content = $form_match[1];
    
            // Match all et_pb_contact_field blocks inside the found form
            preg_match_all('/\[et_pb_contact_field[^]]*field_id="([^"]+)"[^]]*field_title="([^"]+)"(?:[^]]*field_type="([^"]+)")?/', $form_inner_content, $field_matches, PREG_SET_ORDER);
    
            foreach ($field_matches as $match) {
                $fields[] = [
                    'field_id' => strtolower( $match[1] ),
                    'field_title' => $match[2],
                    'field_type' => $match[3] ?? 'text' // Default type to 'text' if not specified
                ];
            }
        }
    
        return $fields;
    }

    /**
     * Check whether the Form ID is valid or not.
     *
     * @param int $post_id  Post ID.
     * @param int $form_id  Form ID.
     * @return boolean  true if the form exists | false for others.
     */
    private function is_valid_form( $post_id, $form_id ){
        if( isset( $post_id ) && isset( $form_id ) ){
            $post = get_post( $post_id, 'ARRAY_A' );
            if( $post ){
                $fields = $this->extract_et_pb_contact_fields( $post['post_content'], $form_id );
                if( $fields ){
                    return true;
                }
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
        if( ( !isset( $entry->post_id ) && !isset( $entry->form_id ) ) || !$this->is_valid_form( $entry->post_id, $entry->form_id ) ){
            return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 400 ) );
        }
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
                'post_id' => $entry->post_id,
                'form_id' => $entry->form_id
            );
            $post_name = "Divi ";
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
     * @param array   $fields_values    Processed fields values.
     * @param array   $et_contact_error Whether there is an error on the form.
     * @param array   $form_info        Contact form info.
     */
    public function payload_form_entry_submitted( $fields_values, $et_contact_error, $form_info ){
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form_info['contact_form_unique_id'],
            'post_id' => $form_info['post_id']
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $entry_details = array(
                'form_id' => $form_info['contact_form_unique_id'],
                'post_id' => $form_info['post_id']
            );
            if( is_array( $fields_values ) ){
                foreach ( $fields_values as $field_id => $field_details ){
                    $entry_details[$field_id] = $field_details['value'];
                }
            }
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/divi-builder/divi-builder.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['divi'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow          2.10.0
 * @since gravity-forms     2.4.18.10
 */
class Zoho_Flow_Gravity_Forms extends Zoho_Flow_Service{
    
    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "form_entry_submitted",
        "form_entry_updated",
        "form_entry_status_updated"
    );
    
    /**
     * List forms
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return WP_REST_Response|WP_Error    WP_REST_Response array of form details | WP_Error object with error details.
     */
    public function list_forms( $request ){
        $forms = GFFormsModel::get_forms( true, 'date_created', 'DESC');
        return rest_ensure_response( $forms );
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
        
        $form_id = $request->get_url_params()['form_id'];
        
        if( $this->is_valid_form( $form_id ) ){
            $form_fields = GFFormsModel::get_form_meta( $form_id );
            
            return rest_ensure_response( $form_fields );
        }
        return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 404 ) );
    }
    
    public function fetch_entry( $request ){
        
        $form_id = $request->get_url_params()['form_id'];
        $entry_id = $request->get_url_params()['entry_id'];
        
        if( $this->is_valid_form( $form_id ) ){
            
            $entry = $this->get_entry_details( $entry_id );
            
            if( $entry ){
                
                if( $form_id == $entry['form_id'] ){
                    return rest_ensure_response( $entry );
                }
                return new WP_Error( 'rest_bad_request', "Entry does not exist in the Form!", array( 'status' => 404 ) );
            }
            return new WP_Error( 'rest_bad_request', "Entry does not exist!", array( 'status' => 404 ) );
        }
        return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 404 ) );
    }
    
    public function search_entries( $request ){
        
        $form_id = $request->get_url_params()['form_id'];
        
        if( $this->is_valid_form( $form_id ) ){
            
            $search_criteria = isset( $request['search'] ) && is_array( $request['search'] ) ? $request['search'] : array();
            $sorting = isset( $request['sorting'] ) && is_array( $request['sorting'] ) ? $request['sorting'] : array( 'key' => 'date_updated', 'direction' => 'DESC' );
            $paging = isset( $request['paging'] ) && is_array( $request['paging'] ) ? $request['paging'] : array( 'page_size' => 20, 'current_page' => 1 );
            
            $entries = GFFormsModel::search_leads( $form_id, $search_criteria, $sorting, $paging );
            
            $entries_to_return = array();
            foreach ( $entries as $entry ){
                $entry_details = $this->get_entry_details( $entry['id'] );
                if( $entry_details ){
                    $entries_to_return[] = $entry_details;
                }
            }
            return rest_ensure_response( $entries_to_return );
        }
        return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 400 ) );
    }
    
    public function add_form_entry( $request ){
        
        $entry_data = $request->get_params();
        $entry_id = GFAPI::add_entry( $entry_data );
        
        if( is_wp_error( $entry_id ) ){
            $errors = $entry_id->get_error_messages();
            return new WP_Error( 'rest_bad_request', $errors[0], array( 'status' => 400 ) );
        }
        return rest_ensure_response( $this->get_entry_details( $entry_id ) );
    }
    
    public function submit_form_entry( $request ){
        
        $form_id = $request->get_url_params()['form_id'];
        $entry_data = $request->get_params();
        
        $entry = GFAPI::submit_form( $form_id, $entry_data );
        
        if( is_wp_error( $entry ) ){
            $errors = $entry->get_error_messages();
            return new WP_Error( 'rest_bad_request', $errors[0], array( 'status' => 400 ) );
        }
        
        $entry_details = $this->get_entry_details( $entry['entry_id'] );
        if( $entry_details ){
            $entry = array_merge( $entry, $entry_details );
        }
        return rest_ensure_response( $entry );
    }
    
    public function update_form_entry( $request ){
        
        $entry_id = $request->get_url_params()['entry_id'];
        $entry_data = $request->get_params();
        $entry_updated = GFAPI::update_entry( $entry_data, $entry_id );
        
        if( is_wp_error( $entry_updated ) ){
            $errors = $entry_updated->get_error_messages();
            return new WP_Error( 'rest_bad_request', $errors[0], array( 'status' => 400 ) );
        }
        return rest_ensure_response( $this->get_entry_details( $entry_id ) );
    }
    
    private function is_valid_form( $form_id ){
        
        if( isset( $form_id ) && is_numeric( $form_id ) ){
            return GFFormsModel::get_form( $form_id );
        }
        return false;
    }
    
    private function get_entry_details( $entry_id ){
        if( isset( $entry_id ) && is_numeric( $entry_id ) ){
            
            $entry_details = GFFormsModel::get_entry( $entry_id );
            
            if( !is_wp_error( $entry_details ) ){
                foreach ( $entry_details as $key => $value ){ //For Backward compatibility, numeric keys are not removed.
                    if( is_float( $key ) ){
                        $entry_details[$key] = maybe_unserialize( $value, true );
                        $entry_details['input_'.$key] = $entry_details[$key];
                    }
                    elseif( is_numeric( $key ) ){
                        $entry_details[$key] = maybe_unserialize( $value, true );
                        $entry_details['input_'. preg_replace('/\./', '_', $key)] = $entry_details[$key];
                    }
                }
                return $entry_details;
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
            $post_name = "Gravity Forms ";
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
     * @param   array       $entry      Form entry details.
     * @param   array       $form_data  Form details array.
     */
    public function payload_form_entry_submitted( $entry, $form ){
        $form_id = $form['id'];
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $entry_details =  $this->get_entry_details( $entry['id'] );
            if( $entry_details ){
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
    }
    
    /**
     * Fires after entry is updated.
     *
     * @param   array       $entry      Form entry details.
     * @param   array       $form_data  Form details array.
     */
    public function payload_form_entry_updated( $form, $entry_id ){
        $form_id = $form['id'];
        $args = array(
            'event' => 'form_entry_updated',
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $entry_details =  $this->get_entry_details( $entry_id );
            if( $entry_details ){
                $event_data = array(
                    'event' => 'form_entry_updated',
                    'data' => $entry_details
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
     * Fires after entry status is updated.
     *
     * @param   array       $entry      Form entry details.
     * @param   array       $form_data  Form details array.
     */
    public function payload_form_entry_status_updated( $entry_id, $status, $old_status ){
        
        $entry_details =  $this->get_entry_details( $entry_id );
        
        if( $entry_details ){
            $form_id = $entry_details['form_id'];
            $args = array(
                'event' => 'form_entry_status_updated',
                'form_id' => $form_id
            );
            $webhooks = $this->get_webhook_posts( $args );
            if( !empty( $webhooks ) ){
                
                $entry_details['old_status'] = $old_status;
                $event_data = array(
                    'event' => 'form_entry_status_updated',
                    'data' => $entry_details
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/gravityforms/gravityforms.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['gravity_forms'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
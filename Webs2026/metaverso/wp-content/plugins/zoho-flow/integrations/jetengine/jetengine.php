<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Jet_Engine\Modules\Custom_Content_Types\Module;

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.10.0
 * @since jetengine     3.5.8
 */
class Zoho_Flow_JetEngine extends Zoho_Flow_Service{
    
    /**
     *  @var array Webhook events supported.
     */
    public static $supported_events = array(
        "cct_entry_added",
        "cct_entry_updated",
        "cct_entry_added_or_updated",
        "form_entry_submitted",
        "cpt_entry_added_or_updated",
        "cpt_entry_status_changed"
    );
    
    /**
     * List custom content types
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int     $limit         Number of results. Default: 200.
     * @type string  $order_by      List order by the field. Default: id.
     * @type string  $order         List order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response    WP_REST_Response array with CCT details
     */
    public function list_cct( $request ){
        
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
                "SELECT id, slug, status, labels, args FROM {$wpdb->prefix}jet_post_types WHERE status = %s ORDER BY $order_by $order LIMIT %d",
                'content-type',
                $limit
            ), 'ARRAY_A'
                );
        
        foreach ( $results as $index => $field ){
            
            $results[ $index ]['labels'] = maybe_unserialize( $field['labels'], true );
            $results[ $index ]['args'] = maybe_unserialize( $field['args'], true );
            
        }
        
        return rest_ensure_response( $results );
    }
    
    /**
     * Fetch custom content type details
     * 
     * @param WP_REST_Request $request WP_REST_Request object.
     * @return WP_REST_Response    WP_REST_Response CCT details
     */
    public function fetch_cct( $request ){
        
        $cct_slug = $request->get_url_params()['cct_slug'];
        $cct = $this->is_valid_cct( $cct_slug );
        
        if( $cct ){
            return rest_ensure_response( $cct );
        }
        
        return new WP_Error( 'rest_bad_request', "Content type does not exist!", array( 'status' => 404 ) );
    }
    
    /**
     * Add new CCT entry
     * 
     * @param WP_REST_Request               $request    WP_REST_Request object.
     * @return WP_REST_Response|WP_Error    WP_REST_Response Item details.
     */
    public function add_cct_entry( $request ){
        
        $content_type = Module::instance()->manager->get_content_types( $request->get_url_params()['cct_slug'] );
        
        if( $content_type ){
            
            $entry_data = $request->get_params();
            unset( $entry_data['cct_slug'] );
            $handler = $content_type->get_item_handler();
            $item_id = $handler->update_item( $entry_data );
            
            if( $item_id ){
                
                $item_details = $content_type->db->get_item( $item_id );
                $item_details['id'] = $item_details['_ID'];
                
                return rest_ensure_response( $item_details );
            }
            
            return new WP_Error( 'rest_bad_request', "Content type item not added!", array( 'status' => 400 ) );
            
        }
        return new WP_Error( 'rest_bad_request', "Content type does not exist!", array( 'status' => 404 ) );
        
    }
    
    /**
     * Update CCT entry
     *
     * @param WP_REST_Request               $request    WP_REST_Request object.
     * @return WP_REST_Response|WP_Error    WP_REST_Response Item details.
     */
    public function update_cct_entry( $request ){
        
        $content_type = Module::instance()->manager->get_content_types( $request->get_url_params()['cct_slug'] );
        
        if( $content_type ){
            
            $entry_data = $request->get_params();
            unset( $entry_data['cct_slug'] );
            $handler = $content_type->get_item_handler();
            $item_id = $handler->update_item( $entry_data );
            
            if( $item_id ){
                
                $item_details = $content_type->db->get_item( $item_id );
                $item_details['id'] = $item_details['_ID'];
                
                return rest_ensure_response( $item_details );
            }
            
            return new WP_Error( 'rest_bad_request', "Content type item not updated!", array( 'status' => 400 ) );
            
        }
        return new WP_Error( 'rest_bad_request', "Content type does not exist!", array( 'status' => 404 ) );
        
    }
    
    /**
     * Fetch CCT entry
     *
     * @param WP_REST_Request               $request    WP_REST_Request object.
     * @return WP_REST_Response|WP_Error    WP_REST_Response Item details.
     */
    public function get_cct_entry( $request ){
        
        $content_type = Module::instance()->manager->get_content_types( $request->get_url_params()['cct_slug'] );
        
        if( $content_type ){
            
            $item_details = $content_type->db->get_item( $request->get_url_params()['_ID'] );
            
            if( $item_details ){
                
                $item_details['id'] = $item_details['_ID'];
                
                return rest_ensure_response( $item_details );
            }
            
            return new WP_Error( 'rest_bad_request', "Item does not exist!", array( 'status' => 404 ) );
            
        }
        return new WP_Error( 'rest_bad_request', "Content type does not exist!", array( 'status' => 404 ) );
        
    }
    
    private function is_valid_cct( $cct_slug ){
        
        if( isset( $cct_slug ) && is_string( $cct_slug )){
            
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}jet_post_types WHERE `slug` = %s AND `status` = %s",
                    $cct_slug,
                    'content-type'
                ), 'ARRAY_A'
                    );
            
            if( !empty( $result ) ){
                
                $result['labels'] = maybe_unserialize( $result['labels'], true );
                $result['args'] = maybe_unserialize( $result['args'], true );
                $result['meta_fields'] = maybe_unserialize( $result['meta_fields'], true );
                return $result;
                
            }
        }
        
        return false;
    }
    
    /**
     * List custom post type details
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int     $limit         Number of results. Default: 200.
     * @type string  $order_by      List order by the field. Default: id.
     * @type string  $order         List order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response    WP_REST_Response array with CPT details
     */
    public function list_cpt( $request ){
        
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
                "SELECT id, slug, status, labels, args FROM {$wpdb->prefix}jet_post_types WHERE status = %s ORDER BY $order_by $order LIMIT %d",
                'publish',
                $limit
            ), 'ARRAY_A'
                );
        
        foreach ( $results as $index => $field ){
            
            $results[ $index ]['labels'] = maybe_unserialize( $field['labels'], true );
            $results[ $index ]['args'] = maybe_unserialize( $field['args'], true );
            
        }
        
        return rest_ensure_response( $results );
    }
    
    /**
     * Fetch custom post type details
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     * @return WP_REST_Response    WP_REST_Response CPT details
     */
    public function fetch_cpt( $request ){
        
        $cpt_slug = $request->get_url_params()['cpt_slug'];
        $cpt = $this->is_valid_cpt( $cpt_slug );
        
        if( $cpt ){
            
            return rest_ensure_response( $cpt );
            
        }
        
        return new WP_Error( 'rest_bad_request', "Post type does not exist!", array( 'status' => 404 ) );
    }
    
    private function is_valid_cpt( $cpt_slug ){
        
        if( isset( $cpt_slug ) && is_string( $cpt_slug )){
            
            global $wpdb;
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}jet_post_types WHERE `slug` = %s AND `status` = %s",
                    $cpt_slug,
                    'publish'
                        ), 'ARRAY_A'
                            );
            
            if( !empty( $result ) ){
                
                $result['labels'] = maybe_unserialize( $result['labels'], true );
                $result['args'] = maybe_unserialize( $result['args'], true );
                $result['meta_fields'] = maybe_unserialize( $result['meta_fields'], true );
                return $result;
                
            }
        }
        
        return false;
    }
    
    /**
     * List forms
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * request params  Optional. Arguments for querying forms.
     * @type int     limit        Number of forms to query for. Default: 200.
     * @type string  $order_by    Forms list order by the field. Default: post_modified.
     * @type string  $order       Form list order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response|WP_Error    WP_REST_Response array of form details | WP_Error object with error details.
     */
    public function list_forms( $request ){
        
        $args = array(
            "post_type" => "jet-engine-booking",
            "numberposts" => ($request['limit']) ? $request['limit'] : '200',
            "orderby" => ($request['order_by']) ? $request['order_by'] : 'post_modified',
            "order" => ($request['order']) ? $request['order'] : 'DESC',
        );
        
        $forms_list = get_posts( $args );
        $forms_return_list = array();
        
        foreach ( $forms_list as $form ){
            
            $forms_return_list[] = array(
                "ID" => $form->ID,
                "post_title" => $form->post_title,
                "post_status" => $form->post_status,
                "post_author" => $form->post_author,
                "post_date" => $form->post_date,
                "post_modified" => $form->post_modified
            );
        }
        
        return rest_ensure_response( $forms_return_list );
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
        
        if( $this->is_valid_form( $form_id ) ){
            
            $form_fields = get_post_meta( $form_id, '_form_data' );
            $form_data = $form_fields[0];
            $parsed_data = json_decode( wp_unslash( $form_data ), true );
            
            if ( ! $parsed_data ) {
                $parsed_data = json_decode( $form_data, true );
            }
            
            if ( ! $parsed_data ) {
                $parsed_data = array();
            }
            
            foreach ( $parsed_data as $index => $value ) {
                $parsed_data[ $index ]['i'] = '' . $value['i'];
            }
            
            return rest_ensure_response( $parsed_data );
        }
        
        return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 404 ) );
    }
    
    private function is_valid_form( $form_id ){
        
        if( isset( $form_id ) ){
            
            if( "jet-engine-booking" === get_post_type( $form_id ) ){
                return true;
            }
            
            return false;
        }
        else{
            
            return false;
        }
    }
    
    private function get_cpt_entry( $post_id ){
        
        if( isset( $post_id ) && is_numeric( $post_id ) ){
            
            $post = get_post( $post_id, 'ARRAY_A');
            
            if( $post ){
                
                unset( $post['post_password'] );
                $post['meta'] = array();
                $post_meta = get_post_meta($post_id);
                
                foreach ($post_meta as $meta_key => $meta_value ) {
                    if( is_array( $meta_value ) ){
                        if( 1 < sizeof( $meta_value ) ){
                            
                            $post['meta'][$meta_key] = array();
                            
                            foreach ($meta_value as $value) {
                                $post['meta'][$meta_key][] = maybe_unserialize( $value );
                            }
                        }
                        else{
                            $post['meta'][$meta_key] = maybe_unserialize( $meta_value[0] );
                        }
                    }
                    else{
                        $post['meta'][$meta_key] = maybe_unserialize( $meta_value );
                    }
                    
                }
                
                return $post;
            }
        }
        
        return false;
    }
    
    private function upsert_option( $option_name, $option_value ){
        
        if( isset( $option_name ) && isset( $option_value ) && is_string( $option_name ) && is_string( $option_value ) ){
            
            $option = get_option( $option_name );
            
            if( $option ){
                
                if( is_array( $option ) ){
                    
                    if( !in_array( $option_value, $option ) ){
                        $option[] = $option_value;
                    }
                }
                else{
                    
                    $option = array( $option_value );
                }
                
                update_option( $option_name, $option );
            }
            else{
                
                $option = array( $option_value );
                add_option( $option_name, $option );
            }
            
            return get_option( $option_name );
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
                'event' => $entry->event
            );
            
            if( 'cct_entry_added' === $entry->event || 'cct_entry_updated' === $entry->event || 'cct_entry_added_or_updated' === $entry->event ){
                
                if( ( !isset( $entry->cct_slug ) ) || !$this->is_valid_cct( $entry->cct_slug ) ){
                    return new WP_Error( 'rest_bad_request', "CCT does not exist!", array( 'status' => 400 ) );
                }
                
                $options = false;
                
                if( 'cct_entry_added' === $entry->event ){
                    $options = $this->upsert_option( 'ZF_JetEngine_CCT_item_added', 'jet-engine/custom-content-types/created-item/'.$entry->cct_slug );
                }
                elseif( 'cct_entry_updated' === $entry->event ){
                    $options = $this->upsert_option( 'ZF_JetEngine_CCT_item_updated', 'jet-engine/custom-content-types/updated-item/'.$entry->cct_slug );
                }
                elseif( 'cct_entry_added_or_updated' === $entry->event ){
                    $options = $this->upsert_option( 'ZF_JetEngine_CCT_item_added_or_updated', 'jet-engine/custom-content-types/updated-item/'.$entry->cct_slug );
                }
                
                if( !$options ){
                    return new WP_Error( 'rest_bad_request', "Option not added", array( 'status' => 400 ) );
                }
                
                $args['cct_slug'] = $entry->cct_slug;
            }
            
            elseif( 'form_entry_submitted' === $entry->event ){
                
                if( ( !isset( $entry->form_id ) ) || !$this->is_valid_form( $entry->form_id ) ){
                    return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 400 ) );
                }
                
                $args['form_id'] = $entry->form_id;
            }
            
            elseif( 'cpt_entry_added_or_updated' === $entry->event || 'cpt_entry_status_changed' === $entry->event ){
                
                if( ( !isset( $entry->cpt_slug ) ) || !$this->is_valid_cpt( $entry->cpt_slug ) ){
                    return new WP_Error( 'rest_bad_request', "CPT does not exist!", array( 'status' => 400 ) );
                }
                
                $options = false;
                
                if( 'cpt_entry_added_or_updated' === $entry->event ){
                    $options = $this->upsert_option( 'ZF_JetEngine_CPT_item_added_or_updated', 'save_post_'.$entry->cpt_slug );
                    if( !$options ){
                        return new WP_Error( 'rest_bad_request', "Option not added", array( 'status' => 400 ) );
                    }
                }
                
                $args['cpt_slug'] = $entry->cpt_slug;
            }
            $post_name = "JetEngine ";
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
     * Fires after cct entry is added.
     *
     * @param   array   $item       Entry details.
     * @param   int     $item_id    Entry ID.
     * @param   Jet_Engine\Modules\Custom_Content_Types\Item_Handler $item_handler Item_Handler
     */
    public function payload_cct_entry_added( $item, $item_id, $item_handler ){
        $cct_slug = ($item_handler->get_factory())->args['slug'];
        $args = array(
            'event' => 'cct_entry_added',
            'cct_slug' => $cct_slug
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $item['id'] = $item_id;
            $event_data = array(
                'event' => 'cct_entry_added',
                'data' => $item
            );
            foreach( $webhooks as $webhook ){
                $event_data['id'] = $webhook->ID;
                $url = $webhook->url;
                zoho_flow_execute_webhook( $url, $event_data, array() );
            }
        }
    }
    
    /**
     * Fires after cct entry is updated.
     *
     * @param   array   $item       Entry details.
     * @param   array   $prev_item  Entry previous details.
     * @param   Jet_Engine\Modules\Custom_Content_Types\Item_Handler $item_handler Item_Handler
     */
    public function payload_cct_entry_updated( $item, $prev_item, $item_handler ){
        if( is_array( $prev_item ) && !empty( $prev_item ) ){
            $cct_slug = ($item_handler->get_factory())->args['slug'];
            $args = array(
                'event' => 'cct_entry_updated',
                'cct_slug' => $cct_slug
            );
            $webhooks = $this->get_webhook_posts( $args );
            if( !empty( $webhooks ) ){
                $item['id'] = $item['_ID'];
                $event_data = array(
                    'event' => 'cct_entry_updated',
                    'data' => $item
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
     * Fires after cct entry is added or  updated.
     *
     * @param   array   $item       Entry details.
     * @param   array   $prev_item  Entry previous details.
     * @param   Jet_Engine\Modules\Custom_Content_Types\Item_Handler $item_handler Item_Handler
     */
    public function payload_cct_entry_added_or_updated( $item, $prev_item, $item_handler ){
        $cct_slug = ($item_handler->get_factory())->args['slug'];
        $args = array(
            'event' => 'cct_entry_added_or_updated',
            'cct_slug' => $cct_slug
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $item['id'] = $item['_ID'];
            $event_data = array(
                'event' => 'cct_entry_added_or_updated',
                'data' => $item
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
     * @param   array   $handler  Form handler.
     */
    public function payload_form_entry_submitted( $handler ){
        $form_id = $handler->form;
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $entry_details = $handler->notifcations->data;
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
     * Fires after cpt entry is added or  updated.
     *
     * @param   int         $post_id    CPT post ID.
     * @param   WP_Post     $post       CPT post object.
     * @param   boolean     $update     Flag for update.
     */
    public function payload_cpt_entry_added_or_updated( $post_id, $post, $update ){
        $args = array(
            'event' => 'cpt_entry_added_or_updated',
            'cpt_slug' => get_post_type( $post )
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $entry_details = $this->get_cpt_entry( $post_id );
            $event_data = array(
                'event' => 'cpt_entry_added_or_updated',
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
     * Fires after cpt entry status is updated.
     *
     * @param   string      $new_status     New status of the post.
     * @param   string      $old_status     Old status of the post.
     * @param   WP_Post     $post           CPT post object.
     */
    public function payload_cpt_entry_status_changed( $new_status, $old_status, $post ){
        if( $new_status !== $old_status ){
            $args = array(
                'event' => 'cpt_entry_status_changed',
                'cpt_slug' => get_post_type( $post )
            );
            $webhooks = $this->get_webhook_posts( $args );
            if( !empty( $webhooks ) ){
                $entry_details = $this->get_cpt_entry( $post->ID );
                $entry_details['old_status'] = $old_status;
                $event_data = array(
                    'event' => 'cpt_entry_status_changed',
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/jet-engine/jet-engine.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['jetengine'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
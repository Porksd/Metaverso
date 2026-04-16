<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.11.0
 * @since spectra       2.16.3
 */
class Zoho_Flow_Spectra extends Zoho_Flow_Service{
    
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
     * @type    string  $order_by       List order by the field. Default: id.
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
            "post_type" => "page",
            "numberposts" => $limit,
            's' => 'wp:uagb/forms',
            'orderby'        => $order_by,
            'order'          => $order,
        );
        $posts = get_posts( $args );
        $return_array = array();
        foreach( $posts as $post ){
            if( has_block( 'uagb/forms', $post->ID) ){
                $blocks = parse_blocks( $post->post_content );
                $return_array = array_merge( $return_array, $this->block_processor( $blocks, $post->ID, $post->post_title ) );
            }
        }
        return rest_ensure_response( $return_array );
    }
    
    private function block_processor( $blocks, $post_id, $post_title ){
        $forms = array();
        if( is_array( $blocks ) ){
            foreach ( $blocks as $block ){
                if( 'uagb/forms' === $block[ 'blockName' ] ){
                    $forms[] = array(
                        'id' => $block[ 'attrs' ][ 'block_id' ],
                        'name' => $block[ 'attrs' ][ 'formLabel' ],
                        'post_id' => $post_id,
                        'post_title' => $post_title
                    );
                }
                elseif ( is_array( $block[ 'innerBlocks' ] ) && !empty(  $block[ 'innerBlocks' ] ) ){
                    $inner_forms = $this->block_processor( $block[ 'innerBlocks' ], $post_id, $post_title );
                    if( is_array( $inner_forms ) && !empty( $inner_forms ) ){
                        $forms = array_merge($forms, $inner_forms);
                    }
                }
            }
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
     * @type    int     $block_id   Form block ID.
     * 
     * @return WP_REST_Response    WP_REST_Response array with form field details
     */
    public function list_form_fields( $request ){
        $post_id = $request->get_url_params()['post_id'];
        $block_id = $request->get_url_params()['form_id'];
        $post = get_post( $post_id, 'ARRAY_A' );
        if( $post && has_block( 'uagb/forms', $post['ID'] ) ){
            $blocks = parse_blocks( $post['post_content'] );
            return rest_ensure_response( $this->block_fiels_processor( $blocks, $block_id, array() ) );
        }
        return new WP_Error( 'rest_bad_request', 'Invalid post ID', array( 'status' => 400 ) );
    }
    
    private function block_fiels_processor( $blocks, $block_id, $fields ){
        if( is_array( $blocks ) ){
            foreach ( $blocks as $block ){
                if( 'uagb/forms' === $block[ 'blockName' ] && $block[ 'attrs' ][ 'block_id' ] === $block_id ){
                    $fields = $this->field_constructor( $block[ 'innerBlocks' ] );
                }
                elseif ( is_array( $block[ 'innerBlocks' ] ) && !empty(  $block[ 'innerBlocks' ] ) ){
                    $return_fields = $this->block_fiels_processor( $block[ 'innerBlocks' ], $block_id, $fields );
                    if( !empty( $return_fields ) ){
                        $fields = $return_fields;
                    }
                }
            }
        }
        return $fields;
    }
    
    private function field_constructor( $blocks ){
        $field_array = array();
        if( is_array( $blocks ) ){
            foreach ( $blocks as $field_block ){
                $field = $field_block[ 'attrs' ];
                $field['block_name'] = $field_block[ 'blockName' ];
                $field['inner_blocks'] = $field_block[ 'innerBlocks' ];
                $field_array[] = $field;
            }
        }
        return $field_array;
    }
    
    private function fields_for_payload_handling( $post_id, $form_id ){
        $post = get_post( $post_id, 'ARRAY_A' );
        if( $post && has_block( 'uagb/forms', $post['ID'] ) ){
            $blocks = parse_blocks( $post['post_content'] );
            $fields = $this->block_fiels_processor_for_payload_handling( $blocks, $form_id, array() );
            if( !empty( $fields ) ){
                return $fields;
            }
        }
        return false;
    }
    
    private function block_fiels_processor_for_payload_handling( $blocks, $block_id, $fields ){
        if( is_array( $blocks ) ){
            foreach ( $blocks as $block ){
                if( 'uagb/forms' === $block[ 'blockName' ] && $block[ 'attrs' ][ 'block_id' ] === $block_id ){
                    $fields = $this->field_constructor_for_payload_handling( $block[ 'innerBlocks' ] );
                    return $fields;
                }
                elseif ( is_array( $block[ 'innerBlocks' ] ) && !empty(  $block[ 'innerBlocks' ] ) ){
                    $return_fields = $this->block_fiels_processor_for_payload_handling( $block[ 'innerBlocks' ], $block_id, $fields );
                    if( !empty( $return_fields ) ){
                        $fields = $return_fields;
                    }
                }
            }
        }
        return $fields;
    }
    
    private function field_constructor_for_payload_handling( $blocks ){
        $field_array = array();
        if( is_array( $blocks ) ){
            foreach ( $blocks as $field_block ){
                if( $field_block[ 'attrs' ][ 'name' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'name' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                if( $field_block[ 'attrs' ][ 'textareaName' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'textareaName' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                elseif( $field_block[ 'attrs' ][ 'selectName' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'selectName' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                elseif( $field_block[ 'attrs' ][ 'checkboxName' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'checkboxName' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                elseif( $field_block[ 'attrs' ][ 'radioName' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'radioName' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                elseif( $field_block[ 'attrs' ][ 'phoneName' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'phoneName' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                elseif( $field_block[ 'attrs' ][ 'acceptText' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'acceptText' ]] = $field_block[ 'attrs' ]['block_id'];
                }
                elseif( $field_block[ 'attrs' ][ 'hidden_field_name' ] ){
                    $field_array[$field_block[ 'attrs' ][ 'hidden_field_name' ]] = $field_block[ 'attrs' ]['block_id'];
                }
            }
        }
        return $field_array;
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
        if( ( !isset( $entry->post_id ) && !isset( $entry->form_id ) ) || !$this->fields_for_payload_handling( $entry->post_id, $entry->form_id ) ){
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
            $post_name = "Spectra ";
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
     * @param   array       $form_data         Form entry details.
     */
    public function payload_form_entry_submitted( $form_data ){
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form_data['id']
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $post_id = get_post_meta( $webhooks[0]->ID, 'post_id', true );
            $form_fields = $this->fields_for_payload_handling( $post_id, $form_data['id'] );
            $payload = array( );
            if($form_fields ){
                foreach ( $form_data as $field_name => $field_value ){
                    $field_id = $form_fields[ $field_name ];
                    if( $field_id ){
                        $payload[$field_id] = $field_value;
                    }
                    else{
                        $payload[$field_name] = $field_value;
                    }
                    
                }
            }
            else{
                $payload = $form_data;
            }
            $event_data = array(
                'event' => 'form_entry_submitted',
                'data' => $payload
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['spectra'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
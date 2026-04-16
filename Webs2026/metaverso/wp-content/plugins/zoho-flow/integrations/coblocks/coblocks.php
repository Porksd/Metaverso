<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.11.0
 * @since coblocks      3.1.13
 */
class Zoho_Flow_CoBlocks extends Zoho_Flow_Service{

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
            "post_type" => "page",
            "numberposts" => $limit,
            's' => 'coblocks/form',
            'orderby'        => $order_by,
            'order'          => $order,
        );
        $posts = get_posts( $args );
        $return_array = array();
        foreach( $posts as $post ){
            if( has_block( 'coblocks/form', $post->ID) ){
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
                if( 'coblocks/form' === $block[ 'blockName' ] ){
                    $fields = $this->parse_form_fields( $block['innerBlocks'] );
                    $forms[] = array(
                        'id' => $this->form_id_generator( $fields ),
                        'name' => isset( $block[ 'attrs' ][ 'metadata' ][ 'name' ] ) ? $block[ 'attrs' ][ 'metadata' ][ 'name' ] : 'Form',
                        'post_id' => $post_id,
                        'post_title' => $post_title,
                        //'fields' => $fields
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

    private function parse_form_fields( $blocks ){
        $return_array = array();
        if( is_array( $blocks ) ){
            $name_count = 1;
            $textarea_count = 1;
            $text_count = 1;
            $date_count = 1;
            $phone_count = 1;
            $radio_count = 1;
            $select_count = 1;
            $checkbox_count = 1;
            $website_count = 1;
            $hidden_count = 1;
            foreach( $blocks as $block ){
                if('coblocks/field-name' === $block['blockName'] ){
                    //static $name_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Name', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'name',
                        'label_slug' => $name_count > 1 ? sanitize_title( $label . '-' . $name_count ) : sanitize_title( $label ),
                        'has_last_name' => ( isset( $atts['hasLastName'] ) && $atts['hasLastName'] ),
                        'label_first_name' => isset( $atts['labelFirstName'] ) ? $atts['labelFirstName'] : __( 'First', 'coblocks' ),
                        'label_last_name' => isset( $atts['labelLastName'] ) ? $atts['labelLastName'] : __( 'Last', 'coblocks' )
                    );
                    $name_count++;
                }
                elseif('coblocks/field-email' === $block['blockName'] ){
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Email', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'email',
                        'label_slug' => sanitize_title( $label )
                    );
                }
                elseif('coblocks/field-textarea' === $block['blockName'] ){
                    //static $textarea_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Message', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'textarea',
                        'label_slug' => $textarea_count > 1 ? sanitize_title( $label . '-' . $textarea_count ) : sanitize_title( $label )
                    );
                    $textarea_count++;
                }
                elseif('coblocks/field-text' === $block['blockName'] ){
                    //static $text_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Text', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'text',
                        'label_slug' => $text_count > 1 ? sanitize_title( $label . '-' . $text_count ) : sanitize_title( $label )
                    );
                    $text_count++;
                }
                elseif('coblocks/field-date' === $block['blockName'] ){
                    //static $date_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Date', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'date',
                        'label_slug' => $date_count > 1 ? sanitize_title( $label . '-' . $date_count ) : sanitize_title( $label )
                    );
                    $date_count++;
                }
                elseif('coblocks/field-phone' === $block['blockName'] ){
                    //static $phone_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Phone', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'phone',
                        'label_slug' => $phone_count > 1 ? sanitize_title( $label . '-' . $phone_count ) : sanitize_title( $label )
                    );
                    $phone_count++;
                }
                elseif('coblocks/field-radio' === $block['blockName'] ){
                    //static $radio_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Choose one', 'coblocks' );
                    $label_desc    = sanitize_title( $label ) !== 'choose-one' ? sanitize_title( $label ) : 'choose-one';
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'radio',
                        'label_slug' => $radio_count > 1 ? sanitize_title( $label_desc . '-' . $radio_count ) : sanitize_title( $label_desc )
                    );
                    $radio_count++;
                }
                elseif('coblocks/field-select' === $block['blockName'] ){
                    //static $select_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Select', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'select',
                        'label_slug' => $select_count > 1 ? sanitize_title( $label . '-' . $select_count ) : sanitize_title( $label )
                    );
                    $select_count++;
                }
                elseif('coblocks/field-checkbox' === $block['blockName'] ){
                    //static $checkbox_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Checkbox', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'checkbox',
                        'label_slug' => $checkbox_count > 1 ? sanitize_title( $label . '-' . $checkbox_count ) : sanitize_title( $label )
                    );
                    $checkbox_count++;
                }
                elseif('coblocks/field-website' === $block['blockName'] ){
                    //static $website_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Website', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'website',
                        'label_slug' => $website_count > 1 ? sanitize_title( $label . '-' . $website_count ) : sanitize_title( $label )
                    );
                    $website_count++;
                }
                elseif('coblocks/field-hidden' === $block['blockName'] ){
                    //static $hidden_count = 1;
                    $atts = $block['attrs'];
                    $label = isset( $atts['label'] ) ? $atts['label'] : __( 'Hidden', 'coblocks' );
                    $return_array[] = array(
                        'label' => $label,
                        'type' => 'hidden',
                        'label_slug' => $hidden_count > 1 ? sanitize_title( $label . '-' . $hidden_count ) : sanitize_title( $label )
                    );
                    $hidden_count++;
                }
            }
        }
        return $return_array;
    }

    private function form_id_generator( $fields ){
        $slug_array = array();
        if( is_array( $fields ) ){
            foreach( $fields as $field ){
                $slug_array[] = 'field-'.$field['label_slug'];
            }
        }
        return sha1( implode( ' ', $slug_array ) );
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
        $form_id = $request->get_url_params()['form_id'];
        $post = get_post( $post_id, 'ARRAY_A' );
        if( $post && has_block( 'coblocks/form', $post['ID'] ) ){
            $blocks = parse_blocks( $post['post_content'] );
            $fields = $this->block_field_processor( $blocks, $form_id );
            if( $fields ){
                return rest_ensure_response( $fields );
            }
            return new WP_Error( 'rest_bad_request', 'Invalid Form ID', array( 'status' => 400 ) );
        }
        return new WP_Error( 'rest_bad_request', 'Invalid Post ID', array( 'status' => 400 ) );
    }

    private function block_field_processor( $blocks, $form_id ){
        if( is_array( $blocks ) ){
            foreach ( $blocks as $block ){
                if( 'coblocks/form' === $block[ 'blockName' ] ){
                    $fields = $this->parse_form_fields( $block['innerBlocks'] );
                    if( $form_id === $this->form_id_generator( $fields ) ){
                        return $fields;
                    }
                }
                if ( is_array( $block[ 'innerBlocks' ] ) && !empty(  $block[ 'innerBlocks' ] ) ){
                    $return_fields = $this->block_field_processor( $block[ 'innerBlocks' ], $form_id );
                    if( !empty( $return_fields ) ){
                        return $return_fields;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check whether the Form ID is valid or not.
     *
     * @param int $form_id  Form ID.
     * @return boolean  true if the form exists | false for others.
     */
    private function is_valid_form( $post_id, $form_id ){
        if( isset( $post_id ) && isset( $form_id ) ){
            $post = get_post( $post_id, 'ARRAY_A' );
            if( $post && has_block( 'coblocks/form', $post['ID'] ) ){
                $blocks = parse_blocks( $post['post_content'] );
                $fields = $this->block_field_processor( $blocks, $form_id );
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
            $post_name = "CoBlocks ";
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

    private function payload_form_id_generator( $fields ){
        $slug_array = array();
        if( is_array( $fields ) ){
            foreach( $fields as $key => $value ){
                $slug_array[] = $key;
            }
        }
        return sha1( implode( ' ', $slug_array ) );
    }

    /**
     * Fires after entry is processed.
     *
     * @param array   $form_data    User submitted form data.
     * @param array   $atts         Form block attributes.
     * @param boolean $email        True when email sends, else false.
     */
    public function payload_form_entry_submitted( $form_data, $atts, $email ){
        $form_id = $this->payload_form_id_generator( $form_data );
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $form_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            if( is_array( $form_data ) ){
                foreach ( $form_data as $field_name => $field_details ){
                    $form_data[$field_name] = $field_details['value'];
                }
            }
            $event_data = array(
                'event' => 'form_entry_submitted',
                'data' => $form_data
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/coblocks/class-coblocks.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['coblocks'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
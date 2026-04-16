<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow              2.11.0
 * @since snow-monkey-forms     9.1.0
 */
class Zoho_Flow_Snow_Monkey_Forms extends Zoho_Flow_Service{
    
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
     * request params  Optional. Arguments for querying forms.
     * @type int     limit        Number of forms to query for. Default: 200.
     * @type string  $order_by    Forms list order by the field. Default: post_modified.
     * @type string  $order       Form list order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response|WP_Error    WP_REST_Response array of form details | WP_Error object with error details.
     */
    public function list_forms( $request ){
        $args = array(
            "post_type" => "snow-monkey-forms",
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
            $post = get_post( $form_id, 'ARRAY_A' );
            $blocks = parse_blocks( $post['post_content'] );
            foreach( $blocks as $block ){
                if( 'snow-monkey-forms/form--input' === $block['blockName'] ){
                    return rest_ensure_response( $this->parse_form_block( $block['innerBlocks'] ) );
                }
            }
        }
        return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 404 ) );
    }
    
    private function parse_form_block( $blocks ){
        $field_array = array();
        if( is_array( $blocks ) ){
            foreach ( $blocks as $block ){
                if( 1 == str_starts_with( $block['blockName'], 'snow-monkey-forms/control' ) && !empty( $block['attrs'] ) ){
                    $attrs = $block['attrs'];
                    $attrs['type'] = $block['blockName'];
                    $field_array[] = $attrs;
                }
                if( is_array( $block['innerBlocks'] ) && !empty( $block['innerBlocks'] ) ){
                    $inner_field_array = $this->parse_form_block( $block['innerBlocks'] );
                    if( !empty( $inner_field_array ) ){
                        $field_array = array_merge( $field_array, $inner_field_array );
                    }
                }
            }
        }
        return $field_array;
    }

    /**
     * Check whether the Form ID is valid or not.
     *
     * @param int $form_id  Form ID.
     * @return boolean  true if the form exists | false for others.
     */
    private function is_valid_form( $form_id ){
        if( isset( $form_id ) ){
            if( "snow-monkey-forms" === get_post_type( $form_id ) ){
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
        if( ( !isset( $entry->post_id ) && !isset( $entry->form_id ) ) || !$this->is_valid_form( $entry->form_id ) ){
            return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 400 ) );
        }
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
                'form_id' => $entry->form_id
            );
            $post_name = "Snow Monkey Forms ";
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
     * @param   bool                                            $is_sended      Form entry details.
     * @param   Snow_Monkey\Plugin\Forms\App\Model\Responser    $responser      Respone object
     * @param   Snow_Monkey\Plugin\Forms\App\Model\Setting      $setting        Setting object
     * @param   Snow_Monkey\Plugin\Forms\App\Model\MailParser   $mail_parser    Mail parser object
     */
    public function payload_form_entry_submitted( $is_sended, $responser, $setting, $mail_parser ){
        $args = array(
            'event' => 'form_entry_submitted',
            'form_id' => $setting->get('form_id')
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $event_data = array(
                'event' => 'form_entry_submitted',
                'data' => $responser->get_all()
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/snow-monkey-forms/snow-monkey-forms.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['snow_monkey_forms'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
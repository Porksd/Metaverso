<?php

use ElementorPro\Modules\Forms\Submissions\Database\Repositories\Form_Snapshot_Repository;
class Zoho_Flow_Elementor extends Zoho_Flow_Service
{
    /**
     * @deprecated 2.10.1
     */
    public function get_forms( $request ) {

        $forms = array();
        $formsnaps = Form_Snapshot_Repository::instance()->all();

        foreach ($formsnaps as $form){
            $data = array();
            $schema = $this->get_form_schema();
            if ( isset( $schema['properties']['id'] ) ) {
                $data['id'] = $form->id;
            }
            if ( isset( $schema['properties']['title'] ) ) {
                $data['title'] = $form->name;
            }
            if ( isset( $schema['properties']['post_id'] ) ) {
                $data['post id'] = $form->post_id;
            }
            array_push($forms, $data);
        }

        return rest_ensure_response( $forms );
    }

    /**
     * @deprecated 2.10.1
     */
    public function get_form_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'form',
            'type'                 => 'form',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'ID of the Elementor Form.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'title' => array(
                    'description'  => esc_html__( 'The title of the Elementor Form.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit'),
                ),
                'post_id' => array(
                    'description'  => esc_html__( 'The Post Id of the Elementor Form.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                ),
            ),
        );

        return $schema;
    }

    /**
     * @deprecated 2.10.1
     */
    public function get_fields( $request ) {
        $id = $request['form_id'];
        $ids = $this->splitPostAndFormId($id);
        $formsnaps = Form_Snapshot_Repository::instance()->find($ids[0], $ids[1], true);

        if(empty($formsnaps)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $form_fields = $formsnaps->fields;

        if ( empty( $form_fields) ) {
            return rest_ensure_response( $form_fields );
        }
        $fields = array();
        foreach( $form_fields as $field ){
            $data = array(
                'id'=> $field['id'],
                'type'=> $field['type'],
                'label' => $field['label'],
            );
            array_push($fields, $data);
        }
        return rest_ensure_response( $fields );
    }

    /**
     * @deprecated 2.10.1
     */
    public function get_webhooks($request){
        $id = $request['form_id'];
        $ids = $this->splitPostAndFormId($id);
        $formsnaps = Form_Snapshot_Repository::instance()->find($ids[0], $ids[1], true);

        if(empty($formsnaps)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $args = array(
            'form_id' => $id
        );
        $webhooks = $this->get_webhook_posts($args);

        if ( empty( $webhooks ) ) {
            return rest_ensure_response( $webhooks );
        }

        $data = array();

        foreach ( $webhooks as $webhook ) {
            $webhook = array(
                'plugin_service' => $this->get_service_name(),
                'id' => $webhook->ID,
                'form_id' => $id,
                'url' => $webhook->url
            );
            array_push($data, $webhook);
        }

        return rest_ensure_response( $data );
    }

    /**
     * @deprecated 2.10.1
     */
    public function create_webhook_old( $request ) {
        $id = $request['form_id'];
        $url = esc_url_raw($request['url']);
        $ids = $this->splitPostAndFormId($id);
        $formsnaps = Form_Snapshot_Repository::instance()->find($ids[0], $ids[1], true);

        if(empty($formsnaps)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $form_title = $formsnaps->name;

        $post_id = $this->create_webhook_post($form_title, array(
            'form_id' => $id,
            'url' => $url
        ));

        return rest_ensure_response( array(
            'plugin_service' => $this->get_service_name(),
            'id' => $post_id,
            'form_id' => $id,
            'url' => $url
        ) );
    }

    /**
     * @deprecated 2.10.1
     */
    public function delete_webhook_old( $request ) {
        $id = $request['form_id'];
        $ids = $this->splitPostAndFormId($id);
        $formsnaps = Form_Snapshot_Repository::instance()->find($ids[0], $ids[1], true);

        if(empty($formsnaps)){
            return new WP_Error( 'rest_not_found', esc_html__( 'The form is not found.', 'zoho-flow' ), array( 'status' => 404 ) );
        }

        $webhook_id = $request['webhook_id'];
        $result = $this->delete_webhook_post($webhook_id);
        if(is_wp_error($result)){
            return $result;
        }
        return rest_ensure_response(array(
            'plugin_service' => $this->get_service_name(),
            'id' => $result->ID
        ));
        return rest_ensure_response($result);
    }

    /**
     * @deprecated 2.10.1
     */
    public function get_form_webhook_schema() {
        $schema = array(
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'webhook',
            'type'                 => 'webhook',
            'properties'           => array(
                'id' => array(
                    'description'  => esc_html__( 'Unique id of the webhook.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'form_id' => array(
                    'description'  => esc_html__( 'Unique id of the form.', 'zoho-flow' ),
                    'type'         => 'integer',
                    'context'      => array( 'view', 'edit'),
                    'readonly'     => true,
                ),
                'url' => array(
                    'description'  => esc_html__( 'The webhook URL.', 'zoho-flow' ),
                    'type'         => 'string',
                    'context'      => array( 'view', 'edit')
                ),
            ),
        );

        return $schema;
    }

    /**
     * @deprecated 2.10.1
     */
    public function process_form_submission($record, $handler)
    {
        $form_details = $handler->get_current_form();
        $formid = $form_details['id'];
        $postid = $form_details['settings']['form_post_id'];
        $formpost_id = implode('_', array($postid, $formid));

        $args = array(
            'form_id' => $formpost_id
        );
        $webhooks = $this->get_webhook_posts($args);
        if( !empty( $webhooks ) ){
          $raw_fields = $record->get( 'fields' );
          $data = array();
          foreach ( $raw_fields as $id => $field ) {
              $type = $field['type'];
              $fieldId = $field['id'];
              if( $type === 'step'){
                  continue;
              }
              $data[$fieldId] = $field['value'];
          }
          $files = array();
      	foreach ( $webhooks as $webhook ) {
      		$url = $webhook->url;
      		zoho_flow_execute_webhook($url, $data, $files);
      	}
        }

    }

    /**
     * @deprecated 2.10.1
     */
    private function splitPostAndFormId($id){
        return explode('_', $id);
    }
    
    /**
     * @since 2.10.1
     * @var array Webhook events supported.
     */
    public static $supported_events = array(
        "submission_added",
    );
    
    /**
     * List forms
     *
     * @since 2.10.1
     * 
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return WP_REST_Response|WP_Error    WP_REST_Response array of form details | WP_Error object with error details.
     */
    public function list_forms( $request ){
        $forms = array();
        $formsnaps = Form_Snapshot_Repository::instance()->all();
        
        foreach ($formsnaps as $form){
            $forms[] = array(
                'id' => $form->id,
                'title' => $form->name,
                'post_id' => $form->post_id,
            );
        }
        return rest_ensure_response( $forms );
    }
    
    /**
     * List form fields
     *
     * @since 2.10.1
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
        $post_id = $request->get_url_params()['post_id'];
        
        $formsnap = Form_Snapshot_Repository::instance()->find( $post_id, $form_id );
        
        if(empty($formsnap)){
            return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 404 ) );
        }
        
        $form_fields = $formsnap->fields;
        return rest_ensure_response( $form_fields );
    }
    
    /**
     * Check whether the Form ID is valid or not.
     *
     * @since 2.10.1
     *  
     * @param int $form_id  Form ID.
     * @return boolean  true if the form exists | false for others.
     */
    private function is_valid_form( $form_id, $post_id ){
        if( isset( $form_id ) && isset( $post_id ) && is_numeric( $post_id ) ){
            $formsnap = Form_Snapshot_Repository::instance()->find( $post_id, $form_id );
            
            if( !empty($formsnap ) ){
                return true;
            }
            return false;
        }
        else{
            return false;
        }
    }
    
    /**
     * Creates a webhook entry
     * The events available in $supported_events array only accepted
     *
     * @since 2.10.1
     * 
     * @param WP_REST_Request   $request  WP_REST_Request object.
     * @return WP_REST_Response|WP_Error    WP_REST_Response array with Webhook ID | WP_Error object with error details.
     */
    public function create_webhook( $request ){
        $entry = json_decode( $request->get_body() );
        if( !isset( $entry->form_id ) || !isset( $entry->post_id ) || !$this->is_valid_form( $entry->form_id, $entry->post_id ) ){
            return new WP_Error( 'rest_bad_request', "Form does not exist!", array( 'status' => 400 ) );
        }
        if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
            $args = array(
                'name' => $entry->name,
                'url' => $entry->url,
                'event' => $entry->event,
                'form_id' => $entry->form_id,
                'post_id' => $entry->post_id
            );
            $post_name = "Elementor Pro ";
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
     * @since 2.10.1
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
     * Fires after new entry is added.
     * 
     * @since 2.10.1
     *
     * @param   ElementorPro\Modules\Forms\Classes\Form_Record  $record     Form record details.
     * @param   ElementorPro\Modules\Forms\Classes\Ajax_Handler $handler    Form details.
     */
    public function payload_submission_added($record, $handler){
        $form_details = $handler->get_current_form();
        $form_id = $form_details['id'];
        $post_id = $form_details['settings']['form_post_id'];
        $args = array(
            'event' => 'submission_added',
            'form_id' => $form_id,
            'post_id' => $post_id
        );
        $webhooks = $this->get_webhook_posts( $args );
        if( !empty( $webhooks ) ){
            $form_post = $record->get( 'form_settings' );
            $form_data = array(
                'form_name' => $form_post['form_name'],
                'form_id' => $form_post['form_id'],
                'form_post_id' => $form_post['form_post_id'],
                'edit_post_id' => $form_post['edit_post_id']
            );
            $event_data = array(
                'event' => 'submission_added',
                'data' => array(
                    'submission_data' => $record->get( 'fields' ),
                    'meta_data' => $record->get( 'meta' ),
                    'form_data' => $form_data,
                    'files' => $record->get( 'files' )
                )
            );
            foreach( $webhooks as $webhook ){
                $url = $webhook->url;
                zoho_flow_execute_webhook( $url, $event_data, array() );
            }
        }
    }
    
    /**
     * default API
     * Get user and system info.
     *
     * @since 2.10.1
     * 
     * @return array|WP_Error System and logged in user details | WP_Error object with error details.
     */
    public function get_system_info(){
        $system_info = parent::get_system_info();
        if( ! function_exists( 'get_plugin_data' ) ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_dir = ABSPATH . 'wp-content/plugins/elementor-pro/elementor-pro.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['elementor_pro'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}

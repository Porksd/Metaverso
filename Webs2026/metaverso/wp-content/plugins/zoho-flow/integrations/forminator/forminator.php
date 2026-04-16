<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
  * class used for APIs and Webhooks
  *
  * @since zohoflow      1.5.0
  * @since forminator    1.24.6
  */
class Zoho_Flow_Forminator extends Zoho_Flow_Service
{
	/**
    * webhook events supported
    */
  public static $supported_events = array("form_entry_added","poll_added","quiz_added");

	/**
   * list forms
   *
   * @param WP_REST_Request $request WP_REST_Request onject.
   *
   * request params  Optional. Arguments for querying forms.
   * @type array  	$form_ids 	array of form Ids to list details. Default all.
   * @type int     	page        Index of the page. Default 1.
	 * @type int     	per_page    Number of results per request. Default 10.
	 * @type string   status      Status of the form. Possible values: draft, publish, any. Default all.
   *
   * @return array Array of forms.
   */
  public function get_forms( $request ){
    $forms_list = Forminator_API::get_forms(
			isset($request['form_ids']) ? $request['form_ids'] : null,
			isset($request['page']) ? $request['page'] : '',
			isset($request['per_page']) ? $request['per_page'] : 10,
			isset($request['status']) ? $request['status'] : ''
		);
		$forms_array = array();
		foreach ($forms_list as $form_model) {
			$form_array = array(
				'id' => $form_model->id,
				'name' => $form_model->name,
				'status' => $form_model->status
			);
			array_push( $forms_array, $form_array );
		}
    return rest_ensure_response($forms_array);
  }

	/**
    * list form fields
    *
    * @param WP_REST_Request $request WP_REST_Request onject.
    *
    * request path param  Mandatory.
    * @type int  form_id   Form ID to retrive the fields for.
    *
    * @return array|WP_Error array of field groups and fields | WP_Error object with error details.
    */
  public function get_form_fields( $request ){
		if( isset( $request['form_id'] ) ){
			$forms_field_list = Forminator_API::get_form_wrappers( $request['form_id'] );
	    if( is_wp_error( $forms_field_list ) ){
	      return new WP_Error( 'rest_bad_request', $forms_field_list->get_error_messages()[0], array( 'status' => 404 ) );
	    }
	    return rest_ensure_response( $forms_field_list );
		}
    else{
      return new WP_Error( 'rest_bad_request', 'Form does not exist!', array( 'status' => 404 ) );
    }
  }

	/**
   * list polls
   *
   * @param WP_REST_Request $request WP_REST_Request onject.
   *
   * request params  Optional. Arguments for querying polls.
   * @type array  	$poll_ids 	array of poll Ids to list details. Default all.
   * @type int     	page        Index of the page. Default 1.
	 * @type int     	per_page    Number of results per request. Default 10.
	 * @type string   status      Status of the poll. Possible values: draft, publish, any. Default all.
   *
   * @return array Array of polls.
   */
  public function get_polls( $request ){
    $polls_list = Forminator_API::get_polls(
			isset($request['poll_ids']) ? $request['poll_ids'] : null,
			isset($request['page']) ? $request['page'] : '',
			isset($request['per_page']) ? $request['per_page'] : 10,
			isset($request['status']) ? $request['status'] : ''
		);
		$polls_array = array();
		foreach ( $polls_list as $poll_model ) {
			$poll_array = array(
				'id' => $poll_model->id,
				'name' => $poll_model->name,
				'status' => $poll_model->status
			);
			array_push( $polls_array, $poll_array );
		}
    return rest_ensure_response( $polls_array );
  }

	/**
   * list quizzes
   *
   * @param WP_REST_Request $request WP_REST_Request onject.
   *
   * request params  Optional. Arguments for querying quizzes.
   * @type array  	$quiz_ids 	array of quiz Ids to list details. Default all.
   * @type int     	page        Index of the page. Default 1.
	 * @type int     	per_page    Number of results per request. Default 10.
	 * @type string   status      Status of the quizzes. Possible values: draft, publish, any. Default all.
   *
   * @return array Array of quizzes.
   */
  public function get_quizzes( $request ){
    $quiz_list = Forminator_API::get_quizzes(
			isset($request['quiz_ids']) ? $request['quiz_ids'] : null,
			isset($request['page']) ? $request['page'] : '',
			isset($request['per_page']) ? $request['per_page'] : 10,
			isset($request['status']) ? $request['status'] : ''
		);
		$quizzes_array = array();
		foreach ( $quiz_list as $quiz_model ) {
			$quiz_array = array(
				'id' => $quiz_model->id,
				'name' => $quiz_model->name,
				'status' => $quiz_model->status
			);
			array_push( $quizzes_array, $quiz_array );
		}
    return rest_ensure_response($quizzes_array);
  }

	/**
    * list quiz fields
    *
    * @param WP_REST_Request $request WP_REST_Request onject.
    *
    * request path param  Mandatory.
    * @type int  quiz_id   Quiz ID to retrive the fields for.
    *
    * @return array|WP_Error array of quiz details along with fields | WP_Error object with error details.
    */
  public function get_quiz_fields( $request ){
		if( isset( $request['quiz_id'] ) ){
			$quiz_field_list = Forminator_API::get_quiz( $request['quiz_id'] );
	    if( is_wp_error( $quiz_field_list ) ){
	      return new WP_Error( 'rest_bad_request', $quiz_field_list->get_error_messages()[0], array( 'status' => 404 ) );
	    }
	    return rest_ensure_response( $quiz_field_list );
		}
    else{
      return new WP_Error( 'rest_bad_request', 'Quiz does not exist!', array( 'status' => 404 ) );
    }
  }



	/**
    * Creates a webhook entry
    * The events available in $supported_events array only accepted
    *
    * @param WP_REST_Request $request WP_REST_Request onject.
    *
    * @return array|WP_Error Array with Webhook ID | WP_Error object with error details.
    */
  public function create_webhook( $request ){
    $entry = json_decode( $request->get_body() );
    $resource_type = '';
    $resource_id = '';
    if( isset( $entry->form_id ) && is_numeric( $entry->form_id ) && ( 'form_entry_added' === $entry->event ) ){
      $form_detail = Forminator_API::get_form( $entry->form_id );
      if( is_wp_error( $form_detail ) ){
        return new WP_Error( 'rest_bad_request', $form_detail->get_error_messages()[0], array( 'status' => 400 ) );
      }
      $resource_type = 'form';
      $resource_id = $entry->form_id;
    }
    if( isset( $entry->poll_id ) && is_numeric( $entry->poll_id ) && ( 'poll_added' === $entry->event ) ){
      $poll_detail = Forminator_API::get_poll( $entry->poll_id );
      if( is_wp_error( $poll_detail ) ){
        return new WP_Error( 'rest_bad_request', $poll_detail->get_error_messages()[0], array( 'status' => 400 ) );
      }
      $resource_type = 'poll';
      $resource_id = $entry->poll_id;
    }
    if( isset( $entry->quiz_id ) && is_numeric( $entry->quiz_id ) && ( 'quiz_added' === $entry->event ) ){
      $quiz_detail = Forminator_API::get_quiz( $entry->quiz_id );
      if( is_wp_error( $quiz_detail ) ){
        return new WP_Error( 'rest_bad_request', $quiz_detail->get_error_messages()[0], array( 'status' => 400 ) );
      }
      $resource_type = 'quiz';
      $resource_id = $entry->quiz_id;
    }
    if( empty( $resource_type ) || empty( $resource_id ) ){
      return new WP_Error( 'rest_bad_request', 'Invalid resource', array( 'status' => 400 ) );
    }
    if( ( isset( $entry->name ) ) && ( isset( $entry->url ) ) && ( isset( $entry->event ) ) && ( in_array( $entry->event, self::$supported_events ) ) && ( preg_match( "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $entry->url ) ) ){
      $args = array(
        'name' => $entry->name,
        'url' => $entry->url,
        'event' => $entry->event,
        'resource_id' => $resource_id,
        'resource_type' => $resource_type
      );
    	$post_name = "Forminator ";
			$post_id = $this->create_webhook_post( $post_name, $args );
			if( is_wp_error( $post_id ) ){
				$errors = $post_id->get_error_messages();
				return new WP_Error( 'rest_bad_request', $errors, array( 'status' => 400 ) );
			}
			return rest_ensure_response(
				array(
					'webhook_id' => $post_id
				)
			);
		}
		else{
			return new WP_Error( 'rest_bad_request', 'Data validation failed', array( 'status' => 400 ) );
		}
	}

	/**
    * Deletes a webhook entry
    * Webhook ID returned from webhook create event should be used. Use minimum user scope.
    *
    * @param WP_REST_Request $request WP_REST_Request onject.
    *
    * @return array|WP_Error Array with success message | WP_Error object with error details.
    */
  public function delete_webhook( $request ){
	   $webhook_id = $request['webhook_id'];
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
     * Fires once a form entry is stored in DB and before sends mail.
     *
		 * @param Forminator_CForm_Front_Mail 	$front_mail			Forminator_CForm_Front_Mail object.
		 * @param Forminator_Form_Model 				$custom_form		Current form object.
		 * @param array                       	$data  					form fields and value data array.
		 * @param Forminator_Form_Entry_Model 	$entry 					saved entry.
		 *
     * @return array|null Webhook payload. event and form entry details | null if criteria not match.
     */
  public function payload_form_entry_added( $front_mail, $custom_form, $data, $entry ){
    $args = array(
      'event' => 'form_entry_added',
      'resource_type' => 'form',
      'resource_id' => $data['form_id']
    );
    $webhooks = $this->get_webhook_posts( $args );
		if( !empty( $webhooks ) ){
			foreach ( $entry as $key => $value ) {
	      if( 'meta_data' != $key ){
	        $data[$key] = $value;
	      }
	    }
	    foreach ( $entry->meta_data as $key => $value ) {
	      $data[$key] = $value['value'];
	    }
	    $event_data = array(
	      'event' => 'form_entry_added',
	      'data' => $data
	    );
	    foreach( $webhooks as $webhook ){
	      $url = $webhook->url;
	      zoho_flow_execute_webhook( $url, $event_data, array() );
	    }
		}

  }

	/**
		* Fires once a poll entry is stored in DB and before sends mail.
		*
		* @param Forminator_Poll_Model 				$current_poll			Current poll object.
		* @param Forminator_Poll_Model 				$poll							Current poll object.
		* @param array                       	$data  						Poll field and answer array.
		* @param Forminator_Form_Entry_Model 	$entry 						saved entry.
		*
		* @return array|null Webhook payload. event and poll entry details | null if criteria not match.
		*/
  public function payload_poll_added( $current_poll, $poll, $data, $entry ){
    $args = array(
      'event' => 'poll_added',
      'resource_type' => 'poll',
      'resource_id' => $data['form_id']
    );
    $webhooks = $this->get_webhook_posts( $args );
		if( !empty( $webhooks ) ){
			foreach ( $entry as $key => $value ) {
	      if($key != 'meta_data'){
	        $data[$key] = $value;
	      }
	    }
	    foreach ( $entry->meta_data as $key => $value ) {
	      $data[$key] = $value['value'];
	    }
	    $data['answer'] = $data[$data[$data['form_id']]];
			$event_data = array(
	      'event' => 'poll_added',
	      'data' => $data
	    );
	    foreach( $webhooks as $webhook ){
	      $url = $webhook->url;
	      zoho_flow_execute_webhook( $url, $event_data, array() );
	    }
		}
  }

	/**
		* Fires once a quiz entry is stored in DB and before sends mail.
		*
		* @param Forminator_Quiz_Front_Mail 	$current_quiz			Forminator_Quiz_Front_Mail object.
		* @param Forminator_Quiz_Model 			$quiz					Current quiz object.
		* @param array                       	$data  					quiz fields and value array.
		* @param Forminator_Form_Entry_Model 	$entry 					saved entry.
		*
		* @return array|null Webhook payload. event and quiz entry details | null if criteria not match.
		*/
  public function payload_quiz_added( $current_quiz, $quiz, $data, $entry ){
    $args = array(
      'event' => 'quiz_added',
      'resource_type' => 'quiz',
      'resource_id' => $data['form_id']
    );
    $webhooks = $this->get_webhook_posts( $args );
		if( !empty( $webhooks ) ){
			foreach ( $entry as $key => $value ) {
	      if($key != 'meta_data'){
	        $data[$key] = $value;
	      }
	    }
	    foreach ( $entry->meta_data as $key => $value ) {
	      $data[$key] = $value['value'];
	    }
	    foreach ( $quiz->questions as $key => $value ) {
	      $data[$value['slug']] = $value['answers'][$data['answers'][$value['slug']]]['title'];
	      if(empty($value['answers'][$data['answers'][$value['slug']]]['toggle'])){
	        $data[$value['slug'].'_is_correct'] = false;
	      }
	      else{
	        $data[$value['slug'].'_is_correct'] = true;
	      }
	    }
			$event_data = array(
	      'event' => 'quiz_added',
	      'data' => $data
	    );
	    foreach( $webhooks as $webhook ){
	      $url = $webhook->url;
	      zoho_flow_execute_webhook( $url, $event_data,array() );
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
		if( ! function_exists('get_plugin_data') ){
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugin_dir = ABSPATH . 'wp-content/plugins/forminator/forminator.php';
		if(file_exists($plugin_dir)){
			$plugin_data = get_plugin_data( $plugin_dir );
			@$system_info['forminator'] = $plugin_data['Version'];
		}
		return rest_ensure_response( $system_info );
	}
}

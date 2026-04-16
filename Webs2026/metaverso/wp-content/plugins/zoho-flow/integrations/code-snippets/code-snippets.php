<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow          2.11.0
 * @since code-snippets     3.6.5.1
 */
class Zoho_Flow_Code_Snippets extends Zoho_Flow_Service{
    
    /**
     * List snippets
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return WP_REST_Response    WP_REST_Response array with snippet details
     */
    public function list_all_snippets( $request ){
        $all_snippet_objects = Code_Snippets\get_snippets();
        $all_snippets = array();
        foreach( $all_snippet_objects as $snippet_object ){
            error_log(print_r($snippet_object->get_fields(),true));
            $snippet = $snippet_object->get_fields();
            unset( $snippet['code'] );
            $all_snippets[] = $snippet;
        }
        
        return rest_ensure_response( $all_snippets );
    }
    
    /**
     * Activate snippet
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $snippet_id     ID of the snippet.
     *
     * @return WP_REST_Response    WP_REST_Response Action message
     */
    public function activate_snippet( $request ){
        $snippet_id = $request->get_url_params()['snippet_id'];
        if( isset( $snippet_id ) && is_numeric( $snippet_id )){
            $snippet_before = Code_Snippets\get_snippet( $snippet_id );
            if( $snippet_before->__get('id') ){
                if( $snippet_before->__get('active') ){
                    return rest_ensure_response( array(
                        'message' => 'Snippet has already been activated.'
                    ));
                }
                Code_Snippets\activate_snippet( $snippet_id );
                $snippet_after = Code_Snippets\get_snippet( $snippet_id );
                if( !$snippet_after->__get('active') ){
                    return new WP_Error( 'rest_bad_request', 'Unable to activate the snippet.', array( 'status' => 400 ) );
                }
                return rest_ensure_response( array(
                    'message' => 'Snippet has been activated.'
                ));
            }
        }
        return new WP_Error( 'rest_bad_request', 'Snippet does not exist!', array( 'status' => 400 ) );
    }
    
    /**
     * Deactivate snippet
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $snippet_id     ID of the snippet.
     *
     * @return WP_REST_Response    WP_REST_Response Action message
     */
    public function deactivate_snippet( $request ){
        $snippet_id = $request->get_url_params()['snippet_id'];
        if( isset( $snippet_id ) && is_numeric( $snippet_id )){
            $snippet_before = Code_Snippets\get_snippet( $snippet_id );
            if( $snippet_before->__get('id') ){
                if( !$snippet_before->__get('active') ){
                    return rest_ensure_response( array(
                        'message' => 'Snippet is already in an inactive state.'
                    ));
                }
                Code_Snippets\deactivate_snippet( $snippet_id );
                $snippet_after = Code_Snippets\get_snippet( $snippet_id );
                if( $snippet_after->__get('active') ){
                    return new WP_Error( 'rest_bad_request', 'Unable to deactivate the snippet.', array( 'status' => 400 ) );
                }
                return rest_ensure_response( array(
                    'message' => 'Snippet has been deactivated.'
                ));
            }
        }
        return new WP_Error( 'rest_bad_request', 'Snippet does not exist!', array( 'status' => 400 ) );
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/code-snippets/code-snippets.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['code_snippets'] = $plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}
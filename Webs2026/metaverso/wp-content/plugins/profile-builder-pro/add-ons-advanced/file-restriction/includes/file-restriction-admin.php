<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Enqueue Admin Scripts
 *
 */
function wppb_file_restriction_enqueue_scripts( $hook ){

    if ( $hook === 'profile-builder_page_profile-builder-content_restriction' || $hook === 'profile-builder_page_profile-builder-toolbox-settings' ) {

        // load the Select2 library if not loaded already
        if ( !wp_script_is( 'wppb-select2', 'enqueued' ) )
            wp_enqueue_script( 'wppb-select2', WPPB_PLUGIN_URL . 'assets/js/select2/select2.min.js', array(), PROFILE_BUILDER_VERSION, true );

        if ( !wp_style_is( 'wppb-select2-style', 'enqueued' ) )
            wp_enqueue_style( 'wppb-select2-style', WPPB_PLUGIN_URL . 'assets/css/select2/select2.min.css', false, PROFILE_BUILDER_VERSION );

        // link the File Restriction JS file
        wp_enqueue_script( 'wppb-file-restriction', WPPB_PAID_PLUGIN_URL . 'add-ons-advanced/file-restriction/assets/js/file-restriction.js', array( 'jquery' ), PROFILE_BUILDER_VERSION, true );
    }

}
add_action( 'admin_enqueue_scripts', 'wppb_file_restriction_enqueue_scripts' );


/**
 * Add File Restriction options to Content Restriction settings
 *
 */
function wppb_file_restriction_options_add( $content_restriction_settings ) {

    if( !empty( $content_restriction_settings['restrict_all_files'] ) && $content_restriction_settings['restrict_all_files'] === 'yes' ){
        $file_restriction_activated = 'yes';
    }
    else $file_restriction_activated = 'no';

    $web_server = wppb_file_restriction_get_web_server();

    ?>
    <!-- File Restriction -->
    <h4 class="" style="font-weight: 600; font-size: 13px; line-height: 16px; text-transform: uppercase; color: #1E1E1E; display: flex; justify-content: flex-start; margin: 35px 0 0 0;"><?php esc_html_e( 'Files Restriction', 'profile-builder' ); ?></h4>

    <!-- Web Server -->
    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
        <label class="cozmoslabs-form-field-label" for="restrict-all-files-enable"><?php esc_html_e( 'Web Server', 'profile-builder' ); ?></label>

        <p class="cozmoslabs-description cozmoslabs-description-align-right"><?php echo  esc_html( ucfirst( $web_server ) ) ?></p>
    </div>

    <!-- Protect ALL Files -->
    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
        <label class="cozmoslabs-form-field-label" for="restrict-all-files-enable"><?php esc_html_e( 'Protect ALL File Types', 'profile-builder' ); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" name="wppb_content_restriction_settings[restrict_all_files]" id="restrict-all-files-enable" value="yes" <?php echo ( $file_restriction_activated == 'yes' ) ? 'checked' : ''; ?> >
            <label class="cozmoslabs-toggle-track" for="restrict-all-files-enable"></label>
        </div>

        <div class="cozmoslabs-toggle-description">
            <label for="restrict-all-files-enable" class="cozmoslabs-description"><?php esc_html_e( 'Protect every file type from your Media Library.', 'profile-builder' ); ?></label>
        </div>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php echo sprintf( esc_html__( 'This option can cause performance issues, enable at your own risk. %1$sLearn more%2$s', 'profile-builder' ), '<a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/files-restriction/?utm_source=wpbackend&utm_medium=clientsite&utm_content=file-restriction-restart-nginx-notice&utm_campaign=PBDocs#Protect_All_File_Types" target="_blank">', '</a>' ) ?></p>
    </div>

    <!-- Protected File Types -->
    <div class="cozmoslabs-form-field-wrapper">
        <label class="cozmoslabs-form-field-label" for="restricted-file-types"><?php esc_html_e( 'Protected File Types', 'profile-builder' ); ?></label>

        <select name="wppb_content_restriction_settings[restricted_file_types][]" class="wppb-select wppb-select2" id="restricted-file-types" multiple>
            <?php

            $default_restricted_files = array( 'zip', 'gz', 'tar', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'xlsm', 'csv', 'pdf', 'mp4', 'm4v', 'mp3', 'ts', 'key', 'm3u8' );
            $content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );
            $selected_file_types = !empty( $content_restriction_settings['restricted_file_types'] ) ? $content_restriction_settings['restricted_file_types'] : array();

            // create the file extensions list for the Restricted File Types selector (include any extra extensions added by users)
            $file_types_list = array_merge( $default_restricted_files, $selected_file_types );
            $file_types_list = array_unique( $file_types_list );
            $file_types_list = array_values( $file_types_list );

            foreach ( $file_types_list as $file_type ) {
                echo '<option value="'. esc_attr( $file_type ) .'"' . ( ( !empty( $selected_file_types )  && in_array( $file_type, $selected_file_types ) ) ? ' selected' : '' ) . '>' . esc_html( $file_type ) . '</option>';
            }

            ?>
        </select>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select the type of files within the WordPress Upload directory that you want to protect.', 'profile-builder' ); ?></p>
    </div>


    <?php if ( $web_server === 'nginx' ): ?>

        <!-- Nginx Configuration Rule -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="restrict-all-files-enable"><?php esc_html_e( 'Nginx Configuration', 'profile-builder' ); ?></label>

            <div class="cozmoslabs-description cozmoslabs-description-align-right" style="position: relative; padding-left: 0; cursor: copy;">
                <div title='Click to copy' class="wppb-shortcode_copy-text wppb-dashboard-shortcodes__row__input" style="overflow-wrap: anywhere;">
                    # Profile Builder PRO: File Restriction <br>
                    include <?php echo esc_html( WPPB_PAID_PLUGIN_DIR . '/add-ons-advanced/file-restriction/includes/file-restriction-nginx.conf' ) ?>;
                </div>
                <span style='display: none; position: absolute; top: 5px; right: 10px;' class='wppb-copy-message'><strong><?php esc_html_e( 'Nginx directive copied!', 'profile-builder' ); ?></strong></span>
            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'This Nginx include directive needs to be added into the Nginx configuration file on your server, for the File Restriction feature to work correctly. After the directive was inserted, the Nginx web-server also needs to be restarted.', 'profile-builder' ); ?>
            </p>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'NOTE: If the restriction settings are changed, the Nginx web server needs to be restarted for the new rules to take effect.', 'profile-builder' ); ?>
            </p>
        </div>

    <?php endif; ?>


    <?php

    // check if the user disabled the rewrite process for the File Restriction .htaccess rules
    $rewrite_rules_enabled = apply_filters( 'wppb_file_restriction_rewrite_apache_rule', true );

    if ( $web_server === 'apache' && ( !is_writable( ABSPATH . '.htaccess' ) || !$rewrite_rules_enabled ) ):
        ?>

        <!-- Apache Information -->
        <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
            <label class="cozmoslabs-form-field-label" for="restrict-all-files-enable"><?php esc_html_e( 'Apache Configuration', 'profile-builder' ); ?></label>

            <div style="flex-basis: 250px; flex-grow: 1; position: relative; padding-left: 0; cursor: copy;">
                <div title='Click to copy' class="wppb-shortcode_copy-text wppb-dashboard-shortcodes__row__input" style="overflow-wrap: anywhere;">
                    <?php echo nl2br( esc_html( wppb_file_restriction_create_htaccess_rule('') ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <span style='display: none; position: absolute; top: 5px; right: 10px;' class='wppb-copy-message'><strong><?php esc_html_e( 'Apache directive copied!', 'profile-builder' ); ?></strong></span>
            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'It appears that the .htaccess file in your WordPress root directory is not writable.', 'profile-builder' ); ?>
                <br>
                <?php esc_html_e( 'This means that WordPress is unable to automatically update the file with necessary rules or changes.', 'profile-builder' ); ?>
                <br>
                <?php esc_html_e( 'To fix this issue follow the steps bellow.', 'profile-builder' ); ?>
            </p>

            <?php if ( !$rewrite_rules_enabled ): ?>
                <p class="cozmoslabs-description cozmoslabs-description-space-left">
                    <?php printf( esc_html__( '%1$sNOTE:%2$s The %3$s filter hook is preventing File Restriction rules from being written to the %4$s file!', 'profile-builder' ), '<strong>', '</strong>', '<em>wppb_file_restriction_rewrite_apache_rule</em>', '<em>.htaccess</em>' ); ?>
                </p>
            <?php endif; ?>


            <div class="cozmoslabs-description cozmoslabs-description-space-left">

                <p><?php esc_html_e( 'Check File Permissions:', 'profile-builder' ); ?></p>
                <ul style="padding-left: 40px; list-style-type: square;">
                    <li><?php esc_html_e( 'Using FTP/SFTP:', 'profile-builder' ); ?>
                        <ul style="padding-left: 40px; list-style-type: square;">
                            <li><?php esc_html_e( 'Connect to your server using an FTP/SFTP client.', 'profile-builder' ); ?></li>
                            <li><?php esc_html_e( 'Navigate to the root directory of your WordPress installation (where the .htaccess file is located).', 'profile-builder' ); ?></li>
                            <li><?php esc_html_e( 'Right-click on the .htaccess file and select "File Permissions" or "Properties."', 'profile-builder' ); ?></li>
                            <li><?php esc_html_e( 'Ensure the file permissions are set to 644 or 664. This allows the file to be readable and writable by the owner, and readable by the group and others.', 'profile-builder' ); ?></li>
                        </ul>
                    </li>
                    <li><?php esc_html_e( 'Using Hosting Control Panel:', 'profile-builder' ); ?>
                        <ul style="padding-left: 40px; list-style-type: square;">
                            <li><?php esc_html_e( 'Log in to your hosting control panel (e.g., cPanel, Plesk).', 'profile-builder' ); ?></li>
                            <li><?php esc_html_e( 'Open the File Manager and navigate to the root directory of your WordPress installation.', 'profile-builder' ); ?></li>
                            <li><?php esc_html_e( 'Find the .htaccess file and check its permissions. Set them to 644 or 664 if necessary.', 'profile-builder' ); ?></li>
                        </ul>
                    </li>
                </ul>

                <p><?php esc_html_e( 'Manually Edit .htaccess:', 'profile-builder' ); ?></p>
                <ul style="padding-left: 40px; list-style-type: square;">
                    <li><?php esc_html_e( 'The .htaccess file is located in the root directory of your WordPress installation.', 'profile-builder' ); ?></li>
                    <li><?php esc_html_e( 'Use a text editor to open the .htaccess file. Ensure you back up the file before making changes.', 'profile-builder' ); ?></li>
                    <li><?php esc_html_e( 'Include the directive provided above.', 'profile-builder' ); ?></li>
                </ul>

            </div>

            <p class="cozmoslabs-description cozmoslabs-description-space-left">
                <?php esc_html_e( 'If you are unsure about making these changes or if the issue persists, please contact the support team of your hosting provider for assistance.', 'profile-builder' ); ?>
            </p>

        </div>

    <?php endif; ?>

    <?php
}
add_action( 'wppb_extra_content_restriction_settings', 'wppb_file_restriction_options_add' );


/**
 * Handle File Restriction options save
 *
 * - rewrite restriction rules accordingly
 *
 */
function wppb_file_restriction_options_save( $old_data, $new_data, $meta_key ) {
    $rewrite_rules = false;
    $temp_data = $new_data;

    if ( isset( $new_data['restrict_all_files'] ) ) {
        if ( !isset( $old_data['restrict_all_files'] ) || $old_data['restrict_all_files'] !== $new_data['restrict_all_files'] ) {
            $rewrite_rules = true;
        }
    } elseif ( isset( $old_data['restrict_all_files'] ) && $old_data['restrict_all_files'] === 'yes' ) {
        $rewrite_rules = true;
        $temp_data['restrict_all_files'] = 'no';
    }

    if ( isset( $new_data['restricted_file_types'] ) ) {
        if ( !isset( $old_data['restricted_file_types'] ) || $old_data['restricted_file_types'] !== $new_data['restricted_file_types'] ) {
            $rewrite_rules = true;
        }
    } elseif ( isset( $old_data['restricted_file_types'] ) ) {
            $rewrite_rules = true;
            $temp_data['restricted_file_types'] = array();
    }

    // Update settings here if File Restriction options have changed, for the restriction rules to be correctly applied bellow
    if ( $temp_data != $new_data )
        update_option( 'wppb_content_restriction_settings', $temp_data );

    if ( $rewrite_rules ) {
        $web_server = wppb_file_restriction_get_web_server();

        if ( $web_server === 'apache' ) {

            // reload the .htaccess file to update the File Restriction rules
            flush_rewrite_rules();

        }
        elseif ( $web_server === 'nginx' ) {

            // regenerate the File Restriction nginx config file
            wppb_file_restriction_rewrite_nginx_rules();

        }
    }

}
add_action('update_option_wppb_content_restriction_settings', 'wppb_file_restriction_options_save', 10, 3 );



/****************************************************************
 * APACHE
 ***************************************************************/

/**
 * Create .htaccess file rules according to the File Restriction settings
 *
 */
function wppb_file_restriction_create_htaccess_rule( $rules ) {

    // WordPress root directory
    $wp_root = basename(ABSPATH);

    // Profile Builder root directory (different paid versions)
    $pb_root = basename(WPPB_PAID_PLUGIN_DIR);

    // File Restriction Engine path
    $file_path = '/'. $wp_root . '/wp-content/plugins/' . $pb_root . '/add-ons-advanced/file-restriction/includes/file-restriction-engine.php';

    $content_restriction_settings = get_option( 'wppb_content_restriction_settings', 'not_found' );
    $htaccess_rule = '';

    if ( wppb_check_if_add_on_is_active( 'wppb_fileRestriction' ) ) {
        if ( !empty( $content_restriction_settings['restrict_all_files'] ) && $content_restriction_settings['restrict_all_files'] === 'yes' ) {
            $htaccess_rule = <<<EOD

                          # Profile Builder PRO: File Restriction
                          <IfModule mod_rewrite.c>
                              RewriteBase /
                              RewriteRule ^wp-content/uploads/(.*)$ $file_path [L]
                          </IfModule>

                          EOD;
        }
        else {
            $counter              = 0;
            $number_of_extensions = !empty( $content_restriction_settings['restricted_file_types'] ) ? count( $content_restriction_settings['restricted_file_types'] ) : 0;
            $file_types_rule      = '';

            foreach ( $content_restriction_settings['restricted_file_types'] as $file_type ) {
                $counter++;
                $file_types_rule .= '\.' . $file_type;

                if ( $counter < $number_of_extensions ) {
                    $file_types_rule = $file_types_rule . '|';
                }
            }

            if ( !empty( $file_types_rule ) ) {
                $htaccess_rule = <<<EOD
                              
                              # Profile Builder PRO: File Restriction
                              <IfModule mod_rewrite.c>
                                  RewriteBase /
                                  RewriteRule ^wp-content/uploads/.*($file_types_rule)$ $file_path [L]
                              </IfModule>

                              EOD;
            }
        }
    }

    return $rules . $htaccess_rule;
}


/**
 * Update .htaccess file rules
 *
 */
function wppb_file_restriction_insert_htaccess_rule( $rules ) {

    // check if the user disabled the rewrite process for the File Restriction .htaccess rules
    $rewrite_rules_enabled = apply_filters( 'wppb_file_restriction_rewrite_apache_rule', true );

    if ( !$rewrite_rules_enabled ) {
        return $rules;
    }

    return wppb_file_restriction_create_htaccess_rule( $rules );
}
add_filter('mod_rewrite_rules', 'wppb_file_restriction_insert_htaccess_rule');



/****************************************************************
 * NGINX
 ***************************************************************/

/**
 * Generate Nginx rules based on File Restriction settings.
 *
 * @return string The Nginx configuration rules.
 */
function wppb_file_restriction_generate_nginx_rules() {

    // Profile Builder root directory (different paid versions)
    $pb_root = basename(WPPB_PAID_PLUGIN_DIR);

    // File Restriction Engine path
    $file_path = '/wp-content/plugins/'. $pb_root .'/add-ons-advanced/file-restriction/includes/file-restriction-engine.php';

    $content_restriction_settings = get_option('wppb_content_restriction_settings', 'not_found');
    $nginx_rule = '';

    if ( wppb_check_if_add_on_is_active( 'wppb_fileRestriction' ) ) {
        if ( !empty( $content_restriction_settings['restrict_all_files'] ) && $content_restriction_settings['restrict_all_files'] === 'yes' ) {
            $nginx_rule = <<<EOD
                            # Profile Builder PRO: File Restriction
                            location ~* ^/wp-content/uploads/(.*)$ {
                                rewrite ^/wp-content/uploads/(.*)$ $file_path last;
                            }
                            EOD;
        } elseif ( !empty( $content_restriction_settings['restricted_file_types'] ) ) {
            $counter = 0;
            $number_of_extensions = count( $content_restriction_settings['restricted_file_types'] );
            $file_types_rule = '';

            foreach ( $content_restriction_settings['restricted_file_types'] as $file_type ) {
                $counter++;
                $file_types_rule .= $file_type;

                if ( $counter < $number_of_extensions ) {
                    $file_types_rule .= '|';
                }
            }

            if ( !empty( $file_types_rule ) ) {
                $nginx_rule = <<<EOD
                                # Profile Builder PRO: File Restriction
                                location ~* ^/wp-content/uploads/.*\.($file_types_rule)$ {
                                    rewrite ^/wp-content/uploads/.*\.($file_types_rule)$ $file_path last;
                                }
                                EOD;
            }
        }
    }

    return $nginx_rule;
}


/**
 * Rewrite Nginx rules to the configuration file
 *
 */
function wppb_file_restriction_rewrite_nginx_rules() {

    // File Restriction rules for nginx
    $nginx_rules = wppb_file_restriction_generate_nginx_rules();

    // File Restriction nginx config file path
    $nginx_conf_file_path = esc_html( WPPB_PAID_PLUGIN_DIR . '/add-ons-advanced/file-restriction/includes/file-restriction-nginx.conf' );

    // Update the File Restriction rules in nginx config file (if the file is missing it will be automatically created)
    file_put_contents( $nginx_conf_file_path, $nginx_rules );


    // Reset the admin notice informing the user to restart the Nginx web server (File Restriction rules have been updated)
    delete_user_meta( get_current_user_id(), 'wppb_file_restriction_nginx_restart_dismiss_notification' );


    // Remove 'wppb_dismiss_admin_notification' argument from url for the Nginx restart notification to show up again when needed
    // --> in case the File Restriction options get to be changed and saved again, right after the Nginx restart notification was dismissed and the "wppb_dismiss_admin_notification" is still present, the notice remains dismissed and will not show up again
    if ( isset( $_POST['_wp_http_referer'] ) && strpos( sanitize_text_field( $_POST['_wp_http_referer'] ), 'wppb_dismiss_admin_notification=wppb_file_restriction_nginx_restart' ) !== false ) {
        $redirect_url = remove_query_arg( 'wppb_dismiss_admin_notification', sanitize_text_field( $_POST['_wp_http_referer'] ) );

        wp_safe_redirect( $redirect_url );
        exit();
    }

}


/****************************************************************
 * FILE UPLOAD
 ***************************************************************/

/**
 * Set Upload Field restriction settings for the uploaded file's attachment post
 *
 */
function wppb_file_restriction_set_upload_field_attachment_restrictions( $user_id, $attachment_id, $field_meta_name, $simple_upload ) {

    if ( empty( $attachment_id ) )
        return;

    $toolbox_fields_settings = get_option( 'wppb_toolbox_fields_settings' );
    $restricted_file = isset( $toolbox_fields_settings['restrict-uploaded-file'] ) ? $toolbox_fields_settings['restrict-uploaded-file'] : "" ;
    $restricted_file_roles = isset( $toolbox_fields_settings['restricted-file-roles'] ) ? $toolbox_fields_settings['restricted-file-roles'] : array() ;
    $restricted_upload_fields = isset( $toolbox_fields_settings['restricted-upload-fields'] ) ? $toolbox_fields_settings['restricted-upload-fields'] : array() ;

    // do not apply any restrictions if "Restrict Uploaded File" option is not active
    if( !empty( $restricted_file ) && $restricted_file === 'yes' && ( empty( $restricted_upload_fields ) || in_array( $field_meta_name, $restricted_upload_fields ) ) ) {

        // set restriction type to Default for the Global Content Restriction settings to be applied
        add_post_meta( $attachment_id, 'wppb-content-restrict-type', 'default' );

        // restrict attachment to logged-in users
        add_post_meta( $attachment_id, 'wppb-content-restrict-user-status', 'loggedin' );

        // restrict attachment to user roles selected in "Restricted File to Roles" option
        if( !empty( $restricted_file_roles ) && is_array( $restricted_file_roles ) ) {
            $user_role_slugs = array_map( 'sanitize_text_field', $restricted_file_roles );
            foreach( $user_role_slugs as $role_slug ) {
                if( ! empty( $role_slug ) ) {
                    add_post_meta( $attachment_id, 'wppb-content-restrict-user-role',  $role_slug );
                }
            }
        }
    }

}
add_action( 'wppb_upload_field_after_user_meta_update', 'wppb_file_restriction_set_upload_field_attachment_restrictions', 10, 4 );


/**
 * Add Upload Field restriction settings to Advanced Settings -> Fields section
 *
 */
function wppb_file_restriction_add_upload_field_restriction_settings( $toolbox_fields_settings ) {
    ?>
    <!-- Restrict Upload files to logged-in users -->
    <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
        <label class="cozmoslabs-form-field-label" for="toolbox-restrict-uploaded-file"><?php esc_html_e('Restrict Uploaded Files', 'profile-builder'); ?></label>

        <div class="cozmoslabs-toggle-container">
            <input type="checkbox" id="toolbox-restrict-uploaded-file" name="wppb_toolbox_fields_settings[restrict-uploaded-file]"<?php echo ( ( isset( $toolbox_fields_settings['restrict-uploaded-file'] ) && ( $toolbox_fields_settings['restrict-uploaded-file'] == 'yes' ) ) ? ' checked' : '' ); ?> value="yes">
            <label class="cozmoslabs-toggle-track" for="toolbox-restrict-uploaded-file"></label>
        </div>

        <div class="cozmoslabs-toggle-description">
            <label for="toolbox-restrict-uploaded-file" class="cozmoslabs-description"><?php esc_html_e( 'Restrict uploaded files to logged-in users.', 'profile-builder' ); ?></label>
        </div>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Enabling this option, the uploaded files will be restricted to logged-in users only.', 'profile-builder' ); ?></p>
    </div>

    <!-- Restricted Upload fields -->
    <div class="cozmoslabs-form-field-wrapper" id="restricted-upload-fields">
        <label class="cozmoslabs-form-field-label" for="toolbox-restricted-upload-fields"><?php esc_html_e( 'Restricted Upload Fields', 'profile-builder' ); ?></label>

        <select id="toolbox-restricted-upload-fields" class="wppb-select wppb-select2" name="wppb_toolbox_fields_settings[restricted-upload-fields][]" multiple="multiple">

            <?php

            $form_fields = get_option('wppb_manage_fields', array());
            $restricted_upload_fields = isset( $toolbox_fields_settings['restricted-upload-fields'] ) ? $toolbox_fields_settings['restricted-upload-fields'] : array() ;

            foreach( $form_fields as $field ) {
                if ( $field['field'] === 'Upload' )
                    echo '<option value="'.esc_attr( $field['meta-name'] ).'" '. ( in_array( $field['meta-name'], $restricted_upload_fields ) ? "selected" : "" ) .'>'.esc_html( $field['field-title'] ).'</option>';
            }

            ?>

        </select>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Selecting any Upload Fields, restrictions will apply to files uploaded through those fields only.', 'profile-builder' ); ?></p>
    </div>

    <!-- Restrict Upload files to user roles -->
    <div class="cozmoslabs-form-field-wrapper" id="restricted-file-roles">
        <label class="cozmoslabs-form-field-label" for="toolbox-restricted-file-roles"><?php esc_html_e( 'Restrict Files to User Roles', 'profile-builder' ); ?></label>

        <select id="toolbox-restricted-file-roles" class="wppb-select wppb-select2" name="wppb_toolbox_fields_settings[restricted-file-roles][]" multiple="multiple">

            <?php

            global $wp_roles;

            $user_roles = $wp_roles->role_names;
            $selected_roles = isset( $toolbox_fields_settings['restricted-file-roles'] ) ? $toolbox_fields_settings['restricted-file-roles'] : array() ;

            if ( !empty( $user_roles ) ) {

                foreach( $user_roles as $role_slug => $role_name ) {
                    echo '<option value="'.esc_attr( $role_slug ).'" '. ( in_array( $role_slug, $selected_roles ) ? "selected" : "" ) .'>'.esc_html( $role_name ).'</option>';
                }

            }

            ?>

        </select>

        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Selecting any User Roles will restrict the uploaded files to those roles only.', 'profile-builder' ); ?></p>
    </div>
    <?php
}
add_action( 'wppb_extra_toolbox_fields_settings', 'wppb_file_restriction_add_upload_field_restriction_settings' );
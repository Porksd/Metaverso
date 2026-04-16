<?php
// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

/*
* Function that registers the settings for the bbPress options page
*
* @since v1.0.0
*
*/
function wppb_in_bbpress_register_settings() {
    register_setting( 'wppb_bbpress_settings', 'wppb_bbpress_settings', 'wppb_in_bbpress_settings_sanitize' );
}
if ( is_admin() ) {
    add_action('admin_init', 'wppb_in_bbpress_register_settings');
}

/**
 * Function that creates the "bbPress" submenu page
 *
 * @since v.1.0.0
 *
 * @return void
 */
function wppb_in_bbp_settings_submenu_page() {
    add_submenu_page( 'profile-builder', __( 'bbPress', 'profile-builder' ), __( 'bbPress', 'profile-builder' ), 'manage_options', 'profile-builder-bbpress', 'wppb_in_bbpress_settings_content' );
}
add_action( 'admin_menu', 'wppb_in_bbp_settings_submenu_page', 19 );  // this priority adds bbPress Sync below the Addons tab in PB menu


// set default values for bbPress settings page
function wppb_in_bbpress_settings_defaults(){
    $wppb_bbpress_settings = get_option( 'wppb_bbpress_settings', 'not_found' );

    $edit_profile_form = 'wppb-default-edit-profile';

    $versions = array( 'Profile Builder Pro', 'Profile Builder Agency', 'Profile Builder Unlimited', 'Profile Builder Dev' );

    // Check Profile Builder version, display User Listing only for Pro
    if ( defined('PROFILE_BUILDER') && in_array( PROFILE_BUILDER, $versions ) )
        $user_listing = 'Userlisting';
    else {
        $user_listing = '';

        if( !empty( $wppb_bbpress_settings['Userlisting'] ) ){
            $wppb_bbpress_settings['Userlisting'] = '';
            update_option( 'wppb_bbpress_settings', $wppb_bbpress_settings );
        }

    }

    // set default values
    if ( $wppb_bbpress_settings == 'not_found' )
        update_option( 'wppb_bbpress_settings', array( 'UserListing' => $user_listing, 'EditProfileForm' => $edit_profile_form, 'Login' =>'no' ) );

}


/**
 * Function that adds content to the "bbPress" submenu page
 *
 * @since v.1.0.0
 *
 * @return string
 */
function wppb_in_bbpress_settings_content() {

    // set default values for bbPress settings page
    wppb_in_bbpress_settings_defaults();

    ?>
    <div class="wrap wppb-wrap cozmoslabs-wrap">
        <form method="post" action="options.php">
            <?php $wppb_bbpress_settings = get_option( 'wppb_bbpress_settings' ); ?>
            <?php settings_fields( 'wppb_bbpress_settings' ); ?>

            <h1></h1>
            <!-- WordPress Notices are added after the h1 tag -->

            <div class="cozmoslabs-page-header">
                <div class="cozmoslabs-section-title">

                    <h2 class="cozmoslabs-page-title">
                        <?php esc_html_e( 'bbPress Integration', 'profile-builder' ); ?>
                        <a href="https://www.cozmoslabs.com/docs/profile-builder/add-ons/bbpress/?utm_source=wpbackend&utm_medium=pb-documentation&utm_campaign=PBDocs" target="_blank" data-code="f223" class="wppb-docs-link dashicons dashicons-editor-help"></a>
                    </h2>

                </div>
            </div>

            <div class="cozmoslabs-form-subsection-wrapper">
                <h4 class="cozmoslabs-subsection-title"><?php esc_html_e( 'bbPress Profile and Forms', 'profile-builder' ); ?></h4>

                <?php // Check Profile Builder version, display User Listing only for Pro
                $versions = array( 'Profile Builder Pro', 'Profile Builder Agency', 'Profile Builder Unlimited', 'Profile Builder Dev' );

                if ( defined('PROFILE_BUILDER') && in_array( PROFILE_BUILDER, $versions ) ) { ?>

                    <div class="cozmoslabs-form-field-wrapper">
                        <label class="cozmoslabs-form-field-label" for="wppb-bbpress-userlisting"><?php esc_html_e('Single User-listing', 'profile-builder'); ?></label>

                        <select name="wppb_bbpress_settings[UserListing]" class="wppb-select" id="wppb-bbpress-userlisting">
                            <option value=""> <?php esc_html_e( 'None', 'profile-builder' ); ?></option>
                            <?php
                            $args = array(
                                'post_type' => 'wppb-ul-cpt',
                                'post_status' => 'publish',
                                'numberposts' => -1,
                                'orderby' => 'date',
                                'order' => 'ASC'
                            );
                            $user_listings = get_posts( apply_filters( 'wppb_bbpress_user_listings_args', $args) );

                            foreach ( $user_listings as $key => $value ){
                                echo '<option value="'. esc_attr( $value->post_title ).'"';
                                if ( isset($wppb_bbpress_settings['UserListing']) && ( $wppb_bbpress_settings['UserListing'] == $value->post_title ) )
                                    echo ' selected';

                                echo '>' . esc_html( $value->post_title ) . '</option>';
                            }
                            ?>

                        </select>

                        <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select which Single User-listing managed via Profile Builder should replace the default bbPress user profile.', 'profile-builder' ); ?></p>
                    </div>

                <?php } ?>

                <div class="cozmoslabs-form-field-wrapper">
                    <label class="cozmoslabs-form-field-label" for="wppb-bbpress-edit-profile"><?php esc_html_e('Edit Profile Form', 'profile-builder'); ?></label>

                    <select name="wppb_bbpress_settings[EditProfileForm]" class="wppb-select" id="wppb-bbpress-edit-profile">
                        <option value=""> <?php esc_html_e( 'None', 'profile-builder' ); ?></option>
                        <option value="wppb-default-edit-profile" <?php if ( $wppb_bbpress_settings['EditProfileForm'] == 'wppb-default-edit-profile' ) echo 'selected'; ?>><?php esc_html_e( 'Default Edit Profile', 'profile-builder' ); ?></option>
                        <?php
                        $args = array(
                            'post_type' => 'wppb-epf-cpt',
                            'post_status' => 'publish',
                            'numberposts' => -1,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        );
                        $edit_profile_forms = get_posts( apply_filters( 'wppb_bbpress_edit_profile_forms_args', $args) );

                        foreach ( $edit_profile_forms as $key => $value ){
                            echo '<option value="'. esc_attr( $value->post_title ).'"';
                            if ( $wppb_bbpress_settings['EditProfileForm'] == $value->post_title )
                                echo ' selected';

                            echo '>' . esc_html( $value->post_title ) . '</option>';
                        }
                        ?>

                    </select>

                    <p class="cozmoslabs-description cozmoslabs-description-space-left"><?php esc_html_e( 'Select Profile Builder Edit Profile form to replace the bbPress Profile Edit tab.', 'profile-builder' ); ?></p>
                </div>

                <div class="cozmoslabs-form-field-wrapper cozmoslabs-toggle-switch">
                    <label class="cozmoslabs-form-field-label" for="wppb-bbpress-login"><?php esc_html_e('Login Form', 'profile-builder'); ?></label>

                    <div class="cozmoslabs-toggle-container">
                        <input type="checkbox" name="wppb_bbpress_settings[Login]" id="wppb-bbpress-login" value="yes" <?php echo (!empty($wppb_bbpress_settings['Login']) && $wppb_bbpress_settings['Login'] === 'yes') ? 'checked' : ''; ?> >
                        <label class="cozmoslabs-toggle-track" for="wppb-bbpress-login"></label>
                    </div>

                    <div class="cozmoslabs-toggle-description">
                        <p class="cozmoslabs-description"><?php esc_html_e( 'Replace the default bbPress Login form with the Profile Builder Login form.', 'profile-builder' ); ?></p>
                    </div>
                </div>

                <?php do_action( 'wppb_extra_bbpress_settings', $wppb_bbpress_settings ); ?>
            </div>

            <p class="submit"><input type="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes' ); //phpcs:ignore ?>" /></p>
        </form>
    </div>

<?php
}

/*
 * Function that sanitizes the bbPress settings
 *
 * @param array $wppb_bbpress_settings
 *
 * @since v.1.0.0
 */
function wppb_in_bbpress_settings_sanitize( $wppb_bbpress_settings ) {

    $wppb_bbpress_settings = apply_filters( 'wppb_bbpress_settings_sanitize_extra', $wppb_bbpress_settings );

    return $wppb_bbpress_settings;
}


/*
 * Function that pushes settings errors to the user
 *
 * @since v.1.0.0
 */
function wppb_in_bbpress_settings_admin_notices() {
    settings_errors( 'wppb_bbpress_settings' );
}
add_action( 'admin_notices', 'wppb_in_bbpress_settings_admin_notices' );


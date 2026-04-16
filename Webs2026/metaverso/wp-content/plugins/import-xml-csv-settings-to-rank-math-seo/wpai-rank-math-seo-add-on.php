<?php
/*
Plugin Name: WP All Import - Rank Math SEO Add-On
Description: An add-on to facilitate importing to Rank Math using WP All Import.
Version: 1.1
Author: WP All Import
*/

/**
 * Class Rank_Math_Seo_Add_On.
 */
final class Rank_Math_Seo_Add_On {

    /**
     * @var string
     */
    protected static $version = '1.1';

    /**
     * Singletone instance.
     * @var Rank_Math_Seo_Add_On
     */
    protected static $instance;

    /**
     * Add On instance.
     * @var RapidAddon
     */
    protected $add_on;

    /**
     * Return singletone instance.
     * @return Rank_Math_Seo_Add_On
     */
    static public function get_instance() {
        if ( self::$instance == NULL ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Rank_Math_Seo_Add_On constructor.
     */
    protected function __construct() {

        add_action( 'init', [ $this, 'init' ] );

    }

    /**
     *  Check add-on conditions.
     */
    public function init() {

        // only run in admin, wp_cli, Scheduling, or cron
        if( is_admin() || php_sapi_name() === 'cli' || isset($_GET['import_key'])) {

            $this->constants();
            $this->includes();

            // Make sure the add-on should run for this import type
            $helpers = new WPAI_RankMath_SEO_Helpers();
            if(strpos($helpers->get_taxonomy_type(), 'pa_') !== false || in_array($helpers->get_post_type(), ['shop_order', 'comments', 'reviews'])){
                // Don't run for attributes or WooCo Orders
                return null;
            }

            add_action( 'admin_enqueue_scripts', [ $this, 'rank_math_seo_admin_scripts' ] );

            $this->add_on = new Soflyy\WpAllImportRapidAddon\RapidAddon( 'Rank Math SEO Add-On', 'rank_math_seo_addon' );

            $fields = new WPAI_RankMath_SEO_Field_Factory( $this->add_on);

            $this->filters();

            $fields->generate();



            $this->add_on->set_import_function( [ $this, 'import' ] );

            $this->add_on->run(
                array(
                    'plugins' => array('seo-by-rank-math/rank-math.php')
                )
            );

            $notice_message = 'The Rank Math SEO Add-On requires WP All Import <a href="http://www.wpallimport.com/order-now/?utm_source=free-plugin&utm_medium=dot-org&utm_campaign=rankmathseo" target="_blank">Pro</a> or <a href="https://wordpress.org/plugins/wp-all-import/">Free</a>, and the <a href="https://wordpress.org/plugins/seo-by-rank-math/" target="_blank">Rank Math SEO Plugin</a>.';

            $this->add_on->admin_notice( $notice_message, array( 'plugins' => array( 'seo-by-rank-math/rank-math.php' ) ) );
        }

    }

    /**
     * Import function.
     *
     * @param $post_id
     * @param $data
     * @param $import_options
     * @param $article
     */
    public function import( $post_id, $data, $import_options, $article ) {

        $importer = new WPAI_RankMath_SEO_Importer( $this->add_on );
        $importer->import($post_id, $data, $import_options, $article);

    }

    /**
     * @param $hook
     */
    public function rank_math_seo_admin_scripts($hook ) {

        if( isset($_GET['page']) && in_array($_GET['page'], ['pmxi-admin-manage', 'pmxi-admin-import'])) {
            wp_enqueue_script('rank_math_seo_add_on_script', plugin_dir_url(__FILE__) . 'static/js/admin.js', array('jquery'), self::$version);

        }

    }

    private function includes(){
        include WPAI_PLUGIN_DIR_PATH . "rapid-addon.php";
        include_once WPAI_PLUGIN_DIR_PATH . 'classes/class-field-factory.php';
        include_once WPAI_PLUGIN_DIR_PATH . 'classes/class-importer.php';
        include_once WPAI_PLUGIN_DIR_PATH . 'classes/class-helpers.php';
	    include_once WPAI_PLUGIN_DIR_PATH . 'classes/class-schema.php';
    }

    public function constants() {
        if ( ! defined( 'WPAI_PLUGIN_DIR_PATH' ) ) {
            // Dir path
            define( 'WPAI_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        }

        if ( ! defined( 'WPAI_ROOT_DIR' ) ) {
            // Root directory for the plugin.
            define( 'WPAI_ROOT_DIR', str_replace( '\\', '/', dirname( __FILE__ ) ) );
        }

        if ( ! defined( 'WPAI_PLUGIN_PATH' ) ) {
            // Path to the main plugin file.
            define( 'WPAI_PLUGIN_PATH', WPAI_ROOT_DIR . '/' . basename( __FILE__ ) );
        }

    }

    private function filters(){


// Enable our custom add-on's section.
        function show_addon_section_users_customers( $sections, $post_type ) {

            // Enable add-on section for Users.
            if ( 'import_users' == $post_type )
                $sections[] = 'featured';

            // Enable add-on section for Customers.
            if ( 'shop_customer' == $post_type )
                $sections[] = 'featured';

            return $sections;
        }

        add_filter( 'pmxi_visible_template_sections', 'show_addon_section_users_customers', 11, 2 );

// Disable the Images section.
        function hide_images_users_customers( $is_enabled, $post_type ){

            // Disable Images section for Users, return true to enable.
            if ( 'import_users' == $post_type )
                $is_enabled = false;

            // Disable Images section for Customers, return true to enable.
            if ( 'shop_customer' == $post_type )
                $is_enabled = false;

            return $is_enabled;

        }

        add_filter( 'wp_all_import_is_images_section_enabled', 'hide_images_users_customers', 10, 2 );


    }


}

function rmseoao_pmxi_reimport_options_after_taxonomies($post_type, $post){

    if ( in_array($post_type, ['import_users', 'shop_customer', 'taxonomies']) ) {
        return FALSE;
    }

    ?>
                <div class="input">
                    <input type="hidden" name="is_update_rank_math_schema" value="0" />
                    <input type="checkbox" id="is_update_rank_math_schema_<?php echo esc_attr( $post_type ); ?>" name="is_update_rank_math_schema" value="1" <?php if( isset( $post['is_update_rank_math_schema'] ) ){ echo $post['is_update_rank_math_schema'] ? 'checked="checked"': ''; } ?> class="switcher"/>
                    <label for="is_update_rank_math_schema_<?php echo esc_attr( $post_type ); ?>"><?php _e('Rank Math Schema', 'Rank_Math_SEO_Add_On') ?></label>
                </div>
            <?php

}

add_action('pmxi_reimport_options_after_taxonomies', 'rmseoao_pmxi_reimport_options_after_taxonomies', 11, 2);

function rmseoao_pmxi_options_options( $defaultOptions, $isWizard ){
    if( !isset( $defaultOptions['is_update_rank_math_schema'] ) ) {
        $defaultOptions['is_update_rank_math_schema'] = 1;
    }
    return $defaultOptions;
}

add_filter('pmxi_options_options', 'rmseoao_pmxi_options_options', 10, 2);

function rmseoao_wpimport_pmxi_save_options( $post ) {

    if ( 'options' === PMXI_Plugin::getInstance()->getAdminCurrentScreen()->action ) {
        if( !isset( $post['is_update_rank_math_schema'] ) ){
            $post['is_update_rank_math_schema'] = isset( $_POST['is_update_rank_math_schema'] ) ? sanitize_text_field( wp_unslash( $_POST['is_update_rank_math_schema'] ) ) : '';
        }

    }
    return $post;
}
add_filter( 'pmxi_save_options', 'rmseoao_wpimport_pmxi_save_options' );

Rank_Math_Seo_Add_On::get_instance();

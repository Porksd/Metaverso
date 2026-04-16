<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once ATLT_PRO_PATH . "/includes/Register/LocoAutomaticTranslateAddonProBase.php";

if(!class_exists("LocoAutomaticTranslateAddonPro")) {
	class LocoAutomaticTranslateAddonPro {
        public $plugin_file=__FILE__;
        public $responseObj;
        public $licenseMessage;
        public $showMessage=false;
        public $slug="loco-atlt-register";
        function __construct() {
    	    add_action( 'admin_print_styles', [ $this, 'SetAdminStyle' ] );
    	    $licenseKey=get_option("LocoAutomaticTranslateAddonPro_lic_Key","");
    	    $liceEmail=get_option( "LocoAutomaticTranslateAddonPro_lic_email","");
            LocoAutomaticTranslateAddonProBase::addOnDelete(function(){
               delete_option("LocoAutomaticTranslateAddonPro_lic_Key");
            });
    	    if(LocoAutomaticTranslateAddonProBase::CheckWPPlugin($licenseKey,$liceEmail,$this->licenseMessage,$this->responseObj,__FILE__)){
    		    add_action( 'admin_menu', [$this,'ActiveAdminMenu'],101);
    		    add_action( 'admin_post_LocoAutomaticTranslateAddonPro_el_deactivate_license', [ $this, 'action_deactivate_license' ] );
    		    //$this->licenselMessage=$this->mess;
                add_action('wp_ajax_loco_install_pro', array($this, 'loco_install_pro'));           

    	    }else{
    	        if(!empty($licenseKey) && !empty($this->licenseMessage)){
    	           $this->showMessage=true;
                }
                
    		    update_option("LocoAutomaticTranslateAddonPro_lic_Key","") || add_option("LocoAutomaticTranslateAddonPro_lic_Key","");
    		    add_action( 'admin_post_LocoAutomaticTranslateAddonPro_el_activate_license', [ $this, 'action_activate_license' ] );
    		    add_action( 'admin_menu', [$this,'InactiveMenu'],101);
    	    }
        }
    	function SetAdminStyle() {
    		wp_register_style( "LocoAutomaticTranslateAddonProLic", plugins_url("style.css",$this->plugin_file),10);
    		wp_enqueue_style( "LocoAutomaticTranslateAddonProLic" );
        }
        function ActiveAdminMenu(){
                add_submenu_page( 'loco',
                'Loco Automatic Translate Addon Pro', 
                'Auto Translate Addon - Premium License',
                 'manage_options', 
                    $this->slug,
                 array($this, 'Activated'));

                 if( class_exists( 'LocoAutoTranslateAddonPro' ) ){
                     // no further execution required
                     return;
                 }
           
        }
        function InactiveMenu() {
            add_submenu_page( 'loco',
            'Loco Automatic Translate Addon Pro', 
            'Auto Translate Addon - Premium License',
             'activate_plugins', 
             $this->slug,
             array($this, 'LicenseForm'));
    	  /*  add_menu_page( "LocoAutomaticTranslateAddonPro", "Loco Automatic Translate Addon Pro", 'activate_plugins', $this->slug,  [$this,"LicenseForm"], " dashicons-star-filled " ); */

        }
        function action_activate_license(){
        		check_admin_referer( 'el-license' );
        		$licenseKey=!empty($_POST['el_license_key'])?$_POST['el_license_key']:"";
        		$licenseEmail=!empty($_POST['el_license_email'])?$_POST['el_license_email']:"";
        		update_option("LocoAutomaticTranslateAddonPro_lic_Key",$licenseKey) || add_option("LocoAutomaticTranslateAddonPro_lic_Key",$licenseKey);
        		update_option("LocoAutomaticTranslateAddonPro_lic_email",$licenseEmail) || add_option("LocoAutomaticTranslateAddonPro_lic_email",$licenseEmail);
        		wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
        	}
        function action_deactivate_license() {
    	    check_admin_referer( 'el-license' );
    	    if(LocoAutomaticTranslateAddonProBase::RemoveLicenseKey(__FILE__,$message)){
    		    update_option("LocoAutomaticTranslateAddonPro_lic_Key","") || add_option("LocoAutomaticTranslateAddonPro_lic_Key","");
    	    }
    	    wp_safe_redirect(admin_url( 'admin.php?page='.$this->slug));
        }
        function Activated(){
            ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="LocoAutomaticTranslateAddonPro_el_deactivate_license"/>
                <div class="el-license-container">
                    <h3 class="el-license-title"><i class="dashicons-before dashicons-translation"></i> <?php _e("Automatic Translate Addon For Loco Translate - Premium License Status",$this->slug);?> </h3>
                    <div class="el-license-content">
                        <div class="el-license-form">
                            <h3>Active License Status</h3>
                            <ul class="el-license-info">
                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("License Status",$this->slug);?></span>

                                    <?php if ( $this->responseObj->is_valid ) : ?>
                                        <span class="el-license-valid"><?php _e("Valid",$this->slug);?></span>
                                    <?php else : ?>
                                        <span class="el-license-valid"><?php _e("Invalid",$this->slug);?></span>
                                    <?php endif; ?>
                                </div>
                            </li>

                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("License Type",$this->slug);?></span>
                                    <?php echo $this->responseObj->license_title; ?>
                                </div>
                            </li>

                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("License Expiry Date",$this->slug);?></span>
                                    <?php echo $this->responseObj->expire_date; ?>
                                </div>
                            </li>

                            <li>
                                <div>
                                    <span class="el-license-info-title"><?php _e("Support Expiry Date",$this->slug);?></span>
                                    <?php echo $this->responseObj->support_end; ?>
                                </div>
                            </li>
                                <li>
                                    <div>
                                        <span class="el-license-info-title"><?php _e("Your License Key",$this->slug);?></span>
                                        <span class="el-license-key"><?php echo esc_attr( substr($this->responseObj->license_key,0,9)."XXXXXXXX-XXXXXXXX".substr($this->responseObj->license_key,-9) ); ?></span>
                                    </div>
                                </li>
                            </ul>
                            <div class="el-license-active-btn">
                                <?php wp_nonce_field( 'el-license' ); ?>
                                <?php submit_button('Deactivate License'); ?>
                            </div>
                        </div>
                        <div class="el-license-textbox">
                        <h3>Important Points</h3>
                        <ol>
                            <li>Please deactivate your license first before moving your website or changing domain.</li>
                            <li>Plugin does not auto-translate any string that contains HTML and special characters.</li>
                            <li>Currently DeepL Doc Translator provides limited number of free docs translations per day. You can purchase to <a href="https://www.deepl.com/pro?cta=homepage-free-trial#pricing" target="_blank">DeepL Pro</a> to increase this limit.</li>
                            <li>If you have any issue or query, please <a href="https://locoaddon.com/support/" target="_blank">contact support</a>.</li>
                        </ol>
                        <div class="el-pluginby">
                            Plugin by<br/>
                            <a href="https://coolplugins.net" target="_blank"><img src="<?php echo ATLT_PRO_URL.'/assets/images/coolplugins-logo.png' ?>"/></a>
                        </div>
                        </div>
                    </div>
                </div>
            </form>
    	<?php
        }

        function LicenseForm() {
    	    ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    	    <input type="hidden" name="action" value="LocoAutomaticTranslateAddonPro_el_activate_license"/>
    	    <div class="el-license-container">
    		    <h3 class="el-license-title"><i class="dashicons-before dashicons-translation"></i> <?php _e("Automatic Translate Addon For Loco Translate - Premium License",$this->slug);?></h3>
                <div class="el-license-content">
                    <div class="el-license-form">
                        <h3>Activate Premium License</h3>
                        <?php
                        if(!empty($this->showMessage) && !empty($this->licenseMessage)){
                            ?>
                            <div class="notice notice-error is-dismissible">
                                <p><?php echo _e($this->licenseMessage,$this->slug); ?></p>
                            </div>
                            <?php
                        }
                        ?>
                        <!--Enter License Key Here START-->
                        <div class="el-license-field">
                            <label for="el_license_key"><?php _e("Enter License code",$this->slug);?></label>
                            <input type="text" class="regular-text code" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
                        </div>
                        <div class="el-license-field">
                            <label for="el_license_key"><?php _e("Email Address",$this->slug);?></label>
                            <?php
                                $purchaseEmail   = get_option( "LocoAutomaticTranslateAddonPro_lic_email", get_bloginfo( 'admin_email' ));
                            ?>
                            <input type="text" class="regular-text code" name="el_license_email" size="50" value="<?php echo sanitize_email($purchaseEmail); ?>" placeholder="" required="required">
                            <div><small><?php _e("✅ I agree to share my purchase code and email for plugin verification and to receive future updates notifications!",$this->slug);?></small></div>
                        </div>
                        <div class="el-license-active-btn">
                            <?php wp_nonce_field( 'el-license' ); ?>
                            <?php submit_button('Activate'); ?>
                        </div>
                        <!--Enter License Key Here END-->
                    </div>
                    
                    <div class="el-license-textbox">
                        <div>
                        <strong style="color:#e00b0b;">*Important Points</strong>
                        <ol>
                        <li>Premium version supports <b>Google Translate</b> for better translations.</li>
                        <li>Automatic translate providers do not support HTML and special characters translations. So plugin will not automatic translate any string that contains HTML or special characters.</li>
                        <li>If any auto-translation provider stops any of its free translation service then plugin will not support that translation service provider.</li>
                        <li>DeepL Translate provides better translations than Google, Yandex or other machine translation providers. <a href="https://techcrunch.com/2017/08/29/deepl-schools-other-online-translators-with-clever-machine-learning/" target="_blank"><b>Read review by Techcrunch!</b></a></li>
                        <li>Currently DeepL Doc Translator provides limited number of free docs translations per day. You can purchase to <a href="https://www.deepl.com/pro?cta=homepage-free-trial#pricing" target="_blank">DeepL Pro</a> to increase this limit.</li>
                        <li>If you have any issue or query, please <a href="https://locoaddon.com/support/" target="_blank">contact support</a>.</li>
                        </ol>
                        </div>
                        <div class="el-pluginby">
                            Plugin by<br/>
                            <a href="https://coolplugins.net" target="_blank"><img src="<?php echo ATLT_PRO_URL.'/assets/images/coolplugins-logo.png' ?>"/></a>
                        </div>
                    </div>
                </div>
    	    </div>
        </form>
    	    <?php
        }
    }

    new LocoAutomaticTranslateAddonPro();
}
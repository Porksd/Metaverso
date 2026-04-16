<?php

// WPPU Admin Panel Settings

function wppu_main_options_default_settings() {
	$defaultImg = plugin_dir_url( __FILE__ ) . 'images/Preloader_3.gif';
	$saved = (array) get_option( 'wppu_display' );
	$defaults = array(
	'sample_radio_buttons'      => 'image_radio_options',
	'animation_effects'    		=> 'fadeOut',
	'preloader_load_type'    	=> 'window',
	'custom_load_time'	    	=> '1500',
	'delay_timing'    	 		=> '500',
	'loading_out_timing'   		=> '600',
	'disable_in_mobile'   		=> '',
	'disable_in_tablet'   		=> '',
	'get_all_pages'     		=> '',
	'get_all_pages_ID'     		=> '',
	'home_only_ID'     			=> '',
	'404_error_page'     		=> '',
	'wppu_search_page'     		=> '',
	'get_all_post_type'     	=> '',
	'get_all_post_type_list' 	=> '',
	'get_all_archive_page'     	=> '',
	'get_all_single_post'     	=> '',
	'awesome_loading_selected'  => 'la-ball-atom',
	'awesome_selectedcounter'   => '4',
	'awesome_loading_text'     	=> 'Loading...',
	'awesome_background'     	=> '#FFFFFF',
	'awesome_mainColor'     	=> '#000000',
	'awesome_textcolor'     	=> '#000000',
	'awesome_font_size'			=>	'13',
	'package_selector'			=>	$defaultImg,
	'background_size_selector' 	=> 'auto',
	'background_size'			=>	'32',
	'background_color'			=>	'#FFFFFF',
	'custom_image'				=>	'',
	'effect_options'			=>	'facebook',
	'input_example'				=>	'Please Wait...',
	'preview_example'			=>	'',
	'preview_backgroundColor'	=>	'#FFFFFF',
	'preview_mainColor'			=>	'#000000',
	'special_text' 				=>	'wppu',
	'special_background' 		=>	'#009999',
	'special_color'				=>	'#FFFFFF',
	'special_font_size'			=>	'8',
	'pace_effects' 				=>	'center-atom',
	'pace_color' 				=>	'#000000',
	'pace_background' 			=>	'#FFFFFF',
	'custom_wppu_markup'    	=> '',
	'custom_wppu_css'     		=> '',
	'custom_wppu_js'     		=> '',
	'custom_wppu_js_pos'     	=> '',
	'custom_wppu_extra_css'     => '',
	'exclude_js_from_plugins'   => '',

	'trendy_loading_style'     	=> 'trendy_fazer_man',
	'trendy_loading_text'     	=> 'Loading...',
	'trendy_background'     	=> '#4A5256',
	'wppu_trendy_markup'     	=> '',
	'trendy_textcolor'     		=> '#000000',
	'trendy_font_size'			=>	'13',

	'3d_loader_text' 			=>	'wppu',
	'3d_loader_background' 		=>	'#009999',
	'3d_loader_background_con' 	=>	'#FFFFFF',
	'3d_loader_color'			=>	'#FFFFFF',
	'3d_loader_font_size'		=>	'30',
	'3d_loader_animate_style'	=>	'wppu-3d-loader--flipDelay wppu-3d-loader--3d',

	'fill_progress_bar_pos' 	=> 'top',
	'fill_progress_bar_bg'      => '#ed4e6e',
	'fill_progress_bar_height'  => '3',
	'fill_counter_text_color'	=> '#000',
	'fill_counter_text_size'	=> '20',
	'fill_counter_enable'		=> '1',
	'fill_counter_position'		=> 'none',

	'fill_loader_type'			=> 'text',
	'fill_loader_background'	=> '#FFF',
	'fill_loader_text'			=> 'WPPU',
	'fill_loader_thumb'			=> 'http://www.oshwa.org/wp-content/uploads/2014/03/oshw-logo-800-px.png',
	'fill_loader_thumb_size'	=> '100',
	'fill_loader_text_size'		=> '20',
	'fill_loader_text_color'	=> '',

	'object_loader_thumb'		=> 'https://upload.wikimedia.org/wikipedia/en/thumb/2/22/Heckert_GNU_white.svg/1200px-Heckert_GNU_white.svg.png',
	'object_loader_thumb_size'	=> '250',
	'object_main_color'			=> '#FFFFFF',
	'object_wrapper_bg'			=> '#009999',

	'before_server_render'		=> '',
	'exclude_parents'		=> '',

);

	$defaults = apply_filters( 'wppu_main_options_default_settings', $defaults );

	$options = wp_parse_args( $saved, $defaults );
	$options = array_intersect_key( $options, $defaults );

	return $options;
}

if ( is_admin() ) : // Load only if we are viewing an admin page

function wppu_plugin_page_init() {
	// Register settings and call sanitation functions
	register_setting( 'wppu_display_options_add_page', 'wppu_display', 'wppu_all_field_validate' );
}
add_action( 'admin_init', 'wppu_plugin_page_init' );


// Options for radio buttons field
function wppu_radio_general_save_settings() {
	$sample_radio_buttons = array(
		'image_radio_options' => array(
			'value' => 'image_radio_options',
			'label' => __( 'Active Image Options', 'wppu' )
		),
		'css_radio_options' => array(
			'value' => 'css_radio_options',
			'label' => __( 'Active Css Options', 'wppu' )
		),
		'fill_radio_options' => array(
			'value' => 'fill_radio_options',
			'label' => __( 'Active Css Options', 'wppu' )
		),
		'awesome_radio_options' => array(
			'value' => 'awesome_radio_options',
			'label' => __( 'Active Awesome CSS3 Options', 'wppu' )
		),
		'trendy_radio_options' => array(
			'value' => 'trendy_radio_options',
			'label' => __( 'Active Trendy Options', 'wppu' )
		),
		'special_radio_options' => array(
			'value' => 'special_radio_options',
			'label' => __( 'Active Special Options', 'wppu' )
		),
		'three_radio_options' => array(
			'value' => 'three_radio_options',
			'label' => __( 'Active 3d Options', 'wppu' )
		),
		'pace_radio_options' => array(
			'value' => 'pace_radio_options',
			'label' => __( 'Active Pace Options', 'wppu' )
		),
		'object_radio_options' => array(
			'value' => 'object_radio_options',
			'label' => __( 'Active Object Options', 'wppu' )
		),
		'custom_your_options' => array(
			'value' => 'custom_your_options',
			'label' => __( 'Active Your Custom Preloader', 'wppu' )
		)
	);

	return apply_filters( 'wppu_radio_general_save_settings', $sample_radio_buttons );
}

function get_all_pages() {
	$home_ID = get_option('show_on_front');
	if ( $home_ID == 'page' ) {
		$home_ID = get_option('page_on_front');
		$args = array(
			'exclude' => $home_ID,
			'post_type' => 'page',
			'post_status' => 'publish'
		);
	} else {
		$args = array(
			'post_type' => 'page',
			'post_status' => 'publish'
		);
	}
	foreach( get_pages($args) as $page ) :
		$get_all_pages[$page->ID] = array(
			'value' => $page->ID,
			'label' => $page->post_name
		);
	endforeach;

	return $get_all_pages;
}

function get_all_post_type() {
	$args = array(
	   'public'   => true,
	   '_builtin' => false
	);

	$output = 'names'; // names or objects, note names is the default
	$operator = 'and'; // 'and' or 'or'

	$post_types = get_post_types( $args, $output, $operator ); 

	foreach( $post_types  as $post_type ) :
		$get_all_post_type[] = array(
			'value' => $post_type,
			'label' => $post_type
		);
	endforeach;
		$get_all_post_type[] = array(
			'value' => 'post',
			'label' => 'Default post'
		);

	return $get_all_post_type;
}


function wppu_select_animation_effect() {
	$animation_effects = array(
		'fadeOut' => array(
			'value' =>  'fadeOut',
			'label' => __( 'fadeOut', 'wppu' )
		),
		'slideUp' => array(
			'value' =>  'slideUp',
			'label' => __( 'slideUp', 'wppu' )
		),
		'slideDown' => array(
			'value' =>  'slideDown',
			'label' => __( 'slideDown', 'wppu' )
		),
		'slideLeft' => array(
			'value' =>  'slideLeft',
			'label' => __( 'slideLeft', 'wppu' )
		),
		'slideRight' => array(
			'value' => 'slideRight',
			'label' => __( 'slideRight', 'wppu' )
		),
		'blind' => array(
			'value' => 'blind',
			'label' => __( 'blind', 'wppu' )
		),
		'bounce' => array(
			'value' => 'bounce',
			'label' => __( 'bounce', 'wppu' )
		),
		'clip' => array(
			'value' => 'clip',
			'label' => __( 'clip', 'wppu' )
		),
		'drop' => array(
			'value' => 'drop',
			'label' => __( 'drop', 'wppu' )
		),
		'fold' => array(
			'value' => 'fold',
			'label' => __( 'fold', 'wppu' )
		),
		'highlight' => array(
			'value' => 'highlight',
			'label' => __( 'highlight', 'wppu' )
		),
		'pulsate' => array(
			'value' => 'pulsate',
			'label' => __( 'pulsate', 'wppu' )
		),
		'scale' => array(
			'value' => 'scale',
			'label' => __( 'scale', 'wppu' )
		),
		'shake' => array(
			'value' => 'shake',
			'label' => __( 'shake', 'wppu' )
		)
	);

	return apply_filters( 'wppu_select_animation_effect', $animation_effects );
}

function wppu_select_load_types(){
	$types = array(
		'window' => array(
			'value' =>  'window',
			'label' => __( 'Window Load (Default)', 'wppu' )
		),
		'custom' => array(
			'value' => 'custom',
			'label' => __( 'Custom ( Custom time period for preloader )', 'wppu' )
		)
	);
	return apply_filters('wppu_select_load_types', $types);
}

function wppu_pace_effects_callback_settings() {
	$effect_options = array(
		'minimal' => array(
			'value' =>  'minimal',
			'label' => __( 'Minimal', 'wppu' )
		),
		'flash' => array(
			'value' =>  'flash',
			'label' => __( 'Flash', 'wppu' )
		),
		'barber-shop' => array(
			'value' => 'barber-shop',
			'label' => __( 'Barber Shop', 'wppu' )
		),
		'fill-left' => array(
			'value' => 'fill-left',
			'label' => __( 'Fill Left', 'wppu' )
		),
		'big-counter' => array(
			'value' => 'big-counter',
			'label' => __( 'Big Counter', 'wppu' )
		),
		'corner-indicator' => array(
			'value' => 'corner-indicator',
			'label' => __( 'Corner Indicator', 'wppu' )
		),
		'bounce' => array(
			'value' => 'bounce',
			'label' => __( 'Bounce', 'wppu' )
		),
		'loading-bar' => array(
			'value' => 'loading-bar',
			'label' => __( 'Loading Bar', 'wppu' )
		),
		'center-circle' => array(
			'value' => 'center-circle',
			'label' => __( 'Center Circle', 'wppu' )
		),
		'center-atom' => array(
			'value' => 'center-atom',
			'label' => __( 'Center Atom', 'wppu' )
		),
		'center-radar' => array(
			'value' => 'center-radar',
			'label' => __( 'Center Radar', 'wppu' )
		),
		'center-simple' => array(
			'value' => 'center-simple',
			'label' => __( 'Center Simple', 'wppu' )
		)
	);

	return apply_filters( 'wppu_pace_effects_callback_settings', $effect_options );
}


function wppu_custom_js_pos() {
	$wppu_custom_js_pos = array(
		'after' => array(
			'value' =>  'after',
			'label' => __( 'After HTML Markup', 'wppu' )
		),
		'before' => array(
			'value' =>  'before',
			'label' => __( 'Before HTML Markup', 'wppu' )
		)
	);

	return apply_filters( 'wppu_custom_js_pos', $wppu_custom_js_pos );
}

function wppu_image_bgsize_sec_callback_settings() {
	$sample_radio_buttons = array(
		'auto' => array(
			'value' => 'auto',
			'label' => __( 'Auto', 'wppu' )
		),
		'custom' => array(
			'value' => 'custom',
			'label' => __( 'Custom', 'wppu' )
		)
	);

	return apply_filters( 'wppu_image_bgsize_sec_callback_settings', $sample_radio_buttons );
}

function wppu_yes_no() {
	$sample_radio_buttons = array(
		'yes' => array(
			'value' => 'yes',
			'label' => __( 'Yes', 'wppu' )
		),
		'no' => array(
			'value' => 'no',
			'label' => __( 'No', 'wppu' )
		)
	);

	return apply_filters( 'wppu_yes_no', $sample_radio_buttons );
}
function wppu_enable_disable() {
	$enable_disable = array(
		'enable' => array(
			'value' => 'enable',
			'label' => __( 'Enable', 'wppu' )
		),
		'disable' => array(
			'value' => 'disable',
			'label' => __( 'Disable', 'wppu' )
		)
	);

	return apply_filters( 'wppu_enable_disable', $enable_disable );
}


function wppu_fill_loader_type() {
	$fill_loader_type = array(
		'text' => array(
			'value' => 'text',
			'label' => __( 'Text', 'wppu' )
		),
		'image' => array(
			'value' => 'image',
			'label' => __( 'Image', 'wppu' )
		)
	);

	return apply_filters( 'wppu_fill_loader_type', $fill_loader_type );
}

function wppu_fill_progress_bar_position() {
	$fill_progress_bar_pos = array(
		'none' => array(
			'value' => 'none',
			'label' => __( 'None', 'wppu' )
		),
		'top' => array(
			'value' => 'top',
			'label' => __( 'Top', 'wppu' )
		),
		'bottom' => array(
			'value' => 'bottom',
			'label' => __( 'Bottom', 'wppu' )
		)
	);

	return apply_filters( 'wppu_fill_progress_bar_position', $fill_progress_bar_pos );
}


function wppu_select_element_callback_settings() {
	$effect_options = array(
		'bounce' => array(
			'value' =>  'bounce',
			'label' => __( 'bounce', 'wppu' )
		),
		'rotateplane' => array(
			'value' =>  'rotateplane',
			'label' => __( 'rotateplane', 'wppu' )
		),
		'stretch' => array(
			'value' => 'stretch',
			'label' => __( 'stretch', 'wppu' )
		),
		'orbit' => array(
			'value' => 'orbit',
			'label' => __( 'orbit', 'wppu' )
		),
		'roundBounce' => array(
			'value' => 'roundBounce',
			'label' => __( 'roundBounce', 'wppu' )
		),
		'win8' => array(
			'value' => 'win8',
			'label' => __( 'win8', 'wppu' )
		),
		'win8_linear' => array(
			'value' => 'win8_linear',
			'label' => __( 'win8 linear', 'wppu' )
		),
		'ios' => array(
			'value' => 'ios',
			'label' => __( 'ios', 'wppu' )
		),
		'facebook' => array(
			'value' => 'facebook',
			'label' => __( 'facebook', 'wppu' )
		),
		'rotation' => array(
			'value' => 'rotation',
			'label' => __( 'rotation', 'wppu' )
		),
		'timer' => array(
			'value' => 'timer',
			'label' => __( 'timer', 'wppu' )
		),
		'pulse' => array(
			'value' => 'pulse',
			'label' => __( 'pulse', 'wppu' )
		),
		'progressBar' => array(
			'value' => 'progressBar',
			'label' => __( 'progressBar', 'wppu' )
		),
		'bouncePulse' => array(
			'value' => 'bouncePulse',
			'label' => __( 'bouncePulse', 'wppu' )
		)
	);

	return apply_filters( 'wppu_select_element_callback_settings', $effect_options );
}


function wppu_threed_loader_animate_style() {
	$effect_options = array(
		'flip3d' => array(
			'value' =>  'wppu-3d-loader--flipDelay wppu-3d-loader--3d',
			'label' => __( 'Flip 3D', 'wppu' )
		),
		'flipdown3d' => array(
			'value' =>  'wppu-3d-loader--flipDelayDown wppu-3d-loader--3d',
			'label' => __( 'FlipDown 3D', 'wppu' )
		),
		'slowflip' => array(
			'value' =>  'wppu-3d-loader--slowFlip',
			'label' => __( 'slowFlip 2D', 'wppu' )
		),
		'slowflip3d' => array(
			'value' =>  'wppu-3d-loader--slowFlip wppu-3d-loader--3d',
			'label' => __( 'slowFlip 3D', 'wppu' )
		),
		'fliphoz' => array(
			'value' =>  'wppu-3d-loader--flipHoz wppu-3d-loader--3d',
			'label' => __( 'FlipHoz 3D', 'wppu' )
		),
		'fade' => array(
			'value' =>  'wppu-3d-loader--fade',
			'label' => __( 'Fade 2D', 'wppu' )
		),
		'slidedown' => array(
			'value' =>  'wppu-3d-loader--slideDown',
			'label' => __( 'slideDown 2D', 'wppu' )
		),
		'slideup' => array(
			'slideup' =>  'wppu-3d-loader--slideUp',
			'label' => __( 'slideUp 2D', 'wppu' )
		),
		'slideboth' => array(
			'value' =>  'wppu-3d-loader--slideBoth',
			'label' => __( 'slideBoth 2D', 'wppu' )
		),
	);

	return apply_filters( 'wppu_threed_loader_animate_style', $effect_options );
}


function wppu_display_options_add_page() {
	// Add theme options page to the addmin menu
	add_menu_page( 
		'WP Preloader Unlimited', 
		'WPPU', 
		'administrator', 
		'wppu_options', 
		'wppu_display',
		'dashicons-universal-access-alt'
	);
}

add_action( 'admin_menu', 'wppu_display_options_add_page' );

// Function to generate options page
function wppu_display() {
	global $wppu_display;

	if ( ! isset( $_REQUEST['updated'] ) )
		$_REQUEST['updated'] = false; // This checks whether the form has just been submitted. ?>

	<div class="wrap">

	<?php if ( false !== $_REQUEST['updated'] ) : ?>
	<div class="updated fade"><p><strong><?php _e( 'Options saved', 'wppu' ); ?></strong></p></div>
	<?php endif; // If the form has just been submitted, this shows the notification
		$options = wppu_main_options_default_settings();
		$bgC = $options['awesome_mainColor'];

		$trendy_background 		= $options['trendy_background'];
		$trendy_textcolor  		= $options['trendy_textcolor'];
		$trendy_font_size 		= $options['trendy_font_size'];
		$trendy_loading_text 	= $options['trendy_loading_text'];
		$trendy_loading_style 	= $options['trendy_loading_style'];
		$wppu_trendy_markup 	= $options['wppu_trendy_markup'];

		if($options['awesome_loading_selected'] == 'la-ball-atom') {
			echo '<style type="text/css">
					.item-generate.la-ball-atom div:nth-child(1) {
						background-color: '.$bgC.';
					}
				  </style>';
		}

		echo '<style type="text/css">
				.trendy-preloader-main-con {
					background-color: ' . $trendy_background . ';
				}
				.trendy-preloader-main-con  .trendy_loading_text {
				  color: ' . $trendy_textcolor . ';
				  font-size: ' . $trendy_font_size . 'px;
				  display: inline-block;
				}
			  </style>';

		
	 ?>


	<style type="text/css">

	</style>

	<form method="post" action="options.php" enctype="multipart/form-data" id="wppu_Options_Form">
	<div class="form_loading"></div>
	<?php $options = wppu_main_options_default_settings(); ?>
	
	<?php settings_fields( 'wppu_display_options_add_page' );
	/* This function outputs some hidden fields required by the form,
	including a nonce, a unique number used to ensure the form has been submitted from the admin page
	and not somewhere else, very important for security */ ?>

	<div class="pixiefy-form-container">
		<div id="pixiefy-main-tabs">
			<ul id="pixiefy-tabUL">
				<div class="logo"></div>
				<li><a class="pixiefy-tab-li-1" href="#pixiefy-tabs-1"><span class="dashicons dashicons-admin-generic"></span><?php _e( 'General Settings', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-2" href="#pixiefy-tabs-2"><span class="dashicons dashicons-format-image"></span><?php _e( 'Image Options', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-10" href="#pixiefy-tabs-10"><span class="dashicons dashicons-thumbs-up"></span><?php _e( 'Fill Animation', 'wppu' ); ?></a> <span class="trendy_new_sp"><?php _e( 'New', 'blogie' ); ?></span> </li>
				<li><a class="pixiefy-tab-li-8" href="#pixiefy-tabs-8"><span class="dashicons dashicons-megaphone"></span><?php _e( 'Trendy', 'wppu' ); ?></a> <span class="trendy_new_sp"><?php _e( 'New', 'blogie' ); ?></span> </li>
				<li><a class="pixiefy-tab-li-9" href="#pixiefy-tabs-9"><span class="dashicons dashicons-slides"></span><?php _e( '3D Letter', 'wppu' ); ?></a> <span class="trendy_new_sp"><?php _e( 'New', 'blogie' ); ?></span> </li>
				<li><a class="pixiefy-tab-li-3" href="#pixiefy-tabs-3"><span class="dashicons dashicons-clock"></span><?php _e( 'CSS Options', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-7" href="#pixiefy-tabs-7"><span class="dashicons dashicons-lightbulb"></span><?php _e( 'Awesome CSS3', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-4" href="#pixiefy-tabs-4"><span class="dashicons dashicons-editor-paste-text"></span><?php _e( 'Special Options', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-12" href="#pixiefy-tabs-12"><span class="dashicons dashicons-image-filter"></span><?php _e( 'Object Options', 'wppu' ); ?></a> <span class="trendy_new_sp"><?php _e( 'New', 'blogie' ); ?></span></li>
				<li><a class="pixiefy-tab-li-5" href="#pixiefy-tabs-5"><span class="dashicons dashicons-smiley"></span><?php _e( 'Pace Options', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-6" href="#pixiefy-tabs-6"><span class="dashicons dashicons-admin-appearance"></span><?php _e( 'Your Own Custom', 'wppu' ); ?></a></li>
				<li><a class="pixiefy-tab-li-11" href="#pixiefy-tabs-11"><span class="dashicons dashicons-admin-customizer"></span><?php _e( 'Extra CSS', 'wppu' ); ?></a></li>
			</ul>
			<div id="wppu-all-tab-container" class="tab-content-container">
				<div id="pixiefy-tabs-1">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('General Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Set Preloader Where you want', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="wppu-full-single">
								<div class="wppu-move wppu-alert-massage">
									<?php _e( 'Following settings will applies to control which pages you want to preloader show or not . If you don\'t set anything then it will show all the places. Every checkbox use to hide your preloader certaine places. If you check then preloader will be hide that checked place.', 'wppu' ); ?>
									<p></p>
								</div>
							 </div>
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Select and Disable Preloader - Please select/Checked where you want to hide preloader.', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
							    <input type="hidden" style="display:none" id="get_all_pages" name="wppu_display[get_all_pages]" value="<?php echo esc_attr( $options['get_all_pages'] ); ?>" />
							    <input type="hidden" style="display:none" id="get_all_pages_ID" name="wppu_display[get_all_pages_ID]" value="<?php echo esc_attr( $options['get_all_pages_ID'] ); ?>" />
							    <input type="hidden" style="display:none" id="get_all_post_type" name="wppu_display[get_all_post_type]" value="<?php echo esc_attr( $options['get_all_post_type'] ); ?>" />
							    <input type="hidden" style="display:none" id="get_all_post_type_list" name="wppu_display[get_all_post_type_list]" value="<?php echo esc_attr( $options['get_all_post_type_list'] ); ?>" />

									<div class="list_accordion" id="wppCaccordion">
									<h3><?php esc_html_e('Disable Home Page', 'wppu'); ?></h3>
									<div class="single-accordion">
										<div>
											<?php $options = wppu_main_options_default_settings();
											$html = '<input type="checkbox" id="home_only_ID" name="wppu_display[home_only_ID]" value="1"' . checked( 1, $options['home_only_ID'], false ) . '/>';
											$html .= '&nbsp;';
											$html .= '<label for="home_only_ID">'.esc_html__('Home/Front Page', 'wppu').'</label>';

											echo $html.'<br />'; ?>
										</div>
									</div>

									<h3><?php esc_html_e('Disable Specific Pages', 'wppu'); ?></h3>
									<div class="single-accordion">
										<div class="all_pages_checkbox_group">
											<input id="ckAllPages" type="checkbox">
											<label for="ckAllPages"><strong><?php esc_html_e('Disable All', 'wppu'); ?></strong></label>
											<br>
										    <?php
										    $i = 1;
										    foreach ( get_all_pages() as $network ) { ?>    
									            <input type="checkbox" class="checkPages" data-id="#<?php echo $network['value']; ?>" id="<?php echo $network['value']; ?>" value="1" name="all_pages_checkbox_group[]" />

									            <label for="<?php echo $network['value']; ?>"><?php $Pname = str_replace('-', ' ', $network['label']); echo ucwords($Pname); ?></label><br />
										    <?php $i++; } ?>
									    </div>
									</div>

									<h3><?php esc_html_e('Disable Specific Post Type ( Single post )', 'wppu'); ?></h3>
									<div class="single-accordion">
										<div class="all_post_types_checkbox_group">
											<input id="ckAllPosts" type="checkbox">
											<label for="ckAllPosts"><strong><?php esc_html_e('Select All', 'wppu'); ?></strong></label>
											<br>
										    <?php
										    $i = 1;
										    foreach ( get_all_post_type() as $network ) { ?>    
										        
										            <input type="checkbox" class="checkPosts" data-id="#<?php echo $network['value']; ?>" id="<?php echo $network['value']; ?>" value="1" name="all_post_types_checkbox_group[]" />

										            <label for="<?php echo $network['value']; ?>"><?php $Pname = str_replace('-', ' ', $network['label']); echo ucwords($Pname); ?></label><br />
										    <?php $i++; } ?>
										    
									    </div>
									</div>

									<h3><?php esc_html_e('Disable Archive Page', 'wppu'); ?></h3>
									<div class="single-accordion">
										<div>
											<?php $options = wppu_main_options_default_settings();
											$html = '<input type="checkbox" id="get_all_archive_page" name="wppu_display[get_all_archive_page]" value="1"' . checked( 1, $options['get_all_archive_page'], false ) . '/>';
											$html .= '&nbsp;';
											$html .= '<label for="get_all_archive_page">'.esc_html__('All kind of Archive Page', 'wppu').'</label>';

											echo $html.'<br />'; ?>
									    </div>
									</div>

									<h3><?php esc_html_e('Disable 404 Error Page', 'wppu'); ?></h3>
									<div class="single-accordion">
										<div>
											<?php $options = wppu_main_options_default_settings();
											$html = '<input type="checkbox" id="404_error_page" name="wppu_display[404_error_page]" value="1"' . checked( 1, $options['404_error_page'], false ) . '/>';
											$html .= '&nbsp;';
											$html .= '<label for="404_error_page">'.esc_html__('404 Error Page', 'wppu').'</label>';

											echo $html.'<br />'; ?>
										</div>
									</div>
									<h3><?php esc_html_e('Disable Search Page', 'wppu'); ?>Disable Search Page</h3>
									<div class="single-accordion">
										<div>
											<?php $options = wppu_main_options_default_settings();
											$html = '<input type="checkbox" id="wppu_search_page" name="wppu_display[wppu_search_page]" value="1"' . checked( 1, $options['wppu_search_page'], false ) . '/>';
											$html .= '&nbsp;';
											$html .= '<label for="wppu_search_page">'.esc_html__('Search Result Page', 'wppu').'</label>';

											echo $html.'<br />'; ?>
										</div>
									</div>

									<h3><?php esc_html_e('Disable on Mobile Or Tablet', 'wppu'); ?></h3>
									<div class="single-accordion">
										<div>
											<?php $options = wppu_main_options_default_settings();
											$html = '<input type="checkbox" id="disable_in_mobile" name="wppu_display[disable_in_mobile]" value="1"' . checked( 1, $options['disable_in_mobile'], false ) . '/>';
											$html .= '&nbsp;';
											$html .= '<label for="disable_in_mobile">'.esc_html__('Disable Preloader on Mobile', 'wppu').'</label>';
											
											echo $html; ?><br />
											<?php $options = wppu_main_options_default_settings();
											$html = '<input type="checkbox" id="disable_in_tablet" name="wppu_display[disable_in_tablet]" value="1"' . checked( 1, $options['disable_in_tablet'], false ) . '/>';
											$html .= '&nbsp;';
											$html .= '<label for="disable_in_tablet">'.esc_html__('Disable Preloader on Tablet', 'wppu').'</label>';
											
											echo $html; ?>
										</div>
									</div>
								</div>

							</div>
						</div>

					</div>

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Animation Settings ( Exclude = Pace options )', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="animation_effects"><?php _e('Select Preloader Hide Animation', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('select animation when preloader ready to hide', 'wppu' ); ?>"></span>
								<select  id="animation_effects" name="wppu_display[animation_effects]">
									<?php
										$options = wppu_main_options_default_settings();
										$selected = $options['animation_effects'];
										$p = '';
										$r = '';

										foreach ( wppu_select_animation_effect() as $option ) {
											$label = $option['label'];
											if ( $selected == $option['value'] ) // Make default first in list
												$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											else
												$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
										}
										echo $p . $r;
									?>
								</select>
							</div>
						</div>
					</div>

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Preloader Load Type', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="preloader_load_type"><?php _e('Select Preloader Load Type', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('Select preload load type for when preload should hide', 'wppu' ); ?>"></span>
								<select  id="preloader_load_type" name="wppu_display[preloader_load_type]">
									<?php
										$options = wppu_main_options_default_settings();
										$load_type_selected = $options['preloader_load_type'];
										$p = '';
										$r = '';

										foreach ( wppu_select_load_types() as $option ) {
											$label = $option['label'];
											if ( $load_type_selected == $option['value'] ) // Make default first in list
												$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											else
												$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
										}
										echo $p . $r;
									?>
								</select>
							</div>
						</div>
						<div id="custom_load_time-wrapper" class="pixiefy_single_column_container" <?php echo ( isset($load_type_selected) && $load_type_selected === "custom" ) ? 'style="display:block;"' : 'style="display:none;"'; ?> ?>
							<div class="pixiefy_signle_column_title">
								<label for="custom_load_time">Custom time for preloader (milliseconds)</label>
							</div>
							<div class="pixiefy_signle_column_content">
								<?php // Render the output
								echo '<input type="text" id="custom_load_time" name="wppu_display[custom_load_time]" value="' . $options['custom_load_time'] . '" />';
								?>
								<p>Set how many ms will you want to preloader display on your website. dosn't matter window load or not. preloader window will be display according to this custom time. But "Preloader Hide Timing" settings will be work same as before.</p>
							</div>
						</div>
					</div>

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Preloader Hide Timing', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Preloader Hide Dealy Timing', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input type="hidden" style="display:none" id="delay_timing" name="wppu_display[delay_timing]" value="<?php echo esc_attr( $options['delay_timing'] ); ?>" />
								<div class="really_font_size">
									<div class="really_font_size_div">
										<div class="delay_timing"></div>
										<p class="backgroundslize_slider_display"><?php esc_html_e('>Delay hide', 'wppu'); ?>: <span><?php echo $options['delay_timing'] ?></span>ms;</p>
									</div>
								</div>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Preloader Hiding Time', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input type="hidden" style="display:none" id="loading_out_timing" name="wppu_display[loading_out_timing]" value="<?php echo esc_attr( $options['loading_out_timing'] ); ?>" />
								<div class="really_font_size">
									<div class="really_font_size_div">
										<div class="loading_out_timing"></div>
										<p class="backgroundslize_slider_display"><?php esc_html_e('>Hide Time', 'wppu'); ?>: <span><?php echo $options['loading_out_timing'] ?></span>ms;</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Extra Settings', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Exclude Preload js from plugins', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<?php echo '<div class="wppu-background-size-set" id="exclude_js_from_plugins">';
								$i = 1;
								foreach ( wppu_yes_no() as $button ) {
								?>
								<input type="radio" id="exclude-opt-<?php echo $i; ?>" name="wppu_display[exclude_js_from_plugins]" value="<?php echo esc_attr( $button['value'] ); ?>" <?php checked( $options['exclude_js_from_plugins'], $button['value'] ); ?> />
								<label for="exclude-opt-<?php echo $i; ?>"><?php echo $button['label']; ?></label>
								
								<?php $i++;
								} echo "</div>"; ?>

								<p>If you want to exclude cache or minified from third party plugin like autoptimizer, cloudeflare rocket loader then enable this option. Enable by this option wppu plugin js will not be minified or cache by those third party plugin. This option not work for all plugin yet.</p>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Enable/Disable Loader before server render', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<?php echo '<div class="wppu-background-size-set" id="before_server_render">';
								$i = 1;
								foreach ( wppu_enable_disable() as $button ) {
								?>
								<input type="radio" id="before_server-<?php echo $i; ?>" name="wppu_display[before_server_render]" value="<?php echo esc_attr( $button['value'] ); ?>" <?php checked( $options['before_server_render'], $button['value'] ); ?> />
								<label for="before_server-<?php echo $i; ?>"><?php echo $button['label']; ?></label>
								
								<?php $i++;
								} echo "</div>"; ?>

								<p>If you want start loader to loading immediately after valid link then enable this feature Otherwise leave it as disable.</p>
								<?php
								$istyle = '';
								if( isset( $options['before_server_render'] ) && 'enable' === $options['before_server_render'] ) {
									$istyle = 'style="display: block;"';
								}
								?>
								<div class="pixiefy_parents_lists" <?php echo $istyle; ?>>
									<?php echo '<input type="text" id="exclude_parents" name="wppu_display[exclude_parents]" value="' . $options['exclude_parents'] . '" />'; ?> <br />
									<label for="exclude_parents">Input class or ID (separate by comma) those anchor you want to exclude from this feature./label>
								</div>
							</div>
						</div>
					</div>

				</div>  <!-- end of tab-content-container -->
				<div id="pixiefy-tabs-2">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Image Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set image option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'image_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="image_radio_options-1" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="image_radio_options-1">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>


							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="schedule"><?php _e('Select Image Collection', 'wppu') ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('Click on the image you want to', 'wppu' ); ?>"></span>
								<input type="hidden" id="select_gif" name="wppu_display[package_selector]" id="package_selector" value="<?php echo esc_attr( $options['package_selector'] ); ?>" />
								<div>
									<select name="selector" id="schedule">
										<option value="tabs-1"><?php _e('Modern Package', 'wppu' ); ?></option>
										<option value="tabs-2"><?php _e('Another Modern Package', 'wppu' ); ?></option>
										<option value="tabs-3"><?php _e('Regular Package', 'wppu' ); ?></option>
									</select>
								</div>
							</div>
							
						</div>

						<div class="wppu-full-single">
							<div>
								<div id="myModal">
									<div id="tabs">
										<div class="tab_content" id="tabs-1">
											<ul>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_1.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_2.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_3.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_4.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_5.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_6.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/Preloader_7.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/8.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/9.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/10.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/11.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/12.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/13.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/14.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/15.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/16.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/17.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/1.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/2.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/5.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/6.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/7.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/9.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/10.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/11.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/12.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/13.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/14.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/15.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/16.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/17.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/18.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/19.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/20.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/21.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/23.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/24.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/25.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/26.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/27.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/28.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/29.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/30.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/31.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/32.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/33.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/34.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/37.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/40.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/47.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/48.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/49.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/53.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/73.GIF'; ?>"></li>
											</ul>
										</div>

										<div class="tab_content" id="tabs-2">
											<ul>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/35.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/36.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/38.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/39.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/41.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/42.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/43.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/44.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/45.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/3.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/4.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/8.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/22.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/46.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/50.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/51.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/52.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/54.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/55.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/56.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/57.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/58.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/59.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/60.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/61.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/62.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/63.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/64.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/65.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/66.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/67.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/68.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/69.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/70.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/71.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/72.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/74.GIF'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/new/75.GIF'; ?>"></li>
											</ul>
										</div>

										<div class="tab_content" id="tabs-3">
											<ul>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/1.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/2.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/3.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/4.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/5.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/6.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/7.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/8.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/9.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/10.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/11.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/12.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/13.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/14.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/15.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/classic/16.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-1.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-2.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-3.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-4.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-5.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-6.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-7.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-8.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-9.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-10.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-11.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-12.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-13.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-14.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-15.gif'; ?>"></li>
												<li class="plz_click"><img width="32" src="<?php echo plugin_dir_url( __FILE__ ) . 'images/preloader-16.gif'; ?>"></li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Set Background Size Selector', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Set Background Image Size', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('If you dont use custom size option then it will set default style background image size auto', 'wppu' ); ?>"></span>
								<?php echo '<div class="wppu-background-size-set" id="size_selector">';
								$i = 1;
								foreach ( wppu_image_bgsize_sec_callback_settings() as $button ) {
								?>
								<input type="radio" id="background-size-<?php echo $i; ?>" name="wppu_display[background_size_selector]" value="<?php echo esc_attr( $button['value'] ); ?>" <?php checked( $options['background_size_selector'], $button['value'] ); ?> />&nbsp;
								<label for="background-size-<?php echo $i; ?>"><?php echo $button['label']; ?></label>
								
								<?php $i++;
								} echo "</div>"; ?>
							</div>
						</div>
						<div class="wppu_yeap">
							<div class="custom_size_slider">
								<div id="backgrondSize"></div>
								<p class="backgroundslize_slider_display">width: <span>
									<?php if(!empty($options['background_size'])) { echo $options['background_size']; } else { echo '32';} ?></span>px; height: auto</p>
							</div>		
						</div>
						<div class="wppu-full-single">
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="main_bgColor"><?php _e('Select Background Color', 'wppu' ); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<span class="toolTipD" title="<?php _e('Choose background according to image color', 'wppu' ); ?>"></span>
									<input type="hidden" id="background_sizer" name="wppu_display[background_size]" value="<?php echo esc_attr( $options['background_size'] ); ?>" />
									<div>
										<input id="main_bgColor" class="my-color-field" type="text" name="wppu_display[background_color]" value="<?php echo esc_attr( $options['background_color'] ); ?>" data-default-color="#ffffff" />
										<span class="clear_color" id="clear_color"><?php _e('Remove Color', 'wppu' ); ?></span>
									</div>
								</div>
							</div>
						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="wppu-full-single">
								<div class="preview_main_area alignleft">
									<div class="preview_div">
										<?php $defaultImg = plugin_dir_url( __FILE__ ) . 'images/Preloader_1.gif'; ?>
										<div class="wow_wppu_preview" style="background-image:url(<?php $oDefault = $options['package_selector']; if(!empty($oDefault)) { echo  esc_attr( $options['package_selector'] );} else { echo $defaultImg; } ?>);background-color:<?php echo esc_attr( $options['background_color'] ); ?>;background-size:<?php
										if ($options['background_size_selector'] == 'auto') {
											echo 'auto';
										} else {
											echo esc_attr( $options['background_size'] ).'px auto';
										}
										  ?>">
										</div>
									</div>
								</div>
								<div class="open_something_for_draggable">
									<span class="preview_opener" id="preview_openar"><?php _e('Open Draggble Preview', 'wppu' ); ?></span>
									<div id="draggle_preview_dialog" title="Live Preview">
										<div class="preview_main_area">
											<div class="preview_div">
												<?php $defaultImg = plugin_dir_url( __FILE__ ) . 'images/Preloader_1.gif'; ?>
												<div class="wow_wppu_preview" style="background-image:url(<?php $oDefault = $options['package_selector']; if(!empty($oDefault)) { echo  esc_attr( $options['package_selector'] );} else { echo $defaultImg; } ?>);background-color:<?php echo esc_attr( $options['background_color'] ); ?>;background-size:<?php
												if ($options['background_size_selector'] == 'auto') {
													echo 'auto';
												} else {
													echo esc_attr( $options['background_size'] ).'px auto';
												}
												  ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Use your custom image', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="_unique_name"><?php _e('Upload Custom Image', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('plz upload a gif image only', 'wppu' ); ?>"></span>
								<div class="single_image_upload">
							      <div class="uploder_with_btn">
							      	<input id="_unique_name" type="text" name="wppu_display[custom_image]" value="<?php echo esc_attr( $options['custom_image'] ); ?>" />
							        <input id="_unique_name_button" class="button button-primary" name="_unique_name_button" type="button" value="<?php _e("Upload" , "dholaikhaal"); ?>" />
							      </div>
							      <div class="img_prof plz_click">
							      	 <img src="<?php $profile_av = esc_attr( $options['custom_image'] ); echo $profile_av ?>" style="<?php echo  empty($profile_av) ? 'display:none;' :'' ?> max-width: 150px; max-height: 150px;">
							      </div>
							    </div> <!-- single img upload -->

							</div>
						</div>
					</div> <!-- single column -->

				</div>

				<div id="pixiefy-tabs-8">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Trendy Options', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set Trendy option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'trendy_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="trendy_radio_options-1" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="trendy_radio_options-1">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>

							</div> 
						</div>

						<div class="wppu-full-single">
							<div>
								<div id="myModalTabs">
									<textarea style="display: none" class="large-text" rows="7" cols="30" type="text" name="wppu_display[wppu_trendy_markup]" id="wppu_trendy_markup"><?php echo stripslashes( $options['wppu_trendy_markup'] ); ?></textarea>

									<input type="hidden" id="trendy_loading_style" name="wppu_display[trendy_loading_style]" id="trendy_loading_style" value="<?php echo esc_attr( $options['trendy_loading_style'] ); ?>" />
									<div id="css_tabs">
										<div class="css-container trendy_loader_container">

											<div class="items items-list trendy-scroll-container">
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_fazer_man">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                            	<div class="wppu_trendy_block trendy_fazer_man">
								                                <div class="wppu-trendy-loader-body">
																  <span>
																    <span></span>
																    <span></span>
																    <span></span>
																    <span></span>
																  </span>
																  <div class="fazer_base">
																    <span></span>
																    <div class="fazer_face"></div>
																  </div>
																</div>
																<div class="longfazers">
																  <span></span>
																  <span></span>
																  <span></span>
																  <span></span>
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
															</div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_tape_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu_trendy_block trendy_tape_loader">
							                                	<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
																<div class="wppu-trendy-tape"></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_gear_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu_trendy_block trendy_gear_loader">
							                                	<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
							                                	<div class="wppu-gear"></div> 
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_clock_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_clock_loader">
								                            	<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                                <div class="trendy-block-in">
																	<div class="wppu-trendy-clock"></div> 
																</div>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_clock2_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_clock2_loader">
								                            	<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                                <div class="block-in">
																	<div class="wppu-trendy-clock2"></div> 
																</div>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_finger_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_finger_loader">
																<div class="finger-loading">
													                <div class="finger-loader finger-loader-1">
													                    <div class="finger-loader-item">
													                        <span></span><i></i>
													                    </div>
													                </div>
													                <div class="finger-loader finger-loader-2">
													                    <div class="finger-loader-item">
													                        <span></span><i></i>
													                    </div>
													                </div>
													                <div class="finger-loader finger-loader-3">
													                    <div class="finger-loader-item">
													                          <span></span><i></i>
													                    </div>
													                </div>
													                <div class="finger-loader finger-loader-4">
													                    <div class="finger-loader-item">
													                        <span></span><i></i>
													                    </div>
													                </div>
													                <div class="last-finger-loader">
													                    <div class="last-finger-loader-item"><i></i></div>
													                </div>
													            </div>
													            <h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_polygon_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_polygon_loader">
																<div class="polygon-loader">
																  <div></div>
																  <div></div>
																  <div></div>
																  <div></div>
																  <div></div>
																  <div></div>
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_segment_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_segment_loader">
																<div class="wppu-trendy-segment-loader">
																  <div class="wppu-trendy-segment-holder">
																    <div class="wppu-trendy-segment wppu-trendy-segment-one"></div>
																  </div>
																  <div class="wppu-trendy-segment-holder">
																    <div class="wppu-trendy-segment wppu-trendy-segment-two"></div>
																  </div>
																  <div class="wppu-trendy-segment-holder">
																    <div class="wppu-trendy-segment wppu-trendy-segment-three"></div>
																  </div>
																  <div class="wppu-trendy-segment-holder">
																    <div class="wppu-trendy-segment wppu-trendy-segment-four"></div>
																  </div>
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_twinner_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_twinner_loader">
																<div class="trendy-twinner-loader"></div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_cardflip_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_cardflip_loader">
																<div class="cardflip-loader">
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																  <div class="cardflip-dot"></div>
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_wrapper1_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_wrapper1_loader">
																<div class="first-wrapper">
																  <div class="square-loader">
																    <div class="wrapp-square first_square"></div>
																    <div class="wrapp-square second_square"></div>
																    <div class="wrapp-square third_square"></div>
																  </div> 
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_wrapper2_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_wrapper2_loader">
																<div class="second-wrapper">
																  <div class="circle-loader">
																      <div class="wrapp-circle circle_four"></div>
																      <div class="wrapp-circle circle_three"></div>
																      <div class="wrapp-circle circle_two"></div>
																      <div class="wrapp-circle circle_one"></div>
																  </div>
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_wrapper3_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_wrapper3_loader">
																<div class="third-wrapper">
																  <div class="square-loader">
																    <div class="wrapp-square first_square"></div>
																    <div class="wrapp-square second_square"></div>
																    <div class="wrapp-square third_square"></div>
																  </div> 
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item trendy_click" data-add-div="16" data-class="trendy_cat_loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
								                            <div class="wppu_trendy_block trendy_cat_loader">
																<div class="cat-loader">
																  <div class="cat-track">
																    <div class="cat-mouse"></div>
																  </div>
																  <div class="cat-face">
																    <div class="cat-ears-container"></div>
																    <div class="cat-eyes-container">
																      <div class="cat-eye"></div>
																      <div class="cat-eye"></div>
																    </div>
																    <div class="cat-phiz">
																      <div class="cat-nose"></div>
																      <div class="cat-lip"></div>
																      <div class="cat-mouth"></div>
																    </div>
																  </div>
																</div>
																<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
								                            </div>
							                            </div>
							                        </div>
							                    </div>
							                </div>

										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="trendy_preview_container alignleft">
								<div class="wppu-awesome-loaders trendy-preloader-main-con awesome_limon_prv" style="background-color:<?php echo $options['trendy_background']; ?>">

									<div class="css-container wppu-css-loader">
										<div class="items">
					                        <div class="item">
						                        <div id="trendy_main_container" class="item-loader-container">
						                               <?php
						                               if( !empty($options['wppu_trendy_markup']) ) {
						                               		echo stripslashes( $options['wppu_trendy_markup'] );
						                               	} else { ?>

														<div class="item-inner">
								                            <div class="item-loader-container">
								                            	<div class="wppu_trendy_block trendy_fazer_man">
									                                <div class="wppu-trendy-loader-body">
																	  <span>
																	    <span></span>
																	    <span></span>
																	    <span></span>
																	    <span></span>
																	  </span>
																	  <div class="fazer_base">
																	    <span></span>
																	    <div class="fazer_face"></div>
																	  </div>
																	</div>
																	<div class="longfazers">
																	  <span></span>
																	  <span></span>
																	  <span></span>
																	  <span></span>
																	</div>
																	<?php if (!empty($options['trendy_loading_text'])) : ?>
																	<h2 class="trendy_loading_text"><?php esc_html_e('Loading', 'wppu'); ?></h2>
																	<?php endif; ?>
																</div>
								                            </div>
								                        </div>
														<?php } ?>
						                        </div>
						                    </div>
					                    </div>
									</div>

								</div>
							</div>
						</div>
					</div> <!-- single column -->
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Customize trendy css preloader', 'wppu' ); ?></h3>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="trendy_loading_text"><?php _e('Type your loading text', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<?php // Render the output
										echo '<input type="text" id="trendy_loading_text" name="wppu_display[trendy_loading_text]" value="' . $options['trendy_loading_text'] . '" />';
										?>
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="trendy_background"><?php _e('Trendy CSS Preloader Background', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="trendy_background" class="my-color-field" type="text" name="wppu_display[trendy_background]" value="<?php echo esc_attr( $options['trendy_background'] ); ?>" data-default-color="#4A5256" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="trendy_textcolor"><?php _e('Trendy CSS Text Color', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="trendy_textcolor" class="my-color-field" type="text" name="wppu_display[trendy_textcolor]" value="<?php echo esc_attr( $options['trendy_textcolor'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label><?php _e('Text Size', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input type="hidden" style="display:none" id="trendy_font_size" name="wppu_display[trendy_font_size]" value="<?php echo esc_attr( $options['trendy_font_size'] ); ?>" />
									<div class="really_font_size">
										<div class="really_font_size_div">
											<div class="trendy_font_sizer"></div>
											<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['trendy_font_size'] ?></span>px;</p>
										</div>		
									</div>
								</div>
							</div>
						</div> 
					</div> <!-- single column -->	

				</div>

				<div id="pixiefy-tabs-7">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Awesome CSS3 Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set Awesome CSS3 option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'awesome_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="awesome_radio_options-1" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="awesome_radio_options-1">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>


							</div> 
						</div>

						<div class="wppu-full-single">
							<div>
								<div id="myModalTabs">
									<input type="hidden" id="awesome_loading_selected" name="wppu_display[awesome_loading_selected]" id="awesome_loading_selected" value="<?php echo esc_attr( $options['awesome_loading_selected'] ); ?>" />
									<input type="hidden" id="awesome_selectedcounter" name="wppu_display[awesome_selectedcounter]" id="awesome_selectedcounter" value="<?php echo esc_attr( $options['awesome_selectedcounter'] ); ?>" />
									<div id="css_tabs">
										<div class="css-container">
											<div class="items items-list">
							                    <div class="item css_click" data-add-div="16" data-class="la-ball-8bits">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-8bits">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="4" data-class="la-ball-atom">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-atom">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-beat">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-beat">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-ball-circus">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-circus">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="4" data-class="la-ball-climbing-dot">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-climbing-dot">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-ball-clip-rotate">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-clip-rotate">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-ball-clip-rotate-multiple">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-clip-rotate-multiple">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-ball-clip-rotate-pulse">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-clip-rotate-pulse">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-ball-elastic-dots">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-elastic-dots">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-fall">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-fall">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="4" data-class="la-ball-fussion">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-fussion">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="9" data-class="la-ball-grid-beat">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-grid-beat">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="9" data-class="la-ball-grid-pulse">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-grid-pulse">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="4" data-class="la-ball-newton-cradle">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-newton-cradle">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-pulse">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-pulse">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-ball-pulse-rise">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-pulse-rise">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-pulse-sync">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-pulse-sync">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-ball-rotate">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-rotate">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-ball-running-dots">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-running-dots">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-ball-scale">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-scale">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-scale-multiple">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-scale-multiple">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-ball-scale-pulse">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-scale-pulse">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-ball-scale-ripple">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-scale-ripple">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-scale-ripple-multiple">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-scale-ripple-multiple">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-spin">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-spin-clockwise">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin-clockwise">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-spin-clockwise-fade">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin-clockwise-fade">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-spin-clockwise-fade-rotating">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin-clockwise-fade-rotating">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-spin-fade">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin-fade">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-spin-fade-rotating">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin-fade-rotating">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-ball-spin-rotate">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-spin-rotate">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-square-clockwise-spin">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-square-clockwise-spin">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-ball-square-spin">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-square-spin">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-ball-triangle-path">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-triangle-path">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-ball-zig-zag">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-zig-zag">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-ball-zig-zag-deflect">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-ball-zig-zag-deflect">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-cog">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-cog">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-cube-transition">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-cube-transition">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="3" data-class="la-fire">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-fire">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-line-scale">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-scale">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-line-scale-party">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-scale-party">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-line-scale-pulse-out">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-scale-pulse-out">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="5" data-class="la-line-scale-pulse-out-rapid">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-scale-pulse-out-rapid">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-line-spin-clockwise-fade">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-spin-clockwise-fade">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-line-spin-clockwise-fade-rotating">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-spin-clockwise-fade-rotating">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-line-spin-fade">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-spin-fade">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="8" data-class="la-line-spin-fade-rotating">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-line-spin-fade-rotating">
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="2" data-class="la-square-jelly-box">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-square-jelly-box">
							                                    <div></div>
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-square-loader">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-square-loader">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-square-spin">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-square-spin">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-timer">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-timer">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="la-triangle-skew-spin">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="la-triangle-skew-spin">
							                                    <div></div>
							                                </div>
							                            </div>
							                        </div>
							                    </div>



							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-01">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-01"></div>
							                            </div>
							                        </div>
							                    </div>

							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-02">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-02"></div>
							                            </div>
							                        </div>
							                    </div>

							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-03">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-03"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-04">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-04"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-05">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-05"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-06">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-06"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-07">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-07"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-08">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-08"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-09">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-09"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-10">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-10"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-11">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-11"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-12">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-12"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-14">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-14"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-15">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-15"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-16">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-16"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-17">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-17"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-18">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-18"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-19">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-19"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-20">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-20"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-21">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-21"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-22">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-22"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-23">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-23"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-24">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-24"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-25">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-25"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-26">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-26"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-27">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-27"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-28">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-28"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-29">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-29"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-30">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-30"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-31">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-31"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-32">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-32"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-33">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-33"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-34">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-34"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-35">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-35"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-37">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-37"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-38">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-38"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-39">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-39"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-40">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-40"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-41">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-41"></div>
							                            </div>
							                        </div>
							                    </div>
							                    <div class="item css_click" data-add-div="1" data-class="wppu-awesome-loader-42">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="wppu-awesome-loader-42"></div>
							                            </div>
							                        </div>
							                    </div>

	
							                </div>

										</div>

									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="css_preview_container alignleft">
								<div class="wppu-awesome-loaders awesome_limon_prv" style="background-color:<?php echo $options['awesome_background']; ?>">
									<div class="wppu-css-loader">
										<div class="items">
					                        <div class="item">
						                        <div class="item-inner">
						                            <div class="item-loader-container">
						                                <div class="<?php echo $options['awesome_loading_selected']; ?> la-2x item-generate" style="color:<?php echo $options['awesome_mainColor']; ?>">
						                                    <?php 
						                                    $val = $options['awesome_selectedcounter'];
						                                    for ($i = 1; $i <= $val; ++$i) {
												        		echo '<div></div>';
												        	} ?>
						                                </div>
						                            </div>
						                        </div>
						                        <div class="item-title awesome_title_txt" style="color:<?php echo $options['awesome_textcolor']; ?>;font-size:<?php echo $options['awesome_font_size']; ?>px"><?php echo $options['awesome_loading_text']; ?></div>
						                    </div>
					                    </div>
									</div>
								</div>
							</div>
							<div class="open_something_for_draggable">
								<span class="preview_opener" id="preview_openar_awesome"><?php _e('Open Draggble Preview', 'wppu' ); ?></span>
								<div id="draggle_preview_dialog_awesome" title="Live Preview">
									<div class="wppu-awesome-loaders awesome_limon_prv" style="background-color:<?php echo $options['awesome_background']; ?>">
										<div class="wppu-css-loader">
											<div class="items">
						                        <div class="item">
							                        <div class="item-inner">
							                            <div class="item-loader-container">
							                                <div class="<?php echo $options['awesome_loading_selected']; ?> la-2x item-generate" style="color:<?php echo $options['awesome_mainColor']; ?>">
							                                    <?php 
							                                    $val = $options['awesome_selectedcounter'];
							                                    for ($i = 1; $i <= $val; ++$i) {
													        		echo '<div></div>';
													        	} ?>
							                                </div>
							                            </div>
							                        </div>
							                        <div class="item-title awesome_title_txt" style="color:<?php echo $options['awesome_textcolor']; ?>;font-size:<?php echo $options['awesome_font_size']; ?>px"><?php echo $options['awesome_loading_text']; ?></div>
							                    </div>
						                    </div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Customize awesome css preloader', 'wppu' ); ?></h3>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="awesome_loading_text"><?php _e('Type your loading text', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<?php // Render the output
										echo '<input type="text" id="awesome_loading_text" name="wppu_display[awesome_loading_text]" value="' . $options['awesome_loading_text'] . '" />';
										?>
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="awesome_mainColor"><?php _e('Awesome CSS Preloader Body Color', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="awesome_mainColor" class="my-color-field" type="text" name="wppu_display[awesome_mainColor]" value="<?php echo esc_attr( $options['awesome_mainColor'] ); ?>" data-default-color="#000000" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="awesome_background"><?php _e('Awesome CSS Preloader Background', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="awesome_background" class="my-color-field" type="text" name="wppu_display[awesome_background]" value="<?php echo esc_attr( $options['awesome_background'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="awesome_textcolor"><?php _e('Awesome CSS Text Color', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="awesome_textcolor" class="my-color-field" type="text" name="wppu_display[awesome_textcolor]" value="<?php echo esc_attr( $options['awesome_textcolor'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label><?php _e('Text Size', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input type="hidden" style="display:none" id="awesome_font_size" name="wppu_display[awesome_font_size]" value="<?php echo esc_attr( $options['awesome_font_size'] ); ?>" />
									<div class="really_font_size">
										<div class="really_font_size_div">
											<div class="awesome_font_sizer"></div>
											<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['awesome_font_size'] ?></span>px;</p>
										</div>		
									</div>
								</div>
							</div>
						</div> 
					</div> <!-- single column -->		
				</div>

				<div id="pixiefy-tabs-3">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('CSS Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set css option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'css_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="image_radio_options-2" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="image_radio_options-2">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>


							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="waitMe_ex_effect"><?php _e('Select Element', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<span class="toolTipD" title="<?php _e('select effect and see in live preview', 'wppu'); ?>"></span>

									<select  id="waitMe_ex_effect" name="wppu_display[effect_options]">
										<?php
											$selected = $options['effect_options'];
											$p = '';
											$r = '';

											foreach ( wppu_select_element_callback_settings() as $option ) {
												$label = $option['label'];
												if ( $selected == $option['value'] ) // Make default first in list
													$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
												else
													$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											}
											echo $p . $r;
										?>
									</select>
								</div>
							</div>

							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="input_example"><?php _e('Change Preloader Text', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<span class="toolTipD" title="<?php _e('Type your name or what ever you want as a preloader but plz care full about your length', 'wppu' ); ?>"></span>
									<?php echo '<input type="text" id="input_example" name="wppu_display[input_example]" value="' . $options['input_example'] . '" />'; ?>
								</div>
							</div>
						</div> 
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="css_preview_container">
								<div class="limon_prv"></div>
								<div class="limon_prv_second"></div>
							</div>
							<div class="open_something_for_draggable">
								<span class="preview_opener" id="preview_openar_css"><?php _e('Open Draggble Preview', 'wppu' ); ?></span>
								<div id="draggle_preview_dialog_css" title="Live Preview">
									<div class="limon_prv"></div>
									<div class="limon_prv_second"></div>
								</div>
							</div>
						</div>

					</div> <!-- single column -->	


					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Set Preloader Color and Background Color', 'wppu'); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="preview_mainColor"><?php _e('Preloader Main Color' ,'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="preview_mainColor" class="my-color-field" type="text" name="wppu_display[preview_mainColor]" value="<?php echo esc_attr( $options['preview_mainColor'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="preview_backgroundColor"><?php _e('Preloader Background Color' ,'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="preview_backgroundColor" class="my-color-field" type="text" name="wppu_display[preview_backgroundColor]" value="<?php echo esc_attr( $options['preview_backgroundColor'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>
					</div> <!-- single column -->
				</div>
				<div id="pixiefy-tabs-4">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Special Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set Special option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'special_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="image_radio_options-3" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="image_radio_options-3">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								<?php }} ?>


							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="special_text"><?php _e('Type your text', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<?php // Render the output
										echo '<input type="text" id="special_text" name="wppu_display[special_text]" value="' . $options['special_text'] . '" />';
										?>
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="special_color"><?php _e('Text Color', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="special_color" class="my-color-field" type="text" name="wppu_display[special_color]" value="<?php echo esc_attr( $options['special_color'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="special_background"><?php _e('Text Animation Banckground', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="special_background" class="my-color-field" type="text" name="wppu_display[special_background]" value="<?php echo esc_attr( $options['special_background'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label><?php _e('Text Size', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input type="hidden" style="display:none" id="special_font_size" name="wppu_display[special_font_size]" value="<?php echo esc_attr( $options['special_font_size'] ); ?>" />
									<div class="really_font_size">
										<div class="really_font_size_div">
											<div class="special_font_sizer"></div>
											<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['special_font_size'] ?></span>em;</p>
										</div>		
									</div>
								</div>
							</div>
						</div> 
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container speical_container">
							<div id="special_prev" class="preview_spcial_container">
								<section class="wppu-preloader loading not-loaded" style="background-color:<?php echo $options['special_background'] ?>">
								    <div class="wppu-logo-loader wppu_frameT">
								    	<div class="wppu_frameTC">
									        <div class="wppu_special_preloader">
									            <h2 class="wppu_special" style="font-size:<?php echo $options['special_font_size'] ?>em;">
									                <?php 
									                $val = $options['special_text'];
									                $str = esc_attr( $val );;
									                for ($i = 0; $i < strlen($str); ++$i) {
									                	if (strlen(trim($str[$i])) == 0) {
									                		$string = '&nbsp;';
									                	} else {
									                		$string = $str[$i];
									                	}
									                   echo '<span class="span-'.$i.'">
									                           <p class="t-p">'.$string.'</p>
									                           <p class="h-p" style="color:' . $options['special_color'] . '">'.$string.'</p>
									                        </span>';
									                    }
									                 ?>
									            </h2>
									        </div>
								        </div>
								    </div>
								</section>
								<div id="click_toggle_possition"><?php _e('Click to Large view', 'wppu' ); ?></div>
								<div id="run_animation"><?php _e('Run Again', 'wppu' ); ?></div>
							</div>
						</div>
					</div> <!-- single column -->	
				</div>
				<div id="pixiefy-tabs-5">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Pace Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set Pace option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'pace_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="image_radio_options-4" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="image_radio_options-4">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>


							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="wppu-full-single">
								<div class="wppu-move wppu-alert-massage">
									<?php $home = esc_url( home_url() );
									printf( __( 'First Select your desire pace jQuery progressBar effect, color & background-color then live preview on your site <a href="%1$s" target="_blank">View Preview</a> For details visit <a href="%2$s" target="_blank">pace official page</a>', 'wppu' ),
										esc_url( home_url() ),
										'http://github.hubspot.com/pace/docs/welcome'
									);
									?>
								</div>
							 </div>
						 </div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Set Pace Effect', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="paceSelectOptions"><?php _e('Select Your Effects', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('select your pace effect', 'wppu' ); ?>"></span>
								<select  id="paceSelectOptions" name="wppu_display[pace_effects]">
									<?php
										$selected = $options['pace_effects'];
										$p = '';
										$r = '';

										foreach ( wppu_pace_effects_callback_settings() as $option ) {
											$label = $option['label'];
											if ( $selected == $option['value'] ) // Make default first in list
												$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											else
												$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
										}
										echo $p . $r;
									?>
								</select>
							</div>
						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Set Pace Color And Background', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="pace_color"><?php _e('Choose Your Pace Main Color', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('choose pace preloader main color. change and go to your website and see what happen' , 'wppu' ); ?>"></span>
								<input id="pace_color" class="my-color-field" type="text" name="wppu_display[pace_color]" value="<?php echo esc_attr( $options['pace_color'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="pace_background"><?php _e('Choose pace preloader background color', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('choose pace preloader background color. change and go to your website and see what happen' , 'wppu' ); ?>"></span>
								<input id="pace_background" class="my-color-field" type="text" name="wppu_display[pace_background]" value="<?php echo esc_attr( $options['pace_background'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>
					</div> <!-- single column -->
				</div>
				<div id="pixiefy-tabs-6">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Your Custom Preloader Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set Custom Preloader option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'custom_your_options') {
								?>

								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="image_radio_options-5" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="image_radio_options-5">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>


							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="wppu-full-single">
								<div class="wppu-move wppu-alert-massage">
									<?php _e('Following settings will applies to control to make your own preloader with html, css and javascript', 'wppu' ); ?>
								</div>
							 </div>
						 </div>
					</div> <!-- single column -->
					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Custom Preloader HTML', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="custom_wppu_markup"><?php _e('Input your custom preloader markup/html', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<textarea class="large-text" rows="7" cols="30" type="text" name="wppu_display[custom_wppu_markup]" id="custom_wppu_markup" /><?php echo stripslashes( $options['custom_wppu_markup'] ); ?></textarea>
							</div>
						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Custom Preloader Style', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="custom_wppu_css"><?php _e('Input your custom preloader style/css. no need use <style></style>', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<textarea class="large-text" rows="7" cols="30" type="text" name="wppu_display[custom_wppu_css]" id="custom_wppu_css" /><?php echo stripslashes( $options['custom_wppu_css'] ); ?></textarea>
							</div>
						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Custom Preloader Script', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="custom_wppu_js_pos"><?php _e('Select script position', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('Select script position', 'wppu' ); ?>"></span>
								<select  id="custom_wppu_js_pos" name="wppu_display[custom_wppu_js_pos]">
									<?php
										$selected = $options['custom_wppu_js_pos'];
										$p = '';
										$r = '';

										foreach ( wppu_custom_js_pos() as $option ) {
											$label = $option['label'];
											if ( $selected == $option['value'] ) // Make default first in list
												$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											else
												$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
										}
										echo $p . $r;
									?>
								</select>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="custom_wppu_js"><?php _e('Your custom preloader script for custom preloader hide/animate effect. Leave blank to use default settings (General Settings)', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<textarea class="large-text" rows="7" cols="30" type="text" name="wppu_display[custom_wppu_js]" id="custom_wppu_js" /><?php echo json_decode( $options['custom_wppu_js'] ); ?></textarea>
							</div>
						</div>
					</div> <!-- single column -->
				</div>
				<div id="pixiefy-tabs-9">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('3D Letters Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Set 3D option as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'three_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="three_radio_options" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="three_radio_options">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>


							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="3d_loader_text"><?php _e('Type your text', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<?php // Render the output
										echo '<input type="text" id="3d_loader_text" name="wppu_display[3d_loader_text]" value="' . $options['3d_loader_text'] . '" />';
										?>
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="3d_loader_color"><?php _e('Text Color', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="3d_loader_color" class="my-color-field" type="text" name="wppu_display[3d_loader_color]" value="<?php echo esc_attr( $options['3d_loader_color'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="3d_loader_background"><?php _e('Text Animation Banckground', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="3d_loader_background" class="my-color-field" type="text" name="wppu_display[3d_loader_background]" value="<?php echo esc_attr( $options['3d_loader_background'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="3d_loader_background_con"><?php _e('Conainer Banckground', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="3d_loader_background_con" class="my-color-field" type="text" name="wppu_display[3d_loader_background_con]" value="<?php echo esc_attr( $options['3d_loader_background_con'] ); ?>" data-default-color="#000000" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label><?php _e('Text Size', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input type="hidden" style="display:none" id="3d_loader_font_size" name="wppu_display[3d_loader_font_size]" value="<?php echo esc_attr( $options['3d_loader_font_size'] ); ?>" />
									<div class="really_font_size">
										<div class="really_font_size_div">
											<div class="3d_loader_font_sizer"></div>
											<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['3d_loader_font_size'] ?></span>px;</p>
										</div>		
									</div>
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="3d_loader_animate_style"><?php _e('Select Style', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<span class="toolTipD" title="<?php _e('select effect and see in live preview', 'wppu'); ?>"></span>

									<select  id="3d_loader_animate_style" name="wppu_display[3d_loader_animate_style]">
										<?php
											$selected = $options['3d_loader_animate_style'];
											$p = '';
											$r = '';

											foreach ( wppu_threed_loader_animate_style() as $option ) {
												$label = $option['label'];
												if ( $selected == $option['value'] ) // Make default first in list
													$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
												else
													$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											}
											echo $p . $r;
										?>
									</select>
								</div>
							</div>
						</div> 
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container speical_container">
							<div id="3d_loader_prev" class="preview_spcial_container">
								<section class="wppu-preloader wppu-threed-loader-con loading not-loaded" style="background-color:<?php echo $options['3d_loader_background_con'] ?>">
								    <div class="wppu-logo-loader wppu_frameT">
								    	<div class="wppu_frameTC">
									        <div class="wppu_special_preloader">
									            <div id="wppu_3d_loader_body" class="wppu-3d-loader <?php echo $options['3d_loader_animate_style']; ?>">
									                <?php 
									                $val = $options['3d_loader_text'];
									                $str = strtoupper($val);
									                for ($i = 0; $i < strlen($str); ++$i) {
									                   echo '<span style="background-color: ' . $options['3d_loader_background'] . ';font-size: ' . $options['3d_loader_font_size'] . 'px; color: ' . $options['3d_loader_color'] . ';" class="wppu-3d-loader-item">'.$str[$i].'</span>';
									                    }
									                 ?>
									            </div>
									        </div>
								        </div>
								    </div>
								</section>
							</div>
						</div>
					</div> <!-- single column -->	
				</div>
				<div id="pixiefy-tabs-10">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Fill Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Fill as preloader ==>', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'fill_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="fill_radio_options" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="fill_radio_options">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>

							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="fill_loader_type"><?php _e('Select fill loader type', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<?php echo '<div class="wppu-fill-loader-type" id="fill-loader-type">';
								$i = 1;
								foreach ( wppu_fill_loader_type() as $button ) {
								?>
								<input type="radio" id="wppu-fill-loader-type-<?php echo $i; ?>" name="wppu_display[fill_loader_type]" value="<?php echo esc_attr( $button['value'] ); ?>" <?php checked( $options['fill_loader_type'], $button['value'] ); ?> />&nbsp;
								<label for="wppu-fill-loader-type-<?php echo $i; ?>"><?php echo $button['label']; ?></label>
								
								<?php $i++;
								} echo "</div>"; ?>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="fill_loader_background"><?php _e('Loader Main Background Color', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="fill_loader_background" class="my-color-field" type="text" name="wppu_display[fill_loader_background]" value="<?php echo esc_attr( $options['fill_loader_background'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>

					</div> <!-- single column -->

					<div class="wppu_fill_type_text_only <?php if( 'text' == $options['fill_loader_type'] ) { echo 'wppu_fill_type_active'; } ?>">
						<div class="pixiefy_single_column">
							<h3 class="wppu-move wppu-h3"><?php _e('Fill Text Loader', 'wppu' ); ?></h3>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="fill_loader_text"><?php _e('Type your text', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<?php // Render the output
										echo '<input type="text" id="fill_loader_text" name="wppu_display[fill_loader_text]" value="' . $options['fill_loader_text'] . '" />';
										?>
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="fill_loader_text_color"><?php _e('Text Color', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input id="fill_loader_text_color" class="my-color-field" type="text" name="wppu_display[fill_loader_text_color]" value="<?php echo esc_attr( $options['fill_loader_text_color'] ); ?>" data-default-color="#ffffff" />
								</div>
							</div>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label><?php _e('Text Size', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input type="hidden" style="display:none" id="fill_loader_text_size" name="wppu_display[fill_loader_text_size]" value="<?php echo esc_attr( $options['fill_loader_text_size'] ); ?>" />
									<div class="really_font_size">
										<div class="really_font_size_div">
											<div class="fill_loader_text_sizer"></div>
											<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['fill_loader_text_size'] ?></span>px;</p>
										</div>		
									</div>
								</div>
							</div>
						</div>
					</div> <!-- end text only -->

					<div class="wppu_fill_type_thumb_only <?php if( 'image' == $options['fill_loader_type'] ) { echo 'wppu_fill_type_active'; } ?>">
						<div class="pixiefy_single_column">
							<h3 class="wppu-move wppu-h3"><?php _e('Use your custom image', 'wppu' ); ?></h3>
							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label for="fill_loader_thumb"><?php _e('Upload Custom Image', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<span class="toolTipD" title="<?php _e('plz upload a gif image only', 'wppu' ); ?>"></span>
									<div class="single_image_upload">
								      <div class="uploder_with_btn">
								      	<input id="fill_loader_thumb" type="text" name="wppu_display[fill_loader_thumb]" value="<?php echo esc_attr( $options['fill_loader_thumb'] ); ?>" />
								        <input id="fill_loader_thumb_button" class="button button-primary" name="fill_loader_thumb_button" type="button" value="<?php _e("Upload" , "dholaikhaal"); ?>" />
								      </div>
								      <div class="img_prof plz_click">
								      	 <img src="<?php $profile_av = esc_attr( $options['fill_loader_thumb'] ); echo $profile_av ?>" style="<?php echo  empty($profile_av) ? 'display:none;' : '' ?> max-width: 150px; max-height: 150px;">
								      </div>
								    </div> <!-- single img upload -->

								</div>
							</div>

							<div class="pixiefy_single_column_container">
								<div class="pixiefy_signle_column_title">
									<label><?php _e('Thumb Size', 'wppu'); ?></label>
								</div>
								<div class="pixiefy_signle_column_content">
									<input type="hidden" style="display:none" id="fill_loader_thumb_size" name="wppu_display[fill_loader_thumb_size]" value="<?php echo esc_attr( $options['fill_loader_thumb_size'] ); ?>" />
									<div class="really_font_size">
										<div class="really_font_size_div">
											<div class="fill_loader_thumb_sizer"></div>
											<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['fill_loader_thumb_size'] ?></span>px;</p>
										</div>		
									</div>
								</div>
							</div>
						</div> <!-- single column -->

						
					</div> <!-- fill thumb type -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container speical_container">
							<div class="wppu_fill_progress_bar <?php echo 'wppu_fill_progress_pos-' . $options['fill_progress_bar_pos']; ?>" style="background-color:<?php echo $options['fill_progress_bar_bg'] ?>;height:<?php echo $options['fill_progress_bar_height'] ?>px"></div>
							<div id="fill_loader_preview" class="preview_spcial_container">
								<section class="wppu-preloader wppu-threed-loader-con loading not-loaded" style="background-color:<?php echo $options['fill_loader_background'] ?>">
								    <div class="wppu-logo-loader wppu_frameT">
								    	<div class="wppu_frameTC">
									        <div id="wppu_fill_text_main">
									        	<div id="wppu-fill-loader-text">
													<h2 <?php echo 'style="'; ?> <?php echo (!empty( $options['fill_loader_text_size'] )) ? "font-size: " . $options['fill_loader_text_size'] . "px;" : ""; echo '"'; ?> class="wppu_fill_h2"><?php echo $options['fill_loader_text']; ?></h2>
													<h2 <?php echo 'style="'; ?> <?php echo (!empty( $options['fill_loader_text_size'] )) ? "font-size: " . $options['fill_loader_text_size'] . "px;" : ""; echo (!empty( $options['fill_loader_text_color'] )) ? "color: ".$options['fill_loader_text_color']."" : ""; echo '"'; ?> class="wppu_fill_h2_attr"><?php echo $options['fill_loader_text']; ?></h2>
										        </div>
									        </div>
									        <div id="wppu_fill_thumb_main">
										        <div id="wppu-fill-loader-thunb">
										        	<div class="wppu-fill-thumbnail-fill"  style="background-image: url(<?php 
										        	if( !empty($options['fill_loader_thumb']) ) { echo $options['fill_loader_thumb']; } else { echo 'http://www.oshwa.org/wp-content/uploads/2014/03/oshw-logo-800-px.png' ; } ?>);"></div>
	                    							<img src="<?php 
										        	if( !empty($options['fill_loader_thumb']) ) { echo $options['fill_loader_thumb']; } else { echo 'http://www.oshwa.org/wp-content/uploads/2014/03/oshw-logo-800-px.png' ; } ?>" alt="">
										        </div>
									        </div>
									        <div class="wppu_fill_counter <?php if( isset($options['fill_counter_enable']) && $options['fill_counter_enable'] == 1 ) { echo 'wppu_fill_counter_active'; } ?>" style="color:<?php echo $options['fill_counter_text_color'] ?>;font-size:<?php echo $options['fill_counter_text_size'] ?>px"><span>100</span>%</div>
								        </div>
								    </div>
								</section>
								<div id="run_animation_fill"><?php esc_html_e('Run Again', 'wppu'); ?></div>
							</div>
						</div>
					</div> <!-- single column -->

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Progess bar and Counder controll', 'wppu'); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="fill_progress_bar_pos"><?php _e('Select Progess bar position', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('select effect and see in live preview', 'wppu'); ?>"></span>

								<select  id="fill_progress_bar_pos" name="wppu_display[fill_progress_bar_pos]">
									<?php
										$selected = $options['fill_progress_bar_pos'];
										$p = '';
										$r = '';

										foreach ( wppu_fill_progress_bar_position() as $option ) {
											$label = $option['label'];
											if ( $selected == $option['value'] ) // Make default first in list
												$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
											else
												$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
										}
										echo $p . $r;
									?>
								</select>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="fill_progress_bar_bg"><?php _e('Progess bar background color' ,'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="fill_progress_bar_bg" class="my-color-field" type="text" name="wppu_display[fill_progress_bar_bg]" value="<?php echo esc_attr( $options['fill_progress_bar_bg'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Progesbar height', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input type="hidden" style="display:none" id="fill_progress_bar_height" name="wppu_display[fill_progress_bar_height]" value="<?php echo esc_attr( $options['fill_progress_bar_height'] ); ?>" />
								<div class="really_font_size">
									<div class="really_font_size_div">
										<div class="fill_progress_bar_heightr"></div>
										<p class="backgroundslize_slider_display">Height: <span><?php echo $options['fill_progress_bar_height'] ?></span>px;</p>
									</div>		
								</div>
							</div>
						</div>

						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="fill_counter_enable"><?php _e('Counter Enable/Disable', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<?php $options = wppu_main_options_default_settings();
								$html = '<input type="checkbox" id="fill_counter_enable" name="wppu_display[fill_counter_enable]" value="1"' . checked( 1, $options['fill_counter_enable'], false ) . '/>';
								$html .= '&nbsp;';
								$html .= '<label for="fill_counter_enable">Enable</label>';

								echo $html.'<br />'; ?>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="fill_counter_text_color"><?php _e('Counter Text Color' ,'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="fill_counter_text_color" class="my-color-field" type="text" name="wppu_display[fill_counter_text_color]" value="<?php echo esc_attr( $options['fill_counter_text_color'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>

						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Counter Font Size', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input type="hidden" style="display:none" id="fill_counter_text_size" name="wppu_display[fill_counter_text_size]" value="<?php echo esc_attr( $options['fill_counter_text_size'] ); ?>" />
								<div class="really_font_size">
									<div class="really_font_size_div">
										<div class="fill_counter_text_sizer"></div>
										<p class="backgroundslize_slider_display">font-size: <span><?php echo $options['fill_counter_text_size'] ?></span>px;</p>
									</div>		
								</div>
							</div>
						</div>


					</div> <!-- single column -->
				</div>
				<div id="pixiefy-tabs-11">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Apply Extra CSS to WPPU', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Add/Paste Extra CSS', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="custom_wppu_extra_css"><?php _e('Input your custom preloader style/css. no need use <style></style>', 'wppu' ); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<textarea class="large-text" rows="7" cols="30" type="text" name="wppu_display[custom_wppu_extra_css]" id="custom_wppu_extra_css" /><?php echo stripslashes( $options['custom_wppu_extra_css'] ); ?></textarea>
							</div>
						</div>
					</div> <!-- single column -->
				</div>
						


				<div id="pixiefy-tabs-12">
					<div class="wppu-full-single">
						<h3 class="wppu-top-h3"><?php _e('Object Options Settings', 'wppu' ); ?></h3>
						<input type="submit" class="button-primary top-right-btn" value="<?php esc_html_e('Save Options', 'wppu'); ?>" />
					</div>
					
					<div class="pixiefy_single_column">
						<div class="wppu-full-single full-with-btn">
							<h3 class="wppu-move wppu-h3"><?php _e('Use your custom image', 'wppu' ); ?></h3>
							<div class="right-radio-top-btn wppu_settings_btnonoffswitch">
								<?php
								foreach ( wppu_radio_general_save_settings() as $button ) {
									if($button['value'] == 'object_radio_options') {
								?>
								<input 
								type="radio"
								class="onoffswitch-checkbox"
								id="object_radio_options" 
								name="wppu_display[sample_radio_buttons]" 
								value="<?php echo esc_attr( $button['value'] ); ?>" 
								<?php checked( $options['sample_radio_buttons'], $button['value'] ); ?> />&nbsp;
								<label class="onoffswitch-label" for="object_radio_options">
								    <span class="onoffswitch-inner"></span>
								    <span class="onoffswitch-switch"></span>
							    </label>
								
								<?php }} ?>

							</div> 
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="object_loader_thumb"><?php _e('Upload Custom Image', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<span class="toolTipD" title="<?php _e('plz upload a image only', 'wppu' ); ?>"></span>
								<div class="single_image_upload">
							      <div class="uploder_with_btn">
							      	<input id="object_loader_thumb" type="text" name="wppu_display[object_loader_thumb]" value="<?php echo esc_attr( $options['object_loader_thumb'] ); ?>" />
							        <input id="object_loader_thumb_button" class="button button-primary" name="object_loader_thumb_button" type="button" value="<?php _e("Upload" , "dholaikhaal"); ?>" />
							      </div>
							      <div class="img_prof plz_click">
							      	 <img src="<?php $profile_av = esc_attr( $options['object_loader_thumb'] ); echo $profile_av ?>" style="<?php echo  empty($profile_av) ? 'display:none;' : '' ?> max-width: 150px; max-height: 150px;">
							      </div>
							    </div> <!-- single img upload -->

							</div>
						</div>

						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label><?php _e('Object Size', 'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input type="hidden" style="display:none" id="object_loader_thumb_size" name="wppu_display[object_loader_thumb_size]" value="<?php echo esc_attr( $options['object_loader_thumb_size'] ); ?>" />
								<div class="really_font_size">
									<div class="really_font_size_div">
										<div class="object_loader_thumb_sizer"></div>
										<p class="backgroundslize_slider_display">width: <span><?php echo $options['object_loader_thumb_size'] ?></span>px;</p>
									</div>		
								</div>
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="object_main_color"><?php _e('Object main color' ,'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="object_main_color" class="my-color-field" type="text" name="wppu_display[object_main_color]" value="<?php echo esc_attr( $options['object_main_color'] ); ?>" data-default-color="#ffffff" />
							</div>
						</div>
						<div class="pixiefy_single_column_container">
							<div class="pixiefy_signle_column_title">
								<label for="object_wrapper_bg"><?php _e('Preloader background color' ,'wppu'); ?></label>
							</div>
							<div class="pixiefy_signle_column_content">
								<input id="object_wrapper_bg" class="my-color-field" type="text" name="wppu_display[object_wrapper_bg]" value="<?php echo esc_attr( $options['object_wrapper_bg'] ); ?>" data-default-color="#009999" />
							</div>
						</div>
					</div> <!-- single column -->

					

					<div class="pixiefy_single_column">
						<h3 class="wppu-move wppu-h3"><?php _e('Live Preview', 'wppu' ); ?></h3>
						<div class="pixiefy_single_column_container">
							<div class="wppu-full-single">
								<div class="preview_main_area alignleft">
									<div class="preview_div wppu-object-preview-main">
										<?php $defaultImg = plugin_dir_url( __FILE__ ) . 'images/Preloader_1.gif'; ?>
										<div class="object-wppu-preview" style="background-color:<?php echo esc_attr( $options['object_wrapper_bg'] ); ?>;">
											<div id="wppu-object-wrapper" <?php 
											echo ( !empty( $options['object_loader_thumb_size'] ) ) ? "style='width:".$options['object_loader_thumb_size']."px;height:".$options['object_loader_thumb_size']."px; '" : ""; ?>>
												<div class="wppu-object-logo">
													<img src="<?php $profile_av = esc_attr( $options['object_loader_thumb'] ); echo $profile_av ?>" style="<?php echo empty($profile_av) ? 'display:none;' : '' ?>;">
												</div>
												<div class="wpppu-object-wrap" <?php echo ( !empty( $options['object_main_color'] ) ) ? "style='color:".$options['object_main_color'].";'" : ""; ?>></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div> <!-- single column -->



				</div>

			</div> <!-- end of tab-content-container -->
		</div> <!-- end of pixiefy main tabs -->
	
	</div> <!-- end of pixiefy-form-container -->

	<p class="submit"><input type="submit" class="button-primary" value="<?php esc_html_e('Save Options', 'wppu'); ?>" /></p>
	
	</form>
	<div id="loading-image"></div>
	
	<script type="text/javascript">

		jQuery(document).ready(function() {
		   jQuery('#wppu_Options_Form').submit(function() { 
		   	  jQuery("#loading-image").show();
		   	  jQuery("#loading-image").removeClass("loading_complete");
		      jQuery(this).ajaxSubmit({
		         success: function(){
		         	jQuery('#loading-image').addClass("loading_complete");
		         },
		         timeout: 5000
		      }); 
		      setTimeout("jQuery('#loading-image').hide();", 4500);
		      return false; 
		   });
		});
		jQuery(function(){
			jQuery('.limon_prv_second').waitMe({
				effect: "<?php if(!empty($options['effect_options'])) { echo $options['effect_options']; } else { echo 'ios'; } ?>",
				text: "<?php if(!empty($options['input_example'])) { echo $options['input_example']; } else { echo 'Please Wait...';} ?>",
				bg: "<?php if(!empty($options['preview_backgroundColor'])) { echo $options['preview_backgroundColor']; } else { echo '#FFFFFF';} ?>",
				color: "<?php if(!empty($options['preview_mainColor'])) { echo $options['preview_mainColor']; } else { echo '#000000'; }?>",
				sizeW:'',
				sizeH:''
			});
			jQuery('#waitMe_ex_effect').change(function(){
				jQuery('.limon_prv_second').remove();
				var current_effect = jQuery(this).val(),
					current_color = jQuery('#preview_mainColor').val(),
				    current_bg = jQuery('#preview_backgroundColor').val();
					run_waitMe(current_effect, current_color, current_bg);
			});
			jQuery('#preview_mainColor').wpColorPicker({
				change: function( event, ui ) {
					jQuery('.limon_prv_second').remove();
				    var current_effect = jQuery('#waitMe_ex_effect').val(),
					current_color = ui.color.toString(),
				    current_bg = jQuery('#preview_backgroundColor').val();
					run_waitMe(current_effect, current_color, current_bg);
				}
			});
			jQuery('#preview_backgroundColor').wpColorPicker({
				change: function( event, ui ) {
					jQuery('.limon_prv_second').remove();
				    var current_effect = jQuery('#waitMe_ex_effect').val(),
					current_color = jQuery('#preview_mainColor').val(),
				    current_bg = ui.color.toString();
					run_waitMe(current_effect, current_color, current_bg);
				}
			});
			function run_waitMe( effect, color, bg, text){
				jQuery('.limon_prv').waitMe({
					effect: effect,
					text: 'Please Wait...',
					bg: bg,
					color: color,
					sizeW:'',
					sizeH:''
				});
			}
			jQuery(".trendy_font_sizer").slider({
			   max: 100,
		   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['trendy_font_size'])) { echo intval ( $options['trendy_font_size'] ); } else { echo intval('13'); } ?>,
		       slide: function(event, ui) { 
		         jQuery(this).next().children('span').text(ui.value);
		         jQuery('#trendy_font_size').val(ui.value);
		         jQuery('#trendy_main_container .trendy_loading_text').css('font-size', ui.value+'px');
		    	} 
		    });


		    jQuery(".fill_loader_text_sizer").slider({
			   max: 150,
			   min: 15,
		   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['fill_loader_text_size'])) { echo intval ( $options['fill_loader_text_size'] ); } else { echo intval('13'); } ?>,
		       slide: function(event, ui) { 
		         jQuery(this).next().children('span').text(ui.value);
		         jQuery('#fill_loader_text_size').val(ui.value);
		         jQuery('#wppu-fill-loader-text h2').css('font-size', ui.value+'px');
		    	} 
		    });

		    jQuery(".object_loader_thumb_sizer").slider({
			   max: 500,
			   min: 200,
		   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['object_loader_thumb_size'])) { echo intval ( $options['object_loader_thumb_size'] ); } else { echo intval('100'); } ?>,
		       slide: function(event, ui) { 
		         jQuery(this).next().children('span').text(ui.value);
		         jQuery('#object_loader_thumb_size').val(ui.value);
		         jQuery('#wppu-object-wrapper').css({
		         	width: ui.value+'px',
		         	height: ui.value+'px'
		         });
		    	}
		    });

		    jQuery(".fill_loader_thumb_sizer").slider({
			   max: 500,
			   min: 50,
		   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['fill_loader_thumb_size'])) { echo intval ( $options['fill_loader_thumb_size'] ); } else { echo intval('100'); } ?>,
		       slide: function(event, ui) { 
		         jQuery(this).next().children('span').text(ui.value);
		         jQuery('#fill_loader_thumb_size').val(ui.value);
		         jQuery('#wppu-fill-loader-thunb').css('width', ui.value+'px');
		    	}
		    });

		    jQuery(".fill_progress_bar_heightr").slider({
			   max: 10,
		   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['fill_progress_bar_height'])) { echo intval ( $options['fill_progress_bar_height'] ); } else { echo intval('3'); } ?>,
		       slide: function(event, ui) { 
		         jQuery(this).next().children('span').text(ui.value);
		         jQuery('#fill_progress_bar_height').val(ui.value);
		         jQuery('.wppu_fill_progress_bar').css('height', ui.value+'px');
		    	}
		    });

		    jQuery(".fill_counter_text_sizer").slider({
			   max: 100,
		   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['fill_counter_text_size'])) { echo intval ( $options['fill_counter_text_size'] ); } else { echo intval('13'); } ?>,
		       slide: function(event, ui) { 
		         jQuery(this).next().children('span').text(ui.value);
		         jQuery('#fill_counter_text_size').val(ui.value);
		         jQuery('.wppu_fill_counter').css('font-size', ui.value+'px');
		    	}
		    });



		    <?php $options = wppu_main_options_default_settings(); if( !empty( $options['trendy_loading_text'] ) ) : ?>
		    jQuery('#trendy_main_container .trendy_loading_text').text("<?php echo $options['trendy_loading_text']; ?>");
			<?php endif; ?>

		});
		jQuery(document).ready(function () {
			jQuery(function(){
			    jQuery(".delay_timing").slider({
				   max: 10000,
			   	   value: <?php $BGSIZE = wppu_main_options_default_settings(); if(!empty($BGSIZE['delay_timing'])) { echo $BGSIZE['delay_timing']; } else { echo '500';} ?>,
			       slide: function(event, ui) { 
			         jQuery(this).next().children('span').text(ui.value);
			         jQuery('#delay_timing').val(ui.value);
			    	} 
			    });
			});

			jQuery(function(){
			    jQuery(".loading_out_timing").slider({
				   max: 10000,
			   	   value: <?php $BGSIZE = wppu_main_options_default_settings(); if(!empty($BGSIZE['loading_out_timing'])) { echo $BGSIZE['loading_out_timing']; } else { echo '600';} ?>,
			       slide: function(event, ui) { 
			         jQuery(this).next().children('span').text(ui.value);
			         jQuery('#loading_out_timing').val(ui.value);
			    	} 
			    });
			});
		});

		jQuery('#click_toggle_possition').toggle(function() {
			jQuery('#special_prev').closest('.pixiefy_single_column_container').css('position', 'inherit');
		    jQuery('#special_prev').animate({
		    	width: '100%',
		    	height: '100%',
		    	top: 0,
		    	left: 0
		    });
		     var fontSize = <?php $fontSize = wppu_main_options_default_settings(); if(!empty($fontSize['special_font_size'])) { echo $fontSize['special_font_size']; } else { echo '32';} ?> + 2+'vw';
		    jQuery('h2.wppu_special').css('font-size', fontSize);
		    jQuery(this).text('Back to small view');
		}, function() {
			jQuery('#special_prev').closest('.pixiefy_single_column_container').css('position', 'relative');
			jQuery('#special_prev').animate({
		    	width: '450px',
		    	height: '200px',
		    	top: '-30px',
		    	left: '25%'
		    });
		    jQuery(this).text('Click to Large view');
		   var fontSizet = <?php $fontSize = wppu_main_options_default_settings(); if(!empty($fontSize['special_font_size'])) { echo $fontSize['special_font_size']; } else { echo '32';} ?>+'em'
		    jQuery('h2.wppu_special').css('font-size', fontSizet);
		});
		jQuery(document).ready(function() {
			jQuery('#wpbody-content').css({
					'padding-bottom': '0'
				});

			jQuery('.plz_click img').each(function(i){
		        var imgSrc = this.src;
		        if(imgSrc.indexOf("<?php $mainIMG = wppu_main_options_default_settings(); if(!empty($mainIMG['package_selector'])) { echo esc_attr( $mainIMG['package_selector'] ); } else { echo plugin_dir_url( __FILE__ ) . 'images/Preloader_3.gif';} ?>") >= 0){
		            jQuery(this).parent().addClass('gif_activated');
		        }
			});
			if (jQuery('.gif_activated').parents('.tab_content').length) {
				var theID = jQuery('.gif_activated').parents('.tab_content').attr('id');
				jQuery("#schedule").val(theID).change();
			}
			<?php $options = wppu_main_options_default_settings(); ?>
			jQuery(".css_click[data-class='<?php echo $options['awesome_loading_selected']; ?>']").addClass('css_activated');
			jQuery(".trendy_click[data-class='<?php if( !empty($options['trendy_loading_style']) ) { echo $options['trendy_loading_style']; } else { echo 'trendy_fazer_man'; } ?>']").addClass('trendy_activated');
			jQuery(function(){
				jQuery( "#backgrondSize" ).slider({
				   max: 200,
				   value: <?php $BGSIZE = wppu_main_options_default_settings(); if(!empty($BGSIZE['background_size'])) { echo $BGSIZE['background_size']; } else { echo '32';} ?>,
				   slide: function(event, ui) { 
				     jQuery(this).next().children('span').text(ui.value);
				     jQuery('#background_sizer').val(ui.value);
				     jQuery('.wow_wppu_preview').css('background-size', ''+ui.value+'px auto');
					} 
				});
			});

			jQuery(function(){
			    jQuery(".special_font_sizer").slider({
				   max: 10,
			   	   value: <?php $BGSIZE = wppu_main_options_default_settings(); if(!empty($BGSIZE['special_font_size'])) { echo $BGSIZE['special_font_size']; } else { echo '8';} ?>,
			       slide: function(event, ui) { 
			         jQuery(this).next().children('span').text(ui.value);
			         jQuery('#special_font_size').val(ui.value);
			         jQuery('.wppu-logo-loader h2').css('font-size', ui.value+'em');
			    	} 
			    });
			});

			jQuery(function(){
			    jQuery(".3d_loader_font_sizer").slider({
				   max: 100,
				   min: 10,
			   	   value: <?php $BGSIZE = wppu_main_options_default_settings(); if(!empty($BGSIZE['3d_loader_font_size'])) { echo $BGSIZE['3d_loader_font_size']; } else { echo '30';} ?>,
			       slide: function(event, ui) { 
			         jQuery(this).next().children('span').text(ui.value);
			         jQuery('#3d_loader_font_size').val(ui.value);
			         jQuery('.wppu-3d-loader .wppu-3d-loader-item').css('font-size', ui.value+'px');
			    	} 
			    });
			});

			jQuery(function(){
			    jQuery(".awesome_font_sizer").slider({
				   max: 100,
			   	   value: <?php $options = wppu_main_options_default_settings(); if(!empty($options['awesome_font_size'])) { echo $options['awesome_font_size']; } else { echo '13';} ?>,
			       slide: function(event, ui) { 
			         jQuery(this).next().children('span').text(ui.value);
			         jQuery('#awesome_font_size').val(ui.value);
			         jQuery('.awesome_title_txt').css('font-size', ui.value+'px');
			    	} 
			    });
			});

			jQuery(function(){
				<?php 
				$options = get_option( 'wppu_display' );
				if( isset( $options['get_all_pages_ID'] ) ) :
				$all_pages_ID = $options['get_all_pages_ID'];
				?>
			    jQuery('<?php echo $all_pages_ID; ?>').attr('checked' , 'checked');
				<?php endif;
				if( isset( $options['get_all_post_type_list'] ) ) :
				$post_type_list = $options['get_all_post_type_list']; ?>
			    jQuery('<?php echo $post_type_list; ?>').attr('checked' , 'checked');
				<?php endif; ?>
			    if (jQuery('.all_pages_checkbox_group input.checkPages:checked').length == jQuery('.all_pages_checkbox_group input.checkPages').length) {
			       jQuery('#ckAllPages').attr('checked' , 'checked');
			    }
			    if (jQuery('.all_post_types_checkbox_group input.checkPosts:checked').length == jQuery('.all_post_types_checkbox_group input.checkPosts').length) {
			       jQuery('#ckAllPosts').attr('checked' , 'checked');
			    }
			});
		});
	</script>

	</div>

	<?php
}

function wppu_all_field_validate( $input ) {

	// Create our array for storing the validated options
	$output = array();
	
	// Loop through each of the incoming options
	foreach( $input as $key => $value ) {
		
		// Check to see if the current option has a value. If so, process it.
		if( isset( $input[$key] ) ) {
		
			// Strip all HTML and PHP tags and properly handle quoted strings
			$output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
			
		} // end if
		
	} // end foreach

	// The sample textarea must be safe text with the allowed tags for posts
	if ( isset( $input['custom_wppu_markup'] ) && ! empty( $input['custom_wppu_markup'] ) ) {
		$output['custom_wppu_markup'] = addslashes( wp_kses( stripslashes( $input['custom_wppu_markup'] ), array(
			'a' => array(
				'href' => array(),
				'title' => array()
			),
			'script' => array(
				'src' => array(),
				'type' => array()
			),
			'div' => array(
				'class' => array(),
				'id' => array()
			),
			'span' => array(
				'class' => array(),
				'id' => array()
			),
			'br' => array(),
			'em' => array(),
			'strong' => array(),
		) ) );
	}
		

	// The sample textarea must be safe text with the allowed tags for posts
	if ( isset( $input['wppu_trendy_markup'] ) && ! empty( $input['wppu_trendy_markup'] ) )
		$output['wppu_trendy_markup'] = trim( $input['wppu_trendy_markup'] );

	// The sample textarea must be safe text with the allowed tags for posts
	if ( isset( $input['custom_wppu_css'] ) && ! empty( $input['custom_wppu_css'] ) )
		$output['custom_wppu_css'] = trim( $input['custom_wppu_css'] );

	// The sample textarea must be safe text with the allowed tags for posts
	if ( isset( $input['custom_wppu_js'] ) && ! empty( $input['custom_wppu_js'] ) )
		$output['custom_wppu_js'] = trim( wp_json_encode( $input['custom_wppu_js'] ) );
	
	// Return the array processing any additional functions filtered by this action	
	return apply_filters( 'wppu_all_field_validate', $output, $input );
}

endif;  // EndIf is_admin()
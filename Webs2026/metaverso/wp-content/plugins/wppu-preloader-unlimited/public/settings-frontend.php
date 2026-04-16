<?php 

$main = get_option( 'wppu_display' );
$mainIMG = get_option( 'wppu_display' );
$mainCSS = get_option( 'wppu_display' );
$mainspecial = get_option( 'wppu_display' );
$pace = get_option( 'wppu_display' );

function wppu_addData_defaul__to_frontend() { 
	echo '<div class="wppu--loader wppu_preloader__default_element"></div>';
}

function wppu_object_loader_to_frontend() { 

	$object_data = get_option( 'wppu_display' );
	$thumbnail = '<img src="https://upload.wikimedia.org/wikipedia/en/thumb/2/22/Heckert_GNU_white.svg/1200px-Heckert_GNU_white.svg.png" alt="" />';
	if( isset($object_data['object_loader_thumb']) && !empty($object_data['object_loader_thumb']) ) {
		$thumbnail = '<img src="'.esc_url( $object_data['object_loader_thumb'] ).'" alt="" />';
	}

	$object_bg = "";
	$object_size = "";
	$object_color = "";

	if( isset($object_data['object_wrapper_bg']) && !empty($object_data['object_wrapper_bg']) ) {
		$object_bg =  'style="background-color: '.$object_data['object_wrapper_bg'].';"';
	}
	if( isset($object_data['object_loader_thumb_size']) && !empty($object_data['object_loader_thumb_size']) ) {
		$object_size =  'style="width: '.$object_data['object_loader_thumb_size'].'px; height: '.$object_data['object_loader_thumb_size'].'px;"';
	}
	if( isset($object_data['object_main_color']) && !empty($object_data['object_main_color']) ) {
		$object_color =  'style="color: '.$object_data['object_main_color'].';"';
	}

	if( !empty($object_data['object_loader_thumb_size']) || !empty($object_data['object_wrapper_bg']) )
	echo '<div class="object-wppu-preview wppu--loader" '.$object_bg.'><div id="wppu-object-wrapper" '.$object_size.'><div class="wppu-object-logo">'.$thumbnail.'</div><div class="wpppu-object-wrap" '.$object_color.'></div></div></div>';
}

function wppu_default_preload_css() {

	$options = get_option( 'wppu_display' );

	$mainIMG = get_option( 'wppu_display' );
	if (empty($mainIMG["package_selector"])) {
		$img = plugin_dir_url( __FILE__ ) . 'images/Preloader_3.gif';
	} else {
		$img = esc_attr( $mainIMG["package_selector"] );
	}
	if (empty($mainIMG["package_selector"])) {
		$bgColor = '#FFFFFF';
	} else {
		$bgColor = esc_attr( $mainIMG["background_color"] );
	}
	$defaultImg = plugin_dir_url( __FILE__ ) . 'images/Preloader_3.gif';
	if( isset($mainIMG["background_size_selector"]) && $mainIMG["background_size_selector"] == 'custom') {
		$bgSie = esc_attr( $mainIMG["background_size"] ).'px auto';
	} elseif( isset($mainIMG["background_size_selector"]) && $mainIMG["background_size_selector"] == 'auto') {
		$bgSie = 'auto auto';
	} elseif (empty($main['background_size_selector'])) {
		$bgSie = 'auto auto';
	}


	echo '<style type="text/css">
	.wppu_preloader__default_element {
		background-color: '.$bgColor.';
		background-image: url("'.$img.'");
		background-size: '.$bgSie.';
	}
	</style>';
	
}


function wppu_addData_cssOptions__to_frontend() { 
	$csseff = get_option( 'wppu_display' );

	$effect = (!empty($csseff['effect_options'])) ? $csseff['effect_options'] : 'bounce';
	$text = (!empty($csseff['input_example'])) ? $csseff['input_example'] : 'Please Wait...';
	$bg = (!empty($csseff['preview_backgroundColor'])) ? $csseff['preview_backgroundColor'] : '#FFFFFF';
	$color = (!empty($csseff['preview_mainColor'])) ? $csseff['preview_mainColor'] : '#000000';

	?>
	<!-- WPPU -->
	<div class="wppu--loader" id="main_css_preoad_man" data-effect="<?php echo $effect; ?>" data-text="<?php echo $text; ?>" data-bg="<?php echo $bg; ?>" data-color="<?php echo $color; ?>">
		<div class="css_preloader_activated">
			<div id="css_preoaders_wppu"></div>
		</div>
	</div>
	<!-- end of WPPU -->
	<?php
}

function wppu_cssOptions_preload_css() { 
	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	$bg = get_option( 'wppu_display' );
	if(!empty($bg['preview_backgroundColor'])) { 
		$bgvalue =  $bg['preview_backgroundColor']; 
	} else {
	 $bgvalue = '#FFFFFF';
	}	
	echo '<style type="text/css">
			#main_css_preoad_man {
				background-color: '.$bgvalue.';
				bottom: 0;
				left: 0;
				position: fixed;
				right: 0;
				top: 0;
				z-index: 999999;
			}
			.waitMe_container {
				margin: auto;
				position: relative;
				top: 50%;
			}
			.css_preloader_activated {
				height: 100%;
				position: absolute;
				width: 100%;
			}
		</style>';

}

function wppu_css_options_active_script_style() {
	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	wp_enqueue_style( 'wppu-waitMe', plugin_dir_url( __FILE__ ) . 'css/waitMe.min.css' );
	wp_enqueue_script( 'wppu-waitMe', plugin_dir_url( __FILE__ ) . 'js/waitMe.min.js', array( 'jquery' ), '', true );
}

function wppu_special_options_markup_frontend() { 

	$mainspecial = get_option( 'wppu_display' );
	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));
	?>
	<!-- wppu Preloader -->
	<section class="wppu-preloader loading not-loaded" style="background-color:<?php if(!empty($mainspecial['special_background'])) {echo $mainspecial['special_background']; } else { echo '#009999'; } ?>;">
	    <div class="wppu-logo-loader wppu_frameT">
	    	<div class="wppu_frameTC">
		        <div class="wppu_special_preloader">
		            <h2 class="wppu_special" style="font-size:<?php if(!empty($mainspecial['special_font_size'])) {echo $mainspecial['special_font_size'].'em'; } else { echo '8em'; } ?>;">
		                <?php 
		                $mainspecial = get_option( 'wppu_display' );
		                if(!empty($mainspecial['special_text'])) {
		                	$val = $mainspecial['special_text'];
		                } else {
		                	$val = 'WPPU';
		                }
		                if(!empty($mainspecial['special_color'])) {
		                	$sColor = $mainspecial['special_color'];
		                } else {
		                	$sColor = '#FFFFFF';
		                }
		                
		                $str = $val;
		                for ($i = 0; $i < strlen($str); ++$i) {
		                	if (strlen(trim( $str[$i] )) == 0) {
		                		$string = '&nbsp;';
		                	} else {
		                		$string = $str[$i];
		                	}
		                   echo '<span class="span-'.$i.'">
		                           <p class="t-p">'.$string.'</p>
		                           <p class="h-p" style="color:'.$sColor.'">'.$string.'</p>
		                        </span>';
		                    }
		                 ?>
		            </h2>
		        </div>
	        </div>
	    </div>
	</section>
	<!-- end of preloader -->
	<?php

}



function wppu_threed_options_markup_frontend() { 
	$options = get_option( 'wppu_display' );
	if(!empty($options['3d_loader_animate_style'])) {
    	$lclass = $options['3d_loader_animate_style'];
    } else {
    	$lclass = 'wppu-3d-loader--flipDelay wppu-3d-loader--3d';
    }
	?>
	<!-- wppu Preloader -->
	<section class="wppu--loader wppu-preloader loading not-loaded" style="background-color:<?php if(!empty($options['3d_loader_background_con'])) {echo $options['3d_loader_background_con']; } else { echo '#FFFFFF'; } ?>;">
	    <div class="wppu-logo-loader wppu_frameT">
	    	<div class="wppu_frameTC">
		        <div class="wppu_special_preloader">
		            <div id="wppu_3d_loader_body" class="wppu-3d-loader <?php echo $lclass; ?>">
		                <?php 
		                $options = get_option( 'wppu_display' );
		                if(!empty($options['3d_loader_text'])) {
		                	$val = $options['3d_loader_text'];
		                } else {
		                	$val = 'WPPU';
		                }
		                if(!empty($options['3d_loader_color'])) {
		                	$sColor = $options['3d_loader_color'];
		                } else {
		                	$sColor = '#FFFFFF';
		                }
		                if(!empty($options['3d_loader_background'])) {
		                	$bgColor = $options['3d_loader_background'];
		                } else {
		                	$bgColor = '#009999';
		                }
		                if(!empty($options['3d_loader_font_size'])) {
		                	$fsize = $options['3d_loader_font_size'];
		                } else {
		                	$fsize = '30';
		                }

		                $str = strtoupper($val);
		                for ($i = 0; $i < strlen($str); ++$i) {
		                   echo '<span style="background-color: ' . $bgColor . ';font-size: ' . $fsize . 'px; color: ' . $sColor . ';" class="wppu-3d-loader-item">'.$str[$i].'</span>';
		                    }
		                 ?>
		            </div>
		        </div>
	        </div>
	    </div>
	</section>
	<!-- end of preloader -->
	<?php

}


function wppu_threed_options_style_frontend() {
	wp_enqueue_style( 'wppu-threed-css', plugin_dir_url( __FILE__ ) . 'css/threed.min.css' );
}



function custom_preloader_options_markup_js__forntend(){

	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	$main = get_option( 'wppu_display' );

	$js_pos = isset( $main['wppu_custom_js_pos'] ) ? $main['wppu_custom_js_pos'] : 'after';

	if ( 'before' === $js_pos && !empty( $main['custom_wppu_js'] ) ) {
			$cusomScript = stripslashes( $main['custom_wppu_js'] );
			$exclude_js = (isset($main['exclude_js_from_plugins']) && !empty($main['exclude_js_from_plugins'])) ? $main['exclude_js_from_plugins'] : '';
			if ( !empty($exclude_js) && 'yes' === $exclude_js ) {
				echo '<!--noptimize--><script>'.json_decode( $main['custom_wppu_js'] ).'</script><!--/noptimize-->';
			} else {
				echo '<script>'.json_decode( $main['custom_wppu_js'] ).'</script>';
			}
	}
	
	if ( !empty( $main['custom_wppu_markup'] ) ) {
		echo '<div class="wppu-preloader wppu--loader wppu-custom-preloader-mark">
			'.wp_unslash( $main['custom_wppu_markup'] ).'
		  </div>';
	}
	if ( 'after' === $js_pos && !empty( $main['custom_wppu_js'] ) ) {
		$cusomScript = stripslashes( $main['custom_wppu_js'] );
		$exclude_js = (isset($main['exclude_js_from_plugins']) && !empty($main['exclude_js_from_plugins'])) ? $main['exclude_js_from_plugins'] : '';
		if ( !empty($exclude_js) && 'yes' === $exclude_js ) {
			echo '<!--noptimize--><script>'.json_decode( $main['custom_wppu_js'] ).'</script><!--/noptimize-->';
		} else {
			echo '<script>'.json_decode( $main['custom_wppu_js'] ).'</script>';
		}
		
}

}

function custom_preloader_options_css__forntend() {
	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	$main = get_option( 'wppu_display' );
	$cusomStyle = stripslashes( $main['custom_wppu_css'] );
	echo '<style type="text/css">
			'.$cusomStyle.'
		  </style>';

}


function apply_extra_css_to_wppu() {

	$main = get_option( 'wppu_display' );
	if( isset( $main['custom_wppu_extra_css'] ) ) {
		$cusomStyle = stripslashes( $main['custom_wppu_extra_css'] );
		if ( $cusomStyle ) {
			echo '<style type="text/css">
				'.$cusomStyle.'
			</style>';
		}
	}

}


function wppu_pace_style_add__frontend() {

	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	$pace = get_option( 'wppu_display' );
	if(!empty($pace['pace_background'])) { 
		$bgvalue =  $pace['pace_background']; 
		echo '<style type="text/css">
			#pace_wow {
			  background-color: '.$bgvalue.';
			}
		</style>';
	} elseif(!empty($pace['pace_background']) && $pace['pace_background'] == 'transparent') {
		echo '<style type="text/css">
			#pace_wow {
			  display:none;
			}
		</style>';
	} else {
		$bgvalue = '#FFFFFF';
		echo '<style type="text/css">
			#pace_wow {
			  background-color: '.$bgvalue.';
			}
		</style>';
	}
	if(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'minimal' && !empty($pace['pace_color'])  ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'flash' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  	.pace .pace-activity {
			  		border-top-color: '.$pace['pace_color'].';
						border-left-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'barber-shop' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'fill-left' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'big-counter' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress:after {
			  		color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'bounce' ) { 
		echo '<style type="text/css">
			  	.pace .pace-activity {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'loading-bar' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  		color: '.$pace['pace_color'].';
			  	}
			  	.pace .pace-activity {
				  box-shadow: inset 0 0 0 2px '.$pace['pace_color'].', inset 0 0 0 7px #FFF;
				}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'center-circle' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'center-atom' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress:before {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  	.pace .pace-activity {
			  		border-color: '.$pace['pace_color'].';
			  	}
			  	.pace .pace-activity:after {
			  		border-color: '.$pace['pace_color'].';
			  	}
			  	.pace .pace-activity:before {
			  		border-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'center-radar' ) { 
		echo '<style type="text/css">
			  	.pace .pace-activity {
			  		border-color: '.$pace['pace_color'].' transparent transparent;
			  	}
			  	.pace .pace-activity:before {
			  		border-color: '.$pace['pace_color'].' transparent transparent;
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'corner-indicator' ) { 
		echo '<style type="text/css">
			  	.pace .pace-activity {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	} elseif(!empty($pace['pace_effects']) && $pace['pace_effects'] == 'center-simple' ) { 
		echo '<style type="text/css">
			  	.pace .pace-progress {
			  		background-color: '.$pace['pace_color'].';
			  	}
			  </style>';
	}

}

function wppu_pace_markup_add__frontend() {
	echo '<div id="pace_wow"></div><br />';
}

function wppu_awesome_css_options_files() {

	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	$class = $options['awesome_loading_selected'];
	$class = str_replace( 'la-', '', $class);

	wp_enqueue_style( 'wppu-awesome-css', plugin_dir_url( __FILE__ ) . 'css/awesome/'.$class.'.css' );

}

function wppu_awesome_css_options_markup_frontedn() {

	$options = get_option( 'wppu_display' );
	$pages_IDS = $options['get_all_pages'];
	$pageArray = array_map('trim', explode(',', $pages_IDS));

	$options = get_option( 'wppu_display' );
	$bgC = $options['awesome_mainColor'];
	if($options['awesome_loading_selected'] == 'la-ball-atom') {
		echo '<style type="text/css">
				.item-generate.la-ball-atom div:nth-child(1) {
					background-color: '.$bgC.';
				}
			  </style>';
	}
	?>
	<div class="wppu--loader" id="awesome_preloader_body" style="background-color:<?php echo $options['awesome_background']; ?>">
		<div class="awesome_prelader_item">
	        <div class="awesome_prelader_table">
	            <div class="awesome_prelader_table_cell">
	                <div class="awesome-item-loader-container">
	                    <div class="<?php echo $options['awesome_loading_selected']; ?> la-2x item-generate" style="color:<?php echo $options['awesome_mainColor']; ?>">
	                        <?php 
	                        $val = $options['awesome_selectedcounter'];
	                        for ($i = 1; $i <= $val; ++$i) {
				        		echo '<div></div>';
				        	} ?>
	                    </div>
	                </div>
	            </div>
	            <div class="item-title awesome_title_txt" style="color:<?php echo $options['awesome_textcolor']; ?>; font-size:<?php echo $options['awesome_font_size']; ?>px"><?php echo $options['awesome_loading_text']; ?></div>
	        </div>
	    </div>
	</div>
	<?php

} // end of awesome css markup



/* Adding Latest jQuery from Wordpress */
function wpp_front_init_js() {
	wp_enqueue_script('jquery');

	$main = get_option( 'wppu_display' );

	$options_type = (!empty($main['sample_radio_buttons'])) ? $main['sample_radio_buttons'] : 'image_radio_options';
	$effect = (!empty($main['animation_effects'])) ? $main['animation_effects'] : 'fadeOut';
	$delay = (!empty($main['delay_timing'])) ? (int)$main['delay_timing'] : 200;
	$load_type = (isset($main['preloader_load_type']) && !empty($main['preloader_load_type'])) ? $main['preloader_load_type'] : "window";
	$load_time = (!empty($main['custom_load_time'])) ? (int)$main['custom_load_time'] : 1500;
	$transition = (!empty($main['loading_out_timing'])) ? (int)$main['loading_out_timing'] : 400;
	

	$dependency = array(
		'jquery'
	);

	$jqueryui_effects = array(
		'blind',
		'bounce',
		'clip',
		'drop',
		'explode',
		'fold',
		'highlight',
		'pulsate',
		'scale',
		'shake'
	);
	if (in_array($effect, $jqueryui_effects)) {
		$dependency[] = "jquery-effects-$effect";
		wp_enqueue_script("jquery-effects-$effect");
	}

	$script_name = 'wppu-init';
	if ( $options_type == 'css_radio_options' ) {
		$dependency[] = "wppu-waitMe-js";
		wp_enqueue_style( 'wppu-waitMe', plugin_dir_url( __FILE__ ) . 'css/waitMe.min.css' );
		wp_enqueue_script( 'wppu-waitMe-js', plugin_dir_url( __FILE__ ) . 'js/waitMe.min.js', array( 'jquery' ), '', false );
	} else if ( $options_type == 'fill_radio_options' ) {
		$fill_name = (!empty($main['animation_effects'])) ? $main['animation_effects'] : 'fadeOut';
		$script_name = "fill/fill-".$fill_name;
	} else if ( $options_type == 'special_radio_options' ) {
		$special_name = (!empty($main['animation_effects'])) ? $main['animation_effects'] : 'fadeOut';
		$script_name = "special/special-".$special_name;
	} else if ( $options_type == 'pace_radio_options' ) {
		
		$pace = get_option( 'wppu_display' );
		$paceActive = $main["pace_effects"];
		if (empty($main["pace_effects"])) {
			$paceCss = plugin_dir_url( __FILE__ ) . 'css/pace/pace-theme-center-atom.css';
		} else {
			$paceCss = plugin_dir_url( __FILE__ ).'css/pace/pace-theme-'.$paceActive.'.css';
		}
		wp_enqueue_style( 'wppu-pace-css', $paceCss, array(), '', false );
		wp_enqueue_script( 'wppu-pace-js', plugin_dir_url( __FILE__ ) . 'js/pace.min.js' ,array(), '', false );
		$dependency[] = "wppu-pace-js";
		$script_name = 'wppu-pace-init';

	}  else if ( $options_type == 'object_radio_options' ) {
		wp_enqueue_style( 'wppu-object-css', plugin_dir_url( __FILE__ ) . 'css/object-loader.css', array(), '', false );
	}

	wp_enqueue_script( "wppu-frontend", plugin_dir_url( __FILE__ ) . "js/$script_name.js" , $dependency, "", true );

	wp_localize_script( 'wppu-frontend', 'wppreloaderData', array(
		'options_type' => $options_type,
		'effect' => $effect,
		'delay' => $delay,
		'load_type' => $load_type,
		'load_time' => $load_time,
		'transition' => $transition
	));
}
add_action('wp_enqueue_scripts', 'wpp_front_init_js');

function wppu_add_footer_scripts(){
$main = get_option( 'wppu_display' );
$before = ( isset($main['before_server_render']) && !empty($main['before_server_render'])) ? $main['before_server_render'] : 'disable';
$excludes = ( isset($main['exclude_parents']) && !empty($main['exclude_parents'])) ? $main['exclude_parents'] : '';
if( !empty( $excludes ) ) {
	$excludes = array_map('trim', explode(',', $excludes));
}
if( 'enable' == $before ) :
?>
<!--noptimize-->
<script>
/* <![CDATA[ */
jQuery(document).ready(function(){
	jQuery('a:not([href^="#"]):not([target="_blank"])').on('click', function(e){
		if( 
			!jQuery(this).closest('#wpadminbar').length
			<?php if( !empty( $excludes ) ) {
			foreach( (array) $excludes as $exclude ) { ?>
			&& !jQuery(this).closest('<?php echo $exclude; ?>').length
			<?php }
			} ?>
		) {
			jQuery('.wppu--loader').show();
		}
	});
});
/* ]]> */
</script>
<!--/noptimize-->
<?php
endif;
}
add_action('wp_footer', 'wppu_add_footer_scripts');

function trendy_preloader_options_css__forntend() {
	
	$options = wppu_main_options_default_settings();

	$trendy_background 		= $options['trendy_background'];
	$trendy_textcolor  		= $options['trendy_textcolor'];
	$trendy_font_size 		= $options['trendy_font_size'];
	$trendy_loading_text 	= $options['trendy_loading_text'];
	$trendy_loading_style 	= $options['trendy_loading_style'];

	$wppu_trendy_markup = stripslashes( $options['wppu_trendy_markup'] );

	ob_start(); ?>
	<style type="text/css">
	<?php if( !empty( $trendy_background ) ) : ?>
	.wppu-trendy-frontend-body,
	.finger-loading .last-finger-loader-item i::after {
		background-color: <?php echo $trendy_background; ?>;
	}
	<?php endif; ?>
	h2.trendy_loading_text {
		<?php if( !empty( $trendy_font_size ) ) : ?>
		font-size: <?php echo $trendy_font_size; ?>px;
		<?php endif; if( !empty( $trendy_textcolor ) ) : ?>
		color: <?php echo $trendy_textcolor; ?> !important;
		<?php endif; ?>
	}
	.wppu-trendy-frontend-body {
	  bottom: 0;
	  left: 0;
	  position: fixed;
	  right: 0;
	  top: 0;
	  z-index: 999999;
	  text-align: center;
	}
	.wppu-trendy-frontend-inner {
		height: 100%;
	}
	.wppu-trendy-frontend-body .item-inner {
	  align-items: center;
	  display: flex;
	  height: 100%;
	  justify-content: center;
	  position: relative;
	  width: 100%;
	}
	.wppu_trendy_block {
	  width: 100%;
	}
	<?php $trendy_loading_style = $options['trendy_loading_style']; if ( "trendy_fazer_man" == $trendy_loading_style ) { ?>

		.wppu-trendy-loader-body {
		  position: absolute;
		  top: 50%;
		  margin-left: -50px;
		  left: 50%;
		  animation: speeder .4s linear infinite;
		}

		.wppu-trendy-loader-body > span {
		  height: 5px;
		  width: 35px;
		  background: #000;
		  position: absolute;
		  top: -19px;
		  left: 60px;
		  border-radius: 2px 10px 1px 0;
		}

		.fazer_base span {
		  position: absolute;
		  width: 0;
		  height: 0;
		  border-top: 6px solid transparent;
		  border-right: 100px solid #000;
		  border-bottom: 6px solid transparent;
		}

		.fazer_base span:after {
		  content: "";
		  height: 22px;
		  width: 22px;
		  border-radius: 50%;
		  background: #000;
		  position: absolute;
		  right: -110px;
		  top: -16px;
		}

		.fazer_base span:before {
		  content: "";
		  position: absolute;
		  width: 0;
		  height: 0;
		  border-top: 0 solid transparent;
		  border-right: 55px solid #000;
		  border-bottom: 16px solid transparent;
		  top: -16px;
		  right: -98px;
		}

		.fazer_face {
		  position: absolute;
		  height: 12px;
		  width: 20px;
		  background: #000;
		  border-radius: 20px 20px 0 0;
		  transform: rotate(-40deg);
		  right: -125px;
		  top: -15px;
		}

		.fazer_face:after {
		  content: "";
		  height: 12px;
		  width: 12px;
		  background: #000;
		  right: 4px;
		  top: 7px;
		  position: absolute;
		  transform: rotate(40deg);
		  transform-origin: 50% 50%;
		  border-radius: 0 0 0 2px;
		}
		.trendy_fazer_man .trendy_loading_text {
		  margin-top: 130px;
		}
		.wppu-trendy-loader-body > span > span:nth-child(1),
		.wppu-trendy-loader-body > span > span:nth-child(2),
		.wppu-trendy-loader-body > span > span:nth-child(3),
		.wppu-trendy-loader-body > span > span:nth-child(4) {
		  width: 30px;
		  height: 1px;
		  background: #000;
		  position: absolute;
		  animation: fazer1 .2s linear infinite;
		}

		.wppu-trendy-loader-body > span > span:nth-child(2) {
		  top: 3px;
		  animation: fazer2 .4s linear infinite;
		}

		.wppu-trendy-loader-body > span > span:nth-child(3) {
		  top: 1px;
		  animation: fazer3 .4s linear infinite;
		  animation-delay: -1s;
		}

		.wppu-trendy-loader-body > span > span:nth-child(4) {
		  top: 4px;
		  animation: fazer4 1s linear infinite;
		  animation-delay: -1s;
		}

		@keyframes fazer1 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -80px;
		    opacity: 0;
		  }
		}
		@keyframes fazer2 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -100px;
		    opacity: 0;
		  }
		}
		@keyframes fazer3 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -50px;
		    opacity: 0;
		  }
		}
		@keyframes fazer4 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -150px;
		    opacity: 0;
		  }
		}
		@keyframes speeder {
		  0% {
		    transform: translate(2px, 1px) rotate(0deg);
		  }
		  10% {
		    transform: translate(-1px, -3px) rotate(-1deg);
		  }
		  20% {
		    transform: translate(-2px, 0px) rotate(1deg);
		  }
		  30% {
		    transform: translate(1px, 2px) rotate(0deg);
		  }
		  40% {
		    transform: translate(1px, -1px) rotate(1deg);
		  }
		  50% {
		    transform: translate(-1px, 3px) rotate(-1deg);
		  }
		  60% {
		    transform: translate(-1px, 1px) rotate(0deg);
		  }
		  70% {
		    transform: translate(3px, 1px) rotate(-1deg);
		  }
		  80% {
		    transform: translate(-2px, -1px) rotate(1deg);
		  }
		  90% {
		    transform: translate(2px, 1px) rotate(0deg);
		  }
		  100% {
		    transform: translate(1px, -2px) rotate(-1deg);
		  }
		}
		.longfazers {
		  position: absolute;
		  width: 100%;
		  height: 100%;
		  left: 0;
		  top: 0;
		}

		.longfazers span {
		  position: absolute;
		  height: 2px;
		  width: 20%;
		  background: #000;
		}

		.longfazers span:nth-child(1) {
		  top: 20%;
		  animation: lf .6s linear infinite;
		  animation-delay: -5s;
		}

		.longfazers span:nth-child(2) {
		  top: 40%;
		  animation: lf2 .8s linear infinite;
		  animation-delay: -1s;
		}

		.longfazers span:nth-child(3) {
		  top: 60%;
		  animation: lf3 .6s linear infinite;
		}

		.longfazers span:nth-child(4) {
		  top: 80%;
		  animation: lf4 .5s linear infinite;
		  animation-delay: -3s;
		}

		@keyframes lf {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -200%;
		    opacity: 0;
		  }
		}
		@keyframes lf2 {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -200%;
		    opacity: 0;
		  }
		}
		@keyframes lf3 {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -100%;
		    opacity: 0;
		  }
		}
		@keyframes lf4 {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -100%;
		    opacity: 0;
		  }
		}	
	<?php } elseif( "trendy_tape_loader" == $trendy_loading_style ) { ?>

		.wppu-trendy-tape {
		    margin: 0 auto;
		    width: 100px;
		    height: 30px;
		    border: 1px solid #333;
		    border: none;
		    overflow: hidden;
		    position: relative;
		    background: rgba(0, 0, 0, .3);
		    border-radius: 5px;
		    box-shadow: 0px 35px 0 -5px #333, 0 -5px 0 0px #232323, 0 -25px 0 -5px #ccc, -25px -30px 0 0px #232323, -25px 30px 0 0px #232323, 25px -30px 0 0px #232323, 25px 30px 0 0px #232323, 20px 10px 0 5px #232323, 20px -10px 0 5px #232323, -20px -10px 0 5px #232323, -20px 10px 0 5px #232323;
		    animation: tape2 1.5s linear infinite alternate;
		}
		.trendy_tape_loader .trendy_loading_text {
		  margin-bottom: 45px;
		}
		.wppu-trendy-tape:after,
		.wppu-trendy-tape:before {
		    content: "";
		    border-radius: 100%;
		    width: 38px;
		    height: 38px;
		    display: block;
		    position: absolute;
		    border: 5px dashed #fff;
		    margin: -9px auto;
		    top: 0;
		    bottom: 0;
		    transform: rotate(0deg) scale(.4);
		    animation: tape 4s linear infinite;
		}

		.wppu-trendy-tape:after {
		    left: -10px;
		    box-shadow: 0 0 0 4px #fff, 0 0 0 65px #000;
		}

		.wppu-trendy-tape:before {
		    right: -10px;
		    box-shadow: 0 0 0 4px #fff, 0 0 0 35px #000;
		}

		@keyframes tape {
		    0% {
		        transform: rotate(0deg) scale(.4);
		    }
		    100% {
		        transform: rotate(-360deg) scale(.4);
		    }
		}

		@keyframes tape2 {
		    0% {
		        transform: perspective(500px) rotateX(0deg) rotateY(-20deg) rotateZ(0deg);
		    }
		    100% {
		        transform: perspective(500px) rotateX(0deg) rotateY(20deg) rotateZ(0deg);
		    }
		}
	<?php } elseif( "trendy_gear_loader" == $trendy_loading_style ) { ?>

		.wppu-gear {
		    width: 80px;
		    height: 80px;
		    border-radius: 10%;
		    margin: 0 auto;
		    border: 5px solid #fff;
		    position: relative;
		    box-shadow: 0 10px 0 0 rgba(0, 0, 0, .1);
		}
		.wppu-gear:after {
		    content: "";
		    width: 100px;
		    height: 100px;
		    border-radius: 100%;
		    display: block;
		    border: 5px dashed #fff;
		    position: absolute;
		    top: -24px;
		    left: -24px;
		    box-shadow: inset 0px 0 0 20px #fff;
		    transform: scale(.5) rotate(0deg);
		    animation: wppu-gear 6s linear infinite;
		}

		.wppu-gear:before {
		    content: "";
		    width: 58px;
		    height: 58px;
		    border-radius: 100%;
		    display: block;
		    border: 5px dashed #fff;
		    position: absolute;
		    top: 26px;
		    right: -14px;
		    box-shadow: inset 0px 0 0 10px #fff;
		    transform: scale(.5) rotate(0deg);
		    animation: wppu-gear2 4s linear infinite;
		}
		.trendy_gear_loader h2.trendy_loading_text {
		  margin-bottom: 10px;
		}

		@keyframes wppu-gear {
		    0% {
		        transform: scale(.5) rotate(0deg);
		    }
		    100% {
		        transform: scale(.5) rotate(360deg);
		    }
		}

		@keyframes wppu-gear2 {
		    0% {
		        transform: scale(.5) rotate(0deg);
		    }
		    100% {
		        transform: scale(.5) rotate(-360deg);
		    }
		}
	<?php } elseif( "trendy_clock_loader" == $trendy_loading_style ) { ?>

		.wppu-trendy-clock {
		    width: 80px;
		    height: 80px;
		    border-radius: 100%;
		    background: #333;
		    margin: 10px auto;
		    border: 5px solid #fff;
		    position: relative;
		    box-shadow: 0 5px 0 0 rgba(0, 0, 0, .3), inset 0 5px 0 0 rgba(0, 0, 0, .3), inset 0 0 0 34px #c33;
		}

		.wppu-trendy-clock:after {
		    content: "";
		    width: 5px;
		    height: 60px;
		    display: block;
		    position: absolute;
		    top: 0;
		    bottom: 0;
		    left: 0;
		    right: 0;
		    margin: auto;
		    box-shadow: inset 0px 2px 0 0 #fff, inset 0px 30px 0 0 #333;
		    animation: clock .5s linear infinite;
		}

		.wppu-trendy-clock:before {
		    content: "";
		    width: 5px;
		    height: 50px;
		    display: block;
		    position: absolute;
		    top: 0;
		    bottom: 0;
		    left: 0;
		    right: 0;
		    margin: auto;
		    box-shadow: inset 0px 2px 0 0 #fff, inset 0px 25px 0 0 #333;
		    animation: clockb 6s linear infinite;
		}

		.trendy_clock_loader h2.trendy_loading_text {
		  margin-bottom: 15px;
		}

		@keyframes clock {
		    100% {
		        transform: rotate(360deg);
		    }
		}

		@keyframes clockb {
		    100% {
		        transform: rotate(360deg);
		    }
		}
	<?php } elseif( "trendy_clock2_loader" == $trendy_loading_style ) { ?>

		.wppu-trendy-clock2 {
		    width: 80px;
		    height: 80px;
		    border-radius: 100%;
		    background: ;
		    margin: 10px;
		    border: 5px solid transparent;
		    position: relative;
		    box-shadow: 30px -30px 0 -25px #222, -30px -30px 0 -25px #222, 20px 35px 0 -35px #222, 25px 45px 0 -40px #222, -20px 35px 0 -35px #222, -25px 45px 0 -40px #222, inset 0 0 0 3px #fff, inset 0 6px 0 0 rgba(0, 0, 0, .3), inset 0 0 0 35px #09c, inset 0 0 0 40px #222;
		}

		.wppu-trendy-clock2:after {
		    content: "";
		    width: 5px;
		    height: 60px;
		    display: block;
		    position: absolute;
		    top: 0;
		    bottom: 0;
		    left: 0;
		    right: 0;
		    margin: auto;
		    box-shadow: inset 0px 2px 0 0 #fff, inset 0px 30px 0 0 #222;
		    animation: clock .5s linear infinite;
		}

		.wppu-trendy-clock2:before {
		    content: "";
		    width: 5px;
		    height: 50px;
		    display: block;
		    position: absolute;
		    top: 0;
		    bottom: 0;
		    left: 0;
		    right: 0;
		    margin: auto;
		    box-shadow: inset 0px 2px 0 0 #fff, inset 0px 25px 0 0 #222;
		    animation: clockb 6s linear infinite;
		}

		@keyframes clock {
		    100% {
		        transform: rotate(360deg);
		    }
		}

		@keyframes clockb {
		    100% {
		        transform: rotate(360deg);
		    }
		}

		.wppu-trendy-clock,
		.wppu-trendy-clock2 {
		    display: inline-block
		}
	<?php } elseif( "trendy_finger_loader" == $trendy_loading_style ) { ?>
				
		.finger-loading {
		  position: absolute;
		  left: 50%;
		  top: 50%;
		  margin: -35px 0 0 -56px;
		  width: 112px;
		  height: 70px;
		  *zoom: 1;
		}
		.finger-loading:before,
		.finger-loading:after {
		  display: table;
		  content: "";
		}
		.finger-loading:after {
		  clear: both;
		}
		.finger-loading .finger-loader {
		  float: left;
		  margin: 0 2px 0 0;
		  width: 20px;
		  height: 100%;
		}
		.finger-loading .finger-loader-1 {
		  -webkit-animation: finger-1-animation 2s infinite ease-out;
		  animation: finger-1-animation 2s infinite ease-out;
		}
		.finger-loading .finger-loader-1 span {
		  -webkit-animation: finger-1-animation-span 2s infinite ease-out;
		  animation: finger-1-animation-span 2s infinite ease-out;
		}
		.finger-loading .finger-loader-1 i {
		  -webkit-animation: finger-1-animation-i 2s infinite ease-out;
		  animation: finger-1-animation-i 2s infinite ease-out;
		}
		.finger-loading .finger-loader-2 {
		  -webkit-animation: finger-2-animation 2s infinite ease-out;
		  animation: finger-2-animation 2s infinite ease-out;
		}
		.finger-loading .finger-loader-2 span {
		  -webkit-animation: finger-2-animation-span 2s infinite ease-out;
		  animation: finger-2-animation-span 2s infinite ease-out;
		}
		.finger-loading .finger-loader-2 i {
		  -webkit-animation: finger-2-animation-i 2s infinite ease-out;
		  animation: finger-2-animation-i 2s infinite ease-out;
		}
		.finger-loading .finger-loader-3 {
		  -webkit-animation: finger-3-animation 2s infinite ease-out;
		  animation: finger-3-animation 2s infinite ease-out;
		}
		.finger-loading .finger-loader-3 span {
		  -webkit-animation: finger-3-animation-span 2s infinite ease-out;
		  animation: finger-3-animation-span 2s infinite ease-out;
		}
		.finger-loading .finger-loader-3 i {
		  -webkit-animation: finger-3-animation-i 2s infinite ease-out;
		  animation: finger-3-animation-i 2s infinite ease-out;
		}
		.finger-loading .finger-loader-4 {
		  -webkit-animation: finger-4-animation 2s infinite ease-out;
		  animation: finger-4-animation 2s infinite ease-out;
		}
		.finger-loading .finger-loader-4 span {
		  -webkit-animation: finger-4-animation-span 2s infinite ease-out;
		  animation: finger-4-animation-span 2s infinite ease-out;
		}
		.finger-loading .finger-loader-4 i {
		  -webkit-animation: finger-4-animation-i 2s infinite ease-out;
		  animation: finger-4-animation-i 2s infinite ease-out;
		}
		.finger-loading .finger-loader-item {
		  position: relative;
		  width: 100%;
		  height: 100%;
		  -webkit-border-radius: 6px 6px 8px 8px;
		  -webkit-background-clip: padding-box;
		  -moz-border-radius: 6px 6px 8px 8px;
		  -moz-background-clip: padding;
		  border-radius: 6px 6px 8px 8px;
		  background-clip: padding-box;
		  background: #fff;
		}
		.finger-loading .finger-loader-item span {
		  position: absolute;
		  left: 0;
		  top: 0;
		  width: 100%;
		  height: auto;
		  padding: 5px 5px 0 5px;
		}
		.finger-loading .finger-loader-item span:before,
		.finger-loading .finger-loader-item span:after {
		  content: '';
		  position: relative;
		  display: block;
		  margin: 0 0 2px 0;
		  width: 100%;
		  height: 2px;
		  background: #4492f4;
		}
		.finger-loading .finger-loader-item i {
		  position: absolute;
		  left: 3px;
		  bottom: 3px;
		  width: 14px;
		  height: 14px;
		  -webkit-border-radius: 10px 10px 7px 7px;
		  -webkit-background-clip: padding-box;
		  -moz-border-radius: 10px 10px 7px 7px;
		  -moz-background-clip: padding;
		  border-radius: 10px 10px 7px 7px;
		  background-clip: padding-box;
		  background: #4492f4;
		}
		.finger-loading .last-finger-loader {
		  position: relative;
		  float: left;
		  width: 24px;
		  height: 100%;
		  overflow: hidden;
		}
		.finger-loading .last-finger-loader-item {
		  position: absolute;
		  right: 0;
		  top: 32px;
		  width: 110%;
		  height: 20px;
		  -webkit-border-radius: 0 5px 14px 0;
		  -webkit-background-clip: padding-box;
		  -moz-border-radius: 0 5px 14px 0;
		  -moz-background-clip: padding;
		  border-radius: 0 5px 14px 0;
		  background-clip: padding-box;
		  background: #fff;
		  -webkit-animation: finger-5-animation 2s infinite linear;
		  animation: finger-5-animation 2s infinite linear;
		}
		.finger-loading .last-finger-loader-item i {
		  position: absolute;
		  left: 0;
		  top: -8px;
		  width: 22px;
		  height: 8px;
		  background: #fff;
		  overflow: hidden;
		}
		.trendy_finger_loader .trendy_loading_text {
		  margin-top: 180px;
		}
		.finger-loading .last-finger-loader-item i:after {
		  content: '';
		  position: absolute;
		  left: 0;
		  bottom: 0;
		  width: 34px;
		  height: 20px;
		  -webkit-border-radius: 0 0 15px 15px;
		  -webkit-background-clip: padding-box;
		  -moz-border-radius: 0 0 15px 15px;
		  -moz-background-clip: padding;
		  border-radius: 0 0 15px 15px;
		  background-clip: padding-box;
		}
		@-webkit-keyframes finger-1-animation {
		  0% {
		    padding: 12px 0 5px 0;
		  }
		  20% {
		    padding: 12px 0 5px 0;
		  }
		  29% {
		    padding: 4px 0 24px 0;
		  }
		  35% {
		    padding: 4px 0 24px 0;
		  }
		  41% {
		    padding: 12px 0 5px 0;
		  }
		  100% {
		    padding: 12px 0 5px 0;
		  }
		}
		@keyframes finger-1-animation {
		  0% {
		    padding: 12px 0 5px 0;
		  }
		  20% {
		    padding: 12px 0 5px 0;
		  }
		  29% {
		    padding: 4px 0 24px 0;
		  }
		  35% {
		    padding: 4px 0 24px 0;
		  }
		  41% {
		    padding: 12px 0 5px 0;
		  }
		  100% {
		    padding: 12px 0 5px 0;
		  }
		}
		@-webkit-keyframes finger-1-animation-span {
		  0% {
		    top: 0;
		  }
		  20% {
		    top: 0;
		  }
		  29% {
		    top: -7px;
		  }
		  35% {
		    top: -7px;
		  }
		  41% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@keyframes finger-1-animation-span {
		  0% {
		    top: 0;
		  }
		  20% {
		    top: 0;
		  }
		  29% {
		    top: -7px;
		  }
		  35% {
		    top: -7px;
		  }
		  41% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@-webkit-keyframes finger-1-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  20% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  29% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  35% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  41% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@keyframes finger-1-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  20% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  29% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  35% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  41% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@-webkit-keyframes finger-2-animation {
		  0% {
		    padding: 6px 0 2px 0;
		  }
		  24% {
		    padding: 6px 0 2px 0;
		  }
		  33% {
		    padding: 2px 0 16px 0;
		  }
		  39% {
		    padding: 2px 0 16px 0;
		  }
		  45% {
		    padding: 6px 0 2px 0;
		  }
		  100% {
		    padding: 6px 0 2px 0;
		  }
		}
		@keyframes finger-2-animation {
		  0% {
		    padding: 6px 0 2px 0;
		  }
		  24% {
		    padding: 6px 0 2px 0;
		  }
		  33% {
		    padding: 2px 0 16px 0;
		  }
		  39% {
		    padding: 2px 0 16px 0;
		  }
		  45% {
		    padding: 6px 0 2px 0;
		  }
		  100% {
		    padding: 6px 0 2px 0;
		  }
		}
		@-webkit-keyframes finger-2-animation-span {
		  0% {
		    top: 0;
		  }
		  24% {
		    top: 0;
		  }
		  33% {
		    top: -7px;
		  }
		  39% {
		    top: -7px;
		  }
		  45% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@keyframes finger-2-animation-span {
		  0% {
		    top: 0;
		  }
		  24% {
		    top: 0;
		  }
		  33% {
		    top: -7px;
		  }
		  39% {
		    top: -7px;
		  }
		  45% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@-webkit-keyframes finger-2-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  24% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  33% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  39% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  45% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@keyframes finger-2-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  24% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  33% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  39% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  45% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@-webkit-keyframes finger-3-animation {
		  0% {
		    padding: 0 0 0 0;
		  }
		  28% {
		    padding: 0 0 0 0;
		  }
		  37% {
		    padding: 0 0 12px 0;
		  }
		  43% {
		    padding: 0 0 12px 0;
		  }
		  49% {
		    padding: 0 0 0 0;
		  }
		  100% {
		    padding: 0 0 0 0;
		  }
		}
		@keyframes finger-3-animation {
		  0% {
		    padding: 0 0 0 0;
		  }
		  28% {
		    padding: 0 0 0 0;
		  }
		  37% {
		    padding: 0 0 12px 0;
		  }
		  43% {
		    padding: 0 0 12px 0;
		  }
		  49% {
		    padding: 0 0 0 0;
		  }
		  100% {
		    padding: 0 0 0 0;
		  }
		}
		@-webkit-keyframes finger-3-animation-span {
		  0% {
		    top: 0;
		  }
		  28% {
		    top: 0;
		  }
		  37% {
		    top: -7px;
		  }
		  43% {
		    top: -7px;
		  }
		  49% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@keyframes finger-3-animation-span {
		  0% {
		    top: 0;
		  }
		  28% {
		    top: 0;
		  }
		  37% {
		    top: -7px;
		  }
		  43% {
		    top: -7px;
		  }
		  49% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@-webkit-keyframes finger-3-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  28% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  37% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  43% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  49% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@keyframes finger-3-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  28% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  37% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  43% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  49% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@-webkit-keyframes finger-4-animation {
		  0% {
		    padding: 8px 0 3px 0;
		  }
		  32% {
		    padding: 8px 0 3px 0;
		  }
		  41% {
		    padding: 4px 0 20px 0;
		  }
		  47% {
		    padding: 4px 0 20px 0;
		  }
		  53% {
		    padding: 8px 0 3px 0;
		  }
		  100% {
		    padding: 8px 0 3px 0;
		  }
		}
		@keyframes finger-4-animation {
		  0% {
		    padding: 8px 0 3px 0;
		  }
		  32% {
		    padding: 8px 0 3px 0;
		  }
		  41% {
		    padding: 4px 0 20px 0;
		  }
		  47% {
		    padding: 4px 0 20px 0;
		  }
		  53% {
		    padding: 8px 0 3px 0;
		  }
		  100% {
		    padding: 8px 0 3px 0;
		  }
		}
		@-webkit-keyframes finger-4-animation-span {
		  0% {
		    top: 0;
		  }
		  32% {
		    top: 0;
		  }
		  41% {
		    top: -7px;
		  }
		  47% {
		    top: -7px;
		  }
		  53% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@keyframes finger-4-animation-span {
		  0% {
		    top: 0;
		  }
		  32% {
		    top: 0;
		  }
		  41% {
		    top: -7px;
		  }
		  47% {
		    top: -7px;
		  }
		  53% {
		    top: 0;
		  }
		  100% {
		    top: 0;
		  }
		}
		@-webkit-keyframes finger-4-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  32% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  41% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  47% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  53% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@keyframes finger-4-animation-i {
		  0% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  32% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  41% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  47% {
		    bottom: 8px;
		    height: 12px;
		    -webkit-border-radius: 7px 7px 4px 4px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 7px 7px 4px 4px;
		    -moz-background-clip: padding;
		    border-radius: 7px 7px 4px 4px;
		    background-clip: padding-box;
		  }
		  53% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		  100% {
		    bottom: 3px;
		    height: 14px;
		    -webkit-border-radius: 10px 10px 7px 7px;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 10px 10px 7px 7px;
		    -moz-background-clip: padding;
		    border-radius: 10px 10px 7px 7px;
		    background-clip: padding-box;
		  }
		}
		@-webkit-keyframes finger-5-animation {
		  0% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		  34% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		  43% {
		    top: 20px;
		    right: 2px;
		    -webkit-border-radius: 0 8px 20px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 8px 20px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 8px 20px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(-12deg);
		    transform: rotate(-12deg);
		  }
		  50% {
		    top: 20px;
		    right: 2px;
		    -webkit-border-radius: 0 8px 20px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 8px 20px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 8px 20px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(-12deg);
		    transform: rotate(-12deg);
		  }
		  60% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		  100% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		}
		@keyframes finger-5-animation {
		  0% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		  34% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		  43% {
		    top: 20px;
		    right: 2px;
		    -webkit-border-radius: 0 8px 20px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 8px 20px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 8px 20px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(-12deg);
		    transform: rotate(-12deg);
		  }
		  50% {
		    top: 20px;
		    right: 2px;
		    -webkit-border-radius: 0 8px 20px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 8px 20px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 8px 20px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(-12deg);
		    transform: rotate(-12deg);
		  }
		  60% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		  100% {
		    top: 32px;
		    right: 0;
		    -webkit-border-radius: 0 5px 14px 0;
		    -webkit-background-clip: padding-box;
		    -moz-border-radius: 0 5px 14px 0;
		    -moz-background-clip: padding;
		    border-radius: 0 5px 14px 0;
		    background-clip: padding-box;
		    -webkit-transform: rotate(0deg);
		    transform: rotate(0deg);
		  }
		}
	<?php } elseif( "trendy_polygon_loader" == $trendy_loading_style ) { ?>

		@keyframes type-01 {
		  0% {
		    border-top-width: 0;
		    border-right-width: 64px;
		    border-bottom-width: 64px;
		    border-left-width: 0;
		  }
		  40% {
		    border-top-width: 0;
		    border-right-width: 64px;
		    border-bottom-width: 64px;
		    border-left-width: 0;
		  }
		  45% {
		    border-top-width: 64px;
		    border-right-width: 64px;
		    border-bottom-width: 64px;
		    border-left-width: 0;
		  }
		  100% {
		    border-top-width: 64px;
		    border-right-width: 64px;
		    border-bottom-width: 64px;
		    border-left-width: 0;
		  }
		}
		@keyframes type-02 {
		  0% {
		    border-top-width: 0;
		    border-right-width: 46px;
		    border-bottom-width: 46px;
		    border-left-width: 46px;
		  }
		  40% {
		    border-top-width: 0;
		    border-right-width: 46px;
		    border-bottom-width: 46px;
		    border-left-width: 46px;
		  }
		  45% {
		    border-top-width: 46px;
		    border-right-width: 46px;
		    border-bottom-width: 46px;
		    border-left-width: 46px;
		  }
		  100% {
		    border-top-width: 46px;
		    border-right-width: 46px;
		    border-bottom-width: 46px;
		    border-left-width: 46px;
		  }
		}
		@keyframes type-03 {
		  0% {
		    border-top-width: 0;
		    border-right-width: 0;
		    border-bottom-width: 64px;
		    border-left-width: 64px;
		  }
		  40% {
		    border-top-width: 0;
		    border-right-width: 0;
		    border-bottom-width: 64px;
		    border-left-width: 64px;
		  }
		  45% {
		    border-top-width: 64px;
		    border-right-width: 0;
		    border-bottom-width: 64px;
		    border-left-width: 64px;
		  }
		  100% {
		    border-top-width: 64px;
		    border-right-width: 0;
		    border-bottom-width: 64px;
		    border-left-width: 64px;
		  }
		}
		.polygon-loader {
		  position: absolute;
		  top: 50%;
		  left: 50%;
		  margin: -96px 0 0 -64px;
		  height: 192px;
		  width: 128px;
		}
		.polygon-loader div {
		  position: absolute;
		  top: 64px;
		  left: 64px;
		  border: 0 solid transparent;
		  transform-origin: left top;
		  animation-duration: .52s;
		  animation-iteration-count: infinite;
		  animation-direction: alternate;
		  animation-timing-function: steps(4);
		}
		.trendy_polygon_loader .trendy_loading_text {
		  margin-top: 255px;
		}
		.polygon-loader div:nth-of-type(1) {
		  border-top-color: #210008;
		  -webkit-transform: rotate(-180deg);
		  -moz-transform: rotate(-180deg);
		  -ms-transform: rotate(-180deg);
		  -o-transform: rotate(-180deg);
		  transform: rotate(-180deg);
		  animation-name: type-01;
		}
		.polygon-loader div:nth-of-type(2) {
		  border-top-color: #520019;
		  -webkit-transform: rotate(-90deg);
		  -moz-transform: rotate(-90deg);
		  -ms-transform: rotate(-90deg);
		  -o-transform: rotate(-90deg);
		  transform: rotate(-90deg);
		  animation-name: type-01;
		  animation-delay: .08s;
		}
		.polygon-loader div:nth-of-type(3) {
		  border-top-color: #7b0029;
		  -webkit-transform: rotate(0);
		  -moz-transform: rotate(0);
		  -ms-transform: rotate(0);
		  -o-transform: rotate(0);
		  transform: rotate(0);
		  animation-name: type-01;
		  animation-delay: .12s;
		}
		.polygon-loader div:nth-of-type(4) {
		  border-top-color: #a5003a;
		  -webkit-transform: rotate(90deg);
		  -moz-transform: rotate(90deg);
		  -ms-transform: rotate(90deg);
		  -o-transform: rotate(90deg);
		  transform: rotate(90deg);
		  animation-name: type-01;
		  animation-delay: .16s;
		}
		.polygon-loader div:nth-of-type(5) {
		  left: 0;
		  border-top-color: #d6004a;
		  -webkit-transform: rotate(45deg);
		  -moz-transform: rotate(45deg);
		  -ms-transform: rotate(45deg);
		  -o-transform: rotate(45deg);
		  transform: rotate(45deg);
		  animation-name: type-02;
		  animation-delay: .24s;
		}
		.polygon-loader div:nth-of-type(6) {
		  top: 128px;
		  left: 0;
		  border-top-color: #ff0052;
		  -webkit-transform: rotate(0);
		  -moz-transform: rotate(0);
		  -ms-transform: rotate(0);
		  -o-transform: rotate(0);
		  transform: rotate(0);
		  animation-name: type-03;
		  animation-delay: .28s;
		}
	<?php } elseif( "trendy_segment_loader" == $trendy_loading_style ) { ?>

		.wppu-trendy-segment-loader {
		  height: 120px;
		  width: 120px;
		  margin: 0 auto;
		  transform: rotate(-45deg);
		  font-size: 0;
		  line-height: 0;
		  animation: rotate-loader 5s infinite;
		  padding: 25px;
		  border: 1px solid teal;
		}
		.wppu-trendy-segment-holder {
		  position: relative;
		  display: inline-block;
		  width: 50%;
		  height: 50%;
		}

		.wppu-trendy-segment {
		  position: absolute;
		  background: teal;
		}

		.wppu-trendy-segment-one {
		  bottom: 0;
		  height: 0;
		  width: 100%;
		  animation: slide-one 1s infinite;
		}

		.wppu-trendy-segment-two {
		  left: 0;
		  height: 100%;
		  width: 0;
		  animation: slide-two 1s infinite;
		  animation-delay: 0.25s;
		}

		.wppu-trendy-segment-three {
		  right: 0;
		  height: 100%;
		  width: 0;
		  animation: slide-two 1s infinite;
		  animation-delay: 0.75s;
		}

		.wppu-trendy-segment-four {
		  top: 0;
		  height: 0;
		  width: 100%;
		  animation: slide-one 1s infinite;
		  animation-delay: 0.5s;
		}
		.trendy_segment_loader h2.trendy_loading_text {
		  margin-top: 45px;
		}

		@keyframes slide-one {
		  0%    { height: 0;    opacity: 1; }
		  12.5% { height: 100%; opacity: 1; }
		  50%   { opacity: 1; }
		  100%  { height: 100%; opacity: 0;}
		}

		@keyframes slide-two {
		  0%    { width: 0;    opacity: 1; }
		  12.5% { width: 100%; opacity: 1; }
		  50%   { opacity: 1; }
		  100%  { width: 100%; opacity: 0;}
		}

		@keyframes rotate-loader {
		  0%   { transform: rotate(-45deg); }
		  20%  { transform: rotate(-45deg); }
		  25%  { transform: rotate(-135deg); }
		  45%  { transform: rotate(-135deg); }
		  50%  { transform: rotate(-225deg); }
		  70%  { transform: rotate(-225deg); }
		  75%  { transform: rotate(-315deg); }
		  95%  { transform: rotate(-315deg); }
		  100% { transform: rotate(-405deg); }
		}

	<?php } elseif( "trendy_twinner_loader" == $trendy_loading_style ) { ?>

		.trendy-twinner-loader {
		  width: 1em;
		  height: 1em;
		  font-size: 150px;
		  position: relative;
		}
		@media (max-width: 225px), (max-height: 225px) {
		  .trendy-twinner-loader {
		    font-size: 75px;
		  }
		}
		.trendy-twinner-loader:before, .trendy-twinner-loader:after {
		  content: "";
		  top: 0;
		  display: block;
		  width: 1em;
		  height: 1em;
		  position: absolute;
		  border-width: 0.5em;
		  border-style: double;
		  border-color: transparent;
		  box-sizing: border-box;
		  border-radius: 1em;
		  -webkit-animation: spin 1s infinite;
		          animation: spin 1s infinite;
		}
		.trendy-twinner-loader:after {
		  left: 0;
		  border-left-color: #ecf0f1;
		}
		.trendy-twinner-loader:before {
		  right: 0;
		  border-right-color: #ecf0f1;
		  -webkit-animation-delay: -0.25s;
		          animation-delay: -0.25s;
		}
		.trendy_twinner_loader h2.trendy_loading_text {
		  color: #000000;
		  font-size: 13px;
		  margin-top: 15px;
		}

		@-webkit-keyframes spin {
		  from {
		    -webkit-transform: rotate(360deg);
		            transform: rotate(360deg);
		  }
		}

		@keyframes spin {
		  from {
		    -webkit-transform: rotate(360deg);
		            transform: rotate(360deg);
		  }
		}
	<?php } elseif( "trendy_cardflip_loader" == $trendy_loading_style ) { ?>

		/* The loader container */
		.cardflip-loader {
		  position: absolute;
		  top: 50%;
		  left: 50%;
		  
		  width: 200px;
		  height: 200px;
		  
		  margin-top: -100px;
		  margin-left: -100px;
		  
		  perspective: 400px;
		  transform-type: preserve-3d;
		 }


		/* The dot */
		.cardflip-dot {
		  position: absolute;
		  top: 50%;
		  left: 50%;
		  z-index: 10;
		  
		  width: 40px;
		  height: 40px;
		  
		  margin-top: -20px;
		  margin-left: -80px;
		  
		  transform-type: preserve-3d;
		  transform-origin: 80px 50%;
		  transform: rotateY(0);
		  
		  background-color: #1e3f57;
		  animation: dot1 2000ms cubic-bezier(.56,.09,.89,.69) infinite;
		}
		.trendy_cardflip_loader .trendy_loading_text {
		  margin-top: 120px;
		}

		.cardflip-dot:nth-child(2) {
		  z-index: 9;
		  animation-delay: 150ms;
		}

		.cardflip-dot:nth-child(3) {
		  z-index: 8;
		  animation-delay: 300ms;
		}

		.cardflip-dot:nth-child(4) {
		  z-index: 7;
		  animation-delay: 450ms;
		}

		.cardflip-dot:nth-child(5) {
		  z-index: 6;
		  animation-delay: 600ms;
		}

		.cardflip-dot:nth-child(6) {
		  z-index: 5;
		  animation-delay: 750ms;
		}

		.cardflip-dot:nth-child(7) {
		  z-index: 4;
		  animation-delay: 900ms;
		}

		.cardflip-dot:nth-child(8) {
		  z-index: 3;
		  animation-delay: 1050ms;
		}

		@keyframes dot1 {
		  0% {
		    transform: rotateY(0) rotateZ(0) rotateX(0);
		    background-color: #1e3f57;
		  }
		  45% {
		    transform: rotateZ(180deg) rotateY(360deg) rotateX(90deg);
		    background-color: #6bb2cd;
		    animation-timing-function: cubic-bezier(.15,.62,.72,.98);
		  }
		  90%, 100% {
		    transform: rotateY(0) rotateZ(360deg) rotateX(180deg);
		    background-color: #1e3f57;
		  }
		}
	<?php } elseif( "trendy_wrapper1_loader" == $trendy_loading_style ) { ?>

		.square-loader,
		.circle-loader {
		  position: relative;
		  width: 200px;
		  height: 200px;
		}
		/* 
		=======================
		    Square Preloader
		=======================
		*/
		.wrapp-square {
		  width: 50px;
		  height: 50px;
		  background-color: rgba(255,255,255,0);
		  margin-right: auto;
		  margin-left: auto;
		  border: 2px solid #fff;
		  left: 73px;
		  top: 73px;
		  position: absolute;
		}

		.square-loader {
		  transform: rotate(45deg);
		}

		.first_square {
		  animation: first_square_animate 1s infinite ease-in-out;
		}
		.second_square {
		  animation: second_square 1s forwards, 
		             second_square_animate 1s infinite ease-in-out;
		} 
		.third_square {
		  animation: third_square 1s forwards, 
		             third_square_animate 1s infinite ease-in-out;
		} 
		    
		@keyframes second_square {
		  100% { width: 100px; height:100px; left: 48px; top: 48px; }
		}

		@keyframes third_square {
		  100% { width: 150px; height:150px; left: 23px; top: 23px;}
		}

		@keyframes first_square_animate {
		  0%   { transform: perspective(100px) rotateX(0deg) rotateY(0deg);} 
		  50%  { transform: perspective(100px) rotateX(-180deg) rotateY(0deg); }
		  100% { transform: perspective(100px) rotateX(-180deg) rotateY(-180deg); }
		}

		@keyframes second_square_animate {
		  0%   { transform: perspective(200px) rotateX(0deg) rotateY(0deg); } 
		  50%  { transform: perspective(200px) rotateX(180deg) rotateY(0deg); } 
		  100% { transform: perspective(200px) rotateX(180deg) rotateY(180deg); }
		}

		@keyframes third_square_animate {
		  0%   { transform: perspective(300px) rotateX(0deg) rotateY(0deg); } 
		  50%  { transform: perspective(300px) rotateX(-180deg) rotateY(0deg); } 
		  100% { transform: perspective(300px) rotateX(-180deg) rotateY(-180deg); }
		}
		.third-wrapper .square-loader {
		  -webkit-transform: rotate(0);
		  transform: rotate(0);
		}
	<?php } elseif( "trendy_wrapper2_loader" == $trendy_loading_style ) { ?>
		.circle-loader {
		  position: relative;
		  width: 200px;
		  height: 200px;
		}
		.circle-loader {
		  position: relative;
		}

		.wrapp-circle {
		  border-radius: 50% 50% 50% 50%;
		  position: absolute;
		  border-top: 2px solid #fff;
		  border-bottom: 2px solid transparent;
		  border-left:  2px solid #fff;
		  border-right: 2px solid transparent;
		  animation: animatewrapper 2s infinite; 
		}

		.circle_one {
		  left: 75px;
		  top: 75px;
		  width: 50px;
		  height: 50px;
		}
		              
		.circle_two {
		  left: 65px;
		  top: 65px;
		  width: 70px;
		  height: 70px;
		  animation-delay: 0.2s;
		}
		    
		.circle_three {
		  left: 55px;
		  top: 55px;
		  width: 90px;
		  height: 90px;
		  animation-delay: 0.4s;
		}

		.circle_four {
		  left: 45px;
		  top: 45px;
		  width: 110px;
		  height: 110px;
		  animation-delay: 0.6s;
		} 

		@keyframes animatewrapper {
		  50% { transform: rotate(360deg) scale(0.8); } 
		}

		#trendy_main_container .trendy_cat_loader .trendy_loading_text {
		  display: block;
		  margin-top: 45px;
		}
		#trendy_main_container .trendy_wrapper1_loader .trendy_loading_text{
		  display: block;
		  margin-top: 40px;
		}
		#trendy_main_container .trendy_wrapper2_loader .trendy_loading_text {
		  display: block;
		  margin-top: 0px;
		}
		#trendy_main_container .trendy_segment_loader .trendy_loading_text {
		  display: block;
		  margin-bottom: 0;
		  margin-top: 55px;
		}
		.trendy_clock2_loader .trendy_loading_text {
		  margin-bottom: 20px;
		}
		.trendy_gear_loader .trendy_loading_text {
		  margin-bottom: 20px;
		}
		.trendy_twinner_loader .trendy_loading_text {
		  margin-top: 25px;
		}
	<?php } elseif( "trendy_wrapper3_loader" == $trendy_loading_style ) { ?>
		.square-loader,
		.circle-loader {
		  position: relative;
		  width: 200px;
		  height: 200px;
		}
		/* 
		=======================
		    Square Preloader
		=======================
		*/
		.wrapp-square {
		  width: 50px;
		  height: 50px;
		  background-color: rgba(255,255,255,0);
		  margin-right: auto;
		  margin-left: auto;
		  border: 2px solid #fff;
		  left: 73px;
		  top: 73px;
		  position: absolute;
		}

		.square-loader {
		  transform: rotate(45deg);
		}

		.first_square {
		  animation: first_square_animate 1s infinite ease-in-out;
		}
		.second_square {
		  animation: second_square 1s forwards, 
		             second_square_animate 1s infinite ease-in-out;
		} 
		.third_square {
		  animation: third_square 1s forwards, 
		             third_square_animate 1s infinite ease-in-out;
		} 
		    
		@keyframes second_square {
		  100% { width: 100px; height:100px; left: 48px; top: 48px; }
		}

		@keyframes third_square {
		  100% { width: 150px; height:150px; left: 23px; top: 23px;}
		}

		@keyframes first_square_animate {
		  0%   { transform: perspective(100px) rotateX(0deg) rotateY(0deg);} 
		  50%  { transform: perspective(100px) rotateX(-180deg) rotateY(0deg); }
		  100% { transform: perspective(100px) rotateX(-180deg) rotateY(-180deg); }
		}

		@keyframes second_square_animate {
		  0%   { transform: perspective(200px) rotateX(0deg) rotateY(0deg); } 
		  50%  { transform: perspective(200px) rotateX(180deg) rotateY(0deg); } 
		  100% { transform: perspective(200px) rotateX(180deg) rotateY(180deg); }
		}

		@keyframes third_square_animate {
		  0%   { transform: perspective(300px) rotateX(0deg) rotateY(0deg); } 
		  50%  { transform: perspective(300px) rotateX(-180deg) rotateY(0deg); } 
		  100% { transform: perspective(300px) rotateX(-180deg) rotateY(-180deg); }
		}
		.third-wrapper .square-loader {
		  -webkit-transform: rotate(0);
		  transform: rotate(0);
		}
	<?php } elseif( "trendy_cat_loader" == $trendy_loading_style ) { ?>
		@-webkit-keyframes rotateCatEye {
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@-moz-keyframes rotateCatEye {
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@-o-keyframes rotateCatEye {
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@keyframes rotateCatEye {
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@-webkit-keyframes hideEye {
		  0%, 10%, 85% {
		    margin-top: 0;
		    height: 50px;
		  }
		  30%, 65% {
		    margin-top: 20px;
		    height: 30px;
		  }
		}
		@-moz-keyframes hideEye {
		  0%, 10%, 85% {
		    margin-top: 0;
		    height: 50px;
		  }
		  30%, 65% {
		    margin-top: 20px;
		    height: 30px;
		  }
		}
		@-o-keyframes hideEye {
		  0%, 10%, 85% {
		    margin-top: 0;
		    height: 50px;
		  }
		  30%, 65% {
		    margin-top: 20px;
		    height: 30px;
		  }
		}
		@keyframes hideEye {
		  0%, 10%, 85% {
		    margin-top: 0;
		    height: 50px;
		  }
		  30%, 65% {
		    margin-top: 20px;
		    height: 30px;
		  }
		}
		@-webkit-keyframes blink {
		  0%, 10%, 85% {
		    bottom: 0;
		  }
		  30%, 65% {
		    bottom: 20px;
		  }
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@-moz-keyframes blink {
		  0%, 10%, 85% {
		    bottom: 0;
		  }
		  30%, 65% {
		    bottom: 20px;
		  }
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@-o-keyframes blink {
		  0%, 10%, 85% {
		    bottom: 0;
		  }
		  30%, 65% {
		    bottom: 20px;
		  }
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		@keyframes blink {
		  0%, 10%, 85% {
		    bottom: 0;
		  }
		  30%, 65% {
		    bottom: 20px;
		  }
		  0% {
		    transform: rotate(-0.08turn);
		  }
		  100% {
		    transform: rotate(-1.08turn);
		  }
		}
		.cat-loader {
		  width: 240px;
		  height: 240px;
		}
		.cat-loader .cat-track {
		  width: 100%;
		  height: 100%;
		  border: solid white;
		  border-width: 5px 5px 3px 0;
		  border-top-color: transparent;
		  border-radius: 50%;
		  margin-left: -3px;
		  margin-top: -3px;
		  -webkit-animation: rotateCatEye 3s infinite linear;
		  -moz-animation: rotateCatEye 3s infinite linear;
		  -o-animation: rotateCatEye 3s infinite linear;
		  animation: rotateCatEye 3s infinite linear;
		}
		.cat-loader .cat-track .cat-mouse {
		  position: absolute;
		  right: 31px;
		  top: 18px;
		  width: 25px;
		  height: 25px;
		  background: white;
		  border-radius: 80% 0 55% 50% / 55% 0 80% 50%;
		  transform: rotate(-95deg);
		}
		.cat-loader .cat-track .cat-mouse:before, .cat-loader .cat-track .cat-mouse:after {
		  position: absolute;
		  content: '';
		  width: 9px;
		  height: 9px;
		  border-radius: 50%;
		  background: inherit;
		}
		.cat-loader .cat-track .cat-mouse:before {
		  left: 5px;
		  top: -4px;
		}
		.cat-loader .cat-track .cat-mouse:after {
		  left: 20px;
		  top: 11px;
		}
		.cat-loader .cat-face {
		  position: absolute;
		  left: 50%;
		  top: 50%;
		  width: 130px;
		  height: 130px;
		  margin-left: -65px;
		  margin-top: -65px;
		}
		.cat-loader .cat-face:before, .cat-loader .cat-face:after {
		  position: absolute;
		  content: '';
		  width: 50%;
		  height: 100%;
		}
		.cat-loader .cat-face:before {
		  background: #c8c6c9;
		  border-top-left-radius: 65px;
		  left: 0;
		  border-bottom-left-radius: 55px;
		}
		.cat-loader .cat-face:after {
		  left: 50%;
		  background: #d0ced1;
		  border-top-right-radius: 65px;
		  border-bottom-right-radius: 55px;
		}
		.cat-loader .cat-face .cat-ears-container {
		  position: absolute;
		  top: -8px;
		  width: 130px;
		  height: 50px;
		}
		.cat-loader .cat-face .cat-ears-container:before, .cat-loader .cat-face .cat-ears-container:after {
		  position: absolute;
		  content: '';
		  width: 0;
		  height: 0;
		  border-top: 35px solid transparent;
		  border-bottom: 35px solid transparent;
		}
		.cat-loader .cat-face .cat-ears-container:before {
		  border-left: 35px solid #c8c6c9;
		  left: 0;
		}
		.cat-loader .cat-face .cat-ears-container:after {
		  right: 0;
		  border-right: 35px solid #d0ced1;
		}
		.cat-loader .cat-face .cat-eyes-container {
		  position: absolute;
		  overflow: hidden;
		  left: 50%;
		  top: 30px;
		  width: 106px;
		  height: 50px;
		  margin-left: -53px;
		  z-index: 1;
		  -webkit-animation: hideEye 3s infinite linear;
		  -moz-animation: hideEye 3s infinite linear;
		  -o-animation: hideEye 3s infinite linear;
		  animation: hideEye 3s infinite linear;
		}
		.cat-loader .cat-face .cat-eyes-container .cat-eye {
		  position: relative;
		  bottom: 0;
		  float: left;
		  width: 50px;
		  height: 50px;
		  border-radius: 50%;
		  color: #c8c6c9;
		  background: white;
		  -webkit-animation: blink 3s infinite linear;
		  -moz-animation: blink 3s infinite linear;
		  -o-animation: blink 3s infinite linear;
		  animation: blink 3s infinite linear;
		}
		.cat-loader .cat-face .cat-eyes-container .cat-eye:after {
		  position: absolute;
		  content: '';
		  top: 4px;
		  right: 14px;
		  width: 12px;
		  height: 12px;
		  border-radius: inherit;
		  background: #838091;
		}
		.cat-loader .cat-face .cat-eyes-container .cat-eye:last-child {
		  float: right;
		  color: #d0ced1;
		}
		.cat-loader .cat-face .cat-phiz {
		  position: absolute;
		  left: 50%;
		  top: 66px;
		  width: 32px;
		  height: 48px;
		  margin-left: -16px;
		  z-index: 1;
		}
		.cat-loader .cat-face .cat-phiz .cat-nose {
		  width: 100%;
		  height: 15px;
		  border-top-left-radius: 5px;
		  border-top-right-radius: 5px;
		  border-bottom-left-radius: 25px;
		  border-bottom-right-radius: 25px;
		  background: #838091;
		}
		.cat-loader .cat-face .cat-phiz .cat-lip {
		  position: relative;
		  left: 50%;
		  width: 4px;
		  height: 12px;
		  margin-left: -2px;
		  background: #838091;
		}
		.cat-loader .cat-face .cat-phiz .cat-lip:before {
		  position: absolute;
		  content: '';
		  width: 100%;
		  height: 5px;
		  background: #767385;
		}
		.cat-loader .cat-face .cat-phiz .cat-mouth {
		  position: relative;
		  left: 50%;
		  width: 20px;
		  height: 6px;
		  margin-left: -13px;
		  background: white;
		  border: 3px solid #838091;
		  border-bottom-right-radius: 12px;
		  border-bottom-left-radius: 12px;
		}
		.trendy_cat_loader h2.trendy_loading_text {
		  margin-top: 16px;
		}
	<?php } else { ?>
		.wppu-trendy-loader-body {
		  position: absolute;
		  top: 50%;
		  margin-left: -50px;
		  left: 50%;
		  animation: speeder .4s linear infinite;
		}

		.wppu-trendy-loader-body > span {
		  height: 5px;
		  width: 35px;
		  background: #000;
		  position: absolute;
		  top: -19px;
		  left: 60px;
		  border-radius: 2px 10px 1px 0;
		}

		.fazer_base span {
		  position: absolute;
		  width: 0;
		  height: 0;
		  border-top: 6px solid transparent;
		  border-right: 100px solid #000;
		  border-bottom: 6px solid transparent;
		}

		.fazer_base span:after {
		  content: "";
		  height: 22px;
		  width: 22px;
		  border-radius: 50%;
		  background: #000;
		  position: absolute;
		  right: -110px;
		  top: -16px;
		}

		.fazer_base span:before {
		  content: "";
		  position: absolute;
		  width: 0;
		  height: 0;
		  border-top: 0 solid transparent;
		  border-right: 55px solid #000;
		  border-bottom: 16px solid transparent;
		  top: -16px;
		  right: -98px;
		}

		.fazer_face {
		  position: absolute;
		  height: 12px;
		  width: 20px;
		  background: #000;
		  border-radius: 20px 20px 0 0;
		  transform: rotate(-40deg);
		  right: -125px;
		  top: -15px;
		}

		.fazer_face:after {
		  content: "";
		  height: 12px;
		  width: 12px;
		  background: #000;
		  right: 4px;
		  top: 7px;
		  position: absolute;
		  transform: rotate(40deg);
		  transform-origin: 50% 50%;
		  border-radius: 0 0 0 2px;
		}
		.trendy_fazer_man .trendy_loading_text {
		  margin-top: 130px;
		}
		.wppu-trendy-loader-body > span > span:nth-child(1),
		.wppu-trendy-loader-body > span > span:nth-child(2),
		.wppu-trendy-loader-body > span > span:nth-child(3),
		.wppu-trendy-loader-body > span > span:nth-child(4) {
		  width: 30px;
		  height: 1px;
		  background: #000;
		  position: absolute;
		  animation: fazer1 .2s linear infinite;
		}

		.wppu-trendy-loader-body > span > span:nth-child(2) {
		  top: 3px;
		  animation: fazer2 .4s linear infinite;
		}

		.wppu-trendy-loader-body > span > span:nth-child(3) {
		  top: 1px;
		  animation: fazer3 .4s linear infinite;
		  animation-delay: -1s;
		}

		.wppu-trendy-loader-body > span > span:nth-child(4) {
		  top: 4px;
		  animation: fazer4 1s linear infinite;
		  animation-delay: -1s;
		}

		@keyframes fazer1 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -80px;
		    opacity: 0;
		  }
		}
		@keyframes fazer2 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -100px;
		    opacity: 0;
		  }
		}
		@keyframes fazer3 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -50px;
		    opacity: 0;
		  }
		}
		@keyframes fazer4 {
		  0% {
		    left: 0;
		  }
		  100% {
		    left: -150px;
		    opacity: 0;
		  }
		}
		@keyframes speeder {
		  0% {
		    transform: translate(2px, 1px) rotate(0deg);
		  }
		  10% {
		    transform: translate(-1px, -3px) rotate(-1deg);
		  }
		  20% {
		    transform: translate(-2px, 0px) rotate(1deg);
		  }
		  30% {
		    transform: translate(1px, 2px) rotate(0deg);
		  }
		  40% {
		    transform: translate(1px, -1px) rotate(1deg);
		  }
		  50% {
		    transform: translate(-1px, 3px) rotate(-1deg);
		  }
		  60% {
		    transform: translate(-1px, 1px) rotate(0deg);
		  }
		  70% {
		    transform: translate(3px, 1px) rotate(-1deg);
		  }
		  80% {
		    transform: translate(-2px, -1px) rotate(1deg);
		  }
		  90% {
		    transform: translate(2px, 1px) rotate(0deg);
		  }
		  100% {
		    transform: translate(1px, -2px) rotate(-1deg);
		  }
		}
		.longfazers {
		  position: absolute;
		  width: 100%;
		  height: 100%;
		  left: 0;
		  top: 0;
		}

		.longfazers span {
		  position: absolute;
		  height: 2px;
		  width: 20%;
		  background: #000;
		}

		.longfazers span:nth-child(1) {
		  top: 20%;
		  animation: lf .6s linear infinite;
		  animation-delay: -5s;
		}

		.longfazers span:nth-child(2) {
		  top: 40%;
		  animation: lf2 .8s linear infinite;
		  animation-delay: -1s;
		}

		.longfazers span:nth-child(3) {
		  top: 60%;
		  animation: lf3 .6s linear infinite;
		}

		.longfazers span:nth-child(4) {
		  top: 80%;
		  animation: lf4 .5s linear infinite;
		  animation-delay: -3s;
		}

		@keyframes lf {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -200%;
		    opacity: 0;
		  }
		}
		@keyframes lf2 {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -200%;
		    opacity: 0;
		  }
		}
		@keyframes lf3 {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -100%;
		    opacity: 0;
		  }
		}
		@keyframes lf4 {
		  0% {
		    left: 200%;
		  }
		  100% {
		    left: -100%;
		    opacity: 0;
		  }
		}	
	<?php } if( is_rtl() ) { ?>

	.wppu-trendy-loader-body {
	  direction: ltr;
	}
	.wppu-trendy-loader-body h2 {
	  direction: rtl;
	}

	<?php } ?>
	
	</style>

	<div class="wppu--loader wppu-trendy-frontend-body">
		<div class="wppu-trendy-frontend-inner">
			<?php if( $wppu_trendy_markup ) {
				echo $wppu_trendy_markup;
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
						<h2 class="trendy_loading_text">Loading...</h2>
					</div>
                </div>
            </div>
			<?php } ?>
		</div>
	</div>
	<?php
	echo ob_get_clean();

}


function wppu_fill_loader_frontend(){

	$options = get_option( 'wppu_display' );

	$fill_loader_type = (empty($options['fill_loader_type'])) ? "text" : $options['fill_loader_type'];
	$fill_loader_background = ($options['fill_loader_background']) ? "#FFF" : $options['fill_loader_background'];
	$progress_bar = ($options['fill_progress_bar_pos'] != "none") ? 'true' : 'false';
	$counter_enable = (isset($options['fill_counter_enable'])) ? 'true' : 'false';

	if(!empty($options['fill_loader_thumb'])) { 
	    $bg = 'background-image:url('.$options['fill_loader_thumb'].');';
	    $src = $options['fill_loader_thumb'];
	} else {
	    $bg = 'background-image:url(http://www.oshwa.org/wp-content/uploads/2014/03/oshw-logo-800-px.png);';
	    $src =  "http://www.oshwa.org/wp-content/uploads/2014/03/oshw-logo-800-px.png";
	}

	if(!empty($options['fill_loader_thumb_size'])) { 
	    $width = "width:" . $options['fill_loader_thumb_size'] . "px;";
	} else {
	    $width =  "width: 100px;";
	}


	ob_start(); ?>

	<style>
		#wppu-fill-loader-text {
		  display: inline-block;
		  margin: auto;
		  position: relative;
		  text-align: center;
		  width: auto;
		}
		#wppu-fill-loader-text h2 {
		  color: rgba(0, 0, 0, 0.1);
		  display: inline-block;
		  font-size: 5.75em;
		  letter-spacing: 0;
		  line-height: 1.5;
		  overflow: hidden;
		  position: relative;
		  text-transform: none;
		}
		#wppu-fill-loader-text h2.wppu_fill_h2_attr {
		  color: #000;
		  display: block;
		  left: 0;
		  position: absolute;
		  top: 0;
		  width: 0%;
		}
		.wppu-fill-loader-thumb {
		  width: 100px;
		  position: relative;
		}
		.wppu-fill-loader-thumb img {
		  display: block;
		  height: auto;
		  opacity: 0.3;
		  position: relative;
		  width: 100%;
		  z-index: 1;
		}

		.wppu-fill-thumbnail-fill {
		  background-position: center bottom;
		  background-repeat: no-repeat;
		  background-size: cover;
		  bottom: 0;
		  display: block;
		  height: 0;
		  left: 0;
		  position: absolute;
		  transition: all 0s ease 0s;
		  width: 100%;
		  z-index: 3;
		}
		#run_animation_fill {
		  background-color: #000;
		  color: #fff;
		  cursor: pointer;
		  line-height: 1em;
		  padding: 6px 10px 8px;
		  position: absolute;
		  right: 0;
		  top: 0;
		  z-index: 991;
		}
		.wppu_fill_progress_bar {
		  height: 2px;
		  left: 0;
		  position: absolute;
		  top: 0;
		  display: none;
		  width: 0%;
		  z-index: 140;
		  background-color: #ed4e6e;
		}
		.wppu_fill_progress_pos-top.wppu_fill_progress_bar {
		  display: block;
		  top: 0;
		  left: 0;
		}
		.wppu_fill_progress_pos-bottom.wppu_fill_progress_bar {
		  display: block;
		  top: inherit;
		  bottom: 0;
		  left: 0;
		}
		.wppu_fill_counter {
		  line-height: 1;
		  margin-top: 20px;
		}
		#wppu-fill-loader-thunb {
		  width: 100px;
		  position: relative;
		  margin: auto;
		}
		#wppu-fill-loader-thunb img {
		  display: block;
		  height: auto;
		  opacity: 0.3;
		  position: relative;
		  width: 100%;
		  z-index: 1;
		}
		.wppu-fill-thumbnail-fill {
		  background-position: center bottom;
		  background-repeat: no-repeat;
		  background-size: cover;
		  bottom: 0;
		  display: block;
		  height: 0;
		  left: 0;
		  position: absolute;
		  transition: all 0s ease 0s;
		  width: 100%;
		  z-index: 3;
		}

		.wppu-preloader-loaded .wppu_fill_progress_bar,
		.wppu-preloader-loaded h2.wppu_fill_h2_attr {
		    transition: width 0.9s ease 0s;
		    -webkit-transition: width 0.9s ease 0s;
		    -moz-transition: width 0.9s ease 0s;
		    -o-transition: width 0.9s ease 0s;
		    -ms-transition: width 0.9s ease 0s;
		    width: 100% !important;
		}
		.wppu-preloader-loaded .wppu-fill-thumbnail-fill {
			transition: height 0.9s ease 0s;
		    -webkit-transition: height 0.9s ease 0s;
		    -moz-transition: height 0.9s ease 0s;
		    -o-transition: height 0.9s ease 0s;
		    -ms-transition: height 0.9s ease 0s;
		    height: 100% !important;
		}
	</style>
	
	<!-- wppu Preloader -->
	<section class="wppu-preloader loading not-loaded" style="background-color:<?php if(!empty($options['fill_loader_background'])) {echo $options['fill_loader_background']; } else { echo '#FFFFFF'; } ?>;">

		<?php if ( $options['fill_progress_bar_pos'] != "none" ) {
		 ?>
			<div class="wppu_fill_progress_bar <?php echo 'wppu_fill_progress_pos-' . $options['fill_progress_bar_pos']; ?>" style="background-color:<?php echo $options['fill_progress_bar_bg'] ?>;height:<?php echo $options['fill_progress_bar_height'] ?>px"></div>
		<?php } ?>
	    <div class="wppu-logo-loader wppu_frameT">
	    	<div class="wppu_frameTC">
		        <div class="wppu_special_preloader">
		            <div id="wppu_fill_main_loader">
		                
						<?php if ( "image" == $fill_loader_type ) { ?>
							<div id="wppu-fill-loader-thunb" style="<?php echo $width; ?>">
					        	<div class="wppu-fill-thumbnail-fill"  style="<?php echo $bg; ?>"></div>
								<img src="<?php echo $src; ?>" alt="">
					        </div>
						<?php } else { ?>
							<div id="wppu-fill-loader-text">
					            <h2 <?php echo 'style="'; ?> <?php echo (!empty( $options['fill_loader_text_size'] )) ? "font-size: " . $options['fill_loader_text_size'] . "px;" : ""; echo '"'; ?>  class="wppu_fill_h2"><?php echo $options['fill_loader_text']; ?></h2>
					            <h2 <?php echo 'style="'; ?> <?php echo (!empty( $options['fill_loader_text_size'] )) ? "font-size: " . $options['fill_loader_text_size'] . "px;" : ""; echo (!empty( $options['fill_loader_text_color'] )) ? "color: ".$options['fill_loader_text_color']."" : ""; echo '"'; ?> class="wppu_fill_h2_attr"><?php echo $options['fill_loader_text']; ?></h2>
					        </div>
						<?php } 
						if ( isset($options['fill_counter_enable']) ) { ?>
						<div class="wppu_fill_counter" style="color:<?php echo $options['fill_counter_text_color'] ?>;font-size:<?php echo $options['fill_counter_text_size'] ?>px"><span>0</span>%</div>
						<?php } ?>

		            </div>
		        </div>
	        </div>
	    </div>
	</section>
	<!-- end of preloader -->

	<?php
	echo ob_get_clean();
}



if (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'image_radio_options' ) {
	add_action('wp_head', 'wppu_addData_defaul__to_frontend');
	add_action('wp_head', 'wppu_default_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'css_radio_options') {
	add_action('wp_head', 'wppu_cssOptions_preload_css');
	add_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_footer', 'wppu_default_image_option_script_init');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'special_radio_options') {
	add_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'custom_your_options') {
	add_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	add_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'pace_radio_options') {
	add_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	add_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'awesome_radio_options') {
	add_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	add_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'trendy_radio_options') {
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	add_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'three_radio_options') {
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	add_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	add_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'fill_radio_options') {
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	add_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} elseif (!empty($main['sample_radio_buttons']) && $main['sample_radio_buttons'] == 'object_radio_options') {
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	add_action( 'wp_head', 'wppu_object_loader_to_frontend' );
} else {
	add_action('wp_head', 'wppu_addData_defaul__to_frontend');
	add_action('wp_head', 'wppu_default_preload_css');
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
}
add_action( 'wp_head', 'apply_extra_css_to_wppu' );



function wppu_add_defer_attribute($tag, $handle) {
   // add script handles to the array below
   $scripts_to_defer = array(
   	'jquery',
   	'jquery-effects-blind',
		'jquery-effects-bounce',
		'jquery-effects-clip',
		'jquery-effects-drop',
		'jquery-effects-explode',
		'jquery-effects-fold',
		'jquery-effects-highlight',
		'jquery-effects-pulsate',
		'jquery-effects-scale',
		'jquery-effects-shake',
		'wppu-waitMe-js',
		'wppu-pace-js',
		'wppu-frontend'
   );
   
   foreach($scripts_to_defer as $defer_script) {
      if ($defer_script === $handle) {
      	 $tag = preg_replace( "/type=['\"]text\/(javascript|css)['\"]/", '', $tag );
         $tag = str_replace(' src', 'data-cfasync="false" src', $tag);
         return $tag;
      }
   }
   return $tag;
}
$exclude_js = (isset($main['exclude_js_from_plugins']) && !empty($main['exclude_js_from_plugins'])) ? $main['exclude_js_from_plugins'] : '';
if ( !empty($exclude_js) && 'yes' === $exclude_js ) {
	add_filter('script_loader_tag', 'wppu_add_defer_attribute', 10, 2);
	add_filter('autoptimize_filter_js_exclude','wppu_ao_override_jsexclude',10,1);
}


/**
 * JS optimization exclude strings, as configured in admin page.
 *
 * @param $exclude: comma-seperated list of exclude strings
 * @return: comma-seperated list of exclude strings
 */
function wppu_ao_override_jsexclude($exclude) {
	return $exclude.", wppu-preloader-unlimited/public/js/";
}



function wppu_remove_hooking(){
	remove_action( 'wp_enqueue_scripts', 'wppu_awesome_css_options_files' );
	remove_action( 'wp_head', 'wppu_awesome_css_options_markup_frontedn' );
	remove_action( 'wp_head', 'wppu_pace_style_add__frontend' );
	remove_action( 'wp_head', 'wppu_pace_markup_add__frontend' );
	remove_action( 'wp_head', 'custom_preloader_options_markup_js__forntend' );
	remove_action( 'wp_head', 'custom_preloader_options_css__forntend' );
	remove_action( 'wp_head', 'wppu_special_options_markup_frontend' );
	remove_action('wp_head', 'wppu_cssOptions_preload_css');
	remove_action('wp_head', 'wppu_addData_cssOptions__to_frontend');
	remove_action('wp_head', 'wppu_addData_defaul__to_frontend');
	remove_action('wp_head', 'wppu_default_preload_css');
	remove_action('wp_head', 'trendy_preloader_options_css__forntend');
	remove_action( 'wp_head', 'wppu_threed_options_markup_frontend' );
	remove_action( 'wp_enqueue_scripts', 'wppu_threed_options_style_frontend' );
	remove_action( 'wp_head', 'wppu_fill_loader_frontend' );
	remove_action( 'wp_head', 'wppu_object_loader_to_frontend' );
	remove_action( 'wp_head', 'apply_extra_css_to_wppu' );
}


// remove action spefic options
add_action('wp_head','wppu15_home_custom_remove_action',1); 

function wppu15_home_custom_remove_action() {

	$options = get_option( 'wppu_display' );
	if( isset( $options['get_all_post_type'] ) ) {
		$get_all_post_type = $options['get_all_post_type'];
		$wppu_post_types = array_map('trim', explode(',', $get_all_post_type));
		if ( is_singular( $wppu_post_types )  ) {
			wppu_remove_hooking();
		}
	}
	if( isset( $options['get_all_pages'] ) ) {
		$pages_IDS = $options['get_all_pages'];
		$pageArray = array_map('trim', explode(',', $pages_IDS));
		if(is_page($pageArray)) {
			wppu_remove_hooking();
		}
	}
	
	$detect = new Mobile_Detect;
	
	if(isset($options['home_only_ID'])) {
		if(is_front_page() || is_home()) {
			wppu_remove_hooking();
		}
	}
	
	
	if(isset($options['get_all_archive_page'])) {
		if ( is_archive() ) {
			wppu_remove_hooking();
		}
	}
	if(isset($options['404_error_page'])) {
		if ( is_404() ) {
			wppu_remove_hooking();
		}
	}
	if(isset($options['wppu_search_page'])) {
		if ( is_search() ) {
			wppu_remove_hooking();
		}
	}
	if(isset($options['disable_in_mobile'])) {
		if( $detect->isMobile() && !$detect->isTablet() ){
			wppu_remove_hooking();
		}
	}
	if(isset($options['disable_in_tablet'])) {
		if( $detect->isTablet() ){
			wppu_remove_hooking();
		}
	}

}
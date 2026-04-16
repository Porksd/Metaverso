<?php

defined('ABSPATH') || die();

define('WP_INSTALLING', true);

global $current_site;

use BitCode\BitForm\Core\Integration\IntegrationHandler;

function wpmu_activate_stylesheet()
{
?>
	<style type="text/css">
		h5 {
			text-align: center;
		}

		#bf_content {
			text-align: center;
			padding: 5px;
			margin: 30px auto;
			max-width: 100%;
			width: fit-content;
		}

		#bf_content * {
			text-align: center;
			width: 100%;
			white-space: unset;
		}
	</style>
<?php
}

add_action('wp_head', 'wpmu_activate_stylesheet');

$key = $_GET['bf_activation_key'];
$formId = $_GET['bf_f_id'];
$userId = $_GET['bf_user_id'];

$existAuth = (new IntegrationHandler($formId))->getAllIntegration('wp_user_auth', 'wp_auth', 1);

$code = '';

if (is_wp_error($existAuth)) {
	wp_redirect(home_url());
	exit();
}

if (metadata_exists('user', $userId, 'bf_activation_code')) {
	$code = get_user_meta($userId, 'bf_activation_code', true);
}

$intDetails = json_decode($existAuth[0]->integration_details);

$activation = (bool) get_user_meta($userId, 'bf_activation');

$customMessages = '';
$customRedirectPage = '';

if (isset($intDetails->custom_redirect)) {
	if ($code == $key) {
		update_user_meta($userId, 'bf_activation', 1);
		delete_user_meta($userId, 'bf_activation_code');
		$customRedirectPage = (isset($intDetails->succ_url)) ? $intDetails->succ_url : '';
	} else if ($activation === false) {
		$customRedirectPage = (isset($intDetails->invalid_key_url)) ? $intDetails->invalid_key_url : '';
	} else if (empty($code) && $activation === true) {
		$customRedirectPage = (isset($intDetails->already_activated_url)) ? $intDetails->already_activated_url : '';
	}
} else {
	if ($code == $key) {
		update_user_meta($userId, 'bf_activation', 1);
		delete_user_meta($userId, 'bf_activation_code');
		$customMessages = (isset($intDetails->acti_succ_msg)) ? $intDetails->acti_succ_msg : 'Your account has been activated successfully, You can now login.';
	} else if (empty($code) && $activation === true) {
		$customMessages = (isset($intDetails->already_activated_msg)) ? $intDetails->already_activated_msg : 'Your account is already activated!';
	} else if ($activation === false) {
		$customMessages = (isset($intDetails->invalid_key_msg)) ? $intDetails->invalid_key_msg : 'Your URL is invalid!';
	}
}

get_header(); ?>

<div id="bf_content">
	<?php
	if (!empty($customMessages)) {
		echo '<p>' . __($customMessages, 'bitformpro') . '<p>';
	} else {
		wp_redirect($customRedirectPage);
		exit();
	}
	?>
</div>
<?php get_footer(); ?>
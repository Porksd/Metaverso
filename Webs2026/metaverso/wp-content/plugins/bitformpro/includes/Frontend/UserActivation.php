<?php

namespace BitCode\BitFormPro\Frontend;

use BitCode\BitFormPro\Core\Util\Utility;
use BitCode\BitForm\Core\Integration\IntegrationHandler;

class UserActivation
{

    protected $formId;

    protected $userId;

    protected $token;

    public function __construct($token, $formId, $userId)
    {

        $this->token = $token;
        $this->userId = $userId;
        $this->formId = $formId;
    }

    public function emailVerified()
    {
        $exist = (new IntegrationHandler($this->formId))->getAllIntegration('wp_user_auth', 'wp_auth', 1);

        if (is_wp_error($exist)) {
            wp_redirect(home_url());
            exit();
        }

        $code = '';

        if (metadata_exists('user', $this->userId, 'bf_activation_code')) {
            $code = get_user_meta($this->userId, 'bf_activation_code', true);
        }

        $intDetails = json_decode($exist[0]->integration_details);

        $activation = (bool) get_user_meta($this->userId, 'bf_activation');

        if ($code === $this->token) {
            update_user_meta($this->userId, 'bf_activation', 1);
            delete_user_meta($this->userId, 'bf_activation_code');
            $this->redirect((object) $intDetails, 1);
        } else if (empty($code) && $activation === true) {
            $this->redirect((object) $intDetails, 2);
        } else if ($activation === false || $code !== $this->token) {
            $this->redirect((object) $intDetails, 0);
        }
    }

    public function redirect($config, $index)
    {
        $redirectPages = [
            (isset($config->invalid_key_url)) ? $config->invalid_key_url : '',
            (isset($config->succ_url)) ? $config->succ_url : '',
            (isset($config->already_activated_url)) ? $config->already_activated_url : '',
        ];

        $customMessages = [
            (isset($config->invalid_key_msg)) ? $config->invalid_key_msg : 'Sorry! Your URL Is Invalid!',
            (isset($config->acti_succ_msg)) ? $config->acti_succ_msg : 'Your account has been activated successfully, You can now login.',
            (isset($config->already_activated_msg)) ? $config->already_activated_msg : 'Your account is already activated!',
        ];

        if (isset($redirectPages[$index])) {
            if (isset($config->custom_redirect) && (string) $config->custom_redirect === '1') {
                wp_redirect($redirectPages[$index]);
                exit();
            } else {
                Utility::view('views/confirmation/email_confirmation', ['data' => [
                    'title'   => 'Account Activation',
                    'message' => $customMessages[$index],
                ]]);
            }
        }
    }
}

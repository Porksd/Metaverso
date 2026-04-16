<?php

namespace VSHM\Plugin\PaymentGateways;

use Httpful\Exception\ConnectionErrorException;
use VSHM\Bus\RefundReservation;
use VSHM\Bus\RegisterPayment;
use VSHM\Bus\SaveSettings;
use VSHM\Functions;
use VSHM\Plugin\PaymentGateways\PayPal\Listener;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\PaymentGateways\Refund;
use VSHM\Routes\PaymentGatewaysRoute;
use VSHM\Routes\SaveSettingsRoute;
use VSHM\Settings\CurrencyCode;
use VSHM\Settings\Service\Redirect;
use VSHM\Settings\Service\RedirectUrl;
use VSHM\Tools;
use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class PayPal_Gateway
 */
class PayPal_Gateway implements PaymentGateway
{
    private static $id = 'paypal';

    public const OPTIONS_TAG = 'tbk_paypal_settings';

    public function __construct()
    {
    }

    private static function _get_settings(): array
    {
        $defaults = [
            'active'           => FALSE,
            'sandbox'          => FALSE,
            'accountEmail'     => '',
            'primaryEmail'     => '',
            'debugIpn'         => FALSE,
            'redirectUrl'      => get_site_url(),
            'logo'             => '',
            'clientId'         => '',
            'clientSecret'     => '',
            'clientIdTest'     => '',
            'clientSecretTest' => ''
        ];

        return get_option(self::OPTIONS_TAG, $defaults) + $defaults;
    }

    public static function subscribe(): void
    {
        add_filter('tbk_payment_gateway_config', [self::class, 'getSettings'], 10, 2);
        add_filter('tbk_backend_collecting_payment_gateways_panels', [self::class, 'getSettingsPanel']);
        add_action('vshm_dispatching_SaveSettings', [self::class, 'saveBackendSettings']);
        add_action('vshm_dispatching_RefundReservation', [self::class, 'refundReservation']);
        add_filter('tbk_payment_gateways_frontend_choice', [self::class, 'getFrontendProps']);
        add_action('vshm_payment_gateways_listen_' . self::$id, [self::class, 'listenerIPN'], 10, 2);
        add_action('vshm_payment_gateways_redirect_' . self::$id, [self::class, 'redirect']);
        add_filter('tbk_payment_gateways_prepare_link' . self::$id, [self::class, 'prepareGateway'], 10, 2);
        add_filter('vshm_export_settings', static function ($settings) {
            $settings['paymentGateways'][ self::$id ] = self::_get_settings();

            return $settings;
        });
        add_filter('vshm_import_settings', static function ($toSave, $settings, $version) {
            if (isset($settings['paymentGateways'][ self::$id ])) {
                $options = $settings['paymentGateways'][ self::$id ] + self::_get_settings();
                update_option(self::OPTIONS_TAG, $options);
            }

            return $toSave;
        }, 10, 3);
    }

    public static function redirect(\WP_REST_Request $request)
    {
        $status        = $request->get_param('status');
        $reservationId = $request->get_param('res_id');

        $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);

        if (!$reservation) {
            return REST_Controller::get_error_response('paypal_redirect');
        }

        $settings    = self::_get_settings();
        $success_url = $settings['redirectUrl'] ?: get_site_url();

        $serviceRedirect = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => Redirect::ID], TRUE);

        if (filter_var($serviceRedirect, FILTER_VALIDATE_BOOLEAN)) {
            $serviceRedirectUrl = ServicesData::provideBy(['service_id' => $reservation->serviceId, 'key' => RedirectUrl::ID], TRUE);
            if ($serviceRedirectUrl) {
                $success_url = $serviceRedirectUrl;
            }
        }

        $success_url = add_query_arg([
            'reservation' => $reservationId,
            'status'      => $status,
            'tbk-hash'    => $request->get_param('tbk-hash'),
            'tbk-view'    => 'landing'
        ], $success_url);

        wp_redirect($success_url);
        exit;
    }


    public static function saveBackendSettings($command): void
    {
        $settings        = $command->getSettings();
        $to_save         = [];
        $currencyCode    = vshm()->settings->get(CurrencyCode::ID);
        $currencyChanged = FALSE;

        foreach ($settings as $id => $value) {
            if (mb_strpos($id, self::$id . '|') === 0) {
                $to_save[ mb_substr($id, mb_strlen(self::$id . '|')) ] = $value;
                unset($settings[ $id ]);
            }

            // Check currency
            if ($id === CurrencyCode::ID) {
                $currencyCode    = $value;
                $currencyChanged = TRUE;
            }
        }

        if ($to_save || $currencyChanged) {
            $options = $to_save + self::_get_settings();

            if ($options['active'] && !$options['accountEmail']) {
                $options['active'] = FALSE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("PayPal gateway can't be activated without providing an Account Email");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'active' => FALSE
                    ];

                    return $response;
                });
            }

            if ($options['active'] && !self::verifyCurrency($currencyCode)) {
                $options['active'] = FALSE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("PayPal gateway is deactivated, as it doesn't support the current currency.");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'active' => FALSE
                    ];

                    return $response;
                });
            }

            update_option(self::OPTIONS_TAG, $options);
        }

        $command->setSettings($settings);
    }

    public static function getSettings($settings, $gatewayId): array
    {
        if ($gatewayId === self::$id) {
            $returnedSettings = [];
            foreach (self::_get_settings() as $key => $setting) {
                $returnedSettings[ self::$id . '|' . $key ] = $setting;
            }

            return $returnedSettings;
        }

        return $settings;
    }

    public static function refundReservation(RefundReservation $command): void
    {
        if ($command->getGatewayId() !== self::$id) {
            return;
        }

        $paymentData = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => 'paymentDetails'], TRUE);

        if (!$paymentData || !isset($paymentData['txn_id'])) {
            add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                return [
                    'status'  => 'KO',
                    'message' => __('No payment to refund.', 'team-booking')
                ];
            });
            add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                return 404;
            });

            return;
        }

        if (!self::isRestAppReady()) {
            add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                return [
                    'status'  => 'KO',
                    'message' => __('PayPal API are not configured, refunds are not active.', 'team-booking')
                ];
            });
            add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                return 404;
            });

            return;
        }

        $data = self::refundPayment($paymentData['txn_id']);

        if ($data === FALSE) {
            add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                return [
                    'status'  => 'KO',
                    'message' => __('PayPal API connection error.', 'team-booking')
                ];
            });
            add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                return 400;
            });

            return;
        }

        switch ($data['status']) {
            case 'COMPLETED':
                $command->setRefundData([
                    'id'      => $data['id'],
                    'status'  => $data['status'],
                    'created' => time()
                ]);
                break;
            case 'FAILED':
                add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                    return [
                        'status'  => 'KO',
                        'message' => sprintf(
                        /* translators: %s: Reason for a refund failure */
                            __('PayPal refund failed. Reason: %s', 'team-booking'),
                            $data['status_details']['reason'] ?? ''
                        )
                    ];
                });
                add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                    return 400;
                });

                break;
            case 'PENDING':
                add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                    return [
                        'status'  => 'KO',
                        'message' => sprintf(
                        /* translators: %s: Reason for a refund pending status */
                            __('PayPal refund pending. Reason: %s', 'team-booking'),
                            $data['status_details']['reason'] ?? ''
                        )
                    ];
                });
                add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                    return 400;
                });

                break;
        }
    }

    public static function refundPayment($transactionId)
    {
        $settings = self::_get_settings();

        $url = 'https://api-m.sandbox.paypal.com/v2/payments/captures/' . $transactionId . '/refund';

        try {
            $response = \Httpful\Request::post($url)
                ->expectsJson()
                ->sendsJson()
                ->addHeader('PayPal-Request-Id', $transactionId . '-refund')
                ->authenticateWith(
                    $settings['sandbox'] ? $settings['clientIdTest'] : $settings['clientId'],
                    $settings['sandbox'] ? $settings['clientSecretTest'] : $settings['clientSecret'])
                ->send();

            return (array)$response->body;
        } catch (ConnectionErrorException $e) {

            error_log('PayPal refund error: connection error.');
            Tools::log_dump($e);

            return FALSE;
        }
    }

    public static function isRestAppReady(): bool
    {
        $settings = self::_get_settings();

        return $settings['sandbox'] ? ($settings['clientIdTest'] && $settings['clientSecretTest']) : $settings['clientId'] && $settings['clientSecret'];
    }

    /**
     * @param string $reservation_id
     *
     * @return string
     */
    public static function processPayment(string $reservation_id): string
    {
        $settings = self::_get_settings();

        $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);

        if (!$reservation) {
            error_log("PayPal payment process failed: reservation not found. ID: " . $reservation_id);

            return '';
        }

        $customer = Customers::provideBy(['id' => $reservation->customerId], TRUE);

        if (!$customer) {
            error_log("PayPal payment process failed: customer not found. ID: " . $reservation->customerId);

            return '';
        }

        $items = [];

        $service = Services::provideBy(['id' => $reservation->serviceId], TRUE);

        if (!$service) {
            error_log("PayPal payment process failed: service not found. ID: " . $reservation->serviceId);

            return '';
        }

        $items[] = [
            'name'     => $service->name . ' '
                . Functions::date_formatter($reservation->start)['date']
                . ' ' . sprintf(
                /* translators: 1: reservation start time 2: reservation end time */
                    __('from %1$s to %2$s', 'team-booking'),
                    Functions::date_formatter($reservation->start)['time'],
                    Functions::date_formatter($reservation->end)['time']
                ),
            'quantity' => 1,
            'amount'   => Functions::reservation_get_final_price($reservation_id)->inclusive()->getAmount()->toFloat(),
        ];

        // Prepare GET data
        $query = [];

        $redirectUrl = REST_Controller::get_root_rest_url()
            . PaymentGatewaysRoute::getPath() . self::$id . '/redirect/?res_id='
            . $reservation_id . '&status=success'
            . '&tbk-hash=' . md5($reservation_id . $customer['access_token']);

        #$query['notify_url'] = 'https://b13b-93-42-71-39.ngrok.io/wp-json/thebooking/v1/backend/payment_gateways/' . self::$id . '/listen/';
        $query['notify_url'] = REST_Controller::get_root_rest_url() . PaymentGatewaysRoute::getPath() . self::$id . '/listen/';
        if (count($items) > 1) {
            $query['cmd']    = '_cart';
            $query['upload'] = '1';
            $i               = 1;
            foreach ($items as $item) {
                $query[ 'item_name_' . $i ] = $item['name'];
                $query[ 'quantity_' . $i ]  = $item['quantity'];
                $query[ 'amount_' . $i ]    = $item['amount'];
                $i++;
            }
        } else {
            $query['cmd']       = '_xclick';
            $query['item_name'] = $items[0]['name'];
            $query['quantity']  = $items[0]['quantity'];
            $query['amount']    = $items[0]['amount'];
        }
        $query['cbt']           = sprintf(
        /* translators: %s: Name of the website */
            __('Return to %s', 'team-booking'),
            get_bloginfo('name')
        );
        $query['currency_code'] = vshm()->settings->get(CurrencyCode::ID);
        $query['business']      = $settings['accountEmail'];
        $image_url              = wp_get_attachment_image_src($settings['logo']);
        if (is_array($image_url)) {
            $query['image_url'] = $image_url[0];
        }
        $query['custom']  = $reservation_id;
        $query['return']  = $redirectUrl;
        $query['charset'] = 'UTF-8';
        $query['lc']      = self::getCountryCode();

        // Prepare query string
        $query_string = http_build_query($query);

        // Return
        return self::getPayPalUrl() . $query_string;
    }

    /**
     * @return string
     */
    private static function getPayPalUrl(): string
    {
        $settings = self::_get_settings();

        return $settings['sandbox'] ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?' : 'https://www.paypal.com/cgi-bin/webscr?';
    }

    /**
     * @return string
     */
    private static function getCountryCode(): string
    {
        $pp_locales = [
            'AU',
            'AT',
            'BE',
            'BR',
            'CA',
            'CH',
            'CN',
            'DE',
            'ES',
            'GB',
            'FR',
            'IT',
            'NL',
            'PL',
            'PT',
            'RU',
            'US',
            'da_DK',
            'he_IL',
            'id_ID',
            'ja_JP',
            'no_NO',
            'pt_BR',
            'ru_RU',
            'sv_SE',
            'th_TH',
            'tr_TR',
            'zh_CN',
            'zh_HK',
            'zh_TW',
        ];
        $wp_locale  = get_locale();
        if (in_array(get_locale(), $pp_locales, TRUE)) {
            return $wp_locale;
        }

        $wp_cc = substr($wp_locale, -2);
        if (in_array($wp_cc, $pp_locales, TRUE)) {
            return $wp_cc;
        }

        return 'US';
    }

    public static function getGatewayId(): string
    {
        return self::$id;
    }

    public static function getFrontendProps($gateways)
    {
        $options = self::_get_settings();
        if ($options['active']) {
            $gateways[] = [
                'id'          => self::$id,
                'label'       => self::getLabel(),
                'description' => __('Direct payment', 'team-booking'),
                'img'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M111.4 295.9c-3.5 19.2-17.4 108.7-21.5 134-.3 1.8-1 2.5-3 2.5H12.3c-7.6 0-13.1-6.6-12.1-13.9L58.8 46.6c1.5-9.6 10.1-16.9 20-16.9 152.3 0 165.1-3.7 204 11.4 60.1 23.3 65.6 79.5 44 140.3-21.5 62.6-72.5 89.5-140.1 90.3-43.4.7-69.5-7-75.3 24.2zM357.1 152c-1.8-1.3-2.5-1.8-3 1.3-2 11.4-5.1 22.5-8.8 33.6-39.9 113.8-150.5 103.9-204.5 103.9-6.1 0-10.1 3.3-10.9 9.4-22.6 140.4-27.1 169.7-27.1 169.7-1 7.1 3.5 12.9 10.6 12.9h63.5c8.6 0 15.7-6.3 17.4-14.9.7-5.4-1.1 6.1 14.4-91.3 4.6-22 14.3-19.7 29.3-19.7 71 0 126.4-28.8 142.9-112.3 6.5-34.8 4.6-71.4-23.8-92.6z"/></svg>'
            ];
        }

        return $gateways;
    }

    public static function getLabel()
    {
        return __('PayPal', 'team-booking');
    }

    public static function prepareGateway(string $link, string $reservationId): string
    {
        return self::processPayment($reservationId);
    }

    public static function verifyCurrency($code): bool
    {
        $supported_currencies = [
            'AUD',
            'BRL',
            'CAD',
            'CNY',
            'CZK',
            'DKK',
            'EUR',
            'HKD',
            'HUF',
            'ILS',
            'JPY',
            'MYR',
            'MXN',
            'TWD',
            'NZD',
            'NOK',
            'PHP',
            'PLN',
            'GBP',
            'RUB',
            'SGD',
            'SEK',
            'CHF',
            'THB',
            'USD',
        ];

        return in_array($code, $supported_currencies);
    }

    public static function listenerIPN($post_data, $raw_post_data): void
    {
        $settings = self::_get_settings();

        // Log IPN errors
        if ($settings['debugIpn']) {
            ini_set('log_errors', TRUE);
            ini_set('error_log', __DIR__ . '/PayPal/ipn_errors.log');
        }

        $listener              = new Listener();
        $listener->use_sandbox = (bool)$settings['sandbox'];

        // try to process the IPN POST
        try {
            $listener->requirePostMethod();
            $verified = $listener->processIpn($post_data);
        } catch (\Exception $e) {
            // Log IPN errors
            if ($settings['debugIpn']) {
                error_log($e->getMessage());
                $outputFile = __DIR__ . '/ipn_dump.log';
                $filehandle = fopen($outputFile, 'ab') or die();
                fwrite($filehandle, Tools::stringify_dump($post_data));
                fclose($filehandle);
            }

            return;
        }

        if ($verified) {
            if (isset($post_data['charset'])) {
                $charset = $post_data['charset'];
                // If not UTF-8, convert all the values
                if (Tools::mb_strtoupper($charset) !== 'UTF-8') {
                    foreach ($post_data as $key => &$value) {
                        $value = Tools::mb_convert_encoding($value, 'UTF-8', $charset);
                    }
                    unset($value);
                }
                // Store the charset values for future implementation
                $post_data['charset']          = 'UTF-8';
                $post_data['charset_original'] = $charset;
            }

            $errmsg   = '';   // stores errors from fraud checks
            $currency = vshm()->settings->get(CurrencyCode::ID);

            $reservation = Reservations::provideBy(['id' => $post_data['custom']], TRUE);

            if (!$reservation) {
                $errmsg .= 'Reservation not found: ';
                $errmsg .= $post_data['custom'] . "\n";
            } else {

                /** @var $amount  \Whitecube\Price\Price */
                $amount          = Functions::reservation_get_final_price($reservation->id)->inclusive();
                $reservationData = array_column(ReservationsData::provideBy(['reservation_id' => $reservation->id]), 'value', 'key');

                if (isset($reservationData[ \VSHM\Settings\Reservation\CurrencyCode::ID ])) {
                    /**
                     * Payment should have the same currency code. Check?
                     */
                    $currency = $reservationData[ \VSHM\Settings\Reservation\CurrencyCode::ID ];
                }
            }

            // 1. Make sure the payment status is "Completed"
            $multi_currency = FALSE;
            if ($post_data['payment_status'] !== 'Completed') {
                if ($post_data['payment_status'] === 'Pending' && $post_data['pending_reason'] === 'multi_currency') {
                    // Warning: the PayPal account
                    // settings requires manual confirmation of payments
                    // with currency that merchant doesn't hold
                    $body = "WARNING: You are asking for payments in a currency that your PayPal account doesn't hold! Please either change the currency, or change your PayPal -> Profile -> Payment receiving preferences for 'Allow payments sent to me in a currency I do not hold' to 'Yes, accept and convert them'.\n\n";
                    $body .= $listener->getTextReport();
                    wp_mail(get_bloginfo('admin_email'), 'Currency not held by your PayPal account', $body);
                    $multi_currency = TRUE;
                    unset($body);
                } else {
                    return;
                }
            }
            // 2. Make sure seller email matches your primary account email.
            $receiver_check = !$settings['primaryEmail'] ? $settings['accountEmail'] : $settings['primaryEmail'];
            if (strtolower($post_data['receiver_email']) !== strtolower($receiver_check)) {
                $errmsg .= "'receiver_email' does not match: \n";
                $errmsg .= $post_data['receiver_email'] . "\n";
                $errmsg .= $settings['primaryEmail'] . "\n";
            }
            // 3. Make sure the amount(s) paid match
            $paid_amount = $post_data['mc_gross'];
            $taxes       = 0.00;
            if (isset($post_data['tax']) && is_numeric($post_data['tax'])) {
                $taxes = $post_data['tax'];
            }
            if (!\Whitecube\Price\Price::parse($paid_amount - $taxes, $currency)->equals($amount)) {
                $errmsg .= "'mc_gross' does not match: ";
                $errmsg .= $paid_amount . "\n";
                $errmsg .= $amount . "\n";
            }
            // 4. Make sure the currency code matches
            if ($post_data['mc_currency'] !== $currency) {
                $errmsg .= "'mc_currency' does not match: ";
                $errmsg .= $post_data['mc_currency'] . "\n";
                $errmsg .= $currency . "\n";
            }
            // 5. Ensure the transaction is not a duplicate.
            $txn_id = $post_data['txn_id'];

            $reservationsData = ReservationsData::provideBy(['key' => 'paymentDetails']);
            foreach ($reservationsData as $details) {
                if (isset($details['value']['txn_id']) && $details['value']['txn_id'] === $txn_id) {
                    $errmsg .= "'txn_id' has already been processed: " . $txn_id . "\n";
                    $errmsg .= "Reservation id: " . $details['reservation_id'] . "\n";
                    $errmsg .= "Payment id: " . $details['value']['payment_id_internal'] . "\n";
                    break;
                }
            }

            if (!empty($errmsg)) {
                // manually investigate errors from the fraud checking
                $body = "IPN failed fraud checks: \n$errmsg\n\n";
                $body .= $listener->getTextReport();
                Tools::log_dump($body);
            } else {
                $payment_details_array = [];
                if ($multi_currency) {
                    $payment_details_array['notes'] = esc_html__('Done in a currency not held by your PayPal account at the moment of the transaction.', 'team-booking');
                }
                $paymentId                                    = Tools::generate_token();
                $payment_details_array['txn_id']              = $txn_id;
                $payment_details_array['paid_amount']         = $paid_amount;
                $payment_details_array['payer_email']         = $post_data['payer_email'];
                $payment_details_array['gateway']             = self::$id;
                $payment_details_array['created']             = time();
                $payment_details_array['payment_id_internal'] = $paymentId;

                vshm()->bus->dispatch(new RegisterPayment(
                        $amount,
                        self::$id,
                        [$reservation->id],
                        $payment_details_array
                    )
                );
            }
            if ($settings['debugIpn']) {
                $outputFile = __DIR__ . '/ipn_dump.log';
                $filehandle = fopen($outputFile, 'wb') or die();
                fwrite($filehandle, Tools::stringify_dump($errmsg));
                fwrite($filehandle, '<br>');
                fwrite($filehandle, Tools::stringify_dump($post_data));
                fclose($filehandle);
            }
        } else {
            // manually investigate the invalid IPN
            Tools::log_dump($listener->getTextReport());
        }
    }

    public static function getSettingsPanel($panel): \VSHM\UI\Admin\SidebarItem
    {
        $menu_item_sub = \VSHM\UI\Admin\SidebarItem::option(__('PayPal', 'team-booking'), 'payment-gateways-paypal');

        $paypal_panel = \VSHM\UI\Admin\Plugin\CustomSettingsPanel::get(__('PayPal Settings', 'team-booking'), 'tbk-paypal-settings');
        $paypal_panel->setDescription(__('Use of either Business or Premium PayPal account is recommended to avoid issues, as you are not supposed to make commercial transactions with a Personal PayPal account.', 'team-booking'));
        $paypal_panel->setIconUrl(vshm()->plugin['URL'] . '/Assets/paypal_logo.jpg');

        $paypal_panel->setEndpoint(\VSHM\Routes\PaymentGatewaysRoute::getPath() . self::$id . '/');

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Active', 'team-booking'), self::$id . '|' . 'active');
        $setting->setDescription(__('Use this payment gateway to accept online payments.', 'team-booking'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Account email', 'team-booking'), self::$id . '|' . 'accountEmail');
        $setting->setDescription(__('Payments will be addressed to this email.', 'team-booking'));
        $setting->setAlert(Alert::info(__('If you are using the Sandbox for testing, ensure to put here an email address generated in the Sandbox instead of your live account, or the payment notifications will fail.', 'team-booking')));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Primary email (optional)', 'team-booking'), self::$id . '|' . 'primaryEmail');
        $setting->setDescription(__('Payments will be checked against this email. Use this setting if your PayPal account handles multiple email addresses and the payments are not supposed to go into the primary one.', 'team-booking'));
        $setting->setAlert(Alert::info(__('Ensure that your primary PayPal email address is correct, otherwise payment notifications will fail.', 'team-booking')));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Use sandbox', 'team-booking'), self::$id . '|' . 'sandbox');
        $setting->setDescription(__('Activate the sandbox to test payments.', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Redirect URL', 'team-booking'), self::$id . '|' . 'redirectUrl');
        $setting->setDescription(__('After payment, the customer will be redirected to this URL.', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('API Client ID', 'team-booking'), self::$id . '|' . 'clientId');
        $setting->setDescription(__('Required to enable refunds', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('API Client Secret', 'team-booking'), self::$id . '|' . 'clientSecret');
        $setting->setDescription(__('Required to enable refunds', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('API Client ID (test)', 'team-booking'), self::$id . '|' . 'clientIdTest');
        $setting->setDescription(__('Required to enable refunds', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('API Client Secret (test)', 'team-booking'), self::$id . '|' . 'clientSecretTest');
        $setting->setDescription(__('Required to enable refunds', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Debug (IPN)', 'team-booking'), self::$id . '|' . 'debugIpn');
        $setting->setDescription(__('Activate this setting to dump IPN requests from PayPal in case of issues.', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $paypal_panel->addSubItem($setting);

        $menu_item_sub->setContent($paypal_panel);
        $panel->addItem($menu_item_sub);

        return $panel;
    }
}
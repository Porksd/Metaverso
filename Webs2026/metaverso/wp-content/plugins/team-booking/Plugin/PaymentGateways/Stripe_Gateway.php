<?php

namespace VSHM\Plugin\PaymentGateways;

use VSHM\Bus\RefundReservation;
use VSHM\Bus\RegisterPayment;
use VSHM\Functions;
use VSHM\Providers\Customers;
use VSHM\Providers\Reservations;
use VSHM\Providers\ReservationsData;
use VSHM\Providers\Services;
use VSHM\Providers\ServicesData;
use VSHM\REST_Controller;
use VSHM\Routes\PaymentGateways\Prepare;
use VSHM\Routes\PaymentGateways\Refund;
use VSHM\Routes\PaymentGatewaysRoute;
use VSHM\Routes\SaveSettingsRoute;
use VSHM\Settings\CurrencyCode;
use VSHM\Settings\Service\Redirect;
use VSHM\Settings\Service\RedirectUrl;
use VSHM\Tools;
use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Settings_Content;
use VSHM\UI\Admin\Settings_Dependency;

defined('ABSPATH') || exit;

/**
 * Class Stripe_Gateway
 */
class Stripe_Gateway implements PaymentGateway
{
    private static $id = 'stripe';

    public const OPTIONS_TAG = 'tbk_stripe_settings';

    public function __construct()
    {
    }

    private static function _get_settings(): array
    {
        $defaults = [
            'active'        => FALSE,
            'sandbox'       => FALSE,
            'secretKey'     => '',
            'pubKey'        => '',
            'secretKeyTest' => '',
            'pubKeyTest'    => '',
            'sendReceipt'   => FALSE,
            'iDeal'         => TRUE,
            'redirectUrl'   => '',
            'loadLibrary'   => TRUE,
            'webhookSecret' => '',
            'webhookId'     => ''
        ];

        return get_option(self::OPTIONS_TAG, $defaults) + $defaults;
    }

    public static function subscribe(): void
    {
        add_filter('tbk_payment_gateway_config', [self::class, 'getSettings'], 10, 2);
        add_filter('tbk_backend_collecting_payment_gateways_panels', [self::class, 'getSettingsPanel']);
        add_action('vshm_dispatching_SaveSettings', [self::class, 'saveBackendSettings']);
        add_action('vshm_dispatching_RefundReservation', [self::class, 'refundReservation']);
        add_action('vshm_payment_gateways_listen_' . self::$id, [self::class, 'listenerIPN'], 10, 2);
        add_action('vshm_payment_gateways_redirect_' . self::$id, [self::class, 'redirect']);
        add_filter('tbk_payment_gateways_frontend_choice', [self::class, 'getFrontendProps']);
        add_action('permalink_structure_changed', [self::class, 'updateWebhook'], 10, 2);
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
        $settings      = self::_get_settings();

        $reservation = Reservations::provideBy(['id' => $reservationId], TRUE);

        if (!$reservation) {
            return REST_Controller::get_error_response('stripe_redirect');
        }

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

    public static function updateWebhook($old_perm, $new_perm): void
    {
        $settings = self::_get_settings();
        if ($settings['webhookId']) {
            \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
            $stripe = new \Stripe\StripeClient($settings['sandbox'] ? $settings['secretKeyTest'] : $settings['secretKey']);
            try {
                $updated = $stripe->webhookEndpoints->update(
                    $settings['webhookId'],
                    ['url' => REST_Controller::get_root_rest_url() . PaymentGatewaysRoute::getPath() . self::$id . '/listen/']
                );
            } catch (\Stripe\Exception\ApiErrorException|\Exception  $e) {
                Tools::log_dump($e);
            }
        }
    }

    public static function deleteWebhook(): void
    {
        $settings = self::_get_settings();
        if ($settings['webhookId']) {
            \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
            $stripe = new \Stripe\StripeClient($settings['sandbox'] ? $settings['secretKeyTest'] : $settings['secretKey']);
            try {
                $webhook = $stripe->webhookEndpoints->delete(
                    $settings['webhookId'],
                    []
                );
            } catch (\Stripe\Exception\ApiErrorException|\Exception  $e) {
                Tools::log_dump($e);
            }
        }
    }

    public static function refundReservation(RefundReservation $command): void
    {
        if ($command->getGatewayId() !== self::$id) {
            return;
        }

        $options = self::_get_settings();

        if (!$options['active']) {
            add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                return [
                    'status'  => 'KO',
                    'message' => __('Stripe gateway is not active.', 'team-booking')
                ];
            });
            add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                return 403;
            });

            return;
        }

        $paymentData = ReservationsData::provideBy(['reservation_id' => $command->getId(), 'key' => 'paymentDetails'], TRUE);

        if (!$paymentData || !isset($paymentData['transaction_id'])) {
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

        $data = self::refundPayment($paymentData['transaction_id']);

        if ($data === FALSE) {
            add_filter('vshm_' . Refund::getPath() . '_response', static function ($args) {
                return [
                    'status'  => 'KO',
                    'message' => __('Stripe API error.', 'team-booking')
                ];
            });
            add_filter('vshm_' . Refund::getPath() . '_response_code', static function ($code) {
                return 400;
            });

            return;
        }

        $command->setRefundData([
            'id'      => $data['id'],
            'status'  => $data['status'],
            'charge'  => $data['charge'],
            'created' => time()
        ]);
    }

    public static function refundPayment($paymentIntent)
    {
        $options = self::_get_settings();

        \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
        $stripe = new \Stripe\StripeClient($options['sandbox'] ? $options['secretKeyTest'] : $options['secretKey']);
        try {
            $refund = $stripe->refunds->create(['payment_intent' => $paymentIntent]);

            return $refund->toArray();
        } catch (\Stripe\Exception\ApiErrorException|\Exception $e) {
            Tools::log_dump($e);

            return FALSE;
        }
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

            /**
             *  Checking that at least a couple of API keys are provided
             */
            if ($options['active'] && !(($options['pubKey'] && $options['secretKey']) || ($options['pubKeyTest'] && $options['secretKeyTest']))) {
                $options['active'] = FALSE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("Stripe gateway can't be activated without providing Live (or Test) API keys");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'active' => FALSE
                    ];

                    return $response;
                });
            }

            /**
             *  Forcing sandbox at activation when only test keys are provided
             */
            if (isset($to_save['active']) && $to_save['active'] && ($options['pubKeyTest'] && $options['secretKeyTest']) && (!$options['pubKey'] || !$options['secretKey'])) {
                $options['sandbox'] = TRUE;
            }

            /**
             *  If the sandbox is active, test API keys must be provided
             */
            if ($options['active'] && $options['sandbox'] && !($options['pubKeyTest'] && $options['secretKeyTest'])) {
                $options['sandbox'] = FALSE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("Stripe test mode can't be activated without providing Test API keys");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'sandbox' => FALSE
                    ];

                    return $response;
                });
            }

            /**
             *  If the sandbox is not active, live API keys must be provided
             */
            if ($options['active'] && isset($to_save['sandbox']) && !$to_save['sandbox'] && !($options['pubKey'] && $options['secretKey'])) {
                $options['sandbox'] = TRUE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("Stripe test mode can't be deactivated without providing Live API keys");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'sandbox' => TRUE
                    ];

                    return $response;
                });
            }

            /**
             *  If the currency is not supported, deactivate the gateway
             */
            if ($options['active'] && !self::verifyCurrency($currencyCode)) {
                $options['active'] = FALSE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("Stripe gateway is deactivated, as it doesn't support the current currency.");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'active' => FALSE
                    ];

                    return $response;
                });
            }

            /**
             *  IDEAL only supports EUR
             */

            if ($options['active'] && $options['iDeal'] && $currencyCode !== 'EUR') {

                $options['iDeal'] = FALSE;
                add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                    $response['message']        = __("Stripe iDEAL only supports EUR currency.");
                    $response['messageType']    = 'warning';
                    $response['changesToForce'] = [
                        self::$id . '|' . 'iDeal' => FALSE
                    ];

                    return $response;
                });
            }

            // Maybe Create Webhook
            if ($to_save && $options['active'] && !($options['webhookId'])) {
                \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
                $stripe = new \Stripe\StripeClient($options['sandbox'] ? $options['secretKeyTest'] : $options['secretKey']);
                try {
                    $endpoint = $stripe->webhookEndpoints->create([
                        'url'            => REST_Controller::get_root_rest_url() . PaymentGatewaysRoute::getPath() . self::$id . '/listen/',
                        'description'    => "TheBooking Stripe webhook",
                        'enabled_events' => [
                            ['checkout.session.completed']
                        ],
                    ]);

                    $options['webhookSecret'] = $endpoint->secret;
                    $options['webhookId']     = $endpoint->id;

                } catch (\Stripe\Exception\ApiErrorException|\Exception  $e) {
                    $options['active']    = FALSE;
                    $options['webhookId'] = '';

                    add_filter('vshm_' . SaveSettingsRoute::getSaveSettingsPath() . '_response', static function ($response) {
                        $response['message']        = __("Stripe webhook can't be created. Check the error.log for additional info.");
                        $response['messageType']    = 'warning';
                        $response['changesToForce'] = [
                            self::$id . '|' . 'active' => FALSE
                        ];

                        return $response;
                    });
                    Tools::log_dump($e->getError());
                }
            }

            if (!$options['active']) {
                self::deleteWebhook();
                $options['webhookId']     = '';
                $options['webhookSecret'] = '';
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


    /**
     * @param string $reservation_id
     *
     * @return string
     */
    public static function processPayment(string $reservation_id): string
    {
        $settings = self::_get_settings();
        \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
        $stripe = new \Stripe\StripeClient($settings['sandbox'] ? $settings['secretKeyTest'] : $settings['secretKey']);

        $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);

        if (!$reservation) {
            error_log("Stripe payment process failed: reservation not found. ID: " . $reservation_id);

            return '';
        }

        $payment_intent_data = [
            'metadata' => [
                'tbk_reservation_id' => $reservation_id
            ]
        ];

        $customer = Customers::provideBy(['id' => $reservation->customerId], TRUE);

        if (!$customer) {
            error_log("Stripe payment process failed: customer not found. ID: " . $reservation->customerId);

            return '';
        }

        if ($settings['sendReceipt'] && isset($customer['email'])) {
            $payment_intent_data['receipt_email'] = $customer['email'];
        }

        $line_items = [];
        $service    = Services::provideBy(['id' => $reservation->serviceId], TRUE);

        if (!$service) {
            error_log("Stripe payment process failed: service not found. ID: " . $reservation->serviceId);

            return '';
        }

        $line_items[] = [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => vshm()->settings->get(CurrencyCode::ID),
                'unit_amount'  => Functions::reservation_get_final_price($reservation_id)->inclusive()->getMinorAmount()->toInt(),
                'product_data' => [
                    'name'        => sprintf(
                    /* translators: %s: Name of the service */
                        __('Reservation for %s', 'team-booking'),
                        $service->name
                    ),
                    'description' => Functions::date_formatter($reservation->start)['date']
                        . ' ' . sprintf(
                        /* translators: 1: reservation start time 2: reservation end time */
                            __('from %1$s to %2$s', 'team-booking'),
                            Functions::date_formatter($reservation->start)['time'],
                            Functions::date_formatter($reservation->end)['time']
                        ),
                ]
            ]
        ];

        try {
            $redirectUrl   = REST_Controller::get_root_rest_url()
                . PaymentGatewaysRoute::getPath() . self::$id
                . '/redirect/?res_id=' . $reservation_id
                . '&tbk-hash=' . md5($reservation_id . $customer['access_token']);
            $sessionConfig = [
                'payment_method_types' => $settings['iDeal'] ? ['card', 'ideal'] : ['card'],
                'payment_intent_data'  => $payment_intent_data,
                'line_items'           => $line_items,
                'cancel_url'           => $redirectUrl . '&status=cancel',
                'mode'                 => 'payment',
                'success_url'          => $redirectUrl . '&status=success'
            ];

            $session = $stripe->checkout->sessions->create($sessionConfig);

            return $session->url;
        } catch (\Stripe\Exception\ApiErrorException|\Exception  $e) {
            error_log('Stripe Checkout session failed.');
            Tools::log_dump($e->getError()->message);

            return '';
        }
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
                'label'       => __('Card', 'team-booking'),
                'description' => __('Direct payment', 'team-booking'),
                'img'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--! Font Awesome Pro 6.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M64 32C28.7 32 0 60.7 0 96v32H576V96c0-35.3-28.7-64-64-64H64zM576 224H0V416c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V224zM112 352h64c8.8 0 16 7.2 16 16s-7.2 16-16 16H112c-8.8 0-16-7.2-16-16s7.2-16 16-16zm112 16c0-8.8 7.2-16 16-16H368c8.8 0 16 7.2 16 16s-7.2 16-16 16H240c-8.8 0-16-7.2-16-16z"/></svg>'
            ];
        }

        return $gateways;
    }

    public static function getLabel()
    {
        return __('Stripe', 'team-booking');
    }

    public static function prepareGateway(string $link, string $reservationId): string
    {
        $url = self::processPayment($reservationId);

        if (!$url) {
            add_filter('vshm_' . Prepare::getPath() . '_response', static function ($args) {
                return [
                    'status'  => 'KO',
                    'message' => __('Payment error.', 'team-booking')
                ];
            });
            add_filter('vshm_' . Prepare::getPath() . '_response_code', static function ($code) {
                return 400;
            });
        }

        return $url;
    }

    public static function verifyCurrency($code): bool
    {
        $supported_currencies = [
            'AED',
            'USD',
            'AFN',
            'ALL',
            'AMD',
            'ANG',
            'AOA',
            'ARS',
            'AUD',
            'AWG',
            'AZN',
            'BAM',
            'BBD',
            'BDT',
            'BGN',
            'BIF',
            'BMD',
            'BND',
            'BOB',
            'BRL',
            'BSD',
            'BWP',
            'BYN',
            'BZD',
            'CAD',
            'CDF',
            'CHF',
            'CLP',
            'CNY',
            'COP',
            'CRC',
            'CVE',
            'CZK',
            'DJF',
            'DKK',
            'DOP',
            'DZD',
            'EGP',
            'ETB',
            'EUR',
            'FJD',
            'FKP',
            'GBP',
            'GEL',
            'GIP',
            'GMD',
            'GNF',
            'GTQ',
            'GYD',
            'HKD',
            'HNL',
            'HRK',
            'HTG',
            'HUF',
            'IDR',
            'ILS',
            'INR',
            'ISK',
            'JMD',
            'JPY',
            'KES',
            'KGS',
            'KHR',
            'KMF',
            'KRW',
            'KYD',
            'KZT',
            'LAK',
            'LBP',
            'LKR',
            'LRD',
            'LSL',
            'MAD',
            'MDL',
            'MGA',
            'MKD',
            'MMK',
            'MNT',
            'MOP',
            'MRO',
            'MUR',
            'MVR',
            'MWK',
            'MXN',
            'MYR',
            'MZN',
            'NAD',
            'NGN',
            'NIO',
            'NOK',
            'NPR',
            'NZD',
            'PAB',
            'PEN',
            'PGK',
            'PHP',
            'PKR',
            'PLN',
            'PYG',
            'QAR',
            'RON',
            'RSD',
            'RUB',
            'RWF',
            'SAR',
            'SBD',
            'SCR',
            'SEK',
            'SGD',
            'SHP',
            'SLL',
            'SOS',
            'SRD',
            'STD',
            'SZL',
            'THB',
            'TJS',
            'TOP',
            'TRY',
            'TTD',
            'TWD',
            'TZS',
            'UAH',
            'UGX',
            'UYU',
            'UZS',
            'VND',
            'VUV',
            'WST',
            'XAF',
            'XCD',
            'XOF',
            'XPF',
            'YER',
            'ZAR',
            'ZMW'
        ];

        return in_array($code, $supported_currencies);
    }

    public static function listenerIPN($post_data, $raw_post_data): void
    {
        $settings = self::_get_settings();

        \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
        $stripe = new \Stripe\StripeClient($settings['sandbox'] ? $settings['secretKeyTest'] : $settings['secretKey']);

        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event      = NULL;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $raw_post_data,
                $sig_header,
                $settings['webhookSecret']
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Tools::log_dump($e->getMessage());

            return;
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Tools::log_dump($e->getMessage());

            return;
        }

        if ($event->type === 'checkout.session.completed') {
            $checkoutSessionCompleted = $event->data->object;
            /** @var $checkoutSessionCompleted \Stripe\Checkout\Session */
            try {

                $paymentIntent = $stripe->paymentIntents->retrieve($checkoutSessionCompleted->payment_intent, []);

                if (!isset($paymentIntent->metadata['tbk_reservation_id'])) {
                    // Not a TheBooking webhook
                    return;
                }

                $reservation_id = $paymentIntent->metadata['tbk_reservation_id'];

            } catch (\Stripe\Exception\ApiErrorException $e) {
                Tools::log_dump($e->getMessage());

                return;
            }

            // TODO: checks stuff...
            $paymentId                                    = Tools::generate_token();
            $payment_details_array                        = [];
            $payment_details_array['gateway']             = self::$id;
            $payment_details_array['created']             = time();
            $payment_details_array['payment_id_internal'] = $paymentId;
            $payment_details_array['transaction_id']      = $paymentIntent->id;
            $payment_details_array['currency']            = strtoupper($checkoutSessionCompleted->currency);
            $currency                                     = strtoupper($checkoutSessionCompleted->currency);
            $payment_details_array['paid_amount']         = $paymentIntent->amount;

            $reservation = Reservations::provideBy(['id' => $reservation_id], TRUE);

            if (!$reservation) {
                error_log('Stripe Payment received for a Reservation ID not found. ID: ' . $reservation_id . ' Payment ID: ' . $paymentIntent->id);

                return;
            }

            $amount          = Functions::reservation_get_final_price($reservation_id)->inclusive();
            $reservationData = array_column(ReservationsData::provideBy(['reservation_id' => $reservation_id]), 'value', 'key');

            if (isset($reservationData[ \VSHM\Settings\Reservation\CurrencyCode::ID ])) {
                /**
                 * Payment should have the same currency code. Check?
                 */
                $currency = $reservationData[ \VSHM\Settings\Reservation\CurrencyCode::ID ];
            }
            $paidAmount = \Whitecube\Price\Price::ofMinor($paymentIntent->amount, $currency);

            if (!$paidAmount->equals($amount)) {
                //TODO
                error_log('Amounts do not match.');
                Tools::log_dump($paidAmount);
                Tools::log_dump($amount);

                return;
            }

            vshm()->bus->dispatch(new RegisterPayment(
                    $paidAmount,
                    self::$id,
                    [$reservation_id],
                    $payment_details_array
                )
            );
        }

    }

    public static function getSettingsPanel($panel): \VSHM\UI\Admin\SidebarItem
    {
        $menu_item_sub = \VSHM\UI\Admin\SidebarItem::option(__('Stripe', 'team-booking'), 'payment-gateways-stripe');

        $stripe_panel = \VSHM\UI\Admin\Plugin\CustomSettingsPanel::get(__('Stripe Settings', 'team-booking'), 'tbk-stripe-settings');
        $stripe_panel->setIconUrl(vshm()->plugin['URL'] . '/Assets/stripe_logo.svg');

        $stripe_panel->setEndpoint(\VSHM\Routes\PaymentGatewaysRoute::getPath() . self::$id . '/');

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Active', 'team-booking'), self::$id . '|' . 'active');
        $setting->setDescription(__('Use this payment gateway to accept online payments.', 'team-booking'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Publishable Key', 'team-booking'), self::$id . '|' . 'pubKey');
        $setting->setDescription(__('https://dashboard.stripe.com/account/apikeys', 'team-booking'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Secret Key', 'team-booking'), self::$id . '|' . 'secretKey');
        $setting->setDescription(__('https://dashboard.stripe.com/account/apikeys', 'team-booking'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Publishable Key (test)', 'team-booking'), self::$id . '|' . 'pubKeyTest');
        $setting->setDescription(__('https://dashboard.stripe.com/account/apikeys', 'team-booking'));
        $setting->setAlert(Alert::info(__('Used to test payments', 'team-booking')));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Secret Key (test)', 'team-booking'), self::$id . '|' . 'secretKeyTest');
        $setting->setDescription(__('https://dashboard.stripe.com/account/apikeys', 'team-booking'));
        $setting->setAlert(Alert::info(__('Used to test payments', 'team-booking')));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Input::get(__('Redirect URL', 'team-booking'), self::$id . '|' . 'redirectUrl');
        $setting->setDescription(__('After payment, the customer will be redirected to this URL.', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Use sandbox', 'team-booking'), self::$id . '|' . 'sandbox');
        $setting->setDescription(__('Activate the sandbox to test payments.', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Send receipt to the customer', 'team-booking'), self::$id . '|' . 'sendReceipt');
        $setting->setDescription(__('The customer email provided in the reservation form will be used', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('IDEAL support', 'team-booking'), self::$id . '|' . 'iDeal');
        $setting->setDescription(__('Activate this option if you want to use IDEAL', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $stripe_panel->addSubItem($setting);

        $setting = \VSHM\UI\Admin\Settings_Toggle::get(__('Load Stripe.js library', 'team-booking'), self::$id . '|' . 'loadLibrary');
        $setting->setDescription(__('Deactivate this option only if another plugin is loading the same library', 'team-booking'));
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'active'));
        $stripe_panel->addSubItem($setting);


        $settings = self::_get_settings();

        $url = '';
        if ($settings['webhookId']) {
            try {
                \Stripe\Stripe::setAppInfo(vshm()->plugin['NAME'], vshm()->plugin['VERSION']);
                $stripe  = new \Stripe\StripeClient($settings['sandbox'] ? $settings['secretKeyTest'] : $settings['secretKey']);
                $webhook = $stripe->webhookEndpoints->retrieve(
                    $settings['webhookId'],
                    []
                );
                $url     = $webhook->url;
            } catch (\Stripe\Exception\ApiErrorException|\Exception  $e) {
                Tools::log_dump($e->getMessage());
            }
        }

        $setting = \VSHM\UI\Admin\Settings_Informative::get(__('Stripe webhook URL', 'team-booking'));
        $setting->setDescription(sprintf(
                __('This is the URL where Stripe notifies the payments. It must be equal to %s, otherwise go in your Stripe account dashboard and change the URL.', 'team-booking'),
                REST_Controller::get_root_rest_url() . PaymentGatewaysRoute::getPath() . self::$id . '/listen/'
            )
        );
        $setting->addDependency(Settings_Dependency::TRUTHY(self::$id . '|' . 'webhookId'));
        $setting->addContent(Settings_Content::Text($url));
        $stripe_panel->addSubItem($setting);


        $menu_item_sub->setContent($stripe_panel);
        $panel->addItem($menu_item_sub);

        return $panel;
    }
}
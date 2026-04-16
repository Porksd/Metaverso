<?php

namespace VSHM\Plugin\PaymentGateways;

use VSHM\Bus\SaveSettings;
use VSHM\UI\Admin\SidebarItem;

defined('ABSPATH') || exit;

/**
 * Interface PaymentGateway
 *
 * This is the payment gateway interface
 *
 * @author VonStroheim
 */
interface PaymentGateway
{
    public static function subscribe();

    /**
     * The main payment routine, returns a URL to redirect to for payments
     */
    public static function processPayment(string $reservation_id);

    /**
     * It must return the gateway id (i.e."paypal")
     */
    public static function getGatewayId();

    /**
     * It must return the gateway settings
     */
    public static function getSettings($settings, $gatewayId);

    /**
     * It must return the gateway settings panel (items)
     *
     * @param SidebarItem $panel
     */
    public static function getSettingsPanel($panel);

    /**
     * It must return the frontend gateway properties
     */
    public static function getFrontendProps($gateways);

    /**
     * It must return the gateway name
     */
    public static function getLabel();

    /**
     * It prepares the gateway to payment.
     *
     * @param string $link
     * @param string $reservationId
     */
    public static function prepareGateway(string $link, string $reservationId);

    /**
     * This method is called from the gateway to intercept and save its settings.
     *
     * @param SaveSettings $command the changed settings command
     */
    public static function saveBackendSettings($command);

    /**
     * It checks the compatibility of a general currency code
     * with the gateway.
     *
     * It returns TRUE if the code is compatible,
     * FALSE otherwise.
     *
     * @param string $code
     */
    public static function verifyCurrency($code);

    /**
     * The listener for IPN/Webhooks callbacks.
     *
     * Onsite gateways should simply return;
     *
     * @param array  $post_data
     * @param string $raw_post_data
     */
    public static function listenerIPN($post_data, $raw_post_data);
}
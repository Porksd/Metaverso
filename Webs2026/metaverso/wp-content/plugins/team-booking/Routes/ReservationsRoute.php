<?php

namespace VSHM\Routes;

use VSHM\Functions;
use VSHM\Providers\Objects\Reservation;
use VSHM\REST_Controller;
use VSHM\Settings\CurrencyCode;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Class ReservationsRoute
 *
 * @package VSHM\Routes
 */
final class ReservationsRoute implements Route
{
    /**
     * @var string
     */
    private static $path = '/backend/reservations/';

    /**
     * @param Reservation[]|NULL $reservations
     *
     * @return array|null
     */
    public static function prepare_for_frontend($reservations = NULL): ?array
    {
        if (NULL === $reservations) {
            $reservations = \VSHM\Providers\Reservations::provideByWithData();
        }

        /**
         * Those are properties useful to have straight away in the frontend instead of querying for data
         */
        $prices     = array_column(\VSHM\Providers\ReservationsData::provideBy(['key' => \VSHM\Settings\Reservation\Price::ID]), 'value', 'reservation_id');
        $currencies = array_column(\VSHM\Providers\ReservationsData::provideBy(['key' => \VSHM\Settings\Reservation\CurrencyCode::ID]), 'value', 'reservation_id');
        $paid       = array_column(\VSHM\Providers\ReservationsData::provideBy(['key' => \VSHM\Settings\Reservation\Paid::ID]), 'value', 'reservation_id');
        $tickets    = array_column(\VSHM\Providers\ReservationsData::provideBy(['key' => \VSHM\Settings\Reservation\Tickets::ID]), 'value', 'reservation_id');

        $final_prices = Functions::reservations_get_final_prices($reservations);

        $parsed = [];

        foreach ($reservations as $key => $reservation) {

            $parsed[ $key ] = (array)$reservation;

            if (isset($prices[ $reservation->id ])) {
                $parsed[ $key ][ \VSHM\Settings\Reservation\Price::ID ]        = $final_prices[ $reservation->id ]->inclusive()->getAmount();
                $parsed[ $key ][ \VSHM\Settings\Reservation\CurrencyCode::ID ] = $currencies[ $reservation->id ] ?? vshm()->settings->get(CurrencyCode::ID);
            }
            if (isset($paid[ $reservation->id ])) {
                $parsed[ $key ][ \VSHM\Settings\Reservation\Paid::ID ] = filter_var($paid[ $reservation->id ], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($tickets[ $reservation->id ])) {
                $parsed[ $key ][ \VSHM\Settings\Reservation\Tickets::ID ] = (int)$tickets[ $reservation->id ];
            }
            $parsed[ $key ]['humanDuration'] = Tools::human_time_diff($reservation->start, $reservation->end);

            $parsed[ $key ] = apply_filters('tbk_preparing_reservation_for_frontend', $parsed[ $key ]);
        }

        return $parsed;
    }

    public static function register(): void
    {
        REST_Controller::register_routes([
            \VSHM\Routes\Reservations\Get::getPath()                      => \VSHM\Routes\Reservations\Get::get(),
            \VSHM\Routes\Reservations\GetUsersCurrent::getPath()          => \VSHM\Routes\Reservations\GetUsersCurrent::get(),
            \VSHM\Routes\Reservations\GetSingle::getPath()                => \VSHM\Routes\Reservations\GetSingle::get(),
            \VSHM\Routes\Reservations\GetPriceInfo::getPath()             => \VSHM\Routes\Reservations\GetPriceInfo::get(),
            \VSHM\Routes\Reservations\ChangeService::getPath()            => \VSHM\Routes\Reservations\ChangeService::get(),
            \VSHM\Routes\Reservations\ChangeProvider::getPath()           => \VSHM\Routes\Reservations\ChangeProvider::get(),
            \VSHM\Routes\Reservations\ChangeStatus::getPath()             => \VSHM\Routes\Reservations\ChangeStatus::get(),
            \VSHM\Routes\Reservations\ChangeCustomer::getPath()           => \VSHM\Routes\Reservations\ChangeCustomer::get(),
            \VSHM\Routes\Reservations\ChangeDate::getPath()               => \VSHM\Routes\Reservations\ChangeDate::get(),
            \VSHM\Routes\Reservations\ChangeData::getPath()               => \VSHM\Routes\Reservations\ChangeData::get(),
            \VSHM\Routes\Reservations\ChangeFormEntries::getPath()        => \VSHM\Routes\Reservations\ChangeFormEntries::get(),
            \VSHM\Routes\Reservations\DeleteFormEntry::getPath()          => \VSHM\Routes\Reservations\DeleteFormEntry::get(),
            \VSHM\Routes\Reservations\DeleteFile::getPath()               => \VSHM\Routes\Reservations\DeleteFile::get(),
            \VSHM\Routes\Reservations\Cancel::getPath()                   => \VSHM\Routes\Reservations\Cancel::get(),
            \VSHM\Routes\Reservations\Confirm::getPath()                  => \VSHM\Routes\Reservations\Confirm::get(),
            \VSHM\Routes\Reservations\Approve::getPath()                  => \VSHM\Routes\Reservations\Approve::get(),
            \VSHM\Routes\Reservations\Deny::getPath()                     => \VSHM\Routes\Reservations\Deny::get(),
            \VSHM\Routes\Reservations\CancelByCustomer::getPath()         => \VSHM\Routes\Reservations\CancelByCustomer::get(),
            \VSHM\Routes\Reservations\ChangeCancellationReason::getPath() => \VSHM\Routes\Reservations\ChangeCancellationReason::get(),
            \VSHM\Routes\Reservations\RemoveAll::getPath()                => \VSHM\Routes\Reservations\RemoveAll::get(),
            \VSHM\Routes\Reservations\RemoveMulti::getPath()              => \VSHM\Routes\Reservations\RemoveMulti::get(),
            \VSHM\Routes\Reservations\Remove::getPath()                   => \VSHM\Routes\Reservations\Remove::get(),
            \VSHM\Routes\Reservations\FilesGet::getPath()                 => \VSHM\Routes\Reservations\FilesGet::get(),
            \VSHM\Routes\Reservations\Create::getPath()                   => \VSHM\Routes\Reservations\Create::get(),
            \VSHM\Routes\Reservations\RemoveOrder::getPath()              => \VSHM\Routes\Reservations\RemoveOrder::get(),
        ]);
    }

    public static function getPath(): string
    {
        return self::$path;
    }
}
<?php

namespace VSHM\Plugin;

use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\ServiceProviderCustomData;
use VSHM\Settings\Service\Personal_BufferTimespan;

defined('ABSPATH') || exit;

/**
 * Class ReservationsBlockingFactory
 *
 * @package VSHM\Plugin
 * @author  VonStroheim
 */
class ReservationsBlockingFactory
{

    /**
     * @var Reservation[]
     */
    protected $reservations = [];

    public function __construct($reservations, int $providerId)
    {
        foreach ($reservations as $reservation) {

            /** @var $reservation Reservation */

            if ((int)$reservation->providerId === $providerId) {
                $this->reservations[] = $reservation;
            }
        }
    }

    /**
     * @return Reservation[]
     */
    public function get(): array
    {
        return $this->reservations;
    }


    public function getAsPeriodSet($filteringFunction = NULL): PeriodCollection
    {
        $returningRes = $this->reservations;
        if (is_callable($filteringFunction)) {

            foreach ($returningRes as $key => $reservation) {

                /** @var $reservation Reservation */

                if (!$filteringFunction($reservation)) {
                    unset($returningRes[ $key ]);
                }
            }

        }

        $collection = [];

        $serviceBuffers = ServiceProviderCustomData::provideBy(['key' => Personal_BufferTimespan::ID]);

        $filteredServiceBuffer = [];

        foreach ($serviceBuffers as $buffer) {
            $filteredServiceBuffer[ $buffer['provider_id'] . '#' . $buffer['service_id'] ] = $buffer['value'];
        }

        foreach ($returningRes as $returningRe) {

            $buffer = (int)($filteredServiceBuffer[ $returningRe->providerId . '#' . $returningRe->serviceId ] ?? Personal_BufferTimespan::getDefault());

            $collection[ $returningRe->start . '|' . ($returningRe->end + $buffer) ] = Period::make(
                DateTimeTbk::createFromFormatSilently('U', $returningRe->start),
                DateTimeTbk::createFromFormatSilently('U', $returningRe->end + $buffer),
                Precision::SECOND,
                Boundaries::EXCLUDE_ALL
            );
        }

        $mainCollection = PeriodCollection::make(...array_values($collection));

        return $mainCollection;
    }

}
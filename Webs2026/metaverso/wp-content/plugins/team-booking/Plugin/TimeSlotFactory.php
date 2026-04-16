<?php

namespace VSHM\Plugin;

use phpseclib3\Exception\BadConfigurationException;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\Precision;
use VSHM\Functions;
use VSHM\Providers\Objects\Reservation;
use VSHM\Providers\ServicesData;
use VSHM\Settings\Reservation\AvailabilityId;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithOther;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithSame;
use VSHM\Settings\Service\Personal_WhenToClose;
use VSHM\Settings\Service\Personal_WhenToCloseReference;
use VSHM\Settings\Service\Personal_WhenToOpen;
use VSHM\Settings\Service\TotalSlotTickets;

defined('ABSPATH') || exit;

/**
 * Class TimeSlotFactory
 *
 * @package VSHM\Plugin
 * @author  VonStroheim
 */
class TimeSlotFactory
{

    protected $service_id;
    protected $provider_id;
    protected $max_service_tickets;
    protected $max_tickets;
    protected $reference_time;
    protected $when_closed;
    protected $when_opened;

    protected $start;
    protected $end;

    protected $overrides      = [];
    protected $gcal_overrides = [];
    protected $gcal_event_id;

    protected $availability_id;

    protected $overlapping_same;
    protected $overlapping_others;

    protected $buffer = 0;

    protected $now;

    public function __construct($service_id, $provider_id)
    {
        $providersServiceData      = \VSHM\Providers\ServiceProviderCustomData::provideBy([
            'key'         => [
                'operator' => 'IN',
                'value'    => [
                    Personal_WhenToCloseReference::ID,
                    Personal_WhenToClose::ID,
                    Personal_WhenToOpen::ID,
                    Personal_DiscardOverlappingWithSame::ID,
                    Personal_DiscardOverlappingWithOther::ID,
                ]
            ],
            'service_id'  => $service_id,
            'provider_id' => (int)$provider_id
        ]);
        $providersServiceData      = Functions::organize_service_custom_data($providersServiceData)[ (int)$provider_id ][ $service_id ] ?? [];
        $this->service_id          = $service_id;
        $this->provider_id         = (int)$provider_id;
        $this->now                 = new DateTimeTbk();
        $this->reference_time      = $providersServiceData[ Personal_WhenToCloseReference::ID ] ?? Personal_WhenToCloseReference::getDefault();
        $this->when_closed         = $providersServiceData[ Personal_WhenToClose::ID ] ?? Personal_WhenToClose::getDefault();
        $this->when_opened         = $providersServiceData[ Personal_WhenToOpen::ID ] ?? Personal_WhenToOpen::getDefault();
        $this->overlapping_same    = $providersServiceData[ Personal_DiscardOverlappingWithSame::ID ] ?? Personal_DiscardOverlappingWithSame::getDefault();
        $this->overlapping_others  = $providersServiceData[ Personal_DiscardOverlappingWithOther::ID ] ?? Personal_DiscardOverlappingWithOther::getDefault();
        $this->max_service_tickets = (int)ServicesData::provideBy(['service_id' => $service_id, 'key' => TotalSlotTickets::ID], TRUE);
        $this->max_tickets         = $this->max_service_tickets;

    }

    public function setAvailabilityId(string $id): void
    {
        $this->availability_id = $id;
    }

    public function setBuffer($buffer): void
    {
        $this->buffer = $buffer;
    }

    public function setBoundaries(int $start, int $end): void
    {
        $this->start = $start;
        $this->end   = $end;
    }

    public function setGcalOverrides(array $overrides): void
    {
        $this->gcal_overrides = $overrides;
    }

    public function update_gcal_overrides(): void
    {
        $this->overrides   = [];
        $this->max_tickets = $this->max_service_tickets;

        $period = $this->getPeriod();

        foreach ($this->gcal_overrides as $overrideEvent) {
            $overrideEventPeriod = Period::make(
                DateTimeTbk::createFromFormatSilently('U', (int)$overrideEvent['start']),
                DateTimeTbk::createFromFormatSilently('U', (int)$overrideEvent['end']),
                Precision::SECOND,
                Boundaries::EXCLUDE_ALL
            );
            if ($period->overlapsWith($overrideEventPeriod)) {
                foreach ($overrideEvent['properties'] as $property => $value) {
                    switch ($property) {
                        case 'price':
                            break;
                        case 'tickets':
                            $this->max_tickets = (int)$value;
                            break;
                        case 'location':
                            break;
                    }
                    $this->overrides[ $property ] = $value;
                    $this->gcal_event_id          = $overrideEvent['id'];
                }
            }
        }

    }

    public function getPeriod(): Period
    {
        if (!$this->start || !$this->end) {
            throw new BadConfigurationException('TimeSlotFactory must have both start and end set to call this method');
        }

        return Period::make(
            DateTimeTbk::createFromFormatSilently('U', $this->start),
            DateTimeTbk::createFromFormatSilently('U', $this->end),
            Precision::SECOND,
            Boundaries::EXCLUDE_ALL
        );
    }

    public function areOpenCloseConditionsMet(): bool
    {
        $refTime         = $this->get_ref_time();
        $refTimeOpened   = $this->get_ref_time_opened();
        $closedCondition =
            ($this->reference_time === 'end' && $this->end >= $refTime)
            || ($this->reference_time === 'start' && $this->start >= $refTime);

        $openedCondition = $this->start <= $refTimeOpened;

        return $closedCondition && $openedCondition;
    }

    protected function get_ref_time_opened(): int
    {
        try {
            if (substr($this->when_opened, -3) === 'mid') {
                $time_string   = substr($this->when_opened, 0, -3);
                $refTimeOpened = $this->now->add(new \DateInterval($time_string));
                $this->now->setTime(23, 59);
            } else {
                if ($this->when_opened === 'PT0H') {
                    $refTimeOpened = $this->now->add(new \DateInterval('P100Y'));
                } else {
                    $refTimeOpened = $this->now->add(new \DateInterval($this->when_opened));
                }

            }
        } catch (\Exception $exception) {
            $refTimeOpened = $this->now;
        }

        return $refTimeOpened->getTimestamp();
    }

    protected function get_ref_time(): int
    {
        try {
            if (substr($this->when_closed, -3) === 'mid') {
                $time_string = substr($this->when_closed, 0, -3);
                $refTime     = $this->now->add(new \DateInterval($time_string));
                $refTime->setTime(0, 0);
            } else {
                $refTime = $this->now->add(new \DateInterval($this->when_closed));
            }
        } catch (\Exception $exception) {
            $refTime = $this->now;
        }

        return $refTime->getTimestamp();
    }

    protected function get_slot_id()
    {
        return apply_filters('tbk_determine_slot_id', '', $this->provider_id, $this->availability_id, $this->service_id, $this->start, $this->end);
    }

    public function isOverlappingConditionSatisfied(Reservation $reservation): bool
    {
        return ($this->overlapping_others && $reservation->serviceId !== $this->service_id)
            || ($this->overlapping_same && $reservation->serviceId === $this->service_id
                && $reservation->data[ AvailabilityId::ID ] !== $this->availability_id);
    }

    public function overlapsWithReservation(Reservation $reservation): bool
    {
        $reservationPeriod = Period::make(
            DateTimeTbk::createFromFormatSilently('U', (int)$reservation->start),
            DateTimeTbk::createFromFormatSilently('U', (int)$reservation->end),
            Precision::SECOND,
            Boundaries::EXCLUDE_ALL
        );

        return $this->getPeriod()->overlapsWith($reservationPeriod);
    }

    public function getSlot($reservationCount, $reservationsIds, $slotCustomers): array
    {
        return [
            'allday'            => FALSE,
            'serviceId'         => $this->service_id,
            'providerId'        => $this->provider_id,
            'availabilityId'    => $this->availability_id,
            'event_id'          => $this->get_slot_id(),
            'start'             => $this->start,
            'end'               => $this->end,
            '___start'          => DateTimeTbk::createFromFormatSilently('U', $this->start)->format(\DateTime::RFC3339),
            '___end'            => DateTimeTbk::createFromFormatSilently('U', $this->end)->format(\DateTime::RFC3339),
            'reservationsCount' => $reservationCount,
            'reservationsIds'   => $reservationsIds,
            'customers'         => $slotCustomers,
            'overrides'         => $this->overrides,
            'gcalEventId'       => $this->gcal_event_id,
            'buffer'            => $this->buffer
        ];
    }

}
<?php

namespace VSHM\Plugin;

use VSHM\Functions;
use VSHM\Settings\Service\Personal_SlotDuration;
use VSHM\Settings\Service\Personal_SlotDurationRule;
use VSHM\Settings\Service\SlotDuration;
use VSHM\Settings\Service\SlotDurationRule;

defined('ABSPATH') || exit;

/**
 * Class TimeSlotDurationFactory
 *
 * @package VSHM\Plugin
 * @author  VonStroheim
 */
class TimeSlotDurationFactory
{

    protected $service_id;
    protected $duration_setting;

    protected $provider_duration_setting;
    protected $provider_duration;

    protected $fixed_duration;

    public function __construct($serviceId, $providerId)
    {
        $providersServiceData            = \VSHM\Providers\ServiceProviderCustomData::provideBy([
            'key'         => [
                'operator' => 'IN',
                'value'    => [
                    Personal_SlotDurationRule::ID,
                    Personal_SlotDuration::ID,
                ]
            ],
            'service_id'  => $serviceId,
            'provider_id' => (int)$providerId
        ]);
        $providersServiceData            = Functions::organize_service_custom_data($providersServiceData)[ (int)$providerId ][ $serviceId ] ?? [];
        $serviceData                     = \VSHM\Providers\ServicesData::provideBy([
            'key'        => [
                'operator' => 'IN',
                'value'    => [
                    SlotDurationRule::ID,
                    SlotDuration::ID,
                ]
            ],
            'service_id' => $serviceId
        ]);
        $serviceData                     = Functions::organize_service_data($serviceData)[ $serviceId ] ?? [];
        $this->service_id                = $serviceId;
        $this->duration_setting          = $serviceData[ SlotDurationRule::ID ] ?? SlotDurationRule::getDefault();
        $this->fixed_duration            = $serviceData[ SlotDuration::ID ] ?? SlotDuration::getDefault();
        $this->provider_duration_setting = $providersServiceData[ Personal_SlotDurationRule::ID ] ?? Personal_SlotDurationRule::getDefault();
        $this->provider_duration         = $providersServiceData[ Personal_SlotDuration::ID ] ?? Personal_SlotDuration::getDefault();
    }

    public function get(int $duration): int
    {

        if ($this->duration_setting === SlotDurationRule::FIXED && $this->fixed_duration) {
            return (int)$this->fixed_duration;
        }

        if ($this->duration_setting === SlotDurationRule::PROVIDER
            && $this->provider_duration_setting === SlotDurationRule::FIXED
            && $this->provider_duration) {
            return $this->provider_duration;
        }

        return $duration;
    }

}
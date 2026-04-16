<?php

namespace VSHM\Plugin;

use VSHM\Functions;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithOther;
use VSHM\Settings\Service\Personal_DiscardOverlappingWithPersonal;
use VSHM\Settings\Service\Personal_FillingLogic;

defined('ABSPATH') || exit;

/**
 * Class TimeSlotReflowLogicFactory
 *
 * @package VSHM\Plugin
 * @author  VonStroheim
 */
class TimeSlotReflowLogicFactory
{

    protected $service_id;
    protected $reflow_logic;
    protected $overlapping_personal;
    protected $overlapping_other;

    public function __construct($serviceId, $providerId)
    {
        $providersServiceData       = \VSHM\Providers\ServiceProviderCustomData::provideBy([
            'key'         => [
                'operator' => 'IN',
                'value'    => [
                    Personal_FillingLogic::ID,
                    Personal_DiscardOverlappingWithPersonal::ID,
                    Personal_DiscardOverlappingWithOther::ID,
                ]
            ],
            'service_id'  => $serviceId,
            'provider_id' => $providerId
        ]);
        $providersServiceData            = Functions::organize_service_custom_data($providersServiceData)[ (int)$providerId ][ $serviceId ] ?? [];
        $this->service_id           = $serviceId;
        $this->reflow_logic         = $providersServiceData[ Personal_FillingLogic::ID ] ?? Personal_FillingLogic::getDefault();
        $this->overlapping_personal = $providersServiceData[ Personal_DiscardOverlappingWithPersonal::ID ] ?? Personal_DiscardOverlappingWithPersonal::getDefault();
        $this->overlapping_other    = $providersServiceData[ Personal_DiscardOverlappingWithOther::ID ] ?? Personal_DiscardOverlappingWithOther::getDefault();
    }

    public function mustReflow(): bool
    {

        $mustReflow = FALSE;
        if ($this->reflow_logic === Personal_FillingLogic::ADAPTIVE
            && (
                $this->overlapping_personal || $this->overlapping_other
            )
        ) {
            $mustReflow = TRUE;
        }

        return $mustReflow;
    }

}
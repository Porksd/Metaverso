<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class Settings_ProvidersOrder
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Settings_ProvidersOrder extends Settings_Base
{
    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure         = parent::get_structure();
        $structure['type'] = 'providersOrder';

        return $structure;
    }
}
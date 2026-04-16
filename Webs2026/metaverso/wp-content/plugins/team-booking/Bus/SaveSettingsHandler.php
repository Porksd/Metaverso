<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * SaveSettingsHandler
 */
class SaveSettingsHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command SaveSettings */
        $settings          = vshm()->settings->getAllByContext();
        $settings['style'] = vshm()->settings->getAllByContext('style');
        $options           = $command->getSettings() + $settings;
        update_option(vshm()->get_settings_name(), $options);
    }
}
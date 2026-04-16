<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;

defined('ABSPATH') || exit;

/**
 * SaveUserPrefsHandler
 *
 * @package VSHM\Bus
 */
class SaveUserPrefsHandler implements HandlerInterface
{
    public function dispatch(CommandInterface $command): void
    {
        /** @var $command SaveUserPrefs */

        $options = apply_filters('vshm_backend_user_prefs', [$command->getId() => $command->getValue()]);

        update_user_option(get_current_user_id(), 'tbkUserPrefs', $options);
    }
}
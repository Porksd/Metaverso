<?php

namespace VSHM\Plugin;

use VSHF\Bus\Middleware;
use VSHM\Tools;

defined('ABSPATH') || exit;

/**
 * Connects WordPress actions to Bus dispatching operations
 */
class BusMiddleware extends Middleware
{
    public function before(): void
    {
        $classNameShort = Tools::get_short_classname(get_class($this->command));
        do_action('vshm_bus_dispatching', $classNameShort, $this->command, $this->agent_type, $this->agent_id);
        do_action('vshm_dispatching_' . $classNameShort, $this->command, $this->agent_type, $this->agent_id);
        $this->next();
    }

    public function after(): void
    {
        $classNameShort = Tools::get_short_classname(get_class($this->command));
        do_action('vshm_dispatched_' . $classNameShort, $this->command, $this->agent_type, $this->agent_id);
        do_action('vshm_bus_dispatched', $classNameShort, $this->command, $this->agent_type, $this->agent_id);
    }

}
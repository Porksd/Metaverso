<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CleanLogs
 *
 * @package VSHM\Bus
 */
class CleanLogs implements CommandInterface
{
    public function __construct()
    {
    }
}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * SaveSettings
 *
 * @package VSHM\Bus
 */
class SaveSettings implements CommandInterface
{
    /**
     * @var array
     */
    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     */
    public function setSettings($settings): void
    {
        $this->settings = $settings;
    }
}
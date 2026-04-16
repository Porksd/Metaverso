<?php

namespace VSHM\UI\Admin;

defined('ABSPATH') || exit;

/**
 * Class VSHM
 *
 * @package VSHM\UI\Admin
 * @author  VonStroheim
 */
class Alert implements Element
{
    const TYPE__WARNING = 'warning';
    const TYPE__INFO    = 'info';
    const TYPE__ERROR   = 'error';
    const TYPE__SUCCESS = 'success';

    private $message;
    private $type;
    private $description;

    /**
     * @return self
     */
    public static function warning($message)
    {
        $alert          = new self();
        $alert->type    = self::TYPE__WARNING;
        $alert->message = $message;

        return $alert;
    }

    /**
     * @return self
     */
    public static function info($message)
    {
        $alert          = new self();
        $alert->type    = self::TYPE__INFO;
        $alert->message = $message;

        return $alert;
    }

    /**
     * @return self
     */
    public static function error($message)
    {
        $alert          = new self();
        $alert->type    = self::TYPE__ERROR;
        $alert->message = $message;

        return $alert;
    }

    /**
     * @return self
     */
    public static function success($message)
    {
        $alert          = new self();
        $alert->type    = self::TYPE__SUCCESS;
        $alert->message = $message;

        return $alert;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function get_structure(): array
    {
        $structure = [
            'type'    => $this->type,
            'message' => $this->message
        ];

        if ($this->description) {
            $structure['description'] = $this->description;
        }

        return $structure;
    }
}
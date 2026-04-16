<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * CreateCustomer
 *
 * @package VSHM\Bus
 */
class CreateCustomer implements CommandInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $phone;

    /**
     * @var int
     */
    private $wpUserId;

    /**
     * @var string
     */
    private $timezone;

    /**
     * @param      $id
     * @param      $name
     * @param      $email
     * @param      $phone
     * @param      $wpUserId
     * @param null $timezone
     */
    public function __construct($id, $name, $email, $phone, $wpUserId, $timezone = NULL)
    {
        $this->name     = $name;
        $this->id       = $id;
        $this->email    = (string)$email;
        $this->phone    = (string)$phone;
        $this->wpUserId = (int)$wpUserId;
        $this->timezone = $timezone;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return int
     */
    public function getWpUserId(): int
    {
        return $this->wpUserId;
    }

    /**
     * @return string
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getValue(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'wpUserId' => $this->wpUserId,
            'timezone' => $this->timezone
        ];
    }

}
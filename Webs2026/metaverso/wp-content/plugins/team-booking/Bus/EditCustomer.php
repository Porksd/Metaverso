<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * EditCustomer
 *
 * @package VSHM\Bus
 */
class EditCustomer implements CommandInterface
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
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $status;

    /**
     * @param      $id
     * @param      $name
     * @param      $email
     * @param      $phone
     * @param      $wpUserId
     * @param      $token
     * @param      $status
     * @param null $timezone
     */
    public function __construct($id, $name, $email, $phone, $wpUserId, $token, $status, $timezone = NULL)
    {
        $this->id       = (string)$id;
        $this->name     = (string)$name;
        $this->email    = (string)$email;
        $this->phone    = (string)$phone;
        $this->wpUserId = (int)$wpUserId;
        $this->timezone = $timezone;
        $this->token    = (string)$token;
        $this->status   = (int)$status;
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

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public function getValue(): array
    {
        return [
            'name'     => $this->name,
            'id'       => $this->id,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'wpUserId' => $this->wpUserId,
            'timezone' => $this->timezone,
            'token'    => $this->token,
            'status'   => $this->status,
        ];
    }

}
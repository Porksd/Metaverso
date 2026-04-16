<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;

defined('ABSPATH') || exit;

/**
 * SendEmail
 *
 * $subject = '',
 * $body = '',
 * $to = [],
 * $from = [],
 * $attachments = [],
 * $cc = [],
 * $bcc = [],
 * $replyTo = []
 *
 * @package VSHM\Bus
 */
class SendEmail implements CommandInterface
{

    /**
     * @var string
     */
    private $subject;
    /**
     * @var string
     */
    private $body;
    /**
     * @var array
     */
    private $to;
    /**
     * @var array
     */
    private $from;
    /**
     * @var array
     */
    private $attachments;

    /**
     * @var array
     */
    private $cc;
    /**
     * @var array
     */
    private $bcc;
    /**
     * @var array
     */
    private $replyTo;

    public function __construct(string $subject = '', string $body = '', array $to = [], array $from = [], array $attachments = [], array $cc = [], array $bcc = [], array $replyTo = [])
    {
        $this->subject     = $subject;
        $this->body        = $body;
        $this->to          = $to;
        $this->from        = $from;
        $this->attachments = $attachments;
        $this->cc          = $cc;
        $this->bcc         = $bcc;
        $this->replyTo     = $replyTo;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * @return array
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @return array
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * @return array
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * @return array
     */
    public function getReplyTo(): array
    {
        return $this->replyTo;
    }
}
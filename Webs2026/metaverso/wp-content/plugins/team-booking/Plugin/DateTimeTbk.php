<?php

namespace VSHM\Plugin;

defined('ABSPATH') || exit;

/**
 * Class DateTimeTbk
 *
 * @package VSHM\Plugin
 * @author  VonStroheim
 */
class DateTimeTbk extends \DateTimeImmutable
{
    /**
     * DateTimeTbk constructor.
     *
     * It runs the parent constructor plus the ability to "recover" in case of timestamps
     * passed without prepending "@"
     *
     * @param string|int         $time
     * @param \DateTimeZone|NULL $timezone
     */
    public function __construct($time = 'now', \DateTimeZone $timezone = NULL)
    {
        if (is_int($time)) {
            $time = '@' . $time;
        }

        try {
            parent::__construct($time, $timezone);
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                trigger_error(sanitize_text_field("Something were wrong with DateTimeTbk construction: {$e->getMessage()}"));
            }
        }
    }

    /**
     * It fixes the 2038 bug, and it's apparently faster.
     *
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->format('U');
    }

    /**
     * @return string
     */
    public function localized_date()
    {
        return wp_date(get_option('date_format'), $this->getTimestamp());
    }

    /**
     * @param bool $allDay
     *
     * @return string
     */
    public function localized_time($allDay = FALSE)
    {
        if ($allDay) {
            return esc_html__('All day', 'team-booking');
        }

        return wp_date(get_option('time_format'), $this->getTimestamp());
    }

    /**
     * @param bool   $allDay
     * @param string $separator
     *
     * @return string
     */
    public function localized_date_time(bool $allDay = FALSE, string $separator = '@'): string
    {
        return $this->localized_date() . ' ' . $separator . ' ' . $this->localized_time($allDay);
    }

    /**
     * @param string                    $format
     * @param string                    $time
     * @param \DateTimeZone|string|NULL $timezone
     *
     * @return bool|\DateTime|static
     */
    public static function createFromFormatSilently(string $format, string $time, $timezone = NULL)
    {
        $ext_dt = new static();

        if (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }

        $dt = self::createFromFormat($format, $time, $timezone);

        /**
         * Must fail silently?
         */
        if (!$dt) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                trigger_error(sanitize_text_field("Time '{$time}' and format '{$format}' are not valid for a DateTime object."));
            }

            return $ext_dt;
        }

        // TODO: investigate why this produces different results from parent class
        if ($timezone) {
            return $ext_dt->setTimestamp($dt->getTimestamp())->setTimezone($timezone);
        }

        return $ext_dt->setTimestamp($dt->getTimestamp());
    }

    /**
     * Creates a DateTime instance from MySql formatted datetime
     *
     * @param string $mysql
     * @param string $targetTimezone
     *
     * @return bool|\DateTime|static
     */
    public static function fromDB($mysql, $targetTimezone = NULL)
    {
        $date = static::createFromFormat('Y-m-d H:i:s', $mysql, new \DateTimeZone('UTC'));

        if (NULL === $targetTimezone) {
            return $date;
        }

        return $date->setTimezone(new \DateTimeZone($targetTimezone));
    }

    /**
     * @return string
     */
    public function toDB()
    {
        $date = $this->setTimezone(new \DateTimeZone('UTC'));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @param string             $string
     * @param \DateTimeZone|NULL $timezone
     *
     * @return DateTimeTbk
     */
    public static function create($string, \DateTimeZone $timezone = NULL)
    {
        return new self($string, $timezone);
    }
}
<?php

namespace VSHM\Settings;

use VSHM\UI\Admin\Alert;
use VSHM\UI\Admin\Element_Setting;
use VSHM\UI\Admin\Settings_Option;

defined('ABSPATH') || exit;

/**
 * Class FrontendBookingManagerPage
 */
class FrontendBookingManagerPage extends SettingBase
{

    public const ID = 'bookingManagerPage';

    public static function getBackendElement(): Element_Setting
    {
        $args  = [
            'sort_order'   => 'asc',
            'sort_column'  => 'post_title',
            'hierarchical' => 1,
            'exclude'      => '',
            'include'      => '',
            'meta_key'     => '',
            'meta_value'   => '',
            'authors'      => '',
            'exclude_tree' => '',
            'number'       => '',
            'offset'       => 0,
            'post_type'    => 'page',
            'post_status'  => 'publish'
        ];
        $pages = get_pages($args);

        $element = \VSHM\UI\Admin\Settings_Select::get(__('Reservation status page', 'team-booking'), self::ID);
        $element->setDescription(__('Select the frontend page where customers can view and manage their reservations.', 'team-booking'));
        $element->setAlert(Alert::warning(__('To ensure functionality, the selected page must include the plugin widget in any section.', 'team-booking')));

        $element->addOption(Settings_Option::get(__('No page selected', 'team-booking'), 0));
        foreach ($pages as $page) {
            $element->addOption(Settings_Option::get($page->post_title, $page->ID));
        }

        return $element;
    }

    public static function sanitize($value): int
    {
        return (int)$value;
    }

    public static function default(): int
    {
        return 0;
    }
}
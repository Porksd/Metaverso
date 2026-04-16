<?php

namespace VSHM\Plugin;

use VSHM\Providers\ServiceProviders;
use VSHM\Providers\Services;

defined('ABSPATH') || exit;

/**
 * Class Elementor_Widget
 *
 * @author  VonStroheim
 * @since   3.0.0
 */
class Elementor_Widget extends \Elementor\Widget_Base
{
    public function get_name(): string
    {
        return 'tbk_widget';
    }

    public function get_keywords(): array
    {
        return ['booking', 'thebooking', 'teambooking', 'reservations', 'calendar'];
    }

    public function get_title()
    {
        return __('TheBooking widget', 'team-booking');
    }

    public function get_icon(): string
    {
        return 'tbk-elementor-widget-icon';
    }

    protected function register_controls()
    {

        $providerOptions = [];
        $serviceOptions  = [];
        foreach (Services::provide() as $service) {
            $serviceOptions[ $service->id ] = $service->name;
        }
        foreach (ServiceProviders::provide() as $provider) {
            $providerOptions[ $provider['id'] ] = $provider['name'];
        }

        $controls = apply_filters('tbk_elementor_widget_controls', [
            'view_section'         => [
                'data'     =>
                    [
                        'label' => __('Configuration', 'team-booking'),
                        'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                    ],
                'controls' =>
                    [
                        'widget_type'      =>
                            [
                                'label'       => __('Widget type', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::SELECT,
                                'options'     => [
                                    'monthly'      => __('Monthly calendar', 'team-booking'),
                                    'upcoming'     => __('Upcoming events', 'team-booking'),
                                    'unscheduled'  => __('Unscheduled services', 'team-booking'),
                                    'reservations' => __('Reservations list (logged users only)', 'team-booking'),
                                ],
                                'default'     => 'monthly',
                            ],
                        'displayed_events' =>
                            [
                                'label'       => __('Displayed events', 'team-booking'),
                                'description' => __('The number of upcoming events that are displayed on the page', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::NUMBER,
                                'min'         => 1,
                                'step'        => 1,
                                'default'     => 4,
                                'condition'   => [
                                    'widget_type' => 'upcoming',
                                ],
                            ],
                        'show_more'        =>
                            [
                                'label'       => __('Show more', 'team-booking'),
                                'description' => __('Shows a button to load more events', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::SWITCHER,
                                'default'     => FALSE,
                                'condition'   => [
                                    'widget_type' => 'upcoming',
                                ],
                            ],
                        'upcoming_limit'   =>
                            [
                                'label'       => __('Maximum fetched events', 'team-booking'),
                                'description' => __('Limit the number of maximum events that can be loaded. 0 means no limit.', 'team-booking'),
                                'label_block' => TRUE,
                                'min'         => 0,
                                'step'        => 1,
                                'type'        => \Elementor\Controls_Manager::NUMBER,
                                'default'     => FALSE,
                                'condition'   => [
                                    'widget_type' => 'upcoming',
                                    'show_more'   => TRUE,
                                ],
                            ],
                    ]
            ],
            'restrictions_section' => [
                'data'     =>
                    [
                        'label' => __('Restrictions', 'team-booking'),
                        'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                    ],
                'controls' =>
                    [
                        'restrict_services'  =>
                            [
                                'label'       => __('Restrict services', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::SELECT2,
                                'multiple'    => TRUE,
                                'options'     => $serviceOptions,
                                'default'     => [],
                                'condition'   => [
                                    'widget_type' => ['upcoming', 'monthly', 'unscheduled'],
                                ],
                            ],
                        'restrict_providers' =>
                            [
                                'label'       => __('Restrict providers', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::SELECT2,
                                'multiple'    => TRUE,
                                'options'     => $providerOptions,
                                'default'     => [],
                                'condition'   => [
                                    'widget_type' => ['upcoming', 'monthly', 'unscheduled'],
                                ],
                            ],
                        'read_only'          =>
                            [
                                'label'       => __('Read-only', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::SWITCHER,
                                'default'     => FALSE,
                                'condition'   => [
                                    'widget_type' => ['upcoming', 'monthly', 'unscheduled'],
                                ],
                            ],
                        'logged_only'        =>
                            [
                                'label'       => __('Logged-only', 'team-booking'),
                                'label_block' => TRUE,
                                'type'        => \Elementor\Controls_Manager::SWITCHER,
                                'default'     => FALSE,
                                'condition'   => [
                                    'widget_type' => ['upcoming', 'monthly', 'unscheduled'],
                                ],
                            ],
                    ]
            ],
        ]);

        foreach ($controls as $section_id => $section_struct) {
            $this->start_controls_section($section_id, $section_struct['data']);

            foreach ($section_struct['controls'] as $control_id => $control_data) {
                $this->add_control($control_id, $control_data);
            }

            $this->end_controls_section();
        }
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    /**
     * Shortcode building process
     */
    public function build_shortcode(): string
    {
        $attrs    = '';
        $settings = $this->get_settings_for_display();

        if ($settings['restrict_services']) {
            $attrs .= ' services="' . implode(',', $settings['restrict_services']) . '"';
        }

        if ($settings['restrict_providers']) {
            $attrs .= ' providers="' . implode(',', $settings['restrict_providers']) . '"';
        }

        if ($settings['widget_type']) {
            $attrs .= ' view="' . $settings['widget_type'] . '"';
        }

        if ($settings['upcoming_limit']) {
            $attrs .= ' upcoming-limit="' . $settings['upcoming_limit'] . '"';
        }

        if ($settings['displayed_events']) {
            $attrs .= ' displayed-events="' . $settings['displayed_events'] . '"';
        }

        if ($settings['show_more']) {
            $attrs .= ' show-more="true"';
        }

        if ($settings['logged_only']) {
            $attrs .= ' logged-only="true"';
        }

        if ($settings['read_only']) {
            $attrs .= ' read-only="true"';
        }

        return '[tbk-calendar' . apply_filters('tbk_elementor_widget_calendar_shortcode_attrs', $attrs, $settings) . ']';
    }

    /**
     *
     */
    public function render_plain_content()
    {
        echo esc_html($this->build_shortcode());
    }

    /**
     *
     */
    protected function render()
    {
        ?>
        <div class="elementor-shortcode"><?php echo do_shortcode(shortcode_unautop($this->build_shortcode())); ?></div>
        <?php
    }
}
<?php

class tbkOptions
{

    protected $plugin_options_page = '';

    /**
     * Initialize hooks.
     */
    public function init(): void
    {

        add_action('init', [self::class, '_load_textdomain']);

        \VSHM\Functions::register();

        if (\VSHM\Tools::is_request('frontend')) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        }

        if (!\VSHM\Tools::is_request('cron')) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 1);
            add_action('customize_register', [$this, 'customize_register']);
            add_action('admin_menu', [$this, 'create_admin_menu_page'], 9);
            add_filter('vshm_backend_menu_items', [$this, 'menu_items']);
            add_filter('vshm_backend_services', [\VSHM\Routes\ServicesRoute::class, 'prepare_for_backend']);
            add_filter('vshm_backend_providers', static function ($providers) {
                return \VSHM\Providers\ServiceProviders::provide();
            });
            add_filter('vshm_backend_customers', static function ($customers) {
                return \VSHM\Providers\Customers::provide();
            });
            add_filter('vshm_backend_promotions', [\VSHM\Routes\PromotionsRoute::class, 'prepare_for_frontend']);

            add_filter('vshm_frontend_services', [\VSHM\Routes\ServicesRoute::class, 'prepare_for_frontend']);
            add_filter('vshm_frontend_providers', [\VSHM\Routes\ProvidersRoute::class, 'prepare_for_frontend']);
            add_filter('vshm_frontend_settings', [$this, 'frontend_settings']);

            add_shortcode('tbk-calendar', [$this, 'shortcode_booking'], 10, 3);
            add_shortcode('tb-calendar', [$this, 'shortcode_booking'], 10, 3); //legacy
            add_shortcode('tb-upcoming', [$this, 'shortcode_booking'], 10, 3); //legacy
            add_shortcode('tb-reservations', [$this, 'shortcode_booking'], 10, 3); //legacy
            add_shortcode('tb-cancellation', [$this, 'shortcode_booking'], 10, 3); //legacy

            /**
             * Required to avoid SuperAdmins to get the demo message
             */
            add_filter(
                'map_meta_cap',
                function ($caps, $cap, $user_id) {
                    /**
                     * Only filter checks for custom_capability.
                     */
                    if ('tbk_backend_demo' === $cap) {
                        $user      = get_userdata($user_id);
                        $user_caps = $user->get_role_caps();

                        /**
                         * If the user does not have the capability, or it's denied, then
                         * add do_not_allow.
                         */
                        if (empty($user_caps[ $cap ])) {
                            $caps[] = 'do_not_allow';
                        }
                    }

                    return $caps;
                },
                10,
                3
            );

            /* Elementor support */
            if (did_action('elementor/loaded')) {
                add_action('elementor/widgets/widgets_registered', static function () {
                    \Elementor\Plugin::instance()->widgets_manager->register(new \VSHM\Plugin\Elementor_Widget());
                });
                add_action('elementor/editor/before_enqueue_scripts', static function () {
                    \VSHM\Tools::enqueue_style('tbk-elementor-css', '/Plugin/elementor.css');
                    \VSHM\Tools::enqueue_script('tbk-elementor-js', '/Plugin/elementor.js');
                });
            }

            /* Divi support */
            if (class_exists('ET_Builder_Module ')) {

            }

            /* LocoTranslate support */
            add_action('loco_file_written', static function ($path) {
                if (substr($path, -16) === 'tbk-scripts.json') {
                    require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
                    $filesys = new \WP_Filesystem_Direct(TRUE);
                    $filesys->copy(
                        $path,
                        str_replace('tbk-scripts', 'tbk-backend-scripts', $path),
                        TRUE
                    );
                }
            });
            add_filter('loco_compile_single_json', static function ($jsonPath, $poPath) {
                $info = pathinfo($poPath);
                $path = '';
                if (strpos($info['filename'], 'team-booking') === 0) {
                    $path = $info['dirname'] . '/' . $info['filename'] . '-tbk-scripts.json';
                }

                return $path;
            }, 999, 2);

        }

        if (\VSHM\Tools::is_request('rest')) {
            add_action('vshm_registering_routes', [$this, 'register_routes']);
        }

        if (\VSHM\Tools::is_request('admin')) {

            /**
             * Block editor
             */
            add_action('enqueue_block_editor_assets', [self::class, 'load_block_editor_scripts'], 99);
            add_action('enqueue_block_editor_assets', [$this, 'enqueue_frontend_scripts']);
            add_filter('block_categories_all', [self::class, 'load_block_editor_category'], 10, 2);
            add_action('init', [self::class, 'load_block_editor_blocks']);
        }

        vshm()->register_routes();
    }

    public function shortcode_booking($atts, $content, $tag): string
    {
        $scoped_id = 'tbk-frontend-' . substr(md5(time() . mt_rand()), 0, 4);

        $data_attr = [];

        if (isset($atts['services']) || isset($atts['booking'])) {
            $services    = $atts['services'] ?? $atts['booking'];
            $data_attr[] = 'data-services="' . $services . '"';
        }
        if (isset($atts['providers']) || isset($atts['coworker'])) {
            $providers   = $atts['providers'] ?? $atts['coworker'];
            $data_attr[] = 'data-providers="' . $providers . '"';
        }

        if (isset($atts['view'])) {
            $data_attr[] = 'data-view="' . $atts['view'] . '"';
        } elseif ($tag === 'tb-upcoming') {
            $data_attr[] = 'data-view="upcoming"';
        } elseif ($tag === 'tb-reservations') {
            $data_attr[] = 'data-view="reservations"';
        } elseif ($tag === 'tb-cancellation') {
            $data_attr[] = 'data-view="reservations"';
        }

        if (isset($atts['upcoming-limit']) || isset($atts['limit'])) {
            $limit       = $atts['upcoming-limit'] ?? $atts['limit'];
            $data_attr[] = 'data-max-upcoming="' . $limit . '"';
        }
        if (isset($atts['displayed-events']) || isset($atts['shown'])) {
            $events      = $atts['displayed-events'] ?? $atts['shown'];
            $data_attr[] = 'data-default-upcoming="' . $events . '"';
        }
        if (isset($atts['show-more']) || isset($atts['more'])) {
            $more        = $atts['show-more'] ?? $atts['more'];
            $data_attr[] = 'data-show-more-upcoming="' . $more . '"';
        }
        if (isset($atts['logged-only']) || isset($atts['logged_only'])) {
            $logged_only = $atts['logged-only'] ?? $atts['logged_only'];
            $data_attr[] = 'data-loggedonly="' . $logged_only . '"';
        }
        if (isset($atts['read-only']) || isset($atts['read_only'])) {
            $read_only   = $atts['read-only'] ?? $atts['read_only'];
            $data_attr[] = 'data-readonly="' . $read_only . '"';
        }

        return "<div class='tbk-frontend' id='" . $scoped_id . "' " . implode(' ', $data_attr) . "><div class='tbk-inner-content'></div></div>";
    }

    public static function load_block_editor_scripts(): void
    {
        $handle = 'tbk-scripts';
        wp_add_inline_script($handle, 'const VSHM_BACKEND_SERVICES=' . json_encode(apply_filters('vshm_backend_services', [])), 'before');
        wp_add_inline_script($handle, 'const VSHM_BACKEND_PROVIDERS=' . json_encode(apply_filters('vshm_backend_providers', [])), 'before');
    }

    public static function load_block_editor_category($categories)
    {
        return array_merge(
            $categories,
            [
                [
                    'slug'  => 'tbk-bookings',
                    'title' => __('Booking tools', 'team-booking')
                ],
            ]
        );
    }

    public static function load_block_editor_blocks(): void
    {
        register_block_type(vshm()->plugin['PATH'] . 'UI/Blocks/');
    }

    /**
     * Loads the text domain on plugin load.
     *
     * @return bool
     */
    public static function _load_textdomain(): bool
    {
        $result = load_plugin_textdomain('team-booking', FALSE, dirname(plugin_basename(vshm()->plugin['FILE'])) . '/languages');

        return $result;
    }

    public function frontend_settings($settings)
    {
        $settings[ \VSHM\Settings\CurrencyCode::ID ]              = vshm()->settings->get(\VSHM\Settings\CurrencyCode::ID);
        $settings[ \VSHM\Settings\PriceFormat::ID ]               = vshm()->settings->get(\VSHM\Settings\PriceFormat::ID);
        $settings[ \VSHM\Settings\PriceDecimals::ID ]             = vshm()->settings->get(\VSHM\Settings\PriceDecimals::ID);
        $settings[ \VSHM\Settings\CurrencyFormat::ID ]            = vshm()->settings->get(\VSHM\Settings\CurrencyFormat::ID);
        $settings[ \VSHM\Settings\GoogleMapsApiKey::ID ]          = vshm()->settings->get(\VSHM\Settings\GoogleMapsApiKey::ID);
        $settings[ \VSHM\Settings\SkipGoogleMapsLib::ID ]         = vshm()->settings->get(\VSHM\Settings\SkipGoogleMapsLib::ID);
        $settings[ \VSHM\Settings\FrontendTimezone::ID ]          = vshm()->settings->get(\VSHM\Settings\FrontendTimezone::ID);
        $settings[ \VSHM\Settings\FetchingGranularity::ID ]       = vshm()->settings->get(\VSHM\Settings\FetchingGranularity::ID);
        $settings[ \VSHM\Settings\LoadMapsAutocomplete::ID ]      = vshm()->settings->get(\VSHM\Settings\LoadMapsAutocomplete::ID);
        $settings[ \VSHM\Settings\LoginUrl::ID ]                  = vshm()->settings->get(\VSHM\Settings\LoginUrl::ID);
        $settings[ \VSHM\Settings\RegistrationUrl::ID ]           = vshm()->settings->get(\VSHM\Settings\RegistrationUrl::ID);
        $settings[ \VSHM\Settings\AllowCart::ID ]                 = vshm()->settings->get(\VSHM\Settings\AllowCart::ID);
        $settings[ \VSHM\Settings\LoadCalendarAtClosestSlot::ID ] = vshm()->settings->get(\VSHM\Settings\LoadCalendarAtClosestSlot::ID);
        $settings[ \VSHM\Settings\SortingServices::ID ]           = vshm()->settings->get(\VSHM\Settings\SortingServices::ID);
        $settings[ \VSHM\Settings\SortingProviders::ID ]          = vshm()->settings->get(\VSHM\Settings\SortingProviders::ID);

        $settings['style']        = [
            \VSHM\Settings\SettingAvailableSlotColor::ID => vshm()->settings->get(\VSHM\Settings\SettingAvailableSlotColor::ID, 'style'),
            \VSHM\Settings\SettingSoldoutSlotColor::ID   => vshm()->settings->get(\VSHM\Settings\SettingSoldoutSlotColor::ID, 'style'),
            \VSHM\Settings\SettingBackgroundColor::ID    => vshm()->settings->get(\VSHM\Settings\SettingBackgroundColor::ID, 'style'),
            \VSHM\Settings\SettingBorderColor::ID        => vshm()->settings->get(\VSHM\Settings\SettingBorderColor::ID, 'style'),
            \VSHM\Settings\SettingBorderRadius::ID       => vshm()->settings->get(\VSHM\Settings\SettingBorderRadius::ID, 'style'),
            \VSHM\Settings\SettingBorderWidth::ID        => vshm()->settings->get(\VSHM\Settings\SettingBorderWidth::ID, 'style'),
            \VSHM\Settings\SettingDotsLogic::ID          => vshm()->settings->get(\VSHM\Settings\SettingDotsLogic::ID, 'style'),
            \VSHM\Settings\SettingDotsThreshold::ID      => vshm()->settings->get(\VSHM\Settings\SettingDotsThreshold::ID, 'style'),
            \VSHM\Settings\SettingMapsStyle::ID          => vshm()->settings->get(\VSHM\Settings\SettingMapsStyle::ID, 'style'),
            \VSHM\Settings\SettingMapsZoom::ID           => vshm()->settings->get(\VSHM\Settings\SettingMapsZoom::ID, 'style'),
            \VSHM\Settings\SettingPrimaryColor::ID       => vshm()->settings->get(\VSHM\Settings\SettingPrimaryColor::ID, 'style'),
        ];
        $settings['weekStartsOn'] = get_option('start_of_week'); // 0 = Sunday
        $settings['hideWeekends'] = FALSE; // @NEXT

        return $settings;
    }

    /**
     * @param $slug
     */
    public function register_routes($slug): void
    {
        if ($slug === vshm()->plugin['SLUG']) {
            \VSHM\Routes\ApiTokensRoute::register();
            \VSHM\Routes\ServicesRoute::register();
            \VSHM\Routes\ProvidersRoute::register();
            \VSHM\Routes\ReservationsRoute::register();
            \VSHM\Routes\FormsRoute::register();
            \VSHM\Routes\FunctionsRoute::register();
            \VSHM\Routes\CustomersRoute::register();
            \VSHM\Routes\PromotionsRoute::register();
            \VSHM\Routes\InternalRoute::register();
            \VSHM\Routes\PaymentGatewaysRoute::register();
            \VSHM\Routes\AvailabilityRoute::register();
            \VSHM\Routes\ActionLinksRoute::register();
            \VSHM\Routes\ApiRoute::register();
        }
    }

    /**
     * @param $wp_customize
     */
    public static function customize_register($wp_customize): void
    {
        $wp_customize->add_panel(vshm()->plugin['SLUG'], [
            'title'       => vshm()->plugin['NAME'],
            'description' => __('Please navigate in the preview to a page where the booking calendar resides.', 'team-booking'),
            'priority'    => 160
        ]);

        $wp_customize->add_section(vshm()->plugin['SLUG'] . '-style', [
            'title' => __('Style & Colors', 'team-booking'),
            'panel' => vshm()->plugin['SLUG'],
        ]);

        $wp_customize->add_section(vshm()->plugin['SLUG'] . '-maps', [
            'title' => __('Maps', 'team-booking'),
            'panel' => vshm()->plugin['SLUG'],
        ]);

        $wp_customize->add_section(vshm()->plugin['SLUG'] . '-behaviour', [
            'title' => __('Behaviour', 'team-booking'),
            'panel' => vshm()->plugin['SLUG'],
        ]);

        do_action('vshm_settings_customizer', $wp_customize, vshm()->plugin['SLUG']);
    }

    /**
     * Dashboard page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_dashboard(): \VSHM\UI\Admin\MenuItem
    {
        $page           = \VSHM\UI\Admin\Page::full_width();
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $page->setContent($settings_panel);

        return \VSHM\UI\Admin\MenuItem::tab(__('Dashboard', 'team-booking'), $page, \VSHM\UI\Admin\Icons::COFFEE);
    }

    /**
     * Availability page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_availability(): \VSHM\UI\Admin\MenuItem
    {
        $page = \VSHM\UI\Admin\Page::sidebar_left();

        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('Working hours', 'team-booking'), 'working-hours');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $table          = \VSHM\UI\Admin\Plugin\WorkingHoursPanel::get('', 'working_hours');
        $settings_panel->addItem($table);
        $menu_item->setContent($settings_panel);

        $page->addSidebarItem($menu_item);

        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('Your slots', 'team-booking'), 'your-slots');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $calendar       = \VSHM\UI\Admin\Plugin\AvailabilityCalendar::get('', 'slots_self');
        $calendar->setProviderId(get_current_user_id());
        $settings_panel->addItem($calendar);
        $menu_item->setContent($settings_panel);

        $page->addSidebarItem($menu_item);

        if (\VSHM\Functions::current_user_can_admin()) {
            $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('All slots', 'team-booking'), 'all-slots');
            $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
            $calendar       = \VSHM\UI\Admin\Plugin\AvailabilityCalendar::get('', 'slots_all');
            $settings_panel->addItem($calendar);
            $menu_item->setContent($settings_panel);

            $page->addSidebarItem($menu_item);
        }

        return \VSHM\UI\Admin\MenuItem::tab(
            __('Availability', 'team-booking'),
            apply_filters('tbk_backend_availability_menu_items', $page),
            \VSHM\UI\Admin\Icons::CALENDAR,
            'availability'
        );
    }

    /**
     * Settings page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_settings(): \VSHM\UI\Admin\MenuItem
    {
        $page = \VSHM\UI\Admin\Page::sidebar_left();

        $menu_item = \VSHM\UI\Admin\SidebarItem::submenu(__('Core', 'team-booking'), 'core');

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('General', 'team-booking'), 'core-general');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $settings_panel->addItem(\VSHM\Settings\PaymentPendingTime::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\CurrencyCode::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\CurrencyFormat::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\PriceFormat::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\PriceDecimals::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\SenderEmail::getBackendElement());

        $menu_item_sub->setContent(apply_filters('tbk_settings_core_general_items', $settings_panel));
        $menu_item->addItem($menu_item_sub);

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('Redirects', 'team-booking'), 'core-redirects');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $settings_panel->addItem(\VSHM\Settings\LoginUrl::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\RegistrationUrl::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\FrontendBookingManagerPage::getBackendElement());
        $menu_item_sub->setContent($settings_panel);
        $menu_item->addItem($menu_item_sub);

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('Roles', 'team-booking'), 'core-roles');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $settings_panel->addItem(\VSHM\Settings\AllowedServiceProviderWpRoles::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\AllowedAdminWpRoles::getBackendElement());
        $menu_item_sub->setContent($settings_panel);
        $menu_item->addItem($menu_item_sub);

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('Frontend', 'team-booking'), 'core-frontend');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $settings_panel->addItem(\VSHM\Settings\PrepopulateBookingForm::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\LoadCalendarAtClosestSlot::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\FrontendTimezone::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\AllowCart::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\SortingServices::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\SortingProviders::getBackendElement());
        $menu_item_sub->setContent($settings_panel);
        $menu_item->addItem($menu_item_sub);

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('Style', 'team-booking'), 'core-style');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $infoMessage = new \VSHM\UI\Admin\InfoMessage(
            __('Frontend can be styled through the customizer', 'team-booking'),
            __('Inside the customizer preview window, navigate to a page where the booking calendar resides.', 'team-booking')
        );
        $infoMessage->addAction(
            __('Customize', 'team-booking'),
            admin_url('/customize.php?autofocus[panel]=' . vshm()->plugin['SLUG'] . '&return=%2Fwp-admin%2Fadmin.php%3Fpage%3D' . vshm()->plugin['SLUG']),
            'primary'
        );
        $settings_panel->addItem($infoMessage);


        $menu_item_sub->setContent($settings_panel);
        $menu_item->addItem($menu_item_sub);

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('Import/Export', 'team-booking'), 'core-impexp');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $export = \VSHM\UI\Admin\Settings_Informative::get(__('Export settings', 'team-booking'));
        $export->setDescription(__("Export the plugin settings in a JSON file. It doesn't export data or user settings.", 'team-booking'));
        $export->addContent(\VSHM\UI\Admin\Settings_Content::CustomType('exportSettingsButton'));
        $export->setAlert(\VSHM\UI\Admin\Alert::warning(__("The exported file can't be imported on different versions of the plugin. This is meant to migrate settings from one environment to one another, not to pass settings through plugin versions which is not needed.", 'team-booking')));
        $settings_panel->addItem($export);

        $import = \VSHM\UI\Admin\Settings_Informative::get(__('Import settings', 'team-booking'));
        $import->addContent(\VSHM\UI\Admin\Settings_Content::CustomType('importSettingsButton'));
        $import->setDescription(__("Import the plugin settings from a JSON file. It doesn't import data or user settings.", 'team-booking'));
        $settings_panel->addItem($import);

        $import = \VSHM\UI\Admin\Settings_Informative::get(__('Import/Export data', 'team-booking'));
        global $wpdb;
        $import->addContent(\VSHM\UI\Admin\Settings_Content::UnorderedList([
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\Reservations::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\ReservationsData::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\Services::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\ServicesData::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\Promotions::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\Locations::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\ServiceProviderCustomData::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Providers\Customers::TABLE_NAME),
            \VSHM\UI\Admin\Settings_Content::Text($wpdb->prefix . \VSHM\Modules\EventLogger::TABLE_NAME),
        ]));
        $import->setDescription(__("Data such as services, reservations, promotions and so on, requires an import/export of the following tables though a proper db migration plugin.", 'team-booking'));
        $settings_panel->addItem($import);

        if (\VSHM\Update::is_v2_data_present()) {
            $export = \VSHM\UI\Admin\Settings_Informative::get(__('Migrate from version 2.x', 'team-booking'));
            $export->setDescription(__("Migrates the data from old plugin version installed.", 'team-booking'));
            $export->addContent(\VSHM\UI\Admin\Settings_Content::CustomType('apiCallButtonPost', [
                'route'      => '/backend/migrate/fromv2/',
                'buttonText' => __("Migrate", 'team-booking'),
                'postAction' => 'RELOAD'
            ]));
            $export->setAlert(\VSHM\UI\Admin\Alert::error(__("This will overwrite the current plugin data.", 'team-booking')));
            $settings_panel->addItem($export);
            $export = \VSHM\UI\Admin\Settings_Informative::get(__('Clean version 2.x data', 'team-booking'));
            $export->setDescription(__("Removes data of previous plugin versions.", 'team-booking'));
            $export->addContent(\VSHM\UI\Admin\Settings_Content::CustomType('apiCallButtonPost', [
                'route'      => '/backend/migrate/remove/v2data/',
                'buttonText' => __("Cleanup", 'team-booking')
            ]));
            $export->setAlert(\VSHM\UI\Admin\Alert::error(__("In case you migrated the data, please ensure that all is working as expected, before cleaning up 2.x data. This operation cannot be undone.", 'team-booking')));
            $settings_panel->addItem($export);
        }

        $menu_item_sub->setContent($settings_panel);
        $menu_item->addItem($menu_item_sub);

        $menu_item_sub  = \VSHM\UI\Admin\SidebarItem::option(__('Advanced', 'team-booking'), 'core-adv');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $settings_panel->addItem(\VSHM\Settings\UseCache::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\FetchingGranularity::getBackendElement());

        $menu_item_sub->setContent($settings_panel);
        $menu_item->addItem($menu_item_sub);

        $page->addSidebarItem($menu_item);

        $menu_item = \VSHM\UI\Admin\SidebarItem::submenu(__('Payment Gateways', 'team-booking'), 'payment-gateways');

        $page->addSidebarItem(apply_filters('tbk_backend_collecting_payment_gateways_panels', $menu_item));

        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('API', 'team-booking'), 'api');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $table = \VSHM\UI\Admin\Plugin\DataTableApiTokens::get(__('API tokens', 'team-booking'));
        $table->setDescription(__('API tokens can be used by 3rd party applications to read/write the plugin data.', 'team-booking'));
        $table->setEndpoint(\VSHM\Routes\ApiTokensRoute::getPath());
        $table->addColumn(__('Name', 'team-booking'), 'name', 'name');
        $table->addColumn(__('Token', 'team-booking'), 'token', 'token');
        $table->addColumn(__('Usages', 'team-booking'), 'usages', 'usages');
        $table->addColumn(__('Actions', 'team-booking'), 'actions');
        $settings_panel->addItem($table);

        $urlInfo = \VSHM\UI\Admin\Settings_Informative::get(__('API endpoint', 'team-booking'));
        $urlInfo->setDescription(\VSHM\REST_Controller::get_root_rest_url() . \VSHM\Routes\ApiRoute::getPath());
        $settings_panel->addItem($urlInfo);

        $warning = \VSHM\UI\Admin\Settings_Informative::get(__('A note on API tokens', 'team-booking'));
        $warning->setDescription(__("Don't generate tokens if you don't know what you're doing. Read the documentation to explore how the API endpoint works.", 'team-booking'));
        $warning->setAlert(\VSHM\UI\Admin\Alert::warning(__("Give tokens only to someone you trust, as they are able to read/write sensitive data.", 'team-booking')));

        $settings_panel->addItem($warning);

        $menu_item->setContent($settings_panel);
        $page->addSidebarItem($menu_item);

        $menu_item      = \VSHM\UI\Admin\SidebarItem::option(__('Google Maps', 'team-booking'), 'gmaps');
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $settings_panel->setTitle(__('Google Maps', 'team-booking'));
        $settings_panel->setDescription(__('Google requires the activation of a Google Cloud billing account, in order to use Google Maps in your website.', 'team-booking'));
        $settings_panel->setIconUrl(vshm()->plugin['URL'] . '/Assets/maps_logo.svg');

        $settings_panel->addItem(\VSHM\Settings\GoogleMapsApiKey::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\SkipGoogleMapsLib::getBackendElement());
        $settings_panel->addItem(\VSHM\Settings\LoadMapsAutocomplete::getBackendElement());

        $menu_item->setContent($settings_panel);
        $page->addSidebarItem($menu_item);

        return \VSHM\UI\Admin\MenuItem::tab(
            __('Settings', 'team-booking'),
            apply_filters('tbk_backend_settings_page_items', $page),
            \VSHM\UI\Admin\Icons::SETTINGS,
            'settings'
        );
    }

    /**
     * Reservations page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_reservations(): \VSHM\UI\Admin\MenuItem
    {
        $page           = \VSHM\UI\Admin\Page::full_width();
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $table = \VSHM\UI\Admin\Plugin\DataTableReservations::get('');

        $settings_panel->addItem($table);
        $page->setContent($settings_panel);

        return \VSHM\UI\Admin\MenuItem::tab(__('Reservations', 'team-booking'), $page, \VSHM\UI\Admin\Icons::SCHEDULE, 'reservations');
    }

    /**
     * Providers page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_providers(): \VSHM\UI\Admin\MenuItem
    {
        $page           = \VSHM\UI\Admin\Page::full_width();
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $table = \VSHM\UI\Admin\Plugin\DataTableProviders::get('');
        $table->setEndpoint(\VSHM\Routes\ProvidersRoute::getPath());
        $table->addColumn(__('Name', 'team-booking'), 'name', 'name');
        $table->addColumn(__('E-mail', 'team-booking'), 'email', 'email');
        $table->addColumn(__('WP Roles', 'team-booking'), 'wpRoles', 'wpRoles');
        $table->addColumn(__('Settings', 'team-booking'), 'settings');

        $providerSettingItems = [
            [
                'id'    => 'general',
                'label' => __('General', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Provider\RestrictServices::getBackendElement()->get_structure(),
                    \VSHM\Settings\Provider\AllowedServices::getBackendElement()->get_structure(),
                ]
            ],
        ];

        $table->addSettingItems($providerSettingItems);

        $settings_panel->addItem($table);

        $page->setContent($settings_panel);

        return \VSHM\UI\Admin\MenuItem::tab(__('Providers', 'team-booking'), $page, \VSHM\UI\Admin\Icons::TEAM, 'providers');
    }

    /**
     * Customers page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_customers(): \VSHM\UI\Admin\MenuItem
    {
        $page           = \VSHM\UI\Admin\Page::full_width();
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $table          = \VSHM\UI\Admin\Plugin\DataTableCustomers::get('');
        $table->setEndpoint(\VSHM\Routes\CustomersRoute::getPath());

        $table->addColumn(__('Name', 'team-booking'), 'name', 'name');
        $table->addColumn(__('E-mail', 'team-booking'), 'email', 'email');
        $table->addColumn(__('Phone', 'team-booking'), 'phone', 'phone');
        $table->addColumn(__('Actions', 'team-booking'), 'actions');

        $customerSettingItems = [
            [
                'id'    => 'general',
                'label' => __('General', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Customer\Name::getBackendElement()->get_structure(),
                    \VSHM\Settings\Customer\Email::getBackendElement()->get_structure(),
                    \VSHM\Settings\Customer\Phone::getBackendElement()->get_structure()
                ]
            ],
        ];

        $table->addSettingItems($customerSettingItems);
        $settings_panel->addItem($table);
        $page->setContent($settings_panel);

        return \VSHM\UI\Admin\MenuItem::tab(__('Customers', 'team-booking'), $page, \VSHM\UI\Admin\Icons::CONTACTS, 'customers');
    }

    /**
     * Services page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_services(): \VSHM\UI\Admin\MenuItem
    {
        $page = \VSHM\UI\Admin\Page::full_width();

        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();

        $table = \VSHM\UI\Admin\Plugin\DataTableServices::get('');
        $table->setEndpoint(\VSHM\Routes\ServicesRoute::getPath());
        $table->addColumn(__('Name', 'team-booking'), \VSHM\Settings\Service\Name::ID, \VSHM\Settings\Service\Name::ID);
        $table->addColumn(__('Status', 'team-booking'), 'status', 'status');
        $table->addColumn(__('Class', 'team-booking'), 'class', 'class');

        if (\VSHM\Functions::current_user_can_admin()) {
            $table->addColumn(__('Actions', 'team-booking'), 'actions');
        }

        $table->addColumn(__('Settings', 'team-booking'), 'settings');

        $serviceSettingItems = [
            [
                'id'    => 'general',
                'label' => __('General', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Name::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\ShortDescription::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Id::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Description::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Color::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Picture::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\SlotDurationRule::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\SlotDuration::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Location::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\LocationAssigned::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\LocationVisibility::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\ShowMap::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\CreateZoomMeeting::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\TotalSlotTickets::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\TotalUserSlotTickets::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\BlockAvailabilityAfterOneReservation::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\AssignmentRule::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\DirectProvider::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'frontend',
                'label' => __('Frontend', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\ShowBookedSlots::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\ShowSlotCustomers::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\DiscardedAvailableSlots::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\ShowTimes::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\ShowProvider::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\ShowProviderUrl::getBackendElement()->get_structure()
                ]
            ],
            [
                'id'    => 'approval',
                'label' => __('Approval', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Approval::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\UntilApproval::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'cancellation',
                'label' => __('Cancellation', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\AllowCancellation::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\CancellationReason::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\CancellationTimespan::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'payments',
                'label' => __('Payments', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Price::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\PaymentRequirement::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'access',
                'label' => __('Access', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Access::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\MaxUserReservations::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'redirect',
                'label' => __('Redirect and conversions', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Redirect::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\RedirectUrl::getBackendElement()->get_structure()
                ]
            ]
        ];

        $table->addSettingItems(apply_filters('tbk_service_settings_items', $serviceSettingItems));

        $table->addNotificationItems(apply_filters('tbk_service_notifications_items', []));

        $personalItems = [
            [
                'id'    => 'availability',
                'label' => __('Personal Availability', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Personal_Participate::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_SlotDurationRule::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_SlotDuration::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_BufferTimespan::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_BufferRule::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'behavior',
                'label' => __('Behavior', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Personal_WhenToClose::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_WhenToCloseReference::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_WhenToOpen::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'overlapping',
                'label' => __('Overlapping', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Service\Personal_DiscardOverlappingWithPersonal::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_DiscardOverlappingWithSame::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_OverlappingWithSameDropTickets::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_DiscardOverlappingWithOther::getBackendElement()->get_structure(),
                    \VSHM\Settings\Service\Personal_FillingLogic::getBackendElement()->get_structure(),
                ]
            ]
        ];

        $table->addPersonalItems(apply_filters('tbk_service_personal_settings_items', $personalItems));

        $settings_panel->addItem($table);

        $page->setContent($settings_panel);

        return \VSHM\UI\Admin\MenuItem::tab(__('Services', 'team-booking'), $page, \VSHM\UI\Admin\Icons::SERVICES, 'services');
    }

    /**
     * Promotions page
     *
     * @return \VSHM\UI\Admin\MenuItem
     */
    private static function _get_page_promotions(): \VSHM\UI\Admin\MenuItem
    {
        $page           = \VSHM\UI\Admin\Page::full_width();
        $settings_panel = \VSHM\UI\Admin\SettingsPanel::get();
        $table          = \VSHM\UI\Admin\Plugin\DataTablePromotions::get('');

        $promotionSettingItems = [
            [
                'id'    => 'general',
                'label' => __('General', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Promotion\Name::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\PromotionType::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\DiscountType::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\Value::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\PromotionPeriod::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'advanced',
                'label' => __('Advanced', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Promotion\CouponMode::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\MaximumUses::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\TimeslotMinStartActive::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\TimeslotMinStart::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\TimeslotMaxEndActive::getBackendElement()->get_structure(),
                    \VSHM\Settings\Promotion\TimeslotMaxEnd::getBackendElement()->get_structure(),
                ]
            ],
            [
                'id'    => 'services',
                'label' => __('Target services', 'team-booking'),
                'items' => [
                    \VSHM\Settings\Promotion\PromotionServices::getBackendElement()->get_structure(),
                ]
            ],
        ];

        $table->addSettingItems($promotionSettingItems);

        $settings_panel->addItem($table);
        $page->setContent($settings_panel);

        return \VSHM\UI\Admin\MenuItem::tab(__('Promotions', 'team-booking'), $page, \VSHM\UI\Admin\Icons::PROMOTION, 'promotions');
    }

    /**
     * @param $structure
     *
     * @return mixed
     */
    public function menu_items($structure)
    {
        // TODO: dashboard and stats
        #$structure[] = self::_get_page_dashboard()->get_structure();
        $structure[] = self::_get_page_reservations()->get_structure();
        $structure[] = self::_get_page_availability()->get_structure();
        $structure[] = self::_get_page_services()->get_structure();

        if (\VSHM\Functions::current_user_can_admin()) {
            $structure[] = self::_get_page_providers()->get_structure();
            $structure[] = self::_get_page_customers()->get_structure();
            $structure[] = self::_get_page_promotions()->get_structure();
            $structure[] = self::_get_page_settings()->get_structure();
        }

        return $structure;
    }

    /**
     *
     * Create new plugin options page under the Settings menu.
     */
    public function create_admin_menu_page(): void
    {
        if (isset($_GET['page'])) {
            $page = sanitize_text_field($_GET['page']);
        }

        $this->plugin_options_page = add_menu_page(
            'TheBooking',
            'TheBooking',
            \VSHM\Settings\AllowedServiceProviderWpRoles::ROLE,
            'team-booking',
            [$this, 'render_plugin_options_page'],
            'data:image/svg+xml;base64,PHN2ZyBpZD0idGJsX2xvZ28iIGRhdGEtbmFtZT0idGJrIGxvZ28iIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDEwNC4yOSA4MC4yOCI+PGRlZnM+PHN0eWxlPi5jbHMtMXtmaWxsOm5vbmU7fS5jbHMtMntmaWxsOiNkNDE0NWE7fS5jbHMtM3tmaWxsOiMwNzdhZWY7fS5jbHMtNHtmaWxsOiMwNzdhZWY7fTwvc3R5bGU+PC9kZWZzPjx0aXRsZT5pY29uPC90aXRsZT48cmVjdCBjbGFzcz0iY2xzLTEiIHg9IjEyLjU1IiB5PSI1Ny4yMSIgd2lkdGg9IjI5LjI1IiBoZWlnaHQ9IjE4LjI4IiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMTguOTQgLTE2LjIpIHJvdGF0ZSgtNi41KSIvPjxyZWN0IGNsYXNzPSJjbHMtMiIgeD0iMTIuNTUiIHk9IjU3LjIxIiB3aWR0aD0iMjkuMjUiIGhlaWdodD0iMTguMjgiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xOC45NCAtMTYuMikgcm90YXRlKC02LjUpIi8+PHJlY3QgY2xhc3M9ImNscy0zIiB4PSI1My43OSIgeT0iODAuMTEiIHdpZHRoPSIyOS4yNSIgaGVpZ2h0PSIxOC4yOCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTIxLjI3IC0xMS4zOCkgcm90YXRlKC02LjUpIi8+PHJlY3QgY2xhc3M9ImNscy00IiB4PSI4NS43MiIgeT0iMjEuMjkiIHdpZHRoPSIyOS4yNSIgaGVpZ2h0PSIxOC4yOCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTE0LjQxIC04LjE1KSByb3RhdGUoLTYuNSkiLz48L3N2Zz4='
        );
    }

    public function render_plugin_options_page(): void
    {
        $style = !\VSHM\Update::is_awaiting_migration_from_v2() ? '' : '<style>
                body {
                 background: white;
                }
            }
            </style>';
        echo $style . '<div id="tbk-backend"></div>';
    }

    public function enqueue_frontend_scripts($hook): void
    {
        $handle = 'tbk-scripts';

        $dep = ['wp-api', 'wp-i18n'];

        // enqueue development or production React code
        if (file_exists(__DIR__ . "/dist/frontend.js")) {
            $dep[] = 'wp-element';
            \VSHM\Tools::enqueue_script($handle, "/dist/frontend.js", $dep, TRUE);
            \VSHM\Tools::enqueue_script($handle . '-vendor', "/dist/frontendVendor.js", $dep, TRUE);
            \VSHM\Tools::enqueue_style($handle . '-css', '/dist/frontend.css');
            \VSHM\Tools::enqueue_style($handle . '-vendor-css', '/dist/frontendVendor.css');

        } else {
            \VSHM\Tools::enqueue_script($handle . '-runtime', '//localhost:8080/assets/runtime.js', $dep, TRUE);
            \VSHM\Tools::enqueue_script($handle, '//localhost:8080/assets/frontend.js', $dep, TRUE);
        }

        // Try to load from the languages directory first
        $path = WP_LANG_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
        if (file_exists($path . 'team-booking-en_US.po')) {
            wp_set_script_translations($handle, 'team-booking', $path);
        } else {
            $path = vshm()->plugin['PATH'] . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
            wp_set_script_translations($handle, 'team-booking', $path);
        }
        vshm()->add_frontend_env_vars($handle);
    }

    public function enqueue_admin_scripts($hook): void
    {

        // Are we on the plugin options page?
        if ($hook === $this->plugin_options_page) {

            wp_enqueue_media();
            wp_deregister_style('forms');
            wp_enqueue_style('admin-bar');
            wp_enqueue_style('nav-menus');
            wp_enqueue_style('admin-menu');
            wp_enqueue_style('common');
            wp_enqueue_style('media');

            $dep = ['wp-api', 'wp-i18n'];
            //$dep = ['react', 'react-dom']; // alternative way of loading React via WP core

            $handle = 'tbk-backend-scripts';

            // Load external scripts
            \VSHM\Tools::enqueue_script('tbk-sheet-js', '//cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.mini.min.js', [], FALSE);
            \VSHM\Tools::enqueue_script('tbk-nouislider-js', '/Libs/noUiSlider/nouislider.min.js', [], FALSE);
            \VSHM\Tools::enqueue_style('tbk-nouislider-css', '/Libs/noUiSlider/nouislider.min.css');

            // enqueue development or production React code
            if (file_exists(__DIR__ . "/dist/backend.js")) {
                $dep[] = 'wp-element';
                \VSHM\Tools::enqueue_script($handle, '/dist/backend.js', $dep, TRUE);
                \VSHM\Tools::enqueue_script($handle . '-vendor', '/dist/backendVendor.js', $dep, TRUE);
                \VSHM\Tools::enqueue_style($handle . '-css', '/dist/backend.css');
                \VSHM\Tools::enqueue_style($handle . '-vendor-css', '/dist/backendVendor.css');
                vshm()->add_backend_env_vars($handle);
            } else {
                \VSHM\Tools::enqueue_script($handle . '-runtime', '//localhost:8080/assets/runtime.js', $dep, TRUE);
                \VSHM\Tools::enqueue_script($handle, '//localhost:8080/assets/backend.js', $dep, TRUE);
                vshm()->add_backend_env_vars($handle);
            }

            // Try to load from the languages directory first
            $path = WP_LANG_DIR . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
            if (file_exists($path . 'team-booking-en_US.po')) {
                wp_set_script_translations($handle, 'team-booking', $path);
            } else {
                $path = vshm()->plugin['PATH'] . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
                wp_set_script_translations($handle, 'team-booking', $path);
            }
        }
    }
}

$tbk_options = new tbkOptions();
$tbk_options->init();
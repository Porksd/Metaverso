<?php

namespace BitCode\BitFormPro;

/**
 * Main class for the plugin.
 *
 * @since 1.0.0-alpha
 */

use BitCode\BitFormPro\Admin\Admin_Bar;
use BitCode\BitFormPro\Admin\DblOptin;
use BitCode\BitFormPro\API\Route\Routes;
use BitCode\BitFormPro\Auth\Auth;
use BitCode\BitFormPro\Auth\UserRowAction;
use BitCode\BitFormPro\Core\Ajax\AjaxService;
use BitCode\BitFormPro\Core\CronShedule;
use BitCode\BitFormPro\Core\Database\DB;
use BitCode\BitFormPro\Core\Database\PostInfoModel;
use BitCode\BitFormPro\Core\Update\Updater;
use BitCode\BitFormPro\Core\Util\FormDuplicateEntry;
use BitCode\BitFormPro\Frontend\DoubleOptin;
use BitCode\BitFormPro\Frontend\UserActivation;
use BitCode\BitFormPro\Integration\Integrations;
use BitCode\BitForm\Core\Capability\Request;
use BitCode\BitForm\Core\Database\FormModel;
use BitCode\BitForm\Core\Integration\IntegrationHandler;

final class Plugin
{
    /**
     * Main instance of the plugin.
     *
     * @since 1.0.0-alpha
     * @var   Plugin|null
     */
    private static $instance = null;

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function initialize()
    {
        add_action('init', [$this, 'init_classes'], 10);
        add_action('init', [$this, 'handleVersionUpdateFallback']);
        add_filter('plugin_action_links_' . plugin_basename(BITFORMPRO_PLUGIN_MAIN_FILE), [$this, 'plugin_action_links']);
        add_filter('bitform_dynamic_field_filter', [$this, 'dynamicFieldValues'], 10, 8);
        add_action('rest_api_init', [$this, 'register_api'], 10);
        add_action('bf_double_optin_confirmation', [new DblOptin(), 'sendEntryMailConfirmation'], 10, 2);
        add_filter('bf_email_body_text', [new DblOptin(), 'updatedEmailBodyMessage'], 10, 2);

        if (version_compare(BITFORMS_VERSION, '2.13.2', '>=') && class_exists('\BitCode\BitForm\Config')) {
            add_filter(\BitCode\BitForm\Config::VAR_PREFIX . 'telemetry_data', [self::class, 'telemetryProStatus'], 10, 1);
        }
    }

    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes()
    {
        if (!empty($_GET['bf_activation_key']) && !empty($_GET['bf_f_id']) && !empty($_GET['bf_user_id'])) {
            (new UserActivation($_GET['bf_activation_key'], $_GET['bf_f_id'], $_GET['bf_user_id']))->emailVerified();
        }
        if (!empty($_GET['token']) && !empty($_GET['entry_id'])) {
            (new DoubleOptin($_GET['entry_id'], $_GET['token']))->approvedEntryByEmail();
        }

        if (Request::Check('admin')) {
            (new Admin_Bar())->register();
            new Updater();
        }
        if (Request::Check('ajax')) {
            new AjaxService();
        }
        (new Integrations())->registerHooks();
        (new CronShedule())->cron_schedule();
        (new FormDuplicateEntry())->register();
        (new Auth())->register();
        (new DblOptin())->deletedUnconfirmedEntries();

        if (method_exists('BitCode\BitForm\Core\Integration\IntegrationHandler', 'getIntegrationWithoutFormId')) {
            $existAuth = (new IntegrationHandler(0))->getIntegrationWithoutFormId('wp_user_auth', 'wp_auth', 1);
            if (!is_wp_error($existAuth) && !isset($existAuth->errors['result_empty'])) {
                (new UserRowAction())->userRowAction();
            }
        }
    }

    public function register_api()
    {

        $routes = new Routes();
        $routes->register_routes();
    }

    public function handleVersionUpdateFallback()
    {
        $installed = get_option('bitformpro_installed');
        $oldversion = null;
        if ($installed) {
            $oldversion = get_option('bitformpro_version');
        }
        if (!$oldversion) {
            update_option('bitformpro_version', BITFORMPRO_VERSION);
            $this->changeValidationErrObjectOfIsUnique();
        }
        // if ($oldversion && version_compare($oldversion, BITFORMPRO_VERSION, '!=')) {
        //     update_option('bitformpro_version', BITFORMPRO_VERSION);
        //     if (version_compare('1.4.10', $oldversion, '>=')) {
        //         $this->changeValidationErrObjectOfIsUnique();
        //     }
        // }
    }

    private function changeValidationErrObjectOfIsUnique()
    {
        $formModel = new FormModel();
        $forms = $formModel->get(
            ['id', 'form_content']
        );
        if (!is_wp_error($forms)) {
            foreach ($forms as $form) {
                $formID = $form->id;
                $formContent = json_decode($form->form_content);
                $fields = $formContent->fields;

                foreach ($fields as $fldKey => $fldData) {
                    if (isset($fldData->err) && isset($fldData->err->entryUnique)) {
                        if (isset($fldData->err->entryUnique->isEntryUnique)) {
                            unset($fldData->err->entryUnique->isEntryUnique);
                            $fldData->err->entryUnique->show = true;
                        } else {
                            $fldData->err->entryUnique->show = false;
                        }

                        if (isset($fldData->err->userUnique->isUserUnique)) {
                            unset($fldData->err->userUnique->isUserUnique);
                            $fldData->err->userUnique->show = true;
                        } else {
                            $fldData->err->userUnique->show = false;
                        }
                    }

                    if (isset($fldData->{"entryUnique:"})) {
                        unset($fldData->{"entryUnique:"});
                    }

                    $fields->{$fldKey} = $fldData;
                }

                $formContent->fields = $fields;

                $formModel->update(
                    [
                        "form_content" => wp_json_encode($formContent),
                    ],
                    [
                        "id" => $formID,
                    ]
                );
            }
        }
    }

    private function dynamicTaxanomyFields($taxonomy, $order, $orderBy)
    {
        $allCategoreis = get_terms(
            [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'orderby' => $orderBy,
                'order' => $order,
            ]
        );
        return $allCategoreis;
    }
    private function userDynamicFieldsData($role, $order, $orderBy)
    {
        $args = [
            'orderby' => $orderBy,
            'order' => $order,
            'fields' => ['ID', 'display_name', 'user_login', 'user_email', 'user_nicename'],
        ];
        if ($role != 'all') {
            $args['role'] = $role;
        }
        $users = get_users($args);
        return $users;
    }

    private function postDynamicFildData($postType, $order, $orderBy, $postStatus)
    {
        $postModel = new PostInfoModel();
        $posts = $postModel->getAllPosts($postType, $orderBy, $order, $postStatus);

        return $posts;
    }

    private function acfDynamicOptions($fieldKey)
    {
        $options = [];
        $types = ['select', 'checkbox', 'radio'];
        $groups = acf_get_field_groups();
        foreach ($groups as $group) {
            foreach (acf_get_fields($group['key']) as $acfField) {
                if (in_array($acfField['type'], $types) && $acfField['key'] === $fieldKey) {
                    $options = $acfField['choices'];
                }
            }
        }
        return $options;
    }

    public function dynamicFieldValues($fields)
    {
        $dynamicOptionFieldTypes = ['check', 'radio', 'select', 'html-select'];
        $isMigratedToV2 = get_option('bitforms_migrated_to_v2');
        foreach ($fields as $key => $field) {
            if (in_array($field->typ, $dynamicOptionFieldTypes)) {
                $optionLbl = 'lbl';
                $optionVal = 'val';

                // this logis is for V1 Select field
                if ($field->typ == 'select' && !$isMigratedToV2) {
                    $optionLbl = 'label';
                    $optionVal = 'value';
                } elseif ($field->typ == 'select') {
                    foreach ($field->optionsList as $keyIndex => $optionListObj) {
                        if (property_exists($field, 'customTypeList') && !empty($field->customTypeList[$keyIndex])) {
                            $listArray = (array) $optionListObj;
                            $listName = array_keys($listArray)[0];
                            $field->optionsList[$keyIndex]->$listName = $this->getDynamicOptions($optionLbl, $optionVal, $field->customTypeList[$keyIndex]);
                        }
                    }
                }
                if (property_exists($field, 'customType') && !empty($field->customType)) {
                    $field->opt = $this->getDynamicOptions($optionLbl, $optionVal, $field->customType);
                }
                $fields->$key = $field;
                //$field->opt = array_merge($field->opt, $field->customType->oldOpt);
            }
        }
        return $fields;
    }

    private function getDynamicOptions($optLbl, $optVal, $customType)
    {
        $options = [];
        $opt = [];

        $filter = $customType->filter;
        if ($customType->fieldType === 'user_field') {
            $options = $this->userDynamicFieldsData($filter->role, $filter->order, $filter->orderBy);
        } elseif ($customType->fieldType === 'taxanomy_field') {
            $options = $this->dynamicTaxanomyFields($filter->taxanomy, $filter->order, $filter->orderBy);
        } elseif ($customType->fieldType === 'post_field') {
            $options = $this->postDynamicFildData($filter->postType, $filter->order, $filter->orderBy, $filter->postStatus);
        } elseif ($customType->fieldType === 'acf_options') {
            $data = $this->acfDynamicOptions($filter->fieldkey);
            if (!empty($data)) {
                foreach (array_values($data) as $key => $option) {
                    $opt[$key] = (object) [];
                    $opt[$key]->$optLbl = (string) $option;
                }
                foreach (array_keys($data) as $key => $option) {
                    $opt[$key]->$optVal = (string) $option;
                }
            }
        }
        if (!empty($options) && !is_wp_error($options)) {
            $lebel = $customType->lebel;
            $value = empty($customType->hiddenValue) ? $customType->value : $customType->hiddenValue;

            foreach ($options as $key => $option) {
                if (!empty($lebel) && !empty($value)) {
                    $opt[$key] = (object) [];
                    $opt[$key]->$optLbl = (string) $option->$lebel;
                    $opt[$key]->$optVal = (string) str_replace(',', '_', $option->$value);
                }
            }
        }
        return $opt;
    }

    /**
     * Plugin action links
     *
     * @param  array $links
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        $links[] = '<a href="https://docs.form.bitapps.pro/" target="_blank">' . __('Docs', 'bitformpro') . '</a>';

        return $links;
    }

    /**
     * Retrieves the main instance of the plugin.
     *
     * @since 1.0.0-alpha
     *
     * @return BITFORMPRO Plugin main instance.
     */
    public static function instance()
    {
        return static::$instance;
    }

    public static function update_tables()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        global $bitformspro_db_version;
        $installed_db_version = get_site_option("bitformspro_db_version");
        if ($installed_db_version != $bitformspro_db_version) {
            DB::migrate();
        }
    }
    /**
     * Loads the plugin main instance and initializes it.
     *
     * @since 1.0.0-alpha
     *
     * @param string $main_file Absolute path to the plugin main file.
     * @return bool True if the plugin main instance could be loaded, false otherwise./
     */
    public static function load($main_file)
    {
        if (null !== static::$instance) {
            return false;
        }
        static::update_tables();
        static::$instance = new static($main_file);
        static::$instance->initialize();
        return true;
    }

    public static function telemetryProStatus($data)
    {
        $integrateData = get_option('bitformpro_integrate_key_data');

        $data['pro'] = [
            'version' => BITFORMPRO_VERSION,
            'hasLicense' => $integrateData['key'] ? true : false,
        ];

        if ($integrateData['key']) {
            $data['pro']['license'] = $integrateData['key'] ?? '';
            $data['pro']['status'] = $integrateData['status'] ?? '';
            $data['pro']['expireAt'] = $integrateData['expireIn'] ?? '';
        }
        return $data;
    }
}
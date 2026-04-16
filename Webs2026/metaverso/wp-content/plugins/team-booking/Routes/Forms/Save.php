<?php

namespace VSHM\Routes\Forms;

use VSHM\Bus\CreateFormField;
use VSHM\Bus\DeleteFormField;
use VSHM\Bus\UpdateForm;
use VSHM\Bus\UpdateFormField;
use VSHM\Functions;
use VSHM\REST_Controller;
use VSHM\Routes\FormsRoute;
use VSHM\Routes\SingleRoute;

defined('ABSPATH') || exit;

/**
 * Class Save
 *
 * @package VSHM\Routes
 */
class Save implements SingleRoute
{
    public static function getPath(): string
    {
        return FormsRoute::getPath() . 'save/';
    }

    public static function get(): array
    {
        return [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => function (\WP_REST_Request $request) {

                $item = $request->get_param('data');

                $currentData = \VSHM\Providers\Forms::provideBy([
                    'id' => $item['id']
                ], TRUE);

                if (!$currentData) {
                    return REST_Controller::get_error_response(self::getPath());
                }

                /**
                 * See if there are fields to add/remove
                 *
                 * @NEXT we are doing this because at the moment a Form Field is only
                 *       linked to a single form
                 */
                $newFieldsIds = array_column($item['fields'], 'id');
                $newFieldsIds = array_unique($newFieldsIds);
                foreach ($currentData['fields'] as $fieldId) {
                    /**
                     * If $fieldId is not present in $newFields then delete it
                     */
                    if (!in_array($fieldId, $newFieldsIds, TRUE)) {
                        vshm()->bus->dispatch(new DeleteFormField($fieldId));
                    }
                }
                foreach ($item['fields'] as $newField) {
                    if (!in_array($newField['id'], $currentData['fields'], TRUE)) {
                        /**
                         * @NEXT when form fields can be shared across multiple form records
                         *       we need to ensure that the field id doesn't exist before creating it
                         */
                        vshm()->bus->dispatch(new CreateFormField(
                            $newField['id'],
                            $newField['type'],
                            $newField['hook'],
                            $newField['label'],
                            $newField['data'],
                            $newField['description']
                        ));
                    } else {
                        /**
                         * Updating field
                         */
                        vshm()->bus->dispatch(new UpdateFormField(
                            $newField['id'],
                            $newField['type'],
                            $newField['hook'],
                            $newField['label'],
                            $newField['data'],
                            $newField['description']
                        ));
                    }
                }

                vshm()->bus->dispatch(new UpdateForm(
                    $item['id'],
                    $newFieldsIds,
                    $item['required'],
                    $item['active'],
                    $item['logic']
                ));

                return REST_Controller::get_ok_response(
                    self::getPath(),
                    ['message' => __('Form has been saved', 'team-booking')]
                );
            },
            'permission_callback' => static function () {
                return Functions::current_user_can_admin(TRUE);
            }
        ];
    }
}
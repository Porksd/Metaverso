<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\DB;
use VSHM\Providers\FormFields;
use VSHM\Providers\Forms;

defined('ABSPATH') || exit;

/**
 * CleanFormsHandler
 *
 * @package VSHM\Bus
 */
class CleanFormsHandler implements HandlerInterface
{

    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CleanForms */
        $forms          = Forms::provide();
        $fields_to_keep = [];
        foreach ($forms as $form) {
            if (is_array($form['fields']) && !empty($form['fields'])) {
                $fields_to_keep += $form['fields'];
            }
        }
        DB::deleteWhere(FormFields::TABLE_NAME, [
            'field_id' => [
                'operator' => 'NOT IN',
                'value'    => $fields_to_keep
            ]
        ]);
    }
}
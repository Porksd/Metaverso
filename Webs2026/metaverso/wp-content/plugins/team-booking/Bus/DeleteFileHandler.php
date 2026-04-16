<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * DeleteFileHandler
 *
 * @package VSHM\Bus
 */
class DeleteFileHandler implements HandlerInterface
{

    public function dispatch(CommandInterface $command): void
    {
        /** @var $command DeleteFile */
        DB::create_table(SaveFileHandler::$table_name, SaveFileHandler::$table_structure);

        $file = DB::select(SaveFileHandler::$table_name, '*', DB::where([
            'hash' => $command->getData()
        ]), '', FALSE, 'ARRAY_A', 'get_row');

        if ($file) {
            wp_delete_file($file['path']);
            DB::delete(SaveFileHandler::$table_name, [
                'hash' => $command->getData()
            ]);
        }

        do_action('tbk_file_is_deleted', $command->getData());

    }
}
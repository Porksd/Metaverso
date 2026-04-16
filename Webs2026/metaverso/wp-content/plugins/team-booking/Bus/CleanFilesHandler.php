<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\DB;

defined('ABSPATH') || exit;

/**
 * CleanFilesHandler
 *
 * @package VSHM\Bus
 */
class CleanFilesHandler implements HandlerInterface
{

    public function dispatch(CommandInterface $command): void
    {
        /** @var $command CleanFiles */

        global $wpdb;
        $table_name_1 = $wpdb->prefix . SaveFileHandler::$table_name;
        $table_name_2 = $wpdb->prefix . 'tbk_reservations_data';

        DB::create_table(SaveFileHandler::$table_name, SaveFileHandler::$table_structure);

        /**
         * Select all those file records having no connection with any reservation record.
         */
        $results = $wpdb->get_results("SELECT * FROM $table_name_1 one WHERE NOT EXISTS (SELECT * FROM $table_name_2 two WHERE two.data_value LIKE CONCAT('%', one.hash, '%'))", 'OBJECT');
        $now     = time();
        $tz      = new \DateTimeZone('UTC');
        foreach ($results as $file) {
            if ($now - \DateTime::createFromFormat('Y-m-d H:i:s', $file->created, $tz)->getTimestamp() > $command->getData()) {
                wp_delete_file($file->path);
                DB::delete(SaveFileHandler::$table_name, [
                    'id' => $file->id
                ]);
            }
        }

    }
}
<?php

namespace VSHM\Bus;

use VSHF\Bus\CommandInterface;
use VSHF\Bus\HandlerInterface;
use VSHM\Providers\Files;

defined('ABSPATH') || exit;

/**
 * SaveFileHandler
 *
 * @package VSHM\Bus
 */
class SaveFileHandler implements HandlerInterface
{
    public static $table_name = 'tbk_uploaded_files';

    public static $table_structure = [
        'hash' => 'varchar',
        'path' => 'text',
        'url'  => 'text',
        'mime' => 'varchar'
    ];

    public function dispatch(CommandInterface $command): void
    {
        /** @var $command SaveFile */

        Files::store($command->getData());

        do_action('tbk_file_is_uploaded', $command->getData());

    }
}
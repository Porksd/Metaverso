<?php
namespace BitCode\BitFormPro\Core\Util;

class Utility
{

    public static function loadFile($path, $data)
    {
        $file = BITFORMPRO_PLUGIN_DIR_PATH . $path . '.php';
        
        extract($data);

        ob_start();

        include($file);

        return ob_get_clean();

    }

    public static function view($path, $data = []){

        status_header(200);
        
        echo self::loadFile($path, $data);
        
        exit(200);

    }


}

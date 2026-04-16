<?php

namespace VSHM\UI\Customizer;

class Separator_Control extends \WP_Customize_Control
{
    public $type = 'vshm-separator';

    public function render_content()
    {
        ?>
        <hr>
        <?php
    }
}
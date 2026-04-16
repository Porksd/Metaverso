<?php

namespace VSHM\UI\Customizer;

class Range_Slider_Control extends \WP_Customize_Control
{
    public $type = 'vshm-range-value';

    public function enqueue()
    {
        wp_enqueue_script('vshm-range-value-control', vshm()->plugin['URL'] . '/UI/Customizer/Range_Slider_Control.js', ['jquery'], rand(), TRUE);
        wp_enqueue_style('vshm-range-value-control', vshm()->plugin['URL'] . '/UI/Customizer/Range_Slider_Control.css', [], rand());
    }

    public function render_content()
    {
        ?>
        <label>
            <span class="customize-control-title"><?= esc_html($this->label) ?></span>
            <?php if (!empty($this->description)) { ?>
                <span class="description customize-control-description"><?= $this->description ?></span>
            <?php } ?>
            <div class="range-slider" style="width:100%; display:flex;flex-direction: row;justify-content: flex-start;">
				<span class="range-input-container"><input class="range-slider__range" type="range" value="<?= esc_attr($this->value()) ?>"
				<?php
                $this->input_attrs();
                $this->link();
                ?>
				>
				<span class="range-slider__value">0</span></span>
            </div>
        </label>
        <?php
    }
}
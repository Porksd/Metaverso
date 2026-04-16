<?php

namespace VSHM\UI\Customizer;

class Radios_Control extends \WP_Customize_Control
{
    public $type = 'vshm-radios';

    public function enqueue()
    {
        wp_enqueue_style('vshm-radios', vshm()->plugin['URL'] . '/UI/Customizer/Radios_Control.css', [], rand());
    }

    public function render_content()
    {
        ?>
        <label>
            <span class="customize-control-title"><?= esc_html($this->label) ?></span>
            <?php if (!empty($this->description)) { ?>
                <span class="description customize-control-description"><?= $this->description ?></span>
            <?php } ?>
            <div class="vshm-radios mode-<?= $this->type ?>">
                <?php foreach ($this->choices as $value => $label) { ?>
                    <div class="vshm-radios__option">
                        <label class="vshm-radios__option__label" style="<?= isset($this->input_attrs[ $value ]) ? esc_attr($this->input_attrs[ $value ]) : '' ?>">
                            <input
                                    type="radio"
                                    name="<?= $this->id ?>"
                                    value="<?= esc_attr($value) ?>"
                                <?= $this->link() ?>
                                <?php checked($this->value(), $value); ?>
                            />
                            <span class="vshm-radios__option__selector"></span>
                            <?php if ($label && $this->type === 'vshm-radios') { ?>
                                <span class="vshm-radios__option__label__content"><?= $label ?></span>
                            <?php } ?>
                        </label>
                        <?php if ($label && $this->type !== 'vshm-radios') { ?>
                            <div class="vshm-radios__option__label__content"><?= $label ?></div>
                        <?php } ?>
                    </div>

                <?php } ?>
            </div>
        </label>
        <?php
    }
}